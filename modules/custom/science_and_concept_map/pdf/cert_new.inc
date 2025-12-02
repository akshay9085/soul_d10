<?php
function generate_pdf()
{
	$mpath = drupal_get_path('module', 'science_and_concept_map');
	//var_dump($mpath);die;
	require($mpath . '/pdf/fpdf/fpdf.php');
	require($mpath . '/pdf/phpqrcode/qrlib.php');
	$user = \Drupal::currentUser();
	$x                 = $user->uid;
	$proposal_id       = arg(3);
	$query3 = \Drupal::database()->query("SELECT * FROM soul_science_and_concept_map_proposal WHERE approval_status=3 AND uid= :uid AND id=:proposal_id", array(
		':uid' => $user->uid,
		':proposal_id'=>$proposal_id
	));
	$data3 = $query3->fetchObject();
	
	if($data3){
	if($data3->uid != $x){
		\Drupal::messenger()->addError('Certificate is not available');
		return;
	}
	}
	
	$pdf = new FPDF('L', 'mm', 'Letter');
	if (!$pdf) {
		echo "Error!";
	} //!$pdf
	$pdf->AddPage();
	$image_bg = $mpath . "/pdf/images/bg_cert.png";
	$pdf->Image($image_bg, 0, 0, $pdf->GetPageWidth(), $pdf->GetPageHeight());
	$pdf->SetMargins(18, 1, 18);
	$path   = drupal_get_path('module', 'science_and_concept_map');
	$pdf->SetFont('times', 'BI', 25);
	$pdf->Ln(35);
	$pdf->SetFont('times', 'I' , 16);
	$pdf->SetTextColor(0, 0, 0);
	$pdf->Cell(240, 20, 'This e-certificate is being awarded to', '0', '1', 'C');
	$pdf->Ln(-7);
	$pdf->SetFont('times', 'BI', 16);
	$pdf->SetTextColor(4,118,208);
	$pdf->MultiCell(240, 8, utf8_decode($data3->contributor_name), '0', 'C');
	$pdf->Ln(-1);
	
	if (strtolower($data3->department) != "others") {
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('times', 'I' , 16);
		$pdf->Cell(240, 8, 'from ', '0', '1', 'C');
		$pdf->Ln(-1);
		$pdf->SetFont('times', 'BI', 16);
		$pdf->SetTextColor(4,118,208);
		$pdf->MultiCell(240, 8, utf8_decode($data3->university) , '0','C');
		$pdf->Ln(-1);
		if (strpos($data3->contributor_name,',')!==false){
			$pdf->SetFont('times', 'I', 16);
		$pdf->SetTextColor(0, 0, 0);
		$pdf->Cell(240, 8,'The team has successfully completed the Science and Concept Map Project ', '0', '1', 'C');
		}
		else{
		$pdf->SetFont('times', 'I', 16);
		$pdf->SetTextColor(0, 0, 0);
		$pdf->Cell(240, 8,'The candidate has successfully completed the Science and Concept Map Project', '0', '1', 'C');
		}
		$pdf->Ln(-1);
		// $pdf->SetFont('times', 'I', 16);
		// $pdf->Cell(240, 8, 'the Science and Concept Map Project ', '0', '1', 'C');
		$pdf->Cell(240, 8,'under SOUL (Science OpenSource Software for Teaching Learning).', '0', '1', 'C');
		$pdf->Ln(-1);
		$pdf->SetFont('times', 'I', 16);
		$pdf->SetTextColor(0, 0, 0);
		$pdf->MultiCell(240, 8,'The topic was '.'"'. utf8_decode($data3->project_title).'"', '0', 'C');
		// $pdf->Ln(-1);
		$pdf->Cell(240, 4, 'The work done is available at', '0', '1', 'C');
		$pdf->Ln(4);
		$pdf->SetX(110);
		$pdf->SetFont('', 'U');
		$pdf->SetTextColor(139, 69, 19);
		$pdf->write(0, 'https://soul.fossee.in/', 'https://soul.fossee.in/');
		$pdf->SetTextColor(0, 0, 0);
		$pdf->Ln(10);
		$pdf->SetFont('times', 'BI', 16);
		$pdf->Cell(240, 8, ' Prof. Kannan Moudgalya', '0', '1', 'C');
		$pdf->Ln(-2);
		$pdf->SetFont('Arial', 'I', 10);
		$pdf->Cell(240, 8, ' Principal Investigator - FOSSEE', '0', '1', 'C');
		$pdf->Ln(-4);
		$pdf->SetFont('Arial', 'I', 10);
		$pdf->Cell(240, 8, ' (Free/Libre and Open Source Software for Education)', '0', '1', 'C');
		$pdf->Ln(-4);
		$pdf->Cell(240, 8, 'IIT Bombay', '0', '1', 'C');
		// $pdf->Cell(240, 4, 'The work done is available at', '0', '1', 'C');
		// $pdf->SetX(120);
		// $pdf->SetFont('', 'U');
		// $pdf->SetTextColor(139, 69, 19);
		// $pdf->write(0, 'https://Soul.fossee.in/', 'https://Soul.fossee.in/');
		// $pdf->Ln(0);
		//$pdf->Ln(52);
		// $pdf->SetFont('Arial', '', 9);
		// $pdf->SetTextColor(0, 0, 0);
		
		//$pdf->Cell(240, 8, ' This is a computer generated certificate and requires no signature. To verify, scan QR code or visit: https://cfd.fossee.in/research-migration-project/certificates/verify', '0', '1', 'C');
		$pdf->Ln(0);
	} //strtolower($data3->department) != "others"
	else {
		$pdf->SetTextColor(0, 0, 0);
		$pdf->Cell(240, 8, 'from ', '0', '1', 'c');
		$pdf->SetTextColor(0, 0, 0);
		$pdf->Cell(240, 8, 'from ' . $data3->university . ' has successfully', '0', '1', 'C');
		$pdf->Ln(0);
		$pdf->Cell(240, 8, 'completed Science and Concept Map Project', '0', '1', 'C');
		$pdf->Ln(0);
	}
	$proposal_get_id = 0;
	$UniqueString    = "";
	$tempDir         = $path . "/pdf/temp_prcode/";
	$query           = \Drupal::database()->select('soul_science_and_concept_map_qr_code');
	$query->fields('soul_science_and_concept_map_qr_code');
	$query->condition('proposal_id', $proposal_id);
	$result          = $query->execute();
	$data            = $result->fetchObject();
	$DBString        = $data->qr_code;
	$proposal_get_id = $data->proposal_id;
	if ($DBString == "" || $DBString == "null") {
		$UniqueString = generateRandomString();
		$query        = "
				INSERT INTO soul_science_and_concept_map_qr_code
				(proposal_id,qr_code)
				VALUES
				(:proposal_id,:qr_code)
				";
		$args         = array(
			":proposal_id" => $proposal_id,
			":qr_code" => $UniqueString
		);
		$result       = \Drupal::database()->query($query, $args, $query);
	} //$DBString == "" || $DBString == "null"
	else {
		$UniqueString = $DBString;
	}
	$codeContents        = "https://soul.fossee.in/science-and-concept-map-project/certificates/verify/" . $UniqueString;
	$fileName            = 'generated_qrcode.png';
	$pngAbsoluteFilePath = $tempDir . $fileName;
	$urlRelativeFilePath = $path . "/pdf/temp_prcode/" . $fileName;
	QRcode::png($codeContents, $pngAbsoluteFilePath);
	/*$pdf->SetTextColor(0, 0, 0);
	$pdf->Ln(30);
	$pdf->SetX(198);
	$pdf->SetFont('', '');
	$pdf->SetTextColor(0, 0, 0);
	$pdf->SetY(-85);
	$pdf->SetX(200);
	$pdf->Ln(16);
	$pdf->Cell(240, 8, 'Prof. Kannan M. Moudgalya', 0, 1, 'R');
	$pdf->Ln(-2);
	$pdf->SetFont('Arial', '', 10);
	$pdf->Cell(240, 8, 'Principal Investigator - FOSSEE', 0, 1, 'R');
	$pdf->Ln(-2);
	$pdf->Cell(240, 8, ' Dept. of Chemical Engineering, IIT Bombay.', 0, 1, 'R');*/
	
	//$pdf->Ln(30);
	$pdf->SetX(29);
	$pdf->SetY(-58);
	$pdf->Image($pngAbsoluteFilePath, $pdf->GetX() + 205, $pdf->GetY() -134, 30, 0);
	// $sign = $path . "/pdf/images/sign.png";
	// $pdf->Image($sign, $pdf->GetX() + 170, $pdf->GetY() - 15, 80, 0);
	$pdf->SetX(29);
	$pdf->SetFont('Arial', 'B', 10);
	$pdf->SetY(15);
	$pdf->Ln(8);
	$pdf->SetX(215);
	$pdf->Cell(0, 0, $UniqueString, 0, 0, 'C');
	// $pdf->SetX(29);
	// $pdf->SetY(-50);
	 $image5 = $path . "/pdf/images/footer_text.png";
	$pdf->SetY(-55);
	$pdf->SetX(80);
	$image3 = $path . "/pdf/images/moe_logo.png";
	$image2 = $path . "/pdf/images/soul_logo.png"; 
	$image4 = $path . "/pdf/images/fossee.png"; 
	$pdf->Ln(8);
	$pdf->Image($image2, $pdf->GetX() +5, $pdf->GetY() + 3, 60, 0);
	$pdf->Ln(6);
	$pdf->Image($image4, $pdf->GetX() + 90, $pdf->GetY() -5, 60, 0);
	
	$pdf->Image($image3, $pdf->GetX() + 180, $pdf->GetY() -5, 60, 0);
	//$pdf->Ln(2);
	// $pdf->Cell(240, 8, ' This is a computer generated certificate and requires no signature.', '0', '1', 'C');
	$pdf->Image($image5, $pdf->GetX()-3 , $pdf->GetY() + 22, 250, 0);
	$pdf->SetFont('Arial', 'I', 8);
	$pdf->SetTextColor(0, 0, 0);
	$filename = str_replace(' ', '-', $data3->contributor_name) . '-SOUL-Project-Certificate.pdf';
	$file     = $path . '/pdf/temp_certificate/' . $proposal_id . '_' . $filename;
	$pdf->Output($file, 'F');
	ob_clean();
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: public");
	header("Content-Description: File Transfer");
	header("Content-Type: application/pdf");
	header("Content-Disposition: attachment; filename=" . $filename);
	header("Content-Length: " . filesize($file));
	header("Content-Transfer-Encoding: binary");
	header("Expires: 0");
	header("Pragma: no-cache");
	flush();
	$fp = fopen($file, "r");
	while (!feof($fp)) {
		echo fread($fp, filesize($file));
		flush();
	} //!feof($fp)
	ob_end_flush();
	ob_clean();
	fclose($fp);
	unlink($file);

	//drupal_goto('flowsheeting-project/certificate');
	return;
}
function generateRandomString($length = 5)
{
	$characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString     = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	} //$i = 0; $i < $length; $i++
	return $randomString;
}
