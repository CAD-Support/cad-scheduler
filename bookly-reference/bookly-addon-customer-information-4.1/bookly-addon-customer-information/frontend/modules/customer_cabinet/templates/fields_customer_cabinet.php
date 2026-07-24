<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Controls\Inputs;
use Bookly\Lib\Utils;

/** @var \stdClass[] $field */
?>

<div class="form-group" data-id="customer_information_<?php echo esc_attr( $field->id ) ?>">
    <label for="info_field_<?php echo esc_attr( $field->id ) ?>"><?php echo $field->label ?></label>
    <div>
        <?php if ( $field->type == 'text-field' ) : ?>
            <input id="info_field_<?php echo esc_attr( $field->id ) ?>" name="info_fields[<?php echo esc_attr( $field->id ) ?>]" value="<?php echo esc_attr( $field->value ) ?>" type="text" class="form-control bookly-js-control-input" />

        <?php elseif ( $field->type == 'textarea' ) : ?>
            <textarea id="info_field_<?php echo esc_attr( $field->id ) ?>" name="info_fields[<?php echo esc_attr( $field->id ) ?>]" rows="3" class="form-control bookly-js-control-input"><?php echo esc_attr( $field->value ) ?></textarea>

        <?php elseif ( $field->type == 'text-content' ) : ?>
            <?php echo nl2br( $field->label ) ?>

        <?php elseif ( $field->type == 'checkboxes' ) : ?>
            <?php foreach ( $field->items as $i => $item ) : ?>
                <?php Inputs::renderCheckBox( $item, esc_attr( $item ), in_array( $item, $field->value ), array(
                    'name' => 'info_fields[' . esc_attr( $field->id ) . '][]',
                    'class' => ' bookly-js-control-input',
                ) ); ?>
            <?php endforeach ?>

        <?php elseif ( $field->type == 'radio-buttons' ) : ?>
            <?php foreach ( $field->items as $item ) : ?>
                <div class="radio">
                    <?php Inputs::renderRadio( $item, esc_attr( $item ), $field->value == $item, array( 'name' => 'info_fields[' . esc_attr( $field->id ) . ']', 'class' => ' bookly-js-control-input' ) ); ?>
                </div>
            <?php endforeach ?>

        <?php elseif ( $field->type == 'drop-down' ) : ?>
            <select id="info_field_<?php echo esc_attr( $field->id ) ?>" name="info_fields[<?php echo esc_attr( $field->id ) ?>]" class="form-control custom-select bookly-js-control-input">
                <option value=""></option>
                <?php foreach ( $field->items as $item ) : ?>
                    <option value="<?php echo esc_attr( $item ) ?>"<?php selected( $field->value == $item ) ?>><?php echo $item ?></option>
                <?php endforeach ?>
            </select>

        <?php elseif ( $field->type == 'number' ) : ?>
            <input id="info_field_<?php echo esc_attr( $field->id ) ?>" name="info_fields[<?php echo esc_attr( $field->id ) ?>]" type="number" min="<?php echo $field->limits ? $field->min : '' ?>" max="<?php echo $field->limits ? $field->max : '' ?>" class="form-control bookly-js-control-input" value="<?php echo esc_attr( $field->value ) ?>"/>
        
        <?php elseif ( $field->type == 'date' ) : ?>
            <input id="info_field_<?php echo esc_attr( $field->id ) ?>" name="info_fields[<?php echo esc_attr( $field->id ) ?>]" class="form-control bookly-js-control-input bookly-js-date" type="text" data-value="<?php echo esc_attr( $field->value ) ?>" data-min="<?php echo $field->limits ? $field->min : '' ?>" data-max="<?php echo $field->limits ? $field->max : '' ?>"/>
        
        <?php elseif ( $field->type == 'time' ) : ?>
            <?php
            $start_time = $field->limits ? $field->min : '00:00';
            $end_time = $field->limits ? $field->max : '23:59';
            $start_time = date( 'i', strtotime( $start_time ) ) * 1 + date( 'H', strtotime( $start_time ) ) * 60;
            $end_time = date( 'i', strtotime( $end_time ) ) * 1 + date( 'H', strtotime( $end_time ) ) * 60;
            $items = array();
            while ( $start_time <= $end_time ) {
                $items[] = date( 'H:i', $start_time * 60 );
                $start_time += $field->delimiter ?: 60;
            }
            ?>
            <select id="info_field_<?php echo esc_attr( $field->id ) ?>" name="info_fields[<?php echo esc_attr( $field->id ) ?>]" class="form-control bookly-js-control-input">
                <option value=""></option>
                <?php foreach ( $items as $item ) : ?>
                    <option value="<?php echo esc_attr( $item ) ?>" <?php selected( $field->value == $item ) ?>><?php echo Utils\DateTime::formatTime( $item ) ?></option>
                <?php endforeach ?>
            </select>

        <?php elseif ( $field->type == 'file' ) : ?>
            <div class="form-row bookly-js-uploaded" data-slug="<?php echo esc_attr( $field->value ) ?>">
                <div class="col-8"><?php echo esc_html( $data['name'] ) ?></div>
                <div class="ml-auto">
                    <button class="btn btn-default bookly-js-download"><?php esc_html_e( 'Download', 'bookly' ) ?></button>
                    <button class="btn btn-danger bookly-js-delete mr-1"><i class="far fa-trash-alt"></i></button>
                </div>
            </div>
            <div class="input-group mb-3 bookly-js-upload">
                <div class="custom-file">
                    <label class="custom-file-label" for="info_field_<?php echo esc_attr( $field->id ) ?>"></label>
                    <input type="file" class="custom-file-input" id="info_field_<?php echo esc_attr( $field->id ) ?>" data-id="<?php echo esc_attr( $field->id ) ?>">
                </div>
            </div>
            <input id="info_field_<?php echo esc_attr( $field->id ) ?>" name="info_fields[<?php echo esc_attr( $field->id ) ?>]" value="<?php echo esc_attr( $field->value ) ?>" type="hidden" class="form-control bookly-js-control-input" />
            <div class="bookly-js-error text-danger"></div>
        <?php endif ?>
    </div>
</div>
