<?php

namespace App\Services;

class HtmlSanitizerService
{
    public function sanitize(string $html): string
    {
        libxml_use_internal_errors(true);

        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        foreach (['script', 'style', 'iframe', 'object', 'embed', 'link', 'meta'] as $tag) {
            $this->removeNodes($document, $tag);
        }

        $this->removeDangerousAttributes($document);

        $cleaned = $document->saveHTML();

        libxml_clear_errors();

        return $cleaned ?: ''; 
    }

    protected function removeNodes(\DOMDocument $document, string $tagName): void
    {
        $nodes = $document->getElementsByTagName($tagName);
        for ($i = $nodes->length - 1; $i >= 0; $i--) {
            $node = $nodes->item($i);
            if ($node) {
                $node->parentNode->removeChild($node);
            }
        }
    }

    protected function removeDangerousAttributes(\DOMDocument $document): void
    {
        $xpath = new \DOMXPath($document);
        $nodes = $xpath->query('//*');

        foreach ($nodes as $node) {
            if (! $node instanceof \DOMElement || ! $node->hasAttributes()) {
                continue;
            }

            $attributes = [];
            foreach ($node->attributes as $attribute) {
                $attributes[] = $attribute->name;
            }

            foreach ($attributes as $name) {
                $value = $node->getAttribute($name);

                if (str_starts_with(strtolower($name), 'on')) {
                    $node->removeAttribute($name);
                    continue;
                }

                if (in_array(strtolower($name), ['srcdoc', 'formaction', 'xlink:href', 'data', 'dynsrc'], true)) {
                    $node->removeAttribute($name);
                    continue;
                }

                if (in_array(strtolower($name), ['href', 'src'], true) && preg_match('/^\s*(javascript|data):/i', $value)) {
                    $node->removeAttribute($name);
                }
            }
        }
    }
}
