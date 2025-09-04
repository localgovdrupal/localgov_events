<?php

namespace Drupal\localgov_events_remove_expired\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure expired event settings for this site.
 */
class ExpiredEventSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'localgov_events_remove_expired_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['action'] = [
      '#type' => 'radios',
      '#title' => $this->t('Action after Events are expired:'),
      '#options' => [
        'none' => $this->t('None'),
        'unpublish' => $this->t('Unpublish'),
        'delete' => $this->t('Delete'),
      ],
      '#config_target' => 'localgov_events_remove_expired.settings:action',
    ];

    $form['expire_days'] = [
      '#type' => 'textfield',
      '#title' => $this->t('How many days will event be unpublished or deleted after expired ?'),
      '#description' => $this->t('Set 0 to delete or unpublished events right after events are expired.'),
      '#config_target' => 'localgov_events_remove_expired.settings:expire_days',
    ];

    $form['items_per_cron'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of items processed per cron run:'),
      '#config_target' => 'localgov_events_remove_expired.settings:items_per_cron',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'localgov_events_remove_expired.settings',
    ];
  }

}
