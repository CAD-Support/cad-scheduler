<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Lib\Utils\Common;
use Bookly\Lib\Utils\Price;
use Bookly\Frontend\Modules\Booking\Lib\Steps;
use Bookly\Frontend\Modules\Booking\Proxy as BookingProxy;

/** @var Bookly\Lib\UserBookingData $userData
 * @var Bookly\Lib\CartInfo $cart_info
 * @var array $table
 */
echo $progress_tracker;
?>
<div class="bookly-box"><?php echo $info_text ?></div>
<?php if ( $userData->getFirstStep() != Steps::CART && get_option( 'bookly_app_button_book_more_near_next' ) == '0' ) : ?>
    <div class="bookly-box">
        <button class="bookly-add-item bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40">
            <span class="ladda-label"><?php echo Common::getTranslatedOption( 'bookly_l10n_button_book_more' ) ?></span>
        </button>
    </div>
<?php endif ?>
<div class="bookly-box bookly-label-error"></div>
<div class="bookly-cart-step">
    <div class="bookly-cart bookly-box">
        <table>
            <thead class="bookly-desktop-version">
            <tr>
                <?php foreach ( $table['headers'] as $position => $column ) : ?>
                    <th <?php if ( isset( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ) echo 'class="bookly-rtext"' ?>><?php echo $column ?></th>
                <?php endforeach ?>
                <th></th>
            </tr>
            </thead>
            <tbody class="bookly-desktop-version">
            <?php foreach ( $table['rows'] as $key => $data ) : ?>
                <tr data-cart-key="<?php echo $key ?>" class="bookly-cart-primary">
                    <?php foreach ( $data as $position => $value ) : ?>
                        <td <?php if ( isset( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ) echo 'class="bookly-rtext"' ?>><?php echo $value ?></td>
                    <?php endforeach ?>
                    <td class="bookly-rtext bookly-nowrap bookly-js-actions">
                        <button class="bookly-round" data-action="edit" title="<?php esc_attr_e( 'Edit', 'bookly' ) ?>" data-style="zoom-in" data-spinner-size="30"><span class="ladda-label"><i class="bookly-icon-sm bookly-icon-edit"></i></span></button>
                        <button class="bookly-round" data-action="drop" title="<?php esc_attr_e( 'Remove', 'bookly' ) ?>" data-style="zoom-in" data-spinner-size="30"><span class="ladda-label"><i class="bookly-icon-sm bookly-icon-drop"></i></span></button>
                    </td>
                </tr>
                <?php BookingProxy\Shared::renderCartItemInfo( $userData, $key, $table['header_position'], true ) ?>
                <?php BookingProxy\Discounts::renderCartItemInfo( $userData, $key, $table['header_position'], true ) ?>
            <?php endforeach ?>
            </tbody>
            <tbody class="bookly-mobile-version">
            <?php foreach ( $table['rows'] as $key => $data ) : ?>
                <?php foreach ( $data as $position => $value ) : ?>
                    <tr data-cart-key="<?php echo $key ?>" class="bookly-cart-primary">
                        <th><?php echo $table['headers'][ $position ] ?></th>
                        <td><?php echo $value ?></td>
                    </tr>
                <?php endforeach ?>
                <?php BookingProxy\Shared::renderCartItemInfo( $userData, $key, $table['header_position'], false ) ?>
                <?php BookingProxy\Discounts::renderCartItemInfo( $userData, $key, $table['header_position'], false ) ?>
                <tr data-cart-key="<?php echo $key ?>">
                    <th></th>
                    <td class="bookly-js-actions">
                        <button class="bookly-round" data-action="edit" title="<?php esc_attr_e( 'Edit', 'bookly' ) ?>" data-style="zoom-in" data-spinner-size="30"><span class="ladda-label"><i class="bookly-icon-sm bookly-icon-edit"></i></span></button>
                        <button class="bookly-round" data-action="drop" title="<?php esc_attr_e( 'Remove', 'bookly' ) ?>" data-style="zoom-in" data-spinner-size="30"><span class="ladda-label"><i class="bookly-icon-sm bookly-icon-drop"></i></span></button>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
            <?php if ( isset( $table['header_position']['price'] ) || ( $table['show']['deposit'] && isset( $table['header_position']['deposit'] ) ) ) : ?>
                <tfoot class="bookly-mobile-version">
                <?php BookingProxy\Discounts::renderCartDiscountRow( $table, 'mobile', $userData ) ?>
                <?php if ( isset ( $table['header_position']['price'] ) ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Total', 'bookly' ) ?>:</th>
                        <td><strong class="bookly-js-total-price"><?php echo Price::format( $cart_info->getTotal() ) ?></strong></td>
                    </tr>
                    <?php if ( $table['show']['tax'] ) : ?>
                        <tr>
                            <th><?php esc_html_e( 'Total tax', 'bookly' ) ?>:</th>
                            <td><strong class="bookly-js-total-tax"><?php echo Price::format( $cart_info->getTotalTax() ) ?></strong></td>
                        </tr>
                    <?php endif ?>
                <?php endif ?>
                <?php BookingProxy\DepositPayments::renderPayNowRow( $cart_info, $table, 'mobile' ) ?>
                </tfoot>
                <tfoot class="bookly-desktop-version">
                <?php if ( $cart_info->getWaitingListTotal() > 0 ) : ?>
                    <tr>
                        <?php foreach ( $table['headers'] as $position => $column ) : ?>
                            <td <?php if ( isset ( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ) echo 'class="bookly-rtext"' ?>>
                                <?php if ( $position == 0 ) : ?>
                                    <strong><?php esc_html_e( 'Waiting list', 'bookly' ) ?>:</strong>
                                <?php endif ?>
                                <?php if ( isset ( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ): ?>
                                    <strong class="bookly-js-waiting-list-price"><?php echo Price::format( $cart_info->getWaitingListTotal() ) ?></strong>
                                <?php endif ?>
                                <?php if ( $table['show']['deposit'] && $position == $table['header_position']['deposit'] ) : ?>
                                    <strong class="bookly-js-waiting-list-deposit"><?php echo Price::format( $cart_info->getWaitingListDeposit() ) ?></strong>
                                <?php endif ?>
                            </td>
                        <?php endforeach ?>
                        <td></td>
                    </tr>
                <?php endif ?>
                <tr>
                    <?php foreach ( $table['headers'] as $position => $column ) : ?>
                        <td <?php if ( isset ( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ) echo 'class="bookly-rtext"' ?>>
                            <?php if ( $position == 0 ) : ?>
                                <strong><?php esc_html_e( 'Subtotal', 'bookly' ) ?>:</strong>
                            <?php endif ?>
                            <?php if ( isset( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ) : ?>
                                <strong class="bookly-js-subtotal-price"><?php echo Price::format( $cart_info->getSubtotal() ) ?></strong>
                            <?php endif ?>
                            <?php if ( $table['show']['deposit'] && $position == $table['header_position']['deposit'] ) : ?>
                                <strong class="bookly-js-subtotal-deposit"><?php echo Price::format( $cart_info->getDeposit() ) ?></strong>
                            <?php endif ?>
                        </td>
                    <?php endforeach ?>
                    <td></td>
                </tr>
                <?php BookingProxy\Discounts::renderCartDiscountRow( $table, 'desktop', $userData ) ?>
                <?php BookingProxy\CustomerGroups::renderCartDiscountRow( $table, 'desktop' ) ?>
                <tr>
                    <?php foreach ( $table['headers'] as $position => $column ) : ?>
                        <td <?php if ( isset( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ) echo 'class="bookly-rtext"' ?>>
                            <?php if ( $position == 0 ) : ?>
                                <strong><?php esc_html_e( 'Total', 'bookly' ) ?>:</strong>
                            <?php endif ?>
                            <?php if ( isset( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ) : ?>
                                <strong class="bookly-js-total-price"><?php echo Price::format( $cart_info->getTotal() ) ?></strong>
                            <?php endif ?>
                            <?php if ( $table['show']['tax'] && $position == $table['header_position']['tax'] ) : ?>
                                <strong class="bookly-js-total-tax"><?php echo Price::format( $cart_info->getTotalTax() ) ?></strong>
                            <?php endif ?>
                        </td>
                    <?php endforeach ?>
                    <td></td>
                </tr>
                <?php BookingProxy\DepositPayments::renderPayNowRow( $cart_info, $table, 'desktop' ) ?>
                </tfoot>
            <?php endif ?>
        </table>
    </div>
</div>

<?php BookingProxy\RecurringAppointments::renderInfoMessage( $userData ) ?>

<div class="bookly-box bookly-nav-steps">
    <?php if ( $show_back_btn ) : ?>
        <button class="bookly-back-step bookly-js-back-step bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40">
            <span class="ladda-label"><?php echo Common::getTranslatedOption( 'bookly_l10n_button_back' ) ?></span>
        </button>
    <?php endif ?>
    <div class="<?php echo get_option( 'bookly_app_align_buttons_left' ) ? 'bookly-left' : 'bookly-right' ?>">
        <button class="bookly-next-step bookly-js-next-step bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40">
            <span class="ladda-label"><?php echo Common::getTranslatedOption( 'bookly_l10n_step_cart_button_next' ) ?></span>
        </button>
        <?php if ( $userData->getFirstStep() != Steps::CART && get_option( 'bookly_app_button_book_more_near_next' ) == '1' ) : ?>
            <button class="bookly-add-item bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40" style="margin-right: 10px">
                <span class="ladda-label"><?php echo Common::getTranslatedOption( 'bookly_l10n_button_book_more' ) ?></span>
            </button>
        <?php endif ?>
    </div>
</div>