<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\PageHeader\Renderer as PageHeaderRenderer;
use Bookly\Backend\Components as BooklyComponents;
use BooklyCustomerGroups\Backend\Components;

/** @var int $no_groups_count */
?>
<div id="bookly-tbs" class="wrap bookly-css-root bookly-main-page-wrap">
    <?php PageHeaderRenderer::render( $self::pageSlug(), __( 'Customer Groups', 'bookly' ) ) ?>

    <div class="bookly:card">
        <div class="bookly:card-body">
            <div id="bookly-customer_groups-datatables"></div>

            <div class="bookly:mt-3">
                <?php esc_html_e( 'Customers without group', 'bookly' ) ?>: <span class="bookly-js-no-groups"><?php echo $no_groups_count ?></span>
            </div>
        </div>
        <?php Components\Dialogs\CustomerGroup\Edit::render() ?>
    </div>
</div>
