<?php

/**
 * @file
 * Contains \Drupal\science_and_concept_map\Form\ScienceAndConceptMapProposalEditForm.
 */

namespace Drupal\science_and_concept_map\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

class ScienceAndConceptMapProposalEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'science_and_concept_map_proposal_edit_form';
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
        return ;
      }
    } //$proposal_q
    else {
      \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirectUrl(Url::fromUserInput('/science-and-concept-map-project/manage-proposal'));
      return ;
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
    if ($proposal_data->second_software == "NULL" || $proposal_data->second_software == "") {
      $second_software = "Not Entered";
    } //$proposal_data->city == NULL
    else {
      $second_software = $proposal_data->second_software;
    }
    $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
    $form['name_title'] = [
      '#type' => 'select',
      '#title' => t('Title'),
      '#options' => [
        'Dr' => 'Dr',
        'Prof' => 'Prof',
        'Mr' => 'Mr',
        'Mrs' => 'Mrs',
        'Ms' => 'Ms',
      ],
      '#required' => TRUE,
      '#default_value' => $proposal_data->name_title,
    ];
    $form['contributor_name'] = [
      '#type' => 'textfield',
      '#title' => t('Name of the Proposer'),
      '#size' => 100,
      '#maxlength' => 500,
      '#required' => TRUE,
      '#default_value' => $proposal_data->contributor_name,
    ];
    $student_email = '';
if (!empty($proposal_data->uid)) {
  $user_entity = \Drupal::entityTypeManager()
    ->getStorage('user')
    ->load($proposal_data->uid);

  if ($user_entity) {
    $student_email = $user_entity->getEmail();
  }
}

$form['student_email_id'] = [
  '#type' => 'item',
  '#title' => $this->t('Email'),
  '#markup' => $student_email,
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
      '#type' => 'textfield',
      '#title' => t('University/Institute'),
      '#size' => 50,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#default_value' => $proposal_data->university,
    ];
    $form['country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => [
        'India' => 'India',
        'Others' => 'Others',
      ],
      '#default_value' => $proposal_data->country,
      '#required' => TRUE,
      '#tree' => TRUE,
      '#validated' => TRUE,
    ];
    /*$form['other_country'] = array(
		'#type' => 'textfield',
		'#title' => t('Other than India'),
		'#size' => 100,
		'#default_value' => $proposal_data->country,
		'#attributes' => array(
			'placeholder' => t('Enter your country name')
		),
		'#states' => array(
			'visible' => array(
				':input[name="country"]' => array(
					'value' => 'Others'
				)
			)
		)
	);*/
    /*$form['other_state'] = array(
		'#type' => 'textfield',
		'#title' => t('State other than India'),
		'#size' => 100,
		'#attributes' => array(
			'placeholder' => t('Enter your state/region name')
		),
		'#default_value' => $proposal_data->state,
		'#states' => array(
			'visible' => array(
				':input[name="country"]' => array(
					'value' => 'Others'
				)
			)
		)
	);*/
    $form['other_city'] = [
      '#type' => 'textfield',
      '#title' => t('City other than India'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your city name')
        ],
      '#default_value' => $proposal_data->city,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['all_state'] = [
      '#type' => 'select',
      '#title' => t('State'),
      '#options' => _df_list_of_states(),
      '#default_value' => $proposal_data->state,
      '#validated' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
    ];
    $form['city'] = [
      '#type' => 'select',
      '#title' => t('City'),
      '#options' => _df_list_of_cities(),
      '#default_value' => $proposal_data->city,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
    ];
    $form['pincode'] = [
      '#type' => 'textfield',
      '#title' => t('Pincode'),
      '#size' => 30,
      '#maxlength' => 6,
      '#default_value' => $proposal_data->pincode,
      '#attributes' => [
        'placeholder' => 'Insert pincode of your city/ village....'
        ],
    ];
    $form['category'] = [
      '#type' => 'select',
      '#title' => t('Category'),
      '#default_value' => $proposal_data->category,
      '#required' => TRUE,
      '#options' => _soul_list_of_category(),
      /*'#markup' => $proposal_data->category,*/

    ];
    $form['project_guide_name'] = [
      '#type' => 'textfield',
      '#title' => t('Name of the faculty member of your Institution, if any, who helped you with this project '),
      '#size' => 250,
      '#default_value' => $proposal_data->project_guide_name,
      '#attributes' => [
        'placeholder' => t('Enter full name of faculty member')
        ],
      '#maxlength' => 250,
    ];
    $form['project_guide_department'] = [
      '#type' => 'textfield',
      '#title' => t('Department of the faculty member of your Institution, if any, who helped you with this project '),
      '#size' => 250,
      '#default_value' => $proposal_data->project_guide_department,
      '#attributes' => [
        'placeholder' => t('Enter department name of faculty member')
        ],
      '#maxlength' => 250,
    ];
    $form['project_guide_email_id'] = [
      '#type' => 'textfield',
      '#title' => t('Email id of the faculty member of your Institution, if any, who helped you with this project'),
      '#size' => 30,
      '#default_value' => $proposal_data->project_guide_email_id,
      '#attributes' => [
        'placeholder' => t('Enter Email id of the faculty member')
        ],
    ];
    $form['project_guide_university'] = [
      '#type' => 'textfield',
      '#title' => t('Enter University name of faculty member'),
      '#default_value' => $proposal_data->project_guide_university,
      '#size' => 80,
      '#maxlength' => 200,
    ];
    // $form['sub_category'] = array(
    // 	'#type' => 'select',
    // 	'#default_value' => $proposal_data->sub_category,
    // 	'#title' => t('Sub Category'),
    // 	'#required' => TRUE,
    // 	'#options' => _soul_list_of_sub_category(),
    // 	'#states' => array(
    // 		'visible' => array(
    // 			':input[name="category"]' => array(
    // 				'value' => '3D Modeling Project'
    // 			)
    // 		)
    // 	)
    // );
    // $software_version_options =  _soul_list_of_software_version();
    // $form['software_version'] = array(
    // 	'#type' => 'select',
    // 	'default_value' => $software_versions,
    // 	'#title' => t('Software Version'),
    // 	'#options' => $software_version_options,
    // 	'#required' => TRUE,
    // 	'#ajax' => array(
    //     	'callback' => 'ajax_solver_used_callback',
    //     ),
    // );
    // $software_version_id = isset($form_state['values']['software_version']) ? $form_state['values']['software_version'] : key($software_version_options);
    // $form['software_version_no'] = array(
    // 	'#type' => 'select',
    // 	'#default_value' => $proposal_data->software_version_no,
    // 	'#title' => t('Software Version No'),
    // 	'#options' => _soul_list_of_software_version_details($software_version_id),

    // 	'#prefix' => '<div id="ajax-solver-replace">',
    // 	'#suffix' => '</div>',
    // 	'#required' => TRUE,
    // 	'#tree' => TRUE,
    // 	'#validated' => TRUE
    // );
    // /*$form['other_software_version_no'] = array(
    // 	'#type' => 'item',
    // 	'#default_value' => $proposal_data->other_software_version_no,
    // 	'#title' => t('Other Software Version No'),
    // 	'#size' => 100,
    // 	'#attributes' => array(
    // 		'placeholder' => t('Enter your answer')
    // 	),
    // 	'#states' => array(
    // 		'visible' => array(
    // 			':input[name="software_version_no"]' => array(
    // 				'value' => 'Another'
    // 			)
    // 		)
    // 	)
    // );*/
    // $form['second_software'] = array(
    // 	'#type' => 'item',
    // 	'#default_value' => $second_software,
    // 	'#title' => t('Second Software Version'),
    // 	'#options' => _soul_list_of_second_software_version()
    // );
    // if ($proposal_data->is_ncert_book == 'Yes') {
    $form['is_ncert_book'] = [
      '#type' => 'radios',
      '#title' => t('Does the content of this project match any chapter  of a textbook'),
      '#options' => [
        'Yes' => 'Yes',
        'No' => 'No',
      ],
      '#required' => TRUE,
      '#default_value' => $proposal_data->is_ncert_book,
    ];
    $form['preference1'] = [
      '#type' => 'fieldset',
      '#title' => t('Details of Textbook'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="is_ncert_book"]' => [
            'value' => 'Yes'
            ]
          ]
        ],
    ];
    $form['preference1']['book1'] = [
      '#type' => 'textfield',
      '#title' => t('Title of the book'),
      '#size' => 30,
      '#maxlength' => 100,
      '#states' => [
        'required' => [
          ':input[name="is_ncert_book"]' => ['value' => 'Yes'],
        ],
      ],
      '#validated' => TRUE,
      '#default_value' => $book_data->book ?? '',
    ];
    $form['preference1']['author1'] = [
      '#type' => 'textfield',
      '#title' => t('Author Name'),
      '#size' => 30,
      '#maxlength' => 100,
      '#default_value' => $book_data->author ?? '',
    ];
    $form['preference1']['isbn1'] = [
      '#type' => 'textfield',
      '#title' => t('ISBN No'),
      '#size' => 30,
      '#maxlength' => 25,
      '#states' => [
        'required' => [
          ':input[name="is_ncert_book"]' => ['value' => 'Yes'],
        ],
      ],
      '#validated' => TRUE,
      '#default_value' => $book_data->isbn ?? '',
    ];
    $form['preference1']['publisher1'] = [
      '#type' => 'textfield',
      '#title' => t('Publisher & Place'),
      '#size' => 30,
      '#maxlength' => 50,
      '#states' => [
        'required' => [
          ':input[name="is_ncert_book"]' => ['value' => 'Yes'],
        ],
      ],
      '#validated' => TRUE,
      '#default_value' => $book_data->publisher ?? '',
    ];
    $form['preference1']['edition1'] = [
      '#type' => 'textfield',
      '#title' => t('Edition'),
      '#size' => 4,
      '#maxlength' => 2,
      '#states' => [
        'required' => [
          ':input[name="is_ncert_book"]' => ['value' => 'Yes'],
        ],
      ],
      '#validated' => TRUE,
      '#default_value' => $book_data->edition ?? '',
    ];
    $form['preference1']['book_year'] = [
      '#type' => 'textfield',
      '#title' => t('Year of publication'),
      '#size' => 4,
      '#maxlength' => 4,
      '#states' => [
        'required' => [
          ':input[name="is_ncert_book"]' => ['value' => 'Yes'],
        ],
      ],
      '#validated' => TRUE,
      '#default_value' => $book_data->book_year ?? '',
    ];

    $form['year_of_study'] = [
      '#type' => 'select',
      '#title' => t('The project is suitable for class (school education)/year of study(college education) '),
      // '#options' => '_tbc_list_of_main_categories()',
		'#options' => _df_list_of_classes(),
      // '#default_value' => '-select-',
		'#tree' => TRUE,
      '#required' => TRUE,
      '#default_value' => $proposal_data->year_of_study,
    ];
    $form['project_title'] = [
      '#type' => 'textfield',
      '#title' => t('Title of the Science and Concept Map Project'),
      '#size' => 75,
      '#required' => TRUE,
      '#default_value' => $proposal_data->project_title,
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#default_value' => $proposal_data->description,
      '#title' => t('Project Description'),
      '#size' => 250,
      '#attributes' => [
        'placeholder' => t('Enter Project Description')
        ],
      '#description' => t('Minimum character limit is 250 and Maximum character limit is 700'),
      '#required' => TRUE,
    ];
    // SAMPLE FILE//
    $form['reference'] = [
      '#type' => 'textfield',
      '#title' => t('Reference'),
      '#size' => 20,
      '#attributes' => [
        'placeholder' => 'Links of must be provided....'
        ],
      '#default_value' => $proposal_data->reference,
    ];
    $form['date_of_proposal'] = [
      '#type' => 'date',
      '#title' => t('Date of Proposal'),
      // Use stored creation timestamp; HTML5 date expects Y-m-d.
      '#default_value' => !empty($proposal_data->creation_date) ? date('Y-m-d', $proposal_data->creation_date) : '',
      '#date_format' => 'd M Y',
      '#disabled' => TRUE,
      '#date_label_position' => '',
    ];
    // $form['expected_date_of_completion'] = array(
    // 	'#type' => 'date_popup',
    // 	'#title' => t('Expected Date of Completion'),
    // 	'#date_label_position' => '',
    // 	'#description' => '',
    // 	'#default_value' => '',
    // 	'#date_format' => 'd-M-Y',

    // 	'#date_year_range' => '0 : +1',
    // 	'#datepicker_options' => array('maxDate' => 31, 'minDate' => 0),
    // 	'#required' => TRUE
    // );
    $form['delete_proposal'] = [
      '#type' => 'checkbox',
      '#title' => t('Delete Proposal'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
   
    $form['cancel'] = [
      '#type' => 'item',
      '#markup' => \Drupal\Core\Link::fromTextAndUrl(t('Cancel'), \Drupal\Core\Url::fromRoute('science_and_concept_map.proposal_all'))->toString(),
    ];

    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    //Validation for project title
    $form_state->setValue(['project_title'], trim($form_state->getValue([
      'project_title'
      ])));
    if ($form_state->getValue(['project_title']) != '') {
      if (strlen($form_state->getValue(['project_title'])) > 250) {
        $form_state->setErrorByName('project_title', t('Maximum charater limit is 250 charaters only, please check the length of the project title'));
      } //strlen($form_state['values']['project_title']) > 250
      else {
        if (strlen($form_state->getValue(['project_title'])) < 10) {
          $form_state->setErrorByName('project_title', t('Minimum charater limit is 10 charaters, please check the length of the project title'));
        }
      } //strlen($form_state['values']['project_title']) < 10
    } //$form_state['values']['project_title'] != ''
    else {
      $form_state->setErrorByName('project_title', t('Project title shoud not be empty'));
    }
    if (preg_match('/[\^£$%&*()}{@#~?><>.:;`|=+¬]/', $form_state->getValue([
      'project_title'
      ]))) {
      $form_state->setErrorByName('project_title', t('Special characters are not allowed for Project Title'));
    }
    return $form_state;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    /* get current proposal */
    $proposal_id = (int) (
      \Drupal::routeMatch()->getParameter('id')
      ?? \Drupal::request()->attributes->get('id')
      ?? 0
    );
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
    /* delete proposal */
    if ($form_state->getValue(['delete_proposal']) == 1) {
      /* sending email */
      $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
      $email_to = $user_data ? $user_data->getEmail() : '';
      $from = \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_from_email');
      $bcc = \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_emails');
      $cc = \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_cc_emails');
      $params['science_and_concept_map_proposal_deleted']['proposal_id'] = $proposal_id;
      $params['science_and_concept_map_proposal_deleted']['user_id'] = $proposal_data->uid;
      $params['science_and_concept_map_proposal_deleted']['headers'] = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];
      $langcode = $user_data ? $user_data->getPreferredLangcode() : \Drupal::languageManager()->getDefaultLanguage()->getId();
      $mail_manager = \Drupal::service('plugin.manager.mail');
      $result = $mail_manager->mail('science_and_concept_map', 'science_and_concept_map_proposal_deleted', $email_to, $langcode, $params, $from, TRUE);
      if (empty($result) || (isset($result['result']) && !$result['result'])) {
        \Drupal::messenger()->addError('Error sending email message.');
      }
      \Drupal::messenger()->addStatus(t('soul science-and-concept-map-project proposal has been deleted.'));
      if (rrmdir_project($proposal_id) == TRUE) {
        $query_book = \Drupal::database()->delete('soul_science_and_concept_map_textbook_details');
        $query_book->condition('proposal_id', $proposal_id);
        $num_book_deleted = $query_book->execute();
        $query = \Drupal::database()->delete('soul_science_and_concept_map_proposal');
        $query->condition('id', $proposal_id);
        $num_deleted = $query->execute();
        \Drupal::messenger()->addStatus(t('Proposal Deleted'));
        $form_state->setRedirectUrl(Url::fromUserInput('/science-and-concept-map-project/manage-proposal'));
        return;
      } //rrmdir_project($proposal_id) == TRUE
    } //$form_state['values']['delete_proposal'] == 1
	/* update proposal */
    $v = $form_state->getValues();
    $project_title = $v['project_title'];
    $proposar_name = $v['name_title'] . ' ' . $v['contributor_name'];
    $university = $v['university'];
    $directory_names = _scmp_dir_name($project_title, $proposar_name);
    if (DF_RenameDir($proposal_id, $directory_names)) {
      $directory_name = $directory_names;
    } //LM_RenameDir($proposal_id, $directory_names)
    else {
      return;
    }
    $str = substr($proposal_data->abstractfilepath, strrpos($proposal_data->abstractfilepath, '/'));
    $resource_file = ltrim($str, '/');
    $abstractfilepath = $directory_name . '/' . $resource_file;
    $query = "UPDATE soul_science_and_concept_map_proposal SET 
				name_title=:name_title,
				contributor_name=:contributor_name,
				university=:university,
				city=:city,
				pincode=:pincode,
				project_guide_name=:project_guide_name,
    			project_guide_department=:project_guide_department,
    			project_guide_email_id=:project_guide_email_id,
				project_guide_university=:project_guide_university,
				state=:state,
				project_title=:project_title,
				description=:description,
				reference=:reference,
				directory_name=:directory_name ,
				abstractfilepath=:abstractfilepath,
				year_of_study=:year_of_study,
				is_ncert_book=:is_ncert_book
				WHERE id=:proposal_id";
    $args = [
      ':name_title' => $v['name_title'],
      ':contributor_name' => $v['contributor_name'],
      ':university' => $v['university'],
      ':city' => $v['city'],
      ':pincode' => $v['pincode'],
      ":project_guide_name" => trim($v['project_guide_name']),
      ":project_guide_department" => trim($v['project_guide_department']),
      ":project_guide_email_id" => trim($v['project_guide_email_id']),
      ":project_guide_university" => trim(($v['project_guide_university'])),
      ':state' => $v['all_state'],
      ':project_title' => $project_title,
      ':description' => $v['description'],
      ':reference' => $v['reference'],
      ':directory_name' => $directory_name,
      ':abstractfilepath' => $abstractfilepath,
      ':proposal_id' => $proposal_id,
      ":is_ncert_book" => $v['is_ncert_book'],
      ":year_of_study" => trim($v['year_of_study']),
    ];
    $result = \Drupal::database()->query($query, $args);
    $query_book = " UPDATE soul_science_and_concept_map_textbook_details SET
				book=:book, 
				author=:author,
				isbn =:isbn, 
				publisher=:publisher, 
				edition=:edition, 
				book_year=:book_year
				WHERE proposal_id=:proposal_id
	";
    $args_book = [
      ":proposal_id" => $proposal_id,
      ":book" => trim(ucwords(strtolower($form_state->getValue(['book1'])))),
      ":author" => trim(ucwords(strtolower($form_state->getValue(['author1'])))),
      ":isbn" => trim($form_state->getValue(['isbn1'])),
      ":publisher" => trim(ucwords(strtolower($form_state->getValue(['publisher1'])))),
      ":edition" => trim($form_state->getValue(['edition1'])),
      ":book_year" => trim($form_state->getValue(['book_year'])),
    ];
    $result_books = \Drupal::database()->query($query_book, $args_book);
    \Drupal::messenger()->addStatus(t('Proposal Updated'));
  }

}
?>
