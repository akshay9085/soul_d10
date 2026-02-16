<?php

/**
 * @file
 * Contains \Drupal\science_and_concept_map\Form\ScienceAndConceptMapProposalStatusForm.
 */

namespace Drupal\science_and_concept_map\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Link;

class ScienceAndConceptMapProposalStatusForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'science_and_concept_map_proposal_status_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $proposal_id = (int) (
      \Drupal::routeMatch()->getParameter('id')
      ?? \Drupal::request()->attributes->get('id')
      ?? 0
    );
    $query = \Drupal::database()->select('soul_science_and_concept_map_textbook_details');
    $query ->fields('soul_science_and_concept_map_textbook_details');
    $query->condition('proposal_id', $proposal_id);
    $book_q = $query->execute();
    $book_data = $book_q->fetchObject();
    $query = \Drupal::database()->select('soul_science_and_concept_map_proposal');
    $query->fields('soul_science_and_concept_map_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      } //$proposal_data = $proposal_q->fetchObject()
      else {
        \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
        $form_state->setRedirectUrl(Url::fromUserInput('/science-and-concept-map-project/manage-proposal'));
        return [];
      }
    } //$proposal_q
    else {
      \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirectUrl(Url::fromUserInput('/science-and-concept-map-project/manage-proposal'));
      return [];
    }
    if ($proposal_data->country == "NULL" || $proposal_data->country == "") {
      $country = "Not Entered";
    } //$proposal_data->country == NULL
    else {
      $country = $proposal_data->country;
    }
    if ($proposal_data->state == "NULL" || $proposal_data->state == "") {
      $state = "Not Entered";
    } //$proposal_data->state == NULL
    else {
      $state = $proposal_data->state;
    }
    if ($proposal_data->city == "NULL" || $proposal_data->city == "") {
      $city = "Not Entered";
    } //$proposal_data->city == NULL
    else {
      $city = $proposal_data->city;
    }
    if ($proposal_data->project_guide_name == "NULL" || $proposal_data->project_guide_name == "") {
      $project_guide_name = "Not Entered";
    } //$proposal_data->project_guide_name == NULL
    else {
      $project_guide_name = $proposal_data->project_guide_name;
    }
    if ($proposal_data->project_guide_email_id == "NULL" || $proposal_data->project_guide_email_id == "") {
      $project_guide_email_id = "Not Entered";
    } //$proposal_data->project_guide_email_id == NULL
    else {
      $project_guide_email_id = $proposal_data->project_guide_email_id;
    }
    if ($proposal_data->project_guide_university == "NULL" || $proposal_data->project_guide_university == "") {
      $project_guide_university = "Not Entered";
    } //$proposal_data->project_guide_university == NULL
    else {
      $project_guide_university = $proposal_data->project_guide_university;
    }
    if ($proposal_data->second_software == "NULL" || $proposal_data->second_software == "") {
      $second_software = "Not Entered";
    } //$proposal_data->city == NULL
    else {
      $second_software = $proposal_data->second_software;
    }
    $query = \Drupal::database()->select('soul_science_and_concept_map_software_version');
    $query->fields('soul_science_and_concept_map_software_version');
    $query->condition('id', $proposal_data->software_version);
    $software_version_data = $query->execute()->fetchObject();
    if (!$software_version_data) {
      $software_versions = 'NA';
    }
    else {
      $software_versions = $software_version_data->software_versions;
    }

    // Contributor name with link (replaces old D7 l()).
    $form['contributor_name'] = [
      '#type' => 'item',
      '#title' => t('Student name'),
      '#markup' => Link::fromTextAndUrl(
        $proposal_data->name_title . ' ' . $proposal_data->contributor_name,
        Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid])
      )->toString(),
    ];

    $form['student_email_id'] = [
      '#type' => 'item',
      '#markup' => (function() use ($proposal_data) {
        $entity = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
        return $entity ? $entity->getEmail() : '';
      })(),
      '#title' => t('Email'),
    ];
    /*$form['month_year_of_degree'] = array(
		'#type' => 'date_popup',
		'#title' => t('Month and year of award of degree'),
		'#date_label_position' => '',
		'#description' => '',
		'#default_value' => $proposal_data->month_year_of_degree,
		'#date_format' => 'M-Y',
		'#date_increment' => 0,
		'#date_year_range' => '1960:+0',
		'#datepicker_options' => array(
			'maxDate' => 0
		),
		'#disabled' => TRUE
	);*/
    $form['university'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->university,
      '#title' => t('University/Institute'),
    ];
    $form['department'] = [
      '#type' => 'item',
      '#title' => t('Department/Branch'),
      '#markup' => $proposal_data->department,
    ];
    $form['country'] = [
      '#type' => 'item',
      '#markup' => $country,
      '#title' => t('Country'),
    ];
    $form['state'] = [
      '#type' => 'item',
      '#markup' => $state,
      '#title' => t('State'),
    ];
    $form['category'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->category,
      '#title' => t('Select Category'),
    ];
    $form['city'] = [
      '#type' => 'item',
      '#markup' => $city,
      '#title' => t('City'),
      //ashvdjas
    ];
    $form['pincode'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->pincode,
      '#title' => t('Pincode/Postal code'),
    ];
    $form['project_guide_name'] = [
      '#type' => 'item',
      '#title' => t('Project guide'),
      '#markup' => $project_guide_name,
    ];
    $form['project_guide_department'] = [
      '#type' => 'item',
      '#title' => t('Department of the faculty member of your Institution, if any, who helped you with this project '),
      '#markup' => $proposal_data->project_guide_department,
    ];
    $form['project_guide_email_id'] = [
      '#type' => 'item',
      '#title' => t('Project guide email'),
      '#markup' => $project_guide_email_id,
    ];
    $form['project_guide_university'] = [
      '#type' => 'item',
      '#title' => t('University of the faculty member of your Institution, if any, who helped you with this project '),
      '#markup' => $proposal_data->project_guide_university,
    ];
    $form['category'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->category,
      '#title' => t('Category'),
    ];
    $form['sub_category'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->sub_category,
      '#title' => t('Sub Category'),
    ];
    $form['software_version'] = [
      '#type' => 'item',
      '#markup' => $software_versions,
      '#title' => t('Software Version'),
    ];
    $form['software_version_no'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->software_version_no,
      '#title' => t('Software Version No'),
    ];
    $form['other_software_version_no'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->other_software_version_no,
      '#title' => t('Other Software Version No'),
    ];
    $form['second_software'] = [
      '#type' => 'item',
      '#markup' => $second_software,
      '#title' => t('Second Software Version'),
    ];
    $form['project_guide_name'] = [
      '#type' => 'item',
      '#title' => t('Project guide'),
      '#markup' => $project_guide_name,
    ];
    $form['project_guide_email_id'] = [
      '#type' => 'item',
      '#title' => t('Project guide email'),
      '#markup' => $project_guide_email_id,
    ];
    if ($proposal_data->is_ncert_book == 'Yes') {
      $form['book_name'] = [
        '#type' => 'item',
        '#markup' => $book_data->book,
        '#title' => t('Book name'),
      ];
      $form['author_name'] = [
        '#type' => 'item',
        '#markup' => $book_data->author,
        '#title' => t('Author name'),
      ];
      $form['isbn_no'] = [
        '#type' => 'item',
        '#markup' => $book_data->isbn,
        '#title' => t('ISBN no.'),
      ];
      $form['publisher'] = [
        '#type' => 'item',
        '#markup' => $book_data->publisher,
        '#title' => t('Publisher and place'),
      ];
      $form['edition'] = [
        '#type' => 'item',
        '#markup' => $book_data->edition,
        '#title' => t('Edition of a book'),
      ];
      $form['book_year'] = [
        '#type' => 'item',
        '#markup' => $book_data->book_year,
        '#title' => t('Year of publication'),
      ];
    }
    $form['year_of_study'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->year_of_study,
      '#title' => t('The project is suitable for class (school education)/year of study(college education)'),
    ];
    $form['project_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->project_title,
      '#title' => t('Title of the science and concept map Project'),
    ];
    $form['description'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->description,
      '#title' => t('Description of the science and concept map Project'),
    ];
    /************************** reference link filter *******************/
    $url = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i';
    $reference = preg_replace($url, '<a href="$0" target="_blank" title="$0">$0</a>', $proposal_data->reference);
    /******************************/
    $form['reference'] = [
      '#type' => 'item',
      '#markup' => $reference,
      '#title' => t('References'),
    ];
    $proposal_status = '';
    switch ($proposal_data->approval_status) {
      case 0:
        $proposal_status = t('Pending');
        break;
      case 1:
        $proposal_status = t('Approved');
        break;
      case 2:
        $proposal_status = t('Dis-approved');
        break;
      case 3:
        $proposal_status = t('Completed');
        break;
      default:
        $proposal_status = t('Unkown');
        break;
    }
    $form['proposal_status'] = [
      '#type' => 'item',
      '#markup' => $proposal_status,
      '#title' => t('Proposal Status'),
    ];
    if ($proposal_data->approval_status == 0) {
      $approve_url = Url::fromRoute('science_and_concept_map.proposal_approval_form', ['id' => $proposal_id]);
      $approve_link = Link::fromTextAndUrl($this->t('Click here'), $approve_url)->toString();
      $form['approve'] = [
        '#type' => 'item',
        '#title' => $this->t('Approve'),
        '#markup' => $approve_link,
      ];
    } //$proposal_data->approval_status == 0
    if ($proposal_data->approval_status == 1) {
      $form['completed'] = [
        '#type' => 'checkbox',
        '#title' => t('Completed'),
        '#description' => t('Check if user has provided all the required files and pdfs.'),
      ];
    } //$proposal_data->approval_status == 1
    if ($proposal_data->approval_status == 2) {
      $form['message'] = [
        '#type' => 'item',
        '#markup' => $proposal_data->message,
        '#title' => t('Reason for disapproval'),
      ];
    } //$proposal_data->approval_status == 2
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    // Cancel link (replaces old D7 l()).
    $form['cancel'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl(t('Cancel'), Url::fromRoute('science_and_concept_map.proposal_all'))->toString(),
    ];

    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $proposal_id = (int) (
      \Drupal::routeMatch()->getParameter('id')
      ?? \Drupal::request()->attributes->get('id')
      ?? 0
    );
    //$proposal_q = db_query("SELECT * FROM {soul_science_and_concept_map_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('soul_science_and_concept_map_proposal');
    $query->fields('soul_science_and_concept_map_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      } //$proposal_data = $proposal_q->fetchObject()
      else {
        \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
        $form_state->setRedirectUrl(Url::fromUserInput('/science-and-concept-map-project/manage-proposal'));
        return;
      }
    } //$proposal_q
    else {
      \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirectUrl(Url::fromUserInput('/science-and-concept-map-project/manage-proposal'));
      return;
    }
    /* set the book status to completed */
    if ($form_state->getValue(['completed']) == 1) {
      $up_query = "UPDATE soul_science_and_concept_map_proposal SET approval_status = :approval_status , actual_completion_date = :expected_completion_date WHERE id = :proposal_id";
      $args = [
        ":approval_status" => '3',
        ":proposal_id" => $proposal_id,
        ":expected_completion_date" => time(),
      ];
      $result = \Drupal::database()->query($up_query, $args);
      CreateReadmeFileSoulScienceAndConceptMapProject($proposal_id);
      if (!$result) {
        \Drupal::messenger()->addError('Error in update status');
        return;
      } //!$result
		/* sending email */
      $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
      $email_to = $user_data ? $user_data->getEmail() : '';
      $from = \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_from_email');
      $bcc = $user->getEmail() . ', ' . \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_emails');
      $cc = \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_cc_emails');
      $params['science_and_concept_map_proposal_completed']['proposal_id'] = $proposal_id;
      $params['science_and_concept_map_proposal_completed']['user_id'] = $proposal_data->uid;
      $params['science_and_concept_map_proposal_completed']['headers'] = [
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
      $result = $mail_manager->mail('science_and_concept_map', 'science_and_concept_map_proposal_completed', $email_to, $langcode, $params, $from, TRUE);
      if (empty($result) || (isset($result['result']) && !$result['result'])) {
        \Drupal::messenger()->addError('Error sending email message.');
      }
      \Drupal::messenger()->addStatus('Congratulations! soul science and concept map proposal has been marked as completed. User has been notified of the completion.');
    }
    $form_state->setRedirectUrl(Url::fromUserInput('/science-and-concept-map-project/manage-proposal'));
    return;

  }

}
?>
