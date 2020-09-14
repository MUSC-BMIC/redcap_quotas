# QuotaConfig (REDCap External Module)

## Configuration

- Enable the module in your project, if it is not already enabled
- Click **Configure** for **QuotaConfig**

Passed Quota Check Indicator:  a variable that indicates whether a participant passed the quota checks that have been configured for the study
Confirmed Enrollment Indicator:  a variable that indicates that a participant has been manually approved by the study team
Block Number: a variable that stores the block number if block size feature is enabled
Eligible for Enrollment: a variable that is used as a first check if Confirmed Enrollment Indicator is set
Enrolled Confirmed: a variable that is used as a second and final check if Confirmed Enrollment Indicator is set

There are 2 methods of creating variables within the REDCap project - using the Online Designer OR by uploading data dictionary

**Method #1 - Using Online Designer**

All the variables have to be manually created by navigating to the Designer Page.

**Setting up the Passed Quota Check Indicator (required):**
- In your project, navigate to the ‘Designer Page’
- Click to Modify the Instrument
- Click ‘Add Field’
- **Within the ‘Add New Field’ modal:**
	- ‘Field Type’ should be set to ‘Yes - No'
	- Under ‘Action Tags / Field Annotation’ add the tag @hidden-survey
	- Under ‘Variable Name’, name your "Passed Quota Check" variable and Save

**Setting up the Confirmed Enrollment Indicator (not required):**
- In your project, navigate to the ‘Designer Page’
- Click to Modify the Instrument
- Click ‘Add Field’
- **Within the ‘Add New Field’ modal:**
	- ‘Field Type’ should be set to ‘Yes - No'
	- Under ‘Action Tags / Field Annotation’ add the tag @hidden-survey
	- Under ‘Variable Name’, name your "Confirmed Enrollment" variable and Save

**Setting up the Block Number variable:**
- In your project, navigate to the ‘Designer Page’
- Click to Modify the Instrument
- Click ‘Add Field’
- **Within the ‘Add New Field’ modal:**
  - ‘Field Type’ should be set to ‘Text Box (Short Text, Number, Date/Time, ...)'
  - Under ‘Action Tags / Field Annotation’ add the tag @hidden-survey
  - Under ‘Variable Name’, name your "Block Number" variable and Save

**Setting up the Eligible for Enrollment variable:**
- In your project, navigate to the ‘Designer Page’
- Click to Modify the Instrument
- Click ‘Add Field’
- **Within the ‘Add New Field’ modal:**
  - ‘Field Type’ should be set to ‘Yes - No'
  - Under ‘Action Tags / Field Annotation’ add the tag @hidden-survey
  - Under ‘Variable Name’, name your "Eligible for Enrollment" variable and Save

**Setting up the Enrolled Confirmed:**
- In your project, navigate to the ‘Designer Page’
- Click to Modify the Instrument
- Click ‘Add Field’
- **Within the ‘Add New Field’ modal:**
  - ‘Field Type’ should be set to ‘Yes - No'
  - Under ‘Action Tags / Field Annotation’ add the tag @hidden-survey
  - Under ‘Variable Name’, name your "Enrolled Confirmed" variable and Save


**Method #2 - Uploading Data Dictionary**

The Data Dictionary is a more advanced method which allows to view/edit all the variables in a single csv file.
Navigate to Data Dictionary and upload the data dictionary in the link shown below.

[Data Dictionary link](https://github.com/HSSC/redcap_quotas/blob/master/QuotaConfig_data_dictionary.csv)

Upload the data dictionary and then commit changes after it is uploaded. The new Data Dictionary will completely overwrite your existing variables, so you want to be sure you've uploaded the right file.


If both Quota Config & Cheat Blocker modules are enabled, upload the combined data dictionary in the link shown below

[Data Dictionary link](https://github.com/HSSC/redcap_quotas/blob/master/QuotaConfig_CheatBlocker_data_dictionary.csv)


- Navigate to ‘Applications >> External Modules’ and click to configure ‘QuotaConfig’ select your newly created variable ("Passed Quota Check" variable) in the Passed Quota Check Indicator dropdown and Save.  The "Confirmed Enrollment" variable is not required, but if you wish to include this in your study, add it to the Confirmed Enrollment Indicator dropdown and Save.
