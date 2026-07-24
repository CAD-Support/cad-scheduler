<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Lib\Config;
use Bookly\Lib\Utils\Common;
use Bookly\Lib\Proxy;

$custom_fields = Proxy\CustomFields::getWhichHaveData() ?: array();
$customer_information = Proxy\CustomerInformation::getFieldsWhichMayHaveData() ?: array();
?>
<div id="bookly-editor-customer-cabinet-popup" style="display: none">
    <form id="bookly-customer-cabinet-form">
        <table>
            <tr>
                <td class="bookly-customer-tabs">
                    <label for="bookly-select-shortcode"><input id="bookly-customer-cabinet-tabs" type="checkbox" checked><?php esc_html_e( 'Customer cabinet (all services displayed in tabs)', 'bookly' ) ?></label>
                </td>
            </tr>
            <tr>
                <td>
                    <div><label><input type="checkbox" data-cabinet-tab="appointments" checked><?php esc_html_e( 'Appointment management', 'bookly' ) ?></label></div>
                    <ul>
                        <li><label><input type="checkbox" data-appointments data-option="filters" checked><?php esc_html_e( 'Filters', 'bookly' ) ?></label></li>
                        <li>
                            <label><input type="checkbox" data-appointments data-option="date" checked><?php esc_html_e( 'Date', 'bookly' ) ?></label>
                            <div class="bookly-customer-cabinet-sub-item">
                                <label><input type="checkbox" data-appointments data-option="timezone" checked><?php esc_html_e( 'Show timezone', 'bookly' ) ?></label>
                            </div>
                        </li>
                        <?php if ( Config::locationsActive() ) : ?>
                            <li><label><input type="checkbox" data-appointments data-option="location" checked><?php esc_html_e( 'Location', 'bookly' ) ?></label></li>
                        <?php endif ?>
                        <li><label><input type="checkbox" data-appointments data-option="category" checked><?php esc_html_e( 'Category', 'bookly' ) ?></label></li>
                        <li><label><input type="checkbox" data-appointments data-option="service" checked><?php esc_html_e( 'Service', 'bookly' ) ?></label></li>
                        <li><label><input type="checkbox" data-appointments data-option="staff" checked><?php esc_html_e( 'Staff', 'bookly' ) ?></label></li>
                        <li><label><input type="checkbox" data-appointments data-option="duration" checked><?php esc_html_e( 'Duration', 'bookly' ) ?></label></li>
                        <li><label><input type="checkbox" data-appointments data-option="price" checked><?php esc_html_e( 'Price', 'bookly' ) ?></label></li>
                        <li><label><input type="checkbox" data-appointments data-option="status" checked><?php esc_html_e( 'Status', 'bookly' ) ?></label></li>
                        <li><label><input type="checkbox" data-appointments data-option="online_meeting"><?php esc_html_e( 'Online meeting', 'bookly' ) ?></label></li>
                        <li><label><input type="checkbox" data-appointments data-option="join_online_meeting"><?php esc_html_e( 'Join online meeting', 'bookly' ) ?></label></li>
                        <?php foreach ( $custom_fields as $field ) : ?>
                            <?php if ( $field->type != 'file' ) : ?>
                                <li><label><input type="checkbox" data-appointments data-option="custom_field_<?php echo $field->id ?>"><?php echo Common::stripScripts( $field->label ) ?></label></li>
                            <?php endif ?>
                        <?php endforeach ?>
                        <li><label><input type="checkbox" data-appointments data-option="cancel" checked><?php esc_html_e( 'Cancel', 'bookly' ) ?></label></li>
                        <li><label><input type="checkbox" data-appointments data-option="reason" checked><?php esc_html_e( 'Cancellation reason', 'bookly' ) ?></label></li>
                        <li><label><input type="checkbox" data-appointments data-option="reschedule" checked><?php esc_html_e( 'Reschedule', 'bookly' ) ?></label></li>
                    </ul>
                </td>
            </tr>
            <tr>
                <td>
                    <div><label><input type="checkbox" data-cabinet-tab="profile" checked><?php esc_html_e( 'Profile management', 'bookly' ) ?></label></div>
                    <ul>
                        <li><label><input type="checkbox" data-profile data-option="name" checked><?php esc_html_e( 'Name', 'bookly' ) ?></label></li>
                        <li><label><input type="checkbox" data-profile data-option="email" checked><?php esc_html_e( 'Email', 'bookly' ) ?></label></li>
                        <li><label><input type="checkbox" data-profile data-option="phone" checked><?php esc_html_e( 'Phone', 'bookly' ) ?></label></li>
                        <li><label><input type="checkbox" data-profile data-option="birthday" checked><?php esc_html_e( 'Birthday', 'bookly' ) ?></label></li>
                        <li><label><input type="checkbox" data-profile data-option="address" checked><?php esc_html_e( 'Address', 'bookly' ) ?></label></li>
                        <li><label><input type="checkbox" data-profile data-option="full_address" checked><?php esc_html_e( 'Customer address', 'bookly' ) ?></label></li>
                        <li><label><input type="checkbox" data-profile data-option="wp_password" checked><?php esc_html_e( 'WordPress password', 'bookly' ) ?></label></li>
                        <?php foreach ( $customer_information as $field ) : ?>
                            <li><label><input type="checkbox" data-profile data-option="customer_information_<?php echo $field->id ?>"><?php echo Common::stripScripts( $field->label ) ?></label></li>
                        <?php endforeach ?>
                        <li><label><input type="checkbox" data-profile data-option="delete" checked><?php esc_html_e( 'Delete account', 'bookly' ) ?></label></li>
                    </ul>
                </td>
            </tr>
            <tr>
                <td>
                    <button class="button button-primary bookly-js-insert-shortcode" type="button"><?php esc_html_e( 'Insert', 'bookly' ) ?></button>
                </td>
            </tr>
        </table>
    </form>
</div>
<style type="text/css">
    #bookly-customer-cabinet-form form {
        margin-top: 15px;
    }

    #bookly-customer-cabinet-form form table {
        width: 100%;
    }

    #bookly-customer-cabinet-form ul {
        margin-left: 25px;
    }

    #bookly-customer-cabinet-form .bookly-customer-cabinet-sub-item {
        margin-left: 25px;
    }

    #bookly-customer-cabinet-form .bookly-customer-tabs {
        padding-bottom: 25px;
        padding-top: 15px;
    }
</style>
<script type="text/javascript">
    jQuery(function($) {
        var $form = $('#bookly-customer-cabinet-form'),
            $tabs = $('#bookly-customer-cabinet-tabs', $form),
            $insert = $('button.bookly-js-insert-shortcode', $form);

        $tabs.on('change', function() {
            if (!$(this).prop('checked')) {
                var checkboxes = 0;
                $('[data-cabinet-tab]', $form).each(function() {
                    if ($(this).prop('checked')) {
                        if (checkboxes > 0) {
                            $(this).prop('checked', false);
                        }
                        checkboxes++;
                    }
                });
            }
        });

        $('[data-cabinet-tab]', $form).on('change', function() {
            if ($('[data-cabinet-tab]:checked', $form).length > 1) {
                $tabs.prop('checked', true);
            }
        });

        $insert.on('click', function(e) {
            e.preventDefault();

            var code = '[bookly-customer-cabinet';
            var tabs = $('[data-cabinet-tab]:checked', $form).map(function() {
                return $(this).attr('data-cabinet-tab');
            }).get().join(',');
            code += ' tabs="' + tabs + '"';
            $('[data-cabinet-tab]:checked', $form).each(function() {
                var tab = $(this).attr('data-cabinet-tab'),
                    options = $('[data-' + tab + ']:checked', $form).map(function() {
                        return $(this).attr('data-option');
                    }).get().join(',');
                code += ' ' + tab + '="' + options + '"';
            });

            code += ']';

            window.send_to_editor(code);
            window.parent.tb_remove();
            return false;
        });
    });
</script>