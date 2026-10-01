<?
require_once "lib.php";
if(!$connect) $connect=dbConn();

$no = isset($_REQUEST["no"]) ? intval($_REQUEST["no"]) : 0;
$page = isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 1;
$mid = isset($_REQUEST["mid"]) ? mysql_real_escape_string($_REQUEST["mid"], $connect) : "";
$del = isset($_REQUEST["del"]) ? intval($_REQUEST["del"]) : 0;
$reg_date=time();
$today=date("Y-m-d", $reg_date);
$date=$today;

if($del==1) {
	$delete_que="delete from `work_list` where no=$no";
	mysql_query("$delete_que") or error(mysql_error());
}

if(!$no||$del==1) {
	$no=0;
	$start_time="10";
	$start_minute="00";
	$end_time="17";
	$end_minute="00";
}
$data=mysql_fetch_array(mysql_query("SELECT mb_name, mb_level FROM `g4_member` where mb_id='".$mid."'"));
$w_name=$data[mb_name];
$mb_level=$data[mb_level];
//echo $data[mb_name];

if($no) {
	$temp=mysql_fetch_array(mysql_query("SELECT * FROM `work_list` where no='".$no."'"));
	$w_name=$temp[w_name];
	$content=$temp[content];
	$content=str_replace("<br />","\r\n",$content);
	$user_num=$temp[user_num];
	$date=$temp[date];
	$start_time=$temp[start_time];
	$start_minute=$temp[start_minute];
	$end_time=$temp[end_time];
	$end_minute=$temp[end_minute];
}


?>
<form name='set_work_list' id="set_work_list" method='POST' action='/reports/work_list_report_ok.php' target='hiddenframe'>
<input type='hidden' name='no' id='no' value='<?=$no?>'>
<input type='hidden' name='page' id='page' value='<?=$page?>'>
도서관지기 <input type='text' id='w_name' name='w_name' size='7' style='margin:0 0 10px 0' value='<?=$w_name?>' readonly><br>
날짜 <input type='text' id='date' name='date' size='8' style='margin:0 0 10px 34px' value='<?=$date?>'><br>
업무시간 
<select name="start_time" id="start_time" style='margin:0 0 10px 10px' >
  <option value="09">09</option>
  <option value="10" selected>10</option>
  <option value="11">11</option>
  <option value="12">12</option>
  <option value="13">13</option>
  <option value="14">14</option>
  <option value="15">15</option>
  <option value="16">16</option>
  <option value="17">17</option>
  <option value="18">18</option>
  <option value="19">19</option>
</select>
<select name="start_minute" id="start_minute">
  <option value="00" selected>00</option>
  <option value="10">10</option>
  <option value="20">20</option>
  <option value="30">30</option>
  <option value="40">40</option>
  <option value="50">50</option>
</select>
~
<select name="end_time" id="end_time">
  <option value="09">09</option>
  <option value="10">10</option>
  <option value="11">11</option>
  <option value="12">12</option>
  <option value="13">13</option>
  <option value="14">14</option>
  <option value="15">15</option>
  <option value="16">16</option>
  <option value="17" selected>17</option>
  <option value="18">18</option>
  <option value="18">19</option>
</select>
<select name="end_minute" id="end_minute">
  <option value="00" selected>00</option>
  <option value="10">10</option>
  <option value="20">20</option>
  <option value="30">30</option>
  <option value="40">40</option>
  <option value="50">50</option>
</select>
<?
if($no) {
?>
<script>
$('#start_time').val('<?=$start_time?>').prop("selected",true);
$('#start_minute').val('<?=$start_minute?>').prop("selected",true);
$('#end_time').val('<?=$end_time?>').prop("selected",true);
$('#end_minute').val('<?=$end_minute?>').prop("selected",true);
</script>
<?
}
?>
<br>
이용자수 <input type='text' id='user_num' name='user_num' size='3' value='<?=$user_num?>' style='margin:0 0 10px 10px'> 명<br>
특기사항<br><textarea name="content" cols="80" rows="5" style='margin:0 0 10px 60px'><?=$content?></textarea><br>
<!--input type='submit' value='입력' class='button' style='margin:0 0 0 60px'-->
<button type="button" id="input_ok_bt" style="margin-left:60px">등록</button>
<br>
<!--span style='color:#c80000'>*아직 프로그래밍중입니다.</span-->
</form>

<?
// 30일 기준일자 계산 (오늘 포함 최근 30일)
$date_30_ago = date("Y-m-d", strtotime("-30 days", $reg_date));

// 과거 내역 전체 개수 조회
$count_res = mysql_query("SELECT COUNT(*) FROM `work_list` WHERE date < '{$date_30_ago}'");
$count_row = $count_res ? mysql_fetch_row($count_res) : array(0);
$past_count = $count_row ? (int)$count_row[0] : 0;

$page_size = 20; // 과거 내역 페이지당 출력 건수
$past_pages = ($past_count > 0) ? (int)ceil($past_count / $page_size) : 0;
$total_pages = 1 + $past_pages;

if ($page < 1) $page = 1;
if ($page > $total_pages) $page = $total_pages;

if ($page == 1) {
	// 1페이지: 최근 30일 이내 작성분
	$list_que = "SELECT * FROM `work_list` WHERE date >= '{$date_30_ago}' ORDER BY date DESC, no DESC";
} else {
	// 2페이지 이후: 30일 이전의 과거 내역 페이징
	$offset = ($page - 2) * $page_size;
	$list_que = "SELECT * FROM `work_list` WHERE date < '{$date_30_ago}' ORDER BY date DESC, no DESC LIMIT {$offset}, {$page_size}";
}
$result = mysql_query($list_que);
$list_num_rows = $result ? mysql_num_rows($result) : 0;
?>

<div style="margin-top:20px; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
	<div style="font-weight:bold; font-size:13px; color:#333;">
		<? if ($page == 1) { ?>
			<span style="color:#2a6496;">■ 최근 30일 업무일지</span> 
			<span style="font-weight:normal; font-size:12px; color:#666;">(<?=$date_30_ago?> 이후 등록분 &middot; 총 <?=$list_num_rows?>건)</span>
		<? } else { ?>
			<span style="color:#555;">■ 이전 업무일지</span> 
			<span style="font-weight:normal; font-size:12px; color:#666;">(<?=$date_30_ago?> 이전 내역 &middot; <?=$page?> / <?=$total_pages?> 페이지)</span>
		<? } ?>
	</div>
	<div style="font-size:12px; color:#777;">
		전체 <?=$total_pages?>페이지 (과거 내역 <?=$past_count?>건)
	</div>
</div>

<table style="width:100%; border-collapse:collapse;" border="0">
<colgroup width=75>
<colgroup width=85>
<colgroup width=70>
<colgroup width=60>
<colgroup width=260>
<colgroup width=75>
<colgroup width=65>
<tr style="background-color:#cccccc; height:28px;">
<td style="text-align:center">날짜</td><td style="text-align:center">시간</td><td style="text-align:center">이름</td><td style="text-align:center">이용자수</td><td style="text-align:center">특기사항</td><td style="text-align:center">관리</td><td style="text-align:center">관장확인</td>
</tr>
<?
if ($list_num_rows > 0) {
	while($data = @mysql_fetch_array($result)) {
		echo "<tr style='border-bottom:1px solid #ddd; height:28px;'>";
		echo "<td style='text-align:center;'>$data[date]</td>";
		echo "<td style='text-align:center;'>$data[start_time]:$data[start_minute] ~ $data[end_time]:$data[end_minute]</td>";
		echo "<td style='text-align:center;'>$data[w_name]</td>";
		echo "<td style='text-align:right; padding-right:22px;'>$data[user_num]</td>";
		echo "<td style='padding:4px 6px;'>$data[content]</td>";
		echo "<td style='text-align:center;'>";
		if($data['w_name'] == $w_name || $mb_level >= 10) {
			echo "<a href='/admin/work_list.php?no=$data[no]&page=$page'>[수정]</a> ";
			echo "<a href='/admin/work_list.php?del=1&no=$data[no]&page=$page' onclick='return confirm(\"정말 삭제하시겠습니까?\");'>[삭제]</a>";
		}
		echo "</td>";
		echo "<td style='text-align:center;'>";
		if($mb_level >= 10) {
			$is_checked = ($data['confirm'] == '1') ? 'checked' : '';
			echo "<input type='checkbox' onchange='checkBox(this)' id='confirm_$data[no]' name='confirm' value='$data[no]' $is_checked />";
		} else {
			echo ($data['confirm'] == '1') ? '확인' : '미확인';
		}
		echo "</td>";
		echo "</tr>";
	}
} else {
	echo "<tr><td colspan='7' style='text-align:center; padding:30px; color:#888;'>등록된 업무일지가 없습니다.</td></tr>";
}
echo "</table>";

// 페이징 네비게이션 출력
if ($total_pages > 1) {
	echo "<div style='text-align:center; margin:20px 0 10px 0; font-size:12px;'>";
	
	// [처음]
	if ($page > 1) {
		echo "<a href='/admin/work_list.php?page=1' style='display:inline-block; padding:3px 7px; margin:0 2px; border:1px solid #ccc; text-decoration:none; color:#333; background:#fff; border-radius:3px;'>&laquo; 처음</a> ";
	}
	
	// [이전 10페이지]
	$page_block = 10;
	$start_p = (int)(($page - 1) / $page_block) * $page_block + 1;
	$end_p = min($start_p + $page_block - 1, $total_pages);
	
	if ($start_p > 1) {
		$prev_p = $start_p - 1;
		echo "<a href='/admin/work_list.php?page={$prev_p}' style='display:inline-block; padding:3px 7px; margin:0 2px; border:1px solid #ccc; text-decoration:none; color:#333; background:#fff; border-radius:3px;'>&lsaquo; 이전</a> ";
	}
	
	// 페이지 번호들
	for ($p = $start_p; $p <= $end_p; $p++) {
		if ($p == $page) {
			echo "<span style='display:inline-block; padding:3px 8px; margin:0 2px; border:1px solid #2a6496; font-weight:bold; background:#2a6496; color:#fff; border-radius:3px;'>{$p}</span> ";
		} else {
			echo "<a href='/admin/work_list.php?page={$p}' style='display:inline-block; padding:3px 8px; margin:0 2px; border:1px solid #ccc; text-decoration:none; color:#333; background:#fff; border-radius:3px;'>{$p}</a> ";
		}
	}
	
	// [다음 10페이지]
	if ($end_p < $total_pages) {
		$next_p = $end_p + 1;
		echo "<a href='/admin/work_list.php?page={$next_p}' style='display:inline-block; padding:3px 7px; margin:0 2px; border:1px solid #ccc; text-decoration:none; color:#333; background:#fff; border-radius:3px;'>다음 &rsaquo;</a> ";
	}
	
	// [맨끝]
	if ($page < $total_pages) {
		echo "<a href='/admin/work_list.php?page={$total_pages}' style='display:inline-block; padding:3px 7px; margin:0 2px; border:1px solid #ccc; text-decoration:none; color:#333; background:#fff; border-radius:3px;'>맨끝 &raquo;</a> ";
	}
	
	echo "</div>";
}

mysql_close($connect);
?>
<script>
$('#input_ok_bt').click(function(){
	if($("#user_num").val()!="") {
		$( "#set_work_list" ).submit();
	} else {
		alert("이용자수를 입력해 주십시요.");
	}
});

function checkBox(checked){
	var url="/reports/work_list_report_confirm.php?no="+checked.getAttribute('value');
	if( checked.checked==true ){
		url+="&confirm=1";
	} else {
		url+="&confirm=0";
	}
	$('#hiddenframe').attr('src', url);
}



</script>
<iframe width=500 height=400 name='hiddenframe' id='hiddenframe' style='display:none;'></iframe>
