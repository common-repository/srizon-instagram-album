<?php
include_once 'defaults.php';
include_once 'settings.php';
/**
 * @param string $username
 * @param string $access_token
 *
 * @return array|bool
 */
function srizon_instagram_username_to_id( $username, $access_token ) {

	// try direct
	$response = wp_remote_get( 'https://www.instagram.com/' . $username . '/?__a=1', [ 'timeout' => 30 ] );
	if ( $response['response']['code'] == 200 ) {
		$json = json_decode( $response['body'] );

		return $json->user;
	} else {
		// try search
		$response = wp_remote_get( 'https://api.instagram.com/v1/users/search?q=' . $username . '&access_token=' . $access_token, [ 'timeout' => 30 ] );
		if ( $response['response']['code'] == 200 ) {
			$json = json_decode( $response['body'] );

			return $json->data;
		}
	}

	return false;
}

/**
 * @param \WP_REST_Request $req
 */
function srizon_instagram_save_user_album( $req ) {
	$access_token = get_option( 'srizon_instagram_access_token', false );
	$json_data    = json_decode( $req->get_body() );
	$user         = srizon_instagram_username_to_id( $json_data->username, $access_token );

	//return $user_id;
	if ( is_array( $user ) ) {
		if ( count( $user ) ) {
			$ret['result'] = 'selection';
			$ret['users']  = $user;

			return $ret;
		} else {
			return new WP_Error( 'user_not_found', 'User Not Found', [ 'status' => 404 ] );
		}
	} else {
		if ( ! $user->id ) {
			return new WP_Error( 'user_not_found', 'User Not Found', [ 'status' => 404 ] );
		}
		if ( trim( $json_data->title ) ) {
			$title = trim( $json_data->title );
		} else if ( trim( $user->full_name ) ) {
			$title = 'Photos of ' . $user->full_name;
		} else {
			$title = 'Photos of ' . $user->username;
		}

		$payload                    = [ ];
		$payload['title']           = $title;
		$payload['type']            = 'user';
		$payload['userid']          = $user->id;
		$payload['username']        = $user->username;
		$payload['full_name']       = $user->full_name;
		$payload['profile_picture'] = $user->profile_pic_url;
		$payload['hashtag']         = '';
		$payload['options']         = serialize( srizon_instagram_get_global_settings() );

		SrizonInstaDB::SaveAlbum( $payload );

		$ret['result'] = 'saved';
		$ret['albums'] = srizon_instagram_get_album_index();
		$ret['api']    = $payload;

		return $ret;
	}
}

function srizon_instagram_get_album_index() {
	return SrizonInstaDB::GetAllAlbums();
}

/**
 * @param \WP_REST_Request $req
 *
 * @return mixed
 */
function srizon_instagram_save_hashtag_album( $req ) {
	$json_data = json_decode( $req->get_body() );
	$hashtag   = trim( $json_data->hashtag, " \t\n\r\0\x0B" );

	if ( strlen( $hashtag ) == 0 ) {
		return new WP_Error( 'hashtag_empty', 'Empty Hashtag. Please provide something valid', [ 'status' => 404 ] );
	}

	if ( trim( $json_data->title ) ) {
		$title = trim( $json_data->title );
	} else {
		$title = 'Photos with tag: ' . $hashtag;
	}

	$payload                    = [ ];
	$payload['title']           = $title;
	$payload['type']            = 'hashtag';
	$payload['userid']          = null;
	$payload['username']        = '';
	$payload['full_name']       = null;
	$payload['profile_picture'] = null;
	$payload['hashtag']         = $hashtag;
	$payload['options']         = serialize( srizon_instagram_get_global_settings() );

	SrizonInstaDB::SaveAlbum( $payload );
	$ret['result'] = 'saved';
	$ret['albums'] = srizon_instagram_get_album_index();

	return $ret;
}

/**
 * @param array $req
 *
 * @return mixed
 */
function srizon_instagram_delete_album( $req ) {
	SrizonInstaDB::DeleteAlbum( $req['id'] );
	$ret['result'] = 'deleted';
	$ret['albums'] = srizon_instagram_get_album_index();

	return $ret;
}

/**
 * @param array $req
 *
 * @return mixed
 */
function srizon_instagram_get_album( $req ) {
	$album = SrizonInstaDB::getAlbum( (int) $req['id'] );
	if ( $album ) {
		$ret['result'] = 'success';
		$ret['album']  = $album;

		return $ret;
	}

	return new WP_Error( 'album_not_found', 'Album Not Found. Make sure that the shortcode matches and existing album', [ 'status' => 404 ] );
}

/**
 * @param \WP_REST_Request $req
 *
 * @return mixed
 */
function srizon_instagram_update_album_settings( $req ) {
	$json_data = json_decode( $req->get_body() );

	SrizonInstaDB::UpdateAlbumSettings( $json_data->id, $json_data->settings );
	$ret['result'] = 'updated';
	$ret['albums'] = srizon_instagram_get_album_index();

	return $ret;
}

/**
 * @param \WP_REST_Request $req
 */
function srizon_instagram_get_album_data( $req ) {
	$json_data = json_decode( $req->get_body() );

	$album = SrizonInstaDB::getAlbum( (int) $json_data->id );

	if ( $album ) {
		$ret['result'] = 'success';
		$ret['data']   = SrizonInstaAPI::getAlbumData( $json_data->id );

		return $ret;
	}

	return new WP_Error( 'album_not_found', 'Album Not Found. Make sure that the shortcode matches and existing album', [ 'status' => 404 ] );
}

/**
 * @param \WP_REST_Request $req
 *
 * @return mixed
 */
function srizon_instagram_get_album_load_more( $req ) {
	$json_data = json_decode( $req->get_body() );

	$ret['result'] = 'success';
	$ret['data']   = SrizonInstaAPI::getAlbumLoadMore( $json_data->id, $json_data->url );

	return $ret;
}

/**
 * @param \WP_REST_Request $req
 *
 * @return mixed
 */
function srizon_instagram_sync_album( $req ) {
	$json_data = json_decode( $req->get_body() );

	$ret['result'] = 'success';
	$ret['data']   = SrizonInstaAPI::syncAlbum( $json_data->id );

	return $ret;
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'srizon-instagram/v1', '/useralbum/', [
		'methods'             => 'POST',
		'callback'            => 'srizon_instagram_save_user_album',
		'permission_callback' => 'srizon_instagram_permission_admin',
	] );

	register_rest_route( 'srizon-instagram/v1', '/album/', [
		'methods'  => 'GET',
		'callback' => 'srizon_instagram_get_album_index',
	] );

	register_rest_route( 'srizon-instagram/v1', '/album-data/', [
		'methods'  => 'POST',
		'callback' => 'srizon_instagram_get_album_data',
	] );
	register_rest_route( 'srizon-instagram/v1', '/album-sync/', [
		'methods'  => 'POST',
		'callback' => 'srizon_instagram_sync_album',
	] );
	register_rest_route( 'srizon-instagram/v1', '/album-load-more/', [
		'methods'  => 'POST',
		'callback' => 'srizon_instagram_get_album_load_more',
	] );

	register_rest_route( 'srizon-instagram/v1', '/album/(?P<id>[\d]+)', [
		'methods'             => 'DELETE',
		'callback'            => 'srizon_instagram_delete_album',
		'permission_callback' => 'srizon_instagram_permission_admin',
	] );
	register_rest_route( 'srizon-instagram/v1', '/album/(?P<id>[\d]+)', [
		'methods'  => 'GET',
		'callback' => 'srizon_instagram_get_album',
	] );

	register_rest_route( 'srizon-instagram/v1', '/hashtagalbum/', [
		'methods'             => 'POST',
		'callback'            => 'srizon_instagram_save_hashtag_album',
		'permission_callback' => 'srizon_instagram_permission_admin',
	] );
	register_rest_route( 'srizon-instagram/v1', '/album-settings/', [
		'methods'             => 'POST',
		'callback'            => 'srizon_instagram_update_album_settings',
		'permission_callback' => 'srizon_instagram_permission_admin',
	] );
} );
