/**
 * @deprecated Use src/renderers/* (registry + type renderers). Kept so old
 * enqueue paths fail loudly instead of silently breaking.
 */
(function (global) {
  'use strict';
  if (!global.CAD?.Renderers) {
    throw new Error(
      'cad-renderers.js moved to src/renderers/. Update the bridge enqueue to load helpers, registry, and type renderers.'
    );
  }
})(typeof window !== 'undefined' ? window : globalThis);
