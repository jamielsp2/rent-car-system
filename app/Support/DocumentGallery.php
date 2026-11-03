<?php

namespace App\Support;

use App\Models\Customer;
use Illuminate\Support\HtmlString;

/**
 * Preview thumbnails of a customer's identity photos (front and back). Clicking one
 * opens a viewer on the same page: a close button (X), arrows to move between the
 * photos, a counter, and Esc or a click on the dark background to close.
 *
 * The photos live on the private disk and are streamed by the staff-only route
 * `admin.customers.document`.
 */
class DocumentGallery
{
    public const VIEWER_JS = <<<'JS'
window.vydDocs = function (urls, start) {
    var i = start;
    var o = document.createElement('div');
    o.setAttribute('style', 'position:fixed;inset:0;background:rgba(0,0,0,.85);display:flex;align-items:center;justify-content:center;z-index:99999;');
    var btn = 'position:absolute;background:rgba(255,255,255,.15);color:#fff;border:0;border-radius:999px;width:48px;height:48px;font-size:28px;line-height:1;cursor:pointer;';
    o.innerHTML =
        '<button type="button" aria-label="Cerrar" style="' + btn + 'top:16px;right:16px;font-size:34px;">&times;</button>' +
        '<button type="button" aria-label="Anterior" style="' + btn + 'left:16px;top:50%;margin-top:-24px;">&lsaquo;</button>' +
        '<img alt="" style="max-width:88vw;max-height:84vh;border-radius:8px;background:#fff;">' +
        '<button type="button" aria-label="Siguiente" style="' + btn + 'right:16px;top:50%;margin-top:-24px;">&rsaquo;</button>' +
        '<div style="position:absolute;bottom:16px;color:#fff;font-size:14px;"></div>';
    document.body.appendChild(o);

    var img = o.querySelector('img');
    var counter = o.lastChild;
    var buttons = o.querySelectorAll('button');
    var multiple = urls.length > 1;
    buttons[1].style.display = multiple ? '' : 'none';
    buttons[2].style.display = multiple ? '' : 'none';

    function show() {
        img.src = urls[i];
        counter.textContent = multiple ? (i + 1) + ' / ' + urls.length : '';
    }
    function go(step) {
        i = (i + step + urls.length) % urls.length;
        show();
    }
    function close() {
        document.removeEventListener('keydown', onKey);
        o.remove();
    }
    function onKey(e) {
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowRight') go(1);
        if (e.key === 'ArrowLeft') go(-1);
    }

    buttons[0].onclick = close;
    buttons[1].onclick = function () { go(-1); };
    buttons[2].onclick = function () { go(1); };
    o.onclick = function (e) { if (e.target === o) close(); };
    document.addEventListener('keydown', onKey);
    show();
};
JS;

    /**
     * Thumbnails of the saved photos, or null when there are none.
     */
    public static function for(Customer $record): ?HtmlString
    {
        $photos = collect(['front' => 'Frente', 'back' => 'Reverso'])
            ->filter(fn (string $label, string $side) => filled($record->{"document_{$side}_path"}));

        if ($photos->isEmpty()) {
            return null;
        }

        $urls = $photos->keys()
            ->map(fn (string $side) => route('admin.customers.document', [$record, $side]))
            ->values()
            ->all();
        $list = e(json_encode($urls));

        $html = '<div style="display:flex;gap:14px;flex-wrap:wrap;">';

        foreach ($photos->values()->all() as $index => $label) {
            $html .= '<figure style="margin:0;text-align:center;">'
                . '<img src="' . e($urls[$index]) . '" alt="' . e($label) . '" loading="lazy"'
                . ' onclick="vydDocs(' . $list . ',' . $index . ')"'
                . ' style="height:120px;width:auto;border-radius:6px;border:1px solid #e5e7eb;background:#f9fafb;cursor:zoom-in;">'
                . '<figcaption style="font-size:12px;color:#6b7280;margin-top:4px;">' . e($label) . '</figcaption>'
                . '</figure>';
        }

        return new HtmlString($html . '</div><script>' . static::VIEWER_JS . '</script>');
    }
}
