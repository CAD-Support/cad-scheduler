/**
 * Shared presentation helpers for appointment body renderers.
 * Normalized CAD appointment only — never Bookly field IDs or json_data.
 * @module renderers/helpers
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before renderers/helpers.js');

  function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text != null && text !== '') node.textContent = text;
    return node;
  }

  function formatTime(iso) {
    const d = new Date(iso);
    return Number.isNaN(d.getTime())
      ? String(iso || '')
      : d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
  }

  function formatTimeRange(appointment) {
    return `${formatTime(appointment.start)} – ${formatTime(appointment.end)}`;
  }

  function paintersLabel(appointment) {
    const n = Number(appointment.painters);
    const count = Number.isFinite(n) && n > 0 ? Math.floor(n) : 1;
    return count === 1 ? '👥 1 painter' : `👥 ${count} painters`;
  }

  function phoneLine(appointment) {
    const raw = String(appointment.phone ?? '').trim();
    if (!raw) return '';
    const formatted = CAD.utils?.formatPhone ? CAD.utils.formatPhone(raw) : raw;
    return `☎ ${formatted}`;
  }

  function statusLine(appointment) {
    if (CAD.cardRenderer?.statusBadges) {
      const badges = CAD.cardRenderer.statusBadges(appointment);
      if (badges.length) {
        return badges.map((b) => `${b.icon} ${b.label}`).join(' · ');
      }
    }
    const slug = String(appointment.status || '').trim().toLowerCase();
    if (!slug || slug === 'approved' || slug === 'confirmed') {
      return '✓ Confirmed';
    }
    if (slug === 'noshow' || slug === 'no-show') {
      return '❌ No Show';
    }
    return `✓ ${slug.replace(/[-_]/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())}`;
  }

  function appendDivider(fragment) {
    fragment.appendChild(el('hr', 'cad-popover__rule'));
  }

  function appendField(fragment, label, value) {
    if (value == null || String(value).trim() === '') return;
    fragment.appendChild(el('div', 'cad-popover__field-label', label));
    fragment.appendChild(el('div', 'cad-popover__field-value', String(value)));
  }

  function appendIdentity(fragment, appointment) {
    fragment.appendChild(
      el('div', 'cad-popover__customer', appointment.customer || 'Walk-in')
    );
    const phone = phoneLine(appointment);
    if (phone) fragment.appendChild(el('div', 'cad-popover__line', phone));
    fragment.appendChild(el('div', 'cad-popover__line', formatTimeRange(appointment)));
  }

  CAD.RendererHelpers = Object.freeze({
    el,
    formatTime,
    formatTimeRange,
    paintersLabel,
    phoneLine,
    statusLine,
    appendDivider,
    appendField,
    appendIdentity,
  });
})(typeof window !== 'undefined' ? window : globalThis);
