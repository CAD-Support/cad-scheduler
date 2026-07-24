<?php
namespace BooklyEvents\Lib\Entities;

use Bookly\Lib as BooklyLib;

class Event extends BooklyLib\Base\Entity
{
    /** @var string */
    protected $title;
    /** @var int */
    protected $location_id;
    /** @var string */
    protected $start_date;
    /** @var string */
    protected $end_date;
    /** @var string */
    protected $sales_end_at;
    /** @var int */
    protected $max_capacity = 0;
    /** @var  int */
    protected $attachment_id;
    /** @var string */
    protected $info;
    /** @var string */
    protected $ticket_mask;
    /** @var string */
    protected $color;
    /** @var int */
    protected $published = 0;
    /** @var string */
    protected $tags;
    /** @var  int */
    protected $wc_product_id = 0;
    /** @var string */
    protected $wc_cart_info_name;
    /** @var string */
    protected $online_meeting_provider = 'off';
    /** @var string */
    protected $online_meeting_id;
    /** @var string */
    protected $online_meeting_data;
    /** @var int */
    protected $online_meeting_staff_id;
    /** @var string */
    protected $created_at;

    protected $loggable = true;

    protected static $table = 'bookly_events';

    protected static $schema = array(
        'id' => array( 'format' => '%d' ),
        'location_id' => array( 'format' => '%d', 'reference' => array( 'entity' => 'Location', 'namespace' => '\BooklyLocations\Lib\Entities', 'required' => 'bookly-addon-locations' ) ),
        'title' => array( 'format' => '%s' ),
        'start_date' => array( 'format' => '%s' ),
        'end_date' => array( 'format' => '%s' ),
        'sales_end_at' => array( 'format' => '%s' ),
        'max_capacity' => array( 'format' => '%d' ),
        'attachment_id' => array( 'format' => '%d' ),
        'info' => array( 'format' => '%s' ),
        'ticket_mask' => array( 'format' => '%s' ),
        'color' => array( 'format' => '%s' ),
        'published' => array( 'format' => '%d' ),
        'tags' => array( 'format' => '%s' ),
        'wc_product_id' => array( 'format' => '%d' ),
        'wc_cart_info_name' => array( 'format' => '%s' ),
        'online_meeting_provider' => array( 'format' => '%s' ),
        'online_meeting_id' => array( 'format' => '%s' ),
        'online_meeting_data' => array( 'format' => '%s' ),
        'online_meeting_staff_id' => array( 'format' => '%d', 'reference' => array( 'entity' => 'Staff', 'namespace' => '\Bookly\Lib\Entities' ) ),
        'created_at' => array( 'format' => '%s' ),
    );

    /**
     * Get translated title.
     *
     * @param string $locale
     * @return string
     */
    public function getTranslatedTitle( $locale = null )
    {
        return BooklyLib\Utils\Common::getTranslatedString( 'event_' . $this->getId(), $this->getTitle(), $locale );
    }

    /**
     * Get translated info.
     *
     * @param string $locale
     * @return string
     */
    public function getTranslatedInfo( $locale = null )
    {
        return BooklyLib\Utils\Common::getTranslatedString( 'event_' . $this->getId() . '_info', $this->getInfo(), $locale );
    }

    /**************************************************************************
     * Entity Fields Getters & Setters                                        *
     **************************************************************************/

    /**
     * @return int
     */
    public function getLocationId()
    {
        return $this->location_id;
    }

    /**
     * @param int $location_id
     * @return Event
     */
    public function setLocationId( $location_id )
    {
        $this->location_id = $location_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return Event
     */
    public function setTitle( $title )
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * @param string $start_date
     * @return Event
     */
    public function setStartDate( $start_date )
    {
        $this->start_date = $start_date;

        return $this;
    }

    /**
     * @return string
     */
    public function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * @param string $end_date
     * @return Event
     */
    public function setEndDate( $end_date )
    {
        $this->end_date = $end_date;

        return $this;
    }

    /**
     * @return string
     */
    public function getSalesEndAt()
    {
        return $this->sales_end_at;
    }

    /**
     * @param string $sales_end_at
     * @return Event
     */
    public function setSalesEndAt( $sales_end_at )
    {
        $this->sales_end_at = $sales_end_at;

        return $this;
    }

    /**
     * Overall ticket cap for the event (sum across all ticket types). 0 = unlimited.
     *
     * @return int
     */
    public function getMaxCapacity()
    {
        return $this->max_capacity;
    }

    /**
     * @param int $max_capacity
     * @return Event
     */
    public function setMaxCapacity( $max_capacity )
    {
        $this->max_capacity = $max_capacity;

        return $this;
    }

    /**
     * @return int
     */
    public function getAttachmentId()
    {
        return $this->attachment_id;
    }

    /**
     * @param int $attachment_id
     * @return Event
     */
    public function setAttachmentId( $attachment_id )
    {
        $this->attachment_id = $attachment_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @param string $info
     * @return Event
     */
    public function setInfo( $info )
    {
        $this->info = $info;

        return $this;
    }

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param string $created_at
     * @return Event
     */
    public function setCreatedAt( $created_at )
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * @return string
     */
    public function getTicketMask()
    {
        return $this->ticket_mask;
    }

    /**
     * @param string $ticket_mask
     * @return Event
     */
    public function setTicketMask( $ticket_mask )
    {
        $this->ticket_mask = $ticket_mask;

        return $this;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param string $color
     * @return Event
     */
    public function setColor( $color )
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return int
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * @param int $published
     * @return Event
     */
    public function setPublished( $published )
    {
        $this->published = $published;

        return $this;
    }

    /**
     * Gets tags
     *
     * @return string
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Sets tags
     *
     * @param string $tags
     * @return $this
     */
    public function setTags( $tags )
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Gets wc_product_id
     *
     * @return int
     */
    public function getWCProductId()
    {
        return $this->wc_product_id;
    }

    /**
     * Sets wc_product_id
     *
     * @param int $wc_product_id
     * @return $this
     */
    public function setWCProductId( $wc_product_id )
    {
        $this->wc_product_id = $wc_product_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getWcCartInfoName()
    {
        return $this->wc_cart_info_name;
    }

    /**
     * @param string $wc_cart_info_name
     * @return Event
     */
    public function setWcCartInfoName( $wc_cart_info_name )
    {
        $this->wc_cart_info_name = $wc_cart_info_name;

        return $this;
    }

    /**
     * @return string
     */
    public function getOnlineMeetingProvider()
    {
        return $this->online_meeting_provider;
    }

    /**
     * @param string $online_meetings
     * @return Event
     */
    public function setOnlineMeetingProvider( $online_meetings )
    {
        $this->online_meeting_provider = $online_meetings;

        return $this;
    }

    /**
     * @return string
     */
    public function getOnlineMeetingId()
    {
        return $this->online_meeting_id;
    }

    /**
     * @param string $online_meeting_id
     * @return Event
     */
    public function setOnlineMeetingId( $online_meeting_id )
    {
        $this->online_meeting_id = $online_meeting_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getOnlineMeetingData()
    {
        return $this->online_meeting_data;
    }

    /**
     * @param string $online_meeting_data
     * @return Event
     */
    public function setOnlineMeetingData( $online_meeting_data )
    {
        $this->online_meeting_data = $online_meeting_data;

        return $this;
    }

    /**
     * @return string
     */
    public function getOnlineMeetingStaffId()
    {
        return $this->online_meeting_staff_id;
    }

    /**
     * @param string $online_meeting_staff_id
     * @return Event
     */
    public function setOnlineMeetingStaffId( $online_meeting_staff_id )
    {
        $this->online_meeting_staff_id = $online_meeting_staff_id;

        return $this;
    }

    /**
     * Get translated Cart info name
     *
     * @param string $locale
     * @return string
     */
    public function getTranslatedWCCartInfoName( $locale = null )
    {
        return BooklyLib\Utils\Common::getTranslatedString( 'event_' . $this->getId() . '_wc_cart_info_name', $this->getWCCartInfoName(), $locale );
    }

    /**************************************************************************
     * Overridden Methods                                                     *
     **************************************************************************/

    /**
     * Save event.
     *
     * @return false|int
     */
    public function save()
    {
        if ( $this->getId() === null ) {
            $this->setCreatedAt( current_time( 'mysql' ) );
        }

        if ( $this->color === null ) {
            $this->color = sprintf( '#%06X', mt_rand( 0, 0x64FFFF ) );
        }

        $return = parent::save();
        if ( $this->isLoaded() ) {
            // Register string for translate in WPML.
            do_action( 'wpml_register_single_string', 'bookly', 'event_' . $this->getId(), $this->getTitle() );
            do_action( 'wpml_register_single_string', 'bookly', 'event_' . $this->getId() . '_info', $this->getInfo() );
            do_action( 'wpml_register_single_string', 'bookly', 'event_' . $this->getId() . '_wc_cart_info_name', $this->getTranslatedWCCartInfoName() );
        }

        return $return;
    }
}
