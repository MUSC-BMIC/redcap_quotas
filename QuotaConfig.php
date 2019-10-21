<?php

namespace MUSC\QuotaConfig;
use REDCap;

class QuotaConfig extends \ExternalModules\AbstractExternalModule
{
  function redcap_every_page_top(int $project_id)
  {
    if (strpos(PAGE, 'ExternalModules/manager/project.php') !== false)
    {
      // Get all field variable names in project
      // Get the data dictionary for the current project in array format
$dd_array = REDCap::getDataDictionary('array');

// Loop through each field and do something with each
foreach ($dd_array as $field_name=>$field_attributes)
{
  echo($field_name);
  print_r($field_attributes);
    // Do something with this field if it is a checkbox field
    if ($field_attributes['field_type'] == "checkbox") {
        // Something

    }
}
  $fields = REDCap::getFieldNames();

  // Loop through each field and do something with each
  foreach ($fields as $this_field) {
    print($this_field);
      // Do something with $this_field

  }
      print("What's up");
      print("What's up");
      print("What's up");
      print("What's up");
      print("What's up");
    }
  }
}

?>
