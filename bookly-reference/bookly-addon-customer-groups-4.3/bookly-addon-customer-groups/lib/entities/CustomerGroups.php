<?php
namespace BooklyCustomerGroups\Lib\Entities;

use Bookly\Lib;

class CustomerGroups extends Lib\Base\Entity
{
    protected static $table = 'bookly_customer_groups';

    /** @var string */
    protected $name;
    /** @var string */
    protected $description;
    /** @var string */
    protected $appointment_status;
    /** @var string */
    protected $discount;
    /** @var int */
    protected $skip_payment = 0;
    /** @var string */
    protected $gateways;

    protected static $schema = array(
        'id'                 => array( 'format' => '%d' ),
        'name'               => array( 'format' => '%s' ),
        'description'        => array( 'format' => '%s' ),
        'appointment_status' => array( 'format' => '%s' ),
        'discount'           => array( 'format' => '%s' ),
        'skip_payment'       => array( 'format' => '%d' ),
        'gateways'           => array( 'format' => '%s' ),
    );

    /**************************************************************************
     * Entity Fields Getters & Setters                                        *
     **************************************************************************/

    /**
     * Gets name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets name
     *
     * @param string $name
     * @return $this
     */
    public function setName( $name )
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets description
     *
     * @param string $description
     * @return $this
     */
    public function setDescription( $description )
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Gets appointment_status
     *
     * @return string
     */
    public function getAppointmentStatus()
    {
        return $this->appointment_status;
    }

    /**
     * Sets appointment_status
     *
     * @param string $appointment_status
     * @return $this
     */
    public function setAppointmentStatus( $appointment_status )
    {
        $this->appointment_status = $appointment_status;

        return $this;
    }

    /**
     * Gets discount
     *
     * @return string
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Sets discount
     *
     * @param string $discount
     * @return $this
     */
    public function setDiscount( $discount )
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Gets skip_payment
     *
     * @return string
     */
    public function getSkipPayment()
    {
        return $this->skip_payment;
    }

    /**
     * Sets skip_payment
     *
     * @param string $skip_payment
     * @return $this
     */
    public function setSkipPayment( $skip_payment )
    {
        $this->skip_payment = $skip_payment;

        return $this;
    }

    /**
     * Gets gateways
     *
     * @return string
     */
    public function getGateways()
    {
        return $this->gateways;
    }

    /**
     * Sets gateways
     *
     * @param string $gateways
     * @return $this
     */
    public function setGateways( $gateways )
    {
        $this->gateways = $gateways;

        return $this;
    }

    /**************************************************************************
     * Overridden Methods                                                     *
     **************************************************************************/
}
