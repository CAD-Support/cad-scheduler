<?php
namespace BooklyCoupons\Backend\Modules\Settings\ProxyProviders;

use Bookly\Backend\Modules\Settings\Proxy;
use Bookly\Backend\Components\Settings\Menu;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function renderMenuItem()
    {
        Menu::renderItem( __( 'Coupons', 'bookly' ), 'coupons' );
    }

    /**
     * @inheritDoc
     */
    public static function renderTab()
    {
        self::renderTemplate( 'settings_tab' );
    }

    /**
     * @inheritDoc
     */
    public static function prepareCodes( array $codes, $section )
    {
        switch ( $section ) {
            case 'calendar_one_participant':
            case 'ics_for_customer':
                $codes['coupon'] = array( 'description' => __( 'Coupon code', 'bookly' ) );
                break;
            case 'calendar_many_participants':
            case 'ics_for_staff':
                $codes = array_merge_recursive( $codes, array(
                    'participants' => array(
                        'loop' => array(
                            'codes' => array(
                                'coupon' => array( 'description' => __( 'Coupon code', 'bookly' ) )
                            ),
                        ),
                    ),
                ) );
                break;
            case 'google_calendar':
            case 'outlook_calendar':
            case 'apple_calendar':
                $codes = array_merge_recursive( $codes, array(
                    'coupon' => array( 'description' => __( 'Coupon code', 'bookly' ) ),
                    'participants' => array(
                        'loop' => array(
                            'item' => 'participant',
                            'codes' => array(
                                'coupon' => array( 'description' => __( 'Coupon code', 'bookly' ) )
                            ),
                        ),
                    ),
                ) );
                break;
        }

        return $codes;
    }

    /**
     * @inheritDoc
     */
    public static function saveSettings( array $alert, $tab, array $params )
    {
        if ( $tab == 'coupons' ) {
            $options = array( 'bookly_coupons_default_code_mask' );
            foreach ( $options as $option_name ) {
                if ( array_key_exists( $option_name, $params ) ) {
                    update_option( $option_name, $params[ $option_name ] );
                }
            }
            $alert['success'][] = __( 'Settings saved.', 'bookly' );
        }

        return $alert;
    }
}