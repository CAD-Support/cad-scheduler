/**
 * CAD Scheduler — Editor
 * Selection and future inline editing.
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;

  if (!CAD) {
    throw new Error('cad-core.js must be loaded before cad-editor.js');
  }

  CAD.editor = {
    selectedId: null,
    boundContainer: null,

    /**
     * @returns {HTMLElement[]}
     */
    getAppointmentElements() {
      if (!this.boundContainer) {
        return [];
      }

      return Array.from(this.boundContainer.querySelectorAll('.cad-appointment'));
    },

    select(appointmentId) {
      this.selectedId = appointmentId;
      CAD.State.set('selectedAppointmentId', appointmentId);

      this.getAppointmentElements().forEach((el) => {
        const isSelected = el.dataset.id === String(appointmentId);
        el.classList.toggle('cad-appointment--selected', isSelected);
        el.setAttribute('aria-selected', isSelected ? 'true' : 'false');
      });

      const el = this.boundContainer?.querySelector(
        `.cad-appointment[data-id="${appointmentId}"]`
      );
      el?.focus();

      CAD.Events.emit('appointment:select', {
        id: appointmentId,
      });
    },

    clearSelection() {
      this.selectedId = null;
      CAD.State.set('selectedAppointmentId', null);

      this.getAppointmentElements().forEach((el) => {
        el.classList.remove('cad-appointment--selected');
        el.setAttribute('aria-selected', 'false');
      });

      CAD.Events.emit('appointment:select', { id: null });
    },

    restoreSelection() {
      const selectedId = CAD.State.get('selectedAppointmentId');

      if (!selectedId) {
        return;
      }

      const el = this.boundContainer?.querySelector(
        `.cad-appointment[data-id="${selectedId}"]`
      );

      if (el) {
        el.classList.add('cad-appointment--selected');
        el.setAttribute('aria-selected', 'true');
        this.selectedId = String(selectedId);
      }
    },

    /**
     * @param {KeyboardEvent} event
     */
    focusRelativeAppointment(event) {
      const items = this.getAppointmentElements();

      if (items.length === 0) {
        return;
      }

      const currentIndex = items.findIndex((el) => el === document.activeElement);
      let nextIndex = currentIndex;

      if (event.key === 'ArrowDown' || event.key === 'ArrowRight') {
        nextIndex = currentIndex < 0 ? 0 : Math.min(currentIndex + 1, items.length - 1);
      } else if (event.key === 'ArrowUp' || event.key === 'ArrowLeft') {
        nextIndex = currentIndex < 0 ? items.length - 1 : Math.max(currentIndex - 1, 0);
      } else {
        return;
      }

      event.preventDefault();
      const next = items[nextIndex];
      next.focus();
      this.select(next.dataset.id);
    },

    bind(container) {
      if (!container || this.boundContainer === container) {
        return;
      }

      if (this.boundContainer) {
        this.boundContainer.removeEventListener('click', this._onClick);
        this.boundContainer.removeEventListener('keydown', this._onKeydown);
      }

      this.boundContainer = container;
      this._onClick = (event) => {
        const block = event.target.closest('.cad-appointment');

        if (block?.dataset.id) {
          event.preventDefault();
          this.select(block.dataset.id);
          return;
        }

        if (event.target.closest('.cad-table-column__body, .cad-calendar__scroll')) {
          this.clearSelection();
        }
      };

      this._onKeydown = (event) => {
        if (event.key === 'Escape') {
          this.clearSelection();
          return;
        }

        if (
          event.key === 'ArrowDown' ||
          event.key === 'ArrowUp' ||
          event.key === 'ArrowLeft' ||
          event.key === 'ArrowRight'
        ) {
          this.focusRelativeAppointment(event);
          return;
        }

        if (event.key === 'Enter' || event.key === ' ') {
          const block = event.target.closest('.cad-appointment');

          if (block?.dataset.id) {
            event.preventDefault();
            this.select(block.dataset.id);
          }
        }
      };

      container.addEventListener('click', this._onClick);
      container.addEventListener('keydown', this._onKeydown);
    },

    async save(changes) {
      return CAD.API.request('cad_update_appointment', changes);
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
