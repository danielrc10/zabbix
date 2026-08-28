window.dynamic_rack_indicator_edit_form = new class {
	#overlay; #dialogue; #form;
	init({form_id}) {
		this.#overlay = overlays_stack.getById('dynamic-rack-indicator-edit-overlay');
		this.#dialogue = this.#overlay.$dialogue[0]; this.#form = document.getElementById(form_id);
		this.#form.querySelector('[name="status_mode"]').addEventListener('change', () => this.#update());
		this.#update(); this.#form.style.display = ''; this.#overlay.recoverFocus();
	}
	#update() {
		const mode = this.#form.querySelector('[name="status_mode"]').value;
		this.#toggle('.js-thresholds', mode === 'thresholds'); this.#toggle('.js-values', mode === 'values');
	}
	#toggle(selector, visible) { for (const element of this.#form.querySelectorAll(selector)) element.style.display = visible ? '' : 'none'; }
	submit() {
		const fields = getFormFields(this.#form); this.#overlay.setLoading();
		fetch(new Curl(this.#form.getAttribute('action')).getUrl(), {
			method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}, body: urlEncodeData(fields)
		}).then((response) => response.json()).then((response) => {
			if ('error' in response) throw {error: response.error};
			overlayDialogueDestroy(this.#overlay.dialogueid);
			this.#dialogue.dispatchEvent(new CustomEvent('dialogue.submit', {detail: response}));
		}).catch((exception) => {
			for (const element of this.#form.parentNode.children) if (element.matches('.msg-good, .msg-bad, .msg-warning')) element.remove();
			const messages = typeof exception === 'object' && 'error' in exception ? exception.error.messages : ['Erro inesperado do servidor.'];
			this.#form.parentNode.insertBefore(makeMessageBox('bad', messages)[0], this.#form);
		}).finally(() => this.#overlay.unsetLoading());
	}
};
