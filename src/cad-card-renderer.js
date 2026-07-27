/**
 * CAD Scheduler v2 — Adaptive appointment card renderer (Sprint 2.6 final)
 * Max ~4 lines; Crock A Doodle type accents; shared CAD.Badges.
 * @module cad-card-renderer
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before cad-card-renderer.js');

  /** @type {Readonly<{ compact: number, standard: number, large: number }>} */
  const HEIGHT_THRESHOLDS = Object.freeze({
    compact: 48,
    standard: 72,
    large: 96,
  });

  let tipEl = null;
  let tipHideTimer = null;

  function densityForHeight(availableHeight) {
    const h = Number(availableHeight);
    if (!Number.isFinite(h) || h < HEIGHT_THRESHOLDS.compact) return 'compact';
    if (h < HEIGHT_THRESHOLDS.standard) return 'standard';
    if (h < HEIGHT_THRESHOLDS.large) return 'large';
    return 'xl';
  }

  function appointmentType(appointment) {
    const type = String(appointment?.type || 'studio').toLowerCase();
    if (type === 'birthday' || type === 'event') return type;
    return 'studio';
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

  function birthdayChildName(appointment) {
    const bday =
      appointment?.birthday && typeof appointment.birthday === 'object'
        ? appointment.birthday
        : null;
    return String(bday?.childName ?? '').trim();
  }

  function statusBadgeMeta(appointment) {
    return CAD.Badges
      ? CAD.Badges.resolveStatus(appointment)
      : { key: 'approved', icon: '🟢', label: 'Approved' };
  }

  /** Compat for older helpers */
  function statusBadge(appointment) {
    return statusBadgeMeta(appointment);
  }

  function statusBadges(appointment) {
    const badge = statusBadgeMeta(appointment);
    if (badge.key === 'approved') return [];
    return [badge];
  }

  function tipStatusLine(appointment) {
    const badge = statusBadgeMeta(appointment);
    return `${badge.icon} ${badge.label}`;
  }

  function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text != null && text !== '') node.textContent = text;
    return node;
  }

  function finePointerHover() {
    return (
      typeof window.matchMedia === 'function' &&
      window.matchMedia('(hover: hover) and (pointer: fine)').matches
    );
  }

  function ensureTip() {
    if (tipEl && tipEl.isConnected) return tipEl;
    tipEl = el('div', 'cad-appointment-tip');
    tipEl.setAttribute('role', 'tooltip');
    tipEl.hidden = true;
    tipEl.style.setProperty('opacity', '1', 'important');
    tipEl.style.setProperty('background', '#ffffff', 'important');
    tipEl.style.setProperty('background-color', '#ffffff', 'important');
    tipEl.style.setProperty('background-image', 'none', 'important');
    tipEl.style.setProperty('backdrop-filter', 'none', 'important');
    tipEl.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
    tipEl.style.setProperty('filter', 'none', 'important');
    document.body.appendChild(tipEl);
    return tipEl;
  }

  function hideTip() {
    if (tipHideTimer) {
      clearTimeout(tipHideTimer);
      tipHideTimer = null;
    }
    if (!tipEl) return;
    tipEl.hidden = true;
    tipEl.replaceChildren();
    tipEl.remove();
    tipEl = null;
  }

  function fillTip(tip, appointment) {
    tip.replaceChildren();
    const type = appointmentType(appointment);
    const child = birthdayChildName(appointment);
    const lines = [];

    if (type === 'birthday' && child) {
      lines.push({ className: 'cad-appointment-tip__customer', text: child });
      lines.push({
        className: 'cad-appointment-tip__line',
        text: `Booked by ${appointment.customer || 'Walk-in'}`,
      });
    } else if (type === 'event') {
      lines.push({
        className: 'cad-appointment-tip__customer',
        text: appointment.service || 'Event',
      });
    } else {
      lines.push({
        className: 'cad-appointment-tip__customer',
        text: appointment.customer || 'Walk-in',
      });
    }

    lines.push(
      { className: 'cad-appointment-tip__line', text: formatTimeRange(appointment) },
      { className: 'cad-appointment-tip__line', text: tipStatusLine(appointment) }
    );

    lines.forEach(({ className, text }) => {
      tip.appendChild(el('div', className, text));
    });
  }

  function positionTip(tip, anchor) {
    const rect = anchor.getBoundingClientRect();
    const margin = 8;
    tip.hidden = false;
    tip.style.left = '0px';
    tip.style.top = '0px';
    const tipRect = tip.getBoundingClientRect();
    let left = rect.left;
    let top = rect.top - tipRect.height - margin;
    if (top < margin) top = rect.bottom + margin;
    left = Math.min(left, window.innerWidth - tipRect.width - margin);
    left = Math.max(margin, left);
    tip.style.left = `${Math.round(left)}px`;
    tip.style.top = `${Math.round(top)}px`;
  }

  function bindTooltip(card, appointment) {
    card.addEventListener('mouseenter', () => {
      if (!finePointerHover()) return;
      if (tipHideTimer) {
        clearTimeout(tipHideTimer);
        tipHideTimer = null;
      }
      const tip = ensureTip();
      fillTip(tip, appointment);
      positionTip(tip, card);
    });
    card.addEventListener('mouseleave', () => {
      tipHideTimer = setTimeout(hideTip, 80);
    });
  }

  function appendBadges(fragment, appointment) {
    if (CAD.Badges?.badgeRow) {
      fragment.appendChild(CAD.Badges.badgeRow(appointment));
      return;
    }
    const row = el('span', 'cad-badge-row');
    row.appendChild(
      el('span', 'cad-badge cad-badge--painters', `👥 ${appointment.painters || 1}`)
    );
    const meta = statusBadgeMeta(appointment);
    row.appendChild(
      el(
        'span',
        `cad-badge cad-badge--status cad-badge--${meta.key}`,
        `${meta.icon} ${meta.label}`
      )
    );
    fragment.appendChild(row);
  }

  /**
   * Max 4 lines. No phone/email.
   * Birthday: child, contact, badges
   * Studio: customer, badges
   * Event: event name, badges
   */
  function render(appointment, availableHeight) {
    const density = densityForHeight(availableHeight);
    const fragment = document.createDocumentFragment();
    const type = appointmentType(appointment);
    const child = birthdayChildName(appointment);
    const customer = String(appointment.customer || 'Walk-in');

    if (type === 'birthday') {
      fragment.appendChild(
        el(
          'span',
          'cad-appointment__customer cad-appointment__customer--primary',
          child || customer
        )
      );
      if (child && density !== 'compact') {
        fragment.appendChild(el('span', 'cad-appointment__booked-by', customer));
      }
    } else if (type === 'event') {
      fragment.appendChild(
        el(
          'span',
          'cad-appointment__customer cad-appointment__customer--primary',
          appointment.service || 'Event'
        )
      );
    } else {
      fragment.appendChild(
        el(
          'span',
          'cad-appointment__customer cad-appointment__customer--primary',
          customer
        )
      );
    }

    if (density !== 'compact') {
      appendBadges(fragment, appointment);
    }

    return fragment;
  }

  CAD.cardRenderer = Object.freeze({
    HEIGHT_THRESHOLDS,
    densityForHeight,
    statusBadge,
    statusBadges,
    render,
    bindTooltip,
  });
})(typeof window !== 'undefined' ? window : globalThis);
