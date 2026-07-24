<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Lib\Utils\Price;
/** @var \Bookly\Lib\CartInfo $cart_info
 *  @var array $table = ['headers' => [], 'header_position' => [], 'show' => ['deposit' => bool,'tax' => bool ] ] */
?>
<tr>
    <th><?php esc_html_e( 'Pay now', 'bookly' ) ?>:</th>
    <td><strong class="bookly-js-pay-now-price"><?php echo Price::format( $cart_info->getPayNow() ) ?></strong></td>
</tr>
<?php if ( $table['show']['tax'] ) : ?>
<tr>
    <th><?php esc_html_e( 'Pay now tax', 'bookly' ) ?>:</th>
    <td><strong class="bookly-js-pay-now-tax"><?php echo Price::format( $cart_info->getPayTax()) ?></strong></td>
</tr>
<?php endif ?>
