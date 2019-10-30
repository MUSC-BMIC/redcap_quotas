$(document).ready(function() {
  
  $("#submit-btn-saverecord")[0].onclick = function(e) {
    $quota_met = false;
    $.ajaxSetup({
      async: false
    });
    $.get(quotaEnforcementSettings.url, $('form').serialize(), function(data) {
      quota_met = data;
      console.log(quota_met);
      if (quota_met == "true") {
        $quota_met = true;
        $('#quota-failure-modal').modal('show');
        //we want to save failures too, with a hidden field
      }
    });
    $.ajaxSetup({
      async: true
    });
    if ($quota_met == true) {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();
    }
    else {
      //stop the submit, show success modal, then proceed submitting
      dataEntrySubmit(this);
    }
  };
});
