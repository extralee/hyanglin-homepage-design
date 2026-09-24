<?
require_once "lib.php";
if(!$connect) $connect=dbConn();

$cr_member_no = isset($_REQUEST["cr_member_no"]) ? mysql_real_escape_string($_REQUEST["cr_member_no"], $connect) : "";

for($i=1; $i<=8; $i++) {
	$val = isset($_REQUEST["category_subject_".$i]) ? trim(mysql_real_escape_string($_REQUEST["category_subject_".$i], $connect)) : "";
	$modify_que="update schedule_category set subject='".$val."' where no='$i'";
	mysql_query("$modify_que") or error(mysql_error());
}

@mysql_close($connect);
?>
<SCRIPT LANGUAGE="JavaScript">
this.location="work_schedule.php?mid=<?=$cr_member_no?>";
</SCRIPT>