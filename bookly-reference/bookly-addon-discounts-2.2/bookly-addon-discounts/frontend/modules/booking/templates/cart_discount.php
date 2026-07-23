<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Lib\Utils\Price;
use BooklyDiscounts\Lib\Entities\Discount;

/**  @var array $table = ['headers' => [], 'header_position' => [], 'show' => ['deposit' => bool,'tax' => bool ] ] */
/** @var Discount[] $discounts */
/** @var string $layout */
?>
<?php if ( $layout == 'mobile' ) : ?>
    <tr>
        <th><?php esc_html_e( 'Discount', 'bookly' ) ?>:</th>
        <th>
            <?php foreach ( $discounts as $discount ) : ?>
                <?php if ( $discount->getDiscount() > 0 ) : ?><?php echo $discount->getDiscount() ?>%<br/><?php endif ?><?php if ( $discount->getDeduction() > 0 ) : ?><?php echo Price::format( $discount->getDeduction() ) ?><br/><?php endif ?>
            <?php endforeach ?>
        </th>
    </tr>
<?php else : ?>
    <tr>
        <?php foreach ( $table['headers'] as $position => $column ) : ?>
            <td <?php if ( isset( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ) echo 'class="bookly-rtext"' ?>>
                <?php if ( $position == 0 ) : ?>
                    <strong><?php esc_html_e( 'Discount', 'bookly' ) ?>:</strong>
                <?php endif ?>
                <?php if ( isset( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ) : ?>
                    <?php foreach ( $discounts as $discount ) : ?>
                        <?php if ( $discount->getDiscount() > 0 ) : ?><b><?php echo $discount->getDiscount() ?>%</b><br/><?php endif ?><?php if ( $discount->getDeduction() > 0 ) : ?><b><?php echo Price::format( $discount->getDeduction() ) ?></b><br/><?php endif ?>
                    <?php endforeach ?>
                <?php endif ?>
            </td>
        <?php endforeach ?>
        <td></td>
    </tr>
<?php endif ?>