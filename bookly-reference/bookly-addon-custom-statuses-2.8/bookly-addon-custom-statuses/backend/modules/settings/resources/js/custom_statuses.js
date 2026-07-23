jQuery(function ($) {
    'use strict';

    const table = 'custom_statuses';
    const $modal = $('#bookly-custom-statuses-modal');
    const $name = $('#bookly-custom-statuses-name', $modal);
    const $busy = $('#bookly-custom-statuses-busy', $modal);
    const $newTitle = $('#bookly-custom-statuses-new-title', $modal);
    const $editTitle = $('#bookly-custom-statuses-edit-title', $modal);
    const $saveButton = $('#bookly-custom-statuses-save', $modal);
    const $defaultStatus = $('#bookly_gen_default_appointment_status');

    // id of the status being edited; null => creating a new one.
    let editId = null;
    let bt = null;

    /**
     * Init columns.
     */
    let columns = [];
    $.each(BooklyCustomStatusesL10n.datatables[table].settings.columns, function (column, show) {
        switch (column) {
            case 'busy':
                columns.push({
                    data: 'busy',
                    badge: function (row) {
                        return row.busy == 1
                            ? 'bookly:bg-amber-100 bookly:text-amber-800 bookly:border-amber-200'
                            : 'bookly:bg-green-100 bookly:text-green-800 bookly:border-green-200';
                    },
                    render: function (data) {
                        return data == 1 ? BooklyCustomStatusesL10n.busy : BooklyCustomStatusesL10n.free;
                    }
                });
                break;
            default:
                columns.push({ data: column, render: function (data) { return BooklyDatatables.escapeHtml(data); } });
                break;
        }
        columns[columns.length - 1].title = BooklyCustomStatusesL10n.datatables[table].titles[column] || column;
        columns[columns.length - 1].name = column;
        columns[columns.length - 1].show = show;
    });

    /**
     * Init the datatable. Lazy — the tab is hidden until shown, so mounting only
     * when visible lets the table measure column widths correctly.
     */
    function initTable() {
        if (bt) {
            return;
        }
        bt = BooklyDatatables.showForm('bookly-' + table + '-datatables', {
            serverSide: false,
            ajax: {
                url: ajaxurl,
                method: 'POST',
                data: function (d) {
                    return $.extend({
                        action: 'bookly_custom_statuses_get_statuses',
                        csrf_token: BooklyL10nGlobal.csrf_token
                    }, d);
                }
            },
            columns: columns,
            tableSettings: Object.assign({}, BooklyCustomStatusesL10n.datatables[table], {
                l10n: Object.assign({}, BooklyCustomStatusesL10n.datatables.l10n, { zeroRecords: BooklyCustomStatusesL10n.zeroRecords })
            }),
            edit: function (row) { openModal(row); },
            checked: function () {
                return [{
                    label: BooklyCustomStatusesL10n.delete,
                    icon: 'trash',
                    variant: 'destructive',
                    click: function (selected) { deleteStatuses(selected); }
                }];
            },
            searchFilter: {
                placeholder: BooklyCustomStatusesL10n.search,
                name: 'filter[search]'
            },
            saveSettings: function (settings) {
                $.post(ajaxurl, Object.assign({
                    action: 'bookly_update_table_settings',
                    table: table,
                    csrf_token: BooklyL10nGlobal.csrf_token
                }, settings));
            },
            topToolbar: [{
                label: BooklyCustomStatusesL10n.order,
                icon: 'list-ordered',
                variant: 'outline',
                click: openReorder
            }, {
                id: 'bookly-add-status',
                label: BooklyCustomStatusesL10n.add_status,
                icon: 'plus',
                variant: 'default',
                click: function () { openModal(null); }
            }]
        });
    }

    // Init now if the tab is already visible, otherwise on first show.
    if ($('#bookly_settings_custom_statuses').is(':visible')) {
        initTable();
    }
    $('a[href="#bookly_settings_custom_statuses"]').on('shown.bs.tab', initTable);

    /**
     * Open create/edit modal. row === null => new status.
     */
    function openModal(row) {
        editId = row ? row.id : null;
        $newTitle.toggle(!row);
        $editTitle.toggle(!!row);
        $name.val(row ? row.name : '');
        $busy.val(row ? row.busy : 1);
        $modal.booklyModal('show');
    }

    /**
     * Save status.
     */
    $saveButton.on('click', function (e) {
        e.preventDefault();
        let data = $(this).closest('form').serializeArray();
        data.push({ name: 'action', value: 'bookly_custom_statuses_save_status' });
        if (editId) {
            data.push({ name: 'id', value: editId });
        }
        let ladda = Ladda.create(this, { timeout: 2000 });
        ladda.start();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function (response) {
                ladda.stop();
                if (response.success) {
                    $modal.booklyModal('hide');
                    bt.reload();
                    // Keep the "default appointment status" select (Calendar settings) in sync.
                    if ($defaultStatus.length && $('option[value="' + response.data.slug + '"]', $defaultStatus).length === 0) {
                        $defaultStatus.append($('<option/>', { value: response.data.slug, text: response.data.name }));
                    }
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    /**
     * Delete the given statuses.
     */
    function deleteStatuses(selected) {
        if (!confirm(BooklyCustomStatusesL10n.areYouSure)) {
            return;
        }
        const ids = selected.map(function (r) { return r.id; });
        bt.setLoading(true);
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'bookly_custom_statuses_delete_statuses',
                csrf_token: BooklyL10nGlobal.csrf_token,
                ids: ids
            },
            success: function (response) {
                if (response.success) {
                    // Remove the deleted statuses from the default-status select.
                    if ($defaultStatus.length) {
                        selected.forEach(function (r) {
                            $('option[value="' + r.slug + '"]', $defaultStatus).remove();
                        });
                    }
                    bt.reload();
                } else {
                    bt.setLoading(false);
                    alert(response.data.message);
                }
            }
        });
    }

    /**
     * Reorder dialog. Items are taken in position order.
     */
    function openReorder() {
        const items = bt.getRows()
            .slice()
            .sort(function (a, b) { return a.position - b.position; })
            .map(function (row) { return { id: row.id, label: row.name || '' }; });

        BooklyDatatables.showReorder({
            title: BooklyCustomStatusesL10n.order_title,
            hint: BooklyCustomStatusesL10n.order_hint,
            items: items,
            saveLabel: BooklyCustomStatusesL10n.datatables.l10n.save,
            cancelLabel: BooklyCustomStatusesL10n.datatables.l10n.cancel,
            onSave: function (orderedIds) {
                return $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'bookly_custom_statuses_update_statuses_position',
                        csrf_token: BooklyL10nGlobal.csrf_token,
                        positions: orderedIds
                    }
                }).then(function () { bt.reload(); });
            }
        });
    }
});
