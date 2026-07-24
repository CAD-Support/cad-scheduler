<?php
namespace BooklyDiscounts\Backend\Components\Dialogs\Discount;

use Bookly\Lib as BooklyLib;
use BooklyDiscounts\Lib;

class EditAjax extends BooklyLib\Base\Ajax
{
    /**
     * Add new discount.
     */
    public static function saveDiscount()
    {
        $discount = new Lib\Entities\Discount( self::parameters() );
        $discount->save();

        // Services.
        $service_ids = self::parameter( 'service_ids', array() );
        if ( empty ( $service_ids ) || $discount->getType() === Lib\Entities\Discount::TYPE_APPOINTMENTS ) {
            Lib\Entities\ServiceDiscount::query()
                ->delete()
                ->where( 'discount_id', $discount->getId() )
                ->execute();
        } else {
            Lib\Entities\ServiceDiscount::query()
                ->delete()
                ->where( 'discount_id', $discount->getId() )
                ->whereNotIn( 'service_id', $service_ids )
                ->execute();
            $new_services = Lib\Entities\ServiceDiscount::query()
                ->where( 'discount_id', $discount->getId() )
                ->fetchColDiff( 'service_id', $service_ids );
            foreach ( $new_services as $service_id ) {
                $discount_service = new Lib\Entities\ServiceDiscount();
                $discount_service
                    ->setDiscountId( $discount->getId() )
                    ->setServiceId( $service_id )
                    ->save();
            }
        }

        wp_send_json_success();
    }

    public static function getDiscountLists()
    {
        wp_send_json_success( array(
            'service_id' => Lib\Entities\ServiceDiscount::query()->where( 'discount_id', self::parameter( 'discount_id' ) )->fetchCol( 'service_id' ),
        ) );
    }
}