<?php
namespace BooklyCustomerInformation\Frontend\Modules\CustomerCabinet;

use Bookly\Lib as BooklyLib;
use BooklyFiles\Lib\Entities\Files;

class Components extends BooklyLib\Base\Component
{
    /**
     * Render customer information field for customer cabinet
     *
     * @param \stdClass $field
     */
    public static function render( $field )
    {
        $data = array();
        if ( $field->type === 'file' && $field->value != '' ) {
            $data['name'] = Files::query()->where( 'slug', $field->value )->fetchVar( 'name' );
        }
        self::renderTemplate( 'fields_customer_cabinet', compact( 'field', 'data' ) );
    }
}