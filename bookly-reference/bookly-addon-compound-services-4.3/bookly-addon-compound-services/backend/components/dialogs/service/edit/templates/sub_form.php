<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Lib\Entities\SubService;
use Bookly\Lib\Utils\DateTime;
use Bookly\Backend\Components\Controls\Elements;
use Bookly\Backend\Components\Controls\Inputs;
/**
 * @var array $service
 * @var array $simple_services
 */
$time_interval = get_option( 'bookly_gen_time_slot_length' );
?>
<div class="form-group">
    <div class="list-group bookly-js-sub-services" style="overflow: auto;">
        <?php foreach ( $service['sub_services'] as $sub_service ) : ?>
            <?php if ( $sub_service['type'] == SubService::TYPE_SERVICE ) : ?>
                <li class="list-group-item bookly-js-sub-service" data-sub-service-id="<?php echo $sub_service['sub_service_id'] ?>">
                    <div class="h5 form-row align-items-center">
                        <?php Elements::renderReorder() ?>
                        <div class="px-2">
                            <i class="fas fa-fw fa-circle" style="color: <?php echo esc_attr( $simple_services[ $sub_service['sub_service_id'] ]['color'] ) ?>">&nbsp;</i>
                        </div>
                        <div class="mr-auto">
                            <?php echo $simple_services[ $sub_service['sub_service_id'] ]['title'] ?>
                        </div>
                        <button class="btn btn-link p-0 text-danger bookly-js-sub-service-remove" title="<?php esc_attr_e( 'Delete', 'bookly' ) ?>"><i class="far fa-fw fa-trash-alt"></i></button>
                    </div>
                    <div><?php esc_attr_e( 'Duration', 'bookly' ) ?>: <?php echo DateTime::secondsToInterval( $simple_services[ $sub_service['sub_service_id'] ]['duration'] ) ?></div>
                </li>
            <?php else: ?>
                <li class="list-group-item bookly-js-sub-service">
                    <div class="h5 form-row align-items-center">
                        <?php Elements::renderReorder() ?>
                        <div class="mr-auto pl-2">
                            <?php esc_html_e( 'Spare time', 'bookly' ) ?>
                        </div>
                        <button class="btn btn-link p-0 text-danger bookly-js-sub-service-remove" title="<?php esc_attr_e( 'Delete', 'bookly' ) ?>"><i class="far fa-fw fa-trash-alt"></i></button>
                    </div>
                    <div class="form-row">
                        <div class="col-auto mt-2">
                            <?php esc_html_e( 'Duration', 'bookly' ) ?>:
                        </div>
                        <div class="col-2">
                        <select class="form-control custom-select" name="duration">
                            <?php for ( $j = $time_interval; $j <= 720; $j += $time_interval ) : ?><?php if ( $sub_service['duration'] / 60 > $j - $time_interval && $sub_service['duration'] / 60 < $j ) : ?>
                                <option value="<?php echo esc_attr( $sub_service['duration'] ) ?>" selected><?php echo DateTime::secondsToInterval( $sub_service['duration'] ) ?></option><?php endif ?>
                                <option value="<?php echo $j * 60 ?>" <?php selected( $sub_service['duration'], $j * 60 ) ?>><?php echo DateTime::secondsToInterval( $j * 60 ) ?></option><?php endfor ?>
                            <?php for ( $j = 86400; $j <= 604800; $j += 86400 ) : ?>
                                <option value="<?php echo $j ?>" <?php selected( $sub_service['duration'], $j ) ?>><?php echo DateTime::secondsToInterval( $j ) ?></option><?php endfor ?>
                        </select>
                        </div>
                    </div>
                </li>
            <?php endif ?>
        <?php endforeach ?>
        <li class="list-group-item form-group mb-0">
            <select class="form-control bookly-js-compound-service-list custom-select">
                <option value="-1"><?php esc_html_e( 'Add simple service', 'bookly' ) ?></option>
                <option value="0"><?php esc_html_e( '=== Spare time ===', 'bookly' ) ?></option>
                <?php foreach ( $simple_services as $_service ) : ?>
                    <?php if ( $_service['id'] != $service['id'] ) : ?>
                        <option value="<?php echo $_service['id'] ?>"><?php echo esc_html( $_service['title'] ) ?></option>
                    <?php endif ?>
                <?php endforeach ?>
            </select>
        </li>
    </div>
</div>
<div class="form-group">
    <?php Inputs::renderRadioGroup( __( 'Same staff member for all sub-services', 'bookly' ), __( 'If this option is enabled and the customer selects a staff member in the first step of the booking form then only this staff will be used for searching available slots for all sub-services.', 'bookly' ), array(), $service['same_staff_for_subservices'], array( 'name' => 'same_staff_for_subservices' ) ) ?>
</div>