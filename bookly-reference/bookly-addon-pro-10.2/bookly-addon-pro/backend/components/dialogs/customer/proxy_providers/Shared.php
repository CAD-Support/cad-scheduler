<?php
namespace BooklyPro\Backend\Components\Dialogs\Customer\ProxyProviders;

use Bookly\Backend\Components\Dialogs\Customer\Edit\Proxy;
use Bookly\Lib as BooklyLib;
use BooklyPro\Lib;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareL10n( $localize )
    {
        $address_labels = array(
            'country' => __( 'Country', 'bookly' ),
            'state' => __( 'State/Region', 'bookly' ),
            'postcode' => __( 'Postal Code', 'bookly' ),
            'city' => __( 'City', 'bookly' ),
            'street' => __( 'Street Address', 'bookly' ),
            'street_number' => __( 'Street Number', 'bookly' ),
            'additional_address' => __( 'Additional Address', 'bookly' ),
        );
        $address = array();
        foreach ( Lib\Utils\Common::getDisplayedAddressFields() as $field_name => $field ) {
            $address[ $field_name ] = $address_labels[ $field_name ];
        }
        $localize['address'] = $address;
        $tags = Lib\Entities\Tag::query()
            ->select( 'tag, color_id' )
            ->where( 'type', Lib\Entities\Tag::TYPE_CUSTOMER )
            ->fetchArray();
        $localize['tagsList'] = $tags;
        $localize['tagColors'] = Lib\Config::getTagColors();
        $localize['l10n']['tags'] = __( 'Tags', 'bookly' );
        $localize['l10n']['birthday'] = __( 'Date of birth', 'bookly' );
        $localize['l10n']['createWpUser'] = __( 'Create WordPress user', 'bookly' );

        return $localize;
    }

    /**
     * @inheritDoc
     */
    public static function prepareSaveCustomer( $response, $request, $customer )
    {
        $removed_tags = $customer->getTags() ? json_decode( $customer->getTags(), false ) : array();
        if ( ! is_array( $removed_tags ) ) {
            $removed_tags = array();
        }

        $new_tags = array();
        if ( $tags = $request->get( 'tags' ) ) {
            $tag_colors = $request->get( 'tagColors' );
            $customer->setTags( json_encode( $tags, 256 ) );
            foreach ( $tags as $tag ) {
                $tag_exists = Lib\Entities\Tag::query()
                    ->where( 'tag', $tag )
                    ->where( 'type', Lib\Entities\Tag::TYPE_CUSTOMER )
                    ->count();
                if ( ! $tag_exists ) {
                    $new_tag = new Lib\Entities\Tag();
                    $new_tag
                        ->setTag( $tag )
                        ->setColorId( isset( $tag_colors[ $tag ] ) ? $tag_colors[ $tag ] : 0 )
                        ->setType( Lib\Entities\Tag::TYPE_CUSTOMER )
                        ->save();
                    $new_tags[] = array( 'tag' => $tag, 'color_id' => $new_tag->getColorId() );
                }
                $key = array_search( $tag, $removed_tags, true );
                if ( $key !== false ) {
                    unset( $removed_tags[ $key ] );
                }
            }
        } else {
            $customer->setTags( null );
        }
        $response['new_tags'] = $new_tags;

        if ( $request->get( 'wp_user_id' ) === 'create' ) {
            $exists_active_notification = BooklyLib\Entities\Notification::query()
                ->where( 'type', BooklyLib\Entities\Notification::TYPE_CUSTOMER_NEW_WP_USER )
                ->where( 'active', 1 )
                ->limit( 1 )
                ->count();
            if ( $exists_active_notification ) {
                // Try to create WordPress user
                try {
                    $wp_user = Lib\Utils\Common::createWPUser( array(
                        'email' => $request->get( 'email' ),
                        'first_name' => $request->get( 'first_name' ),
                        'last_name' => $request->get( 'last_name' ),
                        'full_name' => $request->get( 'full_name' ),
                    ), $password, 'client' );
                    $wp_user->set_role( get_option( 'bookly_cst_new_account_role' ) );

                    // Send email/sms notification.
                    $customer->setEmail( $wp_user->user_email );
                    Lib\Notifications\NewWpUser\Sender::sendAuthToClient( $customer, $wp_user->display_name, $password );
                    $response['wp_user'] = array(
                        'ID' => $wp_user->ID,
                        'user_email' => $request->get( 'email' ),
                        'display_name' => $wp_user->display_name,
                        'user_id' => $wp_user->ID,
                        'first_name' => '',
                        'last_name' => '',
                        'phone' => null,
                    );
                    $customer->setWpUserId( $wp_user->ID );
                } catch ( BooklyLib\Base\ValidationException $e ) {
                    $response['errors'][ $e->getField() ] = $e->getMessage();
                }
            } else {
                $response['errors']['wp_user'] = __( 'Please setup and enable New customer\'s WordPress user login details notification', 'bookly' );
            }
        }

        // Remove unused tags from the database.
        foreach ( $removed_tags as $tag ) {
            $tag_exists = BooklyLib\Entities\Customer::query()
                ->whereNot( 'id', $customer->getId() )
                ->whereNot( 'tags', null )
                ->whereLike( 'tags', '%"' . $tag . '"%' )
                ->limit( 1 )
                ->count();
            if ( ! $tag_exists ) {
                Lib\Entities\Tag::query()
                    ->where( 'type', Lib\Entities\Tag::TYPE_CUSTOMER )
                    ->delete()
                    ->where( 'tag', $tag )
                    ->execute();
            }
        }

        return $response;
    }
}