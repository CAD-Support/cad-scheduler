<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
/** @var array $custom_fields */
?>
<div class="mb-3"><small class="text-muted form-text"><?php esc_html_e( 'You can select only Checkbox Group, Radio Button Group or Dropdown in "If" statement.', 'bookly' ) ?></small></div>
<div id="bookly-custom-fields-conditions"></div>
<div>
    <button class="btn btn-success" id="bookly-add-condition"><i class="fa-fw fas fa-plus"></i><?php esc_html_e( 'Add condition', 'bookly' ) ?></button>
</div>
<div id="bookly-template" style="display:none">
    <div class="form-row align-items-center mb-3 bookly-js-custom-fields-condition">
        <div class="col-auto">Show</div>
        <div class="col">
            <select class="form-control bookly-js-custom-fields-target">
                <?php foreach ( $custom_fields as $custom_field ) : ?>
                    <option value="<?php echo $custom_field->id ?>"><?php echo esc_html( $custom_field->label ) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">if</div>
        <div class="col">
            <select class="form-control bookly-js-custom-fields-source">
                <?php foreach ( $custom_fields as $custom_field ) : ?>
                    <?php if ( property_exists( $custom_field, 'items' ) && count( $custom_field->items ) > 0 ) : ?>
                        <?php $first_custom_field = isset( $first_custom_field ) ? $first_custom_field : $custom_field ?>
                        <option value="<?php echo $custom_field->id ?>"><?php echo esc_html( $custom_field->label ) ?></option>
                    <?php endif ?>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col">
            <select class="form-control bookly-js-custom-fields-equal">
                <option value="1"><?php esc_html_e( 'in', 'bookly' ) ?></option>
                <option value="0"><?php esc_html_e( 'not in', 'bookly' ) ?></option>
            </select>
        </div>
        <div class="col bookly-js-custom-fields-values"></div>
        <div class="col-auto">
            <button class="btn btn-danger"><i class="fa-fw far fa-trash-alt"></i></button>
        </div>
    </div>
</div>