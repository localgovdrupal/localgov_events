# LocalGov Drupal Events

This module provides two content types:

## Event (localgov_event)
This can be used to represent in-person or online events on your LGD site. 
Events can be one off, or recurring.

## Event Channel (localgov_event_channel)
This is used to group events. Events can be assigned to one or more channels, 
and you can have as many channels as you like. Event channels can be used for 
whatever grouping of events is required, but could be things like 
events in a specific library or the calendar of council meetings.

## Setup
Enable the module.

Create some facets if you'd like to use them. 
Facets are used to filter content, but unlike regular filters, they derive the list of values that content can be filtered by from the content being listed.
This means that using a facet only show relevant filtering options and will never produce zero results.

To set up facets you need to create some facet types. For example, if you want to filter your events by an age range, go to Structure -> Finders Facet Types and click "Add Finders Facer Type". Then type "Age range" into the Name field and save the form. The Description field on this page is optional, and is only seen by site admins. Put a description of what the facet represents here if you like.
Then go to Content -> Finders facets and click the "Add Finders Facet" button. Choose "Age range" from the list you're presented with. On the next page, type the age range itself (EG 0 to 2 years) in the Title field and save the page. Repeat these steps to add as many facet values as you like.

The next step is to set up an event channel.
Go to Content -> Add content -> Event Channel.
Enter the name of the channel in the Title field, for example: "Events at the central library". 
The Body field can be any text you'd like to show above the listing of events. 
Choose "Event" under "Enabled entry types". 
If your site has multiple event content types, this field controls which can be placed in the channel.
As we've only got one at the moment, choose it.
For the "Event List View" field, choose "Events". A new field called "Display" will appear with one option called "Embed". Choose "Embed".
In the "Enabled Facets" field, choose "Age range" and save the form.

You'll be taken the new channel page, which won't contain any events yet.
To add one, go to Content -> Add content -> Event.

Add a title for the event. In the "Finder channels" section, choose the event channel that you just created. Add a summary, body, date and Under Facets, choose the Age range that you'd like this event to be for.
When you save the page, you'll be taken to the event. If you navigate back to the event channel, you'll see the event listed in the channel.

In this manner, you can create multiple listings of events in your site.

If you'd like to try out the calendar view, edit the event channel, and in the "Event calendar view" field, choose "Finder events calender" and then "Embed" in the "Display" field that appears. You can hide the main listing when using the calendar view by changing the "Event list view" field back to "None".
