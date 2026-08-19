<?php declare(strict_types = 0);

/**
 * PT-BR: Lista visual das métricas configuradas no widget.
 * EN: Visual list of metrics configured in the widget.
 *
 * Autor / Author: Daniel Carvalho <danielrc10@gmail.com>
 * Licença / License: PolyForm Noncommercial 1.0.0
 * Uso comercial / Commercial use: contato / contact danielrc10@gmail.com
 */

namespace Modules\DynamicStatusCards\Includes;

use CButton,
	CCol,
	CColHeader,
	CDiv,
	CList,
	CRow,
	CSpan,
	CTable,
	CTag,
	CTemplateTag,
	CVar,
	CWidgetFieldView;

class CWidgetFieldMetricListView extends CWidgetFieldView {

	public function __construct(CWidgetFieldMetricList $field) {
		$this->field = $field;
	}

	public function getFocusableElementId(): string {
		return 'list_'.$this->field->getName();
	}

	public function getView(): CTag {
		$acoes = [
			(new CButton('edit', 'Editar'))->addClass(ZBX_STYLE_BTN_LINK)->removeId(),
			(new CButton('remove', 'Remover'))->addClass(ZBX_STYLE_BTN_LINK)->removeId()
		];

		$modelo = new CTemplateTag($this->field->getName().'-row-tmpl', new CRow([
			(new CDiv('#{rotulo}'))->addClass('text'),
			(new CDiv('#{padroes_resumo}'))->addClass('text'),
			(new CDiv('#{regra_resumo}'))->addClass('text'),
			(new CList(array_merge($acoes, [(new CSpan())->addClass('js-metrica-data')])))
				->addClass(ZBX_STYLE_HOR_LIST)
		]));

		$cabecalho = [
			(new CColHeader('Métrica'))->addStyle('width: 22%')->addItem($modelo),
			(new CColHeader('Item ou padrão'))->addStyle('width: 43%'),
			(new CColHeader('Regra de estado'))->addStyle('width: 25%'),
			'Ações'
		];

		$tabela = (new CTable())
			->setId('list_'.$this->field->getName())
			->setHeader($cabecalho);

		foreach ($this->field->getValue() as $indice => $metrica) {
			$dados = [];
			foreach ($metrica as $campo => $valor) {
				$dados[] = new CVar($this->field->getName().'['.$indice.']['.$campo.']', $valor);
			}

			$padroes_resumo = implode(', ', $metrica['padroes']);
			if (($metrica['padroes_complemento'] ?? []) !== []) {
				$padroes_resumo .= ' / '.implode(', ', $metrica['padroes_complemento']);
			}

			$tabela->addRow((new CRow([
				(new CDiv($metrica['rotulo']))->addClass('text'),
				(new CDiv($padroes_resumo))->addClass('text'),
				(new CDiv(self::resumirRegra($metrica)))->addClass('text'),
				(new CList(array_merge($acoes, [(new CSpan($dados))->addClass('js-metrica-data')])))
					->addClass(ZBX_STYLE_HOR_LIST)
			]))->setAttribute('data-index', $indice));
		}

		$tabela->addRow(
			(new CCol(
				(new CButton('add', 'Adicionar métrica'))
					->addClass(ZBX_STYLE_BTN_LINK)
					->setEnabled(!$this->isDisabled())
			))->setColSpan(count($cabecalho))
		);

		return $tabela;
	}

	public static function resumirRegra(array $metrica): string {
		$resumo = '';
		switch ($metrica['estado_modo']) {
			case CWidgetFieldMetricList::ESTADO_LIMITES:
				$sentido = $metrica['direcao'] === CWidgetFieldMetricList::DIRECAO_MAIOR_PIOR
					? 'maior é pior'
					: 'menor é pior';
				$resumo = 'Limiares: aviso '.$metrica['limite_aviso'].', crítico '.$metrica['limite_critico'].
					' ('.$sentido.')';
				break;

			case CWidgetFieldMetricList::ESTADO_VALORES:
				$resumo = 'Valores exatos';
				break;

			default:
				$resumo = 'Somente informativa';
		}

		if (($metrica['padroes_bloqueio'] ?? []) !== []) {
			$resumo .= ' · com indisponibilidade';
		}
		if ((int) ($metrica['estado_percentual_calculado'] ?? 0) === 1) {
			$resumo .= ' · percentual calculado';
		}
		if ((int) ($metrica['mostrar_rotulo'] ?? 1) === 0) {
			$resumo .= ' · nome oculto';
		}

		if (($metrica['exibicao'] ?? CWidgetFieldMetricList::EXIBICAO_VALOR)
				!== CWidgetFieldMetricList::EXIBICAO_VALOR) {
			$resumo .= ' · histórico '.(int) ($metrica['historico_dias'] ?? 1).'d';
		}

		return $resumo;
	}
}
