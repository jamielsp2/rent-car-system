<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Cleans HTML written in the editor (blog posts, FAQ answers) before it is
 * shown on the public site: formatting tags only, no scripts or handlers.
 */
class Html
{
    protected const TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'h2', 'h3', 'h4',
        'blockquote', 'a', 'img', 'figure', 'figcaption', 'span',
    ];

    protected const ATTRIBUTES = ['a' => ['href'], 'img' => ['src', 'alt']];

    public static function clean(?string $html): string
    {
        if (trim((string) $html) === '') {
            return '';
        }

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $root = $doc->getElementsByTagName('div')->item(0);
        static::walk($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    protected static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                if ($child->nodeType === XML_COMMENT_NODE) {
                    $node->removeChild($child);
                }

                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form'], true)) {
                $node->removeChild($child);

                continue;
            }

            static::walk($child);

            if (! in_array($tag, self::TAGS, true)) {
                // Unknown wrapper: keep its content, drop the tag.
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            $allowed = self::ATTRIBUTES[$tag] ?? [];

            foreach (iterator_to_array($child->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                $value = trim($attribute->value);

                if (! in_array($name, $allowed, true) || (in_array($name, ['href', 'src'], true) && ! static::safeUrl($value))) {
                    $child->removeAttribute($attribute->name);
                }
            }

            if ($tag === 'img') {
                $child->setAttribute('src', Media::url($child->getAttribute('src')) ?? '');
                $child->setAttribute('loading', 'lazy');
            }

            if ($tag === 'a' && str_starts_with($child->getAttribute('href'), 'http')) {
                $child->setAttribute('rel', 'noopener nofollow');
            }
        }
    }

    protected static function safeUrl(string $url): bool
    {
        return ! preg_match('/^\s*(javascript|data|vbscript):/i', $url);
    }
}
