<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Controls\Elements;
use Bookly\Backend\Components\Settings\Selects;
/** @var string $services_html */
/** @var string $description_html */
?>
<div class="form-row">
    <div class="col-md-5">
        <?php Selects::renderSingle( 'bookly_custom_fields_per_service', __( 'Bind fields to services', 'bookly' ), __( 'When this setting is enabled you will be able to create service specific custom fields.', 'bookly' ) ) ?>
    </div>
    <div class="col-md-5" id="bookly-js-merge-repeating" style="display: none">
        <?php Selects::renderSingle( 'bookly_custom_fields_merge_repeating', __( 'Merge repeating custom fields for multiple bookings of the service', 'bookly' ), __( 'If enabled, customers will see custom fields for unique appointments while booking multiple instances of the service. Repeating custom fields are merged (collapsed) into one field. If disabled, customers will see custom fields for each appointment in the set of bookings.', 'bookly' ) ) ?>
    </div>
</div>

<hr/>

<ul id="bookly-custom-fields" class="list-unstyled"></ul>

<div id="bookly-js-add-fields">
    <button class="btn btn-default mb-2 mr-1" data-type="text-field"><i class="fas fa-fw fa-plus mr-1"></i><?php esc_html_e( 'Text Field', 'bookly' ) ?></button>
    <button class="btn btn-default mb-2 mr-1" data-type="textarea"><i class="fas fa-fw fa-plus mr-1"></i><?php esc_html_e( 'Text Area', 'bookly' ) ?></button>
    <button class="btn btn-default mb-2 mr-1" data-type="text-content"><i class="fas fa-fw fa-plus mr-1"></i><?php esc_html_e( 'Text Content', 'bookly' ) ?></button>
    <button class="btn btn-default mb-2 mr-1" data-type="checkboxes"><i class="fas fa-fw fa-plus mr-1"></i><?php esc_html_e( 'Checkbox Group', 'bookly' ) ?></button>
    <button class="btn btn-default mb-2 mr-1" data-type="radio-buttons"><i class="fas fa-fw fa-plus mr-1"></i><?php esc_html_e( 'Radio Button Group', 'bookly' ) ?></button>
    <button class="btn btn-default mb-2 mr-1" data-type="drop-down"><i class="fas fa-fw fa-plus mr-1"></i><?php esc_html_e( 'Drop Down', 'bookly' ) ?></button>
    <button class="btn btn-default mb-2 mr-1" data-type="number"><i class="fas fa-fw fa-plus mr-1"></i><?php esc_html_e( 'Numeric Field', 'bookly' ) ?></button>
    <button class="btn btn-default mb-2 mr-1" data-type="date"><i class="fas fa-fw fa-plus mr-1"></i><?php esc_html_e( 'Date Field', 'bookly' ) ?></button>
    <button class="btn btn-default mb-2 mr-1" data-type="time"><i class="fas fa-fw fa-plus mr-1"></i><?php esc_html_e( 'Time Field', 'bookly' ) ?></button>
    <button class="btn btn-default mb-2 mr-1" data-type="captcha"><i class="fas fa-fw fa-plus mr-1"></i><?php esc_html_e( 'Captcha', 'bookly' ) ?></button>
    <?php Bookly\Lib\Proxy\Files::renderCustomFieldButton() ?>
</div>

<small class="text-muted form-text"><?php esc_html_e( 'HTML allowed in all texts and labels.', 'bookly' ) ?></small>

<ul id="bookly-templates" style="display:none">

    <li data-type="textarea">
        <div class="form-row">
            <div class="col-auto">
                <?php Elements::renderReorder( 'bookly-js-reorder-cf' ) ?>
            </div>
            <div class="col">
                <div class="mb-2">
                    <?php esc_html_e( 'Text Area', 'bookly' ) ?><span class="bookly-js-replace-code text-muted"></span>
                    <a href="#" class="bookly-js-delete far fa-fw fa-trash-alt text-danger"
                       title="<?php esc_attr_e( 'Remove field', 'bookly' ) ?>"></a>
                </div>
                <div class="form-row">
                    <div class="col-md-8 mb-3">
                        <div class="input-group">
                            <input class="bookly-js-label form-control" type="text"
                                   placeholder="<?php esc_attr_e( 'Enter a label', 'bookly' ) ?>"/>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <div class="custom-control custom-checkbox">
                                        <input class="bookly-js-required custom-control-input" type="checkbox"/>
                                        <label class="custom-control-label">
                                            <span class="d-none d-sm-inline"><?php esc_html_e( 'Required field', 'bookly' ) ?></span>
                                            <i class="d-sm-none fas fa-fw fa-asterisk"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <?php echo $services_html ?>
                    </div>
                </div>
                <?php echo $description_html ?>
            </div>
        </div>
        <hr class="mt-0"/>
    </li>

    <li data-type="text-content">
        <div class="form-row">
            <div class="col-auto">
                <?php Elements::renderReorder( 'bookly-js-reorder-cf' ) ?>
            </div>
            <div class="col">
                <div class="mb-2">
                    <?php esc_html_e( 'Text Content', 'bookly' ) ?>
                    <a href="#" class="bookly-js-delete far fa-fw fa-trash-alt text-danger"
                       title="<?php esc_attr_e( 'Remove field', 'bookly' ) ?>"></a>
                </div>
                <div class="form-row">
                    <div class="col-md-8 mb-3">
                                    <textarea class="bookly-js-label form-control" type="text" rows="3"
                                              placeholder="<?php esc_attr_e( 'Enter a content', 'bookly' ) ?>"></textarea>
                    </div>
                    <div class="col-auto">
                        <?php echo $services_html ?>
                    </div>
                </div>
                <?php echo $description_html ?>
            </div>
        </div>
        <hr class="mt-0"/>
    </li>

    <li data-type="text-field">
        <div class="form-row">
            <div class="col-auto">
                <?php Elements::renderReorder( 'bookly-js-reorder-cf' ) ?>
            </div>
            <div class="col">
                <div class="mb-2">
                    <?php esc_html_e( 'Text Field', 'bookly' ) ?><span class="bookly-js-replace-code text-muted"></span>
                    <a href="#" class="bookly-js-delete far fa-fw fa-trash-alt text-danger"
                       title="<?php esc_attr_e( 'Remove field', 'bookly' ) ?>"></a>
                </div>
                <div class="form-row">
                    <div class="col-md-8 mb-3">
                        <div class="input-group">
                            <input class="bookly-js-label form-control" type="text"
                                   placeholder="<?php esc_attr_e( 'Enter a label', 'bookly' ) ?>"/>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <div class="custom-control custom-checkbox">
                                        <input class="bookly-js-required custom-control-input" type="checkbox"/>
                                        <label class="custom-control-label">
                                            <span class="d-none d-sm-inline"><?php esc_html_e( 'Required field', 'bookly' ) ?></span>
                                            <i class="d-sm-none fas fa-fw fa-asterisk"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <?php echo $services_html ?>
                    </div>
                </div>
                <?php echo $description_html ?>
            </div>
        </div>
        <hr class="mt-0"/>
    </li>

    <li data-type="checkboxes" class="bookly-js-custom-field">
        <div class="form-row">
            <div class="col-auto">
                <?php Elements::renderReorder( 'bookly-js-reorder-cf' ) ?>
            </div>
            <div class="col">
                <div class="mb-2">
                    <?php esc_html_e( 'Checkbox Group', 'bookly' ) ?><span class="bookly-js-replace-code text-muted"></span>
                    <a href="#" class="bookly-js-delete far fa-fw fa-trash-alt text-danger"
                       title="<?php esc_attr_e( 'Remove field', 'bookly' ) ?>"></a>
                </div>
                <div class="form-row">
                    <div class="col-md-8 mb-3">
                        <div class="input-group">
                            <input class="bookly-js-label form-control" type="text"
                                   placeholder="<?php esc_attr_e( 'Enter a label', 'bookly' ) ?>"/>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <div class="custom-control custom-checkbox">
                                        <input class="bookly-js-required custom-control-input" type="checkbox"/>
                                        <label class="custom-control-label">
                                            <span class="d-none d-sm-inline"><?php esc_html_e( 'Required field', 'bookly' ) ?></span>
                                            <i class="d-sm-none fas fa-fw fa-asterisk"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <ul class="bookly-js-items list-unstyled mt-2"></ul>
                        <button class="btn btn-sm btn-default mt-1" data-type="checkboxes-item">
                            <i class="fas fa-fw fa-plus mr-1"></i><?php esc_html_e( 'Checkbox', 'bookly' ) ?>
                        </button>
                    </div>
                    <div class="col-auto">
                        <?php echo $services_html ?>
                    </div>
                </div>
                <?php echo $description_html ?>
            </div>
        </div>
        <hr class="mt-0"/>
    </li>

    <li data-type="radio-buttons" class="bookly-js-custom-field">
        <div class="form-row">
            <div class="col-auto">
                <?php Elements::renderReorder( 'bookly-js-reorder-cf' ) ?>
            </div>
            <div class="col">
                <div class="mb-2">
                    <?php esc_html_e( 'Radio Button Group', 'bookly' ) ?><span class="bookly-js-replace-code text-muted"></span>
                    <a href="#" class="bookly-js-delete far fa-fw fa-trash-alt text-danger"
                       title="<?php esc_attr_e( 'Remove field', 'bookly' ) ?>"></a>
                </div>
                <div class="form-row">
                    <div class="col-md-8 mb-3">
                        <div class="input-group">
                            <input class="bookly-js-label form-control" type="text"
                                   placeholder="<?php esc_attr_e( 'Enter a label', 'bookly' ) ?>"/>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <div class="custom-control custom-checkbox">
                                        <input class="bookly-js-required custom-control-input" type="checkbox"/>
                                        <label class="custom-control-label">
                                            <span class="d-none d-sm-inline"><?php esc_html_e( 'Required field', 'bookly' ) ?></span>
                                            <i class="d-sm-none fas fa-fw fa-asterisk"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <ul class="bookly-js-items list-unstyled mt-2"></ul>
                        <button class="btn btn-sm btn-default mt-1" data-type="radio-buttons-item">
                            <i class="fas fa-fw fa-plus mr-1"></i><?php esc_html_e( 'Radio Button', 'bookly' ) ?>
                        </button>
                    </div>
                    <div class="col-auto">
                        <?php echo $services_html ?>
                    </div>
                </div>
                <?php echo $description_html ?>
            </div>
        </div>
        <hr class="mt-0"/>
    </li>

    <li data-type="drop-down" class="bookly-js-custom-field">
        <div class="form-row">
            <div class="col-auto">
                <?php Elements::renderReorder( 'bookly-js-reorder-cf' ) ?>
            </div>
            <div class="col">
                <div class="mb-2">
                    <?php esc_html_e( 'Drop Down', 'bookly' ) ?><span class="bookly-js-replace-code text-muted"></span>
                    <a href="#" class="bookly-js-delete far fa-fw fa-trash-alt text-danger"
                       title="<?php esc_attr_e( 'Remove field', 'bookly' ) ?>"></a>
                </div>
                <div class="form-row">
                    <div class="col-md-8 mb-3">
                        <div class="input-group">
                            <input class="bookly-js-label form-control" type="text"
                                   placeholder="<?php esc_attr_e( 'Enter a label', 'bookly' ) ?>"/>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <div class="custom-control custom-checkbox">
                                        <input class="bookly-js-required custom-control-input" type="checkbox"/>
                                        <label class="custom-control-label">
                                            <span class="d-none d-sm-inline"><?php esc_html_e( 'Required field', 'bookly' ) ?></span>
                                            <i class="d-sm-none fas fa-fw fa-asterisk"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <ul class="bookly-js-items list-unstyled mt-2"></ul>
                        <button class="btn btn-sm btn-default mt-1" data-type="drop-down-item">
                            <i class="fas fa-fw fa-plus mr-1"></i><?php esc_html_e( 'Option', 'bookly' ) ?>
                        </button>
                    </div>
                    <div class="col-auto">
                        <?php echo $services_html ?>
                    </div>
                </div>
                <?php echo $description_html ?>
            </div>
        </div>
        <hr class="mt-0"/>
    </li>
    <li data-type="number">
        <div class="form-row">
            <div class="col-auto">
                <?php Elements::renderReorder( 'bookly-js-reorder-cf' ) ?>
            </div>
            <div class="col">
                <div class="mb-2">
                    <?php esc_html_e( 'Numeric Field', 'bookly' ) ?><span class="bookly-js-replace-code text-muted"></span>
                    <a href="#" class="bookly-js-delete far fa-fw fa-trash-alt text-danger"
                       title="<?php esc_attr_e( 'Remove field', 'bookly' ) ?>"></a>
                </div>
                <div class="form-row">
                    <div class="col-md-8 mb-3">
                        <div class="input-group">
                            <input class="bookly-js-label form-control" type="text"
                                   placeholder="<?php esc_attr_e( 'Enter a label', 'bookly' ) ?>"/>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <div class="custom-control custom-checkbox">
                                        <input class="bookly-js-required custom-control-input" type="checkbox"/>
                                        <label class="custom-control-label">
                                            <span class="d-none d-sm-inline"><?php esc_html_e( 'Required field', 'bookly' ) ?></span>
                                            <i class="d-sm-none fas fa-fw fa-asterisk"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <?php echo $services_html ?>
                    </div>
                </div>
                <?php echo $description_html ?>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input class="bookly-js-use-limits custom-control-input" type="checkbox"/>
                        <label class="custom-control-label">
                            <span class="d-none d-sm-inline"><?php esc_html_e( 'Use limit values', 'bookly' ) ?></span>
                        </label>
                    </div>
                </div>
                <div class="form-row bookly-js-limits">
                    <div class="col-md-8 mb-3">
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-6">
                                    <input class="form-control bookly-js-min-value" type="number" step="1" name="min_value" placeholder="<?php esc_attr_e( 'Min value', 'bookly' ) ?>"/>
                                </div>
                                <div class="col-6">
                                    <input class="form-control bookly-js-max-value" type="number" step="1" name="max_value" placeholder="<?php esc_attr_e( 'Max value', 'bookly' ) ?>"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr class="mt-0"/>
    </li>
    <li data-type="time">
        <div class="form-row">
            <div class="col-auto">
                <?php Elements::renderReorder( 'bookly-js-reorder-cf' ) ?>
            </div>
            <div class="col">
                <div class="mb-2">
                    <?php esc_html_e( 'Time Field', 'bookly' ) ?><span class="bookly-js-replace-code text-muted"></span>
                    <a href="#" class="bookly-js-delete far fa-fw fa-trash-alt text-danger"
                       title="<?php esc_attr_e( 'Remove field', 'bookly' ) ?>"></a>
                </div>
                <div class="form-row">
                    <div class="col-md-8 mb-3">
                        <div class="input-group">
                            <input class="bookly-js-label form-control" type="text"
                                   placeholder="<?php esc_attr_e( 'Enter a label', 'bookly' ) ?>"/>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <div class="custom-control custom-checkbox">
                                        <input class="bookly-js-required custom-control-input" type="checkbox"/>
                                        <label class="custom-control-label">
                                            <span class="d-none d-sm-inline"><?php esc_html_e( 'Required field', 'bookly' ) ?></span>
                                            <i class="d-sm-none fas fa-fw fa-asterisk"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <?php echo $services_html ?>
                    </div>
                </div>
                <?php echo $description_html ?>
                <div class="form-row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <input class="bookly-js-delimiter form-control" type="number" step="1" min="1" placeholder="<?php esc_attr_e( 'Delimiter, minutes', 'bookly' ) ?>"/>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input class="bookly-js-use-limits custom-control-input" type="checkbox"/>
                        <label class="custom-control-label">
                            <span class="d-none d-sm-inline"><?php esc_html_e( 'Use limit values', 'bookly' ) ?></span>
                        </label>
                    </div>
                </div>
                <div class="form-row bookly-js-limits">
                    <div class="col-md-8 mb-3">
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-6">
                                    <input class="form-control bookly-js-min-value" type="time" name="min_value" placeholder="<?php esc_attr_e( 'Min value', 'bookly' ) ?>"/>
                                </div>
                                <div class="col-6">
                                    <input class="form-control bookly-js-max-value" type="time" name="max_value" placeholder="<?php esc_attr_e( 'Max value', 'bookly' ) ?>"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr class="mt-0"/>
    </li>
    <li data-type="date">
        <div class="form-row">
            <div class="col-auto">
                <?php Elements::renderReorder( 'bookly-js-reorder-cf' ) ?>
            </div>
            <div class="col">
                <div class="mb-2">
                    <?php esc_html_e( 'Date Field', 'bookly' ) ?><span class="bookly-js-replace-code text-muted"></span>
                    <a href="#" class="bookly-js-delete far fa-fw fa-trash-alt text-danger"
                       title="<?php esc_attr_e( 'Remove field', 'bookly' ) ?>"></a>
                </div>
                <div class="form-row">
                    <div class="col-md-8 mb-3">
                        <div class="input-group">
                            <input class="bookly-js-label form-control" type="text"
                                   placeholder="<?php esc_attr_e( 'Enter a label', 'bookly' ) ?>"/>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <div class="custom-control custom-checkbox">
                                        <input class="bookly-js-required custom-control-input" type="checkbox"/>
                                        <label class="custom-control-label">
                                            <span class="d-none d-sm-inline"><?php esc_html_e( 'Required field', 'bookly' ) ?></span>
                                            <i class="d-sm-none fas fa-fw fa-asterisk"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <?php echo $services_html ?>
                    </div>
                </div>
                <?php echo $description_html ?>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input class="bookly-js-use-limits custom-control-input" type="checkbox"/>
                        <label class="custom-control-label">
                            <span class="d-none d-sm-inline"><?php esc_html_e( 'Use limit values', 'bookly' ) ?></span>
                        </label>
                    </div>
                </div>
                <div class="form-row bookly-js-limits">
                    <div class="col-md-8 mb-3">
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-6">
                                    <input class="form-control bookly-js-min-value bookly-js-date" type="text" name="min_value" placeholder="<?php esc_attr_e( 'Min value', 'bookly' ) ?>"/>
                                </div>
                                <div class="col-6">
                                    <input class="form-control bookly-js-max-value bookly-js-date" type="text" name="max_value" placeholder="<?php esc_attr_e( 'Max value', 'bookly' ) ?>"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr class="mt-0"/>
    </li>
    <li data-type="captcha">
        <div class="form-row">
            <div class="col-auto">
                <?php Elements::renderReorder( 'bookly-js-reorder-cf' ) ?>
            </div>
            <div class="col">
                <div class="mb-2">
                    <?php esc_html_e( 'Captcha', 'bookly' ) ?>
                    <a href="#" class="bookly-js-delete far fa-fw fa-trash-alt text-danger"
                       title="<?php esc_attr_e( 'Remove field', 'bookly' ) ?>"></a>
                </div>
                <div class="form-row">
                    <div class="col-md-8 mb-3">
                        <div class="input-group">
                            <input class="bookly-js-label form-control" type="text"
                                   placeholder="<?php esc_attr_e( 'Enter a label', 'bookly' ) ?>"/>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <div class="custom-control custom-checkbox">
                                        <input class="bookly-js-required custom-control-input" type="checkbox"/>
                                        <label class="custom-control-label">
                                            <span class="d-none d-sm-inline"><?php esc_html_e( 'Required field', 'bookly' ) ?></span>
                                            <i class="d-sm-none fas fa-fw fa-asterisk"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <?php echo $services_html ?>
                    </div>
                </div>
                <?php echo $description_html ?>
            </div>
        </div>
        <hr class="mt-0"/>
    </li>

    <li data-type="checkboxes-item">
        <div class="form-row align-items-center mb-2">
            <div class="col-auto">
                <?php Elements::renderReorder( 'bookly-js-reorder-cf-item' ) ?>
            </div>
            <div class="col">
                <div class="input-group">
                    <input class="form-control" type="text" placeholder="<?php esc_attr_e( 'Enter a label', 'bookly' ) ?>">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <div class="custom-control custom-checkbox">
                                <input class="bookly-js-default custom-control-input" type="checkbox">
                                <label class="custom-control-label">
                                    <span><?php esc_html_e( 'Default', 'bookly' ) ?></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <a href="#" class="bookly-js-delete far fa-fw fa-trash-alt text-danger"
                   title="<?php esc_attr_e( 'Remove item', 'bookly' ) ?>"></a>
            </div>
        </div>
    </li>

    <li data-type="radio-buttons-item">
        <div class="form-row align-items-center mb-2">
            <div class="col-auto">
                <?php Elements::renderReorder( 'bookly-js-reorder-cf-item' ) ?>
            </div>
            <div class="col">
                <div class="input-group">
                    <input class="form-control" type="text" placeholder="<?php esc_attr_e( 'Enter a label', 'bookly' ) ?>">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <div class="custom-control custom-radio">
                                <input class="bookly-js-default custom-control-input" type="radio">
                                <label class="custom-control-label">
                                    <span><?php esc_html_e( 'Default', 'bookly' ) ?></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <a href="#" class="bookly-js-delete far fa-fw fa-trash-alt text-danger"
                   title="<?php esc_attr_e( 'Remove item', 'bookly' ) ?>"></a>
            </div>
        </div>
    </li>

    <li data-type="drop-down-item">
        <div class="form-row align-items-center mb-2">
            <div class="col-auto">
                <?php Elements::renderReorder( 'bookly-js-reorder-cf-item' ) ?>
            </div>
            <div class="col">
                <div class="input-group">
                    <input class="form-control" type="text" placeholder="<?php esc_attr_e( 'Enter a label', 'bookly' ) ?>">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <div class="custom-control custom-radio">
                                <input class="bookly-js-default custom-control-input" type="radio">
                                <label class="custom-control-label">
                                    <span><?php esc_html_e( 'Default', 'bookly' ) ?></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <a href="#" class="bookly-js-delete far fa-fw fa-trash-alt text-danger"
                   title="<?php esc_attr_e( 'Remove item', 'bookly' ) ?>"></a>
            </div>
        </div>
    </li>

    <?php Bookly\Lib\Proxy\Files::renderCustomFieldTemplate( $services_html, $description_html ) ?>
</ul>