XenForo-WarningImprovements
======================

A Collection of improvements to XF's warning system.

- User Criteria for warning points
- Allow users to view their own warnings, and find which posts where warned. 
- Sends an alert to a user when they receive a warning. (Defaults on, togglable)
- Allows the Custom Warning to be customized
- Copy Warning title/text automatically to the public warning
- Optional "Continue" button on the warning dialog, instead of a "warn" button for the first few tabs.
- Allow the default content action to be set
- Control defaults for user notification
 - Alerts
 - Lock PMs by default
- Option to require a note when entering a warning
- Ability to see warning actions applied to an account from the front-end
 - users may see warning actions against thier account
 - automatically roll-up identical warning actions to show the latest expiry
 - per-group moderator permissions for editing/viewing all/disable summarization.
- Additional conversation substitution replaceable for the warning conversation.
 - points
 - warning_title
 - warning_link
- Option to force new conversation email to be sent on a warning conversation. 
 - Will send even if they are banned!
 - Always sends full conversation text.
 - This can ignore conversation privacy options.
 
New Permission to control if a user can see who warned them.
- View Warning Issuer.

New moderator permissions for viewing warning actions.
- View Warning Actions
- Edit Warning Actions
- Don't Summarize Warning Actions

There is an option for "Anonymise Warning Alerts" if you do not wish for the issuer to be associated with the warning.

