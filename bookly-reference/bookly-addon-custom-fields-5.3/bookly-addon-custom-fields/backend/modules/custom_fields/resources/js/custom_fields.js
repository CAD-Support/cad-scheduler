jQuery(function($) {

    const $tab_container = $('.bookly-js-custom-fields-wrap'),
        $save_btn = $('#ajax-save-custom-fields'),
        $reset_btn = $('#ajax-reset-custom-fields')
    ;

    let custom_fields;

    $('.bookly-js-custom-fields-tabs a').off().on('click', function(e) {
        $('.bookly-js-custom-fields-tabs a').removeClass('bookly:active');
        $(this).addClass('bookly:active');
        $tab_container.html('<div class=\'bookly-loading\'></div>');
        let tab = $(this).data('tab');
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            xhrFields: {withCredentials: true},
            data: {
                action: 'bookly_custom_fields_load_tab',
                csrf_token: BooklyL10nGlobal.csrf_token,
                tab: tab
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $tab_container.html(response.data.html);
                    custom_fields = response.data.custom_fields;
                    if (tab === 'general') {
                        const $fields = $("#bookly-custom-fields"),
                            $cf_per_service = $('#bookly_custom_fields_per_service'),
                            $mergeRepeatingBlock = $('#bookly-js-merge-repeating'),
                            $cf_merge_repeating = $('#bookly_custom_fields_merge_repeating')
                        ;

                        Sortable.create($fields[0], {
                            handle: '.bookly-js-draghandle.bookly-js-reorder-cf'
                        });

                        $cf_merge_repeating.data('default', $cf_merge_repeating.val());

                        $cf_per_service
                            .data('default', $cf_per_service.val())
                            .change(function() {
                                $mergeRepeatingBlock.toggle(this.value == '1');
                                $('.bookly-js-services').booklyDropdown(this.value == '1' ? 'show' : 'hide');
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
                         * Submit custom fields form.
                         */
                        $save_btn.off().on('click', function(e) {
                            e.preventDefault();
                            let ladda = Ladda.create(this),
                                data = {
                                    fields: [],
                                    per_service: $cf_per_service.val(),
                                    merge_repeating: $cf_merge_repeating.val()
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
                                field.id = $this.data('bookly-field-id');
                                field.services = $this.find('.bookly-js-services').booklyDropdown('getSelected');
                                data.fields.push(field);
                            });
                            $.ajax({
                                type: 'POST',
                                url: ajaxurl,
                                xhrFields: {withCredentials: true},
                                data: booklySerialize.buildRequestData('bookly_custom_fields_save_custom_fields', data),
                                complete: function() {
                                    ladda.stop();
                                    booklyAlert({success: [BooklyCustomFieldsL10n.saved]});
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
                            //type, id, label, description, required, services, min, max
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
                            if (!field.hasOwnProperty('required')) {
                                field.required = false;
                            }
                            if (!field.hasOwnProperty('services')) {
                                field.services = [];
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
                                .find('.bookly-js-use-limits').prop({id: 'bookly-limits-' + field.id, checked: field.limits})
                                .next('label').attr('for', 'bookly-limits-' + field.id).end()
                                .end()
                                .find('.bookly-js-limits').toggle(field.limits).end()
                                .find('.bookly-js-label').val(field.label).end()
                                .find('.bookly-js-description').val(field.description).end()
                                .find('.bookly-js-delimiter').val(field.delimiter).end()
                                .find('.bookly-js-replace-code').text(' - {custom_field#' + field.id + '}').end()
                                .find('.bookly-js-services')
                                .booklyDropdown()
                                .booklyDropdown($cf_per_service.val() == '1' ? 'show' : 'hide')
                                .booklyDropdown('setSelected', field.services || [])
                            ;
                            addVisibilityButton($new_field, field.visible);

                            $new_field.find('.bookly-js-use-limits').on('change', function() {
                                $new_field.find('.bookly-js-limits').toggle($(this).prop('checked'));
                            });
                            // Date field.
                            $new_field.find('.bookly-js-date').daterangepicker({
                                parentEl: $('.bookly-js-custom-fields-wrap').closest('.bookly\\:card'),
                                singleDatePicker: true,
                                showDropdowns: true,
                                autoUpdateInput: true,
                                locale: BooklyCustomFieldsL10n.datePicker
                            }, function() {});
                            if (field.type === 'date') {
                                if (field.min !== '') {
                                    $new_field.find('.bookly-js-min-value').data('daterangepicker').setStartDate(moment(field.min));
                                }
                                if (field.max !== '') {
                                    $new_field.find('.bookly-js-max-value').data('daterangepicker').setStartDate(moment(field.max));
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
                                    .attr('title', BooklyCustomFieldsL10n.visible);
                            } else {
                                $button
                                    .addClass('fa-eye-slash')
                                    .addClass('text-muted')
                                    .attr('title', BooklyCustomFieldsL10n.backendOnly);
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
                                $custom_field = $ul.closest('.bookly-js-custom-field'),
                                cf_id = $custom_field.data('booklyFieldId'),
                                option_id = 0;
                            $custom_field.find('input.bookly-js-default').each(function() {
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
                            $.each(custom_fields, function(i, field) {
                                let $new_field = addField(field);
                                // add children
                                if (field.items) {
                                    $.each(field.items, function(i, value) {
                                        addItem($new_field.find('ul.bookly-js-items'), field.type + '-item', value, field.default);
                                    });
                                }
                            });

                            $cf_merge_repeating.val($cf_merge_repeating.data('default'));
                            $cf_per_service.val($cf_per_service.data('default'));
                            $cf_per_service.change();

                            $(':focus').blur();
                        }

                    } else {
                        // Conditions tab.
                        let $add_condition = $('#bookly-add-condition'),
                            $template = $('#bookly-template'),
                            $conditions = $('#bookly-custom-fields-conditions'),
                            custom_fields_conditions = response.data.custom_fields_conditions;

                        // Disable conditions if no custom fields with options available.
                        if ($template.find('.bookly-js-custom-fields-source option').length === 0) {
                            $template.find('.bookly-js-custom-fields-source').prop('disabled', true);
                            $template.find('.bookly-js-custom-fields-equal').prop('disabled', true);
                            $template.find('.bookly-js-custom-fields-values').prop('disabled', true);
                        }

                        resetConditions();

                        $add_condition.on('click', function() {
                            $conditions.append($template.html());
                            prepareValues($conditions.find('.bookly-js-custom-fields-condition:last .bookly-js-custom-fields-source'));
                        });
                        $conditions.on('click', '.bookly-js-custom-fields-condition button.btn-danger', function() {
                            $(this).closest('.bookly-js-custom-fields-condition').remove();
                        });
                        $conditions.on('change', '.bookly-js-custom-fields-source', function() {
                            prepareValues($(this));
                        });
                        $reset_btn.off().on('click', function() {
                            resetConditions();
                        });
                        $save_btn.off().on('click', function() {
                            e.preventDefault();
                            let ladda = Ladda.create(this),
                                conditions = [];
                            ladda.start();
                            $conditions.find('.bookly-js-custom-fields-condition').each(function() {
                                let value = $(this).find('.bookly-js-custom-fields-values ul').booklyDropdown('getSelected');
                                if (value.length) {
                                    conditions.push({
                                        source: $(this).find('.bookly-js-custom-fields-source').val(),
                                        target: $(this).find('.bookly-js-custom-fields-target').val(),
                                        equal: $(this).find('.bookly-js-custom-fields-equal').val(),
                                        value: value,
                                    });
                                }
                            });
                            $.ajax({
                                type: 'POST',
                                url: ajaxurl,
                                xhrFields: {withCredentials: true},
                                data: {
                                    action: 'bookly_custom_fields_save_conditions',
                                    csrf_token: BooklyL10nGlobal.csrf_token,
                                    conditions: conditions
                                },
                                dataType: 'json',
                                success: function() {
                                    ladda.stop();
                                    booklyAlert({success: [BooklyCustomFieldsL10n.saved]});
                                }
                            });
                        });

                        function prepareValues($source) {
                            let custom_field = custom_fields.find(function(i) { return i.id === parseInt($source.val())}),
                                $values = $source.closest('.bookly-js-custom-fields-condition').find('.bookly-js-custom-fields-values');
                            $values.empty();
                            $values.html($("<ul></ul>"));
                            $.each(custom_field.items, function(i, item) {
                                $values.find('ul').append($("<li></li>").data("value", item).text(item));
                            });
                            $values.find('ul').booklyDropdown();
                        }

                        function resetConditions() {
                            $conditions.html('');
                            $.each(custom_fields_conditions, function(i, condition) {
                                if (custom_fields.find(function(i) { return i.id === parseInt(condition.source); }) !== undefined) {
                                    $conditions.append($template.html());
                                    let $_template = $conditions.find('.bookly-js-custom-fields-condition:last');
                                    $_template.find('.bookly-js-custom-fields-target').val(condition.target);
                                    $_template.find('.bookly-js-custom-fields-source').val(condition.source);
                                    prepareValues($_template.find('.bookly-js-custom-fields-source'));
                                    $_template.find('.bookly-js-custom-fields-equal').val(condition.equal);
                                    $_template.find('.bookly-js-custom-fields-values ul').booklyDropdown('setSelected', condition.value);
                                }
                            });
                        }
                    }
                }
            }
        });
    }).first().trigger('click');
});