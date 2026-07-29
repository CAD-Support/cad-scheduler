/**
 * Appointment popover shell — Sprint 2.6 final (presentation only).
 * Colored type ribbon + shared sections. No booking-type body logic.
 * @module components/popover
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before components/popover.js');

  const TYPE_RIBBON = Object.freeze({
    studio: { label: 'Studio Reservation', icon: '🎨' },
    birthday: { label: 'Birthday Party', icon: '🎂' },
    event: { label: 'Event', icon: '🎉' },
  });

  function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text != null && text !== '') node.textContent = text;
    return node;
  }

  function appointmentType(appointment) {
    const type = String(appointment?.type || 'studio').toLowerCase();
    if (type === 'birthday' || type === 'event') return type;
    return 'studio';
  }

  function appendFooterSection(footer, title, icon, fill) {
    const section = el('section', 'cad-popover__section cad-popover__section--footer');
    const heading = el(
      'h3',
      'cad-popover__section-title',
      icon ? `${icon} ${title}` : title
    );
    const body = el('div', 'cad-popover__section-body');
    section.append(heading, body);
    const ok = fill(body);
    if (ok === false || !body.childNodes.length) return;
    footer.appendChild(section);
  }

  CAD.Popover = {
    root: null,
    panel: null,
    ribbon: null,
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
      const ribbon = el('div', 'cad-popover__ribbon');
      const ribbonLabel = el('span', 'cad-popover__ribbon-label', 'Appointment');
      const closeBtn = el('button', 'cad-popover__close', '×');
      closeBtn.type = 'button';
      closeBtn.setAttribute('aria-label', 'Close');
      closeBtn.addEventListener('click', () => this.close());
      ribbon.append(ribbonLabel, closeBtn);

      const body = el('div', 'cad-popover__body');
      const footer = el('div', 'cad-popover__footer');

      panel.append(ribbon, body, footer);
      root.append(backdrop, panel);
      document.body.appendChild(root);

      this.root = root;
      this.panel = panel;
      this.ribbon = ribbon;
      this._ribbonLabel = ribbonLabel;
      this.body = body;
      this.footer = footer;

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

    render(appointment) {
      this.init();
      if (!appointment) return this;

      this.appointment = appointment;
      const type = appointmentType(appointment);
      const ribbonMeta = TYPE_RIBBON[type] || TYPE_RIBBON.studio;

      const renderer = CAD.Renderers?.get ? CAD.Renderers.get(type) : null;
      const view = renderer?.render
        ? renderer.render(appointment)
        : { title: ribbonMeta.label, body: document.createDocumentFragment() };

      this.root.dataset.type = type;
      this.ribbon.dataset.type = type;
      this._ribbonLabel.textContent = `${ribbonMeta.icon} ${view.title || ribbonMeta.label}`;

      this.body.replaceChildren();
      if (view.body) this.body.append(view.body);

      this.renderChrome(appointment);
      this.root.hidden = false;
      this.root.classList.add('cad-popover--open');
      this.closeBtnFocus();
      return this;
    },

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

    renderChrome(appointment) {
      this.footer.replaceChildren();

      appendFooterSection(this.footer, 'Status', '🏷', (body) => {
        if (CAD.Badges?.statusBadge) {
          body.appendChild(CAD.Badges.statusBadge(appointment));
        } else if (CAD.RendererHelpers?.appendStatusBadge) {
          CAD.RendererHelpers.appendStatusBadge(body, appointment);
        }
        if (CAD.StatusPanel?.render) {
          body.appendChild(
            CAD.StatusPanel.render(appointment, (slug) => this.setStatus(slug))
          );
        }
        return true;
      });

      const notes = String(appointment.notes ?? '').trim();
      if (notes) {
        appendFooterSection(this.footer, 'Internal Notes', '📝', (body) => {
          body.appendChild(el('div', 'cad-popover__notes', notes));
          return true;
        });
      }

      appendFooterSection(this.footer, 'Actions', null, (body) => {
        const actions = el('div', 'cad-popover__actions');
        if (CAD.ReservationManager?.open) {
          const edit = el(
            'button',
            'cad-popover__btn cad-popover__btn--primary',
            'Edit Reservation'
          );
          edit.type = 'button';
          edit.addEventListener('click', () => {
            this.close();
            CAD.ReservationManager.open(appointment);
          });
          actions.appendChild(edit);
        }
        body.appendChild(actions);
        return actions.childNodes.length > 0;
      });
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
