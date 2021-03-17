setTimeout(function() {
  $(function() {

    function quotaCheck_identifyDuplicates(e) {
      $is_duplicate = false;
      $form_data = $('form').serialize() + '&event_id=' + event_id;

      //Reaches here only if quota_config & cheat_blocker modules are enabled
      $.get({
        url: quotaEnforcementSettings.url,
        async: false,
        data: $form_data,
        success: function(data) {
          data = JSON.parse(data);

          //data from quota_config module
          failed_data_count_check = data.failed_data_check_count;
          block_number = data.block_number;
          quota_eligibility_message = data.eligibility_message;
          participant_enrolled = data.participant_enrolled;

          //data from cheat_blocker module
          is_duplicate = data.is_duplicate;
          automatic_duplicate_check = data.automatic_duplicate_check;
          cheat_eligibility_message = data.cheat_eligibility_message;
          potential_duplicate_message = data.potential_duplicate_message;
          potential_duplicate_record_ids = data.potential_duplicate_record_ids;
          potential_failed_criteria = data.potential_failed_criteria;
          duplicate_record_ids = data.duplicate_record_ids;
          failed_criteria = data.failed_criteria;
          duplicates_count = data.duplicates_count;
          data_entry_time = data.data_entry_time;

          console.log(data);

          //Checking all the different scenarios from quota config & cheat blocker modules
          //Potential duplicate message shows up only for delayed enrollment of cheat blocker plugin
          //If delayed enrollment is enabled in one module or both, then show eligibility message
          if(potential_duplicate_message){
            $message = cheatSettings['potential_duplicate_message'];
          }
          else if(quota_eligibility_message || cheat_eligibility_message){
            $message = quotaEnforcementSettings['eligibility_message'];
          }
          else if (failed_data_count_check || is_duplicate){
            $message = quotaEnforcementSettings['rejected'];
          }
          else{
            $message = quotaEnforcementSettings['accepted'];
          }

          $("#quota-modal .modal-body").html($message);
          $('#quota-modal').modal('show');

          //save quota_config data
          if (failed_data_count_check) {
            $("#" + quotaEnforcementSettings['passed_quota_check'] + "-tr :input").val(0);// Set passed_quota_check to false
          }
          else {
            $("#" + quotaEnforcementSettings['passed_quota_check'] + "-tr :input").val(1);// Set passed_quota_check to true
          }

          // Set confirmed_enrollment based on quota met and participant_enrolled
          if(!failed_data_count_check && participant_enrolled) {
            $("#" + quotaEnforcementSettings['confirmed_enrollment'] + "-tr :input").val(1);
          }
          else if (failed_data_count_check || participant_enrolled == 0){
            $("#" + quotaEnforcementSettings['confirmed_enrollment'] + "-tr :input").val(0);
          }

          $("#block_number-tr :input").val(block_number);


          //save cheat_blocker data
          $("#duplicate_check-tr :input").val(is_duplicate);
          $("#pot_duplicate_record_ids-tr :input").val(potential_duplicate_record_ids);
          $("#potential_failed_criteria-tr :input").val(potential_failed_criteria);
          $("#duplicate_record_ids-tr :input").val(duplicate_record_ids);
          $("#failed_criteria-tr :input").val(failed_criteria);
          $("#duplicates_count-tr :input").val(duplicates_count);

          if(data_entry_time){
            $("#data_entry_time-tr :input").val(data_entry_time);
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

    var submitBtns = $("[id^=submit-btn-save], [name^=submit-btn-save]");

    submitBtns.prop("onclick", null).off("click");
    submitBtns.each((i, elt) => {
      elt.onclick = quotaCheck_identifyDuplicates;
    });
  });
}, 0);



