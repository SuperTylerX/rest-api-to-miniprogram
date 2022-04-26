<?php

if (!defined('ABSPATH')) {
	exit;
}

class RAM_API extends WP_REST_Controller {

	/**
	 * Setup class.
	 * @since 2.0
	 */
	public function __construct() {
		$this->rest_api_init();
	}

	/**
	 * Init WP REST API.
	 * @since 2.6.0
	 */
	private function rest_api_init() {

		$this->rest_api_includes();

		// Init REST API routes.
		add_action('rest_api_init', array($this, 'register_rest_routes'));
	}

	/**
	 * Include REST API classes.
	 * @since 2.6.0
	 */
	private function rest_api_includes() {
		include_once('api/ram-rest-posts-controller.php');
		include_once('api/ram-rest-comments-controller.php');
		include_once('api/ram-rest-weixin-controller.php');
		include_once('api/ram-rest-settings-controller.php');
		include_once('api/ram-rest-payment-controller.php');
		include_once('api/ram-rest-categories-controller.php');
		include_once('api/ram-rest-live-controller.php');
		include_once('api/ram-wp-rest-posts-controller.php');
		include_once('api/ram-rest-qq-controller.php');
		include_once('api/ram-rest-menu-controller.php');
		include_once('api/ram-rest-forums-controller.php');
		include_once('api/ram-rest-register-controller.php');
	}

	/**
	 * Register REST API routes.
	 * @since 2.6.0
	 */
	public function register_rest_routes() {
		$controllers = array(
			'RAM_REST_Posts_Controller',
			'RAM_REST_Comments_Controller',
			'RAM_REST_Options_Controller',
			'RAW_REST_Payment_Controller',
			'RAM_REST_Categories_Controller',
			'RAM_WP_REST_Posts_Controller',
			'RAM_REST_Live_Controller',
			'RAM_REST_Menu_Controller',
			'RAM_REST_Weixin_Controller',
			'RAM_REST_QQ_Controller',
			'RAM_REST_Forums_Controller',
			'RAM_REST_Register_Controller'
		);

		foreach ($controllers as $controller) {
			$this->$controller = new $controller();
			$this->$controller->register_routes();
		}
	}
}
