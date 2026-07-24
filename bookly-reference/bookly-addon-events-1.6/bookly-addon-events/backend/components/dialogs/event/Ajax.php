<?php
namespace BooklyEvents\Backend\Components\Dialogs\Event;

use Bookly\Backend\Components\Dialogs\Queue\NotificationList;
use Bookly\Lib as BooklyLib;
use BooklyPro\Lib as BooklyProLib;
use BooklyEvents\Lib\Entities;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return array( '_default' => 'supervisor' );
    }

    public static function getEvent()
    {
        $response = array( 'success' => false, 'data' => array() );

        $event = new Entities\Event();
        if ( $event->load( self::parameter( 'id' ) ) ) {
            $response['success'] = true;
            $response['data']['id'] = $event->getId();
            $response['data']['info'] = $event->getInfo();
            $response['data']['start_date'] = $event->getStartDate();
            $response['data']['end_date'] = $event->getEndDate();
            $response['data']['sales_end_at'] = $event->getSalesEndAt();
            $response['data']['max_capacity'] = (int) $event->getMaxCapacity();
            $response['data']['title'] = $event->getTitle();
            $response['data']['attachment_id'] = $event->getAttachmentId();
            $response['data']['thumb'] = BooklyLib\Utils\Common::getAttachmentUrl( $event->getAttachmentId() );
            $response['data']['location_id'] = $event->getLocationId();
            $response['data']['published'] = (int) $event->getPublished();
            $response['data']['color'] = $event->getColor();
            $response['data']['tags'] = $event->getTags();
            $response['data']['wc_product_id'] = $event->getWcProductId();
            $response['data']['wc_cart_info_name'] = $event->getWcCartInfoName();
            $response['data']['ticket_mask'] = $event->getTicketMask() ?: get_option( 'bookly_event_ticked_default_code_mask' );
            $response['data']['online_meeting_provider'] = $event->getOnlineMeetingProvider();
            $response['data']['online_meeting_staff_id'] = $event->getOnlineMeetingStaffId();
            $response['data']['online_meeting_start_url'] = BooklyLib\Proxy\Shared::buildOnlineMeetingStartUrl( '', $event );
            $response['data']['affected_staff'] = Entities\EventStaff::query()
                ->select( 'staff_id AS id, is_staff, is_organizer' )
                ->where( 'event_id', $event->getId() )
                ->fetchArray() ?: array();
        }

        wp_send_json( $response );
    }

    public static function saveEvent()
    {
        $event = new Entities\Event();
        $event->load( self::parameter( 'id' ) );
        $event->setTitle( self::parameter( 'title' ) )
            ->setInfo( self::parameter( 'info' ) )
            ->setAttachmentId( self::parameter( 'attachment_id' ) )
            ->setPublished( self::parameter( 'published' ) )
            ->setStartDate( self::parameter( 'start_date' ) )
            ->setEndDate( self::parameter( 'end_date' ) )
            ->setSalesEndAt( self::parameter( 'sales_end_at' ) )
            ->setMaxCapacity( (int) self::parameter( 'max_capacity' ) )
            ->setColor( self::parameter( 'color' ) )
            ->setTicketMask( self::parameter( 'ticket_mask' ) ?: get_option( 'bookly_event_ticked_default_code_mask' ) )
            ->setOnlineMeetingProvider( self::parameter( 'online_meeting_provider' ) )
            ->setOnlineMeetingStaffId( self::parameter( 'online_meeting_staff_id' ) );
        if ( BooklyLib\Config::locationsActive() ) {
            $event->setLocationId( self::parameter( 'location_id' ) );
        }
        if ( get_option( 'bookly_wc_enabled' ) && get_option( 'bookly_wc_product' ) ) {
            $event->setWcProductId( self::parameter( 'wc_product_id' ) )
                ->setWcCartInfoName( self::parameter( 'wc_cart_info_name' ) );
        }
        $removed_tags = $event->getTags() ? json_decode( $event->getTags(), false ) : array();
        if ( ! is_array( $removed_tags ) ) {
            $removed_tags = array();
        }

        $new_tags = array();
        if ( $tags = self::parameter( 'tags' ) ) {
            $tag_colors = self::parameter( 'tag_colors' );
            $event->setTags( json_encode( $tags, 256 ) );
            foreach ( $tags as $tag ) {
                $tag_exists = BooklyProLib\Entities\Tag::query()
                    ->where( 'tag', $tag )
                    ->where( 'type', BooklyProLib\Entities\Tag::TYPE_EVENT )
                    ->count();
                if ( ! $tag_exists ) {
                    $new_tag = new BooklyProLib\Entities\Tag();
                    $new_tag
                        ->setTag( $tag )
                        ->setColorId( isset( $tag_colors[ $tag ] ) ? $tag_colors[ $tag ] : 0 )
                        ->setType( BooklyProLib\Entities\Tag::TYPE_EVENT )
                        ->save();
                    $new_tags[] = array( 'tag' => $tag, 'color_id' => $new_tag->getColorId() );
                }
                $key = array_search( $tag, $removed_tags, true );
                if ( $key !== false ) {
                    unset( $removed_tags[ $key ] );
                }
            }
        } else {
            $event->setTags( null );
        }

        BooklyLib\Proxy\Shared::syncOnlineMeeting( array(), $event );

        $event->save();

        // Remove unused tags from the database.
        foreach ( $removed_tags as $tag ) {
            $tag_exists = Entities\Event::query()
                ->whereNot( 'id', $event->getId() )
                ->whereNot( 'tags', null )
                ->whereLike( 'tags', '%"' . $tag . '"%' )
                ->limit( 1 )
                ->count();
            if ( ! $tag_exists ) {
                BooklyProLib\Entities\Tag::query()
                    ->delete()
                    ->where( 'type', BooklyProLib\Entities\Tag::TYPE_EVENT )
                    ->where( 'tag', $tag )
                    ->execute();
            }
        }
        if ( $event->isLoaded() ) {
            $organizers_list = self::parameter( 'organizers_list' ) ?: array();
            $staff_list = self::parameter( 'staff_list' ) ?: array();
            $unique = array_unique( array_merge( $organizers_list, $staff_list ) );
            /** @var Entities\EventStaff[] $existing */
            $existing = Entities\EventStaff::query()
                ->where( 'event_id', $event->getId() )
                ->indexBy( 'staff_id' )
                ->find();

            if ( empty ( $unique ) ) {
                Entities\EventStaff::query()
                    ->delete()
                    ->where( 'event_id', $event->getId() )
                    ->execute();
            } else {
                Entities\EventStaff::query()
                    ->delete()
                    ->where( 'event_id', $event->getId() )
                    ->whereNotIn( 'staff_id', $unique )
                    ->execute();
                $new_staff = Entities\EventStaff::query()
                    ->where( 'event_id', $event->getId() )
                    ->fetchColDiff( 'staff_id', $unique );
                foreach ( $new_staff as $staff_id ) {
                    $staff = new Entities\EventStaff();
                    $staff
                        ->setEventId( $event->getId() )
                        ->setStaffId( $staff_id )
                        ->setIsOrganizer( in_array( $staff_id, $organizers_list ) )
                        ->setIsStaff( in_array( $staff_id, $staff_list ) )
                        ->save();
                }
                foreach ( $unique as $staff_id ) {
                    if ( isset( $existing[ $staff_id ] ) ) {
                        $staff = $existing[ $staff_id ];
                        $staff
                            ->setIsOrganizer( in_array( $staff_id, $organizers_list ) )
                            ->setIsStaff( in_array( $staff_id, $staff_list ) )
                            ->save();
                    }
                }
            }

            $data = compact( 'new_tags' );
            if ( self::parameter( 'send_notifications' ) ) {
                $queue = new NotificationList();
                \BooklyEvents\Lib\Notifications\Sender::sendNewEvent( $event, $queue );
                $list = $queue->getList();
                if ( $list ) {
                    $db_queue = new BooklyLib\Entities\NotificationQueue();
                    $db_queue
                        ->setData( json_encode( array( 'all' => $list ) ) )
                        ->save();

                    $data['queue'] = array( 'token' => $db_queue->getToken(), 'all' => $queue->getInfo() );
                }
            }

            wp_send_json_success( $data );
        }

        wp_send_json_error( array( 'message' => __( 'Failed', 'bookly' ) ) );
    }
}
