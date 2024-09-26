<?php
/**
 * @file
 * Contains Drupal\localgov_expired_events\Form\ExpiredEventSettingsForm.
 */

namespace Drupal\localgov_expired_events\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ExpiredEventSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'localgov_expired_events_form';
  }

  /**
   * {@inheritdoc }
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('localgov_expired_events.settings');

    $form['expired_action'] = [
      '#type' => 'radios',
      '#title' => t('Action after Events are expired:'),
      '#options' => array(
        'none' => 'None',
        'unpublished' => t('Unpublished'),
        'delete' => t('Delete'),
      ),
      '#default_value' => $config->get('action') ? $config->get('action') : 'none',
    ];
    // Source text field
    $form['expire_days'] = [
      '#type' => 'textfield',
      '#title' => t('How many days will event be unpublished or deleted after expired ? '),
      '#default_value' =>  $config->get('expire_days') ? $config->get('expire_days') : 0,
      '#description' => t('Set 0 to delete or unpublished events right after events are expired.'),
    ];

    // Source text field
    $form['items_per_cron'] = [
      '#type' => 'textfield',
      '#title' => t('Number of items processed per cron run:'),
      '#default_value' =>  $config->get('items_per_cron') ? $config->get('items_per_cron') : 0,
    ];
    return $form;
  }

  /**
   * {@inheritdoc }
   */
  protected function getEditableConfigNames() {
    return [
      'localgov_expried_events.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();
    if(!is_numeric($values['expire_days'])) {
      $form_state->setErrorByName('expire_days', $this->t('Must be a valid number'));
    }
    if(!is_numeric($values['items_per_cron'])) {
      $form_state->setErrorByName('items_per_cron', $this->t('Must be a valid number'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $configFactory = \Drupal::configFactory();
    $config = $configFactory->getEditable('localgov_expired_events.settings');
    $config->set('action', $form_state->getValue('expired_action'))
      ->set('expire_days',  $form_state->getValue('expire_days'))
      ->set('items_per_cron', $form_state->getValue('items_per_cron'))
      ->save();
    return parent::submitForm($form, $form_state);
  }

}