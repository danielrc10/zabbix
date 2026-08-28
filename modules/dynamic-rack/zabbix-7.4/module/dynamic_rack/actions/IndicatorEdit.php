<?php declare(strict_types = 0);

namespace Modules\DynamicRack\Actions;

use CController, CControllerResponseData;
use Modules\DynamicRack\Includes\CWidgetFieldIndicatorList;
use Zabbix\Widgets\{CWidgetField};
use Zabbix\Widgets\Fields\CWidgetFieldPatternSelectItem;

class IndicatorEdit extends CController {
	protected function init(): void { $this->disableCsrfValidation(); }

	protected function checkInput(): bool {
		$fields = [
			'equipment_key' => 'string', 'label' => 'string', 'patterns' => 'array', 'state_patterns' => 'array',
			'format' => 'string', 'decimals' => 'int32', 'suffix' => 'string', 'side' => 'string',
			'balloon' => 'int32', 'location' => 'string', 'status_mode' => 'string', 'direction' => 'string',
			'warning' => 'string', 'critical' => 'string', 'ok_values' => 'string',
			'warning_values' => 'string', 'critical_values' => 'string', 'default_status' => 'string',
			'inherit_color' => 'in 0,1', 'hostids' => 'array', 'groupids' => 'array', 'host_tags' => 'array',
			'evaltype_host' => 'int32', 'maintenance' => 'in 0,1', 'equipment_keys' => 'array',
			'edit' => 'in 1', 'copy' => 'in 1', 'update' => 'in 1', 'templateid' => 'string'
		];
		$valid = $this->validateInput($fields);
		if ($valid && $this->hasInput('update')) {
			$field = new CWidgetFieldIndicatorList('indicators');
			$field->setValue([$this->clean($this->getInputAll())]);
			foreach ($field->validate(true) as $error) { error($error); }
			$valid = !hasErrorMessages();
		}
		if (!$valid) {
			$this->setResponse((new CControllerResponseData(['main_block' => json_encode([
				'error' => ['messages' => array_column(get_and_clear_messages(), 'message')]
			], JSON_THROW_ON_ERROR)]))->disableView());
		}
		return $valid;
	}

	protected function checkPermissions(): bool { return $this->getUserType() >= USER_TYPE_ZABBIX_USER; }

	protected function doAction(): void {
		$input = $this->getInputAll();
		if ($this->hasInput('update')) {
			$field = new CWidgetFieldIndicatorList('indicators');
			$field->setValue([$this->clean($input)]);
			$row = $field->getValue()[0];
			if ($this->hasInput('edit')) { $row['edit'] = 1; }
			$this->setResponse((new CControllerResponseData(['main_block' => json_encode($row, JSON_THROW_ON_ERROR)]))->disableView());
			return;
		}
		$data = $input + CWidgetFieldIndicatorList::defaults();
		$templateid = $this->getInput('templateid', '') ?: null;
		$data += [
			'action' => $this->getAction(), 'templateid' => $templateid,
			'hostids' => $this->getInput('hostids', []), 'equipment_keys' => $this->getInput('equipment_keys', []),
			'user' => ['debug_mode' => $this->getDebugMode()]
		];
		$data['patterns_field'] = $this->patternField('patterns', 'Item ou padrão', $data['patterns'], $templateid, true);
		$data['state_patterns_field'] = $this->patternField('state_patterns', 'Item alternativo para o estado', $data['state_patterns'], $templateid, false);
		$this->setResponse(new CControllerResponseData($data));
	}

	private function clean(array $input): array {
		unset($input['action'], $input['edit'], $input['copy'], $input['update'], $input['hostids'],
			$input['groupids'], $input['host_tags'], $input['evaltype_host'], $input['maintenance'],
			$input['equipment_keys'], $input['templateid']);
		return array_replace(CWidgetFieldIndicatorList::defaults(), $input);
	}

	private function patternField(string $name, string $label, array $value, $templateid, bool $required): CWidgetFieldPatternSelectItem {
		$field = (new CWidgetFieldPatternSelectItem($name, $label))->setValue($value)->setTemplateId($templateid);
		if ($required) { $field->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK); }
		return $field;
	}
}
