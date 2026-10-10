<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Licencia del sistema: un código firmado por la plataforma de licencias
 * (RCS1.<datos>.<firma>) que se pega en /licencia. Se comprueba sin internet con la
 * clave pública; además, cada pocas horas consulta a la plataforma para enterarse de
 * renovaciones (la fecha se actualiza sola) o revocaciones.
 */
class License
{
    public const ACTIVE = 'active';

    public const GRACE = 'grace';

    public const EXPIRED = 'expired';

    public const REVOKED = 'revoked';

    public const WRONG_DOMAIN = 'wrong_domain';

    public const INVALID = 'invalid';

    public const MISSING = 'missing';

    /**
     * Datos de un código si la firma es válida; null si está alterado o no es un código.
     *
     * @return array{id: string, client: string, plan: string, iat: int, exp: int, domain: ?string, url: ?string}|null
     */
    public static function parse(string $code, ?string $publicKey = null): ?array
    {
        $parts = explode('.', preg_replace('/\s+/', '', $code));

        if (count($parts) !== 3 || $parts[0] !== 'RCS1') {
            return null;
        }

        $payload = self::decode($parts[1]);
        $signature = self::decode($parts[2]);
        $key = base64_decode((string) ($publicKey ?? config('license.public_key')), true);

        if ($payload === null || $signature === null || $key === false || strlen($key) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
            || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES || ! sodium_crypto_sign_verify_detached($signature, $payload, $key)) {
            return null;
        }

        $data = json_decode($payload, true);

        if (! is_array($data) || ($data['v'] ?? null) !== 1 || ! isset($data['id'], $data['exp']) || ! is_int($data['exp'])) {
            return null;
        }

        return [
            'id' => (string) $data['id'],
            'client' => (string) ($data['client'] ?? ''),
            'plan' => (string) ($data['plan'] ?? ''),
            'iat' => (int) ($data['iat'] ?? 0),
            'exp' => $data['exp'],
            'domain' => ! empty($data['domain']) ? strtolower((string) $data['domain']) : null,
            'url' => ! empty($data['url']) ? (string) $data['url'] : null,
        ];
    }

    /**
     * Estado de una licencia ya verificada.
     *
     * @return array{state: string, days_left: int, expires_at: int, grace_ends_at: int}
     */
    public static function evaluate(array $payload, bool $revoked, ?string $host, ?int $now = null, ?int $graceDays = null): array
    {
        $now ??= time();
        $graceDays ??= (int) config('license.grace_days', 3);
        $graceEnds = $payload['exp'] + $graceDays * 86400;

        $state = match (true) {
            $revoked => self::REVOKED,
            $payload['domain'] !== null && $host !== null && ! self::hostMatches($host, $payload['domain']) => self::WRONG_DOMAIN,
            $now <= $payload['exp'] => self::ACTIVE,
            $now <= $graceEnds => self::GRACE,
            default => self::EXPIRED,
        };

        return [
            'state' => $state,
            'days_left' => (int) floor(($payload['exp'] - $now) / 86400),
            'expires_at' => $payload['exp'],
            'grace_ends_at' => $graceEnds,
        ];
    }

    public static function hostMatches(string $host, string $domain): bool
    {
        $host = strtolower($host);

        return $host === $domain || str_ends_with($host, '.' . $domain);
    }

    /**
     * Estado actual del sistema (lee el código guardado y lo verifica).
     *
     * @return array{state: string, license: ?array, days_left: int, expires_at: int, grace_ends_at: int}
     */
    public static function current(): array
    {
        $none = ['license' => null, 'days_left' => 0, 'expires_at' => 0, 'grace_ends_at' => 0];
        $code = self::storedCode();

        if ($code === null) {
            return ['state' => self::MISSING] + $none;
        }

        $payload = self::parse($code);

        if ($payload === null) {
            return ['state' => self::INVALID] + $none;
        }

        $host = app()->runningInConsole() ? null : request()->getHost();

        return self::evaluate($payload, self::state()['revoked'] ?? false, $host) + ['license' => $payload];
    }

    public static function allows(): bool
    {
        return in_array(self::current()['state'], [self::ACTIVE, self::GRACE], true);
    }

    /**
     * Guarda un código nuevo (lo que se pega en /licencia).
     *
     * @throws ValidationException
     */
    public static function activate(string $code): array
    {
        $payload = self::parse($code);

        if ($payload === null) {
            throw ValidationException::withMessages(['code' => 'El código no es válido. Cópialo completo, tal como lo entrega la plataforma de licencias.']);
        }

        if ($payload['exp'] + (int) config('license.grace_days', 3) * 86400 < time()) {
            throw ValidationException::withMessages(['code' => 'Esa licencia ya venció el ' . date('d/m/Y', $payload['exp']) . '. Pide un código renovado.']);
        }

        if ($payload['domain'] !== null && ! app()->runningInConsole() && ! self::hostMatches(request()->getHost(), $payload['domain'])) {
            throw ValidationException::withMessages(['code' => 'Esa licencia es para el dominio ' . $payload['domain'] . ', no para este sitio.']);
        }

        self::storeCode(preg_replace('/\s+/', '', $code));
        self::saveState(['checked_at' => 0, 'revoked' => false]);
        self::syncStorage();

        return $payload;
    }

    /**
     * Pide a la plataforma las credenciales del bucket R2 de este cliente (si tiene uno)
     * y las guarda. Sin internet o sin bucket no cambia nada.
     */
    public static function syncStorage(): bool
    {
        $code = self::storedCode();
        $payload = $code ? self::parse($code) : null;
        $parts = $payload && $payload['url'] ? parse_url($payload['url']) : false;

        if ($payload === null || ! $parts || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {
            return false;
        }

        parse_str($parts['query'] ?? '', $query);
        $query['r'] = 'storage';
        $endpoint = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '') . ($parts['path'] ?? '/') . '?' . http_build_query($query);

        try {
            $data = Http::timeout(6)->connectTimeout(3)->acceptJson()->asForm()->post($endpoint, ['token' => $code])->json();
        } catch (Throwable) {
            return false;
        }

        if (! is_array($data) || ($data['status'] ?? null) !== 'ok') {
            return false;
        }

        foreach (['endpoint', 'bucket', 'key', 'secret'] as $field) {
            if (empty($data[$field]) || ! is_string($data[$field])) {
                return false;
            }
        }

        if (! preg_match('#^https://[a-z0-9]+(\.eu|\.fedramp)?\.r2\.cloudflarestorage\.com$#', $data['endpoint'])) {
            return false;
        }

        R2::save(['endpoint' => $data['endpoint'], 'bucket' => $data['bucket'], 'key' => $data['key'], 'secret' => $data['secret']]);

        return true;
    }

    /**
     * Pregunta a la plataforma si hay renovación o revocación. Sin internet o con
     * error, no cambia nada (la licencia sigue valiendo hasta su fecha).
     *
     * @return bool true si se pudo consultar
     */
    public static function checkOnline(): bool
    {
        $payload = ($code = self::storedCode()) ? self::parse($code) : null;
        $url = $payload['url'] ?? null;

        if ($payload === null || ! $url || ! preg_match('#^https?://#i', $url)) {
            return false;
        }

        $state = self::state();
        $state['checked_at'] = time();
        self::saveState($state);

        try {
            // Built by hand: passing the query as an argument would replace the one already in the URL.
            $response = Http::timeout(4)->connectTimeout(3)->acceptJson()
                ->get($url . (str_contains($url, '?') ? '&' : '?') . http_build_query(['id' => $payload['id']]));
        } catch (Throwable) {
            return false;
        }

        $data = $response->json();

        if (! is_array($data) || ! isset($data['status'])) {
            return false;
        }

        if ($data['status'] === 'revoked') {
            $state['revoked'] = true;
            self::saveState($state);

            return true;
        }

        if (in_array($data['status'], ['active', 'expired'], true)) {
            $state['revoked'] = false;
            self::saveState($state);

            // A renewal: the same license with a later date, signed by the platform.
            $renewed = is_string($data['token'] ?? null) ? self::parse($data['token']) : null;

            if ($renewed !== null && $renewed['id'] === $payload['id'] && $renewed['exp'] > $payload['exp']) {
                self::storeCode(preg_replace('/\s+/', '', $data['token']));
            }

            // Keeps the bucket's credential current (it changes when the provider rotates it).
            self::syncStorage();
        }

        return true;
    }

    /** ¿Toca consultar a la plataforma? Cada `check_minutes`; cada 2 minutos mientras la licencia no esté vigente (para recoger una activación o renovación). */
    public static function isCheckDue(): bool
    {
        if (self::storedCode() === null) {
            return false;
        }

        $interval = (self::allows() ? max((int) config('license.check_minutes', 30), 1) : 2) * 60;

        return time() - (int) (self::state()['checked_at'] ?? 0) >= $interval;
    }

    /** Momento de la última consulta a la plataforma (timestamp), o null si nunca se hizo. */
    public static function lastCheckedAt(): ?int
    {
        $at = (int) (self::state()['checked_at'] ?? 0);

        return $at > 1 ? $at : null;
    }

    /** Anota que se va a consultar, para que peticiones simultáneas no repitan la consulta. */
    public static function markChecked(): void
    {
        self::saveState(['checked_at' => time()] + self::state());
    }

    /**
     * Qué debe mostrar el aviso del panel: null si todo está en orden.
     *
     * @return array{level: string, message: string}|null
     */
    public static function notice(): ?array
    {
        $license = self::current();
        $date = fn (int $ts) => date('d/m/Y', $ts);

        return match (true) {
            in_array($license['state'], [self::MISSING, self::INVALID], true) => ['level' => 'danger', 'message' => 'Este tenant no está activado. Activa tu licencia para dejar de ver este aviso.'],
            $license['state'] === self::REVOKED => ['level' => 'danger', 'message' => 'La licencia de este tenant fue suspendida o ya no existe. Debes activarla de nuevo.'],
            $license['state'] === self::WRONG_DOMAIN => ['level' => 'danger', 'message' => 'Esta licencia es de otro dominio: este tenant no está activado. Activa la licencia correcta.'],
            $license['state'] === self::EXPIRED => ['level' => 'danger', 'message' => 'La licencia de este tenant venció el ' . $date($license['expires_at']) . '. Debes renovarla y activarla.'],
            $license['state'] === self::GRACE => ['level' => 'warning', 'message' => 'La licencia venció el ' . $date($license['expires_at']) . '. Renuévala y actívala para quitar este aviso.'],
            $license['state'] === self::ACTIVE && $license['days_left'] <= 7 => ['level' => 'warning', 'message' => 'La licencia vence en ' . max($license['days_left'], 0) . ' ' . ($license['days_left'] === 1 ? 'día' : 'días') . ' (' . $date($license['expires_at']) . ').'],
            default => null,
        };
    }

    // ---- almacenamiento: archivos en storage/app (no dependen de la base de datos) ----

    protected static function codePath(): string
    {
        return storage_path('app/license.key');
    }

    protected static function statePath(): string
    {
        return storage_path('app/license-state.json');
    }

    protected static function storedCode(): ?string
    {
        $code = is_file(self::codePath()) ? trim((string) file_get_contents(self::codePath())) : '';

        return $code !== '' ? $code : null;
    }

    protected static function storeCode(string $code): void
    {
        file_put_contents(self::codePath(), $code, LOCK_EX);
    }

    /** @return array{checked_at?: int, revoked?: bool} */
    protected static function state(): array
    {
        $data = is_file(self::statePath()) ? json_decode((string) file_get_contents(self::statePath()), true) : null;

        return is_array($data) ? $data : [];
    }

    protected static function saveState(array $state): void
    {
        file_put_contents(self::statePath(), json_encode($state), LOCK_EX);
    }

    protected static function decode(string $text): ?string
    {
        $raw = base64_decode(strtr($text, '-_', '+/'), true);

        return $raw === false ? null : $raw;
    }
}
