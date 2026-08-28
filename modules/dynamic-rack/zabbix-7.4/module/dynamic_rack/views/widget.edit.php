<?php declare(strict_types = 0);

use Modules\DynamicRack\Includes\{
	AssetLibrary, CWidgetFieldEquipmentListView, CWidgetFieldIndicatorListView
};

$form = new CWidgetFormView($data);
$groups = array_key_exists('groupids', $data['fields'])
	? new CWidgetFieldMultiSelectGroupView($data['fields']['groupids']) : null;
$hosts = $data['templateid'] === null ? new CWidgetFieldMultiSelectHostView($data['fields']['hostids']) : null;
if ($hosts !== null && $groups !== null) {
	$hosts->setFilterPreselect([
		'id' => $groups->getId(), 'accept' => CMultiSelect::FILTER_PRESELECT_ACCEPT_ID, 'submit_as' => 'groupid'
	]);
}

$source = (new CWidgetFormFieldsetCollapsibleView('Origem e filtros'))
	->addField($groups)->addField($hosts)
	->addField(array_key_exists('evaltype_host', $data['fields'])
		? new CWidgetFieldRadioButtonListView($data['fields']['evaltype_host']) : null)
	->addField(array_key_exists('host_tags', $data['fields']) ? new CWidgetFieldTagsView($data['fields']['host_tags']) : null)
	->addField(new CWidgetFieldCheckBoxView($data['fields']['maintenance']));

$rack = (new CWidgetFormFieldsetCollapsibleView('Rack'))
	->setExpanded()
	->addField(new CWidgetFieldSelectView($data['fields']['rack_preset']))
	->addField(new CWidgetFieldIntegerBoxView($data['fields']['rack_units']))
	->addField(new CWidgetFieldRadioButtonListView($data['fields']['numbering']))
	->addField((new CWidgetFieldIntegerBoxView($data['fields']['u_scale']))->setFieldHint(makeHelpIcon(
		'O rack nunca cria rolagem interna. Esta escala aumenta sua altura; se necessário, o dashboard ou navegador rola.'
	)))
	->addField(new CWidgetFieldIntegerBoxView($data['fields']['balloon_width']))
	->addField(new CWidgetFieldCheckBoxView($data['fields']['auto_fit_labels']));

$topology = (new CWidgetFormFieldsetCollapsibleView('Equipamentos, prateleiras e espaços'))
	->setExpanded()
	->addField((new CWidgetFieldEquipmentListView($data['fields']['equipment']))->setFieldHint(makeHelpIcon(
		'Itens filhos usam a chave de uma prateleira pai e dividem sua largura em slots. Itens passivos ocupam U sem monitoramento.'
	)));

$indicators = (new CWidgetFormFieldsetCollapsibleView('Indicadores, balões e rodapé'))
	->setExpanded()
	->addField((new CWidgetFieldIndicatorListView($data['fields']['indicators']))->setFieldHint(makeHelpIcon(
		'Vários indicadores podem compartilhar o mesmo número de balão. Um equipamento ou U pode ter quantos balões forem necessários.'
	)))
	->addField(new CWidgetFieldCheckBoxListView($data['fields']['footer_blocks']))
	->addField(new CWidgetFieldSelectView($data['fields']['balloon_color_mode']));

$appearance = (new CWidgetFormFieldsetCollapsibleView('Cores'))
	->addField(new CWidgetFieldColorView($data['fields']['color_ok']))
	->addField(new CWidgetFieldColorView($data['fields']['color_warning']))
	->addField(new CWidgetFieldColorView($data['fields']['color_critical']))
	->addField(new CWidgetFieldColorView($data['fields']['color_disabled']))
	->addField(new CWidgetFieldColorView($data['fields']['color_no_data']))
	->addField(new CWidgetFieldColorView($data['fields']['rack_color']))
	->addField(new CWidgetFieldColorView($data['fields']['rack_background']));

$form->addFieldset($source)->addFieldset($rack)->addFieldset($topology)->addFieldset($indicators)
	->addFieldset($appearance)->includeJsFile('widget.edit.js.php')->initFormJs('widget_form.init('.json_encode([
		'templateid' => $data['templateid'],
		'brands' => AssetLibrary::getBrands(),
		'equipment_assets' => AssetLibrary::getEquipment()
	], JSON_THROW_ON_ERROR).');')->show();
