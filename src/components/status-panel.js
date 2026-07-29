/**
 * Quick status action panel (Bookly custom status slugs only).
 * @module components/status-panel
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before components/status-panel.js');

  const STATUS_ACTIONS = Object.freeze([
    { slug: 'approved', icon: '🟢', label: 'Approved' },
    { slug: 'deposit-paid', icon: '💰', label: 'Deposit Paid' },
    { slug: 'arrived', icon: '✅', label: 'Arrived' },
    { slug: 'paid', icon: '💲', label: 'Paid' },
    { slug: 'no-show', icon: '❌', label: 'No Show' },
  ]);

  function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text != null && text !== '') node.textContent = text;
    return node;
  }

  function normalizeStatus(slug) {
    let value = String(slug || '')
      .trim()
      .toLowerCase()
      .replace(/[\s_]+/g, '-');
    if (value === 'noshow') value = 'no-show';
    if (value === 'confirmed') value = 'approved';
    return value;
  }

  function isActive(actionSlug, current) {
    if (current === actionSlug) return true;
    if (actionSlug === 'approved' && (current === '' || current === 'approved')) {
      return true;
    }
    return false;
  }

  CAD.StatusPanel = Object.freeze({
    actions: STATUS_ACTIONS,
    normalizeStatus,

    /**
     * @param {Record<string, unknown>} appointment
     * @param {(slug: string) => void} onSelect
     * @param {{ variant?: 'default'|'chips' }} [options]
     * @returns {HTMLElement}
     */
    render(appointment, onSelect, options) {
      const variant = options?.variant === 'chips' ? 'chips' : 'default';
      const wrap = el(
        'div',
        variant === 'chips' ? 'cad-status-chips' : 'cad-popover__statuses'
      );
      wrap.setAttribute('role', 'group');
      wrap.setAttribute('aria-label', 'Appointment status');
      const current = normalizeStatus(appointment?.status);

      STATUS_ACTIONS.forEach((action) => {
        const label =
          variant === 'chips' ? action.label : `${action.icon} ${action.label}`;
        const btn = el(
          'button',
          variant === 'chips'
            ? 'cad-status-chips__btn'
            : 'cad-popover__status-btn',
          label
        );
        btn.type = 'button';
        btn.dataset.status = action.slug;
        btn.title = `${action.icon} ${action.label}`;
        if (isActive(action.slug, current)) {
          btn.classList.add(
            variant === 'chips'
              ? 'cad-status-chips__btn--active'
              : 'cad-popover__status-btn--active'
          );
        }
        btn.addEventListener('click', () => {
          if (typeof onSelect === 'function') onSelect(action.slug);
        });
        wrap.appendChild(btn);
      });

      return wrap;
    },
  });
})(typeof window !== 'undefined' ? window : globalThis);
