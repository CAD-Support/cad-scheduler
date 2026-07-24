<?php
namespace BooklyCart\Frontend\Modules\Booking\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Frontend\Modules\Booking\Proxy;

class Local extends Proxy\Cart
{
    /**
     * @inheritDoc
     */
    public static function getStepHtml( BooklyLib\UserBookingData $userData, $progress_tracker, $info_text, $show_back_btn = true )
    {
        $table = array(
            'headers' => array(),
            'header_position' => array(),
            'rows' => array(),
            'show' => array(
                'deposit' => false,
                'tax' => false,
            ),
        );
        $cart_columns = get_option( 'bookly_cart_show_columns', array() );

        foreach ( $userData->cart->getItems() as $cart_key => $cart_item ) {
            if ( BooklyLib\Proxy\RecurringAppointments::hideChildAppointments( false, $cart_item ) ) {
                continue;
            }
            $nop_prefix = ( $cart_item->getNumberOfPersons() > 1 ? '<i class="bookly-icon-user"></i>' . $cart_item->getNumberOfPersons() . ' &times; ' : '' );
            $slots = $cart_item->getSlots();
            $service_dp = $slots[0][2] !== null
                ? BooklyLib\Slots\DatePoint::fromStr( $slots[0][2] )->toClientTz()
                : null;

            foreach ( $cart_columns as $header => $attr ) {
                if ( $attr['show'] ) {
                    switch ( $header ) {
                        case 'service':
                            $table['rows'][ $cart_key ][] = $cart_item->getService()->getTranslatedTitle();
                            break;
                        case 'date':
                            $table['rows'][ $cart_key ][] = $service_dp ? $service_dp->formatI18nDate() : __( 'N/A', 'bookly' );
                            break;
                        case 'time':
                            if ( $service_dp ) {
                                if ( $cart_item->getService()->getDuration() * $cart_item->getUnits() < DAY_IN_SECONDS ) {
                                    $table['rows'][ $cart_key ][] = $service_dp->formatI18nTime();
                                } else {
                                    $table['rows'][ $cart_key ][] = $cart_item->getService()->getStartTimeInfo();
                                }
                            } else {
                                $table['rows'][ $cart_key ][] = __( 'N/A', 'bookly' );
                            }
                            break;
                        case 'employee':
                            $table['rows'][ $cart_key ][] = $cart_item->getStaff()->getTranslatedName();
                            break;
                        case 'price':
                            if ( $cart_item->getNumberOfPersons() > 1 ) {
                                $price = $nop_prefix . BooklyLib\Utils\Price::format( $cart_item->getServicePriceWithoutExtras() ) . ' = ' . BooklyLib\Utils\Price::format( $cart_item->getServicePriceWithoutExtras() * $cart_item->getNumberOfPersons() );
                            } else {
                                $price = BooklyLib\Utils\Price::format( $cart_item->getServicePriceWithoutExtras() );
                            }
                            if ( $cart_item->toBePutOnWaitingList() ) {
                                $price = '(' . $price . ')';
                            }
                            $table['rows'][ $cart_key ][] = $price;
                            break;
                        case 'deposit':
                            if ( BooklyLib\Config::depositPaymentsActive() ) {
                                $deposit = BooklyLib\Proxy\DepositPayments::formatDeposit( $cart_item->getDepositPrice(), $cart_item->getDeposit() );
                                if ( $cart_item->toBePutOnWaitingList() ) {
                                    $deposit = '(' . $deposit . ')';
                                }
                                $table['rows'][ $cart_key ][] = $deposit;
                                $table['show']['deposit'] = true;
                            }
                            break;
                        case 'tax':
                            if ( BooklyLib\Config::taxesActive() ) {
                                $tax = '';
                                if ( ! $cart_item->toBePutOnWaitingList() ) {
                                    $tax = BooklyLib\Utils\Price::format( BooklyLib\Proxy\Taxes::getServiceTaxAmount( $cart_item ) );
                                }
                                $table['rows'][ $cart_key ][] = $tax;
                                $table['show']['tax'] = true;
                            }
                            break;
                    }
                }
            }
        }

        $position = 0;
        foreach ( $cart_columns as $header => $attr ) {
            if ( $attr['show'] ) {
                if ( $header != 'deposit' || $table['show']['deposit'] ) {
                    $table['header_position'][ $header ] = $position;
                }
                switch ( $header ) {
                    case 'service':
                        $table['headers'][] = BooklyLib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_service' );
                        ++ $position;
                        break;
                    case 'date':
                        $table['headers'][] = __( 'Date', 'bookly' );
                        ++ $position;
                        break;
                    case 'time':
                        $table['headers'][] = __( 'Time', 'bookly' );
                        ++ $position;
                        break;
                    case 'employee':
                        $table['headers'][] = BooklyLib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_employee' );
                        ++ $position;
                        break;
                    case 'price':
                        $table['headers'][] = __( 'Price', 'bookly' );
                        ++ $position;
                        break;
                    case 'deposit':
                        if ( $table['show']['deposit'] ) {
                            $table['headers'][] = _x( 'Deposit', 'portion of the payment', 'bookly' );
                            ++ $position;
                        }
                        break;
                    case 'tax':
                        if ( $table['show']['tax'] ) {
                            $table['headers'][] = __( 'Tax', 'bookly' );
                            ++ $position;
                        }
                        break;
                }
            }
        }
        $cart_info = $userData->cart->getInfo( null, false ); // without coupon

        return self::renderTemplate( '5_cart', compact( 'progress_tracker', 'info_text', 'userData', 'table', 'cart_info', 'show_back_btn' ), false );
    }

    /**
     * @inheritDoc
     */
    public static function renderButton()
    {
        self::renderTemplate( 'button' );
    }
}