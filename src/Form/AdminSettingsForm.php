<?php

namespace Drupal\jokes_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AdminSettingsForm.
 *
 * @package Drupal\jokes_api\Form
 */
class AdminSettingsForm extends ConfigFormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'jokes_api_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return [
      'jokes_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('jokes_api.settings');

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Global settings for Jokes FFW module.'),
    ];
    // $form['batch'] = [
    //   '#type' => 'select',
    //   '#title' => 'Choose batch',
    //   '#options' => [
    //     'batch_1' => $this->t('batch 1 - 1000 operations'),
    //     'batch_2' => $this->t('batch 2 - 20 operations.'),
    //   ],
    // ];

    $form['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API URL'),
      '#default_value' => $config->get('api_url'),
    ];

    $form['node_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Node Type'),
      '#default_value' => $config->get('node_type'),
    ];

    $form['page_size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Page Size'),
      '#default_value' => $config->get('page_size'),
    ];

    $form['default_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto publish imported nodes'),
      '#default_value' => $config->get('default_status'),
    ];

    $form['show_published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Only show published nodes'),
      '#default_value' => $config->get('show_published'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $config = $this->config('jokes_api.settings');
    $config->set('api_url', $form_state->getValue('api_url'));
    $config->set('node_type', $form_state->getValue('node_type'));
    $config->set('page_size', $form_state->getValue('page_size'));
    $config->set('default_status', $form_state->getValue('default_status'));
    $config->set('show_published', $form_state->getValue('show_published'));

    $config->save();
    parent::submitForm($form, $form_state);
  }
}
