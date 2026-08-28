<?php declare(strict_types = 0);

use Modules\DynamicRack\Includes\CWidgetFieldEquipmentList;

$form = (new CForm())->setId('dynamic_rack_equipment_edit_form')->setName('dynamic_rack_equipment')
	->addStyle('display: none;')->addVar('action', $data['action'])->addVar('update', 1);
$form->addItem((new CSubmitButton())->addClass(ZBX_STYLE_FORM_SUBMIT_HIDDEN));
if (array_key_exists('edit', $data)) { $form->addVar('edit', 1); }
if (array_key_exists('copy', $data)) { $form->addVar('copy', 1); }

$grid = new CFormGrid();
$grid->addItem([(new CLabel('Chave única', 'key'))->setAsteriskMark(), new CFormField(
	(new CTextBox('key', $data['key']))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)->setAriaRequired()
)]);

$parent_options = ['' => 'Nenhuma — ocupa U diretamente'];
foreach ($data['parent_keys'] as $key) { if ((string) $key !== '') { $parent_options[(string) $key] = (string) $key; } }
$grid->addItem([new CLabel('Prateleira pai', 'parent_key'), new CFormField(
	(new CSelect('parent_key'))->setId('parent_key')->setValue($data['parent_key'])
		->addOptions(CSelect::createOptionsFromArray($parent_options))
)]);
$grid->addItem([(new CLabel('U inicial (contado da base)', 'u'))->addClass('js-direct-position'),
	(new CFormField((new CNumericBox('u', (int) $data['u'], 2))->setAttribute('min', 1)->setAttribute('max', 48)))->addClass('js-direct-position')]);
$grid->addItem([(new CLabel('Altura em U', 'height_u'))->addClass('js-direct-position'),
	(new CFormField((new CNumericBox('height_u', (int) $data['height_u'], 2))->setAttribute('min', 1)->setAttribute('max', 48)))->addClass('js-direct-position')]);
$grid->addItem([(new CLabel('Slot na prateleira', 'slot'))->addClass('js-child-position'),
	(new CFormField((new CNumericBox('slot', (int) $data['slot'], 2))->setAttribute('min', 1)->setAttribute('max', 12)))->addClass('js-child-position')]);
$grid->addItem([(new CLabel('Quantidade de slots', 'slot_count'))->addClass('js-child-position'),
	(new CFormField((new CNumericBox('slot_count', (int) $data['slot_count'], 2))->setAttribute('min', 1)->setAttribute('max', 12)))->addClass('js-child-position')]);

$type_labels = [
	'server' => 'Servidor', 'storage' => 'Storage', 'switch' => 'Switch', 'firewall' => 'Firewall',
	'router' => 'Roteador', 'dvr' => 'DVR/NVR', 'ups' => 'Nobreak/UPS', 'modem' => 'Modem',
	'patch_panel' => 'Patch panel', 'shelf' => 'Prateleira', 'blank' => 'Tampa cega',
	'cable_manager' => 'Organizador de cabos', 'empty' => 'Espaço vazio', 'custom' => 'Personalizado'
];
$grid->addItem([new CLabel('Tipo', 'type'), new CFormField(
	(new CSelect('type'))->setId('type')->setValue($data['type'])->addOptions(CSelect::createOptionsFromArray($type_labels))
)]);

$host_options = ['' => 'Sem host — passivo/manual'];
foreach ($data['hosts'] as $host) { $host_options[(string) $host['hostid']] = $host['name']; }
$grid->addItem([new CLabel('Host monitorado', 'hostid'), new CFormField(
	(new CSelect('hostid'))->setId('hostid')->setValue($data['hostid'])->addOptions(CSelect::createOptionsFromArray($host_options))
)]);
$grid->addItem([(new CLabel('Rótulo principal', 'primary_label'))->setAsteriskMark(), new CFormField(
	(new CTextBox('primary_label', $data['primary_label']))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)->setAriaRequired()
)]);
$grid->addItem([new CLabel('Rótulo secundário', 'secondary_label'), new CFormField(
	(new CTextBox('secondary_label', $data['secondary_label']))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
)]);

$brand_options = ['' => 'Sem logomarca'];
foreach (array_keys($data['brands']) as $name) { $brand_options[$name] = $name; }
$grid->addItem([new CLabel('Marca / mini logo', 'brand'), new CFormField(
	(new CSelect('brand'))->setId('brand')->setValue($data['brand'])->addOptions(CSelect::createOptionsFromArray($brand_options))
)]);
$asset_options = ['' => 'Sem ícone'];
foreach (array_keys($data['equipment_assets']) as $name) { $asset_options[$name] = $name; }
$grid->addItem([new CLabel('Desenho do equipamento', 'asset'), new CFormField(
	(new CSelect('asset'))->setId('asset')->setValue($data['asset'])->addOptions(CSelect::createOptionsFromArray($asset_options))
)]);
$grid->addItem([new CLabel('Acabamento / cor base', 'finish'), new CFormField(
	(new CColorPicker('finish'))->setColor($data['finish'])
)]);
$grid->addItem([new CLabel('Origem do estado', 'status_mode'), new CFormField(
	(new CSelect('status_mode'))->setId('status_mode')->setValue($data['status_mode'])->addOptions(CSelect::createOptionsFromArray([
		'automatic' => 'Automático pelos indicadores', 'manual' => 'Manual', 'disabled' => 'Desativado'
	]))
)]);
$manual_status = (new CSelect('manual_status'))->setId('manual_status')->setValue($data['manual_status'])
	->addOptions(CSelect::createOptionsFromArray([
		'ok' => 'OK', 'warning' => 'Aviso', 'critical' => 'Crítico', 'disabled' => 'Desativado', 'no_data' => 'Sem dados'
	]));
$grid->addItem([(new CLabel('Estado manual', 'manual_status'))->addClass('js-manual-status'),
	(new CFormField($manual_status))->addClass('js-manual-status')]);

$form->addItem((new CFormFieldsetCollapsible('Equipamento e posição', $grid))->setExpanded())
	->addItem((new CScriptTag('dynamic_rack_equipment_edit_form.init('.json_encode([
		'form_id' => $form->getId()
	], JSON_THROW_ON_ERROR).');'))->setOnDocumentReady());

$copying = array_key_exists('copy', $data);
$output = [
	'header' => array_key_exists('edit', $data) ? 'Editar equipamento' : ($copying ? 'Copiar equipamento' : 'Novo equipamento'),
	'script_inline' => $this->readJsFile('equipment.edit.js.php', null, ''), 'body' => $form->toString(),
	'buttons' => [[
		'title' => array_key_exists('edit', $data) ? 'Atualizar' : ($copying ? 'Adicionar cópia' : 'Adicionar'),
		'keepOpen' => true, 'isSubmit' => true, 'action' => 'dynamic_rack_equipment_edit_form.submit();'
	]]
];
if ($data['user']['debug_mode'] == GROUP_DEBUG_MODE_ENABLED) {
	CProfiler::getInstance()->stop(); $output['debug'] = CProfiler::getInstance()->make()->toString();
}
echo json_encode($output, JSON_THROW_ON_ERROR);
