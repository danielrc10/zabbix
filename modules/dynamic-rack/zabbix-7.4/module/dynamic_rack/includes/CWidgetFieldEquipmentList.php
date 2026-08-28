<?php declare(strict_types = 0);

namespace Modules\DynamicRack\Includes;

use DB;
use Zabbix\Widgets\CWidgetField;

class CWidgetFieldEquipmentList extends CWidgetField {
	public const DEFAULT_VIEW = CWidgetFieldEquipmentListView::class;
	public const DEFAULT_VALUE = [];

	public const TYPES = [
		'server', 'storage', 'switch', 'firewall', 'router', 'dvr', 'ups', 'modem',
		'patch_panel', 'shelf', 'blank', 'cable_manager', 'empty', 'custom'
	];
	public const PASSIVE_TYPES = ['patch_panel', 'shelf', 'blank', 'cable_manager', 'empty'];

	public function __construct(string $name, ?string $label = null) {
		parent::__construct($name, $label);
		$this->setDefault(self::DEFAULT_VALUE);
	}

	public static function defaults(): array {
		return [
			'key' => '', 'parent_key' => '', 'u' => 1, 'height_u' => 1, 'type' => 'server',
			'hostid' => '', 'primary_label' => '', 'secondary_label' => '', 'brand' => '',
			'asset' => 'server.svg', 'finish' => '303841', 'status_mode' => 'automatic',
			'manual_status' => 'ok', 'slot' => 1, 'slot_count' => 1
		];
	}

	public function setValue($value): self {
		if (!is_array($value)) {
			return parent::setValue([]);
		}
		$rows = [];
		foreach (array_values($value) as $row) {
			if (is_array($row)) {
				$row = array_replace(self::defaults(), $row);
				$row['key'] = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $row['key']);
				$row['parent_key'] = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $row['parent_key']);
				$row['brand'] = AssetLibrary::normalize('brands', (string) $row['brand']);
				$row['asset'] = AssetLibrary::normalize('equipment', (string) $row['asset']);
				$row['finish'] = strtoupper(ltrim(trim((string) $row['finish']), '#'));
				$rows[] = $row;
			}
		}
		return parent::setValue($rows);
	}

	public function validate(bool $strict = false): array {
		$errors = parent::validate($strict);
		$keys = [];
		foreach ($this->getValue() as $index => $row) {
			$number = $index + 1;
			if ($row['key'] === '') {
				$errors[] = "Equipamento {$number}: informe uma chave.";
			}
			elseif (isset($keys[$row['key']])) {
				$errors[] = "Equipamento {$number}: a chave deve ser única.";
			}
			$keys[$row['key']] = true;
			if (trim((string) $row['primary_label']) === '') {
				$errors[] = "Equipamento {$number}: informe o rótulo principal.";
			}
			if (preg_match('/^[0-9A-F]{6}$/Di', (string) $row['finish']) !== 1) {
				$errors[] = "Equipamento {$number}: a cor de acabamento é inválida.";
			}
			if ($row['hostid'] !== '' && preg_match('/^\d+$/D', (string) $row['hostid']) !== 1) {
				$errors[] = "Equipamento {$number}: o identificador de host é inválido.";
			}
		}
		foreach ($this->getValue() as $index => $row) {
			if ($row['parent_key'] !== '' && !isset($keys[$row['parent_key']])) {
				$errors[] = 'Equipamento '.($index + 1).': a prateleira pai não existe.';
			}
			if ($row['parent_key'] === $row['key']) {
				$errors[] = 'Equipamento '.($index + 1).': um equipamento não pode ser seu próprio pai.';
			}
		}
		$by_key = [];
		foreach ($this->getValue() as $row) { $by_key[$row['key']] = $row; }
		foreach ($this->getValue() as $index => $row) {
			if ($row['parent_key'] !== '' && isset($by_key[$row['parent_key']])
					&& $by_key[$row['parent_key']]['type'] !== 'shelf') {
				$errors[] = 'Equipamento '.($index + 1).': o pai precisa ser do tipo prateleira.';
			}
			if ((int) $row['slot'] > (int) $row['slot_count']) {
				$errors[] = 'Equipamento '.($index + 1).': o slot não pode ser maior que a quantidade de slots.';
			}
			$seen = [$row['key'] => true];
			$parent = $row['parent_key'];
			while ($parent !== '' && isset($by_key[$parent])) {
				if (isset($seen[$parent])) {
					$errors[] = 'Equipamento '.($index + 1).': foi detectado um ciclo de prateleiras.';
					break;
				}
				$seen[$parent] = true;
				$parent = $by_key[$parent]['parent_key'];
			}
		}
		return $errors;
	}

	public function toApi(array &$widget_fields = []): void {
		$integer = ['u', 'height_u', 'slot', 'slot_count'];
		foreach ($this->getValue() as $index => $row) {
			foreach ($row as $name => $value) {
				$widget_fields[] = [
					'type' => in_array($name, $integer, true) ? ZBX_WIDGET_FIELD_TYPE_INT32 : ZBX_WIDGET_FIELD_TYPE_STR,
					'name' => $this->name.'.'.$index.'.'.$name,
					'value' => $value
				];
			}
		}
	}

	protected function getValidationRules(bool $strict = false): array {
		$max = DB::getFieldLength('widget_field', 'value_str');
		$rules = ['type' => API_OBJECTS, 'fields' => [
			'key' => ['type' => API_STRING_UTF8, 'length' => 64, 'default' => ''],
			'parent_key' => ['type' => API_STRING_UTF8, 'length' => 64, 'default' => ''],
			'u' => ['type' => API_INT32, 'in' => '1:48', 'default' => 1],
			'height_u' => ['type' => API_INT32, 'in' => '1:48', 'default' => 1],
			'type' => ['type' => API_STRING_UTF8, 'in' => implode(',', self::TYPES), 'default' => 'server'],
			'hostid' => ['type' => API_STRING_UTF8, 'length' => 32, 'default' => ''],
			'primary_label' => ['type' => API_STRING_UTF8, 'length' => 255, 'default' => ''],
			'secondary_label' => ['type' => API_STRING_UTF8, 'length' => 255, 'default' => ''],
			'brand' => ['type' => API_STRING_UTF8, 'length' => 128, 'default' => ''],
			'asset' => ['type' => API_STRING_UTF8, 'length' => 128, 'default' => ''],
			'finish' => ['type' => API_STRING_UTF8, 'length' => 6, 'default' => '303841'],
			'status_mode' => ['type' => API_STRING_UTF8, 'in' => 'automatic,manual,disabled', 'default' => 'automatic'],
			'manual_status' => ['type' => API_STRING_UTF8, 'in' => 'ok,warning,critical,disabled,no_data', 'default' => 'ok'],
			'slot' => ['type' => API_INT32, 'in' => '1:12', 'default' => 1],
			'slot_count' => ['type' => API_INT32, 'in' => '1:12', 'default' => 1]
		]];
		if (($this->getFlags() & self::FLAG_NOT_EMPTY) !== 0) {
			self::setValidationRuleFlag($rules, API_NOT_EMPTY);
		}
		return $rules;
	}
}
