<?php declare(strict_types = 0);

/**
 * PT-BR: Janela visual para configurar uma métrica do card.
 * EN: Visual dialog for configuring one card metric.
 *
 * Autor / Author: Daniel Carvalho <danielrc10@gmail.com>
 * Licença / License: PolyForm Noncommercial 1.0.0
 * Uso comercial / Commercial use: contato / contact danielrc10@gmail.com
 */

use Modules\DynamicStatusCards\Includes\{
	CWidgetFieldMetricList,
	IconLibrary
};

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
if (array_key_exists('copy', $data)) {
	$formulario->addVar('copy', 1);
}

$grade_item = new CFormGrid();
$grade_formatacao = new CFormGrid();
$grade_estado = new CFormGrid();
$grade_aparencia = new CFormGrid();

$rotulo_tipo = new CLabel('Tipo de linha', 'tipo');
$rotulo_tipo->setHint(makeHelpIcon(
	'Métrica exibe um item. Espaço vazio mantém uma linha em branco. Separador horizontal divide seções do card.'
));
$grade_item->addItem([
	$rotulo_tipo,
	new CFormField(
		(new CSelect('tipo'))
			->setId('tipo')
			->setValue($data['tipo'])
			->addOptions(CSelect::createOptionsFromArray([
				CWidgetFieldMetricList::TIPO_METRICA => 'Métrica',
				CWidgetFieldMetricList::TIPO_ESPACADOR => 'Espaço vazio',
				CWidgetFieldMetricList::TIPO_SEPARADOR => 'Separador horizontal'
			]))
	)
]);

$grade_item->addItem([
	(new CLabel('Nome exibido', 'rotulo'))->setAsteriskMark(),
	new CFormField(
		(new CTextBox('rotulo', $data['rotulo']))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired()
	)
]);

$grade_aparencia->addItem([
	new CLabel('Nome da métrica no card', 'mostrar_rotulo'),
	new CFormField(
		(new CSelect('mostrar_rotulo'))
			->setId('mostrar_rotulo')
			->setValue((int) $data['mostrar_rotulo'])
			->addOptions(CSelect::createOptionsFromArray([
				1 => 'Mostrar',
				0 => 'Ocultar'
			]))
	)
]);

$icone_selecionado = IconLibrary::normalize((string) $data['icone']);
$catalogo_icones = (new CDiv())->addClass('dynamic-status-icons');
$catalogo_icones->addItem(new CVar('icone', $icone_selecionado, 'icone'));
$fonte_selecionada = $data['icones'][$icone_selecionado] ?? '';
$miniatura_selecionada = (new CSpan($icone_selecionado === IconLibrary::NO_ICON ? '—' : ''))
	->addClass('dynamic-status-icons__preview')
	->addClass('dynamic-status-icons__selected-preview');
if ($icone_selecionado === IconLibrary::NO_ICON) {
	$miniatura_selecionada->addClass('dynamic-status-icons__preview--none');
}
else {
	$miniatura_selecionada->addStyle('--dsc-icon-preview: url("'.$fonte_selecionada.'");');
}
$botao_catalogo = (new CTag('button', true, [
	$miniatura_selecionada,
	(new CSpan($icone_selecionado))->addClass('dynamic-status-icons__selected-name'),
	(new CSpan('▾'))->addClass('dynamic-status-icons__chevron')
]))
	->setAttribute('type', 'button')
	->setAttribute('aria-expanded', 'false')
	->setAttribute('aria-haspopup', 'listbox')
	->addClass('dynamic-status-icons__toggle');
$painel_icones = (new CDiv())
	->addClass('dynamic-status-icons__panel')
	->setAttribute('role', 'listbox');
foreach ($data['icones'] as $arquivo => $url) {
	$sem_icone = $arquivo === IconLibrary::NO_ICON;
	$miniatura = (new CSpan($sem_icone ? '—' : ''))
		->addClass('dynamic-status-icons__preview');
	if ($sem_icone) {
		$miniatura->addClass('dynamic-status-icons__preview--none');
	}
	else {
		$miniatura->addStyle('--dsc-icon-preview: url("'.$url.'");');
	}

	$botao_icone = (new CTag('button', true, [
			$miniatura,
			(new CSpan($sem_icone ? 'none' : $arquivo))->addClass('dynamic-status-icons__filename')
		]))
			->setAttribute('type', 'button')
			->setAttribute('data-icon', $arquivo)
			->setAttribute('role', 'option')
			->setAttribute('aria-selected', $arquivo === $icone_selecionado ? 'true' : 'false')
			->addClass('dynamic-status-icons__option');
	if ($arquivo === $icone_selecionado) {
		$botao_icone->addClass('is-selected');
	}
	$painel_icones->addItem($botao_icone);
}
$catalogo_icones->addItem([$botao_catalogo, $painel_icones]);

$rotulo_icone = new CLabel('Indicador de estado');
$rotulo_icone->setHint(makeHelpIcon(
	'Escolha o ícone exibido com a cor do estado. Novos arquivos SVG adicionados em assets/icons aparecem automaticamente.'
));
$grade_aparencia->addItem([$rotulo_icone, new CFormField($catalogo_icones)]);

$padroes_view = (new CWidgetFieldPatternSelectItemView($data['padroes_field']))
	->setFormName($formulario->getName())
	->setPlaceholder('nome exato ou padrão com *');
$padroes_complemento_view = (new CWidgetFieldPatternSelectItemView($data['padroes_complemento_field']))
	->setFormName($formulario->getName())
	->setPlaceholder('opcional: memória total, disco total...');
$padroes_estado_view = (new CWidgetFieldPatternSelectItemView($data['padroes_estado_field']))
	->setFormName($formulario->getName())
	->setPlaceholder('opcional: item que determina somente a cor');
$padroes_bloqueio_view = (new CWidgetFieldPatternSelectItemView($data['padroes_bloqueio_field']))
	->setFormName($formulario->getName())
	->setPlaceholder('opcional: item como Ping Ativo ou Disponibilidade');

if ($data['templateid'] === null && $data['hostids']) {
	$padroes_view->setPopupParameter('hostids', $data['hostids']);
	$padroes_complemento_view->setPopupParameter('hostids', $data['hostids']);
	$padroes_estado_view->setPopupParameter('hostids', $data['hostids']);
	$padroes_bloqueio_view->setPopupParameter('hostids', $data['hostids']);
}

foreach ($padroes_view->getViewCollection() as ['label' => $label, 'view' => $view, 'class' => $class]) {
	$grade_item->addItem([$label, (new CFormField($view))->addClass($class)]);
}
$grade_item
	->addItem($padroes_view->getTemplates())
	->addItem(new CScriptTag([$padroes_view->getJavaScript()]));

foreach ($padroes_complemento_view->getViewCollection()
		as ['label' => $label, 'view' => $view, 'class' => $class]) {
	$label->setHint(makeHelpIcon(
		'Acrescenta o valor deste item após o principal, por exemplo: 23,17 GB / 31,94 GB.'
	));
	$grade_item->addItem([$label, (new CFormField($view))->addClass($class)]);
}
$grade_item
	->addItem($padroes_complemento_view->getTemplates())
	->addItem(new CScriptTag([$padroes_complemento_view->getJavaScript()]));

$rotulo_separador_complemento = new CLabel('Texto entre os valores', 'separador_complemento');
$rotulo_separador_complemento->setHint(makeHelpIcon(
	'Exemplos: " / " produz 10 / 100; "/" produz 10/100; " de " produz 10 de 100.'
));
$grade_formatacao->addItem([
	$rotulo_separador_complemento,
	new CFormField(
		(new CTextBox('separador_complemento', $data['separador_complemento']))
			->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
			->setAttribute('maxlength', 32)
	)
]);

$grade_estado->addItem([
	new CLabel('Percentual calculado'),
	new CFormField(
		(new CCheckBox('estado_percentual_calculado'))
			->setChecked((int) $data['estado_percentual_calculado'] === 1)
			->setUncheckedValue('0')
			->setLabel('Usar valor principal ÷ complementar × 100 para determinar a cor')
	)
]);

$grade_formatacao->addItem([
	new CLabel('Formato do valor', 'formato'),
	new CFormField(
		(new CSelect('formato'))
			->setId('formato')
			->setValue($data['formato'])
			->addOptions(CSelect::createOptionsFromArray([
				CWidgetFieldMetricList::FORMATO_AUTOMATICO => 'Automático (formatação do Zabbix)',
				CWidgetFieldMetricList::FORMATO_MAPA => 'Mapeamento personalizado',
				CWidgetFieldMetricList::FORMATO_NUMERO => 'Número',
				CWidgetFieldMetricList::FORMATO_PERCENTUAL_FRACAO => 'Percentual (fração × 100)',
				CWidgetFieldMetricList::FORMATO_DATA => 'Data Unix',
				CWidgetFieldMetricList::FORMATO_TEXTO => 'Texto'
			]))
	)
]);

$grade_formatacao->addItem([
	(new CLabel('Mapeamento de exibição', 'mapa'))->addClass('js-formato-mapa'),
	(new CFormField(
		(new CTextArea('mapa', $data['mapa']))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAttribute('placeholder', "1=UP\n0=DOWN")
	))->addClass('js-formato-mapa')
]);

$grade_formatacao->addItem([
	(new CLabel('Casas decimais', 'decimais'))->addClass('js-formato-decimais'),
	(new CFormField(
		(new CNumericBox('decimais', $data['decimais'], 1))->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
	))->addClass('js-formato-decimais')
]);

$grade_formatacao->addItem([
	(new CLabel('Sufixo', 'sufixo'))->addClass('js-formato-numero'),
	(new CFormField(
		(new CTextBox('sufixo', $data['sufixo']))
			->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
			->setAttribute('placeholder', ' ms, %, dias...')
	))->addClass('js-formato-numero')
]);

$grade_formatacao->addItem([
	(new CLabel('Formato da data', 'formato_data'))->addClass('js-formato-data'),
	(new CFormField(
		(new CTextBox('formato_data', $data['formato_data']))->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
	))->addClass('js-formato-data')
]);

$grade_formatacao->addItem([
	new CLabel('Modo de exibição', 'exibicao'),
	new CFormField(
		(new CSelect('exibicao'))
			->setId('exibicao')
			->setValue($data['exibicao'])
			->addOptions(CSelect::createOptionsFromArray([
				CWidgetFieldMetricList::EXIBICAO_VALOR => 'Somente valor atual',
				CWidgetFieldMetricList::EXIBICAO_VALOR_HISTORICO => 'Valor atual + barra histórica',
				CWidgetFieldMetricList::EXIBICAO_HISTORICO => 'Somente barra histórica',
				CWidgetFieldMetricList::EXIBICAO_VALOR_GRAFICO => 'Valor atual + gráfico histórico',
				CWidgetFieldMetricList::EXIBICAO_GRAFICO => 'Somente gráfico histórico'
			]))
	)
]);

foreach ($padroes_estado_view->getViewCollection() as ['label' => $label, 'view' => $view, 'class' => $class]) {
	$label->setHint(makeHelpIcon(
		'Use quando um item deve ser mostrado, mas outro item do mesmo card deve definir a cor.'
	));
	$label->addClass('js-estado-item-alternativo');
	$grade_estado->addItem([
		$label,
		(new CFormField($view))->addClass($class)->addClass('js-estado-item-alternativo')
	]);
}
$grade_estado
	->addItem($padroes_estado_view->getTemplates())
	->addItem(new CScriptTag([$padroes_estado_view->getJavaScript()]));

foreach ($padroes_bloqueio_view->getViewCollection() as ['label' => $label, 'view' => $view, 'class' => $class]) {
	$label->setHint(makeHelpIcon(
		'Quando este item tiver um dos valores críticos abaixo, a linha fica vermelha e pode exibir '.
		'"Indisponível". Com o serviço ativo, continuam valendo as regras próprias da métrica.'
	));
	$grade_estado->addItem([$label, (new CFormField($view))->addClass($class)]);
}
$grade_estado
	->addItem($padroes_bloqueio_view->getTemplates())
	->addItem(new CScriptTag([$padroes_bloqueio_view->getJavaScript()]));

$grade_estado->addItem([
	new CLabel('Valores que indicam indisponibilidade', 'valores_bloqueio_critico'),
	new CFormField(
		(new CTextBox('valores_bloqueio_critico', $data['valores_bloqueio_critico']))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAttribute('placeholder', '0, down')
	)
]);

$grade_estado->addItem([
	new CLabel('Texto quando indisponível', 'texto_bloqueio'),
	new CFormField(
		(new CTextBox('texto_bloqueio', $data['texto_bloqueio']))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAttribute('placeholder', 'Indisponível')
	)
]);

$grade_estado->addItem([
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

$grade_estado->addItem([
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

$grade_estado->addItem([
	(new CLabel('Limite de aviso', 'limite_aviso'))->addClass('js-estado-limiares'),
	(new CFormField(
		(new CTextBox('limite_aviso', $data['limite_aviso']))
			->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
			->setAttribute('placeholder', '50')
	))->addClass('js-estado-limiares')
]);

$grade_estado->addItem([
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
	$grade_estado->addItem([
		(new CLabel($rotulo, $nome))->addClass('js-estado-valores'),
		(new CFormField(
			(new CTextBox($nome, $data[$nome]))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->setAttribute('placeholder', $placeholder)
		))->addClass('js-estado-valores')
	]);
}

$grade_estado->addItem([
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

$rotulo_periodo_historico = (new CLabel('Período histórico', 'historico_dias'))->addClass('js-historico');
$rotulo_periodo_historico->setHint(makeHelpIcon(
	'Usa itens numéricos e a retenção de histórico do Zabbix. Períodos sem amostras aparecem como sem dados.'
));
$grade_formatacao->addItem([
	$rotulo_periodo_historico,
	(new CFormField([
		(new CNumericBox('historico_dias', $data['historico_dias'], 2))
			->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH),
		' dias'
	]))->addClass('js-historico')
]);

$grade_formatacao->addItem([
	(new CLabel('Atenção'))->addClass('js-historico')->addClass('js-historico-aviso-periodo'),
	(new CFormField(
		(new CSpan(
			'Períodos acima de 24 horas podem aumentar significativamente o tempo de carregamento. '.
			'O histórico é consultado novamente a cada atualização do dashboard.'
		))->addClass(ZBX_STYLE_COLOR_WARNING)
	))->addClass('js-historico')->addClass('js-historico-aviso-periodo')
]);

$grade_formatacao->addItem([
	(new CLabel('Percentual acima do histórico'))->addClass('js-historico'),
	(new CFormField(
		(new CCheckBox('historico_mostrar_percentual'))
			->setChecked((int) $data['historico_mostrar_percentual'] === 1)
			->setUncheckedValue('0')
			->setLabel('Mostrar 100,00% disponibilidade ou OK')
	))->addClass('js-historico')
]);

$grade_aparencia->addItem([
	(new CLabel('Cores do histórico'))->addClass('js-historico'),
	(new CFormField(
		(new CCheckBox('historico_cores_personalizadas'))
			->setChecked((int) $data['historico_cores_personalizadas'] === 1)
			->setUncheckedValue('0')
			->setLabel('Personalizar nesta métrica')
	))->addClass('js-historico')
]);

$cores_historicas = [
	'historico_cor_ok' => 'Cor histórica OK',
	'historico_cor_aviso' => 'Cor histórica de aviso',
	'historico_cor_critico' => 'Cor histórica crítica',
	'historico_cor_indisponivel' => 'Cor histórica indisponível',
	'historico_cor_sem_dados' => 'Cor histórica sem dados'
];
foreach ($cores_historicas as $nome => $rotulo) {
	$grade_aparencia->addItem([
		(new CLabel($rotulo, $nome))->addClass('js-historico')->addClass('js-historico-cores'),
		(new CFormField(
			(new CColorPicker($nome))->setColor($data[$nome])
		))->addClass('js-historico')->addClass('js-historico-cores')
	]);
}

$grade_estado->addItem([
	new CLabel('Ausência do item'),
	new CFormField(
		(new CCheckBox('obrigatorio'))
			->setChecked((int) $data['obrigatorio'] === 1)
			->setUncheckedValue('0')
			->setLabel('Marcar o card como sem dados')
	)
]);

$formulario
	->addItem(
		(new CFormFieldsetCollapsible('Item e valor', $grade_item))->setExpanded()
	)
	->addItem(
		new CFormFieldsetCollapsible('Formatação e exibição', $grade_formatacao)
	)
	->addItem(
		new CFormFieldsetCollapsible('Estado e disponibilidade', $grade_estado)
	)
	->addItem(
		new CFormFieldsetCollapsible('Personalizar aparência', $grade_aparencia)
	)
	->addItem(
		(new CScriptTag('dynamic_status_cards_metric_edit_form.init('.json_encode([
			'form_id' => $formulario->getId()
		], JSON_THROW_ON_ERROR).');'))->setOnDocumentReady()
	);

$copiando = array_key_exists('copy', $data);
$saida = [
	'header' => array_key_exists('edit', $data)
		? 'Editar métrica'
		: ($copiando ? 'Copiar métrica' : 'Nova métrica'),
	'script_inline' => $this->readJsFile('metric.edit.js.php', null, ''),
	'body' => $formulario->toString(),
	'buttons' => [[
		'title' => array_key_exists('edit', $data) ? 'Atualizar' : ($copiando ? 'Adicionar cópia' : 'Adicionar'),
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
