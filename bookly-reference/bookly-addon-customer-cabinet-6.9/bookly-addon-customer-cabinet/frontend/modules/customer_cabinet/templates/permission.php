<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly ?>
<?php if ( is_user_logged_in() ) : ?>
    <div id="bookly-tbs">
        <div>
            <?php esc_html_e( 'Oops!', 'bookly' ) ?> <?php esc_html_e( 'It seems like you are trying to log in with a user that is not associated with an existing customer or staff member.', 'bookly' ) ?> <?php esc_html_e( 'Please double-check your credentials and ensure that you are using the correct login information.', 'bookly' ) ?>
        </div>
    </div>
<?php else : ?>
    <?php wp_login_form() ?>
<?php endif ?>