<?php
namespace BooklyEvents\Backend\Modules\Appearance\ProxyProviders;

use Bookly\Lib as BooklyLib;
use BooklyPro\Lib as BooklyProLib;
use BooklyPro\Backend\Modules\Appearance as BooklyProAppearance;
use Bookly\Backend\Modules\Appearance;

class Shared extends Appearance\Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareAppearances( $appearances )
    {
        $appearances[ BooklyProLib\Entities\Form::TYPE_EVENTS_FORM ] = array(
            'id' => BooklyProLib\Entities\Form::TYPE_EVENTS_FORM,
            'title' => __( 'Events form', 'bookly' ),
            'description' => __( 'Present events and sell tickets with a fast and clear form.', 'bookly' ),
            'img' => plugins_url( 'backend/modules/appearance/resources/images/appearance-events-form.png', BooklyProLib\Plugin::getMainFile() ),
            'appearance' => BooklyProAppearance\ProxyProviders\Local::getAppearance( BooklyProLib\Entities\Form::TYPE_EVENTS_FORM ),
            'url' => add_query_arg( array( 'page' => Appearance\Page::pageSlug() ), admin_url( 'admin.php' ) ) . '&' . BooklyProLib\Entities\Form::TYPE_EVENTS_FORM,
        );

        return $appearances;
    }
}