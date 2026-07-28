<?php
/**
 * Snippet 10A — AJAX Bridge (v2)
 *
 * Code Snippets: priority 20 — bootstrap / routing only.
 * Requires snippets 10–12 from includes/ (see docs/deployment.md).
 *
 * @package CAD_Scheduler
 */

defined( 'ABSPATH' ) || exit;

// IMPORTANT:
// This version must reference an existing Git tag or commit that is
// already available on jsDelivr. Do not increment before the release
// has been pushed and published.
if ( ! defined( 'CAD_SCHEDULER_VERSION' ) ) {
	define( 'CAD_SCHEDULER_VERSION', '2.7.3' );
}

if ( ! defined( 'CAD_SCHEDULER_GITHUB_REPO' ) ) {
	define( 'CAD_SCHEDULER_GITHUB_REPO', 'CAD-Support/cad-scheduler' );
}

function cad_scheduler_asset_url( $path ) {
	$path = ltrim( $path, '/' );
	$url  = sprintf(
		'https://cdn.jsdelivr.net/gh/%s@%s/%s',
		CAD_SCHEDULER_GITHUB_REPO,
		CAD_SCHEDULER_VERSION,
		$path
	);
	return apply_filters( 'cad_scheduler_asset_url', $url, $path );
}

function cad_scheduler_ready() {
	return class_exists( 'CAD_Bookly_Repository', false )
		&& class_exists( 'CAD_Bookly_Mapper', false )
		&& class_exists( 'CAD_Schedule_Provider', false );
}

/**
 * Weekly open hours for the matrix (0 = Sunday … 6 = Saturday).
 * Use null for a closed day. Override via cad_scheduler_open_hours.
 *
 * @return array<int, array{start: string, end: string}|null>
 */
function cad_scheduler_open_hours() {
	$start = apply_filters( 'cad_scheduler_day_start', '08:00' );
	$end   = apply_filters( 'cad_scheduler_day_end', '20:00' );
	$day   = array(
		'start' => $start,
		'end'   => $end,
	);
	$weekly = array(
		0 => $day,
		1 => $day,
		2 => $day,
		3 => $day,
		4 => $day,
		5 => $day,
		6 => $day,
	);
	return apply_filters( 'cad_scheduler_open_hours', $weekly );
}

/**
 * @return array<int, array{code: string, message: string, fix?: string, blocking?: bool}>
 */
function cad_scheduler_health() {
	$issues = array();

	if ( ! class_exists( 'CAD_Bookly_Repository', false ) ) {
		$issues[] = array(
			'code'      => 'missing_repository',
			'message'   => 'CAD Bookly Repository is not loaded.',
			'fix'       => 'Activate snippet 10 from includes/class-cad-bookly-repository.php.',
			'blocking'  => true,
		);
	}

	if ( ! class_exists( 'CAD_Bookly_Mapper', false ) ) {
		$issues[] = array(
			'code'      => 'missing_mapper',
			'message'   => 'CAD Bookly Mapper is not loaded.',
			'fix'       => 'Activate snippet 11 from includes/class-cad-bookly-mapper.php.',
			'blocking'  => true,
		);
	}

	if ( ! class_exists( 'CAD_Schedule_Provider', false ) ) {
		$issues[] = array(
			'code'      => 'missing_provider',
			'message'   => 'CAD Schedule Provider is not loaded.',
			'fix'       => 'Activate snippet 12 from includes/class-cad-schedule-provider.php.',
			'blocking'  => true,
		);
	}

	if ( class_exists( 'CAD_Bookly_Repository', false ) && ! CAD_Bookly_Repository::is_available() ) {
		$issues[] = array(
			'code'     => 'bookly_unavailable',
			'message'  => 'Bookly database tables were not found.',
			'fix'      => 'Install and activate Bookly on this WordPress site.',
			'blocking' => false,
		);
	}

	return apply_filters( 'cad_scheduler_health', $issues );
}

/**
 * @param array<int, array{code?: string, message?: string, fix?: string, blocking?: bool}> $issues
 */
function cad_scheduler_has_blocking_issues( $issues = null ) {
	if ( null === $issues ) {
		$issues = cad_scheduler_health();
	}
	foreach ( $issues as $issue ) {
		if ( ! empty( $issue['blocking'] ) ) {
			return true;
		}
	}
	return false;
}

function cad_scheduler_diagnostics_enabled() {
	if ( cad_scheduler_has_blocking_issues() ) {
		return true;
	}
	return (bool) apply_filters( 'cad_scheduler_diagnostics_enabled', false );
}

function cad_scheduler_validation_mode_enabled() {
	return (bool) apply_filters( 'cad_scheduler_validation_mode_enabled', false );
}

/**
 * @param array<int, array{code?: string, message?: string, fix?: string}> $issues
 */
function cad_scheduler_render_diagnostics( array $issues ) {
	if ( empty( $issues ) ) {
		if ( ! cad_scheduler_diagnostics_enabled() ) {
			return '';
		}
		$issues = array(
			array(
				'code'    => 'ok',
				'message' => 'CAD Scheduler components are loaded.',
			),
		);
	}

	$html  = '<div class="cad-scheduler__diagnostics" role="alert">';
	$html .= '<p class="cad-scheduler__diagnostics-title"><strong>CAD Scheduler</strong></p>';
	$html .= '<ul class="cad-scheduler__diagnostics-list">';

	foreach ( $issues as $issue ) {
		$message = esc_html( (string) ( $issue['message'] ?? 'Unknown issue.' ) );
		$fix     = isset( $issue['fix'] ) ? esc_html( (string) $issue['fix'] ) : '';
		$html   .= '<li>' . $message . ( $fix ? ' — ' . $fix : '' ) . '</li>';
	}

	$html .= '</ul></div>';

	return $html;
}

/**
 * @return array<int, array{code: string, message: string}>
 */
function cad_scheduler_health_for_config() {
	$out = array();
	foreach ( cad_scheduler_health() as $issue ) {
		$out[] = array(
			'code'    => (string) ( $issue['code'] ?? 'unknown' ),
			'message' => (string) ( $issue['message'] ?? '' ),
		);
	}
	return $out;
}

function cad_schedule_provider() {
	static $provider = null;
	if ( ! cad_scheduler_ready() ) {
		return null;
	}
	if ( null === $provider ) {
		$provider = new CAD_Schedule_Provider();
	}
	return $provider;
}

function cad_enqueue_assets() {
	if ( ! cad_scheduler_ready() ) {
		return;
	}

	$provider = cad_schedule_provider();
	$ver      = CAD_SCHEDULER_VERSION;
	$src      = cad_scheduler_asset_url( 'src/' );

	wp_enqueue_style( 'cad-scheduler', cad_scheduler_asset_url( 'assets/css/cad-scheduler.css' ), array(), $ver );
	wp_enqueue_script( 'cad-core', $src . 'cad-core.js', array(), $ver, true );
	wp_enqueue_script( 'cad-api', $src . 'cad-api.js', array( 'cad-core' ), $ver, true );
	wp_enqueue_script( 'cad-staff-pipeline-report', $src . 'cad-staff-pipeline-report.js', array( 'cad-core' ), $ver, true );
	wp_enqueue_script( 'cad-editor', $src . 'cad-editor.js', array( 'cad-core' ), $ver, true );
	wp_enqueue_script( 'cad-badges', $src . 'components/badges.js', array( 'cad-core' ), $ver, true );
	wp_enqueue_script( 'cad-card-renderer', $src . 'cad-card-renderer.js', array( 'cad-core', 'cad-badges' ), $ver, true );
	wp_enqueue_script( 'cad-components', $src . 'cad-components.js', array( 'cad-core', 'cad-card-renderer' ), $ver, true );
	wp_enqueue_script( 'cad-renderer-helpers', $src . 'renderers/helpers.js', array( 'cad-core', 'cad-badges', 'cad-card-renderer' ), $ver, true );
	wp_enqueue_script( 'cad-renderer-registry', $src . 'renderers/registry.js', array( 'cad-core' ), $ver, true );
	wp_enqueue_script( 'cad-renderer-reservation', $src . 'renderers/reservation-renderer.js', array( 'cad-renderer-helpers', 'cad-renderer-registry' ), $ver, true );
	wp_enqueue_script( 'cad-renderer-birthday', $src . 'renderers/birthday-renderer.js', array( 'cad-renderer-helpers', 'cad-renderer-registry' ), $ver, true );
	wp_enqueue_script( 'cad-renderer-event', $src . 'renderers/event-renderer.js', array( 'cad-renderer-helpers', 'cad-renderer-registry' ), $ver, true );
	wp_enqueue_script( 'cad-status-panel', $src . 'components/status-panel.js', array( 'cad-core', 'cad-badges' ), $ver, true );
	wp_enqueue_script( 'cad-popover', $src . 'components/popover.js', array( 'cad-core', 'cad-api', 'cad-badges', 'cad-renderer-registry', 'cad-renderer-reservation', 'cad-renderer-birthday', 'cad-renderer-event', 'cad-status-panel' ), $ver, true );
	wp_enqueue_script( 'cad-calendar', $src . 'cad-calendar.js', array( 'cad-core', 'cad-components' ), $ver, true );
	wp_enqueue_script( 'cad-notify', $src . 'cad-notify.js', array( 'cad-core' ), $ver, true );
	wp_enqueue_script( 'cad-dnd', $src . 'cad-dnd.js', array( 'cad-core', 'cad-api', 'cad-calendar', 'cad-notify' ), $ver, true );
	wp_enqueue_script( 'cad-ui', $src . 'cad-ui.js', array( 'cad-core', 'cad-api', 'cad-calendar', 'cad-popover', 'cad-staff-pipeline-report', 'cad-dnd', 'cad-notify' ), $ver, true );
	wp_enqueue_script( 'cad-navigation', $src . 'cad-navigation.js', array( 'cad-core', 'cad-ui' ), $ver, true );

	wp_localize_script(
		'cad-core',
		'cadConfig',
		array(
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'cad_scheduler' ),
			'today'          => current_time( 'Y-m-d' ),
			'tables'         => $provider->get_tables(),
			'dayStart'       => apply_filters( 'cad_scheduler_day_start', '08:00' ),
			'dayEnd'         => apply_filters( 'cad_scheduler_day_end', '20:00' ),
			'openHours'      => cad_scheduler_open_hours(),
			'slotMinutes'    => 15,
			'hourHeight'     => 64,
			'booklyEditUrl'  => apply_filters(
				'cad_scheduler_bookly_edit_url',
				admin_url( 'admin.php?page=bookly-calendar' )
			),
			'health'         => cad_scheduler_health_for_config(),
			'diagnostics'    => cad_scheduler_diagnostics_enabled(),
			'validationMode' => cad_scheduler_validation_mode_enabled(),
		)
	);

	// TEMPORARY CDN polyfill (while CAD_SCHEDULER_VERSION pins 2.4.1):
	// mirrors src/cad-ui.js + src/cad-calendar.js tables sync / State rebuild.
	// Source of truth is src/ — delete this inline after tagging a release that
	// includes those files and bumping CAD_SCHEDULER_VERSION.
	wp_add_inline_script( 'cad-navigation', cad_scheduler_tables_sync_inline_js() );

	wp_add_inline_script(
		'cad-navigation',
		"(function(){document.addEventListener('DOMContentLoaded',function(){if(typeof CAD==='undefined'||!CAD.ui||!CAD.Navigation)return;CAD.init(window.cadConfig||{});var m=document.getElementById('cad-scheduler');if(!m)return;CAD.ui.mount('#cad-scheduler');if(m.querySelector('.cad-scheduler__diagnostics'))return;CAD.Navigation.init();CAD.Popover&&CAD.Popover.init();CAD.ui.load(CAD.Config.get('today'));});})();"
	);

	if ( cad_scheduler_diagnostics_enabled() ) {
		wp_add_inline_script( 'cad-navigation', cad_scheduler_staff_pipeline_inline_js(), 'after' );
	}
}

/**
 * TEMPORARY CDN compatibility polyfill — NOT permanent application logic.
 *
 * Mirrors (do not diverge from) the permanent implementations in:
 *   - src/cad-ui.js      → payload.tables → Config / State / cadConfig
 *   - src/cad-calendar.js → resolveTables, applyColumnTracks, full header+lane rebuild
 *
 * Needed only while CAD_SCHEDULER_VERSION still points at CDN builds that
 * ignored data.tables / State (AJAX could return 9 tables while the grid kept 7).
 * After tagging a release that includes those src/ fixes and bumping
 * CAD_SCHEDULER_VERSION, remove this function and its wp_add_inline_script call.
 *
 * TEMP DEBUG Sprint 2.5.1 console logs are duplicated here to match src — remove
 * from both after live column-count is verified.
 *
 * @return string
 */
function cad_scheduler_tables_sync_inline_js() {
	return <<<'JS'
/* TEMPORARY CDN polyfill — source of truth: src/cad-ui.js + src/cad-calendar.js.
   Delete after CAD_SCHEDULER_VERSION bump includes those files. */
(function () {
  function resolveTablesFromState() {
    var stateTables = CAD.State && CAD.State.get('tables');
    if (Array.isArray(stateTables) && stateTables.length > 0) return stateTables;
    var configTables = CAD.Config && CAD.Config.get('tables');
    if (Array.isArray(configTables) && configTables.length > 0) return configTables;
    return Array.isArray(stateTables) ? stateTables : [];
  }

  function syncTablesEverywhere(tables) {
    if (!Array.isArray(tables)) return;
    var copy = tables.slice();
    if (CAD.Config && typeof CAD.Config.merge === 'function') {
      CAD.Config.merge({ tables: copy });
    }
    if (CAD.State && typeof CAD.State.set === 'function') {
      CAD.State.set('tables', copy);
    }
    // CDN builds may read page-load cadConfig directly — keep it in sync.
    if (typeof window !== 'undefined' && window.cadConfig) {
      window.cadConfig.tables = copy;
    }
  }

  function applyScheduleTables(payload) {
    if (!payload || !Array.isArray(payload.tables) || typeof CAD === 'undefined') return;
    syncTablesEverywhere(payload.tables);
  }

  function isAbortError(err) {
    return !!(err && typeof err === 'object' && err.name === 'AbortError');
  }

  /** TEMPORARY: mirror of CAD.calendar.render (src/cad-calendar.js) for CDN 2.4.1. */
  function forceRebuildFromState(container) {
    if (!container || !CAD.calendar) return;
    var tables = resolveTablesFromState();
    syncTablesEverywhere(tables);

    var appointments = CAD.State.get('appointments');
    if (!Array.isArray(appointments)) appointments = [];
    var metrics = typeof CAD.calendar.gridMetrics === 'function'
      ? CAD.calendar.gridMetrics(appointments)
      : null;

    container.innerHTML = '';
    container.classList.add('cad-matrix');
    container.style.maxWidth = '100%';

    if (!tables.length) {
      if (CAD.components && typeof CAD.components.emptyState === 'function') {
        container.appendChild(CAD.components.emptyState('No tables configured.'));
      }
      return;
    }

    var tableCount = tables.length;
    var tableCountStr = String(tableCount);
    container.style.setProperty('--cad-table-count', tableCountStr);
    if (metrics) {
      container.style.setProperty('--cad-grid-height', metrics.gridHeight + 'px');
      container.style.setProperty('--cad-slot-height', metrics.slotHeight + 'px');
      container.style.setProperty('--cad-day-start-min', String(metrics.startMin));
    }

    var scroll = document.createElement('div');
    scroll.className = 'cad-matrix__scroll';
    scroll.tabIndex = 0;
    scroll.setAttribute('role', 'region');
    scroll.setAttribute('aria-label', 'Studio schedule');
    scroll.style.setProperty('--cad-table-count', tableCountStr);
    scroll.style.maxWidth = '100%';
    scroll.style.width = '100%';
    scroll.style.minWidth = '0';
    scroll.style.overflowX = 'auto';
    scroll.style.overflowY = 'auto';
    if (metrics) {
      scroll.style.setProperty('--cad-grid-height', metrics.gridHeight + 'px');
      scroll.style.setProperty('--cad-slot-height', metrics.slotHeight + 'px');
      scroll.style.setProperty('--cad-day-start-min', String(metrics.startMin));
    }

    // Literal repeat(N) — CDN CSS uses repeat(var(--cad-table-count)) which can
    // fail to expand when the var is inherited; inline tracks always match State.
    function applyColumnTracks(el, n) {
      el.style.setProperty('--cad-table-count', String(n));
      el.style.display = 'grid';
      el.style.alignItems = 'start';
      el.style.gridTemplateColumns =
        'var(--cad-time-width) repeat(' + n + ', minmax(var(--cad-col-min), 1fr))';
      el.style.minWidth =
        'max(100%, calc(var(--cad-time-width) + (' + n + ' * var(--cad-col-min))))';
    }

    var head = document.createElement('div');
    head.className = 'cad-matrix__head';
    var corner = document.createElement('div');
    corner.className = 'cad-matrix__corner';
    corner.textContent = (CAD.State && CAD.State.get('selectedDate')) || 'Today';
    head.appendChild(corner);
    tables.forEach(function (table) {
      var label = document.createElement('div');
      label.className = 'cad-matrix__table-label';
      label.textContent = table.name;
      label.dataset.tableId = table.id;
      head.appendChild(label);
    });

    var body = document.createElement('div');
    body.className = 'cad-matrix__body';

    var timeCol = document.createElement('div');
    timeCol.className = 'cad-matrix__times';
    if (metrics && Array.isArray(metrics.labels)) {
      metrics.labels.forEach(function (text) {
        var slot = document.createElement('div');
        slot.className = 'cad-matrix__time-slot';
        slot.textContent = text;
        timeCol.appendChild(slot);
      });
    }
    body.appendChild(timeCol);

    tables.forEach(function (table) {
      var lane = document.createElement('div');
      lane.className = 'cad-matrix__lane';
      lane.dataset.tableId = table.id;
      var lines = document.createElement('div');
      lines.className = 'cad-matrix__lines';
      if (metrics) {
        for (var i = 0; i < metrics.slotCount; i += 1) {
          var line = document.createElement('div');
          line.className = 'cad-matrix__line';
          lines.appendChild(line);
        }
      }
      var blocks = document.createElement('div');
      blocks.className = 'cad-matrix__blocks';
      appointments
        .filter(function (a) { return String(a.tableId) === String(table.id); })
        .forEach(function (appointment) {
          if (!CAD.components || typeof CAD.components.appointmentBlock !== 'function' || !metrics) return;
          var start = new Date(appointment.start);
          var end = new Date(appointment.end);
          if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return;
          var toMin = function (d) { return d.getHours() * 60 + d.getMinutes(); };
          var relStart = Math.max(0, toMin(start) - metrics.startMin);
          var relEnd = Math.min(metrics.rangeMin, toMin(end) - metrics.startMin);
          var duration = Math.max(relEnd - relStart, metrics.slotMinutes / 2);
          var pxPerMinute = metrics.gridHeight / metrics.rangeMin;
          blocks.appendChild(CAD.components.appointmentBlock(appointment, {
            top: (relStart * pxPerMinute) + 'px',
            height: Math.max(duration * pxPerMinute, metrics.slotHeight * 0.85) + 'px',
          }));
        });
      lane.appendChild(lines);
      lane.appendChild(blocks);
      body.appendChild(lane);
    });

    applyColumnTracks(head, tableCount);
    applyColumnTracks(body, tableCount);

    scroll.appendChild(head);
    scroll.appendChild(body);
    container.appendChild(scroll);

    // TEMP DEBUG Sprint 2.5.1 — remove after live column-count verified
    // (after attach so getComputedStyle reflects live layout)
    var headerCount = head.querySelectorAll('.cad-matrix__table-label').length;
    var bodyColumnCount = body.querySelectorAll('.cad-matrix__lane').length;
    try {
      console.log(
        'Rendering columns:',
        tables.map(function (t) { return t && t.name; })
      );
      console.log('Header DOM nodes:', headerCount);
      console.log('Body column DOM nodes:', bodyColumnCount);
      console.log('TEMP DEBUG --cad-table-count:', tableCountStr);
      console.log(
        'TEMP DEBUG gridTemplateColumns:',
        window.getComputedStyle(head).gridTemplateColumns
      );
    } catch (e) {}

    if (CAD.editor && typeof CAD.editor.bind === 'function') CAD.editor.bind(container);
    if (CAD.DnD && typeof CAD.DnD.bind === 'function') CAD.DnD.bind(container);

    // TEMP DEBUG — day-start alignment (CSS var vs open hours vs first row vs snap).
    try {
      var cssRaw = window.getComputedStyle(container).getPropertyValue('--cad-day-start-min').trim();
      var cssMin = cssRaw === '' ? null : parseInt(cssRaw, 10);
      var openCfg = (CAD.Config && CAD.Config.get('dayStart')) || null;
      var openMap = CAD.Config && CAD.Config.get('openHours');
      var firstLabel = metrics && metrics.labels ? metrics.labels.find(function (l) { return !!l; }) : null;
      console.log('[CAD day-start]', {
        cssVar: cssRaw,
        cssMin: cssMin,
        metricsStartMin: metrics ? metrics.startMin : null,
        metricsDayStart: metrics ? metrics.dayStart : null,
        configDayStart: openCfg,
        openHoursToday: openMap,
        firstVisibleLabel: firstLabel,
        snapUses: cssMin !== null && !isNaN(cssMin) ? cssMin : 8 * 60,
        snapFallbackIfMissing: 8 * 60,
        aligned:
          metrics &&
          cssMin === metrics.startMin
      });
    } catch (e2) {}
  }

  /** TEMPORARY: replace CDN calendar.render until version bump. */
  function patchCalendarRender() {
    if (typeof CAD === 'undefined' || !CAD.calendar || typeof CAD.calendar.render !== 'function') return;
    if (CAD.calendar._cadStateTablesRender) return;

    CAD.calendar.render = function (container) {
      if (!container) return;
      forceRebuildFromState(container);
    };
    CAD.calendar._cadStateTablesRender = true;
  }

  /** TEMPORARY: replace CDN ui.load until version bump (mirrors src/cad-ui.js). */
  function patchUiLoad() {
    if (typeof CAD === 'undefined' || !CAD.ui || typeof CAD.ui.load !== 'function') return;
    if (CAD.ui._cadScheduleTablesSync) return;

    CAD.ui.load = function (date) {
      var ui = this;
      var scheduleDate =
        date ||
        (CAD.State && CAD.State.get('selectedDate')) ||
        (CAD.Config && CAD.Config.get('today'));

      if (ui._loadController && typeof ui._loadController.abort === 'function') {
        ui._loadController.abort();
      }
      var controller = new AbortController();
      ui._loadController = controller;
      var seq = (ui._loadSeq = (ui._loadSeq || 0) + 1);

      CAD.State.update({
        selectedDate: scheduleDate,
        loading: true,
        error: null,
      });
      if (CAD.editor && typeof CAD.editor.clear === 'function') CAD.editor.clear();
      ui.render();

      return CAD.API.getSchedule(scheduleDate, { signal: controller.signal })
        .then(function (result) {
          if (seq !== ui._loadSeq) return ui;
          if (result && result.success === false) {
            throw new Error(
              (result.data && result.data.message) || 'Failed to load schedule'
            );
          }
          var payload = (result && result.data) || {};
          applyScheduleTables(payload);
          CAD.State.set('appointments', payload.appointments || []);
          if (payload.staffPipeline) ui._lastStaffPipeline = payload.staffPipeline;
          return ui;
        })
        .catch(function (err) {
          if (isAbortError(err) || seq !== ui._loadSeq) return ui;
          CAD.State.set('error', (err && err.message) || String(err));
          CAD.State.set('appointments', []);
          return ui;
        })
        .then(function () {
          if (seq === ui._loadSeq) {
            CAD.State.set('loading', false);
            // Ensure calendar patch is applied before paint (CDN load order).
            patchCalendarRender();
            ui.render();
            if (typeof ui.reportStaffPipeline === 'function') ui.reportStaffPipeline();
          }
          return ui;
        });
    };

    CAD.ui._cadScheduleTablesSync = true;
  }

  function patch() {
    patchCalendarRender();
    patchUiLoad();
  }

  patch();
  document.addEventListener('DOMContentLoaded', patch);
})();
JS;
}

/**
 * Inline diagnostics runner — works even when CDN assets predate StaffPipelineReport.
 *
 * @return string
 */
function cad_scheduler_staff_pipeline_inline_js() {
	return <<<'JS'
(function () {
  function show(summary) {
    try { console.warn(summary); } catch (e) {}
    var mount = document.getElementById('cad-scheduler') || document.querySelector('.cad-scheduler-mount');
    if (!mount || !mount.parentNode) return;
    var panel = document.getElementById('cad-staff-pipeline-report');
    if (!panel) {
      panel = document.createElement('pre');
      panel.id = 'cad-staff-pipeline-report';
      panel.className = 'cad-staff-pipeline-report';
      panel.setAttribute('role', 'status');
      mount.parentNode.insertBefore(panel, mount.nextSibling);
    }
    panel.textContent = summary;
    panel.style.cssText = 'margin:0.75rem 0 1rem;padding:0.85rem 1rem;max-height:22rem;overflow:auto;border:1px solid #7a5c00;border-radius:6px;background:#fff8e6;color:#3d2e00;font:12px/1.45 ui-monospace,Menlo,Consolas,monospace;white-space:pre-wrap;';
  }

  function withUiCount(summary, uiCount) {
    var text = String(summary || '');
    if (/UI columns:\s+\(pending browser\)/.test(text)) {
      return text.replace(/UI columns:\s+\(pending browser\)/, 'UI columns:       ' + uiCount);
    }
    if (/UI columns:\s+\d+/.test(text)) {
      return text.replace(/UI columns:\s+\d+/, 'UI columns:       ' + uiCount);
    }
    return text + '\nUI columns:       ' + uiCount;
  }

  function finalizeMismatch(summary, ajaxCount, uiCount) {
    var text = withUiCount(summary, uiCount);
    if (ajaxCount != null && uiCount !== ajaxCount && text.indexOf('Mismatch detected between AJAX and UI') === -1) {
      text += '\n\nMismatch detected between AJAX and UI.';
    } else if (ajaxCount != null && uiCount === ajaxCount && text.indexOf('No backend mismatch') !== -1) {
      text += '\nAJAX and UI column counts match.';
    }
    return text;
  }

  function run() {
    var cfg = window.cadConfig || {};
    if (!cfg.diagnostics || !cfg.ajaxUrl || !cfg.nonce) return;
    var body = new FormData();
    body.append('action', 'cad_debug_staff_pipeline');
    body.append('nonce', cfg.nonce);
    fetch(String(cfg.ajaxUrl), { method: 'POST', credentials: 'same-origin', body: body })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (!json || !json.success) {
          show('CAD staff pipeline report failed: ' + JSON.stringify(json && json.data));
          return;
        }
        var data = json.data || {};
        var summary = data.summary || (data.report && data.report.summary) || '';
        var counts = data.counts || (data.report && data.report.counts) || {};
        var uiCount = document.querySelectorAll('.cad-matrix__table-label').length;
        var ajaxCount = counts.ajax != null ? Number(counts.ajax) : null;
        show(finalizeMismatch(summary, ajaxCount, uiCount));
      })
      .catch(function (err) {
        show('CAD staff pipeline report error: ' + (err && err.message ? err.message : String(err)));
      });
  }

  document.addEventListener('DOMContentLoaded', function () {
    // Wait for schedule render so UI column count is meaningful.
    setTimeout(run, 2000);
  });
})();
JS;
}

function cad_maybe_enqueue() {
	if ( ! cad_scheduler_ready() || ! is_singular() ) {
		return;
	}
	$post = get_post();
	if ( $post && has_shortcode( $post->post_content, 'cad_scheduler' ) ) {
		cad_enqueue_assets();
	}
}
add_action( 'wp_enqueue_scripts', 'cad_maybe_enqueue' );

function cad_scheduler_shortcode() {
	$issues = cad_scheduler_health();

	if ( cad_scheduler_has_blocking_issues( $issues ) ) {
		return cad_scheduler_render_diagnostics( $issues );
	}

	cad_enqueue_assets();

	$html = '<div id="cad-scheduler" class="cad-scheduler-mount"></div>';

	if ( cad_scheduler_diagnostics_enabled() ) {
		$html .= cad_scheduler_render_diagnostics( $issues );
	}

	return $html;
}
add_shortcode( 'cad_scheduler', 'cad_scheduler_shortcode' );

function cad_ajax_get_schedule() {
	check_ajax_referer( 'cad_scheduler', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'Authentication required.' ), 403 );
	}

	$capability = apply_filters( 'cad_scheduler_get_schedule_capability', 'read' );
	if ( ! current_user_can( $capability ) ) {
		wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
	}

	$provider = cad_schedule_provider();
	if ( ! $provider ) {
		wp_send_json_error(
			array(
				'message' => 'CAD modules not loaded.',
				'health'  => cad_scheduler_health_for_config(),
			),
			500
		);
	}
	$date = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		wp_send_json_error( array( 'message' => 'Invalid date.' ), 400 );
	}
	wp_send_json_success( $provider->get_schedule( $date ) );
}
add_action( 'wp_ajax_cad_get_schedule', 'cad_ajax_get_schedule' );

/**
 * Staff → tables pipeline dump (Repository → Mapper → Provider).
 * Enable with: add_filter( 'cad_scheduler_diagnostics_enabled', '__return_true' );
 */
function cad_ajax_debug_staff_pipeline() {
	check_ajax_referer( 'cad_scheduler', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'Authentication required.' ), 403 );
	}

	if ( ! cad_scheduler_diagnostics_enabled() && ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error(
			array(
				'message' => 'Enable cad_scheduler_diagnostics_enabled or use an administrator account.',
			),
			403
		);
	}

	$provider = cad_schedule_provider();
	if ( ! $provider ) {
		wp_send_json_error( array( 'message' => 'CAD modules not loaded.' ), 500 );
	}

	$pipeline = $provider->debug_tables_pipeline();
	$pipeline['cadConfigTables'] = $provider->get_tables();

	// Put the human summary first for Network tab / console readability.
	$response = array(
		'summary'      => isset( $pipeline['summary'] ) ? $pipeline['summary'] : '',
		'failingLayer' => isset( $pipeline['failingLayer'] ) ? $pipeline['failingLayer'] : 'none',
		'counts'       => isset( $pipeline['counts'] ) ? $pipeline['counts'] : array(),
		'report'       => $pipeline,
	);

	error_log( "[CAD staffPipeline]\n" . (string) $response['summary'] ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

	wp_send_json_success( $response );
}
add_action( 'wp_ajax_cad_debug_staff_pipeline', 'cad_ajax_debug_staff_pipeline' );

/**
 * Allowed Bookly custom status slugs for CAD popover actions.
 *
 * @return string[]
 */
function cad_scheduler_allowed_appointment_statuses() {
	$statuses = array(
		'approved',
		'confirmed',
		'deposit-paid',
		'arrived',
		'paid',
		'no-show',
		'noshow',
	);
	/**
	 * Filter allowed status slugs for cad_update_appointment_status.
	 *
	 * @param string[] $statuses
	 */
	$filtered = apply_filters( 'cad_scheduler_allowed_appointment_statuses', $statuses );
	return is_array( $filtered ) ? $filtered : $statuses;
}

function cad_ajax_update_appointment_status() {
	check_ajax_referer( 'cad_scheduler', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'Authentication required.' ), 403 );
	}

	$capability = apply_filters( 'cad_scheduler_update_status_capability', 'edit_posts' );
	if ( ! current_user_can( $capability ) ) {
		wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
	}

	$provider = cad_schedule_provider();
	if ( ! $provider ) {
		wp_send_json_error( array( 'message' => 'CAD modules not loaded.' ), 500 );
	}

	$appointment_id = sanitize_text_field( wp_unslash( $_POST['appointment_id'] ?? '' ) );
	$status         = sanitize_text_field( wp_unslash( $_POST['status'] ?? '' ) );
	$status         = strtolower( str_replace( array( ' ', '_' ), '-', $status ) );

	if ( '' === $appointment_id ) {
		wp_send_json_error( array( 'message' => 'Invalid appointment.' ), 400 );
	}

	$allowed = array_map( 'strval', cad_scheduler_allowed_appointment_statuses() );
	if ( ! in_array( $status, $allowed, true ) ) {
		wp_send_json_error( array( 'message' => 'Invalid status.' ), 400 );
	}

	$ok = $provider->update_appointment_status( $appointment_id, $status );
	if ( ! $ok ) {
		wp_send_json_error( array( 'message' => 'Could not update status.' ), 500 );
	}

	wp_send_json_success(
		array(
			'appointment_id' => $appointment_id,
			'status'         => $status,
		)
	);
}
add_action( 'wp_ajax_cad_update_appointment_status', 'cad_ajax_update_appointment_status' );

function cad_ajax_update_appointment() {
	check_ajax_referer( 'cad_scheduler', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'Authentication required.', 'code' => 'auth' ), 403 );
	}

	$capability = apply_filters( 'cad_scheduler_update_appointment_capability', 'edit_posts' );
	if ( ! current_user_can( $capability ) ) {
		wp_send_json_error( array( 'message' => 'Insufficient permissions.', 'code' => 'capability' ), 403 );
	}

	$provider = cad_schedule_provider();
	if ( ! $provider ) {
		wp_send_json_error( array( 'message' => 'CAD modules not loaded.', 'code' => 'modules' ), 500 );
	}

	$appointment_id = sanitize_text_field( wp_unslash( $_POST['appointment_id'] ?? '' ) );
	$staff_id       = sanitize_text_field( wp_unslash( $_POST['staff_id'] ?? $_POST['table_id'] ?? '' ) );
	$start_date     = sanitize_text_field( wp_unslash( $_POST['start'] ?? $_POST['start_date'] ?? '' ) );
	$end_raw        = sanitize_text_field( wp_unslash( $_POST['end'] ?? $_POST['end_date'] ?? '' ) );
	$end_date       = '' !== $end_raw ? $end_raw : null;

	if ( '' === $appointment_id || '' === $staff_id || '' === $start_date ) {
		wp_send_json_error(
			array(
				'message' => 'appointment_id, staff_id (or table_id), and start are required.',
				'code'    => 'invalid_params',
			),
			400
		);
	}

	$result = $provider->update_appointment( $appointment_id, $staff_id, $start_date, $end_date );
	if ( empty( $result['ok'] ) ) {
		$code = isset( $result['code'] ) ? (string) $result['code'] : 'save_failed';
		$status = ( 'conflict' === $code ) ? 409 : 400;
		if ( in_array( $code, array( 'bookly_unavailable', 'save_failed' ), true ) ) {
			$status = 500;
		}
		wp_send_json_error( $result, $status );
	}

	wp_send_json_success( $result );
}
add_action( 'wp_ajax_cad_update_appointment', 'cad_ajax_update_appointment' );
