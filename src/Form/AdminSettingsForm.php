<?php

namespace Drupal\jokes_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jokes_api\Service\JokesApi;

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
    $config = JokesApi::getInstance()->getSettings();

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Global settings for Jokes FFW module.'),
    ];

    $form[JokesApi::PARAM_API_URL] = [
      '#type' => 'textfield',
      '#title' => $this->t('API URL'),
      '#default_value' => $config->get(JokesApi::PARAM_API_URL),
    ];

    $form[JokesApi::PARAM_NODE_TYPE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Node Type'),
      '#default_value' => $config->get(JokesApi::PARAM_NODE_TYPE),
    ];

    $form[JokesApi::PARAM_PAGE_SIZE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Page Size'),
      '#default_value' => $config->get(JokesApi::PARAM_PAGE_SIZE),
    ];

    $form[JokesApi::PARAM_DEFAULT_STATUS] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto publish imported nodes'),
      '#default_value' => $config->get(JokesApi::PARAM_DEFAULT_STATUS),
    ];

    $form[JokesApi::PARAM_SHOW_PUBLISHED] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Only show published nodes'),
      '#default_value' => $config->get(JokesApi::PARAM_SHOW_PUBLISHED),
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
    $config = JokesApi::getInstance()->getSettingsForEdit();
    $config->set(JokesApi::PARAM_API_URL, $form_state->getValue(JokesApi::PARAM_API_URL));
    $config->set(JokesApi::PARAM_NODE_TYPE, $form_state->getValue(JokesApi::PARAM_NODE_TYPE));
    $config->set(JokesApi::PARAM_PAGE_SIZE, $form_state->getValue(JokesApi::PARAM_PAGE_SIZE));
    $config->set(JokesApi::PARAM_DEFAULT_STATUS, $form_state->getValue(JokesApi::PARAM_DEFAULT_STATUS));
    $config->set(JokesApi::PARAM_SHOW_PUBLISHED, $form_state->getValue(JokesApi::PARAM_SHOW_PUBLISHED));

    $config->save();

    JokesApi::getInstance()->clearCache();
    parent::submitForm($form, $form_state);
  }
}
