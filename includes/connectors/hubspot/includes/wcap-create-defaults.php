<?php
/**
 * Create Default Setup in Hubspot.
 *
 * @package Connectors/Hubspot/Actions
 */

/**
 * Create Default Setup in Hubspot.
 */
class Wcap_Hubspot_Create_Default_Setup_Action {

	/**
	 * Class Instance.
	 *
	 * @var $ins
	 */
	private static $ins = null;

	public $custom_properties = array();

	/**
	 * Construct.
	 */
	public function __construct() {
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_hubspot' );
		$this->wcap_setup_custom_properties_list();
	}

	/**
	 * Get instance.
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function wcap_create_default_setup( $settings ) {

		// Fetch the connector data.
		$connector_settings   = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_hubspot' );
		$api_key              = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
		$status               = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';
		$property_grp_created = isset( $connector_settings['prp_grp_created'] ) ? $connector_settings['prp_grp_created'] : false;
		$properties_created   = isset( $connector_settings['prp_created'] ) ? $connector_settings['prp_created'] : false;
		$list_created         = isset( $connector_settings['list_created'] ) ? $connector_settings['list_created'] : false;
		$workflow_created     = isset( $connector_settings['workflow_id'] ) ? $connector_settings['workflow_id'] : false;

		$create_property     = false;
		$create_list         = false;
		$create_workflow     = false;
		$collective_response = array();
		// Check if Property Group exists in Hubspot.
		$grp_exists = $this->wcap_check_group_exists( $api_key, $property_grp_created );
		if ( ! $grp_exists ) { // group is not present in HB.
			// Create the property group.
			$res_grp = $this->wcap_create_default_abandoned_cart_property_grp( $api_key );
			if ( $res_grp ) {
				$collective_response['property_grp']['status'] = $res_grp['status'];
				$collective_response['property_grp']['message'] = $res_grp['msg'];
				$collective_response['property_grp']['grp_id'] = $res_grp['grp_id'];
				$create_property = 'success' === $res_grp['status'] ? true : false;
			}
		} else {
			$collective_response['property_grp']['status'] = 'success';
			$collective_response['property_grp']['message'] = '';
			$collective_response['property_grp']['grp_id'] = $property_grp_created > 0 ? $property_grp_created : true;
			$create_property = true;
		}

		// Create the properties.
		if ( $create_property && 'active' !== $status && ! $properties_created ) {
			$res_prop = $this->wcap_create_default_abandoned_cart_properties( $api_key );
			if ( $res_prop ) {
				$collective_response['properties']['status'] = $res_prop['status'];
				$collective_response['properties']['msg'] = $res_prop['msg'];
				$create_list = 'success' === $res_prop['status'] ? true : false;
			}
		}

		// Check if list exists.
		$list_id = $this->wcap_check_list_exists( $api_key, $list_created );
		if ( ! $list_id ) {
			// Create the list.
			$res_list = $this->wcap_create_default_abandoned_cart_list( $api_key );
			if ( $res_list ) {
				$collective_response['list']['status'] = $res_list['status'];
				$collective_response['list']['msg'] = $res_list['msg'];
				$collective_response['list']['list_id'] = $res_list['list_id'];
			}
		} else {
			// Based on the results.
			$collective_response['list']['status'] = 'success';
			$collective_response['list']['msg'] = '';
			$collective_response['list']['list_id'] = $list_id;
		}

		// Create the workflow.
		$wkflw_id = $this->wcap_check_workflow_exists( $api_key, $workflow_created );
		if ( ! $wkflw_id ) {
			$res_wkfl = $this->wcap_create_default_abandoned_cart_workflow( $api_key );
			if ( $res_wkfl ) {
				$collective_response['workflow']['status'] = $res_wkfl['status'];
				$collective_response['workflow']['msg'] = $res_wkfl['msg'];
				$collective_response['workflow']['workflow_id'] = $res_wkfl['workflow_id'];
			}
		} else {
			$collective_response['workflow']['status'] = 'success';
			$collective_response['workflow']['msg'] = '';
			$collective_response['workflow']['workflow_id'] = $wkflw_id;
		}

		return $collective_response;
	}

	public function wcap_check_group_exists( $api_key ) {

		$params = array(
			'api_key' => $api_key,
			'name' => 'wcapgroup',
		);
		
		// Check  if the list exists in Hubspot.
		$connector_hb = Wcap_Hubspot::get_instance();
		$call         = $connector_hb->registered_calls['wcap_hubspot_get_property_group'];
				
		$call->set_data( $params );
		$response = $call->process();
		$resp     = wp_remote_retrieve_body( $response );
		if ( isset( $resp['status'] ) && 'error' === $resp['status'] ) {
			return false;
		} elseif ( isset( $resp['name'] ) && 'wcapgroup' === $resp['name'] ) {
			return true;
		}

		return false;

	}

	public function wcap_check_property_exists( $api_key, $name ) {

		$params = array(
			'api_key' => $api_key,
			'name' => $name,
		);
		
		// Check  if the list exists in Hubspot.
		$connector_hb = Wcap_Hubspot::get_instance();
		$call         = $connector_hb->registered_calls['wcap_hubspot_get_property'];
				
		$call->set_data( $params );
		$response = $call->process();
		$resp     = wp_remote_retrieve_body( $response );
		if ( isset( $resp['status'] ) && 'error' === $resp['status'] ) {
			return false;
		} elseif ( isset( $resp['name'] ) && $name === $resp['name'] ) {
			return true;
		}

		return false;

	}

	public function wcap_check_list_exists( $api_key, $existing_id ) {

		if ( $existing_id > 0 ) {
			$params = array(
				'api_key' => $api_key,
				'list_id' => $existing_id,
			);
		} else {
			$params = array(
				'api_key' => $api_key
			);
		}
		// Check  if the list exists in Hubspot.
		$connector_hb = Wcap_Hubspot::get_instance();
		$call         = $connector_hb->registered_calls['wcap_hubspot_get_list'];
			
		$call->set_data( $params );
		$response = $call->process();
		$resp     = wp_remote_retrieve_body( $response );
		if ( isset( $resp['lists'] ) && count( $resp['lists'] ) > 0 ) {
			foreach( $resp['lists']  as $list_data ) {
				if ( 'Abandoned Carts List' == $list_data['name'] ) {
					$list_id = $list_data['listId'];
					break;
				}
			}
		} elseif ( isset( $resp['listId'] ) ) {
			$list_id = $resp['listId'];
		}

		if ( isset( $list_id ) && $list_id > 0 ) {
			return $list_id;
		} else {
			return false;
		}

	}

	public function wcap_check_workflow_exists( $api_key, $existing_id ) {

		if ( $existing_id > 0 ) {
			$params = array(
				'api_key' => $api_key,
				'workflow_id' => $existing_id,
			);
		} else {
			$params = array(
				'api_key' => $api_key
			);
		}
		// Check  if the list exists in Hubspot.
		$connector_hb = Wcap_Hubspot::get_instance();
		$call         = $connector_hb->registered_calls['wcap_hubspot_get_workflow'];
			
		$call->set_data( $params );
		$response = $call->process();
		$resp     = wp_remote_retrieve_body( $response );
		if ( isset( $resp['workflows'] ) && count( $resp['workflows'] ) > 0 ) {
			foreach( $resp['workflows']  as $wk_data ) {
				if ( 'Abandoned Carts Workflow' == $wk_data['name'] ) {
					$wkfl_id = $wk_data['migrationStatus']['workflowId'];
					break;
				}
			}
		} elseif ( isset( $resp['migrationStatus']['workflowId'] ) ) {
			$wkfl_id = $resp['migrationStatus']['workflowId'];
		}

		if ( isset( $wkfl_id ) && $wkfl_id > 0 ) {
			return $wkfl_id;
		} else {
			return false;
		}
		
	}

	public function wcap_create_default_abandoned_cart_property_grp( $api_key = '' ) {

		if ( '' !== $api_key ) {
			$connector_hb = Wcap_Hubspot::get_instance();
			$call         = $connector_hb->registered_calls['wcap_hubspot_create_property_group'];
			
			$params = array(
				'api_key' => $api_key,
				'name' => 'wcapgroup',
				'displayName' => 'Abandoned Cart Details from AC Pro',
			);

			$call->set_data( $params );
			$response = $call->process();
			$resp     = wp_remote_retrieve_body( $response );
			if ( isset( $response['response'] ) && 200 != absint( $response['response'] ) ) {
				$result['status'] = isset( $resp['status'] ) ? $resp['status'] : '';
				$result['msg'] = isset( $resp['message'] ) ? $resp['message'] : '';
				$result['grp_id'] = 0;
			} else {
				$result['status'] = 'success';
				$result['msg']    = '';
				$result['grp_id'] = $resp['portalId'];
			}
			return $result;
		}
		return false;
	}

	public function wcap_create_default_abandoned_cart_properties( $api_key = '' ) {

		if ( '' !== $api_key ) {
			$connector_hb = Wcap_Hubspot::get_instance();
			$call         = $connector_hb->registered_calls['wcap_hubspot_create_property'];

			$property_result = array();
			foreach ( $this->custom_properties as $prop_name => $prop_details ) {
				$name = $prop_details['name'];
				// Create only if not present.
				$exists = $this->wcap_check_property_exists( $api_key, $name );
				if ( ! $exists ) {
					$label = $prop_details['label'];
					$desc  = $prop_details['description'];
					$type  = $prop_details['type'];
					$field_type = $prop_details['fieldType'];
					$disp_order = $prop_details['displayOrder'];

					$params = array(
						'api_key' => $api_key,
						'name' => $name,
						'label' => $label,
						'description' => $desc,
						'groupName' => 'wcapgroup',
						'type' => $type,
						'fieldType' => $field_type,
						'formField' => true,
						'displayOrder' => $disp_order,
						'options' => []
					);

					$call->set_data( $params );
					$response = $call->process();

					if ( isset( $response['response'] ) && 200 != absint( $response['response'] ) ) {
						$property_result[ $name ] = 'error';
					} else {
						$property_result[ $name ] = 'success';
					}
				} else {
					$property_result[ $name ] = 'success';
				}
			}
		
			if ( in_array( 'error', $property_result ) ) {
				$result['status'] = 'error';
				$result['msg']    = __( 'Creation of one or more properties failed. Please try again.', 'woocommerce-ac' );
			} else {
				$result['status'] = 'success';
				$result['msg'] = '';
			}

			return $result;
		}
		return false;
	}

	public function wcap_create_default_abandoned_cart_list( $api_key ) {

		if ( '' !== $api_key ) {
			$connector_hb = Wcap_Hubspot::get_instance();
			$call         = $connector_hb->registered_calls['wcap_hubspot_create_list'];
			
			$params = array(
				'api_key' => $api_key,
				'name' => 'Abandoned Carts List',
				'dynamic' => true,
				'filters' => array(
					array(
						array(
							'operator' => 'NEQ',
							'value' => '',
							'property' => 'email',
							'type' => 'string'
						),
						array(
							'operator' => 'EQ',
							'value' => 'yes',
							'property' => 'wcap_abandoned_cart',
							'type' => 'string'
						)
					)
				)
			);

			$call->set_data( $params );
			$response = $call->process();
			$resp     = wp_remote_retrieve_body( $response );
			// Capture the status.
			if ( isset( $response['response'] ) && 200 !== absint( $response['response'] ) ) {
				$result['status'] = $resp['status'];
				$result['msg'] = $resp['message'];
				$result['list_id'] = 0;
			} else {
				$result['status'] = 'success';
				$result['msg']    = '';
				$result['list_id'] = $resp['listId'];
			}

			return $result;
		}
		return false;

	}

	public function wcap_create_default_abandoned_cart_workflow( $api_key ) {

		if ( '' !== $api_key ) {
			$connector_hb = Wcap_Hubspot::get_instance();
			$call         = $connector_hb->registered_calls['wcap_hubspot_create_workflow'];

			$params = array(
				'api_key' => $api_key,
				'name' => 'Abandoned Carts Workflow',
				'type' => 'DRIP_DELAY',
				'enabled' => false,
				'segmentCriteria' => array(
					array(
						array(
							'operator' => 'EQ',
							'value' => 'yes',
							'property' => 'wcap_abandoned_cart',
							'type' => 'string'
						)
					)
				),
				'actions' => array(
					array(
						"type" => "DELAY",
						"delayMillis" => 1800000,
					)
				)
			);

			$call->set_data( $params );
			$response = $call->process();
			$resp_wk  = wp_remote_retrieve_body( $response );

			// Capture the status.
			if ( isset( $response['response'] ) && 200 !== absint( $response['response'] ) ) {
				$result['status']      = isset( $resp_wk['status'] ) ? $resp_wk['status'] : '' ;
				$result['msg']         = isset( $resp_wk['message'] ) ? $resp_wk['message'] : '' ;
				$result['workflow_id'] = 0;
			} else {
				$result['status']      = 'success';
				$result['msg']         = '';
				$result['workflow_id'] = $resp_wk['migrationStatus']['workflowId'];
			}
			return $result;
		}
		return false;
	}

	public function wcap_setup_custom_properties_list() {

		$properties = array(
			'wcap_cart_counter' => array(
				'name' => 'wcap_cart_counter',
				'label' => 'Cart Counter',
				'description' => 'Cart Counter',
				'type' => 'integer',
				'fieldType' => 'number',
				'displayOrder' => 1,
			),
			'wcap_abandoned_date' => array(
				'name' => 'wcap_abandoned_date',
				'label' => 'Cart Abandoned Date',
				'description' => 'Date on which the cart was abandoned',
				'type' => 'datetime',
				'fieldType' => 'date',
				'displayOrder' => 2
			),
			'wcap_cart_products' => array(
				'name' => 'wcap_cart_products',
				'label' => 'Abandoned Cart Products',
				'description' => 'List of Products abandoned',
				'type' => 'string',
				'fieldType' => 'text',
				'displayOrder' => 3
			),
			'wcap_products_html' => array(
				'name' => 'wcap_products_html',
				'label' => 'Abandoned Cart Products HTML',
				'description' => 'Products table HTML',
				'type' => 'string',
				'fieldType' => 'text',
				'displayOrder' => 4
			),
			'wcap_products_sku' => array(
				'name' => 'wcap_products_sku',
				'label' => 'Cart Products SKU',
				'description' => 'Products SKU',
				'type' => 'string',
				'fieldType' => 'text',
				'displayOrder' => 5
			),
			'wcap_cart_subtotal' => array(
				'name' => 'wcap_cart_subtotal',
				'label' => 'Abandoned Cart Subtotal',
				'description' => 'Abandoned Cart Subtotal',
				'type' => 'string',
				'fieldType' => 'text',
				'displayOrder' => 6
			),
			'wcap_cart_tax' => array(
				'name' => 'wcap_cart_tax',
				'label' => 'Abandoned Cart Tax',
				'description' => 'Abandoned Cart Tax',
				'type' => 'string',
				'fieldType' => 'text',
				'displayOrder' => 7
			),
			'wcap_cart_total' => array(
				'name' => 'wcap_cart_total',
				'label' => 'Abandoned Cart Total',
				'description' => 'Abandoned Cart Total',
				'type' => 'string',
				'fieldType' => 'text',
				'displayOrder' => 8
			),
			'wcap_cart_url' => array(
				'name' => 'wcap_cart_url',
				'label' => 'Abandoned Cart URL',
				'description' => 'Abandoned Cart URL',
				'type' => 'string',
				'fieldType' => 'text',
				'displayOrder' => 9
			),
			'wcap_abandoned_cart' => array(
				'name' => 'wcap_abandoned_cart',
				'label' => 'Current Abandoned Cart',
				'description' => 'Current Abandoned Cart',
				'type' => 'string',
				'fieldType' => 'text',
				'displayOrder' => 10
			)
		);
		$this->custom_properties = $properties;
	}
}
new Wcap_Hubspot_Create_Default_Setup_Action();
