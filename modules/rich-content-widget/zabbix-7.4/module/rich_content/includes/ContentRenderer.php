<?php declare(strict_types = 0);

/**
 * Renderizador autocontido para Markdown e HTML simples.
 *
 * O conteúdo é sanitizado no backend. Scripts, estilos inline, iframes,
 * manipuladores de eventos e URLs com protocolos perigosos nunca chegam à view.
 */

namespace Modules\RichContent\Includes;

class ContentRenderer {
	private const ALLOWED_TAGS = [
		'a', 'article', 'b', 'blockquote', 'br', 'code', 'dd', 'div', 'dl', 'dt', 'em', 'figcaption',
		'figure', 'footer', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hr', 'i', 'img', 'li',
		'mark', 'ol', 'p', 'pre', 'section', 'small', 'span', 's', 'strong', 'table', 'tbody', 'td',
		'tfoot', 'th', 'thead', 'tr', 'u', 'ul'
	];

	/**
	 * Divide o documento em blocos de grid. O separador funciona nos dois formatos.
	 */
	public static function renderColumns(string $source, int $format): array {
		$parts = preg_split(
			'/^\h*(?:\[\[coluna\]\]|\[\[column\]\]|<!--\h*(?:coluna|column)\h*-->)\h*$/mi',
			str_replace(["\r\n", "\r"], "\n", $source)
		);

		$columns = [];
		foreach ($parts === false ? [$source] : $parts as $part) {
			if (trim($part) === '') {
				continue;
			}

			$columns[] = $format === WidgetForm::FORMAT_HTML
				? self::sanitizeHtml($part)
				: self::renderMarkdown($part);
		}

		return $columns;
	}

	/**
	 * Aceita HTTPS/HTTP, caminhos web locais e data URI de imagem.
	 * Data URIs são limitadas para impedir que o dashboard carregue conteúdo excessivo.
	 */
	public static function sanitizeImageSource(string $source): string {
		$source = trim($source);
		if ($source === '') {
			return '';
		}

		if (preg_match('#^https?://#i', $source) === 1) {
			return filter_var($source, FILTER_VALIDATE_URL) !== false ? $source : '';
		}

		if (preg_match('#^data:image/(png|jpe?g|gif|webp|svg\+xml);base64,([a-z0-9+/=\r\n]+)$#i', $source, $matches) === 1) {
			$binary = base64_decode(preg_replace('/\s+/', '', $matches[2]), true);
			if ($binary === false || strlen($binary) > 1_048_576) {
				return '';
			}

			if (strtolower($matches[1]) === 'svg+xml'
					&& preg_match('/<(?:script|foreignObject)\b|\bon\w+\h*=|(?:href|xlink:href)\h*=\h*["\']\h*(?:javascript:|data:|https?:)/i', $binary) === 1) {
				return '';
			}

			return 'data:image/'.strtolower($matches[1]).';base64,'.base64_encode($binary);
		}

		// Arquivos copiados para o frontend devem ser referenciados por caminho web, nunca por caminho do SO.
		if (preg_match('#^(?!//)(?:/|assets/|modules/)[a-z0-9._~/%?=&+\-]+$#i', $source) === 1
				&& strpos(urldecode($source), '..') === false) {
			return $source;
		}

		return '';
	}

	private static function renderMarkdown(string $markdown): string {
		$lines = explode("\n", $markdown);
		$html = [];
		$count = count($lines);

		for ($index = 0; $index < $count;) {
			$line = rtrim($lines[$index]);

			if (trim($line) === '') {
				$index++;
				continue;
			}

			if (preg_match('/^```([a-z0-9_-]*)\h*$/i', trim($line), $code_match) === 1) {
				$language = strtolower($code_match[1]);
				$code = [];
				$index++;
				while ($index < $count && preg_match('/^```\h*$/', trim($lines[$index])) !== 1) {
					$code[] = $lines[$index++];
				}
				$index += $index < $count ? 1 : 0;
				$class = $language !== '' ? ' class="language-'.htmlspecialchars($language, ENT_QUOTES, 'UTF-8').'"' : '';
				$html[] = '<pre><code'.$class.'>'.htmlspecialchars(implode("\n", $code), ENT_QUOTES, 'UTF-8').'</code></pre>';
				continue;
			}

			if (preg_match('/^(#{1,6})\h+(.+)$/', $line, $heading) === 1) {
				$level = strlen($heading[1]);
				$html[] = '<h'.$level.'>'.self::renderInline(trim($heading[2])).'</h'.$level.'>';
				$index++;
				continue;
			}

			if (preg_match('/^\h*(?:-{3,}|\*{3,}|_{3,})\h*$/', $line) === 1) {
				$html[] = '<hr>';
				$index++;
				continue;
			}

			if ($index + 1 < $count && strpos($line, '|') !== false
					&& self::isTableSeparator($lines[$index + 1])) {
				$headers = self::splitTableRow($line);
				$alignments = self::tableAlignments($lines[$index + 1]);
				$index += 2;
				$rows = [];
				while ($index < $count && trim($lines[$index]) !== '' && strpos($lines[$index], '|') !== false) {
					$rows[] = self::splitTableRow($lines[$index++]);
				}

				$table = '<div class="rc-table-scroll"><table><thead><tr>';
				foreach ($headers as $cell_index => $cell) {
					$table .= '<th'.self::alignmentAttribute($alignments[$cell_index] ?? '').'>'
						.self::renderInline($cell).'</th>';
				}
				$table .= '</tr></thead><tbody>';
				foreach ($rows as $row) {
					$table .= '<tr>';
					foreach ($headers as $cell_index => $_) {
						$table .= '<td'.self::alignmentAttribute($alignments[$cell_index] ?? '').'>'
							.self::renderInline($row[$cell_index] ?? '').'</td>';
					}
					$table .= '</tr>';
				}
				$html[] = $table.'</tbody></table></div>';
				continue;
			}

			if (preg_match('/^\h*>\h?(.*)$/', $line) === 1) {
				$quote = [];
				while ($index < $count && preg_match('/^\h*>\h?(.*)$/', $lines[$index], $match) === 1) {
					$quote[] = $match[1];
					$index++;
				}
				$html[] = '<blockquote>'.self::renderMarkdown(implode("\n", $quote)).'</blockquote>';
				continue;
			}

			if (preg_match('/^\h*([-+*]|\d+[.)])\h+(.+)$/', $line, $list_match) === 1) {
				$ordered = preg_match('/^\d/', $list_match[1]) === 1;
				$tag = $ordered ? 'ol' : 'ul';
				$items = [];
				while ($index < $count
						&& preg_match('/^\h*([-+*]|\d+[.)])\h+(.+)$/', $lines[$index], $item_match) === 1
						&& (preg_match('/^\d/', $item_match[1]) === 1) === $ordered) {
					$items[] = '<li>'.self::renderInline($item_match[2]).'</li>';
					$index++;
				}
				$html[] = '<'.$tag.'>'.implode('', $items).'</'.$tag.'>';
				continue;
			}

			$paragraph = [$line];
			$index++;
			while ($index < $count && trim($lines[$index]) !== '' && !self::startsBlock($lines, $index)) {
				$paragraph[] = rtrim($lines[$index++]);
			}
			$html[] = '<p>'.self::renderInline(implode("\n", $paragraph)).'</p>';
		}

		return implode("\n", $html);
	}

	private static function startsBlock(array $lines, int $index): bool {
		$line = $lines[$index];
		return preg_match('/^\h*(?:#{1,6}\h+|```|>|[-+*]\h+|\d+[.)]\h+|(?:-{3,}|\*{3,}|_{3,})\h*$)/', $line) === 1
			|| ($index + 1 < count($lines) && strpos($line, '|') !== false
				&& self::isTableSeparator($lines[$index + 1]));
	}

	private static function renderInline(string $text): string {
		$tokens = [];
		$prefix = 'RICHCONTENT'.bin2hex(random_bytes(5)).'TOKEN';
		$store = static function(string $html) use (&$tokens, $prefix): string {
			$key = $prefix.count($tokens).'END';
			$tokens[$key] = $html;
			return $key;
		};

		$text = preg_replace_callback('/`([^`]+)`/', static function(array $match) use ($store): string {
			return $store('<code>'.htmlspecialchars($match[1], ENT_QUOTES, 'UTF-8').'</code>');
		}, $text);

		$text = preg_replace_callback(
			'/!\[([^\]]*)\]\(([^)\h]+)(?:\h+["\']([^"\']*)["\'])?\)(?:\{([^}]*)\})?/',
			static function(array $match) use ($store): string {
				$source = self::sanitizeImageSource($match[2]);
				if ($source === '') {
					return $store('<span class="rc-invalid-media">[imagem bloqueada]</span>');
				}

				$attributes = self::markdownImageAttributes($match[4] ?? '');
				return $store('<img class="rc-inline-media" src="'.htmlspecialchars($source, ENT_QUOTES, 'UTF-8').'" alt="'
					.htmlspecialchars($match[1], ENT_QUOTES, 'UTF-8').'" loading="lazy" decoding="async"'.$attributes.'>');
			},
			$text
		);

		$text = preg_replace_callback('/\[([^\]]+)\]\(([^)\h]+)(?:\h+["\']([^"\']*)["\'])?\)/',
			static function(array $match) use ($store): string {
				$url = self::sanitizeLink($match[2]);
				if ($url === '') {
					return $store(htmlspecialchars($match[1], ENT_QUOTES, 'UTF-8'));
				}
				return $store('<a href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'" rel="noopener noreferrer">'
					.htmlspecialchars($match[1], ENT_QUOTES, 'UTF-8').'</a>');
			}, $text);

		$text = htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
		$text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
		$text = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $text);
		$text = preg_replace('/(?<!\*)\*([^*\n]+)\*(?!\*)/', '<em>$1</em>', $text);
		$text = preg_replace('/(?<!_)_([^_\n]+)_(?!_)/', '<em>$1</em>', $text);
		$text = preg_replace('/~~(.+?)~~/s', '<s>$1</s>', $text);
		$text = preg_replace('/\h{2}\n/', '<br>', $text);
		$text = str_replace("\n", ' ', $text);

		return strtr($text, $tokens);
	}

	private static function markdownImageAttributes(string $source): string {
		$options = [];
		preg_match_all('/([a-z-]+)\h*=\h*([^\h]+)/i', $source, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$options[strtolower($match[1])] = trim($match[2], '"\'');
		}

		$attributes = '';
		foreach (['width', 'height'] as $dimension) {
			if (isset($options[$dimension]) && ctype_digit($options[$dimension])) {
				$value = max(1, min(4096, (int) $options[$dimension]));
				$attributes .= ' '.$dimension.'="'.$value.'"';
			}
		}

		$fit = in_array($options['fit'] ?? '', ['contain', 'cover', 'fill', 'none'], true)
			? $options['fit'] : 'contain';
		$rotation = in_array($options['rotate'] ?? '', ['0', '90', '180', '270'], true)
			? $options['rotate'] : '0';
		$aspect = in_array($options['aspect'] ?? '', ['auto', '1/1', '4/3', '16/9', '3/2'], true)
			? $options['aspect'] : 'auto';
		$flip = strtolower($options['flip'] ?? '');

		$attributes .= ' data-fit="'.$fit.'" data-rotation="'.$rotation.'" data-aspect="'.$aspect.'"';
		if (in_array($flip, ['h', 'horizontal', 'both'], true)) {
			$attributes .= ' data-flip-h="1"';
		}
		if (in_array($flip, ['v', 'vertical', 'both'], true)) {
			$attributes .= ' data-flip-v="1"';
		}

		return $attributes;
	}

	private static function sanitizeHtml(string $html): string {
		if (!class_exists('DOMDocument')) {
			return '<p>'.nl2br(htmlspecialchars(strip_tags($html), ENT_QUOTES, 'UTF-8')).'</p>';
		}

		$document = new \DOMDocument('1.0', 'UTF-8');
		$previous = libxml_use_internal_errors(true);
		$loaded = $document->loadHTML(
			'<?xml encoding="UTF-8"><div id="rich-content-sanitizer-root">'.$html.'</div>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		if (!$loaded) {
			return '';
		}

		$root = $document->getElementById('rich-content-sanitizer-root');
		if ($root === null) {
			return '';
		}

		self::sanitizeNode($root);
		$output = '';
		foreach ($root->childNodes as $child) {
			$output .= $document->saveHTML($child);
		}

		return $output;
	}

	private static function sanitizeNode(\DOMNode $node): void {
		$children = [];
		foreach ($node->childNodes as $child) {
			$children[] = $child;
		}

		foreach ($children as $child) {
			if ($child->nodeType === XML_COMMENT_NODE) {
				$child->parentNode->removeChild($child);
				continue;
			}

			if ($child->nodeType !== XML_ELEMENT_NODE) {
				continue;
			}

			self::sanitizeNode($child);
			$tag = strtolower($child->nodeName);
			if (!in_array($tag, self::ALLOWED_TAGS, true)) {
				if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'template'], true)) {
					$child->parentNode->removeChild($child);
				}
				else {
					while ($child->firstChild !== null) {
						$child->parentNode->insertBefore($child->firstChild, $child);
					}
					$child->parentNode->removeChild($child);
				}
				continue;
			}

			self::sanitizeAttributes($child, $tag);
		}
	}

	private static function sanitizeAttributes(\DOMElement $element, string $tag): void {
		$attributes = [];
		foreach ($element->attributes as $attribute) {
			$attributes[] = [$attribute->name, $attribute->value];
		}

		foreach ($attributes as [$name, $value]) {
			$name = strtolower($name);
			$allowed = false;

			if ($name === 'class') {
				$classes = array_filter(preg_split('/\s+/', $value), static function(string $class): bool {
					return preg_match('/^rc-[a-z0-9_-]+$/', $class) === 1;
				});
				$value = implode(' ', $classes);
				$allowed = $value !== '';
			}
			elseif (in_array($name, ['title', 'aria-label'], true)) {
				$allowed = true;
			}
			elseif ($tag === 'a' && $name === 'href') {
				$value = self::sanitizeLink($value);
				$allowed = $value !== '';
			}
			elseif ($tag === 'a' && $name === 'target' && in_array($value, ['_blank', '_self'], true)) {
				$allowed = true;
			}
			elseif ($tag === 'img' && $name === 'src') {
				$value = self::sanitizeImageSource($value);
				$allowed = $value !== '';
			}
			elseif ($tag === 'img' && in_array($name, ['alt', 'loading', 'decoding'], true)) {
				$allowed = true;
			}
			elseif ($tag === 'img' && in_array($name, ['width', 'height'], true) && ctype_digit($value)) {
				$value = (string) max(1, min(4096, (int) $value));
				$allowed = true;
			}
			elseif ($tag === 'img' && $name === 'data-fit'
					&& in_array($value, ['contain', 'cover', 'fill', 'none'], true)) {
				$allowed = true;
			}
			elseif ($tag === 'img' && $name === 'data-rotation'
					&& in_array($value, ['0', '90', '180', '270'], true)) {
				$allowed = true;
			}
			elseif ($tag === 'img' && $name === 'data-aspect'
					&& in_array($value, ['auto', '1/1', '4/3', '16/9', '3/2'], true)) {
				$allowed = true;
			}
			elseif ($tag === 'img' && in_array($name, ['data-flip-h', 'data-flip-v'], true) && $value === '1') {
				$allowed = true;
			}
			elseif (in_array($tag, ['td', 'th'], true) && in_array($name, ['colspan', 'rowspan'], true)
					&& ctype_digit($value)) {
				$value = (string) max(1, min(24, (int) $value));
				$allowed = true;
			}

			if ($allowed) {
				$element->setAttribute($name, $value);
			}
			else {
				$element->removeAttribute($name);
			}
		}

		if ($tag === 'a') {
			$element->setAttribute('rel', 'noopener noreferrer');
		}
		elseif ($tag === 'img') {
			if (!$element->hasAttribute('src')) {
				$element->parentNode->removeChild($element);
				return;
			}
			$element->setAttribute('loading', 'lazy');
			$element->setAttribute('decoding', 'async');
		}
	}

	private static function sanitizeLink(string $url): string {
		$url = trim($url);
		if (preg_match('#^(?:https?://|mailto:)#i', $url) === 1) {
			return filter_var($url, FILTER_VALIDATE_URL) !== false || stripos($url, 'mailto:') === 0 ? $url : '';
		}
		if (preg_match('#^(?!//)/(?!.*\.\.)[a-z0-9._~/%?=&+#\-]*$#i', $url) === 1) {
			return $url;
		}
		return '';
	}

	private static function isTableSeparator(string $line): bool {
		$cells = self::splitTableRow($line);
		return count($cells) > 0 && count(array_filter($cells, static function(string $cell): bool {
			return preg_match('/^:?-{3,}:?$/', trim($cell)) === 1;
		})) === count($cells);
	}

	private static function splitTableRow(string $line): array {
		$line = trim($line);
		$line = trim($line, '|');
		return array_map('trim', preg_split('/(?<!\\\\)\|/', $line) ?: []);
	}

	private static function tableAlignments(string $line): array {
		return array_map(static function(string $cell): string {
			$cell = trim($cell);
			if (str_starts_with($cell, ':') && str_ends_with($cell, ':')) {
				return 'center';
			}
			if (str_ends_with($cell, ':')) {
				return 'right';
			}
			return str_starts_with($cell, ':') ? 'left' : '';
		}, self::splitTableRow($line));
	}

	private static function alignmentAttribute(string $alignment): string {
		return $alignment !== '' ? ' class="rc-text-'.$alignment.'"' : '';
	}
}
