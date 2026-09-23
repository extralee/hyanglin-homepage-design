<?php
require_once("./_common.php");

$tab = "setting";
$nav = "work_list";

if (!has_authority($tab, $nav)) {
    alert("권한이 없습니다. 비정상 접근!", $g4[path]);      
}

require_once("{$g4['path']}/_head.php");

$dbui->view_head("업무일지");
?>

<table cellspacing="5" cellpadding="5" class="dbui_list">
<tr><td>
<?
$url="http://127.0.0.1/reports/work_list_report.php?mid=".$member[mb_id]."&no=".$no."&del=".$del;
//$jdata = json_decode(file_get_contents($url), true);
//echo "+++".$jdata['b']."+++";
$jdata = file_get_contents($url);
echo $jdata;
?>
</td></tr> 
</table>
<br>
<?php require_once("{$g4['path']}/_tail.php"); ?>
