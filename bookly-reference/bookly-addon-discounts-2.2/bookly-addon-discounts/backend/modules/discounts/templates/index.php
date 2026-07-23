<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\PageHeader\Renderer as PageHeaderRenderer;
use Bookly\Backend\Components as BooklyComponents;
use BooklyDiscounts\Backend\Components;
?>
<div id="bookly-tbs" class="wrap bookly-css-root bookly-main-page-wrap">
    <?php PageHeaderRenderer::render( $self::pageSlug(), __( 'Discounts', 'bookly' ) ) ?>

    <div class="bookly:card">
        <div class="bookly:card-body">
            <div id="bookly-discounts-datatables"></div>
        </div>
    </div>

    <?php Components\Dialogs\Discount\Edit::render() ?>
</div>
