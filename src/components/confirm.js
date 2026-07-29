/**
 * Shared awaitable confirm dialog (Continue / Cancel).
 * @module components/confirm
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before components/confirm.js');

  let root = null;
  let resolveFn = null;

  function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text != null && text !== '') node.textContent = text;
    return node;
  }

  function ensureDom() {
    if (root) return root;

    root = el('div', 'cad-confirm');
    root.hidden = true;
    root.setAttribute('role', 'dialog');
    root.setAttribute('aria-modal', 'true');
    root.setAttribute('aria-labelledby', 'cad-confirm-title');

    const backdrop = el('div', 'cad-confirm__backdrop');
    const panel = el('div', 'cad-confirm__panel');
    const title = el('h2', 'cad-confirm__title', 'Confirm');
    title.id = 'cad-confirm-title';
    const message = el('p', 'cad-confirm__message');
    const actions = el('div', 'cad-confirm__actions');
    const cancel = el('button', 'cad-confirm__btn cad-confirm__btn--ghost', 'Cancel');
    cancel.type = 'button';
    const confirm = el('button', 'cad-confirm__btn cad-confirm__btn--primary', 'Continue');
    confirm.type = 'button';

    actions.append(cancel, confirm);
    panel.append(title, message, actions);
    root.append(backdrop, panel);
    document.body.appendChild(root);

    const finish = (value) => {
      if (!resolveFn) return;
      const resolve = resolveFn;
      resolveFn = null;
      root.hidden = true;
      resolve(value);
    };

    backdrop.addEventListener('click', () => finish(false));
    cancel.addEventListener('click', () => finish(false));
    confirm.addEventListener('click', () => finish(true));
    document.addEventListener('keydown', (event) => {
      if (root.hidden || !resolveFn) return;
      if (event.key === 'Escape') {
        event.preventDefault();
        finish(false);
      }
    });

    root._title = title;
    root._message = message;
    root._cancel = cancel;
    root._confirm = confirm;
    return root;
  }

  /**
   * @param {{
   *   title?: string,
   *   message: string,
   *   confirmLabel?: string,
   *   cancelLabel?: string,
   * }} options
   * @returns {Promise<boolean>} true = confirm, false = cancel
   */
  CAD.confirm = function confirmDialog(options = {}) {
    const dialog = ensureDom();
    if (resolveFn) {
      resolveFn(false);
      resolveFn = null;
    }

    dialog._title.textContent = options.title || 'Confirm';
    dialog._message.textContent = options.message || '';
    dialog._confirm.textContent = options.confirmLabel || 'Continue';
    dialog._cancel.textContent = options.cancelLabel || 'Cancel';
    dialog._confirm.className = options.danger
      ? 'cad-confirm__btn cad-confirm__btn--danger'
      : 'cad-confirm__btn cad-confirm__btn--primary';
    dialog.hidden = false;
    queueMicrotask(() => dialog._confirm.focus());

    return new Promise((resolve) => {
      resolveFn = resolve;
    });
  };
})(typeof window !== 'undefined' ? window : globalThis);
