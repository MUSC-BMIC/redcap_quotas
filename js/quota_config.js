$(document).ready(function() {
  var $modal = $('#external-modules-configure-modal');

  $modal.on('show.bs.modal', function() {

    // Making sure we are overriding this modules's modal only.
    if ($(this).data('module') !== quotaConfigSettings.modulePrefix) {
        return;
    }

    $(document).ajaxComplete(function() {
      $modal.find("select[name*='field_name']").each(function() {
        quotaConfigSettings.useOldVal = "true"
        $(this).trigger('change');
        quotaConfigSettings.useOldVal = "false"
      });
    });

    $(document).on('change', "select[name*='field_name']", function() {
      selectedVal = $(this).val();
      if (quotaConfigFields.hasOwnProperty(selectedVal)) {
        inputTd = $(this).closest("tr").next("tr").find("td.external-modules-input-td")
        oldInput = inputTd.find("input, select, textarea");

        // dropdowns and radio buttons
        if (['dropdown', 'radio'].indexOf(quotaConfigFields[selectedVal].field_type) != -1) {
          options = quotaConfigFields[selectedVal].select_choices_or_calculations.split(" | ");
          newSelect = '<select class="' + oldInput.attr('class') + '" name="' + oldInput.attr('name') + '">';

          $.each(options, function(index, value) {
            option = value.split(", ");

            if (quotaConfigSettings.useOldVal == 'true' && oldInput.val() == option[0]) {
              newSelect += '<option value=' + option[0] + ' selected=selected>' + option[1] + '</option>';
            }
            else {
              newSelect += '<option value=' + option[0] + '>' + option[1] + '</option>';
            }

          });

          newSelect += '</select>';

          oldInput.replaceWith(newSelect);
        }

        // text, notes, and calculated fields
        if (['calc', 'text', 'notes'].indexOf(quotaConfigFields[selectedVal].field_type) != -1) {

          if (quotaConfigSettings.useOldVal == 'true') {
            newInput = '<input type="text" class="' + oldInput.attr('class') + '" name="' + oldInput.attr('name') + '" value="' + oldInput.val() + '">';
          }
          else {
            newInput = '<input type="text" class="' + oldInput.attr('class') + '" name="' + oldInput.attr('name') + '">';
          }

          oldInput.replaceWith(newInput);
        }
      }
    });
  });
});
