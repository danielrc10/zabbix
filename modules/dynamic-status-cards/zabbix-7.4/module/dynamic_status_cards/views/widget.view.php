<?php declare(strict_types = 0);

use Modules\DynamicStatusCards\Includes\CWidgetFieldMetricList;

/**
 * PT-BR: Apresentação da grade responsiva de cards.
 * EN: Responsive card grid presentation.
 *
 * Autor / Author: Daniel Carvalho <danielrc10@gmail.com>
 * Licença / License: PolyForm Noncommercial 1.0.0
 * Uso comercial / Commercial use: contato / contact danielrc10@gmail.com
 */

/**
 * PT-BR: Grade responsiva de cards.
 * EN: Responsive card grid.
 *
 * @var CView $this
 * @var array $data
 */

$conteudo = new CDiv();
$conteudo->addClass('dynamic-status-cards');
$conteudo->addClass('dynamic-status-cards--colunas-'.$data['colunas']);
$estilos = [
	'--dsc-ok: #'.$data['cores']['ok'].';'.
	'--dsc-aviso: #'.$data['cores']['aviso'].';'.
	'--dsc-critico: #'.$data['cores']['critico'].';'.
	'--dsc-sem-dados: #'.$data['cores']['sem_dados'].';'
];

if ($data['aparencia']['fundo_css'] !== '') {
	$estilos[] = 'background: '.$data['aparencia']['fundo_css'].';';
}

if ($data['aparencia']['cor_texto'] !== '') {
	$estilos[] = 'color: #'.$data['aparencia']['cor_texto'].';';
}

if ($data['aparencia']['fundo_personalizado']) {
	if ($data['aparencia']['texto_claro']) {
		$estilos[] = '--dsc-card-fundo: rgba(0, 0, 0, .24);'.
			'--dsc-card-borda: rgba(255, 255, 255, .24);'.
			'--dsc-card-divisor: rgba(255, 255, 255, .18);';
	}
	else {
		$estilos[] = '--dsc-card-fundo: rgba(255, 255, 255, .38);'.
			'--dsc-card-borda: rgba(0, 0, 0, .20);'.
			'--dsc-card-divisor: rgba(0, 0, 0, .16);';
	}
}

$conteudo->addStyle(implode('', $estilos));

if ($data['mensagem'] !== '') {
	$conteudo->addItem(
		(new CDiv($data['mensagem']))->addClass('dynamic-status-cards__mensagem')
	);
}
else {
	foreach ($data['cards'] as $card) {
		$cabecalho = new CDiv();
		$cabecalho->addClass('dynamic-status-card__cabecalho');
		$cabecalho->addItem(
			(new CTag('h3', true, $card['titulo']))->addClass('dynamic-status-card__titulo')
		);
		$cabecalho->addItem(
			(new CSpan($card['estado'] === 'neutro' ? '—' : ''))->addClass('dynamic-status-card__estado-geral')
		);

		if ($card['host'] !== '') {
			$cabecalho->addItem(
				(new CDiv($card['host']))->addClass('dynamic-status-card__host')
			);
		}

		$lista = new CDiv();
		$lista->addClass('dynamic-status-card__linhas');
		foreach ($card['linhas'] as $linha) {
			$somente_historico = $linha['exibicao'] === CWidgetFieldMetricList::EXIBICAO_HISTORICO;
			$conteudo_linha = [];
			if (!$somente_historico) {
				$valor = (new CSpan($linha['valor']))->addClass('dynamic-status-card__valor');
				$valor->setAttribute('title', $linha['valor']);
				$indicador = (new CSpan($linha['estado'] === 'neutro' ? '—' : ''))
					->addClass('dynamic-status-card__indicador');
				$conteudo_principal = [];
				if ($linha['mostrar_rotulo']) {
					$conteudo_principal[] = (new CSpan($linha['rotulo']))
						->addClass('dynamic-status-card__rotulo');
				}
				$conteudo_principal[] = $valor;
				$conteudo_principal[] = $indicador;

				$linha_principal = (new CDiv($conteudo_principal))
					->addClass('dynamic-status-card__linha-principal');
				if (!$linha['mostrar_rotulo']) {
					$linha_principal->addClass('dynamic-status-card__linha-principal--sem-rotulo');
				}
				$conteudo_linha[] = $linha_principal;
			}

			if ($linha['historico'] !== null) {
				$percentual_historico = $linha['historico']['percentual_texto'];
				if ($somente_historico && ($linha['mostrar_rotulo'] || $percentual_historico !== '')) {
					$cabecalho_historico = new CDiv();
					$cabecalho_historico->addClass('dynamic-status-card__historico-cabecalho');
					if ($linha['mostrar_rotulo']) {
						$cabecalho_historico->addItem(new CSpan($linha['rotulo']));
					}
					if ($percentual_historico !== '') {
						$cabecalho_historico->addItem(
							(new CSpan($percentual_historico))
								->addClass('dynamic-status-card__historico-resumo')
						);
					}
					$conteudo_linha[] = $cabecalho_historico;
				}

				$barra = (new CDiv())
					->addClass('dynamic-status-card__historico-barra')
					->setAttribute('role', 'img')
					->setAttribute('aria-label', 'Histórico de estados de '.$linha['rotulo']);

				foreach ($linha['historico']['segmentos'] as $segmento) {
					$barra->addItem(
						(new CSpan())
							->addClass('dynamic-status-card__historico-segmento')
							->addClass('dynamic-status-card__historico-segmento--'.$segmento['estado'])
							->addStyle(
								'background-color: #'.$segmento['cor'].';'.
								'flex-grow: '.max(1, (int) $segmento['peso']).';'
							)
							->setAttribute('title', $segmento['tooltip'])
							->setAttribute('aria-label', $segmento['tooltip'])
					);
				}

				$eixo = (new CDiv([
					new CSpan($linha['historico']['inicio_texto']),
					(new CSpan($linha['historico']['meio_texto']))
						->addClass('dynamic-status-card__historico-meio'),
					new CSpan($linha['historico']['fim_texto'])
				]))->addClass('dynamic-status-card__historico-eixo');

				$conteudo_historico = [];
				if (!$somente_historico && $percentual_historico !== '') {
					$conteudo_historico[] = (new CDiv($percentual_historico))
						->addClass('dynamic-status-card__historico-resumo');
				}
				$conteudo_historico[] = $barra;
				$conteudo_historico[] = $eixo;
				$conteudo_linha[] = (new CDiv($conteudo_historico))
					->addClass('dynamic-status-card__historico');
			}

			$lista->addItem(
				(new CDiv($conteudo_linha))
					->addClass('dynamic-status-card__linha')
					->addClass('dynamic-status-card__linha--'.$linha['estado'])
			);
		}

		$conteudo->addItem(
			(new CDiv([$cabecalho, $lista]))
				->addClass('dynamic-status-card')
				->addClass('dynamic-status-card--'.$card['estado'])
		);
	}
}

(new CWidgetView($data))
	->addItem($conteudo)
	->show();
