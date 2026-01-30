<?php
/**
 * Base Module Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Agency_Nexus_Base_Module {

	/**
	 * Module Name
	 */
	protected $name;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Initialize module. Should be overridden by child classes.
	 */
	abstract public function init();

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		// To be overridden by child classes if needed.
	}

	/**
	 * Helper to get module directory path.
	 */
	protected function get_path() {
		return AGENCY_NEXUS_PATH . 'modules/' . strtolower( $this->name ) . '/';
	}

	/**
	 * Helper to get module template.
	 */
	protected function get_template( $template_name, $args = [] ) {
		extract( $args );
		include $this->get_path() . 'templates/' . $template_name . '.php';
	}
}
