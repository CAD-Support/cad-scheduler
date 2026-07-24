<?php
namespace BooklyCoupons\Lib\Entities;

use Bookly\Lib as BooklyLib;

class CouponCustomer extends BooklyLib\Base\Entity
{
    /** @var  int */
    protected $coupon_id = 0;
    /** @var  int  */
    protected $customer_id = 0;

    protected static $table = 'bookly_coupon_customers';

    protected static $schema = array(
        'id'          => array( 'format' => '%d' ),
        'coupon_id'   => array( 'format' => '%d', 'reference' => array( 'entity' => 'Coupon',  ) ),
        'customer_id' => array( 'format' => '%d', 'reference' => array( 'entity' => 'Customer', 'namespace' => '\Bookly\Lib\Entities' ) ),
    );

    /**************************************************************************
     * Entity Fields Getters & Setters                                        *
     **************************************************************************/

    /**
     * Gets coupon_id
     *
     * @return int
     */
    public function getCouponId()
    {
        return $this->coupon_id;
    }

    /**
     * Sets coupon_id
     *
     * @param int $coupon_id
     * @return $this
     */
    public function setCouponId( $coupon_id )
    {
        $this->coupon_id = $coupon_id;

        return $this;
    }

    /**
     * Gets customer_id
     *
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    /**
     * Sets customer_id
     *
     * @param int $customer_id
     * @return $this
     */
    public function setCustomerId( $customer_id )
    {
        $this->customer_id = $customer_id;

        return $this;
    }

}
