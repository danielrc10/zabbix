<?php declare(strict_types = 0);

$form = (new CForm())->setId('dynamic_rack_indicator_edit_form')->setName('dynamic_rack_indicator')
	->addStyle('display: none;')->addVar('action', $data['action'])->addVar('update', 1);
$form->addItem((new CSubmitButton())->addClass(ZBX_STYLE_FORM_SUBMIT_HIDDEN));
if (array_key_exists('edit', $data)) { $form->addVar('edit', 1); }
if (array_key_exists('copy', $data)) { $form->addVar('copy', 1); }

$grid = new CFormGrid();
$equipment_options = [];
foreach ($data['equipment_keys'] as $key) { if ((string) $key !== '') { $equipment_options[(string) $key] = (string) $key; } }
$grid->addItem([(new CLabel('Equipamento', 'equipment_key'))->setAsteriskMark(), new CFormField(
	(new CSelect('equipment_key'))->setId('equipment_key')->setValue($data['equipment_key'])
		->addOptions(CSelect::createOptionsFromArray($equipment_options))
)]);
$grid->addItem([(new CLabel('Rótulo', 'label'))->setAsteriskMark(), new CFormField(
	(new CTextBox('label', $data['label']))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)->setAriaRequired()
)]);

$patterns = (new CWidgetFieldPatternSelectItemView($data['patterns_field']))->setFormName($form->getName())
	->setPlaceholder('nome exato ou padrão com *');
$state_patterns = (new CWidgetFieldPatternSelectItemView($data['state_patterns_field']))->setFormName($form->getName())
	->setPlaceholder('opcional: item usado somente para a cor');
if ($data['templateid'] === null && $data['hostids']) {
	$patterns->setPopupParameter('hostids', $data['hostids']);
	$state_patterns->setPopupParameter('hostids', $data['hostids']);
}
foreach ($patterns->getViewCollection() as ['label' => $label, 'view' => $view, 'class' => $class]) {
	$grid->addItem([$label, (new CFormField($view))->addClass($class)]);
}
$grid->addItem($patterns->getTemplates())->addItem(new CScriptTag([$patterns->getJavaScript()]));
foreach ($state_patterns->getViewCollection() as ['label' => $label, 'view' => $view, 'class' => $class]) {
	$grid->addItem([$label, (new CFormField($view))->addClass($class)]);
}
$grid->addItem($state_patterns->getTemplates())->addItem(new CScriptTag([$state_patterns->getJavaScript()]));

$grid->addItem([new CLabel('Formato', 'format'), new CFormField(
	(new CSelect('format'))->setId('format')->setValue($data['format'])->addOptions(CSelect::createOptionsFromArray([
		'automatic' => 'Automático', 'number' => 'Número', 'percent' => 'Percentual', 'text' => 'Texto'
	]))
)]);
$grid->addItem([new CLabel('Casas decimais', 'decimals'), new CFormField(
	(new CNumericBox('decimals', (int) $data['decimals'], 1))->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
)]);
$grid->addItem([new CLabel('Sufixo', 'suffix'), new CFormField(
	(new CTextBox('suffix', $data['suffix']))->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
)]);

$placement = new CFormGrid();
$placement->addItem([new CLabel('Local', 'location'), new CFormField(
	(new CSelect('location'))->setId('location')->setValue($data['location'])->addOptions(CSelect::createOptionsFromArray([
		'balloon' => 'Balão', 'footer' => 'Rodapé', 'both' => 'Balão e rodapé'
	]))
)]);
$placement->addItem([new CLabel('Lado do balão', 'side'), new CFormField(
	(new CSelect('side'))->setId('side')->setValue($data['side'])->addOptions(CSelect::createOptionsFromArray([
		'auto' => 'Automático / alternado', 'left' => 'Esquerda', 'right' => 'Direita'
	]))
)]);
$placement->addItem([new CLabel('Número do balão', 'balloon'), new CFormField(
	(new CNumericBox('balloon', (int) $data['balloon'], 2))->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
)]);
$placement->addItem([new CLabel('Cor em caso de problema'), new CFormField(
	(new CCheckBox('inherit_color'))->setChecked((int) $data['inherit_color'] === 1)->setUncheckedValue('0')
		->setLabel('Herdar a cor do pior estado deste equipamento')
)]);

$state = new CFormGrid();
$state->addItem([new CLabel('Regra de estado', 'status_mode'), new CFormField(
	(new CSelect('status_mode'))->setId('status_mode')->setValue($data['status_mode'])->addOptions(CSelect::createOptionsFromArray([
		'none' => 'Somente informativo', 'thresholds' => 'Limiares numéricos', 'values' => 'Valores exatos'
	]))
)]);
$direction = (new CSelect('direction'))->setId('direction')->setValue($data['direction'])
	->addOptions(CSelect::createOptionsFromArray(['higher_worse' => 'Maior é pior', 'lower_worse' => 'Menor é pior']));
$state->addItem([(new CLabel('Direção', 'direction'))->addClass('js-thresholds'),
	(new CFormField($direction))->addClass('js-thresholds')]);
foreach (['warning' => 'Limite de aviso', 'critical' => 'Limite crítico'] as $name => $label) {
	$state->addItem([(new CLabel($label, $name))->addClass('js-thresholds'),
		(new CFormField((new CTextBox($name, $data[$name]))->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)))->addClass('js-thresholds')]);
}
foreach (['ok_values' => 'Valores OK', 'warning_values' => 'Valores de aviso', 'critical_values' => 'Valores críticos'] as $name => $label) {
	$state->addItem([(new CLabel($label, $name))->addClass('js-values'),
		(new CFormField((new CTextBox($name, $data[$name]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)))->addClass('js-values')]);
}
$default_status = (new CSelect('default_status'))->setId('default_status')->setValue($data['default_status'])
	->addOptions(CSelect::createOptionsFromArray([
		'neutral' => 'Neutro', 'ok' => 'OK', 'warning' => 'Aviso', 'critical' => 'Crítico'
	]));
$state->addItem([(new CLabel('Estado padrão', 'default_status'))->addClass('js-values'),
	(new CFormField($default_status))->addClass('js-values')]);

$form->addItem((new CFormFieldsetCollapsible('Item e valor', $grid))->setExpanded())
	->addItem((new CFormFieldsetCollapsible('Posicionamento', $placement))->setExpanded())
	->addItem(new CFormFieldsetCollapsible('Estado', $state))
	->addItem((new CScriptTag('dynamic_rack_indicator_edit_form.init('.json_encode(['form_id' => $form->getId()], JSON_THROW_ON_ERROR).');'))->setOnDocumentReady());

$copying = array_key_exists('copy', $data);
$output = [
	'header' => array_key_exists('edit', $data) ? 'Editar indicador' : ($copying ? 'Copiar indicador' : 'Novo indicador'),
	'script_inline' => $this->readJsFile('indicator.edit.js.php', null, ''), 'body' => $form->toString(),
	'buttons' => [[
		'title' => array_key_exists('edit', $data) ? 'Atualizar' : ($copying ? 'Adicionar cópia' : 'Adicionar'),
		'keepOpen' => true, 'isSubmit' => true, 'action' => 'dynamic_rack_indicator_edit_form.submit();'
	]]
];
if ($data['user']['debug_mode'] == GROUP_DEBUG_MODE_ENABLED) {
	CProfiler::getInstance()->stop(); $output['debug'] = CProfiler::getInstance()->make()->toString();
}
echo json_encode($output, JSON_THROW_ON_ERROR);
