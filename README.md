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
#### v0.1.6
- Configure checkbox choice to FluentCRM tag mappings on each form feed.
- Add all matching tags alongside static tags without removing existing tags.
- Validate choice and tag mappings; skip stale mappings during processing.

#### v0.1.5
- Enable text field to map to name field
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


### Checkbox choice tags

Open the form's Settings > FluentCRM feed and use **Checkbox choice tags**.
Each row maps one regular Checkbox field choice to an existing FluentCRM tag.
Repeat a choice on another row to assign more than one tag. Several selected
choices apply the union of their tags and the feed's static tags.

Mappings use the form ID, field ID, and exact stored choice value, rather than
display labels or numeric sub-input positions. Choose non-empty, unique choice
values within each field. Stable values allow label edits and choice reordering;
changing a stored value requires remapping. Deleted choices/tags are ignored
at runtime and must be fixed or removed before resaving the feed.

Tags are additive: an unchecked box on a later submission does not remove
an earlier tag. Existing feeds with no mappings retain their behavior. This
feature does not change contact subscription status, opt-in handling, lists,
or previously submitted entries. Run `php tests/checkbox-choice-tags.php` for
isolated regression checks, then verify the UI and actual submissions in staging.

### TODOs
- Add mapping for assigning lists based on field choices in the form.
- Conditionally show the donation summary if the subscriber has donated.
    - Maybe write a class to define the subscribers donations?
