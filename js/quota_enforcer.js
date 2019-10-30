$(document).ready(function() {

  $("#submit-btn-saverecord")[0].onclick = function(e) {
    $quota_met = false;
    $.ajaxSetup({
      async: false
    });
    $.get(quotaEnforcementSettings.url, $('form').serialize(), function(data) {
      quota_met = data;
      console.log(quota_met);

      $message = (quota_met == "true") ? quotaConfigMessages['rejected'] : quotaConfigMessages['accepted'];
      $("#quota-modal .modal-body").html($message.value);
      $('#quota-modal').modal('show');

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
