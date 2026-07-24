<?php
namespace BooklyPro\Backend\Modules\Services\ProxyProviders;

use Bookly\Backend\Components\Controls\Buttons;
use Bookly\Backend\Modules\Services\Proxy;
use BooklyPro\Lib;
use BooklyPro\Backend\Components;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function duplicateService( $source_id, $target_id )
    {
        foreach ( Lib\Entities\StaffPreferenceOrder::query()->where( 'service_id', $source_id )->fetchArray() as $record ) {
            $new_record = new Lib\Entities\StaffPreferenceOrder( $record );
            $new_record->setId( null )->setServiceId( $target_id )->save();
        }
    }

    public static function renderAddOnsComponents()
    {
        Components\Dialogs\Service\Tags\Dialog::render();
    }

    public static function renderTopButtons()
    {
        echo '<div class="col-12 col-sm-auto">';
        Buttons::renderDefault( 'bookly-services-tags-button', 'w-100 mb-3', __( 'Tags', 'bookly' ), array( 'data-toggle' => 'bookly-modal', 'data-target' => '#bookly-service-tags-modal', 'disabled' => 'disabled' ), true );
        echo '</div>';
    }
}