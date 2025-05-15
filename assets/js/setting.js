/*
 * @Author: Amirhossein Hosseinpour <https://amirhp.com>
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/05/15 13:46:46
 */

(function ($) {
  $(document).ready(function () {
    $("input.wpColorPicker").wpColorPicker();
    $("select").select2();
    reload_last_active_tab();
    setTimeout(function () { $("input[type=checkbox][data-ref]").trigger("refresh"); }, 200);
    setTimeout(reload_last_active_tab, 100);
    setTimeout(reload_last_active_tab, 500);
    setTimeout(reload_last_active_tab, 1000);

    // initiate repeater
    var $repeater = $(".repeater.translation-panel").repeater({
      hide: function (deleteElement) {
        $(this).remove();
        build_translation_data(".repeater.translation-panel", "#gettext_replace");
      },
    });
    var $str_replace = $(".repeater.str_replace-panel").repeater({
      hide: function (deleteElement) {
        $(this).remove();
        build_translation_data(".repeater.str_replace-panel", "#str_replace");
      },
    });

    // load repeater prev-data
    var json = $("#gettext_replace").val();
    try {
      var obj = JSON.parse(json);
      var list = new Array();
      if (obj.gettext) {
        $.each(obj.gettext, function (i, x) {
          list.push(x);
        });
        $repeater.setList(list);
      }
    } catch (e) {
      console.info("could not load translations repeater data");
    }

    // load repeater prev-data
    var json = $("#str_replace").val();
    try {
      var obj = JSON.parse(json);
      var list = new Array();
      if (obj.gettext) {
        $.each(obj.gettext, function (i, x) {
          list.push(x);
        });
        $str_replace.setList(list);
      }
    } catch (e) {
      console.info("could not load str_replace repeater data");
    }

    $(document).on("change keyup", ".repeater.translation-panel input", function (e) {
      e.preventDefault();
      build_translation_data(".repeater.translation-panel", "#gettext_replace");
    });
    $(document).on("change keyup", ".repeater.str_replace-panel input", function (e) {
      e.preventDefault();
      build_translation_data(".repeater.str_replace-panel", "#str_replace");
    });
    $(document).on("change refresh", "input[type=checkbox][data-ref]", function (e) {
      e.preventDefault();
      var me = $(this);
      if (me.prop("checked")) {
        $(me.data("ref")).show();
      } else {
        $(me.data("ref")).hide();
      }
    });

    $('.repeater.translation-panel table.wp-list-table').sortable({
      items: 'tr',
      cursor: 'move',
      axis: 'y',
      scrollSensitivity: 40,
      update: function (event, ui) {
        build_translation_data(".repeater.translation-panel", "#gettext_replace");
      },
      /* handle: 'td.wc-shipping-zone-method-sort', */
    });
    $('.repeater.str_replace-panel table.wp-list-table').sortable({
      items: 'tr',
      cursor: 'move',
      axis: 'y',
      scrollSensitivity: 40,
      update: function (event, ui) {
        build_translation_data(".repeater.str_replace-panel", "#str_replace");
      },
      /* handle: 'td.wc-shipping-zone-method-sort', */
    });

    $(document).on("click tap", ".woo-nav-tab-wrapper a.nav-tab", function (e) {
      e.preventDefault();
      var me = $(this);
      $(`.woo-nav-tab-wrapper .nav-tab.nav-tab-active`).removeClass("nav-tab-active");
      me.addClass("nav-tab-active");
      $(`form>.tab-content.tab-active`).removeClass("tab-active");
      $(`form>.tab-content[data-tab=${me.data("tab")}]`).addClass("tab-active");
      window.location.hash = me.data("tab");
      localStorage.setItem("gravity_otp_verification_", me.data("tab"));
    });

    function build_translation_data(container = ".repeater.translation-panel", data_inp = "#gettext_replace") {
      console.log(`build_translation_data ${container} ~> ${data_inp}`);
      try {
        var gettext = {
          "gettext": []
        };
        $(`${container} table tr[data-repeater-item]`).each(function (i, x) {
          var item = {};
          $(this).find("[data-slug]").each(function (indexInArray, valueOfElement) {
            let el = $(valueOfElement);
            slug = el.attr("data-slug");
            switch (el.attr("type")) {
              case "checkbox":
                val = el.prop("checked") ? "yes" : "no";
                break;
              default:
                val = el.val();
            }
            item[slug] = val;
          });
          gettext["gettext"][i] = item;
        });
        var jsonData = JSON.stringify(gettext);
        $(data_inp).val(jsonData).trigger("change");
      } catch (e) { }
    }

    function reload_last_active_tab() {
      if (window.location.hash && "" !== window.location.hash) {
        $(".nav-tab[data-tab=" + window.location.hash.replace("#", "") + "]").trigger("click");
      } else {
        // last = localStorage.getItem("gravity_otp_verification_");
        // if (last && "" != last) { $(".nav-tab[data-tab=" + last.replace("#", "") + "]").trigger("click"); }
      }
    }

    setTimeout(function () { $("#sms_gateway").trigger("refresh"); }, 100);

    $(document).on("change refresh", "#sms_gateway", function (e) {
      e.preventDefault();
      var me = $(this);
      $(".sms_gateways_helper>div").addClass("hide");
      $(".help-" + me.val()).removeClass("hide");
      $("tr.gateway_option_field").removeClass("hide");
      switch (me.val()) {
        case "sms_faraz":
          $("tr.gateway_option_field").addClass("hide");
          $("tr.api_server, tr.api_username, tr.api_password, tr.api_sender_number, tr.api_otp_sms").removeClass("hide");
          break;
        case "woo_sms":
          $("tr.gateway_option_field").addClass("hide");
          $("tr.api_otp_sms").removeClass("hide");
          break;
        case "sms_ir":
          $("tr.gateway_option_field").addClass("hide");
          $("tr.api_server, tr.api_username, tr.api_password, tr.api_sender_number, tr.api_otp_sms").removeClass("hide");
          break;
        case "sms_ir_v2":
          $("tr.gateway_option_field").addClass("hide");
          $("tr.api_server, tr.api_username, tr.api_sender_number, tr.api_otp_sms").removeClass("hide");
          break;
        default:

      }
    });

  });
})(jQuery);