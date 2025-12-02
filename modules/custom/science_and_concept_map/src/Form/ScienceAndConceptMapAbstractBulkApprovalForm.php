<?php

/**
 * @file
 * Contains \Drupal\science_and_concept_map\Form\ScienceAndConceptMapAbstractBulkApprovalForm.
 */

namespace Drupal\science_and_concept_map\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\DataCommand;
use Drupal\Core\Database\Database;
use Drupal\Core\Render\RendererInterface;


class ScienceAndConceptMapAbstractBulkApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'science_and_concept_map_abstract_bulk_approval_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $options = $this->_bulk_list_of_science_and_concept_map_project();
      // var_dump($this->_science_and_concept_map_details(103));die;
    $selected = $form_state->getValue(['science_and_concept_map_project']) ?: key($options);
    $form = [];
    $form['science_and_concept_map_project'] = [
      '#type' => 'select',
      '#title' => t('Title of the science and concept map project'),
      '#options' => $options,
      '#default_value' => $selected,
      '#ajax' => [
        // Use class method callback syntax so Drupal can invoke it.
        'callback' => '::ajax_bulk_science_and_concept_map_abstract_details_callback',
        'event' => 'change',
        ],
      '#suffix' => '<div id="ajax_selected_science_and_concept_map"></div><div id="ajax_selected_science_and_concept_map_pdf"></div>',
    ];
    $form['science_and_concept_map_actions'] = [
      '#type' => 'select',
      '#title' => t('Please select action for science and concept map project'),
      '#options' => $this->_bulk_list_science_and_concept_map_actions(),
      '#default_value' => 0,
      '#prefix' => '<div id="ajax_selected_science_and_concept_map_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="science_and_concept_map_project"]' => [
            'value' => 0
            ]
          ]
        ],
    ];
    // var_dump($form_state->getValue('science_and_concept_map_actions'));die;
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => t('Please specify the reason for Resubmission / Dis-Approval'),
      '#prefix' => '<div id= "message_submit">',
      '#states' => [
        'visible' => [
          [
            ':input[name="science_and_concept_map_actions"]' => [
              'value' => 3
              ]
            ],
          'or',
          [
            ':input[name="science_and_concept_map_actions"]' => [
              'value' => 2
              ]
            ],
        ]
        ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#states' => [
        'invisible' => [
          ':input[name="science_and_concept_map_project"]' => [
            'value' => 0
          ]
        ]
      ],
    ];
  
    return $form;
  }

  public function ajax_bulk_science_and_concept_map_abstract_details_callback(array &$form, FormStateInterface $form_state) {
  $response = new AjaxResponse();

  $selected = $form_state->getValue('science_and_concept_map_project');

  if (!empty($selected) && $selected != 0) {
    // Update the HTML of the target container.
    $details_markup = $this->_science_and_concept_map_details($selected);
    $response->addCommand(new HtmlCommand('#ajax_selected_science_and_concept_map', $details_markup));

    // Update the select options and replace that element’s wrapper.
    $form['science_and_concept_map_actions']['#options'] = $this->_bulk_list_science_and_concept_map_actions();
    $rendered = \Drupal::service('renderer')->render($form['science_and_concept_map_actions']);
    $response->addCommand(new ReplaceCommand('#ajax_selected_science_and_concept_map_action', $rendered));
  }
  else {
    // Clear the container and pass along the current value as jQuery data.
    $response->addCommand(new HtmlCommand('#ajax_selected_science_and_concept_map', ''));
    $response->addCommand(new DataCommand('#ajax_selected_science_and_concept_map', 'form_state_value_select', $selected));
  }

  return $response;
}

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $msg = '';
    $root_path = science_and_concept_map_document_path();
    if ($form_state->get(['clicked_button', '#value']) == 'Submit') {
      if ($form_state->getValue(['science_and_concept_map_project']))
        // science_and_concept_map_abstract_del_lab_pdf($form_state['values']['science_and_concept_map_project']);
 {
        if (\Drupal::currentUser()->hasPermission('soul science and concept map bulk manage abstract')) {
          $query = \Drupal::database()->select('soul_science_and_concept_map_proposal');
          $query->fields('soul_science_and_concept_map_proposal');
          $query->condition('id', $form_state->getValue(['science_and_concept_map_project']));
          $user_query = $query->execute();
          $user_info = $user_query->fetchObject();
          $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($user_info->uid);
          if ($form_state->getValue(['science_and_concept_map_actions']) == 1) {
            // approving entire project //
            $query = \Drupal::database()->select('soul_science_and_concept_map_submitted_abstracts');
            $query->fields('soul_science_and_concept_map_submitted_abstracts');
            $query->condition('proposal_id', $form_state->getValue(['science_and_concept_map_project']));
            $abstracts_q = $query->execute();
            $experiment_list = '';
            while ($abstract_data = $abstracts_q->fetchObject()) {
              \Drupal::database()->query("UPDATE {soul_science_and_concept_map_submitted_abstracts} SET abstract_approval_status = 1, is_submitted = 1, approver_uid = :approver_uid WHERE id = :id", [
                ':approver_uid' => $user->id(),
                ':id' => $abstract_data->id,
              ]);
              \Drupal::database()->query("UPDATE {soul_science_and_concept_map_submitted_abstracts_file} SET file_approval_status = 1, approvar_uid = :approver_uid WHERE submitted_abstract_id = :submitted_abstract_id", [
                ':approver_uid' => $user->id(),
                ':submitted_abstract_id' => $abstract_data->id,
              ]);
            } //$abstract_data = $abstracts_q->fetchObject()
            \Drupal::messenger()->addStatus(t('Approved science and concept map project.'));
            // email 
            //
            // // This looks like another module's variable. You'll need to rewrite this call
            // // to ensure that it uses the correct configuration object.
            // $email_subject = t('[!site_name][Science and Concept Map Project] Your uploaded project files for science and concept map project have been approved', array(
            // 						'!site_name' => variable_get('site_name', '')
            // 					));

            //
            // // This looks like another module's variable. You'll need to rewrite this call
            // // to ensure that it uses the correct configuration object.
            // $email_body = array(
            // 						0 => t('
            // 
            // Dear !user_name,
            // 
            // Your uploaded report and project files for the science and concept map project has been approved:
            // 
            // Full Name : ' . $user_info->name_title . ' ' . $user_info->contributor_name . '
            // Title of science and concept map project  : ' . $user_info->project_title . '
            // Title of Category  : ' . $user_info->category . '
            // 
            // Best Wishes,
            // 
            // !site_name Team,
            // FOSSEE,IIT Bombay', array(
            // 							'!site_name' => variable_get('site_name', ''),
            // 							'!user_name' => $user_data->name
            // 						))
            // 					);

            /** sending email when everything done **/
            // Compose approval email
            $site_name = \Drupal::config('system.site')->get('name');
            $email_subject = t('[@site][Science and Concept Map] Project files approved', ['@site' => $site_name]);
            $email_body = [
              0 => t('Dear @name,

Your uploaded report and project files for the Science and Concept Map project have been approved.

Full Name: @fullname
Project Title: @title
Category: @category

Best wishes,
@site Team
FOSSEE, IIT Bombay', [
                '@site' => $site_name,
                '@name' => $user_data ? $user_data->getAccountName() : '',
                '@fullname' => $user_info->name_title . ' ' . $user_info->contributor_name,
                '@title' => $user_info->project_title,
                '@category' => $user_info->category,
              ]),
            ];

            $email_to = $user_data ? $user_data->getEmail() : '';
            $from = \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_from_email');
            $bcc = \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_emails');
            $cc = \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_cc_emails');
            $params['standard']['subject'] = $email_subject;
            $params['standard']['body'] = $email_body;
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
            $result = $mail_manager->mail('science_and_concept_map', 'standard', $email_to, $langcode, $params, $from, TRUE);
            if (empty($result) || (isset($result['result']) && !$result['result'])) {
              $msg = \Drupal::messenger()->addError('Error sending email message.');
            }
          } //$form_state['values']['science_and_concept_map_actions'] == 1
          elseif ($form_state->getValue(['science_and_concept_map_actions']) == 2) {
            if (strlen(trim($form_state->getValue(['message']))) <= 30) {
              $form_state->setErrorByName('message', t(''));
              $msg = \Drupal::messenger()->addError("Please mention the reason for resubmission. Minimum 30 character required");
              return $msg;
            }
            // pending review entire project 
            $query = \Drupal::database()->select('soul_science_and_concept_map_submitted_abstracts');
            $query->fields('soul_science_and_concept_map_submitted_abstracts');
            $query->condition('proposal_id', $form_state->getValue(['science_and_concept_map_project']));
            $abstracts_q = $query->execute();
            $experiment_list = '';
            while ($abstract_data = $abstracts_q->fetchObject()) {
              \Drupal::database()->query("UPDATE {soul_science_and_concept_map_submitted_abstracts} SET abstract_approval_status = 0, is_submitted = 0, approver_uid = :approver_uid WHERE id = :id", [
                ':approver_uid' => $user->id(),
                ':id' => $abstract_data->id,
              ]);
              \Drupal::database()->query("UPDATE {soul_science_and_concept_map_proposal} SET is_submitted = 0, approver_uid = :approver_uid WHERE id = :id", [
                ':approver_uid' => $user->id(),
                ':id' => $abstract_data->proposal_id,
              ]);
              \Drupal::database()->query("UPDATE {soul_science_and_concept_map_submitted_abstracts_file} SET file_approval_status = 0, approvar_uid = :approver_uid WHERE submitted_abstract_id = :submitted_abstract_id", [
                ':approver_uid' => $user->id(),
                ':submitted_abstract_id' => $abstract_data->id,
              ]);
            } //$abstract_data = $abstracts_q->fetchObject()
            \Drupal::messenger()->addStatus(t('Resubmit the project files'));
            // email 
            //
            // // This looks like another module's variable. You'll need to rewrite this call
            // // to ensure that it uses the correct configuration object.
            // $email_subject = t('[!site_name][Science and Concept Map Project] Your uploaded science and concept map project have been marked as pending', array(
            // 						'!site_name' => variable_get('site_name', '')
            // 					));

            //
            // // This looks like another module's variable. You'll need to rewrite this call
            // // to ensure that it uses the correct configuration object.
            // $email_body = array(
            // 						0 => t('
            // 
            // Dear !user_name,
            // 
            // Kindly resubmit the project files for the project : ' . $user_info->project_title . 'after making changes considering the following reviewer’s comments.
            // 
            // Comment: ' . $form_state['values']['message'] . '
            // 
            // Best Wishes,
            // 
            // !site_name Team,
            // FOSSEE, IIT Bombay', array(
            // 							'!site_name' => variable_get('site_name', ''),
            // 							'!user_name' => $user_data->name
            // 						))
            // 					);

            // Compose email content for Pending Review
            $site_name = \Drupal::config('system.site')->get('name');
            $email_subject = t('[@site][Science and Concept Map] Project marked pending review', ['@site' => $site_name]);
            $email_body = [
              0 => t('Dear @name,

Kindly resubmit the project files for the project: @title after making changes considering the reviewer comments below.

Comment: @comment

Best wishes,
@site Team
FOSSEE, IIT Bombay', [
                '@site' => $site_name,
                '@name' => $user_data ? $user_data->getAccountName() : '',
                '@title' => $user_info->project_title,
                '@comment' => $form_state->getValue(['message']),
              ]),
            ];
            $email_to = $user_data ? $user_data->getEmail() : '';
            $from = \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_from_email');
            $bcc = \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_emails');
            $cc = \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_cc_emails');
            $params['standard']['subject'] = $email_subject;
            $params['standard']['body'] = $email_body;
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
            $result = $mail_manager->mail('science_and_concept_map', 'standard', $email_to, $langcode, $params, $from, TRUE);
            if (empty($result) || (isset($result['result']) && !$result['result'])) {
              \Drupal::messenger()->addError('Error sending email message.');
            }
          } //$form_state['values']['science_and_concept_map_actions'] == 2
          elseif ($form_state->getValue(['science_and_concept_map_actions']) == 3) //disapprove and delete entire science and concept map project
 {
            if (strlen(trim($form_state->getValue(['message']))) <= 30) {
              $form_state->setErrorByName('message', t(''));
              $msg = \Drupal::messenger()->addError("Please mention the reason for disapproval. Minimum 30 character required");
              return $msg;
            } //strlen(trim($form_state['values']['message'])) <= 30
            if (!\Drupal::currentUser()->hasPermission('soul science and concept map bulk delete abstract')) {
              $msg = \Drupal::messenger()->addError(t('You do not have permission to Bulk Dis-Approved and Deleted Entire Project.'));
              return $msg;
            } //!user_access('science_and_concept_map bulk delete code')
            if (science_and_concept_map_abstract_delete_project($form_state->getValue(['science_and_concept_map_project']))) //////
 {
              \Drupal::messenger()->addStatus(t('Dis-Approved and Deleted Entire science and concept map project.'));
              //
              // // This looks like another module's variable. You'll need to rewrite this call
              // // to ensure that it uses the correct configuration object.
              // $email_subject = t('[!site_name][Science and Concept Map Project] Your uploaded science and concept map project have been marked as dis-approved', array(
              // 						'!site_name' => variable_get('site_name', '')
              // 					));

              //
              // // This looks like another module's variable. You'll need to rewrite this call
              // // to ensure that it uses the correct configuration object.
              // $email_body = array(
              // 						0 => t('
              // 
              // Dear !user_name,
              // 
              // Your uploaded science and concept map project files for the science and concept map project Title : ' . $user_info->project_title . ' have been marked as dis-approved.
              // 
              // Reason for dis-approval: ' . $form_state['values']['message'] . '
              // 
              // Best Wishes,
              // 
              // !site_name Team,
              // FOSSEE, IIT Bombay', array(
              // 					'!site_name' => variable_get('site_name', ''),
              // 					'!user_name' => $user_data->name
              // 											))
              // 					);

              $email_to = $user_data ? $user_data->getEmail() : '';
              $from = \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_from_email');
              $bcc = \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_emails');
              $cc = \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_cc_emails');
              $params['standard']['subject'] = $email_subject;
              $params['standard']['body'] = $email_body;
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
              $result = $mail_manager->mail('science_and_concept_map', 'standard', $email_to, $langcode, $params, $from, TRUE);
              if (empty($result) || (isset($result['result']) && !$result['result'])) {
                \Drupal::messenger()->addError('Error sending email message.');
              }
            } //science_and_concept_map_abstract_delete_project($form_state['values']['science_and_concept_map_project'])
            else {
              \Drupal::messenger()->addError(t('Error Dis-Approving and Deleting Entire science and concept map project.'));
            }
            // email 

          } //$form_state['values']['science_and_concept_map_actions'] == 3
				/*elseif ($form_state['values']['science_and_concept_map_actions'] == 4)
				{
					if (strlen(trim($form_state['values']['message'])) <= 30)
					{
						form_set_error('message', t(''));
						$msg = drupal_set_message("Please mention the reason for disapproval/deletion. Minimum 30 character required", 'error');
						return $msg;
					} //strlen(trim($form_state['values']['message'])) <= 30
					$query = db_select('soul_science_and_concept_map_abstract_experiment');
					$query->fields('soul_science_and_concept_map_abstract_experiment');
					$query->condition('proposal_id', $form_state['values']['lab']);
					$query->orderBy('number', 'ASC');
					$experiment_q = $query->execute();
					$experiment_list = '';
					while ($experiment_data = $experiment_q->fetchObject())
					{
						$experiment_list .= '<p>' . $experiment_data->number . ') ' . $experiment_data->title . '<br> Description :  ' . $experiment_data->description . '<br>';
						$experiment_list .= ' ';
						$experiment_list .= '</p>';
					} //$experiment_data = $experiment_q->fetchObject()
					if (!user_access('lab migration bulk delete code'))
					{
						$msg = drupal_set_message(t('You do not have permission to Bulk Delete Entire Lab Including Proposal.'), 'error');
						return $msg;
					} //!user_access('lab migration bulk delete code')
					// check if dependency files are present 
					$dep_q = db_query("SELECT * FROM {soul_science_and_concept_map_abstract_dependency_files} WHERE proposal_id = :proposal_id", array(
						":proposal_id" => $form_state['values']['lab']
					));
					if ($dep_data = $dep_q->fetchObject())
					{
						$msg = drupal_set_message(t("Cannot delete lab since it has dependency files that can be used by others. First delete the dependency files before deleting the lab."), 'error');
						return $msg ;
					} //$dep_data = $dep_q->fetchObject()
					if (science_and_concept_map_abstract_delete_lab($form_state['values']['lab']))
					{
						drupal_set_message(t('Dis-Approved and Deleted Entire Lab solutions.'), 'status');
						$query = db_select('soul_science_and_concept_map_abstract_experiment');
						$query->fields('soul_science_and_concept_map_abstract_experiment');
						$query->condition('proposal_id', $form_state['values']['lab']);
						$experiment_q = $query->execute()->fetchObject();
						$dir_path = $root_path . $experiment_q->directory_name;
						if (is_dir($dir_path))
						{
							$res = rmdir($dir_path);
							if (!$res)
							{
								$msg = drupal_set_message(t("Cannot delete Lab directory : " . $dir_path . ". Please contact administrator."), 'error');
								return $msg;
							} //!$res
						} //is_dir($dir_path)
						else
						{
							drupal_set_message(t("Lab directory not present : " . $dir_path . ". Skipping deleting lab directory."), 'status');
						}
						$proposal_q = db_query("SELECT * FROM {soul_science_and_concept_map_abstract_proposal} WHERE id = :id", array(
							":id" => $form_state['values']['lab']
						));
						$proposal_data = $proposal_q->fetchObject();
						$proposal_id = $proposal_data->id;
						db_query("DELETE FROM {soul_science_and_concept_map_abstract_experiment} WHERE proposal_id = :proposal_id", array(
							":proposal_id" => $proposal_id
						));
						db_query("DELETE FROM {soul_science_and_concept_map_abstract_proposal} WHERE id = :id", array(
							":id" => $proposal_id
						));
						drupal_set_message(t('Deleted Lab Proposal.'), 'status');
						//email 
						$email_subject = t('[!site_name] Your uploaded Lab Migration solutions including the Lab proposal have been deleted', array(
							'!site_name' => variable_get('site_name', '')
						));
						$email_body = array(
							0 => t('

Dear !user_name,

We regret to inform you that all the uploaded Experiments of your Lab with following details have been deleted permanently.

Title of Lab :' . $user_info->lab_title . '

List of experiments : ' . $experiment_list . '

Reason for dis-approval: ' . $form_state['values']['message'] . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', array(
								'!site_name' => variable_get('site_name', ''),
								'!user_name' => $user_data->name
							))
						);
						// email 
						//  $email_subject = t('Your uploaded Lab Migration solutions including the Lab proposal have been deleted');
						$email_body = array(
							0 => t('Your all the uploaded solutions including the Lab proposal have been deleted permanently.')
						);
					} //science_and_concept_map_abstract_delete_lab($form_state['values']['lab'])
					else
					{
						$msg = drupal_set_message(t('Error Dis-Approving and Deleting Entire Lab.'), 'error');
					}
				} //$form_state['values']['science_and_concept_map_actions'] == 4
				else
				{
					$msg = drupal_set_message(t('You do not have permission to bulk manage code.'), 'error');
				}*/
        }
      } //user_access('science_and_concept_map project bulk manage code')
      return $msg;
    } //$form_state['clicked_button']['#value'] == 'Submit'
  }


/**********************************************************
 * 1) Build list of approved/submitted proposals as options
 **********************************************************/
  protected function _bulk_list_of_science_and_concept_map_project(): array {
  $project_titles = ['0' => 'Please select...'];

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

/**********************************************************
 * 2) Bulk actions select options
 **********************************************************/
  protected function _bulk_list_science_and_concept_map_actions(): array {
  return [
    0 => 'Please select...',
    1 => 'Approve Entire science and concept map Project',
    2 => 'Resubmit Project files',
    3 => 'Dis-Approve Entire science and concept map Project (This will delete science and concept map Project)',
    // 4 => 'Delete Entire science and concept map Project Including Proposal',
  ];
  }

/**********************************************************
 * 3) Details block for a selected proposal (safe markup)
 **********************************************************/
  protected function _science_and_concept_map_details($proposal_id) {
  $db = \Drupal::database();

  // Proposal
  $proposal_q = $db->select('soul_science_and_concept_map_proposal', 'p')
    ->fields('p')
    ->condition('id', $proposal_id)
    ->range(0, 1)
    ->execute()
    ->fetchObject();

  if (!$proposal_q) {
    return Markup::create('<em>Proposal not found.</em>');
  }

  // Abstract (filetype 'A')
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

  // Code files (filetype 'S')
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

  // Upload link (if you have a route, prefer fromRoute)
  // Example if route is defined: Url::fromRoute('science_and_concept_map.upload_abstract')
  $upload_url = Url::fromUserInput('/science-and-concept-map-project/abstract-code/upload');
  $upload_link = Link::fromTextAndUrl('Upload abstract', $upload_url)->toString();

  // Full download link (prefer fromRoute with parameter if you have it)
  // Example: Url::fromRoute('science_and_concept_map.full_download', ['project' => $proposal_id])
  $download_url = Url::fromUserInput('/science-and-concept-map-project/full-download/project/' . $proposal_id);
  $download_link = Link::fromTextAndUrl('Download science and concept map project', $download_url)->toString();

  // Build safe HTML
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

  // When no code file, also show the upload link
  if ($code_filename === 'File not uploaded') {
    $out .= $upload_link . '<br /><br />';
  }

  $out .= $download_link;

  return Markup::create($out);
  }

}
?>
