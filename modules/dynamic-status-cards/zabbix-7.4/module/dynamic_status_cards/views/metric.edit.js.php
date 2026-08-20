<?php declare(strict_types = 0);

/**
 * PT-BR: Interações do editor visual de uma métrica.
 * EN: Interactions for the visual metric editor.
 *
 * Autor / Author: Daniel Carvalho <danielrc10@gmail.com>
 * Licença / License: PolyForm Noncommercial 1.0.0
 */

use Modules\DynamicStatusCards\Includes\CWidgetFieldMetricList;

?>

window.dynamic_status_cards_metric_edit_form = new class {
	#overlay;
	#dialogue;
	#form;

	init({form_id}) {
		this.#overlay = overlays_stack.getById('dynamic-status-cards-metric-edit-overlay');
		this.#dialogue = this.#overlay.$dialogue[0];
		this.#form = document.getElementById(form_id);

		this.#form.querySelectorAll(
			'[name="formato"], [name="estado_modo"], [name="exibicao"], [name="estado_percentual_calculado"], [name="historico_dias"], [name="historico_cores_personalizadas"]'
		)
			.forEach((element) => {
				element.addEventListener('change', () => this.#updateForm());
				element.addEventListener('input', () => this.#updateForm());
			});

		this.#form.querySelector('.dynamic-status-icons')?.addEventListener('click', (event) => {
			const option = event.target.closest('.dynamic-status-icons__option');
			if (option === null) {
				return;
			}

			this.#form.querySelector('[name="icone"]').value = option.dataset.icon;
			for (const candidate of this.#form.querySelectorAll('.dynamic-status-icons__option')) {
				const selected = candidate === option;
				candidate.classList.toggle('is-selected', selected);
				candidate.setAttribute('aria-pressed', selected ? 'true' : 'false');
			}
		});

		this.#form.querySelectorAll('[name="rotulo"], [name="limite_aviso"], [name="limite_critico"]')
			.forEach((element) => element.addEventListener('change', () => element.value = element.value.trim()));

		this.#updateForm();
		this.#form.style.display = '';
		this.#overlay.recoverFocus();
	}

	#updateForm() {
		const formato = document.getElementById('formato').value;
		const estado_modo = document.getElementById('estado_modo').value;
		const exibicao = document.getElementById('exibicao').value;
		const historico_ativo = exibicao !== '<?= CWidgetFieldMetricList::EXIBICAO_VALOR ?>';
		const percentual_calculado = this.#form.querySelector('[name="estado_percentual_calculado"]').checked;
		const historico_dias = Number.parseInt(this.#form.querySelector('[name="historico_dias"]').value, 10) || 1;
		const cores_personalizadas = this.#form
			.querySelector('[name="historico_cores_personalizadas"]').checked;

		this.#toggle('.js-formato-mapa', formato === '<?= CWidgetFieldMetricList::FORMATO_MAPA ?>');
		this.#toggle('.js-formato-numero', formato === '<?= CWidgetFieldMetricList::FORMATO_NUMERO ?>');
		this.#toggle('.js-formato-decimais', [
			'<?= CWidgetFieldMetricList::FORMATO_NUMERO ?>',
			'<?= CWidgetFieldMetricList::FORMATO_PERCENTUAL_FRACAO ?>'
		].includes(formato));
		this.#toggle('.js-formato-data', formato === '<?= CWidgetFieldMetricList::FORMATO_DATA ?>');
		this.#toggle('.js-estado-limiares', estado_modo === '<?= CWidgetFieldMetricList::ESTADO_LIMITES ?>');
		this.#toggle('.js-estado-valores', estado_modo === '<?= CWidgetFieldMetricList::ESTADO_VALORES ?>');
		this.#toggle('.js-estado-item-alternativo', !percentual_calculado);
		this.#toggle('.js-historico', historico_ativo);
		this.#toggle('.js-historico-aviso-periodo', historico_ativo && historico_dias > 1);
		this.#toggle('.js-historico-cores', historico_ativo && cores_personalizadas);
	}

	#toggle(selector, visible) {
		for (const element of this.#form.querySelectorAll(selector)) {
			element.style.display = visible ? '' : 'none';
		}
	}

	submit() {
		const curl = new Curl(this.#form.getAttribute('action'));
		const fields = getFormFields(this.#form);

		this.#overlay.setLoading();
		fetch(curl.getUrl(), {
			method: 'POST',
			headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
			body: urlEncodeData(fields)
		})
			.then((response) => response.json())
			.then((response) => {
				if ('error' in response) {
					throw {error: response.error};
				}

				overlayDialogueDestroy(this.#overlay.dialogueid);
				this.#dialogue.dispatchEvent(new CustomEvent('dialogue.submit', {detail: response}));
			})
			.catch((exception) => {
				for (const element of this.#form.parentNode.children) {
					if (element.matches('.msg-good, .msg-bad, .msg-warning')) {
						element.remove();
					}
				}

				const title = typeof exception === 'object' && 'error' in exception
					? exception.error.title
					: null;
				const messages = typeof exception === 'object' && 'error' in exception
					? exception.error.messages
					: ['Erro inesperado do servidor.'];
				this.#form.parentNode.insertBefore(makeMessageBox('bad', messages, title)[0], this.#form);
			})
			.finally(() => this.#overlay.unsetLoading());
	}
};
