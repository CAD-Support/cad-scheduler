<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use BooklyCustomFields\Lib\Plugin;
use Bookly\Frontend\Modules\Booking\Proxy;
use Bookly\Lib\Utils\Common;
use Bookly\Lib\Utils;

/** @var array $cf_data */
/** @var array $conditional_fields */
/** @var bool $show_service_title */
/** @var string $captcha_url */
/** @var string $form_id */
?>

<?php foreach ( $cf_data as $key => $cf_item ) : ?>
    <div class="bookly-custom-fields-container" data-key="<?php echo $key ?>">
        <?php if ( $show_service_title && ! empty ( $cf_item['custom_fields'] ) ) : ?>
            <div class="bookly-box"><b><?php echo $cf_item['service_title'] ?></b></div>
        <?php endif ?>
        <?php foreach ( $cf_item['custom_fields'] as $position => $custom_field ) : ?>
            <div class="bookly-box bookly-custom-field-row bookly-overflow-visible" data-id="<?php echo $custom_field->id ?>" data-type="<?php echo $custom_field->type ?>"<?php if ( in_array( $custom_field->id, $conditional_fields ) ) : ?> style="display: none;"<?php endif ?>>
                <div class="bookly-form-group">
                    <?php if ( $custom_field->type != 'text-content' ) : ?>
                        <label for="bookly-cf-<?php echo $position ?>-<?php echo $form_id ?>"><?php echo Common::stripScripts( $custom_field->label ) ?></label>
                    <?php endif ?>
                    <div>
                        <?php if ( $custom_field->type == 'text-field' ) : ?>
                            <input id="bookly-cf-<?php echo $position ?>-<?php echo $form_id ?>" type="text" class="bookly-js-custom-field" value="<?php echo esc_attr( isset( $cf_item['data'][ $custom_field->id ] ) ? $cf_item['data'][ $custom_field->id ] : '' ) ?>"/>
                        <?php elseif ( $custom_field->type == 'number' ) : ?>
                            <input id="bookly-cf-<?php echo $position ?>-<?php echo $form_id ?>" type="number" min="<?php echo $custom_field->limits ? $custom_field->min : '' ?>" max="<?php echo $custom_field->limits ? $custom_field->max : '' ?>" class="bookly-js-custom-field" value="<?php echo esc_attr( isset( $cf_item['data'][ $custom_field->id ] ) ? $cf_item['data'][ $custom_field->id ] : '' ) ?>"/>
                        <?php elseif ( $custom_field->type == 'textarea' ) : ?>
                            <textarea id="bookly-cf-<?php echo $position ?>-<?php echo $form_id ?>" rows="3" class="bookly-js-custom-field"><?php echo esc_html( isset( $cf_item['data'][ $custom_field->id ] ) ? $cf_item['data'][ $custom_field->id ] : '' ) ?></textarea>
                        <?php elseif ( $custom_field->type == 'text-content' ) : ?>
                            <?php echo nl2br( $custom_field->label ) ?>
                        <?php elseif ( $custom_field->type == 'checkboxes' ) : ?>
                            <?php foreach ( $custom_field->items as $i => $item ) : ?>
                                <label for="bookly-cf-ch-<?php echo $position ?>-<?php echo $form_id ?>-<?php echo $i ?>">
                                    <input type="checkbox" class="bookly-js-custom-field"
                                           value="<?php echo esc_attr( $item['value'] ) ?>" <?php checked( ( ! isset( $cf_item['data'][ $custom_field->id ] ) && property_exists( $custom_field, 'default' ) && in_array( $item['value'], $custom_field->default ) ) || ( isset( $cf_item['data'][ $custom_field->id ] ) && is_array( $cf_item['data'][ $custom_field->id ] ) && in_array( $item['value'], $cf_item['data'][ $custom_field->id ] ) ), true, true ) ?>
                                           id="bookly-cf-ch-<?php echo $position ?>-<?php echo $form_id ?>-<?php echo $i ?>"
                                    />
                                    <span><?php echo Common::stripScripts( $item['label'] ) ?></span>
                                </label><br/>
                            <?php endforeach ?>
                        <?php elseif ( $custom_field->type == 'radio-buttons' ) : ?>
                            <?php foreach ( $custom_field->items as $i => $item ) : ?>
                                <label for="bookly-cf-ra-<?php echo $position ?>-<?php echo $form_id ?>-<?php echo $i ?>">
                                    <input type="radio" class="bookly-js-custom-field" name="bookly-js-custom-field-<?php echo $custom_field->id ?>-<?php echo $key ?>"
                                           value="<?php echo esc_attr( $item['value'] ) ?>" <?php checked( isset( $cf_item['data'][ $custom_field->id ] ) ? $item['value'] === $cf_item['data'][ $custom_field->id ] : property_exists( $custom_field, 'default' ) && $item['value'] === $custom_field->default ) ?>
                                           id="bookly-cf-ra-<?php echo $position ?>-<?php echo $form_id ?>-<?php echo $i ?>"
                                    />
                                    <span><?php echo Common::stripScripts( $item['label'] ) ?></span>
                                </label><br/>
                            <?php endforeach ?>
                        <?php elseif ( $custom_field->type == 'drop-down' ) : ?>
                            <select id="bookly-cf-<?php echo $position ?>-<?php echo $form_id ?>" class="bookly-js-custom-field">
                                <option value=""></option>
                                <?php foreach ( $custom_field->items as $item ) : ?>
                                    <option value="<?php echo esc_attr( $item['value'] ) ?>" <?php selected( isset( $cf_item['data'][ $custom_field->id ] ) ? $item['value'] === $cf_item['data'][ $custom_field->id ] : property_exists( $custom_field, 'default' ) && $item['value'] === $custom_field->default ) ?>><?php echo esc_html( $item['label'] ) ?></option>
                                <?php endforeach ?>
                            </select>
                        <?php elseif ( $custom_field->type == 'date' ) : ?>
                            <div class="bookly-js-datepicker-calendar-wrap bookly:relative" data-id="bookly-cf-<?php echo $position ?>-<?php echo $form_id ?>">
                                <input id="bookly-cf-<?php echo $position ?>-<?php echo $form_id ?>" class="bookly-date-from bookly-js-cf-date bookly-js-custom-field" type="text" value="" data-value="<?php echo esc_attr( isset( $cf_item['data'][ $custom_field->id ] ) ? $cf_item['data'][ $custom_field->id ] : '' ) ?>" data-min="<?php echo $custom_field->limits ? $custom_field->min : '' ?>" data-max="<?php echo $custom_field->limits ? $custom_field->max : '' ?>" readonly/>
                                <span class="bookly:absolute bookly:inset-y-0 bookly:rtl:left-0 bookly:ltr:right-0  bookly:w-10 bookly:flex bookly:items-center bookly:justify-center bookly:cursor-pointer bookly:text-3xl bookly:text-gray-400 bookly:hover:text-gray-600">&times;</span>
                                <div class="bookly:relative bookly:w-full bookly:z-10 bookly-js-datepicker-container">
                                    <div class="bookly:absolute bookly:top-1 bookly:w-72 bookly:p-0 bookly:bg-white bookly-js-datepicker-calendar bookly:min-w-[200px]"></div>
                                </div>
                            </div>
                        <?php elseif ( $custom_field->type == 'time' ) : ?>
                            <?php
                            $start_time = $custom_field->limits ? $custom_field->min : '00:00';
                            $end_time = $custom_field->limits ? $custom_field->max : '23:59';
                            $start_time = date( 'i', strtotime( $start_time ) ) * 1 + date( 'H', strtotime( $start_time ) ) * 60;
                            $end_time = date( 'i', strtotime( $end_time ) ) * 1 + date( 'H', strtotime( $end_time ) ) * 60;
                            $items = array();
                            while ( $start_time <= $end_time ) {
                                $items[] = date( 'H:i', $start_time * 60 );
                                $start_time += $custom_field->delimiter ?: 60;
                            }
                            ?>
                            <select id="bookly-cf-<?php echo $position ?>-<?php echo $form_id ?>" class="bookly-js-custom-field">
                                <option value=""></option>
                                <?php foreach ( $items as $item ) : ?>
                                    <option value="<?php echo esc_attr( $item ) ?>" <?php selected( $item, isset( $cf_item['data'][ $custom_field->id ] ) ? $cf_item['data'][ $custom_field->id ] : null, true ) ?>><?php echo Utils\DateTime::formatTime( $item ) ?></option>
                                <?php endforeach ?>
                            </select>
                        <?php elseif ( $custom_field->type == 'captcha' ) : ?>
                            <img class="bookly-js-captcha-img" src="<?php echo esc_url( $captcha_url ) ?>" alt="<?php esc_attr_e( 'Captcha', 'bookly' ) ?>" height="75" width="160" style="width:160px;height:75px;"/>
                            <img class="bookly-js-captcha-refresh" width="16" height="16" title="<?php esc_attr_e( 'Another code', 'bookly' ) ?>" alt="<?php esc_attr_e( 'Another code', 'bookly' ) ?>"
                                 src="<?php echo plugins_url( 'frontend/resources/images/refresh.png', Plugin::getMainFile() ) ?>" style="cursor: pointer"/>
                            <input type="text" class="bookly-js-custom-field bookly-captcha" value="<?php echo esc_attr( isset( $cf_item['data'][ $custom_field->id ] ) ? $cf_item['data'][ $custom_field->id ] : '' ) ?>"/>
                        <?php endif ?>
                        <?php Proxy\Files::renderField( $custom_field, $cf_item['data'], 'custom-field' ) ?>
                    </div>
                    <?php if ( $custom_field->type != 'text-content' ) : ?>
                        <div class="bookly-label-error bookly-custom-field-error"></div>
                    <?php endif ?>
                    <?php if ( $custom_field->description !== '' ) : ?>
                        <div><?php echo Common::stripScripts( $custom_field->description ) ?></div>
                    <?php endif ?>
                </div>
            </div>
        <?php endforeach ?>
    </div>
<?php endforeach ?>