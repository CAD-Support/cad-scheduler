<?php
namespace BooklyCustomerGroups\Backend\Components\Dialogs\Staff\Edit\ProxyProviders;

use Bookly\Backend\Components\Dialogs\Staff\Edit\Proxy;

class Local extends Proxy\CustomerGroups
{
    /**
     * @inheritDoc
     */
    public static function renderPaymentGatewaysHelp()
    {
        echo '<small class="text-muted form-text">' . esc_html__( 'Note that only common payment methods for customer and staff will be available on Payment step', 'bookly' ) . '</small>';
    }
}