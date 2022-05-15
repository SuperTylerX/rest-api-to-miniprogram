<?php

if (!defined('ABSPATH')) {
	exit;
}

function weixinapp_create_menu() {
	// 创建新的顶级菜单
	add_menu_page('Uni App设置', 'Uni App设置', 'administrator', 'weixinapp_slug', 'weixinapp_settings_page', 'dashicons-smartphone', 99);
	add_submenu_page('weixinapp_slug', "基础设置", "基础设置", "administrator", 'weixinapp_slug', 'weixinapp_settings_page');
	// 调用注册设置函数
	add_action('admin_init', 'register_weixinappsettings');
}

function get_jquery_source() {
	$url = plugins_url('', __FILE__);
	wp_enqueue_style("tabs", plugins_url() . "/rest-api-to-miniprogram-enhanced/includes/js/tab/tabs.css", false, "1.0", "all");
	wp_enqueue_script("tabs", plugins_url() . "/rest-api-to-miniprogram-enhanced/includes/js/tab/tabs.min.js", false, "1.0");
	wp_enqueue_script('rawscript', plugins_url() . '/' . REST_API_TO_MINIPROGRAM_PLUGIN_NAME . '/includes/js/script.js', false, '1.0');
	if (function_exists('wp_enqueue_media')) {
		wp_enqueue_media();
	}
}


function register_weixinappsettings() {
	// 微信ID和秘钥设置
	register_setting('uniapp-group', 'wf_appid');
	register_setting('uniapp-group', 'wf_secret');

	// QQ ID和秘钥设置
	register_setting('uniapp-group', 'wf_qq_appid');
	register_setting('uniapp-group', 'wf_qq_secret');

	// 商户号
	register_setting('uniapp-group', 'wf_mchid');
	register_setting('uniapp-group', 'wf_paykey');
	register_setting('uniapp-group', 'wf_paybody');

	// 评论是否开启
	register_setting('uniapp-group', 'wf_enable_comment_option');
	register_setting('uniapp-group', 'wf_enable_qq_comment_option');
	register_setting('uniapp-group', 'uni_enable_h5_comment_option');

	register_setting('uniapp-group', 'uni_show_comment_location');

    register_setting('uniapp-group', 'wf_praise_word');
	register_setting('uniapp-group', 'wf_weixin_enterprise_minapp');
	register_setting('uniapp-group', 'wf_qq_enterprise_minapp');

	register_setting('uniapp-group', 'wf_list_ad');
	register_setting('uniapp-group', 'wf_list_ad_id');
	register_setting('uniapp-group', 'wf_list_ad_every');

	register_setting('uniapp-group', 'wf_excitation_ad_id');
	register_setting('uniapp-group', 'wf_video_ad_id');
	register_setting('uniapp-group', 'wf_interstitial_ad_id');

	register_setting('uniapp-group', 'wf_detail_ad');
	register_setting('uniapp-group', 'wf_detail_ad_id');
	register_setting('uniapp-group', 'wf_display_categories');

	register_setting('uniapp-group', 'wf_downloadfile_domain');
	register_setting('uniapp-group', 'wf_business_domain');
	register_setting('uniapp-group', 'wf_zan_imageurl');
	register_setting('uniapp-group', 'wf_share_imageurl');
	register_setting('uniapp-group', 'wf_poster_imageurl');

	register_setting('uniapp-group', 'enable_index_interstitial_ad');
	register_setting('uniapp-group', 'enable_detail_interstitial_ad');
	register_setting('uniapp-group', 'enable_topic_interstitial_ad');
	register_setting('uniapp-group', 'enable_list_interstitial_ad');
	register_setting('uniapp-group', 'enable_hot_interstitial_ad');
	register_setting('uniapp-group', 'enable_comments_interstitial_ad');
	register_setting('uniapp-group', 'enable_live_interstitial_ad');

	// APP 设置
	register_setting('uniapp-group', 'uni_app_updated_version');
	register_setting('uniapp-group', 'uni_app_updated_version_code');
	register_setting('uniapp-group', 'uni_app_updated_content');
	register_setting('uniapp-group', 'uni_app_updated_download_link');

	// 评论和发帖审核
	register_setting('uniapp-group', 'uni_enable_manual_censorship'); // 是否开启人工审核
	register_setting('uniapp-group', 'uni_enable_ai_censorship');   // 是否开启AI审核
}

function weixinapp_settings_page() {
	?>
    <div class="wrap">
        <h2>Uni APP设置</h2>
		<?php

		if (!empty($_REQUEST['settings-updated'])) {
			echo '<div id="message" class="updated fade"><p><strong>设置已保存</strong></p></div>';
		}

		if (version_compare(PHP_VERSION, '5.6.0', '<=')) {

			echo '<div class="notice notice-error is-dismissible">
    <p><font color="red">提示：php版本小于5.6.0, 插件程序将无法正常使用,当前系统的php版本是:' . PHP_VERSION . '</font></p>
    </div>';
		}
		?>
        <form method="post" action="options.php">
			<?php settings_fields('uniapp-group'); ?>
			<?php do_settings_sections('uniapp-group'); ?>
            <div class="responsive-tabs">
                <h2>通用设置</h2>
                <div class="section">
                    <table class="form-table">

                        <tr valign="top">
                            <th scope="row">是否开启人工评论和发帖审核</th>
                            <td>
								<?php
								$uni_enable_manual_censorship = get_option('uni_enable_manual_censorship');
								$is_uni_enable_manual_censorship = empty($uni_enable_manual_censorship) ? '' : 'checked';
								echo '<input name="uni_enable_manual_censorship"  type="checkbox" ' . $is_uni_enable_manual_censorship . ' />';
								?>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">是否开启AI评论和发帖审核</th>
                            <td>
								<?php
								$uni_enable_ai_censorship = get_option('uni_enable_ai_censorship');
								$is_uni_enable_ai_censorship = empty($uni_enable_ai_censorship) ? '' : 'checked';
								echo '<input name="uni_enable_ai_censorship"  type="checkbox" ' . $is_uni_enable_ai_censorship . ' />';
								?>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">是否开启显示评论IP位置</th>
                            <td>
			                    <?php
			                    $uni_show_comment_location = get_option('uni_show_comment_location');
			                    $is_uni_show_comment_location = empty($uni_show_comment_location) ? '' : 'checked';
			                    echo '<input name="uni_show_comment_location"  type="checkbox" ' . $is_uni_show_comment_location . ' />';
			                    ?>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">在小程序里显示的文章分类id</th>
                            <td><input type="text" name="wf_display_categories" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_display_categories')); ?>"/>
                                <br/>
                                <p style="color: #959595 ; display:inline">* 文章分类id,只支持一级分类,请用英文半角逗号分隔，留空则显示所有分类</p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">小程序logo图片地址</th>
                            <td><input type="text" name="wf_logo_imageurl" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_logo_imageurl')); ?>"/> <input
                                        id="wf_logo_imageurl-btn" class="button im-upload" type="button"
                                        value="选择图片"/><br/>
                                <p style="color: #959595; display:inline">*
                                    请输完整的图片地址，例如：https://www.watch-life.net/images/poster.jpg</p>
                            </td>

                        </tr>

                        <tr valign="top">
                            <th scope="row">海报图片默认地址</th>
                            <td><input type="text" name="wf_poster_imageurl" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_poster_imageurl')); ?>"/> <input
                                        id="wf_poster_imageurl-btn" class="button im-upload" type="button"
                                        value="选择图片"/><br/>
                                <p style="color: #959595; display:inline">*
                                    请输完整的图片地址，例如：https://www.watch-life.net/images/poster.jpg</p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">分享菜单图片地址</th>
                            <td><input type="text" name="wf_share_imageurl" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_share_imageurl')); ?>"/> <input
                                        id="wf_share_imageurl-btn" class="button im-upload" type="button"
                                        value="选择图片"/><br/>
                                <p style="color: #959595; display:inline">*
                                    请输完整的图片地址，例如：https://www.watch-life.net/images/poster.jpg</p>
                            </td>

                        </tr>

                        <tr valign="top">
                            <th scope="row">赞赏码图片地址</th>
                            <td><input type="text" name="wf_zan_imageurl" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_zan_imageurl')); ?>"/> <input
                                        id="wf_zan_imageurl-btn" class="button im-upload" type="button"
                                        value="选择图片"/><br/>
                                <p style="color: #959595; display:inline">*
                                    请输完整的图片地址，例如：https://www.watch-life.net/images/poster.jpg</p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">"赞赏"文字调整为</th>
                            <td><input type="text" name="wf_praise_word" placeholder="喜欢"
                                       style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_praise_word')); ?>"/><br/>
                                <p style="color: #959595; display:inline">*
                                    例如：<code>鼓励</code>,<code>喜欢</code>，<code>稀罕</code>，不要超过两个汉字</p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">downloadFile域名</th>
                            <td>
                                    <textarea name="wf_downloadfile_domain" id="wf_downloadfile_domain"
                                              class="large-text code"
                                              rows="3"><?php echo esc_attr(get_option('wf_downloadfile_domain')); ?></textarea>
                                <br/>
                                <p style="color: #959595; display:inline">请输入域名，用英文逗号分隔</p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">业务域名</th>
                            <td>
                                    <textarea name="wf_business_domain" id="wf_business_domain" class="large-text code"
                                              rows="3"><?php echo esc_attr(get_option('wf_business_domain')); ?></textarea>
                                <br/>
                                <p style="color: #959595; display:inline">请输入域名，用英文逗号分隔。仅支持企业主体小程序。</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <h2>微信小程序设置</h2>
                <div class="section">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">AppID</th>
                            <td><input type="text" name="wf_appid" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_appid')); ?>"/>*
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">AppSecret</th>
                            <td><input type="text" name="wf_secret" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_secret')); ?>"/>*
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">商户号MCHID</th>
                            <td><input type="text" name="wf_mchid" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_mchid')); ?>"/>
                                <p style="color: #959595; display:inline">微信支付商户后台获取</p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">商户支付密钥key</th>
                            <td><input type="text" name="wf_paykey" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_paykey')); ?>"/>
                                <p style="color: #959595; display:inline">微信支付商户后台获取</p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">支付描述</th>
                            <td><input type="text" name="wf_paybody" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_paybody')); ?>"/><br/>
                                <p style="color: #959595; display:inline">* 商家名称-销售商品类目，例如：守望轩-赞赏</p>
                            </td>
                        </tr>


                        <tr valign="top">
                            <th scope="row">开启小程序的评论</th>
                            <td>
								<?php
								$wf_enable_comment_option = get_option('wf_enable_comment_option');
								$checkbox = empty($wf_enable_comment_option) ? '' : 'checked';
								echo '<input name="wf_enable_comment_option"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">小程序是否是企业主体</th>
                            <td>
								<?php
								$wf_weixin_enterprise_minapp = get_option('wf_weixin_enterprise_minapp');
								$checkbox = empty($wf_weixin_enterprise_minapp) ? '' : 'checked';
								echo '<input name="wf_weixin_enterprise_minapp"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?><p style="color: #959595; display:inline">* 如果是企业主体的小程序，请勾选</p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">开启文章列表广告</th>
                            <td>
								<?php
								$wf_list_ad = get_option('wf_list_ad');
								$checkbox = empty($wf_list_ad) ? '' : 'checked';
								echo '<input name="wf_list_ad"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                                &emsp;&emsp;&emsp;Banner广告id:&emsp;<input type="text" name="wf_list_ad_id"
                                                                          style="width:300px; height:40px"
                                                                          value="<?php echo esc_attr(get_option('wf_list_ad_id')); ?>"/>
                                <br/>&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;每<input
                                        type="number" name="wf_list_ad_every" style="width:40px; height:40px"
                                        value="<?php echo esc_attr(get_option('wf_list_ad_every')); ?>"/>条列表展示一条广告<br/>
                                <p style="color: #959595; display:inline">&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;请输入整数,否则无法正常展示广告</p>
                            </td>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">开启内容详情页广告</th>
                            <td>

								<?php
								$wf_detail_ad = get_option('wf_detail_ad');
								$checkbox = empty($wf_detail_ad) ? '' : 'checked';
								echo '<input name="wf_detail_ad"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                                &emsp;&emsp;&emsp;Banner广告id:&emsp;<input type="text" name="wf_detail_ad_id"
                                                                          style="width:300px; height:40px"
                                                                          value="<?php echo esc_attr(get_option('wf_detail_ad_id')); ?>"/>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">激励视频广告id</th>
                            <td>
                                <input type="text" name="wf_excitation_ad_id" style="width:300px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_excitation_ad_id')); ?>"/>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">视频广告id</th>
                            <td>
                                <input type="text" name="wf_video_ad_id" style="width:300px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_video_ad_id')); ?>"/>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">插屏广告id</th>
                            <td>
                                <input type="text" name="wf_interstitial_ad_id" style="width:300px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_interstitial_ad_id')); ?>"/>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">启动插屏广告的页面</th>
                            <td>
								<?php
								$enable_index_interstitial_ad = get_option('enable_index_interstitial_ad');
								$checkbox = empty($enable_index_interstitial_ad) ? '' : 'checked';
								echo '首页<input name="enable_index_interstitial_ad"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                                &emsp;
								<?php
								$enable_detail_interstitial_ad = get_option('enable_detail_interstitial_ad');
								$checkbox = empty($enable_detail_interstitial_ad) ? '' : 'checked';
								echo '文章详情页<input name="enable_detail_interstitial_ad"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>

                                &emsp;
								<?php
								$enable_topic_interstitial_ad = get_option('enable_topic_interstitial_ad');
								$checkbox = empty($enable_topic_interstitial_ad) ? '' : 'checked';
								echo '专题(分类)页<input name="enable_topic_interstitial_ad"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                                &emsp;
								<?php
								$enable_list_interstitial_ad = get_option('enable_list_interstitial_ad');
								$checkbox = empty($enable_list_interstitial_ad) ? '' : 'checked';
								echo '专题(分类)文章列表页 &emsp;<input name="enable_list_interstitial_ad"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                                &emsp;
								<?php
								$enable_hot_interstitial_ad = get_option('enable_hot_interstitial_ad');
								$checkbox = empty($enable_hot_interstitial_ad) ? '' : 'checked';
								echo '排行页<input name="enable_hot_interstitial_ad"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                                &emsp;
								<?php
								$enable_comments_interstitial_ad = get_option('enable_comments_interstitial_ad');
								$checkbox = empty($enable_comments_interstitial_ad) ? '' : 'checked';
								echo '最新评论页<input name="enable_comments_interstitial_ad"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>

                            </td>
                        </tr>

                    </table>
                </div>

                <h2>QQ小程序设置</h2>
                <div class="section">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">AppID</th>
                            <td><input type="text" name="wf_qq_appid" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_qq_appid')); ?>"/>*
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">AppSecret</th>
                            <td><input type="text" name="wf_qq_secret" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_qq_secret')); ?>"/>*
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">开启小程序的评论</th>
                            <td>
								<?php
								$wf_enable_qq_comment_option = get_option('wf_enable_qq_comment_option');
								$checkbox = empty($wf_enable_qq_comment_option) ? '' : 'checked';
								echo '<input name="wf_enable_qq_comment_option"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">小程序是否是企业主体</th>
                            <td>
								<?php
								$wf_qq_enterprise_minapp = get_option('wf_qq_enterprise_minapp');
								$checkbox = empty($wf_qq_enterprise_minapp) ? '' : 'checked';
								echo '<input name="wf_qq_enterprise_minapp"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?><p style="color: #959595; display:inline">* 如果是企业主体的小程序，请勾选</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <h2>APP设置</h2>
                <div class="section">
                    <table class="form-table">

                        <tr valign="top">
                            <th scope="row"><label for="uni_app_updated_version">APP版本名称</label></th>
                            <td><input type="text" name="uni_app_updated_version" style="width:400px; height:40px"
                                       id="uni_app_updated_version"
                                       value="<?php echo esc_attr(get_option('uni_app_updated_version')); ?>"/>
                                <p style="color: #959595; display:inline">*版本名称用于用户端显示，形式类似于"1.2.0"</p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row"><label for="uni_app_updated_version_code">APP版本号</label></th>
                            <td><input type="number" name="uni_app_updated_version_code"
                                       style="width:400px; height:40px"
                                       id="uni_app_updated_version_code"
                                       value="<?php echo esc_attr(get_option('uni_app_updated_version_code')); ?>"/>
                                <p style="color: #959595; display:inline">*版本号用于开发者区分，请使用纯数字表示，形式类似于"120"</p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row"><label for="uni_app_updated_content">APP下载链接</label></th>
                            <td><input type="text" name="uni_app_updated_content" style="width:400px; height:40px"
                                       id="uni_app_updated_content"
                                       value="<?php echo esc_attr(get_option('uni_app_updated_content')); ?>"/>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row"><label for="uni_app_updated_download_link">APP更新内容</label></th>
                            <td>
                                <textarea name="uni_app_updated_download_link" cols="60"
                                          rows="10"
                                          id="uni_app_updated_download_link"><?php echo esc_attr(get_option('uni_app_updated_download_link')); ?></textarea>
                            </td>
                        </tr>
                    </table>
                </div>

                <h2>H5设置</h2>
                <div class="section">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">开启H5的评论和发帖</th>
                            <td>
								<?php
								$uni_enable_h5_comment_option = get_option('uni_enable_h5_comment_option');
								$checkbox = empty($uni_enable_h5_comment_option) ? '' : 'checked';
								echo '<input name="uni_enable_h5_comment_option" type="checkbox"  value="true" ' . $checkbox . ' />';
								?>
                            </td>
                        </tr>
                    </table>
                </div>

                <h2>论坛设置</h2>
                <div class="section">
                    <table class="form-table">
                    </table>
                </div>
            </div>


			<?php submit_button(); ?>
        </form>
		<?php get_jquery_source(); ?>
        <script>
            jQuery(document).ready(function ($) {
                RESPONSIVEUI.responsiveTabs();
            });
        </script>
    </div>
<?php }
