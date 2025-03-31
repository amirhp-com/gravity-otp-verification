<?php
/*
 * @Author: Amirhossein Hosseinpour <https://amirhp.com>
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/03/31 21:50:58
 */
namespace BlackSwan\GravityOTPVerification;
defined("ABSPATH") or die("<h2>Unauthorized Access!</h2><hr><small>OTP Verification for Gravity Forms :: Developed by <a href='https://blackswandev.com/'>BlackSwanDev</a></small>");
class log_page extends mainClass {
  public function __construct() {
    parent::__construct(false);
    $this->render_page();
  }
  public function render_page() {
    ob_start();
    ?>
    <div class="wrap">
      <h1 class="heading"><span class='fas fa-list'></span>&nbsp;<strong><?php echo  esc_attr($this->title); ?></strong> &mdash; <?php echo  esc_attr__("SMS Logs", "gravity-otp-verification"); ?></h1>
    </div>
    <?php
    $this->update_footer_info();
    ?>
    <p class="alert alert-info alert-alt info">
      <?php echo  esc_attr(sprintf(
        /* translators: 1: ip */
        __("YourIP: %s", "gravity-otp-verification"),
        $this->get_real_IP_address()
      ));
      ?>
    </p>
    <?php
    echo "<div class='hitools dt-buttons'>
            <!-- a class='dt-button' id='add_new' href='#add_new'><i class='fas fa-plus-circle'></i> " . esc_attr_x("Add New", "setting-general", "gravity-otp-verification") . "</! -->
            <a class='dt-button' id='select_all' href='#select_all'><i class='fas fa-check'></i> " . esc_attr_x("Select All", "setting-general", "gravity-otp-verification") . "</a>
            <a class='dt-button' id='advanced_search' href='#advanced_search'><i class='fas fa-search'></i> " . esc_attr_x("Advanced Search", "setting-general", "gravity-otp-verification") . "</a>
            <a class='dt-button' id='delete_selected' href='#delete_selected'><i class='fas fa-trash'></i> " . esc_attr_x("Delete Selected", "setting-general", "gravity-otp-verification") . "</a>
            <a class='dt-button' id='empty_db_truncate' href='?gheyas_empty_db_truncate=1' data-refresh='true' href='#'><i class='fas fa-trash-alt'></i> " . esc_attr_x("Empty Database", "setting-general", "gravity-otp-verification") . "</a>
            <a class='dt-button' id='force_db_create' href='?gheyas_force_db_create=1'><i class='fas fa-database'></i> " . esc_attr_x("Fix Database Structure", "setting-general", "gravity-otp-verification") . "</a>
          </div>";
    $defaults = apply_filters(
      "gravity-otp-verification/datatables_defaults",
      array(
        "td" => "gravity-otp-verification",
        "table" => $this->db_table,
        "current_page_url" => admin_url("admin.php?page=gravity_otp_verification_deposit"),
        "default_per_page" => 50,
        "table_headers" => array(
          "_sharp_id"     => __("ID", "gravity-otp-verification"),
          "date_created"  => __("Date created", "gravity-otp-verification"),
          "user_id"       => __("User", "gravity-otp-verification"),
          "phone"         => __("Mobile", "gravity-otp-verification"),
          "otp"           => __("OTP Code", "gravity-otp-verification"),
          "status"        => __("Status", "gravity-otp-verification"),
          "gf_id"         => __("Gravity Form", "gravity-otp-verification"),
          "page_id"       => __("Ref. Page", "gravity-otp-verification"),
          "user_agent"    => __("User Agent", "gravity-otp-verification"),
          "ip"            => __("User IP", "gravity-otp-verification"),
          "res"           => __("Technical Info", "gravity-otp-verification"),
          "_sharp_action" => __("Action", "gravity-otp-verification"),
        ),
        "table_search" => array(
          "_sharp_id"     => __("ID", "gravity-otp-verification"),
          "date_created"  => __("Date created", "gravity-otp-verification"),
          "date_modified" => __("Date created", "gravity-otp-verification"),
          "user_id"       => __("User", "gravity-otp-verification"),
          "mobile"        => __("Mobile", "gravity-otp-verification"),
          "otp"           => __("OTP Code", "gravity-otp-verification"),
          "status"        => __("Status", "gravity-otp-verification"),
          "gf_id"         => __("Gravity Form", "gravity-otp-verification"),
          "page_id"       => __("Ref. Page", "gravity-otp-verification"),
          "user_agent"    => __("User Agent", "gravity-otp-verification"),
          "ip"            => __("User IP", "gravity-otp-verification"),
          "res"           => __("Technical Info", "gravity-otp-verification"),
        ),
        "item_val_parsing"  => function ($obj, $header_key, $item_value) {
          $item_value = property_exists($obj, $header_key) ? $obj->$header_key : "";
          switch ($header_key) {
            case '_res':
              return "<span style='cursor: pointer;margin: 0.5rem;' title='" . esc_attr__("View more", "gravity-otp-verification") . "'>" . __("more ..", "gravity-otp-verification") . "</span>";
              break;
            case '_sharp_id':
              return "<label><input name='selected_id[]' autocomplete='off' value='$obj->id' type='checkbox'/> $obj->id</label>";
              break;
            case '_sharp_action':
              $action = '<div class="dt-buttons">
                <a href="' . esc_attr(esc_url(isset($obj->user_id) && !empty($obj->user_id) ? admin_url("user-edit.php?user_id={$obj->user_id}") : "#user_not_found")) . '" target="_blank" class="dt-button btn-just-icon edit--user" data-id="' . $obj->id . '" data-tippy-content="' . __("Edit User", "gravity-otp-verification") . '"><i class="fas fa-user-circle fa-fw"></i></a>
                <!-- a href="javascript:;" class="dt-button btn-just-icon edit--entry" data-id="' . $obj->id . '" data-tippy-content="' . __("Edit entry", "gravity-otp-verification") . '"><i class="fas fa-pen fa-fw"></i></a -->
                <a href="javascript:;" class="dt-button btn-just-icon delete--entry" data-id="' . $obj->id . '" data-tippy-content="' . __("Delete entry", "gravity-otp-verification") . '"><i class="fas fa-trash-alt fa-fw"></i></a>
              </div>';
              return $action;
              break;
            case 'customer_id':
            case 'user_id':
            case 'edited_by':
              return 0 == $obj->$header_key ? __("- Guest -", "gravity-otp-verification") : $this->display_user($obj->$header_key, true, true, true);
              break;
            case 'phone':
              return make_clickable($obj->mobile);
              break;
            case 'gf_id':
              $form = \GFAPI::get_form($obj->$header_key);
              if ($form && isset($form['title'])) return "<a href='" . admin_url("admin.php?page=gf_edit_forms&id={$obj->$header_key}") . "' target='_blank'>{$form['title']}</a>";
              return $obj->$header_key;
              break;
            case 'page_id':
              $title = get_the_title($obj->$header_key);
              if ($title) return "<a href='" . admin_url("post.php?post={$obj->$header_key}&action=edit") . "' target='_blank'>{$title}</a>";
              return $obj->$header_key;
              break;
            case 'note':
            case 'note_admin':
            case 'res':
            case 'result':
              if (empty($item_value)) return "";
              $ref = wp_unique_id("view_{$obj->id}_{$header_key}__");
              return "<pre id='$ref' style='display:none;white-space: pre-wrap;text-align: start;padding: 0.5rem;'>" . htmlentities(
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
                print_r($item_value, 1)
              ) . "</pre>
                  <div class='dt-buttons'><a class='view-in-popup dt-button' data-ref='$ref' href='javascript:;'>" . esc_attr__("View", "gravity-otp-verification") . "</a></div>";
              break;
            case 'date_created':
            case 'date_modified':
              return !empty($obj->$header_key) ? '<span style="direction: ltr;unicode-bidi: plaintext;">' . date_i18n("Y/m/d H:i:s", strtotime($item_value)) . "</span>" : "";
              break;
            case 'status':
              return $this->status($item_value);
              break;
            default:
              return esc_html($obj->$header_key);
              break;
          }
          return esc_html(property_exists($obj, $header_key) ? $obj->$header_key : "");
        },
        "item_tr_class" => function ($obj) {
          return "status-{$obj->status}";
        },
        "item_tr_fn" => function ($obj) {
          $datas = htmlentities(json_encode($obj, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
          return "data-json=\"$datas\"";
        },
      )
    );
    ?>
    <template id="addnewshelfform">
      <form class="addnewshelf">
        <div class="row-item"><label for="status"><?php echo  esc_attr__("Status", "gravity-otp-verification"); ?></label><select class="status" name="status" id="status" autocomplete="off" inputmode="none">
            <option value="pending"><?php echo  esc_attr__("Pending", "gravity-otp-verification"); ?></option>
            <option value="approved"><?php echo  esc_attr__("Approved", "gravity-otp-verification"); ?></option>
            <option value="rejected"><?php echo  esc_attr__("Rejected", "gravity-otp-verification"); ?></option>
            <option value="unknown"><?php echo  esc_attr__("Unknown", "gravity-otp-verification"); ?></option>
          </select></div>
      </form>
    </template>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $this->dataTable($defaults);
    $html_content = ob_get_contents();
    ob_end_clean();
    echo $html_content;
  }
}
return new log_page;
