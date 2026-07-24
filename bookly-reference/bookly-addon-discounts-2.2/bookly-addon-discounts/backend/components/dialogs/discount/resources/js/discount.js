jQuery(function ($) {

    let
        $modal              = $('#bookly-discount-modal'),
        $modal_title        = $('#bookly-discount-modal-title', $modal),
        $discount_title     = $('#bookly-discount-title', $modal),
        $discount_type      = $('#bookly-discount-type', $modal),
        $discount_threshold = $('#bookly-discount-threshold', $modal),
        $discount_discount  = $('#bookly-discount-discount', $modal),
        $discount_deduction = $('#bookly-discount-deduction', $modal),
        $save_button        = $('#bookly-discount-save', $modal),
        $date_start         = $('#bookly-discount-date-start', $modal),
        $clear_date_start   = $('#bookly-discount-clear-date-start', $modal),
        $date_end           = $('#bookly-discount-date-end', $modal),
        $clear_date_end     = $('#bookly-discount-clear-date-end', $modal),
        $discount_services  = $('#bookly-discount-services', $modal),
        editData,
        doneCallback
    ;

    /**
     * Open the dialog. Triggered from the datatable's edit/new actions:
     *   $(document.body).trigger('bookly_discounts.discount_dialog', [rowData, onDone]);
     * rowData === null => new discount.
     */
    $(document.body).on('bookly_discounts.discount_dialog', function (e, data, onDone) {
        editData = data || null;
        doneCallback = onDone;
        if (editData) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bookly_discounts_get_discount_lists',
                    csrf_token: BooklyL10nGlobal.csrf_token,
                    discount_id: editData.id,
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $discount_services.booklyDropdown('setSelected', response.data.service_id);
                        $modal.booklyModal('show');
                    }
                }
            });
        } else {
            $discount_services.booklyDropdown('setSelected', []);
            $modal.booklyModal('show');
        }
    });

    $discount_type.on('change', function () {
        if (this.value === 'nop') {
            $discount_services.closest('.bookly-js-discount-services-wrap').show();
        } else {
            $discount_services.closest('.bookly-js-discount-services-wrap').hide();
        }
    });

    /**
     * Services.
     */
    $discount_services.booklyDropdown();

    /**
     * On show modal.
     */
    $modal
    .on('show.bs.modal', function (e) {
        var data;
        if (editData) {
            data = editData;
            $modal_title.html(BooklyDiscountDialogL10n.title.edit);
        } else {
            data = {title: '', type: $discount_type.find('option').val(), threshold: 1, discount: 0, deduction: 0, date_start: null, date_end: null, enabled: 0, services: null};
            $modal_title.html(BooklyDiscountDialogL10n.title.new);
            $modal_title.show();
        }
        $discount_title.val(data.title);
        $discount_type.val(data.type).trigger('change');
        $discount_threshold.val(data.threshold);
        $discount_discount.val(data.discount);
        $discount_deduction.val(data.deduction);
        $('input[name="enabled"][value="' + data.enabled + '"]', $modal).prop('checked', true);

        if (data.date_start === null) {
            $date_start.val('');
            $date_start.next('input:hidden').val(null);
        } else {
            let start = moment(data.date_start);
            $date_start.val(start.format(BooklyDiscountDialogL10n.datePicker.format)).trigger('change');
            $date_start.next('input:hidden').val(start.format('YYYY-MM-DD'))
        }
        if (data.date_end === null) {
            $date_end.val('');
            $date_end.next('input:hidden').val(null);
        } else {
            let end = moment(data.date_end);
            $date_end.val(end.format(BooklyDiscountDialogL10n.datePicker.format)).trigger('change');
            $date_end.next('input:hidden').val(end.format('YYYY-MM-DD'))
        }
        $('input', $modal).removeClass('is-invalid');
    })
    .on('hidden.bs.modal', function () {
        editData = null;
    });

    /**
     * Date start.
     */
    $date_start.daterangepicker({
        parentEl: '#bookly-discount-modal',
        singleDatePicker: true,
        showDropdowns: true,
        autoUpdateInput: false,
        locale: BooklyDiscountDialogL10n.datePicker
    }, function (start) {
        $date_start.val(start.format(BooklyDiscountDialogL10n.datePicker.format)).trigger('change');
        $date_start.next('input:hidden').val(start.format('YYYY-MM-DD'))
    });
    $date_start.on('apply.daterangepicker', function(ev, picker) {
        $date_start.val(picker.startDate.format(BooklyDiscountDialogL10n.datePicker.format)).trigger('change');
        $date_start.next('input:hidden').val(picker.startDate.format('YYYY-MM-DD'))
    });
    $clear_date_start.on('click', function () {
        $date_start.val('');
        $date_start.next('input:hidden').val(null);
    });

    /**
     * Date end.
     */
    $date_end.daterangepicker({
        parentEl: '#bookly-discount-modal',
        singleDatePicker: true,
        showDropdowns: true,
        autoUpdateInput: false,
        locale: BooklyDiscountDialogL10n.datePicker
    }, function (start) {
        $date_end.val(start.format(BooklyDiscountDialogL10n.datePicker.format)).trigger('change');
        $date_end.next('input:hidden').val(start.format('YYYY-MM-DD'))
    });
    $date_end.on('apply.daterangepicker', function(ev, picker) {
        $date_end.val(picker.startDate.format(BooklyDiscountDialogL10n.datePicker.format)).trigger('change');
        $date_end.next('input:hidden').val(picker.startDate.format('YYYY-MM-DD'))
    });
    $clear_date_end.on('click', function () {
        $date_end.val('');
        $date_end.next('input:hidden').val(null);
    });

    /**
     * Save discount.
     */
    $save_button.on('click', function (e) {
        e.preventDefault();
        let $form = $(this).parents('form'),
            ladda = Ladda.create(this, {timeout: 2000}),
            abort = false,
            data;

        $('input', $form).removeClass('is-invalid');
        if ($discount_discount.val() < 0 || $discount_discount.val() > 100) {
            $discount_discount.addClass('is-invalid');
            alert(BooklyDiscountDialogL10n.discountError);
            abort = true;
        }
        if ($discount_deduction.val() < 0) {
            $discount_deduction.addClass('is-invalid');
            alert(BooklyDiscountDialogL10n.deductionError);
            abort = true;
        }
        $('input[required]', $form).each(function () {
            if ($(this).val() == '') {
                $(this).addClass('is-invalid');
                abort = true;
            }
        });
        if (abort) {
            return false;
        }
        data = booklySerialize.form($form);
        if (editData) {
            data.id = editData.id;
        }
        ladda.start();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: booklySerialize.buildRequestData('bookly_discounts_save_discount', data),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    if (typeof doneCallback === 'function') {
                        doneCallback();
                    }
                    $modal.booklyModal('hide');
                } else {
                    alert(response.data.message);
                }
                ladda.stop();
            }
        });
    });
});