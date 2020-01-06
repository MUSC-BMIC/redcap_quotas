# QuotaConfig (REDCap External Module)

## Configuration

- Enable the module in your project, if it is not already enabled
- Click **Configure** for **QuotaConfig**

**Setting up the Quota Met Indicator:**
- In your project, navigate to the ‘Designer Page’
- Click to Modify the Instrument
- Click ‘Add Field’
- **Within the ‘Add New Field’ modal:** 
	- ‘Field Type’ should be set to ‘True - False’
	- Under ‘Action Tags / Field Annotation’ add the tag @hidden 
	- Under ‘Variable Name’, name your variable and Save. 

- Navigate to ‘Applications >> External Modules’ and click to configure ‘QuotaConfig’ select your newly created variable (that you just created above) in the Quota Met Indicator dropdown and Save.