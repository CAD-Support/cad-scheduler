<?php
namespace BooklyAdvancedGoogleCalendar\Lib\Google;

use Bookly\Lib\Config;
use Bookly\Lib\Entities\Appointment;
use Bookly\Lib\Slots\DatePoint;
use BooklyPro\Lib\Google\Calendar as ProCalendar;
use Bookly\Lib as BooklyLib;

class Calendar extends ProCalendar
{
    /**
     * Perform incremental synchronization of appointments with Google Calendar.
     *
     * @return bool
     */
    public function sync()
    {
        if ( ! $this->_hasCalendar() ) {
            return true;
        }

        if ( ! $this->_hasSyncToken() ) {
            return $this->_fullSync();
        }

        try {
            $params = array(
                'singleEvents' => true,
                'maxResults' => self::EVENTS_PER_REQUEST,
                'syncToken' => $this->_getSyncToken(),
            );

            $sync_up_to_date = date_create( sprintf( '%d days midnight', get_option( 'bookly_gc_full_sync_offset_days_after', 0 ) ) );

            do {
                // Fetch events.
                $events = $this->client->service()->events->listEvents( $this->_getCalendarId(), $params );
                BooklyLib\Utils\Log::tempPut( BooklyLib\Utils\Log::OPTION_GOOGLE, null, null, serialize( $events ), 'Fetch google events up to ' . $sync_up_to_date->format( 'Y-m-d' ) );

                /** @var \BooklyGoogle_Service_Calendar_Event $event */
                foreach ( $events->getItems() as $event ) {
                    $event_start = $event->getStart();
                    if ( $event_start ) {
                        $event_start_date = new \DateTime( $event_start->getDateTime() );
                    }

                    if ( ! $event_start || $event_start_date <= $sync_up_to_date ) {
                        if ( $this->_isDeletedEvent( $event ) || $this->_isTransparentEvent( $event ) ) {
                            // Delete appointment if event does not block time on Google Calendar anymore.
                            $appointment = $this->_findAppointmentForEvent( $event );
                            BooklyLib\Utils\Log::tempPut( BooklyLib\Utils\Log::OPTION_GOOGLE, $appointment ? $appointment->getId() : null, null, 'delete', 'Delete bookly event from google' );
                            if ( $appointment ) {
                                $appointment->setGoogleEventId( null )->delete();
                            }
                        } else {
                            // Create/update appointment.
                            $appointment = $this->_findAppointmentForEvent( $event );

                            BooklyLib\Utils\Log::tempPut( BooklyLib\Utils\Log::OPTION_GOOGLE, $appointment ? $appointment->getId() : null, null, 'create/update', 'Create/Update bookly event from google getId = ' . $event->getId() . ' getEtag = ' . $event->getEtag() );
                            if ( $appointment && $appointment->getGoogleEventETag() == $event->getEtag() ) {
                                // Skip event with existing ETag.
                                BooklyLib\Utils\Log::tempPut( BooklyLib\Utils\Log::OPTION_GOOGLE, $appointment->getId(), null, 'create/update', 'Skip google action' );
                                continue;
                            }
                            $appointment = $appointment ?: new Appointment();
                            $this->_populateAppointment( $appointment, $event )->save();
                            BooklyLib\Proxy\Shared::syncOnlineMeeting( array(), $appointment );
                        }
                    }
                }
                $params['pageToken'] = $events->getNextPageToken();

            } while ( $params['pageToken'] !== null );

            // Save next sync token.
            $this->_setSyncToken( $events->getNextSyncToken() )->_saveData();

            return true;

        } catch ( \BooklyGoogle_Service_Exception $e ) {
            if ( $e->getCode() == 410 ) {
                // Full sync required by server.
                return $this->_fullSync();
            } else {
                $this->client->addError( $e->getMessage() );
            }
        } catch ( \Exception $e ) {
            $this->client->addError( $e->getMessage() );
        }

        return false;
    }

    /**
     * Clear sync token.
     *
     * @return $this
     */
    public function clearSyncToken()
    {
        return $this->_setSyncToken( null );
    }

    /**
     * Create notification channel.
     *
     * @return bool
     */
    public function watch()
    {
        if ( ! $this->_hasCalendar() ) {
            return true;
        }

        try {
            if ( $this->_hasChannel() ) {
                // Stop existing channel.
                $this->stopWatching( false );
            }
            // Create new channel.
            $channel_id = str_replace( '.', '-', uniqid( 'bookly-' . $this->client->staff()->getId() . '-', true ) );
            $channel = new \BooklyGoogle_Service_Calendar_Channel();
            $channel->setId( $channel_id );
            $channel->setType( 'web_hook' );
            $channel->setAddress( admin_url( 'admin-ajax.php?action=bookly_advanced_google_calendar_push_notifications', 'https' ) );

            $channel = $this->client->service()->events->watch( $this->_getCalendarId(), $channel );

            // Save channel data.
            $this
                ->_setChannelId( $channel->getId() )
                ->_setChannelResourceId( $channel->getResourceId() )
                ->_setChannelExpiration( $channel->getExpiration() )
                ->_saveData();

            return true;

        } catch ( \Exception $e ) {
            $this->client->addError( $e->getMessage() );
        }

        return false;
    }

    /**
     * Stop notification channel.
     *
     * @param bool $update_staff
     * @return bool
     */
    public function stopWatching( $update_staff = true )
    {
        if ( ! $this->_hasChannel() ) {
            return true;
        }

        try {
            $channel = new \BooklyGoogle_Service_Calendar_Channel();
            $channel->setId( $this->_getChannelId() );
            $channel->setResourceId( $this->_getChannelResourceId() );

            $this->client->service()->channels->stop( $channel );

            // Delete channel data.
            $this
                ->_setChannelId( null )
                ->_setChannelResourceId( null )
                ->_setChannelExpiration( null );

            if ( $update_staff ) {
                $this->_saveData();
            }

            return true;

        } catch ( \Exception $e ) {
            $this->client->addError( $e->getMessage() );
        }

        return false;
    }

    /**
     * Perform full 2-way synchronization between appointments and Google Calendar.
     *
     * @return bool
     */
    protected function _fullSync()
    {
        $start_date = DatePoint::fromStr( sprintf( '-%d days midnight', get_option( 'bookly_gc_full_sync_offset_days_before', 0 ) ) );
        $end_date = DatePoint::fromStr( sprintf( '%d days midnight', get_option( 'bookly_gc_full_sync_offset_days_after', 0 ) ) );

        // 1. Delete appointments created from Google Calendar.
        Appointment::query()
            ->delete()
            ->where( 'staff_id', $this->client->staff()->getId() )
            ->where( 'created_from', 'google' )
            ->whereGt( 'end_date', $start_date->format( 'Y-m-d H:i:s' ) )
            ->whereLte( 'end_date', $end_date->format( 'Y-m-d H:i:s' ) )
            ->execute();
        BooklyLib\Utils\Log::common( BooklyLib\Utils\Log::ACTION_DELETE, Appointment::getTableName(), null, null, __METHOD__, 'Delete all appointments created from Google Calendar' );

        // 2. Delete Google Calendar events created from Bookly.
        if ( $this->_deleteEventsCreatedFromBookly( $start_date, $end_date ) ) {
            // 3. Copy Google Calendar events to appointments.
            if ( $this->_copyEventsToAppointments( $start_date, $end_date ) ) {
                // 4. Copy appointments to Google Calendar events.
                $appointments = Appointment::query()
                    ->where( 'staff_id', $this->client->staff()->getId() )
                    ->where( 'created_from', 'bookly' )
                    ->whereGt( 'end_date', $start_date->format( 'Y-m-d H:i:s' ) )
                    ->whereLte( 'end_date', $end_date->format( 'Y-m-d H:i:s' ) )
                    ->find();
                /** @var Appointment $appointment */
                foreach ( $appointments as $appointment ) {
                    if ( ! $this->syncAppointment( $appointment->setGoogleEventId( null ) ) ) {
                        return false;
                    }
                }

                return true;
            }

        }

        return false;
    }

    /**
     * Find associated appointment for given Google Calendar event.
     *
     * @param \BooklyGoogle_Service_Calendar_Event $event
     * @return Appointment|false
     */
    protected function _findAppointmentForEvent( \BooklyGoogle_Service_Calendar_Event $event )
    {
        $appointment = new Appointment();
        $appointment->loadBy( array(
            'staff_id' => $this->client->staff()->getId(),
            'google_event_id' => $event->getId(),
        ) );

        return $appointment->isLoaded() ? $appointment : false;
    }

    /**
     * Copy Google Calendar events to appointments starting from given date.
     *
     * @param DatePoint $start_date
     * @param DatePoint $end_date
     * @return bool
     */
    protected function _copyEventsToAppointments( DatePoint $start_date, DatePoint $end_date )
    {
        try {
            $params = array(
                'singleEvents' => true,
                'maxResults' => self::EVENTS_PER_REQUEST,
                'timeMin' => $start_date->format( \DateTime::RFC3339 ),
                'timeMax' => $end_date->format( \DateTime::RFC3339 ),
            );

            do {
                // Fetch events.
                $events = $this->client->service()->events->listEvents( $this->_getCalendarId(), $params );

                /** @var \BooklyGoogle_Service_Calendar_Event $event */
                foreach ( $events->getItems() as $event ) {
                    if ( ! $this->_isTransparentEvent( $event ) ) {
                        $appointment = new Appointment();
                        $this->_populateAppointment( $appointment, $event )->save();
                    }
                }
                $params['pageToken'] = $events->getNextPageToken();

            } while ( $params['pageToken'] !== null );

            // Save next sync token.
            $this->_setSyncToken( $events->getNextSyncToken() )->_saveData();

            return true;

        } catch ( \Exception $e ) {
            $this->client->addError( $e->getMessage() );
        }

        return false;
    }

    /**
     * Delete Google Calendar events created from Bookly starting from given date.
     *
     * @param DatePoint $start_date
     * @param DatePoint $end_date
     * @return bool
     */
    protected function _deleteEventsCreatedFromBookly( DatePoint $start_date, DatePoint $end_date )
    {
        try {
            $params = array(
                'privateExtendedProperty' => 'staff_token=' . $this->client->staff()->getICalendarToken(),
                'maxResults' => self::EVENTS_PER_REQUEST,
                'timeMin' => $start_date->format( \DateTime::RFC3339 ),
                'timeMax' => $end_date->format( \DateTime::RFC3339 ),
            );

            do {
                // Find events.
                $events = $this->client->service()->events->listEvents( $this->_getCalendarId(), $params );

                /** @var \BooklyGoogle_Service_Calendar_Event $event */
                foreach ( $events->getItems() as $event ) {
                    $this->client->service()->events->delete( $this->_getCalendarId(), $event->getId() );
                }

                $params['pageToken'] = $events->getNextPageToken();

            } while ( $params['pageToken'] !== null );

            return true;

        } catch ( \Exception $e ) {
            $this->client->addError( $e->getMessage() );
        }

        return false;
    }

    /**
     * Populate appointment with data from given Google Calendar event.
     *
     * @param Appointment $appointment
     * @param \BooklyGoogle_Service_Calendar_Event $event
     * @return Appointment
     * @throws
     */
    protected function _populateAppointment( Appointment $appointment, \BooklyGoogle_Service_Calendar_Event $event )
    {
        // Get start/end dates of event and transform them into WP time zone
        // (Google doesn't transform all day events into our time zone).
        $event_start = $event->getStart();
        $event_end = $event->getEnd();

        if ( $event_start->getDateTime() === null ) {
            // All day event.
            $event_start_date = new \DateTime( $event_start->getDate(), new \DateTimeZone( $this->_getTimeZone() ) );
            $event_end_date = new \DateTime( $event_end->getDate(), new \DateTimeZone( $this->_getTimeZone() ) );
        } else {
            // Regular event.
            $event_start_date = new \DateTime( $event_start->getDateTime() );
            $event_end_date = new \DateTime( $event_end->getDateTime() );
        }

        // Convert to WP time zone.
        $event_start_date = date_timestamp_set( date_create( Config::getWPTimeZone() ), $event_start_date->getTimestamp() );
        $event_end_date = date_timestamp_set( date_create( Config::getWPTimeZone() ), $event_end_date->getTimestamp() - $appointment->getExtrasDuration() );

        // Populate appointment.
        if ( ! $appointment->isLoaded() ) {
            $appointment->setCreatedFrom( 'google' );
        }
        if ( $appointment->getCreatedFrom() == 'google' ) {
            if ( get_option( 'bookly_gc_full_sync_titles', 1 ) ) {
                $appointment->setCustomServiceName( $event->getSummary() );
            } else if ( $appointment->getCustomServiceName() === null ) {
                $appointment->setCustomServiceName( __( 'Google Calendar event', 'bookly' ) );
            }
        }
        $appointment
            ->setStaff( $this->client->staff() )
            ->setStartDate( $event_start_date->format( 'Y-m-d H:i:s' ) )
            ->setEndDate( $event_end_date->format( 'Y-m-d H:i:s' ) )
            ->setGoogleEventId( $event->getId() )
            ->setGoogleEventETag( $event->getEtag() );

        return $appointment;
    }

    /**
     * Get sync token for Google Calendar.
     *
     * @return string|null
     */
    protected function _getSyncToken()
    {
        return $this->client->data()->calendar->sync_token;
    }

    /**
     * Set sync token for Google Calendar.
     *
     * @param string $sync_token
     * @return $this
     */
    protected function _setSyncToken( $sync_token )
    {
        $this->client->data()->calendar->sync_token = $sync_token;

        return $this;
    }

    /**
     * Get channel ID for watched resource.
     *
     * @return string|null
     */
    public function _getChannelId()
    {
        return $this->client->data()->channel->id;
    }

    /**
     * Set channel ID for watched resource.
     *
     * @param string $id
     * @return $this
     */
    protected function _setChannelId( $id )
    {
        $this->client->data()->channel->id = $id;

        return $this;
    }

    /**
     * Get channel resource ID.
     *
     * @return string
     */
    protected function _getChannelResourceId()
    {
        return $this->client->data()->channel->resource_id;
    }

    /**
     * Set channel resource ID.
     *
     * @param string $resource_id
     * @return $this
     */
    protected function _setChannelResourceId( $resource_id )
    {
        $this->client->data()->channel->resource_id = $resource_id;

        return $this;
    }

    /**
     * Get channel expiration timestamp.
     *
     * @return int
     */
    protected function _getChannelExpiration()
    {
        return $this->client->data()->channel->expiration;
    }

    /**
     * Set channel expiration timestamp.
     *
     * @param int $expiration
     * @return $this
     */
    protected function _setChannelExpiration( $expiration )
    {
        $this->client->data()->channel->expiration = $expiration;

        return $this;
    }

    /**
     * Tells whether there is a configured notification channel.
     *
     * @return bool
     */
    protected function _hasChannel()
    {
        return $this->_getChannelId() != '';
    }

    /**
     * Tells whether there is a sync token.
     *
     * @return bool
     */
    protected function _hasSyncToken()
    {
        return $this->_getSyncToken() != '';
    }

    /**
     * Save current Google data to associated staff.
     *
     * @return $this
     */
    protected function _saveData()
    {
        $this->client->staff()->setGoogleData( $this->client->data()->toJson() )->save();

        return $this;
    }

    /**
     * Tells whether given event has been deleted from Google Calendar.
     *
     * @param \BooklyGoogle_Service_Calendar_Event $event
     * @return bool
     */
    protected function _isDeletedEvent( \BooklyGoogle_Service_Calendar_Event $event )
    {
        return $event->getStatus() == 'cancelled';
    }
}