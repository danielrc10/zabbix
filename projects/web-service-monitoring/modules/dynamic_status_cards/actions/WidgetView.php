<?php declare(strict_types = 0);

/**
 * PT-BR: Consulta, agrupamento e preparação dos cards para apresentação.
 * EN: Data retrieval, grouping, and card preparation for presentation.
 *
 * Autor / Author: Daniel Carvalho <danielrc10@gmail.com>
 * LinkedIn: https://www.linkedin.com/in/daniel-ti/
 * Licença / License: MIT
 */

namespace Modules\DynamicStatusCards\Actions;

use API,
	CControllerDashboardWidgetView,
	CControllerResponseData,
	CSettingsHelper,
	Manager;

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
		$linhas = json_decode($this->fields_values['linhas'], true);
		$linhas = is_array($linhas) ? $linhas : [];

		$dados = [
			'name' => $this->getInput('name', $this->widget->getDefaultName()),
			'cards' => [],
			'colunas' => max(1, min(6, (int) $this->fields_values['colunas'])),
			'mensagem' => '',
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		if (!$linhas) {
			$dados['mensagem'] = 'A configuração das linhas está vazia ou contém um JSON inválido.';
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
		$itens = API::Item()->get([
			'output' => ['itemid', 'hostid', 'units', 'value_type', 'name_resolved', 'key_'],
			'selectHosts' => ['name'],
			'selectTags' => ['tag', 'value'],
			'selectValueMap' => ['mappings'],
			'webitems' => true,
			'hostids' => $hostids,
			'tags' => [[
				'tag' => $tag_agrupamento,
				'operator' => 4
			]],
			'filter' => ['status' => ITEM_STATUS_ACTIVE],
			'limit' => 10000
		]);

		$cards = $this->agruparItens($itens, $linhas, $tag_agrupamento);
		uasort($cards, static function(array $a, array $b): int {
			$por_host = strnatcasecmp($a['host'], $b['host']);
			return $por_host !== 0 ? $por_host : strnatcasecmp($a['titulo'], $b['titulo']);
		});
		$cards = array_slice($cards, 0, (int) $this->fields_values['limite_cards'], true);

		$itens_historico = [];
		foreach ($cards as $card) {
			foreach ($card['itens'] as $item) {
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
			$dados['mensagem'] = 'Nenhum item com a tag e os padrões configurados foi encontrado.';
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
			$grupo = $this->obterValorTag($item['tags'] ?? [], $tag_agrupamento);
			if ($grupo === null || $grupo === '') {
				continue;
			}

			$host = $item['hosts'][0]['name'] ?? '';
			$chave_card = $item['hostid'].'|'.$grupo;
			if (!array_key_exists($chave_card, $cards)) {
				$cards[$chave_card] = [
					'titulo' => $grupo,
					'host' => $host,
					'hostid' => $item['hostid'],
					'itens' => array_fill(0, count($linhas), null)
				];
			}

			$nome = (string) ($item['name_resolved'] ?? '');
			foreach ($linhas as $indice => $linha) {
				if ($cards[$chave_card]['itens'][$indice] === null
						&& $this->correspondeAoPadrao($nome, (string) ($linha['padrao'] ?? ''))) {
					$cards[$chave_card]['itens'][$indice] = $item;
					break;
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
				$linha = $this->montarLinha($configuracao, $item, $amostra);
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

	private function montarLinha(array $configuracao, ?array $item, ?array $amostra): array {
		$linha = [
			'rotulo' => (string) ($configuracao['rotulo'] ?? ''),
			'valor' => 'Sem dados',
			'estado' => 'sem_dados',
			'itemid' => $item['itemid'] ?? null,
			'clock' => $amostra['clock'] ?? null
		];

		if ($item === null || $amostra === null) {
			if (($configuracao['obrigatorio'] ?? true) === false) {
				$linha['estado'] = 'neutro';
			}
			return $linha;
		}

		$valor_bruto = $amostra['value'];
		$linha['valor'] = $this->formatarValor($valor_bruto, $item, $configuracao);
		$linha['estado'] = $this->avaliarEstado($valor_bruto, $configuracao);

		return $linha;
	}

	private function formatarValor($valor, array $item, array $configuracao): string {
		$formato = $configuracao['formato'] ?? 'automatico';

		switch ($formato) {
			case 'mapa':
				$mapa = is_array($configuracao['mapa'] ?? null) ? $configuracao['mapa'] : [];
				$chave = (string) $valor;
				return array_key_exists($chave, $mapa) ? (string) $mapa[$chave] : $chave;

			case 'numero':
				$decimais = max(0, min(6, (int) ($configuracao['decimais'] ?? 0)));
				$sufixo = (string) ($configuracao['sufixo'] ?? $item['units'] ?? '');
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
		$estados = is_array($configuracao['estados'] ?? null) ? $configuracao['estados'] : [];
		$chave = (string) $valor;
		if (array_key_exists($chave, $estados)) {
			return $this->normalizarEstado((string) $estados[$chave]);
		}

		if (is_numeric($valor) && is_array($configuracao['limites'] ?? null)) {
			foreach ($configuracao['limites'] as $limite) {
				if (!is_array($limite) || !isset($limite['operador'], $limite['valor'], $limite['estado'])) {
					continue;
				}
				if ($this->comparar((float) $valor, (string) $limite['operador'], (float) $limite['valor'])) {
					return $this->normalizarEstado((string) $limite['estado']);
				}
			}
		}

		return 'neutro';
	}

	private function comparar(float $valor, string $operador, float $limite): bool {
		switch ($operador) {
			case '<': return $valor < $limite;
			case '<=': return $valor <= $limite;
			case '>': return $valor > $limite;
			case '>=': return $valor >= $limite;
			case '==': return $valor == $limite;
			case '!=': return $valor != $limite;
			default: return false;
		}
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

	/**
	 * PT-BR: Implementa apenas o curinga *, preservando [] como texto literal.
	 * EN: Implements only the * wildcard, preserving [] as literal text.
	 */
	private function correspondeAoPadrao(string $texto, string $padrao): bool {
		$expressao = str_replace('\\*', '.*', preg_quote($padrao, '/'));
		return preg_match('/^'.$expressao.'$/iu', $texto) === 1;
	}
}
