<?php
namespace BooklyCustomerInformation\Backend\Modules\CustomerInformation;

use Bookly\Lib as BooklyLib;
use BooklyCustomerInformation\Lib;

class Page extends BooklyLib\Base\Component
{
    /**
     *  Render page.
     */
    public static function render()
    {
        self::enqueueStyles( array(
            'bookly' => array( 'backend/resources/css/fontawesome-all.min.css' => array ( 'bookly-backend-globals' ) ),
        ) );

        self::enqueueScripts( array(
            'bookly' => array( 'backend/resources/js/sortable.min.js' => array( 'bookly-backend-globals' ), ),
            'module' => array( 'js/customer-information.js' => array( 'bookly-sortable.min.js' ) ),
        ) );

        wp_localize_script( 'bookly-customer-information.js', 'BooklyCustomerInformationL10n', array(
            'fields' => Lib\ProxyProviders\Local::getFields(),
            'visible' => __( 'Field will be displayed in booking form', 'bookly' ),
            'backendOnly' => __( 'Field will not be displayed in booking form', 'bookly' ),
            'saved' => __( 'Settings saved.', 'bookly' ),
        ) );

        self::renderTemplate( 'index' );
    }
}