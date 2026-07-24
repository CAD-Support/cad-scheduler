<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Lib\Utils\Common;
use Bookly\Frontend\Modules\Booking\Proxy;
use Bookly\Lib\Utils;

?>
<div class="bookly-customer-information-container">
    <?php foreach ( $fields as $position => $field ) : ?>
        <div class="bookly-box bookly-js-info-field-row bookly-overflow-visible" data-id="<?php echo $field->id ?>" data-type="<?php echo $field->type ?>"<?php if ( $field->type != 'text-content' && $field->ask_once && isset( $data[ $field->id ] ) && $data[ $field->id ] !== '' && $data[ $field->id ] !== null && $data[ $field->id ] !== array() && $is_logged_in ) : ?> style="display: none;" <?php endif ?>>
            <div class="bookly-form-group">
                <?php if ( $field->type != 'text-content' ) : ?>
                    <label for="bookly-ci-<?php echo $position ?>-<?php echo $form_id ?>"><?php echo Common::stripScripts( $field->label ) ?></label>
                <?php endif ?>
                <div>
                    <?php if ( $field->type == 'text-field' ) : ?>
                        <input id="bookly-ci-<?php echo $position ?>-<?php echo $form_id ?>" type="text" class="bookly-js-info-field" value="<?php echo isset( $data[ $field->id ] ) ? esc_attr( $data[ $field->id ] ) : '' ?>"/>
                    <?php elseif ( $field->type == 'number' ) : ?>
                        <input id="bookly-ci-<?php echo $position ?>-<?php echo $form_id ?>" type="number" class="bookly-js-info-field" value="<?php echo esc_attr( isset( $data[ $field->id ] ) ? $data[ $field->id ] : '' ) ?>" min="<?php echo $field->limits ? $field->min : '' ?>" max="<?php echo $field->limits ? $field->max : '' ?>"/>
                    <?php elseif ( $field->type == 'textarea' ) : ?>
                        <textarea id="bookly-ci-<?php echo $position ?>-<?php echo $form_id ?>" rows="3" class="bookly-js-info-field"><?php echo isset( $data[ $field->id ] ) ? esc_html( $data[ $field->id ] ) : '' ?></textarea>
                    <?php elseif ( $field->type == 'text-content' ) : ?>
                        <?php echo nl2br( Common::stripScripts( $field->label ) ) ?>
                    <?php elseif ( $field->type == 'checkboxes' ) : ?>
                        <?php foreach ( $field->items as $i => $item ) : ?>
                            <label for="bookly-ci-ch-<?php echo $position ?>-<?php echo $form_id ?>-<?php echo $i ?>">
                                <input type="checkbox" class="bookly-js-info-field"
                                       value="<?php echo esc_attr( $item['value'] ) ?>" <?php checked( isset( $data[ $field->id ] ) && in_array( $item['value'], $data[ $field->id ] ) ) ?>
                                       id="bookly-ci-ch-<?php echo $position ?>-<?php echo $form_id ?>-<?php echo $i ?>"/>
                                <span><?php echo Common::stripScripts( $item['label'] ) ?></span>
                            </label><br/>
                        <?php endforeach ?>
                    <?php elseif ( $field->type == 'radio-buttons' ) : ?>
                        <?php foreach ( $field->items as $i => $item ) : ?>
                            <label for="bookly-ci-ra-<?php echo $position ?>-<?php echo $form_id ?>-<?php echo $i ?>">
                                <input type="radio" class="bookly-js-info-field" name="bookly-js-info-field-<?php echo $field->id ?>"
                                       value="<?php echo esc_attr( $item['value'] ) ?>" <?php checked( isset( $data[ $field->id ] ) && $item['value'] == $data[ $field->id ] ) ?>
                                       id="bookly-ci-ra-<?php echo $position ?>-<?php echo $form_id ?>-<?php echo $i ?>">
                                <span><?php echo Common::stripScripts( $item['label'] ) ?></span>
                            </label><br/>
                        <?php endforeach ?>
                    <?php elseif ( $field->type == 'drop-down' ) : ?>
                        <select id="bookly-ci-<?php echo $position ?>-<?php echo $form_id ?>" class="bookly-js-info-field">
                            <option value=""></option>
                            <?php foreach ( $field->items as $i => $item ) : ?>
                                <option value="<?php echo esc_attr( $item['value'] ) ?>" <?php selected( isset( $data[ $field->id ] ) && $item['value'] == $data[ $field->id ] ) ?>><?php echo esc_html( $item['label'] ) ?></option>
                            <?php endforeach ?>
                        </select>
                    <?php elseif ( $field->type == 'date' ) : ?>
                        <div class="bookly-js-datepicker-calendar-wrap bookly:relative" data-id="bookly-ci-<?php echo $position ?>-<?php echo $form_id ?>">
                            <input id="bookly-ci-<?php echo $position ?>-<?php echo $form_id ?>" class="bookly-date-from bookly-js-cf-date bookly-js-info-field" type="text" value="" data-value="<?php echo esc_attr( isset( $data[ $field->id ] ) ? $data[ $field->id ] : '' ) ?>" data-min="<?php echo $field->limits ? $field->min : '' ?>" data-max="<?php echo $field->limits ? $field->max : '' ?>" readonly/>
                            <span class="bookly:absolute bookly:inset-y-0 bookly:rtl:left-0 bookly:ltr:right-0  bookly:w-10 bookly:flex bookly:items-center bookly:justify-center bookly:cursor-pointer bookly:text-3xl bookly:text-gray-400 bookly:hover:text-gray-600">&times;</span>
                            <div class="bookly:relative bookly:w-full bookly:z-10 bookly-js-datepicker-container">
                                <div class="bookly:absolute bookly:top-1 bookly:w-72 bookly:p-0 bookly:bg-white bookly-js-datepicker-calendar bookly:min-w-[200px]"></div>
                            </div>
                        </div>
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
                        <select id="bookly-ci-<?php echo $position ?>-<?php echo $form_id ?>" class="bookly-js-info-field">
                            <option value=""></option>
                            <?php foreach ( $items as $item ) : ?>
                                <option value="<?php echo esc_attr( $item ) ?>" <?php selected( $item, isset( $data[ $field->id ] ) ? $data[ $field->id ] : null ) ?>><?php echo Utils\DateTime::formatTime( $item ) ?></option>
                            <?php endforeach ?>
                        </select>
                    <?php endif ?>
                    <?php Proxy\Files::renderField( $field, $data, 'info-field' ) ?>
                </div>
                <?php if ( $field->type != 'text-content' ) : ?>
                    <div class="bookly-label-error bookly-js-info-field-error"></div>
                <?php endif ?>
                <?php if ( $field->description !== '' ) : ?>
                    <div><?php echo Common::stripScripts( $field->description ) ?></div>
                <?php endif ?>
            </div>
        </div>
    <?php endforeach ?>
</div>