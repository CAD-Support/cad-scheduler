<?php
namespace BooklyDepositPayments\Backend\Components\Dialogs\Staff\Edit\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Backend\Components\Dialogs\Staff\Edit\Proxy;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function renderStaffServiceLabels()
    {
        self::renderTemplate( 'staff_service_label' );
    }

    /**
     * @inheritDoc
     */
    public static function renderStaffService( $staff_id, BooklyLib\Entities\Service $service, array $services_data, $attributes = array() )
    {
        $read_only = false;
        if ( ( $service->getType() == BooklyLib\Entities\Service::TYPE_PACKAGE )
            || ( isset( $attributes[ 'read-only' ][ 'deposit' ] ) && $attributes[ 'read-only' ][ 'deposit' ] ) ) {
            $read_only = true;
        }

        $value = array_key_exists( $service->getId(), $services_data ) ? $services_data[ $service->getId() ][ 'deposit' ] : '100%';
        $attribute = !array_key_exists( $service->getId(), $services_data ) ? 'disabled' : '';

        self::renderTemplate( 'staff_service', compact( 'attribute', 'service', 'value', 'read_only' ) );
    }
}