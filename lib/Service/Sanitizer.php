<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\PostIt\Service;

use DOMDocument;
use DOMElement;
use DOMNode;

/** Allow-list HTML sanitizer for note content. */
class Sanitizer {
	private const TAGS = ['p', 'br', 'div', 'span', 'b', 'strong', 'i', 'em', 'u', 's', 'strike', 'ul', 'ol', 'li',
		'h1', 'h2', 'h3', 'blockquote', 'code', 'pre', 'a', 'img', 'hr'];
	private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'form', 'input', 'button', 'textarea', 'select', 'link', 'meta', 'base', 'template', 'noscript'];
	private const CSS = ['color', 'background-color', 'font-size', 'text-align', 'font-weight', 'font-style', 'text-decoration'];
	public const MAX = 200000;

	public function clean(?string $html): string {
		$html = (string)$html;
		if (strlen($html) > self::MAX) $html = substr($html, 0, self::MAX);
		if (trim($html) === '') return '';
		$doc = new DOMDocument();
		$prev = libxml_use_internal_errors(true);
		$doc->loadHTML('<?xml encoding="utf-8"?><div id="postit-root">' . $html . '</div>', LIBXML_NONET | LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
		libxml_clear_errors(); libxml_use_internal_errors($prev);
		$root = $doc->getElementById('postit-root') ?? $doc->documentElement;
		if (!$root) return '';
		$this->walk($root);
		$out = '';
		foreach (iterator_to_array($root->childNodes) as $c) $out .= $doc->saveHTML($c);
		return $out;
	}
	private function walk(DOMNode $node): void {
		foreach (iterator_to_array($node->childNodes) as $c) {
			if ($c->nodeType === XML_COMMENT_NODE || $c->nodeType === XML_PI_NODE || $c->nodeType === XML_CDATA_SECTION_NODE) { $node->removeChild($c); continue; }
			if (!($c instanceof DOMElement)) continue;
			$tag = strtolower($c->tagName);
			if (in_array($tag, self::DROP, true)) { $node->removeChild($c); continue; }
			if (!in_array($tag, self::TAGS, true)) { // unwrap unknown tags, keep their text
				$this->walk($c);
				while ($c->firstChild) $node->insertBefore($c->firstChild, $c);
				$node->removeChild($c);
				continue;
			}
			$this->attrs($c, $tag);
			if ($tag === 'img' && !$c->hasAttribute('src')) { $node->removeChild($c); continue; }
			$this->walk($c);
		}
	}
	private function attrs(DOMElement $e, string $tag): void {
		$keep = [];
		foreach (iterator_to_array($e->attributes) as $a) $keep[strtolower($a->name)] = $a->value;
		foreach (array_keys($keep) as $n) $e->removeAttribute($n);
		if (isset($keep['style']) && ($s = $this->style($keep['style'])) !== '') $e->setAttribute('style', $s);
		if ($tag === 'a' && isset($keep['href']) && preg_match('#^(https?://|mailto:)#i', trim($keep['href']))) {
			$e->setAttribute('href', trim($keep['href'])); $e->setAttribute('target', '_blank'); $e->setAttribute('rel', 'noopener noreferrer nofollow');
		}
		if ($tag === 'img' && isset($keep['src'])) {
			$src = trim($keep['src']);
			// Only same-origin note images served by this app.
			if (preg_match('#^/[A-Za-z0-9._/-]*?/apps/postit/img/[a-f0-9]{32}\.(png|jpg|gif|webp)$#', $src)) {
				$e->setAttribute('src', $src);
				if (isset($keep['alt'])) $e->setAttribute('alt', mb_substr($keep['alt'], 0, 200));
				if (isset($keep['width']) && ctype_digit($keep['width'])) $e->setAttribute('width', $keep['width']);
			}
		}
	}
	private function style(string $s): string {
		$out = [];
		foreach (explode(';', $s) as $decl) {
			if (!str_contains($decl, ':')) continue;
			[$k, $v] = array_map('trim', explode(':', $decl, 2));
			$k = strtolower($k);
			if (!in_array($k, self::CSS, true)) continue;
			if (preg_match('/url\s*\(|expression|javascript:|[<>\\\\]/i', $v)) continue;
			if (!preg_match('/^[#a-zA-Z0-9 ,.%()-]{1,64}$/', $v)) continue;
			$out[] = $k . ': ' . $v;
		}
		return implode('; ', $out);
	}
}
