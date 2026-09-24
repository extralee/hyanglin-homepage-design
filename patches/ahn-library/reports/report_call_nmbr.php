<?
require_once "lib.php";
if(!$connect) $connect=dbConn();

$etc_from = isset($_REQUEST["etc_from"]) ? mysql_real_escape_string($_REQUEST["etc_from"], $connect) : "";
$etc_to = isset($_REQUEST["etc_to"]) ? mysql_real_escape_string($_REQUEST["etc_to"], $connect) : "";
$etc_code = isset($_REQUEST["etc_code"]) ? mysql_real_escape_string($_REQUEST["etc_code"], $connect) : "";
//echo $etc_from;
//echo $etc_to;
//echo $etc_code;

$from=intval($etc_from);
if($from<10) $from="00000".$from;
elseif($from<100) $from="0000".$from;
elseif($from<1000) $from="000".$from;
elseif($from<10000) $from="00".$from;
elseif($from<100000) $from="0".$from;
$to=intval($etc_to);
if($to<10) $to="00000".$to;
elseif($to<100) $to="0000".$to;
elseif($to<1000) $to="000".$to;
elseif($to<10000) $to="00".$to;
elseif($to<100000) $to="0".$to;

/*
material_type_dm의 구분
1=안병무간행물
3=안병무소장물
9=교회 출판물

SELECT  b.material_cd, b.call_nmbr1, b.call_nmbr2, b.call_nmbr3, a.barcode_nmbr, a.copy_desc FROM `biblio_copy` a left join `biblio` b on a.bibid=b.bibid WHERE substring(a.barcode_nmbr,1,2)='9' and substring(a.barcode_nmbr,3)>='000050' and substring(a.barcode_nmbr,3)<='000060' ORDER BY a.barcode_nmbr
*/

$result=mysql_query("SELECT  b.material_cd, b.call_nmbr1, b.call_nmbr2, b.call_nmbr3, a.barcode_nmbr, a.copy_desc FROM `biblio_copy` a left join `biblio` b on a.bibid=b.bibid WHERE substring(a.barcode_nmbr,1,2)='$etc_code' and substring(a.barcode_nmbr,3)>='$from' and substring(a.barcode_nmbr,3)<='$to' ORDER BY a.barcode_nmbr");
echo "5<br>";
while($data=@mysql_fetch_array($result)) {
	$material_type="";
	if($data[0]==1) $material_type="AP";
	elseif($data[0]==3) $material_type="AC";
	elseif($data[0]==9) $material_type="HP";
	echo $material_type."<br>";
	echo $data[1]."<br>";
	echo $data[2]."<br>";
	echo $data[3]." ".$data[5]."<br>";
	echo $data[4]."<br>";
}

/*
$result=mysql_query("SELECT  barcode_nmbr FROM `biblio_copy` order by barcode_nmbr");


while($data=@mysql_fetch_array($result)) {
	echo $data[0]."<br>";
}
*/
mysql_close($connect);

?>