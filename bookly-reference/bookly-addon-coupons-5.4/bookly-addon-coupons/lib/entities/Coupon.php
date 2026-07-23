<?php
namespace BooklyCoupons\Lib\Entities;

use BooklyCoupons\Lib;
use Bookly\Lib as BooklyLib;

class Coupon extends BooklyLib\Base\Entity
{
    /** @var string */
    protected $code = '';
    /** @var float */
    protected $discount = 0;
    /** @var float */
    protected $deduction = 0;
    /** @var int */
    protected $usage_limit = 1;
    /** @var int */
    protected $used = 0;
    /** @var int */
    protected $once_per_customer = 0;
    /** @var string */
    protected $date_limit_start;
    /** @var string */
    protected $date_limit_end;
    /** @var int */
    protected $min_appointments = 1;
    /** @var int */
    protected $max_appointments;

    protected static $table = 'bookly_coupons';

    protected static $schema = array(
        'id' => array( 'format' => '%d' ),
        'code' => array( 'format' => '%s' ),
        'discount' => array( 'format' => '%f' ),
        'deduction' => array( 'format' => '%f' ),
        'usage_limit' => array( 'format' => '%d' ),
        'used' => array( 'format' => '%d' ),
        'once_per_customer' => array( 'format' => '%d' ),
        'date_limit_start' => array( 'format' => '%s' ),
        'date_limit_end' => array( 'format' => '%s' ),
        'min_appointments' => array( 'format' => '%d' ),
        'max_appointments' => array( 'format' => '%d' ),
    );

    /**
     * Apply coupon.
     *
     * @param $amount
     * @return float
     */
    public function apply( $amount )
    {
        return BooklyLib\Utils\Price::correction( $amount, $this->getDiscount(), $this->getDeduction() );
    }

    /**
     * Increase the number of times the coupon has been used.
     *
     * @param int $quantity
     * @return $this
     */
    public function claim( $quantity = 1 )
    {
        $this->setUsed( $this->getUsed() + $quantity );
        return $this;
    }

    /**
     * Check whether coupon is fully used.
     *
     * @return bool
     */
    public function fullyUsed()
    {
        return $this->used >= $this->usage_limit;
    }

    /**
     * Check if coupon has started.
     *
     * @return bool
     */
    public function started()
    {
        if ( $this->date_limit_start ) {
            $today = BooklyLib\Slots\DatePoint::now()->format( 'Y-m-d' );
            if ( $today < $this->date_limit_start ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if coupon is expired.
     *
     * @return bool
     */
    public function expired()
    {
        if ( $this->date_limit_end ) {
            $today = BooklyLib\Slots\DatePoint::now()->format( 'Y-m-d' );
            if ( $today > $this->date_limit_end ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if coupon has limit for customer.
     *
     * @return bool
     */
    public function hasLimitForCustomer()
    {
        return CouponCustomer::query()->where( 'coupon_id', $this->id )->count() > 0;
    }

    /**
     * Check if coupon is valid for given customer.
     *
     * @param BooklyLib\Entities\Customer $customer
     * @return bool
     */
    public function validForCustomer( BooklyLib\Entities\Customer $customer )
    {
        $valid = true;
        // Check limit to customers.
        if ( CouponCustomer::query()->where( 'coupon_id', $this->id )->count() > 0 ) {
            $cc    = new CouponCustomer();
            $valid = $cc->loadBy( array( 'coupon_id' => $this->id, 'customer_id' => $customer->getId() ) );
        }
        // Check once per customer.
        if ( $valid && $this->once_per_customer ) {
            $count = BooklyLib\Entities\CustomerAppointment::query( 'ca' )
                ->leftJoin( 'Payment', 'p', 'p.id = ca.payment_id' )
                ->where( 'ca.customer_id', $customer->getId() )
                ->where( 'p.coupon_id', $this->id )
                ->count();
            $valid = $count == 0;
        }

        return $valid;
    }

    /**
     * Check if coupon is valid for given cart item.
     *
     * @param BooklyLib\CartItem $cart_item
     * @return bool
     */
    public function validForCartItem( BooklyLib\CartItem $cart_item )
    {
        $cs = new CouponService();
        if ( $cs->loadBy( array( 'coupon_id' => $this->id, 'service_id' => $cart_item->getServiceId() ) ) ) {
            $cst = new CouponStaff();

            return $cst->loadBy( array( 'coupon_id' => $this->id, 'staff_id' => $cart_item->getStaffId() ) );
        }

        return false;
    }

    /**
     * Check if coupon is valid for given cart.
     *
     * @param BooklyLib\Cart $cart
     * @return bool
     */
    public function validForCart( BooklyLib\Cart $cart )
    {
        $valid = false;

        $services   = array();
        $cart_items = $cart->getItems();
        foreach ( $cart_items as $item ) {
            if ( $this->validForCartItem( $item ) ) {
                // Count each service in cart.
                $service_id = $item->getServiceId();
                if ( ! isset ( $services[ $service_id ] ) ) {
                    $services[ $service_id ] = 0;
                }
                ++ $services[ $service_id ];
            }
        }

        if ( ! empty ( $services ) ) {
            // Find min and max count.
            $min_count = PHP_INT_MAX;
            $max_count = 0;
            foreach ( $services as $count ) {
                if ( $count < $min_count ) {
                    $min_count = $count;
                }
                if ( $count > $max_count ) {
                    $max_count = $count;
                }
            }
            if ( $min_count >= $this->min_appointments ) {
                if ( $this->max_appointments === null || $max_count <= $this->max_appointments ) {
                    $valid = true;
                }
            }
        }

        return $valid;
    }

    /**************************************************************************
     * Entity Fields Getters & Setters                                        *
     **************************************************************************/

    /**
     * Gets code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Sets code
     *
     * @param string $code
     * @return $this
     */
    public function setCode( $code )
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Gets discount
     *
     * @return float
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Sets discount
     *
     * @param float $discount
     * @return $this
     */
    public function setDiscount( $discount )
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Gets deduction
     *
     * @return float
     */
    public function getDeduction()
    {
        return $this->deduction;
    }

    /**
     * Sets deduction
     *
     * @param float $deduction
     * @return $this
     */
    public function setDeduction( $deduction )
    {
        $this->deduction = $deduction;

        return $this;
    }

    /**
     * Gets usage_limit
     *
     * @return int
     */
    public function getUsageLimit()
    {
        return $this->usage_limit;
    }

    /**
     * Sets usage_limit
     *
     * @param int $usage_limit
     * @return $this
     */
    public function setUsageLimit( $usage_limit )
    {
        $this->usage_limit = $usage_limit;

        return $this;
    }

    /**
     * Gets used
     *
     * @return int
     */
    public function getUsed()
    {
        return $this->used;
    }

    /**
     * Sets used
     *
     * @param int $used
     * @return $this
     */
    public function setUsed( $used )
    {
        $this->used = $used;

        return $this;
    }

    /**
     * Gets once_per_customer
     *
     * @return int
     */
    public function getOncePerCustomer()
    {
        return $this->once_per_customer;
    }

    /**
     * Sets once_per_customer
     *
     * @param int $once_per_customer
     * @return $this
     */
    public function setOncePerCustomer( $once_per_customer )
    {
        $this->once_per_customer = $once_per_customer;

        return $this;
    }

    /**
     * Gets date_limit_start
     *
     * @return string
     */
    public function getDateLimitStart()
    {
        return $this->date_limit_start;
    }

    /**
     * Sets date_limit_start
     *
     * @param string $date_limit_start
     * @return $this
     */
    public function setDateLimitStart( $date_limit_start )
    {
        $this->date_limit_start = $date_limit_start;

        return $this;
    }

    /**
     * Gets date_limit_end
     *
     * @return string
     */
    public function getDateLimitEnd()
    {
        return $this->date_limit_end;
    }

    /**
     * Sets date_limit_end
     *
     * @param string $date_limit_end
     * @return $this
     */
    public function setDateLimitEnd( $date_limit_end )
    {
        $this->date_limit_end = $date_limit_end;

        return $this;
    }

    /**
     * Gets min_appointments
     *
     * @return int
     */
    public function getMinAppointments()
    {
        return $this->min_appointments;
    }

    /**
     * Sets min_appointments
     *
     * @param int $min_appointments
     * @return $this
     */
    public function setMinAppointments( $min_appointments )
    {
        $this->min_appointments = $min_appointments;

        return $this;
    }
}