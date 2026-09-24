<?
require_once "lib.php";
if(!$connect) $connect=dbConn();

$member_no = isset($_REQUEST["member_no"]) ? mysql_real_escape_string($_REQUEST["member_no"], $connect) : "";
if(!$member_no && isset($_REQUEST["mid"])) $member_no = mysql_real_escape_string($_REQUEST["mid"], $connect);

header("Content-Type:application/json");

$temp=mysql_fetch_array(mysql_query("select mb_name, mb_level from `g4_member` where mb_id='$member_no'"));
$mb_level=$temp[1];
if($mb_level>7) $is_admin=1; else $is_admin=0;

$jdata = [$is_admin];
echo json_encode($jdata);

mysql_close($connect);
?>
