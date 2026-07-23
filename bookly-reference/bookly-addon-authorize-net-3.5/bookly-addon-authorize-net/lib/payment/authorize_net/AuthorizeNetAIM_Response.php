<?php
namespace BooklyAuthorizeNet\Lib\Payment\AuthorizeNet;

class AuthorizeNetAIM_Response
{
    const APPROVED = 1;
    /** @var bool */
    public $approved = false;
    /** @var string */
    public $error_message;
    /** @var string */
    public $transaction_id;

    /**
     * Constructor. Parses the AuthorizeNet response string.
     *
     * @param string $response      The response from the AuthNet server.
     * @param string $delimiter     The delimiter used (default is ",")
     * @param string $encap_char    The encap_char used (default is "|")
     */
    public function __construct( $response, $delimiter, $encap_char )
    {
        if ( $response ) {
            if ( $encap_char ) {
                $response_array = explode( $encap_char . $delimiter . $encap_char, substr( $response, 1, - 1 ) );
            } else {
                $response_array = explode( $delimiter, $response );
            }

            // If AuthorizeNet doesn't return a delimited response.
            if ( count( $response_array ) < 10 ) {
                $this->approved = false;
                $this->error_message = 'Unrecognized response from Authorize.net: ' . $response;

                return;
            }
            $this->approved = $response_array[0] == self::APPROVED;
            $this->transaction_id = $response_array[6];
            if ( ! $this->approved ) {
                $this->error_message = $response_array[3];
            }
        } else {
            $this->approved = false;
            $this->error_message = 'Error connecting to Authorize.net';
        }
    }

}