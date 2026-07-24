<?php
namespace BooklyDiscounts\Backend\Components\Dialogs\Discount;

use Bookly\Lib as BooklyLib;
use BooklyDiscounts\Lib;

class Edit extends BooklyLib\Base\Component
{
    public static function render()
    {
        self::enqueueStyles( array(
            'alias' => array( 'bookly-backend-globals', ),
        ) );

        self::enqueueScripts( array(
            'module' => array( 'js/discount.js' => array( 'bookly-backend-globals' ) ),
        ) );

        wp_localize_script( 'bookly-discount.js', 'BooklyDiscountDialogL10n', array(
            'csrfToken' => BooklyLib\Utils\Common::getCsrfToken(),
            'title' => array(
                'new' => esc_html__( 'New discount', 'bookly' ),
                'edit' => esc_html__( 'Edit discount', 'bookly' ),
            ),
            'discountError' => esc_html__( 'Discount should be between 0 and 100.', 'bookly' ),
            'deductionError' => esc_html__( 'Deduction should be a positive number.', 'bookly' ),
            'datePicker' => BooklyLib\Utils\DateTime::datePickerOptions(),
        ) );

        $services = BooklyLib\Utils\Common::getServiceDataForDropDown( 's.type <> "package"' );

        self::renderTemplate( 'edit', compact( 'services' ) );
    }
}