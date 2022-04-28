<?php
/**
 * Modified from bbPress API
 * Contributors: casiepa
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
	exit;
}

class RAM_REST_Forums_Controller extends WP_REST_Controller {

	public function __construct() {
		$this->namespace = 'uni-app-rest-enhanced/v1';
		$this->resource_name = 'forums';
	}

	public function register_routes() {

		// 注册获取所有论坛概览信息API
		register_rest_route($this->namespace, '/' . $this->resource_name, array(
			array(
				'methods' => 'GET',
				'callback' => array($this, 'bbp_api_forums')
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema')
		));

		// 注册获取指定论坛文章列表API
		register_rest_route($this->namespace, '/' . $this->resource_name . '/(?P<id>\d+)', array(
			array(
				'methods' => 'GET',
				'callback' => array($this, 'bbp_api_forums_one'),
				'args' => array(
					'id' => array(
						'validate_callback' => function ($param, $request, $key) {
							return is_numeric($param);
						}
					)
				)
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));

		// 注册获取指定文章内容API
		register_rest_route($this->namespace, '/' . $this->resource_name . '/topic/(?P<id>\d+)', array(
			array(
				'methods' => 'GET',
				'callback' => array($this, 'bbp_api_topics_one'),
				'args' => array(
					'id' => array(
						'validate_callback' => function ($param, $request, $key) {
							return is_numeric($param);
						}
					)
				)
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));

		// 注册获取指定文章评论API
		register_rest_route($this->namespace, '/' . $this->resource_name . '/reply/(?P<id>\d+)', array(
			array(
				'methods' => 'GET',
				'callback' => array($this, 'bbp_api_replies_one'),
				'args' => array(
					'id' => array(
						'validate_callback' => function ($param, $request, $key) {
							return is_numeric($param);
						}
					)
				)
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));

		// 注册发布帖子到指定论坛API
		register_rest_route($this->namespace, '/' . $this->resource_name . '/(?P<id>\d+)', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'bbp_api_new_topic_post'),
				'permission_callback' => array($this, 'bbp_api_new_topic_post_permissions_check'),
				'args' => array(
					'content' => array(
						'required' => true,
						'description' => 'Content for the initial post in the new topic.',
						'type' => 'string'
					),
					'id' => array(
						'validate_callback' => function ($param, $request, $key) {
							return is_numeric($param);
						}
					),
					'tags' => array(
						'required' => false,
						'type' => 'string'
					),
				)
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema')
		));


	}

	// 获取所有论坛概览信息方法
	public function bbp_api_forums() {
		$all_forums_data = $all_forums_ids = array();
		if (bbp_has_forums()) {
			// Get root list of forums
			while (bbp_forums()) {
				bbp_the_forum();
				$forum_id = bbp_get_forum_id();
				$all_forums_ids[] = $forum_id;
				if ($sublist = bbp_forum_get_subforums()) {
					foreach ($sublist as $sub_forum) {
						$all_forums_ids[] = (int)$sub_forum->ID;
					}
				}
			} // while
			$i = 0;
			foreach ($all_forums_ids as $forum_id) {
				$all_forums_data[$i]['order'] = get_post($forum_id)->menu_order;
				$all_forums_data[$i]['id'] = $forum_id;
				$all_forums_data[$i]['name'] = bbp_get_forum_title($forum_id);
				$all_forums_data[$i]['parent'] = bbp_get_forum_parent_id($forum_id);
				$all_forums_data[$i]['content'] = bbp_get_forum_content($forum_id);
				$i++;
			}
		}
		if (empty($all_forums_data)) {
			return null;
		}
		return $all_forums_data;
	}


	// 获取指定论坛文章列表方法
	public function bbp_api_forums_one($data) {
		$all_forum_data = array();
		$bbp = bbpress();
		$forum_id = bbp_get_forum_id($data['id']);

		if (!bbp_is_forum($forum_id)) {
			return new WP_Error('error', 'Parameter value ' . $data['id'] . ' is not an ID of a forum', array('status' => 404));
		}

		$per_page = !isset($_GET['per_page']) ? 10 : (int)$_GET['per_page'];
		if ($per_page > 100) $per_page = 100;
		$page = !isset($_GET['page']) ? 1 : (int)$_GET['page'];

		$all_forum_data['id'] = $forum_id;
		$all_forum_data['title'] = bbp_get_forum_title($forum_id);
		$all_forum_data['name'] = bbp_get_forum_title($forum_id);
		$all_forum_data['parent'] = bbp_get_forum_parent_id($forum_id);
		$all_forum_data['total'] = (int)bbp_get_forum_topic_count($forum_id);
		$content = bbp_get_forum_content($forum_id);
		$all_forum_data['content'] = $content;
		$all_forum_data['page'] = $page;
		$all_forum_data['per_page'] = $per_page;

		$stickies = bbp_get_stickies($forum_id);
		$all_forum_data['stickies'] = [];
		foreach ($stickies as $topic_id) {
			$all_forum_data['stickies'][] = $this->get_topic_detail($topic_id);
		}

		$super_stickies = bbp_get_stickies();
		$all_forum_data['super_stickies'] = [];
		foreach ($super_stickies as $topic_id) {
			$all_forum_data['super_stickies'][] = $this->get_topic_detail($topic_id);
		}

		if (bbp_has_topics(array('orderby' => 'date',
			'order' => 'DESC',
			'posts_per_page' => $per_page,
			'paged' => $page,
			'post_parent' => $forum_id))
		) {
			$all_forum_data['total_topics'] = (int)$bbp->topic_query->found_posts;
			$all_forum_data['total_pages'] = ceil($all_forum_data['total_topics'] / $per_page);

			while (bbp_topics()) : bbp_the_topic();
				$topic_id = bbp_get_topic_id();
				if (!bbp_is_topic_super_sticky($topic_id) && !bbp_is_topic_sticky($topic_id)) {
					$all_forum_data['topics'][] = $this->get_topic_detail($topic_id);
				}
			endwhile;

		} else {
			$all_forum_data['topics'] = array();
		}

		if (empty($all_forum_data)) {
			return null;
		}
		return $all_forum_data;
	}

	function get_topic_detail($topic_id) {
		$one_sticky = array();
		$one_sticky['id'] = $topic_id;
		$one_sticky['title'] = html_entity_decode(bbp_get_topic_title($topic_id));
		$one_sticky['reply_count'] = bbp_get_topic_reply_count($topic_id);
		$one_sticky['permalink'] = bbp_get_topic_permalink($topic_id);
		$author_id = bbp_get_topic_author_id($topic_id);
		$one_sticky['author_id'] = $author_id;
		$one_sticky['author_name'] = bbp_get_topic_author_display_name($topic_id);;
		$one_sticky['author_avatar'] = get_avatar_url_2($author_id);
		$one_sticky['pageviews'] = (int)get_post_meta($topic_id, 'views', true);
		$one_sticky['post_date'] = bbp_get_topic_post_date($topic_id);
		$one_sticky['excerpt'] = mb_strimwidth(wp_filter_nohtml_kses(bbp_get_topic_content($topic_id)), 0, 150, '...');
		$one_sticky['allimg'] = get_post_content_images(bbp_get_topic_content($topic_id));
		$one_sticky['content_nohtml'] = wp_filter_nohtml_kses(bbp_get_topic_content($topic_id));
		$one_sticky['like_count'] = count(bbp_get_topic_favoriters($topic_id));
		return $one_sticky;
	}

	// 获取指定文章内容
	public function bbp_api_topics_one($data) {
		$all_topic_data = array();
		$topic_id = $data['id'];
		if ($topic_id == 0) {
			return new WP_Error('error', 'Parameter value of ID for a topic should not be 0', array('status' => 404));
		}
		if (!bbp_is_topic($topic_id)) {
			return new WP_Error('error', 'Parameter value ' . $topic_id . ' is not an ID of a topic', array('status' => 404));
		} else {
			$all_topic_data['id'] = $topic_id;
			$all_topic_data['title'] = html_entity_decode(bbp_get_topic_title($topic_id));
			$all_topic_data['reply_count'] = bbp_get_topic_reply_count($topic_id);
			$all_topic_data['permalink'] = bbp_get_topic_permalink($topic_id);
			$tags = wp_get_object_terms($topic_id, "topic-tag");
			$i = 0;
			foreach ($tags as $tag) {
				$all_topic_data['tags'][$i]['tag_ids'] = $tag->term_id;
				$all_topic_data['tags'][$i]['tag_names'] = $tag->name;
				$i++;
			}
			$all_topic_data['author_name'] = bbp_get_topic_author_display_name($topic_id);
			$author_id = bbp_get_topic_author_id($topic_id);
			$all_topic_data['author_id'] = $author_id;
			$all_topic_data['author_avatar'] = get_avatar_url_2($author_id);
			$all_topic_data['post_date'] = bbp_get_topic_post_date($topic_id);
			$all_topic_data['is_sticky'] = bbp_is_topic_sticky($topic_id);
			$all_topic_data['is_super_sticky'] = bbp_is_topic_super_sticky($topic_id);

			$raw_enable_comment_option = get_option('raw_enable_comment_option');
			$all_topic_data['enableComment'] = empty($raw_enable_comment_option) ? "0" : "1";

			$pageviews = (int)get_post_meta($topic_id, 'views', true);
			$all_topic_data['views'] = $pageviews;

			$post_views = $pageviews + 1;
			if (!update_post_meta($topic_id, 'views', $post_views)) {
				add_post_meta($topic_id, 'views', 1, true);
			}
			$all_topic_data['allimg'] = get_post_content_images(bbp_get_topic_content($topic_id));
			$all_topic_data['content_nohtml'] = wp_filter_nohtml_kses(bbp_get_topic_content($topic_id));

			return $all_topic_data;
		}
	}

	// 获取指定文章评论
	public function bbp_api_replies_one($data) {
		$topic_id = bbp_get_topic_id($data['id']);
		$bbp = bbpress();
		$per_page = !isset($_GET['per_page']) ? 10 : (int)$_GET['per_page'];
		if ($per_page > 100) $per_page = 100;
		$page = !isset($_GET['page']) ? 1 : (int)$_GET['page'];

		$all_topic_data['current_page'] = $page;
		$all_topic_data['per_page'] = $per_page;

		if (bbp_has_replies(array('orderby' => 'date', 'order' => 'DESC', 'posts_per_page' => $per_page, 'paged' => $page, 'post_parent' => $topic_id))) {
			$all_topic_data['total_replies'] = (int)$bbp->reply_query->found_posts - 1; // Remove the topic that comes as first reply
			$all_topic_data['total_pages'] = ceil($all_topic_data['total_replies'] / $per_page);

			$i = 0;
			while (bbp_replies()) : bbp_the_reply();
				$reply_id = bbp_get_reply_id();
				if ($reply_id != $topic_id) {
					// The first reply is the topic itself, so this 'if' removes it
					$all_topic_data['replies'][$i]['id'] = $reply_id;
					$all_topic_data['replies'][$i]['author_name'] = bbp_get_reply_author_display_name($reply_id);
					$all_topic_data['replies'][$i]['author_id'] = bbp_get_reply_author_id($reply_id);
					$all_topic_data['replies'][$i]['author_avatar'] = get_avatar_url_2(bbp_get_reply_author_email($reply_id));
					$all_topic_data['replies'][$i]['post_date'] = bbp_get_reply_post_date($reply_id);
					$all_topic_data['replies'][$i]['reply_to'] = bbp_get_reply_to($reply_id);
					$all_topic_data['replies'][$i]['content'] = bbp_get_reply_content($reply_id);
					$i++;
				}
			endwhile;
		} else {
			// No replies
			$all_topic_data['replies'] = array();
		}
		return $all_topic_data;
	}

	function bbp_api_new_topic_post($data) {
		$return = array();
		//required fields in POST data
		$forum_id = bbp_get_forum_id($data['id']);
		if ($forum_id == 0) {
			return new WP_Error('error', 'Parameter value of ID for a forum should not be 0', array('status' => 404));
		}
		if (!bbp_is_forum($forum_id)) {
			return new WP_Error('error', 'Parameter value ' . $data['id'] . ' is not an ID of a forum', array('status' => 404));
		}
		if (bbp_is_forum_category($forum_id)) {
			return new WP_Error('error', 'Forum with ID ' . $data['id'] . ' is a category, so no topics allowed', array('status' => 404));
		}

		$content = $data['content'];
		$title = substr(wp_filter_nohtml_kses($data['content']), 0, 10);
		$current_user = wp_get_current_user();
		$userId = $current_user->ID;

		$_tags = isset($data['tags']) ? $data['tags'] : "";
		$tags = explode(',', $_tags);

		$post_status = 'publish';

		$raw_enable_topic_check = get_option('raw_enable_topic_check');
		if (!empty($raw_enable_topic_check)) {
			$post_status = 'pending';
		}

		$new_topic_id = bbp_insert_topic(
			array(
				'post_parent' => $forum_id,
				'post_title' => $title,
				'post_content' => $content,
				'post_author' => $userId,
				'post_status' => $post_status
			),
			array(
				'forum_id' => $forum_id,
			)
		);

		if (!empty($new_topic_id)) {
			$term_taxonomy_ids = wp_set_object_terms($new_topic_id, $tags, 'topic-tag');

			$message = "提交成功";
			$messagecode = "1";

			if (!empty($raw_enable_topic_check)) {
				$message = "提交成功,管理员审核通过后方可显示.";
				$messagecode = "2";//需要审核显示

			}

			$response = array('success' => true,
				'messagecode' => $messagecode,
				'message' => $message,
				'new_topic_id' => $new_topic_id,
				'post_status' => $post_status
			);
			$response = rest_ensure_response($response);

		} else {
			return new WP_Error('error', '发表失败', array('status' => 400));
		}
		return $response;
	}

	function bbp_api_new_topic_post_permissions_check($request) {

		$current_user = wp_get_current_user();
		$ID = $current_user->ID;
		if ($ID == 0) {
			return new WP_Error('error', '尚未登录或Token无效', array('status' => 400));
		}

		$tags = isset($request['tags']) ? $request['tags'] : "";

		if (!empty($tags)) {
			if (strlen($tags) > 50) {
				return new WP_Error('error', '标签字数太多了', array('status' => 400));
			}
			$tempTags = explode(',', $tags);
			if (!is_array($tempTags)) {
				return new WP_Error('error', '标签格式不正确', array('status' => 400));
			}
		}

		$content = $request['content'];
		if (empty($content)) {
			return new WP_Error('error', '内容为空', array('status' => 400));
		}

		if (strlen($content) > 5000) {
			return new WP_Error('error', '内容文字太多，超过5000字', array('status' => 400));
		}

		return true;
	}

}
