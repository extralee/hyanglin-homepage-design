<?
require_once "lib.php";
if(!$connect) $connect=dbConn();

$no = isset($_REQUEST["no"]) ? intval($_REQUEST["no"]) : 0;
$w_name = isset($_REQUEST["w_name"]) ? mysql_real_escape_string($_REQUEST["w_name"], $connect) : "";
$user_num = isset($_REQUEST["user_num"]) ? intval($_REQUEST["user_num"]) : 0;
$date = isset($_REQUEST["date"]) ? mysql_real_escape_string($_REQUEST["date"], $connect) : "";
$start_time = isset($_REQUEST["start_time"]) ? mysql_real_escape_string($_REQUEST["start_time"], $connect) : "";
$start_minute = isset($_REQUEST["start_minute"]) ? mysql_real_escape_string($_REQUEST["start_minute"], $connect) : "";
$end_time = isset($_REQUEST["end_time"]) ? mysql_real_escape_string($_REQUEST["end_time"], $connect) : "";
$end_minute = isset($_REQUEST["end_minute"]) ? mysql_real_escape_string($_REQUEST["end_minute"], $connect) : "";
$content = isset($_REQUEST["content"]) ? $_REQUEST["content"] : "";
$content = str_replace("\r\n","<br />",$content);
$content = mysql_real_escape_string($content, $connect);

$reg_date=time(); // 현재의 시간구함;;
$update_date=date("YmdHis", $reg_date);

$content=str_replace("\r\n","<br />",$content);
$content=addslashes($content);

if($no>0) { //수정
	$modify_que="update work_list set w_name='$w_name', confirm='0', content='$content', user_num='$user_num', date='$date', start_time='$start_time', start_minute='$start_minute', end_time='$end_time', end_minute='$end_minute', reg_date='$update_date' where no='$no'";
	mysql_query("$modify_que") or error(mysql_error());
} else {
	$insert_que="insert into work_list (w_name,confirm,content,user_num,date,start_time,start_minute,end_time,end_minute,reg_date) values ('$w_name','0','$content','$user_num','$date','$start_time','$start_minute','$end_time','$end_minute','$update_date')";
	//echo $insert_que;
	mysql_query("$insert_que") or error(mysql_error());
}

//echo "777";
@mysql_close($connect);
?>
<SCRIPT LANGUAGE="JavaScript">
parent.location.reload(true);
</SCRIPT>