<?php
function srizon_instagram_album_shortcode( $atts ) {
	if ( ! isset( $atts['id'] ) ) {
		return 'Invalid shortcode... ID missing';
	}
	srizon_instagram_load_site_footer_resources();

	return '<div class="srizon"><div class="srzinst" data-id="' . $atts['id'] . '"></div></div>';
}

add_shortcode( 'srzinst', 'srizon_instagram_album_shortcode' );

add_action( 'wp_enqueue_scripts', 'srizon_instagram_load_site_head_resources' );
function srizon_instagram_load_site_head_resources() {
	wp_enqueue_style( 'srizon-materialize', srizon_instagram_get_resource_url( 'site/resources/materialize.css' ), null, '1.0' );
	wp_enqueue_style( 'srizon-instagram-site', srizon_instagram_get_resource_url( 'site/resources/app.css' ), null, '1.0' );
}

//wp_enqueue_style( 'roboto', 'https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700', null, '1.0' );
//wp_enqueue_style( 'material-icons', 'https://fonts.googleapis.com/icon?family=Material+Icons', null, '1.0' );

function srizon_instagram_load_site_footer_resources() {
	wp_enqueue_script( 'wp-api' );
	//wp_enqueue_script( 'srizon-materialize', srizon_instagram_get_resource_url( 'site/resources/materialize.js' ), [ 'jquery' ], '1.0', true );
	wp_enqueue_script( 'react', srizon_instagram_get_resource_url( 'site/resources/react.min.js' ), null, '15.6.1', true );
	wp_enqueue_script( 'react-dom', srizon_instagram_get_resource_url( 'site/resources/react-dom.min.js' ), null, '15.6.1', true );
	wp_enqueue_script( 'srizon-instagram-site', srizon_instagram_get_resource_url( 'site/resources/app.js' ), [ 'jquery' ], '1.0', true );
}
