<?php
include_once "defaults.php";
function srizon_instagram_disconnect_user() {
	delete_option( 'srizon_instagram_access_token' );
	delete_option( 'srizon_instagram_connected_user' );
}

function srizon_instagram_get_settings() {
	$settings              = srizon_instagram_api_settings_defaults();
	$connected_user_object = json_decode( get_option( 'srizon_instagram_connected_user', false ) );
	if ( $connected_user_object ) {
		$settings['connected_user'] = isset( $connected_user_object->data ) ? $connected_user_object->data : false;
	} else {
		$settings['connected_user'] = false;
	}

	$settings['global'] = srizon_instagram_get_global_settings();

	return $settings;
}

function srizon_instagram_get_global_settings() {
	$global_settings = get_option( 'srizon_instagram_global_settings', false );
	if ( $global_settings ) {
		return array_merge( srizon_instagram_album_global_defaults(), (array) $global_settings );
	} else {
		return srizon_instagram_album_global_defaults();
	}
}

/**
 * @param \WP_REST_Request $req
 *
 * @return mixed
 */
function srizon_instagram_save_global_settings( $req ) {
	$json_data = json_decode( $req->get_body() );
	update_option( 'srizon_instagram_global_settings', $json_data );

	$resp           = [ ];
	$resp['result'] = 'saved';
	$resp['data']   = $json_data;

	return $resp;
}

add_action( 'rest_api_init', function () {

	register_rest_route( 'srizon-instagram/v1', '/settings/', [
		'methods'             => 'GET',
		'callback'            => 'srizon_instagram_get_settings',
		'permission_callback' => 'srizon_instagram_permission_admin',
	] );

	register_rest_route( 'srizon-instagram/v1', '/disconnect-user/', [
		'methods'             => 'GET',
		'callback'            => 'srizon_instagram_disconnect_user',
		'permission_callback' => 'srizon_instagram_permission_admin',

	] );

	register_rest_route( 'srizon-instagram/v1', '/save-global-settings/', [
		'methods'             => 'POST',
		'callback'            => 'srizon_instagram_save_global_settings',
		'permission_callback' => 'srizon_instagram_permission_admin',
	] );
} );

