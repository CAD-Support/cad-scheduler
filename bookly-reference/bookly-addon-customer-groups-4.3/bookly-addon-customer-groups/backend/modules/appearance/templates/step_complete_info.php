<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Modules\Appearance\Codes;
use Bookly\Backend\Components\Editable\Elements;
use Bookly\Backend\Modules\Appearance\Proxy;
?>
<div class="bookly-box bookly-js-done-state-booking-skip-payment bookly-collapse">
    <?php Elements::renderText( 'bookly_l10n_info_complete_step_group_skip_payment', Codes::getJson( 8, true ) ) ?>
    <?php Proxy\Pro::renderQRCode() ?>
</div>