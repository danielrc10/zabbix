<?php declare(strict_types = 0);

namespace Modules\DynamicRack\Includes;

use DB;
use Zabbix\Widgets\CWidgetField;

class CWidgetFieldIndicatorList extends CWidgetField {
	public const DEFAULT_VIEW = CWidgetFieldIndicatorListView::class;
	public const DEFAULT_VALUE = [];

	public static function defaults(): array {
		return [
			'equipment_key' => '', 'label' => '', 'patterns' => [], 'state_patterns' => [],
			'format' => 'automatic', 'decimals' => 0, 'suffix' => '', 'side' => 'auto',
			'balloon' => 1, 'location' => 'balloon', 'status_mode' => 'none',
			'direction' => 'higher_worse', 'warning' => '', 'critical' => '',
			'ok_values' => '', 'warning_values' => '', 'critical_values' => '',
			'default_status' => 'neutral', 'inherit_color' => 1
		];
	}

	public function __construct(string $name, ?string $label = null) {
		parent::__construct($name, $label);
		$this->setDefault(self::DEFAULT_VALUE);
	}

	public function setValue($value): self {
		if (!is_array($value)) {
			return parent::setValue([]);
		}
		$rows = [];
		foreach (array_values($value) as $row) {
			if (!is_array($row)) {
				continue;
			}
			$row = array_replace(self::defaults(), $row);
			$row['equipment_key'] = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $row['equipment_key']);
			$row['patterns'] = array_values(array_filter(array_map('strval', (array) $row['patterns']), 'strlen'));
			$row['state_patterns'] = array_values(array_filter(array_map('strval', (array) $row['state_patterns']), 'strlen'));
			$rows[] = $row;
		}
		return parent::setValue($rows);
	}

	public function validate(bool $strict = false): array {
		$errors = parent::validate($strict);
		foreach ($this->getValue() as $index => $row) {
			$number = $index + 1;
			if ($row['equipment_key'] === '') {
				$errors[] = "Indicador {$number}: escolha um equipamento.";
			}
			if (trim((string) $row['label']) === '') {
				$errors[] = "Indicador {$number}: informe o rótulo.";
			}
			if ($row['patterns'] === []) {
				$errors[] = "Indicador {$number}: selecione ao menos um item ou padrão.";
			}
			if ($row['status_mode'] === 'thresholds'
					&& (!is_numeric($row['warning']) || !is_numeric($row['critical']))) {
				$errors[] = "Indicador {$number}: os limiares devem ser numéricos.";
			}
		}
		return $errors;
	}

	public function toApi(array &$widget_fields = []): void {
		$integers = ['decimals', 'balloon', 'inherit_color'];
		foreach ($this->getValue() as $index => $row) {
			foreach ($row as $name => $value) {
				if (in_array($name, ['patterns', 'state_patterns'], true)) {
					foreach ($value as $child_index => $child) {
						$widget_fields[] = ['type' => ZBX_WIDGET_FIELD_TYPE_STR,
							'name' => $this->name.'.'.$index.'.'.$name.'.'.$child_index, 'value' => $child];
					}
					continue;
				}
				$widget_fields[] = [
					'type' => in_array($name, $integers, true) ? ZBX_WIDGET_FIELD_TYPE_INT32 : ZBX_WIDGET_FIELD_TYPE_STR,
					'name' => $this->name.'.'.$index.'.'.$name,
					'value' => $value
				];
			}
		}
	}

	protected function getValidationRules(bool $strict = false): array {
		$max = DB::getFieldLength('widget_field', 'value_str');
		return ['type' => API_OBJECTS, 'fields' => [
			'equipment_key' => ['type' => API_STRING_UTF8, 'length' => 64, 'default' => ''],
			'label' => ['type' => API_STRING_UTF8, 'length' => 255, 'default' => ''],
			'patterns' => ['type' => API_STRINGS_UTF8, 'default' => []],
			'state_patterns' => ['type' => API_STRINGS_UTF8, 'default' => []],
			'format' => ['type' => API_STRING_UTF8, 'in' => 'automatic,number,percent,text', 'default' => 'automatic'],
			'decimals' => ['type' => API_INT32, 'in' => '0:6', 'default' => 0],
			'suffix' => ['type' => API_STRING_UTF8, 'length' => 32, 'default' => ''],
			'side' => ['type' => API_STRING_UTF8, 'in' => 'auto,left,right', 'default' => 'auto'],
			'balloon' => ['type' => API_INT32, 'in' => '1:12', 'default' => 1],
			'location' => ['type' => API_STRING_UTF8, 'in' => 'balloon,footer,both', 'default' => 'balloon'],
			'status_mode' => ['type' => API_STRING_UTF8, 'in' => 'none,thresholds,values', 'default' => 'none'],
			'direction' => ['type' => API_STRING_UTF8, 'in' => 'higher_worse,lower_worse', 'default' => 'higher_worse'],
			'warning' => ['type' => API_STRING_UTF8, 'length' => 64, 'default' => ''],
			'critical' => ['type' => API_STRING_UTF8, 'length' => 64, 'default' => ''],
			'ok_values' => ['type' => API_STRING_UTF8, 'length' => $max, 'default' => ''],
			'warning_values' => ['type' => API_STRING_UTF8, 'length' => $max, 'default' => ''],
			'critical_values' => ['type' => API_STRING_UTF8, 'length' => $max, 'default' => ''],
			'default_status' => ['type' => API_STRING_UTF8, 'in' => 'neutral,ok,warning,critical', 'default' => 'neutral'],
			'inherit_color' => ['type' => API_INT32, 'in' => '0,1', 'default' => 1]
		]];
	}
}
