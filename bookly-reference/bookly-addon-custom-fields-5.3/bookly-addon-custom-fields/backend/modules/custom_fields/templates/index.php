<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Controls\Buttons;
use Bookly\Backend\Components\PageHeader\Renderer as PageHeaderRenderer;
use BooklyCustomFields\Backend\Modules\CustomFields;

/** @var string $tab */
/** @var string $services_html */
?>
<div id="bookly-tbs" class="wrap bookly-css-root bookly-main-page-wrap">
    <?php PageHeaderRenderer::render( $self::pageSlug(), __( 'Custom Fields', 'bookly' ) ) ?>

    <div class="bookly:card bookly:relative">
        <div class="bookly:card-body">
            <ul class="bookly:nav bookly:nav-tabs bookly:mb-3 bookly-js-custom-fields-tabs" role="tablist">
                <li class="bookly:nav-item">
                    <a class="bookly:nav-link<?php if ( $tab === 'general' ) : ?> bookly:active<?php endif ?>" href="<?php echo add_query_arg( array( 'page' => CustomFields\Page::pageSlug() ), admin_url( 'admin.php' ) ) ?>" data-toggle="bookly-tab" data-tab="general"><?php esc_html_e( 'General', 'bookly' ) ?></a>
                </li>
                <li class="bookly:nav-item">
                    <a class="bookly:nav-link<?php if ( $tab === 'conditions' ) : ?> bookly:active<?php endif ?>" href="<?php echo add_query_arg( array( 'page' => CustomFields\Page::pageSlug(), 'tab' => 'conditions' ), admin_url( 'admin.php' ) ) ?>" data-toggle="bookly-tab" data-tab="conditions"><?php esc_html_e( 'Conditions', 'bookly' ) ?></a>
                </li>
            </ul>
            <div class="bookly-js-custom-fields-wrap"></div>
        </div>
        <div class="bookly:card-footer bg-transparent d-flex justify-content-end">
            <?php Buttons::renderSubmit( 'ajax-save-custom-fields' ) ?>
            <?php Buttons::renderReset( 'ajax-reset-custom-fields', 'ml-2' ) ?>
        </div>
    </div>
</div>