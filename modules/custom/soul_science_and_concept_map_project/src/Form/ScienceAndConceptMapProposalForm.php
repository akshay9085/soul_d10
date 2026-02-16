<?php

/**
 * @file
 * Contains \Drupal\science_and_concept_map\Form\ScienceAndConceptMapProposalForm.
 */

namespace Drupal\science_and_concept_map\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Database\Database;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Mail\MailManager;
use Drupal\user\Entity\User;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\HtmlCommand;


class ScienceAndConceptMapProposalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'science_and_concept_map_proposal_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $no_js_use = NULL) {
   
 

$user = \Drupal::currentUser();

/************************ start approve book details ************************/
if ($user->isAnonymous()) {

  \Drupal::messenger()->addError(t(
    'It is mandatory to @login_link on this website to access the flowsheet proposal form. If you are a new user, please create a new account first.',
    [
      '@login_link' => Link::fromTextAndUrl(
        t('login'),
        Url::fromRoute('user.login')
      )->toString(),
    ]
  ));

  $response = new RedirectResponse(
    Url::fromRoute('user.login', [], [
      'query' => \Drupal::destination()->getAsArray(),
    ])->toString()
  );

  return $response;
}

$query = \Drupal::database()->select('soul_science_and_concept_map_proposal', 's');
$query->fields('s');
$query->condition('uid', $user->id());  
$query->orderBy('id', 'DESC');
$query->range(0, 1);

$proposal_data = $query->execute()->fetchObject();

if ($proposal_data) {
  if ($proposal_data->approval_status == 0 || $proposal_data->approval_status == 1) {

    \Drupal::messenger()->addStatus(t('We have already received your proposal.'));

    $response = new RedirectResponse(
      Url::fromRoute('<front>')->toString()
    );

    return $response;
  }
}

    /************************ start approve book details ************************/
//     if ($user->id() == 0) {
//       $msg = \Drupal::messenger()->addError(t('It is mandatory to @login_link on this website to access the flowsheet proposal form. If you are a new user, please create a new account first.', [
//         '@login_link' => Link::fromTextAndUrl(t('login'), Url::fromRoute('user.page'))->toString(),
//       ]));
//       // $msg = \Drupal::messenger()->addError(t('It is mandatory to ' . \Drupal\Core\Link::fromTextAndUrl('login', \Drupal\Core\Url::fromRoute('user.page')) . ' on this website to access the flowsheet proposal form. If you are new user please create a new account first.'));
//       //drupal_goto('dwsim-flowsheet-project');
//       $response = new RedirectResponse(Url::fromRoute('user.login', [], [
//         'query' => \Drupal::destination()->getAsArray(),
//       ])->toString());
      
//       return $response;
//       // drupal_goto('user/login', [
//       //   'query' => drupal_get_destination()
//       //   ]);
//       return $msg;
//     } //$user->uid == 0
//     $query = \Drupal::database()->select('soul_science_and_concept_map_proposal');
//     $query->fields('soul_science_and_concept_map_proposal');
//     $query->condition('uid', $user->uid);
//     $query->orderBy('id', 'DESC');
//     $query->range(0, 1);
//     $proposal_q = $query->execute();
//     $proposal_data = $proposal_q->fetchObject();
//     if ($proposal_data) {
//       if ($proposal_data->approval_status == 0 || $proposal_data->approval_status == 1) {
//       $msg = \Drupal::messenger()->addStatus(t('We have already received your proposal.'));
//         $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
  
//   // Send the redirect response
// //  $response->send();
//         // drupal_goto('');
      
//         return $response;
//         return $msg;
//       } //$proposal_data->approval_status == 0 || $proposal_data->approval_status == 1
//     } //$proposal_data
    $form['#attributes'] = [
      'enctype' => "multipart/form-data"
      ];
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
    ];
    $form['contributor_name'] = [
      '#type' => 'textfield',
      '#title' => t('Name of the contributor'),
      '#size' => 250,
      '#attributes' => [
        'placeholder' => t('Enter your full name.....')
        ],
      '#maxlength' => 250,
      '#required' => TRUE,
    ];
    $form['contributor_email_id'] = [
      '#type' => 'textfield',
      '#title' => t('Email'),
      '#size' => 30,
      '#value' => $user ? $user->getEmail() : '',
      '#disabled' => TRUE,
    ];
    $form['contributor_contact_no'] = [
      '#type' => 'textfield',
      '#title' => t('Contact No.'),
      '#size' => 10,
      '#attributes' => [
        'placeholder' => t('Enter your contact number')
        ],
      '#maxlength' => 250,
    ];
    $form['university'] = [
      '#type' => 'textfield',
      '#title' => t('School/Institute/University/Organisation'),
      '#size' => 80,
      '#maxlength' => 200,
      '#attributes' => [
        'placeholder' => 'Enter full name of your School/Institute/University/Organisation.... '
        ],
      '#required' => TRUE,
    ];
    $form['department'] = [
      '#type' => 'textfield',
      '#title' => t('Department/Branch'),
      '#size' => 80,
      '#maxlength' => 200,
      '#attributes' => [
        'placeholder' => 'Enter Department/Branch.... '
        ],
    ];
    $form['country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => [
        'India' => 'India',
        'Others' => 'Others',
      ],
      '#required' => TRUE,
      '#tree' => TRUE,
      '#validated' => TRUE,
    ];
    $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => t('Other than India'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your country name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['other_state'] = [
      '#type' => 'textfield',
      '#title' => t('State other than India'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your state/region name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['other_city'] = [
      '#type' => 'textfield',
      '#title' => t('City other than India'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your city name')
        ],
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
      '#attributes' => [
        'placeholder' => t('Enter Pincode')
        ],
      '#size' => 6,
    ];
    /***************************************************************************/
    $form['hr'] = [
      '#type' => 'item',
      '#markup' => '<hr>',
    ];
    $form['project_guide_name'] = [
      '#type' => 'textfield',
      '#title' => t('Name of the faculty member of your Institution, if any, who helped you with this project '),
      '#size' => 250,
      '#attributes' => [
        'placeholder' => t('Enter full name of faculty member')
        ],
      '#maxlength' => 250,
    ];
    $form['project_guide_department'] = [
      '#type' => 'textfield',
      '#title' => t('Department of the faculty member of your Institution, if any, who helped you with this project '),
      '#size' => 250,
      '#attributes' => [
        'placeholder' => t('Enter department name of faculty member')
        ],
      '#maxlength' => 250,
    ];
    $form['project_guide_university'] = [
      '#type' => 'textfield',
      '#title' => t('Enter University name of faculty member'),
      '#size' => 80,
      '#maxlength' => 200,
      '#attributes' => [
        'placeholder' => 'Enter University name of faculty member'
        ],
    ];
    $form['project_guide_email_id'] = [
      '#type' => 'textfield',
      '#title' => t('Email id of the faculty member of your Institution, if any, who helped you with this project'),
      '#size' => 30,
      '#attributes' => [
        'placeholder' => t('Enter Email id of the faculty member')
        ],
    ];
    $form['options'] = [
      '#type' => 'select',
      '#title' => t('How did you come to know about the science and concept map project'),
      '#options' => [
        'Poster' => 'Poster',
        'Website' => 'Website',
        'Email' => 'Email',
        'Workshop' => 'Workshop',
        'others' => 'others',
      ],
      '#required' => TRUE,
      '#tree' => TRUE,
      '#validated' => TRUE,
    ];
    $form['category'] = [
      '#type' => 'select',
      '#title' => t('Select Category'),
      '#options' => _soul_list_of_category(),
      '#required' => TRUE,
      '#tree' => TRUE,
      '#validated' => TRUE,
    ];
    $form['sub_category'] = [
      '#type' => 'select',
      '#title' => t('Select Sub Category for 3D Modeling Project'),
      '#options' => _soul_list_of_sub_category(),
      '#states' => [
        'visible' => [
          ':input[name="category"]' => [
            'value' => '3D Modeling Project'
            ]
          ]
        ],
    ];
    // $software_version_options = _soul_list_of_software_version();
    // $form['software_version'] = [
    //   '#type' => 'select',
    //   '#title' => t('Select Software'),
    //   '#options' => $software_version_options,
    //   '#required' => TRUE,
    //   '#ajax' => [
    //     'callback' => 'ajax_solver_used_callback'
    //     ],
    // ];
    // $software_version_id = !$form_state->getValue(['software_version']) ? $form_state->getValue([
    //   'software_version'
    //   ]) : key($software_version_options);
    // $form['software_version_no'] = [
    //   '#type' => 'select',
    //   '#title' => t('Select the Software Version Number to be used'),
    //   '#options' => _soul_list_of_software_version_details($software_version_id),
    //   '#default_value' => 0,
    //   '#prefix' => '<div id="ajax-solver-replace">',
    //   '#suffix' => '</div>',
    //   '#required' => TRUE,
    //   '#tree' => TRUE,
    //   '#validated' => TRUE,
    // ];
  /**********************************************Soul Software info******************************************************************8 */

  $software_version_options = _soul_list_of_software_version();

  $form['category'] = [
    '#type' => 'select',
    '#title' => $this->t('Select Category'),
    '#options' => _soul_list_of_category(),
    '#required' => TRUE,
  ];

  $form['sub_category'] = [
    '#type' => 'select',
    '#title' => $this->t('Select Sub Category for 3D Modeling Project'),
    '#options' => _soul_list_of_sub_category(),
    '#states' => [
      'visible' => [
        ':input[name="category"]' => ['value' => '3D Modeling Project'],
      ],
    ],
  ];

  $form['software_version'] = [
    '#type' => 'select',
    '#title' => $this->t('Select Software'),
    '#options' => $software_version_options,
    '#required' => TRUE,
    '#ajax' => [
      'callback' => '::ajaxSolverUsedCallback',  // If in class
      'wrapper' => 'ajax-solver-replace',
      'event' => 'change',
    ],
  ];

  $software_version_id = $form_state->getValue('software_version') ?? key($software_version_options);

  $form['software_version_no'] = [
    '#type' => 'select',
    '#title' => $this->t('Select the Software Version Number to be used'),
    '#options' => _soul_list_of_software_version_details($software_version_id),
    '#default_value' => 0,
    '#prefix' => '<div id="ajax-solver-replace">',
    '#suffix' => '</div>',
    '#required' => TRUE,
  ];

  $form['other_software_version_no'] = [
    '#type' => 'textfield',
    '#title' => $this->t('Enter your answer'),
    '#size' => 100,
    '#attributes' => [
      'placeholder' => $this->t('Enter your answer'),
    ],
    '#states' => [
      'visible' => [
        ':input[name="software_version_no"]' => ['value' => 'Another'],
      ],
    ],
  ];

  $form['second_software'] = [
    '#type' => 'select',
    '#title' => $this->t('Select Second Software (If applicable)'),
    '#options' => _soul_list_of_second_software_version(),
  ];

 /**********************************************Soul Software info end******************************************************************8 */

    /************** */
    $form['is_ncert_book'] = [
      '#type' => 'radios',
      '#title' => t('Does the content of this project match any chapter  of a textbook'),
      '#options' => [
        'Yes' => 'Yes',
        'No' => 'No',
      ],
      '#required' => TRUE,
    ];
    /*******************Book info********************* */
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
    ];
    $form['preference1']['author1'] = [
      '#type' => 'textfield',
      '#title' => t('Author Name'),
      '#size' => 30,
      '#maxlength' => 100,
      // '#required' => TRUE
      //'#value' => $row1->author,
      //'#disabled' => ($row1->author?TRUE:FALSE),
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
      // '#required' => TRUE
      // '#value' => $row1->isbn,
      // '#disabled' => ($row1->isbn?TRUE:FALSE),
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
      // '#required' => TRUE
      //'#value' => $row1->publisher,
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
      // '#required' => TRUE
      //'#value' => $row1->edition,
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
      // '#required' => TRUE
      //'#value' => $row1->year,
    ];
     /******************book info end********************* */
    $form['year_of_study'] = [
      '#type' => 'select',
      '#title' => t('The project is suitable for class (school education)/year of study(college education) '),
      // '#options' => '_tbc_list_of_main_categories()',
		'#options' => _df_list_of_classes(),
      //'#default_value' => '-select-',
		'#tree' => TRUE,
      '#required' => TRUE,
    ];
   
    $form['project_title'] = [
      '#type' => 'textfield',
      '#title' => t('Project Title'),
      '#size' => 100,
      '#maxlength' => 250,
      '#description' => t('Minimum character limit is 10 and Maximum character limit is 250'),
      '#attributes' => [
        'placeholder' => t('Enter Project Title')
        ],
      '#required' => TRUE,
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => t('Project Description'),
      '#size' => 250,
      '#attributes' => [
        'placeholder' => t('Enter Project Description')
        ],
      '#description' => t('Minimum character limit is 250 and Maximum character limit is 700'),
      '#required' => TRUE,
    ];
    $form['samplefile'] = [
      '#type' => 'fieldset',
      '#title' => t('Submit an abstract<span style="color:red"> *</span>'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['samplefile']['abstractfilepath'] = [
      '#type' => 'file',
      //'#title' => t('Upload circuit diagram'),
		'#size' => 48,
      '#description' => t('Upload filenames with allowed extensions only. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_abstract_upload_extensions') . '</span>',
    ];
    $form['reference'] = [
      '#type' => 'textfield',
      '#description' => t('The links to the documents or websites which are referenced while proposing this project.'),
      '#title' => t('Reference'),
      '#size' => 250,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'Enter reference'
        ],
    ];
    /*$form['fellowship'] = array(
		'#type' => 'radios',
		'#title' => t('If you are applying for FOSSEE Summer Fellowship 2018 - soul Screening level Task 1, select Yes.(To know more about the FOSSEE Summer Fellowship 2018 <a href="https://fossee.in/fellowship/" target="_blank">Click here</a>)'),
		'#options' => array(
			'1' => 'Yes',
			'2' => 'No'
		),
		'#required' => TRUE
	);*/

    $form['date_of_proposal'] = [
      '#type' => 'date',
      '#title' => t('Date of Proposal'),
      '#default_value' => date("Y-m-d"),
      '#date_format' => 'd M Y',
      '#disabled' => TRUE,
      '#date_label_position' => '',
    ];
    $form['expected_date_of_completion'] = [
      '#type' => 'date',
      '#title' => t('Expected Date of Completion'),
      '#date_label_position' => '',
      '#description' => '',
      '#default_value' => '',
      '#date_format' => 'd-M-Y',
      //'#date_increment' => 0,
      //'#minDate' => '+0',
		'#date_year_range' => '0 : +1',
      '#datepicker_options' => [
        'maxDate' => 31,
        'minDate' => 0,
      ],
      '#required' => TRUE,
    ];

    $form['term_condition'] = [
      '#type' => 'checkboxes',
      '#title' => t('Terms And Conditions'),
      '#options' => [
        'status' => t('<a href="term-and-conditions" target="_blank">I agree to the Terms and Conditions</a>')
        ],
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }
  /**
   * AJAX callback for the software version select element.
   */public function ajaxSolverUsedCallback(array &$form, FormStateInterface $form_state) {
  $software_version_id = $form_state->getValue('software_version') ?? key(_soul_list_of_software_version());

  if ($software_version_id != 7) {
    $form['software_version_no']['#options'] = _soul_list_of_software_version_details($software_version_id);
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#ajax-solver-replace', $form['software_version_no']));
    $response->addCommand(new HtmlCommand('#ajax-solver-text-replace', ''));
  } else {
    $rendered_other = \Drupal::service('renderer')->render($form['other_software_version_no']);
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#ajax-solver-replace', ''));
    $response->addCommand(new HtmlCommand('#ajax-solver-text-replace', $rendered_other));
  }

  return $response;
}

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if (preg_match('/[\^£$%&*()}{@#~?><>.:;`|=_+¬]/', $form_state->getValue([
      'contributor_name'
      ]))) {
      $form_state->setErrorByName('contributor_name', t('Special characters are not allowed for Contributor Name'));
    }
    if (!preg_match('/^[0-9\ \+]{0,15}$/', $form_state->getValue([
      'contributor_contact_no'
      ]))) {
      $form_state->setErrorByName('contributor_contact_no', t('Invalid contact phone number'));
    }
    if ($form_state->getValue(['term_condition']) == '1') {
      $form_state->setErrorByName('term_condition', t('Please check the terms and conditions'));
      // $form_state['values']['country'] = $form_state['values']['other_country'];
    } //$form_state['values']['term_condition'] == '1'

    if ($form_state->getValue(['country']) == 'Others') {
      if ($form_state->getValue(['other_country']) == '') {
        $form_state->setErrorByName('other_country', t('Enter country name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['other_country'] == ''
      else {
        $form_state->setValue(['country'], $form_state->getValue([
          'other_country'
          ]));
      }
      if ($form_state->getValue(['other_state']) == '') {
        $form_state->setErrorByName('other_state', t('Enter state name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['other_state'] == ''
      else {
        $form_state->setValue(['all_state'], $form_state->getValue([
          'other_state'
          ]));
      }
      if ($form_state->getValue(['other_city']) == '') {
        $form_state->setErrorByName('other_city', t('Enter city name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['other_city'] == ''
      else {
        $form_state->setValue(['city'], $form_state->getValue(['other_city']));
      }
    } //$form_state['values']['country'] == 'Others'
    else {
      if ($form_state->getValue(['country']) == '') {
        $form_state->setErrorByName('country', t('Select country name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['country'] == ''
      if ($form_state->getValue([
        'all_state'
        ]) == '') {
        $form_state->setErrorByName('all_state', t('Select state name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['all_state'] == ''
      if ($form_state->getValue([
        'city'
        ]) == '') {
        $form_state->setErrorByName('city', t('Select city name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['city'] == ''
    }
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
    $form_state->setValue(['description'], trim($form_state->getValue([
      'description'
      ])));
    if ($form_state->getValue(['description']) != '') {
      if (strlen($form_state->getValue(['description'])) > 700) {
        $form_state->setErrorByName('description', t('Maximum charater limit is 700 charaters only, please check the length of the description'));
      } //strlen($form_state['values']['project_title']) > 250
      else {
        if (strlen($form_state->getValue(['description'])) < 250) {
          $form_state->setErrorByName('description', t('Minimum charater limit is 250 charaters, please check the length of the description'));
        }
      } //strlen($form_state['values']['project_title']) < 10
    } //$form_state['values']['project_title'] != ''
    else {
      $form_state->setErrorByName('description', t('Description shoud not be empty'));
    }
    if (isset($_FILES['files'])) {
      /* check if atleast one source or result file is uploaded */
      if (!($_FILES['files']['name']['abstractfilepath'])) {
        $form_state->setErrorByName('abstractfilepath', t('Please upload Abstract file.'));
      }
      /* check for valid filename extensions */
      foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
        if ($file_name) {
          /* checking file type */
          /*if (strstr($file_form_name, 'sample'))
					$file_type = 'S';
				else
					$file_type = 'U';
				
				/*switch ($file_type)
				{
					case 'S':
						$allowed_extensions_str = variable_get('textbook_companion_source_extensions', '');
						break;
				} *///$file_type
          $allowed_extensions_str = \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_abstract_upload_extensions');
          $allowed_extensions = explode(',', $allowed_extensions_str);
          $fnames = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
          $temp_extension = end($fnames);
          if (!in_array($temp_extension, $allowed_extensions)) {
            $form_state->setErrorByName($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
          }
          if ($_FILES['files']['size'][$file_form_name] <= 0) {
            $form_state->setErrorByName($file_form_name, t('File size cannot be zero.'));
          }
          /* check if valid file name */
          /*if (!textbook_companion_check_valid_filename($_FILES['files']['name'][$file_form_name]))
					form_set_error($file_form_name, t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));*/
        } //$file_name
      } //$_FILES['files']['name'] as $file_form_name => $file_name
    }
    //     
    if ($form_state->getValue(['is_ncert_book']) == 'Yes') {
      $preference1 = $form_state->getValues();
      if ($preference1['book1'] == '') {

        $form_state->setErrorByName('book1', t('Title of the book is required.'));

      }
      if ($preference1['isbn1'] == '') {

        $form_state->setErrorByName('isbn1', t('ISBN No is required.'));

      }
      if ($preference1['publisher1'] == '') {

        $form_state->setErrorByName('publisher1', t('Publisher & Place is required.'));

      }
      if ($preference1['edition1'] == '') {

        $form_state->setErrorByName('edition1', t('Edition is required.'));

      }
      if ($preference1['book_year'] == '') {

        $form_state->setErrorByName('book_year', t('Year of publication is required.'));

      }
    }
    return $form_state;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $root_path = science_and_concept_map_path();
    if (!$user) {
      \Drupal::messenger()->addError('It is mandatory to login on this website to access the proposal form');
      return;
    } //!$user->uid
	/* inserting the user proposal */
    $v = $form_state->getValues();
    $project_title = trim($v['project_title']);
    $proposar_name = $v['name_title'] . ' ' . $v['contributor_name'];
    $university = $v['university'];
    $department = $v['department'];
    $options = $v['options'];
    $category = $v['category'];
    $software_version_no = $v['software_version_no'];
    $directory_name = _scmp_dir_name($project_title, $proposar_name);
    if ($category == '3D Modeling Project') {
      $sub_category = $v['sub_category'];
    }
    else {
      $sub_category = 'None';
    }

    if ($software_version_no == 'Another') {
      $other_software_version_no = $v['other_software_version_no'];
    }
    else {
      $other_software_version_no = 'None';
    }

    $result = "INSERT INTO {soul_science_and_concept_map_proposal} 
    (
    uid, 
    approver_uid,
    name_title, 
    contributor_name,
    contact_no,
    university,
    department,
    city, 
    state, 
    country,
    pincode,
    project_guide_name,
    project_guide_department,
    project_guide_email_id,
	project_guide_university,
    options,
    category,
    sub_category,
    project_title, 
    description,
    software_version,
    software_version_no,
    other_software_version_no,
    second_software,
    directory_name,
    approval_status,
    is_completed, 
    dissapproval_reason,
    creation_date, 
    approval_date,
    abstractfilepath,
    reference,
    expected_date_of_completion,
	is_ncert_book,
	year_of_study
    ) VALUES
    (
    :uid, 
    :approver_uid, 
    :name_title, 
    :contributor_name, 
    :contact_no,
    :university, 
    :department,
    :city,
    :state,  
    :country, 
    :pincode,
    :project_guide_name,
    :project_guide_department,
    :project_guide_email_id,
	:project_guide_university,
    :options,
    :category,
    :sub_category,
    :project_title, 
    :description,
    :software_version,
    :software_version_no,
    :other_software_version_no,
    :second_software,
    :directory_name,
    :approval_status,
    :is_completed, 
    :dissapproval_reason,
    :creation_date, 
    :approval_date,
    :abstractfilepath,
    :reference,
    :expected_date_of_completion,
	:is_ncert_book,
	:year_of_study
    )";
    $args = [
      ":uid" => $user->get('uid')->value,
      ":approver_uid" => 0,
      ":name_title" => $v['name_title'],
      ":contributor_name" => trim($v['contributor_name']),
      ":contact_no" => $v['contributor_contact_no'],
      ":university" => trim($v['university']),
      ":department" => $v['department'],
      ":city" => $v['city'],
      ":pincode" => $v['pincode'],
      ":state" => $v['all_state'],
      ":country" => $v['country'],
      ":project_guide_name" => trim($v['project_guide_name']),
      ":project_guide_department" => trim($v['project_guide_department']),
      ":project_guide_email_id" => trim($v['project_guide_email_id']),
      ":project_guide_university" => trim(($v['project_guide_university'])),
      ":options" => $v['options'],
      ":category" => $category,
      ":sub_category" => $sub_category,
      ":project_title" => $v['project_title'],
      ":description" => $v['description'],
      ":software_version" => $v['software_version'],
      ":software_version_no" => $software_version_no,
      ":other_software_version_no" => $other_software_version_no,
      ":second_software" => $v['second_software'],
      ":directory_name" => $directory_name,
      ":approval_status" => 0,
      ":is_completed" => 0,
      ":dissapproval_reason" => "NULL",
      ":creation_date" => time(),
      ":approval_date" => 0,
      ":expected_date_of_completion" => strtotime(date($v['expected_date_of_completion'])),
      ":abstractfilepath" => "",
      ":reference" => $v['reference'],
      ":is_ncert_book" => $v['is_ncert_book'],
      ":year_of_study" => trim($form_state->getValue(['year_of_study'])),
    ];
    //var_dump($args);die;
    //var_dump($result);die;
    // $result1 = \Drupal::database()->query($result, $args);
$proposal_id = \Drupal::database()
  ->insert('soul_science_and_concept_map_proposal')
  ->fields($args)
  ->execute();

    /* inserting first book preference */
    if ($form_state->getValue(['book1'])) {
      $bk1 = trim($form_state->getValue(['book1']));
      $auth1 = trim($form_state->getValue(['author1']));
      $pref_id = NULL;
      // $directory_name = _dir_name($bk1, $auth1, $pref_id);
      $query = "INSERT INTO soul_science_and_concept_map_textbook_details
      (proposal_id, book, author, isbn, publisher, edition, book_year) VALUES (:proposal_id, :book, :author, :isbn, :publisher, :edition, :book_year)
	";
      $args2 = [
        ":proposal_id" => $proposal_id,
        ":book" => trim(ucwords(strtolower($form_state->getValue(['book1'])))),
        ":author" => trim(ucwords(strtolower($form_state->getValue(['author1'])))),
        ":isbn" => trim($form_state->getValue(['isbn1'])),
        ":publisher" => trim(ucwords(strtolower($form_state->getValue(['publisher1'])))),
        ":edition" => trim($form_state->getValue(['edition1'])),
        ":book_year" => trim($form_state->getValue(['book_year'])),
      ];
      $result = \Drupal::database()->query($query, $args2);
      if (!$result) {
        \Drupal::messenger()->addError(t('Error receiving your first book preference.'));
      } //!$result
    } //$form_state['values']['book1']

    $dest_path = $directory_name . '/';
    $dest_path1 = $root_path . $dest_path;
    //var_dump($dest_path1);die;	
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }
    /* uploading files */
    foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
      if ($file_name) {
        /* checking file type */
        //$file_type = 'S';
        if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
          \Drupal::messenger()->addError(t("Error uploading file. File !filename already exists.", [
            '!filename' => $_FILES['files']['name'][$file_form_name]
            ]));
          //unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
        } //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
			/* uploading file */
        if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
          $query = "UPDATE {soul_science_and_concept_map_proposal} SET abstractfilepath = :abstractfilepath WHERE id = :id";
          $args = [
            ":abstractfilepath" => $dest_path . $_FILES['files']['name'][$file_form_name],
            ":id" => $proposal_id,
          ];

          $updateresult = \Drupal::database()->query($query, $args);
       
          \Drupal::messenger()->addStatus($file_name . ' uploaded successfully.');
        } //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
        else {
          \Drupal::messenger()->addError('Error uploading file : ' . $dest_path . '/' . $file_name);
        }
      } //$file_name
    } //$_FILES['files']['name'] as $file_form_name => $file_name
    if (!$proposal_id) {
      \Drupal::messenger()->addError(t('Error receiving your proposal. Please try again.'));
      return;
    } //!$proposal_id
	/* sending email */
    $email_to = $user->getEmail();
    $from = \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_from_email');
    $bcc = \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_emails');
    $cc = \Drupal::config('science_and_concept_map.settings')->get('science_and_concept_map_cc_emails');
    $params['science_and_concept_map_proposal_received']['proposal_id'] = $proposal_id;
    $params['science_and_concept_map_proposal_received']['user_id'] = $user->id();
    $params['science_and_concept_map_proposal_received']['headers'] = [
      'From' => $from,
      'MIME-Version' => '1.0',
      'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8Bit',
      'X-Mailer' => 'Drupal',
      'Cc' => $cc,
      'Bcc' => $bcc,
    ];
    $langcode = $user->getPreferredLangcode();
    if (!\Drupal::service('plugin.manager.mail')->mail('science_and_concept_map', 'science_and_concept_map_proposal_received', $email_to, $langcode, $params, $from, TRUE)) {
      \Drupal::messenger()->addError('Error sending email message.');
    }
      //  var_dump(_scmp_dir_name);die;
    $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
    // Send the redirect response
    $response->send();
    \Drupal::messenger()->addStatus(t('We have received your soul science and concept map proposal. We will get back to you soon.'));
   
  }

}

?>
