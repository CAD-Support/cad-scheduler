(function(wp, $) {
    var el = wp.element.createElement,
        components = wp.components,
        blockControls = wp.editor.BlockControls,
        inspectorControls = wp.editor.InspectorControls,
        attributes = {
            short_code: {
                type: 'string',
                default: '[bookly-customer-cabinet]'
            },
            tab_appointments: {
                type: 'boolean',
                default: true
            },
            tab_profile: {
                type: 'boolean',
                default: true
            },
            appointments_filters: {
                type: 'boolean',
                default: true
            },
            appointments_date: {
                type: 'boolean',
                default: true
            },
            appointments_timezone: {
                type: 'boolean',
                default: true
            },
            appointments_category: {
                type: 'boolean',
                default: true
            },
            appointments_service: {
                type: 'boolean',
                default: true
            },
            appointments_duration: {
                type: 'boolean',
                default: true
            },
            appointments_staff: {
                type: 'boolean',
                default: true
            },
            appointments_price: {
                type: 'boolean',
                default: true
            },
            appointments_status: {
                type: 'boolean',
                default: true
            },
            join_online_meeting: {
                type: 'boolean',
                default: false
            },
            online_meeting: {
                type: 'boolean',
                default: false
            }
        };

    if (BooklyCustomerCabinetL10n.locationsActive == '1') {
        attributes.appointments_location = {
            type: 'boolean',
            default: true
        }
    }

    $.each(BooklyCustomerCabinetL10n.customFields, function(index, field) {
        if (field.type !== 'file') {
            var attr_name = 'custom_field_' + field.id;
            attributes[attr_name] = {
                type: 'boolean',
                default: false
            };
        }
    });

    attributes.appointments_cancel = {
        type: 'boolean',
        default: true
    };
    attributes.appointments_reason = {
        type: 'boolean',
        default: true
    };
    attributes.appointments_reschedule = {
        type: 'boolean',
        default: true
    };
    attributes.profile_name = {
        type: 'boolean',
        default: true
    };
    attributes.profile_email = {
        type: 'boolean',
        default: true
    };
    attributes.profile_phone = {
        type: 'boolean',
        default: true
    };
    attributes.profile_birthday = {
        type: 'boolean',
        default: true
    };
    attributes.profile_address = {
        type: 'boolean',
        default: true
    };
    attributes.profile_full_address = {
        type: 'boolean',
        default: true
    };
    attributes.profile_wp_password = {
        type: 'boolean',
        default: true
    };
    $.each(BooklyCustomerCabinetL10n.customerInformation, function(index, field) {
        var attr_name = 'customer_information_' + field.id;
        attributes[attr_name] = {
            type: 'boolean',
            default: false
        };
    });
    attributes.profile_delete = {
        type: 'boolean',
        default: true
    };

    wp.blocks.registerBlockType('bookly/customer-cabinet', {
        title: BooklyCustomerCabinetL10n.block.title,
        description: BooklyCustomerCabinetL10n.block.description,
        icon: el('svg', {width: '20', height: '20', viewBox: "0 0 64 64"},
            el('path', {style: {fill: "rgb(0, 0, 0)"}, d: "M 8 0 H 56 A 8 8 0 0 1 64 8 V 22 H 0 V 8 A 8 8 0 0 1 8 0 Z"}),
            el('path', {style: {fill: "rgb(244, 102, 47)"}, d: "M 0 22 H 64 V 56 A 8 8 0 0 1 56 64 H 8 A 8 8 0 0 1 0 56 V 22 Z"}),
            el('rect', {style: {fill: "rgb(98, 86, 86)"}, x: 6, y: 6, width: 52, height: 10}),
            el('rect', {style: {fill: "rgb(242, 227, 227)"}, x: 12, y: 30, width: 40, height: 24}),
            el('ellipse', {style: {fill: "rgb(221, 224, 233)", stroke: 'rgb(0, 0, 0)'}, cx: 44, cy: 48, rx: 16, ry: 12}),
        ),
        category: 'bookly-blocks',
        keywords: [
            'bookly',
            'ratings',
        ],
        supports: {
            customClassName: false,
            html: false
        },
        attributes: attributes,
        edit: function(props) {
            var inspectorTabAppointmentsElements = [],
                inspectorTabProfileElements = [],
                attributes = props.attributes
            ;

            function getShortCode(props, attributes) {
                var short_code = '[bookly-customer-cabinet',
                    tabs = [],
                    appointments = [],
                    profile = [];

                if (attributes.tab_appointments) {
                    tabs.push('appointments');
                }
                if (attributes.tab_profile) {
                    tabs.push('profile');
                }
                if (tabs.length > 0) {
                    short_code += ' tabs="' + tabs.join(',') + '"';
                }

                if (attributes.tab_appointments) {
                    if (attributes.appointments_filters) {
                        appointments.push('filters');
                    }
                    if (attributes.appointments_date) {
                        appointments.push('date');
                    }
                    if (attributes.appointments_timezone) {
                        appointments.push('timezone');
                    }
                    if (attributes.appointments_location) {
                        appointments.push('location');
                    }
                    if (attributes.appointments_category) {
                        appointments.push('category');
                    }
                    if (attributes.appointments_service) {
                        appointments.push('service');
                    }
                    if (attributes.appointments_duration) {
                        appointments.push('duration');
                    }
                    if (attributes.appointments_staff) {
                        appointments.push('staff');
                    }
                    if (attributes.appointments_price) {
                        appointments.push('price');
                    }
                    if (attributes.appointments_status) {
                        appointments.push('status');
                    }
                    if (attributes.online_meeting) {
                        appointments.push('online_meeting');
                    }
                    if (attributes.join_online_meeting) {
                        appointments.push('join_online_meeting');
                    }
                    $.each(BooklyCustomerCabinetL10n.customFields, function(index, field) {
                        if (field.type !== 'file') {
                            var attr_name = 'custom_field_' + field.id;
                            if (attributes[attr_name]) {
                                appointments.push(attr_name)
                            }
                        }
                    });
                    if (attributes.appointments_cancel) {
                        appointments.push('cancel');
                    }
                    if (attributes.appointments_reason) {
                        appointments.push('reason');
                    }
                    if (attributes.appointments_reschedule) {
                        appointments.push('reschedule');
                    }
                    if (appointments.length > 0) {
                        short_code += ' appointments="' + appointments.join(',') + '"';
                    }
                }

                if (attributes.tab_profile) {
                    if (attributes.profile_name) {
                        profile.push('name');
                    }
                    if (attributes.profile_email) {
                        profile.push('email');
                    }
                    if (attributes.profile_phone) {
                        profile.push('phone');
                    }
                    if (attributes.profile_birthday) {
                        profile.push('birthday');
                    }
                    if (attributes.profile_address) {
                        profile.push('address');
                    }
                    if (attributes.profile_full_address) {
                        profile.push('full_address');
                    }
                    if (attributes.profile_wp_password) {
                        profile.push('wp_password');
                    }
                    $.each(BooklyCustomerCabinetL10n.customerInformation, function(index, field) {
                        var attr_name = 'customer_information_' + field.id;
                        if (attributes[attr_name]) {
                            profile.push(attr_name)
                        }
                    });
                    if (attributes.profile_delete) {
                        profile.push('delete');
                    }
                    if (profile.length > 0) {
                        short_code += ' profile="' + profile.join(',') + '"';
                    }
                }

                short_code += ']';

                props.setAttributes({short_code: short_code});

                return short_code;
            }

            inspectorTabAppointmentsElements.push(el(components.PanelRow,
                {},
                el('label', {htmlFor: 'bookly-js-show-tab-appointments'}, BooklyCustomerCabinetL10n.Show),
                el(components.FormToggle, {
                    id: 'bookly-js-show-tab-appointments',
                    checked: attributes.tab_appointments,
                    onChange: function() {
                        return props.setAttributes({tab_appointments: !props.attributes.tab_appointments});
                    },
                })
            ));
            inspectorTabAppointmentsElements.push(el(components.PanelRow,
                {},
                el('label', {htmlFor: 'bookly-js-show-filters'}, BooklyCustomerCabinetL10n.appointment.filters),
                el(components.FormToggle, {
                    id: 'bookly-js-show-filters',
                    checked: attributes.appointments_filters,
                    onChange: function() {
                        return props.setAttributes({appointments_filters: !props.attributes.appointments_filters});
                    },
                })
            ));
            if (props.attributes.tab_appointments) {
                inspectorTabAppointmentsElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-date'}, BooklyCustomerCabinetL10n.appointment.date),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-date',
                        checked: attributes.appointments_date,
                        onChange: function() {
                            return props.setAttributes({appointments_date: !props.attributes.appointments_date});
                        },
                    })
                ));

                if (BooklyCustomerCabinetL10n.locationsActive == '1') {
                    inspectorTabAppointmentsElements.push(el(components.PanelRow,
                        {},
                        el('label', {htmlFor: 'bookly-js-show-location'}, BooklyCustomerCabinetL10n.appointment.location),
                        el(components.FormToggle, {
                            id: 'bookly-js-show-location',
                            checked: attributes.appointments_location,
                            onChange: function() {
                                return props.setAttributes({appointments_location: !props.attributes.appointments_location});
                            },
                        })
                    ));
                }
                inspectorTabAppointmentsElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-timezone'}, BooklyCustomerCabinetL10n.appointment.timezone),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-timezone',
                        checked: attributes.appointments_timezone,
                        onChange: function() {
                            return props.setAttributes({appointments_timezone: !props.attributes.appointments_timezone});
                        },
                    })
                ));
                inspectorTabAppointmentsElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-category'}, BooklyCustomerCabinetL10n.appointment.category),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-category',
                        checked: attributes.appointments_category,
                        onChange: function() {
                            return props.setAttributes({appointments_category: !props.attributes.appointments_category});
                        },
                    })
                ));
                inspectorTabAppointmentsElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-service'}, BooklyCustomerCabinetL10n.appointment.service),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-service',
                        checked: attributes.appointments_service,
                        onChange: function() {
                            return props.setAttributes({appointments_service: !props.attributes.appointments_service});
                        },
                    })
                ));
                inspectorTabAppointmentsElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-duration'}, BooklyCustomerCabinetL10n.appointment.duration),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-duration',
                        checked: attributes.appointments_duration,
                        onChange: function() {
                            return props.setAttributes({appointments_duration: !props.attributes.appointments_duration});
                        },
                    })
                ));
                inspectorTabAppointmentsElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-staff'}, BooklyCustomerCabinetL10n.appointment.staff),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-staff',
                        checked: attributes.appointments_staff,
                        onChange: function() {
                            return props.setAttributes({appointments_staff: !props.attributes.appointments_staff});
                        },
                    })
                ));
                inspectorTabAppointmentsElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-price'}, BooklyCustomerCabinetL10n.appointment.price),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-price',
                        checked: attributes.appointments_price,
                        onChange: function() {
                            return props.setAttributes({appointments_price: !props.attributes.appointments_price});
                        },
                    })
                ));
                inspectorTabAppointmentsElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-status'}, BooklyCustomerCabinetL10n.appointment.status),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-status',
                        checked: attributes.appointments_status,
                        onChange: function() {
                            return props.setAttributes({appointments_status: !props.attributes.appointments_status});
                        },
                    })
                ));
                inspectorTabAppointmentsElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-join-online_meeting'}, BooklyCustomerCabinetL10n.appointment.joinOnlineMeeting),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-join-online_meeting',
                        checked: attributes.join_online_meeting,
                        onChange: function() {
                            return props.setAttributes({join_online_meeting: !props.attributes.join_online_meeting});
                        },
                    })
                ));
                inspectorTabAppointmentsElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-online_meeting'}, BooklyCustomerCabinetL10n.appointment.onlineMeeting),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-online_meeting',
                        checked: attributes.online_meeting,
                        onChange: function() {
                            return props.setAttributes({online_meeting: !props.attributes.online_meeting});
                        },
                    })
                ));

                $.each(BooklyCustomerCabinetL10n.customFields, function(index, field) {
                    if (field.type !== 'file') {
                        var attr_name = 'custom_field_' + field.id,
                            attribute = {};
                        attribute[attr_name] = !props.attributes[attr_name];
                        inspectorTabAppointmentsElements.push(el(components.PanelRow,
                            {},
                            el('label', {htmlFor: 'bookly-js-show-custom-field-' + field.id}, field.label),
                            el(components.FormToggle, {
                                id: 'bookly-js-show-custom-field-' + field.id,
                                checked: attributes[attr_name],
                                onChange: function() {
                                    return props.setAttributes(attribute);
                                },

                            })
                        ));
                        inspectorTabAppointmentsElements.push(el('div', {style: {'margin': '-5px 0 1.5em 0', 'font-style': 'italic'}}, BooklyCustomerCabinetL10n.appointment.customField));
                    }
                });

                inspectorTabAppointmentsElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-cancel'}, BooklyCustomerCabinetL10n.appointment.cancel),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-cancel',
                        checked: attributes.appointments_cancel,
                        onChange: function() {
                            return props.setAttributes({appointments_cancel: !props.attributes.appointments_cancel});
                        },
                    })
                ));
                inspectorTabAppointmentsElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-reason'}, BooklyCustomerCabinetL10n.appointment.reason),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-reason',
                        checked: attributes.appointments_reason,
                        onChange: function() {
                            return props.setAttributes({appointments_reason: !props.attributes.appointments_reason});
                        },
                    })
                ));
                inspectorTabAppointmentsElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-reschedule'}, BooklyCustomerCabinetL10n.appointment.reschedule),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-reschedule',
                        checked: attributes.appointments_reschedule,
                        onChange: function() {
                            return props.setAttributes({appointments_reschedule: !props.attributes.appointments_reschedule});
                        },
                    })
                ));
            }

            inspectorTabProfileElements.push(el(components.PanelRow,
                {},
                el('label', {htmlFor: 'bookly-js-show-tab-profile'}, BooklyCustomerCabinetL10n.Show),
                el(components.FormToggle, {
                    id: 'bookly-js-show-tab-profile',
                    checked: attributes.tab_profile,
                    onChange: function() {
                        return props.setAttributes({tab_profile: !props.attributes.tab_profile});
                    },
                })
            ));

            if (attributes.tab_profile) {
                inspectorTabProfileElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-name'}, BooklyCustomerCabinetL10n.profile.name),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-name',
                        checked: attributes.profile_name,
                        onChange: function() {
                            return props.setAttributes({profile_name: !props.attributes.profile_name});
                        },
                    })
                ));
                inspectorTabProfileElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-email'}, BooklyCustomerCabinetL10n.profile.email),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-email',
                        checked: attributes.profile_email,
                        onChange: function() {
                            return props.setAttributes({profile_email: !props.attributes.profile_email});
                        },
                    })
                ));
                inspectorTabProfileElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-phone'}, BooklyCustomerCabinetL10n.profile.phone),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-phone',
                        checked: attributes.profile_phone,
                        onChange: function() {
                            return props.setAttributes({profile_phone: !props.attributes.profile_phone});
                        },
                    })
                ));
                inspectorTabProfileElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-birthday'}, BooklyCustomerCabinetL10n.profile.birthday),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-birthday',
                        checked: attributes.profile_birthday,
                        onChange: function() {
                            return props.setAttributes({profile_birthday: !props.attributes.profile_birthday});
                        },
                    })
                ));
                inspectorTabProfileElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-address'}, BooklyCustomerCabinetL10n.profile.address),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-address',
                        checked: attributes.profile_address,
                        onChange: function() {
                            return props.setAttributes({profile_address: !props.attributes.profile_address});
                        },
                    })
                ));
                inspectorTabProfileElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-full-address'}, BooklyCustomerCabinetL10n.profile.full_address),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-full-address',
                        checked: attributes.profile_full_address,
                        onChange: function() {
                            return props.setAttributes({profile_full_address: !props.attributes.profile_full_address});
                        },
                    })
                ));
                inspectorTabProfileElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-wp-password'}, BooklyCustomerCabinetL10n.profile.wordpressPassword),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-wp-password',
                        checked: attributes.profile_wp_password,
                        onChange: function() {
                            return props.setAttributes({profile_wp_password: !props.attributes.profile_wp_password});
                        },
                    })
                ));
                $.each(BooklyCustomerCabinetL10n.customerInformation, function(index, field) {
                    var attr_name = 'customer_information_' + field.id,
                        attribute = {};
                    attribute[attr_name] = !props.attributes[attr_name];
                    inspectorTabProfileElements.push(el(components.PanelRow,
                        {},
                        el('label', {htmlFor: 'bookly-js-show-customer-info-' + field.id}, field.label),
                        el(components.FormToggle, {
                            id: 'bookly-js-show-customer-info-' + field.id,
                            checked: attributes[attr_name],
                            onChange: function() {
                                return props.setAttributes(attribute);
                            },

                        })
                    ));
                    inspectorTabProfileElements.push(el('div', {style: {'margin': '-5px 0 1.5em 0', 'font-style': 'italic'}}, BooklyCustomerCabinetL10n.profile.customerInformation));
                });
                inspectorTabProfileElements.push(el(components.PanelRow,
                    {},
                    el('label', {htmlFor: 'bookly-js-show-delete-account'}, BooklyCustomerCabinetL10n.profile.deleteAccount),
                    el(components.FormToggle, {
                        id: 'bookly-js-show-delete-account',
                        checked: attributes.profile_delete,
                        onChange: function() {
                            return props.setAttributes({profile_delete: !props.attributes.profile_delete});
                        },
                    })
                ));
            }

            return [
                el(blockControls, {key: 'controls'}),
                el(inspectorControls, {key: 'inspector'},
                    el(components.PanelBody, {initialOpen: true, title: BooklyCustomerCabinetL10n.appointmentManagement},
                        inspectorTabAppointmentsElements
                    ),
                    el(components.PanelBody, {initialOpen: true, title: BooklyCustomerCabinetL10n.profileManagement},
                        inspectorTabProfileElements
                    ),
                ),
                el('div', {},
                    getShortCode(props, props.attributes)
                )
            ]
        },

        save: function(props) {
            return (
                el('div', {},
                    props.attributes.short_code
                )
            )
        }
    })
})(
    window.wp,
    jQuery
);