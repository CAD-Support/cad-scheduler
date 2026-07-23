<?php
namespace BooklyCustomFields\Frontend\Modules\Booking;

use Bookly\Lib as BooklyLib;
use BooklyCustomFields\Lib\Captcha\Captcha;

class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return array( '_default' => 'anonymous' );
    }

    /**
     * Output a PNG image of captcha to browser.
     */
    public static function captcha()
    {
        Captcha::draw( self::parameter( 'form_id' ) );
    }

    /**
     * Refresh captcha.
     */
    public static function captchaRefresh()
    {
        Captcha::init( self::parameter( 'form_id' ) );
        wp_send_json_success( array( 'captcha_url' => admin_url( sprintf(
            'admin-ajax.php?action=bookly_custom_fields_captcha&csrf_token=%s&form_id=%s&%f',
            BooklyLib\Utils\Common::getCsrfToken(),
            self::parameter( 'form_id' ),
            microtime( true )
        ) ) ) );
    }
}