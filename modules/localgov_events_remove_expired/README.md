# LocalGov Drupal Events remove expired

Provides a way to remove expired events - either by deletion or unpublishing/archiving
A sub-module of localgov_events and works with or without content moderation.

If content moderation is on, this works with the localgov workflow and will set the events to expired when archive is selected.  
If moderation is not in use and archive is selected, the event will be unpublished. 
Unpublishing and deletion of the events are handled by cron, so please make sure a cron job is configured.

Configuration form can be found here - /admin/config/content/expired-events

## Testing expiring events (@see \Drupal\Tests\localgov_events_remove_expired\Functional\ExpiredEventsTest)

Creates an array of events 

past_event          - 1 day past expiry period - date adjustment ` -(expiry_days + 1)`
expiry_period_event - matches the expiry period - date adjustment ` -(expiry_days)`
today_event         - 0 days old - date adjustment `0`
future_event        - an expiry period in the future - date adjustment ` +(expiry_days)`
recurring_event     - starts 2 days ago and recurs for 10 days  - date adjustment `-2 and continue for 10days`


Which tests that event deletion/unpublishing only happens for past_event in each case

### Sample results

**Date of sample run:  01-10-2025**


**Expiry period**                                             |              0               |              1               |              15                       
——————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————
**Past**                                                      |          30-09-2025          |          29-09-2025          |          15-09-2025 
——————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————
**Expiry period in past**                                     |          01-10-2025          |          30-09-2025          |          16-09-2025 
——————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————
**Today**                                                     |          01-10-2025          |          01-10-2025          |          01-09-2025 
——————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————
**Forward Expiry period in future**                           |          01-10-2025          |          02-10-2025          |          16-10-2025 
——————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————
**Recurring event 2 days in past then forward 10 days**       |          29-09-2025+         |          29-09-2025+         |          29-09-2025+
——————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————————