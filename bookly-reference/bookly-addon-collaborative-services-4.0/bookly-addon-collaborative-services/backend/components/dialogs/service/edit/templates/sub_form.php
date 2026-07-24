<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Lib\Entities\SubService;
use Bookly\Lib\Utils\DateTime;
use Bookly\Backend\Components\Controls\Elements;
/**
 * @var array $service
 * @var array $simple_services
 */
?>
<div class="form-group">
    <div class="list-group bookly-js-collaborative-sub-services" style="overflow: auto;">
        <?php foreach ( $service['sub_services'] as $sub_service ) : ?>
            <?php if ( $sub_service['type'] == SubService::TYPE_SERVICE ) : ?>
                <li class="list-group-item bookly-js-collaborative-sub-service" data-sub-service-id="<?php echo $sub_service['sub_service_id'] ?>">
                    <div class="h5 form-row align-items-center">
                        <?php Elements::renderReorder() ?>
                        <div class="px-2">
                            <i class="fas fa-fw fa-circle" style="color: <?php echo esc_attr( $simple_services[ $sub_service['sub_service_id'] ]['color'] ) ?>">&nbsp;</i>
                        </div>
                        <div class="mr-auto">
                            <?php echo esc_html( $simple_services[ $sub_service['sub_service_id'] ]['title'] ) ?>
                        </div>
                        <button class="btn btn-link p-0 text-danger bookly-js-collaborative-sub-service-remove" title="<?php esc_attr_e( 'Delete', 'bookly' ) ?>"><i class="far fa-fw fa-trash-alt"></i></button>
                    </div>
                    <div><?php esc_html_e( 'Duration', 'bookly' ) ?>: <?php echo DateTime::secondsToInterval( $simple_services[ $sub_service['sub_service_id'] ]['duration'] ) ?></div>
                </li>
            <?php endif ?>
        <?php endforeach ?>
        <li class="list-group-item form-group">
            <select class="form-control bookly-js-collaborative-sub-service-add custom-select">
                <option value="-1"><?php esc_html_e( 'Add simple service', 'bookly' ) ?></option>
                <?php foreach ( $simple_services as $_service ) : ?>
                    <?php if ( $_service['id'] != $service['id'] ) : ?>
                        <option value="<?php echo $_service['id'] ?>"><?php echo esc_html( $_service['title'] ) ?></option>
                    <?php endif ?>
                <?php endforeach ?>
            </select>
        </li>
    </div>
    <div class="form-group">
        <label><?php esc_html_e( 'Equal duration', 'bookly' ) ?></label>

        <div class="custom-control custom-radio">
            <input class="custom-control-input" id="bookly-ed-0" type="radio" name="collaborative_equal_duration" value="0" <?php checked( $service['collaborative_equal_duration'], 0 ) ?>>
            <label class="custom-control-label" for="bookly-ed-0"><?php esc_html_e( 'Disabled', 'bookly' ) ?></label>
        </div>
        <div class="custom-control custom-radio">
            <input class="custom-control-input" id="bookly-ed-1" type="radio" name="collaborative_equal_duration" value="1" <?php checked( $service['collaborative_equal_duration'], 1 ) ?>>
            <label class="custom-control-label" for="bookly-ed-1"><?php esc_html_e( 'Enabled', 'bookly' ) ?></label>
        </div>
        <small class="form-text text-muted"><?php esc_html_e( 'Make every service duration equal to the duration of the longest one.', 'bookly' ) ?></small>
    </div>
</div>
