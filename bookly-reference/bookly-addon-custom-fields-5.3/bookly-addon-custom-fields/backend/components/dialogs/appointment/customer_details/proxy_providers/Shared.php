<?php
namespace BooklyCustomFields\Backend\Components\Dialogs\Appointment\CustomerDetails\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Backend\Components\Dialogs\Appointment\CustomerDetails\Proxy;
use BooklyCustomFields\Lib;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareL10n( $localize )
    {
        $custom_fields = Lib\ProxyProviders\Local::getWhichHaveData();

        if ( ! BooklyLib\Config::filesActive() ) {
            $custom_fields = array_filter( $custom_fields, function ( $field ) {
                return $field->type != 'file';
            } );
        }

        $localize['customFields'] = array_values( $custom_fields );
        $localize['customFieldsPerService'] = get_option( 'bookly_custom_fields_per_service', 0 );
        $localize['l10n']['customFields'] = __( 'Custom fields', 'bookly' );
        $localize['moment_format_time'] = BooklyLib\Utils\DateTime::convertFormat( 'time', BooklyLib\Utils\DateTime::FORMAT_MOMENT_JS );
        $localize['datePicker'] = BooklyLib\Utils\DateTime::datePickerOptions();

        return $localize;
    }
}