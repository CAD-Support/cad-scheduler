jQuery(function ($) {
    'use strict';

    const table = 'coupons';

    let $couponModal = $('#bookly-coupon-modal'),
        $seriesNewTitle = $('#bookly-new-coupon-series-title'),
        $couponNewTitle = $('#bookly-new-coupon-title'),
        $couponEditTitle = $('#bookly-edit-coupon-title'),
        $couponCode = $('#bookly-coupon-code'),
        $generateCode = $('#bookly-generate-code'),
        $seriesMask = $('#bookly-coupon-series-mask'),
        $seriesAmount = $('#bookly-coupon-series-amount'),
        $couponDiscount = $('#bookly-coupon-discount'),
        $couponDeduction = $('#bookly-coupon-deduction'),
        $couponUsageLimit = $('#bookly-coupon-usage-limit'),
        $couponOncePerCst = $('#once_per_customer'),
        $couponDateStart = $('#bookly-coupon-date-limit-start'),
        $clearDateStart = $('#bookly-clear-date-limit-start'),
        $couponDateEnd = $('#bookly-coupon-date-limit-end'),
        $clearDateEnd = $('#bookly-clear-date-limit-end'),
        $couponMinApps = $('#bookly-coupon-min-appointments'),
        $couponMaxApps = $('#bookly-coupon-max-appointments'),
        $couponCustomers = $('#bookly-coupon-customers'),
        $customersList = $('#bookly-customers-list'),
        $couponServices = $('#bookly-js-coupon-services'),
        $couponProviders = $('#bookly-js-coupon-providers'),
        $saveButton = $('#bookly-coupon-save', $couponModal),
        $createAnother = $('#bookly-create-another-coupon'),
        $exportDialog = $('#bookly-export-coupon-dialog'),
        $exportSelectAll = $('#bookly-js-export-select-all', $exportDialog),
        editData = null,
        series = false,
        duplicate = false
    ;

    const fl = BooklyCouponL10n.filters;

    // ── Filter state (restore from saved user_meta) ──────────────────────
    const savedFilter = BooklyCouponL10n.datatables[table].settings.filter || {};
    let serviceValue = savedFilter.service ? String(savedFilter.service) : '';
    let staffValue = savedFilter.staff ? String(savedFilter.staff) : '';
    let customerValue = savedFilter.customer ? String(savedFilter.customer) : '';
    let activeValue = String(savedFilter.active) === '1' ? '1' : '';

    const toOptions = function (collection) {
        return Object.keys(collection).map(function (id) {
            return { value: String(id), label: collection[id].title };
        });
    };
    const serviceOptions = toOptions(BooklyCouponL10n.services.collection);
    const staffOptions = toOptions(BooklyCouponL10n.staff.collection);
    // Static customer options (only when the base is small enough to preload).
    const customerOptions = (BooklyCouponL10n.customers.collection || []).map(function (c) {
        return { value: String(c.id), label: c.full_name };
    });

    /**
     * Init columns.
     */
    let columns = [];

    $.each(BooklyCouponL10n.datatables[table].settings.columns, function (column, show) {
        switch (column) {
            case 'services':
                columns.push({
                    data: 'services',
                    render: function (data, type, row) {
                        if (data === null) {
                            return BooklyCouponL10n.services.nothingSelected;
                        } else if (data == 1) {
                            return BooklyDatatables.escapeHtml(BooklyCouponL10n.services.collection[row.service_id].title);
                        } else if (data == BooklyCouponL10n.services.count) {
                            return BooklyCouponL10n.services.allSelected;
                        }
                        return data + '/' + BooklyCouponL10n.services.count;
                    }
                });
                break;
            case 'staff':
                columns.push({
                    data: 'staff',
                    render: function (data, type, row) {
                        if (data === null) {
                            return BooklyCouponL10n.staff.nothingSelected;
                        } else if (data == 1) {
                            if (typeof BooklyCouponL10n.staff.collection[row.staff_id] === 'undefined') {
                                return BooklyCouponL10n.staff.nothingSelected;
                            }
                            return BooklyDatatables.escapeHtml(BooklyCouponL10n.staff.collection[row.staff_id].title);
                        } else if (data == BooklyCouponL10n.staff.count) {
                            return BooklyCouponL10n.staff.allSelected;
                        }
                        return data + '/' + BooklyCouponL10n.staff.count;
                    }
                });
                break;
            case 'customers':
                columns.push({
                    data: 'customers',
                    render: function (data, type, row) {
                        if (data === null) {
                            return BooklyCouponL10n.customers.nothingSelected;
                        } else if (data == 1) {
                            return BooklyDatatables.escapeHtml(row.full_name);
                        } else if (data == BooklyCouponL10n.customers.count) {
                            return BooklyCouponL10n.customers.allSelected;
                        }
                        return data + '/' + BooklyCouponL10n.customers.count;
                    }
                });
                break;
            case 'date_limit_start':
                columns.push({
                    data: 'date_limit_start',
                    render: function (data, type, row) { return BooklyDatatables.escapeHtml(row.date_limit_start_formatted); }
                });
                break;
            case 'date_limit_end':
                columns.push({
                    data: 'date_limit_end',
                    render: function (data, type, row) { return BooklyDatatables.escapeHtml(row.date_limit_end_formatted); }
                });
                break;
            default:
                columns.push({ data: column, render: BooklyDatatables.escapeHtml() });
                break;
        }
        columns[columns.length - 1].title = BooklyCouponL10n.datatables[table].titles[column] || column;
        columns[columns.length - 1].name = column;
        columns[columns.length - 1].show = show;
        // Server searches the code column only — highlight matches there only.
        columns[columns.length - 1].searchable = column === 'code';
    });

    // ── Customer filter (static list or remote AJAX typeahead) ───────────
    const customerFilter = {
        type: 'select',
        name: 'customer',
        label: fl.customer,
        initialValue: customerValue,
        searchPlaceholder: fl.searchPlaceholder,
        options: customerOptions,
        onChange: function (v) { customerValue = v; },
    };
    if (BooklyCouponL10n.customers.remote) {
        // Large customer base — load options on demand via AJAX typeahead.
        customerFilter.remote = true;
        customerFilter.loadOptions = function (term) {
            return $.ajax({
                url: ajaxurl, method: 'POST', dataType: 'json',
                data: { action: 'bookly_get_customers_list', filter: term || '', page: 1, csrf_token: BooklyL10nGlobal.csrf_token }
            }).then(function (resp) {
                return (resp && resp.results ? resp.results : []).map(function (c) {
                    return { value: String(c.id), label: c.text };
                });
            });
        };
        customerFilter.resolveOption = function (id) {
            return $.ajax({
                url: ajaxurl, method: 'POST', dataType: 'json',
                data: { action: 'bookly_get_customers_list', ids: [id], csrf_token: BooklyL10nGlobal.csrf_token }
            }).then(function (resp) {
                const c = resp && resp.results && resp.results[0];
                return c ? { value: String(c.id), label: c.text } : null;
            });
        };
    }

    /**
     * Init datatable.
     */
    let bt = BooklyDatatables.showForm('bookly-' + table + '-datatables', {
        ajax: {
            url: ajaxurl,
            method: 'POST',
            data: function (d) {
                return $.extend({
                    action: 'bookly_coupons_get_coupons',
                    csrf_token: BooklyL10nGlobal.csrf_token,
                    filter: {
                        service: serviceValue,
                        staff: staffValue,
                        customer: customerValue,
                        active: activeValue ? 1 : 0
                    }
                }, d);
            }
        },
        columns: columns,
        tableSettings: Object.assign({}, BooklyCouponL10n.datatables[table], {
            l10n: Object.assign({}, BooklyCouponL10n.datatables.l10n, { zeroRecords: BooklyCouponL10n.zeroRecords })
        }),
        edit: function (row) {
            openCoupon(row, false);
        },
        rowActions: function () {
            return [{
                label: BooklyCouponL10n.duplicate,
                icon: 'copy-plus',
                variant: 'outline',
                click: function (r) { openCoupon(r, true); }
            }];
        },
        checked: function () {
            return [{
                label: BooklyCouponL10n.delete,
                icon: 'trash',
                variant: 'destructive',
                click: function (selected) { deleteCoupons(selected.map(function (r) { return r.id; })); }
            }];
        },
        searchFilter: {
            placeholder: fl.code,
            name: 'filter[code]'
        },
        filters: [
            { type: 'select', name: 'service', label: fl.service, initialValue: serviceValue, searchPlaceholder: fl.searchPlaceholder, options: serviceOptions, onChange: function (v) { serviceValue = v; } },
            { type: 'select', name: 'staff', label: fl.staff, initialValue: staffValue, searchPlaceholder: fl.searchPlaceholder, options: staffOptions, onChange: function (v) { staffValue = v; } },
            customerFilter,
            { type: 'checkbox', name: 'active', label: fl.activeOnly, initialValue: activeValue, onChange: function (v) { activeValue = v; } },
        ],
        saveSettings: function (settings) {
            $.post(ajaxurl, Object.assign({
                action: 'bookly_update_table_settings',
                table: table,
                csrf_token: BooklyL10nGlobal.csrf_token
            }, settings));
        },
        topToolbar: [
            {
                id: 'bookly-coupons-export',
                label: BooklyCouponL10n.export,
                icon: 'download',
                variant: 'outline',
                click: function () {
                    // Rebuild the export column list from the current table columns so it
                    // reflects the live show/hide state (matches appointments export).
                    let columnsHtml = '';
                    bt.getColumns().forEach(function (column, index) {
                        columnsHtml += '<div class="custom-control custom-checkbox">'
                            + '<input class="custom-control-input" id="bookly-ec-' + index + '" name="exp[' + column.name + ']" type="checkbox"' + (column.show ? ' checked' : '') + '>'
                            + '<label class="custom-control-label" for="bookly-ec-' + index + '">' + column.title + '</label>'
                            + '</div>';
                    });
                    $('.bookly-js-columns', $exportDialog).html(columnsHtml);
                    $exportSelectAll.prop('checked', $('.bookly-js-columns input:not(:checked)', $exportDialog).length === 0);
                    $exportDialog.booklyModal('show');
                }
            },
            {
                id: 'bookly-add-series',
                label: BooklyCouponL10n.new_series,
                icon: 'list',
                variant: 'outline',
                click: function () { openNew(true); }
            },
            {
                id: 'bookly-add',
                label: BooklyCouponL10n.new_coupon,
                icon: 'plus',
                variant: 'default',
                click: function () { openNew(false); }
            }
        ]
    });

    /**
     * Open the create/series modal (no existing coupon).
     */
    function openNew(isSeries) {
        editData = null;
        series = isSeries;
        duplicate = false;
        $couponModal.booklyModal('show');
    }

    /**
     * Open the edit/duplicate modal for an existing coupon row.
     */
    function openCoupon(rowData, isDuplicate) {
        editData = rowData;
        series = false;
        duplicate = isDuplicate;
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bookly_coupons_get_coupon_lists',
                csrf_token: BooklyL10nGlobal.csrf_token,
                coupon_id: rowData.id,
                remote: BooklyCouponL10n.customers.remote ? '1' : '0'
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $couponServices.booklyDropdown('setSelected', response.data.service_id);
                    $couponProviders.booklyDropdown('setSelected', response.data.staff_id);
                    if (BooklyCouponL10n.customers.remote) {
                        $couponCustomers.html('');
                        response.data.customers.forEach(function (customer) {
                            $couponCustomers[0].appendChild(new Option(customer.text, customer.id));
                        });
                    }
                    $couponCustomers.val(response.data.customer_id).trigger('change');
                }
                $couponModal.booklyModal('show');
            }
        });
    }

    /**
     * On show modal.
     */
    $couponModal
    .on('show.bs.modal', function () {
        if (editData) {
            $couponCode.val(editData.code);
            $couponDiscount.val(editData.discount);
            $couponDeduction.val(editData.deduction);
            $couponUsageLimit.val(editData.usage_limit);
            $couponOncePerCst.val(editData.once_per_customer);
            $couponDateStart.val(editData.date_limit_start !== null ? moment(editData.date_limit_start, 'YYYY-MM-DD').format(BooklyL10nGlobal.datePicker.format) : '');
            $couponDateStart.next('input:hidden').val(editData.date_limit_start);
            $couponDateEnd.val(editData.date_limit_end !== null ? moment(editData.date_limit_end, 'YYYY-MM-DD').format(BooklyL10nGlobal.datePicker.format) : '');
            $couponDateEnd.next('input:hidden').val(editData.date_limit_end);
            $couponMinApps.val(editData.min_appointments);
            $couponMaxApps.val(editData.max_appointments);
            $seriesNewTitle.hide();
            if (duplicate) {
                $couponEditTitle.hide();
                $couponNewTitle.show();
            } else {
                $couponEditTitle.show();
                $couponNewTitle.hide();
            }
        } else {
            $couponCode.val('');
            $seriesMask.val(BooklyCouponL10n.defaultCodeMask);
            $seriesAmount.val(1);
            $couponDiscount.val('0');
            $couponDeduction.val('0');
            $couponUsageLimit.val('1');
            $couponOncePerCst.val('0');
            $couponDateStart.val('');
            $couponDateEnd.val('');
            $couponMinApps.val('1');
            $couponMaxApps.val('');
            $couponCustomers.val(null).trigger('change');
            $couponEditTitle.hide();
            if (series) {
                $couponNewTitle.hide();
                $seriesNewTitle.show();
            } else {
                $couponNewTitle.show();
                $seriesNewTitle.hide();
            }
            $couponServices.booklyDropdown('selectAll');
            $couponProviders.booklyDropdown('selectAll');
        }
        $('.bookly-js-series-field').toggle(series);
        $('.bookly-js-coupon-field').toggle(!series);
        $couponCode.trigger('change');
        $createAnother.prop('checked', false);
    })
    .on('hidden.bs.modal', function () {
        editData = null;
        $('[name=date_limit_start]', $couponModal).val('');
        $('[name=date_limit_end]', $couponModal).val('');
    });

    /**
     * Code.
     */
    $couponCode.on('keyup change', function () {
        $generateCode.prop('disabled', $couponCode.val().length && $couponCode.val().indexOf('*') === -1);
    });

    /**
     * Generate code.
     */
    $generateCode.on('click', function () {
        let ladda = Ladda.create(this);
        ladda.start();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bookly_coupons_generate_code',
                csrf_token: BooklyL10nGlobal.csrf_token,
                mask: $couponCode.val()
            },
            dataType: 'json',
            success: function (response) {
                ladda.stop();
                if (response.success) {
                    $couponCode.val(response.data.code);
                    $generateCode.prop('disabled', true);
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    /**
     * Date limit start.
     */
    $couponDateStart.daterangepicker({
        parentEl: '#bookly-coupon-modal',
        singleDatePicker: true,
        showDropdowns: true,
        autoUpdateInput: false,
        locale: BooklyL10nGlobal.datePicker
    }, function (start) {
        $couponDateStart.val(start.format(BooklyL10nGlobal.datePicker.format)).trigger('change');
        $couponDateStart.next('input:hidden').val(start.format('YYYY-MM-DD'))
    });
    $couponDateStart.on('apply.daterangepicker', function (ev, picker) {
        $couponDateStart.val(picker.startDate.format(BooklyL10nGlobal.datePicker.format)).trigger('change');
        $couponDateStart.next('input:hidden').val(picker.startDate.format('YYYY-MM-DD'))
    });
    $clearDateStart.on('click', function () {
        $couponDateStart.val('');
        $couponDateStart.next('input:hidden').val(null);
    });

    /**
     * Date limit end.
     */
    $couponDateEnd.daterangepicker({
        parentEl: '#bookly-coupon-modal',
        singleDatePicker: true,
        showDropdowns: true,
        autoUpdateInput: false,
        locale: BooklyL10nGlobal.datePicker
    }, function (start) {
        $couponDateEnd.val(start.format(BooklyL10nGlobal.datePicker.format)).trigger('change');
        $couponDateEnd.next('input:hidden').val(start.format('YYYY-MM-DD'))
    });
    $couponDateEnd.on('apply.daterangepicker', function (ev, picker) {
        $couponDateEnd.val(picker.startDate.format(BooklyL10nGlobal.datePicker.format)).trigger('change');
        $couponDateEnd.next('input:hidden').val(picker.startDate.format('YYYY-MM-DD'))
    });
    $clearDateEnd.on('click', function () {
        $couponDateEnd.val('');
        $couponDateEnd.next('input:hidden').val(null);
    });

    /**
     * Customers list (modal multi-select).
     */
    if (BooklyCouponL10n.customers.remote) {
        $couponCustomers.booklySelect2({
            width: '100%',
            theme: 'bootstrap4',
            dropdownParent: '#bookly-tbs',
            allowClear: false,
            placeholder: '',
            language: {
                noResults: function () { return BooklyCouponL10n.noResultFound; },
                searching: function () { return BooklyCouponL10n.searching; }
            },
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    params.page = params.page || 1;
                    return {
                        action: 'bookly_get_customers_list',
                        filter: params.term,
                        page: params.page,
                        csrf_token: BooklyL10nGlobal.csrf_token
                    };
                },
                processResults: function (data, params) {
                    var customers = [];
                    params.page = params.page || 1;
                    data.results.forEach(function (customer) {
                        BooklyCouponL10n.customers.collection[customer.id] = customer;
                        customers.push({ id: customer.id, text: customer.name });
                    });
                    return { results: customers, pagination: data.pagination };
                }
            },
        });
    } else {
        $couponCustomers.booklySelect2({
            width: '100%',
            theme: 'bootstrap4',
            dropdownParent: '#bookly-tbs',
            allowClear: false,
            placeholder: '',
            language: {
                noResults: function () { return BooklyCouponL10n.noResultFound; }
            }
        });
    }

    $couponCustomers.on('change', function () {
        $customersList.empty();
        $couponCustomers.find('option:selected').each(function () {
            let $option = $(this),
                $li = $('<li class="form-row align-items-center"/>'),
                $span = $('<span class="col-11 text-truncate"/>'),
                $a = $('<a class="far fa-fw fa-trash-alt text-danger" href="#"/>')
            ;
            $span.text($option.text());
            $a.on('click', function (e) {
                e.preventDefault();
                var newValues = [];
                $.each($couponCustomers.val(), function (i, id) {
                    if (id !== $option.val()) {
                        newValues.push(id);
                    }
                });
                $couponCustomers.val(newValues);
                $couponCustomers.trigger('change');
            });
            $a.attr('title', BooklyCouponL10n.removeCustomer);
            $li.append($span).append($a);
            $customersList.append($li);
        });
    });

    /**
     * Services / providers (staff) dropdowns.
     */
    $couponServices.booklyDropdown();
    $couponProviders.booklyDropdown();

    /**
     * Save coupon.
     */
    $saveButton.on('click', function (e) {
        e.preventDefault();
        let data = booklySerialize.form($(this).parents('form')),
            ladda = Ladda.create(this);
        if (editData && !duplicate) {
            data.id = editData.id;
        }
        if (series) {
            data.create_series = 1;
        }
        ladda.start();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: booklySerialize.buildRequestData('bookly_coupons_save_coupon', data),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    bt.reload();
                    if (!series && $createAnother.prop('checked')) {
                        editData = null;
                        $couponNewTitle.show();
                        $couponEditTitle.hide();
                        $couponCode.val('');
                        $createAnother.prop('checked', false);
                    } else {
                        $couponModal.booklyModal('hide');
                    }
                } else {
                    alert(response.data.message);
                }
                ladda.stop();
            }
        });
    });

    /**
     * Delete the given coupons by ids.
     */
    function deleteCoupons(ids) {
        if (!confirm(BooklyCouponL10n.areYouSure)) {
            return;
        }
        bt.setLoading(true);
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bookly_coupons_delete_coupons',
                csrf_token: BooklyL10nGlobal.csrf_token,
                data: ids
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

    /**
     * Export dialog — select-all toggle.
     */
    $exportSelectAll.on('click', function () {
        let checked = this.checked;
        $('.bookly-js-columns input', $exportDialog).each(function () {
            $(this).prop('checked', checked);
        });
    });

    $('.bookly-js-columns input', $exportDialog).on('change', function () {
        $exportSelectAll.prop('checked', $('.bookly-js-columns input:checked', $exportDialog).length == $('.bookly-js-columns input', $exportDialog).length);
    });
});
