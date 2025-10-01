<?php

declare(strict_types=1);

namespace Drupal\Tests\localgov_events_remove_expired\Functional;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\workflows\Entity\Workflow;

/**
 * Confirm the module's date functionality works with expired events.
 *
 * @group localgov_events_remove_expired
 */
class ExpiredEventsTest extends BrowserTestBase {

  use CronRunTrait;

  /**
   * Skip schema checks.
   *
   * @var string[]
   */
  protected static $configSchemaCheckerExclusions = [
    // Missing schema:
    // - 'content.location.settings.reset_map.position'.
    // - 'content.location.settings.weight'.
    'core.entity_view_display.localgov_geo.area.default',
    'core.entity_view_display.localgov_geo.area.embed',
    'core.entity_view_display.localgov_geo.area.full',
    'core.entity_view_display.geo_entity.area.default',
    'core.entity_view_display.geo_entity.area.embed',
    'core.entity_view_display.geo_entity.area.full',
    // Missing schema:
    // - content.location.settings.geometry_validation.
    // - content.location.settings.multiple_map.
    // - content.location.settings.leaflet_map.
    // - content.location.settings.height.
    // - content.location.settings.height_unit.
    // - content.location.settings.hide_empty_map.
    // - content.location.settings.disable_wheel.
    // - content.location.settings.gesture_handling.
    // - content.location.settings.popup.
    // - content.location.settings.popup_content.
    // - content.location.settings.leaflet_popup.
    // - content.location.settings.leaflet_tooltip.
    // - content.location.settings.map_position.
    // - content.location.settings.weight.
    // - content.location.settings.icon.
    // - content.location.settings.leaflet_markercluster.
    // - content.location.settings.feature_properties.
    'core.entity_form_display.geo_entity.address.default',
    'core.entity_form_display.geo_entity.address.inline',
    // Missing schema:
    // - content.postal_address.settings.providers.
    // - content.postal_address.settings.geocode_geofield.
    'core.entity_form_display.localgov_geo.address.default',
    'core.entity_form_display.localgov_geo.address.inline',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test using the minimal profile.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * A user with permission to bypass content access checks.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
    'localgov_events_remove_expired',
    'localgov_events',
    'node',
    'date_recur',
    'content_moderation',
    'views',
    'localgov_workflows',
    'workflows',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser([
      'bypass node access',
      'administer nodes',
      'administer node fields',
      'access content overview',
    ]);
    $this->drupalLogin($this->adminUser);

  }

  /**
   * Tests that expired events are unpublished.
   *
   * Uses various expiry periods.
   */
  public function testUnpublishEvents(): void {
    $this->unpublishEvents(0);
    $this->unpublishEvents(1);
    $this->unpublishEvents(15);
  }

  /**
   * Tests that expired events are deleted.
   *
   * Uses various expiry periods.
   */
  public function testDeleteEvents(): void {
    $this->deleteEvents(0);
    $this->deleteEvents(1);
    $this->deleteEvents(15);
  }

  /**
   * Tests that expired events are unpublished.
   *
   * Uses various expiry periods
   * and content moderation is not enabled.
   */
  public function testUnpublishEventsNonWorkflow(): void {
    $this->unpublishEventsNonWorkflow(0);
    $this->unpublishEventsNonWorkflow(1);
    $this->unpublishEventsNonWorkflow(15);
  }

  /**
   * Tests that expired event is unpublished when necessary.
   *
   * Creates an array of events:
   *
   *  Past event - 1 day past expiry period
   *  Expiry period event - matches the expiry period
   *  Todays event - 0 days old
   *  Future event - an expiry period in the future
   *  Recurring event - starts 2 days ago and recurs for 10 days
   *
   * all events except the past event should remain published.
   *
   * @param int $expiryPeriod
   *   The number of days after which an event expires.
   */
  private function unpublishEvents(int $expiryPeriod): void {

    // Set up the events.
    $events = $this->createEvents($expiryPeriod, TRUE);

    // Set up the configuration to unpublish events.
    $config = \Drupal::configFactory()->getEditable('localgov_events_remove_expired.settings');
    $config->set('expire_days', $expiryPeriod)
      ->set('items_per_cron', 6)
      ->set('action', 'unpublish')
      ->save();

    $this->cronRun();

    // The past event should be unpublished.
    $refreshed_event = \Drupal::entityTypeManager()->getStorage('node')->load($events['past_event']->id());
    $this->assertEquals("0", $refreshed_event->get('status')->value, 'Past event status should remain unpublished');

    // All others should remain published.
    $refreshed_event = \Drupal::entityTypeManager()->getStorage('node')->load($events['expiry_period_event']->id());
    $this->assertEquals("1", $refreshed_event->get('status')->value, $expiryPeriod . ' day old event status should remain published');

    $refreshed_event = \Drupal::entityTypeManager()->getStorage('node')->load($events['today_event']->id());
    $this->assertEquals("1", $refreshed_event->get('status')->value, 'Todays event status should remain published');
    $refreshed_event = \Drupal::entityTypeManager()->getStorage('node')->load($events['future_event']->id());
    $this->assertEquals("1", $refreshed_event->get('status')->value, 'Future status should remain published');
    $refreshed_event = \Drupal::entityTypeManager()->getStorage('node')->load($events['recurring_event']->id());
    $this->assertEquals("1", $refreshed_event->get('status')->value, 'Recurring event status should remain published');

  }

  /**
   * Tests that expired events are deleted when necessary.
   *
   * Creates an array of events:
   *
   *  Past event - 1 day past expiry period
   *  Expiry period event - matches the expiry period
   *  Todays event - 0 days old
   *  Future event - an expiry period in the future
   *  Recurring event - starts 2 days ago and recurs for 10 days
   *
   * all events except the past event should remain published.
   *
   * @param int $expiryPeriod
   *   The number of days after which an event expires.
   */
  private function deleteEvents(int $expiryPeriod): void {

    // Set up the events.
    $events = $this->createEvents($expiryPeriod, TRUE);

    // Set up the configuration to delete events.
    $config = \Drupal::configFactory()->getEditable('localgov_events_remove_expired.settings');
    $config->set('expire_days', $expiryPeriod)
      ->set('items_per_cron', 6)
      ->set('action', 'delete')
      ->save();

    $this->cronRun();

    // The past event should be deleted.
    $this->assertNull(\Drupal::entityTypeManager()->getStorage('node')->load($events['past_event']->id()), 'Past event should be deleted.');

    // All others should remain.
    $this->assertNotNull(\Drupal::entityTypeManager()->getStorage('node')->load($events['expiry_period_event']->id()), $expiryPeriod . ' day old event should not be deleted');
    $this->assertNotNull(\Drupal::entityTypeManager()->getStorage('node')->load($events['today_event']->id()), 'Todays event should not be deleted.');
    $this->assertNotNull(\Drupal::entityTypeManager()->getStorage('node')->load($events['future_event']->id()), 'Future event should not be deleted.');
    $this->assertNotNull(\Drupal::entityTypeManager()->getStorage('node')->load($events['recurring_event']->id()), 'Recurring event should not be deleted.');

  }

  /**
   * Tests that expired event is unpublished when necessary.
   *
   * Creates an array of events:
   *
   *  Past event - 1 day past expiry period
   *  Expiry period event - matches the expiry period
   *  Todays event - 0 days old
   *  Future event - an expiry period in the future
   *  Recurring event - starts 2 days ago and recurs for 10 days
   *
   * all events except the past event should remain published.
   *
   * @param int $expiryPeriod
   *   The number of days after which an event expires.
   */
  private function unpublishEventsNonWorkflow(int $expiryPeriod): void {

    // Remove the workflow from the localgov_event content type.
    $entity = Workflow::load('localgov_editorial');
    $type = $entity->getTypePlugin();
    $type->removeEntityTypeAndBundle('node', 'localgov_event');
    $entity->save();

    // Set up the events.
    $events = $this->createEvents($expiryPeriod, FALSE);

    // Set up the configuration to unpublish events.
    $config = \Drupal::configFactory()->getEditable('localgov_events_remove_expired.settings');
    $config->set('expire_days', $expiryPeriod)
      ->set('items_per_cron', 6)
      ->set('action', 'unpublish')
      ->save();

    $this->cronRun();

    // The past event should be unpublished.
    $refreshed_event = \Drupal::entityTypeManager()->getStorage('node')->load($events['past_event']->id());
    $this->assertEquals("0", $refreshed_event->get('status')->value, 'This event status should remain published');

    // All others should remain published.
    $refreshed_event = \Drupal::entityTypeManager()->getStorage('node')->load($events['expiry_period_event']->id());
    $this->assertEquals("1", $refreshed_event->get('status')->value, $expiryPeriod . ' day old event status should remain published');
    $refreshed_event = \Drupal::entityTypeManager()->getStorage('node')->load($events['today_event']->id());
    $this->assertEquals("1", $refreshed_event->get('status')->value, 'Todays event status should remain published');
    $refreshed_event = \Drupal::entityTypeManager()->getStorage('node')->load($events['future_event']->id());
    $this->assertEquals("1", $refreshed_event->get('status')->value, 'Future status should remain published');
    $refreshed_event = \Drupal::entityTypeManager()->getStorage('node')->load($events['recurring_event']->id());
    $this->assertEquals("1", $refreshed_event->get('status')->value, 'Recurring event status should remain published');
  }

  /**
   * Tests cron handling.
   *
   * Creates 10 events in the past
   * set items_per_cron to 3
   * runs cron
   * test 3 unpublished.
   */
  public function testUnpublishCronBatching(): void {

    // Create a bunch of event nodes.
    $count = 10;

    for ($i = 1; $i <= $count; $i++) {

      $past_event = $this->drupalCreateNode([
        'type' => 'localgov_event',
        'title' => "Event " . $count,
        'body' => ["value" => "Event " . $this->randomMachineName(8)],
        'status' => NodeInterface::PUBLISHED,
        'localgov_event_date' => $this->getDate(-2),
        'moderation_state' => 'published',
      ]);
      $past_event->save();
    }

    // Set up the configuration to unpublish events.
    $config = \Drupal::configFactory()->getEditable('localgov_events_remove_expired.settings');
    $config->set('expire_days', 1)
      ->set('items_per_cron', 3)
      ->set('action', 'unpublish')
      ->save();

    $this->cronRun();

    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'localgov_event')
      ->condition('status', 0)
      ->execute();

    // 3 should be unpublished.
    $this->assertEquals(3, count($nids), '3 events should be unpublished.');

    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'localgov_event')
      ->condition('status', 1)
      ->execute();

    // 7 should be unpublished.
    $this->assertEquals(7, count($nids), '7 events should remain published.');

    // Run cron a 2nd time .
    $this->cronRun();

    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'localgov_event')
      ->condition('status', 0)
      ->execute();

    // 6 should be unpublished.
    $this->assertEquals(6, count($nids), '6 events should be unpublished.');

    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'localgov_event')
      ->condition('status', 1)
      ->execute();

    // 4 should be unpublished.
    $this->assertEquals(4, count($nids), '4 events should remain published.');

    // Run cron a 3rd time .
    $this->cronRun();

    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'localgov_event')
      ->condition('status', 0)
      ->execute();

    // 9 should be unpublished.
    $this->assertEquals(9, count($nids), '9 events should be unpublished.');

    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'localgov_event')
      ->condition('status', 1)
      ->execute();

    // 1 should be unpublished.
    $this->assertEquals(1, count($nids), '1 events should remain published.');

    // Run cron a 4th time .
    $this->cronRun();

    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'localgov_event')
      ->condition('status', 0)
      ->execute();

    // 10 should be unpublished.
    $this->assertEquals(10, count($nids), '10 events should be unpublished.');

    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'localgov_event')
      ->condition('status', 1)
      ->execute();

    // 1 should be unpublished.
    $this->assertEquals(0, count($nids), '0 events should remain published.');

  }

  /**
   * Create array events for testing.
   *
   * - Past event - 1 day past expiry period
   * - Expiry period event - matches the expiry period
   * - Todays event - 0 days old
   * - Future event - an expiry period in the future
   * - Recurring event - starts 2 days ago and recurs for 10 days.
   *
   * all events except the past event should remain published.
   *
   * @param int $expiryPeriod
   *   The number of days after which an event expires.
   * @param bool $moderation_flag
   *   If true (default) set the moderation state to published.
   */
  private function createEvents(int $expiryPeriod, bool $moderation_flag = TRUE): array {

    $events = [];

    // Set up the events.
    $newDate = (-($expiryPeriod + 1));
    $pastEvent = $this->createEvent($newDate, $moderation_flag);

    $this->drupalGet('node/' . $pastEvent->id());
    $this->assertSession()->statusCodeEquals(200, 'The event is not accessible.');
    $this->assertTrue($pastEvent->isPublished(), 'The event status is should be published.');
    $events['past_event'] = $pastEvent;

    $newDate = (-($expiryPeriod));
    $expiryPeriodEvent = $this->createEvent($newDate, $moderation_flag);

    $this->drupalGet('node/' . $expiryPeriodEvent->id());
    $this->assertSession()->statusCodeEquals(200, 'The event is not accessible.');
    $this->assertTrue($expiryPeriodEvent->isPublished(), 'The event status is should be published.');
    $events['expiry_period_event'] = $expiryPeriodEvent;

    $todayEvent = $this->createEvent(0, $moderation_flag);

    $this->drupalGet('node/' . $todayEvent->id());
    $this->assertSession()->statusCodeEquals(200, 'The event is not accessible.');
    $this->assertTrue($todayEvent->isPublished(), 'The event status is should be published.');
    $events['today_event'] = $todayEvent;

    $newDate = ($expiryPeriod);
    $futureEvent = $this->createEvent($newDate, $moderation_flag);

    $this->drupalGet('node/' . $futureEvent->id());
    $this->assertSession()->statusCodeEquals(200, 'The event is not accessible.');
    $this->assertTrue($futureEvent->isPublished(), 'The event status is should be published.');
    $events['future_event'] = $futureEvent;

    // Create a 10 day recurring event with dates in the past and future.
    $recurringEvent = $this->drupalCreateNode([
      'type' => 'localgov_event',
      'title' => 'Recurring with future events.',
      'body' => 'This event has occurrences in the future and should remain.',
      'status' => 1,
      'localgov_event_date' => $this->getRecurringDate(),
    ]);
    if ($moderation_flag) {
      $recurringEvent->set('moderation_state', 'published');
    }
    $recurringEvent->save();
    $events['recurring_event'] = $recurringEvent;

    $this->drupalGet('node/' . $recurringEvent->id());
    $this->assertSession()->statusCodeEquals(200, 'The event is not accessible.');
    $this->assertTrue($recurringEvent->isPublished(), 'The event status is should be published.');
    $events['recurring_event'] = $recurringEvent;

    return $events;
  }

  /**
   * Create an event.
   *
   * @param int $new_date
   *   Based on $new_date being:
   *    - negative for past dates
   *    - zero for today
   *    - positive for future dates.
   * @param bool $moderation_flag
   *   If true (default) set the moderation state to published.
   *
   * @return array
   *   The created node.
   */
  private function createEvent(int $new_date, bool $moderation_flag = TRUE):NodeInterface {

    $title = $this->randomMachineName(8);
    $node = $this->drupalCreateNode([
      'title' => 'localgov_event ' . $title,
      'type' => 'localgov_event',
      'body' => 'LGD Event test page',
      'status' => 1,
      'localgov_event_date' => $this->getDate($new_date),
    ]);
    if ($moderation_flag) {
      $node->set('moderation_state', 'published');
    }
    $node->save();
    return $node;
  }

  /**
   * Get an event date.
   *
   * Based on $new_date being:
   *  - negative for past dates
   *  - zero for today
   *  - positive for future dates .
   *
   *  - all events are 2 hours long
   *  - all dates are UTC and start at midnight
   */
  private function getDate(int $new_date): array {

    $date = new DrupalDateTime('now', 'UTC');
    $date->modify('midnight');
    $start_date = $date->modify($new_date . " days")->format('Y-m-d\TH:i:s');

    $end_date = $date->modify("+2 hours")->format('Y-m-d\TH:i:s');

    return [
      'value' => $start_date,
      'end_value' => $end_date,
      'rrule' => '',
      'timezone' => 'UTC',
    ];
  }

  /**
   * Creates a 10 day recurring event.
   *
   * Starts 2 days ago and
   * continues for 10 days.
   */
  private function getRecurringDate(): array {

    $date = new DrupalDateTime('now', 'UTC');
    $date->modify('midnight');
    // Set date in the past, allowing for expire_days.
    $start_date = $date->modify("-2 days")->format('Y-m-d\TH:i:s');
    $end_date = $date->modify("+2 hours")->format('Y-m-d\TH:i:s');
    return [
      'value' => $start_date,
      'end_value' => $end_date,
      'timezone' => 'UTC',
      'infinite' => 0,
      'rrule' => 'FREQ=DAILY;COUNT=10',
    ];
  }

}
