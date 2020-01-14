setTimeout(function() {
  $(function() {

    function enforceQuota(e) {
      $failed_quota_check = false;
      $form_data = $('form').serialize() + '&event_id=' + event_id;

      $.get({
        url: quotaEnforcementSettings.url,
        async: false,
        data: $form_data,
        success: function(data) {
          failed_quota_check = data;
          console.log(failed_quota_check);

          $message = (failed_quota_check == "true") ? quotaEnforcementSettings['rejected'] : quotaEnforcementSettings['accepted'];
          $("#quota-modal .modal-body").html($message);
          $('#quota-modal').modal('show');

          if (failed_quota_check == 'true') {
            // Set passed_quota_check to false
            $("#" + quotaEnforcementSettings['passed_quota_check'] + "-tr .data :input").val(0);
          }
          else {
            // Set passed_quota_check to true
            $("#" + quotaEnforcementSettings['passed_quota_check'] + "-tr .data :input").val(1);
          }

          e.preventDefault();
          e.stopPropagation();
          e.stopImmediatePropagation();

          $('#quota-modal').off('hidden.bs.modal');
          $('#quota-modal').on('hidden.bs.modal', function (e2) {
            dataEntrySubmit(e.target.id);
          });
        }
      });
    }

    var submitBtns = $("[id^=submit-btn-save]");

    submitBtns.prop("onclick", null).off("click");
    submitBtns.each((i, elt) => {
      elt.onclick = enforceQuota;
    });
  });
}, 0);
