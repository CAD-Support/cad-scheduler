<?php
namespace BooklyPro\Backend\Components\Dialogs\Service\Tags;

use Bookly\Lib;

class Dialog extends Lib\Base\Component
{
    /**
     * Render create service dialog.
     */
    public static function render()
    {
        self::enqueueStyles( array(
            'backend' => array( 'css/fontawesome-all.min.css' => array( 'bookly-backend-globals' ), ),
        ) );

        self::enqueueScripts( array(
            'module' => array( 'js/service-tags-dialog.js' => array( 'bookly-backend-globals' ) ),
        ) );

        self::renderTemplate( 'dialog' );
    }
}