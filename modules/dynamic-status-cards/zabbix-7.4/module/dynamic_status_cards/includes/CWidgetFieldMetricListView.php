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
			(new CButton('copy', 'Copiar'))->addClass(ZBX_STYLE_BTN_LINK)->removeId(),
			(new CButton('remove', 'Remover'))->addClass(ZBX_STYLE_BTN_LINK)->removeId()
		];

		$modelo = new CTemplateTag($this->field->getName().'-row-tmpl', new CRow([
			(new CCol((new CDiv())->addClass(ZBX_STYLE_DRAG_ICON)))
				->addClass('table-col-handle')
				->addClass(ZBX_STYLE_TD_DRAG_ICON),
			(new CDiv('#{rotulo}'))->addClass('text'),
			(new CDiv('#{padroes_resumo}'))->addClass('text'),
			(new CDiv('#{regra_resumo}'))->addClass('text'),
			(new CList(array_merge($acoes, [(new CSpan())->addClass('js-metrica-data')])))
				->addClass(ZBX_STYLE_HOR_LIST)
		]));

		$cabecalho = [
			'',
			(new CColHeader('Métrica'))->addStyle('width: 21%')->addItem($modelo),
			(new CColHeader('Item ou padrão'))->addStyle('width: 39%'),
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
				$padroes_resumo .= ($metrica['separador_complemento'] ?? ' / ').
					implode(', ', $metrica['padroes_complemento']);
			}

			$tipo = $metrica['tipo'] ?? CWidgetFieldMetricList::TIPO_METRICA;
			if ($tipo !== CWidgetFieldMetricList::TIPO_METRICA) {
				$padroes_resumo = '';
			}
			$rotulo = $tipo === CWidgetFieldMetricList::TIPO_ESPACADOR
				? '(Espaço vazio)'
				: ($tipo === CWidgetFieldMetricList::TIPO_SEPARADOR
					? '(Separador horizontal)'
					: $metrica['rotulo']);

			$tabela->addRow((new CRow([
				(new CCol((new CDiv())->addClass(ZBX_STYLE_DRAG_ICON)))
					->addClass('table-col-handle')
					->addClass(ZBX_STYLE_TD_DRAG_ICON),
				(new CDiv($rotulo))->addClass('text'),
				(new CDiv($padroes_resumo))->addClass('text'),
				(new CDiv(self::resumirRegra($metrica)))->addClass('text'),
				(new CList(array_merge($acoes, [(new CSpan($dados))->addClass('js-metrica-data')])))
					->addClass(ZBX_STYLE_HOR_LIST)
			]))->setAttribute('data-index', $indice));
		}

		$tabela->addItem(
			new CTag('tfoot', true,
				new CRow(
					(new CCol(
						(new CButton('add', 'Adicionar linha'))
							->addClass(ZBX_STYLE_BTN_LINK)
							->setEnabled(!$this->isDisabled())
					))->setColSpan(count($cabecalho))
				)
			)
		);

		return $tabela;
	}

	public static function resumirRegra(array $metrica): string {
		$tipo = $metrica['tipo'] ?? CWidgetFieldMetricList::TIPO_METRICA;
		if ($tipo === CWidgetFieldMetricList::TIPO_ESPACADOR) {
			return 'Linha visual sem conteúdo';
		}
		if ($tipo === CWidgetFieldMetricList::TIPO_SEPARADOR) {
			return 'Divisão visual entre seções';
		}

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
			$resumo .= ' · período do dashboard';
			if (($metrica['exibicao'] ?? '') === CWidgetFieldMetricList::EXIBICAO_RESUMO_HISTORICO) {
				$rotulos = [
					CWidgetFieldMetricList::AGREGACAO_HISTORICA_SOMA => 'soma das amostras',
					CWidgetFieldMetricList::AGREGACAO_HISTORICA_MEDIA => 'média das amostras',
					CWidgetFieldMetricList::AGREGACAO_HISTORICA_MINIMO => 'mínimo do período',
					CWidgetFieldMetricList::AGREGACAO_HISTORICA_MAXIMO => 'máximo do período',
					CWidgetFieldMetricList::AGREGACAO_HISTORICA_SOMA_MAXIMOS_DIARIOS
						=> 'soma dos máximos diários',
					CWidgetFieldMetricList::AGREGACAO_HISTORICA_MEDIA_MAXIMOS_DIARIOS
						=> 'média dos máximos diários',
					CWidgetFieldMetricList::AGREGACAO_HISTORICA_AUMENTO_CONTADOR
						=> 'aumento do contador'
				];
				$resumo .= ' · '.($rotulos[$metrica['historico_agregacao'] ?? ''] ?? 'resumo histórico');
			}
		}

		return $resumo;
	}
}
