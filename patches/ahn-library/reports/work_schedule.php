<?
require_once "lib.php";
if(!$connect) $connect=dbConn();

$mid = isset($_REQUEST["mid"]) ? mysql_real_escape_string($_REQUEST["mid"], $connect) : "";
$nowyear = isset($_REQUEST["nowyear"]) ? intval($_REQUEST["nowyear"]) : 0;
$nowmonth = isset($_REQUEST["nowmonth"]) ? intval($_REQUEST["nowmonth"]) : 0;
$go_year = isset($_REQUEST["go_year"]) ? mysql_real_escape_string($_REQUEST["go_year"], $connect) : "";
$go_month = isset($_REQUEST["go_month"]) ? mysql_real_escape_string($_REQUEST["go_month"], $connect) : "";
$cal_view_weeks = isset($_REQUEST["cal_view_weeks"]) ? intval($_REQUEST["cal_view_weeks"]) : 6;

////////////권한 확인
$data=mysql_fetch_array(mysql_query("SELECT mb_name, mb_level FROM `g4_member` where mb_id='".$mid."'"));
$mb_name=$data[0];
///////////////////////////////달력 관련 설정
$reg_date=time();
$real_reg_date=time();
$today=date("Ymd", $reg_date);
$real_nowyear=date("Y", $reg_date);
if(!$nowyear) $t_year=date("Y", $reg_date); else $t_year=$nowyear;
if(!$nowmonth) $t_month=date("m", $reg_date); else $t_month=$nowmonth;
if($t_month=="12"&&$go_month=="next") $t_year++;
if($t_month=="1"&&$go_month=="prev") $t_year--;
$t_date=date("d", $reg_date);
if($go_year=="prev") $t_year--;
if($go_year=="next") $t_year++;
if($go_month=="prev") $t_month--;
if($go_month=="next") $t_month++;
if($t_month==0) $t_month=12;
if($t_month==13) $t_month=1;

$reg_date=mktime(0,0,0,$t_month,$t_date,$t_year);

$nowyear=date("Y", $reg_date);
$nowmonth=date("m", $reg_date);
$nowdate=date("d", $reg_date);
$nowday=date("w", $reg_date);
$start_date=$nowdate-7-$nowday;
$i_start=date("Ymd",mktime(0,0,0,$nowmonth,$start_date,$nowyear));
$cond_start_date=date("Ymd",mktime(0,0,0,$nowmonth-1,1,$nowyear));
$cond_end_date=date("Ymd",mktime(0,0,0,$nowmonth+2,1,$nowyear));
if(!$cal_view_weeks) $cal_view_weeks=6; //몇 주까지 표시할 것인지?
?>
<html lang="ko"> 
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>일정표</title>
<link rel=StyleSheet HREF=/reports/color_set.css?<?=$real_reg_date?> type=text/css title=style>
<link rel=StyleSheet HREF=/reports/style.css?<?=$real_reg_date?> type=text/css title=style>
<script src="/reports/jquery.min.js"></script>
<script language="JavaScript">
var obj_x,obj_y;
function FindXY(loc) {
	jQuery(document).ready(function(){
	   $(document).mousemove(function(e){
	      obj_x=e.pageX;
	      obj_y=e.pageY;
	   }); 
	})
}
FindXY();
var is_move=0;
</script>
</head>
<body topmargin='0'  leftmargin='0' marginwidth='0' marginheight='0' bgcolor='#FFFFFF'>
<div id="schedule_main">
<table id=news_head cellspacing=0 cellpadding=5>
<tr>
	<td style="padding:10px 0 5px 16px; color:#000; line-height:160%">
	</td>
</tr>
<tr>
	<td class="cal_search_td">
		<a href="work_schedule.php?mid=<?=$mid?>&go_year=prev&nowyear=<?=$nowyear?>&nowmonth=<?=$nowmonth?>"><span class=cal_year>◀</span></a>
		<span style="font-size:14pt;font-weight:bold;"><?=$nowyear?>년</span>
		<a href="work_schedule.php?mid=<?=$mid?>&go_year=next&nowyear=<?=$nowyear?>&nowmonth=<?=$nowmonth?>"><span class=cal_year>▶</span></a>
		<a href="work_schedule.php?mid=<?=$mid?>&go_month=prev&nowyear=<?=$nowyear?>&nowmonth=<?=$nowmonth?>"><span class=cal_year style='margin:0 0 0 10px;'>◀</span></a>
		<span style="font-size:14pt;font-weight:bold;"><?=$nowmonth?>월</span>
		<a href="work_schedule.php?mid=<?=$mid?>&go_month=next&nowyear=<?=$nowyear?>&nowmonth=<?=$nowmonth?>"><span class=cal_year>▶</span></a>
		<button type="button" id="view_type1" class="buttonElement <? if($cal_view_weeks==6){ ?>buttonElement_on<? } ?>" onclick="javascript:location.href='work_schedule.php?mid=<?=$mid?>&cal_view_weeks=6'" style="margin:0 5px 0 10px">1개월 보기</button>
		<button type="button" id="view_type2" class="buttonElement <? if($cal_view_weeks==54){ ?>buttonElement_on<? } ?>" onclick="javascript:location.href='work_schedule.php?mid=<?=$mid?>&cal_view_weeks=54'" style="margin:0 5px">1년 보기</button>
		<button type="button" id="view_type3" class="buttonElement category_btn" onclick="javascript:category_work()" style="display:none;">시간구분</button>
		<div id="howto">■ <span style="font-weight:bold">'날짜'</span>를 클릭하여 일정을 등록</div>
	</td>
</tr>
</table>

<div id="box_category">
<form method=post action="schedule_category_ok.php" name="category_form" id="category_form" enctype="multipart/form-data">
<input type='hidden' name='cr_member_no' id='cr_member_no' value='<?=$mid?>'>
<input type='hidden' name='cr_member_no2' id='cr_member_no2' value='<?=$mid?>'>
<?
$category_list="<option value='0'>-------</option>";
$que="select * from schedule_category";
$result=mysql_query($que,$connect) or Error(mysql_error());
while($data=@mysql_fetch_array($result)) {
	echo "<div class='category_box'><input type='text' name='category_subject_$data[0]' class='input_box $form_disabled' value='$data[1]' style='width:100px;'></div>
	";
	if($data[1]!="") $category_list.="<option value='$data[0]'>$data[1]</option>";
}
?>
<button type="button" id="category_cancel_bt" class="buttonElement2_on" style="margin:0; padding:2px 7px 0 7px;">닫기</button>
<button type="button" id="category_ok_bt" class="buttonElement2_on" style="margin:0 0 0 5px; padding:2px 7px 0 7px;">등록</button>
</form>
</div>

<div id="box_table">
<table class='input_table' id='box_table2' border=0 cellpadding=0 cellspacing=0>
<form method=post action="schedule_ok.php" name="reserve_form" id="reserve_form" enctype="multipart/form-data">
<input type='hidden' name='cr_no' id='cr_no'>
<input type='hidden' name='cr_member_no' id='cr_member_no' value='<?=$mid?>'>
<input type='hidden' name='cr_member_no2' id='cr_member_no2' value='<?=$mid?>'>
<input type='hidden' name='nowyear' id='nowyear' value='<?=$nowyear?>'>
<input type='hidden' name='nowmonth' id='nowmonth' value='<?=$nowmonth?>'>
<input type='hidden' name='is_admin' id='is_admin'>
<tr>
	<td  class='input_tag'>시간</td>
	<td  class='input_td'>
		<select name='cr_category' id='cr_category' class='input_box' style='width:120px;'>
			<?=$category_list?>
		</select>
	</td>
</tr>
<tr>
	<td  class='input_tag'>이름</td>
	<td  class='input_td'>
		<select name='cr_subject' id='cr_subject' class='input_box' style='width:100px;'>
<?
$que="select mb_name from g4_member where (mb_level=8 or mb_id='00001'  or mb_id='00005' or mb_id='00007') and mb_id!='00006' order by mb_name";
$result=mysql_query($que,$connect) or Error(mysql_error());
while($data=@mysql_fetch_array($result)) {
	echo "<option value='$data[mb_name]'>$data[mb_name]</option>
	";
}
?>
			</select>
<script>
$('#cr_subject').val('<?=$mb_name?>').prop("selected",true);
</script>
		<!--input type='text' name='cr_subject' id='cr_subject' value='<?=$mb_name?>' class='input_box' style='width:80px;'-->
	</td>
	<!--td  class='input_td'><input type='text' name='cr_subject' id='cr_subject' value='<?=$mb_name?>' class='input_box' style='width:293px;' onBlur='javascript:set_subject_ok()' ></td-->
</tr>
<tr>
	<td  class='input_tag'>날짜</td>
	<td  class='input_td'>
		<select name='cr_year' id='cr_year' class='input_box' onchange='reset_dropdown_day();'>
<?
for($i=$real_nowyear;$i<$real_nowyear+3;$i++){
	echo "<option value='$i'>".$i."년</option>";
}
?>
		</select>
		<select name='cr_month' id='cr_month' class='input_box' onchange='reset_dropdown_day();'>
<?
for($i=1;$i<=12;$i++){
	if($i<10) $ii="0".$i; else $ii=$i;
	if($ii==$nowmonth) $selected="selected"; else $selected="";
	echo "<option value='$i' $selected>".$ii."월</option>";
}
?>
		</select>
		<select name='cr_day' id='cr_day' class='input_box'>
<?
$last_day = date("t", mktime(0, 0, 1, $nowmonth, 1, $nowyear));
for($i=1;$i<=$last_day;$i++){
	if($i<10) $ii="0".$i; else $ii=$i;
	if($ii==$nowdate) $selected="selected"; else $selected="";
	echo "<option value='$i' $selected>".$ii."일</option>";
}
?>
		</select>
	</td>
</tr>
<tr>
	<td  class='input_tag'></td>
	<td  class='input_td input_td_bottom'>
		<button type="button" id="place_reserve_cancel_bt" class="buttonElement2_on">창닫기</button>
		<button type="button" id="place_reserve_delete_bt" class="buttonElement2_on" style="display:none">일정삭제</button>
		<button type="button" id="place_reserve_ok_bt" class="buttonElement2_on">등록</button>
	</td>
</tr>
</table>
</form>
</td></tr></table>
</div>

<table id="newstable_id" class="newstable" border="0" cellpadding="0" cellspacing="0" style="width:100%;">
<tr>
	<td class="newsweek">SUN</td><td class="newsweek">MON</td><td class="newsweek">TUE</td><td class="newsweek">WED</td><td class="newsweek">THU</td><td class="newsweek">FRI</td><td class="newsweek">SAT</td>
</tr>
<?
$same_date_num=10; //같은 날 예약 표시수
$que="select * from schedule where r_date>$cond_start_date and r_date<$cond_end_date order by r_date, category";
//echo $que;
$result=mysql_query($que,$connect) or Error(mysql_error());
$cal_i=1;
while($data=@mysql_fetch_array($result)) {
	if($data[r_date]==$r_date) $cal_i++; else $cal_i=1;
	${'no'.$data[r_date]."-".$cal_i}=$data[no];
	${'member_no'.$data[r_date]."-".$cal_i}=$data[member_no];
	${'r_date'.$data[r_date]."-".$cal_i}=$data[r_date];
	${'from_time'.$data[r_date]."-".$cal_i}=$data[from_time];
	${'time_length'.$data[r_date]."-".$cal_i}=$data[time_length];
	${'subject'.$data[r_date]."-".$cal_i}=$data[subject];
	${'place'.$data[r_date]."-".$cal_i}=$data[place];
	${'member_no'.$data[r_date]."-".$cal_i}=$data[member_no];
	${'category'.$data[r_date]."-".$cal_i}=$data[category];
	${'url'.$data[r_date]."-".$cal_i}=$data[url];
	${'reg_date'.$data[r_date]."-".$cal_i}=$data[reg_date];
	$r_date=$data[r_date];
}
///////////////////////
$que="select * from schedule_category where subject is not null order by no";
$result=mysql_query($que,$connect) or Error(mysql_error());
while($data2=@mysql_fetch_array($result)) {
	${'work_time_'.$data2[no]}=$data2[subject];
}
//////////////////달력
$iii=0;
$week_i=0;
$week_flag=0;
for($k=1; $k<=$cal_view_weeks; $k++) {
	echo "
	<tr>
	";
	for($i=1; $i<=7; $i++) {
		//$dd=$k*7+$i;
		$yy=date('Y',mktime(0,0,0,$nowmonth,$start_date+$iii,$nowyear));
		$mm=date('m',mktime(0,0,0,$nowmonth,$start_date+$iii,$nowyear));
		$dd=date('d',mktime(0,0,0,$nowmonth,$start_date+$iii,$nowyear));
		$ymd=$yy."".$mm."".$dd;
		$ymd_line=$yy."-".$mm."-".$dd;
		$dd=$dd*1;
		$mm=$mm*1;
		if($i==1) $day_color="day_red";
		elseif($i==7) $day_color="day_blue";
		else $day_color="day_black";
		if($dd==1) {
			$month_view="<td class='news_month_td'>".$mm."월</td>";
			$month_start="news_month_start_td";
			$week_flag=1;
		} else {
			$month_view="";
			$month_start="";
		}
		if($week_flag==1&&$week_i<7) {
			$week_start="news_week_start_td";
			$week_i++;
		} else {
			$week_start="";
			$week_flag=0;
			$week_i=0;
		}
		if($today==$ymd) $today_border="id=cal_today"; else $today_border="";
		echo "
			<td class='cal $month_start $week_start' $today_border>
			<table class='cal_in_table'>
			<tr><td class='cal_in_date'>
			<table class='cal_date_table'><tr><td class='cal_date_td' onclick='is_move=1; set_reserve_init(); get_init_ajax_data(\"$ymd\")'><span class='$day_color'>$mm.$dd</span></td>$month_view</tr></table>
			</td></tr>
		";
		for($ccc=1; $ccc<$same_date_num; $ccc++) {
			$time_no=${'from_time'.$ymd."-".$ccc};
			if($time_no!=0) {
				$time=ceil($time_no/2)+8;
				$minute=(($time_no-1)%2)*30;
				if($minute==0) $minute="00";
				$time.=":".$minute;
			} else $time="";
			$time_to_pre=${'from_time'.$ymd."-".$ccc}+${'time_length'.$ymd."-".$ccc};
			$time_to=floor(($time_to_pre+1)/2)+9;
			if($time_to_pre%2) $time_to.=":00"; else $time_to.=":30";
			$category_tip=${'category'.$ymd."-".$ccc};
			$work_time=${'work_time_'.$category_tip};
			$cont="<span class='time_text'>".$work_time."</span> <span class='name'>".${'subject'.$ymd."-".$ccc}."</span>";
			$link=${'url'.$ymd."-".$ccc};
			if($link=="") $link=0;
			//echo "<tr><td class='cal_in_td my_".${'member_no'.$ymd."-".$ccc}."' onclick='is_move=1; modify_reserve(".${'no'.$ymd."-".$ccc}.",\"$link\")'>$cont</td></tr>";
			if(${'no'.$ymd."-".$ccc}!="") echo "<tr><td class='cal_in_td my_".${'member_no'.$ymd."-".$ccc}."' onclick='is_move=1; modify_reserve(".${'no'.$ymd."-".$ccc}.",\"$link\")'>$cont</td></tr>";
			else $ccc=$same_date_num;
		}
		echo"</table></td>
		";
		$iii++;
	}
	echo "
	</tr>
	";
}
?>

</table>
</div>

<script language="JavaScript">
var pre_from_time=0; //시간 변화에  따라서 설정이 마구 변하지 않게 하기 위해서
//var subject_ok=0; //모임명이 제대로 입력되었는지의 변수
var is_admin=1; //관리자이면 1로 셋팅됨
var click_left=0;
var click_top=0;

$('body').click(function (e) {
	if(is_move==1) {
		click_left=e.pageX;
	    click_top=e.pageY;
		var pPosX=0;
		if(click_left+370>dw) {
			pPosX=dw-370;
		} else {
			pPosX=click_left;
		}
		if(dw<480) pPosX=0;
		var pPosY=0;
		var pHeight=293;
		if(parent.windowWidth<480) pHeight=263;
		if(click_top+pHeight>dh) {
			pPosY=dh-pHeight;
		} else {
			pPosY=click_top;
		}
		$("#box_table").css("top",pPosY);
		$("#box_table").css("left",pPosX);
		is_move=0;
	}
});

$('#category_ok_bt').click(function(){
	$( "#category_form" ).submit();
});

$('#category_cancel_bt').click(function(){
	$( "#box_category" ).css("display","none");
});

function category_work() {
	$( "#box_category" ).css("display","block");
}

$('#place_reserve_ok_bt').click(function(){
	if($("#cr_category").val()!="0") {
		//var t=$("#cr_time_length").val();
		//$("#cr_time_length_real").val(t);
		//alert($("#cr_no").val());
		$( "#reserve_form" ).submit();
	} else {
		alert("시간 설정이 완료되지 않았습니다. 시간을 선택해 주십시요.");
	}
});

$('#place_reserve_cancel_bt').click(function(){
	subject_ok=0;
	//$("#cr_subject").val("");
	$("#cr_from_time").val("0");
	if(is_admin==1) $("#cr_time_length").val("0");
	$("#cr_place").val("0");
	$("#box_table").css("display","none");
	//$("#place_reserve_ok_bt").removeClass("buttonElement2_on").addClass("buttonElement2");
});

function set_reserve_init() {
		subject_ok=0;
		$("#cr_category").val("0");
		//$("#cr_subject").val("");
		$("#cr_from_time").val("0");
		if(is_admin==1) $("#cr_time_length").val("0");
		$("#cr_place").val("");
		$("#cr_url").val("");
}

$('#place_reserve_delete_bt').click(function(){
	var r = confirm("일정 등록을 삭제하시겠습니까? 삭제하면 등록내용이 삭제됩니다.");
	if (r == true) {
		var n=$("#cr_no").val();
		location="schedule_delete.php?mid=<?=$mid?>&nowyear=<?=$nowyear?>&nowmonth=<?=$nowmonth?>&cr_no="+n;
	} else {
	}
});

function modify_reserve(n,m) {
	if(is_admin==1){
		get_reserved_ajax_data(n);
	} else {
		if(m!="0") {
			var tg="_blank";
			if(m.substring(0, 4)!="http") {
				m="http://"+$(location).attr('host')+"/"+m;
				//tg="_top";
			} else {
				var imsi = m.split('//');
				var dm2 = imsi[1].split('/');
				if(dm2[0]==$(location).attr('host')) {
					//tg="_top";
				}
			}
			window.open(m,tg);
		} else get_reserved_ajax_data(n);
	}
}

function admin_link_open() {
	var url=$("#cr_url").val();
	if(url!="") {
		var tg="_blank";
		if(url.substring(0, 4)!="http") {
			url="http://"+$(location).attr('host')+"/"+url;
			tg="_top";
		} else {
			var imsi = url.split('//');
			var dm2 = imsi[1].split('/');
			if(dm2[0]==$(location).attr('host')) {
				tg="_top";
			}
		}
		window.open(url,tg);
	}
}

function set_subject_ok() {
	if($("#cr_subject").val()!="") subject_ok=1;
	else subject_ok=0;
	//if(subject_ok!=0) $("#place_reserve_ok_bt").removeClass("buttonElement2").addClass("buttonElement2_on");
	//else $("#place_reserve_ok_bt").removeClass("buttonElement2_on").addClass("buttonElement2");
}

function reset_dropdown_day(){
	var tYear=$("#cr_year").val();
	var tMonth=$("#cr_month").val();
	var lastDay = ( new Date( tYear, tMonth, 0) ).getDate();
	var sText="";
	for(var i=1;i<=lastDay;i++){
		var ii=i;
		if(i<10) ii="0"+i;
		sText+="<option value='"+i+"'>"+ii+"일</option>"
	}
	$("#cr_day").html(sText);
}

function get_init_ajax_data(d,n) {
	var mm=$("#cr_month").val();
	if(mm<10) mm="0"+mm;
	var dd=$("#cr_day").val();
	if(dd<10) dd="0"+dd;
	var today=$("#cr_year").val()+""+mm+""+dd;
	var member_srl=$("#cr_member_no").val();
	if(d>0) today=d;
	if(member_srl>0) {
		if(n!=1) {
			$("#box_table").css("display","block");
		}
		$.ajax({
			url:"./schedule_get_user.php",
			type: "POST", 
			data: {member_no:member_srl, r_date:today},
			dataType:'json',
			success:function(data){
				$("#cr_no").val("");
				$("#is_admin").val(data[0]);
				$("#cr_year").val(parseInt(today.substring(0,4)));
				$("#cr_month").val(parseInt(today.substring(4,6)));
				$("#cr_day").val(parseInt(today.substring(6,8)));
				//$("#cr_member_no").val(parent.member_srl);
				//$("#cr_member_no2").val(parent.member_srl);
				$("#time_insert").html("<select name='cr_time_length' id='cr_time_length' class='input_box' style='margin:0 3px 0 18px;'><option value='0'>----</option><option value='1'>30분</option><option value='2'>1시간</option><option value='3'>1시간 30분</option><option value='4'>2시간</option><option value='5'>2시간 30분</option><option value='6'>3시간</option><option value='7'>4시간</option><option value='8'>5시간</option><option value='9'>6시간</option><option value='10'>종일</option></select>동안");
				if(data[0]=="1" ) { //관리자이면
					is_admin=1;
					$(".cal_in_td").css("cursor","pointer");
					$("#view_type3").css("display","inline-block");
				} else {
					if(n==1) $("#cr_category").html("<?=$category_list?>");
					else $("#cr_category").html("<option value='9'>개인</option>");
				}
				$("#place_reserve_ok_bt").text("등록");
				//$("#place_reserve_ok_bt").removeClass("buttonElement2_on").addClass("buttonElement2");
				$("#place_reserve_ok_bt").css("display","inline-block");
				subject_ok=0;
				//$("#cr_subject").val("");
				$("#place_reserve_delete_bt").css("display","none");
			}
		})
	} else {
		if(n!=1) $(".login_widget",parent.document).show();
		return false;
	}
}

function get_reserved_ajax_data(n) {
	var member_srl=$("#cr_member_no").val();
	$("#cr_no").val(n);
	$.ajax({
		url:"./schedule_get_data.php",
		type: "POST",   
		data: {cr_no:n, m_no:member_srl},
		dataType:'json',
		success:function(data){
			//alert(data);
			$("#cr_url").val(data[1]);
			//$("#cr_member_no").val(data[2]);
			//$("#cr_member_no2").val(data[2]);
			$("#cr_subject").val(data[5]);
			$("#cr_year").val(data[6]);
			$("#cr_month").val(data[7]);
			$("#cr_day").val(data[8]);
			$("#cr_from_time").val(data[9]);
			$("#cr_time_length").val(data[10]);
			$("#cr_place").val(data[11]);
			subject_ok=1;
			if($("#is_admin").val()=="1"||data[13]=="1") {
				$("#place_reserve_delete_bt").css("display","inline");
				//$("#place_reserve_ok_bt").removeClass("buttonElement2").addClass("buttonElement2_on");
				$("#place_reserve_ok_bt").text("수정완료");
			} else{
				if($("#cr_no").val()>0) $("#place_reserve_ok_bt").css("display","none");
				if(data[2]==$("#cr_member_no2").val()) {
					$("#cr_category").html("<option value='9'>개인</option>");
					$("#place_reserve_delete_bt").css("display","inline");
					//$("#place_reserve_ok_bt").removeClass("buttonElement2").addClass("buttonElement2_on");
					$("#place_reserve_ok_bt").text("수정완료");
					$("#place_reserve_ok_bt").css("display","inline");
				} else {
					$("#cr_category").html("<?=$category_list?>");
					$("#place_reserve_delete_bt").css("display","none");
				}
			}
			$("#cr_category").val(data[12]);
			$("#box_table").css("display","block");
		}
	})
}

get_init_ajax_data(0,1); //처음에 데이터를 가져옴
//자기가 등록한 것은 색깔로 표시
$(".my_"+$("#cr_member_no").val()).css("display", "block");
$(".my_0").css("display", "block");
$(".my_"+$("#cr_member_no").val()).addClass("myReserve");

$('#content_iframe',parent.document.body).load(function() {
	var h=$("#schedule_main").height()+300;
	$(this).parent().parent().height(h);
	$(this).height(h);
	$("#schedule_main").height(h);
});
var dw = 0;
var dh = 0;
$(document).ready(function(){
	dw = $(document).width();
	dh = $("#schedule_main").height()+300;
});
</script>

</body></html>



<?
	@mysql_close($connect);
?>