<?php declare(strict_types = 0);

namespace Modules\DynamicRack\Includes;

use CWidgetsData;
use Zabbix\Widgets\{CWidgetField, CWidgetForm};
use Zabbix\Widgets\Fields\{
	CWidgetFieldCheckBox, CWidgetFieldCheckBoxList, CWidgetFieldColor, CWidgetFieldIntegerBox,
	CWidgetFieldMultiSelectGroup, CWidgetFieldMultiSelectHost, CWidgetFieldRadioButtonList,
	CWidgetFieldSelect, CWidgetFieldTags
};

class WidgetForm extends CWidgetForm {
	public const NUMBER_BOTTOM = 0;
	public const NUMBER_TOP = 1;

	public function addFields(): self {
		$equipment = [array_replace(CWidgetFieldEquipmentList::defaults(), [
			'key' => 'core-switch', 'u' => 1, 'height_u' => 1, 'type' => 'switch',
			'primary_label' => 'Core Switch', 'secondary_label' => '24 portas',
			'asset' => 'switch.svg', 'finish' => 'AEB4BA', 'status_mode' => 'manual',
			'manual_status' => 'ok'
		])];

		return $this
			->addField($this->isTemplateDashboard() ? null : new CWidgetFieldMultiSelectGroup('groupids', 'Grupos de hosts'))
			->addField((new CWidgetFieldMultiSelectHost('hostids', 'Hosts'))->setDefault(
				$this->isTemplateDashboard() ? [
					CWidgetField::FOREIGN_REFERENCE_KEY => CWidgetField::createTypedReference(
						CWidgetField::REFERENCE_DASHBOARD, CWidgetsData::DATA_TYPE_HOST_IDS
					)
				] : []
			))
			->addField($this->isTemplateDashboard() ? null : (new CWidgetFieldRadioButtonList('evaltype_host', 'Tags de host', [
				TAG_EVAL_TYPE_AND_OR => 'E/OU', TAG_EVAL_TYPE_OR => 'Ou'
			]))->setDefault(TAG_EVAL_TYPE_AND_OR))
			->addField($this->isTemplateDashboard() ? null : new CWidgetFieldTags('host_tags'))
			->addField((new CWidgetFieldIntegerBox('rack_units', 'Altura do rack (U)', 1, 48))
				->setDefault(42)->setFlags(CWidgetField::FLAG_NOT_EMPTY))
			->addField((new CWidgetFieldSelect('rack_preset', 'Preset', [
				0 => 'Personalizado', 8 => '8U', 12 => '12U', 18 => '18U', 24 => '24U',
				32 => '32U', 42 => '42U', 48 => '48U'
			]))->setDefault(42))
			->addField((new CWidgetFieldRadioButtonList('numbering', 'Numeração', [
				self::NUMBER_BOTTOM => 'U1 embaixo (sobe)', self::NUMBER_TOP => 'U1 em cima (desce)'
			]))->setDefault(self::NUMBER_BOTTOM))
			->addField((new CWidgetFieldIntegerBox('u_scale', 'Escala vertical (px por U)', 12, 72))
				->setDefault(28)->setFlags(CWidgetField::FLAG_NOT_EMPTY))
			->addField((new CWidgetFieldIntegerBox('balloon_width', 'Largura dos balões (px)', 120, 360))
				->setDefault(210)->setFlags(CWidgetField::FLAG_NOT_EMPTY))
			->addField((new CWidgetFieldCheckBox('auto_fit_labels', 'Reduzir rótulos automaticamente para caber'))
				->setDefault(1))
			->addField((new CWidgetFieldEquipmentList('equipment', 'Equipamentos e ocupação'))
				->setDefault($equipment)->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK))
			->addField(new CWidgetFieldIndicatorList('indicators', 'Indicadores e balões'))
			->addField((new CWidgetFieldCheckBoxList('footer_blocks', 'Rodapé abaixo do rack', [
				1 => 'Resumo de ocupação', 2 => 'Estado geral', 3 => 'Indicadores marcados para rodapé',
				4 => 'Temperatura/ambiente'
			]))->setDefault([1, 2, 3]))
			->addField((new CWidgetFieldSelect('balloon_color_mode', 'Cor do balão com problema', [
				'fill' => 'Fundo inteiro', 'stripe' => 'Faixa e borda', 'neutral' => 'Não herdar'
			]))->setDefault('fill'))
			->addField((new CWidgetFieldColor('color_ok', 'Cor OK'))->setDefault('2ECA8B'))
			->addField((new CWidgetFieldColor('color_warning', 'Cor de aviso'))->setDefault('FFD54F'))
			->addField((new CWidgetFieldColor('color_critical', 'Cor crítica'))->setDefault('FF465C'))
			->addField((new CWidgetFieldColor('color_disabled', 'Cor desativado'))->setDefault('56616A'))
			->addField((new CWidgetFieldColor('color_no_data', 'Cor sem dados'))->setDefault('768D99'))
			->addField((new CWidgetFieldColor('rack_color', 'Cor da estrutura'))->setDefault('20262D'))
			->addField((new CWidgetFieldColor('rack_background', 'Cor interna do rack'))->setDefault('11161B'))
			->addField(new CWidgetFieldCheckBox('maintenance', 'Mostrar hosts em manutenção'));
	}

	public function validate(bool $strict = false): array {
		if ($strict && $this->isTemplateDashboard()) {
			$this->getField('hostids')->setValue([
				CWidgetField::FOREIGN_REFERENCE_KEY => CWidgetField::createTypedReference(
					CWidgetField::REFERENCE_DASHBOARD, CWidgetsData::DATA_TYPE_HOST_IDS
				)
			]);
		}
		$errors = parent::validate($strict);
		if ($errors) {
			return $errors;
		}
		$units = (int) $this->getField('rack_units')->getValue();
		$equipment = $this->getField('equipment')->getValue();
		$keys = array_column($equipment, 'key');
		$occupied = [];
		$slots = [];
		foreach ($equipment as $index => $row) {
			if ($row['parent_key'] === '' && ((int) $row['u'] + (int) $row['height_u'] - 1) > $units) {
				$errors[] = 'Equipamento '.($index + 1).': ultrapassa a altura do rack.';
			}
			if ($row['parent_key'] === '') {
				for ($u = (int) $row['u']; $u < (int) $row['u'] + (int) $row['height_u']; $u++) {
					if (isset($occupied[$u])) {
						$errors[] = 'Equipamento '.($index + 1).': o U'.$u.' já está ocupado por '.$occupied[$u].'.';
					}
					else { $occupied[$u] = $row['key']; }
				}
			}
			else {
				$start = ((int) $row['slot'] - 1) / max(1, (int) $row['slot_count']);
				$end = (int) $row['slot'] / max(1, (int) $row['slot_count']);
				foreach ($slots[$row['parent_key']] ?? [] as $used) {
					if ($start < $used['end'] && $end > $used['start']) {
						$errors[] = 'Equipamento '.($index + 1).': o espaço da prateleira conflita com '.$used['key'].'.';
						break;
					}
				}
				$slots[$row['parent_key']][] = ['start' => $start, 'end' => $end, 'key' => $row['key']];
			}
		}
		foreach ($this->getField('indicators')->getValue() as $index => $indicator) {
			if (!in_array($indicator['equipment_key'], $keys, true)) {
				$errors[] = 'Indicador '.($index + 1).': o equipamento associado não existe.';
			}
		}
		return $errors;
	}
}
