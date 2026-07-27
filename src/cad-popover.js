/**
 * @deprecated Use src/components/popover.js (+ status-panel.js). Kept so old
 * enqueue paths fail loudly instead of silently breaking.
 */
(function (global) {
  'use strict';
  if (!global.CAD?.Popover) {
    throw new Error(
      'cad-popover.js moved to src/components/popover.js. Update the bridge enqueue.'
    );
  }
})(typeof window !== 'undefined' ? window : globalThis);
