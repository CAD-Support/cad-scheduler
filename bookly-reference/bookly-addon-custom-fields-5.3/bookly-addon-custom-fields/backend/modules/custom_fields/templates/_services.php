<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly ?>
<ul class="bookly-js-services"
    data-container-class="mb-3"
    data-icon-class="far fa-sticky-note"
    data-txt-select-all="<?php esc_attr_e( 'All services', 'bookly' ) ?>"
    data-txt-all-selected="<?php esc_attr_e( 'All services', 'bookly' ) ?>"
    data-txt-nothing-selected="<?php esc_attr_e( 'No service selected', 'bookly' ) ?>"
>
    <?php foreach ( $service_dropdown_data as $category_id => $category ): ?>
        <li<?php if ( ! $category_id ) : ?> data-flatten-if-single<?php endif ?>><?php echo esc_html( $category['name'] ) ?>
            <ul>
                <?php foreach ( $category['items'] as $service ): ?>
                    <li data-value="<?php echo $service['id'] ?>">
                        <?php echo esc_html( $service['title'] ) ?>
                    </li>
                <?php endforeach ?>
            </ul>
        </li>
    <?php endforeach ?>
</ul>