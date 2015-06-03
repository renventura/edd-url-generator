<?php
/**
 * Plugin Name: EDD URL Generator
 * Plugin URI: http://www.engagewp.com/
 * Description: EDD store managers and admins can generate URLs for adding downloads to a customer's cart, applying a discount, and redirecting to any page.
 * Version: 1.0
 * Author: Ren Ventura
 * Author URI: http://www.engagewp.com/
 *
 * License: GPL 2.0+
 * License URI: http://www.opensource.org/licenses/gpl-license.php
 */

 /*
	Copyright 2015  Ren Ventura, EngageWP.com

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	Permission is hereby granted, free of charge, to any person obtaining a copy of this
	software and associated documentation files (the "Software"), to deal in the Software
	without restriction, including without limitation the rights to use, copy, modify, merge,
	publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons
	to whom the Software is furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included in all copies or
	substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	THE SOFTWARE.
*/

if ( ! class_exists( 'EDD_URL_Generator' ) ) :

class EDD_URL_Generator {

	/**
	 *	@since 1.0
	 */
	private static $instance;

	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof EDD_URL_Generator ) ) {

			self::$instance = new EDD_URL_Generator;
			self::$instance->setup_constants();

			self::$instance->includes();
			self::$instance->init();

		}

		return self::$instance;
	}

	public function setup_constants() {

		if ( ! defined( 'EDD_URL_GENERATOR_PLUGIN_DIR' ) )
			define( 'EDD_URL_GENERATOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

		if ( ! defined( 'EDD_URL_GENERATOR_PLUGIN_URL' ) )
			define( 'EDD_URL_GENERATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

		if ( ! defined( 'EDD_URL_GENERATOR_PLUGIN_FILE' ) )
			define( 'EDD_URL_GENERATOR_PLUGIN_FILE', __FILE__ );

		if ( ! defined( 'EDD_URL_GENERATOR_VERSION' ) )
			define( 'EDD_URL_GENERATOR_VERSION', 1.0 );
	}

	/**
	 *	Include all PHP files
	 */
	public function includes() {

		foreach ( glob( EDD_URL_GENERATOR_PLUGIN_DIR . '/lib/*.php' ) as $file )
			include_once $file;
	}

	/**
	 *	Kick everything off
	 */
	public function init() {

		register_activation_hook( EDD_URL_GENERATOR_PLUGIN_FILE, array( $this, 'plugin_activate' ) );

		add_filter( 'edd_tools_tabs', array( $this, 'new_tab' ) );

		add_action( 'edd_tools_tab_url_generator', array( $this, 'tab_content' ) );

		add_action( 'edd_tools_url_generator_after', array( $this, 'display_url' ) );

	}

	/**
	 *	Make sure EDD is running on plugin activation
	 */
	public function plugin_activate() {

		$edd = $this->is_edd_active();

		//* Run check for dependencies
		if ( ! $edd ) {

			deactivate_plugins( EDD_URL_GENERATOR_PLUGIN_FILE );

			wp_die( __( 'The EDD URL Generator plugin requires Easy Digital Downloads to be active.', 'edd' ) );
		}
	}

	/**
	 *	Add new tab to EDD tools
	 *
	 *	@param array $tabs Existing tools tabs
	 *	@return array $tabs New tools tabs
	 */
	public function new_tab( $tabs ) {

		$tabs['url_generator'] = __( 'URL Generator', 'edd' );

		return $tabs;
	}

	/**
	 *	Add the inputs to the tab's content section
	 */
	public function tab_content() {

		if ( ! current_user_can( 'manage_shop_settings' ) )
			return;

		do_action( 'edd_tools_url_generator_before' );

		?>

		<div class="postbox">

			<h3><?php _e( 'URL Generator', 'edd' ); ?></h3>

			<div class="inside">

				<p><?php _e( 'Generate a link to add a download and/or apply a discount to the customer\'s cart and, optionally, redirect.', 'edd' ); ?></p>

				<form method="post" action="<?php echo admin_url( 'edit.php?post_type=download&page=edd-tools&tab=url_generator' ); ?>">

					<p><?php _e( 'Select a Download to add to the customer\'s cart.', 'edd' ); ?><br/>
						<?php echo EDD()->html->product_dropdown( array(
						'name'        => 'edd_url_generator_products',
						'id'          => 'edd_url_generator_products',
						'multiple'    => false,
						'chosen'      => false,
						'selected'    => -1,
						'placeholder' => sprintf( __( 'Select one or more %s', 'edd' ), edd_get_label_plural() ),
						'show_option_none' => ' '
						) ); ?>
					</p>

					<?php printf( '<p>%s <strong>%s</strong>', __( 'If the Download has variable pricing enabled, enter the price ID of the version to add', 'edd' ), __( '(defaults to 1).', 'edd' ) ); ?>
						<br/><input type="number" name="edd_url_generator_price_id" min="1" max="99">
					</p>

					<p><?php _e( 'Select a Discount to be applied.', 'edd' ); ?>
						<br/><?php echo $this->discounts_dropdown(); ?>
					</p>

					<?php printf( '<p>%s <strong>%s</strong>', __( 'Select a Page to redirect the customer to', 'edd' ), __( '(defaults to Checkout page).', 'edd' ) ); ?>
						<br/><?php echo $this->pages_dropdown(); ?>
					</p>

					<p>
						<input type="hidden" name="edd_action" value="edd_url_generator_nonce" />
						<?php wp_nonce_field( 'edd_url_generator_nonce', 'edd_url_generator_nonce' ); ?>
						<?php submit_button( __( 'Generate URL', 'edd' ), 'secondary', 'submit', false ); ?>
					</p>

				</form>

			</div><!-- .inside -->

		</div><!-- .postbox -->

		<?php

		do_action( 'edd_tools_url_generator_after' );
		do_action( 'edd_tools_after' );
	}

	/**
	 *	Display the generated URL after the configurations have been submitted
	 */
	public function display_url() {

		//* Get the Download ID
		if ( isset( $_POST['edd_url_generator_products'] ) && intval( $_POST['edd_url_generator_products'] ) !== -1 )
			$download_id = absint( $_POST['edd_url_generator_products'] );

		//* Get the discount
		if ( isset( $_POST['edd_url_generator_discounts'] ) && intval( $_POST['edd_url_generator_discounts'] ) !== -1 )
			$discount_code = edd_get_discount_code( absint( $_POST['edd_url_generator_discounts'] ) );

		//* Get the download's price ID (variable pricing only)
		if ( isset( $_POST['edd_url_generator_price_id'] ) )
			$price_id = absint( $_POST['edd_url_generator_price_id'] );

		//* Set the redirect page
		if ( isset( $_POST['edd_url_generator_pages'] ) && intval( $_POST['edd_url_generator_pages'] ) !== -1 ) {
			$redirect_page = get_permalink( absint( $_POST['edd_url_generator_pages'] ) );
		} else $redirect_page = get_permalink( edd_get_option( 'purchase_page' ) );

		//* Bail if neither the download nor discount are set
		if ( ! $download_id && ! $discount_code  ) return;

		//* Download set
		if ( $download_id ) {

			$redirect_page = add_query_arg( array(

				'edd_action' => 'add_to_cart',
				'download_id' => $download_id

			), $redirect_page );

			//* Price ID set
			if ( $price_id ) {

				$redirect_page = add_query_arg( array(

					'edd_options[price_id]' => $price_id

				), $redirect_page );

			} elseif ( edd_has_variable_prices( $download_id ) ) {

				$redirect_page = add_query_arg( array(

					'edd_options[price_id]' => 1

				), $redirect_page );

			}

		}

		//* Discount set
		if ( $discount_code ) {

			$redirect_page = add_query_arg( array(

				'discount' => $discount_code

			), $redirect_page );

		}

		?>

		<div class="postbox">

			<h3><span><?php _e( 'Generated URL', 'edd' ); ?></span></h3>

			<div class="inside">

				<p><strong><?php _e( 'Raw URL', 'edd' ) ?></strong></p>

				<p><?php echo $redirect_page; ?></p>

				<p><strong><?php _e( 'Shortcode', 'edd' ) ?></strong></p>

				<p>[edd_url_generator_link url="<?php echo $redirect_page; ?>" class="button" target="_blank" text="Buy Now"]</p>

				<p><em><?php _e( '* When using the shortcode, you can change the class, target and text attributes as you wish.', 'edd' ) ?></em></p>

			</div><!-- .inside -->

		</div><!-- .postbox -->

	<?php }

	/**
	 *	Check if EDD is active
	 */
	public function is_edd_active() {
		return is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' );
	}

	/**
	 *	Retrieve registered discounts and returns a dropdown with discounts as options
	 */
	public function discounts_dropdown() {

		$args = array( 'nopaging' => true );

		$discounts = edd_get_discounts( $args );

		$options = array();

		if ( $discounts )
			foreach ( $discounts as $discount )
				$options[ absint( $discount->ID ) ] = esc_html( get_the_title( $discount->ID ) );

		else $options[0] = __( 'No discounts found', 'edd' );

		//* Custom discounts dropdown (adds blank option)
		$output = EDD()->html->select( array(
			'name'             => 'edd_url_generator_discounts',
			'selected'         => -1,
			'options'          => $options,
			'show_option_all'  => false,
			'show_option_none' => ' '
		) );

		return $output;
	}

	/**
	 *	Retrieve pages and returns a dropdown with pages as options
	 */
	public function pages_dropdown() {

		$args = array(

			'post_type' => 'page',
			'nopaging' => true

		);

		$pages = get_posts( $args );

		$options = array();

		if ( $pages )
			foreach ( $pages as $page )
				$options[ absint( $page->ID ) ] = esc_html( get_the_title( $page->ID ) );

		else $options[0] = __( 'No pages found', 'edd' );

		//* Custom discounts dropdown (adds blank option)
		$output = EDD()->html->select( array(
			'name'             => 'edd_url_generator_pages',
			'selected'         => -1,
			'options'          => $options,
			'show_option_all'  => false,
			'show_option_none' => ' '
		) );

		return $output;
	}
}

endif;

/**
 *	Main function
 *
 *	@since 1.0
 *	@return object EDD_URL_Generator instance
 */
function EDD_URL_Generator() {
	return EDD_URL_Generator::instance();
}

//* Start the engine
EDD_URL_Generator();
