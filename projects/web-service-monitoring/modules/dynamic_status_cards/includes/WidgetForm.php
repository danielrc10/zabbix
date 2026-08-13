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
	CWidgetFieldIntegerBox,
	CWidgetFieldMultiSelectHost,
	CWidgetFieldTextArea,
	CWidgetFieldTextBox
};

/**
 * PT-BR: Formulário de configuração do widget.
 * EN: Widget configuration form.
 *
 * PT-BR: As linhas ficam em JSON para que o módulo represente diferentes tipos
 * de recursos que compartilhem uma tag de agrupamento.
 * EN: Rows are stored as JSON so the module can represent different resource
 * types that share a grouping tag.
 */
class WidgetForm extends CWidgetForm {

	private const FORMATOS = ['automatico', 'mapa', 'numero', 'data', 'texto'];

	public function addFields(): self {
		$linhas_padrao = <<<'JSON'
[
  {
    "rotulo": "Estado",
    "padrao": "* Availability",
    "formato": "mapa",
    "mapa": {"1": "UP", "0": "DOWN"},
    "estados": {"1": "ok", "0": "critico"}
  }
]
JSON;

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
					->setDefault('site')
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				(new CWidgetFieldTextArea('linhas', 'Configuração das linhas (JSON)'))
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

		$erros = parent::validate($strict);
		if ($erros) {
			return $erros;
		}

		$linhas = json_decode($this->getFieldValue('linhas'), true);
		if (!is_array($linhas) || !$linhas) {
			return ['A configuração das linhas deve ser um array JSON não vazio.'];
		}

		foreach ($linhas as $indice => $linha) {
			$numero = $indice + 1;
			if (!is_array($linha)) {
				$erros[] = "A linha {$numero} precisa ser um objeto JSON.";
				continue;
			}

			if (!isset($linha['rotulo']) || trim((string) $linha['rotulo']) === '') {
				$erros[] = "A linha {$numero} não possui o campo obrigatório \"rotulo\".";
			}
			if (!isset($linha['padrao']) || trim((string) $linha['padrao']) === '') {
				$erros[] = "A linha {$numero} não possui o campo obrigatório \"padrao\".";
			}

			$formato = $linha['formato'] ?? 'automatico';
			if (!in_array($formato, self::FORMATOS, true)) {
				$erros[] = "O formato da linha {$numero} deve ser automatico, mapa, numero, data ou texto.";
			}
		}

		return $erros;
	}
}
