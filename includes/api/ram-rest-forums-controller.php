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
							return is_numeric($param) && bbp_is_forum($param);
						}
					),
					'page' => array(
						'required' => false,
						'validate_callback' => function ($param, $request, $key) {
							return is_numeric($param) && $param > 0;
						},
						'default' => 1
					),
					'per_page' => array(
						'required' => false,
						'validate_callback' => function ($param, $request, $key) {
							return is_numeric($param) && $param > 0 && $param < 100;
						},
						'default' => 10
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
						'validate_callback' => function ($topic_id, $request, $key) {
							return is_numeric($topic_id) && bbp_is_topic($topic_id);
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
						'required' => true,
						'validate_callback' => function ($param, $request, $key) {
							return is_numeric($param) && bbp_is_topic($param);
						}
					),
					'order' => array(
						'required' => false,
						'validate_callback' => function ($param, $request, $key) {
							return in_array($param, array('asc', 'desc'));
						},
						'default' => 'asc'
					),
					'page' => array(
						'required' => false,
						'validate_callback' => function ($param, $request, $key) {
							return is_numeric($param) && $param > 0 && $param < 100;
						},
						'default' => 1
					),
					'per_page' => array(
						'required' => false,
						'validate_callback' => function ($param, $request, $key) {
							return is_numeric($param) && $param > 0 && $param < 100;
						},
						'default' => 10
					)
				)
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));

		// 注册回复帖子API
		register_rest_route($this->namespace, '/' . $this->resource_name . '/reply', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'bbp_api_new_reply'),
				'permission_callback' => array($this, 'jwt_permissions_check'),
				'args' => array(
					'content' => array(
						'required' => true,
						'type' => 'string',
						'validate_callback' => function ($param, $request, $key) {
							return !empty($param) && strlen($param) < 5000;
						}
					),
					'topic_id' => array(
						'required' => true,
						'validate_callback' => function ($param, $request, $key) {
							return is_numeric($param) && bbp_is_topic($param);
						}
					),
					'reply_to_id' => array(
						'required' => true,
						'validate_callback' => function ($param, $request, $key) {
							return is_numeric($param) && ($param === 0 || bbp_is_reply($param));
						},
						'default' => 0
					),
					'platform' => array(
						'required' => true,
						'validate_callback' => function ($param, $request, $key) {
							return in_array($param, array('APP', 'H5', 'MP-WEIXIN', 'MP-ALIPAY', 'MP-BAIDU', 'MP-TOUTIAO', 'MP-QQ'));
						}
					)
				)
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema')
		));

		// 注册发布帖子到指定论坛API
		register_rest_route($this->namespace, '/' . $this->resource_name . '/topic', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'bbp_api_new_topic_post'),
				'permission_callback' => array($this, 'jwt_permissions_check'),
				'args' => array(
					'content' => array(
						'required' => true,
						'type' => 'string',
						'validate_callback' => function ($param, $request, $key) {
							return !empty($param) && strlen($param) < 10000;
						}
					),
					'forum_id' => array(
						'validate_callback' => function ($param, $request, $key) {
							return is_numeric($param) && bbp_is_forum($param);
						}
					),
					'tags' => array(
						'required' => false,
						'type' => 'array',
						'default' => array(),
						'validate_callback' => function ($tags, $request, $key) {
							foreach ($tags as $tag) {
								if (!empty($tag)) {
									if (strlen($tag) > 20) {
										// 标签字数大于20了
										return false;
									}
								}
							}
							return true;
						}
					),
					'images' => array(
						'required' => false,
						'type' => 'array',
						'default' => array(),
						'validate_callback' => function ($images, $request, $key) {
							foreach ($images as $image) {
								if (!empty($image)) {
									// 判断$image是否是图片url
									if (!preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $image)) {
										return false;
									}
								}
							}
							return true;
						}
					),
					'platform' => array(
						'required' => true,
						'validate_callback' => function ($param, $request, $key) {
							return in_array($param, array('APP', 'H5', 'MP-WEIXIN', 'MP-ALIPAY', 'MP-BAIDU', 'MP-TOUTIAO', 'MP-QQ'));
						}
					)
				)
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema')
		));

		// 给文章点赞
		register_rest_route($this->namespace, '/' . $this->resource_name . '/like', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'bbp_topic_like'),
				'permission_callback' => array($this, 'jwt_permissions_check'),
				'args' => array(
					'id' => array(
						'validate_callback' => function ($param, $request, $key) {
							return is_numeric($param) && bbp_is_topic($param);
						}
					),
					'isLike' => array(
						'required' => true,
						'type' => 'boolean',
						'default' => true,
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
		return $all_forums_data;
	}

	// 获取指定论坛文章列表方法
	public function bbp_api_forums_one($request) {
		$all_forum_data = array();
		$bbp = bbpress();
		$forum_id = bbp_get_forum_id($request['id']);

		$per_page = $request['per_page'];
		$page = $request['page'];

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
		return $all_forum_data;
	}

	private function get_topic_detail($topic_id, $is_show_content = false) {
		$one_sticky = array();
		$one_sticky['id'] = $topic_id;
		$one_sticky['title'] = html_entity_decode(bbp_get_topic_title($topic_id));
		$one_sticky['reply_count'] = bbp_get_topic_reply_count($topic_id, true);
		$one_sticky['permalink'] = bbp_get_topic_permalink($topic_id);
		$author_id = bbp_get_topic_author_id($topic_id);
		$one_sticky['author_id'] = $author_id;
		$one_sticky['author_name'] = bbp_get_topic_author_display_name($topic_id);;
		if ($author_id !== 0) {
			$one_sticky['author_avatar'] = get_avatar_url_2($author_id);
		} else {
			$one_sticky['author_avatar'] = get_avatar_url_2(bbp_get_topic_author_email($topic_id));
		}
		$one_sticky['views'] = (int)get_post_meta($topic_id, 'views', true);
		$one_sticky['post_date'] = bbp_get_topic_post_date($topic_id);
		$one_sticky['excerpt'] = mb_strimwidth(wp_filter_nohtml_kses(bbp_get_topic_content($topic_id)), 0, 150, '...');
		$one_sticky['all_img'] = get_post_content_images(bbp_get_topic_content($topic_id));
		if ($is_show_content === true) {
			$one_sticky['content_nohtml'] = wp_filter_nohtml_kses(bbp_get_topic_content($topic_id));
		}
		$one_sticky['like_count'] = count(bbp_get_topic_favoriters($topic_id));

		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		if ($user_id != 0) {
			$one_sticky['is_user_favorite'] = bbp_is_user_favorite($user_id, $topic_id);
		} else {
			$one_sticky['is_user_favorite'] = false;
		}
		return $one_sticky;
	}

	// 获取指定文章详情
	public function bbp_api_topics_one($request) {
		$all_topic_data = array();
		$topic_id = (int)$request['id'];

		$all_topic_data['id'] = $topic_id;
		$all_topic_data['title'] = html_entity_decode(bbp_get_topic_title($topic_id));
		$all_topic_data['reply_count'] = (int)bbp_get_topic_reply_count($topic_id);
		$all_topic_data['permalink'] = bbp_get_topic_permalink($topic_id);
		$tags = wp_get_object_terms($topic_id, "topic-tag");
		foreach ($tags as $tag) {
			$all_topic_data['tags'][] = array('id' => $tag->term_id, 'name' => $tag->name);
		}
		$all_topic_data['author_name'] = bbp_get_topic_author_display_name($topic_id);
		$author_id = bbp_get_topic_author_id($topic_id);
		$all_topic_data['author_id'] = $author_id;
		if ($author_id !== 0) {
			$all_topic_data['author_avatar'] = get_avatar_url_2($author_id);
		} else {
			$all_topic_data['author_avatar'] = get_avatar_url_2(bbp_get_topic_author_email($topic_id));
		}
		$all_topic_data['post_date'] = bbp_get_topic_post_date($topic_id);
		$all_topic_data['is_sticky'] = bbp_is_topic_sticky($topic_id);
		$all_topic_data['is_super_sticky'] = bbp_is_topic_super_sticky($topic_id);
		$all_topic_data['status'] = bbp_get_topic_status($topic_id);

		$raw_enable_comment_option = get_option('raw_enable_comment_option');
		$all_topic_data['is_comment_enabled'] = empty($raw_enable_comment_option);

		$views = (int)get_post_meta($topic_id, 'views', true);
		$all_topic_data['views'] = $views;

		$views = $views + 1;
		if (!update_post_meta($topic_id, 'views', $views)) {
			add_post_meta($topic_id, 'views', 1, true);
		}
		$all_topic_data['content'] = bbp_get_topic_content($topic_id);
		$all_topic_data['like_count'] = count(bbp_get_topic_favoriters($topic_id));

		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		if ($user_id != 0) {
			$all_topic_data['is_user_favorite'] = bbp_is_user_favorite($user_id, $topic_id);
		} else {
			$all_topic_data['is_user_favorite'] = false;
		}
		return $all_topic_data;

	}

	// 获取指定文章评论
	public function bbp_api_replies_one($request) {
		$topic_id = bbp_get_topic_id($request['id']);
		$per_page = $request['per_page'];
		$page = $request['page'];
		$page = ($page - 1) * $per_page;
		$order = $request["order"];
		global $wpdb;
		$sql = "SELECT " . $wpdb->posts . ".* from " . $wpdb->posts . ", " . $wpdb->postmeta . " WHERE ID = post_id AND post_status = 'publish' AND post_type = 'reply' AND meta_key = '_bbp_topic_id' AND meta_value = " . $topic_id . " AND ID NOT IN (select post_id from " . $wpdb->postmeta . " where meta_key = '_bbp_reply_to') ORDER BY post_date " . $order . " LIMIT " . $page . "," . $per_page;

		$comments = $wpdb->get_results($sql);
		$comments_list = array();

		foreach ($comments as $comment) {
			$post_author_id = $comment->post_author;
			$reply_id = (int)$comment->ID;
			$res["userid"] = (int)$post_author_id;
			$res["id"] = $reply_id;
			// 判断用户是否是匿名评论
			if ($post_author_id != 0) {
				$res["author_name"] = get_user_meta($post_author_id, 'nickname', true);
				$res["author_avatar"] = get_avatar_url_2($post_author_id);
			} else {
				$res["author_name"] = get_post_meta($reply_id, '_bbp_anonymous_name', true);
				$res["author_avatar"] = get_avatar_url_2(get_post_meta($reply_id, '_bbp_anonymous_email', true));
			}
			$res["post_date"] = time_tran($comment->post_date);
			$res["content"] = $comment->post_content;

			if (get_option("uni_show_comment_location")) {
				$ip = get_post_meta($reply_id, '_bbp_author_ip', true);
				$res["location"] = empty($ip) ? null : get_ip_location($ip);
			}

			$res["child"] = $this->get_child_comment($topic_id, $reply_id);
			$comments_list[] = $res;

		}
		return $comments_list;
	}

	private function get_child_comment($topic_id, $reply_id) {
		global $wpdb;
		$sql = "SELECT " . $wpdb->posts . ".* from " . $wpdb->posts . ", " . $wpdb->postmeta . " WHERE ID = post_id AND post_status = 'publish' AND post_type = 'reply' AND meta_key = '_bbp_topic_id' AND meta_value = " . $topic_id . " AND ID IN (select post_id from  " . $wpdb->postmeta . " where meta_key = '_bbp_reply_to' AND meta_value = " . $reply_id . ") ORDER BY post_date DESC";

		$comments = $wpdb->get_results($sql);

		$comments_list = array();
		foreach ($comments as $comment) {
			$post_author_id = $comment->post_author;
			$reply_id = (int)$comment->ID;
			$res["userid"] = (int)$post_author_id;
			$res["id"] = $reply_id;
			// 判断用户是否是匿名评论
			if ($post_author_id != 0) {
				$res["author_name"] = get_user_meta($post_author_id, 'nickname', true);
				$res["author_avatar"] = get_avatar_url_2($post_author_id);
			} else {
				$res["author_name"] = get_post_meta($reply_id, '_bbp_anonymous_name', true);
				$res["author_avatar"] = get_avatar_url_2(get_post_meta($reply_id, '_bbp_anonymous_email', true));
			}
			$res["post_date"] = time_tran($comment->post_date);
			$res["content"] = $comment->post_content;
			$res["child"] = $this->get_child_comment($topic_id, $reply_id);
			$comments_list[] = $res;

		}
		return $comments_list;
	}

	// 发表一个新文章
	public function bbp_api_new_topic_post($request) {
		$forum_id = bbp_get_forum_id($request['forum_id']);
		$platform = $request['platform'];
		$content = $request['content'];
		// 过滤xss内容
		$content = wp_filter_post_kses($content);
		$title = mb_substr(trim(wp_filter_nohtml_kses($content)), 0, 10);

		$images = $request['images'];
		// 将图片插入到文章
		foreach ($images as $image) {
			$content .= '<br/><img src="' . $image . '" alt=""/>';
		}

		$current_user = wp_get_current_user();
		$userId = $current_user->ID;
		$tags = isset($request['tags']) ? $request['tags'] : [];

		$uni_enable_manual_censorship = get_option('uni_enable_manual_censorship');
		$uni_enable_ai_censorship = get_option('uni_enable_ai_censorship');

		$post_status = 'publish';
		if (!empty($uni_enable_manual_censorship)) {
			$post_status = 'pending';
		}

		// 判断用户是否开启了小程序端评论检测
		if (!empty($uni_enable_ai_censorship)) {
			if ($platform === 'MP-WEIXIN') {
				$data = array(
					'content' => $content
				);
				$msgSecCheckResult = UniRestAPIInstance()->WechatAPI->msgSecCheck($data);
				$errcode = $msgSecCheckResult['errcode'];
				$errmsg = $msgSecCheckResult['errmsg'];
				if ($errcode == 87014) {
					return new WP_Error($errcode, "内容违规", array('status' => 403));
				}
			} else if ($platform === 'MP-QQ') {
				$data = array(
					'content' => $content
				);
				$msgSecCheckResult = UniRestAPIInstance()->QQAPI->msgSecCheck($data);
				$errcode = $msgSecCheckResult['errCode'];
				$errmsg = $msgSecCheckResult['errMsg'];
				if ($errcode == 87014) {
					return new WP_Error($errcode, "内容违规", array('status' => 403));
				}
			}
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
			// 将文章和图片关联
			foreach ($images as $image) {
				$image_id = attachment_url_to_postid($image);
				if (!empty($image_id)) {
					wp_update_post(array(
						'ID' => $image_id,
						'post_parent' => $new_topic_id,
					));
				}
			}

			$message = "发布成功";
			$code = "1";

			//需要审核显示
			if (!empty($uni_enable_manual_censorship)) {
				$message = "提交成功,管理员审核通过后方可显示";
				$code = "2";
			}

			$response = array('success' => true,
				'code' => $code,
				'message' => $message,
				'data' => array(
					'new_topic_id' => $new_topic_id,
					'post_status' => $post_status
				)
			);
			return rest_ensure_response($response);
		} else {
			return new WP_Error('error', '发布失败', array('status' => 400));
		}
	}

	// 发表一个新评论
	public function bbp_api_new_reply($request) {

		$content = $request['content'];
		$platform = $request['platform'];

		// 过滤xss内容
		$content = wp_filter_post_kses($content);
		$topic_id = $request["topic_id"];
		$reply_to_id = $request["reply_to_id"];
		$forum_id = bbp_get_forum_id($topic_id);

		$current_user = wp_get_current_user();
		$userId = $current_user->ID;

		$uni_enable_manual_censorship = get_option('uni_enable_manual_censorship');
		$uni_enable_ai_censorship = get_option('uni_enable_ai_censorship');

		$post_status = 'publish';
		if (!empty($uni_enable_manual_censorship)) {
			$post_status = 'pending';
		}

		// 判断用户是否开启了小程序端评论检测
		if (!empty($uni_enable_ai_censorship)) {
			if ($platform === 'MP-WEIXIN') {
				$data = array(
					'content' => $content
				);
				$msgSecCheckResult = UniRestAPIInstance()->WechatAPI->msgSecCheck($data);
				$errcode = $msgSecCheckResult['errcode'];
				$errmsg = $msgSecCheckResult['errmsg'];
				if ($errcode == 87014) {
					return new WP_Error($errcode, "内容违规", array('status' => 403));
				}
			} else if ($platform === 'MP-QQ') {
				$data = array(
					'content' => $content
				);
				$msgSecCheckResult = UniRestAPIInstance()->QQAPI->msgSecCheck($data);
				$errcode = $msgSecCheckResult['errCode'];
				$errmsg = $msgSecCheckResult['errMsg'];
				if ($errcode == 87014) {
					return new WP_Error($errcode, "内容违规", array('status' => 403));
				}
			}
		}

		$new_reply_id = bbp_insert_reply(array(
			'post_parent' => $topic_id, // topic ID
			'post_content' => $content,
			'post_author' => $userId,
			'post_status' => $post_status
		), array(
			'forum_id' => $forum_id,
			'topic_id' => $topic_id,
			'reply_to' => $reply_to_id
		));

		if (!empty($new_reply_id)) {
			$message = "发表成功";
			$code = "1";

			if (!empty($uni_enable_manual_censorship)) {
				$message = "提交成功,管理员审核通过后方可显示";
				$code = "2";    //需要审核显示
			}

			$response = array(
				'code' => $code,
				'message' => $message,
				'data' => array(
					'new_reply_id' => $new_reply_id,
					'post_status' => $post_status
				)
			);

			$response = rest_ensure_response($response);

		} else {
			return new WP_Error('error', '发表失败', array('status' => 400));
		}
		return $response;
	}

	// 给文章点赞
	public function bbp_topic_like($request) {
		$post_id = $request["id"];
		$is_like = !empty($request["isLike"]);

		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		if ($user_id == 0) {
			return new WP_Error('error', '尚未登录或Token无效', array('status' => 400));
		}

		if ($is_like) {
			if (bbp_add_user_favorite($user_id, $post_id)) {
				$res["code"] = "200";
				$res["message"] = "点赞成功";
			} else {
				$res["code"] = "400";
				$res["message"] = "点赞失败";
			}
		} else {
			if (bbp_remove_user_favorite($user_id, $post_id)) {
				$res["code"] = "200";
				$res["message"] = "取消点赞成功";
			} else {
				$res["code"] = "400";
				$res["message"] = "取消点赞失败";
			}
		}


		return rest_ensure_response($res);
	}

	function jwt_permissions_check() {
		$current_user = wp_get_current_user();
		$ID = $current_user->ID;
		if ($ID == 0) {
			return new WP_Error('error', '尚未登录或Token无效', array('status' => 400));
		}
		return true;
	}
}
