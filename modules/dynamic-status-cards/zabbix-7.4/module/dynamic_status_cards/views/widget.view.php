<?php declare(strict_types = 0);

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
			$valor = (new CSpan($linha['valor']))->addClass('dynamic-status-card__valor');
			$valor->setAttribute('title', $linha['valor']);
			$indicador = (new CSpan($linha['estado'] === 'neutro' ? '—' : ''))
				->addClass('dynamic-status-card__indicador');

			$lista->addItem(
				(new CDiv([
					(new CSpan($linha['rotulo']))->addClass('dynamic-status-card__rotulo'),
					$valor,
					$indicador
				]))
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
