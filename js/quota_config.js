$(document).ready(function() {
  var $modal = $('#external-modules-configure-modal');
  $modal.on('show.bs.modal', function() {

    // Making sure we are overriding this modules's modal only.
    if ($(this).data('module') !== quotaConfigSettings.modulePrefix) {
        return;
    }

    console.log(quotaConfigSettings);
    console.log(quotaConfigFields);

    $(document).on('change', "select[name*='field-name']", function() {
      if (quotaConfigFields.hasOwnProperty($(this).val())) {
        console.log($(this).closest("tr").next("tr").find("td.external-modules-input-td"));
        console.log($(this).val());
        console.log(quotaConfigFields[$(this).val()]);

        if (['dropdown', 'radio'].includes(quotaConfigFields[$(this).val()].field_type)) {
          console.log("Need to convert to select");
        }
      }
    });
  });
});
