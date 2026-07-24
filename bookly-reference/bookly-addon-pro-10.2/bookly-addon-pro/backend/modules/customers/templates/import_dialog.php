<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Controls\Buttons;
use Bookly\Backend\Modules\Customers\Page;
use Bookly\Lib\Utils\Common;
use Bookly\Backend\Components\Controls\Inputs;

?>
<div id="bookly-import-customers-dialog" class="bookly-modal bookly-fade" tabindex=-1 role="dialog">
    <div class="modal-dialog">
        <form enctype="multipart/form-data" action="<?php echo Common::escAdminUrl( Page::pageSlug() ) ?>" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php esc_html_e( 'Import', 'bookly' ) ?></h5>
                    <button type="button" class="close" data-dismiss="bookly-modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <h5><?php esc_html_e( 'Note', 'bookly' ) ?></h5>
                    <p>
                        <?php esc_html_e( 'You may import list of clients in CSV format. You can choose the columns contained in your file. The sequence of columns should coincide with the specified one.', 'bookly' ) ?>
                    </p>
                    <div class="form-group">
                        <label for="import_customers_file"><?php esc_html_e( 'Select file', 'bookly' ) ?></label>
                        <input name="import_customers_file" id="import_customers_file" type="file" class="bookly:border-input bookly:focus-visible:border-ring bookly:focus-visible:ring-ring/50 bookly:h-8 bookly:rounded-lg bookly:border bookly:bg-transparent bookly:px-2.5 bookly:py-1 bookly:text-base bookly:transition-colors bookly:file:h-6 bookly:file:text-sm bookly:file:font-medium bookly:focus-visible:ring-[length:var(--bookly-ring-width)] bookly:md:text-sm bookly:file:text-foreground bookly:placeholder:text-muted-foreground bookly:w-full bookly:min-w-0 bookly:outline-none bookly:file:inline-flex bookly:file:border-0 bookly:file:bg-transparent bookly:disabled:pointer-events-none bookly:disabled:cursor-not-allowed bookly:disabled:opacity-50"/>
                    </div>
                    <div class="form-group">
                        <?php Inputs::renderCheckBox( __( 'Full name', 'bookly' ), null, true, array( 'name' => 'full_name' ) ) ?>
                        <?php Inputs::renderCheckBox( __( 'First name', 'bookly' ), null, false, array( 'name' => 'first_name' ) ) ?>
                        <?php Inputs::renderCheckBox( __( 'Last name', 'bookly' ), null, false, array( 'name' => 'last_name' ) ) ?>
                        <?php Inputs::renderCheckBox( __( 'Phone', 'bookly' ), null, true, array( 'name' => 'phone' ) ) ?>
                        <?php Inputs::renderCheckBox( __( 'Email', 'bookly' ), null, true, array( 'name' => 'email' ) ) ?>
                        <?php Inputs::renderCheckBox( __( 'Date of birth', 'bookly' ), null, true, array( 'name' => 'birthday' ) ) ?>
                    </div>
                    <div class="form-group">
                        <label for="import_customers_delimiter"><?php esc_html_e( 'Delimiter', 'bookly' ) ?></label>
                        <select name="import_customers_delimiter" id="import_customers_delimiter" class="form-control">
                            <option value=","><?php esc_html_e( 'Comma (,)', 'bookly' ) ?></option>
                            <option value=";"><?php esc_html_e( 'Semicolon (;)', 'bookly' ) ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <?php Inputs::renderCheckBox( __( 'Skip first row of CSV file', 'bookly' ), null, true, array( 'name' => 'skip_first_row' ) ) ?>
                    </div>
                    <input type="hidden" name="import-customers">
                </div>
                <div class="modal-footer">
                    <?php Buttons::renderSubmit( null, null, __( 'Import', 'bookly' ) ) ?>
                </div>
            </div>
        </form>
    </div>
</div>