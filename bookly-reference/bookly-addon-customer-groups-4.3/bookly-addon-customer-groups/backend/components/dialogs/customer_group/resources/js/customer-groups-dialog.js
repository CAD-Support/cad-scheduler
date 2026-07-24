jQuery(function($) {
    $(document.body).on('bookly_customer_groups.groups_dialog', {},
        function (event, data, callback, from_modal) {
            let $modal = $('#bookly-customer-groups-dialog'),
                $form = $('form', $modal).off(),
                $modal_footer = $('.modal-footer', $modal),
                $errors = $('.bookly-js-error-alert', $modal),
                $name_error = $('.bookly-js-error-alert.bookly-js-error-name', $modal),
                $group_name = $('.bookly-js-group-name', $modal),
                $group_description = $('.bookly-js-group-description', $modal),
                $gateways_list = $('#bookly-js-gateways-list', $modal),
                $payment_step = $('#bookly-js-payment-step', $modal)
            ;

            data = $.extend({
                id: null,
                name: '',
                status: '',
                discount: 0,
                skip_payment: 0,
                description: '',
                gateways: [],
                is_general_settings: 0,
            }, data);

            if (data.is_general_settings) {
                data = $.extend(data, BooklyL10nCustomerGroupsDialog.general_settings);
                $group_name.hide();
                $group_description.hide();
            } else {
                $group_name.show();
                $group_description.show();
            }

            $gateways_list.booklyDropdown();
            if (data.gateways === null) {
                $gateways_list.booklyDropdown('selectAll');
            } else {
                $gateways_list.booklyDropdown('setSelected', data.gateways);
            }

            function modalLoading(state) {
                $('.modal-body .bookly-loading', $modal).toggle(state);
                $('.modal-body .bookly-js-modal-body', $modal).toggle(!state);
                $modal_footer.toggle(!state);
            }

            $form.on('submit', function () {
                modalLoading(true);
                let form_data = $form.serializeArray();
                form_data.push({name: 'action', value: 'bookly_customer_groups_save_group'});
                form_data.push({name: 'is_general_settings', value: data.is_general_settings});
                form_data.push({name: 'id', value: data.id});
                form_data.push({name: 'csrf_token', value: BooklyL10nGlobal.csrf_token});
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: form_data,
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            if (data.is_general_settings === 1) {
                                BooklyL10nCustomerGroupsDialog.general_settings = {
                                    status: $('[name="status"]', $modal).val(),
                                    discount: $('[name="discount"]', $modal).val(),
                                    skip_payment: $('[name="skip_payment"]').prop('checked') ? 1 : 0,
                                    gateways: $gateways_list.booklyDropdown('getSelected')
                                }
                            }
                            if (callback) {
                                // Call callback.
                                callback();
                            }
                            // Close the dialog.
                            $modal.booklyModal('hide');
                        } else {
                            $errors.html('');
                            $.each(response.data.errors, function (index, error) {
                                switch (error) {
                                    case 'name_required':
                                        $name_error.append(BooklyL10nCustomerGroupsDialog.l10n.name_required_error);
                                        break;
                                }
                            });
                            $errors.each(function () {
                                if ($(this).html() != '') {
                                    $(this).show();
                                }
                            });
                        }
                        modalLoading(false);
                    }
                });
                return false;
            })
            .on('change', '[name="gateways"]', function () {
                if (this.value == 'default') {
                    $gateways_list.closest('.form-group').hide();
                } else {
                    $gateways_list.closest('.form-group').show();
                }
            });

            if (data.is_general_settings) {
                $('.modal-title', $modal).text(BooklyL10nCustomerGroupsDialog.l10n.settings);
            } else if (data.id == null) {
                $('.modal-title', $modal).text(BooklyL10nCustomerGroupsDialog.l10n.new_group);
            } else {
                $('.modal-title', $modal).text(BooklyL10nCustomerGroupsDialog.l10n.edit_group);
            }

            $modal.on('show.bs.modal', function (event) {
                $name_error.text('');
                $('[name="name"]', $modal).val(data.name);
                $('[name="description"]', $modal).val(data.description);
                $('[name="status"]', $modal).val(data.status);
                $('[name="skip_payment"]', $modal)
                    .prop('checked', data.skip_payment === 1)
                    .on('change', function () {
                        $payment_step.toggle(!this.checked);
                    })
                    .trigger('change');

                $('[name="discount"]', $modal).val(data.discount);
                if (data.gateways === null) {
                    $('[name="gateways"][value="default"]', $modal).prop('checked', true);
                } else {
                    $('[name="gateways"][value="custom"]', $modal).prop('checked', true);
                }
                $('[name="gateways"]:checked', $modal).trigger('change');
                modalLoading(false);
            }).booklyModal();
        }
    );
});