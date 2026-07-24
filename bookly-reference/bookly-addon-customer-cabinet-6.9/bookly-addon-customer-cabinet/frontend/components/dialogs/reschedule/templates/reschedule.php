<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Controls\Buttons;
?>
<div id="bookly-customer-cabinet-reschedule-dialog" class="bookly-modal bookly-fade" tabindex=-1 role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form>
                <div class="modal-header">
                    <h5><?php esc_html_e( 'Reschedule', 'bookly' ) ?></h5>
                    <button type="button" class="close" data-dismiss="bookly-modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger bookly-collapse" id="bookly-reschedule-error"></div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="bookly-reschedule-date"><?php esc_html_e( 'Date', 'bookly' ) ?></label>
                                <input id="bookly-reschedule-date" type="text" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="bookly-reschedule-time"><?php esc_html_e( 'Time', 'bookly' ) ?></label>
                                <select id="bookly-reschedule-time" class="form-control custom-select"></select>
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
