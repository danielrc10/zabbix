/**
 * Controlador de escala proporcional integrado ao ciclo de vida do dashboard.
 * O conteúdo sempre cabe na largura e na altura disponíveis e nunca cria scroll.
 */
class CWidgetRichContent extends CWidget {
	#frame = null;
	#resizeObserver = null;
	#shell = null;

	hasPadding() {
		return false;
	}

	setContents(response) {
		super.setContents(response);
		this.#connect();
	}

	onActivate() {
		this.#scheduleFit();
	}

	onResize() {
		this.#scheduleFit();
	}

	onDeactivate() {
		this.#cancelFit();
	}

	onDestroy() {
		this.#cancelFit();
		this.#resizeObserver?.disconnect();
		this.#resizeObserver = null;
		this.#resetShell();
	}

	#connect() {
		this.#resizeObserver?.disconnect();
		this.#configureShell();

		const root = this._body.querySelector('.rich-content-widget');
		if (root === null) {
			return;
		}

		this.#resizeObserver = new ResizeObserver(() => this.#scheduleFit());
		this.#resizeObserver.observe(root);

		for (const image of root.querySelectorAll('img')) {
			if (!image.complete) {
				image.addEventListener('load', () => this.#scheduleFit(), {once: true});
				image.addEventListener('error', () => this.#scheduleFit(), {once: true});
			}
		}

		if (document.fonts?.ready !== undefined) {
			document.fonts.ready.then(() => this.#scheduleFit());
		}
		this.#scheduleFit();
	}

	#configureShell() {
		this.#resetShell();
		const root = this._body.querySelector('.rich-content-widget');
		const shell = root?.closest('.dashboard-grid-widget');
		if (root === null || shell === null) {
			return;
		}

		this.#shell = shell;
		const transparent = root.dataset.backgroundMode === '0';
		shell.classList.toggle('rich-content-shell--transparent', transparent);
		shell.classList.toggle('rich-content-shell--custom', !transparent);
		shell.style.setProperty('--rc-shell-background', root.dataset.shellBackground ?? 'transparent');
		shell.style.setProperty('--rc-shell-text-color', getComputedStyle(root).getPropertyValue('--rc-text-color'));
	}

	#resetShell() {
		if (this.#shell === null) {
			return;
		}
		this.#shell.classList.remove('rich-content-shell--transparent', 'rich-content-shell--custom');
		this.#shell.style.removeProperty('--rc-shell-background');
		this.#shell.style.removeProperty('--rc-shell-text-color');
		this.#shell = null;
	}

	#scheduleFit() {
		this.#cancelFit();
		this.#frame = requestAnimationFrame(() => {
			this.#frame = null;
			this.#fit();
		});
	}

	#cancelFit() {
		if (this.#frame !== null) {
			cancelAnimationFrame(this.#frame);
			this.#frame = null;
		}
	}

	#fit() {
		const root = this._body.querySelector('.rich-content-widget');
		const canvas = root?.querySelector('.rich-content-canvas');
		if (root === null || canvas === null) {
			return;
		}

		const rootStyle = getComputedStyle(root);
		const padding = Number.parseFloat(rootStyle.getPropertyValue('--rc-padding')) || 0;
		const availableWidth = Math.max(1, root.clientWidth - padding * 2);
		const availableHeight = Math.max(1, root.clientHeight - padding * 2);
		const designWidth = Math.max(320, Number(root.dataset.designWidth) || 960);

		canvas.style.width = `${designWidth}px`;
		canvas.style.left = `${padding}px`;
		canvas.style.top = `${padding}px`;
		canvas.style.transform = 'none';

		// scrollWidth/scrollHeight medem o fluxo; getBoundingClientRect inclui rotação e flip das imagens.
		const canvasRect = canvas.getBoundingClientRect();
		let minimumX = 0;
		let minimumY = 0;
		let maximumX = Math.max(canvas.offsetWidth, canvas.scrollWidth);
		let maximumY = Math.max(canvas.offsetHeight, canvas.scrollHeight);
		for (const element of canvas.querySelectorAll('img')) {
			const rectangle = element.getBoundingClientRect();
			minimumX = Math.min(minimumX, rectangle.left - canvasRect.left);
			minimumY = Math.min(minimumY, rectangle.top - canvasRect.top);
			maximumX = Math.max(maximumX, rectangle.right - canvasRect.left);
			maximumY = Math.max(maximumY, rectangle.bottom - canvasRect.top);
		}

		const naturalWidth = Math.max(1, maximumX - minimumX);
		const naturalHeight = Math.max(1, maximumY - minimumY);
		let scale = Math.min(availableWidth / naturalWidth, availableHeight / naturalHeight);
		if (root.dataset.allowUpscale !== '1') {
			scale = Math.min(1, scale);
		}
		else {
			scale = Math.min(4, scale);
		}

		const left = padding + Math.max(0, (availableWidth - naturalWidth * scale) / 2) - minimumX * scale;
		const top = padding + Math.max(0, (availableHeight - naturalHeight * scale) / 2) - minimumY * scale;
		canvas.style.left = `${left}px`;
		canvas.style.top = `${top}px`;
		canvas.style.transform = `scale(${scale})`;
		root.style.setProperty('--rc-current-scale', String(scale));
		root.dataset.scale = scale.toFixed(4);
	}
}
