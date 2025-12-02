<?php

/**
 * @file
 * Contains \Drupal\science_and_concept_map\Form\ScienceAndConceptMapRunForm.
 */

namespace Drupal\science_and_concept_map\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\user\Entity\User;

class ScienceAndConceptMapRunForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'science_and_concept_map_run_form';
  }

   public function buildForm(array $form, FormStateInterface $form_state) {

    $options_first = $this->_list_of_science_and_concept_map();

    // Get proposal ID from query string (supports old D7 arg() behavior)
    $request = \Drupal::request();
    $url_science_and_concept_map_id = (int) ($request->query->get('id') ?? 0);

    $science_and_concept_map_data = $this->_science_and_concept_map_information($url_science_and_concept_map_id);
    if ($science_and_concept_map_data == 'Not found') {
      $url_science_and_concept_map_id = 0;
    }

    if (!$url_science_and_concept_map_id) {
      $selected = $form_state->getValue('science_and_concept_map') ?? key($options_first);
    }
    else {
      $selected = $url_science_and_concept_map_id;
    }

    $form['science_and_concept_map'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the science and concept map'),
      '#options' => $options_first,
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::science_and_concept_map_project_details_callback',
        'wrapper' => 'ajax_science_and_concept_map_details',
      ],
    ];

    $form['science_and_concept_map_details'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ajax_science_and_concept_map_details'],
      'content' => $this->_science_and_concept_map_details($selected),
    ];

    // Download links:
    $abstract_url = Url::fromRoute('science_and_concept_map.download_upload_file', [
      'proposal_id' => $selected,
    ]);
    $abstract_link = Link::fromTextAndUrl($this->t('Download Abstract'), $abstract_url)->toString();

    $full_url = Url::fromRoute('science_and_concept_map.download_full_project', [
      'id' => $selected,
    ]);
    $full_link = Link::fromTextAndUrl($this->t('Download science and concept map'), $full_url)->toString();

    $form['selected_science_and_concept_map'] = [
      '#type' => 'markup',
      '#markup' => '<div id="ajax_selected_science_and_concept_map">' . $abstract_link . '<br>' . $full_link . '</div>',
    ];

    return $form;
  }

  /**
   * AJAX callback.
   */
  public function science_and_concept_map_project_details_callback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $selected = $form_state->getValue('science_and_concept_map');

    // Replace details section
    $details_markup = $this->_science_and_concept_map_details($selected);
    $response->addCommand(new HtmlCommand('#ajax_science_and_concept_map_details', \Drupal::service('renderer')->render($details_markup)));

    // Replace download links
    $abstract_url = Url::fromRoute('science_and_concept_map.download_upload_file', ['proposal_id' => $selected]);
    $abstract_link = Link::fromTextAndUrl($this->t('Download Abstract'), $abstract_url)->toString();

    $full_url = Url::fromRoute('science_and_concept_map.download_full_project', ['id' => $selected]);
    $full_link = Link::fromTextAndUrl($this->t('Download science and concept map'), $full_url)->toString();

    $links_markup = $abstract_link . '<br>' . $full_link;

    $response->addCommand(new HtmlCommand('#ajax_selected_science_and_concept_map', $links_markup));

    return $response;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No submit action required.
  }

public function _list_of_science_and_concept_map() {
  $science_and_concept_map_titles = [
    0 => 'Please select...',
  ];

  $database = \Drupal::database();
  $query = $database->select('soul_science_and_concept_map_proposal', 'p')
    ->fields('p')
    ->condition('approval_status', 3)
    ->orderBy('project_title', 'ASC');

  $result = $query->execute()->fetchAll();

  foreach ($result as $row) {
    $science_and_concept_map_titles[$row->id] = $row->project_title . ' (Proposed by ' . $row->name_title . ' ' . $row->contributor_name . ')';
  }

  return $science_and_concept_map_titles;
}

public function _science_and_concept_map_information($proposal_id) {
  $database = \Drupal::database();
  $query = $database->select('soul_science_and_concept_map_proposal', 'p')
    ->fields('p')
    ->condition('id', $proposal_id)
    ->condition('approval_status', 3);

  return $query->execute()->fetchObject() ?: NULL;
}

public function _science_and_concept_map_details($science_and_concept_map_default_value) {
  if ($science_and_concept_map_default_value == 0) {
    return [];
  }

  $details = $this->_science_and_concept_map_information($science_and_concept_map_default_value);
  if (!$details) {
    return ['#markup' => '<p>Not found.</p>'];
  }

  // Convert reference URLs to clickable links.
  if (!empty($details->reference)) {
    $pattern = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i';
    $reference = preg_replace($pattern, '<a href="$0" target="_blank">$0</a>', $details->reference);
  }
  else {
    $reference = 'Not provided';
  }

  $markup = "
    <span style='color:#800000;'><strong>About the science and concept map</strong></span>
    <ul>
      <li><strong>Proposer Name:</strong> {$details->name_title} {$details->contributor_name}</li>
      <li><strong>Title:</strong> <span style='color:#5D125D;'>{$details->project_title}</span></li>
      <li><strong>University:</strong> {$details->university}</li>
      <li><strong>Category:</strong> {$details->category}</li>
      <li><strong>Reference:</strong> {$reference}</li>
    </ul>
  ";

  return [
    '#type' => 'markup',
    '#markup' => $markup,
  ];
}

}
?>
