<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Lib as BooklyLib;
use Bookly\Lib\Utils\Common;

$color = isset( $appearance['main_color'] ) ? $appearance['main_color'] : get_option( 'bookly_app_color', '#f4662f' );
/** @var string $form_id */
?>
<style>
    .<?php echo esc_attr( $form_id ) ?> {
        --bookly-color: <?php echo esc_attr( $color ) ?>;
    }

    :root {
        --bookly-flags-url: url(<?php echo plugins_url( 'frontend/resources/images/flags.png', BooklyLib\Plugin::getMainFile() ) ?>);
        --bookly-flags2x-url: url(<?php echo plugins_url( 'frontend/resources/images/flags@2x.png', BooklyLib\Plugin::getMainFile() ) ?>);
        --rtl-phone-align: <?php echo is_rtl() ? 'right !important' : 'left' ?>;
    }
</style>
<?php if ( isset( $appearance['custom_css'] ) && $appearance['custom_css'] != '' ) : ?>
    <style>
        <?php echo Common::css( $appearance['custom_css'] ) ?>
    </style>
<?php endif ?>
