<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
$icon_url = get_site_icon_url();
if ( ! isset( $_POST['submit'] ) ) {// phpcs:ignore
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
        body {
            font-family: Helvetica;
            background-color: #f3f4f6;
            display: flex;
            justify-content: left;
            align-items: left;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: left;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 50px;
        }
        .unsubscribe-options {
            text-align: left;
            margin-top: 20px;
        }
        .unsubscribe-options input[type="radio"],
        .unsubscribe-options input[type="checkbox"] {
            margin-right: 10px;
        }
        .button {
            font-size: 15px;
            background-color: #17a2b8;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 40px;
        }
        .button:hover {
            background-color: #138496;
        }
    </style>
<?php
$email_address = isset( $email_address ) ? esc_html( $email_address ) : '';
$user_id = isset( $user_id ) ? intval( $user_id ) : 0;
$from_address = get_option('wcap_from_email');
?>
<div style="position: absolute; top: -10%; bottom: 0; left: 0; right: 0; margin: auto; text-align: left; width: 45%; height: 75%;">
<div class="container">
    <h1>Unsubscribe from Abandoned Cart Reminders</h1>
    <h4 style="font-weight: 500">Your email address is <strong><?php echo esc_html( $email_address );?></strong></h4>
    <p>To stop receiving abandoned cart reminder emails from <a href="mailto:<?php echo esc_html( $from_address );?>"><?php echo esc_html( $from_address );?></a></p>
<form method="post">
    
    <div class="unsubscribe-options">
        <p>You can:</p>
        <label>
            <input type="radio" name="unsubscribe" value="wcap_current" checked> Unsubscribe from the current abandoned cart
        </label><br>
        <label>
            <input type="radio" name="unsubscribe" value="wcap_all"> Unsubscribe from this and all future abandoned carts
        </label><br>

    </div>

    <button type="submit" name="submit" class="button" id="unsubscribe-button">Unsubscribe </button>
</form>
</div>
</div>
<?php }
if ( isset( $_POST['submit'] ) ) {// phpcs:ignore
    $selected_unsubscribe_option = isset( $_POST['unsubscribe'] ) ? sanitize_text_field( $_POST['unsubscribe'] ) : 'wcap_current';// phpcs:ignore
    Wcap_Populate_Cart_Of_User::wcap_unsubscribe_redirected_page ( $user_id, $email_address, $selected_unsubscribe_option );
    exit;
}
?>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        var unsubscribeButton = document.getElementById('unsubscribe-button'); // Use a specific ID
        unsubscribeButton.addEventListener('click', function() {
            this.textContent           = 'Unsubscribing, please wait...'; // Change button text
            this.style.backgroundColor = '#ccc';   // Change background color
            this.style.color           = '#666';             // Change text color
            this.style.cursor          = 'not-allowed';     // Change cursor to not-allowed
            this.style.opacity         = '0.6';
        });
    });
</script>
