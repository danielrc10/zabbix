class CWidgetDynamicRack extends CWidget {
	#frame = null;

	hasPadding() { return false; }

	setContents(response) {
		super.setContents(response);
		this._body.querySelector('.dynamic-rack-widget')?.addEventListener('click', (event) => this.#select(event));
		this._body.querySelector('.dynamic-rack-widget')?.addEventListener('keydown', (event) => {
			if (['Enter', ' '].includes(event.key)) { event.preventDefault(); this.#select(event); }
		});
		this.#schedule();
	}

	onResize() { this.#schedule(); }
	onDeactivate() { this.#cancel(); }
	onDestroy() { this.#cancel(); }

	#schedule() {
		this.#cancel(); this.#frame = requestAnimationFrame(() => { this.#frame = null; this.#layout(); });
	}
	#cancel() { if (this.#frame !== null) { cancelAnimationFrame(this.#frame); this.#frame = null; } }

	#select(event) {
		const target = event.target.closest('[data-hostid]');
		if (target === null || target.dataset.hostid === '') return;
		const hostid = target.dataset.hostid;
		const itemids = (target.dataset.itemids ?? '').split(',').filter(Boolean);
		const payload = {
			[CWidgetsData.DATA_TYPE_HOST_ID]: [hostid], [CWidgetsData.DATA_TYPE_HOST_IDS]: [hostid]
		};
		if (itemids.length > 0) {
			payload[CWidgetsData.DATA_TYPE_ITEM_ID] = [itemids[0]];
			payload[CWidgetsData.DATA_TYPE_ITEM_IDS] = itemids;
		}
		this.broadcast(payload);
		for (const node of this._body.querySelectorAll('[data-hostid]')) node.classList.toggle('is-selected', node.dataset.hostid === hostid);
	}

	#layout() {
		const root = this._body.querySelector('.dynamic-rack-widget');
		const stage = root?.querySelector('.dynamic-rack-stage');
		const rack = root?.querySelector('.dynamic-rack-body');
		if (!root || !stage || !rack) return;
		stage.style.height = '';
		this.#fitLabels(root);
		let stageRect = stage.getBoundingClientRect(); let rackRect = rack.getBoundingClientRect();
		const scale = Number(root.dataset.uScale) || 28; const width = Number(root.dataset.balloonWidth) || 210;
		const balloons = [...stage.querySelectorAll('.dynamic-rack-balloon')];
		const sides = {left: [], right: []}; let automatic = 0;
		for (const balloon of balloons) {
			let side = balloon.dataset.side;
			if (side === 'auto') side = automatic++ % 2 === 0 ? 'right' : 'left';
			const anchor = rackRect.bottom - stageRect.top - ((Number(balloon.dataset.u) - 1 + Number(balloon.dataset.heightU) / 2) * scale);
			balloon.style.width = `${width}px`; balloon.dataset.resolvedSide = side;
			sides[side].push({balloon, anchor});
		}
		const requiredHeight = Math.max(
			rackRect.height + 28,
			...Object.values(sides).map((entries) => entries.reduce((height, entry) => height + entry.balloon.offsetHeight + 8, 0))
		);
		if (requiredHeight > stageRect.height) {
			stage.style.height = `${requiredHeight}px`;
			stageRect = stage.getBoundingClientRect(); rackRect = rack.getBoundingClientRect();
		}
		for (const side of ['left', 'right']) this.#placeSide(sides[side], side, stageRect, rackRect, width);
		this.#drawLines(stage, rack, balloons);
	}

	#placeSide(entries, side, stageRect, rackRect, width) {
		entries.sort((a, b) => a.anchor - b.anchor); const gap = 8; let bottom = 0;
		for (const entry of entries) {
			const height = entry.balloon.offsetHeight; entry.top = Math.max(0, entry.anchor - height / 2, bottom);
			bottom = entry.top + height + gap;
		}
		if (entries.length > 0) {
			let overflow = entries[entries.length - 1].top + entries[entries.length - 1].balloon.offsetHeight - stageRect.height;
			for (let index = entries.length - 1; index >= 0 && overflow > 0; index--) {
				const maxTop = index === entries.length - 1 ? stageRect.height - entries[index].balloon.offsetHeight
					: entries[index + 1].top - gap - entries[index].balloon.offsetHeight;
				entries[index].top = Math.max(0, Math.min(entries[index].top, maxTop));
			}
		}
		for (const entry of entries) {
			const rackLeft = rackRect.left - stageRect.left; const rackRight = rackRect.right - stageRect.left;
			entry.balloon.style.top = `${entry.top}px`;
			entry.balloon.style.left = `${side === 'left' ? rackLeft - width - 28 : rackRight + 28}px`;
		}
	}

	#drawLines(stage, rack, balloons) {
		const svg = stage.querySelector('.dynamic-rack-lines'); svg.replaceChildren();
		const stageRect = stage.getBoundingClientRect(); const rackRect = rack.getBoundingClientRect();
		svg.setAttribute('viewBox', `0 0 ${stageRect.width} ${stageRect.height}`);
		for (const balloon of balloons) {
			const rect = balloon.getBoundingClientRect(); const side = balloon.dataset.resolvedSide;
			const startX = side === 'left' ? rackRect.left - stageRect.left : rackRect.right - stageRect.left;
			const endX = side === 'left' ? rect.right - stageRect.left : rect.left - stageRect.left;
			const startY = rackRect.bottom - stageRect.top - ((Number(balloon.dataset.u) - 1 + Number(balloon.dataset.heightU) / 2) * (Number(stage.closest('.dynamic-rack-widget').dataset.uScale) || 28));
			const endY = rect.top - stageRect.top + rect.height / 2;
			const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
			const bendX = startX + (endX - startX) * .55;
			path.setAttribute('d', `M ${startX} ${startY} L ${bendX} ${startY} L ${endX} ${endY}`);
			path.setAttribute('class', `dynamic-rack-line status-${[...balloon.classList].find((name) => name.startsWith('status-'))?.slice(7) ?? 'neutral'}`);
			svg.append(path);
		}
	}

	#fitLabels(root) {
		if (root.dataset.autoFitLabels !== '1') return;
		for (const label of root.querySelectorAll('.device-label-fit')) {
			label.style.fontSize = ''; let size = Number.parseFloat(getComputedStyle(label).fontSize) || 12;
			while (size > 5 && (label.scrollWidth > label.clientWidth || label.scrollHeight > label.clientHeight)) {
				size -= .5; label.style.fontSize = `${size}px`;
			}
			if (label.scrollWidth > label.clientWidth || label.scrollHeight > label.clientHeight) {
				label.title = label.textContent.trim();
			}
		}
	}
}
