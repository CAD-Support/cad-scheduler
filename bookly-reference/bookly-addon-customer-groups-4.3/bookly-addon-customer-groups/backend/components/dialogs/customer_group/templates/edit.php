<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Controls\Buttons;
use Bookly\Backend\Components\Controls\Inputs;
use Bookly\Lib\Entities\CustomerAppointment;
use Bookly\Backend\Components\Controls;
?>
<div id="bookly-customer-groups-dialog" class="bookly-modal bookly-fade" tabindex=-1 role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form>
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="close" data-dismiss="bookly-modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="bookly-loading"></div>
                    <div class="bookly-js-modal-body">
                        <div>
                            <div class="form-group bookly-js-group-name">
                                <label for="bookly-cg-name"><?php esc_html_e( 'Group name', 'bookly' ) ?></label>
                                <input class="form-control" type="text" name="name" id="bookly-cg-name"/>
                                <small class="form-text text-danger bookly-js-error-alert bookly-js-error-name bookly-collapse"></small>
                            </div>
                            <div class="form-group">
                                <label for="bookly-cg-status"><?php esc_html_e( 'Default appointment status', 'bookly' ) ?></label>
                                <select class="form-control custom-select" name="status" id="bookly-cg-status">
                                    <option value=""><?php esc_html_e( 'Default', 'bookly' ) ?></option>
                                    <?php foreach ( CustomerAppointment::getStatuses() as $status ) : ?>
                                        <option value="<?php echo esc_attr( $status ) ?>">
                                            <?php echo esc_html( CustomerAppointment::statusToString( $status ) ) ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <?php Inputs::renderCheckBox( __( 'Skip payment step', 'bookly' ), '1', null, array( 'name' => 'skip_payment' ) ) ?>
                            </div>
                            <div id="bookly-js-payment-step">
                                <div class='form-group'>
                                    <?php Controls\Inputs::renderRadioGroup( __( 'Available payment methods', 'bookly' ), null,
                                        array(
                                            'default' => array( 'title' => __( 'Default', 'bookly' ) ),
                                            'custom' => array( 'title' => __( 'Custom', 'bookly' ) ),
                                        ),
                                        'default', array( 'name' => 'gateways' ) ) ?>
                                </div>
                                <div class="form-group border-left ml-4 pl-3">
                                    <ul id="bookly-js-gateways-list"
                                        data-icon-class='fas fa-hand-holding-usd'
                                        data-txt-select-all="<?php esc_attr_e( 'All methods', 'bookly' ) ?>"
                                        data-txt-all-selected="<?php esc_attr_e( 'All methods', 'bookly' ) ?>"
                                        data-txt-nothing-selected="<?php esc_attr_e( 'No methods selected', 'bookly' ) ?>"
                                    >
                                        <?php foreach ( $gateways as $gateway => $title ): ?>
                                            <li data-input-name="gateways_list[]" data-value="<?php echo $gateway ?>">
                                                <?php echo esc_html( $title ) ?>
                                            </li>
                                        <?php endforeach ?>
                                    </ul>
                                </div>
                                <div class="form-group">
                                    <label for="bookly-cg-discount"><?php esc_html_e( 'Total discount', 'bookly' ) ?></label>
                                    <input class="form-control" type="text" name="discount" id="bookly-cg-discount"/>
                                    <small class="form-text text-muted"><?php esc_html_e( 'Enter the fixed amount of discount (e.g. 10 off). To specify a percentage discount (e.g. 10% off), add \'%\' symbol to a numerical value.', 'bookly' ) ?></small>
                                </div>
                            </div>
                            <div class="form-group bookly-js-group-description">
                                <label for="bookly-cg-description"><?php esc_html_e( 'Description', 'bookly' ) ?></label>
                                <textarea class="form-control" name="description" id="bookly-cg-description"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div>
                        <?php Buttons::renderSubmit() ?>
                        <?php Buttons::renderCancel() ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>