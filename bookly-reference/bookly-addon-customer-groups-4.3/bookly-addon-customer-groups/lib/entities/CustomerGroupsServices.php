<?php
namespace BooklyCustomerGroups\Lib\Entities;

use Bookly\Lib;

class CustomerGroupsServices extends Lib\Base\Entity
{
    protected static $table = 'bookly_customer_groups_services';

    /** @var  int */
    protected $group_id;
    /** @var  int */
    protected $service_id;

    protected static $schema = array(
        'id'         => array( 'format' => '%d' ),
        'group_id'   => array( 'format' => '%d', 'reference' => array( 'entity' => 'CustomerGroups' ) ),
        'service_id' => array( 'format' => '%d', 'reference' => array( 'entity' => 'Service', 'namespace' => '\Bookly\Lib\Entities' ) ),
    );

    /**************************************************************************
     * Entity Fields Getters & Setters                                        *
     **************************************************************************/

    /**
     * Gets group_id
     *
     * @return int
     */
    public function getGroupId()
    {
        return $this->group_id;
    }

    /**
     * Sets group_id
     *
     * @param int $group_id
     * @return $this
     */
    public function setGroupId( $group_id )
    {
        $this->group_id = $group_id;

        return $this;
    }

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

    /**************************************************************************
     * Overridden Methods                                                     *
     **************************************************************************/
}
