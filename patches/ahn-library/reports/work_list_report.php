<?
require_once "lib.php";
if(!$connect) $connect=dbConn();

$no = isset($_REQUEST["no"]) ? intval($_REQUEST["no"]) : 0;
$page = isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 1;
$mid = isset($_REQUEST["mid"]) ? mysql_real_escape_string($_REQUEST["mid"], $connect) : "";
$del = isset($_REQUEST["del"]) ? intval($_REQUEST["del"]) : 0;
$reg_date=time();
$date=date("Y-m-d", $reg_date);

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

<table style="width:100%; margin-top:15px;">
<colgroup width=65>
<colgroup width=70>
<colgroup width=60>
<colgroup width=55>
<colgroup width=260>
<colgroup width=65>
<colgroup width=52>
<tr style="background-color:#cccccc">
<td style="text-align:center">날짜</td><td style="text-align:center">시간</td><td style="text-align:center">이름</td><td style="text-align:center">이용자수</td><td style="text-align:center">특기사항</td><td></td><td>관장확인</td>
</tr>
<?
$result=mysql_query("SELECT * FROM `work_list` where date<='".$date."' order by date desc limit 0,100");
while($data=@mysql_fetch_array($result)) {
	echo "<tr>";
	echo "<td>$data[date]</td>";
	echo "<td>$data[start_time]:$data[start_minute] ~ $data[end_time]:$data[end_minute]</td>";
	echo "<td style='text-align:center'>$data[w_name]</td>";
	echo "<td style='text-align:right; padding-right:22px;'>$data[user_num]</td>";
	echo "<td>$data[content]</td>";
	echo "<td style='text-align:center'>";
	if($data[w_name]==$w_name) echo "<a href='/admin/work_list.php?no=$data[no]'>[수정]</a> <a href='/admin/work_list.php?del=1&no=$data[no]'>[삭제]</a>";
	echo "</td>";
	echo "<td style='text-align:center'>";
	if($mb_level>=10) {
		if($data[confirm]=='1') $is_checked='checked';
		else $is_checked='';
		echo "<input type='checkbox' onchange='checkBox(this)' id='confirm' name='confirm' value='$data[no]' $is_checked />";
	} else {
		if($data[confirm]=='1') echo '확인';
		else echo '미확인';
	}
	echo "</td>";
	echo "</tr>";
}
echo "</table>";

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
