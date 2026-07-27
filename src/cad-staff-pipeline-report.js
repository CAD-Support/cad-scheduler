/**
 * Staff pipeline diagnostics — finalize report with UI column count and show summary.
 * @module cad-staff-pipeline-report
 */
(function (global) {
  'use strict';

  const CAD = global.CAD;
  if (!CAD) throw new Error('cad-core.js must be loaded before staff pipeline report helper');

  function resourceLine(row, bullet) {
    const id = String(row?.id ?? '');
    const name = String(row?.name ?? '');
    const position = row?.position == null || row?.position === '' ? '—' : String(row.position);
    const vis = String(row?.visibility ?? '');
    const archived = row?.archived ? 'true' : 'false';
    const prefix = bullet === false ? '' : '- ';
    let line = `${prefix}${name} (ID ${id}) | position=${position} | visibility=${vis} | archived=${archived}`;
    if (row?.excludedReason) line += ` | reason: ${row.excludedReason}`;
    return line;
  }

  /**
   * @param {Record<string, unknown>} report
   * @param {number|null} uiCount
   * @returns {{ report: Record<string, unknown>, summary: string, failingLayer: string }}
   */
  function finalize(report, uiCount) {
    const r = report && typeof report === 'object' ? { ...report } : {};
    const counts = { ...(r.counts && typeof r.counts === 'object' ? r.counts : {}) };
    if (uiCount != null) counts.ui = uiCount;
    r.counts = counts;

    let failing = String(r.failingLayer || 'none');
    if (uiCount != null && Number(counts.ajax ?? 0) !== Number(uiCount)) {
      failing = 'ui';
    }
    r.failingLayer = failing;

    const lines = [];
    lines.push('CAD staff pipeline report');
    lines.push(`Repository build: ${r.repository_build || 'unknown'}`);
    if (r.error) lines.push(`ERROR: ${r.error}`);
    lines.push('');
    lines.push(`Bookly resources: ${Number(counts.bookly || 0)}`);
    lines.push(`Repository:       ${Number(counts.repository || 0)}`);
    lines.push(`Mapper:           ${Number(counts.mapper || 0)}`);
    lines.push(`Provider:         ${Number(counts.provider || 0)}`);
    lines.push(`AJAX tables:      ${Number(counts.ajax || 0)}`);
    lines.push(
      uiCount == null ? 'UI columns:       (pending browser)' : `UI columns:       ${Number(uiCount)}`
    );
    lines.push('');

    if (failing === 'none') {
      lines.push('No mismatch detected across Bookly → Repository → Mapper → Provider → AJAX → UI.');
    } else if (failing === 'ui') {
      lines.push('Mismatch detected between AJAX and UI.');
    } else {
      lines.push(`Mismatch detected at: ${failing.toUpperCase()}`);
    }

    const excluded = r.excluded && typeof r.excluded === 'object' ? r.excluded : {};

    if (failing === 'repository' && Array.isArray(excluded.repository) && excluded.repository.length) {
      lines.push('');
      lines.push('Missing:');
      excluded.repository.forEach((row) => lines.push(resourceLine(row)));
      const reasons = [
        ...new Set(excluded.repository.map((row) => row.excludedReason || 'unknown')),
      ];
      lines.push('');
      lines.push(`Excluded because: ${reasons.join('; ')}`);
    }

    if (failing === 'mapper' && Array.isArray(excluded.mapper) && excluded.mapper.length) {
      lines.push('');
      lines.push('Missing from Mapper:');
      excluded.mapper.forEach((row) => lines.push(resourceLine(row)));
    }

    if (failing === 'provider' && Array.isArray(excluded.provider) && excluded.provider.length) {
      lines.push('');
      lines.push('Missing from Provider:');
      excluded.provider.forEach((row) => lines.push(resourceLine(row)));
      if (r.tables_filter_registered) {
        lines.push('');
        lines.push('Note: a cad_scheduler_tables filter is registered on this site.');
      }
    }

    if (failing === 'ui') {
      lines.push('');
      lines.push('AJAX returned resources that the grid did not render as columns.');
      lines.push('Check horizontal scroll, CDN asset version, and Config.tables after merge.');
    }

    lines.push('');
    lines.push('Stage details (id | name | position | visibility | archived):');
    const stages = r.stages && typeof r.stages === 'object' ? r.stages : {};
    ['bookly', 'repository', 'mapper', 'provider'].forEach((stage) => {
      const rows = Array.isArray(stages[stage]) ? stages[stage] : [];
      lines.push('');
      lines.push(`${stage.toUpperCase()} (${rows.length})`);
      if (!rows.length) {
        lines.push('  (none)');
        return;
      }
      rows.forEach((row) => lines.push(`  ${resourceLine(row, false)}`));
    });

    const summary = lines.join('\n');
    r.summary = summary;
    return { report: r, summary, failingLayer: failing };
  }

  /**
   * Show summary in a panel under the scheduler mount + console.
   * @param {string} summary
   */
  function show(summary) {
    // eslint-disable-next-line no-console
    console.warn(summary);

    const mount =
      document.getElementById('cad-scheduler') ||
      document.querySelector('.cad-scheduler-mount') ||
      document.querySelector('.cad-scheduler');
    if (!mount || !mount.parentNode) return;

    let panel = document.getElementById('cad-staff-pipeline-report');
    if (!panel) {
      panel = document.createElement('pre');
      panel.id = 'cad-staff-pipeline-report';
      panel.className = 'cad-staff-pipeline-report';
      panel.setAttribute('role', 'status');
      mount.parentNode.insertBefore(panel, mount.nextSibling);
    }
    panel.textContent = summary;
  }

  CAD.StaffPipelineReport = Object.freeze({ finalize, show, resourceLine });
})(typeof window !== 'undefined' ? window : globalThis);
