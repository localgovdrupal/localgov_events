<?php

declare(strict_types=1);

namespace Drupal\Tests\localgov_expired_events\Functional;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\workflows\Entity\Workflow;

/**
 * Confirm the module's date functionality works with expired events.
 *
 * @group localgov_expired_events
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
    'localgov_expired_events',
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
   * Tests that an expired event is unpublished.
   *
   * Creates 3 events
   * a past event
   * a future event,
   * a recurring event that spans both
   * then runs cron to unpublish the past event.
   */
  public function testUnpublishEvents(): void {

    // Set up the events.
    $past_event = $this->drupalCreateNode([
      'type' => 'localgov_event',
      'title' => 'Due to be unpublished.',
      'body' => 'This event is in the past and should be unpublished.',
      'status' => 1,
      'localgov_event_date' => $this->getPastDate(),
      'moderation_state' => 'published',
    ]);
    $past_event->save();

    $this->drupalGet('node/' . $past_event->id());
    $this->assertSession()->statusCodeEquals(200, 'The event is not accessible.');
    $this->assertTrue($past_event->isPublished(), 'The event status is should be published.');

    $future_event = $this->drupalCreateNode([
      'type' => 'localgov_event',
      'title' => 'Future event.',
      'body' => 'This event is in the future event and should remain.',
      'status' => 1,
      'localgov_event_date' => $this->getFutureDate(),
      'moderation_state' => 'published',
    ]);
    $future_event->save();

    $this->drupalGet('node/' . $future_event->id());
    $this->assertSession()->statusCodeEquals(200, 'The event is not accessible.');
    $this->assertTrue($future_event->isPublished(), 'The event status is should be published.');

    // Create a 10 day recurring event with dates in the past and future.
    $past_and_future_event = $this->drupalCreateNode([
      'type' => 'localgov_event',
      'title' => 'Recurring with future events.',
      'body' => 'This event has occurrences in the future and should remain.',
      'status' => 1,
      'localgov_event_date' => $this->getPastAndFutureDate(),
      'moderation_state' => 'published',
    ]);
    $past_and_future_event->save();
    $this->drupalGet('node/' . $past_and_future_event->id());
    $this->assertSession()->statusCodeEquals(200, 'The event is not accessible.');
    $this->assertTrue($past_and_future_event->isPublished(), 'The event status is should be published.');

    // Set up the configuration to unpublish events.
    $config = \Drupal::configFactory()->getEditable('localgov_expired_events.settings');
    $config->set('expire_days', 1)
      ->set('items_per_cron', 3)
      ->set('action', 'unpublish')
      ->save();

    // Run cron to process expired events.
    $this->cronRun();

    // The past event should be unpublished.
    $refreshed_event = \Drupal::entityTypeManager()->getStorage('node')->load($past_event->id());

    $this->assertEquals("0", $refreshed_event->get('status')->value, 'Past event status should be unpublished');

    // The future and recurring event should be published.
    $refreshed_event = \Drupal::entityTypeManager()->getStorage('node')->load($future_event->id());
    $this->assertEquals("1", $refreshed_event->get('status')->value, 'Future event status should be published');
    $refreshed_event = \Drupal::entityTypeManager()->getStorage('node')->load($past_and_future_event->id());
    $this->assertEquals("1", $refreshed_event->get('status')->value, 'Past/future event status should be published');

  }

  /**
   * Tests that an expired event is deleted.
   *
   * Creates 3 events
   * a past event
   * a future event,
   * a recurring event that spans both
   * then runs cron to delete the past event.
   */
  public function testDeleteEvents(): void {

    // Set up the events.
    $past_event = $this->drupalCreateNode([
      'type' => 'localgov_event',
      'title' => 'Due to be deleted.',
      'body' => 'This event is in the past and should be deleted.',
      'status' => 1,
      'localgov_event_date' => $this->getPastDate(),
      'moderation_state' => 'published',
    ]);
    $past_event->save();

    $this->drupalGet('node/' . $past_event->id());
    $this->assertSession()->statusCodeEquals(200, 'The event is not accessible.');
    $this->assertTrue($past_event->isPublished(), 'The event status is should be published.');

    $future_event = $this->drupalCreateNode([
      'type' => 'localgov_event',
      'title' => 'Future event.',
      'body' => 'This event is in the future event and should remain.',
      'status' => 1,
      'localgov_event_date' => $this->getFutureDate(),
      'moderation_state' => 'published',
    ]);
    $future_event->save();

    $this->drupalGet('node/' . $future_event->id());
    $this->assertSession()->statusCodeEquals(200, 'The event is not accessible.');
    $this->assertTrue($future_event->isPublished(), 'The event status is should be published.');

    // Create a 10 day recurring event with dates in the past and future.
    $past_and_future_event = $this->drupalCreateNode([
      'type' => 'localgov_event',
      'title' => 'Recurring with future events.',
      'body' => 'This event has occurrences in the future and should remain.',
      'status' => 1,
      'localgov_event_date' => $this->getPastAndFutureDate(),
      'moderation_state' => 'published',
    ]);
    $past_and_future_event->save();

    $this->drupalGet('node/' . $past_and_future_event->id());
    $this->assertSession()->statusCodeEquals(200, 'The event is not accessible.');
    $this->assertTrue($past_and_future_event->isPublished(), 'The event status is should be published.');

    // Set up the configuration to delete events.
    $config = \Drupal::configFactory()->getEditable('localgov_expired_events.settings');
    $config->set('expire_days', 1)
      ->set('items_per_cron', 3)
      ->set('action', 'delete')
      ->save();

    // Run cron to process expired events.
    $this->cronRun();

    // The past event should be no longer exist.
    $this->assertNull(\Drupal::entityTypeManager()->getStorage('node')->load($past_event->id()), 'Past event should be deleted.');

    // The future and recurring event should still exist.
    $this->assertNotNull(\Drupal::entityTypeManager()->getStorage('node')->load($future_event->id()), 'Future event should not be deleted.');
    $this->assertNotNull(\Drupal::entityTypeManager()->getStorage('node')->load($past_and_future_event->id()), 'Recurring past/future event should not be deleted.');

  }

  /**
   * Create a past event date.
   */
  private function getPastDate(): array {
    $date = new DrupalDateTime('now', 'UTC');
    $date->modify('midnight');
    // Set date in the past, allowing for expire_days.
    $start_date = $date->modify("-2 days")->format('Y-m-d\TH:i:s');
    $end_date = $date->modify("+2 hours")->format('Y-m-d\TH:i:s');

    return [
      'value' => $start_date,
      'end_value' => $end_date,
      'rrule' => '',
      'timezone' => 'UTC',
    ];
  }

  /**
   * Create a future event date.
   */
  private function getFutureDate(): array {

    $date = new DrupalDateTime('now', 'UTC');
    $date->modify('midnight');
    // Set date in the past, allowing for expire_days.
    $start_date = $date->modify("+1 days")->format('Y-m-d\TH:i:s');
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
   * With dates in the past and future.
   */
  private function getPastAndFutureDate(): array {
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

  /**
   * Tests that an expired event is unpublished.
   *
   * Creates 3 events
   * a past event
   * a future event,
   * a recurring event that spans both
   * then runs cron to unpublish the past event.
   */
  public function testUnpublishEventsNonWorkflow(): void {

    // Remove the workflow from the localgov_event content type.
    $entity = Workflow::load('localgov_editorial');
    $type = $entity->getTypePlugin();
    $type->removeEntityTypeAndBundle('node', 'localgov_event');
    $entity->save();

    // Set up the events.
    $past_event = $this->drupalCreateNode([
      'type' => 'localgov_event',
      'title' => 'Due to be unpublished.',
      'body' => 'This event is in the past and should be unpublished.',
      'status' => 1,
      'localgov_event_date' => $this->getPastDate(),
    ]);
    $past_event->save();

    $this->drupalGet('node/' . $past_event->id());
    $this->assertSession()->statusCodeEquals(200, 'The event is not accessible.');
    $this->assertTrue($past_event->isPublished(), 'The event status is should be published.');

    $future_event = $this->drupalCreateNode([
      'type' => 'localgov_event',
      'title' => 'Future event.',
      'body' => 'This event is in the future event and should remain.',
      'status' => 1,
      'localgov_event_date' => $this->getFutureDate(),
    ]);
    $future_event->save();

    $this->drupalGet('node/' . $future_event->id());
    $this->assertSession()->statusCodeEquals(200, 'The event is not accessible.');
    $this->assertTrue($future_event->isPublished(), 'The event status is should be published.');

    // Create a 10 day recurring event with dates in the past and future.
    $past_and_future_event = $this->drupalCreateNode([
      'type' => 'localgov_event',
      'title' => 'Recurring with future events.',
      'body' => 'This event has occurrences in the future and should remain.',
      'status' => 1,
      'localgov_event_date' => $this->getPastAndFutureDate(),
    ]);
    $past_and_future_event->save();

    $this->drupalGet('node/' . $past_and_future_event->id());
    $this->assertSession()->statusCodeEquals(200, 'The event is not accessible.');
    $this->assertTrue($past_and_future_event->isPublished(), 'The event status is should be published.');

    // Set up the configuration to unpublish events.
    $config = \Drupal::configFactory()->getEditable('localgov_expired_events.settings');
    $config->set('expire_days', 1)
      ->set('items_per_cron', 3)
      ->set('action', 'unpublish')
      ->save();

    // Run cron to process expired events.
    $this->cronRun();

    // The past event should be unpublished.
    $refreshed_event = \Drupal::entityTypeManager()->getStorage('node')->load($past_event->id());
    $this->assertEquals("0", $refreshed_event->get('status')->value, 'Past event status should be unpublished');

    // The future and recurring event remain published.
    $refreshed_event = \Drupal::entityTypeManager()->getStorage('node')->load($future_event->id());
    $this->assertEquals("1", $refreshed_event->get('status')->value, 'Future event status should be published');
    $refreshed_event = \Drupal::entityTypeManager()->getStorage('node')->load($past_and_future_event->id());
    $this->assertEquals("1", $refreshed_event->get('status')->value, 'Past/future event status should be published');

  }

}
