<?php
/*
Plugin Name: Rest API to UniApp
Plugin URI: https://github.com/SuperTylerX/rest-api-to-miniprogram-enhanced
Description: 在微慕小程序开源版插件基础上进行二次开发，为微信小程序、app提供定制化WordPress REST API json 输出.
Version: 4.0.9
Author: SuperTylerX
Author URI: https://github.com/SuperTylerX/
License: GPL v3
WordPress requires at least: 4.7.1
*/


define('REST_API_TO_MINIPROGRAM_PLUGIN_DIR', plugin_dir_path(__FILE__));
const REST_API_TO_MINIPROGRAM_PLUGIN_NAME = 'rest-api-to-miniprogram-enhanced';
define('REST_API_TO_MINIPROGRAM_PLUGIN_URL', plugins_url(REST_API_TO_MINIPROGRAM_PLUGIN_NAME . '/', dirname(__FILE__)));

//引入ipip库
require_once(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/vendor/ipip/Reader.php');
require_once(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/vendor/ipip/City.php');

include(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/ram-util.php');
include(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/ram-api.php');
include(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/server/uni-wechat-api.php');
include(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/server/uni-qq-api.php');
include(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/settings/wp-wechat-config.php');
include(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/settings/wp-post-config.php');
include(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/settings/wp-tinymce-add-button.php');
include(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/filter/ram-custom-comment-fields.php');
include(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/filter/ram-custom-content.php');
include(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/filter/ram-custom-post-fields.php');
include(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/filter/ram-custom-category.php');
include(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/filter/ram-custom-users-columns.php');
include(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/filter/ram-custom-category-rows.php');
include(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/filter/ram-custom-posts-rows.php');
include(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/admin/exopite-simple-options/exopite-simple-options-framework-class.php');
include(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/settings/wp-wechat-admin.php');

if (!class_exists('RestAPIMiniProgram')) {

	class RestAPIMiniProgram {

		public $WechatAPI;
		public $QQAPI;

		public function __construct() {
			//定制化内容输出，对pc端和api都生效
			add_filter('the_content', 'custom_content_filter');
			//对文章的自定义输出
			add_filter('rest_prepare_post', 'custom_post_fields', 10, 3);
			//对页面的自定义输出
			add_filter('rest_prepare_page', 'custom_post_fields', 10, 3);
			//对评论的自定义输出
			add_filter('rest_prepare_comment', 'custom_comment_fields', 10, 3);
			//获取分类的封面图片
			add_filter('rest_prepare_category', 'custom_fields_rest_prepare_category', 10, 3);

			//添加管理用户头像列
			add_filter('manage_users_columns', 'users_columns');
			add_action('manage_users_custom_column', 'output_users_columns', 10, 3);

			// 添加用户自定义输出
			add_filter('rest_prepare_user', 'addCustomUserField', 10, 3);

			//给TinyMCE编辑器增加A标签按钮
			add_action('after_wp_tiny_mce', 'add_tinyMCE_minapper_button');

			//文章页显示自定义列
			add_filter('manage_posts_columns', 'ram_posts_columns');
			add_action('manage_posts_custom_column', 'output_ram_posts_custom_columns', 10, 3);

			//页面显示自定义列
			add_filter('manage_pages_columns', 'ram_pages_columns');
			add_action('manage_pages_custom_column', 'output_ram_pages_custom_columns', 10, 3);

			//分类目录页自定义列
			add_filter('manage_edit-category_columns', 'ram_custom_taxonomy_columns');
			add_filter('manage_category_custom_column', 'ram_custom_taxonomy_columns_content', 10, 3);

			// 添加激活插件时钩子
			register_activation_hook(__FILE__, "activate");

			// 管理配置
			if (is_admin()) {
				add_action('admin_enqueue_scripts', 'ram_admin_style', 9999);
				add_action('admin_menu', 'weixinapp_create_menu');
				add_action('init', 'minapper_admin_menu');
				add_filter('plugin_action_links', 'ram_plugin_action_links', 10, 2);
				wp_post_config();
			}

			//初始化自定义API
			new RAM_API();

			// 将小程序服务端API加入到全局中
			$this->WechatAPI = new UniWechatAPI();
			$this->QQAPI = new UniQQAPI();
		}
	}

	// 实例化并加入全局变量
	$GLOBALS['RestAPIMiniProgram'] = new RestAPIMiniProgram();

	// 单例模式，返回全局变量
	function UniRestAPIInstance() {

		if (!isset($GLOBALS['RestAPIMiniProgram'])) {
			$GLOBALS['RestAPIMiniProgram'] = new RestAPIMiniProgram();
		}

		return $GLOBALS['RestAPIMiniProgram'];
	}

	function ram_admin_style() {
		wp_enqueue_style('raw-admin-css', REST_API_TO_MINIPROGRAM_PLUGIN_URL . 'includes/css/menu.css', array(), '4.0.4');
	}

	function ram_plugin_action_links($links, $file) {
		if (plugin_basename(__FILE__) !== $file) {
			return $links;
		}

		$settings_link = '<a href="admin.php?page=weixinapp_slug">' . esc_html__('设置', 'REST API TO MiniProgram') . '</a>';

		array_unshift($links, $settings_link);

		return $links;
	}

	function activate() {
		// 给订阅者添加上传文件权限，用于圈子图片上传
		$role = 'subscriber';
		$role = get_role($role);
		$role->add_cap('upload_files');
		$role->add_cap('delete_posts');
	}
}
