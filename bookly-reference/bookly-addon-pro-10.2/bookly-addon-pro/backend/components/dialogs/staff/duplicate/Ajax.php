<?php
namespace BooklyPro\Backend\Components\Dialogs\Staff\Duplicate;

use Bookly\Lib as BooklyLib;
use Bookly\Backend\Components\Dialogs\Staff\Proxy;
use Bookly\Lib\Entities;

class Ajax extends BooklyLib\Base\Ajax
{
    protected static function permissions()
    {
        return array( '_default' => array( 'admin' ) );
    }

    public static function duplicateStaff()
    {
        $staff_id = self::parameter( 'staff_id' );
        $duplicate_name = self::parameter( 'duplicate_name' );
        $original_staff = Entities\Staff::find( $staff_id );

        try {
            $new_staff = clone $original_staff;

            $new_staff
                ->setId( null )
                ->setWpUserId( null )
                ->setFullName( $duplicate_name )
                ->setEmail( '' )
                ->setPhone( '' )
                ->setVisibility( 'private' )
                ->setGoogleData( '' )
                ->setOutlookData( '' )
                ->setZoomOAuthToken( '' )
                ->setICalendarToken( '' )
                ->setColor( null );

            // Save the new staff member
            $new_staff->save();
            $new_staff_id = $new_staff->getId();

            // Duplicate schedule items
            $schedule_items = $original_staff->getScheduleItems();
            foreach ( $schedule_items as $schedule_item ) {
                $new_schedule_item = Entities\StaffScheduleItem::query()
                    ->where( 'staff_id', $new_staff_id )
                    ->where( 'location_id', $schedule_item->getLocationId() )
                    ->where( 'day_index', $schedule_item->getDayIndex() )
                    ->findOne();
                if ( ! $new_schedule_item ) {
                    $new_schedule_item = new Entities\StaffScheduleItem();
                }
                $new_schedule_item
                    ->setStaffId( $new_staff_id )
                    ->setLocationId( $schedule_item->getLocationId() )
                    ->setDayIndex( $schedule_item->getDayIndex() )
                    ->setStartTime( $schedule_item->getStartTime() )
                    ->setEndTime( $schedule_item->getEndTime() )
                    ->save();

                $new_schedule_item_id = $new_schedule_item->getId();

                /** @var Entities\ScheduleItemBreak[] $schedule_breaks */
                $schedule_breaks = Entities\ScheduleItemBreak::query()
                    ->where( 'staff_schedule_item_id', $schedule_item->getId() )
                    ->find();

                foreach ( $schedule_breaks as $break ) {
                    $new_break = new Entities\ScheduleItemBreak();
                    $new_break
                        ->setStaffScheduleItemId( $new_schedule_item_id )
                        ->setStartTime( $break->getStartTime() )
                        ->setEndTime( $break->getEndTime() )
                        ->save();
                }
            }

            /** @var Entities\StaffService[] $staff_services */
            $staff_services = Entities\StaffService::query()
                ->where( 'staff_id', $staff_id )
                ->find();

            foreach ( $staff_services as $staff_service ) {
                $new_staff_service = new Entities\StaffService();
                $new_staff_service
                    ->setStaffId( $new_staff_id )
                    ->setServiceId( $staff_service->getServiceId() )
                    ->setPrice( $staff_service->getPrice() )
                    ->setDeposit( $staff_service->getDeposit() )
                    ->setCapacityMin( $staff_service->getCapacityMin() )
                    ->setCapacityMax( $staff_service->getCapacityMax() )
                    ->save();
            }

            /** @var Entities\Holiday $holidays */
            $holidays = Entities\Holiday::query()
                ->where( 'staff_id', $staff_id )
                ->find();

            foreach ( $holidays as $holiday ) {
                $new_holiday = new Entities\Holiday();
                $new_holiday
                    ->setStaffId( $new_staff_id )
                    ->setParentId( $holiday->getParentId() )
                    ->setDate( $holiday->getDate() )
                    ->setRepeatEvent( $holiday->getRepeatEvent() )
                    ->save();
            }

            Proxy\Shared::duplicateStaff( $staff_id, $new_staff );

            wp_send_json_success();

        } catch ( \Exception $e ) {
            wp_send_json_error( array( 'message' => __( 'Failed', 'bookly' ) ) );
        }
    }
}