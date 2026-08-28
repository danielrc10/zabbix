<?php declare(strict_types = 0);

/**
 * PT-BR: Tela de configuração do widget no editor de dashboards.
 * EN: Widget configuration screen in the dashboard editor.
 *
 * Autor / Author: Daniel Carvalho <danielrc10@gmail.com>
 * Licença / License: PolyForm Noncommercial 1.0.0
 * Uso comercial / Commercial use: contato / contact danielrc10@gmail.com
 */

/**
 * PT-BR: Formulário exibido ao editar o widget no dashboard.
 * EN: Form displayed while editing the dashboard widget.
 *
 * @var CView $this
 * @var array $data
 */

use Modules\DynamicStatusCards\Includes\{
	CWidgetFieldMetricListView,
	IconLibrary,
	WidgetForm
};

$formulario = new CWidgetFormView($data);

$campo_grupos = array_key_exists('groupids', $data['fields'])
	? new CWidgetFieldMultiSelectGroupView($data['fields']['groupids'])
	: null;

$campo_hosts = $data['templateid'] === null
	? new CWidgetFieldMultiSelectHostView($data['fields']['hostids'])
	: null;

if ($campo_hosts !== null && $campo_grupos !== null) {
	$campo_hosts->setFilterPreselect([
		'id' => $campo_grupos->getId(),
		'accept' => CMultiSelect::FILTER_PRESELECT_ACCEPT_ID,
		'submit_as' => 'groupid'
	]);
}

$campo_tag = (new CWidgetFieldTextBoxView($data['fields']['tag_agrupamento']))
	->addRowClass('js-modo-cards-host')
	->setFieldHint(makeHelpIcon(
		'No modo por host, cada valor diferente dessa tag gera um card. Deixe vazio para gerar um card por host.'
	));

$campo_itens_cards = (new CWidgetFieldPatternSelectItemView($data['fields']['itens_cards']))
	->setFilterPreselect($campo_hosts !== null
		? [
			'id' => $campo_hosts->getId(),
			'accept' => CMultiSelect::FILTER_PRESELECT_ACCEPT_ID,
			'submit_as' => 'hostid'
		]
		: []
	)
	->setPlaceholder('por exemplo: Arquivos*')
	->addRowClass('js-modo-cards-item')
	->setFieldHint(makeHelpIcon(
		'No modo por item, cada item encontrado por estes padrões gera seu próprio card.'
	));

$campo_linhas = (new CWidgetFieldMetricListView($data['fields']['linhas']))
	->setFieldHint(makeHelpIcon(
		'Adicione as métricas pela interface. Cada linha aceita itens exatos ou padrões com *, formatação, '.
		'limiares numéricos e valores exatos. A configuração antiga em JSON é convertida automaticamente.'
	));

$campo_icone_cabecalho = (new CWidgetFieldTextBoxView($data['fields']['icone_cabecalho']))
	->setFieldHint(makeHelpIcon(
		'Escolha o indicador que representa o estado geral no topo de cada card. A cor acompanha o pior estado do card.'
	));

$origem = (new CWidgetFormFieldsetCollapsibleView('Origem e criação dos cards'))
	->setExpanded()
	->addField($campo_grupos)
	->addField($campo_hosts)
	->addField(new CWidgetFieldRadioButtonListView($data['fields']['modo_cards']))
	->addField($campo_itens_cards)
	->addField($campo_tag);

$criar_ajuda_rotulos = static function() {
	return makeHelpIcon([
		'Macros suportadas:',
		(new CList([
			'{CARD.NAME} — nome original do card, do grupo ou do item',
			'{HOST.*}',
			'{ITEM.*}',
			'{INVENTORY.*}',
			'Macros de usuário'
		]))->addClass(ZBX_STYLE_LIST_DASHED)
	]);
};

$cabecalho = (new CWidgetFormFieldsetCollapsibleView('Cabeçalho dos cards'))
	->addField(
		(new CWidgetFieldCheckBoxListView($data['fields']['mostrar_rotulos']))->setColumns(2)
	)
	->addFieldsGroup(
		(new CWidgetFieldsGroupView('Rótulo principal'))
			->addField(
				(new CWidgetFieldTextAreaView($data['fields']['rotulo_primario']))
					->setAdaptiveWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
					->setFieldHint($criar_ajuda_rotulos())
			)
			->addRowClass('fields-group-rotulo-primario')
	)
	->addFieldsGroup(
		(new CWidgetFieldsGroupView('Rótulo secundário'))
			->addField(
				(new CWidgetFieldTextAreaView($data['fields']['rotulo_secundario']))
					->setAdaptiveWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
					->setFieldHint($criar_ajuda_rotulos())
			)
			->addRowClass('fields-group-rotulo-secundario')
	);

$filtros = (new CWidgetFormFieldsetCollapsibleView('Filtros'))
	->addField(array_key_exists('evaltype_host', $data['fields'])
		? new CWidgetFieldRadioButtonListView($data['fields']['evaltype_host'])
		: null
	)
	->addField(array_key_exists('host_tags', $data['fields'])
		? new CWidgetFieldTagsView($data['fields']['host_tags'])
		: null
	)
	->addField(new CWidgetFieldRadioButtonListView($data['fields']['evaltype_item']))
	->addField(new CWidgetFieldTagsView($data['fields']['item_tags']))
	->addField(new CWidgetFieldCheckBoxView($data['fields']['manutencao']));

$metricas = (new CWidgetFormFieldsetCollapsibleView('Métricas exibidas'))
	->setExpanded()
	->addField($campo_linhas);

$layout = (new CWidgetFormFieldsetCollapsibleView('Layout'))
	->addField(
		(new CWidgetFieldCheckBoxView($data['fields']['colunas_automaticas']))
			->setFieldHint(makeHelpIcon(
				'Quando marcado, calcula as colunas pela largura do widget e pela quantidade atual de cards. '.
				'O limite manual abaixo é ignorado.'
			))
	)
	->addField(
		(new CWidgetFieldIntegerBoxView($data['fields']['colunas']))
			->addRowClass('js-limite-colunas-manual')
	)
	->addField(
		(new CWidgetFieldIntegerBoxView($data['fields']['largura_maxima_card']))
			->setFieldHint(makeHelpIcon(
				'Impede que poucos cards ocupem toda a largura de um widget grande. '.
				'A distribuição automática continua adicionando colunas e linhas conforme os hosts do grupo.'
			))
	)
	->addField(new CWidgetFieldIntegerBoxView($data['fields']['limite_cards']))
	->addField(
		(new CWidgetFieldIntegerBoxView($data['fields']['periodo_maximo_dias']))
			->setFieldHint(makeHelpIcon(
				'Proteção contra consultas muito extensas. Se o período global do dashboard ultrapassar este '.
				'limite, o widget não consulta o banco e informa o motivo. O período nunca é truncado silenciosamente.'
			))
	);

$aparencia = (new CWidgetFormFieldsetCollapsibleView('Personalizar aparência'))
	->addField(new CWidgetFieldColorView($data['fields']['cor_ok']))
	->addField(new CWidgetFieldColorView($data['fields']['cor_aviso']))
	->addField(new CWidgetFieldColorView($data['fields']['cor_critico']))
	->addField(new CWidgetFieldColorView($data['fields']['cor_sem_dados']))
	->addField($campo_icone_cabecalho)
	->addField(new CWidgetFieldSelectView($data['fields']['fundo_modo']))
	->addField(
		(new CWidgetFieldColorView($data['fields']['fundo_cor']))->addRowClass('js-fundo-solido')
	)
	->addField(
		(new CWidgetFieldColorView($data['fields']['gradiente_cor_inicial']))
			->addRowClass('js-fundo-gradiente')
	)
	->addField(
		(new CWidgetFieldColorView($data['fields']['gradiente_cor_final']))
			->addRowClass('js-fundo-gradiente')
	)
	->addField(
		(new CWidgetFieldSelectView($data['fields']['gradiente_direcao']))
			->addRowClass('js-fundo-gradiente')
	)
	->addField(new CWidgetFieldSelectView($data['fields']['widget_fundo_modo']))
	->addField(
		(new CWidgetFieldColorView($data['fields']['widget_fundo_cor']))
			->addRowClass('js-widget-fundo-solido')
	)
	->addField(
		(new CWidgetFieldColorView($data['fields']['widget_gradiente_cor_inicial']))
			->addRowClass('js-widget-fundo-gradiente')
	)
	->addField(
		(new CWidgetFieldColorView($data['fields']['widget_gradiente_cor_final']))
			->addRowClass('js-widget-fundo-gradiente')
	)
	->addField(
		(new CWidgetFieldSelectView($data['fields']['widget_gradiente_direcao']))
			->addRowClass('js-widget-fundo-gradiente')
	)
	->addField(new CWidgetFieldSelectView($data['fields']['texto_modo']))
	->addField(
		(new CWidgetFieldColorView($data['fields']['texto_cor']))
			->addRowClass('js-texto-personalizado')
	);

$formulario
	->addFieldset($origem)
	->addFieldset($cabecalho)
	->addFieldset($filtros)
	->addFieldset($metricas)
	->addFieldset($layout)
	->addFieldset($aparencia)
	->includeJsFile('widget.edit.js.php')
	->initFormJs('widget_form.init('.json_encode([
		'templateid' => $data['templateid'],
		'icones' => IconLibrary::getIcons()
	], JSON_THROW_ON_ERROR).');')
	->show();
