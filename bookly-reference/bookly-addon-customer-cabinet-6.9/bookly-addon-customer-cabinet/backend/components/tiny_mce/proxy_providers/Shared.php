<?php
namespace BooklyCustomerCabinet\Backend\Components\TinyMce\ProxyProviders;

use Bookly\Backend\Components\TinyMce\Proxy;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function renderMediaButtons( $version )
    {
        if ( $version < 3.5 ) {
            // show button for v 3.4 and below
            echo '<a href="#TB_inline?width=640&inlineId=bookly-editor-customer-cabinet-popup&height=650" title="' . esc_attr__( 'Add Customer Cabinet', 'bookly' ) . '">' . __( 'Add Customer Cabinet', 'bookly' ) . '</a>';
        } else {
            // display button matching new UI
            $img = '<span class="bookly-media-icon"></span> ';
            echo '<a href="#TB_inline?width=640&inlineId=bookly-editor-customer-cabinet-popup&height=650" class="thickbox button bookly-media-button" title="' . esc_attr__( 'Add Customer Cabinet', 'bookly' ) . '">' . $img . __( 'Add Customer Cabinet', 'bookly' ) . '</a>';
        }
    }

    /**
     * @inheritDoc
     */
    public static function renderPopup()
    {
        self::renderTemplate( 'popup' );
    }
}