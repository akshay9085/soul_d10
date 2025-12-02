<?php
function _list_science_and_concept_map_certificates()
{
	$user = \Drupal::currentUser();
	$query_id = \Drupal::database()->query("SELECT id FROM soul_science_and_concept_map_proposal WHERE approval_status=3");
	$exist_id = $query_id->fetchObject();
	if ($exist_id){
	if ($exist_id->id) {
		
			$search_rows = array();
			global $output;
			$output = '';
			$query3 = \Drupal::database()->query("SELECT id,project_guide_name,project_guide_university,project_title FROM 
soul_science_and_concept_map_proposal WHERE project_guide_name != '' AND project_guide_university != '' AND approval_status=3");
			$i = 1;
				while ($search_data3 = $query3->fetchObject()) {
					$download_link = \Drupal\Core\Link::fromTextAndUrl(
					  'Download Certificate',
					  \Drupal\Core\Url::fromRoute('science_and_concept_map.generate_pdf', [], [
					    'query' => ['proposal_id' => $search_data3->id],
					  ])
					)->toString();

					$search_rows[] = array(
						$i,
						$search_data3->project_title,
						$search_data3->project_guide_name,
						$download_link
					);

					$i++;
				} //$search_data3 = $query3->fetchObject()
			if ($search_rows) {
				$search_header = array(
					'No',
					'Project Title',
					'Project Guide Name',
					'Download Certificates'
				);
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
