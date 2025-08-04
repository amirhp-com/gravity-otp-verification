/*
 * @Author: Amirhossein Hosseinpour <https://amirhp.com>
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/08/04 18:39:01
 */
jQuery.noConflict();
(function ($) {
  $(function () {
    $(document).on('gform_load_field_settings', function (event, field, form) {
      var otpDigits = field.otpDigits || 5;
      var mobileFieldId = field.mobileFieldId || '';
      var otpType = field.otpType || 'mobile';
      $('#otp_digits').val(otpDigits);
      $('#otp_type').val(otpType).trigger("change");

      var select = $('#mobile_field_id');
      select.empty(); // Clear existing options
      select.append('<option value="">- select a field -</option>');

      // Populate dropdown with form fields
      $.each(form.fields, function (index, formField) {
        // Skip the OTP field itself to avoid self-reference
        if (formField.type === 'otp') {
          return;
        }
        if (!['phone', 'text', 'email'].includes(formField.type)) {
          return;
        }
        var fieldLabel = formField.label || '(no label)';
        var fieldType = formField.type;
        var fieldId = formField.id;
        var displayText = fieldLabel + ' (' + fieldType + ') - ID: ' + fieldId;
        select.append('<option value="' + fieldId + '"' + (mobileFieldId == fieldId ? ' selected' : '') + '>' + displayText + '</option>');
      });

      // Set the selected value
      select.val(mobileFieldId);
    });
  });
})(jQuery);