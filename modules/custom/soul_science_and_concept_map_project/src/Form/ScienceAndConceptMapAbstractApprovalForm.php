<?php

/**
 * @file
 * Contains \Drupal\science_and_concept_map\Form\ScienceAndConceptMapAbstractApprovalForm.
 */

namespace Drupal\science_and_concept_map\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;


class ScienceAndConceptMapAbstractApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'science_and_concept_map_abstract_approval_form';
  }


/**
 * Form build.
 */
public function buildForm(array $form, FormStateInterface $form_state) {
 
  $request = \Drupal::request();
  $solution_id = (int) (\Drupal::routeMatch()->getParameter('solution_id')
    ?? \Drupal::routeMatch()->getParameter('id')
    ?? $request->attributes->get('solution_id')
    ?? $request->query->get('solution_id')
    ?? 0);

  /* get solution details */
  $query = \Drupal::database()->select('soul_science_and_concept_map_solution');
  $query->fields('soul_science_and_concept_map_solution');
  $query->condition('id', $solution_id);
  $solution_q = $query->execute();
  $solution_data = $solution_q->fetchObject();

  if (!$solution_data) {
    \Drupal::messenger()->addStatus(t('Invalid solution selected.'));
    // Replace drupal_goto() with a redirect on the form state.
    $form_state->setRedirectUrl(Url::fromUserInput('/science-and-concept-map-project/code-approval'));
    return [];
  }

  if ($solution_data->approval_status == 1) {
    \Drupal::messenger()->addError(t('This solution has already been approved. Are you sure you want to change the approval status?'));
  }
  if ($solution_data->approval_status == 2) {
    \Drupal::messenger()->addError(t('This solution has already been dis-approved. Are you sure you want to change the approval status?'));
  }

  /* get experiment data */
  $query = \Drupal::database()->select('soul_science_and_concept_map_experiment');
  $query->fields('soul_science_and_concept_map_experiment');
  $query->condition('id', $solution_data->experiment_id);
  $experiment_q = $query->execute();
  $experiment_data = $experiment_q->fetchObject();

  /* get proposal data */
  $query = \Drupal::database()->select('soul_science_and_concept_map_proposal');
  $query->fields('soul_science_and_concept_map_proposal');
  $query->condition('id', $experiment_data->proposal_id);
  $proposal_q = $query->execute();
  $proposal_data = $proposal_q->fetchObject();

  /* get solution provider details */
  $solution_provider_user_name = '';
  $user_data_entity = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->solution_provider_uid);
  if ($user_data_entity) {
    // Use display name accessor in D8+.
    $solution_provider_user_name = $user_data_entity->getDisplayName();
  }
  else {
    $solution_provider_user_name = '';
  }

  $form['#tree'] = TRUE;

  $form['lab_title'] = [
    '#type' => 'item',
    '#markup' => $proposal_data->lab_title,
    '#title' => t('Title of the Project'),
  ];
  $form['name'] = [
    '#type' => 'item',
    '#markup' => $proposal_data->name,
    '#title' => t('Contributor Name'),
  ];
  $form['experiment']['number'] = [
    '#type' => 'item',
    '#markup' => $experiment_data->number,
    '#title' => t('Experiment Number'),
  ];
  $form['experiment']['title'] = [
    '#type' => 'item',
    '#markup' => $experiment_data->title,
    '#title' => t('Title of the Experiment'),
  ];

  $form['code_number'] = [
    '#type' => 'item',
    '#markup' => $solution_data->code_number,
    '#title' => t('Code No'),
  ];
  $form['code_caption'] = [
    '#type' => 'item',
    '#markup' => $solution_data->caption,
    '#title' => t('Caption'),
  ];

  /* get solution files */
  $solution_files_html = '';
  $query = \Drupal::database()->select('soul_science_and_concept_map_solution_files');
  $query->fields('soul_science_and_concept_map_solution_files');
  $query->condition('solution_id', $solution_id);
  $query->orderBy('id', 'ASC');
  $solution_files_q = $query->execute();
  if ($solution_files_q) {
    while ($solution_files_data = $solution_files_q->fetchObject()) {
      $code_file_type = '';
      switch ($solution_files_data->filetype) {
        case 'S':
          $code_file_type = 'Source';
          break;
        case 'R':
          $code_file_type = 'Result';
          break;
        case 'X':
          $code_file_type = 'Xcox';
          break;
        case 'U':
          $code_file_type = 'Unknown';
          break;
        default:
          $code_file_type = 'Unknown';
          break;
      }
      // Links left commented as in your code (l() deprecated).
      // $solution_files_html .= l($solution_files_data->filename, 'science-and-concept-map-project/download/file/' . $solution_files_data->id) . ' (' . $code_file_type . ')' . '<br/>';
      // if (strlen($solution_files_data->pdfpath) >= 5) { ... }
    }
  }

  /* get dependencies files */
  $query = \Drupal::database()->select('soul_science_and_concept_map_solution_dependency');
  $query->fields('soul_science_and_concept_map_solution_dependency');
  $query->condition('solution_id', $solution_id);
  $query->orderBy('id', 'ASC');
  $dependency_q = $query->execute();
  while ($dependency_data = $dependency_q->fetchObject()) {
    $query = \Drupal::database()->select('soul_science_and_concept_map_dependency_files');
    $query->fields('soul_science_and_concept_map_dependency_files');
    $query->condition('id', $dependency_data->dependency_id);
    $dependency_files_q = $query->execute();
    $dependency_files_data = $dependency_files_q->fetchObject();
    $solution_file_type = 'Dependency file';
    // $solution_files_html .= l($dependency_files_data->filename, 'science-and-concept-map-project/download/dependency/' . $dependency_files_data->id) . ' (Dependency)<br/>';
  }

  $form['solution_files'] = [
    '#type' => 'item',
    '#markup' => $solution_files_html,
    '#title' => t('Solution'),
  ];

  $form['approved'] = [
    '#type' => 'radios',
    '#options' => [
      '0' => 'Pending',
      '1' => 'Approved',
      '2' => 'Dis-approved (Solution will be deleted)',
    ],
    '#title' => t('Approval'),
    '#default_value' => $solution_data->approval_status,
  ];
  $form['message'] = [
    '#type' => 'textarea',
    '#title' => t('Reason for dis-approval'),
    '#states' => [
      'visible' => [
        ':input[name="approved"]' => ['value' => '2'],
      ],
      'required' => [
        ':input[name="approved"]' => ['value' => '2'],
      ],
    ],
  ];
  $form['submit'] = [
    '#type' => 'submit',
    '#value' => t('Submit'),
  ];

  return $form;
}

/**
 * Form validate.
 */
public function validateForm(array &$form, FormStateInterface $form_state) {
  if ($form_state->getValue(['approved']) == 2) {
    if (strlen(trim($form_state->getValue(['message']))) <= 30) {
      $form_state->setErrorByName('message', t('Please mention the reason for disapproval.'));
    }
  }
  return;
}

/**
 * Form submit.
 */
public function submitForm(array &$form, FormStateInterface $form_state) {
  $current_user = \Drupal::currentUser();
  // Replace deprecated arg(3).
  $request = \Drupal::request();
  $solution_id = (int) (\Drupal::routeMatch()->getParameter('solution_id')
    ?? \Drupal::routeMatch()->getParameter('id')
    ?? $request->attributes->get('solution_id')
    ?? $request->query->get('solution_id')
    ?? 0);

  /* get solution details */
  $query = \Drupal::database()->select('soul_science_and_concept_map_solution');
  $query->fields('soul_science_and_concept_map_solution');
  $query->condition('id', $solution_id);
  $solution_q = $query->execute();
  $solution_data = $solution_q->fetchObject();

  if (!$solution_data) {
    \Drupal::messenger()->addStatus(t('Invalid solution selected.'));
    $form_state->setRedirectUrl(Url::fromUserInput('/soul_science_and_concept_map/code_approval'));
    return;
  }

  /* get experiment data */
  $query = \Drupal::database()->select('soul_science_and_concept_map_experiment');
  $query->fields('soul_science_and_concept_map_experiment');
  $query->condition('id', $solution_data->experiment_id);
  $experiment_q = $query->execute();
  $experiment_data = $experiment_q->fetchObject();

  /* get proposal data */
  $query = \Drupal::database()->select('soul_science_and_concept_map_proposal');
  $query->fields('soul_science_and_concept_map_proposal');
  $query->condition('id', $experiment_data->proposal_id);
  $proposal_q = $query->execute();
  $proposal_data = $proposal_q->fetchObject();

  $user_entity = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
  $solution_prove_user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->solution_provider_uid);

  // Config replacements for variable_get().
  $config = \Drupal::config('science_and_concept_map.settings');
  $from = $config->get('science_and_concept_map_from_email') ?: '';
  $bcc = $config->get('science_and_concept_map_emails') ?: '';
  $cc  = $config->get('science_and_concept_map_cc_emails') ?: '';

  // Language replacement for language_default().
  $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

  // Mail manager (replacement for drupal_mail()).
  $mail_manager = \Drupal::service('plugin.manager.mail');

  if ($form_state->getValue(['approved']) == "0") {
    $query = "UPDATE {soul_science_and_concept_map_solution} SET approval_status = 0, approver_uid = :approver_uid, approval_date = :approval_date WHERE id = :solution_id";
    $args = [
      ":approver_uid" => $current_user->id(),
      ":approval_date" => time(),
      ":solution_id" => $solution_id,
    ];
    \Drupal::database()->query($query, $args);

    /* sending email */
    $email_to = $user_entity ? $user_entity->getEmail() : '';
    $params = [];
    $params['solution_pending']['solution_id'] = $solution_id;
    $params['solution_pending']['user_id'] = $user_entity ? $user_entity->id() : 0;
    $params['solution_pending']['headers'] = [
      'From' => $from,
      'MIME-Version' => '1.0',
      'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8Bit',
      'X-Mailer' => 'Drupal',
      'Cc' => $cc,
      'Bcc' => $bcc,
    ];
    $result = $mail_manager->mail('science_and_concept_map', 'solution_pending', $email_to, $langcode, $params, $from, TRUE);
    if (empty($result) || (isset($result['result']) && !$result['result'])) {
      \Drupal::messenger()->addError('Error sending email message.');
    }
  }
  else {
    if ($form_state->getValue(['approved']) == "1") {
      $query = "UPDATE {soul_science_and_concept_map_solution} SET approval_status = 1, approver_uid = :approver_uid, approval_date = :approval_date WHERE id = :solution_id";
      $args = [
        ":approver_uid" => $current_user->id(),
        ":approval_date" => time(),
        ":solution_id" => $solution_id,
      ];
      \Drupal::database()->query($query, $args);

      /* sending email */
      $email_to = $user_entity ? $user_entity->getEmail() : '';
      $params = [];
      $params['solution_approved']['solution_id'] = $solution_id;
      $params['solution_approved']['user_id'] = $user_entity ? $user_entity->id() : 0;
      $params['solution_approved']['headers'] = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];
      $result = $mail_manager->mail('science_and_concept_map', 'solution_approved', $email_to, $langcode, $params, $from, TRUE);
      if (empty($result) || (isset($result['result']) && !$result['result'])) {
        \Drupal::messenger()->addError('Error sending email message.');
      }
    }
    else {
      if ($form_state->getValue(['approved']) == "2") {
        if (soul_science_and_concept_map_delete_solution($solution_id)) {
          /* sending email */
          $email_to = $user_entity ? $user_entity->getEmail() : '';
          $params = [];
          $params['solution_disapproved']['experiment_number'] = $experiment_data->number;
          $params['solution_disapproved']['experiment_title'] = $experiment_data->title;
          $params['solution_disapproved']['solution_number'] = $solution_data->code_number;
          $params['solution_disapproved']['solution_caption'] = $solution_data->caption;
          $params['solution_disapproved']['user_id'] = $user_entity ? $user_entity->id() : 0;
          $params['solution_disapproved']['message'] = $form_state->getValue(['message']);
          $params['solution_disapproved']['headers'] = [
            'From' => $from,
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
            'Content-Transfer-Encoding' => '8Bit',
            'X-Mailer' => 'Drupal',
            'Cc' => $cc,
            'Bcc' => $bcc,
          ];
          $result = $mail_manager->mail('science_and_concept_map', 'solution_disapproved', $email_to, $langcode, $params, $from, TRUE);
          if (empty($result) || (isset($result['result']) && !$result['result'])) {
            \Drupal::messenger()->addError('Error sending email message.');
          }
        }
        else {
          \Drupal::messenger()->addError('Error disapproving and deleting solution. Please contact administrator.');
        }
      }
    }
  }

  \Drupal::messenger()->addStatus('Updated successfully.');
  // Replace drupal_goto() with redirect.
  $form_state->setRedirectUrl(Url::fromUserInput('/science-and-concept-map-project/code-approval'));
}

}
?>
