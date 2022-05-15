<?php

//禁止直接访问
if (!defined('ABSPATH')) exit;

class UniWechatAPI {

	public static $API_URL = array(
		'msgSecCheck' => 'https://api.weixin.qq.com/wxa/msg_sec_check',
	);

	// 获取Access Token
	public function get_access_token() {

		// 读取微信 AppID 和 AppSecret
		$appid = get_option('wf_appid');
		$secret = get_option('wf_secret');

		$access_token = get_option('uni_wechat_access_token');

		// 读取缓存
		if (!empty($access_token) && time() < $access_token['expire_time']) {
			return $access_token['access_token'];
		}

		// 未缓存或者缓存过期
		$api_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;
		$response = wp_remote_get($api_url);

		if (!is_wp_error($response) && is_array($response) && isset($response['body'])) {
			$result = json_decode($response['body'], true);
			if (!isset($result['errcode']) || $result['errcode'] == 0) {
				$access_token = array(
					'access_token' => $result['access_token'],
					'expire_time' => time() + intval($result['expires_in']) - 300
				);
				update_option('uni_wechat_access_token', $access_token);
				return $access_token['access_token'];
			}
		}
		return false;
	}

	// 发起API请求
	private function request($url, $method, $body) {

		if (strpos($url, 'msg_sec_check') != false) {
			//内容安全检测不进行unicode转码
			$body = json_encode($body, JSON_UNESCAPED_UNICODE);

		} else {
			$body = json_encode($body);

		}
		$response = wp_remote_request($url, array(
			'method' => $method,
			'body' => $body
		));

		return !is_wp_error($response) ? json_decode($response['body'], true) : false;
	}

	public function invokingRequest($api, $data) {
		$access_token = $this->get_access_token();
		$access_token = $access_token ? '?access_token=' . $access_token : '';
		$api_url = UniWechatAPI::$API_URL[$api];
		$result = "";
		if (!empty($access_token)) {
			$api_url = $api_url . $access_token;
			$result = $this->request($api_url, 'POST', $data);
		}
		return $result;
	}

	// 文字内容审查
	public function msgSecCheck($data) {
		return $this->invokingRequest('msgSecCheck', $data);
	}

}

