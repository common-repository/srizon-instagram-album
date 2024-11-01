<?php

function srizon_instagram_album_global_defaults() {
	return [
		'cache_time'                => 600,
		'load_more_text'            => 'Load More...',
		'max_image'                 => 1000,
		'total_image_carousel'      => 12,
		'initial_load'              => 12,
		'layout'                    => 'collage',
		'collage_row_height'        => 200,
		'collage_margin'            => 2,
		'collage_thumb_size'        => 1,
		'collage_show_hover'        => false,
		'carousel_thumb_show_hover' => false,
		'carousel_img_txt_overlay'  => false,
		'carousel_show_thumb'       => true,
		'carousel_show_count'       => true,
		'carousel_auto_play'        => true,
		'carousel_slide_interval'   => 5,
		'carousel_thumb_position'   => 'bottom',
		'collage_load_more_method'  => 'button',
	];
}

function srizon_instagram_api_settings_defaults() {
	return [
		'access_token'  => get_option( 'srizon_instagram_access_token', false ),
		'oauth_url'     => 'https://api.instagram.com/oauth/authorize/',
		'client_id'     => '54da896cf80343ecb0e356ac5479d9ec',
		'redirect_uri'  => 'http://api.web-dorado.com/instagram/',
		'response_type' => 'token',
		'scope'         => 'public_content',
	];
	//$settings['client_id']     = '901f9880734f4a2fb06411c8889a51a6';
	//$settings['redirect_uri']  = 'https://srizon.com/instagram-api/';
}