<?php declare(strict_types = 0);

$root = (new CDiv())->addClass('dynamic-rack-widget')
	->setAttribute('data-u-scale', (string) $data['u_scale'])
	->setAttribute('data-balloon-width', (string) $data['balloon_width'])
	->setAttribute('data-auto-fit-labels', $data['auto_fit_labels'] ? '1' : '0')
	->setAttribute('data-color-mode', $data['balloon_color_mode'])
	->addStyle(implode('', [
		'--dr-u: '.$data['u_scale'].'px;', '--dr-balloon-width: '.$data['balloon_width'].'px;',
		'--dr-ok: #'.$data['colors']['ok'].';', '--dr-warning: #'.$data['colors']['warning'].';',
		'--dr-critical: #'.$data['colors']['critical'].';', '--dr-disabled: #'.$data['colors']['disabled'].';',
		'--dr-no-data: #'.$data['colors']['no_data'].';', '--dr-rack: #'.$data['colors']['rack'].';',
		'--dr-rack-background: #'.$data['colors']['background'].';'
	]));

if ($data['message'] !== '') {
	$root->addItem((new CDiv($data['message']))->addClass('dynamic-rack-message'));
}
else {
	$stage = (new CDiv())->addClass('dynamic-rack-stage');
	$stage->addItem((new CTag('svg', true))->addClass('dynamic-rack-lines')->setAttribute('aria-hidden', 'true'));
	$rack_shell = (new CDiv())->addClass('dynamic-rack-shell');
	$rack_body = (new CDiv())->addClass('dynamic-rack-body')
		->addStyle('height: calc('.$data['rack_units'].' * var(--dr-u));')
		->setAttribute('data-units', (string) $data['rack_units']);

	for ($physical_u = 1; $physical_u <= $data['rack_units']; $physical_u++) {
		$display_u = $data['numbering'] === 0 ? $physical_u : $data['rack_units'] - $physical_u + 1;
		$mark = (new CDiv())->addClass('dynamic-rack-u-mark')
			->addStyle('bottom: calc('.($physical_u - 1).' * var(--dr-u)); height: var(--dr-u);')
			->addItem((new CSpan((string) $display_u))->addClass('dynamic-rack-u-number'));
		$rack_body->addItem($mark);
	}

	$children = [];
	foreach ($data['equipment'] as $equipment) {
		if ($equipment['parent_key'] !== '') { $children[$equipment['parent_key']][] = $equipment; }
	}
	foreach ($data['equipment'] as $equipment) {
		if ($equipment['parent_key'] !== '') { continue; }
		$device = (new CDiv())->addClass('dynamic-rack-device')
			->addClass('status-'.$equipment['status'])->addClass('type-'.$equipment['type'])
			->setAttribute('data-equipment-key', $equipment['key'])
			->setAttribute('data-hostid', (string) $equipment['hostid'])
			->setAttribute('tabindex', $equipment['hostid'] !== '' ? '0' : '-1')
			->addStyle(implode('', [
				'bottom: calc('.((int) $equipment['u'] - 1).' * var(--dr-u));',
				'height: calc('.(int) $equipment['height_u'].' * var(--dr-u));',
				'--dr-finish: #'.$equipment['finish'].';'
			]));
		$face = (new CDiv())->addClass('dynamic-rack-device-face');
		if ($equipment['asset_source'] !== '') {
			$face->addItem((new CSpan())->addClass('dynamic-rack-device-icon')
				->addStyle('--dr-asset: url("'.$equipment['asset_source'].'");'));
		}
		$labels = (new CDiv())->addClass('dynamic-rack-device-labels')->addClass('device-label-fit')
			->addItem((new CSpan($equipment['primary_label']))->addClass('dynamic-rack-primary'));
		if ($equipment['secondary_label'] !== '') {
			$labels->addItem((new CSpan($equipment['secondary_label']))->addClass('dynamic-rack-secondary'));
		}
		$face->addItem($labels);
		if ($equipment['brand_source'] !== '') {
			$face->addItem((new CSpan())->addClass('dynamic-rack-brand')
				->addStyle('--dr-brand: url("'.$equipment['brand_source'].'");'));
		}
		$face->addItem((new CSpan())->addClass('dynamic-rack-status-led')->setAttribute('title', $equipment['status']));
		$device->addItem($face);

		if (isset($children[$equipment['key']])) {
			$child_layer = (new CDiv())->addClass('dynamic-rack-shelf-children');
			foreach ($children[$equipment['key']] as $child) {
				$width = 100 / max(1, (int) $child['slot_count']);
				$left = ($child['slot'] - 1) * $width;
				$child_node = (new CDiv())->addClass('dynamic-rack-shelf-child')->addClass('status-'.$child['status'])
					->setAttribute('data-equipment-key', $child['key'])->setAttribute('data-hostid', (string) $child['hostid'])
					->setAttribute('tabindex', $child['hostid'] !== '' ? '0' : '-1')
					->addStyle('left: '.$left.'%; width: '.$width.'%; --dr-finish: #'.$child['finish'].';');
				if ($child['asset_source'] !== '') {
					$child_node->addItem((new CSpan())->addClass('dynamic-rack-device-icon')
						->addStyle('--dr-asset: url("'.$child['asset_source'].'");'));
				}
				$child_node->addItem((new CSpan($child['primary_label']))->addClass('dynamic-rack-primary')->addClass('device-label-fit'));
				if ($child['brand_source'] !== '') {
					$child_node->addItem((new CSpan())->addClass('dynamic-rack-brand')
						->addStyle('--dr-brand: url("'.$child['brand_source'].'");'));
				}
				$child_layer->addItem($child_node);
			}
			$device->addItem($child_layer);
		}
		$rack_body->addItem($device);
	}
	$rack_shell->addItem($rack_body);
	$stage->addItem($rack_shell);

	foreach ($data['balloons'] as $index => $balloon) {
		$node = (new CDiv())->addClass('dynamic-rack-balloon')->addClass('status-'.$balloon['status'])
			->setAttribute('data-index', (string) $index)->setAttribute('data-side', $balloon['side'])
			->setAttribute('data-u', (string) $balloon['u'])->setAttribute('data-height-u', (string) $balloon['height_u'])
			->setAttribute('data-equipment-key', $balloon['equipment_key'])
			->setAttribute('data-hostid', (string) $balloon['hostid'])
			->setAttribute('data-itemids', implode(',', array_unique($balloon['itemids'])));
		if (!$balloon['inherit_color']) { $node->addClass('no-inherit'); }
		$node->addItem((new CDiv($balloon['title']))->addClass('dynamic-rack-balloon-title'));
		foreach ($balloon['rows'] as $row) {
			$node->addItem((new CDiv([
				(new CSpan($row['label']))->addClass('dynamic-rack-balloon-label'),
				(new CSpan($row['value']))->addClass('dynamic-rack-balloon-value')
			]))->addClass('dynamic-rack-balloon-row')->addClass('status-'.$row['status']));
		}
		$stage->addItem($node);
	}
	$root->addItem($stage);

	$footer = (new CDiv())->addClass('dynamic-rack-footer');
	$has_footer = false;
	$occupied = [];
	$worst = 'neutral';
	$ranks = ['neutral' => 0, 'ok' => 1, 'disabled' => 2, 'no_data' => 2, 'warning' => 3, 'critical' => 4];
	foreach ($data['equipment'] as $equipment) {
		if ($equipment['parent_key'] === '' && $equipment['type'] !== 'empty') {
			for ($u = $equipment['u']; $u < $equipment['u'] + $equipment['height_u']; $u++) { $occupied[$u] = true; }
		}
		if (($ranks[$equipment['status']] ?? 0) > ($ranks[$worst] ?? 0)) { $worst = $equipment['status']; }
	}
	if (in_array(1, $data['footer_blocks'], true)) {
		$has_footer = true;
		$footer->addItem((new CDiv([
			(new CSpan('Ocupação'))->addClass('dynamic-rack-footer-label'),
			(new CSpan(count($occupied).' / '.$data['rack_units'].'U'))->addClass('dynamic-rack-footer-value')
		]))->addClass('dynamic-rack-footer-card'));
	}
	if (in_array(2, $data['footer_blocks'], true)) {
		$has_footer = true;
		$footer->addItem((new CDiv([
			(new CSpan('Estado geral'))->addClass('dynamic-rack-footer-label'),
			(new CSpan($worst))->addClass('dynamic-rack-footer-value')
		]))->addClass('dynamic-rack-footer-card')->addClass('status-'.$worst));
	}
	if (in_array(3, $data['footer_blocks'], true)) {
		foreach ($data['footer_indicators'] as $indicator) {
			$has_footer = true;
			$footer->addItem((new CDiv([
				(new CSpan($indicator['equipment'].' · '.$indicator['label']))->addClass('dynamic-rack-footer-label'),
				(new CSpan($indicator['value']))->addClass('dynamic-rack-footer-value')
			]))->addClass('dynamic-rack-footer-card')->addClass('status-'.$indicator['status']));
		}
	}
	if (in_array(4, $data['footer_blocks'], true)) {
		foreach ($data['environment_indicators'] as $indicator) {
			$has_footer = true;
			$footer->addItem((new CDiv([
				(new CSpan('Ambiente · '.$indicator['label']))->addClass('dynamic-rack-footer-label'),
				(new CSpan($indicator['value']))->addClass('dynamic-rack-footer-value')
			]))->addClass('dynamic-rack-footer-card')->addClass('status-'.$indicator['status']));
		}
	}
	if ($has_footer) { $root->addItem($footer); }
}

(new CWidgetView($data))->addItem($root)->show();
