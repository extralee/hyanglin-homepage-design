<?php
require_once("./_common.php");

$tab = "setting";
$nav = "work_schedule";

if (!has_authority($tab, $nav)) {
    alert("권한이 없습니다. 비정상 접근!", $g4[path]);      
}

require_once("{$g4['path']}/_head.php");

$dbui->view_head("도서관지기 일정");
?>

<table cellspacing="5" cellpadding="5" class="dbui_list">
<p><iframe id="content_iframe" src="/reports/work_schedule.php?mid=<?=$member[mb_id]?>" style="border: 0px currentcolor; width: 100%; height: 928px;"></iframe></p>



<tr><td>
<?
//$url="http://www.ahn-library.org/reports/work_schedule.php?mid=".$member[mb_id];
//$jdata = json_decode(file_get_contents($url), true);
//echo "+++".$jdata['b']."+++";
//$jdata = file_get_contents($url);
//echo $jdata;
?>
</td></tr> 
</table>
<br>
<?php require_once("{$g4['path']}/_tail.php"); ?>
