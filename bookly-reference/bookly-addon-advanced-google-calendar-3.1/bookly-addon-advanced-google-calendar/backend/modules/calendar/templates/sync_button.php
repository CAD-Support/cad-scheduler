<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
/**
 * @var bool $show_sync_button
 */
?>
<?php if ( $show_sync_button ) : ?>
<div class="col-sm-auto mb-2">
    <button id="bookly-google-calendar-sync" class="btn btn-default ladda-button" title="<?php esc_attr_e( 'Synchronize with Google Calendar', 'bookly' ) ?>" data-spinner-size="30" data-style="zoom-in" data-spinner-color="#333">
        <span class="ladda-label"><i class="fas fa-sync-alt mr-1"></i><?php esc_html_e( 'Google Calendar', 'bookly' ) ?></i></span>
    </button>
</div>
<?php endif ?>