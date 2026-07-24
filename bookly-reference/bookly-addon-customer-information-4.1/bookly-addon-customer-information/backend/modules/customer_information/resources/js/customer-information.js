jQuery(function($) {
    const $tab_container = $('.bookly-js-custom-fields-wrap'),
        $save_btn = $('#ajax-send-fields'),
        $reset_btn = $('button[type=reset]'),
        customer_fields = BooklyCustomerInformationL10n.fields
    ;

    var $fields = $("#bookly-customer-information-fields");

    Sortable.create($fields[0], {
        handle : '.bookly-js-draghandle.bookly-js-reorder-cf'
    });

    /**
     * Build initial fields.
     */
    restoreFields();
    /**
     * On "Add new field" button click.
     */
    $('#bookly-js-add-fields').on('click', 'button', function() {
        addField({type: $(this).data('type')});
    });

    /**
     * On "Add new item" button click.
     */
    $fields.on('click', 'button', function() {
        addItem($(this).prev('ul'), $(this).data('type'));
    });

    /**
     * Delete field or checkbox/radio button/drop-down option.
     */
    $fields.on('click', '.bookly-js-delete', function(e) {
        e.preventDefault();
        $(this).closest('li').fadeOut('fast', function() {
            $(this).remove();
        });
    }).on('click', '.bookly-js-visibility', function(e) {
        e.preventDefault();
        let $field = $(this).closest('li'),
            visibility = $field.data('visible');
        $field.data('visible', !visibility);
        $(this).remove();
        addVisibilityButton($field, !visibility)
    });

    /**
     * Submit fields form.
     */
    $save_btn.off().on('click', function(e) {
        e.preventDefault();
        let ladda = Ladda.create(this),
            data = {
                fields: [],
            };
        ladda.start();
        $fields.children('li').each(function() {
            var $this = $(this),
                field = {};
            switch ($this.data('type')) {
                case 'checkboxes':
                case 'radio-buttons':
                case 'drop-down':
                    field.items = [];
                    $this.find('ul.bookly-js-items li').each(function() {
                        field.items.push($(this).find('input[type="text"]').val());
                        if ($(this).find('input.bookly-js-default').prop('checked')) {
                            if ($this.data('type') === 'checkboxes') {
                                field.default = field.default || [];
                                field.default.push(field.items[field.items.length - 1]);
                            } else {
                                field.default = field.items[field.items.length - 1];
                            }
                        }
                    });
                    break;
                case 'number':
                    field.limits = $this.find('.bookly-js-use-limits').prop('checked');
                    field.min = $this.find('.bookly-js-min-value').val();
                    field.max = $this.find('.bookly-js-max-value').val();
                    break;
                case 'date':
                    field.limits = $this.find('.bookly-js-use-limits').prop('checked');
                    field.min = $this.find('.bookly-js-min-value').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    field.max = $this.find('.bookly-js-max-value').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    break;
                case 'time':
                    field.delimiter = $this.find('.bookly-js-delimiter').val();
                    field.limits = $this.find('.bookly-js-use-limits').prop('checked');
                    field.min = $this.find('.bookly-js-min-value').val();
                    field.max = $this.find('.bookly-js-max-value').val();
                    break;
            }
            field.visible = !!$this.data('visible');
            field.type = $this.data('type');
            field.label = $this.find('.bookly-js-label').val();
            field.description = $this.find('.bookly-js-description').val();
            field.required = $this.find('.bookly-js-required').prop('checked');
            field.ask_once = $this.find('.bookly-js-ask-once').prop('checked');
            field.id = $this.data('bookly-field-id');
            data.fields.push(field);
        });
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            xhrFields : { withCredentials: true },
            data: booklySerialize.buildRequestData('bookly_customer_information_save_fields', data),
            complete: function() {
                ladda.stop();
                booklyAlert({success : [BooklyCustomerInformationL10n.saved]});
            }
        });
    });

    /**
     * On 'Reset' click.
     */
    $reset_btn.off().on('click', function() {
        $fields.empty();
        restoreFields();
    });

    /**
     * Add new field.
     *
     * @param field
     * @returns {*|jQuery}
     */
    function addField(field) {
        var $new_field = $('ul#bookly-templates > li[data-type=' + field.type + ']').clone();
        // Set id, label and required.
        if (!field.hasOwnProperty('id')) {
            do {
                field.id = Math.floor((Math.random() * 100000) + 1);
            } while ($fields.children('li').map(function() { return jQuery(this).data('bookly-field-id'); }).get().includes(field.id));
        }
        if (!field.hasOwnProperty('label')) {
            field.label = '';
        }
        if (!field.hasOwnProperty('description')) {
            field.description = '';
        }
        if (!field.hasOwnProperty('ask_once')) {
            field.ask_once = false;
        }
        if (!field.hasOwnProperty('required')) {
            field.required = false;
        }
        if (!field.hasOwnProperty('delimiter')) {
            field.delimiter = '';
        }
        if (!field.hasOwnProperty('min')) {
            field.min = '';
        }
        if (!field.hasOwnProperty('max')) {
            field.max = '';
        }
        if (!field.hasOwnProperty('limits')) {
            field.limits = false;
        }
        if (!field.hasOwnProperty('visible')) {
            field.visible = true;
        }
        $new_field
            .hide()
            .data('bookly-field-id', field.id)
            .find('.bookly-js-required').prop({id: 'required-' + field.id, checked: field.required})
            .next('label').attr('for', 'required-' + field.id).end()
            .end()
            .find('.bookly-js-ask-once').prop({id: 'ask-once-' + field.id, checked: field.ask_once})
            .next('label').attr('for', 'ask-once-' + field.id).end()
            .end()
            .find('.bookly-js-use-limits').prop({id: 'bookly-limits-' + field.id, checked: field.limits})
            .next('label').attr('for', 'bookly-limits-' + field.id).end()
            .end()
            .find('.bookly-js-limits').toggle(field.limits).end()
            .find('.bookly-js-label').val(field.label).end()
            .find('.bookly-js-description').val(field.description).end()
            .find('.bookly-js-delimiter').val(field.delimiter).end()
            .find('.bookly-js-replace-code').text(' - {info_field#' + field.id + '}').end()
        ;
        addVisibilityButton($new_field, field.visible);

        $new_field.find('.bookly-js-use-limits').on('change', function() {
            $new_field.find('.bookly-js-limits').toggle($(this).prop('checked'));
        });
        // Date field.
        $('.bookly-js-date', $new_field).daterangepicker({
            parentEl: $('#bookly-customer-information-fields').closest('.bookly\\:card'),
            singleDatePicker: true,
            showDropdowns: true,
            autoUpdateInput: true,
            locale: BooklyL10nGlobal.datePicker
        }, function() {});
        if (field.type === 'date') {
            if (field.min !== '') {
                $('.bookly-js-min-value',$new_field).data('daterangepicker').setStartDate(moment(field.min));
            }
            if (field.max !== '') {
                $('.bookly-js-max-value',$new_field).data('daterangepicker').setStartDate(moment(field.max));
            }
        } else {
            $new_field
                .find('.bookly-js-min-value').val(field.min).end()
                .find('.bookly-js-max-value').val(field.max).end();
        }
        // Add new field to the list.
        $fields.append($new_field);
        $new_field.fadeIn('fast');
        let $items = $new_field.find('ul.bookly-js-items');
        if ($items.length > 0) {
            Sortable.create($items[0], {
                handle: '.bookly-js-draghandle.bookly-js-reorder-cf-item'
            });
        }
        // Set focus to label field.
        $new_field.find('.bookly-js-label').focus();

        return $new_field;
    }

    function addVisibilityButton($field, visibility) {
        let $button = $('<a class="bookly-js-visibility fas fa-fw text-decoration-none">');
        if (visibility) {
            $button
                .addClass('fa-eye')
                .addClass('text-info')
                .attr('title', BooklyCustomerInformationL10n.visible);
        } else {
            $button
                .addClass('fa-eye-slash')
                .addClass('text-muted')
                .attr('title', BooklyCustomerInformationL10n.backendOnly);
        }
        $field.find('a.bookly-js-delete').first().before($button);
        $field.data('visible', visibility);
    }

    /**
     * Add new checkbox/radio button/drop-down option.
     *
     * @param $ul
     * @param type
     * @param value
     * @param default_value
     * @return {*|jQuery}
     */
    function addItem($ul, type, value, default_value) {
        let $new_item = $('ul#bookly-templates > li[data-type=' + type + ']').clone(),
            $info_field = $ul.closest('.bookly-js-field-with-items'),
            cf_id = $info_field.data('booklyFieldId'),
            option_id = 0;
        $info_field.find('input.bookly-js-default').each(function() {
            if ($(this).data('option-id') > option_id) {
                option_id = $(this).data('option-id');
            }
        });
        option_id++;

        if (typeof value != 'undefined') {
            $new_item.find('input[type="text"]').val(value);

            if (type === 'checkboxes-item') {
                if (default_value && default_value.length && default_value.indexOf(value) !== -1) {
                    $new_item.find('input.bookly-js-default').prop('checked', true);
                }
            } else if (default_value === value) {
                $new_item.find('input.bookly-js-default').prop('checked', true);
            }
        }
        $new_item.find('input.bookly-js-default').attr('name', 'cf-option-' + cf_id).data('option-id', option_id).attr('id', 'cf-option-' + cf_id + '-' + option_id).next('label').attr('for', 'cf-option-' + cf_id + '-' + option_id);
        $new_item.hide().appendTo($ul).fadeIn('fast').find('input').focus();

        return $new_item;
    }

    /**
     * Restore fields from default values.
     */
    function restoreFields() {
        $.each(customer_fields, function(i, field) {
            let $new_field = addField(field);
            // add children
            if (field.items) {
                $.each(field.items, function(i, value) {
                    addItem($new_field.find('ul.bookly-js-items'), field.type + '-item', value, field.default);
                });
            }
        });

        $(':focus').blur();
    }
});