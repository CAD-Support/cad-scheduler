/**
 * Shared Reservation Dialog — New + Edit modes (Sprint 3.2.6).
 * One layout/chrome; mode only changes title, initial data, and footer (Delete on edit).
 * Does not change Provider / Mapper / AJAX / Bookly save contracts.
 * @module components/reservation-dialog
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) {
    throw new Error('cad-core.js must be loaded before components/reservation-dialog.js');
  }

  const SLOT_MINUTES = 15;
  const DEFAULT_CAPACITY = 8;
  const DEFAULT_DURATION = 90;

  function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text != null && text !== '') node.textContent = text;
    return node;
  }

  function field(labelText, input, extraClass) {
    const wrap = el('label', `cad-rm__field${extraClass ? ` ${extraClass}` : ''}`);
    wrap.append(el('span', 'cad-rm__label', labelText), input);
    return wrap;
  }

  function section(title, className) {
    const sec = el('section', `cad-rm__section ${className || ''}`.trim());
    sec.append(el('h3', 'cad-rm__section-title', title));
    const body = el('div', 'cad-rm__section-body');
    sec.append(body);
    return { sec, body };
  }

  function parseLocal(sql) {
    return CAD.utils?.parseBooklyLocal
      ? CAD.utils.parseBooklyLocal(sql)
      : new Date(NaN);
  }

  function toMysql(date) {
    if (!(date instanceof Date) || Number.isNaN(date.getTime())) return '';
    const y = date.getFullYear();
    const mo = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    const h = String(date.getHours()).padStart(2, '0');
    const mi = String(date.getMinutes()).padStart(2, '0');
    const s = String(date.getSeconds()).padStart(2, '0');
    return `${y}-${mo}-${d} ${h}:${mi}:${s}`;
  }

  function dateInputValue(sql) {
    const d = parseLocal(sql);
    if (Number.isNaN(d.getTime())) return '';
    const y = d.getFullYear();
    const mo = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${mo}-${day}`;
  }

  function timeInputValue(sql) {
    const d = parseLocal(sql);
    if (Number.isNaN(d.getTime())) return '';
    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
  }

  function combineDateTime(dateStr, timeStr) {
    if (!dateStr || !timeStr) return '';
    const [y, m, d] = dateStr.split('-').map(Number);
    const [h, min] = timeStr.split(':').map(Number);
    return toMysql(new Date(y, m - 1, d, h, min || 0, 0));
  }

  function durationMinutes(startSql, endSql) {
    const start = parseLocal(startSql);
    const end = parseLocal(endSql);
    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return 0;
    return Math.max(0, Math.round((end.getTime() - start.getTime()) / 60000));
  }

  function parseClockToMinutes(value) {
    const m = String(value || '').match(/^(\d{1,2}):(\d{2})$/);
    if (!m) return null;
    const h = Number(m[1]);
    const min = Number(m[2]);
    if (!Number.isFinite(h) || !Number.isFinite(min)) return null;
    return h * 60 + min;
  }

  function minutesToClock(minutes) {
    const clamped = Math.max(0, Math.round(minutes));
    const h = Math.floor(clamped / 60);
    const m = clamped % 60;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
  }

  function defaultServiceId() {
    const configured = Number(CAD.Config.get('quickAddServiceId')) || 0;
    if (configured > 0 && serviceById(configured)) return String(configured);
    const first = servicesList()[0];
    return first?.id != null ? String(first.id) : '';
  }

  function defaultDuration() {
    const n = Number(CAD.Config.get('quickAddDurationMinutes'));
    return Number.isFinite(n) && n >= 15 ? n : DEFAULT_DURATION;
  }

  function durationForService(serviceId) {
    const svc = serviceById(serviceId);
    const mins = Number(svc?.durationMinutes);
    if (Number.isFinite(mins) && mins >= 15) return mins;
    return defaultDuration();
  }

  function toMysqlLocal(isoDate, minutesFromMidnight) {
    const h = Math.floor(minutesFromMidnight / 60);
    const m = minutesFromMidnight % 60;
    return `${isoDate} ${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:00`;
  }

  function formatTimeLabel(hhmm) {
    const mins = parseClockToMinutes(hhmm);
    if (mins == null) return hhmm;
    const d = new Date();
    d.setHours(Math.floor(mins / 60), mins % 60, 0, 0);
    return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
  }

  function dayBoundsMinutes() {
    const start =
      parseClockToMinutes(CAD.Config.get('dayStart')) ??
      parseClockToMinutes('08:00') ??
      8 * 60;
    const end =
      parseClockToMinutes(CAD.Config.get('dayEnd')) ??
      parseClockToMinutes('20:00') ??
      20 * 60;
    return { start, end: Math.max(start + SLOT_MINUTES, end) };
  }

  function timeOptionValues(extra) {
    const { start, end } = dayBoundsMinutes();
    const values = [];
    for (let m = start; m <= end; m += SLOT_MINUTES) {
      const h = Math.floor(m / 60);
      const min = m % 60;
      values.push(`${String(h).padStart(2, '0')}:${String(min).padStart(2, '0')}`);
    }
    const extras = Array.isArray(extra) ? extra : [extra];
    extras.forEach((v) => {
      if (v && !values.includes(v)) values.push(v);
    });
    values.sort((a, b) => (parseClockToMinutes(a) || 0) - (parseClockToMinutes(b) || 0));
    return values;
  }

  function fillTimeSelect(select, selected, extras) {
    const values = timeOptionValues(extras);
    select.replaceChildren();
    values.forEach((value) => {
      const opt = el('option', null, formatTimeLabel(value));
      opt.value = value;
      select.appendChild(opt);
    });
    if (selected && values.includes(selected)) select.value = selected;
    else if (values.length) select.value = values[0];
  }

  function tableOptions() {
    const tables = CAD.State.get('tables') || CAD.Config.get('tables') || [];
    return Array.isArray(tables) ? tables : [];
  }

  function servicesList() {
    const list = CAD.Config.get('services');
    return Array.isArray(list) ? list : [];
  }

  function serviceById(id) {
    const sid = String(id || '');
    return servicesList().find((s) => String(s.id) === sid) || null;
  }

  /**
   * One Service <select> with Bookly categories as non-selectable <optgroup> labels.
   * @param {HTMLSelectElement} select
   * @param {string} [selectedId]
   * @param {{ id?: string, name?: string }|null} [orphan]
   */
  function fillServiceSelect(select, selectedId, orphan) {
    const services = servicesList();
    select.replaceChildren();

    const groups = new Map();
    const ungrouped = [];
    services.forEach((svc) => {
      const label = String(svc.categoryName || '').trim();
      if (label) {
        if (!groups.has(label)) groups.set(label, []);
        groups.get(label).push(svc);
      } else {
        ungrouped.push(svc);
      }
    });

    const appendOpt = (parent, svc) => {
      const opt = el('option', null, svc.name || `Service ${svc.id}`);
      opt.value = String(svc.id);
      parent.appendChild(opt);
    };

    groups.forEach((list, label) => {
      const group = document.createElement('optgroup');
      group.label = label;
      list.forEach((svc) => appendOpt(group, svc));
      select.appendChild(group);
    });
    ungrouped.forEach((svc) => appendOpt(select, svc));

    const sid = String(selectedId || '');
    if (sid && !serviceById(sid) && orphan) {
      const opt = el('option', null, orphan.name || `Service ${sid}`);
      opt.value = sid;
      select.appendChild(opt);
    }

    if (sid && [...select.options].some((o) => o.value === sid)) {
      select.value = sid;
    } else if (services[0]) {
      select.value = String(services[0].id);
    } else {
      select.value = '';
    }
  }

  function tableById(id) {
    const tid = String(id || '');
    return tableOptions().find((t) => String(t.id) === tid) || null;
  }

  function tableCapacity(tableId) {
    const t = tableById(tableId);
    let cap = Number(t?.capacity);
    if (!Number.isFinite(cap) || cap < 1) {
      cap = Number(CAD.Config.get('defaultTableCapacity')) || DEFAULT_CAPACITY;
    }
    return Math.max(1, Math.min(50, Math.floor(cap)));
  }

  function formatWeekday(dateStr) {
    if (!dateStr) return '';
    const d = new Date(`${dateStr}T12:00:00`);
    if (Number.isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString(undefined, {
      weekday: 'long',
      month: 'long',
      day: 'numeric',
    });
  }

  function patchStateAppointment(updated) {
    if (!updated?.id) return;
    const list = Array.isArray(CAD.State.get('appointments'))
      ? CAD.State.get('appointments').slice()
      : [];
    const idx = list.findIndex((a) => String(a.id) === String(updated.id));
    if (idx >= 0) list[idx] = { ...list[idx], ...updated };
    else list.push(updated);
    CAD.State.set('appointments', list);
  }

  function reRenderCalendar() {
    const host =
      CAD.ui?.calendarEl ||
      document.querySelector('#cad-scheduler .cad-scheduler__calendar');
    if (host && CAD.calendar?.render) CAD.calendar.render(host);
  }

  CAD.ReservationDialog = {
    root: null,
    form: null,
    mode: null,
    appointmentId: null,
    context: null,
    highlight: null,
    detailFields: [],
    _appointment: null,
    _busy: false,
    _syncing: false,
    _bound: false,
    _lastDurationMins: null,

    init() {
      if (this.root) return this;

      const root = el('div', 'cad-rm');
      root.hidden = true;
      root.setAttribute('role', 'dialog');
      root.setAttribute('aria-modal', 'true');
      root.setAttribute('aria-label', 'Reservation');

      const backdrop = el('div', 'cad-rm__backdrop');
      backdrop.addEventListener('click', () => this.close());

      const panel = el('div', 'cad-rm__panel');

      const chrome = el('div', 'cad-rm__chrome');

      const header = el('div', 'cad-rm__header');
      const title = el('h2', 'cad-rm__title', 'Reservation');
      const closeBtn = el('button', 'cad-rm__close', '×');
      closeBtn.type = 'button';
      closeBtn.setAttribute('aria-label', 'Close');
      closeBtn.addEventListener('click', () => this.close());
      header.append(title, closeBtn);

      const summary = el('div', 'cad-rm__summary');
      summary.setAttribute('aria-live', 'polite');
      chrome.append(header, summary);

      const form = el('form', 'cad-rm__form');
      form.addEventListener('submit', (event) => {
        event.preventDefault();
        this.submit();
      });

      const scroll = el('div', 'cad-rm__scroll');
      const colMain = el('div', 'cad-rm__col cad-rm__col--main');
      const colSide = el('div', 'cad-rm__col cad-rm__col--side');

      const reservation = section('Reservation', 'cad-rm__section--reservation');
      const customer = section('Customer', 'cad-rm__section--customer');
      const details = section('Reservation Details', 'cad-rm__section--details');
      const resNotes = section('Booking Notes', 'cad-rm__section--res-notes');
      const studioNotes = section('Studio Notes', 'cad-rm__section--notes');
      const statusSec = section('Status', 'cad-rm__section--status');

      this._title = title;
      this._detailsSec = details.sec;
      this._resNotesSec = resNotes.sec;
      this._statusSec = statusSec.sec;
      this._detailsBody = details.body;
      this._statusBody = statusSec.body;

      const serviceSelect = el('select', 'cad-rm__input');
      serviceSelect.name = 'service_id';
      serviceSelect.required = true;

      const tableSelect = el('select', 'cad-rm__input');
      tableSelect.name = 'staff_id';

      const dateInput = el('input', 'cad-rm__input');
      dateInput.type = 'date';
      dateInput.name = 'date';

      const startSelect = el('select', 'cad-rm__input');
      startSelect.name = 'start_time';

      const endSelect = el('select', 'cad-rm__input');
      endSelect.name = 'end_time';

      const durationDisplay = el('div', 'cad-rm__duration');
      durationDisplay.setAttribute('aria-live', 'polite');

      const paintersSelect = el('select', 'cad-rm__input');
      paintersSelect.name = 'painters';

      const serviceField = field('Service', serviceSelect, 'cad-rm__field--half');
      const tableField = field('Table', tableSelect, 'cad-rm__field--half');
      const dateField = field('Date', dateInput, 'cad-rm__field--third');
      const startField = field('Start Time', startSelect, 'cad-rm__field--third');
      const endField = field('End Time', endSelect, 'cad-rm__field--third');
      const durationField = field('Duration', durationDisplay, 'cad-rm__field--half');
      const paintersField = field('🎨 # of Painters', paintersSelect, 'cad-rm__field--half');

      reservation.body.append(
        serviceField,
        tableField,
        dateField,
        startField,
        endField,
        durationField,
        paintersField
      );

      const firstInput = el('input', 'cad-rm__input');
      firstInput.name = 'customer_first';
      firstInput.autocomplete = 'given-name';
      const lastInput = el('input', 'cad-rm__input');
      lastInput.name = 'customer_last';
      lastInput.autocomplete = 'family-name';
      const phoneInput = el('input', 'cad-rm__input');
      phoneInput.type = 'tel';
      phoneInput.name = 'phone';
      const emailInput = el('input', 'cad-rm__input');
      emailInput.type = 'email';
      emailInput.name = 'email';

      customer.body.append(
        field('First Name', firstInput),
        field('Last Name', lastInput),
        field('Phone', phoneInput),
        field('Email', emailInput)
      );

      const customerMissing = el(
        'p',
        'cad-rm__customer-missing',
        'Customer information not available.'
      );
      customerMissing.hidden = true;
      customer.body.appendChild(customerMissing);

      const customerNotesInput = el('textarea', 'cad-rm__textarea');
      customerNotesInput.name = 'customer_notes';
      customerNotesInput.rows = 3;
      customerNotesInput.placeholder = 'Add any notes about the reservation...';
      resNotes.body.append(customerNotesInput);

      const notesInput = el('textarea', 'cad-rm__textarea');
      notesInput.name = 'notes';
      notesInput.rows = 3;
      notesInput.placeholder = 'Add private notes (visible to staff only)...';
      studioNotes.body.append(notesInput);

      const error = el('p', 'cad-rm__error');
      error.hidden = true;

      colMain.append(reservation.sec, customer.sec);
      colSide.append(details.sec, resNotes.sec, studioNotes.sec, statusSec.sec);
      scroll.append(colMain, colSide, error);

      const footer = el('div', 'cad-rm__footer');
      const deleteBtn = el(
        'button',
        'cad-rm__btn cad-rm__btn--danger',
        'Delete Reservation'
      );
      deleteBtn.type = 'button';
      deleteBtn.hidden = true;
      deleteBtn.addEventListener('click', () => this.requestDelete());
      const cancel = el('button', 'cad-rm__btn cad-rm__btn--ghost', 'Cancel');
      cancel.type = 'button';
      cancel.addEventListener('click', () => this.close());
      const save = el('button', 'cad-rm__btn cad-rm__btn--primary', 'Save Reservation');
      save.type = 'submit';
      footer.append(deleteBtn, cancel, save);

      form.append(scroll, footer);
      panel.append(chrome, form);
      root.append(backdrop, panel);
      document.body.appendChild(root);

      this.root = root;
      this.form = form;
      this._summary = summary;
      this._error = error;
      this._saveBtn = save;
      this._deleteBtn = deleteBtn;
      this._customerMissing = customerMissing;
      this._serviceSelect = serviceSelect;
      this._tableSelect = tableSelect;
      this._dateInput = dateInput;
      this._startSelect = startSelect;
      this._endSelect = endSelect;
      this._durationDisplay = durationDisplay;
      this._paintersSelect = paintersSelect;

      const onDateChange = () => {
        this.updateSummary();
        if (this.mode === 'new') this.refreshHighlight();
      };
      const onStartChange = () => {
        this.applyEndFromStart();
        this.updateSummary();
        if (this.mode === 'new') this.refreshHighlight();
      };
      const onEndChange = () => {
        this.syncDurationFromTimes();
        this.updateSummary();
        if (this.mode === 'new') this.refreshHighlight();
      };
      const onServiceChange = () => {
        this.applyServiceDuration();
        this.updateSummary();
        if (this.mode === 'new') this.refreshHighlight();
      };
      const onTableChange = () => {
        this.populatePainters(this._paintersSelect.value);
        this.updateSummary();
        if (this.mode === 'new') this.refreshHighlight();
      };

      dateInput.addEventListener('change', onDateChange);
      startSelect.addEventListener('change', onStartChange);
      endSelect.addEventListener('change', onEndChange);
      serviceSelect.addEventListener('change', onServiceChange);
      tableSelect.addEventListener('change', onTableChange);
      paintersSelect.addEventListener('change', () => this.updateSummary());
      firstInput.addEventListener('input', () => this.updateSummary());

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
     *   mode?: 'new'|'edit',
     *   id?: string|number,
     *   appointment?: object,
     *   tableId?: string,
     *   date?: string,
     *   startMinutes?: number,
     *   lane?: HTMLElement,
     *   matrix?: HTMLElement
     * }|string|number|object} opts
     */
    async open(opts) {
      this.init();
      CAD.Popover?.close?.();

      if (opts == null) return this;
      if (typeof opts === 'string' || typeof opts === 'number') {
        return this.openEdit({ id: opts });
      }
      if (opts.mode === 'new') {
        return this.openNew(opts);
      }
      if (opts.mode === 'edit') {
        return this.openEdit(opts);
      }
      if (opts.id != null || opts.appointment) {
        return this.openEdit(opts);
      }
      if (opts.tableId != null || opts.startMinutes != null) {
        return this.openNew(opts);
      }
      return this.openEdit({ appointment: opts, id: opts.id });
    },

    /** Clear calendar selection without closing this dialog (unlike editor.clear). */
    clearEditorSelection() {
      if (!CAD.editor) return;
      CAD.editor.selectedId = null;
      CAD.editor.container
        ?.querySelectorAll('.cad-appointment--selected')
        .forEach((el) => {
          el.classList.remove('cad-appointment--selected');
        });
    },

    applyModeChrome() {
      const isEdit = this.mode === 'edit';
      if (this._title) {
        this._title.textContent = isEdit ? 'Edit Reservation' : 'New Reservation';
      }
      if (this.root) {
        this.root.setAttribute(
          'aria-label',
          isEdit ? 'Edit reservation' : 'New reservation'
        );
      }
      if (this._detailsSec) this._detailsSec.hidden = !isEdit;
      if (this._statusSec) this._statusSec.hidden = !isEdit;
      if (this._deleteBtn) this._deleteBtn.hidden = !isEdit;
      if (this._saveBtn) {
        this._saveBtn.textContent = isEdit ? 'Save Reservation' : 'Create Reservation';
      }
      if (!isEdit && this._customerMissing) {
        this._customerMissing.hidden = true;
      }
    },

    openNew(ctx) {
      this.mode = 'new';
      this.appointmentId = null;
      this._appointment = null;
      this.detailFields = [];
      this.clearEditorSelection();
      this.clearError();
      this.clearHighlight();
      this.applyModeChrome();

      this.context = ctx || {};
      this.fillNew(this.context);

      this.root.hidden = false;
      document.body.classList.add('cad-rm-open');
      this.showHighlight(this.context);
      queueMicrotask(() => this.form?.elements?.customer_first?.focus?.());
      return this;
    },

    async openEdit(opts) {
      this.mode = 'edit';
      this.context = null;
      this.clearHighlight();
      this.clearError();
      this.applyModeChrome();

      const id =
        opts?.id != null
          ? opts.id
          : opts?.appointment?.id != null
            ? opts.appointment.id
            : opts;
      if (id == null || id === '') return this;

      this.appointmentId = String(id);
      this.root.hidden = false;
      document.body.classList.add('cad-rm-open');

      try {
        const result = await CAD.API.getReservation(id);
        if (result?.success === false) {
          throw new Error(result.data?.message || 'Could not load reservation.');
        }
        const data = result?.data || {};
        this.fill(data.appointment || {}, data.detailFields || []);
      } catch (err) {
        this.showError(err?.message || 'Could not load reservation.');
      }
      return this;
    },

    close() {
      if (!this.root) return this;
      this.root.hidden = true;
      document.body.classList.remove('cad-rm-open');
      this.clearHighlight();
      this.mode = null;
      this.context = null;
      this.appointmentId = null;
      this._appointment = null;
      this.detailFields = [];
      this._busy = false;
      if (this._saveBtn) this._saveBtn.disabled = false;
      if (this._deleteBtn) this._deleteBtn.disabled = false;
      return this;
    },

    clearError() {
      if (!this._error) return;
      this._error.hidden = true;
      this._error.textContent = '';
    },

    showError(message) {
      if (!this._error) return;
      this._error.textContent = message || 'Something went wrong.';
      this._error.hidden = false;
    },

    refreshHighlight() {
      if (this.mode !== 'new' || !this.context) return;
      const startMin = parseClockToMinutes(this._startSelect?.value);
      if (startMin == null) return;
      this.context.startMinutes = startMin;
      this.context.date = this._dateInput.value || this.context.date;
      this.context.tableId = this._tableSelect.value || this.context.tableId;
      this.showHighlight(this.context);
    },

    showHighlight(ctx) {
      this.clearHighlight();
      const lane = ctx?.lane;
      const matrix = ctx?.matrix;
      if (!lane || !matrix) return;

      const blocks = lane.querySelector('.cad-matrix__blocks') || lane;
      const metrics = CAD.calendar?.gridMetrics?.();
      if (!metrics) return;

      const startMin =
        parseClockToMinutes(this._startSelect?.value) ?? ctx.startMinutes;
      const endMin =
        parseClockToMinutes(this._endSelect?.value) ??
        startMin + durationForService(this._serviceSelect?.value);
      const duration = Math.max(15, endMin - startMin);
      const relStart = Math.max(0, startMin - metrics.startMin);
      const relEnd = Math.min(
        metrics.rangeMin,
        startMin + duration - metrics.startMin
      );
      const heightMin = Math.max(metrics.slotMinutes, relEnd - relStart);
      const pxPerMinute = metrics.gridHeight / metrics.rangeMin;

      const highlight = el('div', 'cad-matrix__slot-highlight');
      highlight.style.top = `${relStart * pxPerMinute}px`;
      highlight.style.height = `${Math.max(
        heightMin * pxPerMinute,
        metrics.slotHeight * 0.85
      )}px`;
      highlight.setAttribute('aria-hidden', 'true');
      blocks.appendChild(highlight);
      this.highlight = highlight;
    },

    clearHighlight() {
      this.highlight?.remove();
      this.highlight = null;
    },

    syncDurationFromTimes() {
      if (this._syncing) return;
      this._syncing = true;
      const start = combineDateTime(this._dateInput.value, this._startSelect.value);
      const end = combineDateTime(this._dateInput.value, this._endSelect.value);
      const mins = durationMinutes(start, end);
      this._durationDisplay.textContent =
        mins > 0 ? `${mins} minute${mins === 1 ? '' : 's'}` : '—';
      if (mins >= 15) this._lastDurationMins = mins;
      this._syncing = false;
    },

    serviceDurationMinutes() {
      const mins = Number(serviceById(this._serviceSelect.value)?.durationMinutes);
      return Number.isFinite(mins) && mins >= 15 ? mins : null;
    },

    /**
     * Set End = Start + duration. Prefers Bookly service duration when present.
     * Does not move Start when End is edited manually (see onEndChange).
     * @param {number} [mins]
     */
    applyEndFromDuration(mins) {
      if (this._syncing) return;
      const duration = Number.isFinite(mins) && mins >= 15 ? mins : null;
      if (duration == null) {
        this.syncDurationFromTimes();
        return;
      }
      this._syncing = true;
      const startMin = parseClockToMinutes(this._startSelect.value);
      if (startMin != null) {
        const endValue = minutesToClock(startMin + duration);
        fillTimeSelect(this._endSelect, endValue, [
          this._startSelect.value,
          endValue,
        ]);
        this._lastDurationMins = duration;
      }
      this._syncing = false;
      this.syncDurationFromTimes();
    },

    /** Start changed: keep service duration (or last duration) and move End. */
    applyEndFromStart() {
      const svcMins = this.serviceDurationMinutes();
      const mins =
        svcMins != null
          ? svcMins
          : this._lastDurationMins >= 15
            ? this._lastDurationMins
            : null;
      this.applyEndFromDuration(mins);
    },

    applyServiceDuration() {
      const mins = this.serviceDurationMinutes();
      if (mins == null) {
        this.syncDurationFromTimes();
        return;
      }
      this.applyEndFromDuration(mins);
    },

    populatePainters(preferred) {
      const capacity = tableCapacity(this._tableSelect.value);
      const current = Math.max(1, Number(preferred) || 1);
      this._paintersSelect.replaceChildren();
      for (let n = 1; n <= capacity; n += 1) {
        const opt = el('option', null, String(n));
        opt.value = String(n);
        this._paintersSelect.appendChild(opt);
      }
      const value = Math.min(current, capacity);
      this._paintersSelect.value = String(value);
    },

    updateSummary() {
      if (!this._summary) return;
      const serviceName =
        this._serviceSelect?.selectedOptions?.[0]?.textContent?.trim() ||
        'Reservation';
      const tableRaw =
        this._tableSelect?.selectedOptions?.[0]?.textContent?.trim() || 'Table';
      const tableBase = tableRaw.replace(/\s*\([^)]*\)\s*$/, '').trim() || tableRaw;
      const capacity = tableCapacity(this._tableSelect?.value);
      const tableLabel = `${tableBase} (1–${capacity})`;
      const dateLabel = formatWeekday(this._dateInput?.value);
      const startLabel = formatTimeLabel(this._startSelect?.value || '');
      const endLabel = formatTimeLabel(this._endSelect?.value || '');
      const painters = Number(this._paintersSelect?.value) || 1;
      const paintersLabel =
        painters === 1 ? '1 Painter' : `${painters} Painters`;
      const timeLabel =
        startLabel && endLabel ? `${startLabel}–${endLabel}` : '';

      const parts = [
        { icon: '🟣', text: serviceName, primary: true },
        { icon: '📍', text: tableLabel },
        { icon: '📅', text: dateLabel },
        { icon: '🕗', text: timeLabel },
        { icon: '🎨', text: paintersLabel },
      ].filter((part) => part.text);

      this._summary.replaceChildren();
      parts.forEach((part, index) => {
        if (index > 0) {
          this._summary.appendChild(el('span', 'cad-rm__summary-sep', '•'));
        }
        const item = el(
          'span',
          part.primary
            ? 'cad-rm__summary-part cad-rm__summary-part--primary'
            : 'cad-rm__summary-part'
        );
        const icon = el('span', 'cad-rm__summary-icon', part.icon);
        icon.setAttribute('aria-hidden', 'true');
        item.append(icon, document.createTextNode(`\u00A0${part.text}`));
        this._summary.appendChild(item);
      });
    },

    fillNew(ctx) {
      this._appointment = null;
      this.detailFields = [];
      this._detailsBody.replaceChildren();
      this._statusBody.replaceChildren();

      const tables = tableOptions();
      this._tableSelect.replaceChildren();
      tables.forEach((t) => {
        const opt = el('option', null, t.name || `Table ${t.id}`);
        opt.value = String(t.id);
        this._tableSelect.appendChild(opt);
      });
      const preferredTable = String(ctx.tableId || '');
      if (
        preferredTable &&
        [...this._tableSelect.options].some((o) => o.value === preferredTable)
      ) {
        this._tableSelect.value = preferredTable;
      } else if (tables[0]) {
        this._tableSelect.value = String(tables[0].id);
      }

      fillServiceSelect(this._serviceSelect, defaultServiceId());

      const date = String(ctx.date || CAD.State.get('selectedDate') || CAD.Config.get('today') || '');
      this._dateInput.value = date;

      const startMinutes = Number(ctx.startMinutes);
      const startValue = Number.isFinite(startMinutes)
        ? minutesToClock(startMinutes)
        : this._startSelect.value;
      const duration = durationForService(this._serviceSelect.value);
      const endValue = Number.isFinite(startMinutes)
        ? minutesToClock(startMinutes + duration)
        : startValue;
      fillTimeSelect(this._startSelect, startValue, [startValue, endValue]);
      fillTimeSelect(this._endSelect, endValue, [startValue, endValue]);
      this.syncDurationFromTimes();

      this.populatePainters(1);

      this.form.elements.customer_first.value = '';
      this.form.elements.customer_last.value = '';
      this.form.elements.phone.value = '';
      this.form.elements.email.value = '';
      this.form.elements.customer_notes.value = '';
      this.form.elements.notes.value = '';
      ['customer_first', 'customer_last', 'phone', 'email'].forEach((name) => {
        const input = this.form.elements[name];
        if (input) input.placeholder = '';
      });
      if (this._customerMissing) this._customerMissing.hidden = true;

      this.updateSummary();
    },

    fill(appointment, detailFields) {
      this._appointment = appointment;

      const tables = tableOptions();
      this._tableSelect.replaceChildren();
      tables.forEach((t) => {
        const opt = el('option', null, t.name || `Table ${t.id}`);
        opt.value = String(t.id);
        this._tableSelect.appendChild(opt);
      });
      this._tableSelect.value = String(appointment.tableId || '');

      const currentServiceId = String(appointment.serviceId || '');
      fillServiceSelect(this._serviceSelect, currentServiceId, {
        id: currentServiceId,
        name: appointment.service,
      });

      this._dateInput.value = dateInputValue(appointment.start);
      const startValue = timeInputValue(appointment.start);
      const endValue = timeInputValue(appointment.end);
      fillTimeSelect(this._startSelect, startValue, [startValue, endValue]);
      fillTimeSelect(this._endSelect, endValue, [startValue, endValue]);
      this.syncDurationFromTimes();

      this.populatePainters(appointment.painters || 1);

      this.form.elements.customer_first.value = String(
        appointment.customerFirst || ''
      );
      this.form.elements.customer_last.value = String(
        appointment.customerLast || ''
      );
      this.form.elements.phone.value = String(appointment.phone || '');
      this.form.elements.email.value = String(appointment.email || '');
      this.applyCustomerPlaceholders();
      this.form.elements.customer_notes.value = String(
        appointment.customerNotes || ''
      );
      this.form.elements.notes.value = String(appointment.notes || '');

      this.detailFields = Array.isArray(detailFields) ? detailFields : [];
      this.renderDetailFields(this.detailFields);
      this.renderStatus(appointment);
      this.updateSummary();

      queueMicrotask(() => this.form.elements.customer_first?.focus?.());
    },

    /**
     * Dynamic service fields — no birthday/studio branching in the UI.
     * @param {Array<{id:string,label:string,type:string,value:string,required?:boolean}>} fields
     */
    renderDetailFields(fields) {
      this._detailsBody.replaceChildren();
      if (!fields.length) {
        this._detailsBody.append(
          el('p', 'cad-rm__empty', 'No service-specific fields for this reservation.')
        );
        return;
      }

      fields.forEach((def) => {
        const type = String(def.type || 'text').toLowerCase();
        if (
          type === 'captcha' ||
          type === 'captcha_v2' ||
          type === 'recaptcha' ||
          type === 'text-content'
        ) {
          return;
        }
        let input;
        const isTextarea = type === 'textarea';
        if (isTextarea) {
          input = el('textarea', 'cad-rm__textarea');
          input.rows = 3;
        } else {
          input = el('input', 'cad-rm__input');
          input.type =
            type === 'number' || type === 'email' || type === 'tel' ? type : 'text';
        }
        input.name = `detail_${def.id}`;
        input.dataset.fieldId = String(def.id);
        input.value = def.value != null ? String(def.value) : '';
        if (def.required) input.required = true;
        this._detailsBody.append(
          field(
            String(def.label || def.id),
            input,
            isTextarea ? 'cad-rm__field--span' : ''
          )
        );
      });
    },

    renderStatus(appointment) {
      this._statusBody.replaceChildren();
      if (CAD.StatusPanel?.render) {
        this._statusBody.appendChild(
          CAD.StatusPanel.render(
            appointment,
            async (slug) => {
              try {
                await CAD.API.updateAppointmentStatus(appointment.id, slug);
                const list = Array.isArray(CAD.State.get('appointments'))
                  ? CAD.State.get('appointments').slice()
                  : [];
                const idx = list.findIndex(
                  (a) => String(a.id) === String(appointment.id)
                );
                if (idx >= 0) {
                  list[idx] = { ...list[idx], status: slug };
                  CAD.State.set('appointments', list);
                  reRenderCalendar();
                }
                appointment.status = slug;
                this.renderStatus(appointment);
                CAD.notify?.show('Status updated.', 'success');
              } catch (err) {
                CAD.notify?.show(err?.message || 'Could not update status.', 'error');
              }
            },
            { variant: 'chips' }
          )
        );
      }
    },

    collectDetailFields() {
      const out = {};
      this._detailsBody.querySelectorAll('[data-field-id]').forEach((input) => {
        out[String(input.dataset.fieldId)] = String(input.value ?? '');
      });
      return out;
    },

    applyCustomerPlaceholders() {
      if (this.mode !== 'edit') {
        if (this._customerMissing) this._customerMissing.hidden = true;
        return;
      }
      const first = String(this.form.elements.customer_first.value || '').trim();
      const last = String(this.form.elements.customer_last.value || '').trim();
      const phone = String(this.form.elements.phone.value || '').trim();
      const email = String(this.form.elements.email.value || '').trim();
      const missing = !first && !last && !phone && !email;
      const placeholder = missing ? '—' : '';
      ['customer_first', 'customer_last', 'phone', 'email'].forEach((name) => {
        const input = this.form.elements[name];
        if (input) input.placeholder = placeholder;
      });
      if (this._customerMissing) {
        this._customerMissing.hidden = !missing;
      }
    },

    async requestDelete() {
      if (this._busy || this.mode !== 'edit' || !this.appointmentId) return;
      const ok =
        typeof CAD.confirm === 'function'
          ? await CAD.confirm({
              title: 'Delete reservation',
              message: 'Delete this reservation?',
              confirmLabel: 'Delete',
              cancelLabel: 'Cancel',
              danger: true,
            })
          : false;
      if (!ok) return;

      this._busy = true;
      this.clearError();
      if (this._saveBtn) this._saveBtn.disabled = true;
      if (this._deleteBtn) this._deleteBtn.disabled = true;

      try {
        const result = await CAD.API.deleteReservation(this.appointmentId);
        if (result?.success === false) {
          throw Object.assign(new Error(result.data?.message || 'Could not delete.'), {
            payload: result.data,
          });
        }

        const list = Array.isArray(CAD.State.get('appointments'))
          ? CAD.State.get('appointments').slice()
          : [];
        CAD.State.set(
          'appointments',
          list.filter((a) => String(a.id) !== String(this.appointmentId))
        );
        reRenderCalendar();
        CAD.notify?.show('Reservation deleted.', 'success');
        this.close();
      } catch (err) {
        const message =
          err?.payload?.message || err?.message || 'Could not delete reservation.';
        this.showError(String(message));
        CAD.notify?.show(String(message), 'error');
        if (this._saveBtn) this._saveBtn.disabled = false;
        if (this._deleteBtn) this._deleteBtn.disabled = false;
        this._busy = false;
      }
    },

    async submit() {
      if (this.mode === 'new') return this.submitCreate();
      return this.submitSave();
    },

    async submitCreate() {
      if (this._busy || this.mode !== 'new') return;

      const first = String(this.form.elements.customer_first.value || '').trim();
      const last = String(this.form.elements.customer_last.value || '').trim();
      const customerName = [first, last].filter(Boolean).join(' ').trim();
      if (!customerName) {
        this.showError('First or last name is required.');
        this.form.elements.customer_first?.focus?.();
        return;
      }

      const serviceId = Number(this._serviceSelect.value) || 0;
      if (serviceId <= 0) {
        this.showError('Select a Bookly service.');
        this._serviceSelect.focus();
        return;
      }

      const staffId = String(this._tableSelect.value || this.context?.tableId || '');
      if (!staffId) {
        this.showError('Select a table.');
        this._tableSelect.focus();
        return;
      }

      const date = this._dateInput.value;
      const startClock = this._startSelect.value;
      const endClock = this._endSelect.value;
      const startMin = parseClockToMinutes(startClock);
      const endMin = parseClockToMinutes(endClock);
      if (!/^\d{4}-\d{2}-\d{2}$/.test(date) || startMin == null || endMin == null) {
        this.showError('Date, start, and end are required.');
        return;
      }
      if (endMin <= startMin) {
        this.showError('End time must be after start time.');
        return;
      }

      const durationMins = endMin - startMin;
      const painters = Math.max(1, Number(this._paintersSelect.value) || 1);
      const start = toMysqlLocal(date, startMin);
      const end = toMysqlLocal(date, endMin);

      this._busy = true;
      this.clearError();
      if (this._saveBtn) this._saveBtn.disabled = true;

      try {
        if (CAD.schedule?.confirmIfOutside) {
          const ok = await CAD.schedule.confirmIfOutside(staffId, start, end);
          if (!ok) {
            if (this._saveBtn) this._saveBtn.disabled = false;
            this._busy = false;
            return;
          }
        }

        const result = await CAD.API.createAppointment({
          staffId,
          start,
          end,
          durationMinutes: durationMins,
          customerName,
          phone: String(this.form.elements.phone.value || '').trim(),
          email: String(this.form.elements.email.value || '').trim(),
          painters,
          notes: String(this.form.elements.notes.value || '').trim(),
          serviceId,
        });

        if (result?.success === false) {
          throw Object.assign(
            new Error(result.data?.message || 'Could not create reservation.'),
            { payload: result.data }
          );
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
        reRenderCalendar();
        if (!CAD.ui?.calendarEl && CAD.ui?.load) {
          await CAD.ui.load(date);
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

    async submitSave() {
      if (this._busy || this.mode !== 'edit' || !this.appointmentId) return;

      const date = this._dateInput.value;
      const start = combineDateTime(date, this._startSelect.value);
      const end = combineDateTime(date, this._endSelect.value);
      if (!start || !end) {
        this.showError('Date, start, and end are required.');
        return;
      }
      if (durationMinutes(start, end) <= 0) {
        this.showError('End time must be after start time.');
        return;
      }

      const serviceId = Number(this._serviceSelect.value) || 0;
      if (serviceId <= 0) {
        this.showError('Select a Bookly service.');
        this._serviceSelect.focus();
        return;
      }

      this._busy = true;
      this.clearError();
      if (this._saveBtn) this._saveBtn.disabled = true;

      try {
        if (CAD.schedule?.confirmIfOutside) {
          const ok = await CAD.schedule.confirmIfOutside(
            this._tableSelect.value,
            start,
            end
          );
          if (!ok) {
            if (this._saveBtn) this._saveBtn.disabled = false;
            this._busy = false;
            return;
          }
        }

        const result = await CAD.API.saveReservation({
          appointmentId: this.appointmentId,
          staffId: this._tableSelect.value,
          serviceId,
          start,
          end,
          customerFirst: this.form.elements.customer_first.value.trim(),
          customerLast: this.form.elements.customer_last.value.trim(),
          phone: this.form.elements.phone.value.trim(),
          email: this.form.elements.email.value.trim(),
          painters: Number(this._paintersSelect.value) || 1,
          notes: this.form.elements.notes.value,
          customerNotes: this.form.elements.customer_notes.value,
          detailFields: this.collectDetailFields(),
        });

        if (result?.success === false) {
          throw Object.assign(new Error(result.data?.message || 'Could not save.'), {
            payload: result.data,
          });
        }

        const updated = result?.data?.appointment;
        if (updated) {
          patchStateAppointment(updated);
          reRenderCalendar();
          this.fill(updated, result.data.detailFields || this.detailFields);
        }

        CAD.notify?.show('Reservation saved.', 'success');
        this.close();
      } catch (err) {
        const message =
          err?.payload?.message || err?.message || 'Could not save reservation.';
        this.showError(String(message));
        CAD.notify?.show(String(message), 'error');
        if (this._saveBtn) this._saveBtn.disabled = false;
        this._busy = false;
      }
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
