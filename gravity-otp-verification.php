<?php
/*
 * Plugin Name: OTP Verification for Gravity Forms
 * Description: This plugin ensures secure form submissions by verifying users' mobile numbers via OTP before saving
 * Author: BlackSwanDev
 * Author URI: https://blackswandev.com/
 * Plugin URI: https://wordpress.org/plugins/gravity-otp-verification/
 * Contributors: amirhpcom, pigmentdev, blackswanlab
 * Version: 2.6.0
 * Tested up to: 6.7
 * Requires PHP: 7.1
 * Text Domain: gravity-otp-verification
 * Domain Path: /languages
 * Copyright: (c) BlackSwanDev.Com, All rights reserved.
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/04/30 16:31:46
*/
namespace BlackSwan\GravityOTPVerification;
defined("ABSPATH") or die("<h2>Unauthorized Access!</h2><hr><small>OTP Verification for Gravity Forms :: Developed by <a href='https://blackswandev.com/'>BlackSwanDev</a></small>");
if (!class_exists("gravity_otp")) {
  class gravity_otp {
    public $td = "gravity-otp-verification";
    public $db_slug = "gravity_otp_verification";
    public $version = "2.6.0";
    public $script_version;
    public $db_version = "2.6.0";
    public $title = "Gravity OTP";
    public $sent_ok = "Send Success";
    public $sent_nok = "Send Failed";
    public $debug = false;
    public $url;
    public $plugin_dir;
    public $plugin_basename;
    public $db_table;
    public $allowed_tables;
    public $plugin_url;
    public $assets_url;
    protected $gettext_replace;
    protected $str_replace;
    public $assets_db_url;
    public $plugin_file;
    public $last_ajax_err;
    public function __construct($full = true) {
      global $wpdb;
      $this->db_table        = $wpdb->prefix . $this->db_slug;
      $this->db_table        = $wpdb->prefix . $this->db_slug;
      $this->allowed_tables  = [$this->db_table];
      $this->plugin_file     = __FILE__;
      $this->script_version  = time();
      $this->plugin_dir      = plugin_dir_path(__FILE__);
      $this->plugin_url      = plugins_url("", __FILE__);
      $this->assets_url      = plugins_url("/assets/", __FILE__);
      $this->assets_db_url   = plugins_url("/assets-db/", __FILE__);
      $this->plugin_basename = plugin_basename(__FILE__);
      $this->url             = admin_url("admin.php?page=gravity_otp_verification#tab_general");
      $this->debug           = "yes" == $this->read("debug", "no");
      $this->gettext_replace = $this->read("gettext_replace", "");
      $this->str_replace     = $this->read("str_replace", "");
      if ($full) {
        add_action("init", array($this, "init_plugin"));
        #region string-replace and translate-replace >>>>>>>>>>>>>
        add_filter("gettext", array($this, "gettext_translate"), 999999, 3);
        add_filter("the_content", array($this, "str_replace_translate"), 999999);
        add_action("template_redirect", array($this, "buffer_start_replace_translate"));
        add_action("shutdown", array($this, "buffer_finish_replace_translate"));
        #endregion
      }
    }
    public function enqueue_scripts() {
      global $post;
      wp_enqueue_style('gf-otp-style', "{$this->assets_url}css/otp-style.css", [], $this->script_version);
      wp_enqueue_script('gf-otp-script', "{$this->assets_url}js/otp-script.js", array('jquery'), $this->script_version, true);
      wp_localize_script('gf-otp-script', 'gravity_otp_verification_vars', array(
        "page_id"          => isset($post->ID) ? $post->ID : get_queried_object_id(),
        "nonce"            => wp_create_nonce("gravity_otp_verification_nonce"),
        "ajax_url"         => admin_url("admin-ajax.php"),
        "wait"             => esc_attr__("Please wait ...", "gravity-otp-verification"),
        "send_btn"         => $this->read("send_btn", esc_attr__("Send Code", "gravity-otp-verification")),
        "resend_btn"       => $this->read("resend_btn", esc_attr__("Resend Code", "gravity-otp-verification")),
        "wait_btn"         => $this->read(
          "wait_btn",
          /* translators: 1: seconds placeholder */
          esc_attr__("Wait %d Sec.", "gravity-otp-verification")
        ),
        "err_mobile_field" => esc_attr__("Mobile field not found. Please check the Mobile Field ID setting.", "gravity-otp-verification"),
        "err_mobile_empty" => esc_attr__("Please enter a mobile number.", "gravity-otp-verification"),
      ));
    }
    public function send_otp_callback() {
      check_ajax_referer('gravity_otp_verification_nonce', 'nonce');
      global $wpdb;
      $db_id      = 0;
      $otp_digits = 5;
      if (!isset($_POST['phone']) || empty($_POST['phone'])) {
        wp_send_json_error(["message" => esc_attr__("Please enter a valid mobile number.", "gravity-otp-verification")]);
      }
      $user_id = get_current_user_id();
      $phone   = $this->sanitize_number_field(sanitize_text_field(wp_unslash($_POST['phone'])));
      if (!ctype_digit($phone)) wp_send_json_error(["message" => esc_attr__("Please enter a valid mobile number.", "gravity-otp-verification")]);
      if (!empty($this->read("mobile_regex"))) {
        $regex = preg_match($this->read("mobile_regex"), $phone);
        if (!$regex) wp_send_json_error(["message" => esc_attr__("Please enter a valid mobile number.", "gravity-otp-verification")]);
      }
      if (!ctype_digit($phone)) wp_send_json_error(["message" => esc_attr__("Please enter a valid mobile number.", "gravity-otp-verification")]);
      $form_id        = sanitize_text_field(wp_unslash(isset($_POST['form_id']) ? $_POST['form_id'] : ""));
      $field_id       = sanitize_text_field(wp_unslash(isset($_POST['field_id']) ? $_POST['field_id'] : ""));
      $page_id        = sanitize_text_field(wp_unslash(isset($_POST['page_id']) ? $_POST['page_id'] : ""));
      $attempts       = get_transient('gravity_otp_verification_attempts_' . $phone);
      $max_failed     = $this->read("max_failed", 3);
      $lockdown_delay = $this->read("lockdown_delay", 10);
      $resend_delay   = $this->read("resend_delay", 60);
      $form           = \GFAPI::get_form($form_id);
      if (!$form) wp_send_json(["message" => esc_attr__("Unknown error occured.", "gravity-otp-verification")]);
      $field = \GFFormsModel::get_field($form, $field_id);
      if (!$field) wp_send_json(["message" => esc_attr__("Unknown error occured.", "gravity-otp-verification")]);
      if ($field && isset($field->otpDigits)) $otp_digits = intval($field->otpDigits);

      // Generate a random OTP of the given length
      $min = (int) str_pad('1', $otp_digits, '0', STR_PAD_RIGHT); // e.g., 10000 for 5 digits
      $max = (int) str_pad('9', $otp_digits, '9', STR_PAD_RIGHT); // e.g., 99999 for 5 digits
      $otp = random_int($min, $max);

      if ($attempts >= $max_failed) {
        wp_send_json_error(['message' => sprintf(
          /* translators: 1: locked minutes */
          esc_attr__("Too many failed attempts. Locked for %s minutes.", "gravity-otp-verification"),
          $lockdown_delay
        )]);
      }

      set_transient('gravity_otp_verification_' . $phone, $otp, $resend_delay);
      $smsSent = $this->send_sms($phone, $otp);

      // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
      $this->debug_trace("debugging " . current_action() . ": " . __CLASS__ . "->" . __FUNCTION__ . PHP_EOL . var_export($otp, 1));
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
      $wpdb->insert($this->db_table, [
        "prefix"     => "+98",
        "otp"        => $otp,
        "mobile"     => $phone,
        "user_id"    => $user_id,
        "gf_id"      => $form_id,
        "page_id"    => $page_id,
        "user_agent" => $this->get_user_agent(),
        "ip"         => $this->get_real_IP_address(),
        "status"     => $smsSent && !is_wp_error($smsSent) && false !== $smsSent ? "sent" : "failed",
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
        "res"        => $smsSent && !is_wp_error($smsSent) && false !== $smsSent ? "send successfully" : "error_sending, " . $this->last_ajax_err . var_export($smsSent, 1),
      ]);

      if ($smsSent && !is_wp_error($smsSent) && false !== $smsSent) {
        wp_send_json_success(array(
          "message"  => sprintf($this->sent_ok, $phone),
          "timer"    => $resend_delay,
        ));
      } else {
        wp_send_json_error(array(
          "message"  => sprintf($this->sent_nok, $phone),
          "timer"    => 0,
        ));
      }
    }
    public function get_user_agent() {
      return isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : ''; // @codingStandardsIgnoreLine
    }
    public function send_sms($mobile = 0, $otp = 0, $echo = false) {
      $sms_gateway = $this->read("sms_gateway");
      $api_otp_sms = $this->read("api_otp_sms");
      $message = str_replace(["[otp]", "{otp}", "%otp%", "[OTP]", "{OTP}", "%OTP%",], [$otp, $otp, $otp, $otp, $otp, $otp], $api_otp_sms);
      // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
      $this->debug_trace("debugging " . current_action() . ": " . __CLASS__ . "->" . __FUNCTION__ . PHP_EOL . var_export(func_get_args(), 1));
      // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
      $this->debug_trace("debugging " . current_action() . ": " . __CLASS__ . "->" . __FUNCTION__ . PHP_EOL . var_export(get_defined_vars(), 1));
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
      if ($echo) { $this->debug_trace(print_r($sms_gateway, 1)); }
      switch ($sms_gateway) {
        case 'sms_ir':
          if (is_numeric(trim($message))) {
            $ParameterArray = array(array("Parameter" => "OTP", "ParameterValue" => $otp));
            return $this->ultraFastSend(array("ParameterArray" => $ParameterArray, "Mobile" => $mobile, "TemplateId" => trim($message)), $echo);
          } else {
            return $this->send_normal_sms($mobile, $message, $echo);
          }
          break;
        case 'sms_ir_v2':
          if (is_numeric(trim($message))) {
            $params = array(
              ["name" => "OTP", "value" => $otp],
              ["name" => "VerificationCode", "value" => $otp],
            );
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
            if ($echo) { $this->debug_trace(print_r([$mobile, $params], 1)); }
            return $this->ultraFastSend_v2($mobile, $params, $echo);
          } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
            if ($echo) { $this->debug_trace(print_r([$mobile, $message], 1)); }
            return $this->send_normal_sms_v2($mobile, $message, $echo);
          }
          break;
        case 'sms_faraz':
          $res = $this->send_faraz_sms($mobile, $otp, $echo);
          if (is_wp_error($res)) $this->last_ajax_err = $res->get_error_messages();
          break;

        default:
          $res = apply_filters("gravity-otp-verification/fn-send-sms/{$sms_gateway}", $mobile, $otp);
          break;
      }
      // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
      $this->debug_trace("debugging " . current_action() . ": " . __CLASS__ . "->" . __FUNCTION__ . PHP_EOL . var_export($res, 1));
      return apply_filters("gravity-otp-verification/fn-send-sms", $res, $sms_gateway, $mobile, $otp);
    }
    public function debug_trace($mix=""){
      if ($this->debug){
        echo wp_kses_data("<pre style='text-align: left; direction: ltr; border:1px solid gray; padding: 1rem; overflow: auto;'>{$mix}</pre>");
      }
    }
    #region sms_ir sms
    public function send_normal_sms($MobileNumbers = array(), $Messages = "", $echo = false) {
      $SendMessage = false;
      try {
        // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
        @$SendDateTime = date("Y-m-d") . "T" . date("H:i:s");
        $SendMessage = $this->sendMessage($MobileNumbers, $Messages, $SendDateTime, $echo);
        return $SendMessage;
      } catch (\Throwable $e) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
        error_log('Error SMS Send : ' . $e->getMessage());
      }
      return $SendMessage;
    }
    public function ultraFastSend($data, $echo = false) {
      $api_server = untrailingslashit($this->read("api_server"));
      $api_username = $this->read("api_username");
      $api_password = $this->read("api_password");
      $token = $this->_getToken($api_username, $api_password);
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
      if ($echo) { $this->debug_trace(var_export($token, 1)); }
      if ($token != false) {
        $postData = $data;
        $UltraFastSend = $this->_execute($postData, "{$api_server}/api/UltraFastSend", $token);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
        if ($echo) { $this->debug_trace(var_export($UltraFastSend, 1)); }
        if (!$UltraFastSend) return false;
        $object = json_decode($UltraFastSend);
        $result = false;
        if (is_object($object)) {
          $result = $object->Message . (isset($object->Data) ? " -- " . $object->Data : "");
        } else {
          $result = false;
        }
      } else {
        $result = false;
      }
      return $result;
    }
    public function sendMessage($MobileNumbers, $Messages, $SendDateTime = '', $echo = false) {
      $api_server = untrailingslashit($this->read("api_server"));
      $api_username = $this->read("api_username");
      $api_password = $this->read("api_password");
      $api_sender_number = $this->read("api_sender_number");
      $token = $this->_getToken($api_username, $api_password);
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
      if ($echo) { $this->debug_trace(var_export($token, 1)); }
      if ($token != false) {
        $postData = array(
          'Messages' => $Messages,
          'MobileNumbers' => $MobileNumbers,
          'LineNumber' => $api_sender_number,
          'SendDateTime' => $SendDateTime,
          'CanContinueInCaseOfError' => 'false'
        );
        $SendMessage = $this->_execute($postData, "{$api_server}/api/MessageSend", $token);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
        if ($echo) { $this->debug_trace(var_export($SendMessage, 1)); }
        if (!$SendMessage) return false;
        $object = json_decode($SendMessage);
        $result = false;
        if (is_object($object)) {
          $result = $object->Message . (isset($object->Data) ? " -- " . $object->Data : "");
        } else {
          $result = false;
        }
      } else {
        $result = false;
      }
      return $result;
    }
    private function _getToken() {
      $api_server = untrailingslashit($this->read("api_server"));
      $api_username = $this->read("api_username");
      $api_password = $this->read("api_password");
      $args = array(
        'method' => 'POST',
        'body' => array(
          'UserApiKey' => $api_username,
          'SecretKey' => $api_password,
          'System' => 'php_rest_v_2_0'
        ),
        'httpversion' => '1.0',
        'headers' => array('Content-Type: application/json',)
      );
      $result = wp_remote_retrieve_body(wp_remote_request("{$api_server}/api/Token", $args));
      $response = json_decode($result);
      $resp = false;
      $IsSuccessful = '';
      $TokenKey = '';
      if (is_object($response)) {
        $IsSuccessful = $response->IsSuccessful;
        if ($IsSuccessful == true) {
          $TokenKey = $response->TokenKey;
          $resp = $TokenKey;
        } else {
          $resp = false;
        }
      }
      return $resp;
    }
    private function _execute($postData, $url, $token) {
      $res = wp_remote_request($url, array(
        "method" => 'POST',
        "body" => $postData,
        "httpversion" => '1.0',
        "headers" => array("x-sms-ir-secure-token" => $token,)
      ));
      if (!is_wp_error($res)) {
        return wp_remote_retrieve_body($res);
      }
      return false;
    }
    # sms.ir API-v2
    public function ultraFastSend_v2($MobileNumbers, $params, $echo = false) {
      $api_server   = $this->read("api_server");
      $api_server   = untrailingslashit($api_server);
      $api_username = $this->read("api_username");
      $api_otp_sms  = $this->read("api_otp_sms");
      $args         = array(
        "method" => 'POST',
        "body" => json_encode(array(
          "mobile" => $MobileNumbers,
          "templateId" => $api_otp_sms,
          "parameters" => $params
        )),
        "httpversion" => '1.0',
        "headers"     => array(
          "Content-Type" => "application/json",
          "Accept" => "text/plain",
          "x-api-key" => $api_username,
        )
      );
      $req = wp_remote_request("{$api_server}/verify", $args);
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
      if ($echo) { $this->debug_trace(print_r(["{$api_server}/verify", $args, $req], 1)); }
      if (is_wp_error($req)) {
        return $req->get_error_messages();
      }
      return wp_remote_retrieve_body($req);
    }
    public function send_normal_sms_v2($MobileNumbers, $Messages, $echo = false) {
      $api_server        = $this->read("api_server");
      $api_server        = untrailingslashit($api_server);
      $api_username      = $this->read("api_username");
      $api_sender_number = $this->read("api_sender_number");
      $args              = array(
        "method" => 'POST',
        "body" => json_encode(array(
          "mobiles" => (array) explode(",", $MobileNumbers),
          "messageText" => $Messages,
          "lineNumber" => $api_sender_number,
          "sendDateTime" => null,
        )),
        "httpversion" => '1.0',
        "headers" => array(
          "Content-Type" => "application/json",
          "Accept" => "text/plain",
          "x-api-key" => $api_username,
        )
      );
      $req = wp_remote_request("{$api_server}/bulk", $args);
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
      if ($echo) { $this->debug_trace(print_r(["{$api_server}/bulk", $args, $req], 1)); }
      if (is_wp_error($req)) {
        return $req->get_error_messages();
      }

      return wp_remote_retrieve_body($req);
    }
    #endregion
    #region faraz sms
    public function send_faraz_sms($numbers, $otp, $echo = false) {
      $response = false;
      $username = trim($this->read("api_username"));
      $password = trim($this->read("api_password"));
      $from     = trim($this->read("api_sender_number"));
      $message  = str_replace(["[otp]", "{otp}", "%otp%", "[OTP]", "{OTP}", "%OTP%",], [$otp, $otp, $otp, $otp, $otp, $otp], trim($this->read("api_otp_sms")));
      if (empty($username) || empty($password) || empty($from)) {
        return new \WP_Error("empty-user-pass", esc_attr_x("SMS Provider credentials is not filled completely.", "sms-error", "gravity-otp-verification"));
      }
      $massage = trim(wp_strip_all_tags($message));
      if (is_array($numbers)) $to = $numbers;
      else $to = explode(',', $numbers);
      $massage = str_replace('pcode:', 'patterncode:', wp_strip_all_tags(trim($massage)));
      if (substr($massage, 0, 11) === "patterncode") {
        $massage = str_replace("\r\n", ";", $massage);
        $massage = str_replace("\n", ";", $massage);
        $splitted = explode(';', $massage);
        $patterncodeArray = explode(':', $splitted[0]);
        $patterncode = trim($patterncodeArray[1]);
        unset($splitted[0]);
        $input_data = array();
        foreach ($splitted as $parm) {
          $splitted_parm = explode(':', $parm, 2);
          $input_data[$splitted_parm[0]] = trim($splitted_parm[1]);
        }
        foreach ($to as $toNum) {
          $url = "/patterns/pattern?username=" . $username . "&password=" . urlencode($password) . "&from={$from}&to=" . json_encode(array($toNum)) . "&input_data=" . urlencode(json_encode($input_data)) . "&pattern_code=" . $patterncode;
          $result = $this->cUrl($url, array(), 'GET');
          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
          if ($echo) { $this->debug_trace(var_export([$url, $result], 1)); }
        }
        $result_array = json_decode($result);
        if (is_array($result_array) && $result_array[0] != 'sent') {
          $res_code = $result_array[0];
          $res_data = $result_array[1];
          return $this->getErrors($res_code, $result);
        }
        $response = true;
        return $response;
      } else {
        $url = "/services.jspd";
        $params = array('uname' => $username, 'pass' => $password, 'from' => $from, 'message' => $massage, 'to' => json_encode($to), 'op' => 'send');
        $result = $this->cUrl($url, $params, 'POST');
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
        if ($echo) { $this->debug_trace(var_export([$params, $result], 1)); }
        $result2 = json_decode($result);
        $res_code = $result2[0];
        $res_data = $result2[1];
        if ($res_code == '0') {
          $response = true;
        } else {
          $response = $this->getErrors($res_code, $result);
        }
        return $response;
      }
    }
    private function cUrl($url, $params = array(), $method = 'POST') {
      $domain = untrailingslashit($this->read("api_server"));
      $full_url = $domain . $url;

      $args = array(
        'timeout' => 30,
        'sslverify' => false,
      );

      if ($method === 'POST') {
        $args['body'] = $params;
      }

      $response = ($method === 'POST') ? wp_remote_post($full_url, $args) : wp_remote_get($full_url, $args);

      if (is_wp_error($response)) {
        return json_encode(array('-1', $response->get_error_message()));
      }

      return wp_remote_retrieve_body($response);
    }
    private function getErrors($error = "", $json = "") {
      $errorCodes = array(
        '-1'   => 'ارتباط با سامانه پیامک انجام نشد.',
        '0'    => 'عملیات با موفقیت انجام شده است.',
        '1'    => 'متن پیام خالی می باشد.',
        '2'    => 'کاربر محدود گردیده است.',
        '3'    => 'خط به شما تعلق ندارد.',
        '4'    => 'گیرندگان خالی است.',
        '5'    => 'اعتبار کافی نیست.',
        '7'    => 'خط مورد نظر برای ارسال انبوه مناسب نمی باشد.',
        '9'    => 'خط مورد نظر در این ساعت امکان ارسال ندارد. برای ارسال پیامک در ۲۴ ساعت شبانه روز از وب سرویس پترن استفاده نمایید',
        '98'   => 'حداکثر تعداد گیرنده رعایت نشده است.',
        '99'   => 'اپراتور خط ارسالی قطع می باشد.',
        '21'   => 'پسوند فایل صوتی نامعتبر است.',
        '22'   => 'سایز فایل صوتی نامعتبر است.',
        '23'   => 'تعداد تالش در پیام صوتی نامعتبر است.',
        '100'  => 'شماره مخاطب دفترچه تلفن نامعتبر می باشد.',
        '101'  => 'شماره مخاطب در دفترچه تلفن وجود دارد.',
        '102'  => 'شماره مخاطب با موفقیت در دفترچه تلفن ذخیره گردید.',
        '111'  => 'حداکثر تعداد گیرنده برای ارسال پیام صوتی رعایت نشده است.',
        '131'  => 'تعداد تالش در پیام صوتی باید یکبار باشد.',
        '132'  => 'آدرس فایل صوتی وارد نگردیده است.',
        '301'  => 'از حرف ویژه در نام کاربری استفاده گردیده است.',
        '302'  => 'قیمت گذاری انجام نگردیده است.',
        '303'  => 'نام کاربری وارد نگردیده است.',
        '304'  => 'نام کاربری قبال انتخاب گردیده است.',
        '305'  => 'نام کاربری وارد نگردیده است.',
        '306'  => 'کد ملی وارد نگردیده است.',
        '307'  => 'کد ملی به خطا وارد شده است.',
        '308'  => 'شماره شناسنامه نا معتبر است.',
        '309'  => 'شماره شناسنامه وارد نگردیده است.',
        '310'  => 'ایمیل کاربر وارد نگردیده است.',
        '311'  => 'شماره تلفن وارد نگردیده است.',
        '312'  => 'تلفن به درستی وارد نگردیده است.',
        '313'  => 'آدرس شما وارد نگردیده است.',
        '314'  => 'شماره موبایل را وارد نکرده اید.',
        '315'  => 'شماره موبایل به نادرستی وارد گردیده است.',
        '316'  => 'سطح دسترسی به نادرستی وارد گردیده است.',
        '317'  => 'کلمه عبور وارد نگردیده است.',
        '455'  => 'ارسال در آینده برای کد بالک ارسالی لغو شد.',
        '456'  => 'کد بالک ارسالی نامعتبر است.',
        '458'  => 'کد تیکت نامعتبر است.',
        '964'  => 'شما دسترسی نمایندگی ندارید.',
        '962'  => 'نام کاربری یا کلمه عبور نادرست می باشد.',
        '963'  => 'دسترسی نامعتبر می باشد.',
        '971'  => 'پترن ارسالی نامعتبر است.',
        '970'  => 'پارامتر های ارسالی برای پترن نامعتبر است.',
        '972'  => 'دریافت کننده برای ارسال پترن نامعتبر می باشد.',
        '992'  => 'ارسال پیام از ساعت 8 تا 23 می باشد. برای ارسال پیامک در ۲۴ ساعت شبانه روز از وب سرویس پترن استفاده نمایید',
        '993'  => 'دفترچه تلفن باید یک آرایه باشد',
        '994'  => 'لطفا تصویری از کارت بانکی خود را از منو مدارک ارسال کنید',
        '995'  => 'جهت ارسال با خطوط اشتراکی سامانه، لطفا شماره کارت بانکی خود را به دلیل تکمیل فرایند احراز هویت از بخش ارسال مدارک ثبت نمایید.',
        '996'  => 'پترن فعال نیست.',
        '997'  => 'شما اجازه ارسال از این پترن را ندارید.',
        '998'  => 'کارت ملی یا کارت بانکی شما تایید نشده است.',
        '1001' => 'فرمت نام کاربری درست نمی باشد)حداقل ۵ کاراکتر، فقط حروف و اعداد(.',
        '1002' => 'گذرواژه خیلی ساده می باشد)حداقل ۸ کاراکتر بوده و نام کاربری، ایمیل و شماره موبایل در آن وجود نداشته باشد(.',
        '1003' => 'مشکل در ثبت، با پشتیبانی تماس بگیرید.',
        '1004' => 'مشکل در ثبت، با پشتیبانی تماس بگیرید.',
        '1005' => 'مشکل در ثبت، با پشتیبانی تماس بگیرید.',
        '1006' => 'تاریخ ارسال پیام برای گذشته می باشد، لطفا تاریخ ارسال پیام را به درستی وارد نمایید.',
      );
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
      return (isset($errorCodes[$error])) ? $errorCodes[$error] : 'اشکال تعریف نشده با کد :' . var_export($error, true) . var_export($json, true);
    }
    #endregion
    #region string-replace and translate-replace >>>>>>>>>>>>>
    public function gettext_translate($translated_text, $text_to_translate, $domain) {
      try {
        $debug = json_decode($this->gettext_replace);
        if (isset($debug->gettext)) {
          foreach ($debug->gettext as $obj) {
            $original    = trim($obj->original);
            $translate   = trim($obj->translate);
            $text_domain = trim($obj->text_domain);
            if (!empty($text_domain) && $text_domain != $domain) continue;
            $use_replace = empty($obj->use_replace) ? false : true;
            // $use_regex_replace = empty($obj->use_regex) ? false : true;
            $use_origin_as_translated = empty($obj->two_sided) ? false : true;
            if ($use_replace) {
              if ($use_origin_as_translated) {
                $translated_text = str_replace($original, $translate, $translated_text);
              } else {
                if (stripos($translated_text, $original) != false) {
                  $translated_text = str_replace($original, $translate, $text_to_translate);
                }
              }
            }
            // if ($use_regex_replace) {
            //   if ($use_origin_as_translated) {
            //     // $translated_text = preg_replace($original, $translate, $translated_text);
            //   }else{
            //     // $translated_text = preg_replace($original, $translate, $text_to_translate);
            //   }
            // }
            if ($original == $text_to_translate) {
              $translated_text = $translate;
            }
            if ($use_origin_as_translated && $original == $translated_text) {
              $translated_text = $translate;
            }
          }
        }
      } catch (\Throwable $th) {
      }
      return $translated_text;
    }
    public function str_replace_translate($content) {
      try {
        $debug = json_decode($this->str_replace);
        if (isset($debug->gettext)) {
          foreach ($debug->gettext as $obj) {
            if ("yes" != $obj->active) continue;
            $original = trim($obj->original);
            $translate = trim($obj->translate);
            $buffer = $obj->buffer;
            if ($buffer != "yes") {
              $content = str_replace($original, $translate, $content);
            }
          }
        }
      } catch (\Throwable $th) {
      }
      return $content;
    }
    public function buffer_start_replace_translate() {
      ob_start(function ($content) {
        try {
          $debug = json_decode($this->str_replace);
          if (isset($debug->gettext)) {
            foreach ($debug->gettext as $obj) {
              if ("yes" != $obj->active) continue;
              $original = trim($obj->original);
              $translate = trim($obj->translate);
              $buffer = $obj->buffer;
              if ($buffer == "yes") {
                $content = str_replace($original, $translate, $content);
              }
            }
          }
        } catch (\Throwable $th) {
        }
        return $content;
      });
    }
    /**
     * Flushes all output buffers until none remain.
     *
     * This method ensures that any buffered output is sent to the browser
     * by repeatedly calling ob_end_flush() until all output buffers are cleared.
     * The @ operator suppresses warnings if no buffer exists.
     */
    public function buffer_finish_replace_translate() {
      while (@ob_end_flush());
    }
    #endregion
    public function init_plugin() {
      $this->title = esc_attr__("Gravity OTP", "gravity-otp-verification");
      /* translators: 1: given mobile number */
      $this->sent_ok = esc_attr__("OTP Code sent to mobile <strong>%s</strong> successfully.", "gravity-otp-verification");
      /* translators: 1: given mobile number */
      $this->sent_nok = esc_attr__("Could not send OTP Code to <strong>%s</strong>, Try again.", "gravity-otp-verification");
      add_action("plugin_row_meta", array($this, "plugin_row_meta"), 10, 4);
      add_filter("plugin_action_links", array($this, "plugin_action_links"), 10, 2);
      add_action("admin_menu", array($this, "admin_menu"), 1000);
      add_action("admin_init", array($this, "admin_init"));
      add_shortcode("gravity_otp_popup", array($this, "gravity_otp_popup"), 10, 2);
      add_shortcode("gravity_otp_popup_forced", array($this, "gravity_otp_popup_forced"), 10, 2);
      add_shortcode("gravity_otp_user_ip", array($this, "get_real_IP_address"));
      add_filter("gform_validation", array($this, "validate_otp_before_submit_gform"));
      add_action("gform_pre_submission", array($this, "set_cookie_after_submission"));
      add_action("wp_ajax_gravity-otp-verification", array($this, "handel_ajax_req"));
      add_action("wp_enqueue_scripts", array($this, "enqueue_scripts"));
      add_action("wp_ajax_send_otp", array($this, "send_otp_callback"));
      add_action("wp_ajax_nopriv_send_otp", array($this, "send_otp_callback"));
      add_filter("gform_confirmation", array($this, "remove_entry_param_from_redirect"), 999999, 4);
      require_once plugin_dir_path(__FILE__) . "include/backend-gf-otp.php";
      // phpcs:ignore WordPress.Security.NonceVerification.Recommended
      if (current_user_can("manage_options") && is_admin() && isset($_GET["gravity_otp_verification_send_test"], $_GET["nonce"]) && !empty($_GET["gravity_otp_verification_send_test"]) && !empty($_GET["nonce"]) ) {
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET["nonce"])), $this->td)) { return ; }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended,  WordPress.Security.ValidatedSanitizedInput.MissingUnslash
        $mobile = $this->sanitize_number_field(sanitize_text_field(wp_unslash($_GET["gravity_otp_verification_send_test"])));
        $min = (int) str_pad('1', 4, '0', STR_PAD_RIGHT); // e.g., 10000 for 5 digits
        $max = (int) str_pad('9', 4, '9', STR_PAD_RIGHT); // e.g., 99999 for 5 digits
        $otp = random_int($min, $max);
        $res = $this->send_sms($mobile, $otp, true);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
        echo "<pre style='text-align: left; direction: ltr; border:1px solid gray; padding: 1rem; overflow: auto;'>" . var_export([
          "otp"     => $otp,
          "mobile"  => $mobile,
          "sms_res" => json_decode($res) ? json_decode($res, 1) : $res,
        ], 1) . "</pre>";
        exit;
      }
    }
    public function remove_entry_param_from_redirect($confirmation, $form, $entry, $is_ajax) {
      // Only proceed if it's a redirect confirmation
      if (is_array($confirmation) && isset($confirmation['redirect'])) {
        $url = $confirmation['redirect'];

        // Parse the URL
        $parsed_url = wp_parse_url($url);
        if (isset($parsed_url['query'])) {
          parse_str($parsed_url['query'], $params);
          // Remove the 'entry' parameter if it exists
          if (isset($params['entry'])) {
            unset($params['entry']);
            // Rebuild the query string
            $new_query = http_build_query($params);
            // Construct the new URL
            $new_url = $parsed_url['path'];
            $new_url .= $new_query ? '?' . $new_query : '';
            if (isset($parsed_url['scheme']) && isset($parsed_url['host'])) {
              $new_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $new_url;
            }
            // Update the confirmation redirect
            $confirmation['redirect'] = $new_url;
          }
        } else {
          // If no query string, ensure the base URL is used
          if (isset($parsed_url['scheme']) && isset($parsed_url['host'])) {
            $confirmation['redirect'] = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
          }
        }
      }
      // Return the modified confirmation
      return $confirmation;
    }
    public function gravity_otp_popup($atts = array(), $content = "") {
      $atts = extract(shortcode_atts(array(
        "id" => "",
        "width" => "450px",
        "class" => "",
        "hide_for_logged_in" => "false",
        "hide_for_logged_out" => "false",
        "title" => "false",
        "ajax" => "true",
      ), $atts));
      global $_COOKIE;
      if (empty($id)) return;
      if (isset($_COOKIE["_gravity_otp_verification_processed_{$id}"]) && "yes" == $_COOKIE["_gravity_otp_verification_processed_{$id}"]) return;
      if (!empty($hide_for_logged_in) && "false" != $hide_for_logged_in && is_user_logged_in()) return;
      if (!empty($hide_for_logged_out) && "false" != $hide_for_logged_out && !is_user_logged_in()) return;
      ob_start();
      ?>
      <div class="gf-otp-popup <?php echo  esc_attr($class); ?>">
        <div class="bg-matte"></div>
        <div class="form" style='--gt-otp-width: <?php echo  esc_attr($width); ?>;'>
          <?php echo  do_shortcode($content); ?>
          <?php echo  do_shortcode("[gravityform id='" . esc_attr($id) . "' title='" . esc_attr($title) . "' ajax='" . esc_attr($ajax) . "']"); ?>
        </div>
      </div>
    <?php
      $htmloutput = ob_get_contents();
      ob_end_clean();
      return $htmloutput;
    }
    public function gravity_otp_popup_forced($atts = array(), $content = "") {
      $atts = extract(shortcode_atts(array(
        "id" => "",
        "width" => "450px",
        "class" => "",
        "hide_for_logged_in" => "false",
        "hide_for_logged_out" => "false"
      ), $atts));
      global $_COOKIE;
      if (empty($id)) return;
      if (isset($_COOKIE["_gravity_otp_verification_processed_{$id}"]) && "yes" == $_COOKIE["_gravity_otp_verification_processed_{$id}"]) return;
      if (!empty($hide_for_logged_in) && "false" != $hide_for_logged_in && is_user_logged_in()) return;
      if (!empty($hide_for_logged_out) && "false" != $hide_for_logged_out && !is_user_logged_in()) return;
      ob_start();
    ?>
      <div class="gf-otp-popup <?php echo  esc_attr($class); ?>">
        <div class="bg-matte"></div>
        <div class="form" style='--gt-otp-width: <?php echo  esc_attr($width); ?>;'>
          <?php echo  do_shortcode($content); ?>
        </div>
      </div>
    <?php
      $htmloutput = ob_get_contents();
      ob_end_clean();
      return $htmloutput;
    }
    public function set_cookie_after_submission($form) {
      $form_id = $form['id'];
      foreach ($form['fields'] as &$field) {
        if ($field->type === 'otp') {
          // Set the cookie name, value, and expiration time
          $expiration = time() + (86400 * $this->read("cookie_expiration", 30)); // 30 days
          setcookie("_gravity_otp_verification_processed_{$form_id}", "yes", $expiration, '/');
        }
      }
    }
    public function validate_otp_before_submit_gform($validation_result) {
      $phone_number    = "";
      $error_message   = false;
      $has_otp_field   = false;
      $is_verified     = false;
      $form            = $validation_result['form'];
      $form_id         = $form['id'];
      $current_page    = rgpost('gform_source_page_number_' . $form['id']) ? rgpost('gform_source_page_number_' . $form['id']) : 1;
      $mobile_field_id = false;
      $max_failed      = $this->read("max_failed", 3);
      $lockdown_delay  = $this->read("lockdown_delay", 10);

      foreach ($form['fields'] as &$field) {
        if ($field->type === 'otp') {
          $has_otp_field = true;
          $otp_page_number = $field->pageNumber;

          if ($current_page != $otp_page_number) {
            $field->cssClass = "hide-otp-field"; // Hide OTP if not on its page
            break;
          }

          $otp_field_id = 'input_' . $field->id;
          $otp_code = '';
          $otp_digits = !empty($field->otpDigits) ? intval($field->otpDigits) : 5;
          for ($i = 0; $i < $otp_digits; $i++) {
            $otp_code .= sanitize_text_field(rgpost($otp_field_id . "_$i"));
          }

          // Check if mobile field is attached
          $mobile_field_id = $field->mobileFieldId;
          if (empty($mobile_field_id)) {
            $validation_result['is_valid'] = false;
            $field->failed_validation = true;
            $field->validation_message = esc_attr__("No mobile field attached to OTP field.", "gravity-otp-verification");
            $field->cssClass = "hide-otp-field"; // Explicitly hide OTP field
            break;
          }

          // Get mobile number
          $mobile_input_name = 'input_' . $mobile_field_id;
          $phone_number = $this->sanitize_number_field(rgpost($mobile_input_name));

          if (empty($phone_number)) {
            $validation_result['is_valid'] = false;
            $field->failed_validation = true;
            $field->validation_message = esc_attr__("Mobile number is required.", "gravity-otp-verification");
            $field->cssClass = "hide-otp-field"; // Explicitly hide OTP field
            break;
          }

          // Validate mobile field and hide OTP if invalid
          $mobile_valid = true;
          foreach ($form['fields'] as &$mobile_field) {
            if ($mobile_field->id == $mobile_field_id) {
              // Check mobile number format (only digits, +, and - allowed)
              if (!preg_match('/^[0-9+-]+$/', $phone_number)) {
                $validation_result['is_valid'] = false;
                $mobile_field->failed_validation = true;
                $mobile_field->validation_message = esc_attr__("Mobile number can only contain numbers, + or -.", "gravity-otp-verification");
                $field->cssClass = "hide-otp-field"; // Explicitly hide OTP field
                $mobile_valid = false;
                break 2;
              }

              // Validate mobile number pattern with regex if provided
              if (!empty($this->read("mobile_regex"))) {
                if (!preg_match($this->read("mobile_regex"), $phone_number)) {
                  $validation_result['is_valid'] = false;
                  $mobile_field->failed_validation = true;
                  $mobile_field->validation_message = esc_attr__("Please enter a valid mobile number.", "gravity-otp-verification");
                  $field->cssClass = "hide-otp-field"; // Explicitly hide OTP field
                  $mobile_valid = false;
                  break 2;
                }
              }
              break;
            }
          }

          if (!$mobile_valid) {
            $field->cssClass = "hide-otp-field"; // Extra safety: ensure OTP is hidden
            break;
          }

          // OTP validation logic (only reached if mobile is valid)
          if (empty($otp_code)) {
            $validation_result['is_valid'] = false;
            $field->failed_validation = true;
            $field->validation_message = esc_attr__("Please fill in the OTP.", "gravity-otp-verification");
            break;
          }

          $stored_otp = get_transient('gravity_otp_verification_' . $phone_number);
          if (!$stored_otp) {
            // Generate a random OTP of the given length
            $min = (int) str_pad('1', $otp_digits, '0', STR_PAD_RIGHT); // e.g., 10000 for 5 digits
            $max = (int) str_pad('9', $otp_digits, '9', STR_PAD_RIGHT); // e.g., 99999 for 5 digits
            $new_otp = random_int($min, $max);
            $this->send_sms($phone_number, $new_otp);
            set_transient('gravity_otp_verification_' . $phone_number, $new_otp, 5 * MINUTE_IN_SECONDS);

            $validation_result['is_valid'] = false;
            $field->failed_validation = true;
            $field->validation_message = esc_attr__("OTP has been sent to your mobile. Please enter it.", "gravity-otp-verification");
            break;
          }

          // Verify OTP
          if ($stored_otp === $otp_code) {
            $is_verified = true;
            delete_transient('gravity_otp_verification_' . $phone_number);
            delete_transient('gravity_otp_verification_attempts_' . $phone_number);
          } else {
            $attempts = get_transient('gravity_otp_verification_attempts_' . $phone_number) ?: 0;
            $attempts++;
            set_transient('gravity_otp_verification_attempts_' . $phone_number, $attempts, $lockdown_delay * MINUTE_IN_SECONDS);

            if ($attempts >= $max_failed) {
              $error_message = sprintf(
                /* translators: 1: locked minutes */
                esc_attr__("Too many failed attempts. Locked for %s minutes.", "gravity-otp-verification"),
                $lockdown_delay
              );
            } else {
              $error_message = esc_attr__("Invalid OTP. Please try again.", "gravity-otp-verification");
            }

            $validation_result['is_valid'] = false;
            $field->failed_validation = true;
            $field->validation_message = $error_message;
            break;
          }
        }
      }

      $validation_result['form'] = $form;
      return $validation_result;
    }
    #region
    public function plugin_action_links($actions, $plugin_file) {
      if (plugin_basename(__FILE__) == $plugin_file) {
        $actions[$this->db_slug] = "<a href='{$this->url}'>" . esc_attr__("Settings", "gravity-otp-verification") . "</a>";
      }
      return $actions;
    }
    public function display_user($uid = 0, $link = false, $id = true, $raw_else = false) {
      $user_info = get_userdata($uid);
      if ($user_info) {
        if ($link) {
          return sprintf(
            "<a target='_blank' href='%s' title='%s'>%s</a>%s",
            admin_url("user-edit.php?user_id=$uid"),
            "Username: $user_info->user_login" . PHP_EOL . "Date Reg.: $user_info->user_registered" . PHP_EOL,
            (trim("$user_info->first_name $user_info->last_name") == "" ? $user_info->user_login : "$user_info->first_name $user_info->last_name"),
            ($id ? "&nbsp;&nbsp;<sup title='ID $uid'>ID: $uid</sup>" : "")
          );
        } else {
          return "$user_info->first_name $user_info->last_name";
        }
      } else {
        return $raw_else ? $uid : sprintf(
          /* translators: 1: given user id */
          esc_attr__("ID #%s [deleted-user]", "gravity-otp-verification"),
          $uid
        );
      }
    }
    public function admin_init($hook) {
      // Ensure Gravity Forms is loaded
      if (!class_exists('GFForms')) {
        add_action('admin_notices', function () {
          echo '<div class="error"><p>' . sprintf(
            /* translators: 1: plugin name */
            esc_attr__('<strong>Gravity Forms</strong> is required for the <strong>Mobile OTP Verification for Gravity Forms</strong> plugin to work. Please install and activate %s.', 'gravity-otp-verification'),
            "<a href='https://www.gravityforms.com/' target='_blank'>" . esc_attr__("Gravity Forms", "gravity-otp-verification") . "</a>"
          ) . '</p></div>';
        });
      }
      $cur_version = get_option("gravity_otp_verification_db_version", NULL);
      // Check if it's the first install or if an update is required
      if (is_null($cur_version) || version_compare($cur_version, $this->db_version, "lt")) {
        $this->create_database(true); // Create or update the database
        update_option("gravity_otp_verification_db_version", $this->db_version); // Update the stored version
      }
      foreach ($this->get_setting_options() as $sections) {
        foreach ($sections["data"] as $id => $def) {

          $slug = $this->db_slug . "__" . $id;
          $section = $this->db_slug . "__" . $sections["name"];
          add_option($slug, $def, "", "no");
          // phpcs:ignore PluginCheck.CodeAnalysis.SettingSanitization.register_settingDynamic
          register_setting(
            $section,
            $slug,
            array(
              'type' => 'string',
              'sanitize_callback' => 'sanitize_textarea_field'
            )
          );
        }
      }
    }
    public function get_setting_options() {
      return array(array(
        "name" => "general",
        "data" => array(
          # general settings
          "debug"             => "no",
          "gettext_replace"   => '{"gettext":[]}',
          "str_replace"       => '{"gettext":[]}',
          "max_failed"        => "3",
          "lockdown_delay"    => "10",
          "resend_delay"      => "60",
          "cookie_expiration" => "30",
          "mobile_regex"      => "/^(\+98|0098|98|0)?9\d{9}$/",
          "send_btn"          => "Send Code",
          "resend_btn"        => "Resend Code",
          "wait_btn"          => "Wait %d Sec.",
          # gateway setting
          "sms_gateway"        => "sms_faraz",
          "api_server"         => "sms.farazsms.com",
          "api_username"       => "",
          "api_password"       => "",
          "api_sender_number"  => "3000505",
          "api_otp_template"   => "",
          "api_otp_sms"        => "",
          "api_option_extra_1" => "",
          "api_option_extra_2" => "",
          "api_option_extra_3" => "",
          "api_option_extra_4" => "",
        )
      ));
    }
    public function admin_menu() {
      add_menu_page($this->title, $this->title, "manage_options", $this->db_slug, function () {
        include plugin_dir_path(__FILE__) . "include/backend-page-setting.php";
      });
      add_submenu_page($this->db_slug, $this->title . __("SMS Log", "gravity-otp-verification"), __("SMS Log", "gravity-otp-verification"), "manage_options", "{$this->db_slug}_log", function () {
        include plugin_dir_path(__FILE__) . "include/backend-page-log.php";
      });
    }
    public function dataTable(array $args) {
      if (!current_user_can("manage_options") || !is_blog_admin()) return;
      ob_start();
      $defaults = apply_filters(
        "gravity-otp-verification/datatables_defaults",
        array(
          "_td"                    => "gravity-otp-verification",
          "table"                  => $this->db_table,
          "default_per_page"       => 50,
          "order_by"               => "id",
          "current_page_url"       => add_query_arg([]),
          // phpcs:ignore  WordPress.Security.NonceVerification.Recommended
          "current_page_name"      => (isset($_GET["page"]) ? sanitize_text_field(wp_unslash($_GET["page"])) : "gravity_otp_verification"),
          // phpcs:ignore  WordPress.Security.NonceVerification.Recommended
          "current_section"        => (isset($_GET["section"]) ? sanitize_text_field(wp_unslash($_GET["section"])) : ""),
          // phpcs:ignore  WordPress.Security.NonceVerification.Recommended
          "current_tab"            => (isset($_GET["tab"]) ? sanitize_text_field(wp_unslash($_GET["tab"])) : ""),
          "table_headers"          => array("_sharp_id" => __('ID', "gravity-otp-verification"),),
          "table_search"           => array(),
          "table_id"               => "exported_data",
          "table_class"            => "exported_data table table-striped table-bordered table-shopping display responsive",
          "thead_class"            => "",
          "thead_class"            => "",
          "paginate_btn_class"     => "button button-secondary",
          "paginate_btn_cur_class" => "button button-secondary",
          "paginate_prev"          => __('Previous', "gravity-otp-verification"),
          "paginate_next"          => __('Next', "gravity-otp-verification"),
          "paginate_class"         => "pagination",
          "database_empty"         => "<h3 style='font-weight: bold;'>" . __("Database is empty!", "gravity-otp-verification") . "</h3><h4>" . __("It seems there's nothing to show. please check your log setting and use the app to generate some logs.", "gravity-otp-verification") . "</h4>",
          "item_val_parsing"       => function ($obj, $header_key, $item_value) {
            return esc_html($item_value);
          },
          "item_td_class"          => function ($obj, $header_key, $item_value) {
            return "";
          },
          "item_tr_class"          => function ($obj) {
            return "";
          },
          "item_tr_fn"             => function ($obj) {
            return "";
          },
        )
      );
      $arguments = wp_parse_args($args, $defaults);
      $args = extract($arguments);
      global $wpdb, $wp;
      // phpcs:ignore WordPress.Security.NonceVerification.Recommended
      $post_per_page            = isset($_GET['per_page']) ? abs((int) $_GET['per_page']) : $default_per_page;
      // phpcs:ignore WordPress.Security.NonceVerification.Recommended
      $page                     = isset($_GET['num']) ? abs((int) $_GET['num']) : 1;
      $offset                   = ($page * $post_per_page) - $post_per_page;
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
      $total                    = $wpdb->get_var("SELECT COUNT(1) FROM $table AS combined_table");
      $query                    = "SELECT * FROM $table WHERE 1=1 ";
      $query_extra              = [];
      $notice                   = "";
      $search_result_content    = "";
      $notice_pre               = "<p style='color: #0071a1;'><strong>" . __("Active Filters:", "gravity-otp-verification") . '</strong>';
      $notice_post              = "</p>";
      // phpcs:ignore WordPress.Security.NonceVerification.Recommended
      $query_related            = (isset($_GET["relation"]) && "AND" == $_GET["relation"]) ? true : false;
      // phpcs:ignore WordPress.Security.NonceVerification.Recommended
      $query_exact              = (isset($_GET["exact"]) && "yes" == $_GET["exact"]) ? true : false;
      $table_headers_srch       = $table_headers;
      $table_headers_srch["id"] = "ID";
      foreach ($table_headers_srch as $header => $header_value) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET[$header]) && !empty($_GET[$header])) {
          // phpcs:ignore WordPress.Security.NonceVerification.Recommended
          $value = sanitize_text_field(wp_unslash($_GET[$header]));
          if ("user_id" == $header) {
            // phpcs:ignore  WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $query_extra[] = $wpdb->prepare("`$header` = %s", "$value");
          } elseif ("_sharp_id" == $header || "ID" == $header || "id" == $header) {
            // phpcs:ignore  WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $query_extra[] = $wpdb->prepare("`id` = %d", $value);
          } else {
            $value_filter = $query_exact ? $value : "%$value%";
            // phpcs:ignore  WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $query_extra[] = $wpdb->prepare("`$header` LIKE %s", $value_filter);
          }
          // phpcs:ignore WordPress.Security.NonceVerification.Missing
          $url = esc_url(
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            remove_query_arg(
              $header,
              // phpcs:ignore WordPress.Security.NonceVerification.Missing
              add_query_arg($wp->query_vars, home_url($wp->request))
            )
          );
          $notice .= "<br><a href='" . esc_attr($url) . "'><i class='fas fa-times-circle'></i></a> " . esc_attr($header_value) . ": " . esc_attr($value);
        }
      }
      $notice = !empty($notice) ? $notice_pre . $notice . $notice_post : $notice;
      if (!empty($query_extra)) {
        $query .= " AND " . ($query_related ? implode(" AND ", $query_extra) : implode(" OR ", $query_extra));
      }
      $query .= " ORDER BY `$order_by` DESC LIMIT {$offset}, {$post_per_page}";
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
      $res_obj = $wpdb->get_results($query);

      wp_enqueue_script("gravity_otp_verification_jq_confirm" , "{$this->assets_db_url}js/jquery-confirm.js", array("jquery"), "3.3.4", false);
      wp_enqueue_style("gravity_otp_verification_jq_confirm"  , "{$this->assets_db_url}css/jquery-confirm.css", array(), "3.3.4", false);
      wp_enqueue_style("gravity_otp_verification_font_awesome", "{$this->assets_url}fa/css/all.min.css", array(), "6.7.2");
      wp_enqueue_script("gravity_otp_verification_dt_backend" , "{$this->assets_db_url}/js/backend-data-tables.js", array('jquery'), "2.4.0", true);
      wp_enqueue_style("gravity_otp_verification_datatable"   , "{$this->assets_db_url}css/datatables.min.css", array(), "2.0.1");
      wp_enqueue_script("gravity_otp_verification_datatable"  , "{$this->assets_db_url}js/datatables.min.js", array("jquery"), "2.0.1", true);
      wp_enqueue_script("gravity_otp_verification_SrchHighlt" , "{$this->assets_db_url}js/dataTables.searchHighlight.min.js", array("jquery"), "1.0.1", true);
      wp_enqueue_script("gravity_otp_verification_highlight"  , "{$this->assets_db_url}js/highlight.js", array("jquery"), "3.0.0", true);
      wp_enqueue_script("gravity_otp_verification_popper"     , "{$this->assets_db_url}js/popper.min.js", array("jquery"), "2.11.8", true);
      wp_enqueue_script("gravity_otp_verification_tippy"      , "{$this->assets_db_url}js/tippy-bundle.umd.min.js", array("jquery"), "6.3.7", true);
      wp_enqueue_style("gravity_otp_verification_table_style" , "{$this->assets_db_url}css/table-style.css", array(), "2.4.0", "all");
      wp_localize_script("gravity_otp_verification_dt_backend", "_i18n", $this->localize_script_data_table(['_td' => $_td, 'table' => $table]));
      wp_enqueue_style("gravity_otp_verification_backend", "{$this->assets_db_url}css/backend.css", array(), "2.4.0");
      is_rtl() and wp_add_inline_style("gravity_otp_verification_backend", "#wpbody-content { font-family: bodyfont, roboto, Tahoma; }");

      $items_per_page_selector = "<select id='itemsperpagedisplay' name='per_page' class='select' style='width:320px !important; margin: 0 0 .5rem .5rem; float: right;' title='" . esc_attr(esc_html__("Items per page", "gravity-otp-verification")) . "' >
      <option value='50' " . selected(100, $post_per_page, false) . ">" . sprintf(
        /* translators: 1: number */
        esc_attr_x("Show %s items per page", "items_per_page", "gravity-otp-verification"),
        50
      ) . "</option>
      <option value='100' " . selected(100, $post_per_page, false) . ">" . sprintf(
        /* translators: 1: number */
        esc_attr_x("Show %s items per page", "items_per_page", "gravity-otp-verification"),
        100
      ) . "</option>
      <option value='200' " . selected(200, $post_per_page, false) . ">" . sprintf(
        /* translators: 1: number */
        esc_attr_x("Show %s items per page", "items_per_page", "gravity-otp-verification"),
        200
      ) . "</option>
      <option value='300' " . selected(300, $post_per_page, false) . ">" . sprintf(
        /* translators: 1: number */
        esc_attr_x("Show %s items per page", "items_per_page", "gravity-otp-verification"),
        300
      ) . "</option>
      <option value='400' " . selected(500, $post_per_page, false) . ">" . sprintf(
        /* translators: 1: number */
        esc_attr_x("Show %s items per page", "items_per_page", "gravity-otp-verification"),
        400
      ) . "</option>
      <option value='500' " . selected(500, $post_per_page, false) . ">" . sprintf(
        /* translators: 1: number */
        esc_attr_x("Show %s items per page", "items_per_page", "gravity-otp-verification"),
        500
      ) . "</option>
      <option value='600' " . selected(500, $post_per_page, false) . ">" . sprintf(
        /* translators: 1: number */
        esc_attr_x("Show %s items per page", "items_per_page", "gravity-otp-verification"),
        600
      ) . "</option>
      <option value='700' " . selected(500, $post_per_page, false) . ">" . sprintf(
        /* translators: 1: number */
        esc_attr_x("Show %s items per page", "items_per_page", "gravity-otp-verification"),
        700
      ) . "</option>
      <option value='800' " . selected(500, $post_per_page, false) . ">" . sprintf(
        /* translators: 1: number */
        esc_attr_x("Show %s items per page", "items_per_page", "gravity-otp-verification"),
        800
      ) . "</option>
      <option value='900' " . selected(500, $post_per_page, false) . ">" . sprintf(
        /* translators: 1: number */
        esc_attr_x("Show %s items per page", "items_per_page", "gravity-otp-verification"),
        900
      ) . "</option>
      <option value='1000' " . selected(1000, $post_per_page, false) . ">" . sprintf(
        /* translators: 1: number */
        esc_attr_x("Show %s items per page", "items_per_page", "gravity-otp-verification"),
        1000
      ) . "</option>
      <option disabled>-----------------</option>
      <option value='$total' " . selected($total, $post_per_page, false) . ">" . ($total > 1 ? sprintf(
        /* translators: 1: all items */
        __("Show all %s items at once", "gravity-otp-verification"),
        $total
      ) : __("Show the only found data", "gravity-otp-verification")) . "</option>
      </select>";

      echo "<input type='hidden' id='search_result_content_empty' value='".(!empty($notice)?"yes":"no")."' />";
      if (!empty($notice)) {
        $search_result_content = sprintf(
          /* translators: 1: total number */
          _n(", Search result: %d", ", Search results: %d", $total, "gravity-otp-verification"),
          $wpdb->num_rows
        );
      }
      echo "<div id=\"search_form\" class=\"search_form closed\">";
      // phpcs:ignore  WordPress.Security.NonceVerification.Recommended
      $query_related = (isset($_GET["relation"]) && "AND" == $_GET["relation"]) ? true : false;
      // phpcs:ignore  WordPress.Security.NonceVerification.Recommended
      $query_exact   = (isset($_GET["exact"]) && "yes" == $_GET["exact"]) ? true : false;
      $srch          = "";
      $filter_row_wo = "";

      echo "<p><strong>" . esc_attr__("Advanced Search in Database", "gravity-otp-verification") . "</strong></p>";
      if (!empty($table_search)) {
        echo "<form method='GET'>
          <input type='hidden' name='page' value='" . (
          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.Security.NonceVerification.Recommended
          isset($_GET['page']) ? esc_attr(sanitize_text_field(wp_unslash($_GET['page']))) : "wc-settings") . "' />
          ";
        echo "<div class='form-filters'>";
        foreach ($table_search as $key => $value) {
          echo "<div class=\"form-row search_" . esc_attr($key) . "\">
            <div class=\"form-label\"><label for=\"search_" . esc_attr($key) . "\">" . esc_attr($value) . "</label></div>
            <div class=\"form-input\"><input type=\"text\" id=\"search_" . esc_attr($key) . "\" required name='" . esc_attr($key) . "' value=\"" . (
            // phpcs:ignore WordPress.Security.NonceVerification.Missing,  WordPress.Security.NonceVerification.Recommended
            isset($_GET[$key]) && !empty($_GET[$key]) ?
            // phpcs:ignore WordPress.Security.NonceVerification.Missing,  WordPress.Security.NonceVerification.Recommended
            esc_attr(sanitize_text_field(wp_unslash($_GET[$key]))) : "") . "\" /></div>
            </div>";
        }
        echo "</div>";
        $filter_row_wo = '
          <p><label><strong>' . esc_attr__("Exact Match: ", "gravity-otp-verification") . '</strong>Off&nbsp;<input type="checkbox" id="mode_exact" required name="exact" ' . checked($query_exact, true, false) . ' value="yes" />&nbsp;On</label>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
          <label><strong>' . esc_attr__("Query Relation: ", "gravity-otp-verification") . '</strong>OR&nbsp;<input type="checkbox" id="mode_relation" required name="relation" ' . checked($query_related, true, false) . ' value="AND" />&nbsp;AND</label></p>';
        $srch = "<a href='#submit_form_search' class='dt-button'>" . esc_attr__("Search now", "gravity-otp-verification") . "</a>";
      }
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo $filter_row_wo . "<div class=\"form-row finalrow dt-buttons\">";
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo $srch;
      echo "<a href='#close_form_search' class='dt-button'>" . esc_attr__("Close search", "gravity-otp-verification") . "</a>
      <a href='" . esc_attr($current_page_url) . "' class='dt-button'>" . esc_attr__("Clear search", "gravity-otp-verification") . "</a></div></form></div>
      "; ?>
      <div class="data-table-wrap">
        <form action="<?php echo  esc_attr($current_page_url); ?>" id='mainform'>
          <input type="hidden" name="page" value="<?php echo  esc_attr($current_page_name); ?>" />
          <!-- <input type='hidden' name="tab" value="<?php echo  esc_attr($current_tab); ?>" /> -->
          <!-- <input type="hidden" name="section" value="<?php echo  esc_attr($current_section); ?>" /> -->
          <input type="hidden" name="num" value="<?php echo  esc_attr($page); ?>" />
          <?php
          if (!empty($wpdb->num_rows)) {
            $header = array_unique($table_headers);
            echo "<p class='numeral-details'><b>" . sprintf(
              /* translators: 1: given number */
              esc_attr(_n("Only %s Record indexed", "%s Records indexed", esc_attr($total), "gravity-otp-verification")),
              esc_attr($total)
            ) .
              // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
              $search_result_content . "</b>" . $items_per_page_selector . "</p>";
            do_action("gravity-otp-verification/datatables_before_table", $arguments);
            echo "<table border='1' id='" . esc_attr($table_id) . "' class='" . esc_attr($table_class) . " nowrap' style='width:100%'>";
            $headings_row = "<tr>";
            foreach ($header as $key => $value) {
              $extraClass = "";
              if ($this->str_starts_with($key, "_") || in_array($key, apply_filters("gravity-otp-verification/datatables_no_export_columns", array("_sharp_id", "_action")))) {
                $extraClass = "noExport";
              }
              $headings_row .= "<th class='item_th_{$key} $extraClass'>{$value}</th>";
            }
            $headings_row .= "</tr>";
            echo "<thead class='" . esc_attr($thead_class) . "'>" . esc_attr($headings_row) . "</thead><tbody>";
            foreach ($res_obj as $obj) {
              $item_tr_classes = call_user_func($item_tr_class, $obj);
              $item_tr_fns = call_user_func($item_tr_fn, $obj);
              echo "<tr class='" . esc_attr("item_tr_{$obj->id} $item_tr_classes") . "' " . esc_attr($item_tr_fns) . ">";
              foreach ($header as $header_id => $header_name) {
                $val = call_user_func($item_val_parsing, $obj, $header_id, $header_name);
                $item_td_classes = call_user_func($item_td_class, $obj, $header_id, $header_name);
                echo "<td class='" . esc_attr("item_td_{$header_id} $item_td_classes") . "'>" . esc_attr($val) . "</td>";
              }
              echo "<template id='" . esc_attr("item_tr_{$obj->id}") . "'>
                          <div class='log5'>" . highlight_string("<?php" . PHP_EOL .
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
                print_r($obj, true), 1) . "</div></template>";
              echo "</tr>";
            }
            echo "</tbody><tfoot class='" . esc_attr($thead_class) . "'>" . esc_attr($headings_row) . "</tfoot></table>";
            do_action("gravity-otp-verification/datatables_after_table", $arguments);
            do_action("gravity-otp-verification/datatables_before_paginate", $arguments);
            echo '<div class="' . esc_attr($paginate_class) . '" style="margin-top: 1.5rem;display: block;">';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo paginate_links(array(
              'base'               => add_query_arg('num', '%#%'),
              'format'             => '',
              'show_all'           => false,
              'mid_size'           => 2,
              'end_size'           => 2,
              'prev_text'          => "<span class='" . esc_attr($paginate_btn_class) . "'>" . esc_attr($paginate_prev) . "</span>",
              'next_text'          => "<span class='" . esc_attr($paginate_btn_class) . "'>" . esc_attr($paginate_next) . "</span>",
              'total'              => ceil($total / $post_per_page),
              'current'            => $page,
              'before_page_number' => "<span class='" . esc_attr($paginate_btn_cur_class) . "'>",
              'after_page_number'  => "</span>",
              'type'               => 'list'
            ));
            echo "</div>";
            do_action("gravity-otp-verification/datatables_after_paginate", $arguments);
          } else {
            do_action("gravity-otp-verification/datatables_before_empty_table", $arguments);
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $database_empty;
            do_action("gravity-otp-verification/datatables_after_empty_table", $arguments);
          }
          ?>
        </form>
      </div>
      <?php
      $return_html_data = ob_get_contents();
      ob_end_clean();
      return apply_filters("gravity-otp-verification/datatables_return", $return_html_data, $return_html_data, $arguments);
    }
    public function localize_script_data_table($args = array()) {
      $currentTimestamp = current_time("timestamp");
      $currentDate      = date_i18n(get_option('date_format'), $currentTimestamp);
      $currentTime      = date_i18n(get_option('time_format'), $currentTimestamp);
      $defaults         = array(
        "ajax"               => admin_url("admin-ajax.php"),
        "home"               => home_url(),
        "table"              => "",
        "td"                 => "gravity-otp-verification",
        "wparam"             => $this->db_slug,
        "nonce"              => wp_create_nonce($this->db_slug),
        "title"              => esc_attr_x("Select image file", "translate-js", "gravity-otp-verification"),
        "btntext"            => esc_attr_x("Use this image", "translate-js", "gravity-otp-verification"),
        "clear"              => esc_attr_x("Clear", "translate-js", "gravity-otp-verification"),
        "currentlogo"        => esc_attr_x("Current preview", "translate-js", "gravity-otp-verification"),
        "selectbtn"          => esc_attr_x("Select image", "translate-js", "gravity-otp-verification"),
        "tr_submit"          => esc_attr_x("Submit", "js-string", "gravity-otp-verification"),
        "tr_today"           => esc_attr_x("Today", "js-string", "gravity-otp-verification"),
        "errorTxt"           => esc_attr_x("Error", "translate-js", "gravity-otp-verification"),
        "cancelTtl"          => esc_attr_x("Canceled", "translate-js", "gravity-otp-verification"),
        "confirmTxt"         => esc_attr_x("Confirm", "translate-js", "gravity-otp-verification"),
        "wait"               => esc_attr_x("Please wait ...", "translate-js", "gravity-otp-verification"),
        "set_pending"        => esc_attr_x("Set to Pending", "translate-js", "gravity-otp-verification"),
        "set_approved"       => esc_attr_x("Set to Approved", "translate-js", "gravity-otp-verification"),
        "set_rejected"       => esc_attr_x("Set to Rejected", "translate-js", "gravity-otp-verification"),
        "successTtl"         => esc_attr_x("Success", "translate-js", "gravity-otp-verification"),
        "submitTxt"          => esc_attr_x("Submit", "translate-js", "gravity-otp-verification"),
        "okTxt"              => esc_attr_x("Okay", "translate-js", "gravity-otp-verification"),
        "txtYes"             => esc_attr_x("Yes", "translate-js", "gravity-otp-verification"),
        "txtNop"             => esc_attr_x("No", "translate-js", "gravity-otp-verification"),
        "cancelbTn"          => esc_attr_x("Cancel", "translate-js", "gravity-otp-verification"),
        "copied"             => esc_attr_x("Successfully copied to clipboard!", "translate-js", "gravity-otp-verification"),
        "nosel"              => esc_attr_x("No Selection!", "translate-js", "gravity-otp-verification"),
        "cancelbTn"          => esc_attr_x("Cancel", "translate-js", "gravity-otp-verification"),
        "edit"               => esc_attr_x("Edit Entry", "translate-js", "gravity-otp-verification"),
        "addnew"             => esc_attr_x("Add New Entry", "translate-js", "gravity-otp-verification"),
        "sendTxt"            => esc_attr_x("Send to all", "translate-js", "gravity-otp-verification"),
        "closeTxt"           => esc_attr_x("Close", "translate-js", "gravity-otp-verification"),
        "reloadTxt"          => esc_attr_x("Reload page", "translate-js", "gravity-otp-verification"),
        "deleteConfirmTitle" => esc_attr_x("Delete Confirmation", "translate-js", "gravity-otp-verification"),
        "UnknownErr"         => esc_attr_x("Unfortunately we encountered a technical problem, for more information check console", "translate-js", "gravity-otp-verification"),
        "deleteConfirmation" =>
        /* translators: 1: item id */
        esc_attr_x("Are you sure you want to delete items <u><strong>ID #%s</strong></u> ? <red>THIS CANNOT BE UNDONE.", "translate-js", "gravity-otp-verification"),
        "clearDBConfirmation" => esc_attr_x("Are you sure you want to clear all data from database? <red>THIS CANNOT BE UNDONE.", "translate-js", "gravity-otp-verification"),
        "clearResetSettiConfrm" => esc_attr_x("Are you sure you want to clear all saved settings and revert to default? <red>THIS CANNOT BE UNDONE.", "translate-js", "gravity-otp-verification"),
        "clearDBConfTitle" => esc_attr_x("Clear Database", "translate-js", "gravity-otp-verification"),
        "clearResetSettings" => esc_attr_x("Reset Settings", "translate-js", "gravity-otp-verification"),
        "fixDBConfTitle" => esc_attr_x("Fix & Re-Create Database", "translate-js", "gravity-otp-verification"),
        "fixDBConfirmation" => esc_attr_x("Are you sure you want to re-create and fix database?", "translate-js", "gravity-otp-verification"),
        "attach" => esc_attr_x("Select / Upload image", "translate-js", "gravity-otp-verification"),
        "str1" => sprintf(
          /* translators: 1: plugin name */
          esc_attr_x("Exported via %s", "wc-setting-js", "gravity-otp-verification"),
          $this->title
        ),
        "str2" => $this->title,
        "str3" => sprintf(
          /* translators: 1: date / 2: time */
          /* translators: 1: User name, 2: Website name */
          esc_attr_x('Exported at %1$s @ %2$s', "wc-setting-js", "gravity-otp-verification"),
          $currentDate,
          $currentTime
        ),
        "str4" => sanitize_file_name($this->title . "-export-" . date_i18n("YmdHis", current_time("timestamp"))),
        "str5" => sprintf(
          /* translators: 1: title, 2: date, 3: time */
          esc_attr_x('Exported via %1$s — Export Date: %2$s @ %3$s — Developed by Amirhp.com', "wc-setting-js", "gravity-otp-verification"),
          $this->title,
          $currentDate,
          $currentTime
        ),
        "str6"         => $this->title,
        "tbl1"         => esc_attr_x("No data available in table", "data-table", "gravity-otp-verification"),
        "tbl2"         => esc_attr_x("Showing _START_ to _END_ of _TOTAL_ entries", "data-table", "gravity-otp-verification"),
        "tbl3"         => esc_attr_x("Showing 0 to 0 of 0 entries", "data-table", "gravity-otp-verification"),
        "tbl4"         => esc_attr_x("(filtered from _MAX_ total entries)", "data-table", "gravity-otp-verification"),
        "tbl5"         => esc_attr_x("Show _MENU_ entries", "data-table", "gravity-otp-verification"),
        "tbl6"         => esc_attr_x("Loading...", "data-table", "gravity-otp-verification"),
        "tbl7"         => esc_attr_x("Processing...", "data-table", "gravity-otp-verification"),
        "tbl8"         => esc_attr_x("Search:", "data-table", "gravity-otp-verification"),
        "tbl9"         => esc_attr_x("No matching records found", "data-table", "gravity-otp-verification"),
        "tbl10"        => esc_attr_x("First", "data-table", "gravity-otp-verification"),
        "tbl11"        => esc_attr_x("Last", "data-table", "gravity-otp-verification"),
        "tbl12"        => esc_attr_x("Next", "data-table", "gravity-otp-verification"),
        "tbl13"        => esc_attr_x("Previous", "data-table", "gravity-otp-verification"),
        "tbl14"        => esc_attr_x(": activate to sort column ascending", "data-table", "gravity-otp-verification"),
        "tbl15"        => esc_attr_x(": activate to sort column descending", "data-table", "gravity-otp-verification"),
        "tbl16"        => esc_attr_x("Copy to clipboard", "data-table", "gravity-otp-verification"),
        "tbl17"        => esc_attr_x("Print", "data-table", "gravity-otp-verification"),
        "tbl177"       => esc_attr_x("Column visibility", "data-table", "gravity-otp-verification"),
        "tbl18"        => esc_attr_x("Export CSV", "data-table", "gravity-otp-verification"),
        "tbl19"        => esc_attr_x("Export Excel", "data-table", "gravity-otp-verification"),
        "tbl20"        => esc_attr_x("Export PDF", "data-table", "gravity-otp-verification"),
        "nostatefound" => esc_attr_x("No State Found", "data-table", "gravity-otp-verification"),
        "addnew"       => esc_attr_x("Add New Entry", "data-table", "gravity-otp-verification"),
      );
      $arguments = wp_parse_args($args, $defaults);
      return $arguments;
    }
    public function get_real_IP_address() {
      // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
      if (!empty($_SERVER["GEOIP_ADDR"])) {
        $ip = sanitize_text_field(stripslashes_deep($_SERVER["GEOIP_ADDR"]));
      } elseif (!empty($_SERVER["HTTP_X_REAL_IP"])) {
        $ip = sanitize_text_field(stripslashes_deep($_SERVER["HTTP_X_REAL_IP"]));
      } elseif (!empty($_SERVER["HTTP_CLIENT_IP"])) {
        $ip = sanitize_text_field(stripslashes_deep($_SERVER["HTTP_CLIENT_IP"]));
      } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $ip = sanitize_text_field(stripslashes_deep($_SERVER["HTTP_X_FORWARDED_FOR"]));
      } else {
        $ip = isset($_SERVER["REMOTE_ADDR"]) ? sanitize_text_field(stripslashes_deep($_SERVER["REMOTE_ADDR"])) : "";
      }
      return esc_html($ip);
    }
    public function str_starts_with($whole_word, $character) {
      return strpos($whole_word, $character) === 0;
    }
    /**
     * sanitize and convert numeric input
     *
     * @param  string $string
     * @return int
     */
    public function sanitize_number_field($string) {
      $string = sanitize_text_field($string);
      $newNumbers = range(0, 9);
      // 1. Persian HTML decimal
      $persianDecimal = array('&#1776;', '&#1777;', '&#1778;', '&#1779;', '&#1780;', '&#1781;', '&#1782;', '&#1783;', '&#1784;', '&#1785;');
      // 2. Arabic HTML decimal
      $arabicDecimal = array('&#1632;', '&#1633;', '&#1634;', '&#1635;', '&#1636;', '&#1637;', '&#1638;', '&#1639;', '&#1640;', '&#1641;');
      // 3. Arabic Numeric
      $arabic = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');
      // 4. Persian Numeric
      $persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
      $string  = str_replace($persianDecimal, $newNumbers, $string);
      $string  = str_replace($arabicDecimal, $newNumbers, $string);
      $string  = str_replace($arabic, $newNumbers, $string);
      return str_replace($persian, $newNumbers, $string);
    }
    public static function activation_hook() {
      (new Gravity_OTP)->create_database(1);
    }
    public function create_database($force = false) {
      global $wpdb;
      if (!function_exists('dbDelta')) include_once ABSPATH . 'wp-admin/includes/upgrade.php';
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
      $charset_collate = $wpdb->get_charset_collate();
      $table_name = esc_sql($this->db_table);
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange,  WordPress.DB.PreparedSQL.InterpolatedNotPrepared
      if (false !== $force || $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
        dbDelta("CREATE TABLE `$table_name` (
        `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `date_created` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `date_modified` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        `user_id` BIGINT(20) UNSIGNED,
        `edited_by` BIGINT(20) UNSIGNED,
        `ip` VARCHAR(16),
        `prefix` VARCHAR(255),
        `mobile` VARCHAR(255),
        `otp` VARCHAR(255),
        `gf_id` VARCHAR(255),
        `page_id` VARCHAR(255),
        `user_agent` VARCHAR(255),
        `status` VARCHAR(255),
        `res` TEXT,
        PRIMARY KEY (`id`) ) $charset_collate;");
      }
    }
    public function plugin_row_meta($links_array, $plugin_file_name, $plugin_data, $status) {
      if (strpos($plugin_file_name, basename(__FILE__))) {
        $links_array[] = "<a href='mailto:support@blackswandev.com?subject=GravityOTP'>" . esc_attr__("Support", "gravity-otp-verification") . "</a>";
      }
      return $links_array;
    }
    /**
     * handle backend ajax request
     *
     * @return mixed
     */
    public function handel_ajax_req() {
      check_ajax_referer($this->db_slug, "nonce");
      if (wp_doing_ajax() && isset($_POST["action"]) && sanitize_text_field(wp_unslash($_POST["action"])) == "gravity-otp-verification") {
        if (isset($_POST["wparam"]) && sanitize_text_field(wp_unslash($_POST["wparam"])) === $this->db_slug) {
          global $wpdb;
          $lparam = isset($_POST["lparam"]) ? sanitize_text_field(wp_unslash($_POST["lparam"])) : "unknown";
          switch ($lparam) {
            // edit database entry
            case "edit_entry":
              $id = isset($_POST["id"]) ? sanitize_text_field(wp_unslash($_POST["id"])) : false;

              if (!isset($_POST["table"])) wp_send_json_error(array("msg" => esc_attr__("An unknown error occured.", "gravity-otp-verification"), "err" => "no_table_set",));
              $table = sanitize_text_field(wp_unslash($_POST["table"]));
              if (!in_array($table, $this->allowed_tables)) wp_send_json_error(array("msg" => esc_attr__("An unknown error occured.", "gravity-otp-verification"), "err" => "table_not_allowed"));

              // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
              $formData = $_POST["formData"];
              $edit_array = ["edited_by" => get_current_user_id(),];

              if ($id != false && $id > 0) {
                if (is_array($formData)) {
                  // if $formData is Array, then lets sanitize it
                  foreach ($formData as $item) {
                    $key = sanitize_text_field(wp_unslash($item["name"]));
                    // if invalid item found, skip it ;)
                    if (!in_array($key, ["id", "date_created", "date_modified", "user_id", "edited_by", "ip", "prefix", "mobile", "otp", "gf_id", "page_id", "user_agent", "status", "res",])) continue;
                    // double-sanitize and kses again
                    $edit_array[$key] = trim(wp_kses_post(wp_unslash($item["value"])));
                  }
                }else{
                  // we do not use $formData if its not an Array
                }
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $res = $wpdb->update($table, $edit_array, ["id" => $id], "%s", "%d");
                if (false !== $res) wp_send_json_success(["edits" => $edit_array, "msg" => esc_attr__("Entry updated successfully. Please reload the page to view the changes.", "gravity-otp-verification")]);
                wp_send_json_error(array("msg" => esc_attr__("An unknown error occurred during editing item.", "gravity-otp-verification"), "err" => "could_not_update",));
              }

              wp_send_json_error(array("msg" => esc_attr__("An unknown error occurred.", "gravity-otp-verification"), "err" => "end_of_loop",));
              break;
            // database remove item
            case "delete_item":
              if (!isset($_POST["dparam"]) || empty($_POST["dparam"]) || !is_numeric($_POST["dparam"])) {
                wp_send_json_error(array("msg" => esc_attr__("An unknown error occured.", "gravity-otp-verification"),));
              }

              if (!isset($_POST["table"])) wp_send_json_error(array("msg" => esc_attr__("An unknown error occured.", "gravity-otp-verification"),));
              $table = sanitize_text_field(wp_unslash($_POST["table"]));
              if (!in_array($table, $this->allowed_tables)) wp_send_json_error(array("msg" => esc_attr__("An unknown error occured.", "gravity-otp-verification"),));

              // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
              $del4db = $wpdb->delete($table, array('ID' => sanitize_text_field(wp_unslash($_POST["dparam"]))), array('%d'));
              if (false !== $del4db) {
                wp_send_json_success(array("msg" => esc_attr__("Item successfully deleted", "gravity-otp-verification"),));
              } else {
                wp_send_json_error(array("msg" => esc_attr__("Could not remove item from database.", "gravity-otp-verification"),));
              }
              break;
            // database remove multiple items
            case "delete_item_array":

              if (!isset($_POST["table"])) wp_send_json_error(array("msg" => esc_attr__("An unknown error occured.", "gravity-otp-verification"),));
              $table = sanitize_text_field(wp_unslash($_POST["table"]));
              if (!in_array($table, $this->allowed_tables)) wp_send_json_error(array("msg" => esc_attr__("An unknown error occured.", "gravity-otp-verification"),));


              (array) $ids = (isset($_POST["dparam"]) && !empty($_POST["dparam"]) ? sanitize_text_field(wp_unslash($_POST["dparam"])) : array());
              $ids = implode(',', array_map('absint', $ids));
              // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching,  WordPress.DB.PreparedSQL.InterpolatedNotPrepared
              $res = $wpdb->query("DELETE FROM $table WHERE ID IN($ids)");
              if ($res != false) {
                wp_send_json_success(array("msg" => esc_attr__("Items removed successfully.", "gravity-otp-verification"),));
              } else {
                wp_send_json_error(array("msg" => esc_attr__("There was a problem with your request.", "gravity-otp-verification"),));
              }
              break;
            // database recreate
            case "db_recreate":
              $this->create_database(true);
              wp_send_json_success(array("msg" => esc_attr__("Database successfully re-created and got fixed.", "gravity-otp-verification")));
              break;
            // database truncate
            case "clear_db":
            case "db_empty":
              global $wpdb;
              if (!isset($_POST["table"])) wp_send_json_error(array("msg" => esc_attr__("An unknown error occured.", "gravity-otp-verification"),));
              $table = sanitize_text_field(wp_unslash($_POST["table"]));
              if (!in_array($table, $this->allowed_tables)) wp_send_json_error(array("msg" => esc_attr__("An unknown error occured.", "gravity-otp-verification"),));
              // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
              $del = $wpdb->query("TRUNCATE TABLE `$table`");

              if (false !== $del) {
                wp_send_json_success(array("table" => $table, "msg" => esc_attr__("Database cleared successfully.", "gravity-otp-verification")));
              } else {
                wp_send_json_error(array("table" => $table, "msg" => esc_attr__("Error clearing database!", "gravity-otp-verification")));
              }
              break;
            default:
              wp_send_json_error(array("msg" => esc_attr__("Incorrect Data Supplied.", "gravity-otp-verification")));
              break;
          }
        }
        wp_send_json_error(array("msg" => esc_attr__("Incorrect Data Supplied.", "gravity-otp-verification")));
      }
    }
    /**
     * convert raw-status to named status
     *
     * @param  string $status
     * @return string
     */
    public function status($status) {
      switch ($status) {
        case 'error':
        case 'failed':
          return esc_attr__("Error Occured", "gravity-otp-verification");
          break;
        case 'send':
        case 'sent':
          return esc_attr__("Successful", "gravity-otp-verification");
          break;
        case 'unknown':
          return esc_attr__("Unknown", "gravity-otp-verification");
          break;
        case 'pending':
          return esc_attr__("Pending", "gravity-otp-verification");
          break;
        case 'sending':
          return esc_attr__("Sending", "gravity-otp-verification");
          break;
        case 'banned':
          return esc_attr__("Banned", "gravity-otp-verification");
          break;
        case 'limit':
          return esc_attr__("Reached Limit", "gravity-otp-verification");
          break;
        default:
          // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
          return sprintf(
            /* translators: %s: unknown status, leave this as is */
            esc_attr__("Status: %s", "gravity-otp-verification"),
            ucfirst($status)
          );
          break;
      }
      // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
      return sprintf(
        /* translators: %s: unknown status, leave this as is */
        esc_attr__("Status: %s", "gravity-otp-verification"),
        ucfirst($status)
      );
    }
    public function pretty_print($_2d_array, $color = "#0060df") {
      return substr(rtrim(str_replace(["\n", 'Array(', "[", "]", " => "], ['', '', "<br><span style='color: {$color}'>", "</span>", ": "],
      // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
      print_r($this->format_array($_2d_array), true)), ")"), 8);
    }
    public function format_array($a) {
      return array_map(function ($b) {
        return is_array($b) ? $this->format_array($b) : (is_bool($b) ? ($b ? 'true' : 'false') : $b);
      }, (array) $a);
    }
    public function update_footer_info() {
      add_filter("update_footer", function () {
        return sprintf(
          /* translators: 1: title, 2: version */
          esc_attr_x('%1$s — Version %1$s', "footer-copyright", "gravity-otp-verification"),
          esc_attr__("OTP Verification for Gravity Forms", "gravity-otp-verification"),
          $this->version
        );
      }, 999999999);
    }
    public function read($slug = '', $default = '') {
      return get_option("{$this->db_slug}__{$slug}", $default);
    }
    public function number_format($num = 0, $precision = 2) {
      return number_format($num, $this->read("precision", 2));
    }
    #endregion
  }
  add_action("plugins_loaded", function () {
    global $gravity_otp;
    $gravity_otp = new gravity_otp;
    load_plugin_textdomain("gravity-otp-verification", false, dirname(plugin_basename(__FILE__)) . "/languages/");
    register_activation_hook(__FILE__, array($gravity_otp, "activation_hook"));
  });
}

/*##################################################
Lead Developer: [amirhp-com](https://amirhp.com/)
##################################################*/