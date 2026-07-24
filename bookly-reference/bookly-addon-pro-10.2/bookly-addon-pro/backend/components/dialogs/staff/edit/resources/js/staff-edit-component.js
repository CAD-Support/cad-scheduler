jQuery(function ($) {
    'use strict';
    let $modal = $('#bookly-staff-edit-modal'),
        $modalBody = $('.modal-body', $modal),
        hash       = window.location.href.split('#'),
        staff_id   = BooklyL10nStaffEdit.activeStaffId,
        initialized = false
    ;

    if (BooklyL10nStaffEdit.activeStaffId != null) {
        $(document.body).trigger('bookly.staff.edit', [BooklyL10nStaffEdit.activeStaffId]);
    }

    $modal
        .on('show.bs.modal', function(){
            initialized = false;
        });

    window.addEventListener('bookly:edit-staff', function (e) {
        staff_id = e.detail.id;
    });

    // Open advanced tab
    $modalBody
        .on('click', '#bookly-advanced-tab', function () {
            $('.tab-pane > div').hide();
            let $container = $('#bookly-advanced-container', $modalBody);
            if(!initialized) {
                initialized = true;
                new BooklyStaffAdvanced($container, {
                    get_staff_advanced: {
                        action: 'bookly_pro_get_staff_advanced',
                        staff_id: staff_id
                    },
                    l10n: BooklyL10nStaffEdit
                });
                $('#bookly-advanced-save', $container).addClass('bookly-js-save');
            }
            $container.show();
        });
});