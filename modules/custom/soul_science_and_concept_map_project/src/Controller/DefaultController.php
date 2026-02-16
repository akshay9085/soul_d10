<?php /**
 * @file
 * Contains \Drupal\science_and_concept_map\Controller\DefaultController.
 */

namespace Drupal\science_and_concept_map\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\TrustedRedirectResponse;
use ZipArchive;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Default controller for the science_and_concept_map module.
 */
class DefaultController extends ControllerBase {

public function science_and_concept_map_proposal_pending() {
  $pending_rows = [];

  $pending_q = \Drupal::database()->select('soul_science_and_concept_map_proposal', 'p')
    ->fields('p')
    ->condition('approval_status', 0)
    ->orderBy('id', 'DESC')
    ->execute();

  while ($pending_data = $pending_q->fetchObject()) {

    $approval_url = Link::fromTextAndUrl(
      $this->t('Approve'),
      Url::fromRoute('science_and_concept_map.proposal_approval_form', ['id' => $pending_data->id])
    )->toString();

    $edit_url = Link::fromTextAndUrl(
      $this->t('Edit'),
      Url::fromRoute('science_and_concept_map.proposal_edit_form', ['id' => $pending_data->id])
    )->toString();

    $actions = Markup::create($approval_url . ' | ' . $edit_url);

    $pending_rows[] = [
      date('d-m-Y', (int) $pending_data->creation_date),
      Link::fromTextAndUrl(
        $pending_data->name_title . ' ' . $pending_data->contributor_name,
        Url::fromRoute('entity.user.canonical', ['user' => $pending_data->uid])
      )->toString(),
      $pending_data->project_title,
      $actions,
    ];
  }
if (empty($pending_rows)) {
  \Drupal::messenger()->addStatus($this->t('There are no pending proposals.'));
  return ['#markup' => '']; // return nothing else
}

  $pending_header = [
    $this->t('Date of Submission'),
    $this->t('Student Name'),
    $this->t('Title of the Science and Concept Map Project'),
    $this->t('Action'),
  ];

  return [
    '#type' => 'table',
    '#header' => $pending_header,
    '#rows' => $pending_rows,
    '#empty' => $this->t('There are no pending proposals.'),
  ];
}


  public function science_and_concept_map_proposal_all() {
    /* get pending proposals to be approved */
    $proposal_rows = [];
    $query = \Drupal::database()->select('soul_science_and_concept_map_proposal');
    $query->fields('soul_science_and_concept_map_proposal');
    $query->orderBy('id', 'DESC');
    $proposal_q = $query->execute();
    while ($proposal_data = $proposal_q->fetchObject()) {
      $approval_status = '';
      switch ($proposal_data->approval_status) {
        case 0:
          $approval_status = 'Pending';
          break;
        case 1:
          $approval_status = 'Approved';
          break;
        case 2:
          $approval_status = 'Dis-approved';
          break;
        case 3:
          $approval_status = 'Completed';
          break;
        default:
          $approval_status = 'Unknown';
          break;
      } //$proposal_data->approval_status
      if ($proposal_data->actual_completion_date == 0) {
        $actual_completion_date = "Not Completed";
      } //$proposal_data->actual_completion_date == 0
      else {
        $actual_completion_date = date('d-m-Y', $proposal_data->actual_completion_date);
      }
    
      // l() expects a Url object, created from a route name or external URI.
      // $proposal_rows[] = array(
      // 			date('d-m-Y', $proposal_data->creation_date),
      // 			l($proposal_data->contributor_name, 'user/' . $proposal_data->uid),
      // 			$proposal_data->project_title,
      // 			$actual_completion_date,
      // 			$approval_status,
      // 			l('Status', 'science-and-concept-map-project/manage-proposal/status/' . $proposal_data->id) . ' | ' . l('Edit', 'science-and-concept-map-project/manage-proposal/edit/' . $proposal_data->id)
      // 		);

    
      $status_url = Link::fromTextAndUrl('Status', Url::fromRoute('science_and_concept_map.proposal_status_form', ['id' => $proposal_data->id]))->toString();
      $edit_url = Link::fromTextAndUrl('Edit', Url::fromRoute('science_and_concept_map.proposal_edit_form', ['id' => $proposal_data->id]))->toString();
      $mainLink = Markup::create($status_url . ' | ' . $edit_url);
    
      $proposal_rows[] = array(
          date('d-m-Y', $proposal_data->creation_date),
          // $uid_url = Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid]),
          //  $link = Link::fromTextAndUrl($proposal_data->name, $uid_url)->toString(),
          Link::fromTextAndUrl($proposal_data->contributor_name, Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid]))->toString(),
      

          // Link::fromTextAndUrl($pending_data->name, 'user/' . $pending_data->uid),
          $proposal_data->project_title,
          $actual_completion_date,
          // $proposal_data->department,
          $approval_status,
          $mainLink 
        
          );
        }
    
      //$proposal_data = $proposal_q->fetchObject()
	/* check if there are any pending proposals */
    if (!$proposal_rows) {
      \Drupal::messenger()->addStatus(t('There are no proposals.'));
      return '';
    } //!$proposal_rows
    $proposal_header = [
      'Date of Submission',
      'Student Name',
      'Title of the science-and-concept-map project',
      'Date of Completion',
      'Status',
      'Action',
    ];
    
    // theme() has been renamed to _theme() and should NEVER be called directly.
    // Calling _theme() directly can alter the expected output and potentially
    // introduce security issues (see https://www.drupal.org/node/2195739). You
    // should use renderable arrays instead.
    // 
    // 
    // @see https://www.drupal.org/node/2195739
    // $output = theme('table', array(
    // 		'header' => $proposal_header,
    // 		'rows' => $proposal_rows
    // 	));
    $output = [
      '#type' => 'table',
      '#header' => $proposal_header,
      '#rows' => $proposal_rows,
  ];
    return $output;
  }

  public function science_and_concept_map_abstract_approval() {
    /* get a list of unapproved solutions */
    //$pending_solution_q = db_query("SELECT * FROM {soul_science_and_concept_map_solution} WHERE approval_status = 0");
    $query = \Drupal::database()->select('soul_science_and_concept_map_solution');
    $query->fields('soul_science_and_concept_map_solution');
    $query->condition('approval_status', 0);
    $pending_solution_q = $query->execute();
    if (!$pending_solution_q) {
      \Drupal::messenger()->addStatus(t('There are no pending code approvals.'));
      return '';
    }
    $pending_solution_rows = [];
    while ($pending_solution_data = $pending_solution_q->fetchObject()) {
      /* get experiment data */
      //$experiment_q = db_query("SELECT * FROM {soul_science_and_concept_map_experiment} WHERE id = %d", $pending_solution_data->experiment_id);
      $query = \Drupal::database()->select('soul_science_and_concept_map_experiment');
      $query->fields('soul_science_and_concept_map_experiment');
      $query->condition('id', $pending_solution_data->experiment_id);
      $experiment_q = $query->execute();
      $experiment_data = $experiment_q->fetchObject();
      /* get proposal data */
      // $proposal_q = db_query("SELECT * FROM {soul_science_and_concept_map_proposal} WHERE id = %d", $experiment_data->proposal_id);
      $query = \Drupal::database()->select('soul_science_and_concept_map_proposal');
      $query->fields('soul_science_and_concept_map_proposal');
      $query->condition('id', $experiment_data->proposal_id);
      $proposal_q = $query->execute();
      $proposal_data = $proposal_q->fetchObject();
      /* get solution provider details */
      $solution_provider_user_name = '';
      $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->solution_provider_uid);
      if ($user_data) {
        $solution_provider_user_name = $user_data->name;
      }
      else {
        $solution_provider_user_name = '';
      }
      /* setting table row information */
      // Actions: link to approval form (pass solution_id via query).
      $approve_url = Url::fromRoute('science_and_concept_map.abstract_approval_form', [], [
        'query' => ['solution_id' => $pending_solution_data->id],
      ]);
      $approve_link = Link::fromTextAndUrl('Edit', $approve_url)->toString();

      $pending_solution_rows[] = [
        $proposal_data->lab_title,
        $experiment_data->title,
        $proposal_data->name,
        $solution_provider_user_name,
        $approve_link,
      ];

    }
    /* check if there are any pending solutions */
    if (!$pending_solution_rows) {
      \Drupal::messenger()->addStatus(t('There are no pending solutions'));
      return '';
    }
    $header = [
      'Title of the Project',
      'Experiment',
      'Proposer',
      'Solution Provider',
      'Actions',
    ];
   
    $output = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $pending_solution_rows,
    ];
    return $output;
  }

  public function science_and_concept_map_abstract() {
    $user = \Drupal::currentUser();
    $return_html = "";
    $proposal_data = science_and_concept_map_get_proposal();
    if (!$proposal_data) {
      return $this->redirect('<front>');
    } //!$proposal_data
    //$return_html .= l('Upload abstract', 'science-and-concept-map-project/abstract-code/upload') . '<br />';
	/* get experiment list */
    $query = \Drupal::database()->select('soul_science_and_concept_map_submitted_abstracts');
    $query->fields('soul_science_and_concept_map_submitted_abstracts');
    $query->condition('proposal_id', $proposal_data->id);
    $abstracts_q = $query->execute()->fetchObject();
    /*if ($abstracts_q)
	{
		if ($abstracts_q->is_submitted == 1)
		{
			drupal_set_message(t('You have already submited your project files, hence you can not upload more code, for any query please write to us.'), 'error', $repeat = FALSE);
			//drupal_goto('circuit-simulation-project/abstract-code');
			//return;
		} //$abstracts_q->is_submitted == 1
	}*/ //$abstracts_q
 
    $query_pro = \Drupal::database()->select('soul_science_and_concept_map_proposal');
    $query_pro->fields('soul_science_and_concept_map_proposal');
    $query_pro->condition('id', $proposal_data->id);
    $abstracts_pro = $query_pro->execute()->fetchObject();
    $query_pdf = \Drupal::database()->select('soul_science_and_concept_map_submitted_abstracts_file');
    $query_pdf->fields('soul_science_and_concept_map_submitted_abstracts_file');
    $query_pdf->condition('proposal_id', $proposal_data->id);
    $query_pdf->condition('filetype', 'A');
    $abstracts_pdf = $query_pdf->execute()->fetchObject();
    if ($abstracts_pdf && $abstracts_pdf->filename != "NULL" && $abstracts_pdf->filename != "") {
      $abstract_filename = $abstracts_pdf->filename;
    }
    else {
      $abstract_filename = "File not uploaded";
    }
    $query_process = \Drupal::database()->select('soul_science_and_concept_map_submitted_abstracts_file');
    $query_process->fields('soul_science_and_concept_map_submitted_abstracts_file');
    $query_process->condition('proposal_id', $proposal_data->id);
    $query_process->condition('filetype', 'S');
    $abstracts_query_process = $query_process->execute()->fetchObject();
    if ($abstracts_query_process && $abstracts_query_process->filename != "NULL" && $abstracts_query_process->filename != "") {
      $abstracts_query_process_filename = $abstracts_query_process->filename;
    }
    else {
      $abstracts_query_process_filename = "File not uploaded";
    }

    $is_submitted = ($abstracts_q && isset($abstracts_q->is_submitted)) ? (string) $abstracts_q->is_submitted : '';
    if ($is_submitted === '') {
      $url = Link::fromTextAndUrl('Upload Abstract', Url::fromRoute('science_and_concept_map.upload_abstract_code_form'))->toString();
    }
    elseif ($is_submitted == '1') {
      $url = '';
    }
    else { // '0'
      $url = Link::fromTextAndUrl('Edit', Url::fromRoute('science_and_concept_map.upload_abstract_code_form'))->toString();
    }
    $return_html .= '<strong>Contributor Name:</strong><br />' . $proposal_data->name_title . ' ' . $proposal_data->contributor_name . '<br /><br />';
    $return_html .= '<strong>Title of the science and concept map Project:</strong><br />' . $proposal_data->project_title . '<br /><br />';
    $return_html .= '<strong>Title of the Category:</strong><br />' . $proposal_data->category . '<br /><br />';
    $return_html .= '<strong>Uploaded Report file of the project:</strong><br />' . $abstract_filename . '<br /><br />';
    $return_html .= '<strong>Uploaded project files of the project:</strong><br />' . $abstracts_query_process_filename . '<br /><br />';
    $return_html .= $url . '<br />';
    $return_html .= '<br />';
    $return_html .= 'Prepare a Project directory in a .zip folder. The directory should contain:' . '<br /><br />' . '<ol>' . '<li>' . 'Data file/s: Please provide completed project files pertaining to the software' . '</li>' . '<li>' . 'Creating Concept Maps using Freeplane must provide files in .mm format and supporting data files if any. Please mention the software version used to generate the data files for this project' . '</li>' . '<li>' . '3D modelling projects using Jmol Application or Avogadro must provide files   in .mol file format. Projects using UCSF-Chimera must provide files in .pdb format. Please mention the software version used to generate the data files for this project' . '</li>' . '<li>' . 'Mathematical Applications Project using Geogebra must provide files  in .ggb file format. Please mention the software version used to generate the data files for this project' . '</li>' . '<li>' . 'Chemistry Lab Project using ChemCollective vlabs must provide Image files as screenshots in JPEG, PNG or PDF format for every step. You may consider recording a video with audio in MP4 format and also submit a media file. Please mention the software version used to generate the data files for this project.' . '</li>' . '<ol>' ;

    return [
      '#type' => 'markup',
      '#markup' => $return_html,
    ];
  }

  public function science_and_concept_map_download_completed_project($id) {
    $user = \Drupal::currentUser();
    $root_path = science_and_concept_map_path();
    $query = \Drupal::database()->select('soul_science_and_concept_map_proposal');
    $query->fields('soul_science_and_concept_map_proposal');
    $query->condition('id', $id);
    $science_and_concept_map_q = $query->execute();
    $science_and_concept_map_data = $science_and_concept_map_q->fetchObject();
    $SCIENCEANDCONCEPTMAP = $science_and_concept_map_data->directory_name . '/';
    /* zip filename */
    $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    /* creating zip archive on the server */
    $zip = new ZipArchive();
    $zip->open($zip_filename, ZipArchive::CREATE);
    $query = \Drupal::database()->select('soul_science_and_concept_map_proposal');
    $query->fields('soul_science_and_concept_map_proposal');
    $query->condition('id', $id);
    $science_and_concept_map_udc_q = $query->execute();
    $query = \Drupal::database()->select('soul_science_and_concept_map_proposal');
    $query->fields('soul_science_and_concept_map_proposal');
    $query->condition('id', $id);
    $query = \Drupal::database()->select('soul_science_and_concept_map_submitted_abstracts_file');
    $query->fields('soul_science_and_concept_map_submitted_abstracts_file');
    $query->condition('proposal_id', $id);
    $project_files = $query->execute();
    //var_dump($root_path . $SCIENCEANDCONCEPTMAP . 'project_files/');die;
    while ($soul_project_files = $project_files->fetchObject()) {
      $zip->addFile($root_path . $SCIENCEANDCONCEPTMAP . 'project_files/' . $soul_project_files->filepath, $SCIENCEANDCONCEPTMAP . str_replace(' ', '_', basename($soul_project_files->filename)));
    }
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0) {
      $response = new BinaryFileResponse($zip_filename);
      $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT;
      $safe_name = str_replace(' ', '_', $science_and_concept_map_data->project_title) . '.zip';
      $response->setContentDisposition($disposition, $safe_name);
      $response->headers->set('Content-Type', 'application/zip');
      $response->deleteFileAfterSend(true);
      return $response;
    }
    \Drupal::messenger()->addError("There are science and concept map project in this proposal to download");
    return new RedirectResponse(Url::fromRoute('science_and_concept_map.proposal_all')->toString());
  }

  public function science_and_concept_map_download_full_project($id) {
    // Drupal 10: accept route param and return a BinaryFileResponse.
    $user = \Drupal::currentUser();
    $root_path = science_and_concept_map_path();
    //var_dump($root_path);die;
    $query = \Drupal::database()->select('soul_science_and_concept_map_proposal');
    $query->fields('soul_science_and_concept_map_proposal');
    $query->condition('id', $id);
    $science_and_concept_map_q = $query->execute();
    $science_and_concept_map_data = $science_and_concept_map_q->fetchObject();
    $SCIENCEANDCONCEPTMAP = $science_and_concept_map_data->directory_name . '/';
    /* zip filename */
    $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    /* creating zip archive on the server */
    $zip = new ZipArchive();
    $zip->open($zip_filename, ZipArchive::CREATE);
    $query = \Drupal::database()->select('soul_science_and_concept_map_proposal');
    $query->fields('soul_science_and_concept_map_proposal');
    $query->condition('id', $id);
    $science_and_concept_map_udc_q = $query->execute();
    $query = \Drupal::database()->select('soul_science_and_concept_map_proposal');
    $query->fields('soul_science_and_concept_map_proposal');
    $query->condition('id', $id);
    $query = \Drupal::database()->select('soul_science_and_concept_map_submitted_abstracts_file');
    $query->fields('soul_science_and_concept_map_submitted_abstracts_file');
    $query->condition('proposal_id', $id);
    $project_files = $query->execute();
    while ($soul_project_files = $project_files->fetchObject()) {
      $zip->addFile($root_path . $SCIENCEANDCONCEPTMAP . 'project_files/' . $soul_project_files->filepath, $SCIENCEANDCONCEPTMAP . str_replace(' ', '_', basename($soul_project_files->filename)));
    }
    $zip_file_count = $zip->numFiles;
    $zip->close();
    if ($zip_file_count > 0) {
      $response = new BinaryFileResponse($zip_filename);
      $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT;
      $safe_name = str_replace(' ', '_', $science_and_concept_map_data->project_title) . '.zip';
      $response->setContentDisposition($disposition, $safe_name);
      $response->headers->set('Content-Type', 'application/zip');
      $response->deleteFileAfterSend(true);
      return $response;
    }
    // No files found; redirect to proposals listing with message.
    \Drupal::messenger()->addError("There are no science and concept map project in this proposal to download");
    return new RedirectResponse(Url::fromRoute('science_and_concept_map.proposal_all')->toString());
  }

  public function science_and_concept_map_completed_proposals_all() {
  $output = "";

  $query = \Drupal::database()->select('soul_science_and_concept_map_proposal', 'p');
  $query->fields('p');
  $query->condition('approval_status', 3);
  $query->orderBy('actual_completion_date', 'DESC');
  $result = $query->execute()->fetchAll();

  if (empty($result)) {
    $output .= "We welcome your contributions.<hr>";
  }
  else {
    $output .= "Work has been completed for the following Science and Concept Map.<hr>";

    $preference_rows = [];
    $i = count($result);

    foreach ($result as $row) {
      $query1 = \Drupal::database()->select('soul_science_and_concept_map_submitted_abstracts_file', 'f');
      $query1->fields('f');
      $query1->condition('file_approval_status', 1);
      $query1->condition('proposal_id', $row->id);
      $file = $query1->execute()->fetchObject();

      $completion_date = date("Y", $row->actual_completion_date);

      // Link
      $run_link = Link::fromTextAndUrl($row->project_title,
        Url::fromRoute('science_and_concept_map.run_form', [], ['query' => ['id' => $row->id]])
      )->toString();

      $preference_rows[] = [
        $i,
        $run_link,
        $row->contributor_name,
        $row->university,
        $completion_date,
      ];
      $i--;
    }

    $preference_header = [
      'No.',
      'Science and Concept Map Project',
      'Contributor Name',
      'University / Institute',
      'Year of Completion',
    ];

    $output = [
      '#type' => 'table',
      '#header' => $preference_header,
      '#rows' => $preference_rows,
    ];
  }

  return $output;
}


  public function science_and_concept_map_progress_all() {
    $page_content = "";
    $query = \Drupal::database()->select('soul_science_and_concept_map_proposal');
    $query->fields('soul_science_and_concept_map_proposal');
    $query->condition('approval_status', 1);
    $query->condition('is_completed', 0);
    $query->orderBy('approval_date', 'DESC');
    $result = $query->execute();
    $records = $result->fetchAll();
    if (count($records) == 0) {
      $page_content .= "We welcome your contributions.<hr>";
    } //$result->rowCount() == 0
    else {
      $i = count($records);
      $page_content .= "Work is in progress for the following Science and Concept Map<hr>";
      $preference_rows = [];
      // $i = 1;
      foreach ($records as $row) {
        $approval_date = date("Y", $row->approval_date);
        $preference_rows[] = [
          $i,
          $row->project_title,
          $row->contributor_name,
          $row->university,
          $approval_date,
        ];
        $i--;
      } //$row = $result->fetchObject()
      $preference_header = [
        'No',
        'Science and Concept Map Project',
        'Contributor Name',
        'Institute',
        'Year',
      ];
      // Build as render array table.
      $page_content =  [
        '#type' => 'table',
        '#header' => $preference_header,
        '#rows' => $preference_rows,
        
      ];
    }
    return $page_content;
  }

  public function science_and_concept_map_download_upload_file($proposal_id) {
    $root_path = science_and_concept_map_document_path();
    $query = \Drupal::database()->select('soul_science_and_concept_map_proposal');
    $query->fields('soul_science_and_concept_map_proposal');
    $query->condition('id', $proposal_id);
    $query->range(0, 1);
    $result = $query->execute();
    $science_and_concept_map_upload_file = $result->fetchObject();
    $absolute = $root_path . $science_and_concept_map_upload_file->abstractfilepath;
    $samplecodename = substr($science_and_concept_map_upload_file->abstractfilepath, strrpos($science_and_concept_map_upload_file->abstractfilepath, '/') + 1);
    $response = new BinaryFileResponse($absolute);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $samplecodename);
    $response->headers->set('Content-Type', 'application/pdf');
    return $response;
  }

  public function soul_science_and_concept_map_project_files($proposal_id) {
    $root_path = science_and_concept_map_document_path();
    $query = \Drupal::database()->select('soul_science_and_concept_map_submitted_abstracts_file');
    $query->fields('soul_science_and_concept_map_submitted_abstracts_file');
    $query->condition('proposal_id', $proposal_id);
    $result = $query->execute();
    $soul_science_and_concept_map_project_files = $result->fetchObject();
    //var_dump($soul_science_and_concept_map_project_files);die;
    $query1 = \Drupal::database()->select('soul_science_and_concept_map_proposal');
    $query1->fields('soul_science_and_concept_map_proposal');
    $query1->condition('id', $proposal_id);
    $result1 = $query1->execute();
    $science_and_concept_map = $result1->fetchObject();
    $directory_name = $science_and_concept_map->directory_name . '/project_files/';
    $samplecodename = $soul_science_and_concept_map_project_files->filename;
    $absolute = $root_path . $directory_name . $samplecodename;
    $response = new BinaryFileResponse($absolute);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $samplecodename);
    $response->headers->set('Content-Type', 'application/pdf');
    return $response;
  }

  public function list_student_certificates() {
    $current_user = $this->currentUser();
    $query = \Drupal::database()->select('soul_science_and_concept_map_proposal', 'p');
    $query->fields('p', ['id', 'project_title', 'contributor_name']);
    $query->condition('approval_status', 3);
    $query->condition('uid', $current_user->id());
    $query->orderBy('project_title', 'ASC');
    $rows = [];
    foreach ($query->execute()->fetchAll() as $record) {
      $rows[] = [
        $record->project_title,
        $record->contributor_name,
        Link::fromTextAndUrl(
          $this->t('Download Certificate'),
          Url::fromRoute('science_and_concept_map.certificates_generate_pdf', [], [
            'query' => ['proposal_id' => $record->id],
          ])
        )->toString(),
      ];
    }

    if (empty($rows)) {
      $proposal_url = Url::fromRoute('science_and_concept_map.proposal_form')->toString();
      $message = Markup::create($this->t('<strong>You need to propose a science and concept map project <a href=":proposal">Proposal</a></strong> or if you have already proposed then your project is under reviewing process', [
        ':proposal' => $proposal_url,
      ]));
      \Drupal::messenger()->addStatus($message);
      return [
        '#type' => 'markup',
        '#markup' => Markup::create('<span style="color:red;">' . $this->t('No certificate available') . '</span>'),
      ];
    }

    return [
      '#type' => 'table',
      '#header' => [
        $this->t('Project Title'),
        $this->t('Contributor Name'),
        $this->t('Download Certificates'),
      ],
      '#rows' => $rows,
    ];
  }

  public function _list_science_and_concept_map_certificates() {
    $user = \Drupal::currentUser();
    $query_id = \Drupal::database()->query("SELECT id FROM soul_science_and_concept_map_proposal WHERE approval_status=3");
    $exist_id = $query_id->fetchObject();
    if ($exist_id) {
      if ($exist_id->id) {

        $search_rows = [];
        global $output;
        $output = '';
        $query3 = \Drupal::database()->query("SELECT id,project_guide_name,project_guide_university,project_title FROM 
soul_science_and_concept_map_proposal WHERE project_guide_name != '' AND project_guide_university != '' AND approval_status=3");
        $i = 1;
        while ($search_data3 = $query3->fetchObject()) {
          $download_link = Link::fromTextAndUrl(
            'Download Certificate',
            Url::fromRoute('science_and_concept_map.generate_pdf', [], [
              'query' => ['proposal_id' => $search_data3->id],
            ])
          )->toString();
          $search_rows[] = [
            $i,
            $search_data3->project_title,
            $search_data3->project_guide_name,
            $download_link,
          ];
          $i++;
        }
        if ($search_rows) {
          $search_header = [
            'No',
            'Project Title',
            'Project Guide Name',
            'Download Certificates',
          ];
          return [
            '#type' => 'table',
            '#header' => $search_header,
            '#rows' => $search_rows,
          ];
        } //$search_rows
        else {
          echo ("Error");
          return '';
        }

      }
    } //$exist_id->id
    else {
      \Drupal::messenger()->addStatus('<strong>You need to propose a science and concept map project <a href="https://soul.fossee.in/science-and-concept-map-project/proposal">Proposal</a></strong> or if you have already proposed then your Project is under reviewing process');
      $page_content = "<span style='color:red;'> No certificate available </span>";
      return $page_content;
    }
  }

  public function verify_certificates($qr_code = '') {
    if ($qr_code) {
      $page_content = verify_qrcode_fromdb($qr_code);
      return [
        '#type' => 'markup',
        '#markup' => $page_content,
      ];
    }
    // Build the form via FormBuilder; returns a render array.
    return \Drupal::formBuilder()->getForm('Drupal\science_and_concept_map\Form\VerifyCertificatesForm');
  }

  /**
   * Delete a pending solution uploaded by a solution provider.
   */
  public function upload_code_delete($solution_id) {
    $current_user = \Drupal::currentUser();
    $solution_id = (int) $solution_id;
    $redirect = new RedirectResponse(Url::fromRoute('science_and_concept_map.abstract')->toString());

    if (!$solution_id) {
      $this->messenger()->addError($this->t('Invalid solution.'));
      return $redirect;
    }

    $connection = \Drupal::database();

    $solution = $connection->select('soul_science_and_concept_map_solution', 's')
      ->fields('s')
      ->condition('id', $solution_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();
    if (!$solution) {
      $this->messenger()->addError($this->t('Invalid solution.'));
      return $redirect;
    }
    if ((int) $solution->approval_status !== 0) {
      $this->messenger()->addError($this->t('You cannot delete a solution after it has been approved.'));
      return $redirect;
    }

    $experiment = $connection->select('soul_science_and_concept_map_experiment', 'e')
      ->fields('e')
      ->condition('id', $solution->experiment_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();
    if (!$experiment) {
      $this->messenger()->addError($this->t('You do not have permission to delete this solution.'));
      return $redirect;
    }

    $proposal = $connection->select('soul_science_and_concept_map_proposal', 'p')
      ->fields('p')
      ->condition('id', $experiment->proposal_id)
      ->condition('solution_provider_uid', $current_user->id())
      ->range(0, 1)
      ->execute()
      ->fetchObject();
    if (!$proposal) {
      $this->messenger()->addError($this->t('You do not have permission to delete this solution.'));
      return $redirect;
    }

    if (soul_science_and_concept_map_delete_solution($solution_id)) {
      $this->messenger()->addStatus($this->t('Solution deleted.'));
      $email_to = $current_user->getEmail();
      if ($email_to) {
        $config = \Drupal::config('science_and_concept_map.settings');
        $from = $config->get('science_and_concept_map_from_email');
        $bcc = $config->get('science_and_concept_map_emails');
        $cc = $config->get('science_and_concept_map_cc_emails');
        $params['solution_deleted_user'] = [
          'lab_title' => $proposal->lab_title ?? '',
          'experiment_title' => $experiment->title ?? '',
          'solution_number' => $solution->code_number ?? '',
          'solution_caption' => $solution->caption ?? '',
          'user_id' => $current_user->id(),
          'headers' => [
            'From' => $from,
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
            'Content-Transfer-Encoding' => '8Bit',
            'X-Mailer' => 'Drupal',
            'Cc' => $cc,
            'Bcc' => $bcc,
          ],
        ];
        $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
        \Drupal::service('plugin.manager.mail')->mail('science_and_concept_map', 'solution_deleted_user', $email_to, $langcode, $params, $from, TRUE);
      }
    }
    else {
      $this->messenger()->addError($this->t('Error deleting solution.'));
    }

    return $redirect;
  }

}
