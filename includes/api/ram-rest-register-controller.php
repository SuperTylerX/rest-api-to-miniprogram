<?php

if (!defined('ABSPATH')) {
	exit;
}

class RAM_REST_Register_Controller extends WP_REST_Controller {

	public function __construct() {
		$this->namespace = 'uni-app-rest-enhanced/v1';
		$this->resource_name = 'register';
		$this->max_duration = 5; // 注册/找回密码的流程最大时间，单位为分钟
	}

	public function register_routes() {

		register_rest_route($this->namespace, '/' . $this->resource_name . '/getGraphicCaptcha', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'getGraphicCaptcha'),
				'permission_callback' => '__return_true',
				'args' => array(
					'token' => array(
						'required' => false
					)
				)
			),
			'schema' => array($this, 'get_public_item_schema'),
		));

		register_rest_route($this->namespace, '/' . $this->resource_name . '/getEmailCaptcha', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'getEmailCaptcha'),
				'permission_callback' => array($this, 'get_email_captcha_permission_check'),
				'args' => array(
					'token' => array(
						'required' => true
					),
					'graphicCaptcha' => array(
						'required' => true
					),
					'email' => array(
						'required' => true
					)
				)
			),
			'schema' => array($this, 'get_public_item_schema'),
		));

		register_rest_route($this->namespace, '/' . $this->resource_name . '/submit', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'submit'),
				'permission_callback' => array($this, 'submit_permission_check'),
				'args' => array(
					'token' => array(
						'required' => true
					),
					'graphicCaptcha' => array(
						'required' => true
					),
					'emailCaptcha' => array(
						'required' => true
					),
					'email' => array(
						'required' => true
					),
					'password' => array(
						'required' => true
					),
					'nickname' => array(
						'required' => true
					)
				)
			),
			'schema' => array($this, 'get_public_item_schema'),
		));

		register_rest_route($this->namespace, '/' . $this->resource_name . '/reset', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'reset'),
				'permission_callback' => array($this, 'reset_permission_check'),
				'args' => array(
					'token' => array(
						'required' => true
					),
					'graphicCaptcha' => array(
						'required' => true
					),
					'emailCaptcha' => array(
						'required' => true
					),
					'email' => array(
						'required' => true
					),
					'password' => array(
						'required' => true
					)
				)
			),
			'schema' => array($this, 'get_public_item_schema'),
		));

	}

	function getGraphicCaptcha($request) {
		$width = 120; //设置图片宽为150像素
		$height = 40; //设置图片高为40像素

		$image = imagecreatetruecolor($width, $height); //设置验证码大小的函数
		$bgColor = imagecolorallocate($image, 255, 255, 255); //验证码颜色RGB为(255,255,255) #ffffff
		imagefill($image, 0, 0, $bgColor); //区域填充

		$cap_code = "";
		for ($i = 0; $i < 4; $i++) {
			$fontSize = 8; //设置字体大小
			$fontColor = imagecolorallocate($image, rand(0, 120), rand(0, 120), rand(0, 120)); //数字越大，颜色越浅，这里是深颜色0-120
			$data = 'QWERTYUIPASDFGHJKLZXCVBNM123456789'; //添加字符串
			$fontContent = substr($data, rand(0, strlen($data) - 1), 1); //去除值，字符串截取方法
			$cap_code .= $fontContent; //.=连续定义变量

			//设置坐标
			$x = ($i * $width / 4) + rand(5, 10);
			$y = rand(5, 10);

			// 写入image
			imagestring($image, $fontSize, $x, $y, $fontContent, $fontColor);
		}

		//设置干扰元素，设置雪花点
		for ($i = 0; $i < 300; $i++) {
			$inputColor = imagecolorallocate($image, rand(50, 200), rand(20, 200), rand(50, 200));
			//设置颜色，20-200颜色比数字浅，不干扰阅读
			imagesetpixel($image, rand(1, 149), rand(1, 39), $inputColor);
			//画一个单一像素的元素
		}

		//增加干扰元素，设置横线(先设置线的颜色，在设置横线)
		for ($i = 0; $i < 4; $i++) {
			$lineColor = imagecolorallocate($image, rand(20, 220), rand(20, 220), rand(20, 220));
			//设置线的颜色
			imageline($image, rand(1, 149), rand(1, 39), rand(1, 299), rand(1, 149), $lineColor);
		}

		// 将图片暂存至buffer中
		ob_start();
		imagepng($image);
		imagedestroy($image);
		$buffer = ob_get_clean();
		ob_end_clean();

		// 获取token，若无token，创建出一个新的token，记录验证码和过期时间，
		//           若有token，使用原有的token并覆盖掉原有的验证码和过期时间。
		$token = $request["token"];
		if (!empty($token)) {
			session_id($token);
		}
		session_start(["name" => "token", "use_cookies" => false]);
		$token = session_id();
		$_SESSION["graphic_captcha_challenge_count"] = 0;
		$_SESSION["uni_captcha"] = $cap_code;
		$_SESSION["uni_captcha_date"] = time();

		// 返回处理的图片，以base64格式返回
		$res["image"] = "data:image/png;base64," . base64_encode($buffer);
		$res["token"] = $token;

		return rest_ensure_response($res);
	}

	function getEmailCaptcha($request) {

		$graphicCaptcha = $request["graphicCaptcha"];
		$token = $request["token"];
		$email = $request["email"];
		session_id($token);
		session_start(["name" => "token", "use_cookies" => false]);
		$_SESSION["email_captcha_challenge_count"] = 0;  // 重置邮件验证码尝试次数

		if (empty($_SESSION)) {
			$res["code"] = "4001";
			// 实际上是session过期了
			$res["message"] = "验证码过期";
			session_destroy();
			return rest_ensure_response($res);
		}

		$server_side_captcha = $_SESSION["uni_captcha"];
		$server_side_captcha_date = $_SESSION["uni_captcha_date"];

		// 也许在某些情况下，SESSION并没有自动过期，于是使用存储的时间戳进行比较
		if (time() - $server_side_captcha_date > 1000 * 60 * $this->max_duration) {
			$res["code"] = "4002";
			$res["message"] = "验证码过期";
			session_destroy();
			return rest_ensure_response($res);
		}

		// 验证码尝试失败，最多进行5次尝试
		if ($server_side_captcha !== $graphicCaptcha) {
			$res["code"] = "4000";
			$res["message"] = "验证码错误";
			$_SESSION["graphic_captcha_challenge_count"] += 1;
			if ($_SESSION["graphic_captcha_challenge_count"] > 5) {
				// 图形验证码最多允许尝试5次，5次后过期重新获取
				$res["code"] = "4001";
				$res["message"] = "验证码过期";
				session_destroy();
				return rest_ensure_response($res);
			}
			// 验证码错误，但是允许再次尝试
			return rest_ensure_response($res);
		}
		$email_code = strval(mt_rand(99999, 999999));
		$is_mail_success = wp_mail($email, "[" . get_bloginfo('name') . "] " . "验证码", "您的验证码为【" . $email_code . "】。验证码" . $this->max_duration . "分钟内有效。");

		if (!$is_mail_success) {
			$res["code"] = "4003";
			$res["message"] = "验证码发送失败";
			return rest_ensure_response($res);
		}

		// 将email和校验码存在SEESION中，以备后面校验
		$_SESSION["uni_captcha_email"] = $request["email"];
		$_SESSION["uni_captcha_email_code"] = $email_code;
		$_SESSION["uni_captcha_email_date"] = time();

		$res["code"] = "200";
		$res["message"] = "成功";
		return rest_ensure_response($res);
	}

	function submit($request) {
		$graphicCaptcha = $request["graphicCaptcha"];
		$emailCaptcha = $request["emailCaptcha"];
		$email = $request["email"];
		$password = $request["password"];
		$nickname = $request["nickname"];
		$token = $request["token"];

		session_id($token);
		session_start(["name" => "token", "use_cookies" => false]);

		if (empty($_SESSION)) {
			$res["code"] = "4001";
			// 实际上是session过期了
			$res["message"] = "验证码过期";
			session_destroy();
			return rest_ensure_response($res);
		}

		$server_side_captcha = $_SESSION["uni_captcha"];
		$server_side_captcha_email = $_SESSION["uni_captcha_email"];
		$server_side_captcha_email_code = $_SESSION["uni_captcha_email_code"];
		$server_side_captcha_email_date = $_SESSION["uni_captcha_email_date"];

		// 也许在某些情况下，SESSION并没有自动过期，于是使用存储的时间戳进行比较
		if (time() - $server_side_captcha_email_date > 1000 * 60 * $this->max_duration) {
			$res["code"] = "4002";
			$res["message"] = "验证码过期";
			session_destroy();
			return rest_ensure_response($res);
		}

		// 验证码尝试失败，最多进行5次尝试
		if ($server_side_captcha_email_code !== $emailCaptcha ||
			$server_side_captcha !== $graphicCaptcha ||
			$server_side_captcha_email !== $email
		) {
			$res["code"] = "4000";
			$res["message"] = "验证码错误";
			$_SESSION["email_captcha_challenge_count"] += 1;
			if ($_SESSION["email_captcha_challenge_count"] > 5) {
				// 图形验证码最多允许尝试5次，5次后过期重新获取
				$res["code"] = "4001";
				$res["message"] = "验证码过期";
				session_destroy();
				return rest_ensure_response($res);
			}
			// 验证码错误，但是允许再次尝试
			return rest_ensure_response($res);
		}

		// 验证成功，创建新用户
		$new_user_data = apply_filters('new_user_data', array(
			'user_pass' => $password,
			'user_login' => get_random_string(16),
			'user_nicename' => $nickname,
			'user_email' => $email,
			'display_name' => $nickname,
			'nickname' => $nickname,
			'first_name' => $nickname,
		));

		$userId = wp_insert_user($new_user_data);
		if (is_wp_error($userId)) {
			$res["code"] = "5000";
			$res["message"] = $userId->get_error_message();
			session_destroy();
			return rest_ensure_response($res);
		}

		$res["code"] = "200";
		$res["message"] = "注册成功";
		session_destroy();
		return rest_ensure_response($res);
	}

	function reset($request) {
		$graphicCaptcha = $request["graphicCaptcha"];
		$emailCaptcha = $request["emailCaptcha"];
		$email = $request["email"];
		$password = $request["password"];
		$token = $request["token"];

		session_id($token);
		session_start(["name" => "token", "use_cookies" => false]);

		if (empty($_SESSION)) {
			$res["code"] = "4001";
			// 实际上是session过期了
			$res["message"] = "验证码过期";
			session_destroy();
			return rest_ensure_response($res);
		}

		$server_side_captcha = $_SESSION["uni_captcha"];
		$server_side_captcha_email = $_SESSION["uni_captcha_email"];
		$server_side_captcha_email_code = $_SESSION["uni_captcha_email_code"];
		$server_side_captcha_email_date = $_SESSION["uni_captcha_email_date"];

		// 也许在某些情况下，SESSION并没有自动过期，于是使用存储的时间戳进行比较
		if (time() - $server_side_captcha_email_date > 1000 * 60 * $this->max_duration) {
			$res["code"] = "4002";
			$res["message"] = "验证码过期";
			session_destroy();
			return rest_ensure_response($res);
		}

		// 验证码尝试失败，最多进行5次尝试
		if ($server_side_captcha_email_code !== $emailCaptcha ||
			$server_side_captcha !== $graphicCaptcha ||
			$server_side_captcha_email !== $email
		) {
			$res["code"] = "4000";
			$res["message"] = "验证码错误";
			$_SESSION["email_captcha_challenge_count"] += 1;
			if ($_SESSION["email_captcha_challenge_count"] > 5) {
				// 图形验证码最多允许尝试5次，5次后过期重新获取
				$res["code"] = "4001";
				$res["message"] = "验证码过期";
				session_destroy();
				return rest_ensure_response($res);
			}
			// 验证码错误，但是允许再次尝试
			return rest_ensure_response($res);
		}
		// 验证成功，更新用户密码

		$the_user = get_user_by_email($email);

		if ($the_user === false) {
			$res["code"] = "5000";
			$res["message"] = "该用户未注册";
			session_destroy();
			return rest_ensure_response($res);
		}
		$userId = wp_update_user(array(
			'user_pass' => $password,
			'user_email' => $email,
			'ID' => $the_user->ID
		));
		if (is_wp_error($userId)) {
			$res["code"] = "5000";
			$res["message"] = $userId->get_error_message();
			session_destroy();
			return rest_ensure_response($res);
		}

		$res["code"] = "200";
		$res["message"] = "更新密码成功";
		session_destroy();
		return rest_ensure_response($res);
	}

	function get_email_captcha_permission_check($request) {
		// 校验网站是否关闭了用户注册
		if (get_option('users_can_register') === "0") {
			return false;
		}
		$graphicCaptcha = $request["graphicCaptcha"];
		$email = $request["email"];
		if (!is_email($email)) {
			return false;
		}
		if (strlen($graphicCaptcha) !== 4) {
			return false;
		}
		return true;
	}

	function submit_permission_check($request) {
		// 校验网站是否关闭了用户注册
		if (get_option('users_can_register') === "0") {
			return false;
		}
		$graphicCaptcha = $request["graphicCaptcha"];
		$emailCaptcha = $request["emailCaptcha"];
		$email = $request["email"];
		$password = $request["password"];
		$nickname = $request["nickname"];
		if (!is_email($email)) {
			return false;
		}
		if (strlen($graphicCaptcha) !== 4) {
			return false;
		}
		if (strlen($emailCaptcha) !== 6) {
			return false;
		}
		if (strlen($password) > 20 || strlen($password) < 6) {
			return false;
		}
		if (strlen($nickname) > 20) {
			return false;
		}
		return true;
	}

	function reset_permission_check($request) {
		$graphicCaptcha = $request["graphicCaptcha"];
		$emailCaptcha = $request["emailCaptcha"];
		$email = $request["email"];
		$password = $request["password"];
		if (!is_email($email)) {
			return false;
		}
		if (strlen($graphicCaptcha) !== 4) {
			return false;
		}
		if (strlen($emailCaptcha) !== 6) {
			return false;
		}
		if (strlen($password) > 20 || strlen($password) < 6) {
			return false;
		}
		return true;
	}
}
