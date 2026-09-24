<?
require_once "lib.php";
if(!$connect) $connect=dbConn();

$no = isset($_REQUEST["no"]) ? intval($_REQUEST["no"]) : 0;
$confirm = isset($_REQUEST["confirm"]) ? intval($_REQUEST["confirm"]) : 0;
$mid = isset($_REQUEST["mid"]) ? mysql_real_escape_string($_REQUEST["mid"], $connect) : "";

$reg_date=time(); // 현재의 시간구함;;
$update_date=date("YmdHis", $reg_date);

//if($confirm) { //수정
	$modify_que="update work_list set confirm='$confirm' where no='$no'";
	mysql_query("$modify_que") or error(mysql_error());
//} 

//echo "777";
@mysql_close($connect);
?>
<SCRIPT LANGUAGE="JavaScript">
<?
if($confirm==1) {
	echo "alert('관장 확인이 이루어졌습니다.')";
} else {
	echo "alert('관장 확인이 취소되었습니다.')";
}
?>
</SCRIPT>