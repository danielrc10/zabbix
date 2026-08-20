<?php declare(strict_types = 0);

/**
 * PT-BR: Descobre e valida os ícones extensíveis usados nas métricas dos cards.
 * EN: Discovers and validates extensible icons used by card metrics.
 *
 * Autor / Author: Daniel Carvalho <danielrc10@gmail.com>
 * Licença / License: PolyForm Noncommercial 1.0.0
 * Uso comercial / Commercial use: contato / contact danielrc10@gmail.com
 */

namespace Modules\DynamicStatusCards\Includes;

class IconLibrary {

	public const DEFAULT_ICON = 'led.svg';
	public const NO_ICON = 'none';

	private const SAFE_FILENAME = '/\A[a-z0-9][a-z0-9_-]*\.svg\z/iD';
	private static $icons = null;

	public static function getIcons(): array {
		if (self::$icons !== null) {
			return self::$icons;
		}

		$files = glob(dirname(__DIR__).'/assets/icons/*.svg') ?: [];
		$icons = [];

		foreach ($files as $path) {
			$filename = basename($path);
			if (preg_match(self::SAFE_FILENAME, $filename) === 1 && filesize($path) <= 65536) {
				$source = self::getSource($filename);
				if ($source !== '') {
					$icons[$filename] = $source;
				}
			}
		}

		uksort($icons, 'strnatcasecmp');

		$ordered = [self::NO_ICON => ''];
		if (array_key_exists(self::DEFAULT_ICON, $icons)) {
			$ordered[self::DEFAULT_ICON] = $icons[self::DEFAULT_ICON];
			unset($icons[self::DEFAULT_ICON]);
		}
		self::$icons = $ordered + $icons;

		return self::$icons;
	}

	public static function normalize(string $filename): string {
		if ($filename === self::NO_ICON) {
			return self::NO_ICON;
		}

		$icons = self::getIcons();

		return array_key_exists($filename, $icons) ? $filename : self::DEFAULT_ICON;
	}

	public static function getSource(string $filename): string {
		if (preg_match(self::SAFE_FILENAME, $filename) !== 1) {
			return '';
		}

		$path = dirname(__DIR__).'/assets/icons/'.$filename;
		if (!is_file($path) || filesize($path) > 65536) {
			return '';
		}

		$content = file_get_contents($path);
		if ($content === false || stripos($content, '<svg') === false) {
			return '';
		}

		return 'data:image/svg+xml;base64,'.base64_encode($content);
	}
}
