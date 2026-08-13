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

$formulario = new CWidgetFormView($data);

$campo_hosts = $data['templateid'] === null
	? new CWidgetFieldMultiSelectHostView($data['fields']['hostids'])
	: null;

$campo_tag = (new CWidgetFieldTextBoxView($data['fields']['tag_agrupamento']))
	->setFieldHint(makeHelpIcon(
		'Cada valor diferente dessa tag gera um card. Exemplo: a tag site agrupa todos os itens do mesmo site.'
	));

$campo_linhas = (new CWidgetFieldTextAreaView($data['fields']['linhas']))
	->setAdaptiveWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	->setFieldHint(makeHelpIcon(
		'Informe um array JSON. Cada objeto precisa de rotulo e padrao. O padrão aceita * como curinga. '.
		'Formatos disponíveis: automatico, mapa, numero, data e texto.'
	));

$formulario
	->addField($campo_hosts)
	->addField($campo_tag)
	->addField($campo_linhas)
	->addField(new CWidgetFieldIntegerBoxView($data['fields']['colunas']))
	->addField(new CWidgetFieldIntegerBoxView($data['fields']['limite_cards']))
	->addField(new CWidgetFieldCheckBoxView($data['fields']['mostrar_host']))
	->addField(new CWidgetFieldCheckBoxView($data['fields']['manutencao']))
	->show();
