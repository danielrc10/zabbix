window.widget_form = new class extends CWidgetForm {
	#form; #templateid; #lists = {}; #templates = {}; #sortables = {};

	init({templateid}) {
		this.#form = this.getForm(); this.#templateid = templateid;
		this.#setupList('equipment', 'js-equipment-data');
		this.#setupList('indicators', 'js-indicator-data');
		this.#form.querySelector('[name="rack_preset"]')?.addEventListener('change', (event) => {
			const units = Number(event.target.value);
			if (units > 0) {
				this.#form.querySelector('[name="rack_units"]').value = units;
				this.#triggerUpdate();
			}
		});
		this.#form.querySelector('[name="rack_units"]')?.addEventListener('input', (event) => {
			const preset = this.#form.querySelector('[name="rack_preset"]');
			if (preset && Number(preset.value) !== Number(event.target.value)) preset.value = '0';
		});
		this.ready();
	}

	#setupList(name, dataClass) {
		const list = document.getElementById(`list_${name}`);
		this.#lists[name] = list;
		this.#templates[name] = new Template(list.querySelector('template').innerHTML);
		this.#sortables[name] = new CSortable(list.querySelector('tbody'), {selector_handle: '.table-col-handle'});
		list.addEventListener('click', (event) => this.#processAction(name, dataClass, event));
		this.#sortables[name].on(CSortable.EVENT_SORT, () => {
			this.#reindex(name); this.#triggerUpdate();
		});
	}

	#processAction(listName, dataClass, event) {
		const action = event.target.getAttribute('name');
		if (!['add', 'edit', 'copy', 'remove'].includes(action)) return;
		if (action === 'remove') {
			event.target.closest('tr').remove(); this.#reindex(listName); this.#triggerUpdate(); return;
		}

		const fields = getFormFields(this.#form);
		const isEquipment = listName === 'equipment';
		const params = {
			hostids: fields.hostids ?? [], groupids: fields.groupids ?? [],
			host_tags: fields.host_tags ?? [], evaltype_host: fields.evaltype_host ?? 0,
			maintenance: fields.maintenance ?? 0
		};
		if (this.#templateid !== null) params.templateid = this.#templateid;
		if (isEquipment) params.parent_keys = (fields.equipment ?? []).filter((row) => row.type === 'shelf').map((row) => row.key);
		else {
			params.equipment_keys = (fields.equipment ?? []).map((row) => row.key);
			const equipmentHosts = (fields.equipment ?? []).map((row) => row.hostid).filter(Boolean);
			if (equipmentHosts.length > 0) params.hostids = [...new Set(equipmentHosts)];
		}

		let index = this.#nextIndex(listName);
		if (action === 'edit' || action === 'copy') {
			const sourceIndex = event.target.closest('tr').dataset.index;
			Object.assign(params, fields[listName][sourceIndex]);
			if (action === 'edit') { index = sourceIndex; params.edit = 1; }
			else {
				params.copy = 1;
				if (isEquipment) params.key = `${params.key}-copy`;
			}
		}

		const suffix = isEquipment ? 'equipment' : 'indicator';
		const dialogue = PopUp(`widget.dynamic_rack.${suffix}.edit`, params, {
			dialogueid: `dynamic-rack-${suffix}-edit-overlay`, dialogue_class: 'modal-popup-generic'
		}).$dialogue[0];
		dialogue.addEventListener('dialogue.submit', (submitEvent) => {
			const row = isEquipment
				? this.#makeEquipmentRow(submitEvent.detail, index)
				: this.#makeIndicatorRow(submitEvent.detail, index);
			if (action === 'edit') this.#lists[listName].querySelector(`tbody > tr[data-index="${index}"]`).replaceWith(row);
			else this.#lists[listName].querySelector('tbody').append(row);
			this.#reindex(listName); this.#triggerUpdate();
		});
	}

	#makeEquipmentRow(data, index) {
		const child = (data.parent_key ?? '') !== '';
		const row = this.#templates.equipment.evaluateToElement({
			...data,
			u_summary: child ? `em ${data.parent_key} · slot ${data.slot}` : `U${data.u} · ${data.height_u}U`,
			type_summary: data.type,
			host_summary: data.hostid ? `#${data.hostid}` : 'passivo/manual', rowNum: index
		});
		this.#finishRow(row, 'equipment', 'js-equipment-data', data, index); return row;
	}

	#makeIndicatorRow(data, index) {
		const row = this.#templates.indicators.evaluateToElement({
			...data, pattern_summary: Object.values(data.patterns ?? {}).join(', '),
			placement_summary: `${data.location} · balão ${data.balloon} · ${data.side}`, rowNum: index
		});
		this.#finishRow(row, 'indicators', 'js-indicator-data', data, index); return row;
	}

	#finishRow(row, listName, dataClass, data, index) {
		row.dataset.index = index; const container = row.querySelector(`.${dataClass}`);
		for (const [key, value] of Object.entries(data)) if (key !== 'edit') this.#appendVars(container, `${listName}[${index}][${key}]`, value);
	}

	#appendVars(container, name, value) {
		if (value !== null && typeof value === 'object') {
			for (const [key, child] of Object.entries(value)) this.#appendVars(container, `${name}[${key}]`, child);
			return;
		}
		const input = document.createElement('input'); input.type = 'hidden'; input.name = name; input.value = value; container.append(input);
	}

	#nextIndex(name) {
		const values = [...this.#lists[name].querySelectorAll('tbody > tr[data-index]')].map((row) => Number.parseInt(row.dataset.index, 10));
		return values.length === 0 ? 0 : Math.max(...values) + 1;
	}

	#reindex(name) {
		const rows = [...this.#lists[name].querySelectorAll('tbody > tr[data-index]')];
		for (const [position, row] of rows.entries()) {
			for (const input of row.querySelectorAll(`input[name^="${name}["]`)) input.name = input.name.replace(new RegExp(`^${name}\\[\\d+]`), `${name}[${10000 + position}]`);
		}
		for (const [position, row] of rows.entries()) {
			for (const input of row.querySelectorAll(`input[name^="${name}[${10000 + position}]"]`)) input.name = input.name.replace(`${name}[${10000 + position}]`, `${name}[${position}]`);
			row.dataset.index = position;
		}
	}

	#triggerUpdate() {
		this.#form.dispatchEvent(new CustomEvent('form_fields.changed', {detail: {}})); this.registerUpdateEvent();
	}
};
