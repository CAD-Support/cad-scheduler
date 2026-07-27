/**
 * Quick status action panel (Bookly custom status slugs only).
 * @module components/status-panel
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before components/status-panel.js');

  const STATUS_ACTIONS = Object.freeze([
    { slug: 'approved', icon: '✓', label: 'Confirmed' },
    { slug: 'deposit-paid', icon: '💰', label: 'Deposit Paid' },
    { slug: 'arrived', icon: '🟢', label: 'Arrived' },
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
     * @returns {HTMLElement}
     */
    render(appointment, onSelect) {
      const wrap = el('div', 'cad-popover__statuses');
      wrap.setAttribute('role', 'group');
      wrap.setAttribute('aria-label', 'Appointment status');
      const current = normalizeStatus(appointment?.status);

      STATUS_ACTIONS.forEach((action) => {
        const btn = el(
          'button',
          'cad-popover__status-btn',
          `${action.icon} ${action.label}`
        );
        btn.type = 'button';
        btn.dataset.status = action.slug;
        if (isActive(action.slug, current)) {
          btn.classList.add('cad-popover__status-btn--active');
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
