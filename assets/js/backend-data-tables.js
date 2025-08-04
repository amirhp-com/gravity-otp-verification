/*
 * @Author: Amirhossein Hosseinpour <https://amirhp.com>
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/08/04 15:42:29
 */

(function ($) {
  $(document).ready(function () {
    var _ajax_req = null;
    var export_title = _i18n.str2,
      export_title_html = "<style>h1{text-align: center !important;}body{padding: 1rem;}</style><h2 align='center'>" + _i18n.str1 + "</span></h2>",
      export_subtitle = _i18n.str3,
      export_filename = _i18n.str4,
      export_excelsheetname = _i18n.str6,
      export_footer = _i18n.str5,
      export_footer_html = "<h2 align='center'>" + export_footer + "</h2>",
      errorTxt = _i18n.errorTxt,
      cancelTtl = _i18n.cancelTtl,
      confirmTxt = _i18n.confirmTxt,
      successTtl = _i18n.successTtl,
      submitTxt = _i18n.submitTxt,
      okTxt = _i18n.okTxt,
      closeTxt = _i18n.closeTxt,
      cancelbTn = _i18n.cancelbTn,
      sendTxt = _i18n.sendTxt,
      titleTx = _i18n.titleTx,
      expireNowE = _i18n.expireNowE,
      txtYes = _i18n.txtYes,
      txtNop = _i18n.txtNop;
    jconfirm.defaults = {
      title: '',
      titleClass: '',
      type: 'blue', // red green orange blue purple dark
      typeAnimated: true,
      draggable: true,
      dragWindowGap: 15,
      dragWindowBorder: true,
      animateFromElement: true,
      smoothContent: true,
      content: '',
      buttons: {},
      defaultButtons: {
        ok: {
          keys: ['enter'],
          text: okTxt,
          action: function () { }
        },
        close: {
          keys: ['enter'],
          text: closeTxt,
          action: function () { }
        },
        cancel: {
          keys: ['esc'],
          text: cancelbTn,
          action: function () { }
        },
      },
      contentLoaded: function (data, status, xhr) { },
      icon: '',
      lazyOpen: false,
      bgOpacity: null,
      theme: 'bootstrap',
      /*light dark supervan material bootstrap modern*/
      animation: 'scale',
      closeAnimation: 'scale',
      animationSpeed: 400,
      animationBounce: 1,
      rtl: $("body").is(".rtl") ? true : false,
      container: 'body',
      containerFluid: false,
      backgroundDismiss: false,
      backgroundDismissAnimation: 'shake',
      autoClose: false,
      closeIcon: null,
      closeIconClass: false,
      watchInterval: 100,
      columnClass: 'm',
      boxWidth: '500px',
      scrollToPreviousElement: true,
      scrollToPreviousElementAnimate: true,
      useBootstrap: false,
      offsetTop: 40,
      offsetBottom: 40,
      bootstrapClasses: {
        container: 'container',
        containerFluid: 'container-fluid',
        row: 'row',
      },
      onContentReady: function () { },
      onOpenBefore: function () { },
      onOpen: function () { },
      onClose: function () { },
      onDestroy: function () { },
      onAction: function () { },
      escapeKey: true,
    };
    if ($('#exported_data').length) {
      var table = $('#exported_data').DataTable({
        aaSorting: [[0, 'desc']],
        autoWidth: true,
        language: {
          emptyTable: _i18n.tbl1,
          info: _i18n.tbl2,
          infoEmpty: _i18n.tbl3,
          infoFiltered: _i18n.tbl4,
          infoPostFix: "",
          thousands: ",",
          lengthMenu: _i18n.tbl5,
          loadingRecords: _i18n.tbl6,
          processing: _i18n.tbl7,
          search: _i18n.tbl8,
          zeroRecords: _i18n.tbl9,
          paginate: {
            first: _i18n.tbl10,
            last: _i18n.tbl11,
            next: _i18n.tbl12,
            previous: _i18n.tbl13,
          },
          aria: {
            sortAscending: _i18n.tbl14,
            sortDescending: _i18n.tbl15,
          },
        },
        select: true,
        paging: false,
        fixedHeader: {
          headerOffset: $('#wpadminbar').outerHeight()
        },
        responsive: { details: { type: "inline", } },
        searchHighlight: true,
        columnDefs: [{ type: 'html-num', targets: [0] }],
        dom: '<"dt-filter-container"fB>rtip',
        fnInitComplete: function (oSettings, json) {
          $(".dt-button.buttons-excel").prepend('<i style="margin-inline-end: 4px;" class="fa fa-file-excel"></i>');
          $(".dt-button.buttons-csv").prepend('<i style="margin-inline-end: 4px;" class="fa fa-file-csv"></i>');
          $(".dt-button.buttons-copy").prepend('<i style="margin-inline-end: 4px;" class="fa fa-copy"></i>');
          $(".dt-button.buttons-print").prepend('<i style="margin-inline-end: 4px;" class="fa fa-print"></i>');
          $(".dt-button.buttons-collection").prepend('<i style="margin-inline-end: 4px;" class="fa fa-columns"></i>');
          $("#exported_data_filter label").css("font-size", "0");
          $("#exported_data_filter input").addClass("form-control").attr("placeholder", $("#exported_data_filter").text().replace(":", " ..."));
          this.api().columns().every(function () {
            var column = this;
            if ($(column.footer()).is(".item_th_log_name") ||
              $(column.footer()).is(".item_th_log_category") ||
              $(column.footer()).is(".item_th_log_creator") ||
              $(column.footer()).is(".item_th_log_user")) {
              var select = $(`<select><option value="">${_i18n.filter}</option></select>`).appendTo($(column.footer()).empty()).on('change', function () {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                column.search(val ? '^' + val + '$' : '', true, false).draw();
              });
              column.data().unique().sort().each(function (d, j) {
                var jj = d.replace(/&/g, "&").replace(/</g, "<").replace(/>/g, ">").replace(/"/g, '"').replace(/'/g, "'");
                var dd = $("body").append("<dummy>");
                var ddd = $("body dummy").html(d).text();
                $("body dummy").remove();
                select.append('<option value="' + ddd + '">' + d + '</option>');
              });
            }
          });
        },
        buttons: [
          {
            extend: 'excel',
            text: _i18n.tbl19,
            title: export_title,
            footer: false,
            header: true,
            sheetName: export_excelsheetname,
            messageTop: _i18n.str1,
            messageBottom: export_footer,
            filename: export_filename,
            exportOptions: {
              columns: "thead th:not(.noExport)"
            },
          },
          {
            extend: 'csv',
            text: _i18n.tbl18,
            bom: true,
            filename: export_filename,
            exportOptions: {
              columns: "thead th:not(.noExport)"
            },
          },
          {
            extend: 'copy',
            footer: false,
            header: true,
            messageTop: export_title,
            title: _i18n.str1,
            messageBottom: export_footer,
            text: _i18n.tbl16,
            exportOptions: {
              columns: "thead th:not(.noExport)"
            },
          },
          {
            extend: 'print',
            text: _i18n.tbl17,
            title: export_title,
            footer: false,
            header: true,
            messageTop: export_title_html,
            messageBottom: export_footer_html + `<style>h1, h2, h3 { font-size: 1.05rem; }</style>`,
            autoPrint: false,
            exportOptions: {
              columns: "thead th:not(.noExport)"
            },
          },
          { extend: 'colvis', text: _i18n.tbl177, },
        ]
      });
      table.on('draw', function () {
        var body = $(table.table().body());
        body.unhighlight();
        body.highlight(table.search());
      });
      table.on('click', 'td.dtr-control', function (e) {
         tippy('[data-tippy-content]:not([data-tippy-content=""])', { allowHTML: true, });
      });
    }

    var $success_color = "rgba(21, 139, 2, 0.8)";
    var $error_color = "rgba(139, 2, 2, 0.8)";
    var $info_color = "rgba(2, 133, 139, 0.8)";
    if (!$("toast").length) { $(document.body).append($("<toast>")); }
    if ($("#search_result_content_empty").val() == "yes") {
      $("#advanced_search").addClass("has-result");
      // $("#search_form").removeClass("closed")
    }
    $(document).on('change', '#itemsperpagedisplay', function () {
      $("form#mainform").submit();
    });

    $(document).on("click tap", ".dataTable .edit--entry", function (e) {
      e.preventDefault();
      var me = $(this);
      var id = me.data("id");
      var json = $("tr.item_tr_" + id).data("json");
      $("tr.item_tr_" + id).addClass("highlight");
      var jc = $.confirm({
        title: _i18n.edit,
        content: $("#add_new_db_entry").html(),
        onContentReady: function () {
          var form = this.$content.find(".add_db_entry_form");
          $.each(json, function (id, val) {
            id = String(id);
            if ($(form).find(`.${id}`).length) {
              $(form).find(`.${id}`).val(val).trigger("change");
            }
          });
        },
        boxWidth: "400px",
        type: "purple",
        icon: "fas fas fa-pen",
        closeIcon: false,
        animation: "scale",
        buttons: {
          no: {
            text: cancelbTn,
            btnClass: "btn-gray",
            keys: ["esc"],
            action: function () {
              $("tr.item_tr_" + id).removeClass("highlight");
              jc.close();
              return true;
            }
          },
          yes: {
            text: submitTxt,
            btnClass: "btn-purple",
            keys: ["enter"],
            action: function () {
              var form = this.$content.find(".add_db_entry_form");
              var formData = $(form).serializeArray();
              $(".jconfirm-closeIcon").hide();
              jc.showLoading(true);
              $.ajax({
                type: 'POST',
                dataType: "json",
                url: _i18n.ajax,
                data: {
                  action: _i18n.td,
                  nonce: _i18n.nonce,
                  table: _i18n.table,
                  wparam: _i18n.wparam,
                  id: id,
                  lparam: "edit_entry",
                  formData: formData,
                },
                success: function (result) {
                  jc.close();
                  if (result.success === true) {
                    show_modal_alert(_i18n.successTtl, result.data.msg, 'fas fa-check-circle', 'green');
                    $(`#exported_data tbody tr.item_tr_${id}`).addClass("modified");
                  }
                  else {
                    show_modal_alert(_i18n.errorTxt, result.data.msg, 'fas fa-exclamation-triangle', 'red');
                  }
                },
                error: function (result) {
                  jc.close();
                  console.error(result);
                  show_modal_alert(_i18n.errorTxt, _i18n.UnknownErr, 'fas fa-check-circle', 'green');
                },
                complete: function (result) {
                  $("tr.item_tr_" + id).removeClass("highlight");
                  jc.close();
                }
              });
              return false;
            }
          },
        },
      });
    });
    $(document).on("click tap", ".dataTable .raw--entry", function (e) {
      e.preventDefault();
      var me = $(this);
      var id = me.data("id");
      show_modal_alert(false, $("tr.item_tr_" + id).data("json").log_action, "", "dark", "900px");
    });
    $(document).on("click tap", ".dataTable .view-in-popup", function (e) {
      e.preventDefault();
      var me = $(this);
      var id = me.data("ref");
      show_modal_alert("", $($("pre#" + id).prop("outerHTML")).show(), "", "dark", "700px");
    });
    $(document).on("click tap", ".dataTable .delete--entry", function (e) {
      e.preventDefault();
      var me = $(this);
      var id = me.data("id");
      $("tr.item_tr_" + id).addClass("highlight");
      var jc = $.confirm({
        title: `${_i18n.deleteConfirmTitle}`,
        content: _i18n.deleteConfirmation.replace("%s", `<u><strong>${id}</strong></u>`),
        icon: 'fas fa-trash-alt',
        closeIcon: 0,
        type: "red",
        boxWidth: "600px",
        onContentReady: function () { },
        buttons: {
          no: {
            text: _i18n.txtNop,
            btnClass: 'btn-red',
            keys: ['esc'],
            action: function () {
              $("tr.item_tr_" + id).removeClass("highlight");
              jc.close();
              return true;
            }
          },
          yes: {
            text: _i18n.txtYes,
            btnClass: 'btn-red',
            keys: ['enter'],
            action: function () {
              jc.showLoading(true);
              if (_ajax_req != null) { _ajax_req.abort(); }
              _ajax_req = $.ajax({
                type: "POST",
                dataType: "json",
                url: _i18n.ajax,
                data: {
                  action: _i18n.td,
                  nonce: _i18n.nonce,
                  wparam: _i18n.wparam,
                  table: _i18n.table,
                  lparam: "delete_item",
                  dparam: id,
                },
                success: function (result) {
                  if (result.success === true) {
                    $(".dataTable tr.child, .dataTable tr.item_tr_" + id).remove();
                    show_modal_alert(_i18n.successTtl, result.data.msg, 'fas fa-check-circle', 'green');
                  } else {
                    show_modal_alert(_i18n.errorTxt, result.data.msg, 'fas fa-exclamation-triangle', 'red');
                  }
                },
                error: function (result) {
                  show_modal_alert(_i18n.errorTxt, _i18n.error, 'fas fa-exclamation-triangle', 'red');
                },
                complete: function (result) {
                  $("tr.item_tr_" + id).removeClass("highlight");
                  jc.close();
                },
              });
              return false;
            }
          },
        }
      });
    });
    $(document).on("click tap", ".dataTable .approve--entry", function (e) {
      e.preventDefault();
      var me = $(this), id = me.data("id"), amount = me.data("amount"), user_id = me.data("user_id"); $("tr.item_tr_" + id).addClass("highlight");
      var jc = $.confirm({
        title: _i18n.set_approved,
        content: _i18n.wait,
        icon: "fas fa-cog",
        closeIcon: 0,
        type: "blue",
        boxWidth: "400px",
        onContentReady: function () {
          jc.showLoading(true);
          if (_ajax_req != null) { _ajax_req.abort(); }
          _ajax_req = $.ajax({
            type: "POST",
            dataType: "json",
            url: _i18n.ajax,
            data: {
              action: _i18n.td,
              nonce: _i18n.nonce,
              wparam: _i18n.wparam,
              table: _i18n.table,
              lparam: "set_approved",
              amount: amount,
              user_id: user_id,
              dparam: id,
            },
            success: function (result) {
              if (result.success === true) {
                show_modal_alert(_i18n.successTtl, result.data.msg, 'fas fa-check-circle', 'green');
              } else {
                show_modal_alert(_i18n.errorTxt, result.data.msg, 'fas fa-exclamation-triangle', 'red');
              }
            },
            error: function (result) {
              show_modal_alert(_i18n.errorTxt, _i18n.error, 'fas fa-exclamation-triangle', 'red');
            },
            complete: function (result) {
              $("tr.item_tr_" + id).removeClass("highlight");
              jc.close();
            },
          });
          return false;
        },
        buttons: false,
      });
    });
    $(document).on("click tap", ".dataTable .pend--entry", function (e) {
      e.preventDefault();
      var me = $(this), id = me.data("id"); $("tr.item_tr_" + id).addClass("highlight");
      var jc = $.confirm({
        title: _i18n.set_pending,
        content: _i18n.wait,
        icon: "fas fa-cog",
        closeIcon: 0,
        type: "blue",
        boxWidth: "400px",
        onContentReady: function () {
          jc.showLoading(true);
          if (_ajax_req != null) { _ajax_req.abort(); }
          _ajax_req = $.ajax({
            type: "POST",
            dataType: "json",
            url: _i18n.ajax,
            data: {
              action: _i18n.td,
              nonce: _i18n.nonce,
              wparam: _i18n.wparam,
              table: _i18n.table,
              lparam: "set_pending",
              dparam: id,
            },
            success: function (result) {
              if (result.success === true) {
                show_modal_alert(_i18n.successTtl, result.data.msg, 'fas fa-check-circle', 'green');
              } else {
                show_modal_alert(_i18n.errorTxt, result.data.msg, 'fas fa-exclamation-triangle', 'red');
              }
            },
            error: function (result) {
              show_modal_alert(_i18n.errorTxt, _i18n.error, 'fas fa-exclamation-triangle', 'red');
            },
            complete: function (result) {
              $("tr.item_tr_" + id).removeClass("highlight");
              jc.close();
            },
          });
          return false;
        },
        buttons: false,
      });
    });
    $(document).on("click tap", ".dataTable .reject--entry", function (e) {
      e.preventDefault();
      var me = $(this), id = me.data("id"); $("tr.item_tr_" + id).addClass("highlight");
      var jc = $.confirm({
        title: _i18n.set_rejected,
        content: _i18n.wait,
        icon: "fas fa-cog",
        closeIcon: 0,
        type: "blue",
        boxWidth: "400px",
        onContentReady: function () {
          jc.showLoading(true);
          if (_ajax_req != null) { _ajax_req.abort(); }
          _ajax_req = $.ajax({
            type: "POST",
            dataType: "json",
            url: _i18n.ajax,
            data: {
              action: _i18n.td,
              nonce: _i18n.nonce,
              wparam: _i18n.wparam,
              table: _i18n.table,
              lparam: "set_rejected",
              dparam: id,
            },
            success: function (result) {
              if (result.success === true) {
                show_modal_alert(_i18n.successTtl, result.data.msg, 'fas fa-check-circle', 'green');
              } else {
                show_modal_alert(_i18n.errorTxt, result.data.msg, 'fas fa-exclamation-triangle', 'red');
              }
            },
            error: function (result) {
              show_modal_alert(_i18n.errorTxt, _i18n.error, 'fas fa-exclamation-triangle', 'red');
            },
            complete: function (result) {
              $("tr.item_tr_" + id).removeClass("highlight");
              jc.close();
            },
          });
          return false;
        },
        buttons: false,
      });
    });
    $(document).on("click tap", ".dataTable .increase--wallet", function (e) {
      e.preventDefault();
      var me = $(this), id = me.data("id"), amount = me.data("amount"), user_id = me.data("user_id"); $("tr.item_tr_" + id).addClass("highlight");
      var jc = $.confirm({
        title: _i18n.increase_wallet,
        content: _i18n.wait,
        icon: "fas fa-cog",
        closeIcon: 0,
        type: "blue",
        boxWidth: "400px",
        onContentReady: function () {
          jc.showLoading(true);
          if (_ajax_req != null) { _ajax_req.abort(); }
          _ajax_req = $.ajax({
            type: "POST",
            dataType: "json",
            url: _i18n.ajax,
            data: {
              action: _i18n.td,
              nonce: _i18n.nonce,
              wparam: _i18n.wparam,
              table: _i18n.table,
              lparam: "increase_wallet",
              amount: amount,
              user_id: user_id,
              dparam: id,
            },
            success: function (result) {
              if (result.success === true) {
                show_modal_alert(_i18n.successTtl, result.data.msg, 'fas fa-check-circle', 'green');
              } else {
                show_modal_alert(_i18n.errorTxt, result.data.msg, 'fas fa-exclamation-triangle', 'red');
                return false;
              }
            },
            error: function (result) {
              show_modal_alert(_i18n.errorTxt, _i18n.error, 'fas fa-exclamation-triangle', 'red');
              return false;
            },
            complete: function (result) {
              $("tr.item_tr_" + id).removeClass("highlight");
              jc.close();
            },
          });
          return false;
        },
        buttons: false,
      });
    });
    $(document).on("click tap", ".dataTable .decrease--wallet", function (e) {
      e.preventDefault();
      var me = $(this), id = me.data("id"), amount = me.data("amount"), user_id = me.data("user_id"); $("tr.item_tr_" + id).addClass("highlight");
      var jc = $.confirm({
        title: _i18n.decrease_wallet,
        content: _i18n.wait,
        icon: "fas fa-cog",
        closeIcon: 0,
        type: "blue",
        boxWidth: "400px",
        onContentReady: function () {
          jc.showLoading(true);
          if (_ajax_req != null) { _ajax_req.abort(); }
          _ajax_req = $.ajax({
            type: "POST",
            dataType: "json",
            url: _i18n.ajax,
            data: {
              action: _i18n.td,
              nonce: _i18n.nonce,
              wparam: _i18n.wparam,
              table: _i18n.table,
              lparam: "decrease_wallet",
              amount: amount,
              user_id: user_id,
              dparam: id,
            },
            success: function (result) {
              if (result.success === true) {
                show_modal_alert(_i18n.successTtl, result.data.msg, 'fas fa-check-circle', 'green');
              } else {
                show_modal_alert(_i18n.errorTxt, result.data.msg, 'fas fa-exclamation-triangle', 'red');
              }
            },
            error: function (result) {
              show_modal_alert(_i18n.errorTxt, _i18n.error, 'fas fa-exclamation-triangle', 'red');
            },
            complete: function (result) {
              $("tr.item_tr_" + id).removeClass("highlight");
              jc.close();
            },
          });
          return false;
        },
        buttons: false,
      });
    });
    $(document).on("click tap", "#add_new", function (e) {
      e.preventDefault();
      var me = $(this);
      var jc = $.confirm({
        title: _i18n.addnew,
        content: $("#add_new_db_entry").html(),
        onContentReady: function () {},
        boxWidth: "400px",
        type: "purple",
        icon: "fas fa-add-circle",
        closeIcon: false,
        animation: "scale",
        buttons: {
          no: {
            text: cancelbTn,
            btnClass: "btn-gray",
            keys: ["esc"],
            action: function () {
              jc.close();
              return true;
            }
          },
          yes: {
            text: submitTxt,
            btnClass: "btn-purple",
            keys: ["enter"],
            action: function () {
              var form = this.$content.find(".add_db_entry_form");
              var formData = $(form).serializeArray();
              $(".jconfirm-closeIcon").hide();
              jc.showLoading(true);
              $.ajax({
                type: 'POST',
                dataType: "json",
                url: _i18n.ajax,
                data: {
                  action: _i18n.td,
                  nonce: _i18n.nonce,
                  table: _i18n.table,
                  wparam: _i18n.wparam,
                  lparam: "edit_entry",
                  formData: formData,
                },
                success: function (result) {
                  jc.close();
                  if (result.success === true) {
                    show_modal_alert(_i18n.successTtl, result.data.msg, 'fas fa-check-circle', 'green');
                  }
                  else {
                    show_modal_alert(_i18n.errorTxt, result.data.msg, 'fas fa-exclamation-triangle', 'red');
                  }
                },
                error: function (result) {
                  jc.close();
                  console.error(result);
                  show_modal_alert(_i18n.successTtl, _i18n.UnknownErr, 'fas fa-check-circle', 'green');
                },
                complete: function (result) {
                  jc.close();
                }
              });
              return false;
            }
          },
        },
      });
    });

    $(document).on("click tap", "a[href='#advanced_search']", function (e) {
      e.preventDefault();
      $("#search_form").toggleClass("closed");
    });
    $(document).on("click tap", "a[href='#close_form_search']", function (e) {
      e.preventDefault();
      $("#search_form").addClass("closed");
    });
    $(document).on("click tap", "a[href='#submit_form_search']", function (e) {
      e.preventDefault();
      $("#search_form form").submit();
    });
    $(document).on("click tap", "td.item_td_log_description", function (e) {
      e.preventDefault();
      var json = $(this).parents("tr").first().data("json");
      $.alert(json.log_description);
    });

    $(document).on("click tap", "#select_all", function (e) {
      e.preventDefault();
      if ($("input[name*=selected_id]").length) {
        var status = $("input[name*=selected_id]").first().prop("checked");
        var ids = $("input[name*=selected_id]").prop("checked", !status).trigger("change");
      } else {
        show_toast("No item found to select.", $error_color);
      }
    });
    $(document).on("click tap", "#delete_selected", function (e) {
      e.preventDefault();
      let me = $(this);
      var ids_array = [];
      var ids = $("input[name*=selected_id]:checked");
      if (ids.length < 1) { show_toast(_i18n.nosel, $error_color); return false; }
      $(`tr.highlight`).removeClass("highlight");
      $.each(ids, function (index, val) {
        ids_array.push($(val).val());
        $(`tr.item_tr_${$(val).val()}`).addClass("highlight");
      });
      var jc = $.confirm({
        title: _i18n.deleteConfirmTitle,
        content: _i18n.deleteConfirmation.replace("%s", `${ids_array.join(", ")}`),
        boxWidth: '600px',
        icon: 'fas fa-trash-alt',
        type: "red",
        closeIcon: false,
        animation: 'scale',
        buttons: {
          no: {
            text: txtNop,
            btnClass: 'btn-red',
            keys: ['n', 'esc'],
            action: function () {
              $(`tr.highlight`).removeClass("highlight");
            }
          },
          yes: {
            text: txtYes,
            btnClass: 'btn-red',
            keys: ['y', 'enter'],
            action: function () {
              $(".jconfirm-closeIcon").hide();
              jc.showLoading(true);
              jc.setBoxWidth("400px");
              $.ajax({
                type: 'POST',
                dataType: "json",
                url: _i18n.ajax,
                data: {
                  action: _i18n.td,
                  nonce: _i18n.nonce,
                  wparam: _i18n.wparam,
                  table: _i18n.table,
                  lparam: "delete_item_array",
                  dparam: ids_array,
                },
                success: function (result) {
                  jc.close();
                  if (result.success === true) {
                    $.each(ids_array, function (index, val) { $(`#exported_data .item_tr_${val}`).remove(); });
                    $.confirm({
                      title: successTtl,
                      content: result.data.msg,
                      icon: 'fas fa-check-circle',
                      type: 'green',
                      boxWidth: '400px',
                      buttons: {
                        close: {
                          text: closeTxt,
                          keys: ['enter', 'esc'],
                          action: function () { }
                        }
                      }
                    });
                  } else {
                    $.confirm({
                      title: errorTxt,
                      content: result.data.msg,
                      icon: 'fa fa-exclamation-triangle',
                      type: 'red',
                      boxWidth: '400px',
                      buttons: {
                        close: {
                          text: closeTxt,
                          keys: ['enter', 'esc'],
                          action: function () { }
                        }
                      }
                    });
                  }
                },
                error: function (result) {
                  jc.close();
                  $.confirm({
                    title: errorTxt,
                    content: _i18n.UnknownErr,
                    icon: 'fa fa-exclamation-triangle',
                    type: 'red',
                    boxWidth: '400px',
                    buttons: {
                      close: {
                        text: closeTxt,
                        keys: ['enter', 'esc'],
                        action: function () { }
                      }
                    }
                  });
                },
                complete: function (result) {
                  $(`tr.highlight`).removeClass("highlight");
                }
              });
              return false;
            }
          },
        },
      });
    });
    $(document).on("click tap", "copy", function (e) {
      e.preventDefault();
      var me = $(this);
      copy_clipboard(me.text());
      show_toast(_i18n.copied, $success_color);
    });
    $(document).on("click tap", "#empty_db_truncate", function (e) {
      e.preventDefault();
      var me = $(this);
      var jc = $.confirm({
        title: _i18n.clearDBConfTitle,
        content: _i18n.clearDBConfirmation,
        boxWidth: '600px',
        icon: 'fas fa-trash-alt',
        closeIcon: false,
        type: "red",
        animation: 'scale',
        buttons: {
          no: {
            text: txtNop,
            btnClass: 'btn-red',
            keys: ['n', 'esc'],
            action: function () { }
          },
          yes: {
            text: txtYes,
            btnClass: 'btn-red',
            keys: ['y', 'enter'],
            action: function () {
              $(".jconfirm-closeIcon").hide();
              jc.showLoading(true);
              jc.setBoxWidth("400px");
              $.ajax({
                type: 'POST',
                dataType: "json",
                url: _i18n.ajax,
                data: {
                  action: _i18n.td,
                  nonce: _i18n.nonce,
                  table: _i18n.table,
                  wparam: _i18n.wparam,
                  lparam: "clear_db",
                },
                success: function (result) {
                  jc.close();
                  if (result.success === true) {
                    $.confirm({
                      title: successTtl,
                      content: result.data.msg,
                      icon: 'fas fa-check-circle',
                      type: 'green',
                      boxWidth: '400px',
                      buttons: {
                        close: {
                          text: closeTxt,
                          keys: ['enter', 'esc'],
                          action: function () {
                            window.location.href = window.location.href;
                          }
                        }
                      }
                    });
                  } else {
                    $.confirm({
                      title: errorTxt,
                      content: result.data.msg,
                      icon: 'fa fa-exclamation-triangle',
                      type: 'red',
                      boxWidth: '400px',
                      buttons: {
                        close: {
                          text: closeTxt,
                          keys: ['enter', 'esc'],
                          action: function () { }
                        }
                      }
                    });
                  }
                },
                error: function (e) {
                  console.error(e);
                  jc.close();
                  $.confirm({
                    title: errorTxt,
                    content: _i18n.UnknownErr,
                    icon: 'fa fa-exclamation-triangle',
                    type: 'red',
                    boxWidth: '400px',
                    buttons: {
                      close: {
                        text: closeTxt,
                        keys: ['enter', 'esc'],
                        action: function () { }
                      }
                    }
                  });
                },
                complete: function (e) { },
              });
              return false;
            }
          },
        }
      });
    });
    $(document).on("click tap", "#rest_settings_all", function (e) {
      e.preventDefault();
      var me = $(this);
      var jc = $.confirm({
        title: _i18n.clearResetSettings,
        content: _i18n.clearResetSettiConfrm,
        boxWidth: '600px',
        icon: 'fas fa-trash-alt',
        closeIcon: false,
        type: "red",
        animation: 'scale',
        buttons: {
          no: {
            text: txtNop,
            btnClass: 'btn-red',
            keys: ['n', 'esc'],
            action: function () { }
          },
          yes: {
            text: txtYes,
            btnClass: 'btn-red',
            keys: ['y', 'enter'],
            action: function () {
              $(".jconfirm-closeIcon").hide();
              jc.showLoading(true);
              jc.setBoxWidth("400px");
              $.ajax({
                type: 'POST',
                dataType: "json",
                url: _i18n.ajax,
                data: {
                  action: _i18n.td,
                  nonce: _i18n.nonce,
                  wparam: _i18n.wparam,
                  lparam: "clear_settings",
                },
                success: function (result) {
                  jc.close();
                  if (result.success === true) {
                    $.confirm({
                      title: successTtl,
                      content: result.data.msg,
                      icon: 'fas fa-check-circle',
                      type: 'green',
                      boxWidth: '400px',
                      buttons: {
                        close: {
                          text: closeTxt,
                          keys: ['enter', 'esc'],
                          action: function () {
                            window.location.href = _i18n.href;
                          }
                        }
                      }
                    });
                  } else {
                    $.confirm({
                      title: errorTxt,
                      content: result.data.msg,
                      icon: 'fa fa-exclamation-triangle',
                      type: 'red',
                      boxWidth: '400px',
                      buttons: {
                        close: {
                          text: closeTxt,
                          keys: ['enter', 'esc'],
                          action: function () { }
                        }
                      }
                    });
                  }
                },
                error: function (e) {
                  console.error(e);
                  jc.close();
                  $.confirm({
                    title: errorTxt,
                    content: _i18n.UnknownErr,
                    icon: 'fa fa-exclamation-triangle',
                    type: 'red',
                    boxWidth: '400px',
                    buttons: {
                      close: {
                        text: closeTxt,
                        keys: ['enter', 'esc'],
                        action: function () { }
                      }
                    }
                  });
                },
                complete: function (e) { },
              });
              return false;
            }
          },
        }
      });
    });
    $(document).on("click tap", "#force_db_create", function (e) {
      e.preventDefault();
      var me = $(this);
      var jc = $.confirm({
        title: _i18n.fixDBConfTitle,
        content: _i18n.fixDBConfirmation,
        boxWidth: '600px',
        icon: 'fas fa-wrench',
        closeIcon: false,
        type: "purple",
        animation: 'scale',
        buttons: {
          no: {
            text: txtNop,
            btnClass: 'btn-purple',
            keys: ['n', 'esc'],
            action: function () { }
          },
          yes: {
            text: txtYes,
            btnClass: 'btn-purple',
            keys: ['y', 'enter'],
            action: function () {
              $(".jconfirm-closeIcon").hide();
              jc.showLoading(true);
              jc.setBoxWidth("400px");
              $.ajax({
                type: 'POST',
                dataType: "json",
                url: _i18n.ajax,
                data: {
                  action: _i18n.td,
                  nonce: _i18n.nonce,
                  wparam: _i18n.wparam,
                  table: _i18n.table,
                  lparam: "db_recreate",
                },
                success: function (result) {
                  jc.close();
                  if (result.success === true) {
                    $.confirm({
                      title: successTtl,
                      content: result.data.msg,
                      icon: 'fas fa-check-circle',
                      type: 'green',
                      boxWidth: '400px',
                      buttons: {
                        close: {
                          text: closeTxt,
                          keys: ['enter', 'esc'],
                          action: function () {
                            window.location.href = window.location.href;
                          }
                        }
                      }
                    });
                  } else {
                    $.confirm({
                      title: errorTxt,
                      content: result.data.msg,
                      icon: 'fa fa-exclamation-triangle',
                      type: 'red',
                      boxWidth: '400px',
                      buttons: {
                        close: {
                          text: closeTxt,
                          keys: ['enter', 'esc'],
                          action: function () { }
                        }
                      }
                    });
                  }
                },
                error: function (e) {
                  console.error(e);
                  jc.close();
                  $.confirm({
                    title: errorTxt,
                    content: _i18n.UnknownErr,
                    icon: 'fa fa-exclamation-triangle',
                    type: 'red',
                    boxWidth: '400px',
                    buttons: {
                      close: {
                        text: closeTxt,
                        keys: ['enter', 'esc'],
                        action: function () { }
                      }
                    }
                  });
                },
                complete: function (e) { },
              });
              return false;
            }
          },
        }
      });
    });

    function show_toast(data = "Sample Toast!", bg = "", delay = 4500) {
      if (!$("toast").length) { $(document.body).append($("<toast>")); } else { $("toast").removeClass("active"); }
      setTimeout(function () {
        $("toast").css("--toast-bg", bg).html(data).stop().addClass("active").delay(delay).queue(function () {
          $(this).removeClass("active").dequeue().off("click tap");
        }).on("click tap", function (e) { e.preventDefault(); $(this).stop().removeClass("active"); });
      }, 200);
    }
    function copy_clipboard(data) {
      var $temp = $("<input>");
      $("body").append($temp);
      $temp.val(data).select();
      document.execCommand("copy");
      $temp.remove();
    }
    function show_modal_alert(title = "", content = "", icon = "fas fa-info-circle", type = "blue", boxWidth = "400px", $fn = null, theme = "modern") {
      $.confirm({
        title: title,
        content: content,
        icon: icon,
        /* light dark supervan material bootstrap modern */
        theme: theme,
        type: type,
        boxWidth: boxWidth,
        buttons: {
          close: {
            btnClass: "btn-" + type,
            text: _i18n.closeTxt,
            keys: ["enter", "esc"],
            action: $fn
          }
        },
      });
    }
    function scroll(e, of = 0) {
      $('html, body').animate({
        scrollTop: e.offset().top - of
      }, 500);
    }
  });
})(jQuery);
