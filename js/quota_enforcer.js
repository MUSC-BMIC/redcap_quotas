setTimeout(function() {
  $(function() {

    function enforceQuota(e) {
      // $failed_data_check_count = false;
      $form_data = $('form').serialize() + '&event_id=' + event_id;

      $.get({
        url: quotaEnforcementSettings.url,
        async: false,
        data: $form_data,
        success: function(data) {
          data = JSON.parse(data);
          failed_data_check_count = data.failed_data_check_count;
          block_number = data.block_number;
          eligibility_message = data.eligibility_message;
          confirmed_enrollment = data.confirmed_enrollment;

          console.log(failed_data_check_count);
          console.log(block_number);
          console.log(confirmed_enrollment);

          //variable to check passed quota & confirmed_enrollment
          enrolled_yn = false;
          if((confirmed_enrollment == 1) || (confirmed_enrollment != 0 && !failed_data_check_count)){
            enrolled_yn = true;
          }

          //$message = eligibility_message ? quotaEnforcementSettings['eligibility_message'] : failed_data_check_count ? quotaEnforcementSettings['rejected'] : quotaEnforcementSettings['accepted'];
          $message = eligibility_message ? quotaEnforcementSettings['eligibility_message'] : enrolled_yn ? quotaEnforcementSettings['accepted'] : quotaEnforcementSettings['rejected'];
          $("#quota-modal .modal-body").html($message);
          $('#quota-modal').modal('show');

          if (failed_data_check_count) {
            // Set passed_quota_check to false
            $("#" + quotaEnforcementSettings['passed_quota_check'] + "-tr :input").val(0);
          }
          else {
            // Set passed_quota_check to true
            $("#" + quotaEnforcementSettings['passed_quota_check'] + "-tr :input").val(1);
          }

          // Set confirmed_enrollment variable
          if(confirmed_enrollment == 1) {
            $("#" + quotaEnforcementSettings['confirmed_enrollment'] + "-tr :input").val(1);
          }
          else if (confirmed_enrollment == 0){
            $("#" + quotaEnforcementSettings['confirmed_enrollment'] + "-tr :input").val(0);
          }


          $("#block_number-tr :input").val(block_number);

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

    var submitBtns = $("[id^=submit-btn-save], [name^=submit-btn-save]");

    submitBtns.prop("onclick", null).off("click");
    submitBtns.each((i, elt) => {
      elt.onclick = enforceQuota;
    });
  });
}, 0);
