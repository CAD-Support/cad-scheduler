<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Editable\Elements;
?>
<div class="bookly-box bookly-js-chain-appointments"<?php if ( ! get_option( 'bookly_chain_appointments_enabled' ) ) : ?> style="display: none;"<?php endif ?>>
    <div class="bookly-btn bookly-inline-block">
        <?php Elements::renderString( array( 'bookly_l10n_chain_appointments_book_more', ) ) ?>
    </div>
</div>