<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Controls\Buttons;
?>
<div class="bookly-modal bookly-fade bookly-js-customer-cabinet-delete-dialog" tabindex=-1 role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form>
                <div class="modal-header">
                    <h5><?php esc_html_e( 'Delete account', 'bookly' ) ?></h5>
                    <button type="button" class="close" data-dismiss="bookly-modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="bookly-loading"></div>
                    <div class="bookly-js-approve-deleting bookly-collapse">
                        <?php esc_html_e( 'You are going to delete your account and all information associated with it. Click Confirm to continue or Cancel to cancel the action.', 'bookly' ) ?>
                    </div>
                    <div class="bookly-js-denied-deleting bookly-collapse">
                        <?php esc_html_e( 'This account cannot be deleted because it is associated with scheduled appointments. Please cancel bookings or contact the service provider.', 'bookly' ) ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <div>
                        <?php Buttons::render( null, 'btn-danger bookly-js-approve-deleting bookly-js-confirm-delete bookly-collapse', __( 'Confirm', 'bookly' ) ) ?>
                        <?php Buttons::render( null, 'btn-default bookly-js-approve-deleting', __( 'Cancel', 'bookly' ), array( 'data-dismiss' => 'bookly-modal' ) ) ?>
                        <?php Buttons::render( null, 'btn-default bookly-js-denied-deleting', __( 'Ok', 'bookly' ), array( 'data-dismiss' => 'bookly-modal' ) ) ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
