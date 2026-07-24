<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
/**  @var array $table = ['headers' => [], 'header_position' => [], 'show' => ['deposit' => bool,'tax' => bool ] ] */
use Bookly\Lib\Utils\Price;
?>
<?php if ( $layout == 'mobile' ) : ?>
<tr>
    <th><?php esc_html_e( 'Group discount', 'bookly' ) ?>:</th>
    <td><strong><?php echo strpos( $discount, '%' ) === false ? Price::format( $discount ) : $discount ?></strong></td>
</tr>
<?php else : ?>
<tr>
    <?php foreach ( $table['headers'] as $position => $column ) : ?>
        <td <?php if ( isset( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ) echo 'class="bookly-rtext"' ?>>
            <?php if ( $position == 0 ) : ?>
                <strong><?php esc_html_e( 'Group discount', 'bookly' ) ?>:</strong>
            <?php endif ?>
            <?php if ( isset( $table['header_position']['price'] ) && $position == $table['header_position']['price'] ) : ?>
                <strong><?php echo strpos( $discount, '%' ) === false ? Price::format( $discount ) : $discount ?></strong>
            <?php endif ?>
        </td>
    <?php endforeach ?>
    <td></td>
</tr>
<?php endif ?>