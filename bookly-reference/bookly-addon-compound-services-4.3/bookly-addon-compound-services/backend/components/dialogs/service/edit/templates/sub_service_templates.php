<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Lib\Utils\DateTime;
use Bookly\Backend\Components\Controls\Elements;
/**
 * @var array $simple_services
 */
$time_interval = get_option( 'bookly_gen_time_slot_length' );
?>
<div class="bookly-js-templates services">
    <div class="template_0">
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
                    <?php _e( 'Duration', 'bookly' ) ?>:
                </div>
                <div class="col-2">
                    <select class="form-control custom-select" name="duration">
                        <?php for ( $j = $time_interval; $j <= 720; $j += $time_interval ): ?>
                            <option value="<?php echo $j * 60 ?>"><?php echo DateTime::secondsToInterval( $j * 60 ) ?></option>
                        <?php endfor ?>
                        <?php for ( $j = 86400; $j <= 604800; $j += 86400 ): ?>
                            <option value="<?php echo $j ?>"><?php echo DateTime::secondsToInterval( $j ) ?></option>
                        <?php endfor ?>
                    </select>
                </div>
            </div>
        </li>
    </div>
    <?php foreach ( $simple_services as $service ) : ?>
        <div class="template_<?php echo $service['id'] ?>">
            <li class="list-group-item bookly-js-sub-service" data-sub-service-id="<?php echo $service['id'] ?>">
                <div class="h5 form-row align-items-center">
                    <?php Elements::renderReorder() ?>
                    <div class="px-2">
                        <i class="fas fa-fw fa-circle" style="color: <?php echo esc_attr( $service['color'] ) ?>">&nbsp;</i>
                    </div>
                    <div class="mr-auto">
                        <?php echo esc_html( $service['title'] ) ?>
                    </div>
                    <button class="btn btn-link p-0 text-danger bookly-js-sub-service-remove" title="<?php esc_attr_e( 'Delete', 'bookly' ) ?>"><i class="far fa-fw fa-trash-alt"></i></button>
                </div>
                <div><?php esc_html_e( 'Duration', 'bookly' ) ?>: <?php echo DateTime::secondsToInterval( $service['duration'] ) ?></div>
            </li>
        </div>
    <?php endforeach ?>
</div>