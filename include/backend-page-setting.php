<?php
/*
 * @Author: Amirhossein Hosseinpour <https://amirhp.com>
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/04/02 11:50:02
 */

namespace BlackSwan\GravityOTPVerification;

defined("ABSPATH") or die("<h2>Unauthorized Access!</h2><hr><small>OTP Verification for Gravity Forms :: Developed by <a href='https://blackswandev.com/'>BlackSwanDev</a></small>");
class setting_page extends gravity_otp {
  public function __construct() {
    parent::__construct();
    $this->render_page();
  }
  public function render_page() {
    ob_start(); ?>
    <div class="wrap">
      <h1 class="heading"><span class='fas fa-cogs'></span>&nbsp;<strong><?php echo esc_attr($this->title); ?></strong> &mdash; <?php echo  esc_attr__("Setting", "gravity-otp-verification"); ?></h1>
    </div>
    <?php
    $this->update_footer_info();
    wp_enqueue_media();
    wp_enqueue_script("jquery-ui");
    wp_enqueue_script("jquery-ui-core");
    wp_enqueue_script("jquery-ui-sortable");
    wp_enqueue_script("wp-color-picker");
    wp_enqueue_style("wp-color-picker");
    wp_enqueue_style("gravity_otp_verification_font_awesome", "{$this->assets_url}fa/css/all.min.css", array(), current_time("timestamp"));
    wp_enqueue_style("gravity_otp_verification_select2", "{$this->assets_db_url}css/select2.min.css", [], "1.0.0");
    wp_enqueue_script("gravity_otp_verification_select2", "{$this->assets_db_url}js/select2.min.js", ["jquery"], "1.2.1");
    wp_enqueue_script("gravity_otp_verification_jquery_repeater", "{$this->assets_url}js/jquery.repeater.min.js", ["jquery"], "1.2.1");
    wp_enqueue_script("gravity_otp_verification_setting_js", "{$this->assets_url}js/setting.js", ["jquery"], "2.4.0");
    wp_enqueue_style("gravity_otp_verification_backend_css", "{$this->assets_url}css/backend.css", [], "1.0.0");
    is_rtl() and wp_add_inline_style("gravity_otp_verification_backend_css", "#wpbody-content { font-family: bodyfont, roboto, Tahoma; }");
    ?>
    <div class="wrap">
      <?php
      // phpcs:ignore  WordPress.Security.NonceVerification.Recommended
      if (isset($_REQUEST["settings-updated"]) && $_REQUEST["settings-updated"] == "true") {
        echo '<div id="message" class="updated notice is-dismissible"><p>' . esc_attr_x("Settings saved successfully.", "setting-general", "gravity-otp-verification") . "</p></div>";
      }
      $gateways = apply_filters("gravity-otp-verification/sms-gateways-list", [
        "sms_ir"    => esc_attr__("SMS.ir", "gravity-otp-verification"),
        "sms_ir_v2" => esc_attr__("SMS.ir v2", "gravity-otp-verification"),
        "sms_faraz" => esc_attr__("FarazSMS (IPPanel)", "gravity-otp-verification"),
      ]);
      ?>
      <form method="post" action="options.php">
        <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
          <a href="#" data-tab="tab_general" class="nav-tab nav-tab-active"><?php echo  esc_attr__("General", "gravity-otp-verification"); ?></a>
          <a href="#" data-tab="tab_translate" class="nav-tab"><?php echo  esc_attr__("Translate", "gravity-otp-verification"); ?></a>
          <a href="#" data-tab="tab_str_replace" class="nav-tab"><?php echo  esc_attr__("String Replace", "gravity-otp-verification"); ?></a>
          <a href="#" data-tab="tab_documentation" class="nav-tab"><?php echo  esc_attr__("Documentation", "gravity-otp-verification"); ?></a>
        </nav>
        <?php
        settings_fields("{$this->db_slug}__general");
        ?>
        <div class="tab-content tab-active" data-tab="tab_general">
          <br>
          <table class="form-table wp-list-table widefat striped table-view-list posts">
            <thead><?php echo  "<tr class='gravity_otp_verification border-top'><th colspan=2><h2 style='display: inline-block;'>" . esc_attr__("General Configuration", "gravity-otp-verification") . "</h2></th></tr>"; ?></thead>
            <tbody>
              <?php
              $this->print_setting_checkbox(["slug" => "debug", "caption" => esc_attr__("Active Debug Mode", "gravity-otp-verification"),]);
              $this->print_setting_input(["slug" => "max_failed", "type" => "number", "caption" => esc_attr__("Max Failed Attempt", "gravity-otp-verification")]);
              $this->print_setting_input(["slug" => "mobile_regex", "type" => "text", "extra_html" => "dir=ltr lang=en_US", "caption" => esc_attr__("Mobile Regex", "gravity-otp-verification"), "desc" => sprintf(
                /* translators: 1: regex */
                esc_attr__('Set regex to validate mobile field, leave empty to disable it.%2$sFor Iranian Mobile use: %1$s', "gravity-otp-verification"),
                "<code>/^(\+98|0098|98|0)?9\d{9}$/</code>",
                "<br>"
              ),]);
              $this->print_setting_input(["slug" => "cookie_expiration", "type" => "number", "caption" => esc_attr__("Cookie Expiration (Day)", "gravity-otp-verification")]);
              $this->print_setting_input(["slug" => "lockdown_delay", "type" => "number", "caption" => esc_attr__("Lockdown Delay (Min.)", "gravity-otp-verification")]);
              $this->print_setting_input(["slug" => "resend_delay", "type" => "number", "caption" => esc_attr__("Resend Delay (Sec.)", "gravity-otp-verification")]);
              $this->print_setting_input(["slug" => "send_btn", "caption" => esc_attr__("Button: Send Code", "gravity-otp-verification")]);
              $this->print_setting_input(["slug" => "resend_btn", "caption" => esc_attr__("Button: Resend Code", "gravity-otp-verification")]);
              $this->print_setting_input(["slug" => "wait_btn", "caption" => esc_attr__("Button: Wait to Resend", "gravity-otp-verification"), "desc" =>
              /* translators: 1: dummy */
              esc_attr__('Use %1$d for Second indictor, e.g. Wait %1$d Seconds.', "gravity-otp-verification"),]);
              $this->print_setting_tr(esc_attr__("SMS Provider Setting", "gravity-otp-verification"));
              $this->print_setting_select([
                "slug"    => "sms_gateway",
                "caption" => esc_attr__("SMS Gateway", "gravity-otp-verification"),
                "options" => $gateways,
                "desc"    => esc_attr__("Select SMS Provider and fill below fields as guid provided", "gravity-otp-verification"),
              ]);
              ?>
              <tr>
                <td colspan='2' class="sms_gateways_helper">
                  <div class="hide help-sms_ir">
                    <ul class="pretty">
                      <li><?php echo wp_kses_post(sprintf(
                            /* translators: 1: opening anchor tag, 2: close anchor tag */
                            __('Fill "Username" and "Password" with <strong>API Key</strong> and <strong>Security Code</strong> from %1$sSMS.ir Profile%2$s', "gravity-otp-verification"),
                            '<a href="https://ip.sms.ir/#/UserApiKey" target="_blank">',
                            '</a>'
                          )); ?></li>
                      <li><?php echo wp_kses_post(sprintf(
                            /* translators: 1: opening anchor tag, 2: close anchor tag */
                            __('Set Sender Number to your %1$sPurchased Sender number%2$s e.g.<code>30002101000338</code>', "gravity-otp-verification"),
                            '<a href="https://app.sms.ir/numbers/my-number" target="_blank">',
                            '</a>'
                          )); ?></li>
                      <li><?php echo wp_kses_post(sprintf(
                            /* translators: 1: url */
                            __('Set API Server URL as %1$s or get Web-service URL from SMS.ir.', "gravity-otp-verification"),
                            '<code>https://ws.sms.ir/</code>'
                          )); ?></li>
                      <li><?php echo
                          // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
                          wp_kses_post(__('Write your Message on OTP SMS field using <code>[otp]</code> or <code>{otp}</code> or <code>%otp%</code> to replace with Actual OTP Code.', "gravity-otp-verification")); ?></li>
                      <li><?php echo wp_kses_post(sprintf(
                            /* translators: 1: opening anchor tag, 2: close anchor tag */
                            __('If you want to send UltraFastSend SMS, %1$sCreate a Template%2$s on SMS.ir and put <code>#OTP#</code> on it, then enter <code>Template ID</code> on <strong>OTP SMS</strong> field', "gravity-otp-verification"),
                            '<a href="https://app.sms.ir/developer/list" target="_blank">',
                            '</a>'
                          )); ?></li>
                    </ul>
                  </div>
                  <div class="hide help-sms_ir_v2">
                    <ul class="pretty">
                      <li><?php echo wp_kses_post( sprintf(
                            /* translators: 1: href link */
                            __('Fill "Username" with <strong>API Key</strong> from %1$sSMS.ir Profile%2$s', "gravity-otp-verification"),
                            '<a href="https://ip.sms.ir/#/UserApiKey" target="_blank">',
                            '</a>'
                          )); ?></li>
                      <li><?php echo wp_kses_post( sprintf(
                            /* translators: 1: opening anchor tag, 2: close anchor tag */
                            __('Set Sender Number to your %1$sPurchased Sender number%2$s e.g.<code>30002101000338</code>', "gravity-otp-verification"),
                            '<a href="https://app.sms.ir/numbers/my-number" target="_blank">',
                            '</a>'
                          )); ?></li>
                      <li><?php echo wp_kses_post( sprintf(
                            /* translators: 1: url */
                            __('Set API Server URL as %1$s or get Web-service URL from SMS.ir.', "gravity-otp-verification"),
                            '<code>https://api.sms.ir/v1/send/</code>'
                          )); ?></li>
                      <li><?php echo
                          // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
                          __('Write your Message on OTP SMS field using <code>[otp]</code> or <code>{otp}</code> or <code>%otp%</code> to replace with Actual OTP Code.', "gravity-otp-verification"); ?></li>
                      <li><?php echo wp_kses_post( sprintf(
                            /* translators: 1: opening anchor tag, 2: close anchor tag */
                            __('If you want to send UltraFastSend SMS, %1$sCreate a Template%2$s on SMS.ir and put <code>#OTP#</code> on it, then enter <code>Template ID</code> on <strong>OTP SMS</strong> field', "gravity-otp-verification"),
                            '<a href="https://app.sms.ir/developer/fast-send" target="_blank">',
                            '</a>'
                          )); ?></li>
                    </ul>
                  </div>
                  <div class="hide help-sms_faraz">
                    <ul class="pretty">
                      <li><?php echo wp_kses_post(__('Set API Server URL to <code>ippanel.com</code> or your given panel address, e.g. <code>sms.farazsms.com</code>', "gravity-otp-verification")); ?></li>
                      <li><?php echo wp_kses_post(__('Set Sender Number to your purchased SMS Sending number or If you are using <strong>Pattern SMS</strong> use <code>3000505</code>', "gravity-otp-verification")); ?></li>
                      <li><?php echo wp_kses_post(__('Username and Password are your SMS Panel Credentials.', "gravity-otp-verification")); ?></li>
                      <li><?php echo
                          // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
                          wp_kses_post(__('Write your Message on OTP SMS field using <code>[otp]</code> or <code>{otp}</code> or <code>%otp%</code> to replace with Actual OTP Code.', "gravity-otp-verification")); ?></li>
                      <li><?php echo
                          // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
                          wp_kses_post(__('If you want to use <strong>Pattern SMS</strong> method , Create a template on your panel and put <code>%otp%</code> on it, then use following example: ', "gravity-otp-verification") . "<pre style='text-align: left;direction: ltr;unicode-bidi: plaintext;'>pcode:6dvqf351dl06p34" . PHP_EOL . "otp:{otp}</pre>"); ?></li>
                    </ul>
                  </div>
                  <?php do_action("gravity-otp-verification/sms-gateways-help"); ?>
                </td>
              </tr>
              <?php
              $this->print_setting_input(["extra_class" => "gateway_option_field", "slug" => "api_server", "extra_html" => "dir=ltr lang=en_US", "caption" => esc_attr__("API Server URL", "gravity-otp-verification")]);
              $this->print_setting_input(["extra_class" => "gateway_option_field", "slug" => "api_username", "extra_html" => "dir=ltr lang=en_US", "caption" => esc_attr__("Username", "gravity-otp-verification")]);
              $this->print_setting_input(["extra_class" => "gateway_option_field", "slug" => "api_password", "extra_html" => "dir=ltr lang=en_US", "caption" => esc_attr__("Password", "gravity-otp-verification")]);
              $this->print_setting_input(["extra_class" => "gateway_option_field", "slug" => "api_sender_number", "extra_html" => "dir=ltr lang=en_US", "caption" => esc_attr__("Sender number", "gravity-otp-verification")]);
              $this->print_setting_textarea(["extra_class" => "gateway_option_field", "slug" => "api_otp_sms", "extra_html" => "dir=ltr lang=en_US", "caption" => esc_attr__("OTP SMS", "gravity-otp-verification")]);
              $this->print_setting_input(["extra_class" => "gateway_option_field", "slug" => "api_option_extra_1", "extra_html" => "dir=ltr lang=en_US", "caption" => esc_attr__("Gateway ExtraOtp1", "gravity-otp-verification")]);
              $this->print_setting_input(["extra_class" => "gateway_option_field", "slug" => "api_option_extra_2", "extra_html" => "dir=ltr lang=en_US", "caption" => esc_attr__("Gateway ExtraOtp2", "gravity-otp-verification")]);
              $this->print_setting_input(["extra_class" => "gateway_option_field", "slug" => "api_option_extra_3", "extra_html" => "dir=ltr lang=en_US", "caption" => esc_attr__("Gateway ExtraOtp3", "gravity-otp-verification")]);

              ?>
              <tr>
                <th><?php echo  esc_attr__("Send TEST SMS", "gravity-otp-verification"); ?></th>
                <td>
                  <a target="_blank" href="<?php echo  esc_attr(wp_nonce_url(admin_url("?gravity_otp_verification_send_test=0912...."), $this->td, "nonce")); ?>" class="button button-secondary"><?php echo  esc_attr__("Send Test OTP SMS", "gravity-otp-verification"); ?></a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="tab-content" data-tab="tab_translate">
          <br>
          <p class="description"><?php echo  esc_attr__("Current plugin textdomain: ", "gravity-otp-verification"); ?><i class="highlighted">gravity-otp-verification</i></p>
          <div class="desc repeater translation-panel">
            <table class="wp-list-table widefat striped table-view-list posts">
              <thead>
                <tr>
                  <th class="th-original"><?php echo  esc_attr__("Original", "gravity-otp-verification"); ?></th>
                  <th class="th-translate"><?php echo  esc_attr__("Translate", "gravity-otp-verification"); ?></th>
                  <th class="th-text-domain"><?php echo  esc_attr__("TextDomain", "gravity-otp-verification"); ?></th>
                  <th class="th-options"><?php echo  esc_attr__("Options", "gravity-otp-verification"); ?></th>
                  <th class="th-action" style="width: 100px;"><?php echo  esc_attr__("Action", "gravity-otp-verification"); ?></th>
                </tr>
              </thead>
              <tbody data-repeater-list="gettext">
                <tr data-repeater-item style="display:none;">
                  <td class="th-original"><span class="dashicons dashicons-menu-alt move-handle"></span>&nbsp;<input type="text" data-slug="original" name="original" value="" placeholder="<?php echo  esc_attr__("Original text ...", "gravity-otp-verification"); ?>" /></td>
                  <td class="th-translate"><input type="text" data-slug="translate" name="translate" placeholder="<?php echo  esc_attr__("Translate to ...", "gravity-otp-verification"); ?>" /></td>
                  <td class="th-text-domain"><input type="text" data-slug="text_domain" name="text_domain" placeholder="<?php echo  esc_attr__("Text Domain (Optional)", "gravity-otp-verification"); ?>" /></td>
                  <td class="th-options">
                    <label><input type="checkbox" value="yes" data-slug="use_replace" name="use_replace" />&nbsp;<?php echo  esc_attr__("Partial Replace?", "gravity-otp-verification"); ?></label>&nbsp;&nbsp;
                    <label><input type="checkbox" value="yes" data-slug="two_sided" name="two_sided" />&nbsp;<?php echo  esc_attr__("Translated Origin?", "gravity-otp-verification"); ?></label>
                  </td>
                  <td class="th-action">
                    <a class="button button-secondary" data-repeater-delete><span style="margin: 4px 0;" class="dashicons dashicons-trash"></span><?php echo  esc_attr__("Delete Row", "gravity-otp-verification"); ?></a>
                  </td>
                </tr>
              </tbody>
            </table>
            <br>
            <a data-repeater-create class="button button-secondary button-hero"><span style="margin: 12px 8px;" class="dashicons dashicons-insert"></span><?php echo  esc_attr__("Add New Row", "gravity-otp-verification"); ?></a>&nbsp;&nbsp;
          </div>
          <br><br>
          <table class="form-table wp-list-table widefat striped table-view-list posts">
            <thead>
              <?php
              echo "<tr class='gravity_otp_verification border-top'><th colspan=2><strong style='display: inline-block;'>" . esc_attr__("Migrate Translation", "gravity-otp-verification") . "</strong></th></tr>";
              ?>
            </thead>
            <tbody>
              <?php
              $this->print_setting_textarea(["slug" => "gettext_replace", "caption" => esc_attr__("Translation Data", "gravity-otp-verification"), "style" => "width: 100%; direction: ltr; min-height: 300px; font-family: monospace; font-size: 0.8rem;",]);
              ?>
            </tbody>
          </table>
        </div>
        <div class="tab-content" data-tab="tab_str_replace">
          <br>
          <div class="desc repeater str_replace-panel">
            <table class="wp-list-table widefat striped table-view-list posts">
              <thead>
                <tr>
                  <th class="th-original"><?php echo  esc_attr__("Original", "gravity-otp-verification"); ?></th>
                  <th class="th-translate"><?php echo  esc_attr__("Replace", "gravity-otp-verification"); ?></th>
                  <th class="th-options"><?php echo  esc_attr__("Options", "gravity-otp-verification"); ?></th>
                  <th class="th-action" style="width: 100px;"><?php echo  esc_attr__("Action", "gravity-otp-verification"); ?></th>
                </tr>
              </thead>
              <tbody data-repeater-list="gettext">
                <tr data-repeater-item style="display:none;">
                  <td class="th-original"><span class="dashicons dashicons-menu-alt move-handle"></span>&nbsp;<input type="text" data-slug="original" name="original" value="" placeholder="<?php echo  esc_attr__("Original text ...", "gravity-otp-verification"); ?>" /></td>
                  <td class="th-translate"><input type="text" data-slug="translate" name="translate" placeholder="<?php echo  esc_attr__("Translate to ...", "gravity-otp-verification"); ?>" /></td>
                  <td class="th-options">
                    <label><input type="checkbox" value="yes" data-slug="buffer" name="buffer" />&nbsp;<?php echo  esc_attr__("Green: Replace in Output Buffer | Red: Replace in Content Only", "gravity-otp-verification"); ?></label>&nbsp;&nbsp;
                    <label><input type="checkbox" value="yes" data-slug="active" name="active" />&nbsp;<?php echo  esc_attr__("Active", "gravity-otp-verification"); ?></label>&nbsp;&nbsp;
                  </td>
                  <td class="th-action">
                    <a class="button button-secondary" data-repeater-delete><span style="margin: 4px 0;" class="dashicons dashicons-trash"></span><?php echo  esc_attr__("Delete Row", "gravity-otp-verification"); ?></a>
                  </td>
                </tr>
              </tbody>
            </table>
            <br>
            <a data-repeater-create class="button button-secondary button-hero"><span style="margin: 12px 8px;" class="dashicons dashicons-insert"></span><?php echo  esc_attr__("Add New Row", "gravity-otp-verification"); ?></a>&nbsp;&nbsp;
          </div>
          <br><br>
          <table class="form-table wp-list-table widefat striped table-view-list posts">
            <thead>
              <?php
              echo "<tr class='gravity_otp_verification border-top'><th colspan=2><strong style='display: inline-block;'>" . esc_attr__("Migrate String Replace", "gravity-otp-verification") . "</strong></th></tr>";
              ?>
            </thead>
            <tbody>
              <?php
              $this->print_setting_textarea(["slug" => "str_replace", "caption" => esc_attr__("String Replace Data", "gravity-otp-verification"), "style" => "width: 100%; direction: ltr; min-height: 300px; font-family: monospace; font-size: 0.8rem;",]);
              ?>
            </tbody>
          </table>
        </div>
        <div class="tab-content" data-tab="tab_documentation">
          <br>
          <div class="desc">
            <table class="wp-list-table widefat striped table-view-list posts fixed">
              <thead>
                <tr>
                  <th><strong>Endpoint/Shortcode/Hook</strong></th>
                  <td><strong>Description</strong></td>
                </tr>
              </thead>
              <tr>
                <td style="direction: ltr;"><code>[gravity_otp_user_ip]</code></td>
                <td>Show current user's detected IP Address</td>
              </tr>
              <tr>
                <td style="direction: ltr;"><code>[gravity_otp_popup
                    id="FORM ID"
                    title="false"
                    ajax="yes"
                    width="450px"
                    class="extra_class html_class"
                    hide_for_logged_in="false"
                    hide_for_logged_out="false"]
                    <br><br><i>some content/shortcode on top of form</i>
                    <br><br>[/gravity_otp_popup]</code></td>
                <td>Show a Popup with Gravity Form If User has Not Submitted that form before</td>
              </tr>
              <tr>
                <td style="direction: ltr;"><code>[gravity_otp_popup_forced
                    id="FORM ID"
                    width="450px"
                    class="extra_class html_class"
                    hide_for_logged_in="false"
                    hide_for_logged_out="false"]
                    <br><br><i>some content/shortcode to be shown inside popup</i>
                    <br><br>[/gravity_otp_popup_forced]</code></td>
                <td>Show a Popup with Given Content in it If User has Not Submitted Gravity Form before</td>
              </tr>
              <tr>
                <td style="direction: ltr;"><code>gravity-otp-verification/sms-gateways-list</code></td>
                <td>Filter hook: Modify list of SMS Gateways</td>
              </tr>
              <tr>
                <td style="direction: ltr;"><code>gravity-otp-verification/sms-gateways-help</code></td>
                <td>Action hook: Print help note for Given Gateway (div.hide.help-GATEWAY_ID)</td>
              </tr>
              <tr>
                <td style="direction: ltr;"><code>gravity-otp-verification/fn-send-sms/$sms_gateway</code></td>
                <td>Filter hook: Handle Sending SMS using Custom SMS Gateway</td>
              </tr>
              <tr>
                <td style="direction: ltr;">Host Cronjob<br><code>define('DISABLE_WP_CRON', true);</code></td>
                <td style="direction: ltr;">* * * * * wget --delete-after <?php echo  esc_attr(home_url("/wp-cron.php?doing_wp_cron")); ?> >/dev/null 2>&1<br>/usr/local/bin/php /home/public_html/wp-cron.php?doing_wp_cron >/dev/null 2>&1</td>
              </tr>
            </table>
            <br>
            <table class="wp-list-table widefat striped table-view-list posts fixed">
              <thead>
                <tr>
                  <th><strong>Credits</strong></th>
                  <td><strong>Library</strong></td>
                </tr>
              </thead>
              <tr>
                <td>jquery.repeater</td>
                <td><a href="https://github.com/DubFriend/jquery.repeater" target="_blank">https://github.com/DubFriend/jquery.repeater</a></td>
              </tr>
              <tr>
                <td>jqueryUI.sortable</td>
                <td><a href="https://jqueryui.com/sortable/" target="_blank">https://jqueryui.com/sortable/</a></td>
              </tr>
              <tr>
                <td>jquery.confirm</td>
                <td><a href="https://craftpip.github.io/jquery-confirm/" target="_blank">https://craftpip.github.io/jquery-confirm/</a></td>
              </tr>
              <tr>
                <td>select2.js</td>
                <td><a href="https://select2.org/" target="_blank">https://select2.org/</a></td>
              </tr>
              <tr>
                <td>datatables.js</td>
                <td><a href="https://datatables.net/" target="_blank">https://datatables.net/</a></td>
              </tr>
            </table>
          </div>
        </div>
        <div class="submit_wrapper">
          <button id="submit" class="button button-primary button-hero"><span style="margin: 12px 8px;" class="dashicons dashicons-yes-alt"></span>&nbsp;<?php echo  esc_attr__("Save setting", "gravity-otp-verification"); ?></button>
        </div>
      </form>
    </div>
  <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $html_content = ob_get_contents();
    ob_end_clean();
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $html_content;
  }
  public function print_setting_input($data) {
    extract(wp_parse_args($data, array(
      "slug"        => "",
      "caption"     => "",
      "type"        => "text",
      "desc"        => "",
      "extra_html"  => "",
      "extra_class" => "",
    )));
    echo "<tr class='type-" . esc_attr($type) . " " . esc_attr($extra_class) . " " . esc_attr(sanitize_title($slug)) . "'>
            <th scope='row'><label for='" . esc_attr($slug) . "'>" . esc_attr($caption) . "</label></th>
            <td><input
                    name='" . esc_attr("{$this->db_slug}__{$slug}") . "'
                    id='" . esc_attr($slug) . "'
                    type='" . esc_attr($type) . "'
                    placeholder='" . esc_attr($caption) . "'
                    title='" . esc_attr(sprintf(
      /* translators: 1: field name */
      _x("Enter %s", "setting-page", "gravity-otp-verification"),
      $caption
    )) . "' value='" . esc_attr($this->read($slug)) . "'
                    class='regular-text " . esc_attr($extra_class) . "' " . esc_attr($extra_html) . " />
            <p class='description'>" . wp_kses_post($desc) . "</p></td></tr>";
  }
  public function print_setting_tr($title = "") { ?>
    <tr style="color: #2c3338;vertical-align: middle !important;font-weight: 400;line-height: 1.4em;border: 1px solid #c3c4c7;background: #fff;">
      <th colspan="2">
        <h2><?php echo  esc_attr($title); ?></h2>
      </th>
    </tr>
<?php
  }
  public function print_setting_checkbox($data) {
    extract(wp_parse_args($data, array(
      "slug"        => "",
      "caption"     => "",
      "desc"        => "",
      "value"       => "yes",
      "extra_html"  => "",
      "extra_class" => "",
    )));
    echo "<tr class='type-checkbox " . esc_attr($extra_class) . " " . esc_attr(sanitize_title($slug)) . "'>
            <th scope='row'><label for='" . esc_attr($slug) . "'>" . esc_attr($caption) . "</label></th>
            <td><input name='" . esc_attr("{$this->db_slug}__{$slug}") . "' id='" . esc_attr($slug) . "' type='checkbox' title='" . esc_attr(sprintf(
      /* translators: 1: field name */
      _x("Enter %s", "setting-page", "gravity-otp-verification"),
      $caption
    )) . "' value='" . esc_attr($value) . "' " . checked(esc_attr($value), esc_attr($this->read($slug)), false) . " class='regular-text " . esc_attr($extra_class) . "' " . esc_attr($extra_html) . " />
    <p class='description'>" . wp_kses_post($desc) . "</p></td></tr>";
  }
  public function print_setting_select($data) {
    extract(wp_parse_args($data, array(
      "slug"        => "",
      "caption"     => "",
      "options"     => array(),
      "desc"        => "",
      "extra_html"  => "",
      "extra_class" => "",
    )));
    echo "<tr class='type-select " . esc_attr($extra_class) . " " . esc_attr(sanitize_title($slug)) . "'>
            <th scope='row'><label for='" . esc_attr($slug) . "'>" . esc_attr($caption) . "</label></th>
            <td><select name='" . esc_attr("{$this->db_slug}__{$slug}") . "' id='" . esc_attr($slug) . "' title='" . esc_attr(
      sprintf(
        /* translators: 1: field name */
        _x("Choose %s", "setting-page", "gravity-otp-verification"),
        esc_attr($caption)
      )
    ) .
      "' class='regular-text " . esc_attr($extra_class) . "' " . esc_attr($extra_html) . ">";
    foreach ($options as $key => $value) {
      if ($key == "EMPTY") $key = "";
      echo "<option value='" . esc_attr($key) . "' " . selected(esc_attr($this->read($slug)), esc_attr($key), false) . ">" . esc_html($value) . "</option>";
    }
    echo "</select><p class='description'>" . wp_kses_post($desc) . "</p></td></tr>";
  }
  public function print_setting_textarea($data) {
    extract(wp_parse_args($data, array(
      "slug"        => "",
      "caption"     => "",
      "style"     => "",
      "desc"        => "",
      "rows"        => "5",
      "extra_html"  => "",
      "full_width"  => "no",
      "extra_class" => "",
    )));
    $full_width = "yes" == $full_width;
    echo "<tr class='type-textarea " . esc_attr($extra_class) . " " . esc_attr(sanitize_title($slug)) . "'>" .
      ($full_width ? "" : "<th scope='row'><label for='" . esc_attr($slug) . "'>" . esc_attr($caption) . "</label></th>") .
      "<td colspan=" . ($full_width ? "2" : "1") . ">" .
      ($full_width ? "<p><label for='" . esc_attr($slug) . "'>" . esc_attr($caption) . "</label></p>" : "") .
      "<textarea name='" . esc_attr("{$this->db_slug}__{$slug}") . "' id='" . esc_attr($slug) . "' placeholder='" . esc_attr($caption) . "' title='" . esc_attr(sprintf(
        /* translators: 1: field name */
        _x("Enter %s", "setting-page", "gravity-otp-verification"), $caption
      )) . "' rows='" . esc_attr($rows) . "' style='" . esc_attr($style) . "' class='regular-text " . esc_attr($extra_class) . "' " . esc_attr($extra_html) . " >" .
      ( esc_textarea($this->read($slug)) ) . "</textarea>
      <p class='description'>" . wp_kses_post($desc) . "</p></td></tr>";
  }
  public function print_setting_editor($data) {
    extract(wp_parse_args($data, array(
      "slug"        => "",
      "caption"     => "",
      "options"     => array(),
      "desc"        => "",
      "extra_class" => "",
    )));
    $editor_settings = array_merge($options, array(
      'editor_height' => 150,    // (number) Editor height in pixels
      'media_buttons' => false,  // (bool) Whether to show the Add Media/other media buttons.
      'teeny'         => false,  // (bool) Whether to output the minimal editor config. Examples include Press This and the Comment editor. Default false.
      'tinymce'       => true,   // (bool|array) Whether to load TinyMCE. Can be used to pass settings directly to TinyMCE using an array. Default true.
      'quicktags'     => false,  // (bool|array) Whether to load Quicktags. Can be used to pass settings directly to Quicktags using an array. Default true.
      'editor_class'  => "",     // (string) Extra classes to add to the editor textarea element. Default empty.
      'textarea_name' => "{$this->db_slug}__{$slug}",
    ));

    $editor_id = strtolower(str_replace(array('-', '_', ' ', '*'), '', $slug));
    echo "<tr class='type-editor " . esc_attr($extra_class) . " " . esc_attr(sanitize_title($slug)) . "'>
    <th scope='row'><label for='" . esc_attr($slug) . "'>" . esc_attr($caption) . "</label></th><td>";
    wp_editor(wp_kses_post($this->read($slug)), $editor_id, $editor_settings);
    echo "<p class='description'>" . wp_kses_post($desc) . "</p></td></tr>";
  }
}
return new setting_page;
