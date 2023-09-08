<?php

namespace Drupal\Tests\localgov_events\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests LocalGov Event page.
 *
 * @group localgov_campaigns
 */
class EventPageTest extends BrowserTestBase {

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
   * Test using the minimal profile.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * Test using the stark theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to bypass content access checks.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field_ui',
    'localgov_events',
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
    ]);
  }

  /**
   * Verifies basic functionality with all modules.
   */
  public function testEventFields() {
    $this->drupalLogin($this->adminUser);

    // Check standard fields.
    $this->drupalGet('/admin/structure/types/manage/localgov_event/fields');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('body');
    $this->assertSession()->pageTextContains('localgov_event_call_to_action');
    $this->assertSession()->pageTextContains('localgov_event_categories');
    $this->assertSession()->pageTextContains('localgov_event_date');
    $this->assertSession()->pageTextContains('localgov_event_image');
    $this->assertSession()->pageTextContains('localgov_event_locality');
    $this->assertSession()->pageTextContains('localgov_event_price');
    $this->assertSession()->pageTextNotContains('localgov_event_provider');
    $this->assertSession()->pageTextNotContains('localgov_event_venue');

    // Check optional provider field.
    \Drupal::service('module_installer')->install(['localgov_directories_page']);
    $this->drupalGet('/admin/structure/types/manage/localgov_event/fields');
    $this->assertSession()->pageTextContains('localgov_event_provider');
    $this->assertSession()->pageTextNotContains('localgov_event_venue');

    // Check optional venue field.
    \Drupal::service('module_installer')->install(['localgov_directories_venue']);
    $this->drupalGet('/admin/structure/types/manage/localgov_event/fields');
    $this->assertSession()->pageTextContains('localgov_event_venue');
  }

}
