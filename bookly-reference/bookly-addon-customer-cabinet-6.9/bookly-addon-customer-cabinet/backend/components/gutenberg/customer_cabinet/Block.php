<?php
namespace BooklyCustomerCabinet\Backend\Components\Gutenberg\CustomerCabinet;

use Bookly\Lib as BooklyLib;

class Block extends BooklyLib\Base\Block
{
    /**
     * @inheritDoc
     */
    public static function registerBlockType()
    {
        self::enqueueScripts( array(
            'module' => array(
                'js/customer-cabinet-block.js' => array( 'wp-blocks', 'wp-components', 'wp-element', 'wp-editor' ),
            ),
        ) );

        wp_localize_script( 'bookly-customer-cabinet-block.js', 'BooklyCustomerCabinetL10n', array(
            'block' => array(
                'title' => 'Bookly - ' . __( 'Customer cabinet', 'bookly' ),
                'description' => __( 'A custom block for displaying customer cabinet', 'bookly' ),
            ),
            'show' => __( 'show', 'bookly' ),
            'Show' => __( 'Show', 'bookly' ),
            'appointment' => array(
                'filters' => __( 'Filters', 'bookly' ),
                'date' => __( 'Date', 'bookly' ),
                'timezone' => __( 'Timezone', 'bookly' ),
                'location' => __( 'Location', 'bookly' ),
                'category' => __( 'Category', 'bookly' ),
                'service' => __( 'Service', 'bookly' ),
                'staff' => __( 'Staff', 'bookly' ),
                'duration' => __( 'Duration', 'bookly' ),
                'price' => __( 'Price', 'bookly' ),
                'status' => __( 'Status', 'bookly' ),
                'cancel' => __( 'Cancel', 'bookly' ),
                'reason' => __( 'Cancellation reason', 'bookly' ),
                'reschedule' => __( 'Reschedule', 'bookly' ),
                'customField' => __( 'Custom field', 'bookly' ),
                'onlineMeeting' => __( 'Online meeting', 'bookly' ),
                'joinOnlineMeeting' => __( 'Join online meeting', 'bookly' ),
            ),
            'profile' => array(
                'name' => __( 'Name', 'bookly' ),
                'email' => __( 'Email', 'bookly' ),
                'phone' => __( 'Phone', 'bookly' ),
                'birthday' => __( 'Birthday', 'bookly' ),
                'address' => __( 'Address', 'bookly' ),
                'full_address' => __( 'Customer address', 'bookly' ),
                'wordpressPassword' => __( 'WordPress password', 'bookly' ),
                'customerInformation' => __( 'Customer information', 'bookly' ),
                'deleteAccount' => __( 'Delete account', 'bookly' ),
            ),
            'appointmentManagement' => __( 'Appointment management', 'bookly' ),
            'profileManagement' => __( 'Profile management', 'bookly' ),
            'customFields' => BooklyLib\Proxy\CustomFields::getWhichHaveData() ?: array(),
            'customerInformation' => BooklyLib\Proxy\CustomerInformation::getFieldsWhichMayHaveData() ?: array(),
            'locationsActive' => (int) BooklyLib\Config::locationsActive(),
        ) );

        register_block_type( 'bookly/customer-cabinet-block', array(
            'editor_script' => 'bookly-customer-cabinet-block.js',
        ) );
    }
}
