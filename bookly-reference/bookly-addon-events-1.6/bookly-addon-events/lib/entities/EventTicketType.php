<?php
namespace BooklyEvents\Lib\Entities;

use Bookly\Lib as BooklyLib;

class EventTicketType extends BooklyLib\Base\Entity
{
    /** @var integer */
    protected $event_id;
    /** @var string */
    protected $title = '';
    /** @var int */
    protected $quantity = 0;
    /** @var int */
    protected $reserved = 0;
    /** @var int */
    protected $reserved_ps = 0;
    /** @var float */
    protected $price = 0;
    /** @var int */
    protected $position = 0;

    protected $loggable = true;

    protected static $table = 'bookly_event_ticket_types';

    protected static $schema = array(
        'id' => array( 'format' => '%d' ),
        'event_id' => array( 'format' => '%d', 'reference' => array( 'entity' => 'Event' ) ),
        'title' => array( 'format' => '%s' ),
        'quantity' => array( 'format' => '%d' ),
        'reserved' => array( 'format' => '%d' ),
        'reserved_ps' => array( 'format' => '%d' ),
        'price' => array( 'format' => '%f' ),
        'position' => array( 'format' => '%d' ),
    );

    /**
     * Get translated title.
     *
     * @param string $locale
     * @return string
     */
    public function getTranslatedTitle( $locale = null )
    {
        return BooklyLib\Utils\Common::getTranslatedString( 'event_ticket_type_' . $this->getId(), $this->getTitle(), $locale );
    }

    /**************************************************************************
     * Entity Fields Getters & Setters                                        *
     **************************************************************************/

    /**
     * @return int
     */
    public function getEventId()
    {
        return $this->event_id;
    }

    /**
     * @param int $event_id
     * @return EventTicketType
     */
    public function setEventId( $event_id )
    {
        $this->event_id = $event_id;

        return $this;
    }

    /**
     * Gets title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets title
     *
     * @param string $title
     * @return $this
     */
    public function setTitle( $title )
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     * @return EventTicketType
     */
    public function setQuantity( $quantity )
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @return int
     */
    public function getReserved()
    {
        return $this->reserved;
    }

    /**
     * @param int $reserved
     * @return EventTicketType
     */
    public function setReserved( $reserved )
    {
        $this->reserved = $reserved;

        return $this;
    }

    /**
     * @return int
     */
    public function getReservedPs()
    {
        return $this->reserved_ps;
    }

    /**
     * @param int $reserved_ps
     * @return EventTicketType
     */
    public function setReservedPs( $reserved_ps )
    {
        $this->reserved_ps = $reserved_ps;

        return $this;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param float $price
     * @return $this
     */
    public function setPrice( $price )
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $position
     * @return EventTicketType
     */
    public function setPosition( $position )
    {
        $this->position = $position;

        return $this;
    }



    /**************************************************************************
     * Overridden Methods                                                     *
     **************************************************************************/

    /**
     * @inerhitDoc
     */
    public function save()
    {
        $return = parent::save();

        if ( $this->isLoaded() ) {
            // Register string for translate in WPML.
            do_action( 'wpml_register_single_string', 'bookly', 'event_ticket_type_' . $this->getId(), $this->getTitle() );
        }

        return $return;
    }
}