/**
 * CAD Scheduler — Calendar
 * Multi-table schedule grid.
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;

  if (!CAD) {
    throw new Error('cad-core.js must be loaded before cad-calendar.js');
  }

  CAD.calendar = {
    container: null,

    render(container) {
      this.container = container;
      if (!this.container) return;

      const tables = CAD.Config.get('tables') ?? [];
      this.container.innerHTML = '';
      this.container.classList.add('cad-calendar');

      tables.forEach((table) => {
        const column = CAD.components.tableColumn(table);
        (CAD.State.get('appointments') ?? [])
          .filter((appt) => appt.tableId === table.id)
          .forEach((appt) => column.appendChild(CAD.components.appointmentBlock(appt)));
        this.container.appendChild(column);
      });

      CAD.editor?.bind(this.container);
    },
  };
})(typeof window !== 'undefined' ? window : globalThis);
