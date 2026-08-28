<?php declare(strict_types = 0);

namespace Modules\DynamicRack\Includes;

/**
 * Catálogo extensível: SVG/PNG/WebP adicionados nas pastas aparecem no editor.
 */
class AssetLibrary {
	private const MAX_BYTES = 131072;
	private const TYPES = [
		'svg' => 'image/svg+xml',
		'png' => 'image/png',
		'webp' => 'image/webp'
	];

	public static function getBrands(): array {
		return self::scan('brands');
	}

	public static function getEquipment(): array {
		return self::scan('equipment');
	}

	public static function normalize(string $folder, string $filename): string {
		$assets = self::scan($folder);
		return array_key_exists($filename, $assets) ? $filename : '';
	}

	public static function source(string $folder, string $filename): string {
		return self::scan($folder)[$filename] ?? '';
	}

	private static function scan(string $folder): array {
		if (!in_array($folder, ['brands', 'equipment'], true)) {
			return [];
		}

		$directory = dirname(__DIR__).'/assets/'.$folder;
		$assets = [];
		foreach (glob($directory.'/*') ?: [] as $path) {
			if (!is_file($path) || filesize($path) > self::MAX_BYTES) {
				continue;
			}
			$filename = basename($path);
			$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
			if (!preg_match('/^[a-z0-9][a-z0-9._-]*$/D', $filename)
					|| !array_key_exists($extension, self::TYPES)) {
				continue;
			}
			$content = file_get_contents($path);
			if ($content === false || !self::isSafeContent($extension, $content)) {
				continue;
			}
			$assets[$filename] = 'data:'.self::TYPES[$extension].';base64,'.base64_encode($content);
		}
		uksort($assets, 'strnatcasecmp');
		return $assets;
	}

	private static function isSafeContent(string $extension, string $content): bool {
		if ($extension === 'png') {
			return str_starts_with($content, "\x89PNG\r\n\x1a\n");
		}
		if ($extension === 'webp') {
			return strlen($content) >= 12 && substr($content, 0, 4) === 'RIFF'
				&& substr($content, 8, 4) === 'WEBP';
		}
		if (!str_contains($content, '<svg')) {
			return false;
		}
		return preg_match('/<(?:script|foreignObject|iframe|object|embed)\b|\bon[a-z]+\s*=|<!DOCTYPE|<!ENTITY|(?:href|src)\s*=\s*["\'](?:https?:|\/\/|data:)/iu', $content) !== 1;
	}
}
