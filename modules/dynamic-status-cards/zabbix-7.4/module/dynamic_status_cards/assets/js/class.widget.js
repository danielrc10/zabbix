/*
 * PT-BR: Controlador responsivo do widget Cards de status dinâmicos para Zabbix 7.4.
 * EN: Responsive controller for the Dynamic Status Cards widget for Zabbix 7.4.
 *
 * Autor / Author: Daniel Carvalho <danielrc10@gmail.com>
 * Licença / License: PolyForm Noncommercial 1.0.0
 * Uso comercial / Commercial use: contato / contact danielrc10@gmail.com
 */

class CWidgetDynamicStatusCards extends CWidget {

	static CARD_MIN_WIDTH = 150;
	static CARD_GAP = 8;

	#layout_frame = null;

	hasPadding() {
		return false;
	}

	onResize() {
		this.#scheduleLayout();
	}

	onDeactivate() {
		this.#cancelLayout();
	}

	onDestroy() {
		this.#cancelLayout();
	}

	setContents(response) {
		super.setContents(response);
		this.#scheduleLayout();
	}

	#scheduleLayout() {
		this.#cancelLayout();
		this.#layout_frame = requestAnimationFrame(() => {
			this.#layout_frame = null;
			this.#updateLayout();
		});
	}

	#cancelLayout() {
		if (this.#layout_frame !== null) {
			cancelAnimationFrame(this.#layout_frame);
			this.#layout_frame = null;
		}
	}

	#updateLayout() {
		const grid = this._body.querySelector('.dynamic-status-cards');

		if (grid === null) {
			return;
		}

		const cards_count = grid.querySelectorAll(':scope > .dynamic-status-card').length;
		const configured_maximum = Number.parseInt(grid.dataset.maxColumns, 10) || 0;
		const max_columns = configured_maximum > 0
			? configured_maximum
			: Math.max(1, cards_count);
		const width = grid.clientWidth;

		if (width <= 0) {
			return;
		}

		const capacity = Math.max(1, Math.floor(
			(width + CWidgetDynamicStatusCards.CARD_GAP)
			/ (CWidgetDynamicStatusCards.CARD_MIN_WIDTH + CWidgetDynamicStatusCards.CARD_GAP)
		));
		const columns = Math.max(1, Math.min(max_columns, Math.max(1, cards_count), capacity));
		const card_width = (width - CWidgetDynamicStatusCards.CARD_GAP * (columns - 1)) / columns;

		grid.style.setProperty('--dsc-columns', columns);
		grid.classList.toggle('dynamic-status-cards--compact', card_width < 190);
		grid.classList.toggle('dynamic-status-cards--narrow', card_width < 150);
		this.#updateVerticalDensity(grid);
	}

	#updateVerticalDensity(grid) {
		grid.classList.remove(
			'dynamic-status-cards--vertical-compact',
			'dynamic-status-cards--vertical-tight'
		);

		const available_height = this._body.clientHeight;

		if (available_height <= 0 || grid.scrollHeight <= available_height + 1) {
			return;
		}

		grid.classList.add('dynamic-status-cards--vertical-compact');

		if (grid.scrollHeight > available_height + 1) {
			grid.classList.add('dynamic-status-cards--vertical-tight');
		}
	}
}
