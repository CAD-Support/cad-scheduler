(function ($) {
    'use strict';
    window.booklyCustomerCabinet = function (Options) {
        let $container = $('.' + Options.form_id);
        if (!$container.length) {
            return;
        }
        let $tabs = $('.bookly-js-tabs li a', $container),
            sections_ready = [];

        // Appointments section — ported to BooklyDatatables.showForm().
        function initAppointments($container) {
            const L = BooklyCustomerCabinetL10n;
            const dt = L.datatables;
            const ts = dt.customer_cabinet_appointments;
            const mountId = Options.form_id + '-appointments';

            var $reschedule_dialog = $('#bookly-customer-cabinet-reschedule-dialog', $container),
                $reschedule_date = $('#bookly-reschedule-date', $reschedule_dialog),
                $reschedule_time = $('#bookly-reschedule-time', $reschedule_dialog),
                $reschedule_error = $('#bookly-reschedule-error', $reschedule_dialog),
                $reschedule_save = $('#bookly-save', $reschedule_dialog),
                $cancel_dialog = $('#bookly-customer-cabinet-cancel-dialog', $container),
                $cancel_button = $('#bookly-yes', $cancel_dialog),
                $cancel_reason = $('#bookly-cancel-reason', $cancel_dialog),
                $cancel_reason_error = $('#bookly-cancel-reason-error', $cancel_dialog);

            // Tailwind classes per appointment status — used by the status column badge
            // (keyed by status_code; custom statuses fall back to the neutral default).
            const statusBadgeClass = {
                pending:    'bookly:bg-amber-100 bookly:text-amber-800 bookly:border-amber-200',
                approved:   'bookly:bg-green-100 bookly:text-green-800 bookly:border-green-200',
                cancelled:  'bookly:bg-gray-100 bookly:text-gray-700 bookly:border-gray-200',
                rejected:   'bookly:bg-red-100 bookly:text-red-800 bookly:border-red-200',
                waitlisted: 'bookly:bg-purple-100 bookly:text-purple-800 bookly:border-purple-200',
                done:       'bookly:bg-blue-100 bookly:text-blue-800 bookly:border-blue-200',
            };
            const defaultBadgeClass = 'bookly:bg-gray-100 bookly:text-gray-700 bookly:border-gray-200';

            // Row captured when a Cancel/Reschedule/Payment button is clicked — replaces the
            // legacy DataTables `row` closure. The dialog handlers read ca_id / raw dates from it.
            var currentRow = {};

            // Filter state.
            var dateValue = undefined; // undefined = any (legacy default)
            var staffValue = '';
            var serviceValue = '';

            const { today, getLocalTimeZone, parseDate } = window.BooklyDatatables.calendarDate;
            const tz = getLocalTimeZone();
            const t = today(tz);
            const startOfMonth = (d) => d.set({ day: 1 });
            const endOfMonth = (d) => d.set({ day: 1 }).add({ months: 1 }).subtract({ days: 1 });

            const datePresets = [
                { label: L.dateRange.today,     range: { start: t,                                  end: t                                  } },
                { label: L.dateRange.tomorrow,  range: { start: t.add({ days: 1 }),                 end: t.add({ days: 1 })                 } },
                { label: L.dateRange.next_7,    range: { start: t,                                  end: t.add({ days: 7 })                 } },
                { label: L.dateRange.next_30,   range: { start: t,                                  end: t.add({ days: 30 })                } },
                { label: L.dateRange.thisMonth, range: { start: startOfMonth(t),                    end: endOfMonth(t)                      } },
                { label: L.dateRange.nextMonth, range: { start: startOfMonth(t.add({ months: 1 })), end: endOfMonth(t.add({ months: 1 }))   } },
                { label: L.dateRange.yesterday, range: { start: t.subtract({ days: 1 }),            end: t.subtract({ days: 1 })            } },
                { label: L.dateRange.last_7,    range: { start: t.subtract({ days: 7 }),            end: t                                  } },
                { label: L.dateRange.last_30,   range: { start: t.subtract({ days: 30 }),           end: t                                  } },
            ];
            if (L.tasks.enabled) {
                datePresets.push({ label: L.tasks.title, range: { tasks: true, start: t, end: t.add({ days: 1 }) } });
            }

            const ymd = (d) => d.year + '-' + String(d.month).padStart(2, '0') + '-' + String(d.day).padStart(2, '0');
            function serializeDate(value) {
                if (!value) return 'any';
                if (value.tasks) return 'null';
                if (value.start && value.end) return ymd(value.start) + ' - ' + ymd(value.end);
                return 'any';
            }

            function meetingRender(urlField) {
                return function (data, type, row) {
                    var url = BooklyDatatables.escapeHtml(row[urlField]);
                    switch (data) {
                        case 'zoom':        return '<a class="badge badge-primary" href="' + url + '" target="_blank"><i class="fas fa-video fa-fw"></i> Zoom <i class="fas fa-external-link-alt fa-fw"></i></a>';
                        case 'google_meet': return '<a class="badge badge-primary" href="' + url + '" target="_blank"><i class="fas fa-video fa-fw"></i> Google Meet <i class="fas fa-external-link-alt fa-fw"></i></a>';
                        case 'jitsi':       return '<a class="badge badge-primary" href="' + url + '" target="_blank"><i class="fas fa-video fa-fw"></i> Jitsi Meet <i class="fas fa-external-link-alt fa-fw"></i></a>';
                        case 'bbb':         return '<a class="badge badge-primary" href="' + url + '" target="_blank"><i class="fas fa-video fa-fw"></i> BigBlueButton <i class="fas fa-external-link-alt fa-fw"></i></a>';
                        case 'teams':       return '<a class="badge badge-primary" href="' + url + '" target="_blank"><i class="fas fa-video fa-fw"></i> Microsoft Teams <i class="fas fa-external-link-alt fa-fw"></i></a>';
                        default:            return '';
                    }
                };
            }

            /**
             * Build columns from the shortcode-selected appointment_columns.
             */
            const columns = [];
            Object.keys(Options.appointment_columns).forEach(function (objectKey) {
                const column = Options.appointment_columns[objectKey];
                const before = columns.length;
                switch (column) {
                    case 'date':
                        columns.push({
                            data: 'start_date',
                            // Time (and timezone, when shown) as muted secondary lines under the date.
                            secondary: row => [row.start_time, row.start_tz].filter(Boolean),
                        });
                        break;
                    case 'location':
                        columns.push({ data: 'location', render: BooklyDatatables.escapeHtml() });
                        break;
                    case 'service':
                        columns.push({
                            data: 'service.title',
                            render: data => BooklyDatatables.escapeHtml(data),
                            secondary: row => (row.service.extras || []).map(e => BooklyDatatables.escapeHtml(e.title)),
                        });
                        break;
                    case 'staff':
                        columns.push({ data: 'staff_name', render: BooklyDatatables.escapeHtml() });
                        break;
                    case 'status':
                        columns.push({
                            data: 'status',
                            badge: row => statusBadgeClass[row.status_code] || defaultBadgeClass,
                        });
                        break;
                    case 'category':
                        columns.push({ data: 'category', render: BooklyDatatables.escapeHtml() });
                        break;
                    case 'duration':
                        // Rendered as a muted secondary line under Service (matches backend
                        // Appointments) — not its own column. Skipped top-level via `parent`.
                        columns.push({ data: 'duration', parent: 'service', render: BooklyDatatables.escapeHtml() });
                        break;
                    case 'online_meeting':
                        columns.push({ data: 'online_meeting_provider', orderable: false, render: meetingRender('online_meeting_url') });
                        break;
                    case 'join_online_meeting':
                        columns.push({ data: 'online_meeting_provider', orderable: false, render: meetingRender('join_online_meeting_url') });
                        break;
                    case 'price':
                        columns.push({
                            data: 'price',
                            render: function (data, type, row) {
                                if (row.payment_id !== null) {
                                    return '<button type="button" class="btn btn-sm btn-default" data-action="show-payment" data-payment_id="' + row.payment_id + '">' + L.payment + '</button>';
                                }
                                return BooklyDatatables.escapeHtml(data);
                            }
                        });
                        break;
                    case 'cancel':
                    case 'reschedule':
                        // Surfaced as row actions (hover strip / single-row selection toolbar),
                        // not as columns — avoids the responsive column-priority problem and keeps
                        // the actions reachable at any screen size. See options.rowActions below.
                        break;
                    default:
                        if (column.match('^custom_field')) {
                            columns.push({ data: 'custom_fields.' + column.substring(13), orderable: false, render: BooklyDatatables.escapeHtml() });
                        }
                        break;
                }
                if (columns.length > before) {
                    const c = columns[columns.length - 1];
                    c.title = (ts.titles && ts.titles[column]) || column;
                    c.name = column;
                    c.show = true;
                }
            });

            // Whether the shortcode enabled the cancel / reschedule actions.
            const cols = Object.values(Options.appointment_columns);
            const hasCancel = cols.indexOf('cancel') !== -1;
            const hasReschedule = cols.indexOf('reschedule') !== -1;

            /**
             * Filters (date / staff / service) — only when the shortcode enables them.
             */
            const filters = [];
            if (Options.filters) {
                filters.push({
                    type: 'dateRange',
                    name: 'date',
                    label: L.filters.date,
                    initialValue: dateValue,
                    presets: datePresets,
                    onChange: (v) => { dateValue = v; },
                });
                filters.push({
                    type: 'select',
                    name: 'staff',
                    label: L.filters.staff,
                    initialValue: staffValue,
                    searchPlaceholder: L.filters.searchPlaceholder,
                    options: (L.filterOptions.staff || []).map(s => ({ value: String(s.id), label: s.full_name })),
                    onChange: (v) => { staffValue = v; },
                });
                filters.push({
                    type: 'select',
                    name: 'service',
                    label: L.filters.service,
                    initialValue: serviceValue,
                    searchPlaceholder: L.filters.searchPlaceholder,
                    options: (L.filterOptions.services || []).map(s => ({ value: String(s.id), label: s.title })),
                    onChange: (v) => { serviceValue = v; },
                });
            }

            const options = {
                // Customer-facing: no admin Settings/Refresh, no column persistence.
                reloadButton: false,
                settingsButton: false,
                ajax: {
                    url: BooklyL10nGlobal.ajax_url_frontend,
                    method: 'POST',
                    data: function () {
                        return {
                            action: 'bookly_customer_cabinet_get_appointments',
                            appointment_columns: Options.appointment_columns,
                            show_timezone: Options.show_timezone ? 1 : 0,
                            csrf_token: BooklyL10nGlobal.csrf_token,
                            time_zone: typeof Intl === 'object' ? Intl.DateTimeFormat().resolvedOptions().timeZone : undefined,
                            time_zone_offset: new Date().getTimezoneOffset(),
                            date: serializeDate(dateValue),
                            staff: staffValue,
                            service: serviceValue,
                            filter: {},
                        };
                    },
                },
                columns: columns,
                tableSettings: Object.assign({}, ts, {
                    settings: Object.assign({}, ts.settings, { order: [[0, 'desc']], page_length: 10 }),
                    l10n: Object.assign({}, dt.l10n, { zeroRecords: L.zeroRecords }),
                }),
                filters: filters,
            };
            options.datePicker = BooklyL10nGlobal.datePicker;

            // Cancel/Reschedule live as row actions (hover strip + single-row selection toolbar),
            // so checkboxes stay ON to make them reachable on touch. When the shortcode enables
            // neither action there's nothing to act on — drop the checkboxes entirely.
            const hasActions = hasCancel || hasReschedule;
            if (hasActions) {
                options.getId = row => String(row.ca_id);
                options.rowActions = function (row) {
                    const actions = [];
                    if (hasReschedule && row.allow_reschedule === 'allow') {
                        actions.push({
                            label: L.reschedule,
                            icon: 'calendar',
                            variant: 'outline',
                            click: function (r) {
                                currentRow = {
                                    ca_id: r.ca_id,
                                    raw_start_date: r.raw_start_date || null,
                                    raw_end_date: r.raw_end_date == null ? null : r.raw_end_date,
                                    full_day: !!r.full_day,
                                };
                                $reschedule_dialog.booklyModal('show');
                            },
                        });
                    }
                    if (hasCancel && row.allow_cancel === 'allow') {
                        actions.push({
                            label: L.cancel,
                            icon: 'ban',
                            variant: 'destructive',
                            click: function (r) {
                                currentRow = { ca_id: r.ca_id };
                                $cancel_dialog.booklyModal('show');
                            },
                        });
                    }
                    return actions;
                };
            } else {
                options.noCheckboxes = true;
            }

            const bt = BooklyDatatables.showForm(mountId, options);
            const $mount = $('#' + mountId);

            // Payment details (rendered as a button inside the Price column).
            $mount.on('click', '[data-action=show-payment]', function () {
                BooklyPaymentDetailsDialog.showDialog({ payment_id: $(this).data('payment_id') });
            });

            // Cancel appointment dialog.
            $cancel_button.on('click', function () {
                if ($cancel_reason.length && $cancel_reason.val() === '') {
                    $cancel_reason_error.show();
                } else {
                    var ladda = Ladda.create(this);
                    ladda.start();
                    $.ajax({
                        url: BooklyL10nGlobal.ajax_url_frontend,
                        type: 'POST',
                        data: {
                            action: 'bookly_customer_cabinet_cancel_appointment',
                            csrf_token: BooklyL10nGlobal.csrf_token,
                            ca_id: currentRow.ca_id,
                            reason: $cancel_reason.val()
                        },
                        dataType: 'json',
                        success: function (response) {
                            ladda.stop();
                            if (response.success) {
                                $cancel_dialog.booklyModal('hide');
                                bt.reload();
                            } else {
                                booklyAlert({ error: [L.errors.cancel] });
                            }
                        }
                    });
                }
            });

            // Reschedule appointment dialog.
            $reschedule_date.daterangepicker(
                {
                    parentEl: '#bookly-customer-cabinet-reschedule-dialog',
                    singleDatePicker: true,
                    showDropdowns: true,
                    autoUpdateInput: true,
                    minDate: moment().add(L.minDate, 'days'),
                    maxDate: moment().add(L.maxDate, 'days'),
                    locale: BooklyL10nGlobal.datePicker
                },
                function () {}
            ).on('change', function () {
                $reschedule_save.prop('disabled', true);
                $reschedule_time.html('');
                $reschedule_error.hide();
                $.ajax({
                    url: BooklyL10nGlobal.ajax_url_frontend,
                    type: 'POST',
                    data: {
                        action: 'bookly_customer_cabinet_get_day_schedule',
                        csrf_token: BooklyL10nGlobal.csrf_token,
                        ca_id: currentRow.ca_id,
                        date: $reschedule_date.data('daterangepicker').startDate.format('YYYY-MM-DD')
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.data.length) {
                            var time_options = response.data[0].options;
                            $.each(time_options, function (index, option) {
                                var $option = $('<option/>');
                                $option.text(option.title).val(option.value);
                                if (option.disabled) {
                                    $option.attr('disabled', 'disabled');
                                }
                                $reschedule_time.append($option);
                            });
                            $reschedule_save.prop('disabled', false);
                        } else {
                            $reschedule_error.text(L.noTimeslots).show();
                        }
                    }
                });
            });
            $reschedule_dialog.on('show.bs.modal', function (e) {
                if (currentRow.full_day) {
                    $reschedule_time.parent(2).hide();
                } else {
                    $reschedule_time.parent(2).show();
                }
                let previous = $reschedule_date.data('daterangepicker').startDate.format('YYYY-MM-DD');
                $reschedule_date.data('daterangepicker').setStartDate(moment(currentRow.raw_start_date));
                $reschedule_date.data('daterangepicker').setEndDate(moment(currentRow.raw_start_date));
                $reschedule_date.data('daterangepicker').maxDate = currentRow.raw_end_date === null
                    ? moment().add(L.maxDate, 'days')
                    : moment(currentRow.raw_end_date);
                if (previous === $reschedule_date.data('daterangepicker').startDate.format('YYYY-MM-DD')) {
                    // Even if the date hasn't changed, forcibly inform the object that it has been changed
                    $reschedule_date.trigger('change');
                }
            });
            $reschedule_save.on('click', function (e) {
                e.preventDefault();
                var ladda = Ladda.create(this);
                ladda.start();
                $.ajax({
                    url: BooklyL10nGlobal.ajax_url_frontend,
                    type: 'POST',
                    data: {
                        action: 'bookly_customer_cabinet_save_reschedule',
                        csrf_token: BooklyL10nGlobal.csrf_token,
                        ca_id: currentRow.ca_id,
                        slot: $reschedule_time.val(),
                    },
                    dataType: 'json',
                    success: function (response) {
                        ladda.stop();
                        if (response.success) {
                            $reschedule_dialog.booklyModal('hide');
                            bt.reload();
                        } else {
                            booklyAlert({ error: [L.errors.reschedule] });
                        }
                    }
                });
            });
        }

        // Profile section
        function initProfile($container) {
            var $profile_content = $('.bookly-js-customer-cabinet-content-profile', $container),
                $form = $('form', $profile_content),
                $delete_btn = $('button.bookly-js-delete-profile', $profile_content),
                $delete_modal = $('.bookly-js-customer-cabinet-delete-dialog', $container),
                $delete_loading = $('.bookly-loading', $delete_modal),
                $approve_deleting = $('.bookly-js-approve-deleting', $delete_modal),
                $denied_deleting = $('.bookly-js-denied-deleting', $delete_modal),
                $confirm_delete_btn = $('.bookly-js-confirm-delete', $delete_modal),
                $phone_field = $('.bookly-js-user-phone-input', $profile_content),
                $save_btn = $('button.bookly-js-save-profile', $profile_content)
            ;

            function fileFieldOnChange($uploaded) {
                let $container = $uploaded.closest('.form-group'),
                    $upload = $('.bookly-js-upload', $container);
                if ($uploaded.data('slug') == '') {
                    $uploaded.hide();
                    $upload.show();
                } else {
                    $uploaded.show();
                    $upload.hide();
                }
                return $container;
            }

            $('.form-group[data-id^="customer_information_"] .bookly-js-uploaded[data-slug]', $profile_content).each(function () {
                let $uploaded = $(this),
                    $container = fileFieldOnChange($uploaded);
                $('.bookly-js-delete', $container).on('click', function (e) {
                    e.preventDefault();
                    if (confirm(BooklyCustomerCabinetL10n.are_you_sure)) {
                        $.ajax({
                            url: BooklyL10nGlobal.ajax_url_frontend,
                            type: 'POST',
                            data: {
                                action: 'bookly_files_delete_customer_information_field',
                                csrf_token: BooklyL10nGlobal.csrf_token,
                                slug: $('.bookly-js-uploaded', $container).data('slug'),
                            },
                            dataType: 'json',
                            success: function (response) {
                                $uploaded.data('slug', '');
                                $('input[type=hidden]', $container).val('');
                                fileFieldOnChange($uploaded);
                            }
                        });
                    }
                });

                $('.bookly-js-download', $container).on('click', function (e) {
                    e.preventDefault();
                    window.open(BooklyL10nGlobal.ajax_url_frontend + (BooklyL10nGlobal.ajax_url_frontend.indexOf('?') > 0 ? '&' : '?') + 'action=bookly_files_download&slug=' + $('.bookly-js-uploaded', $container).data('slug') + '&csrf_token=' + BooklyL10nGlobal.csrf_token, '_blank');
                });

                $('input[type=file]', $container)
                    .on('change', function (e) {
                        let $fg = $container.closest('.form-group');
                        const formData = new FormData();
                        formData.append('customer_information_id', $(this).data('id'));
                        formData.append('action', 'bookly_files_upload');
                        formData.append('csrf_token', BooklyL10nGlobal.csrf_token);
                        formData.append('files[]', e.target.files[0]);

                        fetch(BooklyL10nGlobal.ajax_url_frontend, {
                            method: 'POST',
                            body: formData
                        })
                            .then(function (response) {
                                return response.json();
                            })
                            .then(function (result) {
                                if (result.success) {
                                    $('div:first', $uploaded).html(result.data.name);
                                    $uploaded.data('slug', result.data.slug);
                                    $('input[type=hidden]', $container).val(result.data.slug);
                                    fileFieldOnChange($uploaded);
                                    $('.bookly-js-error', $fg).html('');
                                } else {
                                    $('.bookly-js-error', $fg).html(result.data.error);
                                }
                            });
                    });
            });

            if (Options.intlTelInput.enabled) {
                window.booklyIntlTelInput($phone_field.get(0), {
                    preferredCountries: [Options.intlTelInput.country],
                    initialCountry: Options.intlTelInput.country,
                    geoIpLookup: function (callback) {
                        $.get('https://ipinfo.io', function () {}, 'jsonp').always(function (resp) {
                            var countryCode = (resp && resp.country) ? resp.country : '';
                            callback(countryCode);
                        });
                    },
                });
            }
            $save_btn.on('click', function (e) {
                e.preventDefault();
                var ladda = Ladda.create(this);
                ladda.start();
                $('.is-invalid', $profile_content).removeClass('is-invalid');
                $('.form-group .bookly-js-error').remove();

                let data = booklySerialize.form($form);
                if (data.phone !== undefined && data.phone !== '') {
                    data.phone = Options.intlTelInput.enabled ? booklyGetPhoneNumber($phone_field.get(0)) : $phone_field.val();
                }
                $('.bookly-js-customer-cabinet-content-profile .bookly-js-control-input.bookly-js-date', $container).each(function () {
                    if ($(this).val() != '') {
                        let id = $(this).closest('.form-group').data('id').replace('customer_information_', '');
                        data.info_fields[id] = $(this).data('daterangepicker').startDate.format('YYYY-MM-DD');
                    }
                });
                data.columns = Options.profile_parameters;

                $.ajax({
                    url: BooklyL10nGlobal.ajax_url_frontend,
                    type: 'POST',
                    data: booklySerialize.buildRequestData('bookly_customer_cabinet_save_profile', data),
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            booklyAlert({success: [BooklyCustomerCabinetL10n.profile_update_success]});
                            if ($('[name="current_password"]', $profile_content).val()) {
                                window.location.reload();
                            }
                        } else {
                            $.each(response.errors, function (name, value) {
                                if (name == 'info_fields') {
                                    $.each(value, function (id, text) {
                                        var $form_group = $('.form-group[data-id="customer_information_' + id + '"]', $profile_content);
                                        $form_group.find('.bookly-js-control-input').addClass('is-invalid');
                                        $form_group.append('<div class="bookly-js-error text-danger">' + text + '</div>');
                                    });
                                } else {
                                    var $form_group = $('.form-group [id="bookly_' + name + '"]', $profile_content).closest('.form-group');
                                    $form_group.find('.bookly-js-control-input').addClass('is-invalid');
                                    $form_group.append('<div class="bookly-js-error text-danger">' + value + '</div>');
                                }
                            });
                            $('html, body').animate({
                                scrollTop: $profile_content.find('.is-invalid').first().offset().top - 100
                            }, 1000);
                        }
                        ladda.stop();
                    }
                });
            });
            $delete_btn.on('click', function (e) {
                e.preventDefault();
                $approve_deleting.hide();
                $denied_deleting.hide();
                $delete_loading.show();
                $delete_modal.booklyModal('show');
                $.ajax({
                    url: BooklyL10nGlobal.ajax_url_frontend,
                    type: 'POST',
                    data: {
                        action: 'bookly_customer_cabinet_check_future_appointments',
                        csrf_token: BooklyL10nGlobal.csrf_token,
                    },
                    dataType: 'json',
                    success: function (response) {
                        $delete_loading.hide();
                        if (response.success) {
                            $approve_deleting.show();
                        } else {
                            $denied_deleting.show()
                        }
                    }
                });
            });
            $confirm_delete_btn.on('click', function (e) {
                e.preventDefault();
                var ladda = Ladda.create(this);
                ladda.start();
                $.ajax({
                    url: BooklyL10nGlobal.ajax_url_frontend,
                    type: 'POST',
                    data: {
                        action: 'bookly_customer_cabinet_delete_profile',
                        csrf_token: BooklyL10nGlobal.csrf_token,
                    },
                    dataType: 'json',
                    success: function (response) {
                        ladda.stop();
                        if (response.success) {
                            $delete_modal.booklyModal('hide');
                            window.location.reload();
                        }
                    }
                });
            });
        }

        function initSection(section) {
            if ($.inArray(section, sections_ready) === -1) {
                switch (section) {
                    case 'appointments':
                        initAppointments($container);
                        sections_ready.push(section);
                        break;
                    case 'profile':
                        initProfile($container);
                        sections_ready.push(section);
                        break;
                }
            }
        }

        if (Options.tabs.length > 1) {
            // Tabs
            $tabs.on('click', function () {
                var section = $(this).attr('href').substring(1);
                $('.bookly-js-customer-cabinet-content', $container).hide();
                $('.bookly-js-customer-cabinet-content-' + section, $container).show();
                initSection(section);
            });
            $tabs.first().trigger('click');
        } else {
            var section = Options.tabs[0];
            $('.bookly-js-customer-cabinet-content', $container).show();
            initSection(section);
        }

        $container
            .on('click', '[data-type="open-modal"]', function () {
                $($(this).attr('data-target')).booklyModal('show');
            });

        $('.bookly-js-customer-cabinet-content-profile .bookly-js-control-input.bookly-js-date', $container).each(function () {
            let $elem = $(this),
                init = {
                    parentEl: $elem.closest('form'),
                    singleDatePicker: true,
                    showDropdowns: true,
                    locale: BooklyL10nGlobal.datePicker,
                    autoUpdateInput: false
                };
            if ($elem.data('value')) {
                $elem.val(moment($elem.data('value')).format(BooklyL10nGlobal.datePicker.format));
            }
            if ($elem.data('min')) {
                init.minDate = moment($elem.data('min'));
            }
            if ($elem.data('max')) {
                init.maxDate = moment($elem.data('max'));
            }

            $elem
                .daterangepicker(init, function () {})
                .on('apply.daterangepicker', function (ev, picker) {
                    $elem.val(picker.startDate.format(BooklyL10nGlobal.datePicker.format));
                });
        });
    }
})(jQuery);
