<?php declare(strict_types = 0);

/**
 * Configuração visual usando os componentes nativos do Zabbix 7.4.
 *
 * @var CView $this
 * @var array $data
 */

use Modules\RichContent\Includes\WidgetForm;

$form = new CWidgetFormView($data);

$content = (new CWidgetFormFieldsetCollapsibleView('Conteúdo e macros'))
	->setExpanded()
	->addField(
		(new CWidgetFieldMultiSelectItemView($data['fields']['itemid']))
			->setFieldHint(makeHelpIcon(
				'Define o contexto usado pelo resolvedor nativo para {HOST.*}, {ITEM.*}, inventário e macros de usuário.'
			))
	)
	->addField(new CWidgetFieldSelectView($data['fields']['content_format']))
	->addField(
		(new CWidgetFieldTextAreaView($data['fields']['content']))
			->setAdaptiveWidth(ZBX_TEXTAREA_BIG_WIDTH)
			->setFieldHint(makeHelpIcon([
				'Use [[coluna]] sozinho em uma linha para iniciar outro bloco do grid.',
				'Markdown: títulos, listas, links, imagens, tabelas, código, citações e ênfase.',
				'Imagem Markdown: ![alt](URL){width=320 fit=contain rotate=90 flip=h aspect=16/9}.',
				'HTML é sanitizado; scripts, iframes, eventos e estilos inline são removidos.'
			]))
	);

$layout = (new CWidgetFormFieldsetCollapsibleView('Layout e redimensionamento'))
	->setExpanded()
	->addField(new CWidgetFieldIntegerBoxView($data['fields']['columns']))
	->addField(new CWidgetFieldIntegerBoxView($data['fields']['gap']))
	->addField(
		(new CWidgetFieldIntegerBoxView($data['fields']['design_width']))
			->setFieldHint(makeHelpIcon(
				'O JavaScript usa esta largura como prancheta e aplica transform: scale() para caber no painel sem rolagem.'
			))
	)
	->addField(new CWidgetFieldIntegerBoxView($data['fields']['padding']))
	->addField(new CWidgetFieldCheckBoxView($data['fields']['allow_upscale']));

$media = (new CWidgetFormFieldsetCollapsibleView('Mídia principal'))
	->addField(
		(new CWidgetFieldTextAreaView($data['fields']['media_source']))
			->setAdaptiveWidth(ZBX_TEXTAREA_BIG_WIDTH)
			->setFieldHint(makeHelpIcon(
				'Cole uma URL HTTP(S), um caminho web local ou uma data URI. O seletor de arquivo será exibido abaixo.'
			))
	)
	->addField((new CWidgetFieldTextBoxView($data['fields']['media_alt']))->addRowClass('js-rich-media-option'))
	->addField(new CWidgetFieldSelectView($data['fields']['media_position']))
	->addField((new CWidgetFieldIntegerBoxView($data['fields']['media_width']))->addRowClass('js-rich-media-option'))
	->addField((new CWidgetFieldIntegerBoxView($data['fields']['media_height']))->addRowClass('js-rich-media-option'))
	->addField((new CWidgetFieldSelectView($data['fields']['media_fit']))->addRowClass('js-rich-media-option'))
	->addField((new CWidgetFieldSelectView($data['fields']['media_aspect']))->addRowClass('js-rich-media-option'))
	->addField((new CWidgetFieldSelectView($data['fields']['media_rotation']))->addRowClass('js-rich-media-option'))
	->addField(
		(new CWidgetFieldCheckBoxListView($data['fields']['media_flip']))
			->setColumns(2)
			->addRowClass('js-rich-media-option')
	);

$background = (new CWidgetFormFieldsetCollapsibleView('Fundo'))
	->addField(new CWidgetFieldSelectView($data['fields']['background_mode']))
	->addField((new CWidgetFieldColorView($data['fields']['background_color']))->addRowClass('js-rich-background-solid'))
	->addField((new CWidgetFieldColorView($data['fields']['gradient_color_1']))->addRowClass('js-rich-background-gradient'))
	->addField((new CWidgetFieldColorView($data['fields']['gradient_color_2']))->addRowClass('js-rich-background-gradient'))
	->addField((new CWidgetFieldCheckBoxView($data['fields']['gradient_third_color']))->addRowClass('js-rich-background-gradient'))
	->addField(
		(new CWidgetFieldColorView($data['fields']['gradient_color_3']))
			->addRowClass('js-rich-background-gradient js-rich-gradient-third')
	)
	->addField((new CWidgetFieldIntegerBoxView($data['fields']['gradient_angle']))->addRowClass('js-rich-background-gradient'))
	->addField(new CWidgetFieldColorView($data['fields']['text_color']));

$border = (new CWidgetFormFieldsetCollapsibleView('Borda'))
	->addField(new CWidgetFieldSelectView($data['fields']['border_style']))
	->addField((new CWidgetFieldColorView($data['fields']['border_color']))->addRowClass('js-rich-border-option'))
	->addField((new CWidgetFieldIntegerBoxView($data['fields']['border_width']))->addRowClass('js-rich-border-option'))
	->addField(new CWidgetFieldIntegerBoxView($data['fields']['border_radius']));

$form
	->addFieldset($content)
	->addFieldset($layout)
	->addFieldset($media)
	->addFieldset($background)
	->addFieldset($border)
	->includeJsFile('widget.edit.js.php')
	->initFormJs('widget_form.init('.json_encode([
		'background_transparent' => WidgetForm::BACKGROUND_TRANSPARENT,
		'background_solid' => WidgetForm::BACKGROUND_SOLID,
		'background_gradient' => WidgetForm::BACKGROUND_GRADIENT,
		'media_hidden' => WidgetForm::MEDIA_HIDDEN,
		'border_none' => WidgetForm::BORDER_NONE
	], JSON_THROW_ON_ERROR).');')
	->show();
