<?php
namespace BooklyCart\Backend\Modules\Appearance\ProxyProviders;

use Bookly\Backend\Modules\Appearance\Proxy;

class Local extends Proxy\Cart
{
    /**
     * @inheritDoc
     */
    public static function renderCartStepSettings()
    {
        self::renderTemplate( 'cart_step_settings' );
    }

    /**
     * @inheritDoc
     */
    public static function renderShowStep()
    {
        self::renderTemplate( 'show_cart_step' );
    }

    /**
     * @inheritDoc
     */
    public static function renderStep( $progress_tracker )
    {
        self::renderTemplate( 'cart_step', compact( 'progress_tracker' ) );
    }

    /**
     * @inheritDoc
     */
    public static function renderButton()
    {
        self::renderTemplate( 'button' );
    }
}