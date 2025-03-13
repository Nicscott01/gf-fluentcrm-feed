# Gravity Forms Fluent CRM Feed Plugin

This plugin adds a Gravity Forms Feed that lets you: 

- Add contact to FluentCRM from GravityForms
- Map submission data into a Fluent CRM Subscriber as a:
    - Form Submission
    - Donation
    - Purchase History (coming soon)

We actually copy some baseline data to Fluent CRM Subscriber meta so it will always stay with them regardless of status of Gravity Forms entry (it will still be there if the entry gets deleted). Convenient links to the entry in GF are there as well.

## Motivation Behind the Plugin
I wanted to extend tools that I already use without adding extra website bloat and duplicative database tables. This plugin aims to extend FluentCRM into a full-blown donor platform as well as maintaining its exisiting functionality as a CRM.

### Release Notes
#### v0.1.4
- Add form submission routing fields as hardcoded values (from the predecesors of ACF option)
#### v0.1.3
- Hotfix for missing function
#### v0.1.1
- Add tags & list fields
- Hardcoded values for entry types and columns
- Dynamic settings fields when entry type is selected
- Add subscriber status manual override (so you don't have to map the form fields)
#### v0.1.0 
Initial release. I'm sure there are tons of bugs. We don't do much checking for plugins to be there, etc. So it's a bit fragile at the moment!


### TODOs
- Add mapping for assigning tags/lists based on field choices in the form. Essentially, make a form dynamic to add people to the tags/lists they choose.
- Conditionally show the donation summary if the subscriber has donated.
    - Maybe write a class to define the subscribers donations?