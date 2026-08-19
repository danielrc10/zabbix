<?php declare(strict_types = 0);

/**
 * PT-BR: Campo composto que armazena as métricas configuradas no widget.
 * EN: Composite field that stores the metrics configured in the widget.
 *
 * Autor / Author: Daniel Carvalho <danielrc10@gmail.com>
 * LinkedIn: https://www.linkedin.com/in/daniel-ti/
 * Licença / License: PolyForm Noncommercial 1.0.0
 * Uso comercial / Commercial use: contato / contact danielrc10@gmail.com
 */

namespace Modules\DynamicStatusCards\Includes;

use DB;
use Zabbix\Widgets\CWidgetField;

class CWidgetFieldMetricList extends CWidgetField {

	public const DEFAULT_VIEW = CWidgetFieldMetricListView::class;
	public const DEFAULT_VALUE = [];

	public const FORMATO_AUTOMATICO = 'automatico';
	public const FORMATO_MAPA = 'mapa';
	public const FORMATO_NUMERO = 'numero';
	public const FORMATO_PERCENTUAL_FRACAO = 'percentual_fracao';
	public const FORMATO_DATA = 'data';
	public const FORMATO_TEXTO = 'texto';

	public const EXIBICAO_VALOR = 'valor';
	public const EXIBICAO_VALOR_HISTORICO = 'valor_historico';
	public const EXIBICAO_HISTORICO = 'historico';

	public const ESTADO_NENHUM = 'nenhum';
	public const ESTADO_LIMITES = 'limiares';
	public const ESTADO_VALORES = 'valores';

	public const DIRECAO_MAIOR_PIOR = 'maior_pior';
	public const DIRECAO_MENOR_PIOR = 'menor_pior';

	public const ESTADOS = ['neutro', 'ok', 'aviso', 'critico'];

	public function __construct(string $name, ?string $label = null) {
		parent::__construct($name, $label);
		$this->setDefault(self::DEFAULT_VALUE);
	}

	public static function getMetricDefaults(): array {
		return [
			'rotulo' => '',
			'mostrar_rotulo' => 1,
			'padroes' => [],
			'padroes_complemento' => [],
			'estado_percentual_calculado' => 0,
			'formato' => self::FORMATO_AUTOMATICO,
			'mapa' => '',
			'decimais' => 0,
			'sufixo' => '',
			'formato_data' => 'd/m/Y',
			'padroes_estado' => [],
			'padroes_bloqueio' => [],
			'valores_bloqueio_critico' => '0',
			'texto_bloqueio' => 'Indisponível',
			'estado_modo' => self::ESTADO_NENHUM,
			'direcao' => self::DIRECAO_MAIOR_PIOR,
			'limite_aviso' => '',
			'limite_critico' => '',
			'valores_ok' => '',
			'valores_aviso' => '',
			'valores_critico' => '',
			'estado_padrao' => 'neutro',
			'exibicao' => self::EXIBICAO_VALOR,
			'historico_dias' => 1,
			'historico_mostrar_percentual' => 0,
			'historico_cores_personalizadas' => 0,
			'historico_cor_ok' => '2ECA8B',
			'historico_cor_aviso' => 'FFD54F',
			'historico_cor_critico' => 'FF465C',
			'historico_cor_indisponivel' => '111111',
			'historico_cor_sem_dados' => '768D99',
			'obrigatorio' => 1
		];
	}

	/**
	 * PT-BR: Aceita o JSON da versão 1.0 e o converte uma única vez ao salvar.
	 * EN: Accepts the 1.0 JSON payload and converts it once the widget is saved.
	 */
	public function setValue($value): self {
		if (is_string($value)) {
			$legacy = json_decode($value, true);
			$value = is_array($legacy) ? $legacy : [];
		}

		if (!is_array($value)) {
			return parent::setValue([]);
		}

		$metricas = [];
		foreach (array_values($value) as $metrica) {
			if (is_array($metrica)) {
				$metricas[] = $this->normalizarMetrica($metrica);
			}
		}

		return parent::setValue($metricas);
	}

	public function validate(bool $strict = false): array {
		$erros = parent::validate($strict);
		if ($erros) {
			return $erros;
		}

		foreach ($this->getValue() as $indice => $metrica) {
			$numero = $indice + 1;
			$historico_ativo = $metrica['exibicao'] !== self::EXIBICAO_VALOR;
			$percentual_calculado = (int) $metrica['estado_percentual_calculado'] === 1;
			if ($percentual_calculado && $metrica['padroes_complemento'] === []) {
				$erros[] = "Métrica {$numero}: selecione um item complementar para calcular o percentual.";
			}
			if ($percentual_calculado && $metrica['estado_modo'] !== self::ESTADO_LIMITES) {
				$erros[] = "Métrica {$numero}: o percentual calculado exige avaliação por limiares numéricos.";
			}
			if ($historico_ativo && $metrica['estado_modo'] === self::ESTADO_NENHUM) {
				$erros[] = "Métrica {$numero}: a barra histórica exige uma regra de estado por limiares ou valores exatos.";
			}

			if ($historico_ativo && (int) $metrica['historico_cores_personalizadas'] === 1) {
				foreach ([
					'historico_cor_ok' => 'OK',
					'historico_cor_aviso' => 'aviso',
					'historico_cor_critico' => 'crítica',
					'historico_cor_indisponivel' => 'indisponível',
					'historico_cor_sem_dados' => 'sem dados'
				] as $campo_cor => $rotulo_cor) {
					if (preg_match('/^[0-9A-F]{6}$/Di', $metrica[$campo_cor]) !== 1) {
						$erros[] = "Métrica {$numero}: a cor histórica {$rotulo_cor} é inválida.";
					}
				}
			}

			if ($metrica['padroes_bloqueio'] !== []
					&& trim($metrica['valores_bloqueio_critico']) === '') {
				$erros[] = "Métrica {$numero}: informe ao menos um valor crítico para o item de disponibilidade.";
			}

			if ($metrica['estado_modo'] === self::ESTADO_LIMITES) {
				if (!is_numeric($metrica['limite_aviso']) || !is_numeric($metrica['limite_critico'])) {
					$erros[] = "Métrica {$numero}: os limites de aviso e crítico precisam ser numéricos.";
					continue;
				}

				$aviso = (float) $metrica['limite_aviso'];
				$critico = (float) $metrica['limite_critico'];
				if ($metrica['direcao'] === self::DIRECAO_MAIOR_PIOR && $aviso >= $critico) {
					$erros[] = "Métrica {$numero}: quando valores maiores são piores, o limite de aviso deve ser menor que o crítico.";
				}
				if ($metrica['direcao'] === self::DIRECAO_MENOR_PIOR && $critico >= $aviso) {
					$erros[] = "Métrica {$numero}: quando valores menores são piores, o limite crítico deve ser menor que o de aviso.";
				}
			}
			elseif ($metrica['estado_modo'] === self::ESTADO_VALORES
					&& trim($metrica['valores_ok']) === ''
					&& trim($metrica['valores_aviso']) === ''
					&& trim($metrica['valores_critico']) === ''
					&& $metrica['estado_padrao'] === 'neutro') {
				$erros[] = "Métrica {$numero}: informe pelo menos um valor ou um estado padrão.";
			}
		}

		return $erros;
	}

	public function toApi(array &$widget_fields = []): void {
		$tipos = [
			'rotulo' => ZBX_WIDGET_FIELD_TYPE_STR,
			'mostrar_rotulo' => ZBX_WIDGET_FIELD_TYPE_INT32,
			'estado_percentual_calculado' => ZBX_WIDGET_FIELD_TYPE_INT32,
			'formato' => ZBX_WIDGET_FIELD_TYPE_STR,
			'mapa' => ZBX_WIDGET_FIELD_TYPE_STR,
			'decimais' => ZBX_WIDGET_FIELD_TYPE_INT32,
			'sufixo' => ZBX_WIDGET_FIELD_TYPE_STR,
			'formato_data' => ZBX_WIDGET_FIELD_TYPE_STR,
			'valores_bloqueio_critico' => ZBX_WIDGET_FIELD_TYPE_STR,
			'texto_bloqueio' => ZBX_WIDGET_FIELD_TYPE_STR,
			'estado_modo' => ZBX_WIDGET_FIELD_TYPE_STR,
			'direcao' => ZBX_WIDGET_FIELD_TYPE_STR,
			'limite_aviso' => ZBX_WIDGET_FIELD_TYPE_STR,
			'limite_critico' => ZBX_WIDGET_FIELD_TYPE_STR,
			'valores_ok' => ZBX_WIDGET_FIELD_TYPE_STR,
			'valores_aviso' => ZBX_WIDGET_FIELD_TYPE_STR,
			'valores_critico' => ZBX_WIDGET_FIELD_TYPE_STR,
			'estado_padrao' => ZBX_WIDGET_FIELD_TYPE_STR,
			'exibicao' => ZBX_WIDGET_FIELD_TYPE_STR,
			'historico_dias' => ZBX_WIDGET_FIELD_TYPE_INT32,
			'historico_mostrar_percentual' => ZBX_WIDGET_FIELD_TYPE_INT32,
			'historico_cores_personalizadas' => ZBX_WIDGET_FIELD_TYPE_INT32,
			'historico_cor_ok' => ZBX_WIDGET_FIELD_TYPE_STR,
			'historico_cor_aviso' => ZBX_WIDGET_FIELD_TYPE_STR,
			'historico_cor_critico' => ZBX_WIDGET_FIELD_TYPE_STR,
			'historico_cor_indisponivel' => ZBX_WIDGET_FIELD_TYPE_STR,
			'historico_cor_sem_dados' => ZBX_WIDGET_FIELD_TYPE_STR,
			'obrigatorio' => ZBX_WIDGET_FIELD_TYPE_INT32
		];

		foreach ($this->getValue() as $indice => $metrica) {
			foreach ($tipos as $campo => $tipo) {
				$widget_fields[] = [
					'type' => $tipo,
					'name' => $this->name.'.'.$indice.'.'.$campo,
					'value' => $metrica[$campo]
				];
			}

			foreach ($metrica['padroes'] as $padrao_indice => $padrao) {
				$widget_fields[] = [
					'type' => ZBX_WIDGET_FIELD_TYPE_STR,
					'name' => $this->name.'.'.$indice.'.padroes.'.$padrao_indice,
					'value' => $padrao
				];
			}

			foreach ($metrica['padroes_complemento'] as $padrao_indice => $padrao) {
				$widget_fields[] = [
					'type' => ZBX_WIDGET_FIELD_TYPE_STR,
					'name' => $this->name.'.'.$indice.'.padroes_complemento.'.$padrao_indice,
					'value' => $padrao
				];
			}

			foreach ($metrica['padroes_estado'] as $padrao_indice => $padrao) {
				$widget_fields[] = [
					'type' => ZBX_WIDGET_FIELD_TYPE_STR,
					'name' => $this->name.'.'.$indice.'.padroes_estado.'.$padrao_indice,
					'value' => $padrao
				];
			}

			foreach ($metrica['padroes_bloqueio'] as $padrao_indice => $padrao) {
				$widget_fields[] = [
					'type' => ZBX_WIDGET_FIELD_TYPE_STR,
					'name' => $this->name.'.'.$indice.'.padroes_bloqueio.'.$padrao_indice,
					'value' => $padrao
				];
			}
		}
	}

	protected function getValidationRules(bool $strict = false): array {
		$maximo = DB::getFieldLength('widget_field', 'value_str');
		$regras = ['type' => API_OBJECTS, 'fields' => [
			'rotulo' => ['type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => 255],
			'mostrar_rotulo' => ['type' => API_INT32, 'in' => '0,1', 'default' => 1],
			'padroes' => ['type' => API_STRINGS_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY],
			'padroes_complemento' => ['type' => API_STRINGS_UTF8, 'default' => []],
			'estado_percentual_calculado' => ['type' => API_INT32, 'in' => '0,1', 'default' => 0],
			'formato' => ['type' => API_STRING_UTF8, 'in' => implode(',', [
				self::FORMATO_AUTOMATICO,
				self::FORMATO_MAPA,
				self::FORMATO_NUMERO,
				self::FORMATO_PERCENTUAL_FRACAO,
				self::FORMATO_DATA,
				self::FORMATO_TEXTO
			]), 'default' => self::FORMATO_AUTOMATICO],
			'mapa' => ['type' => API_STRING_UTF8, 'length' => $maximo, 'default' => ''],
			'decimais' => ['type' => API_INT32, 'in' => '0:6', 'default' => 0],
			'sufixo' => ['type' => API_STRING_UTF8, 'length' => 64, 'default' => ''],
			'formato_data' => ['type' => API_STRING_UTF8, 'length' => 64, 'default' => 'd/m/Y'],
			'padroes_estado' => ['type' => API_STRINGS_UTF8, 'default' => []],
			'padroes_bloqueio' => ['type' => API_STRINGS_UTF8, 'default' => []],
			'valores_bloqueio_critico' => [
				'type' => API_STRING_UTF8,
				'length' => $maximo,
				'default' => '0'
			],
			'texto_bloqueio' => ['type' => API_STRING_UTF8, 'length' => 255, 'default' => 'Indisponível'],
			'estado_modo' => ['type' => API_STRING_UTF8, 'in' => implode(',', [
				self::ESTADO_NENHUM,
				self::ESTADO_LIMITES,
				self::ESTADO_VALORES
			]), 'default' => self::ESTADO_NENHUM],
			'direcao' => ['type' => API_STRING_UTF8, 'in' => implode(',', [
				self::DIRECAO_MAIOR_PIOR,
				self::DIRECAO_MENOR_PIOR
			]), 'default' => self::DIRECAO_MAIOR_PIOR],
			'limite_aviso' => ['type' => API_STRING_UTF8, 'length' => 64, 'default' => ''],
			'limite_critico' => ['type' => API_STRING_UTF8, 'length' => 64, 'default' => ''],
			'valores_ok' => ['type' => API_STRING_UTF8, 'length' => $maximo, 'default' => ''],
			'valores_aviso' => ['type' => API_STRING_UTF8, 'length' => $maximo, 'default' => ''],
			'valores_critico' => ['type' => API_STRING_UTF8, 'length' => $maximo, 'default' => ''],
			'estado_padrao' => ['type' => API_STRING_UTF8, 'in' => implode(',', self::ESTADOS), 'default' => 'neutro'],
			'exibicao' => ['type' => API_STRING_UTF8, 'in' => implode(',', [
				self::EXIBICAO_VALOR,
				self::EXIBICAO_VALOR_HISTORICO,
				self::EXIBICAO_HISTORICO
			]), 'default' => self::EXIBICAO_VALOR],
			'historico_dias' => ['type' => API_INT32, 'in' => '1:90', 'default' => 1],
			'historico_mostrar_percentual' => ['type' => API_INT32, 'in' => '0,1', 'default' => 0],
			'historico_cores_personalizadas' => ['type' => API_INT32, 'in' => '0,1', 'default' => 0],
			'historico_cor_ok' => ['type' => API_STRING_UTF8, 'length' => 6, 'default' => '2ECA8B'],
			'historico_cor_aviso' => ['type' => API_STRING_UTF8, 'length' => 6, 'default' => 'FFD54F'],
			'historico_cor_critico' => ['type' => API_STRING_UTF8, 'length' => 6, 'default' => 'FF465C'],
			'historico_cor_indisponivel' => ['type' => API_STRING_UTF8, 'length' => 6, 'default' => '111111'],
			'historico_cor_sem_dados' => ['type' => API_STRING_UTF8, 'length' => 6, 'default' => '768D99'],
			'obrigatorio' => ['type' => API_INT32, 'in' => '0,1', 'default' => 1]
		]];

		if (($this->getFlags() & self::FLAG_NOT_EMPTY) !== 0) {
			self::setValidationRuleFlag($regras, API_NOT_EMPTY);
		}

		return $regras;
	}

	private function normalizarMetrica(array $metrica): array {
		if (!array_key_exists('padroes', $metrica)) {
			$metrica['padroes'] = isset($metrica['padrao']) ? [(string) $metrica['padrao']] : [];
		}
		if (!array_key_exists('padroes_estado', $metrica)) {
			$metrica['padroes_estado'] = isset($metrica['padrao_estado'])
				? [(string) $metrica['padrao_estado']]
				: [];
		}
		if (!array_key_exists('padroes_complemento', $metrica)) {
			$metrica['padroes_complemento'] = [];
		}
		if (!array_key_exists('padroes_bloqueio', $metrica)) {
			$metrica['padroes_bloqueio'] = [];
		}

		if (is_array($metrica['mapa'] ?? null)) {
			$linhas = [];
			foreach ($metrica['mapa'] as $valor => $texto) {
				$linhas[] = $valor.'='.$texto;
			}
			$metrica['mapa'] = implode("\n", $linhas);
		}

		if (!array_key_exists('estado_modo', $metrica)) {
			if (is_array($metrica['estados'] ?? null)) {
				$metrica['estado_modo'] = self::ESTADO_VALORES;
				$listas = ['ok' => [], 'aviso' => [], 'critico' => []];
				foreach ($metrica['estados'] as $valor => $estado) {
					if ((string) $valor === '*') {
						$metrica['estado_padrao'] = in_array($estado, self::ESTADOS, true) ? $estado : 'neutro';
					}
					elseif (array_key_exists($estado, $listas)) {
						$listas[$estado][] = (string) $valor;
					}
				}
				$metrica['valores_ok'] = implode(', ', $listas['ok']);
				$metrica['valores_aviso'] = implode(', ', $listas['aviso']);
				$metrica['valores_critico'] = implode(', ', $listas['critico']);
			}
			elseif (is_array($metrica['limites'] ?? null)) {
				$metrica['estado_modo'] = self::ESTADO_LIMITES;
				$this->converterLimitesLegados($metrica);
			}
			else {
				$metrica['estado_modo'] = self::ESTADO_NENHUM;
			}
		}

		$metrica['padroes'] = array_values(array_filter(array_map('strval', (array) $metrica['padroes']), 'strlen'));
		$metrica['padroes_estado'] = array_values(array_filter(
			array_map('strval', (array) $metrica['padroes_estado']),
			'strlen'
		));
		$metrica['padroes_complemento'] = array_values(array_filter(
			array_map('strval', (array) $metrica['padroes_complemento']),
			'strlen'
		));
		$metrica['padroes_bloqueio'] = array_values(array_filter(
			array_map('strval', (array) $metrica['padroes_bloqueio']),
			'strlen'
		));
		foreach ([
			'historico_cor_ok',
			'historico_cor_aviso',
			'historico_cor_critico',
			'historico_cor_indisponivel',
			'historico_cor_sem_dados'
		] as $campo_cor) {
			if (array_key_exists($campo_cor, $metrica)) {
				$metrica[$campo_cor] = strtoupper(ltrim(trim((string) $metrica[$campo_cor]), '#'));
			}
		}
		$metrica['mostrar_rotulo'] = (int) ($metrica['mostrar_rotulo'] ?? 1);
		$metrica['estado_percentual_calculado'] = (int) ($metrica['estado_percentual_calculado'] ?? 0);
		$metrica['historico_dias'] = (int) ($metrica['historico_dias'] ?? 1);
		$metrica['historico_mostrar_percentual'] = (int) ($metrica['historico_mostrar_percentual'] ?? 0);
		$metrica['historico_cores_personalizadas'] = (int) (
			$metrica['historico_cores_personalizadas'] ?? 0
		);
		$metrica['obrigatorio'] = (int) ($metrica['obrigatorio'] ?? 1);
		unset(
			$metrica['padrao'],
			$metrica['padrao_estado'],
			$metrica['estados'],
			$metrica['limites']
		);

		return array_replace(self::getMetricDefaults(), $metrica);
	}

	private function converterLimitesLegados(array &$metrica): void {
		$metrica['direcao'] = self::DIRECAO_MAIOR_PIOR;
		foreach ($metrica['limites'] as $limite) {
			if (!is_array($limite)) {
				continue;
			}
			if (($limite['estado'] ?? '') === 'critico'
					&& in_array($limite['operador'] ?? '', ['<', '<='], true)) {
				$metrica['direcao'] = self::DIRECAO_MENOR_PIOR;
			}
		}

		foreach ($metrica['limites'] as $limite) {
			if (!is_array($limite)) {
				continue;
			}
			$estado = $limite['estado'] ?? '';
			$operador = $limite['operador'] ?? '';
			$valor = (string) ($limite['valor'] ?? '');

			if ($metrica['direcao'] === self::DIRECAO_MAIOR_PIOR) {
				if ($estado === 'ok' && in_array($operador, ['<', '<='], true)) {
					$metrica['limite_aviso'] = $valor;
				}
				if (($estado === 'aviso' && in_array($operador, ['<', '<='], true))
						|| ($estado === 'critico' && in_array($operador, ['>', '>='], true)
							&& ($metrica['limite_critico'] ?? '') === '')) {
					$metrica['limite_critico'] = $valor;
				}
			}
			else {
				if (($estado === 'aviso' && in_array($operador, ['<', '<='], true))
						|| ($estado === 'ok' && in_array($operador, ['>', '>='], true)
							&& ($metrica['limite_aviso'] ?? '') === '')) {
					$metrica['limite_aviso'] = $valor;
				}
				if ($estado === 'critico' && in_array($operador, ['<', '<='], true)) {
					$metrica['limite_critico'] = $valor;
				}
			}
		}
	}
}
