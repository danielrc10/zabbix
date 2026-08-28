<?php declare(strict_types = 0); ?>

window.widget_form = new class extends CWidgetForm {
	#form;
	#config;
	#mediaSource;

	init(config) {
		this.#form = this.getForm();
		this.#config = config;
		this.#mediaSource = document.getElementById('media_source');

		for (const id of ['background_mode', 'gradient_third_color', 'media_position', 'border_style']) {
			document.getElementById(id)?.addEventListener('change', () => this.#updateVisibility());
		}

		this.#installFilePicker();
		this.#updateVisibility();
		this.ready();
	}

	#installFilePicker() {
		if (this.#mediaSource === null) {
			return;
		}

		const wrapper = document.createElement('div');
		wrapper.className = 'rich-content-upload';
		const input = document.createElement('input');
		input.type = 'file';
		input.accept = 'image/png,image/jpeg,image/gif,image/webp,image/svg+xml';
		input.className = 'rich-content-upload__input';
		input.setAttribute('aria-label', 'Carregar imagem local como base64');
		const note = document.createElement('small');
		note.textContent = 'Arquivo local → base64 (máximo 47 KiB). Para imagens maiores, use URL ou assets/media.';
		wrapper.append(input, note);
		this.#mediaSource.insertAdjacentElement('afterend', wrapper);

		input.addEventListener('change', () => {
			const file = input.files?.[0];
			if (!file) {
				return;
			}
			if (!file.type.startsWith('image/') || file.size > 48_128) {
				alert('Selecione uma imagem válida de até 47 KiB.');
				input.value = '';
				return;
			}

			const reader = new FileReader();
			reader.addEventListener('load', () => {
				this.#mediaSource.value = String(reader.result ?? '');
				this.#mediaSource.dispatchEvent(new Event('change', {bubbles: true}));
				input.value = '';
			});
			reader.readAsDataURL(file);
		});
	}

	#updateVisibility() {
		const backgroundMode = Number(document.getElementById('background_mode')?.value);
		const thirdColor = document.getElementById('gradient_third_color')?.checked ?? false;
		const mediaVisible = Number(document.getElementById('media_position')?.value) !== this.#config.media_hidden;
		const borderVisible = Number(document.getElementById('border_style')?.value) !== this.#config.border_none;

		this.#toggle('.js-rich-background-solid', backgroundMode === this.#config.background_solid);
		this.#toggle('.js-rich-background-gradient', backgroundMode === this.#config.background_gradient);
		this.#toggle(
			'.js-rich-background-gradient.js-rich-gradient-third',
			backgroundMode === this.#config.background_gradient && thirdColor
		);
		this.#toggle('.js-rich-media-option', mediaVisible);
		this.#toggle('.js-rich-border-option', borderVisible);
	}

	#toggle(selector, visible) {
		for (const row of this.#form.querySelectorAll(selector)) {
			row.hidden = !visible;
		}
	}
};
