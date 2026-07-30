/**
 * Reservation Manager facade — Sprint 3.2.6.
 * Delegates to CAD.ReservationDialog (edit mode). Kept for call-site compatibility.
 * @module components/reservation-manager
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) {
    throw new Error('cad-core.js must be loaded before components/reservation-manager.js');
  }

  CAD.ReservationManager = {
    init() {
      CAD.ReservationDialog?.init?.();
      return this;
    },

    isOpen() {
      return (
        CAD.ReservationDialog?.isOpen?.() &&
        CAD.ReservationDialog.mode === 'edit'
      );
    },

    /**
     * @param {string|number|object} appointmentOrId
     */
    open(appointmentOrId) {
      if (appointmentOrId == null) return this;
      if (typeof appointmentOrId === 'object') {
        CAD.ReservationDialog?.open?.({
          mode: 'edit',
          id: appointmentOrId.id,
          appointment: appointmentOrId,
        });
      } else {
        CAD.ReservationDialog?.open?.({ mode: 'edit', id: appointmentOrId });
      }
      return this;
    },

    close() {
      CAD.ReservationDialog?.close?.();
      return this;
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
