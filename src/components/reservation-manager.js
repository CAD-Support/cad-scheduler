/**
 * Reservation Manager — Sprint 3.2.
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

  function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text != null && text !== '') node.textContent = text;
    return node;
  }

  function field(labelText, input, hint) {
    const wrap = el('label', 'cad-rm__field');
    wrap.append(el('span', 'cad-rm__label', labelText), input);
    if (hint) wrap.append(el('span', 'cad-rm__hint', hint));
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

      const form = el('form', 'cad-rm__form');
      form.addEventListener('submit', (event) => {
        event.preventDefault();
        this.submit();
      });

      const reservation = section('Reservation', 'cad-rm__section--reservation');
      const customer = section('Customer', 'cad-rm__section--customer');
      const details = section('Reservation Details', 'cad-rm__section--details');
      const notes = section('Studio Notes', 'cad-rm__section--notes');
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

      const startInput = el('input', 'cad-rm__input');
      startInput.type = 'time';
      startInput.name = 'start_time';
      startInput.step = '900';

      const endInput = el('input', 'cad-rm__input');
      endInput.type = 'time';
      endInput.name = 'end_time';
      endInput.step = '900';

      const durationInput = el('input', 'cad-rm__input');
      durationInput.type = 'number';
      durationInput.name = 'duration';
      durationInput.min = '15';
      durationInput.step = '15';

      const paintersInput = el('input', 'cad-rm__input');
      paintersInput.type = 'number';
      paintersInput.name = 'painters';
      paintersInput.min = '1';

      reservation.body.append(
        field('Service', serviceSelect),
        field('Table', tableSelect),
        field('Date', dateInput),
        field('Start time', startInput),
        field('End time', endInput),
        field('Duration (minutes)', durationInput, 'Updates when start/end change'),
        field('# of painters', paintersInput)
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
        field('First name', firstInput),
        field('Last name', lastInput),
        field('Phone', phoneInput),
        field('Email', emailInput)
      );

      const notesInput = el('textarea', 'cad-rm__textarea');
      notesInput.name = 'notes';
      notesInput.rows = 3;
      notes.body.append(field('Internal notes', notesInput));

      const error = el('p', 'cad-rm__error');
      error.hidden = true;

      const actions = el('div', 'cad-rm__actions');
      const cancel = el('button', 'cad-rm__btn cad-rm__btn--ghost', 'Cancel');
      cancel.type = 'button';
      cancel.addEventListener('click', () => this.close());
      const save = el('button', 'cad-rm__btn cad-rm__btn--primary', 'Save');
      save.type = 'submit';
      actions.append(cancel, save);

      form.append(
        reservation.sec,
        customer.sec,
        details.sec,
        notes.sec,
        statusSec.sec,
        error,
        actions
      );
      panel.append(header, form);
      root.append(backdrop, panel);
      document.body.appendChild(root);

      this.root = root;
      this.form = form;
      this._error = error;
      this._saveBtn = save;
      this._serviceSelect = serviceSelect;
      this._tableSelect = tableSelect;
      this._dateInput = dateInput;
      this._startInput = startInput;
      this._endInput = endInput;
      this._durationInput = durationInput;

      const syncFromStartEnd = () => {
        if (this._syncing) return;
        this._syncing = true;
        const start = combineDateTime(dateInput.value, startInput.value);
        const end = combineDateTime(dateInput.value, endInput.value);
        durationInput.value = String(durationMinutes(start, end) || '');
        this._syncing = false;
      };
      const syncFromDuration = () => {
        if (this._syncing) return;
        this._syncing = true;
        const start = combineDateTime(dateInput.value, startInput.value);
        const mins = Math.max(15, Number(durationInput.value) || 0);
        if (start && mins) {
          const d = parseLocal(start);
          d.setMinutes(d.getMinutes() + mins);
          endInput.value = `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
        }
        this._syncing = false;
      };
      const syncFromService = () => {
        const svc = serviceById(serviceSelect.value);
        const mins = Number(svc?.durationMinutes);
        if (!Number.isFinite(mins) || mins < 15) return;
        durationInput.value = String(mins);
        syncFromDuration();
      };

      dateInput.addEventListener('change', syncFromStartEnd);
      startInput.addEventListener('change', syncFromStartEnd);
      endInput.addEventListener('change', syncFromStartEnd);
      durationInput.addEventListener('change', syncFromDuration);
      serviceSelect.addEventListener('change', syncFromService);

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

    fill(appointment, detailFields) {
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
        const opt = el('option', null, appointment.service || `Service ${currentServiceId}`);
        opt.value = currentServiceId;
        this._serviceSelect.appendChild(opt);
      }
      this._serviceSelect.value = currentServiceId || (services[0] ? String(services[0].id) : '');

      this._dateInput.value = dateInputValue(appointment.start);
      this._startInput.value = timeInputValue(appointment.start);
      this._endInput.value = timeInputValue(appointment.end);
      this._durationInput.value = String(
        durationMinutes(appointment.start, appointment.end) || ''
      );

      this.form.elements.painters.value = String(appointment.painters || 1);
      this.form.elements.customer_first.value = String(
        appointment.customerFirst || ''
      );
      this.form.elements.customer_last.value = String(
        appointment.customerLast || ''
      );
      this.form.elements.phone.value = String(appointment.phone || '');
      this.form.elements.email.value = String(appointment.email || '');
      this.form.elements.notes.value = String(appointment.notes || '');

      this.detailFields = Array.isArray(detailFields) ? detailFields : [];
      this.renderDetailFields(this.detailFields);
      this.renderStatus(appointment);

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
        const type = String(def.type || 'text');
        let input;
        if (type === 'textarea') {
          input = el('textarea', 'cad-rm__textarea');
          input.rows = 3;
        } else {
          input = el('input', 'cad-rm__input');
          input.type = type === 'number' || type === 'email' || type === 'tel' ? type : 'text';
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
      if (CAD.Badges?.statusBadge) {
        this._statusBody.appendChild(CAD.Badges.statusBadge(appointment));
      }
      if (CAD.StatusPanel?.render) {
        this._statusBody.appendChild(
          CAD.StatusPanel.render(appointment, async (slug) => {
            try {
              await CAD.API.updateAppointmentStatus(appointment.id, slug);
              const list = Array.isArray(CAD.State.get('appointments'))
                ? CAD.State.get('appointments').slice()
                : [];
              const idx = list.findIndex((a) => String(a.id) === String(appointment.id));
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
          })
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
      const start = combineDateTime(date, this._startInput.value);
      const end = combineDateTime(date, this._endInput.value);
      if (!start || !end) {
        this.showError('Date, start, and end are required.');
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
          painters: Number(this.form.elements.painters.value) || 1,
          notes: this.form.elements.notes.value,
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
        const message = err?.payload?.message || err?.message || 'Could not save reservation.';
        this.showError(String(message));
        CAD.notify?.show(String(message), 'error');
        if (this._saveBtn) this._saveBtn.disabled = false;
        this._busy = false;
      }
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
