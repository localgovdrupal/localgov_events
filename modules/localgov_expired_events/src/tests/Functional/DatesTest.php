<?php

declare(strict_types=1);

namespace Drupal\Tests\localgov_expired_events\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Confirm the module's date functionality works as intended.
 *
 * @group localgov_expired_events
 */
class DatesTest extends BrowserTestBase {

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
   * Tests that an expired event is archived.
   */
  public function testExpiredEventArchived() {

    $event_date = [
      'value' => '2022-07-22T16:00:00',
      'end_value' => '2022-07-22T18:00:00',
      'rrule' => '',
      'timezone' => 'Europe/London',
      'infinite' => 0,
      'rrule' => 'FREQ=DAILY;COUNT=10',
    ];

    $event = $this->drupalCreateNode([
      'type' => 'localgov_event',
      'title' => 'This event should be archived.',
      'body' => 'This event should be archived.',
      'status' => 1,
      'localgov_event_date' => $event_date,
      'moderation_state' => 'published',
    ]);
    $event->save();

    $this->drupalGet('node/' . $event->id());
    $this->assertSession()->statusCodeEquals(200, 'The event is not accessible.');
    $this->assertTrue($event->isPublished(), 'The event status is should be published.');
    $this->assertEquals("2022-07-22T18:00:00", $event->get('localgov_event_date')->end_value, 'Event end date is not correct.');

    // Set up the configuration to archive events.
    $config = \Drupal::configFactory()->getEditable('localgov_expired_events.settings');
    $config->set('expire_days', 1)
      ->set('items_per_cron', 1)
      ->set('action', 'archived')
      ->save();

    // Run cron to process expired events.
    $this->cronRun();

    $refreshed_event = \Drupal::entityTypeManager()->getStorage('node')->load($event->id());
    $this->assertFalse($refreshed_event->isPublished(), 'The event status is should be unpublished.');
    // Check that the status is set to archived.
    $this->assertEquals("0", $refreshed_event->get('status')->value, 'Event status should be archived');

  }

  /**
   * Tests that an expired event is archived.
   */
  public function testExpiredEventDeleted() {

    $event_date = [
      'value' => '2022-07-22T16:00:00',
      'end_value' => '2022-07-22T18:00:00',
      'rrule' => '',
      'timezone' => 'Europe/London',
      'infinite' => 0,
      'rrule' => 'FREQ=DAILY;COUNT=10',
    ];

    $event = $this->drupalCreateNode([
      'type' => 'localgov_event',
      'title' => 'This event should be deleted.',
      'body' => 'This event should be deleted.',
      'status' => 1,
      'localgov_event_date' => $event_date,
      'moderation_state' => 'published',
    ]);
    $event->save();

    $this->drupalGet('node/' . $event->id());
    $this->assertSession()->statusCodeEquals(200, 'The event is not accessible.');
    $this->assertTrue($event->isPublished(), 'The event status is should be published.');
    $this->assertEquals("2022-07-22T18:00:00", $event->get('localgov_event_date')->end_value, 'Event end date is not correct.');

    // Set up the configuration to delete events.
    $config = \Drupal::configFactory()->getEditable('localgov_expired_events.settings');
    $config->set('expire_days', 1)
      ->set('items_per_cron', 1)
      ->set('action', 'delete')
      ->save();

    // Run cron to process expired events.
    $this->cronRun();

    $refreshed_event = \Drupal::entityTypeManager()->getStorage('node')->load($event->id());
    // Check that the status is set to archived.
    $this->assertNull($refreshed_event, 'Event should be deleted.');

  }

  /**
   * Tests that an expired event is unpublished.
   */
  public function testExpiredEventUnpublished() {

    $event_date = [
      'value' => '2022-07-22T16:00:00',
      'end_value' => '2022-07-22T18:00:00',
      'rrule' => '',
      'timezone' => 'Europe/London',
      'infinite' => 0,
      'rrule' => 'FREQ=DAILY;COUNT=10',
    ];

    $event = $this->drupalCreateNode([
      'type' => 'localgov_event',
      'title' => 'This event should be deleted.',
      'body' => 'This event should be deleted.',
      'status' => 1,
      'localgov_event_date' => $event_date,
      'moderation_state' => 'published',
    ]);
    $event->save();

    $this->drupalGet('node/' . $event->id());
    $this->assertSession()->statusCodeEquals(200, 'The event is not accessible.');
    $this->assertTrue($event->isPublished(), 'The event status is should be published.');
    $this->assertEquals("2022-07-22T18:00:00", $event->get('localgov_event_date')->end_value, 'Event end date is not correct.');

    // Set up the configuration to unpublish events.
    $config = \Drupal::configFactory()->getEditable('localgov_expired_events.settings');
    $config->set('expire_days', 1)
      ->set('items_per_cron', 1)
      ->set('action', 'unpublished')
      ->save();

    // Run cron to process expired events.
    $this->cronRun();

    $refreshed_event = \Drupal::entityTypeManager()->getStorage('node')->load($event->id());
    // Check that the status is set to unpublished.
    $this->assertEquals("1", $refreshed_event->get('status')->value, 'Event status should be unpublished');

  }

}
