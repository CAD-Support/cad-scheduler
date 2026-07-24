<?php
namespace BooklyDiscounts\Lib\Entities;

use Bookly\Lib;

class ServiceDiscount extends Lib\Base\Entity
{
    protected static $table = 'bookly_service_discounts';

    /** @var  int */
    protected $service_id;
    /** @var  int */
    protected $discount_id;

    protected static $schema = array(
        'id' => array( 'format' => '%d' ),
        'service_id' => array( 'format' => '%d', 'reference' => array( 'entity' => 'Service', 'namespace' => '\Bookly\Lib\Entities' ) ),
        'discount_id' => array( 'format' => '%d', 'reference' => array( 'entity' => 'Discount' ) ),
    );

    /**************************************************************************
     * Entity Fields Getters & Setters                                        *
     **************************************************************************/

    /**
     * Gets service_id
     *
     * @return int
     */
    public function getServiceId()
    {
        return $this->service_id;
    }

    /**
     * Sets service_id
     *
     * @param int $service_id
     * @return $this
     */
    public function setServiceId( $service_id )
    {
        $this->service_id = $service_id;

        return $this;
    }

    /**
     * Gets discount_id
     *
     * @return int
     */
    public function getDiscountId()
    {
        return $this->discount_id;
    }

    /**
     * Sets discount_id
     *
     * @param int $discount_id
     * @return $this
     */
    public function setDiscountId( $discount_id )
    {
        $this->discount_id = $discount_id;

        return $this;
    }


    /**************************************************************************
     * Overridden Methods                                                     *
     **************************************************************************/

}
