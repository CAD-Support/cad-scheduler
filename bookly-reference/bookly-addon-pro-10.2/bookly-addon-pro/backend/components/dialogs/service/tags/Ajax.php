<?php
namespace BooklyPro\Backend\Components\Dialogs\Service\Tags;

use Bookly\Lib as BooklyLib;
use BooklyPro\Lib\Config;
use BooklyPro\Lib\Entities;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * Update service tags
     */
    public static function updateServiceTags()
    {
        foreach ( self::parameter( 'tags' ) as $tag ) {
            $tag_id = $tag['id'];
            if ( $tag_id ) {
                $db_tag = Entities\Tag::find( $tag_id );
                if ( $db_tag->getTag() !== $tag['tag'] ) {
                    // Update services
                    /** @var BooklyLib\Entities\Service $service */
                    foreach ( BooklyLib\Entities\Service::query()->whereNot( 'tags', null )->find() as $service ) {
                        if ( in_array( $db_tag->getTag(), $_tags = json_decode( $service->getTags(), false ) ) ) {
                            foreach ( $_tags as &$value ) {
                                if ( $value == $db_tag->getTag() ) {
                                    $value = $tag['tag'];
                                }
                                unset( $value );
                            }
                            $service->setTags( json_encode( $_tags ) )->save();
                        }
                    }
                    // Update appearances
                    /** @var Entities\Form $appearance */
                    foreach ( Entities\Form::query()->find() as $appearance ) {
                        $settings = json_decode( $appearance->getSettings(), true );
                        if ( isset( $settings['tags'] ) ) {
                            $sections = $settings['tags'];
                            foreach ( $sections as $s_key => $section ) {
                                $_tags = $section['tags'];
                                if ( $_tags && in_array( $db_tag->getTag(), $_tags ) ) {
                                    foreach ( $_tags as &$value ) {
                                        if ( $value == $db_tag->getTag() ) {
                                            $value = $tag['tag'];
                                        }
                                        unset( $value );
                                    }
                                    $settings['tags'][ $s_key ]['tags'] = $_tags;
                                    $appearance->setSettings( json_encode( $settings ) )->save();
                                }
                            }
                        }
                    }
                }
                $db_tag->setFields( $tag )->save();
            }
        }

        $tags = Config::getTagList();

        wp_send_json_success( compact( 'tags' ) );
    }
}