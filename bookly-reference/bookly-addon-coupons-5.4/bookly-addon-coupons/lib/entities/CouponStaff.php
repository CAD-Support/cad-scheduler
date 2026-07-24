<?php
namespace BooklyCoupons\Lib\Entities;

use Bookly\Lib as BooklyLib;

class CouponStaff extends BooklyLib\Base\Entity
{
    /** @var  int */
    protected $coupon_id = 0;
    /** @var  int  */
    protected $staff_id = 0;

    protected static $table = 'bookly_coupon_staff';

    protected static $schema = array(
        'id'        => array( 'format' => '%d' ),
        'coupon_id' => array( 'format' => '%d', 'reference' => array( 'entity' => 'Coupon',  ) ),
        'staff_id'  => array( 'format' => '%d', 'reference' => array( 'entity' => 'Staff', 'namespace' => '\Bookly\Lib\Entities' ) ),
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
     * Gets staff_id
     *
     * @return int
     */
    public function getStaffId()
    {
        return $this->staff_id;
    }

    /**
     * Sets staff_id
     *
     * @param int $staff_id
     * @return $this
     */
    public function setStaffId( $staff_id )
    {
        $this->staff_id = $staff_id;

        return $this;
    }

}
