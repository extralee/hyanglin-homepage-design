<?
require_once "lib.php";
if(!$connect) $connect=dbConn();

$cr_no = isset($_REQUEST["cr_no"]) ? intval($_REQUEST["cr_no"]) : 0;
$cr_member_no = isset($_REQUEST["cr_member_no"]) ? mysql_real_escape_string($_REQUEST["cr_member_no"], $connect) : "";
$nowyear = isset($_REQUEST["nowyear"]) ? intval($_REQUEST["nowyear"]) : 0;
$nowmonth = isset($_REQUEST["nowmonth"]) ? intval($_REQUEST["nowmonth"]) : 0;
$cr_category = isset($_REQUEST["cr_category"]) ? mysql_real_escape_string($_REQUEST["cr_category"], $connect) : "";
$cr_subject = isset($_REQUEST["cr_subject"]) ? mysql_real_escape_string($_REQUEST["cr_subject"], $connect) : "";
$cr_name = isset($_REQUEST["cr_name"]) ? mysql_real_escape_string($_REQUEST["cr_name"], $connect) : "";
$cr_year = isset($_REQUEST["cr_year"]) ? intval($_REQUEST["cr_year"]) : 0;
$cr_month = isset($_REQUEST["cr_month"]) ? intval($_REQUEST["cr_month"]) : 0;
$cr_day = isset($_REQUEST["cr_day"]) ? intval($_REQUEST["cr_day"]) : 0;

/*
$data2=mysql_fetch_array(mysql_query("select count(*) from `g4_member` where mb_id='$cr_member_no'"));
if($data2[0]>0&&$cr_category!=9) $cr_member_no=0;
*/
$reg_date=time(); // 현재의 시간구함;;
$update_date=date("YmdHis", $reg_date);
//if($cr_category==0) $cr_category=9;
$cr_subject = str_replace('"','',$cr_subject);
$cr_subject = str_replace("'","",$cr_subject);
$cr_subject = str_replace("ㅤ","",$cr_subject);
$cr_subject = str_replace("  "," ",$cr_subject);
$cr_subject = trim($cr_subject);
$cr_name = str_replace('"','',$cr_name);
$cr_name = str_replace("'","",$cr_name);
$cr_name = str_replace("ㅤ","",$cr_name);
$cr_name = str_replace("  "," ",$cr_name);
$cr_name = trim($cr_name);
if($cr_month<10) $cr_month="0".$cr_month*1;
if($cr_day<10) $cr_day="0".$cr_day*1;
$r_date=$cr_year."".$cr_month."".$cr_day;

if($cr_no>0) { //수정
	$modify_que="update schedule set r_date='$r_date', from_time='10', time_length='1', subject='$cr_subject', url='', place='', member_no='$cr_member_no', category='$cr_category', reg_date='$update_date' where no='$cr_no'";
	mysql_query("$modify_que") or error(mysql_error());
} else {
	if($cr_subject) {
		$insert_que="insert into schedule (r_date,from_time,time_length,subject,url,place,member_no,category,reg_date) values ('$r_date','10','1','$cr_subject','','','$cr_member_no','$cr_category','$update_date')";
		mysql_query("$insert_que") or error(mysql_error());
	}
}

@mysql_close($connect);
?>
<SCRIPT LANGUAGE="JavaScript">
this.location="work_schedule.php?mid=<?=$cr_member_no?>&nowyear=<?=$nowyear?>&nowmonth=<?=$nowmonth?>";
</SCRIPT>