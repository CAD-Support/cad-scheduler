<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Lib\Utils\Price;
use BooklyDiscounts\Lib;

/**  @var Lib\Entities\Discount[] $discounts */
/**  @var int $cart_key */
?>
<?php if ( isset( $positions['price'] ) ) : ?>
    <tr data-cart-key="<?php echo $cart_key ?>">
        <th><?php esc_html_e( 'Discount', 'bookly' ) ?></th>
        <td>
            <?php foreach ( $discounts as $discount ) : ?>
                <?php if ( $discount->getDiscount() > 0 ) : ?><?php echo $discount->getDiscount() ?>%<br/><?php endif ?><?php if ( $discount->getDeduction() > 0 ) : ?><?php echo Price::format( $discount->getDeduction() ) ?><br/><?php endif ?>
            <?php endforeach ?>
        </td>
    </tr>
<?php endif ?>
