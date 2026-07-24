<?php
namespace BooklyPro\Lib\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\Entities\Appointment;
use Bookly\Lib\Entities\Customer;
use Bookly\Lib\Entities\Notification;
use Bookly\Lib\Entities\Payment;
use Bookly\Lib\Entities\Staff;
use Bookly\Lib\Slots\DatePoint;
use Bookly\Lib\Utils\Common;
use Bookly\Lib\DataHolders\Booking as DataHolders;
use Bookly\Lib\Notifications\Assets\Item;
use Bookly\Lib\Notifications\Base\Sender;
use BooklyPro\Backend\Components\License;
use BooklyPro\Lib\Bbb\BigBlueButton;
use BooklyPro\Lib\Config;
use BooklyPro\Lib\Zoom;
use BooklyPro\Lib\Entities;
use BooklyEvents\Lib\Entities\Event;

class Shared extends BooklyLib\Proxy\Shared
{
    /**
     * Add Pro's in-page tabs to the fullscreen header sidebar submenus (Settings tabs positioned to
     * match the in-page menu, plus the Email logs tab on the Email notifications page).
     *
     * @param array $submenus
     * @return array
     */
    public static function buildHeaderSubmenus( $submenus )
    {
        if ( isset( $submenus['bookly-settings'] ) ) {
            // Pro add-on strings live in the 'bookly' text-domain (Pro superset), not the core slug.
            // Just append the tabs — final ordering is imposed centrally in Common::getSubmenus().
            $submenus['bookly-settings'][] = array( 'label' => __( 'Google Calendar', 'bookly' ),  'tab' => 'google_calendar',  'badge' => '' );
            $submenus['bookly-settings'][] = array( 'label' => 'WooCommerce',                      'tab' => 'woo_commerce',     'badge' => '' );
            $submenus['bookly-settings'][] = array( 'label' => 'Facebook',                         'tab' => 'facebook',         'badge' => '' );
            $submenus['bookly-settings'][] = array( 'label' => __( 'Cart', 'bookly' ),             'tab' => 'cart',             'badge' => '' );
            $submenus['bookly-settings'][] = array( 'label' => __( 'Online Meetings', 'bookly' ),  'tab' => 'online_meetings',  'badge' => '' );
            $submenus['bookly-settings'][] = array( 'label' => __( 'User Permissions', 'bookly' ), 'tab' => 'user_permissions', 'badge' => '' );
            $submenus['bookly-settings'][] = array( 'label' => __( 'Additional', 'bookly' ),       'tab' => 'additional',       'badge' => '' );
        }

        if ( isset( $submenus['bookly-notifications'] ) ) {
            // The Email logs tab sits between Notifications and Settings, as in the in-page menu.
            array_splice( $submenus['bookly-notifications'], 1, 0, array(
                array( 'label' => __( 'Email logs', 'bookly' ), 'tab' => 'logs', 'badge' => '' ),
            ) );
        }

        // Appearance: Pro turns the page into a grid of booking-form types, each a distinct
        // ?page=bookly-appearance&<form-type> view — expose them as a submenu (other add-ons, e.g.
        // Events, append their own form here too). Custom-URL items use 'url' + the bare 'flag'.
        $forms = array(
            Entities\Form::TYPE_SEARCH_FORM       => __( 'Search form', 'bookly' ),
            Entities\Form::TYPE_TAGS_FORM         => __( 'Tags form', 'bookly' ),
            Entities\Form::TYPE_SERVICES_FORM     => __( 'Services form', 'bookly' ),
            Entities\Form::TYPE_STAFF_FORM        => __( 'Staff form', 'bookly' ),
            Entities\Form::TYPE_BOOKLY_FORM       => __( 'Step by step form', 'bookly' ),
            Entities\Form::TYPE_CHECKOUT_FORM     => __( 'Checkout form', 'bookly' ),
            Entities\Form::TYPE_CANCELLATION_FORM => __( 'Cancellation form', 'bookly' ),
        );
        if ( ! isset( $submenus['bookly-appearance'] ) ) {
            $submenus['bookly-appearance'] = array();
        }
        foreach ( $forms as $flag => $title ) {
            $submenus['bookly-appearance'][] = array(
                'label' => $title,
                'url'   => admin_url( 'admin.php?page=bookly-appearance&' . $flag ),
                'flag'  => $flag,
                'badge' => '',
            );
        }

        return $submenus;
    }

    /**
     * @inheritDoc
     */
    public static function doHourlyRoutine()
    {
        self::updateCAStatus();
        self::cleanEmailLogs();
        self::removeUnusedTags();
        // Apple Calendar 2-way sync.
        BooklyLib\Proxy\AppleCalendar::reSync();
    }

    /**
     * @inheritDoc
     */
    public static function doDailyRoutine()
    {
        self::handlePurchaseReminder();

        $remaining_days = Config::graceRemainingDays();
        if ( $remaining_days !== false ) {
            $today = (int) ( current_time( 'timestamp' ) / DAY_IN_SECONDS );
            $grace_notifications = get_option( 'bookly_grace_notifications' );
            if ( $today != $grace_notifications['sent'] ) {
                $admin_emails = Common::getAdminEmails();
                if ( ! empty( $admin_emails ) ) {
                    $grace_notifications['sent'] = $today;
                    if ( $remaining_days === 0 && ( $grace_notifications['bookly'] != 1 ) ) {
                        $subject = __( 'Please verify your Bookly Pro license', 'bookly' );
                        $message = __( 'Bookly Pro will need to verify your license to restore access to your bookings. Please enter the purchase code in the administrative panel.', 'bookly' );
                        foreach ( $admin_emails as $email ) {
                            if ( BooklyLib\Utils\Mail::send( $email, $subject, $message ) ) {
                                $grace_notifications['bookly'] = 1;
                                update_option( 'bookly_grace_notifications', $grace_notifications );
                            }
                        }
                    } elseif ( in_array( $remaining_days, array( 13, 7, 1 ) ) ) {
                        $days_text = sprintf( _n( '%d day', '%d days', $remaining_days, 'bookly' ), $remaining_days );
                        $replace = array( '{days}' => $days_text, '{site_url}' => site_url() );
                        $subject = __( 'Please verify your Bookly Pro license', 'bookly' );
                        $message = strtr( __( 'Please verify Bookly Pro license in the administrative panel on {site_url}.', 'bookly' ) . ' ' . __( 'If you do not verify the license within {days}, access to your bookings will be disabled.', 'bookly' ), $replace );
                        foreach ( $admin_emails as $email ) {
                            if ( BooklyLib\Utils\Mail::send( $email, $subject, $message ) ) {
                                update_option( 'bookly_grace_notifications', $grace_notifications );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function renderAdminNotices( $bookly_page )
    {
        License\Components::renderLicenseRequired( $bookly_page );
        License\Components::renderLicenseNotice( $bookly_page );
        License\Components::renderPurchaseReminder( $bookly_page );
    }

    /**
     * @inheritDoc
     */
    public static function prepareCaSeStQuery( BooklyLib\Query $query )
    {
        if ( ! BooklyLib\Config::customerGroupsActive() ) {
            $query->where( 's.visibility', BooklyLib\Entities\Service::VISIBILITY_PUBLIC );
        }

        $query->addSelect( 's.tags' );

        return $query;
    }

    /**
     * @inheritDoc
     */
    public static function prepareStaffServiceQuery( BooklyLib\Query $query )
    {
        $query
            ->addSelect( 'spo.position' )
            ->leftJoin( 'StaffPreferenceOrder', 'spo', 'spo.service_id = ss.service_id AND spo.staff_id = ss.staff_id', '\BooklyPro\Lib\Entities' );

        return $query;
    }

    /**
     * @inheritDoc
     */
    public static function prepareStatement( $value, $statement, $table )
    {
        $tables = array( 'Service', 'Staff' );
        $key = $table . '-' . $statement;
        if ( in_array( $table, $tables ) ) {
            if ( ! self::hasInCache( $key ) ) {
                preg_match( '/(?:(\w+)\()?\W*(?:(\w+)\.(\w+)|(\w+))/', $statement, $match );

                $count = count( $match );
                if ( $count == 4 ) {
                    $field = $match[3];
                } elseif ( $count == 5 ) {
                    $field = $match[4];
                }

                switch ( $field ) {
                    case 'category_id':
                    case 'padding_left':
                    case 'padding_right':
                    case 'staff_preference':
                    case 'staff_preference_settings':
                        self::putInCache( $key, $statement );
                        break;
                }
            }
        } else {
            self::putInCache( $key, $value );
        }

        return self::getFromCache( $key );
    }

    /**
     * @inheritDoc
     */
    public static function prepareNotificationTypes( array $types, $gateway )
    {
        if ( $gateway == 'email' ) {
            $types[] = Notification::TYPE_APPOINTMENT_REMINDER;
            $types[] = Notification::TYPE_LAST_CUSTOMER_APPOINTMENT;
            $types[] = Notification::TYPE_STAFF_DAY_AGENDA;
        }
        $types[] = Notification::TYPE_NEW_BOOKING_COMBINED;
        $types[] = Notification::TYPE_CUSTOMER_BIRTHDAY;
        $types[] = Notification::TYPE_CUSTOMER_NEW_WP_USER;
        $types[] = Notification::TYPE_STAFF_NEW_WP_USER;

        return $types;
    }

    /**
     * @inheritDoc
     */
    public static function prepareTableColumns( $columns, $table )
    {
        switch ( $table ) {
            case BooklyLib\Utils\Tables::APPOINTMENTS:
                $columns['customer_address'] = esc_attr__( 'Customer address', 'bookly' );
                $columns['customer_birthday'] = esc_attr__( 'Customer birthday', 'bookly' );
                $columns['online_meeting'] = esc_attr__( 'Online meeting', 'bookly' );
                break;

            case BooklyLib\Utils\Tables::CUSTOMERS:
                $columns['tags'] = esc_attr__( 'Tags', 'bookly' );
                $columns['address'] = esc_attr__( 'Address', 'bookly' );
                $columns['facebook'] = 'Facebook';
                break;

            case BooklyLib\Utils\Tables::SERVICES:
                $columns['tags'] = esc_attr__( 'Tags', 'bookly' );
                $columns['online_meetings'] = esc_attr__( 'Online meetings', 'bookly' );
                break;

            case BooklyLib\Utils\Tables::STAFF_MEMBERS:
                $columns['category_name'] = esc_attr__( 'Category', 'bookly' );
                break;

        }

        return $columns;
    }

    /**
     * @param Appointment|Event $entity
     * @inheritDoc
     */
    public static function buildOnlineMeetingUrl( $default, BooklyLib\Base\Entity $entity, $customer = null )
    {
        if ( ( $entity instanceof Appointment ) || ( $entity instanceof Event ) ) {
            switch ( $entity->getOnlineMeetingProvider() ) {
                case 'zoom':
                    $default = 'https://zoom.us/j/' . $entity->getOnlineMeetingId();
                    break;
                case 'google_meet':
                case 'jitsi':
                case 'teams':
                    $default = $entity->getOnlineMeetingId();
                    break;
                case 'bbb':
                    $bbb = new BigBlueButton( $entity->getOnlineMeetingId() );
                    $default = $bbb->getJoinMeetingClientUrl( $customer );
                    break;
            }
        }

        return $default;
    }

    /**
     * @param Appointment|Event $entity
     * @inheritDoc
     */
    public static function buildOnlineMeetingPassword( $default, BooklyLib\Base\Entity $entity )
    {
        if ( ( $entity instanceof Appointment ) || ( $entity instanceof Event ) ) {
            if ( $entity->getOnlineMeetingProvider() === 'zoom' ) {
                $options = json_decode( $entity->getOnlineMeetingData() ?: '{}', true );

                return isset( $options['password'] ) ? $options['password'] : $default;
            }
        }

        return $default;
    }

    /**
     * @param Appointment|Event $entity
     * @inheritDoc
     */
    public static function buildOnlineMeetingStartUrl( $default, BooklyLib\Base\Entity $entity )
    {
        if ( ( $entity instanceof Appointment ) || ( $entity instanceof Event ) ) {
            switch ( $entity->getOnlineMeetingProvider() ) {
                case 'zoom':
                    $options = json_decode( $entity->getOnlineMeetingData() ?: '{}', true );

                    $default = isset( $options['start_url'] ) ? $options['start_url'] : self::buildOnlineMeetingUrl( $default, $entity, null );
                    break;
                case 'google_meet':
                case 'jitsi':
                case 'teams':
                    $default = self::buildOnlineMeetingUrl( $default, $entity, null );
                    break;
                case 'bbb':
                    $bbb = new BigBlueButton( $entity->getOnlineMeetingId() );
                    $default = $bbb->getCreateMeetingStaffUrl( json_decode( $entity->getOnlineMeetingData(), true ) );
                    break;
            }
        }

        return $default;
    }

    /**
     * @param Appointment|Event $entity
     * @inheritDoc
     */
    public static function buildOnlineMeetingJoinUrl( $default, BooklyLib\Base\Entity $entity, $customer )
    {
        if ( ( $entity instanceof Appointment ) || ( $entity instanceof Event ) ) {
            switch ( $entity->getOnlineMeetingProvider() ) {
                case 'zoom':
                    $options = json_decode( $entity->getOnlineMeetingData() ?: '{}', true );

                    $default = isset( $options['join_url'] ) ? $options['join_url'] : self::buildOnlineMeetingUrl( $default, $entity, null );
                    break;
                case 'google_meet':
                case 'jitsi':
                case 'teams':
                    $default = $entity->getOnlineMeetingId();
                    break;
                case 'bbb':
                    $bbb = new BigBlueButton( $entity->getOnlineMeetingId() );
                    $default = $bbb->getJoinMeetingClientUrl( $customer );
                    break;
            }
        }

        return $default;
    }

    /**
     * @param Appointment|Event $entity
     * @inheritDoc
     */
    public static function syncOnlineMeeting( array $errors, BooklyLib\Base\Entity $entity )
    {
        $meeting_provider = 'off';
        $title = '';
        $staff_id = null;
        $start = null;
        $duration = null;
        $modified = $entity->getModified();
        $online_provider_changed = false;

        if ( $entity instanceof Appointment ) {
            $service = BooklyLib\Entities\Service::find( $entity->getServiceId() );
            $meeting_provider = $service ? $service->getOnlineMeetings() : 'off';
            $start = DatePoint::fromStr( $entity->getStartDate() );
            $end = DatePoint::fromStr( $entity->getEndDate() );
            $duration = $end->diff( $start ) + $entity->getExtrasDuration();
            $title = $service ? $service->getTitle() : '';
            $staff_id = $entity->getStaffId();
            $online_provider_changed = array_key_exists( 'online_meetings', $modified );
        } elseif ( $entity instanceof Event ) {
            $meeting_provider = $entity->getOnlineMeetingProvider();
            $start = DatePoint::fromStr( $entity->getStartDate() );
            $duration = DatePoint::fromStr( $entity->getEndDate() )->diff( $start );
            $title = $entity->getTitle();
            $staff_id = $entity->getOnlineMeetingStaffId();
            $online_provider_changed = array_key_exists( 'online_meeting_provider', $modified );
        } else {
            return $errors;
        }

        if ( $start ) {
            // Zoom.
            if ( $meeting_provider == 'zoom' ) {
                $zoom = new Zoom\Meetings( $staff_id ? BooklyLib\Entities\Staff::find( $staff_id ) : null );
                $data = array(
                    'topic' => $title,
                    'start_time' => $start->toTz( 'UTC' )->format( 'Y-m-d\TH:i:s\Z' ),
                    'duration' => (int) ( $duration / 60 ),  // duration in minutes
                );
                if ( $online_provider_changed || ! $entity->getOnlineMeetingId() ) {
                    $res = $zoom->create( $data );
                    if ( $res ) {
                        $entity
                            ->setOnlineMeetingProvider( 'zoom' )
                            ->setOnlineMeetingId( $res['id'] )
                            ->setOnlineMeetingData( json_encode( $res ) )
                            ->save();
                    }
                } else {
                    $res = $zoom->update( $entity->getOnlineMeetingId(), $data );
                }

                if ( ! $res ) {
                    $errors = array_merge( $errors, array_map( function( $e ) {
                        return 'Zoom: ' . $e;
                    }, $zoom->errors() ) );
                }
            } elseif ( $meeting_provider == 'jitsi' ) {
                if ( $online_provider_changed || ! $entity->getOnlineMeetingId() ) {
                    $token = md5( uniqid( time(), true ) );
                    $url = sprintf(
                        '%s/%s-%s-%s',
                        get_option( 'bookly_jitsi_server' ),
                        substr( $token, 0, 3 ),
                        substr( $token, 3, 4 ),
                        substr( $token, 7, 3 )
                    );
                    $entity
                        ->setOnlineMeetingProvider( 'jitsi' )
                        ->setOnlineMeetingId( $url )
                        ->save();
                }
            } elseif ( $meeting_provider == 'bbb' ) {
                if ( $online_provider_changed || ! $entity->getOnlineMeetingId() ) {
                    $entity
                        ->setOnlineMeetingProvider( 'bbb' )
                        ->setOnlineMeetingId( BooklyLib\Utils\Common::generateToken( get_class( $entity ), 'online_meeting_id' ) )
                        ->setOnlineMeetingData( json_encode( array(
                            'staff_pw' => wp_generate_password( 8, false ),
                            'client_pw' => wp_generate_password( 8, false ),
                        ) ) )
                        ->save();
                }
            } elseif ( $meeting_provider === 'off' ) {
                if ( $entity->getOnlineMeetingId() || $entity->getOnlineMeetingProvider() ) {
                    Local::deleteOnlineMeeting( $entity );

                    $entity
                        ->setOnlineMeetingProvider( $entity instanceof Appointment ? null : 'off' )
                        ->setOnlineMeetingId( null )
                        ->setOnlineMeetingData( null )
                        ->save();
                }
            }
        }

        return $errors;
    }

    /**
     * @inheritDoc
     */
    public static function prepareAppointmentCodes( $codes, $appointment )
    {
        $customer = new BooklyLib\Entities\Customer();
        $customer->setFullName( 'Client' );
        $codes['online_meeting_url'] = BooklyLib\Proxy\Shared::buildOnlineMeetingUrl( '', $appointment, $customer );
        $codes['online_meeting_password'] = BooklyLib\Proxy\Shared::buildOnlineMeetingPassword( '', $appointment );
        $codes['online_meeting_start_url'] = BooklyLib\Proxy\Shared::buildOnlineMeetingStartUrl( '', $appointment );
        $codes['online_meeting_join_url'] = BooklyLib\Proxy\Shared::buildOnlineMeetingJoinUrl( '', $appointment, $customer );
        $codes['on_waiting_list'] = BooklyLib\Config::waitingListActive()
            ? BooklyLib\Entities\CustomerAppointment::query( 'ca' )
                ->where( 'ca.appointment_id', $appointment->getId() )
                ->where( 'status', BooklyLib\Entities\CustomerAppointment::STATUS_WAITLISTED )
                ->count()
            : 0;

        $staff = BooklyLib\Entities\Staff::find( $appointment->getStaffId() );
        $staff_category = $staff->getCategoryId() ? Entities\StaffCategory::find( $staff->getCategoryId() ) : null;
        $codes['staff_category_name'] = $staff_category ? $staff_category->getTranslatedName() : '';
        $codes['staff_category_info'] = $staff_category ? $staff_category->getTranslatedInfo() : '';
        $codes['staff_category_image'] = ( $staff_category && ( $url = $staff_category->getImageUrl() ) ) ? '<img src="' . $url . '"/>' : '';

        return $codes;
    }

    /**
     * @inheritDoc
     */
    public static function prepareCustomerAppointmentCodes( $codes, $customer_appointment, $format )
    {
        $customer = BooklyLib\Entities\Customer::find( $customer_appointment->getCustomerId() );
        $codes['status'] = BooklyLib\Entities\CustomerAppointment::statusToString( $customer_appointment->getStatus() );
        $codes['client_address'] = $customer->getAddress();
        $codes['client_full_birthday'] = $customer->getBirthday() ? BooklyLib\Utils\DateTime::formatDate( $customer->getBirthday() ) : '';
        $codes['client_birthday'] = $customer->getBirthday() ? date_i18n( 'F j', strtotime( $customer->getBirthday() ) ) : '';

        return $codes;
    }

    /**
     * @inheritDoc
     */
    public static function prepareL10nGlobal( array $obj )
    {
        $plugins = apply_filters( 'bookly_plugins', array() );
        unset ( $plugins['bookly-responsive-appointment-booking-tool'] );
        foreach ( array_keys( $plugins ) as $addon ) {
            $obj['addons'][] = substr( $addon, 13 );
        }

        return $obj;
    }

    /**
     * @inheritDoc
     */
    public static function prepareNotificationTitles( array $titles )
    {
        $titles['new_booking_combined'] = __( 'New booking combined notification', 'bookly' );
        $titles['customer_new_wp_user'] = __( 'New customer\'s WordPress user login details', 'bookly' );
        $titles['staff_new_wp_user'] = __( 'New staff\'s WordPress user login details', 'bookly' );

        return $titles;
    }

    /**
     * @inerhitDoc
     */
    public static function preparePaymentImage( $url, $gateway )
    {
        return $gateway === Payment::TYPE_PAYPAL
            ? plugins_url( 'frontend/resources/images/paypal.svg', \BooklyPro\Lib\Plugin::getMainFile() )
            : $url;
    }

    /**
     * @inerhitDoc
     */
    public static function prepareCartInfo( BooklyLib\CartInfo $cart_info, BooklyLib\CartItem $item )
    {
        if ( $item->getType() === BooklyLib\CartItem::TYPE_CHILD_PAYMENT ) {
            $payment = Payment::find( $item->getPaymentId() );
            if ( $payment ) {
                $amount = $payment->getTotal();
                $cart_info->setSubtotal( $amount );
                $cart_info->setDeposit( $amount );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function unpaidPayments( $payment_ids )
    {
        /** @var Payment[] $payments */
        $payments = Payment::query()
            ->whereIn( 'id', $payment_ids )
            ->where( 'status', Payment::STATUS_PENDING )
            ->find();
        foreach ( $payments as $payment ) {
            BooklyLib\Payment\Proxy\Shared::rollbackPayment( $payment );
        }

        return $payment_ids;
    }

    protected static function handlePurchaseReminder()
    {
        if ( get_option( 'bookly_pr_show_time' ) < time() ) {
            update_option( 'bookly_pr_show_time', time() + 7776000 );
            if ( get_option( 'bookly_pro_purchase_code' ) == '' ) {
                foreach ( get_users( array( 'role' => 'administrator' ) ) as $admin ) {
                    update_user_meta( $admin->ID, 'bookly_show_purchase_reminder', '1' );
                }
            }
        }
    }

    /**
     * Update the statuses of customer appointments.
     */
    protected static function updateCAStatus()
    {
        if ( get_option( 'bookly_auto_change_status' ) ) {
            $statuses = BooklyLib\Entities\CustomerAppointment::getStatuses();
            $status_from = get_option( 'bookly_auto_change_status_from' );
            $status_to = get_option( 'bookly_auto_change_status_to' );
            if ( $status_from != $status_to
                && in_array( $status_from, $statuses, true )
                && in_array( $status_to, $statuses, true )
            ) {

                $records = BooklyLib\Entities\CustomerAppointment::query( 'ca' )
                    ->select( 'ca.*, DATE_ADD(a.end_date, INTERVAL a.extras_duration SECOND) >= DATE_SUB(NOW(), INTERVAL 2 DAY) AS with_notification' )
                    ->leftJoin( 'Appointment', 'a', 'a.id = ca.appointment_id' )
                    ->where( 'status', $status_from )
                    ->whereRaw( 'DATE_ADD(a.end_date, INTERVAL a.extras_duration SECOND) < \'%s\'', array( current_time( 'mysql' ) ) )
                    ->fetchArray();

                if ( $records ) {
                    $notifications = Sender::getNotifications( BooklyLib\Entities\Notification::TYPE_CUSTOMER_APPOINTMENT_STATUS_CHANGED );
                    $appointments_ids = array();
                    // Send notifications.
                    foreach ( $records as $record ) {
                        $ca = new BooklyLib\Entities\CustomerAppointment( $record );
                        $ca->setStatus( $status_to )->save();
                        $appointments_ids[] = $ca->getAppointmentId();
                        if ( $record['with_notification'] && ( $notifications['client'] || $notifications['staff'] ) ) {
                            $item = DataHolders\Item::collect( $ca );
                            $codes = new Item\Codes( DataHolders\Order::createFromItem( $item ) );
                            if ( $notifications['client'] ) {
                                $customer = Customer::find( $ca->getCustomerId() );
                                $codes->prepareForItem( $item, 'client' );
                                foreach ( $notifications['client'] as $notification ) {
                                    if ( $notification->matchesItemForClient( $item ) ) {
                                        Sender::sendToClient( $customer, $notification, $codes );
                                    }
                                }
                            }
                            if ( $notifications['staff'] ) {
                                $staff = Staff::find( $item->getAppointment()->getStaffId() );
                                // Notify staff and/or administrators.
                                foreach ( $notifications['staff'] as $notification ) {
                                    foreach ( $item->getItems() as $i ) {
                                        if ( $notification->matchesItemForStaff( $i ) ) {
                                            $codes->prepareForItem( $i, 'staff' );
                                            Sender::sendToStaff( $staff, $notification, $codes );
                                            Sender::sendToAdmins( $notification, $codes );
                                            Sender::sendToCustom( $notification, $codes );
                                        }
                                    }
                                }
                            }
                        }
                    }
                    list( $sync ) = BooklyLib\Config::syncCalendars();
                    if ( $sync ) {
                        $appointments = Appointment::query()
                            ->whereIn( 'id', $appointments_ids )
                            ->find();
                        foreach ( $appointments as $appointment ) {
                            BooklyLib\Utils\Common::syncWithCalendars( $appointment );
                        }
                    }
                }
            }
        }
    }

    protected static function cleanEmailLogs()
    {
        Entities\EmailLog::query()->delete()
            ->whereRaw( 'created_at < DATE(NOW() - INTERVAL %s DAY)', array( get_option( 'bookly_email_logs_expire', 30 ) ) )
            ->execute();
    }

    /**
     * Delete tags that are no longer in use.
     */
    protected static function removeUnusedTags()
    {
        // Delete unused customers tags
        $exists = Entities\Tag::query()->where( 'type', Entities\Tag::TYPE_CUSTOMER )->fetchCol( 'tag' );
        $db_tags = BooklyLib\Entities\Customer::query()
            ->whereNot( 'tags', null )
            ->whereNot( 'tags', '[]' )
            ->fetchCol( 'tags' );

        // Concatenate all used tags into one string/array
        $all_tags = '';
        foreach ( $db_tags as $tags ) {
            $all_tags .= substr( $tags, 1, -1 ) . ',';
        }
        $all_tags = json_decode( '[' . rtrim( $all_tags, ',' ) . ']', false );

        $used_tags = array_values( $all_tags );
        $delete = array_diff( $used_tags, $exists );
        if ( $delete ) {
            Entities\Tag::query()
                ->delete()
                ->where( 'type', Entities\Tag::TYPE_CUSTOMER )
                ->whereIn( 'tag', $delete )
                ->execute();
        }

        // Delete unused services tags
        $exists = Entities\Tag::query()->where( 'type', Entities\Tag::TYPE_SERVICE )->fetchCol( 'tag' );
        $db_tags = BooklyLib\Entities\Service::query()
            ->whereNot( 'tags', null )
            ->whereNot( 'tags', '[]' )
            ->fetchCol( 'tags' );

        // Concatenate all used tags into one string/array
        $all_tags = '';
        foreach ( $db_tags as $tags ) {
            $all_tags .= substr( $tags, 1, -1 ) . ',';
        }
        $all_tags = json_decode( '[' . rtrim( $all_tags, ',' ) . ']', false );

        $used_tags = array_values( $all_tags );
        $delete = array_diff( $used_tags, $exists );
        if ( $delete ) {
            Entities\Tag::query()
                ->delete()
                ->where( 'type', Entities\Tag::TYPE_SERVICE )
                ->whereIn( 'tag', $delete )
                ->execute();
        }
    }

}