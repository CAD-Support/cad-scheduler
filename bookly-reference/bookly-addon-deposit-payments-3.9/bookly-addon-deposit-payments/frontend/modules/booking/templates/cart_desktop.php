<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Lib\Utils\Price;

/** @var \Bookly\Lib\CartInfo $cart_info
 * @var array $table = ['headers' => [], 'header_position' => [], 'show' => ['deposit' => bool, 'tax' => bool] ]
 */
?>
<tr>
    <?php foreach ( $table['headers'] as $position => $column ) : ?>
        <td <?php if ( isset ( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ) echo 'class="bookly-rtext"' ?>>
            <?php if ( $position == 0 ) : ?>
                <strong><?php esc_html_e( 'Pay now', 'bookly' ) ?>:</strong>
            <?php endif ?>
            <?php if ( ! $table['show']['deposit'] && $position == $table['header_position']['price'] ) : ?>
                <strong class="bookly-js-pay-now-deposit"><?php echo Price::format( $cart_info->getPayNow() ) ?></strong>
            <?php endif ?>
            <?php if ( $table['show']['deposit'] && $position == $table['header_position']['deposit'] ) : ?>
                <strong class="bookly-js-pay-now-deposit"><?php echo Price::format( $cart_info->getPayNow() ) ?></strong>
            <?php endif ?>
            <?php if ( $table['show']['tax'] && $position == $table['header_position']['tax'] ) : ?>
                <strong class="bookly-js-pay-now-tax"><?php echo Price::format( $cart_info->getPayTax() ) ?></strong>
            <?php endif ?>
        </td>
    <?php endforeach ?>
    <td></td>
</tr>
