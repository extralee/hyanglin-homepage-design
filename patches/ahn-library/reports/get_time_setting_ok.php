<?
require_once "lib.php";
if(!$connect) $connect=dbConn();

$time_set = isset($_REQUEST["time_set"]) ? mysql_real_escape_string($_REQUEST["time_set"], $connect) : "";
$phone_set = isset($_REQUEST["phone_set"]) ? mysql_real_escape_string($_REQUEST["phone_set"], $connect) : "";

mysql_query("update  `g4_config` set cf_1='".$time_set."', cf_2='".$phone_set."'  where cf_title='안병무도서관'") or error(mysql_error());

mysql_close($connect);

echo "
<script>
alert('설정이 완료되었습니다.');
</script>
";

?>