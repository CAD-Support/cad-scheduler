<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly ?>
<?php foreach ( $field_ids as $field_id ) : ?>
    <td>
        <?php if ( isset ( $field_values[ $field_id ] ) ): ?>
            <?php echo $field_values[ $field_id ] ?>
        <?php endif ?>
    </td>
<?php endforeach ?>