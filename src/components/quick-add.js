/**
 * Quick Add reservation — Sprint 3.1.
 * Click empty lane slot → modal → Bookly create via CAD.API.createAppointment.
 * @module components/quick-add
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before components/quick-add.js');

  const DEFAULT_DURATION = 90;

  function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text != null && text !== '') node.textContent = text;
    return node;
  }

  function field(labelText, input) {
    const wrap = el('label', 'cad-quick-add__field');
    wrap.append(el('span', 'cad-quick-add__label', labelText), input);
    return wrap;
  }

  function servicesList() {
    const list = CAD.Config.get('services');
    return Array.isArray(list) ? list : [];
  }

  function serviceById(id) {
    const sid = String(id || '');
    return servicesList().find((s) => String(s.id) === sid) || null;
  }

  function defaultServiceId() {
    const configured = Number(CAD.Config.get('quickAddServiceId')) || 0;
    if (configured > 0 && serviceById(configured)) return String(configured);
    const first = servicesList()[0];
    return first?.id != null ? String(first.id) : '';
  }

  function durationForService(serviceId) {
    const svc = serviceById(serviceId);
    const mins = Number(svc?.durationMinutes);
    if (Number.isFinite(mins) && mins >= 15) return mins;
    return defaultDuration();
  }

  function formatWeekday(isoDate) {
    const d = new Date(`${isoDate}T12:00:00`);
    if (Number.isNaN(d.getTime())) return isoDate;
    return d.toLocaleDateString(undefined, { weekday: 'long' });
  }

  function formatClock(minutes) {
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    const d = new Date();
    d.setHours(h, m, 0, 0);
    return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
  }

  function tableName(tableId) {
    const tables = CAD.State.get('tables') || CAD.Config.get('tables') || [];
    const match = (Array.isArray(tables) ? tables : []).find(
      (t) => String(t.id) === String(tableId)
    );
    return match?.name ? String(match.name) : `Table ${tableId}`;
  }

  function slotHeightPx(matrix) {
    const raw = getComputedStyle(matrix).getPropertyValue('--cad-slot-height').trim();
    const n = parseFloat(raw);
    return Number.isFinite(n) && n > 0 ? n : 28;
  }

  function dayStartMin(matrix) {
    const raw = getComputedStyle(matrix).getPropertyValue('--cad-day-start-min').trim();
    const n = parseInt(raw, 10);
    return Number.isFinite(n) ? n : 8 * 60;
  }

  function slotMinutes() {
    const n = Number(CAD.Config.get('slotMinutes'));
    return Number.isFinite(n) && n > 0 ? n : 15;
  }

  /**
   * Map pointer Y in a lane to snapped minutes-from-midnight.
   * @param {HTMLElement} lane
   * @param {number} clientY
   * @param {HTMLElement} matrix
   */
  function snapMinutesFromPoint(lane, clientY, matrix) {
    const blocks = lane.querySelector('.cad-matrix__blocks') || lane;
    const rect = blocks.getBoundingClientRect();
    const y = clientY - rect.top;
    const slotPx = slotHeightPx(matrix);
    const rowIndex = Math.max(0, Math.round(y / slotPx));
    return dayStartMin(matrix) + rowIndex * slotMinutes();
  }

  function toMysqlLocal(isoDate, minutesFromMidnight) {
    const h = Math.floor(minutesFromMidnight / 60);
    const m = minutesFromMidnight % 60;
    return `${isoDate} ${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:00`;
  }

  function defaultDuration() {
    const n = Number(CAD.Config.get('quickAddDurationMinutes'));
    return Number.isFinite(n) && n >= 15 ? n : DEFAULT_DURATION;
  }

  CAD.QuickAdd = {
    root: null,
    panel: null,
    form: null,
    context: null,
    highlight: null,
    container: null,
    _bound: false,
    _busy: false,

    init() {
      if (this.root) return this;

      const root = el('div', 'cad-quick-add');
      root.hidden = true;
      root.setAttribute('role', 'dialog');
      root.setAttribute('aria-modal', 'true');
      root.setAttribute('aria-label', 'Quick add reservation');

      const backdrop = el('div', 'cad-quick-add__backdrop');
      backdrop.addEventListener('click', () => this.close());

      const panel = el('div', 'cad-quick-add__panel');
      const header = el('div', 'cad-quick-add__header');
      const title = el('h2', 'cad-quick-add__title', 'New reservation');
      const closeBtn = el('button', 'cad-quick-add__close', '×');
      closeBtn.type = 'button';
      closeBtn.setAttribute('aria-label', 'Close');
      closeBtn.addEventListener('click', () => this.close());
      header.append(title, closeBtn);

      const summary = el('div', 'cad-quick-add__summary');
      summary.setAttribute('aria-live', 'polite');

      const form = el('form', 'cad-quick-add__form');
      form.addEventListener('submit', (event) => {
        event.preventDefault();
        this.submit();
      });

      const nameInput = el('input', 'cad-quick-add__input');
      nameInput.type = 'text';
      nameInput.name = 'customer_name';
      nameInput.required = true;
      nameInput.autocomplete = 'name';
      nameInput.placeholder = 'Customer name';

      const phoneInput = el('input', 'cad-quick-add__input');
      phoneInput.type = 'tel';
      phoneInput.name = 'phone';
      phoneInput.autocomplete = 'tel';

      const emailInput = el('input', 'cad-quick-add__input');
      emailInput.type = 'email';
      emailInput.name = 'email';
      emailInput.autocomplete = 'email';

      const paintersInput = el('input', 'cad-quick-add__input');
      paintersInput.type = 'number';
      paintersInput.name = 'painters';
      paintersInput.min = '1';
      paintersInput.max = '50';
      paintersInput.value = '1';

      const serviceSelect = el('select', 'cad-quick-add__input');
      serviceSelect.name = 'service_id';
      serviceSelect.required = true;

      const durationInput = el('input', 'cad-quick-add__input');
      durationInput.type = 'number';
      durationInput.name = 'duration_minutes';
      durationInput.min = '15';
      durationInput.step = '15';
      durationInput.value = String(defaultDuration());

      const notesInput = el('textarea', 'cad-quick-add__textarea');
      notesInput.name = 'notes';
      notesInput.rows = 3;

      const error = el('p', 'cad-quick-add__error');
      error.hidden = true;

      const actions = el('div', 'cad-quick-add__actions');
      const cancel = el('button', 'cad-quick-add__btn cad-quick-add__btn--ghost', 'Cancel');
      cancel.type = 'button';
      cancel.addEventListener('click', () => this.close());
      const save = el('button', 'cad-quick-add__btn cad-quick-add__btn--primary', 'Save');
      save.type = 'submit';
      actions.append(cancel, save);

      form.append(
        field('Service', serviceSelect),
        field('Customer name', nameInput),
        field('Phone', phoneInput),
        field('Email', emailInput),
        field('Number of painters', paintersInput),
        field('Duration (minutes)', durationInput),
        field('Notes', notesInput),
        error,
        actions
      );

      panel.append(header, summary, form);
      root.append(backdrop, panel);
      document.body.appendChild(root);

      this.root = root;
      this.panel = panel;
      this.form = form;
      this._summary = summary;
      this._error = error;
      this._saveBtn = save;
      this._nameInput = nameInput;
      this._serviceSelect = serviceSelect;
      this._durationInput = durationInput;

      serviceSelect.addEventListener('change', () => {
        durationInput.value = String(durationForService(serviceSelect.value));
        if (this.context) this.showHighlight(this.context);
      });
      durationInput.addEventListener('change', () => {
        if (this.context) this.showHighlight(this.context);
      });

      if (!this._bound) {
        document.addEventListener('keydown', (event) => {
          if (event.key === 'Escape' && this.isOpen()) {
            event.preventDefault();
            this.close();
          }
        });
        this._bound = true;
      }

      return this;
    },

    isOpen() {
      return Boolean(this.root && !this.root.hidden);
    },

    /**
     * @param {{
     *   tableId: string,
     *   date: string,
     *   startMinutes: number,
     *   lane?: HTMLElement,
     *   matrix?: HTMLElement
     * }} ctx
     */
    open(ctx) {
      this.init();
      CAD.Popover?.close?.();
      CAD.editor?.clear?.();

      this.context = ctx;
      this.clearError();
      this.form.reset();
      this.populateServices();
      this.form.elements.painters.value = '1';
      const serviceId = this._serviceSelect?.value || defaultServiceId();
      this.form.elements.duration_minutes.value = String(durationForService(serviceId));

      this._summary.replaceChildren(
        el('div', 'cad-quick-add__summary-line', tableName(ctx.tableId)),
        el('div', 'cad-quick-add__summary-line', formatWeekday(ctx.date)),
        el('div', 'cad-quick-add__summary-line cad-quick-add__summary-time', formatClock(ctx.startMinutes))
      );

      this.showHighlight(ctx);
      this.root.hidden = false;
      document.body.classList.add('cad-quick-add-open');
      queueMicrotask(() => this._nameInput?.focus());
      return this;
    },

    populateServices() {
      const select = this._serviceSelect;
      if (!select) return;
      const services = servicesList();
      select.replaceChildren();
      if (!services.length) {
        const opt = el('option', null, 'No Bookly services found');
        opt.value = '';
        opt.disabled = true;
        select.appendChild(opt);
        select.value = '';
        return;
      }
      services.forEach((svc) => {
        const opt = el('option', null, svc.name || `Service ${svc.id}`);
        opt.value = String(svc.id);
        select.appendChild(opt);
      });
      const preferred = defaultServiceId();
      select.value = preferred || String(services[0].id);
    },

    close() {
      if (!this.root) return this;
      this.root.hidden = true;
      document.body.classList.remove('cad-quick-add-open');
      this.clearHighlight();
      this.context = null;
      this._busy = false;
      if (this._saveBtn) this._saveBtn.disabled = false;
      return this;
    },

    clearError() {
      if (!this._error) return;
      this._error.hidden = true;
      this._error.textContent = '';
    },

    showError(message) {
      if (!this._error) return;
      this._error.textContent = message || 'Could not create reservation.';
      this._error.hidden = false;
    },

    showHighlight(ctx) {
      this.clearHighlight();
      const lane = ctx.lane;
      const matrix = ctx.matrix;
      if (!lane || !matrix) return;

      const blocks = lane.querySelector('.cad-matrix__blocks') || lane;
      const metrics = CAD.calendar?.gridMetrics?.();
      if (!metrics) return;

      const duration = Math.max(
        15,
        Number(this._durationInput?.value) || durationForService(this._serviceSelect?.value)
      );
      const relStart = Math.max(0, ctx.startMinutes - metrics.startMin);
      const relEnd = Math.min(metrics.rangeMin, ctx.startMinutes + duration - metrics.startMin);
      const heightMin = Math.max(metrics.slotMinutes, relEnd - relStart);
      const pxPerMinute = metrics.gridHeight / metrics.rangeMin;

      const highlight = el('div', 'cad-matrix__slot-highlight');
      highlight.style.top = `${relStart * pxPerMinute}px`;
      highlight.style.height = `${Math.max(heightMin * pxPerMinute, metrics.slotHeight * 0.85)}px`;
      highlight.setAttribute('aria-hidden', 'true');
      blocks.appendChild(highlight);
      this.highlight = highlight;
    },

    clearHighlight() {
      this.highlight?.remove();
      this.highlight = null;
    },

    async submit() {
      if (this._busy || !this.context) return;
      const ctx = this.context;
      const fd = new FormData(this.form);
      const customerName = String(fd.get('customer_name') || '').trim();
      if (!customerName) {
        this.showError('Customer name is required.');
        this._nameInput?.focus();
        return;
      }

      const serviceId = Number(fd.get('service_id')) || 0;
      if (serviceId <= 0) {
        this.showError('Select a Bookly service.');
        this._serviceSelect?.focus();
        return;
      }

      const durationMinutes = Math.max(
        15,
        Number(fd.get('duration_minutes')) || durationForService(serviceId)
      );
      const painters = Math.max(1, Number(fd.get('painters')) || 1);
      const start = toMysqlLocal(ctx.date, ctx.startMinutes);
      const endMinutes = ctx.startMinutes + durationMinutes;
      const end = toMysqlLocal(ctx.date, endMinutes);

      this._busy = true;
      this.clearError();
      if (this._saveBtn) this._saveBtn.disabled = true;

      try {
        if (CAD.schedule?.confirmIfOutside) {
          const ok = await CAD.schedule.confirmIfOutside(ctx.tableId, start, end);
          if (!ok) {
            if (this._saveBtn) this._saveBtn.disabled = false;
            this._busy = false;
            return;
          }
        }

        const result = await CAD.API.createAppointment({
          staffId: ctx.tableId,
          start,
          end,
          durationMinutes,
          customerName,
          phone: String(fd.get('phone') || '').trim(),
          email: String(fd.get('email') || '').trim(),
          painters,
          notes: String(fd.get('notes') || '').trim(),
          serviceId,
        });

        if (result?.success === false) {
          throw Object.assign(new Error(result.data?.message || 'Could not create reservation.'), {
            payload: result.data,
          });
        }

        const created = result?.data?.appointment;
        if (created && created.id != null) {
          const list = Array.isArray(CAD.State.get('appointments'))
            ? CAD.State.get('appointments').slice()
            : [];
          list.push(created);
          CAD.State.set('appointments', list);
        }

        this.close();

        const host =
          CAD.ui?.calendarEl ||
          document.querySelector('#cad-scheduler .cad-scheduler__calendar');
        if (host && CAD.calendar?.render) {
          CAD.calendar.render(host);
        } else if (CAD.ui?.load) {
          await CAD.ui.load(ctx.date);
        }

        CAD.notify?.show('Reservation created.', 'success');
      } catch (err) {
        const message =
          err?.payload?.message || err?.message || 'Could not create reservation.';
        this.showError(String(message));
        CAD.notify?.show(String(message), 'error');
        if (this._saveBtn) this._saveBtn.disabled = false;
        this._busy = false;
      }
    },

    /**
     * Bind empty-lane clicks on the calendar host.
     * @param {HTMLElement} container
     */
    bind(container) {
      if (!container) return this;
      this.init();

      if (this.container === container && container.dataset.cadQuickAddBound === '1') {
        return this;
      }

      this.container = container;
      container.dataset.cadQuickAddBound = '1';

      container.addEventListener('click', (event) => {
        if (document.body.classList.contains('cad-dnd-active')) return;
        if (event.target.closest('.cad-appointment, .cad-popover, .cad-quick-add')) return;

        const lane = event.target.closest('.cad-matrix__lane');
        if (!lane || !container.contains(lane)) return;

        const matrix = lane.closest('.cad-matrix') || container;
        const tableId = lane.dataset.tableId;
        if (!tableId) return;

        const date = String(
          CAD.State.get('selectedDate') || CAD.Config.get('today') || ''
        );
        if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) return;

        event.preventDefault();
        event.stopPropagation();

        const startMinutes = snapMinutesFromPoint(lane, event.clientY, matrix);
        this.open({
          tableId: String(tableId),
          date,
          startMinutes,
          lane,
          matrix,
        });
      });

      return this;
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
