/*
 * @Author: Amirhossein Hosseinpour <https://amirhp.com>
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/08/04 18:45:15
 */
jQuery.noConflict();
(function ($) {
  $(function () {
    function show_toast(data = "Sample Toast!", bg = "", delay = 7000) {
      if (!$("toast").length) {
        $(document.body).append($("<toast>"));
      } else {
        $("toast").removeClass("active");
      }
      setTimeout(function () {
        $("toast")
          .css("--toast-bg", bg)
          .html(data)
          .stop()
          .addClass("active")
          .delay(delay)
          .queue(function () {
            $(this).removeClass("active").dequeue().off("click tap");
          })
          .on("click tap", function (e) {
            e.preventDefault();
            $(this).stop().removeClass("active");
          });
      }, 200);
    }
    var $success_color = "rgba(21, 139, 2, 0.8)";
    var $error_color = "rgba(139, 2, 2, 0.8)";
    var $info_color = "rgba(2, 133, 139, 0.8)";
    if (!$("toast").length) {
      $(document.body).append("<toast style='--toast-bg: rgba(139, 2, 2, 0.8);'>toast</toast>");
      setTimeout(function () { $("toast").empty(); }, 100);
    }
    function place_otp_btn() {
      $(".send-otp-btn").each(function (i, x) {
        var me = $(this);
        var mobileField = $("#"+me.data("mobile-field"));
        if (mobileField.length) {
          mobileField.find(".ginput_container").addClass("has-otp-btn-wrapper").append(me);
        }
      });
    }
    place_otp_btn();

    $(document).on('gform_page_loaded', function(event, form_id, current_page){
      place_otp_btn();
      $(".hide-otp-field").hide();
      $("#gform_submit_button_"+form_id).prop("disabled", true);
      // $("variable")
    });

    var persianNumbers = [/۰/g, /۱/g, /۲/g, /۳/g, /۴/g, /۵/g, /۶/g, /۷/g, /۸/g, /۹/g], arabicNumbers  = [/٠/g, /١/g, /٢/g, /٣/g, /٤/g, /٥/g, /٦/g, /٧/g, /٨/g, /٩/g],
    toEnglishNumber = function (str){ if(typeof str === 'string') { for(var i=0; i<10; i++) { str = str.replace(persianNumbers[i], i).replace(arabicNumbers[i], i); } } return str; };

    $(document).on('keyup', '.otp-input', function(e) {
      var val = toEnglishNumber($(this).val())
      $(this).val(val)
      if (e.key === "Backspace" && !$(this).val()) {
        const prev = $(this).prev(".otp-input");
        if (prev.length) { prev.val("").focus(); }
      }
   });
    $(document).on("input", ".otp-input", function (e) {
      const $this = $(this);
      var val = toEnglishNumber($this.val()).replace(/\D/g, "");
      if (val.length > 0) {
        $this.val(val[0]);
        const next = $this.next(".otp-input");
        if (next.length) { next.focus(); }
        else if ($this.is(":last-child")) { $this.parents("form").find(".gform_button.button[type='submit']").prop("disabled", false).click(); }
      }
    });
    $(document).on("click", ".send-otp-btn", function () {
      const $btn = $(this);
      const formId = $btn.parents("form").data("formid");
      $btn.parents("form").find(".gfield--input-type-otp").hide();
      $btn.parents("form").find(".gfield--input-type-otp .validation_message").empty();
      const fieldId = $btn.data("field-id");
      const $mobileField = $btn.parents("form").find($btn.data("mobile"));
      const otpType = $btn.data("otp_type") || "mobile";
      if (!fieldId) { show_toast(gravity_otp_verification_vars.err_field_id, $error_color); return; }
      if (!formId) { show_toast(gravity_otp_verification_vars.err_form_id, $error_color); return; }
      if (!$mobileField.length) { show_toast(gravity_otp_verification_vars.err_mobile_field, $error_color); return; }
      const phone = $mobileField.val();
      if (!phone && otpType == "mobile") { show_toast(gravity_otp_verification_vars.err_mobile_empty, $error_color); return; }
      if (!phone && otpType == "email") { show_toast(gravity_otp_verification_vars.err_email_empty, $error_color); return; }
      $btn.prop("disabled", true);
      $("#" + $btn.data("mobile-field").replace("field_", "validation_message_")).empty();

      show_toast(gravity_otp_verification_vars.wait, $info_color, 60000);
      $.ajax({ url: gravity_otp_verification_vars.ajax_url, type: "POST", data: {
          action: "send_otp",
          nonce: gravity_otp_verification_vars.nonce,
          page_id: gravity_otp_verification_vars.page_id,
          phone: phone,
          type: otpType,
          form_id: formId,
          field_id: fieldId,
        },
        success: function (response) {
          if (response.success) {
            const $timer = $btn;
            let timeLeft = response.data.timer;
            show_toast(response.data.message, $success_color);
            $("#gform_submit_button_"+formId).prop("disabled", false);
            $btn.parents("form").find(".gfield--input-type-otp").slideDown("slow");
            const timer = setInterval(() => {
              $timer.text(gravity_otp_verification_vars.wait_btn.replace("%d", timeLeft));
              timeLeft--;
              if (timeLeft < 0) {
                clearInterval(timer);
                $btn.text(gravity_otp_verification_vars.resend_btn).prop("disabled", false);
              }
            }, 1000);
          } else {
            show_toast(response.data.message, $error_color);
            $btn.prop("disabled", false);
          }
        },
        error: function (xhr, status, error) {
          show_toast(error, $error_color);
          $btn.prop("disabled", false);
        },
      });
    });
  });
})(jQuery);