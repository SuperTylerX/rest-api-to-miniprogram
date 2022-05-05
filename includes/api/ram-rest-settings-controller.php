<?php

if (!defined('ABSPATH')) {
	exit;
}

class RAM_REST_Options_Controller extends WP_REST_Controller {

	public function __construct() {

		$this->namespace = 'uni-app-rest-enhanced/v1';
		$this->resource_name = 'options';
	}

	// Register our routes.
	public function register_routes() {

		register_rest_route($this->namespace, '/' . $this->resource_name . '/homeconfig', array(
			// Here we register the readable endpoint for collections.
			array(
				'methods' => 'GET',
				'callback' => array($this, 'get_general_setting'),
				'permission_callback' => array($this, 'get_item_permissions_check'),
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));

	}

	public function get_general_setting($request) {

		$expand = get_option('minapper_expand_settings_page');
		$result["downloadfileDomain"] = get_option('wf_downloadfile_domain');
		$result["businessDomain"] = get_option('wf_business_domain');

		$result['posterImageUrl'] = get_option("wf_poster_imageurl") === false ? "" : get_option("wf_poster_imageurl");
		$result["zanImageUrl"] = get_option('wf_zan_imageurl');
		$result["logoImageUrl"] = get_option('wf_logo_imageurl');
		$result["shareImageUrl"] = get_option('wf_share_imageurl');

		if (!empty($expand)) {
			$result["expand"] = ['swipe_nav' => $expand['swipe_nav'], 'selected_nav' => $expand['selected_nav']];
		} else {
			$result["expand"] = ['swipe_nav' => [], 'selected_nav' => []];
		}


		$result["wf_enable_comment_option"] = empty(get_option('wf_enable_comment_option')) ? "0" : get_option('wf_enable_comment_option');
		$result["wf_enable_qq_comment_option"] = empty(get_option('wf_enable_qq_comment_option')) ? "0" : get_option('wf_enable_qq_comment_option');
		$result["uni_enable_h5_comment_option"] = !empty(get_option('uni_enable_h5_comment_option'));
		$result["wf_weixin_enterprise_minapp"] = empty(get_option('wf_weixin_enterprise_minapp')) ? "0" : get_option('wf_weixin_enterprise_minapp');
		$result["wf_qq_enterprise_minapp"] = empty(get_option('wf_qq_enterprise_minapp')) ? "0" : get_option('wf_qq_enterprise_minapp');

		$result["interstitialAdId"] = empty(get_option('wf_interstitial_ad_id')) ? "" : get_option('wf_interstitial_ad_id');
		$result["enable_index_interstitial_ad"] = empty(get_option('enable_index_interstitial_ad')) ? "0" : get_option('enable_index_interstitial_ad');
		$result["enable_detail_interstitial_ad"] = empty(get_option('enable_detail_interstitial_ad')) ? "0" : get_option('enable_detail_interstitial_ad');
		$result["enable_topic_interstitial_ad"] = empty(get_option('enable_topic_interstitial_ad')) ? "0" : get_option('enable_topic_interstitial_ad');
		$result["enable_list_interstitial_ad"] = empty(get_option('enable_list_interstitial_ad')) ? "0" : get_option('enable_list_interstitial_ad');
		$result["enable_hot_interstitial_ad"] = empty(get_option('enable_hot_interstitial_ad')) ? "0" : get_option('enable_hot_interstitial_ad');
		$result["enable_comments_interstitial_ad"] = empty(get_option('enable_comments_interstitial_ad')) ? "0" : get_option('enable_comments_interstitial_ad');
		$result["enable_live_interstitial_ad"] = empty(get_option('enable_live_interstitial_ad')) ? "0" : get_option('enable_comments_interstitial_ad');
		$result["is_user_registration_enable"] = get_option('users_can_register') === "1";

		return rest_ensure_response($result);
	}


	public function get_item_permissions_check($request) {

		return true;
	}

}