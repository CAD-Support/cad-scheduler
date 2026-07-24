<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Modules\Appearance\Codes;
use Bookly\Backend\Components\Editable\Elements;
?>
<div class="bookly-box">
    <?php Elements::renderText( 'bookly_l10n_info_deposit', Codes::getJson( 7 ) ) ?>
</div>
<div class="bookly-box bookly-list">
    <label>
        <input type="radio" class="bookly-js-deposit" name="bookly-full-payment" value="0" checked/>
        <span><?php Elements::renderString( array( 'bookly_l10n_label_deposit_payment', ) ) ?></span>
    </label>
</div>
<div class="bookly-box bookly-list">
    <label>
        <input type="radio" class="bookly-js-deposit" name="bookly-full-payment" value="1"/>
        <span><?php Elements::renderString( array( 'bookly_l10n_label_full_payment', ) ) ?></span>
    </label>
</div>