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
	#widgetFundoModo;
	#textoModo;
	#colunasAutomaticas;
	#modoCards;
	#headerIconInput;
	#headerIconCatalog;

	init({templateid, icones}) {
		this.#form = this.getForm();
		this.#templateid = templateid;
		this.#list = document.getElementById('list_linhas');
		this.#template = new Template(this.#list.querySelector('template').innerHTML);
		this.#sortable = new CSortable(this.#list.querySelector('tbody'), {
			selector_handle: '.table-col-handle'
		});
		this.#fundoModo = document.getElementById('fundo_modo');
		this.#widgetFundoModo = document.getElementById('widget_fundo_modo');
		this.#textoModo = document.getElementById('texto_modo');
		this.#colunasAutomaticas = document.getElementById('colunas_automaticas');
		this.#modoCards = this.#form.querySelector('[name="modo_cards"]:checked');

		this.#list.addEventListener('click', (event) => this.#processAction(event));
		this.#sortable.on(CSortable.EVENT_SORT, () => {
			this.#reindexRows();
			this.#triggerUpdate();
		});
		this.#fundoModo.addEventListener('change', () => this.#updateAppearance());
		this.#widgetFundoModo.addEventListener('change', () => this.#updateAppearance());
		this.#textoModo.addEventListener('change', () => this.#updateAppearance());
		this.#colunasAutomaticas?.addEventListener('change', () => this.#updateColumns());
		this.#form.querySelectorAll('[name="modo_cards"], [name="mostrar_rotulos[]"]')
			.forEach((element) => element.addEventListener('change', () => this.#updateCardConfiguration()));
		this.#initHeaderIconPicker(icones ?? {});
		this.#form.addEventListener('click', (event) => this.#processHeaderIconPicker(event));
		this.#form.addEventListener('keydown', (event) => {
			if (event.key === 'Escape' && this.#headerIconCatalog?.classList.contains('is-open')) {
				event.preventDefault();
				event.stopPropagation();
				this.#closeHeaderIconPicker();
			}
		});
		this.#updateAppearance();
		this.#updateColumns();
		this.#updateCardConfiguration();
		this.ready();
	}

	#updateCardConfiguration() {
		this.#modoCards = this.#form.querySelector('[name="modo_cards"]:checked');
		const porItem = Number(this.#modoCards?.value ?? <?= WidgetForm::MODO_CARDS_HOST ?>)
			=== <?= WidgetForm::MODO_CARDS_ITEM ?>;

		this.#toggleRows('.js-modo-cards-host', !porItem);
		this.#toggleRows('.js-modo-cards-item', porItem);
		this.#updateLabelGroup(
			'fields-group-rotulo-primario',
			document.getElementById('mostrar_rotulos_<?= WidgetForm::MOSTRAR_ROTULO_PRIMARIO ?>')?.checked ?? true
		);
		this.#updateLabelGroup(
			'fields-group-rotulo-secundario',
			document.getElementById('mostrar_rotulos_<?= WidgetForm::MOSTRAR_ROTULO_SECUNDARIO ?>')?.checked ?? false
		);
	}

	#updateLabelGroup(rowClass, visible) {
		const label = this.#form.querySelector(
			`.<?= CFormGrid::ZBX_STYLE_FIELDS_GROUP_LABEL ?>.${rowClass}`
		);
		const group = this.#form.querySelector(`.<?= CFormGrid::ZBX_STYLE_FIELDS_GROUP ?>.${rowClass}`);

		if (label !== null) {
			label.style.display = visible ? '' : 'none';
		}
		if (group !== null) {
			group.style.display = visible ? '' : 'none';
		}
	}

	#updateColumns() {
		const automaticas = this.#colunasAutomaticas?.checked ?? true;

		for (const row of this.#form.querySelectorAll('.js-limite-colunas-manual')) {
			row.hidden = automaticas;
		}
	}

	#initHeaderIconPicker(icons) {
		this.#headerIconInput = document.getElementById('icone_cabecalho');
		if (this.#headerIconInput === null || Object.keys(icons).length === 0) {
			return;
		}

		const selected = Object.prototype.hasOwnProperty.call(icons, this.#headerIconInput.value)
			? this.#headerIconInput.value
			: 'led.svg';
		this.#headerIconInput.value = selected;
		this.#headerIconInput.hidden = true;

		this.#headerIconCatalog = document.createElement('div');
		this.#headerIconCatalog.className = 'dynamic-status-icons dynamic-status-icons--header';

		const toggle = document.createElement('button');
		toggle.type = 'button';
		toggle.className = 'dynamic-status-icons__toggle';
		toggle.setAttribute('aria-expanded', 'false');
		toggle.setAttribute('aria-haspopup', 'listbox');
		toggle.append(
			this.#makeIconPreview(selected, icons[selected], 'dynamic-status-icons__selected-preview'),
			Object.assign(document.createElement('span'), {
				className: 'dynamic-status-icons__selected-name',
				textContent: selected
			}),
			Object.assign(document.createElement('span'), {
				className: 'dynamic-status-icons__chevron',
				textContent: '▾'
			})
		);

		const panel = document.createElement('div');
		panel.className = 'dynamic-status-icons__panel';
		panel.setAttribute('role', 'listbox');
		for (const [filename, source] of Object.entries(icons)) {
			const option = document.createElement('button');
			option.type = 'button';
			option.className = 'dynamic-status-icons__option';
			option.dataset.icon = filename;
			option.setAttribute('role', 'option');
			option.setAttribute('aria-selected', filename === selected ? 'true' : 'false');
			option.classList.toggle('is-selected', filename === selected);
			option.append(
				this.#makeIconPreview(filename, source),
				Object.assign(document.createElement('span'), {
					className: 'dynamic-status-icons__filename',
					textContent: filename === 'none' ? 'none' : filename
				})
			);
			panel.append(option);
		}

		this.#headerIconCatalog.append(toggle, panel);
		this.#headerIconInput.insertAdjacentElement('afterend', this.#headerIconCatalog);
	}

	#makeIconPreview(filename, source, extra_class = '') {
		const preview = document.createElement('span');
		preview.className = `dynamic-status-icons__preview ${extra_class}`.trim();
		if (filename === 'none') {
			preview.classList.add('dynamic-status-icons__preview--none');
			preview.textContent = '—';
		}
		else {
			preview.style.setProperty('--dsc-icon-preview', `url("${source}")`);
		}

		return preview;
	}

	#processHeaderIconPicker(event) {
		if (this.#headerIconCatalog === null || this.#headerIconCatalog === undefined) {
			return;
		}

		const toggle = event.target.closest('.dynamic-status-icons__toggle');
		if (toggle !== null && this.#headerIconCatalog.contains(toggle)) {
			const open = !this.#headerIconCatalog.classList.contains('is-open');
			this.#headerIconCatalog.classList.toggle('is-open', open);
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (open) {
				requestAnimationFrame(() => this.#headerIconCatalog
					.querySelector('.is-selected')?.scrollIntoView({block: 'nearest'}));
			}
			return;
		}

		const option = event.target.closest('.dynamic-status-icons__option');
		if (option !== null && this.#headerIconCatalog.contains(option)) {
			this.#headerIconInput.value = option.dataset.icon;
			for (const candidate of this.#headerIconCatalog.querySelectorAll('.dynamic-status-icons__option')) {
				const selected = candidate === option;
				candidate.classList.toggle('is-selected', selected);
				candidate.setAttribute('aria-selected', selected ? 'true' : 'false');
			}

			const source = option.querySelector('.dynamic-status-icons__preview');
			const selected = this.#headerIconCatalog.querySelector('.dynamic-status-icons__selected-preview');
			selected.style.cssText = source.style.cssText;
			selected.textContent = source.textContent;
			selected.classList.toggle(
				'dynamic-status-icons__preview--none',
				source.classList.contains('dynamic-status-icons__preview--none')
			);
			this.#headerIconCatalog.querySelector('.dynamic-status-icons__selected-name').textContent = option.dataset.icon;
			this.#closeHeaderIconPicker();
			this.#triggerUpdate();
			return;
		}

		if (!this.#headerIconCatalog.contains(event.target)) {
			this.#closeHeaderIconPicker();
		}
	}

	#closeHeaderIconPicker() {
		this.#headerIconCatalog?.classList.remove('is-open');
		this.#headerIconCatalog?.querySelector('.dynamic-status-icons__toggle')
			?.setAttribute('aria-expanded', 'false');
	}

	#updateAppearance() {
		const fundo = Number(this.#fundoModo.value);
		const fundoWidget = Number(this.#widgetFundoModo.value);
		const texto = Number(this.#textoModo.value);

		this.#toggleRows('.js-fundo-solido', fundo === <?= WidgetForm::FUNDO_SOLIDO ?>);
		this.#toggleRows('.js-fundo-gradiente', fundo === <?= WidgetForm::FUNDO_GRADIENTE ?>);
		this.#toggleRows('.js-widget-fundo-solido', fundoWidget === <?= WidgetForm::FUNDO_SOLIDO ?>);
		this.#toggleRows('.js-widget-fundo-gradiente', fundoWidget === <?= WidgetForm::FUNDO_GRADIENTE ?>);
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
