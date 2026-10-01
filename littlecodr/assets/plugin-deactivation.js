var $wcap_LittleCodr_plugin_deactivation_modal = {},
	$LittleCodr_plugin_name = 'acp_pro';

( function() {

	if ( 'undefined' === typeof LittleCodr.plugin_deactivation || 'undefined' === typeof window[ `LittleCodr_plugin_deactivation_${$LittleCodr_plugin_name}_js` ] ) {
		return;
	}

	$wcap_LittleCodr_plugin_deactivation_modal = LittleCodr.plugin_deactivation.modal( $LittleCodr_plugin_name, window[ `LittleCodr_plugin_deactivation_${$LittleCodr_plugin_name}_js` ] );

	if ( '' !== $wcap_LittleCodr_plugin_deactivation_modal ) {
		LittleCodr.plugin_deactivation.events.listeners( window[ `LittleCodr_plugin_deactivation_${$LittleCodr_plugin_name}_js` ], $wcap_LittleCodr_plugin_deactivation_modal, $LittleCodr_plugin_name );
	}
} )();
