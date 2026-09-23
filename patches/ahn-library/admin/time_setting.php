<?php
require_once("./_common.php");

$tab = "setting";
$nav = "time_setting";

if (!has_authority($tab, $nav)) {
    alert("권한이 없습니다. 비정상 접근!", $g4[path]);      
}

require_once("{$g4['path']}/_head.php");

$dbui->view_head("운영시간/전화번호 설정");
?>

<table cellspacing="5" cellpadding="5" class="dbui_list">
<tr><td>
<?
$url="http://127.0.0.1/reports/get_time_setting.php";
//$jdata = json_decode(file_get_contents($url), true);
//echo "+++".$jdata['b']."+++";
$jdata = file_get_contents($url);
echo $jdata;
?>
</td></tr> 
</table>
<br>
<?php require_once("{$g4['path']}/_tail.php"); ?>
