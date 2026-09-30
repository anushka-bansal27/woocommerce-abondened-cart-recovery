var $wcap_tyche_plugin_deactivation_modal = {},
	$tyche_plugin_name = 'acp_pro';

( function() {

	if ( 'undefined' === typeof tyche.plugin_deactivation || 'undefined' === typeof window[ `tyche_plugin_deactivation_${$tyche_plugin_name}_js` ] ) {
		return;
	}

	$wcap_tyche_plugin_deactivation_modal = tyche.plugin_deactivation.modal( $tyche_plugin_name, window[ `tyche_plugin_deactivation_${$tyche_plugin_name}_js` ] );

	if ( '' !== $wcap_tyche_plugin_deactivation_modal ) {
		tyche.plugin_deactivation.events.listeners( window[ `tyche_plugin_deactivation_${$tyche_plugin_name}_js` ], $wcap_tyche_plugin_deactivation_modal, $tyche_plugin_name );
	}
} )();
