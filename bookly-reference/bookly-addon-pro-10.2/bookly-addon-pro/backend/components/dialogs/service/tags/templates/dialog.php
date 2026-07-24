<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Controls\Buttons;
?>
<form id="bookly-service-tags-modal" class="bookly-modal bookly-fade" tabindex=-1 role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php esc_html_e( 'Tags', 'bookly' ) ?></h5>
                <button type="button" class="close" data-dismiss="bookly-modal" aria-label="Close"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="bookly-services-tags"></div>
            </div>
            <div class="modal-footer">
                <?php Buttons::renderSubmit() ?>
                <?php Buttons::renderCancel() ?>
            </div>
        </div>
    </div>
</form>

<div style="display: none">
    <div id="bookly-tag-template">
        <div class="card bookly-collapse-with-arrow">
            <div class="card-header d-flex align-items-center py-1 pr-0 border-bottom-0">
                <input type="text" class="form-control ml-3 my-0" name="name"/>
                <a href="#" class="px-3 bookly-collapsed" role="button" data-toggle="bookly-collapse"></a>
            </div>
            <div class="bookly-collapse border-top">
                <div class="card-body">
                    <div class="row form-row">
                        <div class="col-auto">
                            <div class="bookly-mw-150 bookly-thumb">
                                <i class="fas fa-fw fa-4x fa-camera mt-2 text-white w-100"></i>
                                <?php if ( current_user_can( 'upload_files' ) ) : ?>
                                    <a class="bookly-js-remove-attachment far fa-fw fa-trash-alt text-danger bookly-thumb-delete"
                                       href="javascript:void(0)"
                                       title="<?php esc_attr_e( 'Delete', 'bookly' ) ?>"
                                       style="display: none">
                                    </a>
                                    <div class="bookly-thumb-edit">
                                        <label class="bookly-thumb-edit-btn"><?php esc_html_e( 'Image', 'bookly' ) ?></label>
                                    </div>
                                <?php endif ?>
                                <input type="hidden" name="attachment_id" value="">
                            </div>
                        </div>
                        <div class="col">
                            <input type="hidden" name="id" value=""/>
                            <textarea class="form-control" name="info" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>