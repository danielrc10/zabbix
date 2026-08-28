<?php declare(strict_types = 0);

/**
 * Resolve macros, sanitiza conteúdo e prepara somente dados seguros para a view.
 */

namespace Modules\RichContent\Actions;

use API,
	CControllerDashboardWidgetView,
	CControllerResponseData,
	CMacrosResolverHelper;

use Modules\RichContent\Includes\{ContentRenderer, WidgetForm};

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		$content = (string) ($this->fields_values['content'] ?? '');
		$media_source = (string) ($this->fields_values['media_source'] ?? '');
		$media_alt = (string) ($this->fields_values['media_alt'] ?? 'Imagem do widget');
		$item = $this->getMacroContextItem();

		if ($item !== null) {
			$item['rich_content'] = $content;
			$item['rich_media_source'] = $media_source;
			$item['rich_media_alt'] = $media_alt;

			$resolved = CMacrosResolverHelper::resolveItemBasedWidgetMacros(
				[$item['itemid'] => $item],
				[
					'rich_content' => 'rich_content',
					'rich_media_source' => 'rich_media_source',
					'rich_media_alt' => 'rich_media_alt'
				]
			);
			$item = $resolved[$item['itemid']];
			$content = $item['rich_content'];
			$media_source = $item['rich_media_source'];
			$media_alt = $item['rich_media_alt'];
		}

		$format = (int) ($this->fields_values['content_format'] ?? WidgetForm::FORMAT_MARKDOWN);
		if (!in_array($format, [WidgetForm::FORMAT_MARKDOWN, WidgetForm::FORMAT_HTML], true)) {
			$format = WidgetForm::FORMAT_MARKDOWN;
		}

		$appearance = $this->makeAppearance();
		$media = $this->makeMedia($media_source, $media_alt);
		$columns = ContentRenderer::renderColumns($content, $format);

		$this->setResponse(new CControllerResponseData([
			'name' => $this->getInput('name', $this->widget->getDefaultName()),
			'columns' => $columns,
			'grid_columns' => max(1, min(6, (int) ($this->fields_values['columns'] ?? 2))),
			'gap' => max(0, min(96, (int) ($this->fields_values['gap'] ?? 24))),
			'design_width' => max(320, min(2400, (int) ($this->fields_values['design_width'] ?? 960))),
			'padding' => max(0, min(128, (int) ($this->fields_values['padding'] ?? 24))),
			'allow_upscale' => (int) ($this->fields_values['allow_upscale'] ?? 0) === 1,
			'appearance' => $appearance,
			'media' => $media,
			'message' => $columns === [] && $media === null ? 'Nenhum conteúdo válido foi configurado.' : '',
			'context_item' => $item !== null ? $item['name_resolved'] : '',
			'user' => ['debug_mode' => $this->getDebugMode()]
		]));
	}

	private function getMacroContextItem(): ?array {
		$itemids = $this->fields_values['itemid'] ?? [];
		if (!$itemids) {
			return null;
		}

		$items = API::Item()->get([
			'output' => ['itemid', 'hostid', 'name', 'name_resolved', 'key_', 'description', 'state', 'value_type', 'units'],
			'itemids' => $itemids,
			'webitems' => true,
			'limit' => 1
		]);

		return $items ? reset($items) : null;
	}

	private function makeAppearance(): array {
		$mode = (int) ($this->fields_values['background_mode'] ?? WidgetForm::BACKGROUND_TRANSPARENT);
		$color = '#'.$this->color('background_color', '1F2937');
		if ($mode === WidgetForm::BACKGROUND_GRADIENT) {
			$angle = max(0, min(360, (int) ($this->fields_values['gradient_angle'] ?? 135)));
			$colors = [
				'#'.$this->color('gradient_color_1', '0F172A'),
				'#'.$this->color('gradient_color_2', '0F766E')
			];
			if ((int) ($this->fields_values['gradient_third_color'] ?? 0) === 1) {
				$colors[] = '#'.$this->color('gradient_color_3', '2563EB');
			}

			$stops = count($colors) === 3
				? [$colors[0].' 0%', $colors[1].' 50%', $colors[2].' 100%']
				: [$colors[0].' 0%', $colors[1].' 100%'];
			$background = 'linear-gradient('.$angle.'deg, '.implode(', ', $stops).')';
		}
		elseif ($mode === WidgetForm::BACKGROUND_SOLID) {
			$background = $color;
		}
		else {
			$mode = WidgetForm::BACKGROUND_TRANSPARENT;
			$background = 'transparent';
		}

		$border_styles = [
			WidgetForm::BORDER_NONE => 'none',
			WidgetForm::BORDER_SOLID => 'solid',
			WidgetForm::BORDER_DASHED => 'dashed',
			WidgetForm::BORDER_DOTTED => 'dotted'
		];
		$border_style = $border_styles[(int) ($this->fields_values['border_style'] ?? 0)] ?? 'none';
		$border_width = $border_style === 'none'
			? 0
			: max(0, min(32, (int) ($this->fields_values['border_width'] ?? 0)));

		return [
			'mode' => $mode,
			'background' => $background,
			'text_color' => '#'.$this->color('text_color', 'F8FAFC'),
			'border_style' => $border_style,
			'border_color' => '#'.$this->color('border_color', '38BDF8'),
			'border_width' => $border_width,
			'border_radius' => max(0, min(128, (int) ($this->fields_values['border_radius'] ?? 0)))
		];
	}

	private function makeMedia(string $source, string $alt): ?array {
		$position = (int) ($this->fields_values['media_position'] ?? WidgetForm::MEDIA_HIDDEN);
		if (!in_array($position, [WidgetForm::MEDIA_FIRST, WidgetForm::MEDIA_LAST], true)) {
			return null;
		}

		$source = ContentRenderer::sanitizeImageSource($source);
		if ($source === '') {
			return null;
		}

		$fits = [
			WidgetForm::MEDIA_FIT_CONTAIN => 'contain',
			WidgetForm::MEDIA_FIT_COVER => 'cover',
			WidgetForm::MEDIA_FIT_FILL => 'fill',
			WidgetForm::MEDIA_FIT_NONE => 'none'
		];
		$aspects = [
			WidgetForm::ASPECT_AUTO => 'auto',
			WidgetForm::ASPECT_SQUARE => '1 / 1',
			WidgetForm::ASPECT_4_3 => '4 / 3',
			WidgetForm::ASPECT_16_9 => '16 / 9',
			WidgetForm::ASPECT_3_2 => '3 / 2'
		];
		$rotation = (int) ($this->fields_values['media_rotation'] ?? 0);
		if (!in_array($rotation, [0, 90, 180, 270], true)) {
			$rotation = 0;
		}
		$flip = array_map('intval', $this->fields_values['media_flip'] ?? []);

		return [
			'source' => $source,
			'alt' => mb_substr(strip_tags($alt), 0, 255),
			'position' => $position,
			'width' => max(0, min(2400, (int) ($this->fields_values['media_width'] ?? 0))),
			'height' => max(0, min(2400, (int) ($this->fields_values['media_height'] ?? 0))),
			'fit' => $fits[(int) ($this->fields_values['media_fit'] ?? 0)] ?? 'contain',
			'aspect' => $aspects[(int) ($this->fields_values['media_aspect'] ?? 0)] ?? 'auto',
			'rotation' => $rotation,
			'flip_h' => in_array(WidgetForm::FLIP_HORIZONTAL, $flip, true),
			'flip_v' => in_array(WidgetForm::FLIP_VERTICAL, $flip, true)
		];
	}

	private function color(string $field, string $fallback): string {
		$value = strtoupper((string) ($this->fields_values[$field] ?? $fallback));
		return preg_match('/^[0-9A-F]{6}$/', $value) === 1 ? $value : $fallback;
	}
}
