<?php
namespace BooklyCustomerInformation\Backend\Components\Dialogs\Customer\ProxyProviders;

use Bookly\Backend\Components\Dialogs\Customer\Edit\Proxy;
use BooklyCustomerInformation\Lib;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareL10n( $localize )
    {
        $localize['infoFields'] = Lib\ProxyProviders\Local::getFieldsWhichMayHaveData();

        return $localize;
    }

    /**
     * @inheritDoc
     */
    public static function prepareSaveCustomer( array $response, $request, $customer )
    {
        // Get data indexed by ID.
        $fields_data = array();
        foreach ( $request->get( 'info_fields' ) ?: array() as $field_data ) {
            $fields_data[ $field_data['id'] ] = $field_data;
        }

        $customer->setInfoFields( json_encode( Lib\Utils\Common::prepareInfoFields( $fields_data ) ) );

        return $response;
    }
}