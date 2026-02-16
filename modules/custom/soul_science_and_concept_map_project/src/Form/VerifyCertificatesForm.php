<?php

namespace Drupal\science_and_concept_map\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Certificate verification form (Drupal 10 port).
 */
class VerifyCertificatesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'verify_certificates_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Ensure global helper is loaded (needed when opcode caches miss the module include).
    $this->ensureVerifyHelperLoaded();

    $form['QR_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter QR Code'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Verify'),
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'progress' => ['message' => ''],
      ],
    ];

    $form['displaytable'] = [
      '#type' => 'markup',
      '#prefix' => '<div><div id="displaytable" style="font-weight:bold;padding-top:10px">',
      '#suffix' => '</div></div>',
      '#markup' => '',
    ];

    // If the form was submitted (non-AJAX), show the result.
    if ($form_state->getValue('QR_code')) {
      $form['displaytable']['#markup'] = \verify_qrcode_fromdb($form_state->getValue('QR_code'));
    }

    return $form;
  }

  /**
   * AJAX submit handler.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $this->ensureVerifyHelperLoaded();
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#displaytable', \verify_qrcode_fromdb($form_state->getValue('QR_code'))));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Non-AJAX submit: nothing else to do; display is handled in buildForm().
  }

  /**
   * Load the legacy helper if the function is not available.
   */
  protected function ensureVerifyHelperLoaded(): void {
    if (!function_exists('verify_qrcode_fromdb')) {
      \Drupal::moduleHandler()->loadInclude('science_and_concept_map', 'php', 'src/Services/ScienceAndConceptMapGlobalFunction');
    }
  }

}
