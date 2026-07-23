<?php
namespace BooklyPro\Lib;

use Bookly\Lib as BooklyLib;

abstract class API extends BooklyLib\API
{
    /**
     * Verify purchase code.
     *
     * @param string $purchase_code
     * @param string $plugin_slug
     * @param int|null $blog_id
     * @return array
     */
    public static function verifyPurchaseCode( $purchase_code, $plugin_slug, $blog_id = null )
    {
        $url = add_query_arg(
            array(
                'purchase_code' => $purchase_code,
                'site_url' => get_site_url( $blog_id ),
            ),
            self::getUrl( '/1.1/plugins/' . $plugin_slug . '/purchase-code', $purchase_code )
        );
        $response = wp_remote_get( $url, array(
            'sslverify' => false,
            'timeout' => 25,
        ) );

        if ( ! is_wp_error( $response ) && isset( $response['body'] ) ) {
            update_option( 'bookly_api_server_error_time', '0' );
            $json = json_decode( $response['body'], true );
            if ( isset( $json['success'] ) ) {
                if ( (bool) $json['success'] ) {
                    if ( $plugin_slug === 'bookly-addon-pro' && isset( $json['data']['bookly_pro_licensed_products'] ) ) {
                        update_option( 'bookly_pro_licensed_products', $json['data']['bookly_pro_licensed_products'] ?: array() );
                    }
                    return array( 'valid' => true, );
                }

                if ( isset ( $json['error'] ) ) {
                    switch ( $json['error'] ) {
                        case 'already_in_use':
                            return array(
                                'valid' => false,
                                'error' => __( 'This purchase code is already in use on another site', 'bookly' ) . ': ' . ( isset ( $json['data']['domains'] ) ? implode( ', ', $json['data']['domains'] ) : '' ),
                            );
                        case 'connection':
                            // ... Please try again later.
                            break;
                        case 'invalid':
                        default:
                            return array(
                                'valid' => false,
                                'error' => __( 'The purchase code you entered is not valid.', 'bookly' ) . ' ' . __( 'Please check the code and try again.', 'bookly' ),
                            );
                    }
                }
            }
        }

        return array(
            'valid' => false,
            'error' => __( 'Purchase code verification is temporarily unavailable. Please try again later.', 'bookly' )
        );
    }

    /**
     * Get purchase code data.
     *
     * @param string $purchase_code
     * @param BooklyLib\Base\Plugin $plugin_class
     * @param null $blog_id
     */
    public static function getPurchaseCodeData( $purchase_code, $plugin_class, $blog_id = null )
    {
        $url = add_query_arg(
            array(
                'purchase_code' => $purchase_code,
                'site_url'      => get_site_url( $blog_id ),
            ),
            self::API_URL . '/1.2/plugins/' . $plugin_class::getSlug() . '/purchase-code-data'
        );
        $response = wp_remote_get( $url, array(
            'sslverify' => false,
            'timeout'   => 25,
        ) );

        $json = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $json['status'] ) ) {
            return $json;
        }

        return array(
            'status' => 'connection',
        );
    }

    /**
     * Get purchase code data.
     *
     * @param string $purchase_code
     * @param null $blog_id
     */
    public static function getPurchaseCodeInfo( $purchase_code, $blog_id = null )
    {
        if ( $purchase_code ) {
            $url = self::getUrl( '/1.0/purchase-code/info', $purchase_code );
            $response = wp_remote_post( $url, array(
                'sslverify' => false,
                'timeout' => 25,
                'body' => array(
                    'purchase_code' => $purchase_code,
                    'site_url' => get_site_url( $blog_id ),
                    'bookly' => BooklyLib\Plugin::getVersion(),
                    'bookly-addon-pro' => Plugin::getVersion(),
                ),
            ) );

            $json = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $json['main']['status'] ) ) {
                return $json;
            }
        }

        return array(
            'main' => array(
                'status' => $purchase_code
                    ? 'verification_unavailable'
                    : 'invalid'
            ),
        );
    }

    /**
     * Verify support for purchase code.
     *
     * @param string                $purchase_code
     * @param BooklyLib\Base\Plugin $plugin_class
     * @param int|null              $blog_id
     * @return array
     */
    public static function verifySupport( $purchase_code, $plugin_class, $blog_id = null )
    {
        $url = add_query_arg(
            array(
                'purchase_code' => $purchase_code,
                'site_url'      => get_site_url( $blog_id ),
            ),
            self::API_URL . '/1.1/plugins/' . $plugin_class::getSlug() . '/support'
        );
        $response = wp_remote_get( $url, array(
            'sslverify' => false,
            'timeout'   => 25,
        ) );

        $json = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $json['success'] ) ) {
            if ( (bool) $json['success'] ) {
                return array( 'valid' => true, );
            } elseif ( isset ( $json['error'] ) ) {
                switch ( $json['error'] ) {
                    case 'connection':
                        // ... Please try again later.
                        break;
                    case 'expired':
                        return array(
                            'valid' => false,
                            'message' => sprintf(
                                __( 'Your support period has expired on %s.', 'bookly' ),
                                array_key_exists( 'supported_until', $json )
                                    ? BooklyLib\Utils\DateTime::formatDate( $json['supported_until'] )
                                    : ''
                            ),
                        );
                }
            }
        }

        return array(
            'valid' => false,
            'message' => __( 'Purchase code verification is temporarily unavailable. Please try again later.', 'bookly' )
        );
    }

    /**
     * Detach purchase code from current domain.
     *
     * @param BooklyLib\Base\Plugin $plugin_class
     * @param int|null $blog_id
     * @return bool
     */
    public static function detachPluginPurchaseCode( $plugin_class, $blog_id = null )
    {
        return self::detachPurchaseCode($plugin_class::getPurchaseCode( $blog_id ), $blog_id );
    }

    /**
     * Detach purchase code from current domain.
     *
     * @param string $purchase_code
     * @param int|null $blog_id
     * @return bool
     */
    public static function detachPurchaseCode( $purchase_code, $blog_id = null )
    {
        $url = add_query_arg(
            array(
                'site_url' => get_site_url( $blog_id ),
            ),
            self::getUrl( '/1.0/purchase-code/' . $purchase_code, $purchase_code )
        );

        $response = wp_remote_request( $url, array(
            'method' => 'DELETE',
            'sslverify' => false,
            'timeout' => 25,
        ) );

        if ( ! is_wp_error( $response ) && isset( $response['body'] ) ) {
            $json = json_decode( $response['body'], true );
            if ( isset ( $json['success'] ) && $json['success'] ) {
                return true;
            }
        }

        return false;
    }

    public static function getUrl( $path, $purchase_code )
    {
        return strlen( $purchase_code ) === 19
            ? BooklyLib\Cloud\API::API_URL . $path
            : self::API_URL . $path;
    }
}