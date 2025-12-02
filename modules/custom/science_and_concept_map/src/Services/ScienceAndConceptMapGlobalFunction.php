<?php

namespace Drupal\science_and_concept_map\Services;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Database\Database;

class ScienceAndConceptMapGlobalFunction{
/* AJAX CALLS */
/*function science_and_concept_map_ajax()
{
	$query_type = arg(2);
	if ($query_type == 'chapter_title')
	{
		$chapter_number = arg(3);
		$preference_id = arg(4);
		//$chapter_q = db_query("SELECT * FROM {soul_science_and_concept_map_chapter} WHERE number = %d AND preference_id = %d LIMIT 1", $chapter_number, $preference_id);
		$query = db_select('soul_science_and_concept_map_chapter');
		$query->fields('soul_science_and_concept_map_chapter');
		$query->condition('number', $chapter_number);
		$query->condition('preference_id', $preference_id);
		$query->range(0, 1);
		$chapter_q = $query->execute();
		if ($chapter_data = $chapter_q->fetchObject())
		{
			echo $chapter_data->name;
			return;
		} //$chapter_data = $chapter_q->fetchObject()
	} //$query_type == 'chapter_title'
	else if ($query_type == 'example_exists')
	{
		$chapter_number = arg(3);
		$preference_id = arg(4);
		$example_number = arg(5);
		$chapter_id = 0;
		$query = db_select('soul_science_and_concept_map_chapter');
		$query->fields('soul_science_and_concept_map_chapter');
		$query->condition('number', $chapter_number);
		$query->condition('preference_id', $preference_id);
		$query->range(0, 1);
		$chapter_q = $query->execute();
		if (!$chapter_data = $chapter_q->fetchObject())
		{
			echo '';
			return;
		} //!$chapter_data = $chapter_q->fetchObject()
		else
		{
			$chapter_id = $chapter_data->id;
		}
		$query = db_select('soul_science_and_concept_map_example');
		$query->fields('soul_science_and_concept_map_example');
		$query->condition('chapter_id', $chapter_id);
		$query->condition('number', $example_number);
		$query->range(0, 1);
		$example_q = $query->execute();
		if ($example_data = $example_q->fetchObject())
		{
			if ($example_data->approval_status == 1)
				echo 'Warning! Solution already approved. You cannot upload the same solution again.';
			else
				echo 'Warning! Solution already uploaded. Delete the solution and reupload it.';
			return;
		} //$example_data = $example_q->fetchObject()
	} //$query_type == 'example_exists'
	echo '';
}*/
/*************************** VALIDATION FUNCTIONS *****************************/
function science_and_concept_map_check_valid_filename($file_name)
{
	if (!preg_match('/^[0-9a-zA-Z\.\_-]+$/', $file_name))
		return FALSE;
	else if (substr_count($file_name, ".") > 1)
		return FALSE;
	else
		return TRUE;
}
function science_and_concept_map_check_name($name = '')
{
	if (!preg_match('/^[0-9a-zA-Z\ ]+$/', $name))
		return FALSE;
	else
		return TRUE;
}
function science_and_concept_map_check_code_number($number = '')
{
	if (!preg_match('/^[0-9]+$/', $number))
		return FALSE;
	else
		return TRUE;
}
function science_and_concept_map_path()
{
	return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'soul_uploads/science_and_concept_map_uploads/';
}
function science_and_concept_map_file_path($value='')
{
	return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'soul_uploads/';
}
/************************* USER VERIFICATION FUNCTIONS ************************/
function science_and_concept_map_get_proposal()
{
	$user = \Drupal::currentUser();
	$query = \Drupal::database()->select('soul_science_and_concept_map_proposal');
	$query->fields('soul_science_and_concept_map_proposal');
	$query->condition('uid', $user->uid);
	$query->orderBy('id', 'DESC');
	$query->range(0, 1);
	$proposal_q = $query->execute();
	$proposal_data = $proposal_q->fetchObject();
	if (!$proposal_data)
	{
		\Drupal::messenger()->addError("You do not have any approved soul science and concept map proposal. Please propose the science and concept map proposal");
		// drupal_goto('');
		return new TrustedRedirectResponse('/');
		// return '';
	} //!$proposal_data
	switch ($proposal_data->approval_status)
	{
		case 0:
			\Drupal::messenger()->addStatus(t('Proposal is awaiting approval.'));
			return FALSE;
		case 1:
			return $proposal_data;
		case 2:
			\Drupal::messenger()->addError(t('Proposal has been dis-approved.'));
			return FALSE;
		case 3:
			\Drupal::messenger()->addStatus(t('Proposal has been marked as completed.'));
			return FALSE;
		default:
			\Drupal::messenger()->addError(t('Invalid proposal state. Please contact site administrator for further information.'));
			return FALSE;
	} //$proposal_data->approval_status
	return FALSE;
}
/*************************************************************************/
/***** Function To convert only first charater of string in uppercase ****/
/*************************************************************************/
function ucname($string)
{
	$string = ucwords(strtolower($string));
	foreach (array(
		'-',
		'\''
	) as $delimiter)
	{
		if (strpos($string, $delimiter) !== false)
		{
			$string = implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
		} //strpos($string, $delimiter) !== false
	} //array( '-', '\'' ) as $delimiter
	return $string;
}
function _df_sentence_case($string)
{
	$string = ucwords(strtolower($string));
	foreach (array(
		'-',
		'\''
	) as $delimiter)
	{
		if (strpos($string, $delimiter) !== false)
		{
			$string = implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
		} //strpos($string, $delimiter) !== false
	} //array( '-', '\'' ) as $delimiter
	return $string;
}
function _df_list_of_states()
{
	$states = array(
		0 => '-Select-'
	);
	$query = \Drupal::database()->select('list_states_of_india');
	$query->fields('list_states_of_india');
	//$query->orderBy('', '');
	$states_list = $query->execute();
	while ($states_list_data = $states_list->fetchObject())
	{
		$states[$states_list_data->state] = $states_list_data->state;
	} //$states_list_data = $states_list->fetchObject()
	return $states;
}
function _df_list_of_classes()
{
	$class_name = array();
	$query = \Drupal::database()->select('class_table');
    $query->fields('class_table');
	$query->orderBy('id', 'ASC');
	$class_name_list = $query->execute();
    while ($class_name_list_data = $class_name_list->fetchObject())
      {
        $class_name[$class_name_list_data->class_name] = $class_name_list_data->class_name;
      }
    return $class_name;
}
function _df_list_of_cities()
{
	$city = array(
		0 => '-Select-'
	);
	$query = \Drupal::database()->select('list_cities_of_india');
	$query->fields('list_cities_of_india');
	$query->orderBy('city', 'ASC');
	$city_list = $query->execute();
	while ($city_list_data = $city_list->fetchObject())
	{
		$city[$city_list_data->city] = $city_list_data->city;
	} //$city_list_data = $city_list->fetchObject()
	return $city;
}
function _df_list_of_pincodes()
{
	$pincode = array(
		0 => '-Select-'
	);
	$query = \Drupal::database()->select('list_of_all_india_pincode');
	$query->fields('list_of_all_india_pincode');
	$query->orderBy('pincode', 'ASC');
	$pincode_list = $query->execute();
	while ($pincode_list_data = $pincode_list->fetchObject())
	{
		$pincode[$pincode_list_data->pincode] = $pincode_list_data->pincode;
	} //$pincode_list_data = $pincode_list->fetchObject()
	return $pincode;
}

function _df_list_of_departments()
{
	$department = array();
	$query = \Drupal::database()->select('list_of_departments');
	$query->fields('list_of_departments');
	$query->orderBy('id', 'DESC');
	$department_list = $query->execute();
	while ($department_list_data = $department_list->fetchObject())
	{
		$department[$department_list_data->department] = $department_list_data->department;
	} //$department_list_data = $department_list->fetchObject()
	return $department;
}
function _soul_list_of_second_software_version()
{
    $second_software = array(
		0 => '-Select-'
	);
    $query = \Drupal::database()->select('soul_science_and_concept_map_second_software');
    $query->fields('soul_science_and_concept_map_second_software');
    $second_software_list = $query->execute();
    while ($second_software_list_data = $second_software_list->fetchObject())
      {
        $second_software[$second_software_list_data->second_software] = $second_software_list_data->second_software;
      }
    return $second_software;
}
function _soul_list_of_category()
  {
    $category = array();
    $query = \Drupal::database()->select('soul_science_and_concept_map_category');
    $query->fields('soul_science_and_concept_map_category');
    $category_list = $query->execute();
    while ($category_list_data = $category_list->fetchObject())
      {
        $category[$category_list_data->category] = $category_list_data->category;
      }
    return $category;
  }

function _soul_list_of_sub_category()
{	
	$sub_category = array(
		0 => '-Select-'
	);
	$query = \Drupal::database()->select('soul_science_and_concept_map_sub_category');
	$query->fields('soul_science_and_concept_map_sub_category');
	$sub_category_list = $query->execute();
	while ($sub_category_list_data = $sub_category_list->fetchObject())
	{
		$sub_category[$sub_category_list_data->sub_category] = $sub_category_list_data->sub_category;
	} //$city_list_data = $city_list->fetchObject()
	return $sub_category;
}

function _soul_list_of_software_version()
  {
    $software_version = array();
    $query = \Drupal::database()->select('soul_science_and_concept_map_software_version');
    $query->fields('soul_science_and_concept_map_software_version');
    $software_version_list = $query->execute();
    while ($software_version_list_data = $software_version_list->fetchObject())
      {
        $software_version[$software_version_list_data->id] = $software_version_list_data->software_versions;
      }
    return $software_version;
  }


function _soul_list_of_software_version_details($software_version_id)
{
    $software_version_id = $software_version_id;
    $software_versions = array(
		0 => '-Select-'
	);
    $query = \Drupal::database()->select('soul_science_and_concept_map_software_version_details');
    $query->fields('soul_science_and_concept_map_software_version_details');
    $query->condition('software_version_id',$software_version_id);
    $software_versions_list = $query->execute();
    while($software_versions_data = $software_versions_list->fetchObject()){
        $software_versions[$software_versions_data->software_version_name] = $software_versions_data->software_version_name;
    }
    return $software_versions;
}

function _scmp_dir_name($project, $proposar_name)
{
	$project_title = ucname($project);
	$proposar_name = ucname($proposar_name);
	$dir_name = $project_title . ' By ' . $proposar_name;
	$directory_name = str_replace("__", "_", str_replace(" ", "_", str_replace("/","_", trim($dir_name))));
	return $directory_name;
}
function science_and_concept_map_document_path()
{
	return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'soul_uploads/science_and_concept_map_uploads/';
}
function DF_RenameDir($proposal_id, $dir_name)
{
	$proposal_id = $proposal_id;
	$dir_name = $dir_name;
	$query = \Drupal::database()->query("SELECT directory_name,id FROM soul_science_and_concept_map_proposal WHERE id = :proposal_id", array(
		':proposal_id' => $proposal_id
	));
	$result = $query->fetchObject();
	if ($result != NULL)
	{
		$files = scandir(science_and_concept_map_path());
		$files_id_dir = science_and_concept_map_path() . $result->id;
		//var_dump($files);die;
		$file_dir = science_and_concept_map_path() . $result->directory_name;
		if (is_dir($file_dir))
		{
			$new_directory_name = rename(science_and_concept_map_path() . $result->directory_name, science_and_concept_map_path() . $dir_name);
			return $new_directory_name;
		} //is_dir($file_dir)
		else if (is_dir($files_id_dir))
		{
			$new_directory_name = rename(science_and_concept_map_path() . $result->id, science_and_concept_map_path() . $dir_name);
			return $new_directory_name;
		} //is_dir($files_id_dir)
		else
		{
			\Drupal::messenger()->addMessage('Directory not available for rename.');
			return;
		}
	} //$result != NULL
	else
	{
		\Drupal::messenger()->addMessage('Project directory name not present in databse');
		return;
	}
	return;
}
function CreateReadmeFileSoulScienceAndConceptMapProject($proposal_id)
{
	$result = \Drupal::database()->query("
                        SELECT * from soul_science_and_concept_map_proposal WHERE id = :proposal_id", array(
		":proposal_id" => $proposal_id
	));
	$proposal_data = $result->fetchObject();
	$root_path = science_and_concept_map_path();
	$readme_file = fopen($root_path . $proposal_data->directory_name . "/README.txt", "w") or die("Unable to open file!");
	$txt = "";
	$txt .= "About the science and concept map";
	$txt .= "\n" . "\n";
	$txt .= "Title Of The science and concept map Project: " . $proposal_data->project_title . "\n";
	$txt .= "Proposar Name: " . $proposal_data->name_title . " " . $proposal_data->contributor_name . "\n";
	$txt .= "University: " . $proposal_data->university . "\n";
	$txt .= "\n" . "\n";
	$txt .= "soul science and concept map Project By FOSSEE, IIT Bombay" . "\n";
	fwrite($readme_file, $txt);
	fclose($readme_file);
	return $txt;
}
function rrmdir_project($prop_id)
{
	$proposal_id = $prop_id;
	$result = \Drupal::database()->query("
					SELECT * from soul_science_and_concept_map_proposal WHERE id = :proposal_id", array(
		":proposal_id" => $proposal_id
	));
	$proposal_data = $result->fetchObject();
	$root_path = science_and_concept_map_document_path();
	$dir = $root_path . $proposal_data->directory_name;
	if ($proposal_data->id == $prop_id)
	{
		if (is_dir($dir))
		{
			$objects = scandir($dir);
			foreach ($objects as $object)
			{
				if ($object != "." && $object != "..")
				{
					if (filetype($dir . "/" . $object) == "dir")
					{
						rrmdir($dir . "/" . $object);
					} //filetype($dir . "/" . $object) == "dir"
					else
					{
						unlink($dir . "/" . $object);
					}
				} //$object != "." && $object != ".."
			} //$objects as $object
			reset($objects);
			rmdir($dir);
			$msg = \Drupal::messenger()->addMessage("Directory deleted successfully");
			return $msg;
		} //is_dir($dir)
		$msg = \Drupal::messenger()->addMessage("Directory not present");
		return $msg;
	} //$proposal_data->id == $prop_id
	else
	{
		$msg = \Drupal::messenger()->addMessage("Data not found");
		return $msg;
	}
}
function rrmdir($dir)
{
	if (is_dir($dir))
	{
		$objects = scandir($dir);
		foreach ($objects as $object)
		{
			if ($object != "." && $object != "..")
			{
				if (filetype($dir . "/" . $object) == "dir")
					rrmdir($dir . "/" . $object);
				else
					unlink($dir . "/" . $object);
			} //$object != "." && $object != ".."
		} //$objects as $object
		reset($objects);
		rmdir($dir);
	} //is_dir($dir)
}
}