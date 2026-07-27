/**
 * Appointment popover shell (Calendar First).
 * Positioning, open/close, focus, and shared chrome only — never booking-type logic.
 * Looks up CAD.Renderers.get(appointment.type); unknown types → studio renderer.
 *
 * @module components/popover
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before components/popover.js');

  function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text != null && text !== '') node.textContent = text;
    return node;
  }

  function booklyEditUrl(appointment) {
    const base = String(
      CAD.Config.get('booklyEditUrl') || '/wp-admin/admin.php?page=bookly-calendar'
    );
    try {
      const url = new URL(base, window.location.origin);
      if (appointment?.id != null) {
        url.searchParams.set('appointment_id', String(appointment.id));
      }
      return url.toString();
    } catch (e) {
      return base;
    }
  }

  CAD.Popover = {
    root: null,
    panel: null,
    body: null,
    footer: null,
    appointment: null,
    _bound: false,
    _busy: false,

    init() {
      if (this.root) return this;

      const root = el('div', 'cad-popover');
      root.hidden = true;
      root.setAttribute('role', 'dialog');
      root.setAttribute('aria-modal', 'true');
      root.setAttribute('aria-label', 'Appointment details');

      const backdrop = el('div', 'cad-popover__backdrop');
      backdrop.addEventListener('click', () => this.close());

      const panel = el('div', 'cad-popover__panel');
      const header = el('div', 'cad-popover__header');
      const title = el('h2', 'cad-popover__title', 'Appointment');
      const closeBtn = el('button', 'cad-popover__close', '×');
      closeBtn.type = 'button';
      closeBtn.setAttribute('aria-label', 'Close');
      closeBtn.addEventListener('click', () => this.close());
      header.append(title, closeBtn);

      const body = el('div', 'cad-popover__body');
      const footer = el('div', 'cad-popover__footer');

      panel.append(header, body, footer);
      root.append(backdrop, panel);
      document.body.appendChild(root);

      this.root = root;
      this.panel = panel;
      this.body = body;
      this.footer = footer;
      this._title = title;

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
     * Open shell and fill type-agnostic chrome.
     * Looks up renderer from registry by appointment.type (no if/switch).
     * @param {Record<string, unknown>} appointment Normalized CAD appointment
     */
    render(appointment) {
      this.init();
      if (!appointment) return this;

      this.appointment = appointment;

      const renderer = CAD.Renderers?.get
        ? CAD.Renderers.get(appointment.type)
        : null;
      const view = renderer?.render
        ? renderer.render(appointment)
        : { title: 'Appointment', body: document.createDocumentFragment() };

      this._title.textContent = view.title || 'Appointment';
      this.body.replaceChildren();
      if (view.body) this.body.append(view.body);

      this.renderChrome(appointment);
      this.root.hidden = false;
      this.root.classList.add('cad-popover--open');
      this.closeBtnFocus();
      return this;
    },

    /** Alias for render — used by cad-editor selection. */
    open(appointment) {
      return this.render(appointment);
    },

    closeBtnFocus() {
      this.root?.querySelector('.cad-popover__close')?.focus?.();
    },

    close() {
      if (!this.root) return this;
      this.root.classList.remove('cad-popover--open');
      this.root.hidden = true;
      this.appointment = null;
      this.body?.replaceChildren();
      this.footer?.replaceChildren();
      return this;
    },

    /** Shared chrome for every type: notes, status actions, Open in Bookly. */
    renderChrome(appointment) {
      this.footer.replaceChildren();

      const notes = String(appointment.notes ?? '').trim();
      if (notes) {
        this.footer.appendChild(el('div', 'cad-popover__field-label', '📝 Notes'));
        this.footer.appendChild(el('div', 'cad-popover__notes', notes));
        this.footer.appendChild(el('hr', 'cad-popover__rule'));
      }

      if (CAD.StatusPanel?.render) {
        this.footer.appendChild(
          CAD.StatusPanel.render(appointment, (slug) => this.setStatus(slug))
        );
      }
      this.footer.appendChild(el('hr', 'cad-popover__rule'));

      const bookly = el('a', 'cad-popover__bookly', '✏ Open in Bookly');
      bookly.href = booklyEditUrl(appointment);
      bookly.target = '_blank';
      bookly.rel = 'noopener noreferrer';
      this.footer.appendChild(bookly);
    },

    async setStatus(slug) {
      if (this._busy || !this.appointment?.id) return;
      const status = CAD.StatusPanel?.normalizeStatus
        ? CAD.StatusPanel.normalizeStatus(slug)
        : String(slug || '')
            .trim()
            .toLowerCase();
      if (!status) return;

      this._busy = true;
      try {
        if (CAD.API?.updateAppointmentStatus) {
          const res = await CAD.API.updateAppointmentStatus(
            this.appointment.id,
            status
          );
          if (res?.success === false) {
            throw new Error(res?.data?.message || 'Status update failed');
          }
        }

        this.appointment = { ...this.appointment, status };
        const list = Array.isArray(CAD.State.get('appointments'))
          ? CAD.State.get('appointments').slice()
          : [];
        const idx = list.findIndex(
          (a) => String(a.id) === String(this.appointment.id)
        );
        if (idx >= 0) {
          list[idx] = { ...list[idx], status };
          CAD.State.set('appointments', list);
          CAD.calendar?.render?.(CAD.ui?.calendarEl);
        }
        this.render(this.appointment);
      } catch (err) {
        CAD.Logger.error(err);
        window.alert(err?.message || 'Could not update status.');
      } finally {
        this._busy = false;
      }
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
