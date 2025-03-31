<?php
/*
 * @Author: Amirhossein Hosseinpour <https://amirhp.com>
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/03/31 21:47:46
 */

defined("ABSPATH") or die("<h2>Unauthorized Access!</h2><hr><small>OTP Verification for Gravity Forms :: Developed by <a href='https://blackswandev.com/'>BlackSwanDev</a></small>");
if (!class_exists('GF_Field')) return;

class GF_Field_OTP extends GF_Field {
    public $type = 'otp';

    public $otpDigits = 5; // Default value for OTP digits
    public $mobileFieldId = ''; // Default value for Mobile Field ID

    public function get_form_editor_field_title() {
        return esc_attr__('Mobile OTP', 'gravity-otp-verification');
    }

    public function get_form_editor_field_description() {
        return esc_attr__('Allows users to verify their identity with a mobile OTP.', 'gravity-otp-verification');
    }

    public function get_form_editor_field_icon() {
        return 'gform-icon gform-icon--twilio';
    }

    public function get_form_editor_field_settings() {
        return array(
            'conditional_logic_field_setting',
            'label_setting',
            'description_setting',
            'admin_label_setting',
            'css_class_setting',
            'otp_digits_setting',
            'mobile_field_id_setting',
        );
    }

    public function get_field_input($form, $value = '', $entry = null) {
        $form_id = absint($form['id']);
        $is_entry_detail = $this->is_entry_detail();
        $is_form_editor = $this->is_form_editor();
        $id = (int) $this->id;
        $field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";
        $digits = !empty($this->otpDigits) ? intval($this->otpDigits) : 5;
        $mobile_field_id = !empty($this->mobileFieldId) ? intval($this->mobileFieldId) : '';
        $disabled_text = $is_form_editor ? 'disabled="disabled"' : '';
        $input_html = "<div class='ginput_container ginput_container_otp' data-mobile-field-id='{$mobile_field_id}' data-field-id='{$id}' data-field-ref='{$field_id}' >";
        for ($i = 0; $i < $digits; $i++) {
            $input_html .= sprintf(
                "<input type='text' maxlength='1' class='otp-input' data-index='%d' inputmode='numeric' name='input_%d_%d' %s />",
                $i,
                $id,
                $i,
                $disabled_text
            );
        }
        $input_html .= "<button type='button' data-field-id='{$id}' data-field-ref='{$field_id}' data-mobile='#field_{$form_id}_{$mobile_field_id} #input_{$form_id}_{$mobile_field_id}' data-mobile-field='field_{$form_id}_{$mobile_field_id}' class='send-otp-btn gform_button button' {$disabled_text}>" . $this->read("send_btn", __("Send Code", "gravity-otp-verification")) . "</button>";
        $input_html .= '</div>';
        return $input_html;
    }

    public function read($slug = '', $default = '') {
        return get_option("gravity_otp_verification__{$slug}", $default);
    }

    public function is_conditional_logic_supported() {
        return true;
    }

    public function validate($value, $form) {
        if (is_array($value)) {
            $otp = implode('', $value);
            $digits = !empty($this->otpDigits) ? intval($this->otpDigits) : 5;
            if (strlen($otp) !== $digits || !preg_match('/^\d+$/', $otp)) {
                $this->failed_validation = true;
                $this->validation_message = empty($this->errorMessage) ? esc_html__('Please enter a valid OTP.', 'gravity-otp-verification') : $this->errorMessage;
            }
        }
    }

    public function get_value_merge_tag($value, $input_id, $entry, $form, $modifier, $raw_value, $url_encode, $esc_html, $format, $nl2br) {
        if ($format === 'html' && $nl2br) {
            $value = nl2br($value);
        }
        return $esc_html ? esc_html($value) : $value;
    }

    public function get_value_entry_list($value, $entry, $field_id, $columns, $form) {
        if (is_array($value)) {
            $value = implode('', $value);
        }
        return esc_html($value);
    }

    public function get_value_entry_detail($value, $currency = '', $use_text = false, $format = 'html', $media = 'screen') {
        if (is_array($value)) {
            $value = implode('', $value);
        }
        if ($format === 'html') {
            $value = nl2br($value);
        }
        return esc_html($value);
    }

    public function get_aria_describedby($extra_ids = array()) {
        $id = (int) $this->id;
        $form_id = (int) $this->formId;
        $is_entry_detail = $this->is_entry_detail();
        $is_form_editor = $this->is_form_editor();

        $field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

        $describedby = !empty($this->description) ? " gfield_description_{$form_id}_{$id}" : '';
        if ($this->failed_validation) {
            $describedby .= " validation_message_{$this->formId}_{$this->id}";
        }

        if (!empty($extra_ids)) {
            $describedby .= implode(' ', $extra_ids);
        }

        return empty($describedby) ? '' : 'aria-describedby="' . $describedby . '"';
    }

    public function get_filter_operators() {
        $operators = parent::get_filter_operators();
        $operators[] = 'contains';
        return $operators;
    }

    // Ensure custom properties are saved
    public function get_field_properties() {
        $properties = parent::get_field_properties();
        $properties['otpDigits'] = $this->otpDigits;
        $properties['mobileFieldId'] = $this->mobileFieldId;
        return $properties;
    }
}

// Register custom settings
add_action("gform_field_standard_settings", "gravity_otp_verification_register_custom_settings", 50, 2);
function gravity_otp_verification_register_custom_settings($position, $form_id) {
    if ($position == 50) {
        wp_enqueue_style('gf-otp-style', plugins_url("/assets/", dirname(__FILE__)) . "css/otp-style.css", [], "2.1.0", true);
        // Get the current form and its fields
        $form = GFFormsModel::get_form_meta($form_id);
        $fields = !empty($form['fields']) ? $form['fields'] : array();
        wp_enqueue_script("gravity_otp_verification_gf_setting", plugins_url("/assets/", dirname(__FILE__)) . "js/gf_setting.js", ["jquery"], "2.4.0");
        ?>
        <li class="otp_digits_setting field_setting">
            <label for="otp_digits">
                <?php esc_html_e('OTP Digits', 'gravity-otp-verification'); ?>
                <?php gform_tooltip('form_field_otp_digits'); ?>
            </label>
            <select id="otp_digits" onchange="SetFieldProperty('otpDigits', this.value);">
                <option value="4">4</option>
                <option value="5">5</option>
                <option value="6">6</option>
            </select>
        </li>
        <li class="mobile_field_id_setting field_setting">
            <label for="mobile_field_id">
                <?php esc_html_e('Mobile Field ID', 'gravity-otp-verification'); ?>
                <?php gform_tooltip('form_field_mobile_field_id'); ?>
            </label>
            <select id="mobile_field_id" onchange="SetFieldProperty('mobileFieldId', this.value);">
                <option value=""><?php esc_html_e('Loading...', 'gravity-otp-verification'); ?></option>
            </select>
            <!-- <input type="text" id="mobile_field_id" value="" onchange="SetFieldProperty('mobileFieldId', this.value);" /> -->
            <p class="description"><?php esc_html_e('Enter the ID of the field containing the mobile number.', 'gravity-otp-verification'); ?></p>
        </li>
        <?php
    }
}

// Add tooltips
add_filter("gform_tooltips", "gravity_otp_verification_add_tooltips");
function gravity_otp_verification_add_tooltips($tooltips) {
    $tooltips['form_field_otp_digits'] = esc_html__('Select the number of digits for the OTP code.', 'gravity-otp-verification');
    $tooltips['form_field_mobile_field_id'] = esc_html__('Enter the ID of the field that contains the mobile number.', 'gravity-otp-verification');
    return $tooltips;
}

// Register the field
GF_Fields::register(new GF_Field_OTP());