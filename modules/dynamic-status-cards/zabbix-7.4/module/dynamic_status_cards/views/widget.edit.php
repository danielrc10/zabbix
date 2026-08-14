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

use Modules\DynamicStatusCards\Includes\CWidgetFieldMetricListView;

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
	->setFieldHint(makeHelpIcon(
		'Cada valor diferente dessa tag gera um card. Deixe vazio para gerar um card por host.'
	));

$campo_linhas = (new CWidgetFieldMetricListView($data['fields']['linhas']))
	->setFieldHint(makeHelpIcon(
		'Adicione as métricas pela interface. Cada linha aceita itens exatos ou padrões com *, formatação, '.
		'limiares numéricos e valores exatos. A configuração antiga em JSON é convertida automaticamente.'
	));

$aparencia = (new CWidgetFieldsGroupView('Aparência'))
	->addField(new CWidgetFieldColorView($data['fields']['cor_ok']))
	->addField(new CWidgetFieldColorView($data['fields']['cor_aviso']))
	->addField(new CWidgetFieldColorView($data['fields']['cor_critico']))
	->addField(new CWidgetFieldColorView($data['fields']['cor_sem_dados']))
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
	->addField(new CWidgetFieldSelectView($data['fields']['texto_modo']))
	->addField(
		(new CWidgetFieldColorView($data['fields']['texto_cor']))
			->addRowClass('js-texto-personalizado')
	);

$formulario
	->addField($campo_grupos)
	->addField($campo_hosts)
	->addField($campo_tag)
	->addField($campo_linhas)
	->addField(new CWidgetFieldIntegerBoxView($data['fields']['colunas']))
	->addField(new CWidgetFieldIntegerBoxView($data['fields']['limite_cards']))
	->addFieldsGroup($aparencia)
	->addField(new CWidgetFieldCheckBoxView($data['fields']['mostrar_host']))
	->addField(new CWidgetFieldCheckBoxView($data['fields']['manutencao']))
	->includeJsFile('widget.edit.js.php')
	->initFormJs('widget_form.init('.json_encode([
		'templateid' => $data['templateid']
	], JSON_THROW_ON_ERROR).');')
	->show();
