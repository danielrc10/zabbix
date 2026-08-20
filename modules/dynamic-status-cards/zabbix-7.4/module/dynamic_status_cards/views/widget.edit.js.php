<?php declare(strict_types = 0);

/**
 * PT-BR: Gerencia a lista visual de métricas do formulário principal.
 * EN: Manages the visual metric list in the main widget form.
 *
 * Autor / Author: Daniel Carvalho <danielrc10@gmail.com>
 * Licença / License: PolyForm Noncommercial 1.0.0
 */

use Modules\DynamicStatusCards\Includes\WidgetForm;

?>

window.widget_form = new class extends CWidgetForm {
	#form;
	#templateid;
	#list;
	#template;
	#sortable;
	#fundoModo;
	#textoModo;

	init({templateid}) {
		this.#form = this.getForm();
		this.#templateid = templateid;
		this.#list = document.getElementById('list_linhas');
		this.#template = new Template(this.#list.querySelector('template').innerHTML);
		this.#sortable = new CSortable(this.#list.querySelector('tbody'), {
			selector_handle: '.table-col-handle'
		});
		this.#fundoModo = document.getElementById('fundo_modo');
		this.#textoModo = document.getElementById('texto_modo');

		this.#list.addEventListener('click', (event) => this.#processAction(event));
		this.#sortable.on(CSortable.EVENT_SORT, () => {
			this.#reindexRows();
			this.#triggerUpdate();
		});
		this.#fundoModo.addEventListener('change', () => this.#updateAppearance());
		this.#textoModo.addEventListener('change', () => this.#updateAppearance());
		this.#updateAppearance();
		this.ready();
	}

	#updateAppearance() {
		const fundo = Number(this.#fundoModo.value);
		const texto = Number(this.#textoModo.value);

		this.#toggleRows('.js-fundo-solido', fundo === <?= WidgetForm::FUNDO_SOLIDO ?>);
		this.#toggleRows('.js-fundo-gradiente', fundo === <?= WidgetForm::FUNDO_GRADIENTE ?>);
		this.#toggleRows('.js-texto-personalizado', texto === <?= WidgetForm::TEXTO_PERSONALIZADO ?>);
	}

	#toggleRows(selector, visible) {
		for (const row of this.#form.querySelectorAll(selector)) {
			row.style.display = visible ? '' : 'none';
		}
	}

	#processAction(event) {
		const action = event.target.getAttribute('name');
		if (!['add', 'edit', 'copy', 'remove'].includes(action)) {
			return;
		}

		if (action === 'remove') {
			event.target.closest('tr').remove();
			this.#reindexRows();
			this.#triggerUpdate();
			return;
		}

		const fields = getFormFields(this.#form);
		const params = {hostids: fields.hostids ?? []};
		if (this.#templateid !== null) {
			params.templateid = this.#templateid;
		}
		let index = this.#nextIndex();

		if (action === 'edit' || action === 'copy') {
			const source_index = event.target.closest('tr').dataset.index;
			Object.assign(params, fields.linhas[source_index]);
			if (action === 'edit') {
				index = source_index;
				params.edit = 1;
			}
			else {
				params.copy = 1;
			}
		}

		const dialogue = PopUp('widget.dynamic_status_cards.metric.edit', params, {
			dialogueid: 'dynamic-status-cards-metric-edit-overlay',
			dialogue_class: 'modal-popup-generic'
		}).$dialogue[0];

		dialogue.addEventListener('dialogue.submit', (submit_event) => {
			const row = this.#makeMetricRow(submit_event.detail, index);
			if (action === 'edit') {
				this.#list.querySelector(`tbody > tr[data-index="${index}"]`).replaceWith(row);
			}
			else {
				this.#list.querySelector('tbody').append(row);
			}
			this.#reindexRows();
			this.#triggerUpdate();
		});
	}

	#nextIndex() {
		const indices = [...this.#list.querySelectorAll('tbody > tr[data-index]')]
			.map((row) => Number.parseInt(row.dataset.index, 10));
		return indices.length === 0 ? 0 : Math.max(...indices) + 1;
	}

	#makeMetricRow(data, index) {
		const type = data.tipo ?? 'metrica';
		const displayed_label = type === 'espacador'
			? '(Espaço vazio)'
			: (type === 'separador' ? '(Separador horizontal)' : data.rotulo);
		let pattern_summary = type === 'metrica' ? Object.values(data.padroes ?? {}).join(', ') : '';
		if (type === 'metrica' && Object.keys(data.padroes_complemento ?? {}).length > 0) {
			pattern_summary += `${data.separador_complemento ?? ' / '}${
				Object.values(data.padroes_complemento).join(', ')
			}`;
		}
		const row = this.#template.evaluateToElement({
			...data,
			rotulo: displayed_label,
			rowNum: index,
			padroes_resumo: pattern_summary,
			regra_resumo: this.#ruleSummary(data)
		});

		row.dataset.index = index;
		const container = row.querySelector('.js-metrica-data');
		for (const [key, value] of Object.entries(data)) {
			if (key !== 'edit') {
				this.#appendVars(container, `linhas[${index}][${key}]`, value);
			}
		}

		return row;
	}

	#appendVars(container, name, value) {
		if (value !== null && typeof value === 'object') {
			for (const [key, child] of Object.entries(value)) {
				this.#appendVars(container, `${name}[${key}]`, child);
			}
			return;
		}

		const input = document.createElement('input');
		input.type = 'hidden';
		input.name = name;
		input.value = value;
		container.append(input);
	}

	#ruleSummary(data) {
		if ((data.tipo ?? 'metrica') === 'espacador') {
			return 'Linha visual sem conteúdo';
		}
		if ((data.tipo ?? 'metrica') === 'separador') {
			return 'Divisão visual entre seções';
		}

		let summary;
		if (data.estado_modo === 'limiares') {
			const direction = data.direcao === 'menor_pior' ? 'menor é pior' : 'maior é pior';
			summary = `Limiares: aviso ${data.limite_aviso}, crítico ${data.limite_critico} (${direction})`;
		}
		else if (data.estado_modo === 'valores') {
			summary = 'Valores exatos';
		}
		else {
			summary = 'Somente informativa';
		}

		if (Object.keys(data.padroes_bloqueio ?? {}).length > 0) {
			summary += ' · com indisponibilidade';
		}
		if (Number(data.estado_percentual_calculado ?? 0) === 1) {
			summary += ' · percentual calculado';
		}
		if (Number(data.mostrar_rotulo ?? 1) === 0) {
			summary += ' · nome oculto';
		}
		if ((data.exibicao ?? 'valor') !== 'valor') {
			summary += ` · histórico ${data.historico_dias ?? 1}d`;
		}

		return summary;
	}

	#reindexRows() {
		const rows = [...this.#list.querySelectorAll('tbody > tr[data-index]')];

		for (const [position, row] of rows.entries()) {
			for (const input of row.querySelectorAll('input[name^="linhas["]')) {
				input.name = input.name.replace(/^linhas\[\d+]/, `linhas[${10000 + position}]`);
			}
		}

		for (const [position, row] of rows.entries()) {
			for (const input of row.querySelectorAll(`input[name^="linhas[${10000 + position}]"]`)) {
				input.name = input.name.replace(`linhas[${10000 + position}]`, `linhas[${position}]`);
			}
			row.dataset.index = position;
		}
	}

	#triggerUpdate() {
		this.#form.dispatchEvent(new CustomEvent('form_fields.changed', {detail: {}}));
		this.registerUpdateEvent();
	}
};
