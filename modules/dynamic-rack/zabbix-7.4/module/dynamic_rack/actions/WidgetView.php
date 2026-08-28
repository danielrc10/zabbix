<?php declare(strict_types = 0);

namespace Modules\DynamicRack\Actions;

use API, CControllerDashboardWidgetView, CControllerResponseData, CSettingsHelper, Manager;
use Modules\DynamicRack\Includes\{
	AssetLibrary, CWidgetFieldEquipmentList, CWidgetFieldIndicatorList
};

class WidgetView extends CControllerDashboardWidgetView {
	private const RANK = ['neutral' => 0, 'ok' => 1, 'disabled' => 2, 'no_data' => 2, 'warning' => 3, 'critical' => 4];

	protected function doAction(): void {
		$equipment_field = new CWidgetFieldEquipmentList('equipment');
		$equipment_field->setValue($this->fields_values['equipment'] ?? []);
		$indicator_field = new CWidgetFieldIndicatorList('indicators');
		$indicator_field->setValue($this->fields_values['indicators'] ?? []);
		$equipment = $equipment_field->getValue();
		$indicators = $indicator_field->getValue();

		$data = [
			'name' => $this->getInput('name', $this->widget->getDefaultName()),
			'rack_units' => max(1, min(48, (int) ($this->fields_values['rack_units'] ?? 42))),
			'numbering' => (int) ($this->fields_values['numbering'] ?? 0),
			'u_scale' => max(12, min(72, (int) ($this->fields_values['u_scale'] ?? 28))),
			'balloon_width' => max(120, min(360, (int) ($this->fields_values['balloon_width'] ?? 210))),
			'auto_fit_labels' => (int) ($this->fields_values['auto_fit_labels'] ?? 1) === 1,
			'balloon_color_mode' => $this->fields_values['balloon_color_mode'] ?? 'fill',
			'footer_blocks' => array_map('intval', $this->fields_values['footer_blocks'] ?? []),
			'colors' => [
				'ok' => $this->color('color_ok', '2ECA8B'), 'warning' => $this->color('color_warning', 'FFD54F'),
				'critical' => $this->color('color_critical', 'FF465C'),
				'disabled' => $this->color('color_disabled', '56616A'),
				'no_data' => $this->color('color_no_data', '768D99'),
				'rack' => $this->color('rack_color', '20262D'),
				'background' => $this->color('rack_background', '11161B')
			],
			'equipment' => [], 'balloons' => [], 'footer_indicators' => [], 'environment_indicators' => [], 'message' => '',
			'user' => ['debug_mode' => $this->getDebugMode()]
		];
		if ($equipment === []) {
			$data['message'] = 'Nenhum equipamento foi configurado.';
			$this->setResponse(new CControllerResponseData($data)); return;
		}

		$permitted = $this->permittedHostids();
		$configured_hostids = array_values(array_unique(array_filter(array_map(
			static fn(array $row): string => (string) $row['hostid'], $equipment
		), 'strlen')));
		$active_hostids = $permitted === null ? $configured_hostids : array_values(array_intersect($configured_hostids, $permitted));
		$hosts = $active_hostids ? API::Host()->get([
			'output' => ['hostid', 'name'], 'hostids' => $active_hostids, 'preservekeys' => true
		]) : [];

		$items = $this->getItems($active_hostids, $indicators);
		$history = [];
		if ($items) {
			$period = timeUnitToSeconds(CSettingsHelper::get(CSettingsHelper::HISTORY_PERIOD));
			$history = Manager::History()->getLastValues(array_values($items), 1, $period);
		}

		$indicators_by_equipment = [];
		foreach ($indicators as $indicator) {
			$indicators_by_equipment[$indicator['equipment_key']][] = $indicator;
		}
		$prepared = [];
		$indicator_results = [];
		foreach ($equipment as $row) {
			$hostid = (string) $row['hostid'];
			$host_name = $hosts[$hostid]['name'] ?? '';
			$results = [];
			foreach ($indicators_by_equipment[$row['key']] ?? [] as $indicator) {
				$results[] = $this->prepareIndicator($indicator, $hostid, $items, $history);
			}
			$status = $this->equipmentStatus($row, $results, $hostid, $active_hostids);
			$prepared_row = array_replace($row, [
				'host_name' => $host_name, 'status' => $status,
				'primary_label' => $this->resolveLabel($row['primary_label'], $row, $host_name),
				'secondary_label' => $this->resolveLabel($row['secondary_label'], $row, $host_name),
				'asset_source' => AssetLibrary::source('equipment', $row['asset']),
				'brand_source' => AssetLibrary::source('brands', $row['brand'])
			]);
			$prepared[$row['key']] = $prepared_row;
			$indicator_results[$row['key']] = $results;
		}

		foreach ($prepared as $key => &$row) {
			if ($row['parent_key'] !== '' && isset($prepared[$row['parent_key']])) {
				$row['u'] = $prepared[$row['parent_key']]['u'];
				$row['height_u'] = $prepared[$row['parent_key']]['height_u'];
			}
		}
		unset($row);
		$data['equipment'] = array_values($prepared);
		$data['balloons'] = $this->makeBalloons($prepared, $indicator_results);
		$data['footer_indicators'] = $this->footerIndicators($prepared, $indicator_results);
		$data['environment_indicators'] = array_values(array_filter(
			$data['footer_indicators'],
			static fn(array $row): bool => preg_match('/temperatura|temperature|umidade|humidity/iu', $row['label']) === 1
		));
		$this->setResponse(new CControllerResponseData($data));
	}

	private function getItems(array $hostids, array $indicators): array {
		if ($hostids === [] || $indicators === []) { return []; }
		$items = API::Item()->get([
			'output' => ['itemid', 'hostid', 'name', 'name_resolved', 'key_', 'units', 'value_type'],
			'hostids' => $hostids, 'filter' => ['status' => ITEM_STATUS_ACTIVE], 'webitems' => true,
			'limit' => 10000, 'preservekeys' => true
		]);
		return $items;
	}

	private function prepareIndicator(array $config, string $hostid, array $items, array $history): array {
		$item = $this->matchItem($hostid, $config['patterns'], $items);
		$state_item = $config['state_patterns'] !== [] ? $this->matchItem($hostid, $config['state_patterns'], $items) : $item;
		$sample = $item !== null && isset($history[$item['itemid']][0]) ? $history[$item['itemid']][0] : null;
		$state_sample = $state_item !== null && isset($history[$state_item['itemid']][0]) ? $history[$state_item['itemid']][0] : $sample;
		$value = $sample['value'] ?? null;
		$state_value = $state_sample['value'] ?? null;
		return $config + [
			'itemid' => $item['itemid'] ?? '', 'raw_value' => $value,
			'value' => $this->formatValue($value, $item, $config),
			'status' => $value === null ? 'no_data' : $this->evaluateStatus($state_value, $config)
		];
	}

	private function matchItem(string $hostid, array $patterns, array $items): ?array {
		foreach ($patterns as $pattern) {
			foreach ($items as $item) {
				if ((string) $item['hostid'] !== $hostid) { continue; }
				foreach ([$item['name_resolved'] ?? '', $item['name'] ?? '', $item['key_'] ?? ''] as $candidate) {
					$regex = str_replace('\\*', '.*', preg_quote((string) $pattern, '/'));
					if (preg_match('/^'.$regex.'$/iu', (string) $candidate) === 1) { return $item; }
				}
			}
		}
		return null;
	}

	private function formatValue($value, ?array $item, array $config): string {
		if ($value === null) { return 'Sem dados'; }
		if (in_array($config['format'], ['number', 'percent'], true) && is_numeric($value)) {
			$number = (float) $value * ($config['format'] === 'percent' ? 100 : 1);
			return number_format($number, (int) $config['decimals'], ',', '.').($config['suffix'] !== '' ? ' '.$config['suffix'] : ($config['format'] === 'percent' ? '%' : ''));
		}
		$suffix = $config['suffix'] !== '' ? $config['suffix'] : ($item['units'] ?? '');
		return trim((string) $value.($suffix !== '' ? ' '.$suffix : ''));
	}

	private function evaluateStatus($value, array $config): string {
		if ($config['status_mode'] === 'thresholds') {
			if (!is_numeric($value) || !is_numeric($config['warning']) || !is_numeric($config['critical'])) { return 'no_data'; }
			$value = (float) $value; $warning = (float) $config['warning']; $critical = (float) $config['critical'];
			if ($config['direction'] === 'lower_worse') { return $value <= $critical ? 'critical' : ($value <= $warning ? 'warning' : 'ok'); }
			return $value >= $critical ? 'critical' : ($value >= $warning ? 'warning' : 'ok');
		}
		if ($config['status_mode'] === 'values') {
			foreach (['ok', 'warning', 'critical'] as $status) {
				if ($this->inValues($value, $config[$status.'_values'])) { return $status; }
			}
			return $config['default_status'];
		}
		return 'neutral';
	}

	private function equipmentStatus(array $row, array $results, string $hostid, array $active_hostids): string {
		if ($row['status_mode'] === 'disabled') { return 'disabled'; }
		if ($row['status_mode'] === 'manual') { return $row['manual_status']; }
		if ($hostid !== '' && !in_array($hostid, $active_hostids, true)) { return 'no_data'; }
		$status = $hostid !== '' && $results === [] ? 'no_data' : 'neutral';
		foreach ($results as $result) if ((self::RANK[$result['status']] ?? 0) > (self::RANK[$status] ?? 0)) $status = $result['status'];
		return $status;
	}

	private function makeBalloons(array $equipment, array $results): array {
		$groups = [];
		foreach ($results as $key => $rows) {
			foreach ($rows as $row) {
				if (!in_array($row['location'], ['balloon', 'both'], true)) { continue; }
				$group_key = $key.'|'.$row['balloon'];
				if (!isset($groups[$group_key])) {
					$groups[$group_key] = [
						'equipment_key' => $key, 'balloon' => (int) $row['balloon'], 'side' => $row['side'],
						'u' => $equipment[$key]['u'], 'height_u' => $equipment[$key]['height_u'],
						'title' => $equipment[$key]['primary_label'], 'hostid' => $equipment[$key]['hostid'],
						'status' => $equipment[$key]['status'], 'inherit_color' => (int) $row['inherit_color'] === 1,
						'rows' => [], 'itemids' => []
					];
				}
				$groups[$group_key]['rows'][] = ['label' => $row['label'], 'value' => $row['value'], 'status' => $row['status']];
				if ($row['itemid'] !== '') { $groups[$group_key]['itemids'][] = $row['itemid']; }
			}
		}
		return array_values($groups);
	}

	private function footerIndicators(array $equipment, array $results): array {
		$footer = [];
		foreach ($results as $key => $rows) foreach ($rows as $row) {
			if (in_array($row['location'], ['footer', 'both'], true)) {
				$footer[] = ['equipment' => $equipment[$key]['primary_label'], 'label' => $row['label'],
					'value' => $row['value'], 'status' => $row['status']];
			}
		}
		return $footer;
	}

	private function permittedHostids(): ?array {
		$groupids = !$this->isTemplateDashboard() && ($this->fields_values['groupids'] ?? [])
			? getSubGroups($this->fields_values['groupids']) : null;
		$hostids = ($this->fields_values['hostids'] ?? []) ?: null;
		$tags = !$this->isTemplateDashboard() && ($this->fields_values['host_tags'] ?? []) ? $this->fields_values['host_tags'] : null;
		$filter_maintenance = (int) ($this->fields_values['maintenance'] ?? 0) !== 1;
		if ($groupids === null && $hostids === null && $tags === null && !$filter_maintenance) { return null; }
		$hosts = API::Host()->get([
			'output' => [], 'groupids' => $groupids, 'hostids' => $hostids,
			'evaltype' => $tags !== null ? ($this->fields_values['evaltype_host'] ?? TAG_EVAL_TYPE_AND_OR) : null,
			'tags' => $tags, 'filter' => $filter_maintenance ? ['maintenance_status' => HOST_MAINTENANCE_STATUS_OFF] : null,
			'monitored_hosts' => true, 'preservekeys' => true
		]);
		return array_map('strval', array_keys($hosts));
	}

	private function resolveLabel(string $label, array $row, string $host_name): string {
		return strtr($label, ['{HOST.NAME}' => $host_name, '{EQUIPMENT.KEY}' => $row['key'], '{EQUIPMENT.TYPE}' => $row['type']]);
	}

	private function inValues($value, string $list): bool {
		foreach (array_filter(array_map('trim', preg_split('/[,\r\n]+/u', $list) ?: []), 'strlen') as $candidate) {
			if ((string) $value === $candidate || (is_numeric($value) && is_numeric($candidate) && (float) $value == (float) $candidate)) return true;
		}
		return false;
	}

	private function color(string $field, string $default): string {
		$value = strtoupper(ltrim(trim((string) ($this->fields_values[$field] ?? $default)), '#'));
		return preg_match('/^[0-9A-F]{6}$/D', $value) === 1 ? $value : $default;
	}
}
