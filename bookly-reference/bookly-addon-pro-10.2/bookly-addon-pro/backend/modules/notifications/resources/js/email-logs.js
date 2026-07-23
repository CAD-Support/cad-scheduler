jQuery(function ($) {
    'use strict';

    $(document.body).on('bookly.init_email_logs', function () {
        const $modal = $('#bookly-email-logs-dialog');
        const $to = $('#bookly-email-to', $modal);
        const $subject = $('#bookly-email-subject', $modal);
        const $body = $('#bookly-email-body', $modal);
        const $headers = $('#bookly-email-headers', $modal);
        const $attachments = $('#bookly-email-attachments', $modal);

        const { today, getLocalTimeZone, parseDate } = window.BooklyDatatables.calendarDate;
        const tz = getLocalTimeZone();
        const t = today(tz);
        const startOfMonth = d => d.set({ day: 1 });
        const endOfMonth = d => d.set({ day: 1 }).add({ months: 1 }).subtract({ days: 1 });
        const ymd = d => d.year + '-' + String(d.month).padStart(2, '0') + '-' + String(d.day).padStart(2, '0');

        const datePresets = [
            { label: BooklyEmailLogsL10n.dateRange.yesterday, range: { start: t.subtract({ days: 1 }),  end: t.subtract({ days: 1 }) } },
            { label: BooklyEmailLogsL10n.dateRange.today,     range: { start: t,                        end: t                       } },
            { label: BooklyEmailLogsL10n.dateRange.last_7,    range: { start: t.subtract({ days: 7 }),  end: t                       } },
            { label: BooklyEmailLogsL10n.dateRange.last_30,   range: { start: t.subtract({ days: 30 }), end: t                       } },
            { label: BooklyEmailLogsL10n.dateRange.thisMonth, range: { start: startOfMonth(t),                                  end: endOfMonth(t)                                 } },
            { label: BooklyEmailLogsL10n.dateRange.lastMonth, range: { start: startOfMonth(t.subtract({ months: 1 })),          end: endOfMonth(t.subtract({ months: 1 }))         } },
        ];

        let dateValue;
        const savedFilter = BooklyEmailLogsL10n.datatables.email_logs.settings.filter || {};
        if (savedFilter.range && savedFilter.range !== 'any') {
            const parts = String(savedFilter.range).split(' - ');
            if (parts.length === 2) {
                try { dateValue = { start: parseDate(parts[0].trim()), end: parseDate(parts[1].trim()) }; } catch (e) { /* ignore */ }
            }
        }
        function serializeDate(value) {
            if (!value || !value.start || !value.end) return 'any';
            return ymd(value.start) + ' - ' + ymd(value.end);
        }

        const columns = [];
        // Backend (Ajax::getEmailLogs) searches by e.to, e.subject, e.body —
        // only `to` and `subject` are visible columns; id/created_at aren't searchable.
        const searchableColumns = ['to', 'subject'];
        $.each(BooklyEmailLogsL10n.datatables.email_logs.settings.columns, function (column, show) {
            columns.push({
                data: column,
                name: column,
                show: show,
                searchable: searchableColumns.indexOf(column) !== -1,
                title: BooklyEmailLogsL10n.datatables.email_logs.titles[column] || column,
                render: function (data) { return BooklyDatatables.escapeHtml(data); }
            });
        });

        let bt;
        bt = BooklyDatatables.showForm('bookly-email-logs-datatables', {
            datePicker: BooklyEmailLogsL10n.datePicker,
            ajax: {
                url: ajaxurl,
                method: 'POST',
                data: function (d) {
                    return $.extend({
                        action: 'bookly_pro_get_email_logs',
                        csrf_token: BooklyL10nGlobal.csrf_token,
                        filter: { range: serializeDate(dateValue) }
                    }, d);
                }
            },
            columns: columns,
            tableSettings: Object.assign({}, BooklyEmailLogsL10n.datatables.email_logs, { l10n: Object.assign({}, BooklyEmailLogsL10n.datatables.l10n, { zeroRecords: BooklyEmailLogsL10n.zeroRecords }) }),
            edit: function (row) {
                $to.val(row.to);
                $body.val(row.body);
                $subject.val(row.subject);
                $headers.val(JSON.stringify(row.headers, null, 4));
                $attachments.val(row.attach.join('\n'));
                $modal.booklyModal('show');
            },
            checked: function () {
                return [{
                    label: BooklyEmailLogsL10n.delete,
                    icon: 'trash',
                    variant: 'destructive',
                    click: function (selected) {
                        if (!confirm(BooklyL10n.areYouSure)) return;
                        bt.setLoading(true);
                        $.ajax({
                            url: ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'bookly_pro_delete_email_logs',
                                csrf_token: BooklyL10nGlobal.csrf_token,
                                data: selected.map(function (row) { return row.id; })
                            },
                            dataType: 'json',
                            success: function (response) {
                                if (response.success) {
                                    bt.reload();
                                } else {
                                    alert(response.data.message);
                                }
                                bt.setLoading(false);
                            }
                        });
                    }
                }];
            },
            filters: [{
                type: 'dateRange',
                name: 'date',
                label: BooklyEmailLogsL10n.filters.date,
                initialValue: dateValue,
                presets: datePresets,
                onChange: function (v) { dateValue = v; },
            }],
            searchFilter: { placeholder: BooklyEmailLogsL10n.search, name: 'filter[search]' }
        });

        $('#bookly_email_logs_expire').change(function () {
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'bookly_pro_set_email_logs_expire',
                    expire: $(this).val(),
                    csrf_token: BooklyL10nGlobal.csrf_token,
                },
                dataType: 'json'
            });
        });
    });
});
