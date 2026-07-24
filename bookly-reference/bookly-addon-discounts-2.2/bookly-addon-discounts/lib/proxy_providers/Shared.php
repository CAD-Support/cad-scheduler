<?php
namespace BooklyDiscounts\Lib\ProxyProviders;

use Bookly\Lib as BooklyLib;
use BooklyDiscounts\Lib;

class Shared extends BooklyLib\Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function preparePaymentDetailsItem( $details, BooklyLib\DataHolders\Booking\Item $item )
    {
        $discounts = array();
        foreach ( Lib\Utils\Common::getServiceDiscounts( $item->getService()->getId(), $item->getCA()->getNumberOfPersons() ) as $discount ) {
            $discounts[] = array( 'title' => $discount->getTitle(), 'discount' => $discount->getDiscount(), 'deduction' => $discount->getDeduction() );
        }

        $details['discounts'] = $discounts;

        return $details;
    }

    /**
     * @inheritDoc
     */
    public static function preparePaymentDetails( $details )
    {
        $discounts = array();
        foreach ( Lib\Utils\Common::getCartDiscounts( $details->getCartInfo()->getUserData() ) as $discount ) {
            $discounts[] = array( 'title' => $discount->getTitle(), 'discount' => $discount->getDiscount(), 'deduction' => $discount->getDeduction() );
        }
        $details->setData( compact( 'discounts' ) );

        return $details;
    }

    /**
     * @inheritDoc
     */
    public static function prepareTableColumns( $columns, $table )
    {
        if ( $table === BooklyLib\Utils\Tables::DISCOUNTS ) {
            $columns = array_merge( $columns, array(
                'id' => esc_html__( 'ID', 'bookly' ),
                'title' => esc_html__( 'Title', 'bookly' ),
                'discount' => esc_html__( 'Discount (%)', 'bookly' ),
                'deduction' => esc_html__( 'Deduction', 'bookly' ),
                'condition' => esc_html__( 'Condition', 'bookly' ),
                'services' => esc_html__( 'Services', 'bookly' ),
                'date_start' => esc_html__( 'Active from', 'bookly' ),
                'date_end' => esc_html__( 'Active until', 'bookly' ),
                'enabled' => esc_html__( 'State', 'bookly' ),
            ) );
        }

        return $columns;
    }

    /**
     * @inheritDoc
     */
    public static function prepareTableDefaultSettings( $columns, $table )
    {
        if ( $table === BooklyLib\Utils\Tables::DISCOUNTS ) {
            $columns = array_merge( $columns, array(
                'id' => false,
            ) );
        }

        return $columns;
    }
}