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

    $form['markup_intro'] = ['#markup' => $this->t('<p><strong>Action to take on events after they expire</strong></p>')];

    $form['action'] = [
      '#type' => 'radios',
      '#title' => $this->t('When events have expired:'),
      '#options' => [
        'none' => $this->t('Do nothing'),
        'unpublish' => $this->t('Unpublish'),
        'delete' => $this->t('Delete'),
      ],
      '#config_target' => 'localgov_events_remove_expired.settings:action',
      '#description' => $this->t('Warning: deleted events are removed from the database and may not be recoverable.'),
    ];

    $form['expire_days'] = [
      '#type' => 'number',
      '#title' => $this->t('How many days after events expire should action be taken?'),
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('The action will take place after midnight following the event end date.'),
      '#config_target' => 'localgov_events_remove_expired.settings:expire_days',
    ];

    $form['items_per_cron'] = [
      '#type' => 'number',
      '#title' => $this->t('The events will be processed in batches by a cron run. How many events in a batch?'),
      '#min' => 0,
      '#step' => 1,
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
