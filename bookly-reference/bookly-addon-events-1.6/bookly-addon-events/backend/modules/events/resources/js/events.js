jQuery(function ($) {
    'use strict';

    const D = window.BooklyDatatables;

    const table = 'events';
    const $eventsList = $('#bookly-' + table + '-datatables');

    // ── Date filter ───────────────────────────────────────────────────
    // Default preset set + texts from the shared catalogue (no local date math).
    const datePresets = D.datePresets({ labels: BooklyL10nGlobal.dateRange });
    const thisMonthRange = () => datePresets.find(p => p.label === BooklyL10nGlobal.dateRange.thisMonth)?.range;

    // Default to current month; restore from saved filter if present.
    let dateValue = thisMonthRange();
    let publishedValue = [];
    const savedFilter = BooklyEventsL10n.datatables[table].settings.filter || {};
    if (savedFilter.date === 'any') {
        dateValue = undefined;
    } else if (savedFilter.date) {
        const parsed = D.parseRange(savedFilter.date);
        if (parsed) dateValue = parsed;
    }
    if (Array.isArray(savedFilter.published) && savedFilter.published.length > 0) {
        publishedValue = savedFilter.published.map(String);
    }

    // ── Tag color lookup ──────────────────────────────────────────────
    function tagColor(tag) {
        let color = '#000';
        if (BooklyEventsL10n.tagsData !== undefined) {
            const _tag = BooklyEventsL10n.tagsData.list.find(t => t.tag.toLowerCase() === tag.toLowerCase());
            if (_tag) color = BooklyEventsL10n.tagsData.colors[_tag.color_id];
        }
        return color;
    }

    // ── Columns ───────────────────────────────────────────────────────
    let columns = [
        // Color dot column.
        {
            data: 'color',
            name: '__color_dot',
            permanent: true,
            show: true,
            orderable: false,
            searchable: false,
            class: 'bookly:w-8',
            render: function (data) {
                return '<i class="fas fa-fw fa-circle" style="color:' + data + ';"></i>';
            },
        },
    ];

    const statusBadgeClass = {
        1: 'bookly:bg-green-100 bookly:text-green-800 bookly:border-green-200',
        0: 'bookly:bg-gray-100 bookly:text-gray-700 bookly:border-gray-200',
    };

    // Backend (Ajax::getEvents) searches by e.id, e.title, e.tags only —
    // every other column must opt out of the quick-search highlight.
    const searchableColumns = ['id', 'title', 'tags'];

    $.each(BooklyEventsL10n.datatables[table].settings.columns, function (column, show) {
        switch (column) {
            case 'title':
                columns.push({
                    data: 'title',
                    render: function (data, type, row) {
                        const title = BooklyDatatables.escapeHtml(data);
                        if (row.image) {
                            return '<span class="bookly:inline-flex bookly:items-center bookly:gap-2 bookly:align-middle"><img class="bookly:datatable-thumb" src="' + row.image + '"/>' + title + '</span>';
                        }
                        return title;
                    },
                });
                break;
            case 'start_date':
                columns.push({
                    data: 'start_date',
                    secondary: row => row.start_time || '',
                });
                break;
            case 'end_date':
                columns.push({
                    data: 'end_date',
                    secondary: row => row.end_time || '',
                });
                break;
            case 'published':
                columns.push({
                    data: 'published',
                    render: data => data ? BooklyEventsL10n.status.published : BooklyEventsL10n.status.draft,
                    badge: row => statusBadgeClass[row.published] || statusBadgeClass[0],
                });
                break;
            case 'tags':
                columns.push({
                    data: 'tags',
                    render: function (data) {
                        if (!data) return '';
                        let text = '<div class="bookly:flex bookly:flex-wrap bookly:gap-1">';
                        JSON.parse(data).forEach(function (tag) {
                            text += '<span class="badge p-2 text-white" style="background-color: ' + tagColor(tag) + '">' + BooklyDatatables.escapeHtml(tag) + '</span>';
                        });
                        return text + '</div>';
                    },
                });
                break;
            default:
                columns.push({ data: column, render: BooklyDatatables.escapeHtml() });
                break;
        }
        columns[columns.length - 1].title      = BooklyEventsL10n.datatables[table].titles[column] || column;
        columns[columns.length - 1].name       = column;
        columns[columns.length - 1].show       = show;
        columns[columns.length - 1].searchable = searchableColumns.indexOf(column) !== -1;
    });

    // ── Init datatable ────────────────────────────────────────────────
    const bt = BooklyDatatables.showForm('bookly-' + table + '-datatables', {
        summary: true,
        datePicker: BooklyEventsL10n.datePicker,
        ajax: {
            url: ajaxurl,
            method: 'POST',
            data: function (d) {
                return $.extend({
                    action: 'bookly_events_get_events',
                    csrf_token: BooklyL10nGlobal.csrf_token,
                    filter: { date: D.serializeRange(dateValue), published: publishedValue },
                }, d);
            },
        },
        columns: columns,
        tableSettings: Object.assign({}, BooklyEventsL10n.datatables[table], {
            l10n: Object.assign({}, BooklyEventsL10n.datatables.l10n, { zeroRecords: BooklyEventsL10n.zeroRecords }),
        }),
        // Row click opens the preview hub (glance + delegated actions), not the
        // edit form directly — see openEventPreview.
        edit: function (row) {
            openEventPreview(row.id);
        },
        rowActions: function (row) {
            return [
                {
                    label: BooklyEventsL10n.edit,
                    icon: 'pencil',
                    variant: 'outline',
                    click: function (r) { openEventDialog(r.id); },
                },
                {
                    label: BooklyEventsL10n.tickets,
                    icon: 'ticket',
                    variant: 'outline',
                    click: function (r) { openTickets(r.id); },
                },
                {
                    label: BooklyEventsL10n.attendees,
                    icon: 'users',
                    variant: 'outline',
                    click: function (r) { openAttendees(r.id); },
                },
            ];
        },
        checked: function () {
            return [{
                label: BooklyEventsL10n.delete,
                icon: 'trash',
                variant: 'destructive',
                click: function (selected) { confirmDelete(selected.map(r => r.id)); },
            }];
        },
        filters: [
            {
                type: 'dateRange',
                name: 'date',
                label: BooklyEventsL10n.filters.date,
                initialValue: dateValue,
                presets: datePresets,
                onChange: function (v) { dateValue = v; },
            },
            {
                type: 'checkboxGroup',
                name: 'published',
                label: BooklyEventsL10n.filters.status,
                initialValue: publishedValue,
                options: [
                    { value: '1', label: BooklyEventsL10n.status.published },
                    { value: '0', label: BooklyEventsL10n.status.draft },
                ],
                onChange: function (v) { publishedValue = v; },
            },
        ],
        searchFilter: {
            placeholder: BooklyEventsL10n.search,
            name: 'filter[search]',
        },
        saveSettings: function (settings) {
            $.post(ajaxurl, Object.assign({
                action: 'bookly_update_table_settings',
                table: table,
                csrf_token: BooklyL10nGlobal.csrf_token,
            }, settings));
        },
        topToolbar: [
            {
                id: 'bookly-new-event',
                label: BooklyEventsL10n.new_event,
                icon: 'plus',
                variant: 'default',
                click: function () {
                    openEventDialog(null);
                },
            },
        ],
    });

    // ── Ticket types / Attendees openers (shared by the preview hub and the
    //    per-row actions). ────────────────────────────────────────────
    function openTickets(eventId) {
        BooklyTicketTypeDialog.showForm(
            getBooklyModalContainer('bookly-ticket-type-dialog'),
            { eventId: eventId, onDone: function () { bt.reload(); } }
        );
    }

    function openAttendees(eventId) {
        BooklyAttendeesDialog.showForm(
            getBooklyModalContainer('bookly-attendees-dialog'),
            { eventId: eventId, onDone: function () { bt.reload(); } }
        );
    }

    // ── Event preview hub (Svelte 5 — BooklyEventDialog.showPreview) ───
    // Glance overview of one event with delegated actions. The hub closes itself
    // before each action runs (see EventPreview), so the opened dialog replaces it.
    function openEventPreview(eventId) {
        BooklyEventDialog.showPreview(
            getBooklyModalContainer('bookly-event-preview'),
            {
                eventId: eventId,
                onEdit: function () { openEventDialog(eventId); },
                onTickets: function () { openTickets(eventId); },
                onAttendees: function () { openAttendees(eventId); },
                onDelete: function () { confirmDelete([eventId]); },
            }
        );
    }

    // ── Event dialog (Svelte 5 — BooklyEventDialog) ───────────────────
    // Opens the create/edit modal. `eventId === null` → new event.
    function openEventDialog(eventId) {
        BooklyEventDialog.showForm(
            getBooklyModalContainer('bookly-event-dialog'),
            {
                eventId: eventId,
                onDone: function (result) {
                    if (result && result.data && result.data.new_tags && result.data.new_tags.length) {
                        BooklyEventsL10n.tagsData.list = BooklyEventsL10n.tagsData.list.concat(result.data.new_tags);
                        // Keep the dialog's own tag source (BooklyL10nEventDialog.tagsList — a
                        // separate global it clones on open) in sync too, so re-opening an event
                        // in the same page session resolves a freshly-created tag's colour
                        // instead of falling back to the default. Without this the two tag lists
                        // drift apart until a full page refresh re-localizes them.
                        if (window.BooklyL10nEventDialog && Array.isArray(window.BooklyL10nEventDialog.tagsList)) {
                            window.BooklyL10nEventDialog.tagsList = window.BooklyL10nEventDialog.tagsList.concat(result.data.new_tags);
                        }
                    }
                    bt.reload();
                },
            }
        );
    }

    // ── Delete confirm ────────────────────────────────────────────────
    function confirmDelete(ids) {
        booklyModal(BooklyL10nGlobal.l10n.areYouSure, null, BooklyEventsL10n.cancel, BooklyEventsL10n.yes)
            .on('bs.click.main.button', function (event, modal, mainButton) {
                const ladda = Ladda.create(mainButton);
                ladda.start();
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bookly_events_delete_events',
                        csrf_token: BooklyL10nGlobal.csrf_token,
                        data: ids,
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            bt.reload();
                        } else {
                            booklyAlert({ error: [response.data.message] });
                        }
                        ladda.stop();
                        modal.booklyModal('hide');
                        if (response.data.queue && response.data.queue.all.length) {
                            BooklyNotificationsQueueDialog.showDialog({ queue: response.data.queue });
                        }
                    },
                });
            });
    }
});
