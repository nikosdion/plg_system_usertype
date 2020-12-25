# User Type Selection

A Joomla plugin which allows first time users to select which groups they will be added in.

## The problem to solve

Joomla allows for users to self-register an account. It will place them in exactly one user group. It can only be the same user group for all newly registered users.

What if you have a site which has different possible kinds of users? For example, a real estate site may want to allow self-registration for people listing properties and people interested in properties. 

Sure, you can show both listing display and creation options to everyone but rarely does it make sense from a user experience perspective. The way to solve this is to have different user groups (and view levels) for each type of users on your site so you can only show them what is pertinent to theis intended use of your site. The way site integrators typically implement this on a site is using a membership (subscriptions) component. 

But what if your site's features are free to use at a basic level? Do you really want to have all your visitors go through a sales flow, even for zero charge services? In most cases people would be confused as to why they need to "purchase" a membership to access free services and simply walk way. Moreover, this user flow doesn't work very well if you are creating user accounts through social media e.g. login with Facebook.

**If only there was a way to let the user choose their type without much fuss**.

This is exactly what the User Type Selection plugin does. It will show a page to select a user type when the logged-in user is not already assigned to any of the user groups you've told it about. 

## Features

**Plugin-only solution**. No pesky component to install. Everything is handled by the plugin.

**Made with great user experience in mind**. Users can select their user type with a full page, straightforward selection interface. Unlike similar extensions they are not redirected to the user profile edit page where they have to hunt down an obscure field.

**Secure and reasonable**. Unlike other similar extensions your users can only select their user type _once_. They cannot arbitrarily change their user group assignment. They do not see the names of the user groups you are using internally.

**Add / remove users from groups**. Each user type can add and/or remove the user from one or more groups.

**User type visibility**. Hide a user type if the user already belongs to any of a selection of user groups. Useful for migrating legacy clients to a new service structure on your site.

**Excluded user groups**. If a user belongs to any of the selected user groups they won't see the User Type selection. Useful to prevent Super Users, site staff and paying clients (registered through a membership component) from seeing the User Type selection.

**Custom messages on the page**. You can customise the messages shown at the top and bottom of the User Type selection page.

**Fully customisable HTML and CSS**. You can customise the look and feel of the User Type selection page using standard Joomla template overrides –both for the HTML and the CSS. You even get the SCSS source of the CSS files for easier customisation.