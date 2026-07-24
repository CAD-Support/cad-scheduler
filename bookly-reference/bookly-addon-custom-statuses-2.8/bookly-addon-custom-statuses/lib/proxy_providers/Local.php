<?php
namespace BooklyCustomStatuses\Lib\ProxyProviders;

use Bookly\Lib as BooklyLib;
use BooklyCustomStatuses\Lib\Entities\CustomStatus;

abstract class Local extends BooklyLib\Proxy\CustomStatuses
{
    /** @var CustomStatus[] */
    protected static $all_statuses;
    /** @var CustomStatus[] */
    protected static $busy_statuses;
    /** @var CustomStatus[] */
    protected static $free_statuses;

    /**
     * @inheritDoc
     */
    public static function getAll()
    {
        if ( self::$all_statuses === null ) {
            self::$all_statuses = CustomStatus::query()->sortBy( 'position' )->indexBy( 'slug' )->find();
        }

        return self::$all_statuses;
    }

    /**
     * @inheritDoc
     */
    public static function prepareAllStatuses( array $statuses )
    {
        if ( self::$all_statuses === null ) {
            self::$all_statuses = CustomStatus::query()->sortBy( 'position' )->indexBy( 'slug' )->find();
        }

        foreach ( self::$all_statuses as $status ) {
            $statuses[] = $status->getSlug();
        }

        return $statuses;
    }

    /**
     * @inheritDoc
     */
    public static function prepareBusyStatuses( array $statuses )
    {
        if ( self::$busy_statuses === null ) {
            self::$busy_statuses = CustomStatus::query()->where( 'busy', 1 )->sortBy( 'position' )->find();
        }

        foreach ( self::$busy_statuses as $status ) {
            $statuses[] = $status->getSlug();
        }

        return $statuses;
    }

    /**
     * @inheritDoc
     */
    public static function prepareFreeStatuses( array $statuses )
    {
        if ( self::$free_statuses === null ) {
            self::$free_statuses = CustomStatus::query()->where( 'busy', 0 )->sortBy( 'position' )->find();
        }

        foreach ( self::$free_statuses as $status ) {
            $statuses[] = $status->getSlug();
        }

        return $statuses;
    }

    /**
     * @inheritDoc
     */
    public static function statusToString( $status )
    {
        if ( self::$all_statuses === null ) {
            self::$all_statuses = CustomStatus::query()->sortBy( 'position' )->indexBy( 'slug' )->find();
        }

        if ( $status && isset ( self::$all_statuses[ $status ] ) ) {
            return self::$all_statuses[ $status ]->getName();
        }

        return $status;
    }

    /**
     * @inheritDoc
     */
    public static function statusToIcon( $status )
    {
        if ( self::$all_statuses === null ) {
            self::$all_statuses = CustomStatus::query()->sortBy( 'position' )->indexBy( 'slug' )->find();
        }

        if ( isset ( self::$all_statuses[ $status ] ) && self::$all_statuses[ $status ]->getBusy() ) {
            return 'far fa-times-circle';
        }

        return 'far fa-check-circle';
    }
}