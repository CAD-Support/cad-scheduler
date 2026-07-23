<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Modules\Appearance\Proxy;
use Bookly\Backend\Components\Controls\Inputs;
?>
<div class="bookly-js-cart-settings bookly-collapse">
    <div class="row">
        <?php Proxy\ServiceExtras::renderShowCartExtras() ?>
        <div class='col-md-3 my-2'>
            <?php Inputs::renderCheckBox( __( 'Show "Book more" near "Next" button', 'bookly' ), null, get_option( 'bookly_app_button_book_more_near_next' ), array( 'id' => 'bookly-js-app-button-book-more-near-next' ) ) ?>
        </div>
    </div>
</div>