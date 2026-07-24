<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Lib\Utils\Price;
use BooklyDiscounts\Lib;

/**  @var Lib\Entities\Discount[] $discounts */
/**  @var array $positions */
/**  @var int $cart_key */
?>
<tr data-cart-key="<?php echo $cart_key ?>">
    <?php foreach ( $positions as $position ) : ?>
        <?php if ( isset( $positions['price'] ) && $position == $positions['price'] ) : ?>
            <td class='bookly-rtext'>
                <?php foreach ( $discounts as $discount ) : ?>
                    <?php if ( $discount->getDiscount() > 0 ) : ?><?php echo $discount->getDiscount() ?>%<br/><?php endif ?><?php if ( $discount->getDeduction() > 0 ) : ?><?php echo Price::format( $discount->getDeduction() ) ?><br/><?php endif ?>
                <?php endforeach ?>
            </td>
        <?php elseif ( isset( $positions['service'] ) && $position == $positions['service'] ) : ?>
            <td class="bookly-cart-sub-item">
                <?php esc_html_e( 'Discount', 'bookly' ) ?>
            </td>
        <?php else: ?>
            <td>
            </td>
        <?php endif ?>
    <?php endforeach ?>
    <td></td>
</tr>