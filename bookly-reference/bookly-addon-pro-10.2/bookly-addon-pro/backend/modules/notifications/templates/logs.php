<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Controls\Buttons;
use Bookly\Backend\Components\Settings\Selects;
use Bookly\Lib\Utils\DateTime;
?>
<div class="row">
    <div class="col mb-3">
        <div id="bookly-email-logs-datatables"></div>
    </div>
    <div class="col-12">
        <?php Selects::renderSingle( 'bookly_email_logs_expire', __( 'Keep logs', 'bookly' ), null, array( array( '7', sprintf( _n( '%d day', '%d days', 7, 'bookly' ), 7 ) ), array( '30', sprintf( _n( '%d day', '%d days', 30, 'bookly' ), 30 ) ), array( '90', sprintf( _n( '%d day', '%d days', 90, 'bookly' ), 90 ) ), array( '365', sprintf( _n( '%d day', '%d days', 365, 'bookly' ), 365 ) ) ) ) ?>
    </div>
</div>
<div id="bookly-email-logs-dialog" class="bookly-modal bookly-fade" tabindex=-1 role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php esc_html_e( 'Email details', 'bookly' ) ?></h5>
                <button type="button" class="close" data-dismiss="bookly-modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="bookly-email-to"><?php esc_html_e( 'Recipient', 'bookly' ) ?></label>
                    <input type="text" id="bookly-email-to" class="form-control" readonly/>
                </div>
                <div class="form-group">
                    <label for="bookly-email-subject"><?php esc_html_e( 'Subject', 'bookly' ) ?></label>
                    <input type="text" id="bookly-email-subject" class="form-control" readonly/>
                </div>
                <div class="form-group">
                    <label for="bookly-email-body"><?php esc_html_e( 'Message', 'bookly' ) ?></label>
                    <textarea id="bookly-email-body" class="form-control" rows="12" readonly></textarea>
                </div>
                <div class="form-group">
                    <label for="bookly-email-headers"><?php esc_html_e( 'Headers', 'bookly' ) ?></label>
                    <textarea id="bookly-email-headers" class="form-control" rows="6" readonly></textarea>
                </div>
                <div class="form-group">
                    <label for="bookly-email-attachments"><?php esc_html_e( 'Attachments', 'bookly' ) ?></label>
                    <textarea id="bookly-email-attachments" class="form-control" rows="4" readonly></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <?php Buttons::renderCancel( __( 'Close', 'bookly' ) ) ?>
            </div>
        </div>
    </div>
</div>