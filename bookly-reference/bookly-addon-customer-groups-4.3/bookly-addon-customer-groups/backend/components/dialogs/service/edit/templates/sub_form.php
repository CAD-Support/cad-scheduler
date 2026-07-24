<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Lib\Entities\Service;
?>
<div class="form-group bookly-js-groups-list<?php if ( $service['visibility'] != Service::VISIBILITY_GROUP_BASED ) : ?> bookly-collapse<?php endif ?> border-left ml-4 pl-3">
    <label><?php esc_html_e( 'Groups', 'bookly' ) ?></label><br/>
    <ul class="bookly-js-simple-dropdown"
        data-container-class="bookly-dropdown-block"
        data-icon-class="fas fa-user-friends"
        data-txt-select-all="<?php esc_attr_e( 'All groups', 'bookly' ) ?>"
        data-txt-all-selected="<?php esc_attr_e( 'All groups', 'bookly' ) ?>"
        data-txt-nothing-selected="<?php esc_attr_e( 'No group selected', 'bookly' ) ?>"
    >
        <?php foreach ( $groups_collection as $group ): ?>
            <li data-input-name="group_ids[]" data-value="<?php echo $group['id'] ?>" data-selected="<?php echo (int) in_array( $group['id'], $group_ids ) ?>">
                <?php echo esc_html( $group['name'] ) ?>
            </li>
        <?php endforeach ?>
    </ul>
</div>
