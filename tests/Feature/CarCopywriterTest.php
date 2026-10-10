<?php

namespace Tests\Feature;

use App\Services\CarCopywriter;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class CarCopywriterTest extends TestCase
{
    public function test_generates_summary_and_clean_content(): void
    {
        config(['services.groq.key' => 'test-key']);
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'summary' => '<b>SUV</b> cómoda para toda la familia.',
            'content' => '<h2>x</h2><p>Hola</p><script>alert(1)</script>',
        ])]]]])]);

        $text = app(CarCopywriter::class)->generate(['name' => 'Toyota RAV4 2023', 'year' => 2023]);

        $this->assertSame('SUV cómoda para toda la familia.', $text['summary']);
        $this->assertStringNotContainsString('<script', $text['content']);
        $this->assertStringContainsString('<p>Hola</p>', $text['content']);
        Http::assertSent(fn ($request) => str_contains($request['messages'][1]['content'], 'Toyota RAV4 2023') && $request->hasHeader('Authorization', 'Bearer test-key'));
    }

    public function test_requires_a_name_and_a_key(): void
    {
        config(['services.groq.key' => 'test-key']);
        $this->expectException(RuntimeException::class);
        app(CarCopywriter::class)->generate(['year' => 2023]);
    }

    public function test_unavailable_without_key(): void
    {
        config(['services.groq.key' => null]);
        $this->assertFalse(CarCopywriter::available());
    }
}
