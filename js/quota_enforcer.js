$(document).ready(function() {

  $("#submit-btn-saverecord")[0].onclick = function(e) {
    $quota_met = false;
    $.ajaxSetup({
      async: false
    });

    $form_data = $('form').serialize() + '&event_id=' + event_id;

    $.get(quotaEnforcementSettings.url, $form_data, function(data) {
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

    });

    $.ajaxSetup({
      async: true
    });
  };

  $("#btnCloseCodesModalDelete")[0].onclick = function(e){
    dataEntrySubmit(this);
  }
});
