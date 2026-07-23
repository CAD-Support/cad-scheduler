<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\PageHeader\Renderer as PageHeaderRenderer;
use Bookly\Backend\Components;
use BooklyEvents\Backend\Components\Dialogs;
?>
<div id="bookly-tbs" class="wrap bookly-css-root bookly-main-page-wrap">
    <?php PageHeaderRenderer::render( $self::pageSlug(), __( 'Events', 'bookly' ) ) ?>
    <div class="bookly:card">
        <div class="bookly:card-body">
            <div id="bookly-events-datatables"></div>
        </div>
    </div>
    <?php Dialogs\Event\Dialog::render() ?>
    <?php Dialogs\TicketType\Dialog::render() ?>
    <?php Dialogs\Attendees\Dialog::render() ?>
    <?php Components\Dialogs\Queue\Dialog::render() ?>
    <?php Components\Dialogs\Customer\Edit\Dialog::render() ?>
    <?php Components\Dialogs\Payment\Dialog::render() ?>
</div>
