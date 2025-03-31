/*
 * @Author: Amirhossein Hosseinpour <https://amirhp.com>
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/03/31 21:08:24
 */
jQuery.noConflict();
(function ($) {
  $(function () {
    $(document).on('gform_load_field_settings', function (event, field, form) {
      // Set OTP Digits
      var otpDigits = field.otpDigits || 5;
      $('#otp_digits').val(otpDigits);

      // Set Mobile Field ID and populate dropdown
      var mobileFieldId = field.mobileFieldId || '';
      var select = $('#mobile_field_id');
      select.empty(); // Clear existing options
      select.append('<option value="">- select a field -</option>');

      // Populate dropdown with form fields
      $.each(form.fields, function (index, formField) {
        // Skip the OTP field itself to avoid self-reference
        if (formField.type === 'otp') {
          return;
        }
        if (!['phone', 'text'].includes(formField.type)) {
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