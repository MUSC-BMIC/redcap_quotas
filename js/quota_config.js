if(typeof QuotaConfig === 'undefined') {
  var QuotaConfig = {};
}

$(document).ready(function() {
  $(document).on('change', "select[name*='field-name']", function() {
    console.log($(this).val());
  });
});

QuotaConfig.fieldValueSelector = function(textSelector) {
  textSelector.focus(function() {
    console.log($(this).val());
  });
};
