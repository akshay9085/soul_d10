<?php

namespace Drupal\science_and_concept_map\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Generates the student Science and Concept Map certificate PDF.
 */
class GenerateStudentCertificate extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'generate_student_certificate_pdf';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $module_path = drupal_get_path('module', 'science_and_concept_map');
    require_once $module_path . '/pdf/fpdf/fpdf.php';
    require_once $module_path . '/pdf/phpqrcode/qrlib.php';

    $request = \Drupal::request();
    $proposal_id = (int) ($request->query->get('proposal_id') ?? 0);
    if (!$proposal_id) {
      \Drupal::messenger()->addError($this->t('Certificate is not available.'));
      return [];
    }

    $current_user = $this->currentUser();
    $query = \Drupal::database()->select('soul_science_and_concept_map_proposal', 'p');
    $query->fields('p');
    $query->condition('approval_status', 3);
    $query->condition('id', $proposal_id);
    $query->condition('uid', $current_user->id());
    $query->range(0, 1);
    $proposal = $query->execute()->fetchObject();

    if (!$proposal) {
      \Drupal::messenger()->addError($this->t('Certificate is not available.'));
      return [];
    }

    $pdf = new \FPDF('L', 'mm', 'Letter');
    if (!$pdf) {
      \Drupal::messenger()->addError($this->t('Unable to initialize the PDF generator.'));
      return [];
    }

    $pdf->AddPage();
    $image_bg = $module_path . '/pdf/images/bg_cert.png';
    $pdf->Image($image_bg, 0, 0, $pdf->GetPageWidth(), $pdf->GetPageHeight());
    $pdf->SetMargins(18, 1, 18);
    $pdf->SetFont('Times', 'BI', 25);
    $pdf->Ln(35);
    $pdf->SetFont('Times', 'I', 16);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(240, 20, 'This e-certificate is being awarded to', 0, 1, 'C');
    $pdf->Ln(-7);
    $pdf->SetFont('Times', 'BI', 16);
    $pdf->SetTextColor(4, 118, 208);
    $pdf->MultiCell(240, 8, utf8_decode($proposal->contributor_name), 0, 'C');
    $pdf->Ln(-1);

    if (strtolower($proposal->department) != 'others') {
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetFont('Times', 'I', 16);
      $pdf->Cell(240, 8, 'from ', 0, 1, 'C');
      $pdf->Ln(-1);
      $pdf->SetFont('Times', 'BI', 16);
      $pdf->SetTextColor(4, 118, 208);
      $pdf->MultiCell(240, 8, utf8_decode($proposal->university), 0, 'C');
      $pdf->Ln(-1);
      $pdf->SetFont('Times', 'I', 16);
      $pdf->SetTextColor(0, 0, 0);
      if (strpos($proposal->contributor_name, ',') !== FALSE) {
        $pdf->Cell(240, 8, 'The team has successfully completed the Science and Concept Map Project ', 0, 1, 'C');
      }
      else {
        $pdf->Cell(240, 8, 'The candidate has successfully completed the Science and Concept Map Project', 0, 1, 'C');
      }
      $pdf->Ln(-1);
      $pdf->Cell(240, 8, 'under SOUL (Science OpenSource Software for Teaching Learning).', 0, 1, 'C');
      $pdf->Ln(-1);
      $pdf->SetFont('Times', 'I', 16);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->MultiCell(240, 8, 'The topic was "' . utf8_decode($proposal->project_title) . '"', 0, 'C');
      $pdf->Cell(240, 4, 'The work done is available at', 0, 1, 'C');
      $pdf->Ln(4);
      $pdf->SetX(110);
      $pdf->SetFont('', 'U');
      $pdf->SetTextColor(139, 69, 19);
      $pdf->write(0, 'https://soul.fossee.in/', 'https://soul.fossee.in/');
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Ln(10);
      $pdf->SetFont('Times', 'BI', 16);
      $pdf->Cell(240, 8, ' Prof. Kannan Moudgalya', 0, 1, 'C');
      $pdf->Ln(-2);
      $pdf->SetFont('Arial', 'I', 10);
      $pdf->Cell(240, 8, ' Principal Investigator - FOSSEE', 0, 1, 'C');
      $pdf->Ln(-4);
      $pdf->SetFont('Arial', 'I', 10);
      $pdf->Cell(240, 8, ' (Free/Libre and Open Source Software for Education)', 0, 1, 'C');
      $pdf->Ln(-4);
      $pdf->Cell(240, 8, 'IIT Bombay', 0, 1, 'C');
      $pdf->Ln(0);
    }
    else {
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(240, 8, 'from ', 0, 1, 'C');
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(240, 8, 'from ' . $proposal->university . ' has successfully', 0, 1, 'C');
      $pdf->Ln(0);
      $pdf->Cell(240, 8, 'completed Science and Concept Map Project', 0, 1, 'C');
    }

    $temp_dir = $module_path . '/pdf/temp_prcode/';
    $qr_query = \Drupal::database()->select('soul_science_and_concept_map_qr_code', 'q');
    $qr_query->fields('q');
    $qr_query->condition('proposal_id', $proposal_id);
    $qr_query->range(0, 1);
    $qr_data = $qr_query->execute()->fetchObject();

    if (!$qr_data || empty($qr_data->qr_code) || $qr_data->qr_code === 'null') {
      $unique_string = $this->generateRandomString();
      if ($qr_data) {
        \Drupal::database()->update('soul_science_and_concept_map_qr_code')
          ->fields(['qr_code' => $unique_string])
          ->condition('proposal_id', $proposal_id)
          ->execute();
      }
      else {
        \Drupal::database()->insert('soul_science_and_concept_map_qr_code')
          ->fields([
            'proposal_id' => $proposal_id,
            'qr_code' => $unique_string,
          ])
          ->execute();
      }
    }
    else {
      $unique_string = $qr_data->qr_code;
    }

    if (!isset($unique_string)) {
      $unique_string = $qr_data ? $qr_data->qr_code : $this->generateRandomString();
    }

    $code_contents = 'https://soul.fossee.in/science-and-concept-map-project/certificates/verify/' . $unique_string;
    $file_name = 'generated_qrcode.png';
    $png_absolute_file_path = $temp_dir . $file_name;
    \QRcode::png($code_contents, $png_absolute_file_path);

    $pdf->SetX(29);
    $pdf->SetY(-58);
    $pdf->Image($png_absolute_file_path, $pdf->GetX() + 205, $pdf->GetY() - 134, 30, 0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetY(15);
    $pdf->Ln(8);
    $pdf->SetX(215);
    $pdf->Cell(0, 0, $unique_string, 0, 0, 'C');

    $image_footer = $module_path . '/pdf/images/footer_text.png';
    $image_moe = $module_path . '/pdf/images/moe_logo.png';
    $image_soul = $module_path . '/pdf/images/soul_logo.png';
    $image_fossee = $module_path . '/pdf/images/fossee.png';
    $pdf->SetY(-55);
    $pdf->SetX(80);
    $pdf->Ln(8);
    $pdf->Image($image_soul, $pdf->GetX() + 5, $pdf->GetY() + 3, 60, 0);
    $pdf->Ln(6);
    $pdf->Image($image_fossee, $pdf->GetX() + 90, $pdf->GetY() - 5, 60, 0);
    $pdf->Image($image_moe, $pdf->GetX() + 180, $pdf->GetY() - 5, 60, 0);
    $pdf->Image($image_footer, $pdf->GetX() - 3, $pdf->GetY() + 22, 250, 0);

    $filename = str_replace(' ', '-', $proposal->contributor_name) . '-SOUL-Project-Certificate.pdf';
    $file = $module_path . '/pdf/temp_certificate/' . $proposal_id . '_' . $filename;
    $pdf->Output($file, 'F');

    if (ob_get_length()) {
      ob_clean();
    }
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: public');
    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . filesize($file));
    flush();

    $fp = fopen($file, 'rb');
    while ($fp && !feof($fp)) {
      echo fread($fp, 65536);
      flush();
    }
    if ($fp) {
      fclose($fp);
    }
    unlink($file);

    return [];
  }

  /**
   * Generates a random string for QR codes.
   */
  private function generateRandomString($length = 5) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $characters_length = strlen($characters);
    $random_string = '';
    for ($i = 0; $i < $length; $i++) {
      $random_string .= $characters[random_int(0, $characters_length - 1)];
    }
    return $random_string;
  }
public function submitForm(array &$form, FormStateInterface $form_state) {
  // If you don't need form submission logic, leave empty.
}
}
