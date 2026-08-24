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
	static CARD_PREFERRED_WIDTH = 240;
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

		this.#updateWidgetBackground(grid);

		const cards = [...grid.querySelectorAll(':scope > .dynamic-status-card')];
		const cards_count = cards.length;
		const viewport = this.#getViewportSize();

		if (viewport.width <= 0 || viewport.height <= 0) {
			return;
		}

		const configured_maximum = Number.parseInt(grid.dataset.maxColumns, 10) || 0;
		const configured_card_max_width = Math.max(
			CWidgetDynamicStatusCards.CARD_MIN_WIDTH,
			Number.parseInt(grid.dataset.cardMaxWidth, 10) || 320
		);
		const grid_style = getComputedStyle(grid);
		const padding = {
			horizontal: (Number.parseFloat(grid_style.paddingLeft) || 0)
				+ (Number.parseFloat(grid_style.paddingRight) || 0),
			vertical: (Number.parseFloat(grid_style.paddingTop) || 0)
				+ (Number.parseFloat(grid_style.paddingBottom) || 0)
		};

		grid.style.setProperty('--dsc-content-scale', '1');
		grid.style.setProperty('--dsc-content-width', '100%');
		grid.classList.remove(
			'dynamic-status-cards--compact',
			'dynamic-status-cards--narrow',
			'dynamic-status-cards--vertical-compact',
			'dynamic-status-cards--vertical-tight'
		);

		if (cards_count === 0) {
			grid.style.setProperty('--dsc-columns', '1');
			grid.style.setProperty('--dsc-rows', '1');
			grid.style.setProperty('--dsc-card-width', `${Math.max(1, viewport.width - padding.horizontal)}px`);
			grid.style.width = `${viewport.width}px`;
			grid.style.height = `${viewport.height}px`;

			return;
		}

		const initial_content_height = this.#getMaximumContentHeight(cards);
		const initial_layout = this.#selectLayout({
			cards_count,
			configured_maximum,
			configured_card_max_width,
			content_height: initial_content_height,
			padding,
			viewport
		});

		grid.classList.toggle('dynamic-status-cards--compact', initial_layout.card_width < 190);
		grid.classList.toggle('dynamic-status-cards--narrow', initial_layout.card_width < 150);
		grid.classList.toggle('dynamic-status-cards--vertical-compact', initial_layout.scale < .9);
		grid.classList.toggle('dynamic-status-cards--vertical-tight', initial_layout.scale < .72);

		const content_height = this.#getMaximumContentHeight(cards);
		const layout = this.#selectLayout({
			cards_count,
			configured_maximum,
			configured_card_max_width,
			content_height,
			padding,
			viewport
		});

		grid.style.setProperty('--dsc-columns', layout.columns);
		grid.style.setProperty('--dsc-rows', layout.rows);
		grid.style.setProperty('--dsc-card-width', `${layout.card_width}px`);
		grid.style.setProperty('--dsc-content-scale', layout.scale);
		grid.style.setProperty('--dsc-content-width', `${100 / layout.scale}%`);
		grid.style.width = `${layout.grid_width}px`;
		grid.style.height = `${viewport.height}px`;
	}

	#getViewportSize() {
		const style = getComputedStyle(this._contents);
		const width = this._contents.clientWidth
			- (Number.parseFloat(style.paddingLeft) || 0)
			- (Number.parseFloat(style.paddingRight) || 0);
		const height = this._contents.clientHeight
			- (Number.parseFloat(style.paddingTop) || 0)
			- (Number.parseFloat(style.paddingBottom) || 0);

		return {
			width: Math.max(0, width),
			height: Math.max(0, height)
		};
	}

	#getMaximumContentHeight(cards) {
		return Math.max(1, ...cards.map(card => {
			const content = card.querySelector('.dynamic-status-card__conteudo');

			return content === null ? card.scrollHeight : content.scrollHeight;
		}));
	}

	#selectLayout({
		cards_count,
		configured_maximum,
		configured_card_max_width,
		content_height,
		padding,
		viewport
	}) {
		const column_limit = Math.max(1, Math.min(
			cards_count,
			configured_maximum > 0 ? configured_maximum : cards_count
		));
		const available_width = Math.max(1, viewport.width - padding.horizontal);
		const available_height = Math.max(1, viewport.height - padding.vertical);
		const preferred_aspect = Math.min(
			configured_card_max_width,
			CWidgetDynamicStatusCards.CARD_PREFERRED_WIDTH
		) / content_height;
		let best = null;

		for (let columns = 1; columns <= column_limit; columns++) {
			const rows = Math.ceil(cards_count / columns);
			const card_width = Math.min(
				configured_card_max_width,
				(available_width - CWidgetDynamicStatusCards.CARD_GAP * (columns - 1)) / columns
			);
			const card_height = (
				available_height - CWidgetDynamicStatusCards.CARD_GAP * (rows - 1)
			) / rows;

			if (card_width <= 0 || card_height <= 0) {
				continue;
			}

			const scale = Math.max(.02, Math.min(
				1,
				Math.max(0, card_width - 2) / CWidgetDynamicStatusCards.CARD_MIN_WIDTH,
				Math.max(0, card_height - 2) / content_height
			));
			const candidate = {
				columns,
				rows,
				card_width,
				card_height,
				scale,
				grid_width: padding.horizontal + card_width * columns
					+ CWidgetDynamicStatusCards.CARD_GAP * (columns - 1),
				aspect_delta: Math.abs(Math.log(
					Math.max(.01, card_width / card_height) / Math.max(.01, preferred_aspect)
				)),
				empty_slots: rows * columns - cards_count
			};

			if (this.#isBetterLayout(candidate, best)) {
				best = candidate;
			}
		}

		return best ?? {
			columns: 1,
			rows: cards_count,
			card_width: available_width,
			card_height: available_height / cards_count,
			scale: .02,
			grid_width: viewport.width,
			aspect_delta: 0,
			empty_slots: 0
		};
	}

	#isBetterLayout(candidate, current) {
		if (current === null || candidate.scale > current.scale + .005) {
			return true;
		}

		if (candidate.scale < current.scale - .005) {
			return false;
		}

		if (candidate.empty_slots !== current.empty_slots) {
			return candidate.empty_slots < current.empty_slots;
		}

		if (candidate.aspect_delta < current.aspect_delta - .02) {
			return true;
		}

		if (candidate.aspect_delta > current.aspect_delta + .02) {
			return false;
		}

		return candidate.columns > current.columns;
	}

	#updateWidgetBackground(grid) {
		const background = (grid.dataset.widgetBackground ?? '').trim();
		const elements = new Set([
			this._target,
			this._header,
			this._contents
		]);

		for (const element of elements) {
			if (element === null || element === undefined) {
				continue;
			}

			element.classList.remove(
				'dynamic-status-cards-widget--custom-background',
				'dynamic-status-cards-widget--transparent'
			);
			element.style.removeProperty('--dsc-widget-background');

			if (background === '') {
				continue;
			}

			element.style.setProperty('--dsc-widget-background', background);
			element.classList.add('dynamic-status-cards-widget--custom-background');
			element.classList.toggle(
				'dynamic-status-cards-widget--transparent',
				background.toLowerCase() === 'transparent'
			);
		}
	}

}
