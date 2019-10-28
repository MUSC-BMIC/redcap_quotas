$(document).ready(function() {
  var $quota_exceeded = false;

  $(document).on('submit', '#form', function(e) {
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();
    $.get(quotaEnforcementSettings.url, $('form').serialize(), function(data) {
      quotas = JSON.parse(data);
      console.log(quotas);
      if (quotas['totalNMet'] == true) {
        $quota_exceeded = true;
        console.log('stop');
      }
    });

    return false;
  });
});
