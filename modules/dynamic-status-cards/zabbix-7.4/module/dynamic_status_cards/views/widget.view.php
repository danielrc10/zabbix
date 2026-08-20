<?php declare(strict_types = 0);

use Modules\DynamicStatusCards\Includes\{
	CWidgetFieldMetricList,
	IconLibrary
};

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
			$tipo_linha = $linha['tipo'] ?? CWidgetFieldMetricList::TIPO_METRICA;
			if ($tipo_linha === CWidgetFieldMetricList::TIPO_ESPACADOR) {
				$lista->addItem(
					(new CDiv())
						->addClass('dynamic-status-card__espacador')
						->setAttribute('aria-hidden', 'true')
				);
				continue;
			}
			if ($tipo_linha === CWidgetFieldMetricList::TIPO_SEPARADOR) {
				$lista->addItem(
					(new CDiv())
						->addClass('dynamic-status-card__separador')
						->setAttribute('role', 'separator')
				);
				continue;
			}

			$grafico_ativo = in_array($linha['exibicao'], [
				CWidgetFieldMetricList::EXIBICAO_VALOR_GRAFICO,
				CWidgetFieldMetricList::EXIBICAO_GRAFICO
			], true);
			$somente_historico = in_array($linha['exibicao'], [
				CWidgetFieldMetricList::EXIBICAO_HISTORICO,
				CWidgetFieldMetricList::EXIBICAO_GRAFICO
			], true);
			$conteudo_linha = [];
			if (!$somente_historico) {
				$valor = (new CSpan($linha['valor']))->addClass('dynamic-status-card__valor');
				$valor->setAttribute('title', $linha['valor']);
				$icone = IconLibrary::normalize((string) ($linha['icone'] ?? IconLibrary::DEFAULT_ICON));
				$indicador = (new CSpan())->addClass('dynamic-status-card__indicador');
				if ($icone === IconLibrary::NO_ICON) {
					$indicador->addClass('dynamic-status-card__indicador--nenhum');
				}
				elseif ($icone === IconLibrary::DEFAULT_ICON) {
					$indicador->addItem($linha['estado'] === 'neutro' ? '—' : '');
				}
				else {
					$indicador
						->addClass('dynamic-status-card__indicador--icone')
						->addStyle('--dsc-icon-url: url("'.IconLibrary::getUrl($icone).'");')
						->setAttribute('title', pathinfo($icone, PATHINFO_FILENAME));
				}
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

				if ($grafico_ativo) {
					$grafico = (new CTag('svg', true))
						->addClass('dynamic-status-card__historico-grafico')
						->setAttribute('viewBox', '0 0 1000 90')
						->setAttribute('preserveAspectRatio', 'none')
						->setAttribute('role', 'img')
						->setAttribute('aria-label', 'Gráfico histórico de '.$linha['rotulo']);
					foreach ([6, 45, 84] as $y_grade) {
						$grafico->addItem(
							(new CTag('line', true))
								->addClass('dynamic-status-card__grafico-grade')
								->setAttribute('x1', '0')
								->setAttribute('x2', '1000')
								->setAttribute('y1', (string) $y_grade)
								->setAttribute('y2', (string) $y_grade)
						);
					}
					foreach ($linha['historico']['grafico']['limiares'] as $limite) {
						$linha_limite = (new CTag('line', true, new CTag('title', true, $limite['rotulo'])))
							->addClass('dynamic-status-card__grafico-limite')
							->setAttribute('x1', '0')
							->setAttribute('x2', '1000')
							->setAttribute('y1', (string) $limite['y'])
							->setAttribute('y2', (string) $limite['y'])
							->setAttribute('stroke', '#'.$limite['cor']);
						$grafico->addItem($linha_limite);
					}
					foreach ($linha['historico']['grafico']['segmentos'] as $segmento) {
						$grafico->addItem(
							(new CTag('polygon', true))
								->addClass('dynamic-status-card__grafico-area')
								->setAttribute('points', implode(' ', [
									$segmento['x1'].',84',
									$segmento['x1'].','.$segmento['y1'],
									$segmento['x2'].','.$segmento['y2'],
									$segmento['x2'].',84'
								]))
								->setAttribute('fill', '#'.$segmento['cor'])
						);
						$grafico->addItem(
							(new CTag('line', true, new CTag('title', true, $segmento['tooltip'])))
								->addClass('dynamic-status-card__grafico-linha')
								->setAttribute('x1', (string) $segmento['x1'])
								->setAttribute('y1', (string) $segmento['y1'])
								->setAttribute('x2', (string) $segmento['x2'])
								->setAttribute('y2', (string) $segmento['y2'])
								->setAttribute('stroke', '#'.$segmento['cor'])
						);
					}
					foreach ($linha['historico']['grafico']['pontos'] as $ponto) {
						$grafico->addItem(
							(new CTag('circle', true, new CTag('title', true, $ponto['tooltip'])))
								->addClass('dynamic-status-card__grafico-ponto')
								->setAttribute('cx', (string) $ponto['x'])
								->setAttribute('cy', (string) $ponto['y'])
								->setAttribute('r', '4')
								->setAttribute('fill', '#'.$ponto['cor'])
						);
					}
					if ($linha['historico']['grafico']['pontos'] === []) {
						$grafico->addItem(
							(new CTag('text', true, 'Sem dados'))
								->addClass('dynamic-status-card__grafico-sem-dados')
								->setAttribute('x', '500')
								->setAttribute('y', '50')
								->setAttribute('text-anchor', 'middle')
						);
					}
					$visual_historico = $grafico;
				}
				else {
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
					$visual_historico = $barra;
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
				$conteudo_historico[] = $visual_historico;
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
