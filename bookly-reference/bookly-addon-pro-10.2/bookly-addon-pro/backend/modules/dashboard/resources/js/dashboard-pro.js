jQuery(function ($) {
    'use strict';

    const table = 'analytics';
    const ds = BooklyAnalyticsL10n.datatables[table];
    const dtl10n = BooklyAnalyticsL10n.datatables.l10n;

    const num = function (data) { return data == null ? '0' : data; };
    const dataMap = {
        staff:                    { data: 'staff',                   class: 'bookly:text-left',  render: function (d) { return BooklyDatatables.escapeHtml(d); } },
        service:                  { data: 'service',                 class: 'bookly:text-left',  render: function (d) { return BooklyDatatables.escapeHtml(d); } },
        appointments_total:       { data: 'appointments.total',      class: 'bookly:text-right', render: num },
        appointments_approved:    { data: 'appointments.approved',   class: 'bookly:text-right', render: num },
        appointments_pending:     { data: 'appointments.pending',    class: 'bookly:text-right', render: num },
        appointments_cancelled:   { data: 'appointments.cancelled',  class: 'bookly:text-right', render: num },
        appointments_rejected:    { data: 'appointments.rejected',   class: 'bookly:text-right', render: num },
        appointments_waitlisted:  { data: 'appointments.waitlisted', class: 'bookly:text-right', render: num },
        customers_total:          { data: 'customers.total',         class: 'bookly:text-right', render: num },
        customers_new:            { data: 'customers.new',           class: 'bookly:text-right', render: num },
        revenue:                  { data: 'revenue.total',           class: 'bookly:text-right', render: function (d, type, row) { return BooklyDatatables.escapeHtml(row.revenue.total_formatted); } },
    };

    const columns = [];
    $.each(ds.settings.columns, function (key, show) {
        const def = dataMap[key];
        if (!def) return;
        columns.push($.extend({ name: key, show: show, title: ds.titles[key] || key }, def));
    });

    // The analytics rows arrive with the rest of the page from the combined dashboard
    // endpoint (bookly_get_dashboard_data) — this table never fetches on its own. It
    // mounts in deferLoad mode and is fed via bt.setData() on the `bookly.dashboard.data`
    // event (see the listener at the bottom).
    let bt;

    // tableSettings carries per-table settings/titles/defaults + merged l10n. Default sort: revenue desc.
    const tableSettings = Object.assign({}, ds, {
        l10n: Object.assign({}, dtl10n, {
            zeroRecords: BooklyAnalyticsL10n.zeroRecords,
            emptyTable: BooklyAnalyticsL10n.emptyTable
        })
    });
    if (!tableSettings.settings.order || !tableSettings.settings.order.length) {
        tableSettings.settings = Object.assign({}, tableSettings.settings, {
            order: [{ column: 'revenue.total', order: 'desc' }]
        });
    }

    // Export the currently loaded (filtered) rows to CSV — the report is client-side
    // so getRows() already holds the full filtered set.
    function exportCsv() {
        const cols = bt.getColumns().filter(function (c) { return c.show && !c.parent; });
        const rows = bt.getRows();
        const get = function (row, path) { return path.split('.').reduce(function (o, k) { return o == null ? undefined : o[k]; }, row); };
        const cell = function (row, c) { return c.name === 'revenue' ? ((row.revenue && row.revenue.total_formatted) || '') : (get(row, c.data) != null ? get(row, c.data) : ''); };
        const esc = function (v) { return '"' + String(v).replace(/"/g, '""') + '"'; };
        const lines = [cols.map(function (c) { return esc(c.title); }).join(',')];
        rows.forEach(function (r) { lines.push(cols.map(function (c) { return esc(cell(r, c)); }).join(',')); });
        const blob = new Blob(["﻿" + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'analytics.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(a.href);
    }

    bt = BooklyDatatables.showForm('bookly-analytics-datatables', {
        serverSide: false,           // analytics returns the whole staff×service set at once
        deferLoad: true,             // fed via bt.setData() from the combined dashboard endpoint
        ajax: { dataSrc: 'data' },   // only dataSrc is read (setData path); no request is made
        reloadButton: false,         // owns no endpoint — refreshing the whole dashboard is the filter bar's job
        columns: columns,
        tableSettings: tableSettings,
        noCheckboxes: true,          // read-only report — no selection / row actions
        searchFilter: { placeholder: BooklyAnalyticsL10n.search, name: 'filter[search]' },
        topToolbar: [
            { id: 'bookly-analytics-export', label: BooklyAnalyticsL10n.exportCsv, icon: 'download', variant: 'outline', click: exportCsv },
            { id: 'bookly-analytics-print', label: BooklyAnalyticsL10n.print, icon: 'printer', variant: 'outline', click: function () { bt.print(bt.getColumns().map(function (c, i) { return i; })); } }
        ],
        renderTotal: function (total) {
            return {
                staff:                    '<strong>' + BooklyAnalyticsL10n.total + '</strong>',
                appointments_total:       total.appointments.total,
                appointments_approved:    total.appointments.approved,
                appointments_pending:     total.appointments.pending,
                appointments_cancelled:   total.appointments.cancelled,
                appointments_rejected:    total.appointments.rejected,
                appointments_waitlisted:  total.appointments.waitlisted,
                customers_total:          total.customers.total,
                customers_new:            total.customers.new,
                revenue:                  BooklyDatatables.escapeHtml(total.revenue.total_formatted)
            };
        },
        saveSettings: function (settings) {
            $.post(ajaxurl, Object.assign({
                action: 'bookly_update_table_settings',
                table: table,
                csrf_token: BooklyL10nGlobal.csrf_token
            }, settings));
        }
    });

    // The combined dashboard endpoint delivers the analytics rows for the whole page in
    // one round-trip. Dim while a new request is in flight, then push the rows in — no fetch.
    document.body.addEventListener('bookly.dashboard.loading', function () {
        if (bt) bt.setLoading(true);
    });
    document.body.addEventListener('bookly.dashboard.data', function (e) {
        if (bt) bt.setData((e.detail || {}).analytics || {});
    });
});
