/**
 * Reservation Manager — Sprint 3.2 / 3.2.3 UX.
 * Primary view/edit UI for existing reservations (Bookly save path).
 * Service-specific fields come from the API detailFields list (not hard-coded).
 * @module components/reservation-manager
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) {
    throw new Error('cad-core.js must be loaded before components/reservation-manager.js');
  }

  const SLOT_MINUTES = 15;
  const DEFAULT_CAPACITY = 8;

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

  CAD.ReservationManager = {
    root: null,
    form: null,
    appointmentId: null,
    detailFields: [],
    _appointment: null,
    _busy: false,
    _syncing: false,
    _bound: false,

    init() {
      if (this.root) return this;

      const root = el('div', 'cad-rm');
      root.hidden = true;
      root.setAttribute('role', 'dialog');
      root.setAttribute('aria-modal', 'true');
      root.setAttribute('aria-label', 'Reservation manager');

      const backdrop = el('div', 'cad-rm__backdrop');
      backdrop.addEventListener('click', () => this.close());

      const panel = el('div', 'cad-rm__panel');

      const header = el('div', 'cad-rm__header');
      const title = el('h2', 'cad-rm__title', 'Reservation Manager');
      const closeBtn = el('button', 'cad-rm__close', '×');
      closeBtn.type = 'button';
      closeBtn.setAttribute('aria-label', 'Close');
      closeBtn.addEventListener('click', () => this.close());
      header.append(title, closeBtn);

      const summary = el('div', 'cad-rm__summary');
      summary.setAttribute('aria-live', 'polite');

      const form = el('form', 'cad-rm__form');
      form.addEventListener('submit', (event) => {
        event.preventDefault();
        this.submit();
      });

      const scroll = el('div', 'cad-rm__scroll');

      const reservation = section('Reservation', 'cad-rm__section--reservation');
      const customer = section('Customer', 'cad-rm__section--customer');
      const details = section('Reservation Details', 'cad-rm__section--details');
      const resNotes = section('Reservation Notes', 'cad-rm__section--res-notes');
      const studioNotes = section('Studio Notes', 'cad-rm__section--notes');
      const statusSec = section('Status', 'cad-rm__section--status');

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

      reservation.body.append(
        field('Service', serviceSelect),
        field('Table', tableSelect),
        field('Date', dateInput, 'cad-rm__field--span'),
        field('Start Time', startSelect),
        field('End Time', endSelect),
        field('Duration', durationDisplay),
        field('🎨 # of Painters', paintersSelect)
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

      const customerNotesInput = el('textarea', 'cad-rm__textarea');
      customerNotesInput.name = 'customer_notes';
      customerNotesInput.rows = 2;
      resNotes.body.append(customerNotesInput);

      const notesInput = el('textarea', 'cad-rm__textarea');
      notesInput.name = 'notes';
      notesInput.rows = 2;
      studioNotes.body.append(notesInput);

      const error = el('p', 'cad-rm__error');
      error.hidden = true;

      scroll.append(
        reservation.sec,
        customer.sec,
        details.sec,
        resNotes.sec,
        studioNotes.sec,
        statusSec.sec,
        error
      );

      const footer = el('div', 'cad-rm__footer');
      const cancel = el('button', 'cad-rm__btn cad-rm__btn--ghost', 'Cancel');
      cancel.type = 'button';
      cancel.addEventListener('click', () => this.close());
      const save = el('button', 'cad-rm__btn cad-rm__btn--primary', 'Save Reservation');
      save.type = 'submit';
      footer.append(cancel, save);

      form.append(scroll, footer);
      panel.append(header, summary, form);
      root.append(backdrop, panel);
      document.body.appendChild(root);

      this.root = root;
      this.form = form;
      this._summary = summary;
      this._error = error;
      this._saveBtn = save;
      this._serviceSelect = serviceSelect;
      this._tableSelect = tableSelect;
      this._dateInput = dateInput;
      this._startSelect = startSelect;
      this._endSelect = endSelect;
      this._durationDisplay = durationDisplay;
      this._paintersSelect = paintersSelect;

      const onScheduleChange = () => {
        this.syncDurationFromTimes();
        this.updateSummary();
      };
      const onServiceChange = () => {
        this.applyServiceDuration();
        this.updateSummary();
      };
      const onTableChange = () => {
        this.populatePainters(this._paintersSelect.value);
        this.updateSummary();
      };

      dateInput.addEventListener('change', onScheduleChange);
      startSelect.addEventListener('change', onScheduleChange);
      endSelect.addEventListener('change', onScheduleChange);
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

    async open(appointmentOrId) {
      this.init();
      CAD.QuickAdd?.close?.();
      CAD.Popover?.close?.();

      const id =
        typeof appointmentOrId === 'object' && appointmentOrId
          ? appointmentOrId.id
          : appointmentOrId;
      if (id == null || id === '') return this;

      this.clearError();
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
      this.appointmentId = null;
      this._appointment = null;
      this.detailFields = [];
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
      this._error.textContent = message || 'Something went wrong.';
      this._error.hidden = false;
    },

    syncDurationFromTimes() {
      if (this._syncing) return;
      this._syncing = true;
      const start = combineDateTime(this._dateInput.value, this._startSelect.value);
      const end = combineDateTime(this._dateInput.value, this._endSelect.value);
      const mins = durationMinutes(start, end);
      this._durationDisplay.textContent =
        mins > 0 ? `${mins} minute${mins === 1 ? '' : 's'}` : '—';
      this._syncing = false;
    },

    applyServiceDuration() {
      if (this._syncing) return;
      const svc = serviceById(this._serviceSelect.value);
      const mins = Number(svc?.durationMinutes);
      if (!Number.isFinite(mins) || mins < 15) {
        this.syncDurationFromTimes();
        return;
      }
      this._syncing = true;
      const start = combineDateTime(this._dateInput.value, this._startSelect.value);
      if (start) {
        const d = parseLocal(start);
        d.setMinutes(d.getMinutes() + mins);
        const endValue = `${String(d.getHours()).padStart(2, '0')}:${String(
          d.getMinutes()
        ).padStart(2, '0')}`;
        fillTimeSelect(this._endSelect, endValue, [
          this._startSelect.value,
          endValue,
        ]);
      }
      this._syncing = false;
      this.syncDurationFromTimes();
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
      const tableName =
        this._tableSelect?.selectedOptions?.[0]?.textContent?.trim() || 'Table';
      const dateLabel = formatWeekday(this._dateInput?.value);
      const startLabel = formatTimeLabel(this._startSelect?.value || '');
      const endLabel = formatTimeLabel(this._endSelect?.value || '');
      const painters = Number(this._paintersSelect?.value) || 1;
      const paintersLabel =
        painters === 1 ? '🎨 1 Painter' : `🎨 ${painters} Painters`;

      const parts = [
        serviceName,
        tableName,
        dateLabel,
        startLabel && endLabel ? `${startLabel}–${endLabel}` : '',
        paintersLabel,
      ].filter(Boolean);

      this._summary.replaceChildren();
      parts.forEach((part, index) => {
        if (index > 0) {
          this._summary.appendChild(el('span', 'cad-rm__summary-sep', '•'));
        }
        this._summary.appendChild(el('span', 'cad-rm__summary-part', part));
      });
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

      const services = servicesList();
      this._serviceSelect.replaceChildren();
      services.forEach((svc) => {
        const opt = el('option', null, svc.name || `Service ${svc.id}`);
        opt.value = String(svc.id);
        this._serviceSelect.appendChild(opt);
      });
      const currentServiceId = String(appointment.serviceId || '');
      if (currentServiceId && !serviceById(currentServiceId)) {
        const opt = el(
          'option',
          null,
          appointment.service || `Service ${currentServiceId}`
        );
        opt.value = currentServiceId;
        this._serviceSelect.appendChild(opt);
      }
      this._serviceSelect.value =
        currentServiceId || (services[0] ? String(services[0].id) : '');

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
        if (type === 'textarea') {
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
        this._detailsBody.append(field(String(def.label || def.id), input));
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

    async submit() {
      if (this._busy || !this.appointmentId) return;

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
