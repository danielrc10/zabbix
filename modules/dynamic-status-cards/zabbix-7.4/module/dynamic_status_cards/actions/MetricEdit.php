<?php declare(strict_types = 0);

/**
 * PT-BR: Controlador da janela de inclusão e edição de métricas.
 * EN: Controller for the metric add and edit dialog.
 *
 * Autor / Author: Daniel Carvalho <danielrc10@gmail.com>
 * Licença / License: PolyForm Noncommercial 1.0.0
 * Uso comercial / Commercial use: contato / contact danielrc10@gmail.com
 */

namespace Modules\DynamicStatusCards\Actions;

use CController,
	CControllerResponseData;

use Modules\DynamicStatusCards\Includes\{
	CWidgetFieldMetricList,
	IconLibrary
};
use Zabbix\Widgets\CWidgetField;
use Zabbix\Widgets\Fields\CWidgetFieldPatternSelectItem;

class MetricEdit extends CController {

	protected function init(): void {
		$this->disableCsrfValidation();
	}

	protected function checkInput(): bool {
		$campos = [
			'tipo' => 'string',
			'rotulo' => 'string',
			'mostrar_rotulo' => 'in 0,1',
			'icone' => 'string',
			'padroes' => 'array',
			'padroes_complemento' => 'array',
			'separador_complemento' => 'string',
			'estado_percentual_calculado' => 'in 0,1',
			'formato' => 'string',
			'mapa' => 'string',
			'decimais' => 'int32',
			'sufixo' => 'string',
			'formato_data' => 'string',
			'padroes_estado' => 'array',
			'padroes_bloqueio' => 'array',
			'valores_bloqueio_critico' => 'string',
			'texto_bloqueio' => 'string',
			'estado_modo' => 'string',
			'direcao' => 'string',
			'limite_aviso' => 'string',
			'limite_critico' => 'string',
			'valores_ok' => 'string',
			'valores_aviso' => 'string',
			'valores_critico' => 'string',
			'estado_padrao' => 'string',
			'exibicao' => 'string',
			'historico_dias' => 'int32',
			'historico_mostrar_percentual' => 'in 0,1',
			'historico_cores_personalizadas' => 'in 0,1',
			'historico_cor_ok' => 'string',
			'historico_cor_aviso' => 'string',
			'historico_cor_critico' => 'string',
			'historico_cor_indisponivel' => 'string',
			'historico_cor_sem_dados' => 'string',
			'obrigatorio' => 'in 0,1',
			'edit' => 'in 1',
			'copy' => 'in 1',
			'update' => 'in 1',
			'templateid' => 'string',
			'hostids' => 'array'
		];

		$valido = $this->validateInput($campos) && $this->validarMetrica();
		if (!$valido) {
			$this->setResponse(
				(new CControllerResponseData(['main_block' => json_encode([
					'error' => ['messages' => array_column(get_and_clear_messages(), 'message')]
				], JSON_THROW_ON_ERROR)]))->disableView()
			);
		}

		return $valido;
	}

	protected function checkPermissions(): bool {
		return $this->getUserType() >= USER_TYPE_ZABBIX_USER;
	}

	protected function doAction(): void {
		$entrada = $this->getInputAll();

		if (!$this->hasInput('update')) {
			$dados = [
				'action' => $this->getAction(),
				'templateid' => $this->getInput('templateid', '') !== '' ? $this->getInput('templateid') : null,
				'hostids' => $this->getInput('hostids', []),
				'errors' => hasErrorMessages() ? getMessages() : null,
				'user' => ['debug_mode' => $this->getDebugMode()]
			] + $entrada + CWidgetFieldMetricList::getMetricDefaults();
			$dados['icones'] = IconLibrary::getIcons();

			$dados['padroes_field'] = $this->criarCampoPadroes(
				'padroes',
				'Item ou padrão',
				$dados['padroes'],
				$dados['templateid'],
				$dados['tipo'] === CWidgetFieldMetricList::TIPO_METRICA
			);
			$dados['padroes_complemento_field'] = $this->criarCampoPadroes(
				'padroes_complemento',
				'Item complementar após o valor (opcional)',
				$dados['padroes_complemento'],
				$dados['templateid'],
				false
			);
			$dados['padroes_estado_field'] = $this->criarCampoPadroes(
				'padroes_estado',
				'Item alternativo para determinar a cor',
				$dados['padroes_estado'],
				$dados['templateid'],
				false
			);
			$dados['padroes_bloqueio_field'] = $this->criarCampoPadroes(
				'padroes_bloqueio',
				'Item de disponibilidade (opcional)',
				$dados['padroes_bloqueio'],
				$dados['templateid'],
				false
			);

			$this->setResponse(new CControllerResponseData($dados));
			return;
		}

		$metrica = $this->obterMetricaNormalizada($entrada);
		if ($this->hasInput('edit')) {
			$metrica['edit'] = 1;
		}

		$this->setResponse(
			(new CControllerResponseData(['main_block' => json_encode($metrica, JSON_THROW_ON_ERROR)]))->disableView()
		);
	}

	private function validarMetrica(): bool {
		if (!$this->hasInput('update')) {
			return true;
		}

		$campo = new CWidgetFieldMetricList('linhas', 'Métricas');
		$campo->setValue([$this->limparEntrada($this->getInputAll())]);
		$erros = $campo->validate(true);
		array_map('error', $erros);

		return !$erros;
	}

	private function obterMetricaNormalizada(array $entrada): array {
		$campo = new CWidgetFieldMetricList('linhas', 'Métricas');
		$campo->setValue([$this->limparEntrada($entrada)]);
		$campo->validate(true);

		return $campo->getValue()[0];
	}

	private function limparEntrada(array $entrada): array {
		unset(
			$entrada['action'],
			$entrada['edit'],
			$entrada['copy'],
			$entrada['update'],
			$entrada['templateid'],
			$entrada['hostids']
		);

		return array_replace(CWidgetFieldMetricList::getMetricDefaults(), $entrada);
	}

	private function criarCampoPadroes(string $nome, string $rotulo, array $valor, $templateid,
			bool $obrigatorio): CWidgetFieldPatternSelectItem {
		$campo = (new CWidgetFieldPatternSelectItem($nome, $rotulo))
			->setValue($valor)
			->setTemplateId($templateid);

		if ($obrigatorio) {
			$campo->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK);
		}

		return $campo;
	}
}
