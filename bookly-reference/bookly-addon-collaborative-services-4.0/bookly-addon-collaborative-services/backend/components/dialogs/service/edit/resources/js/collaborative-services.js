jQuery(function ($) {
    $(document.body).on('service.initForm', {},
        // Bind an event handler to the components for service panel.
        function (event, $panel ) {
            let $collaborative_services = $('.bookly-js-collaborative-sub-services', $panel).closest('.form-group');
            $collaborative_services
                // Add sub-service.
                .find('.bookly-js-collaborative-sub-service-add')
                .on('change', function() {
                        if (this.value >= 0) {
                            $('.bookly-js-templates.bookly-js-collaborative-services .template_' + this.value + ' li')
                                .clone()
                                .insertBefore($(this).closest('li'))
                            ;
                            this.value = -1;
                        }
                    })
                    .end()
                // Remove sub-service.
                .on('click', '.bookly-js-collaborative-sub-service-remove', function() {
                    $(this).closest('li').remove();
                });
            // Make sub-services sortable.
            let $sortable = $('.bookly-js-collaborative-sub-services', $collaborative_services);
            if ($sortable.length) {
                Sortable.create($sortable[0], {
                    handle: '.bookly-js-draghandle',
                    draggable: '.bookly-js-collaborative-sub-service'
                });
            }
        }
    ).on('service.submitForm', {},
        // Bind submit handler for service saving.
        function(event, $panel, data) {
            if ($panel.find('[name="type"]').val() === 'collaborative') {
                let i = 0;
                data.sub_services = [];
                $panel.find('.bookly-js-collaborative-sub-services .bookly-js-collaborative-sub-service').each(function() {
                    let subServiceId = $(this).data('sub-service-id');
                    if (subServiceId) {
                        data.sub_services[i++] = {sub_service_id: subServiceId};
                    }
                });
            }
        }
    );
});