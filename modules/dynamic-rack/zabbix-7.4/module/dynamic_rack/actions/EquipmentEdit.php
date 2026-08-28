<?php declare(strict_types = 0);

namespace Modules\DynamicRack\Actions;

use API, CController, CControllerResponseData;
use Modules\DynamicRack\Includes\{AssetLibrary, CWidgetFieldEquipmentList};

class EquipmentEdit extends CController {
	protected function init(): void { $this->disableCsrfValidation(); }

	protected function checkInput(): bool {
		$fields = [
			'key' => 'string', 'parent_key' => 'string', 'u' => 'int32', 'height_u' => 'int32',
			'type' => 'string', 'hostid' => 'string', 'primary_label' => 'string',
			'secondary_label' => 'string', 'brand' => 'string', 'asset' => 'string', 'finish' => 'string',
			'status_mode' => 'string', 'manual_status' => 'string', 'slot' => 'int32',
			'slot_count' => 'int32', 'hostids' => 'array', 'groupids' => 'array', 'host_tags' => 'array',
			'evaltype_host' => 'int32', 'maintenance' => 'in 0,1', 'parent_keys' => 'array',
			'edit' => 'in 1', 'copy' => 'in 1', 'update' => 'in 1'
		];
		$valid = $this->validateInput($fields);
		if ($valid && $this->hasInput('update')) {
			$field = new CWidgetFieldEquipmentList('equipment');
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
			$field = new CWidgetFieldEquipmentList('equipment');
			$field->setValue([$this->clean($input)]);
			$row = $field->getValue()[0];
			if ($this->hasInput('edit')) { $row['edit'] = 1; }
			$this->setResponse((new CControllerResponseData(['main_block' => json_encode($row, JSON_THROW_ON_ERROR)]))->disableView());
			return;
		}
		$hostids = array_values(array_filter(array_map('strval', $this->getInput('hostids', [])), 'strlen'));
		$groupids = array_values(array_filter(array_map('strval', $this->getInput('groupids', [])), 'strlen'));
		$hosts = ($hostids || $groupids) ? API::Host()->get([
			'output' => ['hostid', 'name'], 'hostids' => $hostids ?: null,
			'groupids' => $groupids ? getSubGroups($groupids) : null,
			'evaltype' => $this->getInput('evaltype_host', TAG_EVAL_TYPE_AND_OR),
			'tags' => $this->getInput('host_tags', []) ?: null,
			'filter' => (int) $this->getInput('maintenance', 0) === 1
				? null : ['maintenance_status' => HOST_MAINTENANCE_STATUS_OFF],
			'monitored_hosts' => true, 'sortfield' => 'name', 'limit' => 2000
		]) : [];
		$data = $input + CWidgetFieldEquipmentList::defaults();
		$data += [
			'action' => $this->getAction(), 'hosts' => $hosts,
			'parent_keys' => $this->getInput('parent_keys', []),
			'brands' => AssetLibrary::getBrands(), 'equipment_assets' => AssetLibrary::getEquipment(),
			'user' => ['debug_mode' => $this->getDebugMode()]
		];
		$this->setResponse(new CControllerResponseData($data));
	}

	private function clean(array $input): array {
		unset($input['action'], $input['edit'], $input['copy'], $input['update'], $input['hostids'],
			$input['groupids'], $input['host_tags'], $input['evaltype_host'], $input['maintenance'], $input['parent_keys']);
		return array_replace(CWidgetFieldEquipmentList::defaults(), $input);
	}
}
