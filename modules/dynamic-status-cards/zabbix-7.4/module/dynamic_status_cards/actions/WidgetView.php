<?php declare(strict_types = 0);

/**
 * PT-BR: Consulta, agrupamento e preparação dos cards para apresentação.
 * EN: Data retrieval, grouping, and card preparation for presentation.
 *
 * Autor / Author: Daniel Carvalho <danielrc10@gmail.com>
 * LinkedIn: https://www.linkedin.com/in/daniel-ti/
 * Licença / License: PolyForm Noncommercial 1.0.0
 * Uso comercial / Commercial use: contato / contact danielrc10@gmail.com
 */

namespace Modules\DynamicStatusCards\Actions;

use API,
	CControllerDashboardWidgetView,
	CControllerResponseData,
	CSettingsHelper,
	Manager;

use Modules\DynamicStatusCards\Includes\CWidgetFieldMetricList;

/**
 * PT-BR: Consulta os itens permitidos ao usuário, agrupa-os pela tag configurada
 * e prepara os cards. O módulo não armazena credenciais adicionais.
 * EN: Retrieves items available to the user, groups them by the configured tag,
 * and prepares the cards. The module stores no additional credentials.
 */
class WidgetView extends CControllerDashboardWidgetView {

	private const ESTADOS = [
		'neutro' => 0,
		'ok' => 1,
		'sem_dados' => 2,
		'aviso' => 3,
		'critico' => 4
	];

	protected function doAction(): void {
		$campo_linhas = new CWidgetFieldMetricList('linhas', 'Métricas');
		$campo_linhas->setValue($this->fields_values['linhas'] ?? []);
		$linhas = $campo_linhas->getValue();

		$dados = [
			'name' => $this->getInput('name', $this->widget->getDefaultName()),
			'cards' => [],
			'colunas' => max(1, min(6, (int) $this->fields_values['colunas'])),
			'mensagem' => '',
			'cores' => [
				'ok' => $this->fields_values['cor_ok'] ?? '2ECA8B',
				'aviso' => $this->fields_values['cor_aviso'] ?? 'FFD54F',
				'critico' => $this->fields_values['cor_critico'] ?? 'FF465C',
				'sem_dados' => $this->fields_values['cor_sem_dados'] ?? '768D99'
			],
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		if (!$linhas) {
			$dados['mensagem'] = 'Nenhuma métrica foi configurada para os cards.';
			$this->setResponse(new CControllerResponseData($dados));
			return;
		}

		if ($this->isTemplateDashboard() && !$this->fields_values['hostids']) {
			$dados['mensagem'] = 'Selecione um host para visualizar os cards deste dashboard de template.';
			$this->setResponse(new CControllerResponseData($dados));
			return;
		}

		$hostids = $this->obterHostidsPermitidos();
		if ($hostids === []) {
			$dados['mensagem'] = 'Nenhum host monitorado corresponde à configuração do widget.';
			$this->setResponse(new CControllerResponseData($dados));
			return;
		}

		$tag_agrupamento = trim((string) $this->fields_values['tag_agrupamento']);
		$parametros_itens = [
			'output' => ['itemid', 'hostid', 'units', 'value_type', 'name_resolved', 'key_'],
			'selectHosts' => ['name'],
			'selectTags' => ['tag', 'value'],
			'selectValueMap' => ['mappings'],
			'webitems' => true,
			'hostids' => $hostids,
			'filter' => ['status' => ITEM_STATUS_ACTIVE],
			'limit' => 10000
		];
		if ($tag_agrupamento !== '') {
			$parametros_itens['tags'] = [[
				'tag' => $tag_agrupamento,
				'operator' => 4
			]];
		}
		$itens = API::Item()->get($parametros_itens);

		$cards = $this->agruparItens($itens, $linhas, $tag_agrupamento);
		uasort($cards, static function(array $a, array $b): int {
			$por_host = strnatcasecmp($a['host'], $b['host']);
			return $por_host !== 0 ? $por_host : strnatcasecmp($a['titulo'], $b['titulo']);
		});
		$cards = array_slice($cards, 0, (int) $this->fields_values['limite_cards'], true);

		$itens_historico = [];
		foreach ($cards as $card) {
			foreach (array_merge($card['itens'], $card['itens_estado']) as $item) {
				if ($item !== null) {
					$itens_historico[$item['itemid']] = $item;
				}
			}
		}

		$historico = [];
		if ($itens_historico) {
			$periodo = timeUnitToSeconds(CSettingsHelper::get(CSettingsHelper::HISTORY_PERIOD));
			$historico = Manager::History()->getLastValues(array_values($itens_historico), 1, $periodo);
		}

		$dados['cards'] = $this->montarCards($cards, $linhas, $historico);
		if (!$dados['cards']) {
			$dados['mensagem'] = 'Nenhum item correspondente aos hosts, à tag e aos padrões configurados foi encontrado.';
		}

		$this->setResponse(new CControllerResponseData($dados));
	}

	/**
	 * PT-BR: Retorna null para todos os hosts ou [] quando nenhum é encontrado.
	 * EN: Returns null for all hosts or [] when no matching host is found.
	 */
	private function obterHostidsPermitidos(): ?array {
		$hostids = $this->fields_values['hostids'] ?: null;
		$filtrar_manutencao = (int) $this->fields_values['manutencao'] !== 1;

		if ($hostids === null && !$filtrar_manutencao) {
			return null;
		}

		$hosts = API::Host()->get([
			'output' => [],
			'hostids' => $hostids,
			'filter' => $filtrar_manutencao
				? ['maintenance_status' => HOST_MAINTENANCE_STATUS_OFF]
				: null,
			'monitored_hosts' => true,
			'preservekeys' => true
		]);

		return array_keys($hosts);
	}

	private function agruparItens(array $itens, array $linhas, string $tag_agrupamento): array {
		$cards = [];

		foreach ($itens as $item) {
			$host = $item['hosts'][0]['name'] ?? '';
			$grupo = $tag_agrupamento === ''
				? $host
				: $this->obterValorTag($item['tags'] ?? [], $tag_agrupamento);
			if ($grupo === null || $grupo === '') {
				continue;
			}

			$chave_card = $tag_agrupamento === '' ? (string) $item['hostid'] : $item['hostid'].'|'.$grupo;
			if (!array_key_exists($chave_card, $cards)) {
				$cards[$chave_card] = [
					'titulo' => $grupo,
					'host' => $host,
					'hostid' => $item['hostid'],
					'itens' => array_fill(0, count($linhas), null),
					'itens_estado' => array_fill(0, count($linhas), null)
				];
			}

			$nome = (string) ($item['name_resolved'] ?? '');
			foreach ($linhas as $indice => $linha) {
				if ($cards[$chave_card]['itens'][$indice] === null
						&& $this->correspondeAAlgumPadrao($nome, $linha['padroes'] ?? [])) {
					$cards[$chave_card]['itens'][$indice] = $item;
				}

				$padroes_estado = $linha['padroes_estado'] ?? [];
				if ($padroes_estado && $cards[$chave_card]['itens_estado'][$indice] === null
						&& $this->correspondeAAlgumPadrao($nome, $padroes_estado)) {
					$cards[$chave_card]['itens_estado'][$indice] = $item;
				}
			}
		}

		return $cards;
	}

	private function montarCards(array $cards, array $linhas, array $historico): array {
		$resultado = [];

		foreach ($cards as $card) {
			$linhas_card = [];
			$estado_card = 'neutro';

			foreach ($linhas as $indice => $configuracao) {
				$item = $card['itens'][$indice];
				$amostra = $item !== null && isset($historico[$item['itemid']][0])
					? $historico[$item['itemid']][0]
					: null;
				$item_estado = $card['itens_estado'][$indice];
				$amostra_estado = $item_estado !== null && isset($historico[$item_estado['itemid']][0])
					? $historico[$item_estado['itemid']][0]
					: null;
				$linha = $this->montarLinha($configuracao, $item, $amostra, $amostra_estado);
				$linhas_card[] = $linha;

				if (self::ESTADOS[$linha['estado']] > self::ESTADOS[$estado_card]) {
					$estado_card = $linha['estado'];
				}
			}

			$resultado[] = [
				'titulo' => $card['titulo'],
				'host' => (int) $this->fields_values['mostrar_host'] === 1 ? $card['host'] : '',
				'hostid' => $card['hostid'],
				'estado' => $estado_card,
				'linhas' => $linhas_card
			];
		}

		return $resultado;
	}

	private function montarLinha(array $configuracao, ?array $item, ?array $amostra,
			?array $amostra_estado): array {
		$linha = [
			'rotulo' => (string) ($configuracao['rotulo'] ?? ''),
			'valor' => 'Sem dados',
			'estado' => 'sem_dados',
			'itemid' => $item['itemid'] ?? null,
			'clock' => $amostra['clock'] ?? null
		];

		if ($item === null || $amostra === null) {
			if ((int) ($configuracao['obrigatorio'] ?? 1) === 0) {
				$linha['estado'] = 'neutro';
				$linha['valor'] = '—';
			}
			return $linha;
		}

		$valor_bruto = $amostra['value'];
		$linha['valor'] = $this->formatarValor($valor_bruto, $item, $configuracao);

		if (($configuracao['padroes_estado'] ?? []) !== []) {
			if ($amostra_estado === null) {
				return $linha;
			}
			$valor_bruto = $amostra_estado['value'];
		}

		$linha['estado'] = $this->avaliarEstado($valor_bruto, $configuracao);

		return $linha;
	}

	private function formatarValor($valor, array $item, array $configuracao): string {
		$formato = $configuracao['formato'] ?? 'automatico';

		switch ($formato) {
			case 'mapa':
				$mapa = $this->obterMapa((string) ($configuracao['mapa'] ?? ''));
				$chave = (string) $valor;
				return array_key_exists($chave, $mapa) ? (string) $mapa[$chave] : $chave;

			case 'numero':
				$decimais = max(0, min(6, (int) ($configuracao['decimais'] ?? 0)));
				$sufixo = (string) ($configuracao['sufixo'] ?? $item['units'] ?? '');
				if ($sufixo !== '' && !preg_match('/^\s/u', $sufixo)) {
					$sufixo = ' '.$sufixo;
				}
				return number_format((float) $valor, $decimais, ',', '.').$sufixo;

			case 'data':
				$formato_data = (string) ($configuracao['formato_data'] ?? 'd/m/Y');
				return date($formato_data, (int) $valor);

			case 'texto':
				return (string) $valor;

			case 'automatico':
			default:
				return formatHistoryValue($valor, $item, false);
		}
	}

	private function avaliarEstado($valor, array $configuracao): string {
		$modo = $configuracao['estado_modo'] ?? CWidgetFieldMetricList::ESTADO_NENHUM;
		if ($modo === CWidgetFieldMetricList::ESTADO_LIMITES) {
			if (!is_numeric($valor)) {
				return 'sem_dados';
			}

			$numero = (float) $valor;
			$aviso = (float) $configuracao['limite_aviso'];
			$critico = (float) $configuracao['limite_critico'];
			if ($configuracao['direcao'] === CWidgetFieldMetricList::DIRECAO_MENOR_PIOR) {
				return $numero <= $critico ? 'critico' : ($numero <= $aviso ? 'aviso' : 'ok');
			}

			return $numero > $critico ? 'critico' : ($numero > $aviso ? 'aviso' : 'ok');
		}

		if ($modo === CWidgetFieldMetricList::ESTADO_VALORES) {
			$chave = trim((string) $valor);
			foreach (['ok', 'aviso', 'critico'] as $estado) {
				if (in_array($chave, $this->separarValores((string) $configuracao['valores_'.$estado]), true)) {
					return $estado;
				}
			}

			return $this->normalizarEstado((string) $configuracao['estado_padrao']);
		}

		return 'neutro';
	}

	private function normalizarEstado(string $estado): string {
		return array_key_exists($estado, self::ESTADOS) ? $estado : 'neutro';
	}

	private function obterValorTag(array $tags, string $nome): ?string {
		foreach ($tags as $tag) {
			if (($tag['tag'] ?? null) === $nome) {
				return (string) ($tag['value'] ?? '');
			}
		}

		return null;
	}

	private function obterMapa(string $texto): array {
		$mapa = [];
		foreach (preg_split('/\R/u', $texto) ?: [] as $linha) {
			if (!str_contains($linha, '=')) {
				continue;
			}
			[$valor, $rotulo] = explode('=', $linha, 2);
			$mapa[trim($valor)] = trim($rotulo);
		}

		return $mapa;
	}

	private function separarValores(string $texto): array {
		return array_values(array_filter(array_map('trim', preg_split('/[,\r\n]+/u', $texto) ?: []), 'strlen'));
	}

	private function correspondeAAlgumPadrao(string $texto, array $padroes): bool {
		foreach ($padroes as $padrao) {
			if ($this->correspondeAoPadrao($texto, (string) $padrao)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * PT-BR: Implementa apenas o curinga *, preservando [] como texto literal.
	 * EN: Implements only the * wildcard, preserving [] as literal text.
	 */
	private function correspondeAoPadrao(string $texto, string $padrao): bool {
		$expressao = str_replace('\\*', '.*', preg_quote($padrao, '/'));
		return preg_match('/^'.$expressao.'$/iu', $texto) === 1;
	}
}
