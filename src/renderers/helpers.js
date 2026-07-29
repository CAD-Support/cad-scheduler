/**
 * Shared presentation helpers for appointment body renderers (Sprint 2.6).
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
    const d = CAD.utils?.parseBooklyLocal
      ? CAD.utils.parseBooklyLocal(iso)
      : new Date(NaN);
    return Number.isNaN(d.getTime())
      ? String(iso || '')
      : d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
  }

  function formatTimeRange(appointment) {
    return `${formatTime(appointment.start)} – ${formatTime(appointment.end)}`;
  }

  function paintersCount(appointment) {
    return CAD.Badges?.paintersCount
      ? CAD.Badges.paintersCount(appointment)
      : Math.max(1, Math.floor(Number(appointment.painters) || 1));
  }

  function paintersLabel(appointment) {
    const count = paintersCount(appointment);
    return count === 1 ? '👥 1 painter' : `👥 ${count} painters`;
  }

  function phoneLine(appointment) {
    const raw = String(appointment.phone ?? '').trim();
    if (!raw) return '';
    const formatted = CAD.utils?.formatPhone ? CAD.utils.formatPhone(raw) : raw;
    return `☎ ${formatted}`;
  }

  function statusLine(appointment) {
    const meta = CAD.Badges?.resolveStatus
      ? CAD.Badges.resolveStatus(appointment)
      : { icon: '🟢', label: 'Approved' };
    return `${meta.icon} ${meta.label}`;
  }

  function appendDivider(fragment) {
    fragment.appendChild(el('hr', 'cad-popover__rule'));
  }

  function appendField(fragment, label, value, icon) {
    if (value == null || String(value).trim() === '') return false;
    const row = el('div', 'cad-popover__row');
    const labelText = icon ? `${icon} ${label}` : label;
    row.appendChild(el('div', 'cad-popover__field-label', labelText));
    row.appendChild(el('div', 'cad-popover__field-value', String(value)));
    fragment.appendChild(row);
    return true;
  }

  function appendSection(parent, title, icon, fill) {
    const section = el('section', 'cad-popover__section');
    section.dataset.section = title.toLowerCase().replace(/\s+/g, '-');
    const heading = el(
      'h3',
      'cad-popover__section-title',
      icon ? `${icon} ${title}` : title
    );
    const content = el('div', 'cad-popover__section-body');
    section.append(heading, content);
    const ok = fill(content);
    if (ok === false || !content.childNodes.length) return false;
    parent.appendChild(section);
    return true;
  }

  function appendBookedBy(fragment, appointment) {
    return appendSection(fragment, 'Booked By', '👤', (body) => {
      body.appendChild(
        el('div', 'cad-popover__customer', appointment.customer || 'Walk-in')
      );
      const phone = phoneLine(appointment);
      if (phone) body.appendChild(el('div', 'cad-popover__line', phone));
      return true;
    });
  }

  function appendAttendance(fragment, appointment, options = {}) {
    const showTotal = Boolean(options.showTotalAttending);
    const count = paintersCount(appointment);
    let added = 0;
    if (appendField(fragment, 'Painters', String(count), '👥')) added += 1;
    if (showTotal) {
      if (appendField(fragment, 'Total Attending', String(count), 'Σ')) added += 1;
    }
    return added > 0;
  }

  function appendStatusBadge(fragment, appointment) {
    if (CAD.Badges?.statusBadge) {
      fragment.appendChild(CAD.Badges.statusBadge(appointment));
      return true;
    }
    fragment.appendChild(el('span', 'cad-badge cad-badge--status', statusLine(appointment)));
    return true;
  }

  CAD.RendererHelpers = Object.freeze({
    el,
    formatTime,
    formatTimeRange,
    paintersCount,
    paintersLabel,
    phoneLine,
    statusLine,
    appendDivider,
    appendField,
    appendSection,
    appendBookedBy,
    appendAttendance,
    appendStatusBadge,
  });
})(typeof window !== 'undefined' ? window : globalThis);
