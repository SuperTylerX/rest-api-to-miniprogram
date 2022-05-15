<?php

if (!defined('ABSPATH')) {
	exit;
}

class RAM_REST_Comments_Controller extends WP_REST_Controller {
	public function __construct() {
		$this->namespace = 'uni-app-rest-enhanced/v1';
		$this->resource_name = 'comment';
	}

	public function register_routes() {
		// 注册获取某篇文章的评论的路由
		register_rest_route($this->namespace, '/' . $this->resource_name . '/getcomments', array(
			array(
				'methods' => 'GET',
				'callback' => array($this, 'get_comments'),
				'args' => array(
					'postid' => array(
						'required' => true,
						'validate_callback' => function ($param, $request, $key) {
							return is_numeric($param);
						}
					),
					'page' => array(
						'required' => false,
						'validate_callback' => function ($param, $request, $key) {
							return is_numeric($param);
						},
						'default' => 1
					),
					'limit' => array(
						'required' => false,
						'validate_callback' => function ($param, $request, $key) {
							return is_numeric($param);
						},
						'default' => 10
					),
					'order' => array(
						'required' => false,
						'default' => 'asc',
						'validate_callback' => function ($param, $request, $key) {
							return in_array($param, array('asc', 'desc'));
						}
					),
				)

			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));

		// 注册获取某个用户的评论的路由
		register_rest_route($this->namespace, '/' . $this->resource_name . '/get', array(
			array(
				'methods' => 'GET',
				'callback' => array($this, 'get_comment_by_user'),
				'permission_callback' => array($this, 'jwt_permissions_check')
			),
			'schema' => array($this, 'get_public_item_schema'),
		));

		// 注册删除某个用户的评论的路由
		register_rest_route($this->namespace, '/' . $this->resource_name . '/delete', array(
			array(
				'methods' => 'DELETE',
				'callback' => array($this, 'delete_comment'),
				'permission_callback' => array($this, 'jwt_permissions_check'),
				'args' => array(
					'commentId' => array(
						'required' => true
					)
				)

			),
			'schema' => array($this, 'get_public_item_schema'),
		));

		// 注册添加评论的路由
		register_rest_route($this->namespace, '/' . $this->resource_name . '/add', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'add_comment'),
				'permission_callback' => array($this, 'jwt_permissions_check'),
				'args' => array(
					'post' => array(
						'required' => true
					),
					'parent' => array(
						'required' => true
					),
					'content' => array(
						'required' => true
					),
					'platform' => array(
						'required' => true,
						'validate_callback' => function ($param, $request, $key) {
							return in_array($param, array('APP', 'H5', 'MP-WEIXIN', 'MP-ALIPAY', 'MP-BAIDU', 'MP-TOUTIAO', 'MP-QQ'));
						}
					)
				)

			),
			'schema' => array($this, 'get_public_item_schema'),
		));
	}


	//获取某个用户的评论
	public function get_comment_by_user($request) {
		global $wpdb;

		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;

		$sql = "SELECT * from " . $wpdb->posts . ", " . $wpdb->comments . " WHERE comment_approved = 1 AND comment_post_ID = ID AND user_id= " . $user_id . " order by comment_date DESC LIMIT 20";
		$_posts = $wpdb->get_results($sql);
		$posts = array();
		foreach ($_posts as $post) {
			$_data["post"] = $post->ID;
			$_data["id"] = $post->comment_ID;
			$_data["post_title"] = $post->post_title;
			$_data["comment_content"] = $post->comment_content;
			$posts[] = $_data;
		}
		$result["code"] = "success";
		$result["message"] = "获取评论成功！";
		$result["status"] = "200";
		$result["data"] = $posts;


		$response = rest_ensure_response($result);
		return $response;
	}

	// 获取某篇文章的评论
	public function get_comments($request) {
		$cachedata = '';
		if (function_exists('MRAC')) {
			$cachedata = MRAC()->cacheManager->get_cache();
			if (!empty($cachedata)) {
				$response = rest_ensure_response($cachedata);
				return $response;
			}
		}
		global $wpdb;
		$postid = (int)$request['postid'];
		$limit = (int)$request['limit'];
		$page = (int)$request['page'];
		$order = $request['order'];

		$page = ($page - 1) * $limit;
		$sql = $wpdb->prepare("SELECT t.* FROM " . $wpdb->comments . " t WHERE t.comment_post_ID =%d and t.comment_parent=0 and t.comment_approved='1' order by t.comment_date " . $order . " limit %d,%d", $postid, $page, $limit);

		$comments = $wpdb->get_results($sql);
		$comments_list = array();
		foreach ($comments as $comment) {
			if ($comment->comment_parent == 0) {
				$data["id"] = $comment->comment_ID;
				$data["author_name"] = $comment->comment_author;
				// 判断是否是访客留言
				if (!empty($comment->user_id)) {
					$data["author_url"] = get_avatar_url_2($comment->user_id);
				} else {
					$data["author_url"] = get_avatar_url_2($comment->comment_author_email);
				}
				$data["date"] = time_tran($comment->comment_date);
				$data["content"] = $comment->comment_content;
				$data["userid"] = $comment->user_id;
				if (get_option("uni_show_comment_location")) {
					$data["location"] = empty($comment->comment_author_IP) ? null : get_ip_location($comment->comment_author_IP);
				}
				$data["child"] = $this->get_child_comment($postid, $comment->comment_ID, 5, "asc");
				$comments_list[] = $data;
			}
		}
		$result["code"] = "success";
		$result["message"] = "获取评论成功";
		$result["status"] = "200";
		$result["data"] = $comments_list;

		if ($cachedata == '' && function_exists('MRAC')) {
			$cachedata = MRAC()->cacheManager->set_cache($result, 'postcomments', $postid);
		}
		return rest_ensure_response($result);
	}

	private function get_child_comment($postid, $comment_id, $limit, $order) {
		global $wpdb;
		if ($limit > 0) {
			$comments_list = array();
			$sql = $wpdb->prepare("SELECT t.* FROM " . $wpdb->comments . " t WHERE t.comment_post_ID =%d and t.comment_parent=%d and t.comment_approved='1' order by comment_date " . $order, $postid, $comment_id);

			$comments = $wpdb->get_results($sql);
			foreach ($comments as $comment) {
				$data["id"] = $comment->comment_ID;
				$data["author_name"] = $comment->comment_author;
				// 判断是否是访客留言
				if (!empty($comment->user_id)) {
					$data["author_url"] = get_avatar_url_2($comment->user_id);
				} else {
					$data["author_url"] = get_avatar_url_2($comment->comment_author_email);
				}
				$data["date"] = time_tran($comment->comment_date);
				$data["content"] = $comment->comment_content;
				$data["userid"] = $comment->user_id;
				$data["child"] = $this->get_child_comment($postid, $comment->comment_ID, $limit - 1, $order);
				$comments_list[] = $data;
			}
		}
		return $comments_list;
	}

	// 删除用户的评论
	public function delete_comment($request) {
		$commentId = $request["commentId"];
		$comment = get_comment($commentId);
		$current_user = wp_get_current_user();
		$ID = $current_user->ID;

		$result = [];
		if ($comment->user_id == $ID) {
			if (wp_delete_comment($commentId)) {
				$result["code"] = "success";
				$result["message"] = "删除评论成功";
				$result["status"] = "200";
			} else {
				$result["code"] = "failed";
				$result["message"] = "删除评论失败";
				$result["status"] = "500";
			}
		} else {
			$result["code"] = "failed";
			$result["message"] = "删除评论失败，权限拒绝";
			$result["status"] = "401";
		}
		return rest_ensure_response($result);
	}

	// 新增评论
	public function add_comment($request) {

		$post = $request['post'];
		$parent = $request['parent'];
		$content = $request['content'];
		$platform = $request['platform'];

		// 过滤xss内容
		$content = wp_filter_nohtml_kses($content);
		// filter hyperlinks
//		$content = preg_replace('/<a\s+.*?href="([^"]+)"[^>]*>(.*?)<\/a>/i', '$2', $content);
//		// filter images
//		$content = preg_replace('/<img\s+.*?src="([^"]+)"[^>]*>/i', '', $content);

		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		$author_name = $current_user->display_name;
		$author_email = $current_user->user_email;
		$author_url = $current_user->user_url;

		$authorIp = get_client_ip();
		$authorIp = empty($authorIp) ? '' : $authorIp;
		$uni_enable_manual_censorship = get_option('uni_enable_manual_censorship');
		$uni_enable_ai_censorship = get_option('uni_enable_ai_censorship');

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

		// 判断是否需要人工审核
		$comment_approved = "1";
		$userLevel = getUserLevel($user_id);
		if (!empty($uni_enable_manual_censorship) && $userLevel["level"] == '0') {
			$comment_approved = "0";
		}

		$commentdata = array(
			'comment_post_ID' => $post, // to which post the comment will show up
			'comment_author' => $author_name, //fixed value - can be dynamic
			'comment_author_email' => $author_email, //fixed value - can be dynamic
			'comment_author_url' => $author_url, //fixed value - can be dynamic
			'comment_content' => $content, //fixed value - can be dynamic
			'comment_type' => '', //empty for regular comments, 'pingback' for pingbacks, 'trackback' for trackbacks
			'comment_parent' => $parent, //0 if it's not a reply to another comment; if it's a reply, mention the parent comment ID here
			'user_id' => $user_id, //passing current user ID or any predefined as per the demand
			'comment_author_IP' => $authorIp,
			'comment_approved' => $comment_approved,
//			'comment_agent' => $_SERVER['HTTP_USER_AGENT'] // 加入UA后小程序端无法正常留言
		);

		$comment_id = wp_insert_comment(wp_filter_comment($commentdata));

		if (empty($comment_id)) {
			return new WP_Error('error', '添加评论失败', array('status' => 500));
		} else {

			$result["code"] = "success";
			$message = '留言成功';

			if (!empty($uni_enable_manual_censorship) && $userLevel["level"] == '0') {
				$message = '留言已提交,需管理员审核方可显示。';
			}

			if (function_exists('MRAC')) {
				$cachedata = MRAC()->cacheManager->delete_cache('postcomments', $post);
			}

			$result["status"] = "200";
			$result["level"] = $userLevel;
			$result['comment_approved'] = $comment_approved;
			$result["message"] = $message;
			$response = rest_ensure_response($result);
			return $response;
		}
	}

	function jwt_permissions_check($request) {
		$current_user = wp_get_current_user();
		$ID = $current_user->ID;
		if ($ID == 0) {
			return new WP_Error('error', '尚未登录或Token无效', array('status' => 400));
		}
		return true;
	}
}
