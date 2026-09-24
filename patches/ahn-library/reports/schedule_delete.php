<?
require_once "lib.php";
if(!$connect) $connect=dbConn();

$cr_no = isset($_REQUEST["cr_no"]) ? intval($_REQUEST["cr_no"]) : (isset($_REQUEST["no"]) ? intval($_REQUEST["no"]) : 0);
$mid = isset($_REQUEST["mid"]) ? mysql_real_escape_string($_REQUEST["mid"], $connect) : "";
$nowyear = isset($_REQUEST["nowyear"]) ? intval($_REQUEST["nowyear"]) : 0;
$nowmonth = isset($_REQUEST["nowmonth"]) ? intval($_REQUEST["nowmonth"]) : 0;

if($cr_no>0) { //삭제
	$delete_que="delete from schedule where no=$cr_no";
	mysql_query("$delete_que") or error(mysql_error());
}

@mysql_close($connect);
?>
<SCRIPT LANGUAGE="JavaScript">
this.location="work_schedule.php?mid=<?=$mid?>&nowyear=<?=$nowyear?>&nowmonth=<?=$nowmonth?>";
</SCRIPT>