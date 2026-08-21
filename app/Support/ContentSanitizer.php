<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class ContentSanitizer
{
    private const ALLOWED_TAGS = ['p', 'br', 'strong', 'em', 'u', 'ol', 'ul', 'li', 'blockquote', 'h2', 'h3', 'h4', 'a', 'code', 'pre', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'img'];
    private const ALLOWED_ATTRIBUTES = ['a' => ['href', 'title', 'target', 'rel'], 'img' => ['src', 'alt', 'title', 'width', 'height']];

    public function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') return '';

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementsByTagName('div')->item(0);
        if (!$root) return strip_tags($html);
        $this->sanitizeChildren($root);

        $output = '';
        foreach ($root->childNodes as $child) $output .= $document->saveHTML($child);
        return trim($output);
    }

    private function sanitizeChildren(DOMNode $parent): void
    {
        for ($node = $parent->firstChild; $node;) {
            $next = $node->nextSibling;
            if ($node instanceof DOMElement) {
                $tag = strtolower($node->tagName);
                if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                    if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form'], true)) $parent->removeChild($node);
                    else { while ($node->firstChild) $parent->insertBefore($node->firstChild, $node); $parent->removeChild($node); }
                } else {
                    $this->sanitizeAttributes($node, $tag);
                    $this->sanitizeChildren($node);
                }
            }
            $node = $next;
        }
    }

    private function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $allowed = self::ALLOWED_ATTRIBUTES[$tag] ?? [];
        for ($index = $element->attributes->length - 1; $index >= 0; $index--) {
            $attribute = $element->attributes->item($index);
            if (!$attribute || !in_array(strtolower($attribute->name), $allowed, true)) $element->removeAttributeNode($attribute);
        }
        foreach (['href', 'src'] as $urlAttribute) {
            if (!$element->hasAttribute($urlAttribute)) continue;
            $url = trim($element->getAttribute($urlAttribute));
            if (!preg_match('/^(https?:|mailto:|tel:)/i', $url)) $element->removeAttribute($urlAttribute);
        }
        if ($tag === 'a' && strtolower($element->getAttribute('target')) === '_blank') $element->setAttribute('rel', 'noopener noreferrer');
    }
}
