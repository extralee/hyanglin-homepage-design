<?php
$KData = isset($_REQUEST["KData"]) ? $_REQUEST["KData"] : "";
$_tmp = explode(";base64,", $KData);
if(count($_tmp) == 2) {
	$filename = "fileupload/canvas.jpg";
	$imageData = base64_decode($_tmp[1]);	
	//echo "0000".$imageData;
	$fp = fopen($filename, "wb");
	if($fp) {
		fwrite($fp, $imageData);
		fclose($fp);
		echo "success";
	} else {
		echo "failed";
	}
}
?>
