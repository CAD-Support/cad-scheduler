<?php
namespace BooklyAuthorizeNet\Lib\Payment;

use Bookly\Lib as BooklyLib;

class AuthorizeNetGateway extends BooklyLib\Base\Gateway
{
    protected $type = BooklyLib\Entities\Payment::TYPE_AUTHORIZENET;
    protected $on_site = true;

    /**
     * @inerhitDoc
     */
    protected function getCheckoutUrl( array $intent_data )
    {
    }

    /**
     * @inerhitDoc
     */
    protected function getInternalMetaData()
    {
        return array();
    }

    /**
     * @inerhitDoc
     */
    protected function createGatewayIntent()
    {
        $authorize = $this->getApiClient();

        $userData = $this->request->getUserData();
        $customer = $userData->getCustomer();
        $card = $this->request->get( 'card' );
        $authorize->setField( 'amount', $this->getGatewayAmount() )
            ->setField( 'card_num', $card['number'] )
            ->setField( 'card_code', $card['cvc'] )
            ->setField( 'exp_date', $card['exp_month'] . '/' . $card['exp_year'] )
            ->setField( 'email', $userData->getEmail() )
            ->setField( 'phone', $userData->getPhone() )
            ->setField( 'first_name', $customer->getFirstName() )
            ->setField( 'tax', $this->getGatewayTax() );
        if ( $userData->getPostcode() ) {
            $authorize->setField( 'zip', $userData->getPostcode() );
        }

        if ( $customer->getLastName() ) {
            $authorize->setField( 'last_name', $customer->getLastName() );
        }

        $aim_response = $authorize->authorizeAndCapture();
        if ( $aim_response->approved ) {
            return array(
                'ref_id' => $aim_response->transaction_id,
                'target_url' => $this->getResponseUrl( self::EVENT_RETRIEVE ),
            );
        }
        throw new \Exception( $aim_response->error_message );
    }

    /**
     * @inerhitDoc
     */
    public function retrieveStatus()
    {
        return self::STATUS_COMPLETED;
    }

    /**
     * @return AuthorizeNet
     */
    private function getApiClient()
    {
        return new AuthorizeNet( get_option( 'bookly_authorize_net_api_login_id' ), get_option( 'bookly_authorize_net_transaction_key' ), (bool) get_option( 'bookly_authorize_net_sandbox' ) );
    }
}