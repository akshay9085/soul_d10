<?php

/**
 * @file
 * Contains \Drupal\science_and_concept_map\Form\ScienceAndConceptMapSettingsForm.
 */

namespace Drupal\science_and_concept_map\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class ScienceAndConceptMapSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'science_and_concept_map_settings_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['emails'] = [
      '#type' => 'textfield',
      '#title' => t('(Bcc) Notification emails'),
      '#description' => t('Specify emails id for Bcc option of mail system with comma separated'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_emails'),
    ];
    $form['cc_emails'] = [
      '#type' => 'textfield',
      '#title' => t('(Cc) Notification emails'),
      '#description' => t('Specify emails id for Cc option of mail system with comma separated'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_cc_emails'),
    ];
    $form['from_email'] = [
      '#type' => 'textfield',
      '#title' => t('Outgoing from email address'),
      '#description' => t('Email address to be display in the from field of all outgoing messages'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_from_email'),
    ];
    $form['extensions']['abstract_upload'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed file extensions for uploading abstract file'),
      '#description' => t('A comma separated list WITHOUT SPACE of source file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_abstract_upload_extensions'),
    ];
    $form['extensions']['report_upload'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed file extensions for report'),
      '#description' => t('A comma separated list WITHOUT SPACE of pdf file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_report_upload_extensions'),
    ];
    $form['extensions']['science_and_concept_map_code_upload'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed extensions for code files'),
      '#description' => t('A comma separated list WITHOUT SPACE of pdf file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_code_files_extensions'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    return;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('science_and_concept_map.settings')->set('science_and_concept_map_emails', $form_state->getValue(['emails']))->save();
    \Drupal::configFactory()->getEditable('science_and_concept_map.settings')->set('science_and_concept_map_cc_emails', $form_state->getValue(['cc_emails']))->save();
    \Drupal::configFactory()->getEditable('science_and_concept_map.settings')->set('science_and_concept_map_from_email', $form_state->getValue(['from_email']))->save();
    \Drupal::configFactory()->getEditable('science_and_concept_map.settings')->set('science_and_concept_map_abstract_upload_extensions', $form_state->getValue(['abstract_upload']))->save();
    \Drupal::configFactory()->getEditable('science_and_concept_map.settings')->set('science_and_concept_map_report_upload_extensions', $form_state->getValue(['report_upload']))->save();
    \Drupal::configFactory()->getEditable('science_and_concept_map.settings')->set('science_and_concept_map_code_files_extensions', $form_state->getValue(['science_and_concept_map_code_upload']))->save();
    \Drupal::messenger()->addStatus(t('Settings updated'));
  }

}
?>
