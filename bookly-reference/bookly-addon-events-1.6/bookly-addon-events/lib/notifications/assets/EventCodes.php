<?php
namespace BooklyEvents\Lib\Notifications\Assets;

use Bookly\Lib\Notifications\Assets\Base;
use BooklyEvents\Lib\Entities;
use Bookly\Lib\Utils;
use Bookly\Lib\Proxy;

class EventCodes extends Base\Codes
{
    /** @var Entities\Event */
    protected $event;

    /**
     * Constructor.
     *
     * @param Entities\Event $event
     */
    public function __construct( Entities\Event $event )
    {
        $this->event = $event;
    }

    /**
     * @return Entities\Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @inheritDoc
     */
    protected function getReplaceCodes( $format )
    {
        $replace_codes = parent::getReplaceCodes( $format );

        $location = $this->event->getLocationId()
            ? Proxy\Locations::findById( $this->event->getLocationId() )
            : null;

        $codes = array(
            'event_title' => $this->event->getTitle(),
            'event_info' => $this->event->getInfo(),
            'event_start_date' => Utils\DateTime::formatDate( $this->event->getStartDate() ),
            'event_end_date' => Utils\DateTime::formatDate( $this->event->getEndDate() ),
            'event_start_time' => Utils\DateTime::formatTime( $this->event->getStartDate() ),
            'event_end_time' => Utils\DateTime::formatTime( $this->event->getEndDate() ),
            'event_image_url' => Utils\Common::getAttachmentUrl( $this->event->getAttachmentId(), 'full' ),
            'location_name' => $location ? $location->getName() : '',
            'location_info' => $location ? $location->getInfo() : '',
        );

        $codes['event_image'] = $format === 'html' && $codes['event_image_url']
            ? Utils\Common::getImageTag( $codes['event_image_url'], $this->event->getTitle() )
            : $codes['event_image_url'];

        return array_merge( $replace_codes, $codes );
    }
}