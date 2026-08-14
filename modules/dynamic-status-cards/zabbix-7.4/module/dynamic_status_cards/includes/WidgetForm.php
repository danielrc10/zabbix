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
	CWidgetFieldColor,
	CWidgetFieldIntegerBox,
	CWidgetFieldMultiSelectHost,
	CWidgetFieldSelect,
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
				(new CWidgetFieldTextBox('tag_agrupamento', 'Tag usada para agrupar os cards'))
					->setDefault('')
			)
			->addField(
				(new CWidgetFieldMetricList('linhas', 'Métricas exibidas nos cards'))
					->setDefault($linhas_padrao)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				(new CWidgetFieldIntegerBox('colunas', 'Quantidade de colunas', 1, 6))
					->setDefault(4)
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
				(new CWidgetFieldSelect('fundo_modo', 'Fundo', [
					self::FUNDO_AUTOMATICO => 'Automático (tema do Zabbix)',
					self::FUNDO_TRANSPARENTE => 'Transparente',
					self::FUNDO_SOLIDO => 'Cor sólida',
					self::FUNDO_GRADIENTE => 'Gradiente'
				]))->setDefault(self::FUNDO_AUTOMATICO)
			)
			->addField(
				(new CWidgetFieldColor('fundo_cor', 'Cor do fundo'))->setDefault('1F2937')
			)
			->addField(
				(new CWidgetFieldColor('gradiente_cor_inicial', 'Cor inicial'))->setDefault('1F2937')
			)
			->addField(
				(new CWidgetFieldColor('gradiente_cor_final', 'Cor final'))->setDefault('0F766E')
			)
			->addField(
				(new CWidgetFieldSelect('gradiente_direcao', 'Direção do gradiente', [
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
				new CWidgetFieldCheckBox('mostrar_host', 'Mostrar o nome do host no card')
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
