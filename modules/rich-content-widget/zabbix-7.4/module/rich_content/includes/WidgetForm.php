<?php declare(strict_types = 0);

/**
 * Campos persistidos e validados pela Widget API do Zabbix.
 */

namespace Modules\RichContent\Includes;

use Zabbix\Widgets\{CWidgetField, CWidgetForm};
use Zabbix\Widgets\Fields\{
	CWidgetFieldCheckBox,
	CWidgetFieldCheckBoxList,
	CWidgetFieldColor,
	CWidgetFieldIntegerBox,
	CWidgetFieldMultiSelectItem,
	CWidgetFieldSelect,
	CWidgetFieldTextArea,
	CWidgetFieldTextBox
};

class WidgetForm extends CWidgetForm {
	public const FORMAT_MARKDOWN = 0;
	public const FORMAT_HTML = 1;

	public const MEDIA_HIDDEN = 0;
	public const MEDIA_FIRST = 1;
	public const MEDIA_LAST = 2;

	public const MEDIA_FIT_CONTAIN = 0;
	public const MEDIA_FIT_COVER = 1;
	public const MEDIA_FIT_FILL = 2;
	public const MEDIA_FIT_NONE = 3;

	public const ASPECT_AUTO = 0;
	public const ASPECT_SQUARE = 1;
	public const ASPECT_4_3 = 2;
	public const ASPECT_16_9 = 3;
	public const ASPECT_3_2 = 4;

	public const FLIP_HORIZONTAL = 1;
	public const FLIP_VERTICAL = 2;

	public const BACKGROUND_TRANSPARENT = 0;
	public const BACKGROUND_SOLID = 1;
	public const BACKGROUND_GRADIENT = 2;

	public const BORDER_NONE = 0;
	public const BORDER_SOLID = 1;
	public const BORDER_DASHED = 2;
	public const BORDER_DOTTED = 3;

	public function addFields(): self {
		$default_content = <<<'MARKDOWN'
# Cabeçalho com macros

**Host:** {HOST.NAME}
**Item:** {ITEM.NAME}
**Último valor:** {ITEM.LASTVALUE}

[[coluna]]

## Dados em tabela

| Campo | Valor |
|---|---:|
| Chave | `{ITEM.KEY}` |
| Estado | **Monitorado** |
MARKDOWN;

		return $this
			->addField(
				(new CWidgetFieldMultiSelectItem('itemid', 'Item para contexto das macros'))
					->setMultiple(false)
			)
			->addField(
				(new CWidgetFieldSelect('content_format', 'Formato do conteúdo', [
					self::FORMAT_MARKDOWN => 'Markdown seguro',
					self::FORMAT_HTML => 'HTML simples e sanitizado'
				]))->setDefault(self::FORMAT_MARKDOWN)
			)
			->addField(
				(new CWidgetFieldTextArea('content', 'Conteúdo'))
					->setDefault($default_content)
					->setMaxLength(65535)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				(new CWidgetFieldIntegerBox('columns', 'Colunas internas', 1, 6))
					->setDefault(2)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY)
			)
			->addField(
				(new CWidgetFieldIntegerBox('gap', 'Espaço entre blocos (px)', 0, 96))
					->setDefault(24)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY)
			)
			->addField(
				(new CWidgetFieldIntegerBox('design_width', 'Largura-base do conteúdo (px)', 320, 2400))
					->setDefault(960)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY)
			)
			->addField(
				(new CWidgetFieldIntegerBox('padding', 'Margem interna (px)', 0, 128))
					->setDefault(24)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY)
			)
			->addField(
				(new CWidgetFieldCheckBox('allow_upscale', 'Ampliar conteúdo quando houver espaço'))
					->setDefault(0)
			)
			->addField(
				(new CWidgetFieldTextArea('media_source', 'URL, caminho local ou imagem base64'))
					->setMaxLength(65535)
			)
			->addField(
				(new CWidgetFieldTextBox('media_alt', 'Descrição acessível da imagem'))
					->setDefault('Imagem do widget')
			)
			->addField(
				(new CWidgetFieldSelect('media_position', 'Posição da mídia principal', [
					self::MEDIA_HIDDEN => 'Não exibir',
					self::MEDIA_FIRST => 'Primeiro bloco',
					self::MEDIA_LAST => 'Último bloco'
				]))->setDefault(self::MEDIA_HIDDEN)
			)
			->addField(
				(new CWidgetFieldIntegerBox('media_width', 'Largura da imagem (px; 0 = automática)', 0, 2400))
					->setDefault(0)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY)
			)
			->addField(
				(new CWidgetFieldIntegerBox('media_height', 'Altura da imagem (px; 0 = automática)', 0, 2400))
					->setDefault(0)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY)
			)
			->addField(
				(new CWidgetFieldSelect('media_fit', 'Ajuste da imagem', [
					self::MEDIA_FIT_CONTAIN => 'Conter (contain)',
					self::MEDIA_FIT_COVER => 'Preencher e cortar (cover)',
					self::MEDIA_FIT_FILL => 'Esticar (fill)',
					self::MEDIA_FIT_NONE => 'Tamanho original (none)'
				]))->setDefault(self::MEDIA_FIT_CONTAIN)
			)
			->addField(
				(new CWidgetFieldSelect('media_aspect', 'Proporção da imagem', [
					self::ASPECT_AUTO => 'Automática',
					self::ASPECT_SQUARE => '1:1',
					self::ASPECT_4_3 => '4:3',
					self::ASPECT_16_9 => '16:9',
					self::ASPECT_3_2 => '3:2'
				]))->setDefault(self::ASPECT_AUTO)
			)
			->addField(
				(new CWidgetFieldSelect('media_rotation', 'Rotação', [
					0 => '0°', 90 => '90°', 180 => '180°', 270 => '270°'
				]))->setDefault(0)
			)
			->addField(
				(new CWidgetFieldCheckBoxList('media_flip', 'Espelhamento', [
					self::FLIP_HORIZONTAL => 'Horizontal',
					self::FLIP_VERTICAL => 'Vertical'
				]))->setDefault([])
			)
			->addField(
				(new CWidgetFieldSelect('background_mode', 'Fundo do widget', [
					self::BACKGROUND_TRANSPARENT => 'Transparente',
					self::BACKGROUND_SOLID => 'Cor sólida',
					self::BACKGROUND_GRADIENT => 'Gradiente de 2 ou 3 cores'
				]))->setDefault(self::BACKGROUND_TRANSPARENT)
			)
			->addField((new CWidgetFieldColor('background_color', 'Cor sólida'))->setDefault('1F2937'))
			->addField((new CWidgetFieldColor('gradient_color_1', 'Gradiente: cor inicial'))->setDefault('0F172A'))
			->addField((new CWidgetFieldColor('gradient_color_2', 'Gradiente: cor intermediária'))->setDefault('0F766E'))
			->addField((new CWidgetFieldColor('gradient_color_3', 'Gradiente: cor final'))->setDefault('2563EB'))
			->addField(
				(new CWidgetFieldCheckBox('gradient_third_color', 'Usar a terceira cor no gradiente'))
					->setDefault(0)
			)
			->addField(
				(new CWidgetFieldIntegerBox('gradient_angle', 'Ângulo do gradiente (graus)', 0, 360))
					->setDefault(135)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY)
			)
			->addField((new CWidgetFieldColor('text_color', 'Cor do texto'))->setDefault('F8FAFC'))
			->addField(
				(new CWidgetFieldSelect('border_style', 'Estilo da borda', [
					self::BORDER_NONE => 'Nenhuma',
					self::BORDER_SOLID => 'Sólida',
					self::BORDER_DASHED => 'Tracejada',
					self::BORDER_DOTTED => 'Pontilhada'
				]))->setDefault(self::BORDER_NONE)
			)
			->addField((new CWidgetFieldColor('border_color', 'Cor da borda'))->setDefault('38BDF8'))
			->addField(
				(new CWidgetFieldIntegerBox('border_width', 'Espessura da borda (px)', 0, 32))
					->setDefault(0)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY)
			)
			->addField(
				(new CWidgetFieldIntegerBox('border_radius', 'Arredondamento (px)', 0, 128))
					->setDefault(0)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY)
			);
	}
}
