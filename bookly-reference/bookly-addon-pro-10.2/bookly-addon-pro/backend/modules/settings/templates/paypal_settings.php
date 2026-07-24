<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Settings\Inputs;
use Bookly\Backend\Components\Settings\Selects;
use Bookly\Backend\Modules\Settings\Proxy;
use Bookly\Backend\Components\Controls\Elements;
use Bookly\Backend\Components;
use Bookly\Lib\Utils\DateTime;
use BooklyPro\Lib;
use Bookly\Lib\Cloud;
?>
<div class="card bookly-collapse-with-arrow" data-gateway="<?php echo esc_attr( $type ) ?>">
    <div class="card-header d-flex align-items-center">
        <?php Elements::renderReorder() ?>
        <a href="#bookly_pmt_paypal" class="ml-2" role="button" data-toggle="bookly-collapse">
            PayPal
        </a>
        <img class="ml-auto" src="<?php echo plugins_url( 'frontend/resources/images/paypal.svg', Lib\Plugin::getMainFile() ) ?>" />
    </div>
    <div id="bookly_pmt_paypal" class="bookly-collapse bookly-show">
        <div class="card-body">
            <div class="form-group">
                <?php Selects::renderSingle( 'bookly_paypal_enabled', null, null,
                    Proxy\PaypalPaymentsStandard::prepareToggleOptions( array(
                        array( '0', __( 'Disabled', 'bookly' ) ),
                        array( Lib\Payment\PayPalGateway::TYPE_EXPRESS_CHECKOUT, 'PayPal Express Checkout' ),
                    ) )
                ) ?>
            </div>
            <div class="bookly-paypal">
                <div class="bookly-paypal-express-checkout">
                    <?php Inputs::renderText( 'bookly_paypal_api_username', __( 'API Username', 'bookly' ) ) ?>
                    <?php Inputs::renderText( 'bookly_paypal_api_password', __( 'API Password', 'bookly' ) ) ?>
                    <?php Inputs::renderText( 'bookly_paypal_api_signature', __( 'API Signature', 'bookly' ) ) ?>
                </div>
                <?php Proxy\PaypalPaymentsStandard::renderSetUpOptions() ?>
                <?php Selects::renderSingle( 'bookly_paypal_sandbox', __( 'Sandbox Mode', 'bookly' ), null, array( array( 1, __( 'Yes', 'bookly' ) ), array( 0, __( 'No', 'bookly' ) ) ) ) ?>
                <?php Components\Settings\Payments::renderTax( 'paypal' ) ?>
                <?php Components\Settings\Payments::renderPriceCorrection( 'paypal' ) ?>
                <?php
                $values = array( array( '0', __( 'OFF', 'bookly' ) ) );
                foreach ( array_merge(
                    range( 5 * MINUTE_IN_SECONDS, 55 * MINUTE_IN_SECONDS, 5 * MINUTE_IN_SECONDS ),
                    range( HOUR_IN_SECONDS, 23 * HOUR_IN_SECONDS, HOUR_IN_SECONDS ),
                    range( 24 * HOUR_IN_SECONDS, 168 * HOUR_IN_SECONDS, 24 * HOUR_IN_SECONDS ),
                    array( 336 * HOUR_IN_SECONDS, 504 * HOUR_IN_SECONDS, 672 * HOUR_IN_SECONDS )
                ) as $seconds ) {
                    $values[] = array( $seconds, DateTime::secondsToInterval( $seconds ) );
                }
                Selects::renderSingle( 'bookly_paypal_timeout', __( 'Time interval of payment gateway', 'bookly' ), __( 'This setting determines the time limit after which the payment made via the payment gateway is considered to be incomplete. This functionality requires a scheduled cron job.', 'bookly' ), $values );
                ?>
                <?php if ( ! Cloud\API::getInstance()->account->productActive( Cloud\Account::PRODUCT_CRON ) ) : ?>
                <div class="alert alert-info mb-0">
                    <div class="form-row">
                        <div class="mr-3"><i class="fas fa-info-circle fa-2x"></i></div>
                        <div class="align-content-center">
                            <?php printf( __( 'To ensure stable operation of the "Time interval of payment gateway" feature, please activate %s or follow <a href="%s" target="_blank">the instructions</a> about cron setup.', 'bookly' ), '<a href="' . esc_url( admin_url( 'admin.php?page=bookly-cloud-products' ) ) . '">Bookly Cloud Cron</a>', 'https://support.booking-wp-plugin.com/hc/en-us/articles/360015017400-How-can-I-configure-CRON-to-send-the-Bookly-reminders' ) ?>
                        </div>
                    </div>
                </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>