jQuery(function ($) {
    'use strict';

    const table = 'customer_groups';
    const $noGroups = $('.bookly-js-no-groups');
    const gateways_count = Object.keys(BooklyCustomerGroupsL10n.gateways).length;

    const defaultBadgeClass = 'bookly:bg-gray-100 bookly:text-gray-700 bookly:border-gray-200';
    const statusBadgeClass = {
        pending:    'bookly:bg-amber-100 bookly:text-amber-800 bookly:border-amber-200',
        approved:   'bookly:bg-green-100 bookly:text-green-800 bookly:border-green-200',
        cancelled:  'bookly:bg-gray-100 bookly:text-gray-700 bookly:border-gray-200',
        rejected:   'bookly:bg-red-100 bookly:text-red-800 bookly:border-red-200',
        waitlisted: 'bookly:bg-purple-100 bookly:text-purple-800 bookly:border-purple-200',
        done:       'bookly:bg-blue-100 bookly:text-blue-800 bookly:border-blue-200',
    };

    /**
     * Init columns.
     */
    let columns = [];

    $.each(BooklyCustomerGroupsL10n.datatables[table].settings.columns, function (column, show) {
        switch (column) {
            case 'appointment_status':
                columns.push({
                    data: 'appointment_status',
                    render: BooklyDatatables.escapeHtml(),
                    badge: function (row) { return statusBadgeClass[row.status] || defaultBadgeClass; }
                });
                break;
            case 'gateways':
                columns.push({
                    data: column,
                    orderable: false,
                    render: function (data) {
                        if (data === null) {
                            return BooklyCustomerGroupsL10n.default;
                        }
                        let length = data.length;
                        if (length === 0) {
                            return BooklyCustomerGroupsL10n.nothing_selected;
                        } else if (length === 1) {
                            return BooklyDatatables.escapeHtml(BooklyCustomerGroupsL10n.gateways[data[0]]);
                        }
                        return length === gateways_count
                            ? BooklyCustomerGroupsL10n.all_selected
                            : length + '/' + gateways_count;
                    }
                });
                break;
            default:
                columns.push({ data: column, render: BooklyDatatables.escapeHtml() });
                break;
        }
        columns[columns.length - 1].title = BooklyCustomerGroupsL10n.datatables[table].titles[column] || column;
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
                    action: 'bookly_customer_groups_get_groups',
                    csrf_token: BooklyL10nGlobal.csrf_token
                }, d);
            }
        },
        columns: columns,
        tableSettings: Object.assign({}, BooklyCustomerGroupsL10n.datatables[table], {
            l10n: Object.assign({}, BooklyCustomerGroupsL10n.datatables.l10n, { zeroRecords: BooklyCustomerGroupsL10n.zeroRecords })
        }),
        edit: function (row) {
            $(document.body).trigger('bookly_customer_groups.groups_dialog', [row, function () { bt.reload(); }]);
        },
        checked: function () {
            return [{
                label: BooklyCustomerGroupsL10n.delete,
                icon: 'trash',
                variant: 'destructive',
                click: function (selected) { deleteGroups(selected.map(function (r) { return r.id; })); }
            }];
        },
        searchFilter: {
            placeholder: BooklyCustomerGroupsL10n.search,
            name: 'filter[search]'
        },
        saveSettings: function (settings) {
            $.post(ajaxurl, Object.assign({
                action: 'bookly_update_table_settings',
                table: table,
                csrf_token: BooklyL10nGlobal.csrf_token
            }, settings));
        },
        topToolbar: [
            {
                label: BooklyCustomerGroupsL10n.general_settings,
                icon: 'settings',
                variant: 'outline',
                click: function () {
                    $(document.body).trigger('bookly_customer_groups.groups_dialog', [{ is_general_settings: 1 }]);
                }
            },
            {
                id: 'bookly-js-add-group',
                label: BooklyCustomerGroupsL10n.new_group,
                icon: 'plus',
                variant: 'default',
                click: function () {
                    $(document.body).trigger('bookly_customer_groups.groups_dialog', [{ gateways: null }, function () { bt.reload(); }]);
                }
            }
        ]
    });

    /**
     * Delete the given groups by ids.
     */
    function deleteGroups(ids) {
        if (!confirm(BooklyCustomerGroupsL10n.are_you_sure)) {
            return;
        }
        bt.setLoading(true);
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bookly_customer_groups_delete_groups',
                csrf_token: BooklyL10nGlobal.csrf_token,
                group_ids: ids
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $noGroups.text(response.data.no_groups_count);
                    bt.reload();
                } else {
                    bt.setLoading(false);
                    alert(response.data.message);
                }
            }
        });
    }
});
