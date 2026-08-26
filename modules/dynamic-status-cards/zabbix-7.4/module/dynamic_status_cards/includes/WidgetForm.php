<?php declare(strict_types = 0);

/**
 * PT-BR: Campos e validação do formulário de configuração do widget.
 * EN: Widget configuration form fields and validation.
 *
 * Autor / Author: Daniel Carvalho <danielrc10@gmail.com>
 * LinkedIn: https://www.linkedin.com/in/daniel-ti/
 * Licença / License: PolyForm Noncommercial 1.0.0
 * Uso comercial / Commercial use: contato / contact danielrc10@gmail.com
 */

namespace Modules\DynamicStatusCards\Includes;

use CWidgetsData;
use Zabbix\Widgets\{
	CWidgetField,
	CWidgetForm
};
use Zabbix\Widgets\Fields\{
	CWidgetFieldCheckBox,
	CWidgetFieldCheckBoxList,
	CWidgetFieldColor,
	CWidgetFieldIntegerBox,
	CWidgetFieldMultiSelectGroup,
	CWidgetFieldMultiSelectHost,
	CWidgetFieldPatternSelectItem,
	CWidgetFieldRadioButtonList,
	CWidgetFieldSelect,
	CWidgetFieldTags,
	CWidgetFieldTextArea,
	CWidgetFieldTextBox
};

/**
 * PT-BR: Formulário de configuração do widget.
 * EN: Widget configuration form.
 *
 * PT-BR: As métricas usam um campo composto com editor visual e persistência
 * estruturada nos campos do dashboard.
 * EN: Metrics use a composite field with a visual editor and structured
 * persistence in dashboard fields.
 */
class WidgetForm extends CWidgetForm {
	public const MODO_CARDS_HOST = 0;
	public const MODO_CARDS_ITEM = 1;

	public const MOSTRAR_ROTULO_PRIMARIO = 1;
	public const MOSTRAR_ROTULO_SECUNDARIO = 2;

	public const FUNDO_AUTOMATICO = 0;
	public const FUNDO_TRANSPARENTE = 1;
	public const FUNDO_SOLIDO = 2;
	public const FUNDO_GRADIENTE = 3;

	public const TEXTO_AUTOMATICO = 0;
	public const TEXTO_CLARO = 1;
	public const TEXTO_ESCURO = 2;
	public const TEXTO_PERSONALIZADO = 3;

	public const GRADIENTE_HORIZONTAL = 90;
	public const GRADIENTE_DIAGONAL = 135;
	public const GRADIENTE_VERTICAL = 180;

	public function addFields(): self {
		$linhas_padrao = [array_replace(CWidgetFieldMetricList::getMetricDefaults(), [
			'rotulo' => 'Estado',
			'padroes' => ['* Availability'],
			'formato' => CWidgetFieldMetricList::FORMATO_MAPA,
			'mapa' => "1=UP\n0=DOWN",
			'estado_modo' => CWidgetFieldMetricList::ESTADO_VALORES,
			'valores_ok' => '1',
			'valores_critico' => '0'
		])];

		return $this
			->addField($this->isTemplateDashboard()
				? null
				: new CWidgetFieldMultiSelectGroup('groupids', 'Grupos de hosts')
			)
			->addField(
				(new CWidgetFieldMultiSelectHost('hostids', 'Hosts'))
					->setDefault($this->isTemplateDashboard()
						? [
							CWidgetField::FOREIGN_REFERENCE_KEY => CWidgetField::createTypedReference(
								CWidgetField::REFERENCE_DASHBOARD,
								CWidgetsData::DATA_TYPE_HOST_IDS
							)
						]
						: []
				)
			)
			->addField(
				(new CWidgetFieldRadioButtonList('modo_cards', 'Criar um card por', [
					self::MODO_CARDS_HOST => 'Host',
					self::MODO_CARDS_ITEM => 'Item encontrado'
				]))->setDefault(self::MODO_CARDS_HOST)
			)
			->addField(
				(new CWidgetFieldPatternSelectItem('itens_cards', 'Itens que geram os cards'))
					->setDefault(['*'])
			)
			->addField($this->isTemplateDashboard()
				? null
				: (new CWidgetFieldRadioButtonList('evaltype_host', 'Tags de host', [
					TAG_EVAL_TYPE_AND_OR => 'E/OU',
					TAG_EVAL_TYPE_OR => 'Ou'
				]))->setDefault(TAG_EVAL_TYPE_AND_OR)
			)
			->addField($this->isTemplateDashboard()
				? null
				: new CWidgetFieldTags('host_tags')
			)
			->addField(
				(new CWidgetFieldRadioButtonList('evaltype_item', 'Etiquetas de itens', [
					TAG_EVAL_TYPE_AND_OR => 'E/OU',
					TAG_EVAL_TYPE_OR => 'Ou'
				]))->setDefault(TAG_EVAL_TYPE_AND_OR)
			)
			->addField(new CWidgetFieldTags('item_tags'))
			->addField(
				(new CWidgetFieldTextBox('tag_agrupamento', 'Tag usada para agrupar os cards'))
					->setDefault('')
			)
			->addField(
				(new CWidgetFieldCheckBoxList('mostrar_rotulos', 'Mostrar', [
					self::MOSTRAR_ROTULO_PRIMARIO => 'Rótulo principal',
					self::MOSTRAR_ROTULO_SECUNDARIO => 'Rótulo secundário'
				]))->setDefault([self::MOSTRAR_ROTULO_PRIMARIO])
			)
			->addField(
				(new CWidgetFieldTextArea('rotulo_primario', 'Texto'))
					->setDefault('{CARD.NAME}')
					->prefixLabel('Rótulo principal')
			)
			->addField(
				(new CWidgetFieldTextArea('rotulo_secundario', 'Texto'))
					->setDefault('{HOST.NAME}')
					->prefixLabel('Rótulo secundário')
			)
			->addField(
				(new CWidgetFieldMetricList('linhas', 'Métricas exibidas nos cards'))
					->setDefault($linhas_padrao)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				(new CWidgetFieldCheckBox('colunas_automaticas', 'Ajustar colunas automaticamente'))
					->setDefault(1)
			)
			->addField(
				(new CWidgetFieldIntegerBox('colunas', 'Limite manual de colunas', 1, 6))
					->setDefault(4)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY)
			)
			->addField(
				(new CWidgetFieldIntegerBox('largura_maxima_card', 'Largura máxima de cada card (px)', 160, 1000))
					->setDefault(320)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY)
			)
			->addField(
				(new CWidgetFieldIntegerBox('limite_cards', 'Máximo de cards', 1, 200))
					->setDefault(100)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY)
			)
			->addField(
				(new CWidgetFieldColor('cor_ok', 'Cor OK'))->setDefault('2ECA8B')
			)
			->addField(
				(new CWidgetFieldColor('cor_aviso', 'Cor de aviso'))->setDefault('FFD54F')
			)
			->addField(
				(new CWidgetFieldColor('cor_critico', 'Cor crítica'))->setDefault('FF465C')
			)
			->addField(
				(new CWidgetFieldColor('cor_sem_dados', 'Cor sem dados'))->setDefault('768D99')
			)
			->addField(
				(new CWidgetFieldTextBox('icone_cabecalho', 'Indicador do cabeçalho'))
					->setDefault(IconLibrary::DEFAULT_ICON)
			)
			->addField(
				(new CWidgetFieldSelect('fundo_modo', 'Fundo dos cards', [
					self::FUNDO_AUTOMATICO => 'Automático (tema do Zabbix)',
					self::FUNDO_TRANSPARENTE => 'Transparente',
					self::FUNDO_SOLIDO => 'Cor sólida',
					self::FUNDO_GRADIENTE => 'Gradiente'
				]))->setDefault(self::FUNDO_AUTOMATICO)
			)
			->addField(
				(new CWidgetFieldColor('fundo_cor', 'Cor do fundo dos cards'))->setDefault('1F2937')
			)
			->addField(
				(new CWidgetFieldColor('gradiente_cor_inicial', 'Cor inicial dos cards'))->setDefault('1F2937')
			)
			->addField(
				(new CWidgetFieldColor('gradiente_cor_final', 'Cor final dos cards'))->setDefault('0F766E')
			)
			->addField(
				(new CWidgetFieldSelect('gradiente_direcao', 'Direção do gradiente dos cards', [
					self::GRADIENTE_HORIZONTAL => 'Horizontal',
					self::GRADIENTE_DIAGONAL => 'Diagonal',
					self::GRADIENTE_VERTICAL => 'Vertical'
				]))->setDefault(self::GRADIENTE_DIAGONAL)
			)
			->addField(
				(new CWidgetFieldSelect('widget_fundo_modo', 'Fundo do widget', [
					self::FUNDO_AUTOMATICO => 'Automático (tema do Zabbix)',
					self::FUNDO_TRANSPARENTE => 'Transparente',
					self::FUNDO_SOLIDO => 'Cor sólida',
					self::FUNDO_GRADIENTE => 'Gradiente'
				]))->setDefault(self::FUNDO_AUTOMATICO)
			)
			->addField(
				(new CWidgetFieldColor('widget_fundo_cor', 'Cor do fundo do widget'))->setDefault('1F2937')
			)
			->addField(
				(new CWidgetFieldColor('widget_gradiente_cor_inicial', 'Cor inicial do widget'))
					->setDefault('1F2937')
			)
			->addField(
				(new CWidgetFieldColor('widget_gradiente_cor_final', 'Cor final do widget'))
					->setDefault('0F766E')
			)
			->addField(
				(new CWidgetFieldSelect('widget_gradiente_direcao', 'Direção do gradiente do widget', [
					self::GRADIENTE_HORIZONTAL => 'Horizontal',
					self::GRADIENTE_DIAGONAL => 'Diagonal',
					self::GRADIENTE_VERTICAL => 'Vertical'
				]))->setDefault(self::GRADIENTE_DIAGONAL)
			)
			->addField(
				(new CWidgetFieldSelect('texto_modo', 'Cor do texto', [
					self::TEXTO_AUTOMATICO => 'Automática',
					self::TEXTO_CLARO => 'Clara',
					self::TEXTO_ESCURO => 'Escura',
					self::TEXTO_PERSONALIZADO => 'Personalizada'
				]))->setDefault(self::TEXTO_AUTOMATICO)
			)
			->addField(
				(new CWidgetFieldColor('texto_cor', 'Cor personalizada do texto'))->setDefault('F4F6F7')
			)
			->addField(
				new CWidgetFieldCheckBox('manutencao', 'Mostrar hosts em manutenção')
			);
	}

	public function validate(bool $strict = false): array {
		if ($strict && $this->isTemplateDashboard()) {
			$this->getField('hostids')->setValue([
				CWidgetField::FOREIGN_REFERENCE_KEY => CWidgetField::createTypedReference(
					CWidgetField::REFERENCE_DASHBOARD,
					CWidgetsData::DATA_TYPE_HOST_IDS
				)
			]);
		}

		return parent::validate($strict);
	}
}
