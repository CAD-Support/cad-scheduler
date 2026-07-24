<?php
namespace BooklyEvents\Lib\Entities;

use Bookly\Lib as BooklyLib;

class EventStaff extends BooklyLib\Base\Entity
{
    /** @var int */
    protected $event_id;
    /** @var int|null */
    protected $staff_id;
    /** @var bool */
    protected $is_staff = 0;
    /** @var bool */
    protected $is_organizer = 0;

    protected static $table = 'bookly_event_staff';

    protected static $schema = array(
        'id' => array( 'format' => '%d' ),
        'event_id' => array( 'format' => '%d', 'reference' => array( 'entity' => 'Event' ) ),
        'staff_id' => array( 'format' => '%d', 'reference' => array( 'entity' => 'Staff', 'namespace' => '\Bookly\Lib\Entities' ) ),
        'is_staff' => array( 'format' => '%d' ),
        'is_organizer' => array( 'format' => '%d' ),
    );

    /**************************************************************************
     * Entity Fields Getters & Setters                                        *
     **************************************************************************/

    /**
     * Get gift card type
     *
     * @return int
     */
    public function getEventId()
    {
        return $this->event_id;
    }

    /**
     * Set gift card type
     *
     * @param int $event_id
     * @return $this
     */
    public function setEventId( $event_id )
    {
        $this->event_id = $event_id;

        return $this;
    }

    /**
     * @return int
     */
    public function getStaffId()
    {
        return $this->staff_id;
    }

    /**
     * @param int $staff_id
     * @return $this
     */
    public function setStaffId( $staff_id )
    {
        $this->staff_id = $staff_id;

        return $this;
    }

    /**
     * @return bool
     */
    public function isIsStaff()
    {
        return $this->is_staff;
    }

    /**
     * @param bool $is_staff
     * @return EventStaff
     */
    public function setIsStaff( $is_staff )
    {
        $this->is_staff = $is_staff;

        return $this;
    }

    /**
     * @return bool
     */
    public function isIsOrganizer()
    {
        return $this->is_organizer;
    }

    /**
     * @param bool $is_organizer
     * @return EventStaff
     */
    public function setIsOrganizer( $is_organizer )
    {
        $this->is_organizer = $is_organizer;

        return $this;
    }

    /**************************************************************************
     * Overridden Methods                                                     *
     **************************************************************************/

}