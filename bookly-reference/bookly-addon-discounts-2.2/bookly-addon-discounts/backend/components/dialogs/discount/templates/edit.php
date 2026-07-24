<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Controls\Buttons;
use Bookly\Backend\Components\Controls\Inputs;
use BooklyDiscounts\Lib;

/** @var array $services */

$type_select = '<select class="form-control mx-sm-2 my-2 my-sm-0" id="bookly-discount-type" name="type">';
foreach ( Lib\Entities\Discount::getTypes() as $type => $data ) {
    if ( $data['available'] ) {
        $type_select .= '<option value="' . $type . '">' . $data['title'] . '</option>';
    }
}
$type_select .= '</select>';
?>
<div class="bookly-modal bookly-fade" id="bookly-discount-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form>
                <div class="modal-header">
                    <h5 class="modal-title" id="bookly-discount-modal-title"></h5>
                    <button type="button" class="close" data-dismiss="bookly-modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="col-12">
                            <div class=form-group>
                                <label for="bookly-discount-title"><?php esc_html_e( 'Title', 'bookly' ) ?></label>
                                <input type="text" id="bookly-discount-title" class="form-control" name="title" required/>
                            </div>
                        </div>
                        <div class="col-12">
                            <?php Inputs::renderRadioGroup( __( 'State', 'bookly' ), __( 'Select if the discount is enabled and applied to the booking price if the conditions are met, or if it is disabled and not applied', 'bookly' ), array(), 1, array( 'name' => 'enabled' ) ) ?>
                        </div>
                        <div class="col-sm-6">
                            <div class=form-group>
                                <label for="bookly-discount-discount"><?php esc_html_e( 'Discount (%)', 'bookly' ) ?></label>
                                <input type="number" id="bookly-discount-discount" class="form-control" name="discount" min="0" step="any" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class=form-group>
                                <label for="bookly-discount-deduction"><?php esc_html_e( 'Deduction', 'bookly' ) ?></label>
                                <input type="number" id="bookly-discount-deduction" class="form-control" name="deduction" min="0" step="any" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="bookly-discount-date-start"><?php esc_html_e( 'Date limit (from and to)', 'bookly' ) ?></label>
                        </div>
                        <div class="col-sm-6">
                            <div class=form-group>
                                <div class="input-group">
                                    <input type="text" id="bookly-discount-date-start" class="form-control" autocomplete="off" placeholder="<?php esc_attr_e( 'No limit', 'bookly' ) ?>"/>
                                    <input type="hidden" name="date_start"/>
                                    <span class="input-group-append">
                                        <button class="btn btn-default" type="button" id="bookly-discount-clear-date-start" title="<?php esc_attr_e( 'Clear field', 'bookly' ) ?>">&times;</button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class=form-group>
                                <div class="input-group">
                                    <input type="text" id="bookly-discount-date-end" class="form-control" autocomplete="off" placeholder="<?php esc_attr_e( 'No limit', 'bookly' ) ?>"/>
                                    <input type="hidden" name="date_end"/>
                                    <span class="input-group-append">
                                        <button class="btn btn-default" type="button" id="bookly-discount-clear-date-end" title="<?php esc_attr_e( 'Clear field', 'bookly' ) ?>">&times;</button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mt-2">
                            <div class=form-group>
                                <label for="bookly-discount-type"><?php esc_html_e( 'Condition to apply the discount', 'bookly' ) ?></label>
                                <div class="d-sm-flex align-items-center text-nowrap">
                                    <?php echo sprintf(
                                        __( 'If %s equals or exceeds %s', 'bookly' ),
                                        $type_select,
                                        '<input type="number" id="bookly-discount-threshold" class="form-control ml-sm-2 my-2 my-sm-0" name="threshold" min="1" required />'
                                    ) ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 bookly-js-discount-services-wrap">
                            <div class=form-group>
                                <div class="d-lg-flex align-items-center">
                                    <?php esc_html_e( 'and the service is one of the selected', 'bookly' ) ?>
                                    <div class="mt-2 mt-lg-0 ml-lg-2 flex-fill">
                                        <ul class="bookly-js-simple-dropdown"
                                            id="bookly-discount-services"
                                            data-container-class="bookly-dropdown-block"
                                            data-icon-class="far fa-dot-circle"
                                            data-txt-select-all="<?php esc_attr_e( 'All services', 'bookly' ) ?>"
                                            data-txt-all-selected="<?php esc_attr_e( 'All services', 'bookly' ) ?>"
                                            data-txt-nothing-selected="<?php esc_attr_e( 'No service selected', 'bookly' ) ?>"
                                        >
                                            <?php foreach ( $services as $category_id => $category ) : ?>
                                                <li<?php if ( ! $category_id ) : ?> data-flatten-if-single<?php endif ?>><?php echo esc_html( $category['name'] ) ?>
                                                    <ul>
                                                        <?php foreach ( $category['items'] as $service ): ?>
                                                            <li data-input-name="service_ids[]" data-value="<?php echo $service['id'] ?>">
                                                                <?php echo esc_html( $service['title'] ) ?>
                                                            </li>
                                                        <?php endforeach ?>
                                                    </ul>
                                                </li>
                                            <?php endforeach ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php Inputs::renderCsrf() ?>
                    <?php Buttons::renderSubmit( 'bookly-discount-save' ) ?>
                    <?php Buttons::renderCancel() ?>
                </div>
            </form>
        </div>
    </div>
</div>