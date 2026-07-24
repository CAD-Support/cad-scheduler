jQuery(function ($) {
    'use strict';

    const table = 'discounts';

    const enabledBadgeClass = 'bookly:bg-green-100 bookly:text-green-800 bookly:border-green-200';
    const disabledBadgeClass = 'bookly:bg-gray-100 bookly:text-gray-700 bookly:border-gray-200';

    /**
     * Init columns.
     */
    let columns = [];

    $.each(BooklyDiscountsL10n.datatables[table].settings.columns, function (column, show) {
        switch (column) {
            case 'enabled':
                columns.push({
                    data: 'enabled',
                    render: function (data) { return BooklyDiscountsL10n.state[data]; },
                    badge: function (row) { return row.enabled == 1 ? enabledBadgeClass : disabledBadgeClass; }
                });
                break;
            case 'date_start':
                columns.push({
                    data: 'date_start',
                    render: function (data, type, row) { return BooklyDatatables.escapeHtml(row.date_start_formatted); }
                });
                break;
            case 'date_end':
                columns.push({
                    data: 'date_end',
                    render: function (data, type, row) { return BooklyDatatables.escapeHtml(row.date_end_formatted); }
                });
                break;
            case 'services':
                columns.push({
                    data: 'services',
                    orderable: false,
                    render: function (data, type, row) {
                        if (row.type !== 'nop') {
                            return '';
                        } else if (data == 0) {
                            return BooklyDiscountsL10n.services.nothingSelected;
                        } else if (data == 1) {
                            return BooklyDatatables.escapeHtml(BooklyDiscountsL10n.services.collection[row.service_id].title);
                        } else if (data == BooklyDiscountsL10n.services.count) {
                            return BooklyDiscountsL10n.services.allSelected;
                        }
                        return data + '/' + BooklyDiscountsL10n.services.count;
                    }
                });
                break;
            default:
                columns.push({ data: column, render: BooklyDatatables.escapeHtml() });
                break;
        }
        columns[columns.length - 1].title = BooklyDiscountsL10n.datatables[table].titles[column] || column;
        columns[columns.length - 1].name = column;
        columns[columns.length - 1].show = show;
    });

    /**
     * Init datatable.
     */
    let bt = BooklyDatatables.showForm('bookly-' + table + '-datatables', {
        serverSide: false,
        ajax: {
            url: ajaxurl,
            method: 'POST',
            data: function (d) {
                return $.extend({
                    action: 'bookly_discounts_get_discounts',
                    csrf_token: BooklyL10nGlobal.csrf_token
                }, d);
            }
        },
        columns: columns,
        tableSettings: Object.assign({}, BooklyDiscountsL10n.datatables[table], {
            l10n: Object.assign({}, BooklyDiscountsL10n.datatables.l10n, { zeroRecords: BooklyDiscountsL10n.zeroRecords })
        }),
        edit: function (row) {
            $(document.body).trigger('bookly_discounts.discount_dialog', [row, function () { bt.reload(); }]);
        },
        checked: function () {
            return [{
                label: BooklyDiscountsL10n.delete,
                icon: 'trash',
                variant: 'destructive',
                click: function (selected) { deleteDiscounts(selected.map(function (r) { return r.id; })); }
            }];
        },
        searchFilter: {
            placeholder: BooklyDiscountsL10n.search,
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
            id: 'bookly-new-discount',
            label: BooklyDiscountsL10n.new_discount,
            icon: 'plus',
            variant: 'default',
            click: function () {
                $(document.body).trigger('bookly_discounts.discount_dialog', [null, function () { bt.reload(); }]);
            }
        }]
    });

    /**
     * Delete the given discounts by ids.
     */
    function deleteDiscounts(ids) {
        if (!confirm(BooklyDiscountsL10n.are_you_sure)) {
            return;
        }
        bt.setLoading(true);
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bookly_discounts_delete_discounts',
                csrf_token: BooklyL10nGlobal.csrf_token,
                discounts: ids
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    bt.reload();
                } else {
                    bt.setLoading(false);
                    alert(response.data.message);
                }
            }
        });
    }
});
