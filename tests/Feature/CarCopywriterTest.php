<?php

namespace Tests\Feature;

use App\Services\CarCopywriter;
use App\Services\Translator;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class CarCopywriterTest extends TestCase
{
    protected function fakeGroq(array $answer): void
    {
        config(['services.groq.key' => 'test-key']);
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode($answer)]]]])]);
    }

    public function test_generates_summary_and_clean_content_in_both_languages(): void
    {
        $this->fakeGroq([
            'es' => ['summary' => '<b>SUV</b> cómoda para toda la familia.', 'content' => '<h2>x</h2><p>Hola</p><script>alert(1)</script>'],
            'en' => ['summary' => 'A comfortable SUV for the whole family.', 'content' => '<p>Hello</p>'],
        ]);

        $text = app(CarCopywriter::class)->generate(['name' => 'Toyota RAV4 2023', 'year' => 2023]);

        $this->assertSame('SUV cómoda para toda la familia.', $text['summary']);
        $this->assertStringNotContainsString('<script', $text['content']);
        $this->assertStringContainsString('<p>Hola</p>', $text['content']);
        $this->assertSame('A comfortable SUV for the whole family.', $text['en']['summary']);
        $this->assertSame('<p>Hello</p>', $text['en']['content']);
        Http::assertSent(fn ($request) => str_contains($request['messages'][1]['content'], 'Toyota RAV4 2023') && $request->hasHeader('Authorization', 'Bearer test-key'));
    }

    public function test_requires_a_name_and_both_languages(): void
    {
        $this->fakeGroq(['es' => ['summary' => 'a', 'content' => '<p>b</p>']]);

        try {
            app(CarCopywriter::class)->generate(['year' => 2023]);
            $this->fail('A name is required.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }

        $this->expectException(RuntimeException::class);
        app(CarCopywriter::class)->generate(['name' => 'Toyota RAV4']);
    }

    public function test_unavailable_without_key(): void
    {
        config(['services.groq.key' => null]);
        $this->assertFalse(CarCopywriter::available());
    }

    public function test_translator_keeps_the_requested_keys_and_cleans_html(): void
    {
        $this->fakeGroq(['name' => 'Economy sedan', 'content' => '<p>Hi</p><script>x</script>', 'extra' => 'ignored']);

        $result = app(Translator::class)->translate(['name' => 'Sedán económico', 'content' => '<p>Hola</p>', 'empty' => '  ']);

        $this->assertSame(['name' => 'Economy sedan', 'content' => '<p>Hi</p>x'], $result);
        Http::assertSent(fn ($request) => str_contains($request['messages'][0]['content'], 'English') && ! str_contains($request['messages'][1]['content'], 'empty'));
    }
}
