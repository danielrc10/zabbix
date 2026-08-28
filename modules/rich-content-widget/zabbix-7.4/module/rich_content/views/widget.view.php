<?php declare(strict_types = 0);

/**
 * View declarativa. Todo HTML dinâmico já chega sanitizado pelo ContentRenderer.
 *
 * @var CView $this
 * @var array $data
 */

use Modules\RichContent\Includes\WidgetForm;

$appearance = $data['appearance'];
$style = implode('', [
	'--rc-background: '.$appearance['background'].';',
	'--rc-text-color: '.$appearance['text_color'].';',
	'--rc-border-color: '.$appearance['border_color'].';',
	'--rc-border-style: '.$appearance['border_style'].';',
	'--rc-border-width: '.$appearance['border_width'].'px;',
	'--rc-border-radius: '.$appearance['border_radius'].'px;',
	'--rc-padding: '.$data['padding'].'px;',
	'--rc-gap: '.$data['gap'].'px;',
	'--rc-columns: '.$data['grid_columns'].';'
]);

$root = (new CDiv())
	->addClass('rich-content-widget')
	->setAttribute('data-background-mode', (string) $appearance['mode'])
	->setAttribute('data-shell-background', $appearance['background'])
	->setAttribute('data-design-width', (string) $data['design_width'])
	->setAttribute('data-allow-upscale', $data['allow_upscale'] ? '1' : '0')
	->addStyle($style);

if ($data['message'] !== '') {
	$root->addItem((new CDiv($data['message']))->addClass('rich-content-message'));
}
else {
	$canvas = (new CDiv())->addClass('rich-content-canvas');
	$grid = (new CDiv())->addClass('rich-content-grid');

	$make_media = static function(array $media): CDiv {
		$image_style = implode('', [
			$media['width'] > 0 ? 'width: '.$media['width'].'px;' : '',
			$media['height'] > 0 ? 'height: '.$media['height'].'px;' : '',
			'object-fit: '.$media['fit'].';',
			'aspect-ratio: '.$media['aspect'].';',
			'--rc-image-rotation: '.$media['rotation'].'deg;',
			'--rc-image-flip-x: '.($media['flip_h'] ? '-1' : '1').';',
			'--rc-image-flip-y: '.($media['flip_v'] ? '-1' : '1').';'
		]);

		$image = (new CTag('img'))
			->addClass('rich-content-primary-media')
			->setAttribute('src', $media['source'])
			->setAttribute('alt', $media['alt'])
			->setAttribute('loading', 'lazy')
			->setAttribute('decoding', 'async')
			->addStyle($image_style);

		return (new CDiv($image))->addClass('rich-content-media-block');
	};

	if ($data['media'] !== null && $data['media']['position'] === WidgetForm::MEDIA_FIRST) {
		$grid->addItem($make_media($data['media']));
	}

	foreach ($data['columns'] as $column) {
		$grid->addItem(
			(new CDiv(new CHtmlEntity($column)))
				->addClass('rich-content-column')
		);
	}

	if ($data['media'] !== null && $data['media']['position'] === WidgetForm::MEDIA_LAST) {
		$grid->addItem($make_media($data['media']));
	}

	$canvas->addItem($grid);
	$root->addItem($canvas);
}

(new CWidgetView($data))->addItem($root)->show();
