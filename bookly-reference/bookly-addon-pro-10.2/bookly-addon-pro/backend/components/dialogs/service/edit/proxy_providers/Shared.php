<?php
namespace BooklyPro\Backend\Components\Dialogs\Service\Edit\ProxyProviders;

use Bookly\Backend\Components\Dialogs\Service\Edit\Proxy;
use BooklyPro\Lib;
use Bookly\Lib as BooklyLib;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function enqueueAssetsForServices()
    {
        self::enqueueScripts( array(
            'module' => array( 'js/pro-services.js' => array( 'jquery' ), ),
            'bookly' => array(
                'backend/components/ace/resources/js/ace.js' => array(),
                'backend/components/ace/resources/js/ext-language_tools.js' => array(),
                'backend/components/ace/resources/js/mode-bookly.js' => array(),
                'backend/resources/js/tags.js' => array( 'bookly-pro-services.js' ),
                'backend/components/ace/resources/js/editor.js' => array( 'bookly-pro-services.js' ),
            ),
        ) );

        self::enqueueStyles( array(
            'bookly' => array( 'backend/components/ace/resources/css/ace.css', )
        ) );

        wp_localize_script( 'bookly-pro-services.js', 'BooklyProL10nServiceEditDialog', array(
            'capacity_error' => __( 'Min capacity should not be greater than max capacity.', 'bookly' ),
            'recurrence_error' => __( 'You must select at least one repeat option for recurring services.', 'bookly' ),
            'tags' => array(
                'tagsList' => Lib\Config::getTagList(),
                'colors' => Lib\Config::getTagColors(),
                'label' => __( 'Tags', 'bookly' )
            )
        ) );
    }

    /**
     * @inheritDoc
     */
    public static function prepareUpdateService( array $data )
    {
        // Saving staff preferences for service, when the form is submitted
        /** @var Lib\Entities\StaffPreferenceOrder[] $staff_preferences */
        $staff_preferences = Lib\Entities\StaffPreferenceOrder::query()
            ->where( 'service_id', $data['id'] )
            ->indexBy( 'staff_id' )
            ->find();
        $data['min_time_prior_booking'] = ( isset( $data['min_time_prior_booking'] ) ? $data['min_time_prior_booking'] : '' ) === '' ? null : $data['min_time_prior_booking'];
        $data['min_time_prior_cancel'] = ( isset( $data['min_time_prior_cancel'] ) ? $data['min_time_prior_cancel'] : '' ) === '' ? null : $data['min_time_prior_cancel'];
        if ( array_key_exists( 'positions', $data ) ) {
            foreach ( (array) $data['positions'] as $position => $staff_id ) {
                if ( array_key_exists( $staff_id, $staff_preferences ) ) {
                    $staff_preferences[ $staff_id ]->setPosition( $position )->save();
                } else {
                    $preference = new Lib\Entities\StaffPreferenceOrder();
                    $preference
                        ->setServiceId( $data['id'] )
                        ->setStaffId( $staff_id )
                        ->setPosition( $position )
                        ->save();
                }
            }
        }

        // Staff preference period.
        $data['staff_preference_settings'] = json_encode( array(
            'period' => array(
                'before' => isset( $data['staff_preferred_period_before'] ) ? max( 0, (int) $data['staff_preferred_period_before'] ) : 0,
                'after' => isset( $data['staff_preferred_period_after'] ) ? max( 0, (int) $data['staff_preferred_period_after'] ) : 0,
            ),
            'random' => isset( $data['staff_preferred_random'] ) && (bool) $data['staff_preferred_random'],
        ) );

        if ( $data['gateways'] === 'custom' && isset( $data['gateways_list'] ) ) {
            $data['gateways'] = json_encode( $data['gateways_list'] );
        } else {
            $data['gateways'] = null;
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public static function updateService( array $alert, BooklyLib\Entities\Service $service, array $parameters )
    {
        $removed_tags = $service->getTags() ? json_decode( $service->getTags(), false ) : array();
        if ( ! is_array( $removed_tags ) ) {
            $removed_tags = array();
        }

        if ( isset( $parameters['tags'] ) ) {
            $tags = $parameters['tags']['tags'];
            $tag_colors = $parameters['tags']['colors'];
            $service->setTags( json_encode( $tags, 256 ) )->save();
            foreach ( $tags as $tag ) {
                $tag_exists = Lib\Entities\Tag::query()
                    ->where( 'tag', $tag )
                    ->where( 'type', Lib\Entities\Tag::TYPE_SERVICE )
                    ->count();
                if ( ! $tag_exists ) {
                    $new_tag = new Lib\Entities\Tag();
                    $new_tag
                        ->setTag( $tag )
                        ->setColorId( isset( $tag_colors[ $tag ] ) ? $tag_colors[ $tag ] : 0 )
                        ->setType( Lib\Entities\Tag::TYPE_SERVICE )
                        ->save();
                }
                $key = array_search( $tag, $removed_tags, true );
                if ( $key !== false ) {
                    unset( $removed_tags[ $key ] );
                }
            }
        } else {
            $service->setTags( null );
        }

        // Remove unused tags from the database.
        foreach ( $removed_tags as $tag ) {
            $tag_exists = BooklyLib\Entities\Service::query()
                ->whereNot( 'id', $service->getId() )
                ->whereNot( 'tags', null )
                ->whereLike( 'tags', '%"' . $tag . '"%' )
                ->limit( 1 )
                ->count();
            if ( ! $tag_exists ) {
                Lib\Entities\Tag::query()
                    ->where( 'type', Lib\Entities\Tag::TYPE_SERVICE )
                    ->delete()
                    ->where( 'tag', $tag )
                    ->execute();
            }
        }

        return $alert;
    }

    /**
     * @inheritDoc
     */
    public static function prepareUpdateServiceResponse( array $response, BooklyLib\Entities\Service $service )
    {
        $response['new_tags_list'] = Lib\Config::getTagList();

        return $response;
    }
}