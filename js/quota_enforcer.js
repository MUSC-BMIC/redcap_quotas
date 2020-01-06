setTimeout(function() {
  $(function() {

    function enforceQuota(e) {
      $quota_met = false;
      $form_data = $('form').serialize() + '&event_id=' + event_id;

      $.get({
        url: quotaEnforcementSettings.url,
        async: false,
        data: $form_data,
        success: function(data) {
          quota_met = data;
          console.log(quota_met);

          $message = (quota_met == "true") ? quotaEnforcementSettings['rejected'] : quotaEnforcementSettings['accepted'];
          $("#quota-modal .modal-body").html($message);
          $('#quota-modal').modal('show');

          if (quota_met == 'true') {
            // Set quota_met_indicator to true
            $("#" + quotaEnforcementSettings['quota_met_indicator'] + "-tr .data :input").val(1);
          }
          else {
            // Set quota_met_indicator to false
            $("#" + quotaEnforcementSettings['quota_met_indicator'] + "-tr .data :input").val(0);
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