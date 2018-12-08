<?php

/**
 *	Shortcodes
 *
 *	@package EDD URL Generator
 *	@since 1.0
 *	@author Ren Ventura
 */

//* Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

//* Use generated URLs in a shortcode
add_shortcode( 'edd_url_generator_link', 'edd_url_generator_link_shortcode_callback' );
function edd_url_generator_link_shortcode_callback( $atts ) {

	// Attributes
	extract( shortcode_atts(
		array(
			'url' => '',
			'class' => '',
			'target' => '',
			'text' => '',
		), $atts )
	);

	return sprintf( '<a href="%s" class="%s" target="%s">%s</a>', esc_url( $url ), $class, $target, $text );
}