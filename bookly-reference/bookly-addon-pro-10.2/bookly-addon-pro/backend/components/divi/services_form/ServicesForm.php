<?php
namespace BooklyPro\Backend\Components\Divi\ServicesForm;

use BooklyPro\Lib\Entities\Form;

class ServicesForm extends \ET_Builder_Module
{
    public $slug = 'bookly_divi_services_form';

    /** @var array */
    private $form_options = array();

    public function init()
    {
        $this->name = 'Bookly - ' . esc_html__( 'Services form', 'bookly' );
        $this->form_options = array( '' => __( 'Select form', 'bookly' ) );

        if ( ! is_admin() ) {
            return;
        }

        $forms = Form::query()
            ->select( 'name, token' )
            ->where( 'type', Form::TYPE_SERVICES_FORM )
            ->fetchArray();

        foreach ( $forms as $form ) {
            $this->form_options[ $form['token'] ] = $form['name'];
        }
    }

    public function get_fields()
    {
        return array(
            'form_token' => array(
                'label' => __( 'Form', 'bookly' ),
                'type' => 'select',
                'options' => $this->form_options,
                'default' => '',
                'toggle_slug' => 'main_content',
            ),
        );
    }

    public function render( $attrs, $content = null, $render_slug = null )
    {
        $token = isset( $this->props['form_token'] ) ? trim( $this->props['form_token'] ) : '';
        if ( $token === '' ) {
            return '';
        }

        return do_shortcode( sprintf( '[bookly-services-form %s]', sanitize_text_field( $token ) ) );
    }
}