<?php

/**
 *	Global functions
 *
 *	@package EDD URL Generator
 *	@since 1.0
 *	@author Ren Ventura
 */

//* Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

//* Checks if EDD is active
function edd_url_generator_is_edd_active() {
	return EDD_URL_Generator()->is_edd_active();
}

//* Retrieve registered discounts and returns a dropdown with discounts as options
function edd_url_generator_discounts_dropdown() {
	echo EDD_URL_Generator()->discounts_dropdown();
}

//* Retrieve pages and returns a dropdown with pages as options
function edd_url_generator_pages_dropdown() {
	echo EDD_URL_Generator()->pages_dropdown();
}