<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Lib\Utils\DateTime;
use Bookly\Backend\Components\Controls\Elements;
/**
 * @var array $simple_services
 */
?>
<div class="bookly-js-templates bookly-js-collaborative-services">
    <?php foreach ( $simple_services as $service ) : ?>
        <div class="template_<?php echo $service['id'] ?>">
            <li class="list-group-item bookly-js-collaborative-sub-service" data-sub-service-id="<?php echo $service['id'] ?>">
                <div class="h5 form-row align-items-center">
                    <?php Elements::renderReorder() ?>
                    <div class="px-2">
                        <i class="fas fa-fw fa-circle" style="color: <?php echo esc_attr( $service['color'] ) ?>">&nbsp;</i>
                    </div>
                    <div class="mr-auto">
                        <?php echo esc_html( $service['title'] ) ?>
                    </div>
                    <button class="btn btn-link p-0 text-danger bookly-js-collaborative-sub-service-remove" title="<?php esc_attr_e( 'Delete', 'bookly' ) ?>"><i class="far fa-fw fa-trash-alt"></i></button>
                </div>
                <div><?php esc_html_e( 'Duration', 'bookly' ) ?>: <?php echo DateTime::secondsToInterval( $service['duration'] ) ?></div>
            </li>
        </div>
    <?php endforeach ?>
</div>