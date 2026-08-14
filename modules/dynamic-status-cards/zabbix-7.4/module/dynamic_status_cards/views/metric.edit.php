<?php declare(strict_types = 0);

/**
 * PT-BR: Janela visual para configurar uma métrica do card.
 * EN: Visual dialog for configuring one card metric.
 *
 * Autor / Author: Daniel Carvalho <danielrc10@gmail.com>
 * Licença / License: PolyForm Noncommercial 1.0.0
 * Uso comercial / Commercial use: contato / contact danielrc10@gmail.com
 */

use Modules\DynamicStatusCards\Includes\CWidgetFieldMetricList;

/** @var CView $this */
/** @var array $data */

$formulario = (new CForm())
	->setId('dynamic_status_cards_metric_edit_form')
	->setName('dynamic_status_cards_metric')
	->addStyle('display: none;')
	->addVar('action', $data['action'])
	->addVar('update', 1);

$formulario->addItem((new CSubmitButton())->addClass(ZBX_STYLE_FORM_SUBMIT_HIDDEN));
if (array_key_exists('edit', $data)) {
	$formulario->addVar('edit', 1);
}

$grade = new CFormGrid();

$grade->addItem([
	(new CLabel('Nome exibido', 'rotulo'))->setAsteriskMark(),
	new CFormField(
		(new CTextBox('rotulo', $data['rotulo']))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired()
	)
]);

$padroes_view = (new CWidgetFieldPatternSelectItemView($data['padroes_field']))
	->setFormName($formulario->getName())
	->setPlaceholder('nome exato ou padrão com *');
$padroes_estado_view = (new CWidgetFieldPatternSelectItemView($data['padroes_estado_field']))
	->setFormName($formulario->getName())
	->setPlaceholder('opcional: item que determina somente a cor');

if ($data['templateid'] === null && $data['hostids']) {
	$padroes_view->setPopupParameter('hostids', $data['hostids']);
	$padroes_estado_view->setPopupParameter('hostids', $data['hostids']);
}

foreach ($padroes_view->getViewCollection() as ['label' => $label, 'view' => $view, 'class' => $class]) {
	$grade->addItem([$label, (new CFormField($view))->addClass($class)]);
}
$grade
	->addItem($padroes_view->getTemplates())
	->addItem(new CScriptTag([$padroes_view->getJavaScript()]));

$grade->addItem([
	new CLabel('Formato do valor', 'formato'),
	new CFormField(
		(new CSelect('formato'))
			->setId('formato')
			->setValue($data['formato'])
			->addOptions(CSelect::createOptionsFromArray([
				CWidgetFieldMetricList::FORMATO_AUTOMATICO => 'Automático (formatação do Zabbix)',
				CWidgetFieldMetricList::FORMATO_MAPA => 'Mapeamento personalizado',
				CWidgetFieldMetricList::FORMATO_NUMERO => 'Número',
				CWidgetFieldMetricList::FORMATO_DATA => 'Data Unix',
				CWidgetFieldMetricList::FORMATO_TEXTO => 'Texto'
			]))
	)
]);

$grade->addItem([
	(new CLabel('Mapeamento de exibição', 'mapa'))->addClass('js-formato-mapa'),
	(new CFormField(
		(new CTextArea('mapa', $data['mapa']))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAttribute('placeholder', "1=UP\n0=DOWN")
	))->addClass('js-formato-mapa')
]);

$grade->addItem([
	(new CLabel('Casas decimais', 'decimais'))->addClass('js-formato-numero'),
	(new CFormField(
		(new CNumericBox('decimais', $data['decimais'], 1))->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
	))->addClass('js-formato-numero')
]);

$grade->addItem([
	(new CLabel('Sufixo', 'sufixo'))->addClass('js-formato-numero'),
	(new CFormField(
		(new CTextBox('sufixo', $data['sufixo']))
			->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
			->setAttribute('placeholder', ' ms, %, dias...')
	))->addClass('js-formato-numero')
]);

$grade->addItem([
	(new CLabel('Formato da data', 'formato_data'))->addClass('js-formato-data'),
	(new CFormField(
		(new CTextBox('formato_data', $data['formato_data']))->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
	))->addClass('js-formato-data')
]);

foreach ($padroes_estado_view->getViewCollection() as ['label' => $label, 'view' => $view, 'class' => $class]) {
	$label->setHint(makeHelpIcon(
		'Use quando um item deve ser mostrado, mas outro item do mesmo card deve definir a cor.'
	));
	$grade->addItem([$label, (new CFormField($view))->addClass($class)]);
}
$grade
	->addItem($padroes_estado_view->getTemplates())
	->addItem(new CScriptTag([$padroes_estado_view->getJavaScript()]));

$grade->addItem([
	new CLabel('Avaliação da cor', 'estado_modo'),
	new CFormField(
		(new CSelect('estado_modo'))
			->setId('estado_modo')
			->setValue($data['estado_modo'])
			->addOptions(CSelect::createOptionsFromArray([
				CWidgetFieldMetricList::ESTADO_NENHUM => 'Sem regra (linha informativa)',
				CWidgetFieldMetricList::ESTADO_LIMITES => 'Limiares numéricos',
				CWidgetFieldMetricList::ESTADO_VALORES => 'Valores exatos'
			]))
	)
]);

$grade->addItem([
	(new CLabel('Sentido', 'direcao'))->addClass('js-estado-limiares'),
	(new CFormField(
		(new CSelect('direcao'))
			->setId('direcao')
			->setValue($data['direcao'])
			->addOptions(CSelect::createOptionsFromArray([
				CWidgetFieldMetricList::DIRECAO_MAIOR_PIOR => 'Quanto maior, pior',
				CWidgetFieldMetricList::DIRECAO_MENOR_PIOR => 'Quanto menor, pior'
			]))
	))->addClass('js-estado-limiares')
]);

$grade->addItem([
	(new CLabel('Limite de aviso', 'limite_aviso'))->addClass('js-estado-limiares'),
	(new CFormField(
		(new CTextBox('limite_aviso', $data['limite_aviso']))
			->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
			->setAttribute('placeholder', '50')
	))->addClass('js-estado-limiares')
]);

$grade->addItem([
	(new CLabel('Limite crítico', 'limite_critico'))->addClass('js-estado-limiares'),
	(new CFormField(
		(new CTextBox('limite_critico', $data['limite_critico']))
			->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
			->setAttribute('placeholder', '150')
	))->addClass('js-estado-limiares')
]);

$campos_valores = [
	'valores_ok' => ['Valores OK', '1, up, valid'],
	'valores_aviso' => ['Valores de aviso', 'warning'],
	'valores_critico' => ['Valores críticos', '0, down, invalid']
];
foreach ($campos_valores as $nome => [$rotulo, $placeholder]) {
	$grade->addItem([
		(new CLabel($rotulo, $nome))->addClass('js-estado-valores'),
		(new CFormField(
			(new CTextBox($nome, $data[$nome]))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->setAttribute('placeholder', $placeholder)
		))->addClass('js-estado-valores')
	]);
}

$grade->addItem([
	(new CLabel('Estado para outros valores', 'estado_padrao'))->addClass('js-estado-valores'),
	(new CFormField(
		(new CSelect('estado_padrao'))
			->setId('estado_padrao')
			->setValue($data['estado_padrao'])
			->addOptions(CSelect::createOptionsFromArray([
				'neutro' => 'Neutro',
				'ok' => 'OK',
				'aviso' => 'Aviso',
				'critico' => 'Crítico'
			]))
	))->addClass('js-estado-valores')
]);

$grade->addItem([
	new CLabel('Ausência do item'),
	new CFormField(
		(new CCheckBox('obrigatorio'))
			->setChecked((int) $data['obrigatorio'] === 1)
			->setUncheckedValue('0')
			->setLabel('Marcar o card como sem dados')
	)
]);

$formulario
	->addItem($grade)
	->addItem(
		(new CScriptTag('dynamic_status_cards_metric_edit_form.init('.json_encode([
			'form_id' => $formulario->getId()
		], JSON_THROW_ON_ERROR).');'))->setOnDocumentReady()
	);

$saida = [
	'header' => array_key_exists('edit', $data) ? 'Editar métrica' : 'Nova métrica',
	'script_inline' => $this->readJsFile('metric.edit.js.php', null, ''),
	'body' => $formulario->toString(),
	'buttons' => [[
		'title' => array_key_exists('edit', $data) ? 'Atualizar' : 'Adicionar',
		'keepOpen' => true,
		'isSubmit' => true,
		'action' => 'dynamic_status_cards_metric_edit_form.submit();'
	]]
];

if ($data['user']['debug_mode'] == GROUP_DEBUG_MODE_ENABLED) {
	CProfiler::getInstance()->stop();
	$saida['debug'] = CProfiler::getInstance()->make()->toString();
}

echo json_encode($saida, JSON_THROW_ON_ERROR);
