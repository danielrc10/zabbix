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
	CMacrosResolverHelper,
	Manager;

use Modules\DynamicStatusCards\Includes\{
	CWidgetFieldMetricList,
	IconLibrary,
	WidgetForm
};

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

	private const ROTULOS_ESTADOS_HISTORICOS = [
		'ok' => 'OK',
		'aviso' => 'Aviso',
		'critico' => 'Crítico',
		'indisponivel' => 'Indisponível',
		'sem_dados' => 'Sem dados'
	];

	protected function doAction(): void {
		$inicio_periodo = (int) ($this->fields_values['time_period']['from_ts'] ?? time() - SEC_PER_HOUR);
		$fim_periodo = (int) ($this->fields_values['time_period']['to_ts'] ?? time());
		if ($inicio_periodo > $fim_periodo) {
			[$inicio_periodo, $fim_periodo] = [$fim_periodo, $inicio_periodo];
		}

		$campo_linhas = new CWidgetFieldMetricList('linhas', 'Métricas');
		$campo_linhas->setValue($this->fields_values['linhas'] ?? []);
		$linhas = $campo_linhas->getValue();

		$dados = [
			'name' => $this->getInput('name', $this->widget->getDefaultName()),
			'cards' => [],
			'colunas' => (int) ($this->fields_values['colunas_automaticas'] ?? 1) === 1
				? 0
				: max(1, min(6, (int) $this->fields_values['colunas'])),
			'largura_maxima_card' => max(160, min(1000,
				(int) ($this->fields_values['largura_maxima_card'] ?? 320)
			)),
			'mensagem' => '',
			'cores' => [
				'ok' => $this->fields_values['cor_ok'] ?? '2ECA8B',
				'aviso' => $this->fields_values['cor_aviso'] ?? 'FFD54F',
				'critico' => $this->fields_values['cor_critico'] ?? 'FF465C',
				'sem_dados' => $this->fields_values['cor_sem_dados'] ?? '768D99'
			],
			'aparencia' => $this->montarAparencia(),
			'icone_cabecalho' => IconLibrary::normalize((string) (
				$this->fields_values['icone_cabecalho'] ?? IconLibrary::DEFAULT_ICON
			)),
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
		$modo_cards = (int) ($this->fields_values['modo_cards'] ?? WidgetForm::MODO_CARDS_HOST);
		$padroes_cards = array_values(array_filter(array_map(
			static fn($padrao): string => trim((string) $padrao),
			$this->fields_values['itens_cards'] ?? []
		), static fn(string $padrao): bool => $padrao !== ''));
		if ($modo_cards === WidgetForm::MODO_CARDS_ITEM && $padroes_cards === []) {
			$dados['mensagem'] = 'Informe ao menos um padrão de item para gerar os cards.';
			$this->setResponse(new CControllerResponseData($dados));
			return;
		}
		$parametros_itens = [
			'output' => ['itemid', 'hostid', 'units', 'value_type', 'name_resolved', 'key_'],
			'selectHosts' => ['name'],
			'selectTags' => ['tag', 'value'],
			'selectValueMap' => ['mappings'],
			'webitems' => true,
			'hostids' => $hostids,
			'filter' => ['status' => ITEM_STATUS_ACTIVE],
			'evaltype' => $this->fields_values['evaltype_item'] ?? TAG_EVAL_TYPE_AND_OR,
			'tags' => ($this->fields_values['item_tags'] ?? []) ?: null,
			'limit' => 10000
		];
		if ($tag_agrupamento !== '' && !($this->fields_values['item_tags'] ?? [])) {
			$parametros_itens['tags'] = [[
				'tag' => $tag_agrupamento,
				'operator' => 4
			]];
		}
		$itens = API::Item()->get($parametros_itens);

		$cards = $this->agruparItens($itens, $linhas, $tag_agrupamento, $modo_cards, $padroes_cards);
		uasort($cards, static function(array $a, array $b): int {
			$por_host = strnatcasecmp($a['host'], $b['host']);
			return $por_host !== 0 ? $por_host : strnatcasecmp($a['titulo'], $b['titulo']);
		});
		$cards = array_slice($cards, 0, (int) $this->fields_values['limite_cards'], true);

		$itens_historico = [];
		foreach ($cards as $card) {
			foreach (array_merge(
				$card['itens'],
				$card['itens_complemento'],
				$card['itens_estado'],
				$card['itens_bloqueio']
			) as $item) {
				if ($item !== null) {
					$itens_historico[$item['itemid']] = $item;
				}
			}
		}

		$historico = [];
		if ($itens_historico) {
			$itens_ultimo_valor = array_map(static function(array $item): array {
				$item['source'] = 'history';
				return $item;
			}, array_values($itens_historico));
			$ultimos_valores = Manager::History()->getAggregatedValues(
				$itens_ultimo_valor,
				AGGREGATE_LAST,
				$inicio_periodo,
				$fim_periodo
			) ?? [];
			foreach ($ultimos_valores as $itemid => $amostra) {
				$historico[$itemid] = [$amostra];
			}
		}

		$historicos_agregados = $this->obterHistoricosAgregados(
			$cards,
			$linhas,
			$inicio_periodo,
			$fim_periodo
		);
		$dados['cards'] = $this->montarCards($cards, $linhas, $historico, $historicos_agregados, $dados['cores']);
		if (!$dados['cards']) {
			$dados['mensagem'] = 'Nenhum item correspondente aos hosts, à tag e aos padrões configurados foi encontrado.';
		}

		$this->setResponse(new CControllerResponseData($dados));
	}

	/**
	 * PT-BR: Normaliza as opções visuais e prepara valores CSS seguros.
	 * EN: Normalizes visual options and prepares safe CSS values.
	 */
	private function montarAparencia(): array {
		$modos_fundo = [
			WidgetForm::FUNDO_AUTOMATICO,
			WidgetForm::FUNDO_TRANSPARENTE,
			WidgetForm::FUNDO_SOLIDO,
			WidgetForm::FUNDO_GRADIENTE
		];
		$modos_texto = [
			WidgetForm::TEXTO_AUTOMATICO,
			WidgetForm::TEXTO_CLARO,
			WidgetForm::TEXTO_ESCURO,
			WidgetForm::TEXTO_PERSONALIZADO
		];
		$direcoes = [
			WidgetForm::GRADIENTE_HORIZONTAL,
			WidgetForm::GRADIENTE_DIAGONAL,
			WidgetForm::GRADIENTE_VERTICAL
		];

		$modo_fundo = (int) ($this->fields_values['fundo_modo'] ?? WidgetForm::FUNDO_AUTOMATICO);
		if (!in_array($modo_fundo, $modos_fundo, true)) {
			$modo_fundo = WidgetForm::FUNDO_AUTOMATICO;
		}
		$modo_fundo_widget = (int) (
			$this->fields_values['widget_fundo_modo'] ?? WidgetForm::FUNDO_AUTOMATICO
		);
		if (!in_array($modo_fundo_widget, $modos_fundo, true)) {
			$modo_fundo_widget = WidgetForm::FUNDO_AUTOMATICO;
		}

		$modo_texto = (int) ($this->fields_values['texto_modo'] ?? WidgetForm::TEXTO_AUTOMATICO);
		if (!in_array($modo_texto, $modos_texto, true)) {
			$modo_texto = WidgetForm::TEXTO_AUTOMATICO;
		}

		$direcao = (int) ($this->fields_values['gradiente_direcao'] ?? WidgetForm::GRADIENTE_DIAGONAL);
		if (!in_array($direcao, $direcoes, true)) {
			$direcao = WidgetForm::GRADIENTE_DIAGONAL;
		}
		$direcao_widget = (int) (
			$this->fields_values['widget_gradiente_direcao'] ?? WidgetForm::GRADIENTE_DIAGONAL
		);
		if (!in_array($direcao_widget, $direcoes, true)) {
			$direcao_widget = WidgetForm::GRADIENTE_DIAGONAL;
		}

		$cor_fundo = $this->normalizarCor($this->fields_values['fundo_cor'] ?? '', '1F2937');
		$cor_inicial = $this->normalizarCor($this->fields_values['gradiente_cor_inicial'] ?? '', '1F2937');
		$cor_final = $this->normalizarCor($this->fields_values['gradiente_cor_final'] ?? '', '0F766E');
		$cor_fundo_widget = $this->normalizarCor(
			$this->fields_values['widget_fundo_cor'] ?? '',
			'1F2937'
		);
		$cor_inicial_widget = $this->normalizarCor(
			$this->fields_values['widget_gradiente_cor_inicial'] ?? '',
			'1F2937'
		);
		$cor_final_widget = $this->normalizarCor(
			$this->fields_values['widget_gradiente_cor_final'] ?? '',
			'0F766E'
		);
		$cor_texto_personalizada = $this->normalizarCor($this->fields_values['texto_cor'] ?? '', 'F4F6F7');
		$fundo_css = '';
		$cor_referencia = null;

		switch ($modo_fundo) {
			case WidgetForm::FUNDO_TRANSPARENTE:
				$fundo_css = 'transparent';
				break;

			case WidgetForm::FUNDO_SOLIDO:
				$fundo_css = '#'.$cor_fundo;
				$cor_referencia = $cor_fundo;
				break;

			case WidgetForm::FUNDO_GRADIENTE:
				$fundo_css = 'linear-gradient('.$direcao.'deg, #'.$cor_inicial.' 0%, #'.$cor_final.' 100%)';
				$cor_referencia = $this->misturarCores($cor_inicial, $cor_final);
				break;
		}

		$fundo_widget_css = '';
		$cor_referencia_widget = null;
		switch ($modo_fundo_widget) {
			case WidgetForm::FUNDO_TRANSPARENTE:
				$fundo_widget_css = 'transparent';
				break;

			case WidgetForm::FUNDO_SOLIDO:
				$fundo_widget_css = '#'.$cor_fundo_widget;
				$cor_referencia_widget = $cor_fundo_widget;
				break;

			case WidgetForm::FUNDO_GRADIENTE:
				$fundo_widget_css = 'linear-gradient('.$direcao_widget.'deg, #'.$cor_inicial_widget.
					' 0%, #'.$cor_final_widget.' 100%)';
				$cor_referencia_widget = $this->misturarCores($cor_inicial_widget, $cor_final_widget);
				break;
		}

		switch ($modo_texto) {
			case WidgetForm::TEXTO_CLARO:
				$cor_texto = 'F4F6F7';
				break;

			case WidgetForm::TEXTO_ESCURO:
				$cor_texto = '1F2328';
				break;

			case WidgetForm::TEXTO_PERSONALIZADO:
				$cor_texto = $cor_texto_personalizada;
				break;

			default:
				$cor_texto = ($cor_referencia ?? $cor_referencia_widget) !== null
					? $this->obterCorTextoContrastante($cor_referencia ?? $cor_referencia_widget)
					: '';
		}

		return [
			'modo_fundo' => $modo_fundo,
			'fundo_css' => $fundo_css,
			'fundo_widget_css' => $fundo_widget_css,
			'cor_texto' => $cor_texto,
			'fundo_personalizado' => in_array($modo_fundo, [
				WidgetForm::FUNDO_SOLIDO,
				WidgetForm::FUNDO_GRADIENTE
			], true),
			'texto_claro' => $cor_texto !== '' && $this->corEhClara($cor_texto)
		];
	}

	private function normalizarCor(string $cor, string $padrao): string {
		$cor = strtoupper(ltrim(trim($cor), '#'));
		return preg_match('/^[0-9A-F]{6}$/D', $cor) === 1 ? $cor : $padrao;
	}

	private function misturarCores(string $primeira, string $segunda): string {
		$componentes = [];
		for ($indice = 0; $indice < 6; $indice += 2) {
			$componentes[] = (int) round((hexdec(substr($primeira, $indice, 2))
				+ hexdec(substr($segunda, $indice, 2))) / 2);
		}

		return sprintf('%02X%02X%02X', ...$componentes);
	}

	private function obterCorTextoContrastante(string $fundo): string {
		return $this->corEhClara($fundo) ? '1F2328' : 'F4F6F7';
	}

	private function corEhClara(string $cor): bool {
		$vermelho = hexdec(substr($cor, 0, 2));
		$verde = hexdec(substr($cor, 2, 2));
		$azul = hexdec(substr($cor, 4, 2));
		$luminancia = ($vermelho * 299 + $verde * 587 + $azul * 114) / 1000;

		return $luminancia >= 150;
	}

	/**
	 * PT-BR: Retorna null para todos os hosts ou [] quando nenhum é encontrado.
	 * EN: Returns null for all hosts or [] when no matching host is found.
	 */
	private function obterHostidsPermitidos(): ?array {
		$groupids = null;
		if (!$this->isTemplateDashboard() && ($this->fields_values['groupids'] ?? [])) {
			$groupids = getSubGroups($this->fields_values['groupids']);
		}

		$hostids = $this->fields_values['hostids'] ?: null;
		$tags = !$this->isTemplateDashboard() && ($this->fields_values['host_tags'] ?? [])
			? $this->fields_values['host_tags']
			: null;
		$evaltype = $tags !== null
			? ($this->fields_values['evaltype_host'] ?? TAG_EVAL_TYPE_AND_OR)
			: null;
		$filtrar_manutencao = (int) $this->fields_values['manutencao'] !== 1;

		if ($groupids === null && $hostids === null && $tags === null && !$filtrar_manutencao) {
			return null;
		}

		$hosts = API::Host()->get([
			'output' => [],
			'groupids' => $groupids,
			'hostids' => $hostids,
			'evaltype' => $evaltype,
			'tags' => $tags,
			'filter' => $filtrar_manutencao
				? ['maintenance_status' => HOST_MAINTENANCE_STATUS_OFF]
				: null,
			'monitored_hosts' => true,
			'preservekeys' => true
		]);

		return array_keys($hosts);
	}

	private function agruparItens(array $itens, array $linhas, string $tag_agrupamento, int $modo_cards,
			array $padroes_cards): array {
		if ($modo_cards === WidgetForm::MODO_CARDS_ITEM) {
			return $this->agruparItensPorItem($itens, $linhas, $padroes_cards);
		}

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
					'item_referencia' => $item,
					'itens' => array_fill(0, count($linhas), null),
					'itens_complemento' => array_fill(0, count($linhas), null),
					'itens_estado' => array_fill(0, count($linhas), null),
					'itens_bloqueio' => array_fill(0, count($linhas), null)
				];
			}

			$nome = (string) ($item['name_resolved'] ?? '');
			foreach ($linhas as $indice => $linha) {
				if (($linha['tipo'] ?? CWidgetFieldMetricList::TIPO_METRICA)
						!== CWidgetFieldMetricList::TIPO_METRICA) {
					continue;
				}

				if ($cards[$chave_card]['itens'][$indice] === null
						&& $this->correspondeAAlgumPadrao($nome, $linha['padroes'] ?? [])) {
					$cards[$chave_card]['itens'][$indice] = $item;
				}

				$padroes_complemento = $linha['padroes_complemento'] ?? [];
				if ($padroes_complemento && $cards[$chave_card]['itens_complemento'][$indice] === null
						&& $this->correspondeAAlgumPadrao($nome, $padroes_complemento)) {
					$cards[$chave_card]['itens_complemento'][$indice] = $item;
				}

				$padroes_estado = $linha['padroes_estado'] ?? [];
				if ($padroes_estado && $cards[$chave_card]['itens_estado'][$indice] === null
						&& $this->correspondeAAlgumPadrao($nome, $padroes_estado)) {
					$cards[$chave_card]['itens_estado'][$indice] = $item;
				}

				$padroes_bloqueio = $linha['padroes_bloqueio'] ?? [];
				if ($padroes_bloqueio && $cards[$chave_card]['itens_bloqueio'][$indice] === null
						&& $this->correspondeAAlgumPadrao($nome, $padroes_bloqueio)) {
					$cards[$chave_card]['itens_bloqueio'][$indice] = $item;
				}
			}
		}

		return $cards;
	}

	/**
	 * PT-BR: Cria um card independente para cada item que corresponda ao padrão gerador.
	 * O próprio item gerador alimenta as métricas compatíveis; itens auxiliares de estado,
	 * complemento e disponibilidade continuam sendo procurados no mesmo host.
	 * EN: Creates an independent card for every item matching the generator pattern.
	 */
	private function agruparItensPorItem(array $itens, array $linhas, array $padroes_cards): array {
		$cards = [];
		$itens_por_host = [];

		foreach ($itens as $item) {
			$itens_por_host[(string) $item['hostid']][] = $item;
			$nome = (string) ($item['name_resolved'] ?? '');
			if (!$this->correspondeAAlgumPadrao($nome, $padroes_cards)) {
				continue;
			}

			$host = $item['hosts'][0]['name'] ?? '';
			$chave_card = (string) $item['itemid'];
			$cards[$chave_card] = [
				'titulo' => $nome,
				'host' => $host,
				'hostid' => $item['hostid'],
				'item_referencia' => $item,
				'itens' => array_fill(0, count($linhas), null),
				'itens_complemento' => array_fill(0, count($linhas), null),
				'itens_estado' => array_fill(0, count($linhas), null),
				'itens_bloqueio' => array_fill(0, count($linhas), null)
			];

			foreach ($linhas as $indice => $linha) {
				if (($linha['tipo'] ?? CWidgetFieldMetricList::TIPO_METRICA)
						!== CWidgetFieldMetricList::TIPO_METRICA) {
					continue;
				}

				if ($this->correspondeAAlgumPadrao($nome, $linha['padroes'] ?? [])) {
					$cards[$chave_card]['itens'][$indice] = $item;
				}
				if ($this->correspondeAAlgumPadrao($nome, $linha['padroes_complemento'] ?? [])) {
					$cards[$chave_card]['itens_complemento'][$indice] = $item;
				}
				if ($this->correspondeAAlgumPadrao($nome, $linha['padroes_estado'] ?? [])) {
					$cards[$chave_card]['itens_estado'][$indice] = $item;
				}
				if ($this->correspondeAAlgumPadrao($nome, $linha['padroes_bloqueio'] ?? [])) {
					$cards[$chave_card]['itens_bloqueio'][$indice] = $item;
				}
			}
		}

		foreach ($cards as &$card) {
			foreach ($itens_por_host[(string) $card['hostid']] ?? [] as $item) {
				$nome = (string) ($item['name_resolved'] ?? '');
				foreach ($linhas as $indice => $linha) {
					if (($linha['tipo'] ?? CWidgetFieldMetricList::TIPO_METRICA)
							!== CWidgetFieldMetricList::TIPO_METRICA) {
						continue;
					}

					foreach ([
						'itens_complemento' => 'padroes_complemento',
						'itens_estado' => 'padroes_estado',
						'itens_bloqueio' => 'padroes_bloqueio'
					] as $destino => $origem) {
						if ($card[$destino][$indice] === null
								&& $this->correspondeAAlgumPadrao($nome, $linha[$origem] ?? [])) {
							$card[$destino][$indice] = $item;
						}
					}
				}
			}
		}
		unset($card);

		return $cards;
	}

	/**
	 * PT-BR: Agrega apenas itens numéricos em um número limitado de blocos para
	 * preservar o pior estado sem carregar todas as amostras do período.
	 * EN: Aggregates numeric items into a bounded number of buckets to preserve
	 * the worst state without loading every sample in the period.
	 */
	private function obterHistoricosAgregados(array $cards, array $linhas, int $inicio, int $fim): array {
		$duracao = max(1, $fim - $inicio);
		$grupo = [
			'inicio' => $inicio,
			'fim' => $fim,
			'blocos' => $this->obterQuantidadeBlocosHistoricos($duracao),
			'itens' => [],
			'pontos' => [],
			'itens_extremos_diarios' => [],
			'maximos_diarios' => [],
			'minimos_diarios' => []
		];

		foreach ($cards as $card) {
			foreach ($linhas as $indice => $configuracao) {
				if (($configuracao['tipo'] ?? CWidgetFieldMetricList::TIPO_METRICA)
						!== CWidgetFieldMetricList::TIPO_METRICA) {
					continue;
				}

				if (($configuracao['exibicao'] ?? CWidgetFieldMetricList::EXIBICAO_VALOR)
						=== CWidgetFieldMetricList::EXIBICAO_VALOR) {
					continue;
				}

				$percentual_calculado = (int) ($configuracao['estado_percentual_calculado'] ?? 0) === 1;
				$item_valor = $card['itens'][$indice];
				$item_estado = $percentual_calculado
					? $item_valor
					: ($card['itens_estado'][$indice] ?? $item_valor);
				$item_complemento = $percentual_calculado ? $card['itens_complemento'][$indice] : null;
				$item_bloqueio = $card['itens_bloqueio'][$indice] ?? null;
				$agregacao = $configuracao['historico_agregacao']
					?? CWidgetFieldMetricList::AGREGACAO_HISTORICA_SOMA;
				if ($item_valor !== null
						&& $this->itemSuportaHistorico($item_valor)
						&& in_array($agregacao, [
							CWidgetFieldMetricList::AGREGACAO_HISTORICA_SOMA_MAXIMOS_DIARIOS,
							CWidgetFieldMetricList::AGREGACAO_HISTORICA_MEDIA_MAXIMOS_DIARIOS,
							CWidgetFieldMetricList::AGREGACAO_HISTORICA_AUMENTO_CONTADOR
						], true)) {
					$item_valor['source'] = 'history';
					$grupo['itens_extremos_diarios'][$item_valor['itemid']] = $item_valor;
				}
				foreach ([$item_valor, $item_estado, $item_complemento, $item_bloqueio] as $item) {
					if ($item === null || !$this->itemSuportaHistorico($item)) {
						continue;
					}

					$item['source'] = 'history';
					$grupo['itens'][$item['itemid']] = $item;
				}
			}
		}

		if ($grupo['itens']) {
			$largura = max(1, $grupo['blocos'] - 1);
			$resultado = Manager::History()->getGraphAggregationByWidth(
				array_values($grupo['itens']),
				$grupo['inicio'],
				$grupo['fim'],
				$largura
			);

			foreach ($resultado as $itemid => $dados_item) {
				foreach ($dados_item['data'] ?? [] as $ponto) {
					$indice = max(0, min($grupo['blocos'] - 1, (int) ($ponto['i'] ?? 0)));
					$grupo['pontos'][$itemid][$indice] = $ponto;
				}
			}

			if ($grupo['itens_extremos_diarios']) {
				$intervalo = $this->obterIntervaloMaximosDiarios($grupo['inicio'], $grupo['fim']);
				foreach ([
					AGGREGATE_MAX => 'maximos_diarios',
					AGGREGATE_MIN => 'minimos_diarios'
				] as $funcao => $destino) {
					$resultado_diario = Manager::History()->getAggregationByInterval(
						array_values($grupo['itens_extremos_diarios']),
						$grupo['inicio'],
						$grupo['fim'],
						$funcao,
						$intervalo
					);

					foreach ($resultado_diario as $itemid => $dados_item) {
						foreach ($dados_item['data'] ?? [] as $ponto) {
							if (!isset($ponto['value']) || !is_numeric($ponto['value'])) {
								continue;
							}

							$clock = max($grupo['inicio'], (int) ($ponto['tick'] ?? $grupo['inicio']));
							$dia = zbx_date2str('Y-m-d', $clock);
							$valor = (float) $ponto['value'];
							if (!isset($grupo[$destino][$itemid][$dia])) {
								$grupo[$destino][$itemid][$dia] = $valor;
							}
							elseif ($funcao === AGGREGATE_MAX) {
								$grupo[$destino][$itemid][$dia] = max($grupo[$destino][$itemid][$dia], $valor);
							}
							else {
								$grupo[$destino][$itemid][$dia] = min($grupo[$destino][$itemid][$dia], $valor);
							}
						}
					}
				}
			}
		}

		return $grupo;
	}

	private function obterQuantidadeBlocosHistoricos(int $duracao): int {
		if ($duracao <= SEC_PER_DAY) {
			return 96;
		}

		return min(180, max(72, (int) ceil($duracao / SEC_PER_HOUR)));
	}

	/**
	 * PT-BR: Alinha os blocos aos quartos de hora ou às horas locais, inclusive em fusos com meia hora.
	 * EN: Aligns buckets to local quarter-hours or hours, including half-hour time zones.
	 */
	private function obterIntervaloMaximosDiarios(int $inicio, int $fim): int {
		$intervalo = 3600;
		foreach ([$inicio, $fim] as $clock) {
			$deslocamento = abs((int) date('Z', $clock));
			if ($deslocamento > 0) {
				$intervalo = $this->maximoDivisorComum($intervalo, $deslocamento);
			}
		}

		return max(900, $intervalo);
	}

	private function maximoDivisorComum(int $a, int $b): int {
		while ($b !== 0) {
			$resto = $a % $b;
			$a = $b;
			$b = $resto;
		}

		return max(1, $a);
	}

	private function itemSuportaHistorico(array $item): bool {
		return in_array((int) ($item['value_type'] ?? -1), [
			ITEM_VALUE_TYPE_FLOAT,
			ITEM_VALUE_TYPE_UINT64
		], true);
	}

	private function montarHistoricoLinha(array $configuracao, ?array $item_valor, ?array $item_estado,
			?array $item_complemento, ?array $item_bloqueio, ?array $grupo, array $cores_globais): ?array {
		if ($grupo === null) {
			return null;
		}

		$pontos_valor = $item_valor !== null
			? ($grupo['pontos'][$item_valor['itemid']] ?? [])
			: [];
		if (($configuracao['exibicao'] ?? '') === CWidgetFieldMetricList::EXIBICAO_RESUMO_HISTORICO) {
			return [
				'resumo_texto' => $this->calcularResumoHistorico(
					$pontos_valor,
					$item_valor !== null ? ($grupo['maximos_diarios'][$item_valor['itemid']] ?? []) : [],
					$item_valor !== null ? ($grupo['minimos_diarios'][$item_valor['itemid']] ?? []) : [],
					$item_valor,
					$configuracao
				)
			];
		}

		$cores = $this->obterCoresHistoricas($configuracao, $cores_globais);
		$pontos_estado = $item_estado !== null
			? ($grupo['pontos'][$item_estado['itemid']] ?? [])
			: [];
		$pontos_complemento = $item_complemento !== null
			? ($grupo['pontos'][$item_complemento['itemid']] ?? [])
			: [];
		$pontos_bloqueio = $item_bloqueio !== null
			? ($grupo['pontos'][$item_bloqueio['itemid']] ?? [])
			: [];
		$bloqueio_configurado = ($configuracao['padroes_bloqueio'] ?? []) !== [];
		$percentual_calculado = (int) ($configuracao['estado_percentual_calculado'] ?? 0) === 1;
		$segmentos = [];
		$pontos_grafico = [];
		$blocos_conhecidos = 0;
		$blocos_positivos = 0;
		$duracao = max(1, $grupo['fim'] - $grupo['inicio']);

		for ($indice = 0; $indice < $grupo['blocos']; $indice++) {
			$ponto_valor = $pontos_valor[$indice] ?? null;
			$ponto_estado = $pontos_estado[$indice] ?? null;
			$ponto_complemento = $pontos_complemento[$indice] ?? null;
			$ponto_avaliado = $percentual_calculado
				? $this->calcularPontoPercentual($ponto_estado, $ponto_complemento)
				: $ponto_estado;
			$ponto_regra = !$percentual_calculado && ($configuracao['padroes_estado'] ?? []) === []
				? $this->ajustarPontoParaEstado($ponto_avaliado, $configuracao)
				: $ponto_avaliado;
			$ponto_grafico = $percentual_calculado
				? $this->calcularPontoPercentual($ponto_valor, $ponto_complemento)
				: $this->ajustarPontoParaEstado($ponto_valor, $configuracao);
			$ponto_bloqueio = $pontos_bloqueio[$indice] ?? null;
			$estado = 'sem_dados';

			if ($bloqueio_configurado) {
				if ($ponto_bloqueio !== null) {
					$blocos_conhecidos++;
					if ($this->pontoIndicaIndisponibilidade($ponto_bloqueio, $configuracao)) {
						$estado = 'indisponivel';
					}
					else {
						$blocos_positivos++;
						$estado = $this->avaliarPontoHistorico($ponto_regra, $configuracao);
					}
				}
			}
			else {
				$estado = $this->avaliarPontoHistorico($ponto_regra, $configuracao);
				if ($estado !== 'sem_dados') {
					$blocos_conhecidos++;
					if ($estado === 'ok') {
						$blocos_positivos++;
					}
				}
			}

			$inicio_bloco = $grupo['inicio'] + (int) floor(($indice * $duracao) / $grupo['blocos']);
			$fim_bloco = $grupo['inicio'] + (int) floor((($indice + 1) * $duracao) / $grupo['blocos']);
			if ($ponto_grafico !== null && isset($ponto_grafico['avg']) && is_numeric($ponto_grafico['avg'])) {
				$pontos_grafico[] = [
					'indice' => $indice,
					'valor' => (float) $ponto_grafico['avg'],
					'estado' => $estado,
					'cor' => $cores[$estado],
					'tooltip' => $this->montarTooltipGrafico(
						$inicio_bloco,
						$fim_bloco,
						$ponto_grafico,
						$item_valor,
						$configuracao,
						$percentual_calculado
					)
				];
			}
			$ultimo_indice = count($segmentos) - 1;
			if ($ultimo_indice >= 0 && $segmentos[$ultimo_indice]['estado'] === $estado) {
				$segmentos[$ultimo_indice]['peso']++;
				$segmentos[$ultimo_indice]['fim'] = $fim_bloco;
				$segmentos[$ultimo_indice]['ponto'] = $this->acumularPontoHistorico(
					$segmentos[$ultimo_indice]['ponto'],
					$ponto_avaliado
				);
			}
			else {
				$segmentos[] = [
					'estado' => $estado,
					'cor' => $cores[$estado],
					'peso' => 1,
					'inicio' => $inicio_bloco,
					'fim' => $fim_bloco,
					'ponto' => $this->acumularPontoHistorico(null, $ponto_avaliado)
				];
			}
		}

		foreach ($segmentos as &$segmento) {
			$segmento['tooltip'] = $this->montarTooltipHistorico(
				$segmento['inicio'],
				$segmento['fim'],
				$segmento['estado'],
				$segmento['ponto'],
				$item_estado,
				$configuracao,
				$percentual_calculado
			);
			unset($segmento['inicio'], $segmento['fim'], $segmento['ponto']);
		}
		unset($segmento);

		$percentual_texto = '';
		if ((int) ($configuracao['historico_mostrar_percentual'] ?? 0) === 1) {
			$percentual = $blocos_conhecidos > 0 ? ($blocos_positivos / $blocos_conhecidos) * 100 : null;
			if ($percentual !== null) {
				$rotulo = $bloqueio_configurado ? 'disponibilidade' : 'OK';
				$percentual_texto = number_format($percentual, 2, ',', '.').'% '.$rotulo;
			}
		}

		$formato_eixo = $duracao > SEC_PER_DAY ? 'd/m H:i' : 'H:i';
		$meio = $grupo['inicio'] + (int) floor($duracao / 2);

		return [
			'segmentos' => $segmentos,
			'grafico' => $this->montarGraficoHistorico(
				$pontos_grafico,
				$grupo['blocos'],
				$configuracao,
				$cores
			),
			'percentual_texto' => $percentual_texto,
			'inicio_texto' => zbx_date2str($formato_eixo, $grupo['inicio']),
			'meio_texto' => zbx_date2str($formato_eixo, $meio),
			'fim_texto' => $grupo['fim'] >= time() - 60 ? 'Agora' : zbx_date2str($formato_eixo, $grupo['fim'])
		];
	}

	/**
	 * PT-BR: Calcula o resumo escolhido reutilizando blocos compactos e máximos diários.
	 * EN: Calculates the selected summary from compact buckets and daily maxima.
	 */
	private function calcularResumoHistorico(array $pontos, array $maximos_diarios, array $minimos_diarios, ?array $item,
			array $configuracao): string {
		if ($item === null) {
			return 'Sem dados';
		}

		$soma = 0.0;
		$quantidade = 0;
		$minimo = null;
		$maximo = null;
		foreach ($pontos as $ponto) {
			if (!isset($ponto['avg']) || !is_numeric($ponto['avg'])) {
				continue;
			}

			$amostras = max(1, (int) ($ponto['count'] ?? 1));
			$soma += (float) $ponto['avg'] * $amostras;
			$quantidade += $amostras;
			if (isset($ponto['min']) && is_numeric($ponto['min'])) {
				$minimo = $minimo === null ? (float) $ponto['min'] : min($minimo, (float) $ponto['min']);
			}
			if (isset($ponto['max']) && is_numeric($ponto['max'])) {
				$maximo = $maximo === null ? (float) $ponto['max'] : max($maximo, (float) $ponto['max']);
			}
		}

		$agregacao = $configuracao['historico_agregacao']
			?? CWidgetFieldMetricList::AGREGACAO_HISTORICA_SOMA;
		switch ($agregacao) {
			case CWidgetFieldMetricList::AGREGACAO_HISTORICA_MEDIA:
				$valor = $quantidade > 0 ? $soma / $quantidade : null;
				break;

			case CWidgetFieldMetricList::AGREGACAO_HISTORICA_MINIMO:
				$valor = $minimo;
				break;

			case CWidgetFieldMetricList::AGREGACAO_HISTORICA_MAXIMO:
				$valor = $maximo;
				break;

			case CWidgetFieldMetricList::AGREGACAO_HISTORICA_SOMA_MAXIMOS_DIARIOS:
				$valor = $maximos_diarios ? array_sum($maximos_diarios) : null;
				break;

			case CWidgetFieldMetricList::AGREGACAO_HISTORICA_MEDIA_MAXIMOS_DIARIOS:
				$valor = $maximos_diarios ? array_sum($maximos_diarios) / count($maximos_diarios) : null;
				break;

			case CWidgetFieldMetricList::AGREGACAO_HISTORICA_AUMENTO_CONTADOR:
				$valor = null;
				foreach ($maximos_diarios as $dia => $maximo_diario) {
					if (!array_key_exists($dia, $minimos_diarios)) {
						continue;
					}
					$aumento = max(0.0, (float) $maximo_diario - (float) $minimos_diarios[$dia]);
					$valor = ($valor ?? 0.0) + $aumento;
				}
				break;

			case CWidgetFieldMetricList::AGREGACAO_HISTORICA_SOMA:
			default:
				$valor = $quantidade > 0 ? $soma : null;
		}

		if ($valor === null) {
			return 'Sem dados';
		}

		return $this->formatarValor($valor, $item, $configuracao);
	}

	/**
	 * PT-BR: Converte os blocos agregados em coordenadas SVG e preserva lacunas sem dados.
	 * EN: Converts aggregated buckets into SVG coordinates while preserving no-data gaps.
	 */
	private function montarGraficoHistorico(array $pontos, int $quantidade_blocos, array $configuracao,
			array $cores): array {
		if ($pontos === []) {
			return ['pontos' => [], 'segmentos' => [], 'limiares' => []];
		}

		$valores = array_column($pontos, 'valor');
		$limiares = [];
		$usa_mesma_escala = ($configuracao['padroes_estado'] ?? []) === []
			|| (int) ($configuracao['estado_percentual_calculado'] ?? 0) === 1;
		if ($usa_mesma_escala
				&& ($configuracao['estado_modo'] ?? '') === CWidgetFieldMetricList::ESTADO_LIMITES) {
			foreach ([
				['campo' => 'limite_aviso', 'rotulo' => 'Aviso', 'cor' => $cores['aviso']],
				['campo' => 'limite_critico', 'rotulo' => 'Crítico', 'cor' => $cores['critico']]
			] as $limite) {
				if (is_numeric($configuracao[$limite['campo']] ?? null)) {
					$valor = (float) $configuracao[$limite['campo']];
					$valores[] = $valor;
					$limiares[] = [
						'valor' => $valor,
						'rotulo' => $limite['rotulo'],
						'cor' => $limite['cor']
					];
				}
			}
		}

		$minimo = min($valores);
		$maximo = max($valores);
		$amplitude = $maximo - $minimo;
		if ($amplitude <= 0) {
			$amplitude = max(1.0, abs($maximo) * 0.1);
			$minimo -= $amplitude / 2;
			$maximo += $amplitude / 2;
		}
		else {
			$margem = $amplitude * 0.08;
			$minimo -= $margem;
			$maximo += $margem;
		}
		$amplitude = max(0.000001, $maximo - $minimo);

		$coordenadas = [];
		foreach ($pontos as $ponto) {
			$coordenadas[] = $ponto + [
				'x' => round(((int) $ponto['indice'] / max(1, $quantidade_blocos - 1)) * 1000, 2),
				'y' => round(6 + (($maximo - (float) $ponto['valor']) / $amplitude) * 78, 2)
			];
		}

		$segmentos = [];
		for ($indice = 1, $total = count($coordenadas); $indice < $total; $indice++) {
			$anterior = $coordenadas[$indice - 1];
			$atual = $coordenadas[$indice];
			if ((int) $atual['indice'] !== (int) $anterior['indice'] + 1) {
				continue;
			}

			$segmentos[] = [
				'x1' => $anterior['x'],
				'y1' => $anterior['y'],
				'x2' => $atual['x'],
				'y2' => $atual['y'],
				'cor' => $atual['cor'],
				'tooltip' => $atual['tooltip']
			];
		}

		foreach ($limiares as &$limite) {
			$limite['y'] = round(6 + (($maximo - $limite['valor']) / $amplitude) * 78, 2);
		}
		unset($limite);

		return [
			'pontos' => $coordenadas,
			'segmentos' => $segmentos,
			'limiares' => $limiares
		];
	}

	private function montarTooltipGrafico(int $inicio, int $fim, array $ponto, ?array $item,
			array $configuracao, bool $percentual_calculado): string {
		if ($percentual_calculado
				|| ($configuracao['formato'] ?? '') === CWidgetFieldMetricList::FORMATO_PERCENTUAL_FRACAO) {
			$decimais = max(0, min(6, (int) ($configuracao['decimais'] ?? 2)));
			$valor = number_format((float) $ponto['avg'], $decimais, ',', '.').'%';
		}
		else {
			$valor = $item !== null
				? $this->formatarValor($ponto['avg'], $item, $configuracao)
				: (string) $ponto['avg'];
		}

		return zbx_date2str('d/m/Y H:i', $inicio).' – '.zbx_date2str('d/m/Y H:i', $fim)."\n".
			'Média: '.$valor;
	}

	/**
	 * PT-BR: Calcula uma aproximação percentual para cada bloco agregado.
	 * EN: Calculates a percentage approximation for each aggregated bucket.
	 */
	private function calcularPontoPercentual(?array $principal, ?array $complemento): ?array {
		if ($principal === null || $complemento === null) {
			return null;
		}

		$complemento_minimo = (float) ($complemento['min'] ?? 0);
		$complemento_medio = (float) ($complemento['avg'] ?? 0);
		$complemento_maximo = (float) ($complemento['max'] ?? 0);
		if ($complemento_minimo <= 0 || $complemento_medio <= 0 || $complemento_maximo <= 0) {
			return null;
		}

		return [
			'count' => max(1, min(
				(int) ($principal['count'] ?? 1),
				(int) ($complemento['count'] ?? 1)
			)),
			'min' => ((float) $principal['min'] / $complemento_maximo) * 100,
			'avg' => ((float) $principal['avg'] / $complemento_medio) * 100,
			'max' => ((float) $principal['max'] / $complemento_minimo) * 100
		];
	}

	/**
	 * PT-BR: Converte frações em percentuais antes de aplicar limiares históricos.
	 * EN: Converts fractions to percentages before applying historical thresholds.
	 */
	private function ajustarPontoParaEstado(?array $ponto, array $configuracao): ?array {
		if ($ponto === null
				|| ($configuracao['formato'] ?? '') !== CWidgetFieldMetricList::FORMATO_PERCENTUAL_FRACAO) {
			return $ponto;
		}

		foreach (['min', 'avg', 'max'] as $campo) {
			if (array_key_exists($campo, $ponto)) {
				$ponto[$campo] = (float) $ponto[$campo] * 100;
			}
		}

		return $ponto;
	}

	/**
	 * PT-BR: Consolida os dados estatísticos ao unir blocos consecutivos do mesmo estado.
	 * EN: Consolidates statistical data when adjacent buckets with the same state are merged.
	 */
	private function acumularPontoHistorico(?array $acumulado, ?array $ponto): ?array {
		if ($ponto === null) {
			return $acumulado;
		}

		$quantidade = max(1, (int) ($ponto['count'] ?? 1));
		$soma = (float) ($ponto['avg'] ?? 0) * $quantidade;
		if ($acumulado === null) {
			return [
				'count' => $quantidade,
				'min' => $ponto['min'],
				'avg' => $ponto['avg'],
				'max' => $ponto['max'],
				'sum' => $soma
			];
		}

		$acumulado['count'] += $quantidade;
		$acumulado['min'] = min((float) $acumulado['min'], (float) $ponto['min']);
		$acumulado['max'] = max((float) $acumulado['max'], (float) $ponto['max']);
		$acumulado['sum'] += $soma;
		$acumulado['avg'] = $acumulado['sum'] / $acumulado['count'];

		return $acumulado;
	}

	private function avaliarPontoHistorico(?array $ponto, array $configuracao): string {
		if ($ponto === null) {
			return 'sem_dados';
		}

		$modo = $configuracao['estado_modo'] ?? CWidgetFieldMetricList::ESTADO_NENHUM;
		if ($modo === CWidgetFieldMetricList::ESTADO_LIMITES) {
			$campo = $configuracao['direcao'] === CWidgetFieldMetricList::DIRECAO_MENOR_PIOR ? 'min' : 'max';
			return $this->avaliarEstado($ponto[$campo] ?? null, $configuracao);
		}

		if ($modo === CWidgetFieldMetricList::ESTADO_VALORES) {
			$estado = 'neutro';
			foreach (['min', 'max'] as $campo) {
				$estado_candidato = $this->avaliarEstado($ponto[$campo] ?? null, $configuracao);
				if (self::ESTADOS[$estado_candidato] > self::ESTADOS[$estado]) {
					$estado = $estado_candidato;
				}
			}

			return $estado === 'neutro' ? 'sem_dados' : $estado;
		}

		return 'sem_dados';
	}

	private function pontoIndicaIndisponibilidade(array $ponto, array $configuracao): bool {
		$valores_criticos = $this->separarValores(
			(string) ($configuracao['valores_bloqueio_critico'] ?? '0')
		);

		foreach (['min', 'max'] as $campo) {
			if ($this->valorEstaNaLista($ponto[$campo] ?? null, $valores_criticos)) {
				return true;
			}
		}

		return false;
	}

	private function obterCoresHistoricas(array $configuracao, array $cores_globais): array {
		if ((int) ($configuracao['historico_cores_personalizadas'] ?? 0) === 1) {
			return [
				'ok' => $this->normalizarCor((string) $configuracao['historico_cor_ok'], '2ECA8B'),
				'aviso' => $this->normalizarCor((string) $configuracao['historico_cor_aviso'], 'FFD54F'),
				'critico' => $this->normalizarCor((string) $configuracao['historico_cor_critico'], 'FF465C'),
				'indisponivel' => $this->normalizarCor(
					(string) $configuracao['historico_cor_indisponivel'],
					'111111'
				),
				'sem_dados' => $this->normalizarCor(
					(string) $configuracao['historico_cor_sem_dados'],
					'768D99'
				)
			];
		}

		return [
			'ok' => $this->normalizarCor((string) ($cores_globais['ok'] ?? ''), '2ECA8B'),
			'aviso' => $this->normalizarCor((string) ($cores_globais['aviso'] ?? ''), 'FFD54F'),
			'critico' => $this->normalizarCor((string) ($cores_globais['critico'] ?? ''), 'FF465C'),
			'indisponivel' => '111111',
			'sem_dados' => $this->normalizarCor((string) ($cores_globais['sem_dados'] ?? ''), '768D99')
		];
	}

	private function montarTooltipHistorico(int $inicio, int $fim, string $estado, ?array $ponto,
			?array $item, array $configuracao, bool $percentual_calculado): string {
		$linhas = [
			zbx_date2str('d/m/Y H:i', $inicio).' – '.zbx_date2str('d/m/Y H:i', $fim),
			'Estado: '.(self::ROTULOS_ESTADOS_HISTORICOS[$estado] ?? $estado)
		];

		if ($ponto !== null && $item !== null && $estado !== 'indisponivel') {
			if ($percentual_calculado) {
				$linhas[] = 'Percentual mínimo: '.$this->formatarPercentual($ponto['min']);
				$linhas[] = 'Percentual médio: '.$this->formatarPercentual($ponto['avg']);
				$linhas[] = 'Percentual máximo: '.$this->formatarPercentual($ponto['max']);
			}
			else {
				$configuracao_tooltip = $configuracao;
				if (($configuracao['padroes_estado'] ?? []) !== []
						&& ($configuracao['formato'] ?? '')
							=== CWidgetFieldMetricList::FORMATO_PERCENTUAL_FRACAO) {
					$configuracao_tooltip['formato'] = CWidgetFieldMetricList::FORMATO_AUTOMATICO;
				}
				$linhas[] = 'Mínimo: '.$this->formatarValor($ponto['min'], $item, $configuracao_tooltip);
				$linhas[] = 'Média: '.$this->formatarValor($ponto['avg'], $item, $configuracao_tooltip);
				$linhas[] = 'Máximo: '.$this->formatarValor($ponto['max'], $item, $configuracao_tooltip);
			}
		}

		return implode("\n", $linhas);
	}

	private function montarCards(array $cards, array $linhas, array $historico, array $historicos_agregados,
			array $cores_globais): array {
		$resultado = [];
		$mostrar_rotulos = array_flip($this->fields_values['mostrar_rotulos'] ?? [
			WidgetForm::MOSTRAR_ROTULO_PRIMARIO
		]);
		$modelo_rotulo_primario = (string) ($this->fields_values['rotulo_primario'] ?? '{CARD.NAME}');
		$modelo_rotulo_secundario = (string) ($this->fields_values['rotulo_secundario'] ?? '{HOST.NAME}');

		foreach ($cards as $card) {
			$linhas_card = [];
			$estado_card = 'neutro';

			foreach ($linhas as $indice => $configuracao) {
				$tipo_linha = $configuracao['tipo'] ?? CWidgetFieldMetricList::TIPO_METRICA;
				if ($tipo_linha !== CWidgetFieldMetricList::TIPO_METRICA) {
					$linhas_card[] = [
						'tipo' => $tipo_linha,
						'estado' => 'neutro'
					];
					continue;
				}

				$item = $card['itens'][$indice];
				$amostra = $item !== null && isset($historico[$item['itemid']][0])
					? $historico[$item['itemid']][0]
					: null;
				$item_complemento = $card['itens_complemento'][$indice];
				$amostra_complemento = $item_complemento !== null
						&& isset($historico[$item_complemento['itemid']][0])
					? $historico[$item_complemento['itemid']][0]
					: null;
				$item_estado = $card['itens_estado'][$indice];
				$amostra_estado = $item_estado !== null && isset($historico[$item_estado['itemid']][0])
					? $historico[$item_estado['itemid']][0]
					: null;
				$item_bloqueio = $card['itens_bloqueio'][$indice];
				$amostra_bloqueio = $item_bloqueio !== null
						&& isset($historico[$item_bloqueio['itemid']][0])
					? $historico[$item_bloqueio['itemid']][0]
					: null;
				$linha = $this->montarLinha(
					$configuracao,
					$item,
					$amostra,
					$item_complemento,
					$amostra_complemento,
					$amostra_estado,
					$amostra_bloqueio
				);
				$linha['exibicao'] = $configuracao['exibicao'] ?? CWidgetFieldMetricList::EXIBICAO_VALOR;
				$linha['historico'] = null;
				if ($linha['exibicao'] !== CWidgetFieldMetricList::EXIBICAO_VALOR) {
					$percentual_calculado = (int) ($configuracao['estado_percentual_calculado'] ?? 0) === 1;
					$item_fonte_estado = $percentual_calculado ? $item : ($item_estado ?? $item);
					$linha['historico'] = $this->montarHistoricoLinha(
						$configuracao,
						$item,
						$item_fonte_estado,
						$percentual_calculado ? $item_complemento : null,
						$item_bloqueio,
						$historicos_agregados,
						$cores_globais
					);
				}
				$resumo_historico = $linha['exibicao'] === CWidgetFieldMetricList::EXIBICAO_RESUMO_HISTORICO;
				if ($resumo_historico) {
					$linha['valor'] = $linha['historico']['resumo_texto'] ?? 'Sem dados';
					$linha['estado'] = 'neutro';
					$linha['icone'] = IconLibrary::NO_ICON;
					$linha['historico'] = null;
				}
				$linhas_card[] = $linha;

				if (!$resumo_historico && self::ESTADOS[$linha['estado']] > self::ESTADOS[$estado_card]) {
					$estado_card = $linha['estado'];
				}
			}

			$resultado[] = [
				'rotulo_primario' => array_key_exists(WidgetForm::MOSTRAR_ROTULO_PRIMARIO, $mostrar_rotulos)
					? $this->resolverRotuloCard($card, $modelo_rotulo_primario)
					: '',
				'rotulo_secundario' => array_key_exists(WidgetForm::MOSTRAR_ROTULO_SECUNDARIO, $mostrar_rotulos)
					? $this->resolverRotuloCard($card, $modelo_rotulo_secundario)
					: '',
				'hostid' => $card['hostid'],
				'estado' => $estado_card,
				'linhas' => $linhas_card
			];
		}

		return $resultado;
	}

	/**
	 * PT-BR: Resolve os rótulos com o mesmo contexto de macros baseado em item
	 * usado pelos widgets nativos, além da macro estável {CARD.NAME}.
	 * EN: Resolves labels through the native item-based macro context and the
	 * stable custom {CARD.NAME} macro.
	 */
	private function resolverRotuloCard(array $card, string $modelo): string {
		$rotulo = strtr($modelo, [
			'{CARD.NAME}' => (string) $card['titulo'],
			'{CARD.HOST}' => (string) $card['host']
		]);
		$item = $card['item_referencia'] ?? null;
		if ($item !== null && (!$this->isTemplateDashboard() || $this->fields_values['hostids'])) {
			$item['name'] = (string) ($item['name_resolved'] ?? $item['name'] ?? '');
			$item['hostname'] = (string) $card['host'];
			$resolvido = CMacrosResolverHelper::resolveItemBasedWidgetMacros(
				[$item['itemid'] => $item + ['rotulo_card' => $rotulo]],
				['rotulo_card' => 'rotulo_card']
			);
			$rotulo = (string) ($resolvido[$item['itemid']]['rotulo_card'] ?? $rotulo);
		}

		$rotulo = trim((string) (preg_replace('/\s+/u', ' ', $rotulo) ?? $rotulo));
		return mb_substr($rotulo, 0, 500);
	}

	private function montarLinha(array $configuracao, ?array $item, ?array $amostra,
			?array $item_complemento, ?array $amostra_complemento, ?array $amostra_estado,
			?array $amostra_bloqueio): array {
		$linha = [
			'tipo' => CWidgetFieldMetricList::TIPO_METRICA,
			'rotulo' => (string) ($configuracao['rotulo'] ?? ''),
			'mostrar_rotulo' => (int) ($configuracao['mostrar_rotulo'] ?? 1) === 1,
			'icone' => IconLibrary::normalize((string) ($configuracao['icone'] ?? IconLibrary::DEFAULT_ICON)),
			'valor' => 'Sem dados',
			'estado' => 'sem_dados',
			'itemid' => $item['itemid'] ?? null,
			'clock' => $amostra['clock'] ?? null
		];
		$bloqueio_configurado = ($configuracao['padroes_bloqueio'] ?? []) !== [];
		if ($bloqueio_configurado) {
			if ($amostra_bloqueio === null) {
				return $linha;
			}

			$valor_bloqueio = trim((string) $amostra_bloqueio['value']);
			$valores_criticos = $this->separarValores(
				(string) ($configuracao['valores_bloqueio_critico'] ?? '0')
			);
			if ($this->valorEstaNaLista($valor_bloqueio, $valores_criticos)) {
				$texto_bloqueio = trim((string) ($configuracao['texto_bloqueio'] ?? 'Indisponível'));
				$linha['estado'] = 'critico';
				$linha['valor'] = $texto_bloqueio !== '' ? $texto_bloqueio : 'Indisponível';
				$linha['clock'] = $amostra_bloqueio['clock'] ?? $linha['clock'];
				return $linha;
			}
		}

		if ($item === null || $amostra === null) {
			if ((int) ($configuracao['obrigatorio'] ?? 1) === 0) {
				$linha['estado'] = 'neutro';
				$linha['valor'] = '—';
			}
			return $linha;
		}

		$valor_bruto = $amostra['value'];
		$linha['valor'] = $this->formatarValor($valor_bruto, $item, $configuracao);
		$complemento_configurado = ($configuracao['padroes_complemento'] ?? []) !== [];
		if ($complemento_configurado) {
			$separador_complemento = (string) ($configuracao['separador_complemento'] ?? ' / ');
			$linha['valor'] .= $separador_complemento.($item_complemento !== null && $amostra_complemento !== null
				? formatHistoryValue($amostra_complemento['value'], $item_complemento, false)
				: 'Sem dados');
		}

		if ((int) ($configuracao['estado_percentual_calculado'] ?? 0) === 1) {
			if (!is_numeric($valor_bruto) || $amostra_complemento === null
					|| !is_numeric($amostra_complemento['value'])
					|| (float) $amostra_complemento['value'] <= 0) {
				return $linha;
			}

			$valor_bruto = ((float) $valor_bruto / (float) $amostra_complemento['value']) * 100;
			$linha['estado'] = $this->avaliarEstado($valor_bruto, $configuracao);
			return $linha;
		}

		if (($configuracao['padroes_estado'] ?? []) !== []) {
			if ($amostra_estado === null) {
				return $linha;
			}
			$valor_bruto = $amostra_estado['value'];
		}
		elseif (($configuracao['formato'] ?? '') === CWidgetFieldMetricList::FORMATO_PERCENTUAL_FRACAO
				&& is_numeric($valor_bruto)) {
			$valor_bruto = (float) $valor_bruto * 100;
		}

		$linha['estado'] = $this->avaliarEstado($valor_bruto, $configuracao);

		return $linha;
	}

	private function formatarPercentual($valor): string {
		return number_format((float) $valor, 2, ',', '.').'%';
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

			case CWidgetFieldMetricList::FORMATO_PERCENTUAL_FRACAO:
				$decimais = max(0, min(6, (int) ($configuracao['decimais'] ?? 0)));
				return number_format((float) $valor * 100, $decimais, ',', '.').'%';

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
				if ($this->valorEstaNaLista(
					$chave,
					$this->separarValores((string) $configuracao['valores_'.$estado])
				)) {
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

	private function valorEstaNaLista($valor, array $lista): bool {
		$valor_texto = trim((string) $valor);
		foreach ($lista as $candidato) {
			$candidato = trim((string) $candidato);
			if ($valor_texto === $candidato
					|| (is_numeric($valor_texto) && is_numeric($candidato)
						&& (float) $valor_texto == (float) $candidato)) {
				return true;
			}
		}

		return false;
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
