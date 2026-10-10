<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Public site content editable from the admin (Configuración → Web pública).
 * Saved values live in `settings` under `site_*`; config/site.php has the defaults
 * a fresh installation starts with.
 */
class SiteContent
{
    public static function name(): string
    {
        return Setting::get('site_name') ?: (string) config('site.name');
    }

    public static function logo(): ?string
    {
        return Media::url(Setting::get('site_logo'));
    }

    public static function favicon(): ?string
    {
        return Media::url(Setting::get('site_favicon'));
    }

    /**
     * @return array{badge: string, title: string, subtitle: string, image: ?string}
     */
    public static function hero(): array
    {
        $defaults = config('site.hero');

        return [
            'badge' => Lang::setting('site_hero_badge', $defaults['badge']),
            'title' => Lang::setting('site_hero_title', $defaults['title']),
            'subtitle' => Lang::setting('site_hero_subtitle', $defaults['subtitle']),
            'image' => Media::url(Setting::get('site_hero_image', $defaults['image'])),
        ];
    }

    /**
     * Phone, WhatsApp, e-mail, address and opening hours; `phone_href` is the
     * phone number ready for a tel: link.
     *
     * @return array{phone: string, phone_href: string, whatsapp: string, email: string, address: string, hours: string}
     */
    public static function contact(): array
    {
        $defaults = config('site.contact');
        $phone = (string) Setting::get('site_phone', $defaults['phone']);

        return [
            'phone' => $phone,
            'phone_href' => static::telephone($phone),
            'whatsapp' => preg_replace('/\D+/', '', (string) Setting::get('site_whatsapp', $defaults['whatsapp'])),
            'email' => (string) Setting::get('site_email', $defaults['email']),
            'address' => (string) Setting::get('site_address', $defaults['address']),
            'hours' => (string) Lang::setting('site_hours', $defaults['hours']),
        ];
    }

    /**
     * @return array{facebook: string, instagram: string}
     */
    public static function social(): array
    {
        $defaults = config('site.social');

        return [
            'facebook' => (string) Setting::get('site_facebook', $defaults['facebook']),
            'instagram' => (string) Setting::get('site_instagram', $defaults['instagram']),
        ];
    }

    /**
     * @return array{title: string, text: string, stats: array<int, array{number: string, label: string, image: ?string}>}
     */
    public static function about(): array
    {
        $defaults = config('site.about');

        return [
            'title' => Lang::setting('site_about_title', $defaults['title']),
            'text' => Lang::setting('site_about_text', $defaults['text']),
            'stats' => collect($defaults['stats'])->map(fn (array $stat, int $i) => [
                'number' => (string) Setting::get('site_about_stat_' . ($i + 1) . '_number', $stat['number']),
                'label' => (string) Lang::setting('site_about_stat_' . ($i + 1) . '_label', $stat['label']),
                'image' => Media::url(Setting::get('site_about_stat_' . ($i + 1) . '_image', $stat['image'])),
            ])->all(),
        ];
    }

    public static function copyright(): string
    {
        return (string) Lang::setting('site_copyright') ?: static::name() . '. ' . __('Todos los derechos reservados.');
    }

    /**
     * Where "ask us" buttons lead: WhatsApp when there is a number, e-mail otherwise.
     */
    public static function contactUrl(?string $message = null): ?string
    {
        $contact = static::contact();

        if ($contact['whatsapp']) {
            return 'https://wa.me/' . $contact['whatsapp'] . ($message ? '?text=' . rawurlencode($message) : '');
        }

        return $contact['email'] ? 'mailto:' . $contact['email'] : null;
    }

    /**
     * Main colour of the public site as #rrggbb.
     */
    public static function brandColor(): string
    {
        $color = (string) Setting::get('site_brand_color', config('site.brand_color'));

        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtolower($color) : '#2563eb';
    }

    /**
     * The brand colour, darker, for hover states.
     */
    public static function brandColorDark(): string
    {
        [$r, $g, $b] = sscanf(static::brandColor(), '#%02x%02x%02x');

        return sprintf('#%02x%02x%02x', (int) ($r * 0.82), (int) ($g * 0.82), (int) ($b * 0.82));
    }

    protected static function telephone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        return $digits === '' ? '' : (str_starts_with(trim($phone), '+') ? '+' : '') . $digits;
    }
}
