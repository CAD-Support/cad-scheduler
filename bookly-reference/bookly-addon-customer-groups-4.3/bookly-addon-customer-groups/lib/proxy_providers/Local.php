<?php
namespace BooklyCustomerGroups\Lib\ProxyProviders;

use Bookly\Lib as BooklyLib;
use BooklyCustomerGroups\Backend\Modules\CustomerGroups\Page;
use BooklyCustomerGroups\Lib\Entities;

class Local extends BooklyLib\Proxy\CustomerGroups
{
    /**
     * @inheritDoc
     */
    public static function addBooklyMenuItem()
    {
        add_submenu_page(
            'bookly-menu',
            __( 'Customer Groups', 'bookly' ),
            __( 'Customer Groups', 'bookly' ),
            BooklyLib\Utils\Common::getRequiredCapability(),
            Page::pageSlug(),
            function () { Page::render(); }
        );
    }

    /**
     * @inheritDoc
     */
    public static function takeDefaultAppointmentStatus( $status, $group_id )
    {
        $key = $status . '-' . $group_id;
        if ( ! self::hasInCache( $key ) ) {
            $group = self::getGroup( $group_id );

            if ( in_array( $group->getAppointmentStatus(), BooklyLib\Entities\CustomerAppointment::getStatuses() ) ) {
                $status = $group->getAppointmentStatus();
            }

            self::putInCache( $key, $status );
        }

        return self::getFromCache( $key );
    }

    /**
     * @inheritDoc
     */
    public static function prepareDefaultAppointmentStatuses( array $statuses )
    {
        foreach ( Entities\CustomerGroups::query()->fetchCol( 'id' ) as $group_id ) {
            $statuses[ $group_id ] = self::takeDefaultAppointmentStatus( BooklyLib\Config::getDefaultAppointmentStatus(), $group_id );
        }
        $statuses[0] = self::takeDefaultAppointmentStatus( BooklyLib\Config::getDefaultAppointmentStatus(), 0 );

        return $statuses;
    }

    /**
     * @param $group_id
     * @return Entities\CustomerGroups
     */
    public static function getGroup( $group_id )
    {
        $group = new Entities\CustomerGroups();
        if ( $group_id ) {
            $group->load( $group_id );
        }
        if ( ! $group->isLoaded() ) {
            $group_settings = get_option( 'bookly_customer_groups_general_settings', array() );
            $group
                ->setAppointmentStatus( isset( $group_settings['status'] ) ? $group_settings['status'] : '' )
                ->setDiscount( isset( $group_settings['discount'] ) ? $group_settings['discount'] : 0 )
                ->setSkipPayment( isset( $group_settings['skip_payment'] ) ? $group_settings['skip_payment'] : 0 );
        }

        return $group;
    }

    /**
     * @return array
     */
    public static function getGroups() {
        $groups = array();
        $rows = Entities\CustomerGroups::query( 'cg' )
            ->select( 'cg.id, cg.name' )
            ->fetchArray();
        foreach ( $rows as $group ) {
            $groups[ $group['id'] ] = $group['name'];
        }

        return $groups;
    }

    /**
     * @inerhitDoc
     */
    public static function applyFrontendServiceVisibility( BooklyLib\Query $query )
    {
        $user_id = get_current_user_id();
        $customer = new BooklyLib\Entities\Customer();
        if ( ( $user_id > 0 ) && $customer->loadBy( array( 'wp_user_id' => $user_id ) ) ) {
            $sub_query = Entities\CustomerGroupsServices::query()
                ->select( 'service_id' )
                ->where( 'group_id', $customer->getGroupId() );
            $query->whereRaw( 's.type = %s AND s.visibility = %s AND s.id IN (' . $sub_query . ')',
                array(
                    BooklyLib\Entities\Service::TYPE_SIMPLE,
                    BooklyLib\Entities\Service::VISIBILITY_GROUP_BASED,
                ),
                'OR'
            );
        }

        return $query;
    }
}