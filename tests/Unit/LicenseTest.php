<?php

namespace Tests\Unit;

use App\Support\License;
use PHPUnit\Framework\TestCase;

class LicenseTest extends TestCase
{
    private string $secret;

    private string $public;

    protected function setUp(): void
    {
        $pair = sodium_crypto_sign_keypair();
        $this->secret = sodium_crypto_sign_secretkey($pair);
        $this->public = base64_encode(sodium_crypto_sign_publickey($pair));
    }

    private function code(array $override = []): string
    {
        $payload = json_encode($override + [
            'v' => 1, 'id' => 'LIC-TEST-0000-0001', 'client' => 'Cliente', 'plan' => 'annual',
            'iat' => 1_000_000, 'exp' => 2_000_000, 'domain' => null, 'url' => 'https://licencias.test/index.php?r=api',
        ]);
        $b64 = fn (string $raw) => rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

        return 'RCS1.' . $b64($payload) . '.' . $b64(sodium_crypto_sign_detached($payload, $this->secret));
    }

    public function test_valid_code_is_parsed(): void
    {
        $payload = License::parse($this->code(), $this->public);

        $this->assertSame('LIC-TEST-0000-0001', $payload['id']);
        $this->assertSame('Cliente', $payload['client']);
        $this->assertSame(2_000_000, $payload['exp']);
        $this->assertNull($payload['domain']);
    }

    public function test_pasted_code_with_line_breaks_still_works(): void
    {
        $code = chunk_split($this->code(), 40, "\r\n");

        $this->assertNotNull(License::parse($code, $this->public));
    }

    public function test_a_code_signed_with_another_key_is_rejected(): void
    {
        $other = base64_encode(sodium_crypto_sign_publickey(sodium_crypto_sign_keypair()));

        $this->assertNull(License::parse($this->code(), $other));
    }

    public function test_changing_the_expiry_breaks_the_signature(): void
    {
        [$prefix, $payload, $signature] = explode('.', $this->code());
        $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        $data['exp'] = 9_999_999_999;
        $forged = $prefix . '.' . rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=') . '.' . $signature;

        $this->assertNull(License::parse($forged, $this->public));
    }

    public function test_garbage_is_rejected(): void
    {
        foreach (['', 'hola', 'RCS1.a.b', 'RCS2.a.b.c', 'RCS1....'] as $garbage) {
            $this->assertNull(License::parse($garbage, $this->public), $garbage);
        }
    }

    public function test_states_around_the_expiry_date(): void
    {
        $payload = License::parse($this->code(), $this->public);
        $day = 86400;

        $this->assertSame('active', License::evaluate($payload, false, null, 2_000_000 - 5 * $day, 3)['state']);
        $this->assertSame(5, License::evaluate($payload, false, null, 2_000_000 - 5 * $day, 3)['days_left']);
        $this->assertSame('active', License::evaluate($payload, false, null, 2_000_000, 3)['state']);
        $this->assertSame('grace', License::evaluate($payload, false, null, 2_000_000 + 2 * $day, 3)['state']);
        $this->assertSame('expired', License::evaluate($payload, false, null, 2_000_000 + 4 * $day, 3)['state']);
    }

    public function test_revoked_wins_over_everything(): void
    {
        $payload = License::parse($this->code(), $this->public);

        $this->assertSame('revoked', License::evaluate($payload, true, null, 1_500_000, 3)['state']);
    }

    public function test_domain_lock(): void
    {
        $payload = License::parse($this->code(['domain' => 'micliente.com']), $this->public);

        $this->assertSame('active', License::evaluate($payload, false, 'micliente.com', 1_500_000, 3)['state']);
        $this->assertSame('active', License::evaluate($payload, false, 'app.micliente.com', 1_500_000, 3)['state']);
        $this->assertSame('wrong_domain', License::evaluate($payload, false, 'otro.com', 1_500_000, 3)['state']);
        $this->assertSame('wrong_domain', License::evaluate($payload, false, 'notmicliente.com', 1_500_000, 3)['state']);
    }
}
