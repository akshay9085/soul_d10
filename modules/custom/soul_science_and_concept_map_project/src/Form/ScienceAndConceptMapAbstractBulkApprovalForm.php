<?php

namespace Drupal\science_and_concept_map\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\DataCommand;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;
use Drupal\Component\Utility\Html;

class ScienceAndConceptMapAbstractBulkApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'science_and_concept_map_abstract_bulk_approval_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = $this->_bulk_list_of_science_and_concept_map_project();

    // On first build, $selected will be 0 (Please select...).
    $selected = $form_state->getValue('science_and_concept_map_project') ?? key($options);

    $form['science_and_concept_map_project'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the science and concept map project'),
      '#options' => $options,
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::ajax_bulk_science_and_concept_map_abstract_details_callback',
        'event' => 'change',
      ],
      '#suffix' => '<div id="ajax_selected_science_and_concept_map"></div><div id="ajax_selected_science_and_concept_map_pdf"></div>',
    ];

    $form['science_and_concept_map_actions'] = [
      '#type' => 'select',
      '#title' => $this->t('Please select action for science and concept map project'),
      '#options' => $this->_bulk_list_science_and_concept_map_actions(),
      '#default_value' => 0,
      '#prefix' => '<div id="ajax_selected_science_and_concept_map_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="science_and_concept_map_project"]' => ['value' => 0],
        ],
      ],
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Please specify the reason for Resubmission / Dis-Approval'),
      '#prefix' => '<div id="message_submit">',
      '#suffix' => '</div>',
      '#states' => [
        'visible' => [
          [
            ':input[name="science_and_concept_map_actions"]' => ['value' => 3],
          ],
          'or',
          [
            ':input[name="science_and_concept_map_actions"]' => ['value' => 2],
          ],
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#states' => [
        'invisible' => [
          ':input[name="science_and_concept_map_project"]' => ['value' => 0],
        ],
      ],
    ];

    return $form;
  }

  /**
   * AJAX callback for project select.
   */
  public function ajax_bulk_science_and_concept_map_abstract_details_callback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $selected = $form_state->getValue('science_and_concept_map_project');

    if (!empty($selected) && $selected != 0) {
      // Details block.
      $details_markup = $this->_science_and_concept_map_details($selected);
      $response->addCommand(new HtmlCommand('#ajax_selected_science_and_concept_map', $details_markup));

      // Re-render the actions select (inside its wrapper).
      $form['science_and_concept_map_actions']['#options'] = $this->_bulk_list_science_and_concept_map_actions();
      $rendered = \Drupal::service('renderer')->renderRoot($form['science_and_concept_map_actions']);
      $response->addCommand(new ReplaceCommand('#ajax_selected_science_and_concept_map_action', $rendered));
    }
    else {
      // Clear the area.
      $response->addCommand(new HtmlCommand('#ajax_selected_science_and_concept_map', ''));
      $response->addCommand(new DataCommand('#ajax_selected_science_and_concept_map', 'form_state_value_select', $selected));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();

    // IMPORTANT: use simple string keys here.
    $project_id = (int) $form_state->getValue('science_and_concept_map_project');
    $action     = (int) $form_state->getValue('science_and_concept_map_actions');

    if ($project_id === 0) {
      $form_state->setErrorByName('science_and_concept_map_project', $this->t('Please select a project.'));
      return;
    }
    if ($action === 0) {
      $form_state->setErrorByName('science_and_concept_map_actions', $this->t('Please select an action.'));
      return;
    }

    if (!$user->hasPermission('soul science and concept map bulk manage abstract')) {
      \Drupal::messenger()->addError($this->t('You do not have permission to bulk manage science and concept map projects.'));
      return;
    }

    $connection = \Drupal::database();

    $proposal_q = $connection->select('soul_science_and_concept_map_proposal', 'p')
      ->fields('p')
      ->condition('id', $project_id)
      ->execute()
      ->fetchObject();

    if (!$proposal_q) {
      \Drupal::messenger()->addError($this->t('Invalid project selected.'));
      return;
    }

    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    $user_data = $user_storage->load($proposal_q->uid);
    $redirect_route = 'science_and_concept_map.abstract_bulk_approval_form';
    $redirect_params = [];
/*********************************************************** */
    // ACTION 1: APPROVE
    if ($action === 1) {
      $abstracts_q = $connection->select('soul_science_and_concept_map_submitted_abstracts', 'a')
        ->fields('a')
        ->condition('proposal_id', $project_id)
        ->execute();

      foreach ($abstracts_q as $abstract_data) {
        $connection->update('soul_science_and_concept_map_submitted_abstracts')
          ->fields([
            'abstract_approval_status' => 1,
            'is_submitted' => 1,
            'approver_uid' => $user->id(),
          ])
          ->condition('id', $abstract_data->id)
          ->execute();

        $connection->update('soul_science_and_concept_map_submitted_abstracts_file')
          ->fields([
            'file_approval_status' => 1,
            'approvar_uid' => $user->id(),
          ])
          ->condition('submitted_abstract_id', $abstract_data->id)
          ->execute();
      }

      // After approval, go to the proposal status page.
      $redirect_route = 'science_and_concept_map.proposal_status_form';
      $redirect_params = ['id' => $project_id];

      \Drupal::messenger()->addStatus($this->t('Approved science and concept map project.'));
        $form_state->setRedirect($redirect_route, $redirect_params);
      $site_name = \Drupal::config('system.site')->get('name');
      $email_subject = $this->t('[@site][Science and Concept Map] Project files approved', ['@site' => $site_name]);
      $email_body = [
        0 => $this->t('Dear @name,

Your uploaded report and project files for the Science and Concept Map project have been approved.

Full Name: @fullname
Project Title: @title
Category: @category

Best wishes,
@site Team
FOSSEE, IIT Bombay', [
          '@site'     => $site_name,
          '@name'     => $user_data ? $user_data->getAccountName() : '',
          '@fullname' => $proposal_q->name_title . ' ' . $proposal_q->contributor_name,
          '@title'    => $proposal_q->project_title,
          '@category' => $proposal_q->category,
        ]),
      ];

      $this->sendStandardMail($email_subject, $email_body, $user_data);
    }
/*********************************************************** */
    // ACTION 2: RESUBMIT
    elseif ($action === 2) {
      $comment = trim((string) $form_state->getValue('message'));
      if (mb_strlen($comment) <= 30) {
        $form_state->setErrorByName('message', $this->t('Please enter at least 30 characters with the resubmission reason.'));
        return;
      }

      $abstracts_q = $connection->select('soul_science_and_concept_map_submitted_abstracts', 'a')
        ->fields('a')
        ->condition('proposal_id', $project_id)
        ->execute();

      foreach ($abstracts_q as $abstract_data) {
        $connection->update('soul_science_and_concept_map_submitted_abstracts')
          ->fields([
            'abstract_approval_status' => 0,
            'is_submitted' => 0,
            'approver_uid' => $user->id(),
          ])
          ->condition('id', $abstract_data->id)
          ->execute();

        $connection->update('soul_science_and_concept_map_proposal')
          ->fields([
            'is_submitted' => 0,
            'approver_uid' => $user->id(),
          ])
          ->condition('id', $abstract_data->proposal_id)
          ->execute();

        $connection->update('soul_science_and_concept_map_submitted_abstracts_file')
          ->fields([
            'file_approval_status' => 0,
            'approvar_uid' => $user->id(),
          ])
          ->condition('submitted_abstract_id', $abstract_data->id)
          ->execute();
      }

      \Drupal::messenger()->addStatus($this->t('Resubmit the project files.'));

      $site_name = \Drupal::config('system.site')->get('name');
      $email_subject = $this->t('[@site][Science and Concept Map] Project marked pending review', ['@site' => $site_name]);
      $email_body = [
        0 => $this->t('Dear @name,

Kindly resubmit the project files for the project: @title after making changes considering the reviewer comments below.

Comment: @comment

Best wishes,
@site Team
FOSSEE, IIT Bombay', [
          '@site'    => $site_name,
          '@name'    => $user_data ? $user_data->getAccountName() : '',
          '@title'   => $proposal_q->project_title,
          '@comment' => $comment,
        ]),
      ];

      $this->sendStandardMail($email_subject, $email_body, $user_data);
    }
/*********************************************************** */
    // ACTION 3: DISAPPROVE & DELETE
    elseif ($action === 3) {
      $comment = trim((string) $form_state->getValue('message'));
      if (mb_strlen($comment) <= 30) {
        $form_state->setErrorByName('message', $this->t('Please enter at least 30 characters with the disapproval reason.'));
        return;
      }
      if (!$user->hasPermission('soul science and concept map bulk delete abstract')) {
        \Drupal::messenger()->addError($this->t('You do not have permission to Bulk Dis-Approved and Deleted Entire Project.'));
        return;
      }

      // IMPORTANT: make sure the function is correctly namespaced.
      // If you moved it to science_and_concept_map.module (namespace Drupal\science_and_concept_map),
      // then call it as:
      // \Drupal\science_and_concept_map\science_and_concept_map_abstract_delete_project($project_id);
      //
      // If it is global (no namespace), then prefix with backslash:
      // \science_and_concept_map_abstract_delete_project($project_id);

      $transaction = $connection->startTransaction();
      try {
        if (!\science_and_concept_map_abstract_delete_project($project_id)) {
          throw new \RuntimeException('Abstract delete project helper returned FALSE.');
        }
      }
      catch (\Exception $e) {
        $transaction->rollBack();
        \Drupal::logger('science_and_concept_map')->error($e->getMessage());
        \Drupal::messenger()->addError($this->t('Error Dis-Approving and Deleting Entire science and concept map project.'));
        return;
      }
      \Drupal::messenger()->addStatus($this->t('Dis-Approved and Deleted Entire science and concept map project.'));

        $site_name = \Drupal::config('system.site')->get('name');
        $email_subject = $this->t('[@site][Science and Concept Map] Project disapproved and removed', ['@site' => $site_name]);
        $email_body = [
          0 => $this->t('Dear @name,

Your uploaded science and concept map project files for the project "@title" have been disapproved and removed.

Reason for disapproval: @reason

Best wishes,
@site Team
FOSSEE, IIT Bombay', [
            '@site'   => $site_name,
            '@name'   => $user_data ? $user_data->getAccountName() : '',
            '@title'  => $proposal_q->project_title,
            '@reason' => $comment,
          ]),
        ];

        $this->sendStandardMail($email_subject, $email_body, $user_data);
    }

    // Redirect back to the same form route after submit.
    // Make sure this route name matches your .routing.yml.
    $form_state->setRedirect('science_and_concept_map.abstract_bulk_approval_form');
   
  }




  
  /**
   * Send an email using the module standard key.
   */
  protected function sendStandardMail($subject, array $body, $user) {
    $email_to = $user ? $user->getEmail() : '';
    if (empty($email_to)) {
      return;
    }

    $config = \Drupal::config('science_and_concept_map.settings');
    $from = $config->get('science_and_concept_map_from_email');
    $bcc = $config->get('science_and_concept_map_emails');
    $cc  = $config->get('science_and_concept_map_cc_emails');

    $params['standard']['subject'] = $subject;
    $params['standard']['body'] = $body;
    $params['standard']['headers'] = [
      'From' => $from,
      'MIME-Version' => '1.0',
      'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8Bit',
      'X-Mailer' => 'Drupal',
      'Cc' => $cc,
      'Bcc' => $bcc,
    ];

    $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $mail_manager = \Drupal::service('plugin.manager.mail');
    $mail_manager->mail('science_and_concept_map', 'standard', $email_to, $langcode, $params, $from, TRUE);
  }

  /**
   * 1) Build list of approved/submitted proposals as options.
   */
  protected function _bulk_list_of_science_and_concept_map_project(): array {
    $project_titles = ['0' => $this->t('Please select...')];

    $query = \Drupal::database()->select('soul_science_and_concept_map_proposal', 'p');
    $query->fields('p');
    $query->condition('is_submitted', 1);
    $query->condition('approval_status', 1);
    $query->orderBy('project_title', 'ASC');
    $result = $query->execute();

    foreach ($result as $row) {
      $label = Html::escape($row->project_title) . ' (Proposed by ' . Html::escape($row->contributor_name) . ')';
      $project_titles[$row->id] = $label;
    }

    return $project_titles;
  }

  /**
   * 2) Bulk actions select options.
   */
  protected function _bulk_list_science_and_concept_map_actions(): array {
    return [
      0 => $this->t('Please select...'),
      1 => $this->t('Approve Entire science and concept map Project'),
      2 => $this->t('Resubmit Project files'),
      3 => $this->t('Dis-Approve Entire science and concept map Project (This will delete science and concept map Project)'),
    ];
  }

  /**
   * 3) Details block for a selected proposal (safe markup).
   */
  protected function _science_and_concept_map_details($proposal_id) {
    $db = \Drupal::database();

    $proposal_q = $db->select('soul_science_and_concept_map_proposal', 'p')
      ->fields('p')
      ->condition('id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$proposal_q) {
      return Markup::create('<em>Proposal not found.</em>');
    }

    // Abstract file (A).
    $abs_pdf = $db->select('soul_science_and_concept_map_submitted_abstracts_file', 'f')
      ->fields('f')
      ->condition('proposal_id', $proposal_id)
      ->condition('filetype', 'A')
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    $abstract_filename = (!empty($abs_pdf) && !empty($abs_pdf->filename) && $abs_pdf->filename !== 'NULL')
      ? Html::escape($abs_pdf->filename)
      : 'File not uploaded';

    // Code file (S).
    $code_pdf = $db->select('soul_science_and_concept_map_submitted_abstracts_file', 'f')
      ->fields('f')
      ->condition('proposal_id', $proposal_id)
      ->condition('filetype', 'S')
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    $code_filename = (!empty($code_pdf) && !empty($code_pdf->filename) && $code_pdf->filename !== 'NULL')
      ? Html::escape($code_pdf->filename)
      : 'File not uploaded';

    $upload_url = Url::fromUserInput('/science-and-concept-map-project/abstract-code/upload');
    $upload_link = Link::fromTextAndUrl($this->t('Upload abstract'), $upload_url)->toString();

    $download_url = Url::fromUserInput('/science-and-concept-map-project/full-download/project/' . $proposal_id);
    $download_link = Link::fromTextAndUrl($this->t('Download science and concept map project'), $download_url)->toString();

    $out  = '<strong>Proposer Name:</strong><br />'
      . Html::escape($proposal_q->name_title . ' ' . $proposal_q->contributor_name) . '<br /><br />';

    $out .= '<strong>Title of the science and concept map Project:</strong><br />'
      . Html::escape($proposal_q->project_title) . '<br /><br />';

    $out .= '<strong>Title of the Category:</strong><br />'
      . Html::escape($proposal_q->category) . '<br /><br />';

    $out .= '<strong>Uploaded a Report (brief outline) of the project:</strong><br />'
      . $abstract_filename . '<br /><br />';

    $out .= '<strong>Upload the soul science and concept map project code files for the proposal:</strong><br />'
      . $code_filename . '<br /><br />';

    if ($code_filename === 'File not uploaded') {
      $out .= $upload_link . '<br /><br />';
    }

    $out .= $download_link;

    return Markup::create($out);
  }

}
