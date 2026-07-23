jQuery(function ($) {
    $(document.body).on('service.initForm', {},
        // Bind an event handler to the components for service panel.
        function (event, $panel ) {
            var $sub_services = $('.bookly-js-compound-service-list', $panel);

            var initSubServicesLi = function ($li) {
                $('.bookly-js-sub-service-remove', $li).click(function () {
                    $li.remove();
                });
            };

            $('.bookly-js-sub-services li.bookly-js-sub-service', $panel).each(function () {
                initSubServicesLi($(this));
            });

            $sub_services.on('change', function () {
                if (this.value >= 0) {
                    var $li = $('.bookly-js-templates.services .template_' + $sub_services.val() + ' li').clone();
                    $li.insertBefore($(this).closest('li'));
                    initSubServicesLi($li);
                    this.value = -1;
                }
            });
            let $sortable = $('.bookly-js-sub-services');
            if ($sortable.length) {
                Sortable.create($sortable[0], {
                    handle: '.bookly-js-draghandle',
                    draggable: '.bookly-js-sub-service'
                });
            }
        }
    ).on('service.submitForm', {},
        // Bind submit handler for service saving.
        function (event, $panel, data ) {
            if ($panel.find('[name="type"]').val() == 'compound') {
                let i = 0;
                data.sub_services = [];
                $panel.find('.bookly-js-sub-services .bookly-js-sub-service').each(function () {
                    let subServiceId = $(this).data('sub-service-id');
                    data.sub_services[i] = {};
                    if (subServiceId) {
                        data.sub_services[i].sub_service_id = subServiceId;
                    } else {
                        data.sub_services[i].sub_service_id = 0;
                        data.sub_services[i].duration = $(this).find('select').val();
                    }
                    ++i;
                });
            }
        }
    );
});