<?php
/**
 * Contains all the common functions used in SMS, FB & ATC.
 * This incudes getting & setting of data mainly.
 *
 * @since 8.9.0
 * @package Abandoned-Cart-Pro-for-WooCommerce
 */

/**
 * Common function that can be used to get the data
 * from the notifications_meta table
 *
 * @param integer $template_id - Template ID.
 * @param string  $meta_key - Meta Key.
 * @return boolean|string - Meta Value. Returns false if meta key not found.
 *
 * @since 7.9
 */
use Automattic\WooCommerce\Utilities\OrderUtil;

function wcap_get_notification_meta( $template_id, $meta_key ) {
 return false;
	global $wpdb;

	if ( $template_id > 0 && '' !== $meta_key ) {

		$query_data = $wpdb->get_results( // phpcs:disable
			$wpdb->prepare(
				'SELECT meta_value FROM `' . WCAP_NOTIFICATIONS_META . '` WHERE template_id = %d AND meta_key = %s', // phpcs:ignore
				$template_id,
				$meta_key
			)
		);

		if ( is_array( $query_data ) && count( $query_data ) > 0 ) {
			return ( isset( $query_data[0]->meta_value ) ) ? $query_data[0]->meta_value : false;
		} else {
			return false;
		}
	} else {
		return false;
	}

}

/**
 * Common function that can be used to update the
 * Notifications_meta table
 *
 * @param integer $template_id - Template ID.
 * @param string  $meta_key - Meta Key.
 * @param string  $meta_value - Meta Value.
 *
 * @since 7.9
 */
function wcap_update_notification_meta( $template_id, $meta_key, $meta_value ) {
return false;
	global $wpdb;

	if ( $template_id > 0 && '' !== $meta_key ) {

		$update = $wpdb->update( // phpcs:ignore
			WCAP_NOTIFICATIONS_META,
			array(
				'meta_value' => $meta_value // phpcs:ignore
			),
			array(
				'template_id' => $template_id,
				'meta_key'    => $meta_key, // phpcs:ignore
			)
		);

		if ( 0 === $update && false === wcap_get_notification_meta( $template_id, $meta_key ) ) { // No record was found for update.
			wcap_add_notification_meta( $template_id, $meta_key, $meta_value );
		}
	}

}

/**
 * Common function that can be used to insert in the
 * Notifications_meta table
 *
 * @param integer $template_id - Template ID.
 * @param string  $meta_key - Meta Key.
 * @param string  $meta_value - Meta Value.
 *
 * @since 7.9
 */
function wcap_add_notification_meta( $template_id, $meta_key, $meta_value ) {

	global $wpdb;

	$update = $wpdb->insert( // phpcs:ignore
		WCAP_NOTIFICATIONS_META,
		array(
			'template_id' => $template_id,
			'meta_key'    => $meta_key, // phpcs:ignore
			'meta_value'  => $meta_value, // phpcs:ignore
		)
	);

}

/**
 * Returns the data from the Notifications meta
 * table that have the meta key as passed
 *
 * @param string $meta_key - Meta Key.
 * @return array $results - Results array.
 *
 * @since 7.9
 */
function wcap_get_notification_meta_by_key( $meta_key ) {
	global $wpdb;

	$meta_results = $wpdb->get_results( // phpcs:ignore
		$wpdb->prepare(
			'SELECT meta_id, template_id, meta_value FROM `' . WCAP_NOTIFICATIONS_META . '` WHERE meta_key = %s', // phpcs:ignore
			$meta_key
		)
	);

	return $meta_results;
}

/**
 * Returns the template status
 *
 * @param integer $template_id - Template ID.
 * @return boolean $status - Template status - true - active|false - inactive.
 *
 * @since 7.9
 */
function wcap_get_template_status( $template_id ) {

	$status = false;

	global $wpdb;

	$status_col = $wpdb->get_results( // phpcs:ignore
		$wpdb->prepare(
			'SELECT is_active FROM `' . WCAP_NOTIFICATION_TEMPLATES . '` WHERE id = %d', // phpcs:ignore
			$template_id
		)
	);

	$status = ( isset( $status_col[0] ) ) ? $status_col[0]->is_active : false;

	return $status;
}

/**
 * Returns the list of enabled reminder methods
 *
 * @return array $reminders_enabled - Reminder Methods that are enabled.
 * @since 7.10.0
 */
function wcap_get_enabled_reminders() {

	$reminders_enabled = array();

	$reminders_list = array();

	$reminders_list['emails'] = get_option( 'ac_enable_cart_emails', '' );
	$reminders_list['sms']    = get_option( 'wcap_enable_sms_reminders', '' );
	$reminders_list['whatsapp']    = get_option( 'wcap_enable_whatsapp_reminders', '' );

	foreach ( $reminders_list as $names => $status ) {
		if ( 'on' === $status ) {
			array_push( $reminders_enabled, $names );
		}
	}

	$reminders_enabled = apply_filters( 'wcap_reminders_list', $reminders_enabled );

	return $reminders_enabled;
}

/**
 * Update existing template notifications table.
 *
 * @param int    $id - Template ID.
 * @param string $body - Template Body.
 * @param string $frequency - Frequency.
 * @param string $active - Template status.
 * @param string $coupon_code - Coupon code.
 * @param string $subject - Template subject.
 */
function wcap_update_notifications( $id, $body, $frequency, $active, $coupon_code, $subject = '', $template_name = '' ) {

	$freq_array   = explode( ' ', $frequency );
	$freq_numeric = trim( $freq_array[0] );
	$freq_text    = trim( $freq_array[1] );
	$update_count = WCAP_NOTIFICATION_TEMPLATES_MODEL::update(
		array(
			'body'          => $body,
			'frequency'     => $freq_numeric,
			'day_or_hour'   => $freq_text,
			'is_active'     => $active,
			'coupon_code'   => $coupon_code,
			'subject'       => $subject,
			'template_name' => $template_name,
		),
		array(
			'id' => $id,
		)
	);
	return $update_count;
}

/**
 * Insert new template in notifications table.
 *
 * @param string $body - Template Body.
 * @param string $type - Template type.
 * @param string $active - Template status.
 * @param string $frequency - Frequency.
 * @param string $coupon_code - Coupon code.
 * @param string $default - Default template.
 * @param string $subject - Template subject.
 */
function wcap_insert_notifications( $body, $type, $active, $frequency, $coupon_code, $default, $subject = '', $template_name = '' ) {

	$freq_array   = explode( ' ', $frequency );
	$freq_numeric = trim( $freq_array[0] );
	$freq_text    = trim( $freq_array[1] );

	$insert_id = WCAP_NOTIFICATION_TEMPLATES_MODEL::insert(
		array(
			'body'              => $body,
			'notification_type' => $type,
			'is_active'         => $active,
			'frequency'         => $freq_numeric,
			'day_or_hour'       => $freq_text,
			'coupon_code'       => $coupon_code,
			'default_template'  => $default,
			'subject'           => $subject,
			'template_name'     => $template_name,
		)
	);

	return $insert_id;
}

/**
 * Returns the list of templates
 *
 * @param string $type Type of notification.
 * @return array Templates data.
 *
 * @since 7.9
 */
function wcap_get_notification_templates( $type ) {

	global $wpdb;

	// Get active templates.
	$template_data = $wpdb->get_results( // phpcs:ignore
		$wpdb->prepare(
			'SELECT * FROM `' . WCAP_NOTIFICATION_TEMPLATES_TABLE . '` WHERE notification_type = %s AND is_active = %s', // phpcs:ignore
			$type,
			'1'
		)
	);

	if ( is_array( $template_data ) && count( $template_data ) > 0 ) {

		$templates = array();

		$minute_seconds = 60;
		$hour_seconds   = 3600; // 60 * 60
		$day_seconds    = 86400; // 24 * 60 * 60

		foreach ( $template_data as $data ) {

			$frequency_text = strtolower( $data->day_or_hour );

			switch ( $frequency_text ) {
				case '':
				case 'minutes':
					$frequency = $data->frequency * $minute_seconds;
					break;
				case 'hours':
					$frequency = $data->frequency * $hour_seconds;
					break;
				case 'days':
					$frequency = $data->frequency * $day_seconds;
					break;
			}

			$templates[ $frequency ] = array(
				'id'             => $data->id,
				'body'           => $data->body,
				'template_name'  => $data->template_name,
				'coupon_code'    => $data->coupon_code,
				'activated_time' => $data->activated_time,
			);

			if ( 'fb' === $type ) {
				$templates[ $frequency ]['subject'] = $data->subject;
			}
		}
	} else {
		$templates = array();
	}

	return $templates;
}

/**
 * Returns the list of carts with cart data for which the notification needs to be sent.
 *
 * @param string  $registered_time - Time before which, registered user carts need to be abandoned for notification to be sent.
 * @param string  $guest_time - Time before which guest cart needs to be abandoned for the notification to be sent.
 * @param integer $template_id - Template ID.
 * @return object $type - Template type.
 *
 * @since 7.9
 */
function wcap_get_notification_carts( $registered_time, $guest_time, $template_id, $type = '' ) {

	global $wpdb;

	$carts = array();

	$sent_carts_str  = '';
	$sent_carts_list = wcap_get_notification_meta( $template_id, 'to_be_sent_cart_ids' );

	if ( $sent_carts_list ) {
		$sent_carts = explode( ',', $sent_carts_list );

		foreach ( $sent_carts as $cart_id ) {
			if ( '' !== $sent_carts_str ) {
				$sent_carts_str .= ( '' !== $cart_id ) ? ",'$cart_id'" : '';
			} else {
				$sent_carts_str = ( '' !== $cart_id ) ? "'$cart_id'" : '';
			}
		}
	}

	if ( 'fb' === $type || 'sms' === $type || 'whatsapp' === $type ) {
		$user_id_query = 'AND user_id >= 0';
	} else {
		$user_id_query = 'AND user_id > 0';
	}

	if ( '' !== $sent_carts_str ) {
		// Cart query.
		$cart_query = "SELECT DISTINCT wpac.id, wpac.abandoned_cart_info, wpac.abandoned_cart_time, wpac.user_id, wpac.language FROM `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` as wpac
                        WHERE cart_ignored IN ('0', '2')
                        AND recovered_cart = 0
                        AND unsubscribe_link = '0'
                        " . $user_id_query . "
                        AND wpac.id IN ( $sent_carts_str )
                        AND (( user_type = 'REGISTERED' AND abandoned_cart_time < %s )
                        OR ( user_type = 'GUEST' AND abandoned_cart_time < %s ))";

		$carts = $wpdb->get_results( $wpdb->prepare( $cart_query, $registered_time, $guest_time ) ); // phpcs:ignore

	}
	return $carts;
}

/**
 * Updates the Notifications meta table and removes
 * the Cart ID from the list of carts for which the SMS
 * needs to be sent.
 *
 * @param integer $template_id - Template ID.
 * @param integer $cart_id - Abandoned Cart ID.
 *
 * @since 7.9
 */
function wcap_update_meta( $template_id, $cart_id ) {

	global $wpdb;

	$list_carts = wcap_get_notification_meta( $template_id, 'to_be_sent_cart_ids' );

	$carts_array = explode( ',', $list_carts );

	if ( in_array( $cart_id, $carts_array ) ) { // phpcs:ignore
		$key = array_search( $cart_id, $carts_array ); // phpcs:ignore
		unset( $carts_array[ $key ] );

		$updated_cart_list = implode( ',', $carts_array );
		wcap_update_notification_meta( $template_id, 'to_be_sent_cart_ids', $updated_cart_list );
	}
}

/**
 * Creates a checkout link and inserts a record in the WCAP_TINY_URLS table.
 *
 * @param object $cart_data - Abandoned Cart Data.
 * @param array  $template_data - contains the id, coupon_code & body.
 * @param string $link_type - Link Type: sms_links.
 * @return integer $insert_id - ID of the record inserted in tiny_urls table.
 */
function generate_checkout_url( $cart_data, $template_data, $link_type, $encryption = array() ) {

	global $wpdb;

	$abandoned_id  = $cart_data->id;
	$cart_language = $cart_data->language;

	$template_id     = $template_data['id'];
	$coupon_id       = $template_data['coupon_code'];
	$coupon_to_apply = get_post( $coupon_id, ARRAY_A );
	$coupon_code     = $coupon_to_apply ? $coupon_to_apply['post_title'] : '';

	$checkout_page_id   = wc_get_page_id( 'checkout' );
	$checkout_page_link = $checkout_page_id ? get_permalink( $checkout_page_id ) : '';

	$contact   = isset( $encryption['contact'] ) ? $encryption['contact'] : '';
	$crypt_key = isset( $encryption['crypt_key'] ) ? $encryption['crypt_key'] : '';

	// Force SSL if needed.
	$ssl_is_used = is_ssl() ? true : false;

	if ( true === $ssl_is_used || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) {
		$checkout_page_https = true;
		$checkout_page_link  = str_replace( 'http:', 'https:', $checkout_page_link );
	}

	// check if WPML is active.
	$icl_register_function_exists = function_exists( 'icl_register_string' ) ? true : false;

	if ( $checkout_page_id ) {
		if ( true === $icl_register_function_exists ) {
			if ( 'en' === $cart_language ) { // phpcs:ignore
				// Do nothing.
			} else {
				$checkout_page_link = apply_filters( 'wpml_permalink', $checkout_page_link, $cart_language );
				// if ssl is enabled.
				if ( isset( $checkout_page_https ) && true === $checkout_page_https ) {
					$checkout_page_link = str_replace( 'http:', 'https:', $checkout_page_link );
				}
			}
		}
	}

	$wpdb->insert( // phpcs:ignore
		WCAP_TINY_URLS,
		array(
			'cart_id'           => $abandoned_id,
			'template_id'       => $template_id,
			'long_url'          => '',
			'short_code'        => '',
			'date_created'      => current_time( 'timestamp' ), // phpcs:ignore
			'counter'           => 0,
			'notification_data' => wp_json_encode( array( 'link_clicked' => 'Checkout Page' ) ),
		)
	);
	$insert_id          = $wpdb->insert_id;
	$checkout_page_link = apply_filters( 'wcap_checkout_link_sms_before_encoding', $checkout_page_link, $abandoned_id, $cart_language );
	$encoding_checkout  = $insert_id . '&url=' . $checkout_page_link;
	$validate_checkout  = Wcap_Common::encrypt_validate( $encoding_checkout, $crypt_key );

	$site_url = get_option( 'siteurl' );

	if ( isset( $coupon_code ) && '' !== $coupon_code ) {
		$encrypted_coupon_code = Wcap_Common::encrypt_validate( $coupon_code, $crypt_key );
		$checkout_link_track   = "$site_url/?wacp_action=$link_type&user_info=$contact&validate=$validate_checkout&c=$encrypted_coupon_code";
	} else {
		$checkout_link_track = "$site_url/?wacp_action=$link_type&user_info=$contact&validate=$validate_checkout";
	}

	$wpdb->update( // phpcS:ignore
		WCAP_TINY_URLS,
		array( 'long_url' => $checkout_link_track ),
		array( 'id' => $insert_id )
	);

	return $insert_id;
}

/**
 * Set Cart Session variables.
 *
 * @param string $session_key Key of the session.
 * @param string $session_value Value of the session.
 * @since 7.11.0
 */
function wcap_set_cart_session( $session_key, $session_value ) {
	if ( ! WC()->session ) {
		WC()->initialize_session();
	}
	WC()->session->set( $session_key, $session_value );
}

/**
 * Get Cart Session variables.
 *
 * @param string $session_key Key of the session.
 * @return mixed Value of the session.
 * @since 7.11.0
 */
function wcap_get_cart_session( $session_key ) {
	if ( ! is_object( WC()->session ) ) {
			return false;
	}
	return WC()->session->get( $session_key );
}

/**
 * Delete Cart Session variables.
 *
 * @param string $session_key Key of the session.
 * @since 7.11.0
 */
function wcap_unset_cart_session( $session_key ) {
	WC()->session->__unset( $session_key );
}

/**
 * Returns the Cart History Data.
 *
 * @param int $cart_id - Abandoned Cart ID.
 * @return object $cart_history - From the Abandoned Cart History table.
 * @since 8.7.0
 */
function wcap_get_data_cart_history( $cart_id, $force_check = false ) {
	global $wpdb;

	$cart_history = $wpdb->get_results( // phpcs:ignore
		$wpdb->prepare(
			'SELECT id, user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored, recovered_cart, user_type, language, checkout_link FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' WHERE id = %d', // phpcs:ignore
			$cart_id
		)
	);

	if ( is_array( $cart_history ) && count( $cart_history ) > 0 ) {
		if ( $force_check ) {
			$user_id   = 0;
			$user_type = '';
			if ( isset( $cart_history[0]->user_id ) && '' !== $cart_history[0]->user_id ) {
				$user_id = $cart_history[0]->user_id;
			}
			if ( isset( $cart_history[0]->user_type ) && '' !== $cart_history[0]->user_type ) {
				$user_type = $cart_history[0]->user_type;
			}

			if ( $user_id >= 63000000 && 'GUEST' === $user_type ) {
				$guest_data = wcap_get_data_guest_history( $user_id );
				return array(
					'cart_history' => $cart_history[0],
					'guest_data'   => $guest_data,
				);
			} else {
				return false;
			}
		}
		return $cart_history[0];
	} else {
		return false;
	}
}

/**
 * Returns the Guest Data.
 *
 * @param int $user_id - Guest User ID.
 * @return object $guest_data - From the Guest History table.
 * @since 8.7.0
 */
function wcap_get_data_guest_history( $user_id ) {

	global $wpdb;

	$guest_data = $wpdb->get_results( // phpcs:ignore
		$wpdb->prepare(
			'SELECT * FROM ' . WCAP_GUEST_CART_HISTORY_TABLE . ' WHERE id = %d', // phpcs:ignore
			$user_id
		)
	);

	if ( is_array( $guest_data ) && count( $guest_data ) > 0 ) {
		return $guest_data[0];
	} else {
		return false;
	}
}

/**
 * Return an array of product details.
 *
 * @param string $cart_data - Abandoned Cart Data frm the Cart History table.
 * @return array $product_details - Product Details.
 * @since 8.7.0
 */
function wcap_get_product_details( $cart_data, $show_attributes = false ) {

	$product_details = array();
	$cart_value      = json_decode( stripslashes( $cart_data ) );

	if ( isset( $cart_value->cart ) && count( get_object_vars( $cart_value->cart ) ) > 0 ) {
		foreach ( $cart_value->cart as $product_data ) {
			$product_id   = $product_data->variation_id > 0 ? $product_data->variation_id : $product_data->product_id;
			$product_name = get_the_title( $product_id );
			$_product     = wc_get_product( $product_id );
			if ( $show_attributes && $product_data->variation_id > 0 ) {
				$variation_attributes = $_product->get_variation_attributes();
				// Loop through each selected attributes.
				foreach ( $variation_attributes as $attribute_taxonomy => $term_slug ) {
					$taxonomy       = str_replace( 'attribute_', '', $attribute_taxonomy ); // Get product attribute name or taxonomy.
					$attribute_name = wc_attribute_label( $taxonomy, $_product ); // The label name from the product attribute.

					if ( taxonomy_exists( $taxonomy ) ) { // The term name (or value) from this attribute.
						$attribute_value = get_term_by( 'slug', $term_slug, $taxonomy )->name;
					} else {
						$attribute_value = $term_slug; // For custom product attributes.
					}
					$product_name .= '<br>' . "$attribute_name: $attribute_value";
				}
			}
			$line_tax = isset( $product_data->line_tax ) && $product_data->line_tax > 0 ? $product_data->line_tax : 0;
			$details  = (object) array(
				'product_id'    => $product_data->product_id,
				'variation_id'  => $product_data->variation_id,
				'product_name'  => $product_name,
				'line_subtotal' => isset( $product_data->line_subtotal ) ? $product_data->line_subtotal : 0,
				'quantity'      => $product_data->quantity,
				'line_tax'      => $line_tax,
			);
			array_push( $product_details, $details );
		}
	}

	return $product_details;
}

/**
 * Return ATC template data.
 *
 * @param int $id - Template ID.
 * @return array|false - Results array.
 * @since 8.10.0
 */
function wcap_get_atc_template( $id ) {
	global $wpdb;

	$results = $wpdb->get_results( // phpcs:ignore
		$wpdb->prepare(
			'SELECT * FROM ' . WCAP_ATC_RULES_TABLE . ' WHERE id = %d', // phpcs:ignore
			absint( $id )
		)
	);

	if ( is_array( $results ) && count( $results ) > 0 ) {
		return $results[0];
	} else {
		return false;
	}
}

/**
 * Return active ATC templates.
 *
 * @return array $results - ATC Template Data.
 * @since 8.10.0
 */
function wcap_get_active_popup_templates( $popup_type = 'atc' ) {
	global $wpdb;

	$results = $wpdb->get_results( // phpcs:ignore
		$wpdb->prepare(
			'SELECT * FROM ' . WCAP_ATC_RULES_TABLE . " WHERE is_active = '1' AND popup_type=%s", // phpcs:ignore
			$popup_type
		)
	);

	if ( is_array( $results ) && count( $results ) > 0 ) {
		return $results;
	} else {
		return false;
	}
}

/**
 * Return ATC status.
 *
 * @return bool true | false.
 * @since 8.10.0
 */
function wcap_get_popup_active_status( $type='atc' ) {
	if ( is_user_logged_in() && 'atc' === $type ) {
		return false;
	}
	global $wpdb;

	$count = $wpdb->get_var( // phpcs:ignore
		$wpdb->prepare(
			'SELECT count(id) FROM ' . WCAP_ATC_RULES_TABLE . " WHERE is_active = '1' AND popup_type=%s", // phpcs:ignore
			$type
		)
	);
	$count = apply_filters( 'wcap_return_popup_count', $count, $type );
	if ( $count > 0 ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Return ATC email mandatory status.
 *
 * @return bool true | false.
 * @since 8.10.0
 */
function wcap_get_atc_email_mandatory_status() {
	global $wpdb;

	$mandatory = false;

	$atc_templates = $wpdb->get_results( // phpcs:ignore
		'SELECT frontend_settings FROM ' . WCAP_ATC_RULES_TABLE . " WHERE is_active = '1' AND popup_type='atc'" // phpcs:ignore
	);

	if ( $atc_templates > 0 ) {
		foreach ( $atc_templates as $settings ) {
			$decoded        = json_decode( $settings->frontend_settings );
			$temp_mandatory = $decoded->wcap_atc_mandatory_email;
			if ( 'on' === $temp_mandatory ) {
				$mandatory = true;
			}
		}
	}
	return $mandatory;
}

/**
 * Return ATC coupon status.
 *
 * @return bool true | false.
 * @since 8.10.0
 */
function wcap_get_atc_coupon_status() {
	global $wpdb;

	$atc_coupon_status = false;

	$atc_templates = $wpdb->get_results( // phpcs:ignore
		'SELECT coupon_settings FROM ' . WCAP_ATC_RULES_TABLE . " WHERE is_active = '1' AND popup_type='atc'" // phpcs:ignore
	);

	if ( $atc_templates > 0 ) {
		foreach ( $atc_templates as $settings ) {
			$decoded        = json_decode( $settings->coupon_settings );
			$atc_coupon = $decoded->wcap_atc_auto_apply_coupon_enabled;
			if ( 'on' === $atc_coupon ) {
				$atc_coupon_status = true;
			}
		}
	}
	return $atc_coupon_status;
}

/**
 * Return ATC coupon msg display on Cart page.
 *
 * @return bool true | false.
 * @since 8.10.0
 */
function wcap_get_atc_coupon_msg_cart() {

	global $wpdb;

	$atc_msg_status = false;

	$atc_templates = $wpdb->get_results( // phpcs:ignore
		'SELECT coupon_settings FROM ' . WCAP_ATC_RULES_TABLE . " WHERE is_active = '1'" // phpcs:ignore
	);

	if ( $atc_templates > 0 ) {
		foreach ( $atc_templates as $settings ) {
			$decoded        = json_decode( $settings->coupon_settings );
			$atc_coupon = $decoded->wcap_countdown_cart;
			if ( 'on' === $atc_coupon ) {
				$atc_msg_status = true;
			}
		}
	}
	return $atc_msg_status;
}

/**
 * Return ATC template settings for page ID.
 *
 * @param int $page_id - Page ID.
 * @return array $template_settings - Template Settings.
 * @since 8.10.0
 */
function wcap_get_popup_template_for_page( $page_id, $type = 'atc' ) {
	$active_templates = wcap_get_active_popup_templates( $type );
	
	$template_match = array();
	$match_found    = false;
	$match_rule     = array();
	$page_check     = 'atc' === $type ? '( is_product_category() || is_product() || is_shop() )' : true;
	// Get the active ATC templates.
	if ( is_array( $active_templates ) && count( $active_templates ) > 0 ) {
		if ( count( $active_templates ) == 1 && $page_check ) { // No match & a single record indicate the existing record is the default one and should be used in all the pages.
			$match_found           = true;
			$template_match['id']  = $active_templates[0]->id;
			$template_match['fs']  = json_decode( $active_templates[0]->frontend_settings );
			$template_match['cs']  = json_decode( $active_templates[0]->coupon_settings );
			$template_match['qck'] = json_decode( $active_templates[0]->quick_checkout_settings );
			$template_match['single_no_rules'] = true;
		} else {
			foreach ( $active_templates as $template_data ) {
				$match_rule = array();
				$rules      = isset( $template_data->rules ) ? json_decode( $template_data->rules ) : array();
				$match      = isset( $template_data->match_rules ) ? $template_data->match_rules : 'all';
				// if rules are found for the template.
				if ( count( $rules ) > 0 ) {
					foreach ( $rules as $rule_list ) {
						if ( '' !== $rule_list->rule_type && is_array( $rule_list->rule_value ) && count( $rule_list->rule_value ) > 0 ) {
							if ( 'includes' === $rule_list->rule_condition ) {
								// check for each rule value based on rule type.
								if ( in_array( $page_id, $rule_list->rule_value ) ) {
									$template_match['id']  = $template_data->id;
									$template_match['fs']  = json_decode( $template_data->frontend_settings );
									$template_match['cs']  = json_decode( $template_data->coupon_settings );
									$template_match['qck'] = json_decode( $active_templates[0]->quick_checkout_settings );
									if ( 'all' === $match ) {
										array_push( $match_rule, true );
									} else {
										$match_found = true;
										break;
									}
								} else {
									foreach ( $rule_list->rule_value as $page_details ) {
										switch ( $rule_list->rule_type ) {
											case 'products':
												if ( is_product() || ( function_exists( 'is_producto' ) && is_producto() ) ) {
													if ( (int) $page_id === (int) $page_details ) { // Page ID matches the rule value.
														$template_match['id']  = $template_data->id;
														$template_match['fs']  = json_decode( $template_data->frontend_settings );
														$template_match['cs']  = json_decode( $template_data->coupon_settings );
														$template_match['qck'] = json_decode( $active_templates[0]->quick_checkout_settings );
														if ( 'all' === $match ) {
															array_push( $match_rule, true );
														} else {
															$match_found = true;
															break;
														}
													} elseif ( 'all' === $match ) {
														array_push( $match_rule, false );
													}
												}
												break;
											case 'custom_pages':
												if ( (int) $page_id === (int) $page_details ) { // Page ID matches the rule value.
													$template_match['id']  = $template_data->id;
													$template_match['fs']  = json_decode( $template_data->frontend_settings );
													$template_match['cs']  = json_decode( $template_data->coupon_settings );
													$template_match['qck'] = json_decode( $active_templates[0]->quick_checkout_settings );
													if ( 'all' === $match ) {
														array_push( $match_rule, true );
													} else {
														$match_found = true;
														break;
													}
												} elseif ( 'all' === $match ) {
													array_push( $match_rule, false );
												}
												break;
											case 'product_cat':
												if ( is_product_category() || ( is_product() || ( function_exists( 'is_producto' ) && is_producto() ) ) ) {
													$category_matched = false;
													$get_the_terms    = get_the_terms( $page_id, 'product_cat' );
													foreach ( $get_the_terms as $terms ) {
														if ( (int) $terms->term_id === (int) $page_details || (int) $terms->parent === (int) $page_details ) { // Term ID (category ID) matches the rule value.
															$template_match['id']  = $template_data->id;
															$template_match['fs']  = json_decode( $template_data->frontend_settings );
															$template_match['cs']  = json_decode( $template_data->coupon_settings );
															$template_match['qck'] = json_decode( $active_templates[0]->quick_checkout_settings );
															$category_matched      = true;
															break;
														}
													}
													if ( 'all' === $match ) {
														if ( $category_matched ) {
															array_push( $match_rule, true );
														} else {
															array_push( $match_rule, false );
														}
													} elseif ( 'any' === $match && $category_matched ) {
														$match_found = true;
														break;
													}
												}
												break;
										}
									}
								}
							}
						}
					}
					if ( 'all' === $match && count( $match_rule ) > 0 && ! in_array( false, $match_rule, true ) ) {
						$match_found = true;
					}
					$match_found    = apply_filters( 'wcap_popup_rule_match', $match_found, $page_id, $type, $template_data );
					$template_match = apply_filters( 'wcap_popup_rule_match_template_select', $template_match, $page_id, $type, $template_data );
					if ( $match_found ) {
						break;
					}
				} elseif ( is_product_category() || is_product() || is_shop() ) { // default template as it doesn't have any rules and only woocommerce pages
					
					$match_found           = true;
					$template_match['id']  = $template_data->id;
					$template_match['fs']  = json_decode( $template_data->frontend_settings );
					$template_match['cs']  = json_decode( $template_data->coupon_settings );
					$template_match['qck'] = json_decode( $active_templates[0]->quick_checkout_settings );
				}
			}
		}
		// If a match is found, use that template.
		$template_settings = array();
		
		if ( $match_found ) {
			$template_settings['wcap_heading_section_text_email']           = $template_match['fs']->wcap_heading_section_text_email;
			$template_settings['wcap_heading_section_text_image']           = isset( $template_match['fs']->wcap_heading_section_text_image ) ? $template_match['fs']->wcap_heading_section_text_image : 'popup-icon.svg' ;
			$template_settings['wcap_heading_section_text_image_ei']        = isset( $template_match['fs']->wcap_heading_section_text_image_ei ) ? $template_match['fs']->wcap_heading_section_text_image_ei : 'popup-icon-1.svg' ;
			$template_settings['wcap_text_section_text']                    = $template_match['fs']->wcap_text_section_text;
			$template_settings['wcap_email_placeholder_section_input_text'] = $template_match['fs']->wcap_email_placeholder_section_input_text;
			$template_settings['wcap_button_section_input_text']            = $template_match['fs']->wcap_button_section_input_text;
			$template_settings['wcap_button_color_picker']                  = $template_match['fs']->wcap_button_color_picker;
			$template_settings['wcap_button_text_color_picker']             = $template_match['fs']->wcap_button_text_color_picker;
			$template_settings['wcap_popup_text_color_picker']              = $template_match['fs']->wcap_popup_text_color_picker;
			$template_settings['wcap_popup_heading_color_picker']           = $template_match['fs']->wcap_popup_heading_color_picker;
			$template_settings['wcap_non_mandatory_text']                   = $template_match['fs']->wcap_non_mandatory_text;
			$template_settings['wcap_atc_mandatory_email']                  = $template_match['fs']->wcap_atc_mandatory_email;
			$template_settings['wcap_atc_capture_phone']                    = isset( $template_match['fs']->wcap_atc_capture_phone ) ? $template_match['fs']->wcap_atc_capture_phone : 'off';
			$template_settings['wcap_switch_atc_phone_mandatory']           = isset ( $template_match['fs']->wcap_switch_atc_phone_mandatory ) ? $template_match['fs']->wcap_switch_atc_phone_mandatory : 'off';
			$template_settings['wcap_phone_placeholder']                    = isset( $template_match['fs']->wcap_atc_phone_placeholder ) ? $template_match['fs']->wcap_atc_phone_placeholder : 'Phone number (e.g. +19876543210)';
			$template_settings['template_id']                               = $template_match['id'];
			$template_settings['wcap_atc_auto_apply_coupon_enabled']        = $template_match['cs']->wcap_atc_auto_apply_coupon_enabled;
			$template_settings['wcap_atc_coupon_type']                      = $template_match['cs']->wcap_atc_coupon_type;
			$template_settings['wcap_atc_popup_coupon']                     = $template_match['cs']->wcap_atc_popup_coupon;
			$template_settings['wcap_countdown_cart']                       = $template_match['cs']->wcap_countdown_cart;
			$template_settings['wcap_atc_popup_coupon_validity']            = $template_match['cs']->wcap_atc_popup_coupon_validity;
			$template_settings['wcap_countdown_timer_msg']                  = htmlspecialchars( $template_match['cs']->wcap_countdown_timer_msg );
			$template_settings['wcap_countdown_msg_expired']                = $template_match['cs']->wcap_countdown_msg_expired;
			$template_settings['wcap_quick_ck_heading']                     = isset( $template_match['qck']->wcap_quick_ck_modal_heading ) ? $template_match['qck']->wcap_quick_ck_modal_heading : 'We are sad to see you leave';
			$template_settings['wcap_quick_ck_text']                        = isset( $template_match['qck']->wcap_quick_ck_modal_text ) ? $template_match['qck']->wcap_quick_ck_modal_text : 'There are some items in your cart. These will not last long. Please proceed to checkout to complete the purchase.';
			$template_settings['wcap_quick_ck_button_text']                 = isset( $template_match['qck']->wcap_quick_ck_link_text ) ? $template_match['qck']->wcap_quick_ck_link_text : 'Complete my order!';

			$template_settings['wcap_quick_ck_heading_color']               = isset( $template_match['qck']->wcap_quick_ck_popup_heading_color_picker ) ? $template_match['qck']->wcap_quick_ck_popup_heading_color_picker : '#737f97';
			$template_settings['wcap_quick_ck_text_color']                  = isset( $template_match['qck']->wcap_quick_ck_popup_text_color_picker ) ? $template_match['qck']->wcap_quick_ck_popup_text_color_picker : '#bbc9d2';
			$template_settings['wcap_quick_ck_button_bg_color']             = isset( $template_match['qck']->wcap_quick_ck_button_color_picker ) ? $template_match['qck']->wcap_quick_ck_button_color_picker : '#0085ba';
			$template_settings['wcap_quick_ck_button_txt_color']            = isset( $template_match['qck']->wcap_quick_ck_button_text_color_picker ) ? $template_match['qck']->wcap_quick_ck_button_text_color_picker : '#ffffff';

			
			$template_settings['wcap_quick_ck_link']                        = isset( $template_match['qck']->wcap_quick_ck_redirect_to ) && '' !== $template_match['qck']->wcap_quick_ck_redirect_to ? $template_match['qck']->wcap_quick_ck_redirect_to : wc_get_checkout_url();
			$template_settings['wcap_quick_ck_force_checkout']              = isset( $template_match['qck']->wcap_quick_ck_force_user_to_checkout ) ? $template_match['qck']->wcap_quick_ck_force_user_to_checkout : 'off';
			$template_settings['wcap_enable_ei_for_registered_users']       = isset( $template_match['qck']->wcap_enable_ei_for_registered_users ) ? $template_match['qck']->wcap_enable_ei_for_registered_users : 'on';
			$template_settings['single_no_rules']                           = isset( $template_match['single_no_rules'] ) ? $template_match['single_no_rules'] : false;
		} else { // else load the default template.
			
			// @since 8.15. check if the page is category or product or shop page  , else  don't load the template if page not found.
			if ( ! ( is_product_category() || is_product() || is_shop() ) ) {
				return false;
			}

			$template_settings['wcap_heading_section_text_email']           = 'Please enter your email';
			$template_settings['wcap_heading_section_text_image']           = 'popup-icon.svg';
			$template_settings['wcap_heading_section_text_image_ei']        = 'popup-icon-1.svg';
			$template_settings['wcap_text_section_text']                    = 'To add this item to your cart, please enter your email address.';
			$template_settings['wcap_email_placeholder_section_input_text'] = 'Email Address';
			$template_settings['wcap_button_section_input_text']            = 'Add to Cart';
			$template_settings['wcap_button_color_picker']                  = '#0085ba';
			$template_settings['wcap_button_text_color_picker']             = '#ffffff';
			$template_settings['wcap_popup_text_color_picker']              = '#bbc9d2';
			$template_settings['wcap_popup_heading_color_picker']           = '#737f97';
			$template_settings['wcap_non_mandatory_text']                   = 'No Thanks';
			$template_settings['wcap_atc_mandatory_email']                  = 'off';
			$template_settings['wcap_atc_capture_phone']                    = 'off';
			$template_settings['template_id']                               = 0; // indicates default.
			$template_settings['wcap_atc_auto_apply_coupon_enabled']        = 'off';
			$template_settings['wcap_atc_coupon_type']                      = '';
			$template_settings['wcap_atc_popup_coupon']                     = 0;
			$template_settings['wcap_countdown_cart']                       = '';
			$template_settings['wcap_atc_popup_coupon_validity']            = 0;
			$template_settings['wcap_countdown_timer_msg']                  = '';
			$template_settings['wcap_countdown_msg_expired']                = '';
			$template_settings['wcap_phone_placeholder']                    = 'Phone number (e.g. +19876543210)';
			$template_settings['wcap_quick_ck_heading']                     = 'We are sad to see you leave';
			$template_settings['wcap_quick_ck_text']                        = 'There are some items in your cart. These will not last long. Please proceed to checkout to complete the purchase.';
			$template_settings['wcap_quick_ck_heading_color']               = '#737f97';
			$template_settings['wcap_quick_ck_text_color']                  = '#bbc9d2';
			$template_settings['wcap_quick_ck_button_text']                 = 'Complete my order!';
			$template_settings['wcap_quick_ck_button_bg_color']             = '#0085ba';
			$template_settings['wcap_quick_ck_button_txt_color']            = '#ffffff';
			$template_settings['wcap_quick_ck_link']                        = wc_get_checkout_url();
			$template_settings['wcap_quick_ck_force_checkout']              = 'off';
			$template_settings['wcap_enable_ei_for_registered_users']       = 'on';
			$template_settings['wcap_switch_atc_phone_mandatory']           = 'off';
		}

		if ( 'on' === get_option('wcap_enable_gdpr_consent' ) && 'opt-in' === get_option( 'wcap_select_guest_cart_tracking' ) ) {
			$wcap_email_consent_msg = get_option( 'wcap_gdpr_allow_opt_in', '' );
			$wcap_email_consent_msg = '' !== $wcap_email_consent_msg ? $wcap_email_consent_msg : __( 'I agree to receive abandoned cart reminder emails.', 'woocommerce-ac' );

			$template_settings['wcap_email_consent_checkbox'] = 'on';
			$template_settings['wcap_email_consent_message']  = $wcap_email_consent_msg;
		} else {
			$template_settings['wcap_email_consent_checkbox'] = '';
			$template_settings['wcap_email_consent_message']  = '';
		}

		// SMS consent settings if applicable.
		if ( 'on' === $template_settings['wcap_atc_capture_phone'] ) {
			if ( 'on' === get_option( 'wcap_enable_sms_consent', '' ) ) {
				$wcap_sms_consent_msg = get_option( 'wcap_sms_consent_msg', '' );
				$wcap_sms_consent_msg = '' !== $wcap_sms_consent_msg ? $wcap_sms_consent_msg : __( 'Saving your phone and cart details helps us keep you up to date with this order.', 'woocommerce-ac' );
				$wcap_sms_consent_msg = apply_filters( 'wcap_sms_consent_text', $wcap_sms_consent_msg );

				$template_settings['wcap_sms_consent_checkbox'] = 'on';
				$template_settings['wcap_sms_consent_message']  = $wcap_sms_consent_msg;
			} else {
				$template_settings['wcap_sms_consent_checkbox'] = '';
				$template_settings['wcap_sms_consent_message']  = '';
			}

			// WhatsApp Reminders
			if ( 'on' === get_option('wcap_enable_whatsapp_reminders' ) ) {
				$wcap_wa_consent_msg = get_option( 'wcap_whatsapp_consent_text', '' );
				$wcap_wa_consent_msg = '' !== $wcap_wa_consent_msg ? $wcap_wa_consent_msg : __( 'Saving your phone and cart details helps us keep you up to date with this order.', 'woocommerce-ac' );

				$template_settings['wcap_wa_consent_checkbox'] = 'on';
				$template_settings['wcap_wa_consent_message']  = $wcap_wa_consent_msg;
			} else {
				$template_settings['wcap_wa_consent_checkbox'] = '';
				$template_settings['wcap_wa_consent_message']  = '';
			}
		}
		return $template_settings;
	}
}

/**
 * ATC Template for default preview in ATC Settings.
 *
 * @param int $template_id - ATC Template ID.
 * @return array $template_settings - Template Settings.
 * @since 8.10.0
 */
function wcap_get_atc_template_preview( $template_id ) {
	$atc_template = wcap_get_atc_template( $template_id );
	
	$coupon_settings   = array();

	if ( false !== $atc_template && isset( $atc_template->frontend_settings ) ) {
		$frontend_settings                                              = json_decode( $atc_template->frontend_settings );
		$template_settings['wcap_heading_section_text_email']           = $frontend_settings->wcap_heading_section_text_email;
		$template_settings['wcap_heading_section_text_image']           = isset( $frontend_settings->wcap_heading_section_text_image ) ? $frontend_settings->wcap_heading_section_text_image : 'popup-icon.svg';
		$template_settings['wcap_heading_section_text_image_ei']        = isset( $frontend_settings->wcap_heading_section_text_image_ei ) ? $frontend_settings->wcap_heading_section_text_image_ei : 'popup-icon-1.svg';
		$template_settings['wcap_text_section_text']                    = $frontend_settings->wcap_text_section_text;
		$template_settings['wcap_email_placeholder_section_input_text'] = $frontend_settings->wcap_email_placeholder_section_input_text;
		$template_settings['wcap_button_section_input_text']            = $frontend_settings->wcap_button_section_input_text;
		$template_settings['wcap_button_color_picker']                  = $frontend_settings->wcap_button_color_picker;
		$template_settings['wcap_button_text_color_picker']             = $frontend_settings->wcap_button_text_color_picker;
		$template_settings['wcap_popup_text_color_picker']              = $frontend_settings->wcap_popup_text_color_picker;
		$template_settings['wcap_popup_heading_color_picker']           = $frontend_settings->wcap_popup_heading_color_picker;
		$template_settings['wcap_non_mandatory_text']                   = $frontend_settings->wcap_non_mandatory_text;
		$template_settings['wcap_atc_mandatory_email']                  = $frontend_settings->wcap_atc_mandatory_email;
		$template_settings['wcap_atc_capture_phone']                    = isset ( $frontend_settings->wcap_atc_capture_phone ) ? $frontend_settings->wcap_atc_capture_phone : 'off';
		$template_settings['wcap_atc_phone_placeholder']                = isset( $frontend_settings->wcap_atc_phone_placeholder ) ? $frontend_settings->wcap_atc_phone_placeholder : '';
		$template_settings['wcap_switch_atc_phone_mandatory']           = isset ( $frontend_settings->wcap_switch_atc_phone_mandatory ) ? $frontend_settings->wcap_switch_atc_phone_mandatory : 'off';
		$coupon_settings                                                = json_decode( $atc_template->coupon_settings );
	} else { // else load the default template.
		$template_settings['wcap_heading_section_text_email']           = 'Please enter your email';
		$template_settings['wcap_heading_section_text_image']           = 'popup-icon.svg';
		$template_settings['wcap_heading_section_text_image_ei']        = 'popup-icon-1.svg';
		$template_settings['wcap_text_section_text']                    = 'To add this item to your cart, please enter your email address.';
		$template_settings['wcap_email_placeholder_section_input_text'] = 'Email Address';
		$template_settings['wcap_button_section_input_text']            = 'Add to Cart';
		$template_settings['wcap_button_color_picker']                  = '#0085ba';
		$template_settings['wcap_button_text_color_picker']             = '#ffffff';
		$template_settings['wcap_popup_text_color_picker']              = '#bbc9d2';
		$template_settings['wcap_popup_heading_color_picker']           = '#737f97';
		$template_settings['wcap_non_mandatory_text']                   = 'No Thanks';
		$template_settings['wcap_atc_mandatory_email']                  = 'off';
		$template_settings['wcap_atc_phone_placeholder']                = 'Phone number (e.g. +19876543210)';
	}
	
	$template_settings['rules'] = isset( $atc_template->rules ) ? json_decode( $atc_template->rules ) : array();
	$template_settings['wcap_match_rules'] = '';
			
	
	if ( count( $template_settings['rules'] ) > 0 ) {
		foreach ( $template_settings['rules'] as &$rule ) {
							
			foreach( $rule->rule_value as $rule_key => $rule_value ) {
				$title = '';
				if (  'custom_pages' == $rule->rule_type || 'products' === $rule->rule_type ) {
					$title = get_the_title( $rule_value );
				} elseif ( 'product_cat' == $rule->rule_type ) {
					$term_object = get_term( $rule_value );
					if ( isset( $term_object->name ) ) {					
						$title = wp_kses_post( $term_object->name );
					}					
				} else {
					$title = get_the_title( $rule_value );
				}
				$rule->rule_text[$rule_key]  = $title;				
			}
				$rule->rule_selText	= implode( ', ', $rule->rule_text );		
		}
		$template_settings['wcap_match_rules'] = isset( $atc_template->match_rules ) ? $atc_template->match_rules  : '';
	
	}

	if ( false !== $atc_template && isset( $atc_template->quick_checkout_settings ) ) {
		$quick_ck = json_decode( $atc_template->quick_checkout_settings );
	}
	
	$mode              = isset( $_GET['mode'] ) ? sanitize_text_field( wp_unslash( $_GET['mode'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

	$template_name = isset( $atc_template->name ) ? $atc_template->name : '';
	$template_settings['wcap_template_name'] = 'copytemplate' === $mode ? __( 'Copy', 'woocommerce-ac' ) . ' - ' . $template_name : $template_name;
	$template_settings['wcap_template_type'] = isset( $atc_template->popup_type ) ? $atc_template->popup_type : 'atc';
	
	$template_settings['wcap_phone_placeholder_section_input_text']          = isset( $atc_template->wcap_atc_phone_placeholder ) ? $atc_template->wcap_atc_phone_placeholder : 'Phone number (e.g. +19876543210)';
	$template_settings['wcap_non_mandatory_modal_section_fields_input_text'] = isset( $atc_template->wcap_non_mandatory_text ) ? $atc_template->wcap_non_mandatory_text : __( 'No Thanks', 'woocommerce-ac' );
	
	$template_settings['wcap_ei_modal_text']  = __( 'We are sad to see you go but you can enter your email below and we will save the cart for you.', 'woocommerce-ac' );
	$template_settings['wcap_atc_modal_text'] = __( 'To add this item to your cart, please enter your email address.', 'woocommerce-ac' );
			
	$template_settings['wcap_quick_ck_heading_section_text_email'] = isset( $quick_ck->wcap_quick_ck_modal_heading ) ? $quick_ck->wcap_quick_ck_modal_heading : __( 'We are sad to see you leave', 'woocommerce-ac' );
	$template_settings['wcap_quick_ck_text_section_text']          = isset( $quick_ck->wcap_quick_ck_modal_text ) ? $quick_ck->wcap_quick_ck_modal_text :  __( 'There are some items in your cart. These will not last long. Please proceed to checkout to complete the purchase.	', 'woocommerce-ac' );
	$template_settings['wcap_quick_ck_button_section_input_text']  = isset( $quick_ck->wcap_quick_ck_link_text ) ? $quick_ck->wcap_quick_ck_link_text :  __( 'Complete my order!', 'woocommerce-ac' );
	$template_settings['wcap_quick_ck_redirect_to']                = isset( $quick_ck->wcap_quick_ck_redirect_to ) ? $quick_ck->wcap_quick_ck_redirect_to :  wc_get_checkout_url();
	
	
	$template_settings['wcap_quick_ck_popup_heading_color_picker'] = isset( $quick_ck->wcap_quick_ck_popup_heading_color_picker ) ? $quick_ck->wcap_quick_ck_popup_heading_color_picker : '#737f97';
	$template_settings['wcap_quick_ck_popup_text_color_picker']    = isset( $quick_ck->wcap_quick_ck_popup_text_color_picker ) ? $quick_ck->wcap_quick_ck_popup_text_color_picker : '#bbc9d2';
	$template_settings['wcap_quick_ck_button_color_picker']        = isset( $quick_ck->wcap_quick_ck_button_color_picker ) ? $quick_ck->wcap_quick_ck_button_color_picker : '#0085ba';
	$template_settings['wcap_quick_ck_button_text_color_picker']   = isset( $quick_ck->wcap_quick_ck_button_text_color_picker ) ? $quick_ck->wcap_quick_ck_button_text_color_picker : '#ffffff';
	$template_settings['wcap_quick_ck_force_user_to_checkout']     = isset( $quick_ck->wcap_quick_ck_force_user_to_checkout ) ? $quick_ck->wcap_quick_ck_force_user_to_checkout : 'off';
	$template_settings['wcap_enable_ei_for_registered_users']      = isset( $quick_ck->wcap_enable_ei_for_registered_users ) ? $quick_ck->wcap_enable_ei_for_registered_users : 'on';	
	
	
	$template_settings['template_name']                  = isset( $template_settings->name ) ? $template_settings->name : '';
	$template_settings['template_name']                  = 'copytemplate' === $mode ? __( 'Copy', 'woocommerce-ac' ) . ' - ' . $template_settings['template_name']  : $template_settings['template_name'] ;					
	
	$template_settings['auto_apply_coupon']              = isset( $coupon_settings->wcap_atc_auto_apply_coupon_enabled ) ? $coupon_settings->wcap_atc_auto_apply_coupon_enabled : 'off';
	$template_settings['active_text']                    = __( $template_settings['auto_apply_coupon'], 'woocommerce-ac' ); // phpcs:ignore

	$template_settings['wcap_atc_coupon_type']           = isset( $coupon_settings->wcap_atc_coupon_type ) ? $coupon_settings->wcap_atc_coupon_type : '';
	
	$coupon_id                     = isset( $coupon_settings->wcap_atc_popup_coupon ) ? $coupon_settings->wcap_atc_popup_coupon : 0;
$template_settings['coupon_ids'] = array();
if( ! empty ( $coupon_id ) ) {
		$template_settings['coupon_ids'][ $coupon_id ] = get_the_title( $coupon_id );	
}



	$template_settings['wcap_atc_discount_type']         = isset( $coupon_settings->wcap_atc_discount_type ) ? $coupon_settings->wcap_atc_discount_type : '';
	
	$template_settings['wcap_atc_discount_amount']       = isset( $coupon_settings->wcap_atc_discount_amount ) ? $coupon_settings->wcap_atc_discount_amount : '';
	$template_settings['wcap_atc_coupon_free_shipping']  = isset( $coupon_settings->wcap_atc_coupon_free_shipping ) ? $coupon_settings->wcap_atc_coupon_free_shipping : '';
	
	$template_settings['wcap_atc_coupon_validity']    = isset( $coupon_settings->wcap_atc_popup_coupon_validity ) ? $coupon_settings->wcap_atc_popup_coupon_validity : '';
	$template_settings['wcap_countdown_msg']   = isset( $coupon_settings->wcap_countdown_timer_msg ) ? htmlspecialchars_decode( $coupon_settings->wcap_countdown_timer_msg ) : htmlspecialchars_decode( 'Coupon <coupon_code> expires in <hh:mm:ss>. Avail it now.' );
	$template_settings['wcap_countdown_msg_expired']  = isset( $coupon_settings->wcap_countdown_msg_expired ) ? $coupon_settings->wcap_countdown_msg_expired : 'The offer is no longer valid.';
	$template_settings['wcap_countdown_timer_cart']         = isset( $coupon_settings->wcap_countdown_cart ) ? $coupon_settings->wcap_countdown_cart : 'on';
	$template_settings['active_cart']                 = __( $template_settings['wcap_countdown_timer_cart'], 'woocommerce-ac' ); 
	$template_settings['wcap_auto_apply_coupons_atc'] = isset( $coupon_settings->wcap_atc_auto_apply_coupon_enabled ) ? $coupon_settings->wcap_atc_auto_apply_coupon_enabled : '' ; 
	
	$template_settings['rule_types'] = array(
		''  => __( 'Select Rule Type', 'woocommerce-ac' ),
		'custom_pages'     => __( 'Pages', 'woocommerce-ac' ),
		'product_cat'      => __( 'Product Categories', 'woocommerce-ac' ),
		'products'         => __( 'Products', 'woocommerce-ac' ),
	);
	$template_settings['rule_conditions'] = array(
		''       => __( 'Select Condition', 'woocommerce-ac' ),
		'includes'              => __( 'Includes any of', 'woocommerce-ac' ),
		'excludes'              => __( 'Excludes any of', 'woocommerce-ac' ),
	);
	
	
	$template_settings['mode']        = $mode;
	if ( 'copytemplate' === $mode ) {
		$template_id = '';
	}
	$template_settings['template_id'] = $template_id;	
	$template_settings['save_mode']   = 'edittemplate' === $mode ? 'update' : 'save';	
	
	return $template_settings;
}

/**
 * Create list of products, custom pages & products categories for popup templates & page.
 *
 * @param array $template_settings - Popup Template settings.
 * @param int   $page_id - Page ID being loaded.
 * @param int   $parent_id - Parent ID of the page.
 * @return array List of allowed & disallowed products, categories & pages.
 *
 * @since 8.14.0
 */
function wcap_popup_display_list( $template_settings, $page_id, $parent_id ) {

	// Create a list of products, categories & pages based on the rules for ATC.
	$custom_pages     = array();
	$custom_pages_exc = array();
	$included_cat     = array();
	$excluded_cat     = array();
	$include          = array();
	$exclude          = array();
	if ( is_array( $template_settings ) && count( $template_settings ) > 0 ) {
		$template_id = $template_settings['template_id'];
		if ( $template_id > 0 ) {
			$template_data = wcap_get_atc_template( $template_id );
			$rules         = isset( $template_data->rules ) ? json_decode( $template_data->rules ) : array();
			$match         = isset( $template_data->match_rules ) ? $template_data->match_rules : 'all';
			if ( count( $rules ) > 0 ) {
				foreach ( $rules as $rule_list ) {
					if ( '' !== $rule_list->rule_type && is_array( $rule_list->rule_value) && count( $rule_list->rule_value ) > 0 ) {
						switch ( $rule_list->rule_type ) {
							case 'custom_pages':
								if ( 'includes' === $rule_list->rule_condition ) {
									foreach ( $rule_list->rule_value as $page_name ) {
										array_push( $custom_pages, $page_name ); // Create a list of custom pages for which ATC is enabled.
									}
								} elseif ( 'excludes' === $rule_list->rule_condition ) {
									foreach( $rule_list->rule_value as $page_name ) {
										array_push( $custom_pages_exc, $page_name ); // Create a list of custom pages for which popup is excluded.
									}
								}
								break;
							case 'product_cat':
								if ( 'includes' === $rule_list->rule_condition ) {
									foreach ( $rule_list->rule_value as $id ) {
										$included_cat[] = $id; // Included product category list.
									}
								} elseif ( 'excludes' === $rule_list->rule_condition ) {
									foreach ( $rule_list->rule_value as $id ) {
										$excluded_cat[] = $id; // Excluded product category list.
									}
								} 
								break;
							case 'products':
								if ( 'includes' === $rule_list->rule_condition ) {
									foreach ( $rule_list->rule_value as $id ) {
										$include[] = $id; // Included product list.
									}
								} elseif ( 'excludes' === $rule_list->rule_condition ) {
									foreach ( $rule_list->rule_value as $id ) {
										$exclude[] = $id; // Excluded product list.
									}
								}
								break;
						}
					}
				}
				// If all the rules need to be met, we need to compare and remove data as needed.
				if ( 'all' === $match ) {
					if ( count( $include ) > 0 ) {
						$included_cat = array(); // reset the category as the product will get precedence.
					}
					if ( count( $exclude ) > 0 ) {
						$excluded_cat = array(); // reset the category as the product will get precedence.
					}
				}

				if ( ( count( $exclude ) > 0 && in_array( $page_id, $exclude ) ) || ( count( $include ) > 0 && ! in_array( $page_id, $include ) ) ) { // phpcs:ignore
					return false;
				}
			}
		}
	}
	$allowed_products = array();
	if ( count( $included_cat ) > 0 ) {
		foreach ( $included_cat as $id ) {
			$all_ids = get_posts(
				array(
					'post_type'   => 'product',
					'numberposts' => -1,
					'post_status' => 'publish',
					'fields'      => 'ids',
					'tax_query'   => array( // phpcs:ignore
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'id',
							'terms'    => $id,
							'operator' => 'IN',
						),
					),
				)
			);

			foreach ( $all_ids as $id ) {
				$allowed_products[] = (int) $id;
			}

		}
	}
	if ( count( $include ) > 0 ) {
		foreach ( $include as $id ) {
			$allowed_products[] = (int) $id;
		}
	}
	$allowed_products = count( $allowed_products ) > 0 ? array_unique( $allowed_products ) : $allowed_products;

	$disallowed_products = array();
	if ( count( $excluded_cat ) > 0 ) {
		foreach ( $excluded_cat as $id ) {

			$all_ids = get_posts(
				array(
					'post_type'   => 'product',
					'numberposts' => -1,
					'post_status' => 'publish',
					'fields'      => 'ids',
					'tax_query'   => array( // phpcs:ignore
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'id',
							'terms'    => $id,
							'operator' => 'IN',
						),
					),
				)
			);

			foreach ( $all_ids as $id ) {
				$disallowed_products[] = (int) $id;
			}
		}
	}
	if ( count( $exclude ) > 0 ) {
		foreach ( $exclude as $id ) {
			$disallowed_products[] = (int) $id;
		}
	}
	$disallowed_products = count( $disallowed_products ) > 0 ? array_unique( $disallowed_products ) : $disallowed_products;
	if ( ( count( $included_cat ) > 0 && ( ( ! in_array( $page_id, $allowed_products ) && ! in_array( $page_id, $included_cat ) ) || ( $parent_id > 0 && ! in_array( $parent_id, $included_cat ) ) ) ) || ( count( $excluded_cat ) > 0 && ( ( in_array( $page_id, $disallowed_products ) && in_array( $page_id, $excluded_cat ) ) || ( $parent_id > 0 && in_array( $parent_id, $excluded_cat ) ) ) ) ) {
		return false;
	}
	return array(
		'template_settings'   => $template_settings,
		'custom_pages'        => $custom_pages,
		'custom_pages_exc'    => $custom_pages_exc,
		'allowed_products'    => $allowed_products,
		'disallowed_products' => $disallowed_products,
	);

}

/**
 * Get Abandoned Cart ID using User ID.
 *
 * @param int $user_id - User ID.
 * @return int $abandoned_id - Abandoned Cart ID.
 *
 * @since 8.15.0
 */
function wcap_get_abandoned_id_from_user_id( $user_id ) {

	$abandoned_id = 0;
	global $wpdb;
	if ( $user_id > 0 ) {
		$abandoned_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' WHERE user_id = %s',
				$user_id
			)
		);
	}
	return $abandoned_id;
}

/**
 * Get the notification template by ID.
 *
 * @param int $template_id - Template ID.
 * @return object $template_data - Template Data.
 * @since 8.21.0
 */
function wcap_get_template_data_by_id( $template_id ) {
	global $wpdb;
	$template_data = array();
	if ( $template_id > 0 ) {
		$template_data = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . WCAP_NOTIFICATION_TEMPLATES_TABLE . ' WHERE id = %d',
				(int) $template_id
			)
		);
	}
	return $template_data;
}

/**
* Returns if HPOS is enabled
*
* @return bool
* @since 8.22.0
*/
function is_hpos_enabled() {

	if ( version_compare( WOOCOMMERCE_VERSION, '7.1.0' ) < 0 ) {
			return false;
	}

	if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
		return true;
	}	

	return false;		
}
/**
* Copy the ATC popup default images from the plugin folder to the uploads folder.
*
* @return bool
* @since 8.22.0
*/
function wcap_copy_atc_popup_images_to_uploads_dir() {
    $success = false;
    $images  = array( 'popup-icon.svg', 'popup-icon-1.svg' );
    foreach ( $images as $image) {
        $plugin_image_path = WCAP_PLUGIN_PATH . '/assets/images/' . $image;
        $destination       = WCAP_WP_UPLOADS_PATH . '/' . $image;
        if ( file_exists( $plugin_image_path ) ) {
            if ( ! file_exists( $destination ) ) {
	            if ( copy( $plugin_image_path, $destination ) ) {
	                $success = true;
	            }
	        }
        }
    }
    return $success;
}
