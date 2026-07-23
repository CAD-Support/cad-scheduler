<?php
namespace BooklyEvents\Lib\Entities;

use Bookly\Lib as BooklyLib;
use BooklyPro\Lib\CodeGenerator;

class EventAttendee extends BooklyLib\Base\Entity
{
    /** @var string */
    protected $code = '';
    /** @var int */
    protected $ticket_type_id;
    /** @var int|null */
    protected $customer_id;
    /** @var int|null */
    protected $payment_id;
    /** @var int|null */
    protected $order_id;
    /** @var string|null */
    protected $checked_in_at;

    /** @var string */
    private $code_mask;

    protected static $table = 'bookly_event_attendees';

    protected static $schema = array(
        'id' => array( 'format' => '%d' ),
        'customer_id' => array( 'format' => '%d', 'reference' => array( 'entity' => 'Customer', 'namespace' => '\Bookly\Lib\Entities' ) ),
        'ticket_type_id' => array( 'format' => '%d', 'reference' => array( 'entity' => 'EventTicketType' ) ),
        'code' => array( 'format' => '%s' ),
        'payment_id' => array( 'format' => '%d', 'reference' => array( 'entity' => 'Payment', 'namespace' => '\Bookly\Lib\Entities' ) ),
        'order_id' => array( 'format' => '%d', 'reference' => array( 'entity' => 'Order', 'namespace' => '\Bookly\Lib\Entities' ) ),
        'checked_in_at' => array( 'format' => '%s' ),
    );

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
     * Get gift card type
     *
     * @return int
     */
    public function getTicketTypeId()
    {
        return $this->ticket_type_id;
    }

    /**
     * Set gift card type
     *
     * @param int $ticket_type_id
     * @return $this
     */
    public function setTicketTypeId( $ticket_type_id )
    {
        $this->ticket_type_id = $ticket_type_id;

        return $this;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    /**
     * @param int $customer_id
     * @return $this
     */
    public function setCustomerId( $customer_id )
    {
        $this->customer_id = $customer_id;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPaymentId()
    {
        return $this->payment_id;
    }

    /**
     * @param int|null $payment_id
     * @return $this
     */
    public function setPaymentId( $payment_id )
    {
        $this->payment_id = $payment_id;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @param int|null $order_id
     * @return $this
     */
    public function setOrderId( $order_id )
    {
        $this->order_id = $order_id;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCheckedInAt()
    {
        return $this->checked_in_at;
    }

    /**
     * @param string|null $checked_in_at
     * @return $this
     */
    public function setCheckedInAt( $checked_in_at )
    {
        $this->checked_in_at = $checked_in_at;

        return $this;
    }

    /**
     * @param string $code_mask
     * @return EventAttendee
     */
    public function setCodeMask( $code_mask )
    {
        $this->code_mask = $code_mask;

        return $this;
    }

    /**************************************************************************
     * Overridden Methods                                                     *
     **************************************************************************/

    public function save()
    {
        if ( $this->code == '' ) {
            $mask = $this->code_mask ?: get_option( 'bookly_event_ticked_default_code_mask', '***-****-***' );
            $code = CodeGenerator::generateUniqueCode( '\BooklyEvents\Lib\Entities\EventAttendee', $mask );
            $this->setCode( $code );
        }

        return parent::save();
    }
}