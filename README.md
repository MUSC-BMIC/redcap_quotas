# QuotaConfig (REDCap External Module)

## Configuration

- Enable the module in your project, if it is not already enabled
- Click **Configure** for **QuotaConfig**

Passed Quota Check Indicator:  a variable that indicates whether a participant passed the quota checks that have been configured for the study

Confirmed Enrollment Indicator:  a variable that indicates that a participant has been manually approved by the study team

**Setting up the Passed Quota Check Indicator (required):**
- In your project, navigate to the ‘Designer Page’
- Click to Modify the Instrument
- Click ‘Add Field’
- **Within the ‘Add New Field’ modal:** 
	- ‘Field Type’ should be set to ‘Yes - No'
	- Under ‘Action Tags / Field Annotation’ add the tag @hidden-survey
	- Under ‘Variable Name’, name your "Passed Quota Check" variable and Save. 

**Setting up the Confirmed Enrollment Indicator (not required):**
- In your project, navigate to the ‘Designer Page’
- Click to Modify the Instrument
- Click ‘Add Field’
- **Within the ‘Add New Field’ modal:** 
	- ‘Field Type’ should be set to ‘Yes - No'
	- Under ‘Action Tags / Field Annotation’ add the tag @hidden-survey
	- Under ‘Variable Name’, name your "Confirmed Enrollment" variable and Save. 

- Navigate to ‘Applications >> External Modules’ and click to configure ‘QuotaConfig’ select your newly created variable ("Passed Quota Check" variable) in the Passed Quota Check Indicator dropdown and Save.  The "Confirmed Enrollment" variable is not required, but if you wish to include this in your study, add it to the Confirmed Enrollment Indicator dropdown and Save.