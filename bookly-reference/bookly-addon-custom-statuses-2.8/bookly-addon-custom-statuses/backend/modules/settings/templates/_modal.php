<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Controls\Buttons;
use Bookly\Backend\Components\Controls\Inputs;
?>
<div class="bookly-modal bookly-fade" id="bookly-custom-statuses-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form>
                <div class="modal-header">
                    <h5 class="modal-title" id="bookly-custom-statuses-new-title"><?php esc_html_e( 'New Status', 'bookly' ) ?></h5>
                    <h5 class="modal-title" id="bookly-custom-statuses-edit-title"><?php esc_html_e( 'Edit Status', 'bookly' ) ?></h5>
                    <button type="button" class="close" data-dismiss="bookly-modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class=form-group>
                                <label for="bookly-custom-statuses-name"><?php esc_html_e( 'Name', 'bookly' ) ?></label>
                                <input type="text" id="bookly-custom-statuses-name" class="form-control" name="name" />
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class=form-group>
                                <label for="bookly-custom-statuses-busy"><?php esc_html_e( 'Free/Busy', 'bookly' ) ?></label>
                                <select id="bookly-custom-statuses-busy" class="form-control" name="busy">
                                    <option value="0"><?php esc_html_e( 'Free', 'bookly' ) ?></option>
                                    <option value="1"><?php esc_html_e( 'Busy', 'bookly' ) ?></option>
                                </select>
                                <small class="form-text text-muted"><?php esc_html_e( 'If you select busy, then a customer with this status will occupy a place in appointment. If you select free, then a place will be considered as free.', 'bookly' ) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php Inputs::renderCsrf() ?>
                    <?php Buttons::renderSubmit( 'bookly-custom-statuses-save' ) ?>
                    <?php Buttons::renderCancel() ?>
                </div>
            </form>
        </div>
    </div>
</div>