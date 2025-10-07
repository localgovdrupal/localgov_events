<?php

declare(strict_types=1);

namespace Drupal\Tests\localgov_events_remove_expired\Functional;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional tests for localgov_events_remove_expired permissions.
 */
class PermissionsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  protected static $modules = [
    'localgov_events_remove_expired',
  ];

  /**
   * Test access permissions.
   */
  public function testConfigformUserAccess(): void {

    // Check that anonymous user cannot access to the configuration page.
    $this->drupalGet('/admin/config/content/expired-events');
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);

    $normalAdminUser = $this->createUser(['access administration pages']);
    $this->drupalLogin($normalAdminUser);

    // Check that authenticated user cannot access to the configuration page.
    $this->drupalGet('/admin/config/content/expired-events');
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);

    // Create a user with permission to access the configuration page.
    $eventsAdmin = $this->createUser(['administer expired events']);
    $this->drupalLogin($eventsAdmin);
    $this->drupalGet('/admin/config/content/expired-events');
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
  }

}
