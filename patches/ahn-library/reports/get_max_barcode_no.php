<?
require_once "lib.php";
if(!$connect) $connect=dbConn();

$data=mysql_fetch_array(mysql_query("SELECT max(barcode_nmbr) FROM `biblio_copy` where barcode_nmbr<'HC009999'"));
echo "<b>HC 최종번호</b> = ".$data[0]."<br>";
$data=mysql_fetch_array(mysql_query("SELECT max(barcode_nmbr) FROM `biblio_copy` where barcode_nmbr<'AC009999'"));
echo "<b>AC 최종번호</b> = ".$data[0]."<br>";
$result=mysql_query("SELECT barcode_nmbr FROM `biblio_copy` where barcode_nmbr>'HC009999' OR (barcode_nmbr>'AC009999' AND barcode_nmbr<'HC000000')");
echo "<br>";
echo "<b>입력오류 의심번호</b> = ";
while($data=@mysql_fetch_array($result)) {
	echo $data[0].", ";
}
echo "<br>";
$result=mysql_query("SELECT barcode_nmbr FROM `biblio_copy` where barcode_nmbr<'HC009999' AND barcode_nmbr>'HC000000'");
echo "<br>";
echo "<b>HC 중간에 빈 번호</b> = ";

$pre_no=0;
while($data=@mysql_fetch_array($result)) {
	$no=substr($data[0],2)*1;
	$no_margin=$no-$pre_no;
	if($no_margin>1) {
		for($i=0; $i<$no_margin-1; $i++) {
			$missing_no=$no+$i-($no_margin-1);
			if($missing_no<10) $missing_no="00000".$missing_no;
			elseif($missing_no<100) $missing_no="0000".$missing_no;
			elseif($missing_no<1000) $missing_no="000".$missing_no;
			elseif($missing_no<10000) $missing_no="00".$missing_no;
			elseif($missing_no<100000) $missing_no="0".$missing_no;
			echo "HC".$missing_no.", ";
		}
	}
	$pre_no=$no;
}

$result=mysql_query("SELECT barcode_nmbr FROM `biblio_copy` where barcode_nmbr<'AC009999'");
echo "<br><br>";
echo "<b>AC 중간에 빈 번호</b> = ";
$pre_no=0;
while($data=@mysql_fetch_array($result)) {
	$no=substr($data[0],2)*1;
	$no_margin=$no-$pre_no;
	if($no_margin>1) {
		for($i=0; $i<$no_margin-1; $i++) {
			$missing_no=$no+$i-($no_margin-1);
			if($missing_no<10) $missing_no="00000".$missing_no;
			elseif($missing_no<100) $missing_no="0000".$missing_no;
			elseif($missing_no<1000) $missing_no="000".$missing_no;
			elseif($missing_no<10000) $missing_no="00".$missing_no;
			elseif($missing_no<100000) $missing_no="0".$missing_no;
			echo "AC".$missing_no.", ";
		}
	}
	$pre_no=$no;
}

mysql_close($connect);



?>