<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\PageHeader\Renderer as PageHeaderRenderer;

/** @var array $datatables */
?>
<div id="bookly-tbs" class="wrap bookly-css-root bookly-main-page-wrap">
    <?php PageHeaderRenderer::render( $self::pageSlug(), __( 'Coupons', 'bookly' ) ) ?>
    <div class="bookly:card">
        <div class="bookly:card-body">
            <div id="bookly-coupons-datatables"></div>
        </div>
    </div>
    <?php $self::renderTemplate( 'coupon', compact( 'services', 'dropdown_data', 'customers' ) ) ?>
    <?php $self::renderTemplate( 'export', compact( 'datatables' ) ) ?>
</div>
