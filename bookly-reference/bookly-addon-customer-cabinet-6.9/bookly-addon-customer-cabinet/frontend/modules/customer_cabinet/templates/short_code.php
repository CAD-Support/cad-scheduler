<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Lib\Utils\Common;
use Bookly\Lib\Config;
use Bookly\Lib as BooklyLib;
use BooklyPro\Frontend\Components\Fields\Birthday;
use BooklyCustomerCabinet\Frontend\Components\Dialogs;
use Bookly\Backend\Components\Dialogs as BooklyDialogs;

/** @var BooklyLib\Entities\Customer $customer */
$profile_parameters = isset( $attributes['profile'] ) ? explode( ',', $attributes['profile'] ) : array();
$tabs = array_key_exists( 'tabs', $attributes ) ? explode( ',', $attributes['tabs'] ) : array();
?>
<script type="text/javascript">
    <?php // For payment details dialog ?>
    var ajaxurl = <?php echo json_encode( $ajax_url ) ?>;
    (function (win, fn) {
        var done = false, top = true,
            doc = win.document,
            root = doc.documentElement,
            modern = doc.addEventListener,
            add = modern ? 'addEventListener' : 'attachEvent',
            rem = modern ? 'removeEventListener' : 'detachEvent',
            pre = modern ? '' : 'on',
            init = function (e) {
                if (e.type == 'readystatechange') if (doc.readyState != 'complete') return;
                (e.type == 'load' ? win : doc)[rem](pre + e.type, init, false);
                if (!done) {
                    done = true;
                    fn.call(win, e.type || e);
                }
            },
            poll = function () {
                try {
                    root.doScroll('left');
                } catch (e) {
                    setTimeout(poll, 50);
                    return;
                }
                init('poll');
            };
        if (doc.readyState == 'complete') fn.call(win, 'lazy');
        else {
            if (!modern) if (root.doScroll) {
                try {
                    top = !win.frameElement;
                } catch (e) {
                }
                if (top) poll();
            }
            doc[add](pre + 'DOMContentLoaded', init, false);
            doc[add](pre + 'readystatechange', init, false);
            win[add](pre + 'load', init, false);
        }
    })(window, function () {
        var a = document.getElementsByClassName("bookly-customer-cabinet")[0];
        while (a) {
            try {
                if (getComputedStyle(a).zIndex !== 'auto') {
                    a.style.zIndex = 'auto';
                }
            } catch (e) {
            }
            a = a.parentNode;
        }
        window.booklyCustomerCabinet({
            form_id: <?php echo json_encode( $form_id ) ?>,
            tabs: <?php echo json_encode( $tabs ) ?>,
            appointment_columns: <?php echo json_encode( $appointment_columns ) ?>,
            profile_parameters: <?php echo json_encode( $profile_parameters ) ?>,
            show_timezone: <?php echo json_encode( $show_timezone ) ?>,
            filters: <?php echo json_encode( $filters ) ?>,
            intlTelInput: {
                enabled: <?php echo (int) ( get_option( 'bookly_cst_phone_default_country' ) !== 'disabled' ) ?>,
                country: <?php echo json_encode( get_option( 'bookly_cst_phone_default_country' ) ) ?>
            }
        });
    });
</script>
<div id="bookly-tbs" class="wrap bookly-customer-cabinet <?php echo $form_id ?>">
    <div class="mt-4">
        <?php if ( count( $tabs ) > 1 ) : ?>
            <ul class="bookly-js-tabs nav nav-tabs nav-justified mb-4">
                <?php foreach ( $tabs as $num => $tab ) : ?>
                    <?php switch ( $tab ) :
                        case 'appointments': ?>
                            <li class="nav-item<?php if ( ! $num ) : ?> active<?php endif ?>">
                                <a id="bookly-cabinet-appointments-tab" href="#appointments" data-toggle="bookly-tab" class="nav-link">
                                    <i class="far fa-fw fa-calendar-alt mr-1"></i><?php esc_html_e( 'Appointments', 'bookly' ) ?>
                                </a>
                            </li>
                            <?php break; ?>
                        <?php case 'profile': ?>
                            <li class="nav-item<?php if ( ! $num ) : ?> active<?php endif ?>">
                                <a id="bookly-cabinet-profile-tab" href="#profile" data-toggle="bookly-tab" class="nav-link">
                                    <i class="far fa-fw fa-user mr-1"></i><?php esc_html_e( 'Profile', 'bookly' ) ?>
                                </a>
                            </li>
                            <?php break; ?>
                        <?php endswitch ?>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
        <?php foreach ( $tabs as $num => $tab ) : ?>
            <?php switch ( $tab ) :
                case 'appointments': ?>
                    <div class="bookly-js-customer-cabinet-content bookly-js-customer-cabinet-content-appointments bookly-collapse">
                        <div class="bookly-css-root">
                            <div id="<?php echo $form_id ?>-appointments"></div>
                        </div>
                    </div>
                    <?php break; ?>
                <?php case 'profile': ?>
                    <div class="bookly-js-customer-cabinet-content bookly-js-customer-cabinet-content-profile bookly-collapse">
                        <form>
                            <?php foreach ( $profile_parameters as $column ) : ?>
                                <?php switch ( $column ) :
                                    case 'name': ?>
                                        <?php if ( Config::showFirstLastName() ) : ?>
                                            <div class="row">
                                                <div class="form-group col-sm-6">
                                                    <label for="bookly_first_name"><?php echo esc_html( Common::getTranslatedOption( 'bookly_l10n_label_first_name ' ) ) ?></label>
                                                    <input type="text" name="first_name" class="form-control bookly-js-control-input" id="bookly_first_name" value="<?php echo esc_attr( $customer->getFirstName() ) ?>"/>
                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <label for="bookly_last_name"><?php echo esc_html( Common::getTranslatedOption( 'bookly_l10n_label_last_name' ) ) ?></label>
                                                    <input type="text" name="last_name" class="form-control bookly-js-control-input" id="bookly_last_name" value="<?php echo esc_attr( $customer->getLastName() ) ?>"/>
                                                </div>
                                            </div>
                                        <?php else : ?>
                                            <div class="form-group">
                                                <label for="bookly_full_name"><?php echo esc_html( Common::getTranslatedOption( 'bookly_l10n_label_name' ) ) ?></label>
                                                <input type="text" name="full_name" class="form-control bookly-js-control-input" id="bookly_full_name" value="<?php echo esc_attr( $customer->getFullName() ) ?>"/>
                                            </div>
                                        <?php endif ?>
                                        <?php break ?>
                                    <?php case 'email': ?>
                                        <div class="form-group">
                                            <label for="bookly_email"><?php echo esc_html( Common::getTranslatedOption( 'bookly_l10n_label_email' ) ) ?></label>
                                            <input type="text" name="email" class="form-control bookly-js-control-input" id="bookly_email" value="<?php echo esc_attr( $customer->getEmail() ) ?>"/>
                                        </div>
                                        <?php break ?>
                                    <?php case 'phone': ?>
                                        <div class="form-group">
                                            <label for="bookly_phone"><?php echo esc_html( Common::getTranslatedOption( 'bookly_l10n_label_phone' ) ) ?></label>
                                            <input type="<?php echo get_option( 'bookly_cst_phone_default_country' ) !== 'disabled' ? 'tel' : 'text' ?>" name="phone" class="form-control bookly-js-user-phone-input bookly-js-control-input<?php if ( get_option( 'bookly_cst_phone_default_country' ) != 'disabled' ) : ?> bookly-user-phone<?php endif ?>" id="bookly_phone" value="<?php echo esc_attr( $customer->getPhone() ) ?>"/>
                                        </div>
                                        <?php break ?>
                                    <?php case 'birthday': ?>
                                        <div class="row">
                                            <?php Birthday::renderBootstrap( $customer->getBirthday() ) ?>
                                        </div>
                                        <?php break ?>
                                    <?php case 'address':
                                        $address_show_fields = (array) get_option( 'bookly_cst_address_show_fields', array() );
                                        foreach ( $address_show_fields as $field_name => $field ) : ?>
                                            <?php if ( $field['show'] ) : ?>
                                                <div class="form-group">
                                                    <label for="bookly_<?php echo $field_name ?>"><?php echo esc_html( Common::getTranslatedOption( 'bookly_l10n_label_' . $field_name ) ) ?></label>
                                                    <input class="form-control bookly-js-control-input" type="text" name=<?php echo $field_name ?> id="bookly_<?php echo $field_name ?>" value="<?php echo esc_attr( isset( $customer_address[ $field_name ] ) ? $customer_address[ $field_name ] : '' ) ?>"/>
                                                </div>
                                            <?php endif ?>
                                        <?php endforeach ?>
                                        <?php break ?>
                                    <?php case 'full_address': ?>
                                        <div class="form-group">
                                            <label for="bookly_full_address"><?php esc_html_e( 'Customer address', 'bookly' ) ?></label>
                                            <input type="text" name="full_address" class="form-control bookly-js-control-input" id="bookly_full_address" value="<?php echo esc_attr( $customer->getFullAddress() ) ?>"/>
                                        </div>
                                        <?php break ?>
                                    <?php case 'wp_password': ?>
                                        <div class="row">
                                            <div class="form-group col-sm-3">
                                                <label for="bookly-wp-user"><?php esc_html_e( 'WP user', 'bookly' ) ?></label>
                                                <p><?php $user_data = get_userdata( $customer->getWpUserId() );
                                                    echo $user_data->display_name ?></p>
                                            </div>
                                            <div class="form-group col-sm-3">
                                                <label for="bookly_current_password"><?php esc_html_e( 'Current password', 'bookly' ) ?></label>
                                                <input type="password" name="current_password" class="form-control bookly-js-control-input" id="bookly_current_password" value=""/>
                                            </div>
                                            <div class="form-group col-sm-3">
                                                <label for="bookly_new_password_1"><?php esc_html_e( 'New password', 'bookly' ) ?></label>
                                                <input type="password" name="new_password_1" class="form-control bookly-js-control-input" id="bookly_new_password_1" value=""/>
                                            </div>
                                            <div class="form-group col-sm-3">
                                                <label for="bookly_new_password_2"><?php esc_html_e( 'Confirm password', 'bookly' ) ?></label>
                                                <input type="password" name="new_password_2" class="form-control bookly-js-control-input" id="bookly_new_password_2" value=""/>
                                            </div>
                                        </div>
                                        <?php break ?>
                                    <?php default : ?>
                                        <?php
                                        if ( strpos( $column, 'customer_information' ) === 0 ) {
                                            BooklyLib\Proxy\CustomerInformation::renderCustomerCabinet( substr( $column, 21 ), $customer );
                                        }
                                        ?>
                                        <?php break; ?>
                                    <?php endswitch ?>
                            <?php endforeach ?>
                            <div>
                                <?php if ( in_array( 'delete', $profile_parameters ) ) : ?>
                                    <button class="btn btn-danger bookly-js-delete-profile" data-type="open-modal" data-target=".<?php echo $form_id ?>.bookly-js-customer-cabinet-delete-dialog"><?php esc_html_e( 'Delete account', 'bookly' ) ?></button>
                                <?php endif ?>
                                <button class="btn btn-success float-right bookly-js-save-profile ladda-button" data-style="zoom-in"><?php esc_html_e( 'Save', 'bookly' ) ?></button>
                            </div>
                        </form>
                    </div>
                    <?php break ?>
                <?php endswitch ?>
        <?php endforeach ?>
    </div>
    <?php Dialogs\Reschedule\Dialog::render() ?>
    <?php Dialogs\Cancel\Dialog::render( $show_reason ) ?>
    <?php Dialogs\Delete\Dialog::render() ?>
    <?php BooklyDialogs\Payment\Dialog::render() ?>
</div>