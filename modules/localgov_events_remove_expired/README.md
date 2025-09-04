# LocalGov Drupal Events remove expired

Provides a way to remove expired events - either by deletion or unpublishing/archiving
A sub-module of localgov_events and works with or without content moderation.

If content moderation is on, this works with the localgov workflow and will set the events to expired when archive is selected.  
If moderation is not in use and archive is selected, the event will be unpublished. 
Unpublishing and deletion of the events are handled by cron, so please make sure a cron job is configured.

Configuration form can be found here - /admin/config/content/expired-events

