<?
extract($_REQUEST);
require "lib.php";
if(!$connect) $connect=dbConn();

///////////////////////////////달력 관련 설정
$reg_date=time();
$real_reg_date=time();
$today=date("Ymd", $reg_date);
$real_nowyear=date("Y", $reg_date);
$post_nowyear=$real_nowyear+5;
if(!$nowyear) $t_year=date("Y", $reg_date); else $t_year=$nowyear;
if(!$nowmonth) $t_month=date("m", $reg_date); else $t_month=$nowmonth;
//$t_month=date("m", $reg_date);
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

$what_language="ko"; // 기본 언어설정: ko 고정 (en 모드 비활성화)
?>
<html lang="ko">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Movies</title>
<link rel=StyleSheet HREF=style.css?<?=$real_reg_date?> type=text/css title=style>
<script src="/home/common/js/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="/home/common/css/jquery-ui.css?<?=$real_reg_date?>">
<script src="/home/common/js/jquery-ui.min.js"></script>
<script src="member.js"></script>
<script language="JavaScript">
var obj_x,obj_y;
function FindXY(loc) {
	obj_x = (document.layers) ? loc.pageX : event.clientX;
	obj_y = (document.layers) ? loc.pageY : event.clientY;
	obj_y += document.body.scrollTop;
}

var what_language="ko";
var is_mobile=0; //모바일이면 1로 셋팅

/*
콘텐츠 크기에 따라서 iframe의 크기를 조정하는 코드. 중요함!
window.onload = function(){
	var t=$('#content_iframe',parent.document.body);
	var h=t.contents().find('body')[0].scrollHeight+10;
	var hh=t.contents().find('body')[0].scrollTop+10;
	t.parent().parent().parent().height(h);
	t.parent().height(h);
}
*/

function window_height_set() {
	var t=$('#content_iframe',parent.document.body);
	t.parent().parent().parent().height(5120);
	t.parent().height(5120);
}

var now_year=new Date().getFullYear();
$(function() {
	$("#slider-range").slider({
		range: true,
		min: 1900,
		max: now_year+10,
		values: [1910, now_year],
		slide: function(event, ui) {
			$("#amount").val(ui.values[0] + " - " + ui.values[1]);
		}
	});
	$("#amount").val($("#slider-range").slider("values", 0) + " - " + $("#slider-range").slider("values", 1));

	$("#slider-range2").slider({
		range: true,
		min: 0,
		max: 100,
		values: [60, 100],
		slide: function(event, ui) {
			$("#amount2").val(ui.values[0] + " - " + ui.values[1]);
		}
	});
	$("#amount2").val($("#slider-range2").slider("values", 0) + " - " + $("#slider-range2").slider("values", 1));
});


function scroll_expand() {
	var t=$('#content_iframe',parent.document.body);
	var h=t.contents().find('body')[0].scrollHeight+10;
	//var hh=t.contents().find('body')[0].scrollTop+10;
	//t.parent().parent().parent().height(1000);
	//t.parent().height(1000);
	t.parent().parent().parent().height(h);
	t.parent().height(h);
	is_more_get=1;
}
window.onload = function(){
	scroll_expand();
}

var lang_01="[No Select]";
if(what_language=="ko") lang_01="[선택안함]";
var country_html="<select id='country' name='country' class='en-hide' style='width:200px'><option value='NST' data-sub='9'>"+lang_01+"</option>";
<?
$language_tip="_eng";
if($what_language=="ko") $language_tip="";

$select_que="SELECT country_code, country_name".$language_tip.", region_code FROM country where no!=0 order by 2";
$result=mysql_query($select_que);
$country_list="";
while($data=@mysql_fetch_array($result)) {
	if($data[0]!="NST") $country_list.="<option value='$data[0]' data-sub='$data[2]'>$data[1]</option>";
}
?>
country_html+="<?=$country_list?></select>";
</script>
</head>
<body style="background-color:#fff">

<div id="search_pop">
[영화 검색] <input type='text' name='search_keyword' id='search_keyword' class='input_box' style='margin-left:10px; width:180px;' onkeyup='search_ok()' >
<button type="button" id="search_ok_bt" onclick="search_ok2()" class="buttonElement2_on">검색</button>
<button type="button" id="search_cancel_bt" onclick="search_cencel()" class="buttonElement2_on">취소</button>
<ul id="ul_list">
<ul>
</div>

<div id="movie_pop">
<iframe id="movie_pop_iframe" src="null.php" style="border: 0px currentcolor; "></iframe>
</div>
<div id="e_movie_pop">
<iframe id="e_movie_pop_iframe" src="null.php" style="border: 0px currentcolor; "></iframe>
</div>

<div class="search_holder">
		<div class="search_box right_line admin_view_line"><button type="button" id="room_reserve_bt" class="buttonElement regist_bt" onclick="set_input_init()">등록</button></div>
		<div class="search_box" style='margin-left:10px;'>검색 <input type='text' name='keyword' id='keyword' class='input_box' style='width:120px;'><button type="button" id="keyword_delete_bt" class="buttonElementx" onclick="delete_keyword()">X</button>
		</div>
		<div class="search_box">지역 <select id="region" name="region" style="width:90px">
<?
if($what_language=="ko") echo "
			<option value='0'>[선택안함]</option>
			<option value='1'>북미+영국+오세아니아</option>
			<option value='2'>스페인+포르투갈+중남미</option>
			<option value='3'>서유럽+남유럽</option>
			<option value='4'>북유럽</option>
			<option value='5'>동유럽</option>
			<option value='6'>동아시아</option>
			<option value='7'>남아시아+동남아시아</option>
			<option value='8'>서아시아</option>
			<option value='9'>아프리카</option>";
else echo "
			<option value='0'>[No Select]</option>
			<option value='1'>English language area</option>
			<option value='2'>Spanish/Portuguese language area</option>
			<option value='3'>Western/Southern Europe</option>
			<option value='4'>Northern Europe</option>
			<option value='5'>Eastern Europe</option>
			<option value='6'>East Asia</option>
			<option value='7'>South/Southeast Asia</option>
			<option value='8'>West Asia</option>
			<option value='9'>Africa</option>";
?>
		</select></div>
		<div id="select_country"><span>국가</span>
			<select id="country" name="country" class="en-hide" style="width:90px; margin-right:5px;">
				<option value='NST' selected><? if($what_language=="ko") echo "[선택안함]"; else echo "[No Select]"; ?></option>
<?
$select_que="SELECT t.country, c.country_name".$language_tip.", count(*) FROM `title` t left join country c on t.country=c.country_code group by t.country, c.country_name".$language_tip." order by 2";
$result=mysql_query($select_que);
while($data=@mysql_fetch_array($result)) {
	if($data[0]!="NST") echo "<option value='".$data[0]."'>".$data[1]."</option>";
}
?>
		</div>
		<div class="search_box" style="margin:16px 0 0 10px;">
		<input type='checkbox' name='select_genre1' id='select_genre1' value='1' style="transform:scale(1.2);" checked><label for="select_genre1" class='input_tag3 en-hide'>드라마</label><label for="select_genre1" class='input_tag3 ko-hide admin-hide'>Dra</label>
		<input type='checkbox' name='select_genre2' id='select_genre2' value='2' style="transform:scale(1.2);" checked><label for="select_genre2" class='input_tag3 en-hide'>다큐</label><label for="select_genre2" class='input_tag3 ko-hide admin-hide'>Doc</label>
		<input type='checkbox' name='select_genre3' id='select_genre3' value='3' style="transform:scale(1.2);" checked><label for="select_genre3" class='input_tag3 en-hide'>실험</label><label for="select_genre3" class='input_tag3 ko-hide admin-hide'>Exp</label><span class="v-line"> | </span>
		<input type='checkbox' name='is_short1' id='is_short1' value='1' style="transform:scale(1.2);" checked><label for="is_short1" class='input_tag3 en-hide'>장편</label><label for="is_short1" class='input_tag3 ko-hide admin-hide'>Long</label>
		<input type='checkbox' name='is_short2' id='is_short2' value='2' style="transform:scale(1.2);" checked><label for="is_short2" class='input_tag3 en-hide'>단편</label><label for="is_short2" class='input_tag3 ko-hide admin-hide'>Short</label><span class="v-line"> | </span>
		<input type='checkbox' name='select_ani' id='select_ani' value='2' style="transform:scale(1.2);"><label for="select_ani" class='input_tag3 en-hide'>애니only</label><label for="select_ani" class='input_tag3 ko-hide admin-hide'>Ani only</label>
		</div>
		<div class="search_box"><select id="select_order" name="select_order" style="width:100px">
			<option value="0">제작연도역순</option>
			<option value="1">제작연도순</option>
			<option value="2">제목순</option>
			<option value="3">제목역순</option>
			<option value="4">감독이름순</option>
			<option value="5">감독이름역순</option>
			<option value="6">평점순</option>
			<option value="7">평점역순</option>
		</select></div>
		<div class="search_box" style="margin:16px 0 0 10px;">
		<input type='checkbox' name='watched1' id='watched1' value='1' style="transform:scale(1.2);" checked><label for="watched1" class='input_tag3 en-hide'>본영화</label><label for="watched1" class='input_tag3 ko-hide admin-hide'>본영화</label>
		<input type='checkbox' name='watched2' id='watched2' value='2' style="transform:scale(1.2);" checked><label for="watched2" class='input_tag3 en-hide'>안본</label><label for="watched2" class='input_tag3 ko-hide admin-hide'>안본</label>
		</div>
		<div class="search_box search_btn">
			<button type="button" id="search_bt" class="buttonElement regist_bt" onclick="chech_keyword()" style="margin-left:2px;">검색</button>
		</div>
</div>
<div class="search_holder2">
	<div>
		<button type="button" id="cacegory_bt" class="buttonElement category_bt" onclick="open_category()">분류<span id="category_arrow">▶</span></button>
		<button type="button" id="work_bt" class="buttonElement category_bt admin_view_line" onclick="open_work()">작업<span id="work_arrow">▶</span></button>
		제작연도 <input type="text" id="amount" readonly><div id="slider-range"></div> 평점 <input type="text" id="amount2" readonly><div id="slider-range2"></div>
	</div>
</div>
<script>
if(parent.member_srl!=4) $(".admin_view_line").css("display","none");
</script>
<table id='input_table' border=0 cellpadding=0 cellspacing=0 width=100% style='display:none;'><tr>
<form method=post action="movies_ok.php" name="reserve_form" id="reserve_form" enctype="multipart/form-data">
<td>
<table class='input_table2' id='box_table' border=0 cellpadding=0 cellspacing=0>
<input type='hidden' name='item_no' id='item_no'>
<input type='hidden' name='eitem_no' id='eitem_no'>
<input type='hidden' name='title_no' id='title_no'>
<input type='hidden' name='etitle_no' id='etitle_no'>
<input type='hidden' name='director_no' id='director_no'>
<input type='hidden' name='edirector_no' id='edirector_no'>
<input type='hidden' name='director_srl' id='director_srl'>
<input type='hidden' name='edirector_srl' id='edirector_srl'>
<input type='hidden' name='dt_no' id='dt_no'>
<input type='hidden' name='edt_no' id='edt_no'>
<input type='hidden' name='member_no' id='member_no'>
<input type='hidden' name='genre_no' id='genre_no' value='1'>
<input type='hidden' name='what_language' id='what_language'>
<input type='hidden' name='this_item_member_no' id='this_item_member_no'>
<input type='hidden' name='region_no' id='region_no' value='0'>
<input type='hidden' name='is_codirector' id='is_codirector' value='0'>
<input type='hidden' name='co_director_no' id='co_director_no' value='0'>
<input type='hidden' name='eng_co_director_no' id='eng_co_director_no' value='0'>
<input type='hidden' name='subtitle_no' id='subtitle_no' value='0'>
<input type='hidden' name='subtitle_type_no' id='subtitle_type_no' value='0'>

<tr style="height:20px;" class="en-hide">
	<td  class='input_tag'>No</td>
	<td  class='input_td en-hide'>
		<input type='text' name='item_no_view' id='item_no_view' class='input_box' style='background-color:#000;color:#fff;width:300px;' disabled>
	</td>
</tr>

<tr style="height:20px;" class="en-hide">
	<td  class='input_tag'>제목</td>
	<td  class='input_td en-hide'>
		<!--input type='text' name='title' id='title' class='input_box' style='width:300px;' onfocus="if($(this).css('color')=='rgb(153, 153, 153)') $(this).val('').css('color','#000')" onblur="if($(this).val()=='') $(this).val('NAVER 영화제목과 동일하게!').css('color','#999')"-->
		<input type='text' name='title' id='title' class='input_box' style='width:300px;'>
	</td>
</tr>
<tr style="height:20px;" class="ko-hide">
	<td  class='input_tag ko-hide'>Title</td>
	<td  class='input_td ko-hide'>
		<!--input type='text' name='etitle' id='etitle' class='input_box' style='width:300px;' onfocus="if($(this).css('color')=='rgb(153, 153, 153)') $(this).val('').css('color','#000')" onblur="if($(this).val()=='') $(this).val('Same with IMDb movie title').css('color','#999')"-->
		<!--input type='text' name='etitle_more' id='etitle_more' class='input_box' style='width:300px;' onfocus="if($(this).css('color')=='rgb(153, 153, 153)') $(this).val('').css('color','#000')"-->
		<input type='text' name='etitle' id='etitle' class='input_box' style='width:300px;'>
		<input type='text' name='etitle_more' id='etitle_more' class='input_box' style='width:300px;'>
	</td>
</tr>
<tr style="height:20px;">
	<td  class='input_tag en-hide'>장르</td>
	<td  class='input_tag ko-hide admin-hide'>Genre</td>
	<td  class='input_td'>
		<input type='radio' name='genre' id='genre1' value='1' style="transform:scale(1.2);" checked><label for="genre1" class='input_tag2 en-hide'>드라마</label><label for="genre1" class='input_tag2 ko-hide admin-hide'>Drama</label>
		<input type='radio' name='genre' id='genre2' value='2' style="transform:scale(1.2);"><label for="genre2" class='input_tag2 en-hide'>다큐</label><label for="genre2" class='input_tag2 ko-hide admin-hide'>Docu</label>
		<input type='radio' name='genre' id='genre3' value='3' style="transform:scale(1.2);"><label for="genre3" class='input_tag2 en-hide'>실험</label><label for="genre3" class='input_tag2 ko-hide admin-hide'>Ex</label>
		<input type='checkbox' name='is_ani' id='is_ani' value='2' style="transform:scale(1.2);"><label for="is_ani" class='input_tag2 en-hide'>애니</label><label for="is_ani" class='input_tag2 ko-hide admin-hide'>Ani</label>
		<input type='checkbox' name='is_short_no' id='is_short_no' value='2' style="transform:scale(1.2);"><label for="is_short_no" class='input_tag2 en-hide'>단편</label><label for="is_short_no" class='input_tag2 ko-hide admin-hide'>Sh</label>
	</td>
</tr>
<tr style="height:20px;">
	<td  class='input_tag en-hide'></td>
	<td  class='input_tag ko-hide admin-hide'></td>
	<td  class='input_td'>
		<input type='checkbox' name='is_TV' id='is_TV' value='2' style="transform:scale(1.2);"><label for="is_TV" class='input_tag2 en-hide'>TV</label><label for="is_TV" class='input_tag2 ko-hide admin-hide'>TV</label>
		<input type='checkbox' name='is_series' id='is_series' value='2' style="transform:scale(1.2);"><label for="is_series" class='input_tag2 en-hide'>시리즈</label><label for="is_series" class='input_tag2 ko-hide admin-hide'>Series</label>
		Episode Num<input type='text' name='episode_num' id='episode_num' class='input_box admin-view'>
	</td>
</tr>
<tr style="height:20px;">
	<td  class='input_tag en-hide'>평점</td>
	<td  class='input_tag ko-hide admin-hide'>Rate</td>
	<td  class='input_td'>
		Metacritic<input type='text' name='metacritic_score' id='metacritic_score' class='input_box admin-view'>
		<input type='checkbox' name='metacritic_score_confirm' id='metacritic_score_confirm' value='2' style="transform:scale(1.2);"><label for="metacritic_score_confirm" class='input_tag2 en-hide'>Cf</label><label for="metacritic_score_confirm" class='input_tag2 ko-hide admin-hide'>Cf</label>
		<span style="margin-left:20px">Rotten</span><input type='text' name='rotten_score' id='rotten_score' class='input_box admin-view'>
		<input type='checkbox' name='rotten_score_confirm' id='rotten_score_confirm' value='2' style="transform:scale(1.2);"><label for="rotten_score_confirm" class='input_tag2 en-hide'>Cf</label><label for="rotten_score_confirm" class='input_tag2 ko-hide admin-hide'>Cf</label>
	</td>
</tr>
<tr>
	<td  class='input_tag en-hide'>연도</td>
	<td  class='input_tag ko-hide admin-hide'>Year</td>
	<td  class='input_td' id='country_frame'><input type='text' name='year' id='year' class='input_box' style='width:50px;'></td>
</tr>
<tr class="en-hide">
	<td  class='input_tag'>감독</td>
	<td  class='input_td en-hide'>
		<!--input type='text' name='director' id='director' class='input_box' style='width:300px;' onChange='set_director_country()' onfocus="if($(this).css('color')=='rgb(153, 153, 153)') $(this).val('').css('color','#000')" onblur="if($(this).val()=='') $(this).val('NAVER 감독명과 동일하게!').css('color','#999')"-->
		<input type='text' name='director' id='director' class='input_box' style='width:280px;' onChange='set_director_country()'>
		<input type='checkbox' name='director_check_ok' id='director_check_ok' value='2' style="transform:scale(1.2);">
		<!--input type='text' name='director_more' id='director_more' class='input_box' style='width:300px;' onfocus="if($(this).css('color')=='rgb(153, 153, 153)') $(this).val('').css('color','#000')" onblur="if($(this).val()=='') $(this).val('필요시 동일 감독의 유사 표기 콤마로 구분 나열').css('color','#999')"-->
		<input type='text' name='director_more' id='director_more' class='input_box' style='width:300px;'>
	</td>
</tr>
<tr class="ko-hide">
	<td  class='input_tag'>Director</td>
	<td  class='input_td'>
		<input type='text' name='edirector' id='edirector' class='input_box' style='width:300px;' onfocus="if($(this).css('color')=='rgb(153, 153, 153)') $(this).val('').css('color','#000')">
	</td>
</tr>
<tr class="en-hide">
	<td  class='input_tag'>포스터</td>
	<td  class='input_td'><input type='text' name='poster' id='poster' class='input_box' style='width:300px;' onfocus="if($(this).css('color')=='rgb(153, 153, 153)') $(this).val('').css('color','#000')"></td>
</tr>
<tr class="ko-hide">
	<td  class='input_tag'>Poster</td>
	<td  class='input_td'><input type='text' name='eposter' id='eposter' class='input_box' style='width:300px;' onfocus="if($(this).css('color')=='rgb(153, 153, 153)') $(this).val('').css('color','#000')"></td>
</tr>
<tr class="en-hide">
	<td  class='input_tag'>링크</td>
	<td  class='input_td'><input type='text' name='link' id='link' class='input_box' style='width:300px;'></td>
</tr>
<tr class="ko-hide">
	<td  class='input_tag'>Link</td>
	<td  class='input_td'><input type='text' name='elink' id='elink' class='input_box' style='width:300px;'></td>
</tr>
<tr>
	<td  class='input_tag'>V-Link</td>
	<td  class='input_td'><input type='text' name='viewlink' id='viewlink' class='input_box' style='width:300px; background-color:#999;'></td>
</tr>
<tr class="en-hide">
	<td  class='input_tag'>감상정보</td>
	<td  class='input_td'><input type='text' name='v_info' id='v_info' class='input_box' style='width:200px;'>
		<select id="resolution_type" name="resolution_type" style="width:95px; height:26px;">
			<option value='0'>[화질선택]</option>
			<option value='1'>2160p</option>
			<option value='2'>1080p</option>
			<option value='3'>720p</option>
			<option value='4'>720p이하</option>
			<option value='5'>[다운 중]</option>
			<option value='6'>[2160다운]</option>
			<option value='7'>[1080다운]</option>
			<option value='8'>[720다운]</option>
		</select>	
	</td>
</tr>
<tr style="height:20px;">
	<td  class='input_tag en-hide'>자막</td>
	<td  class='input_tag ko-hide admin-hide'>Sub</td>
	<td  class='input_td'>
		<input type='radio' name='subtitle' id='subtitle1' value='1' style="transform:scale(1.2);"><label for="subtitle1" class='input_tag2 en-hide'>한</label><label for="subtitle1" class='input_tag2 ko-hide admin-hide'>KO</label>
		<input type='radio' name='subtitle' id='subtitle2' value='2' style="transform:scale(1.2);"><label for="subtitle2" class='input_tag2 en-hide'>영</label><label for="subtitle2" class='input_tag2 ko-hide admin-hide'>EN</label>
		<input type='radio' name='subtitle' id='subtitle3' value='3' style="transform:scale(1.2);"><label for="subtitle3" class='input_tag2 en-hide'>그외</label><label for="subtitle3" class='input_tag2 ko-hide admin-hide'>Etc</label>
		<input type='radio' name='subtitle' id='subtitle4' value='4' style="transform:scale(1.2);"><label for="subtitle4" class='input_tag2 en-hide'>無</label><label for="subtitle4" class='input_tag2 ko-hide admin-hide'>NO</label>
		<input type='radio' name='subtitle' id='subtitle5' value='5' style="transform:scale(1.2);"><label for="subtitle5" class='input_tag2 en-hide'>더빙</label><label for="subtitle5" class='input_tag2 ko-hide admin-hide'>DB</label>
		<input type='radio' name='subtitle' id='subtitle6' value='6' style="transform:scale(1.2);"><label for="subtitle6" class='input_tag2 en-hide'>필요</label><label for="subtitle6" class='input_tag2 ko-hide admin-hide'>Need</label>
		<br />
		<input type='radio' name='subtitle_type' id='subtitle_type1' value='1' style="transform:scale(1.2);"><label for="subtitle_type1" class='input_tag2 en-hide'>분리</label><label for="subtitle_type1" class='input_tag2 ko-hide admin-hide'>Out</label>
		<input type='radio' name='subtitle_type' id='subtitle_type2' value='2' style="transform:scale(1.2);"><label for="subtitle_type2" class='input_tag2 en-hide'>묶임(mkv)</label><label for="subtitle_type2" class='input_tag2 ko-hide admin-hide'>mkv in</label>
		<input type='radio' name='subtitle_type' id='subtitle_type3' value='3' style="transform:scale(1.2);"><label for="subtitle_type3" class='input_tag2 en-hide'>삽입</label><label for="subtitle_type3" class='input_tag2 ko-hide admin-hide'>In</label>
		<br />
		<input type='checkbox' name='subtitle_ok' id='subtitle_ok' value='2' style="transform:scale(1.2);"><label for="subtitle_ok" class='input_tag2 en-hide'>K자동</label><label for="subtitle_ok" class='input_tag2 ko-hide admin-hide'>Auto </label>
		<input type='checkbox' name='subtitle_plus_ok' id='subtitle_plus_ok' value='2' style="transform:scale(1.2);"><label for="subtitle_plus_ok" class='input_tag2 en-hide'>K자동+</label><label for="subtitle_plus_ok" class='input_tag2 ko-hide admin-hide'>Auto +</label>
		<input type='checkbox' name='subtitle_sync' id='subtitle_sync' value='2' style="transform:scale(1.2);"><label for="subtitle_sync" class='input_tag2 en-hide'>자막싱크조정필요</label><label for="subtitle_sync" class='input_tag2 ko-hide admin-hide'>Neet Sync Modify</label>
	</td>
</tr>
<tr class="en-hide" id="list_input">
	<td  class='input_tag'>리스트</td>
	<td  class='input_td'><input type='hidden' name='list_no' id='list_no'><input type='text' name='list' id='list' class='input_box' style='width:214px; margin-right:5px;'><input type='text' name='list_value' id='list_value' class='input_box' style='width:40px;margin-right:5px;'><button type="button" id="list_input_button" class="buttonElement3">OK</button></td>
</tr>
<!--tr class="en-hide">
	<td  class='input_tag'></td>
	<td  class='input_td en-hide'>
		<p class='indent_text'>* 자신이 등록한 영화만 수정할 수 있습니다.</p>
		<p class='indent_text'>* 제목이나 감독을 수정할 경우 삭제 후 재등록합니다.</p>
	</td>
</tr>
<tr class="ko-hide admin-hide">
	<td  class='input_tag'></td>
	<td  class='input_td'>
		<p class='indent_text'>* You can modify the data written by you</p>
		<p class='indent_text'>* For title or director, delete the record and rewrite</p>
	</td>
</tr-->
<tr>
<script>
set_member();
var bt_html='<td  class="input_tag"></td>\
	<td  class="input_td tr_bottom" id="button_box" colspan="2">\
		<!--button type="button" id="cinema_get_bt" class="buttonElement2_on">G</button-->\
		<button type="button" id="room_reserve_cancel_bt" class="buttonElement2_on">취소</button>\
		<button type="button" id="room_reserve_delete_bt" class="buttonElement2_on" style="display:none">삭제</button>\
		<button type="button" id="room_reserve_ok_bt" class="buttonElement2_on">완료</button>';
var e_bt_html='<td  class="input_tag"></td>\
	<td  class="input_td tr_bottom" id="button_box" colspan="2">\
		<!--button type="button" id="cinema_get_bt" class="buttonElement2_on">Get Data</button-->\
		<button type="button" id="room_reserve_cancel_bt" class="buttonElement2_on">Cancel</button>\
		<button type="button" id="room_reserve_delete_bt" class="buttonElement2_on" style="display:none">Delete</button>\
		<button type="button" id="room_reserve_ok_bt" class="buttonElement2_on">Register</button>';
if (what_language=='ko'||member_no==4) {
	document.write(bt_html);
} else {
	document.write(e_bt_html);
}
</script>
</tr>
</table>
</form>
</td></tr></table>

<p id="tooltip_box"></p>
<div id="movies_frame">
</div>
<div id="category_frame"><iframe id="category" name="category" src="category.php" style="width:100%; height:100%; border:0px; overflow-y:scroll; overflow-x:hidden;"></iframe></div>
<div id="work_frame"><iframe id="work" name="work" src="category.php?work=1" style="width:100%; height:100%; border:0px; overflow-y:scroll; overflow-x:hidden;"></iframe></div>

<script language="JavaScript">
var view_where=["NF","WC","DS","AP","NV","GG","WV","TV","UP","SZ","AZ","CP"];
var which_disk=["A","B","D","G","R","T","N","Z"];
// 넷플릭스, 왓차, 디즈니, 애플, 네이버, 구글, 웨이브, 티빙, 유플러스, 시즌, 아마존, 쿠팡
var already_listed_category=[];

if(member_no==4) {
	$(".admin-hide").css("display","none");
	$(".admin-view").css("display","inline-block");
} else {
	if (what_language=='ko') {
		$(".ko-hide").css("display","none");
	} else {
		$(".en-hide").css("display","none");
	}
}
$("#what_language").val(what_language);

/*
function getLanguage() {
	return navigator.language || navigator.userLanguage;
}
*/


if (what_language=='ko'||member_no==4) {
	$("#country_frame").append("<span class='input_tag en-hide' style='margin:0 0 0 18px ;'>국가</span> "+country_html);
	//console.log($("#country_frame > #country").children().length);
	//$("#select_country").append("<span>국가별</span> "+country_html);
	//$("#select_country > #country").css("width","140px").css("display","inline-block");
} else {
	$("#country_frame").append("<span class='input_tag ko-hide admin-hide' style='margin:0 0 0 18px ;'>Country</span> "+country_html);
	//$("#select_country").append("<span>Country</span> "+e_country_html);
	//$("#select_country > #country").css("width","140px").css("display","inline-block");
}	

for(var i=0; i<$("#country_frame > #country").children().length; i++) {
	var key=$('#country_frame select[name=country] option:eq('+i+')').val();
	var c_val=$('#country_frame select[name=country] option:eq('+i+')').html();
	eval("var c_" + key + "=\""+c_val+"\"");
	//console.log("var c_" + key + "=\""+c_val+"\"");
}

$('#list_input_button').click(function(){
	var title_no=$("#title_no").val();
	var list_no=$("#list_no").val();
	var list=$("#list").val();
	var list_value=$("#list_value").val();
	if(list_value=="") list_value=0;
	if(list_no>0) {
		$.ajax({
			url:"./movie_set_list.php",
			type: "POST",   
			data: {title_no:title_no, list_no:list_no, list_value:list_value },
			dataType:'json',
			success:function(data){
				var cc=data.split('@@@@');
				if(list_value==0) list_value="-";
				var list_append_html="<tr id='append_list_"+cc[0]+"' class='appended_list'><td  class='input_tag'></td><td><table class='list_table'><tr class='list_box'><td  class='list_name'>"+list+"</td><td  class='list_value'>"+list_value+"</td><td onclick='javascrip:delete_list("+cc[0]+")' class='input_delete'>[X]</td></tr></table></td></tr>";
				$("#list_input").after(list_append_html);
				var k=$('#category').contents().find('#num_'+cc[1]).html();
				var k_num=parseInt(k)+1;
				$('#category').contents().find('#num_'+cc[1]).html(k_num);

				$("#list_value").val("");
				$("#list_no").val("");
				$("#list").val("");
			}
		})
	}
});

function delete_list(n) {
	var rr = confirm("등록을 삭제하시겠습니까?");
	if (rr == true) {
		//console.log(n);
		$.ajax({
			url:"./movie_list_delete.php",
			type: "POST",   
			data: { list_no:n },
			dataType:'json',
			success:function(data){
				var cc=data.split('@@@@');
				$("#append_list_"+cc[0]).remove();
				//console.log(data);
				var k=$('#category').contents().find('#num_'+cc[1]).html();
				var k_num=parseInt(k)-1;
				$('#category').contents().find('#num_'+cc[1]).html(k_num);
			}
		})
	}
}

$('#room_reserve_ok_bt').click(function(){
	if($("#title").val()=="") alert("영화 제목을 입력하세요.");
	else if($("#etitle").val()=="") alert("영화 원제목을 입력하세요.");
	else if($("#year").val()=="") alert("영화 제작연도를 입력하세요.");
	else if($("#year").val()<"1880") alert("영화 제작연도가 부적합합니다.");
	else if($("#metacritic_score").val()==""||$("#metacritic_score").val()==0) alert("영화 평점을 입력하세요.");
	else if($("#country_frame > #country").val()=="NST"&&$("#is_codirector").val()==0) alert("국가를 선택하세요.");
	else if($("#director").val()=="") alert("감독의 이름을 입력하세요.");
	else {
		if($("#title").val()!=""&&$("#year").val()!=""&&$("#director").val()!="") {
			var item_no=$("#item_no").val();
			var eitem_no=$("#eitem_no").val();
			var title_no=$("#title_no").val();
			var etitle_no=$("#etitle_no").val();
			var director_no=$("#director_no").val();
			var director_srl=$("#director_srl").val();
			var edirector_srl=$("#edirector_srl").val();
			var dt_no=$("#dt_no").val();
			var e_dt_no=$("#edt_no").val();
			var member_no=$("#member_no").val();
			var genre_no=$("#genre_no").val();
			var this_item_member_no=$("#this_item_member_no").val();
			var m_title=$("#title").val();
			var etitle=$("#etitle").val();
			var year=$("#year").val();
			var country=$("#country_frame > #country").val();
			var director=$("#director").val();
			var edirector=$("#edirector").val();
			var director_more=$("#director_more").val();
			var m_link=$("#link").val();
			var elink=$("#elink").val();
			open_link=elink;
			var poster=$("#poster").val();
			var eposter=$("#eposter").val();
			var what_language=$("#what_language").val();
			var etitle_more=$("#etitle_more").val();
			var metacritic_score=$("#metacritic_score").val();
			var metacritic_score_confirm=1;
			if($("#metacritic_score_confirm").prop('checked')) metacritic_score_confirm=2;
			var episode_num=$("#episode_num").val();
			var is_TV=1;
			if($("#is_TV").prop('checked')) is_TV=2;
			var is_series=1;
			if($("#is_series").prop('checked')) is_series=2;
			var rotten_score=$("#rotten_score").val();
			var rotten_score_confirm=1;
			if($("#rotten_score_confirm").prop('checked')) rotten_score_confirm=2;
			var is_codirector=$("#is_codirector").val();
			var viewlink=$("#viewlink").val();
			var resolution=$("#resolution_no").val();
			var subtitle=$("#subtitle_no").val();
			var subtitle_type=$("#subtitle_type_no").val();
			var subtitle_ok=1;
			if($("#subtitle_ok").prop('checked')) subtitle_ok=2;
			var subtitle_plus_ok=1;
			if($("#subtitle_plus_ok").prop('checked')) subtitle_plus_ok=2;
			var subtitle_sync=1;
			if($("#subtitle_sync").prop('checked')) subtitle_sync=2;
			var director_check_ok=1;
			if($("#director_check_ok").prop('checked')) director_check_ok=2;
			var v_info=$("#v_info").val();
			var is_short_no=1;
			if($("#is_short_no").prop('checked')) is_short_no=2;
			var is_ani=1;
			if($("#is_ani").prop('checked')) is_ani=2;
			var resolution_type=$("#resolution_type").val();
/*
			var region_no=$("#region_no").val();
			if(region_no=='0') {
				for(var i=0; i<$("#country_frame > #country").children().length; i++) {
					if(country == $("#country_frame > #country").children(":eq("+i+")").val()) {
						var region_no = $('select[name=country] option:eq('+i+')').data("sub");
				alert(region_no);
						break;
					}
				}
			}
*/
			$.ajax({
				url:"./movies_ok.php",
				type: "POST",   
				data: {item_no:item_no,eitem_no:eitem_no,title_no:title_no,etitle_no:etitle_no,director_no:director_no,director_srl:director_srl,edirector_srl:edirector_srl,dt_no:dt_no,e_dt_no:e_dt_no,member_no:member_no,genre_no:genre_no,this_item_member_no:this_item_member_no,m_title:m_title,etitle:etitle,year:year,country:country,director:director,	edirector:edirector, director_more:director_more, m_link:m_link, elink:elink, poster:poster, eposter:eposter, what_language:what_language, etitle_more:etitle_more, metacritic_score:metacritic_score, metacritic_score_confirm:metacritic_score_confirm, rotten_score:rotten_score, rotten_score_confirm:rotten_score_confirm, is_codirector:is_codirector, is_short_no:is_short_no, viewlink:viewlink, v_info:v_info, subtitle:subtitle, subtitle_type:subtitle_type, subtitle_ok:subtitle_ok, subtitle_plus_ok:subtitle_plus_ok, is_TV:is_TV, is_series:is_series, episode_num:episode_num, director_check_ok:director_check_ok, resolution_type:resolution_type, subtitle_sync:subtitle_sync, is_ani:is_ani  },
				dataType:'json',
				success:function(data){
						var ct=data.split('@');
						if(ct[2]=='1') {
							set_input_init();
							$("#input_table").css("display","none");
							alert("동일한 영화가 등록되어 있습니다.");
						} else {
							var genre_no_text="";
							if(genre_no==2) genre_no_text="<span class='year green'>D </span>";
							if(genre_no==3) genre_no_text="<span class='year green'>E </span>";
							var is_short_text="";
							if(is_short_no==2) is_short_text="<span class='year orange'>S </span>";
							var is_ani_text="";
							if(is_ani==2) is_ani_text="<span class='year orange'>A </span>";
							var country_text="";
							if(country!="NST"&&country!="") {
								var country_name=eval("c_"+country);
								country_text="/<div class='tooltip2'>"+country+"<span class='tooltiptext'>"+country_name+"</span></div>";
							}
							var m_box_color="";
							var m_box_title="";
							if(metacritic_score>0) {
								if(metacritic_score_confirm==2) {
									m_box_color="yellow";
									m_box_title="Metacritic Score";
								} else if(metacritic_score_confirm==1) {
									m_box_color="black";
									m_box_title="IMDB User Score";
								} else {
									m_box_color="gray";
									m_box_title="";
								}
							} else {
								m_box_color="null";
							}
							var r_box_color="";
							var r_box_title="";
							if(rotten_score>0) {
								if(rotten_score_confirm==2) {
									r_box_color="red";
									r_box_title="Rotten Tomatoes Consensus Score";
								} else if(rotten_score_confirm==1) {
									r_box_color="black";
									r_box_title="Rotten Tomatoes Temporary Score";
								} else {
									r_box_color="gray";
									r_box_title="";
								}
							} else {
								r_box_color="null";
							}
							var box_id="box_"+ct[0];
							if(is_codirector=="1") box_id="box_"+dt_no;
							var is_file="";
							if(member_no==4) {
								v_info=v_info.substring(0,1);
								if(resolution_type == 1) { //2160p
									is_file="<span class='year red'>◆◆◆</span>";
								} else if(resolution_type == 2) { //1080p
									is_file="<span class='year red'>◆◆</span>";
								} else if(resolution_type == 3) { //720p
									is_file="<span class='year red'>◆</span>";
								} else if(resolution_type == 4){ //720p이하
									is_file="<span class='year lightorange'>◆</span>";
								} else if(resolution_type == 5) { //다운중
									is_file="<span class='year lightgray'>▼</span>";
								} else if(resolution_type == 6) { //다운중
									is_file="<span class='year gray'>▼▼▼</span>";
								} else if(resolution_type == 7) { //다운중
									is_file="<span class='year gray'>▼▼</span>";
								} else if(resolution_type == 8) { //다운중
									is_file="<span class='year gray'>▼</span>";
								} else if(which_disk.indexOf(v_info)==-1) {
									if(v_info!="") is_file="<span class='year black'>■</span>";
									v_info="";
								}
							}
							var sub_info="<div class='sub_info'>";
							if(v_info!="") {
								if(subtitle==1) sub_info+="한글";
								else if(subtitle==2) sub_info+="영문";
								else if(subtitle==3) sub_info+="기타";
								else if(subtitle==4) sub_info+="무자막";
								else if(subtitle==5) sub_info+="더빙";
								else if(subtitle==6) sub_info+="필요";
								if(subtitle>0&&subtitle<4) {
									if(subtitle_type==1) sub_info+="(분리)";
									else if(subtitle_type==2) sub_info+="(묶임)";
									else if(subtitle_type==3) sub_info+="(삽입)";
									if(subtitle_ok==2) sub_info+=" | K자동";
									if(subtitle_plus_ok==2) sub_info+=" | K자동+";
								}
							}
							sub_info+="</div>";
							r1_opacity=metacritic_score/100;
							r2_opacity=rotten_score/100;
							var insert_text="<div class='movie_box' id='"+box_id+"'><div class='tooltip' onclick='get_movie_ajax_data("+ct[0]+","+ct[1]+")'><div class='title'>"+genre_no_text+is_ani_text+is_short_text+m_title.trim()+"</div><span class='tooltiptext'>"+etitle+"</span></div><div class='director_box'><span class='year' onclick='get_movie_ajax_data("+ct[0]+","+ct[1]+")'>("+year.trim()+"</span><span class='country_tip'>"+country_text+")</span> <span class='director' onclick='get_movie_ajax_data("+ct[0]+","+ct[1]+")'>"+director.trim()+"</span></div><div class='eng_title' onclick='get_movie_ajax_data("+ct[0]+","+ct[1]+")'>"+etitle+"</div><div class='rating-box box1 rating-"+m_box_color+"' style='opacity:"+r1_opacity+"' title='"+m_box_title+"'>"+metacritic_score+"</div><div class='rating-box box2 rating-"+r_box_color+"' style='opacity:"+r2_opacity+"' title='"+r_box_title+"'>"+rotten_score+"</div><div class='file_info'>"+is_file+" <div class='hard_where red'>"+v_info+"</div>"+sub_info+"</div><div class='poster_frame'><a href='"+open_link+"' target='_blank'><img src='"+poster+"' onerror='this.src=\"error_image.jpg\"' class='poster'></a></div></div>";
							var movie_box_height=$("#movies_frame").children().first().css("height");
							var movie_box_width=0;
							if(item_no>0||is_codirector=="1") {
								movie_box_width=$("#"+box_id).css("width");
								$("#"+box_id).after(insert_text);
								$("#"+box_id).remove();
								$("#"+box_id).css("height","10px").animate( {height:movie_box_height}, 500, 'linear');
							} else {
								movie_box_width=214;
								$("#movies_frame").prepend(insert_text);
								$("#movies_frame").children().first().css("width","10px").css("height","10px").animate( {width:movie_box_width, height:movie_box_height}, 500, 'linear');
							}
							set_input_init();
							$("#input_table").css("display","none");
						}
				}
			})
		}
	}
});

$('input[name="genre"]').change(function() {
	var id = $('input[name="genre"]:checked').attr('id');
	var value = $('input[name="genre"]:checked').val();
	$("#genre_no").val(value);
});

$('input[name="resolution"]').change(function() {
	var id = $('input[name="resolution"]:checked').attr('id');
	var value = $('input[name="resolution"]:checked').val();
	$("#resolution_no").val(value);
});

$('input[name="subtitle"]').change(function() {
	var id = $('input[name="subtitle"]:checked').attr('id');
	var value = $('input[name="subtitle"]:checked').val();
	$("#subtitle_no").val(value);
});

$('input[name="subtitle_type"]').change(function() {
	var id = $('input[name="subtitle_type"]:checked').attr('id');
	var value = $('input[name="subtitle_type"]:checked').val();
	$("#subtitle_type_no").val(value);
});

$('#room_reserve_cancel_bt').click(function(){
	set_input_init();
	$("#input_table").css("display","none");
});

$('#cinema_get_bt').click(function(){
	$("#movie_pop").css("top","400");
/*
	var get_title=$('#etitle').val();
	get_title=get_title.replace(/ /g, '_');
	get_title=get_title.replace(/'/g, '');
	get_title=get_title.replace(/,/g, '');
	get_title=get_title.replace(/:/g, '');
	get_title="https://www.rottentomatoes.com/m/"+get_title;
	//alert(get_title);
	window.open(get_title, '_blank');
	//var url="movies_get_iframe.php?url="+get_title;
	var get_title="https://www.metacritic.com/browse/movie/?releaseYearMin=1910&releaseYearMax=2023&page=1";
	//$('#movie_pop_iframe').attr('src', get_title);
	url="movies_get_iframe.php?url="+get_title;
	$('#movie_pop_iframe').attr('src', url);
*/
});

$('#cinema_get_bt_item').click(function(){

		//var a=$('#movie_pop_iframe').contents().find('.c-finderProductCard_titleHeading').html();
		//var item=$( "hi.g-text-large");
		//$('#movie_pop_iframe').contents().find(item).css( "color", "red" );
		//tt=$('#movie_pop_iframe').contents().find(item).html();
		//console.log("++++++++99"+tt+"99+++++++++++");
		$('#movie_pop_iframe').contents().find("h1.g-text-large").css( "color", "red" );
		var aa=$('#movie_pop_iframe').contents().find("h1.g-text-large");
		//console.log("++++++++99"+aa+"99+++++++++++");
		/*
		var a=$.trim($('#movie_pop_iframe').contents().find('.h_movie a').html());
		$('#title').val(a);
		a=$.trim($('#movie_pop_iframe').contents().find('.h_movie2').html());
		var a_array=a.split(',');
		a=$.trim(a_array[a_array.length-1]);
		if(isNaN(a)) a=$.trim(a_array[a_array.length]);
		$('#year').val(a);
		a=$('#movie_pop_iframe').contents().find('.info_spec span:nth-child(2) a').html();
		if(a=="undefined") a=$('#movie_pop_iframe').contents().find('.info_spec span:nth-child(1) a').html();
		a_array=a.split(',');
		a=$.trim(a_array[a_array.length-1]);
		for(var i=0; i<$("#country_frame > #country").children().length; i++) {
			if(a == $('#country_frame select[name=country] option:eq('+i+')').text()) {
				var country_code=$('#country_frame select[name=country] option:eq('+i+')').val();
				$("#country_frame > #country").val(country_code).prop("selected", true);
				break;
			}
		}
		a=$('#movie_pop_iframe').contents().find('.step1 a').html();
		a_array=a.split(',');
		a=$.trim(a_array[a_array.length-1]);
		$('#director').val(a);
		a=$.trim($('#movie_pop_iframe').contents().find('.poster:eq(1) img').attr("src"));
		$('#poster').val(a);
		*/
});

function e_get_iframe_data() {
	if($('#e_movie_pop_iframe').attr('src')!="null.php") {
		var a=$.trim($('#e_movie_pop_iframe').contents().find('#star-rating-widget').attr("data-title"));
		$('#etitle').val(a);
		a=$.trim($('#e_movie_pop_iframe').contents().find('.credit_summary_item a').html());
		var a_array=a.split(',');
		a=$.trim(a_array[a_array.length-1]);
		$('#edirector').val(a);
		a=$.trim($('#e_movie_pop_iframe').contents().find('.poster img').attr("src"));
		$('#eposter').val(a);
		a=$.trim($('#e_movie_pop_iframe').contents().find('.metacriticScore span').html());
		$('#metacritic_score').val(a);
	}
}

function open_category() {
	if($("#category_frame").css("display")=="block") {
		$("#movies_frame").css("width", "1200px").css("padding-left", "0px");
		$("#category_frame").css("display", "none");
		$("#category_arrow").text("▶");
		$("#category_arrow").css("color", "#FFFFAC");
	} else {
		$("#movies_frame").css("width", "960px").css("padding-left", "240px");
		$("#category_frame").css("display", "block");
		$("#category_arrow").text("◀");
		$("#category_arrow").css("color", "#C13C02");
	}
}

function open_work() {
	if($("#work_frame").css("display")=="block") {
		$("#movies_frame").css("width", "1200px").css("padding-left", "0px");
		$("#work_frame").css("display", "none");
		$("#work_arrow").text("▶");
		$("#work_arrow").css("color", "#FFFFAC");
	} else {
		$("#movies_frame").css("width", "960px").css("padding-left", "240px");
		$("#work_frame").css("display", "block");
		$("#work_arrow").text("◀");
		$("#work_arrow").css("color", "#C13C02");
	}
}

var is_first_alert=0;
function is_year_ok() {
	var y=$("#year").val();
	if(y!="") {
		if(isNaN(y)) {
			if(is_first_alert==0) {
				$("#year").focus();
				$("#year").select();
				alert("숫자만 입력해 주십시요.");
				is_first_alert++;
			} else is_first_alert=0;
		} else {
			if(y<1890||y><?=$post_nowyear?>) {
				if(is_first_alert==0) {
					$("#year").focus();
					$("#year").select();
					alert("적절한 연도가 아닙니다.");
					is_first_alert++;
				} else is_first_alert=0;
			}
		}
	}
}

function add_codirector() {
	$("#title").attr('readonly', true).css("background-color","#999999");
	$("#etitle").attr('readonly', true).css("background-color","#999999");
	$("#etitle_more").attr('readonly', true).css("background-color","#999999");
	$("#metacritic_score").attr('readonly', true).css("background-color","#999999");
	$("#episode_num").attr('readonly', true).css("background-color","#999999");
	$("#rotten_score").attr('readonly', true).css("background-color","#999999");
	$("#year").attr('readonly', true).css("background-color","#999999");
	$("#country_frame > #country").attr('readonly', true).css("background-color","#999999");
	$("#poster").attr('readonly', true).css("background-color","#999999");
	$("#eposter").attr('readonly', true).css("background-color","#999999");
	$("#link").attr('readonly', true).css("background-color","#999999");
	$("#elink").attr('readonly', true).css("background-color","#999999");
	$("#director_more").attr('readonly', true).css("background-color","#999999").val("")
	$("#director").attr('readonly', false).css("background-color","#ffffff").val("").focus();
	$("#edirector").attr('readonly', false).css("background-color","#ffffff").val("")
	$("#item_no").val("");
	$("#eitem_no").val("");
	$("#is_codirector").val("1");
}

function title_change() {
	var et=$("#etitle").val();
	var etm=$("#etitle_more").val();
	$("#etitle").val(etm);
	$("#etitle_more").val(et);
}

function view_full_country(n) {
	for(var i=0; i<$("#country_frame > #country").children().length; i++) {
		if(n == $("#country_frame > #country").children(":eq("+i+")").val()) {
			var country_name=$("#country_frame > #country").children(":eq("+i+")").text();
			break;
		}
	}
	FindXY();
	$("#tooltip_box").text(country_name);
	$("#tooltip_box").css("top",obj_y+10);
	$("#tooltip_box").css("left",obj_x-25);
}

function set_director_country() {
	var n=$("#director").val();
	$.ajax({
		url:"./movie_get_director_country.php",
		type: "POST",   
		data: {s_key:n},
		dataType:'json',
		success:function(data){
			if(data=="") $("#country_frame > #country").val("NST").prop("selected", true);
			else $("#country_frame > #country").val(data).prop("selected", true);
		}
	})
}

function set_input_init() {
	$("#item_no").val("");
	$("#item_no_view").val("");
	$("#eitem_no").val("");
	$("#title").val("").css("color","#000");
	$("#title").attr('readonly', false).css("background-color","#FFFFFF");
	$("#etitle").val("").attr('readonly', false).css("background-color","#FFFFFF");
	$("#etitle_more").val("").attr('readonly', false).css("background-color","#FFFFFF");
	$("#genre1").prop('checked', true);
	$("#genre_no").val("1");
	$("#resolution0").prop('checked', true);
	$("#resolution_no").val("0");
	$("#subtitle1").prop('checked', false);
	$("#subtitle2").prop('checked', false);
	$("#subtitle3").prop('checked', false);
	$("#subtitle4").prop('checked', false);
	$("#subtitle_no").val("0");
	$("#subtitle_type_no").val("0");
	$("#subtitle_type1").prop('checked', false);
	$("#subtitle_type2").prop('checked', false);
	$("#year").val("").attr('readonly', false).css("background-color","#fff").css("color","#000");
	$("#this_item_member_no").val("");
	$("#country_frame > #country").val("NST").prop("selected", true).attr('readonly', false).css("background-color","#fff");
	$("#director").val("").css("color","#000").css("background-color","#FFFFFF").attr('readonly', false);
	$("#edirector").val("").css("background-color","#FFFFFF").attr('readonly', false);
	$("#director_more").val("").css('color','#000').attr('readonly', false).css("background-color","#FFFFFF");
	$("#link").val("").attr('readonly', false).css("background-color","#fff").css("color","#000");
	$("#elink").val("").attr('readonly', false).css("background-color","#FFFFFF");
	$("#title_no").val("");
	$("#etitle_no").val("");
	$("#director_no").val("");
	$("#edirector_no").val("");
	$("#director_srl").val("");
	$("#edirector_srl").val("");
	$("#poster").val("").attr('readonly', false).css("background-color","#FFFFFF");
	$("#eposter").val("").attr('readonly', false).css("background-color","#FFFFFF");
	$("#dt_no").val("");
	$("#edt_no").val("");
	$("#is_short_no").val(1);
	$("#is_short_no").prop('checked', false);
	$("#is_ani").val(1);
	$("#is_ani").prop('checked', false);
	$("#viewlink").val("");
	$("#v_info").val("");
	$("#regiont_no").val(0);
	$("#metacritic_score").val("").attr('readonly', false).css("background-color","#FFFFFF");
	$("#metacritic_score_confirm").val(1);
	$("#metacritic_score_confirm").prop('checked', false);
	$("#rotten_score").val("").attr('readonly', false).css("background-color","#FFFFFF");
	$("#rotten_score_confirm").val(1);
	$("#rotten_score_confirm").prop('checked', false);
	$("#episode_num").val("").attr('readonly', false).css("background-color","#FFFFFF");
	$("#is_TV").val(1);
	$("#is_TV").prop('checked', false);
	$("#is_series").val(1);
	$("#is_series").prop('checked', false);
	$("#subtitle_ok").val(1);
	$("#subtitle_ok").prop('checked', false);
	$("#subtitle_plus_ok").val(1);
	$("#subtitle_plus_ok").prop('checked', false);
	$("#subtitle_sync").val(1);
	$("#subtitle_sync").prop('checked', false);
	$("#co_director_box").remove();
	$("#title_change_box").remove();
	$("#is_codirector").val("0");
	$("#tooltip_box").css("top",-100);
	$("#tooltip_box").css("left",100);
	$("#room_reserve_delete_bt").css("display","none");
	$("#room_reserve_ok_bt").css("display","inline-block");
	$("#director_more").css("display","inline-block");
	//$("#cinema_get_bt").css("display","inline-block");
	$("#list").val("");
	$("#list_no").val("");
	$("#list_value").val("");
	$(".appended_list").remove();
	$("#director_check_ok").val(1);
	$("#director_check_ok").prop('checked', false);
	$("#resolution_type").val("0").prop("selected", true);
	locate_pop();
}

function date_set(n) {
	$("#cr_year").val(parseInt(n.substring(0,4)));
	$("#cr_month").val(parseInt(n.substring(4,6)));
	reset_dropdown_day();
	$("#cr_day").val(parseInt(n.substring(6,8)));
}

function locate_pop(n) {
	FindXY();
	var t=$('#content_iframe',parent.document.body);
	var h=t.contents().find('body')[0].scrollHeight;
	var documentHeight = $(document).height();
	if(obj_x>800) obj_x=800;
	$("#input_table").css("display","block").css("top",obj_y+20);
	if(is_mobile==0) $("#input_table").css("left",obj_x);
	scroll_expand();
}

$('#room_reserve_delete_bt').click(function(){
	var r = confirm("등록을 삭제하시겠습니까?");
	if (r == true) {
		var item_no=$("#item_no").val();
		var title_no=$("#title_no").val();
		var director_no=$("#director_no").val();
		var member_no=$("#member_no").val();
		var dt_no=$("#dt_no").val();
		var director_srl=$("#director_srl").val();
		var co_director_no=$("#co_director_no").val();
		//console.log(co_director_no);

		var eitem_no=$("#eitem_no").val();
		var etitle_no=$("#etitle_no").val();
		var edirector_no=$("#edirector_no").val();
		var edt_no=$("#edt_no").val();
		var edirector_srl=$("#edirector_srl").val();
		var eng_co_director_no=$("#eng_co_director_no").val();
		//console.log(eng_co_director_no);

		$.ajax({
			url:"./movies_delete.php",
			type: "POST",   
			data: {item_no:item_no, title_no:title_no, director_no:director_no, member_no:member_no, dt_no:dt_no, director_srl:director_srl, eitem_no:eitem_no, etitle_no:etitle_no, edirector_no:edirector_no, edt_no:edt_no, edirector_srl:edirector_srl, co_director_no:co_director_no, eng_co_director_no:eng_co_director_no},
			dataType:'json',
			success:function(data){
					set_input_init();
					$("#input_table").css("display","none");
					$("#box_"+dt_no).animate( {width:'1px', height:'1px'}, 300, 'linear',function(){$("#box_"+dt_no).remove();});
			}
		})
	}
});

function get_movie_ajax_data(n, en) {
	set_input_init();
	$("#tooltip_box").css("top",-100);
	$("#tooltip_box").css("left",100);
	$.ajax({
		url:"./movie_get_data.php",
		type: "POST",   
		data: {s_key:n, e_key:en},
		dataType:'json',
		success:function(data){
			var ct=data.split('@@@@');
			$("#item_no").val(ct[0]);
			$("#item_no_view").val(ct[0]);
			$("#title").val(ct[1]).css("background-color","#fff").css("color","#000");
			$("#genre"+ct[2]).prop('checked', true);
			$("#genre_no").val(ct[2]);
			$("#year").val(ct[3]).css("color","#000");
			$("#country_frame > #country").val(ct[4]).prop("selected", true);
			$("#this_item_member_no").val(ct[5]);
			$("#director").val(ct[28]).css("background-color","#fff").css("color","#000");
			$("#link").val(ct[7]).css("color","#000");
			$("#title_no").val(ct[8]);
			$("#director_no").val(ct[9]);
			$("#director_srl").val(ct[10]);
			$("#dt_no").val(ct[11]);
			$("#poster").val(ct[12]);
			$("#metacritic_score").val(ct[30]);
			$("#region_no").val(ct[31]);
			$("#co_director_no").val(ct[32]);

			$("#eitem_no").val(ct[13]);
			$("#etitle").val(ct[14]);
			$("#edirector").val(ct[29]);
			$("#elink").val(ct[20]);
			$("#etitle_no").val(ct[21]);
			$("#edirector_no").val(ct[22]);
			$("#edirector_srl").val(ct[23]);
			$("#edt_no").val(ct[24]);
			$("#eposter").val(ct[25]);
			$("#director_more").val(ct[26]);
			$("#etitle_more").val(ct[27]);
			$("#eng_co_director_no").val(ct[33]);
			$("#is_short_no").val(ct[34]);
			if(Number(ct[34])==2) $("#is_short_no").prop('checked', true);
			$("#viewlink").val(ct[35]);
			$("#v_info").val(ct[36]);
			$("#list_input").after(ct[37]);
			already_listed_category=ct[38].split('|');
			//$("#resolution_type").val(ct[39]).prop('selected', true);
			//$("#resolution_no").val(ct[39]);
			$("#metacritic_score_confirm").val(ct[40]);
			if(Number(ct[40])==2) $("#metacritic_score_confirm").prop('checked', true);
			if(Number(ct[41])==0) ct[41]="";
			$("#rotten_score").val(ct[41]);
			$("#rotten_score_confirm").val(ct[42]);
			if(Number(ct[42])==2) $("#rotten_score_confirm").prop('checked', true);
			$("#subtitle"+ct[43]).prop('checked', true);
			$("#subtitle_no").val(ct[43]);
			$("#subtitle_type"+ct[44]).prop('checked', true);
			$("#subtitle_type_no").val(ct[44]);
			$("#subtitle_ok").val(ct[45]);
			if(Number(ct[45])==2) $("#subtitle_ok").prop('checked', true);
			$("#subtitle_plus_ok").val(ct[53]);
			if(Number(ct[53])==2) $("#subtitle_plus_ok").prop('checked', true);
			$("#is_TV").val(ct[46]);
			if(Number(ct[46])==2) $("#is_TV").prop('checked', true);
			$("#is_series").val(ct[47]);
			if(Number(ct[47])==2) $("#is_series").prop('checked', true);
			if(Number(ct[48])==0) ct[48]="";
			$("#episode_num").val(ct[48]);
			$("#director_check_ok").val(ct[49]);
			if(Number(ct[49])==2) $("#director_check_ok").prop('checked', true);
			$("#resolution_type").val(ct[50]).prop("selected", true);
			$("#subtitle_sync").val(ct[51]);
			if(Number(ct[51])==2) $("#subtitle_sync").prop('checked', true);
			$("#is_ani").val(ct[52]);
			if(Number(ct[52])==2) $("#is_ani").prop('checked', true);
			//console.log(ct[38]);
			//console.log(already_listed_category);
			
			if($("#this_item_member_no").val()!=$("#member_no").val()) {
				$("#room_reserve_ok_bt").css("display","none");
				$("#room_reserve_delete_bt").css("display","none");
			} else {
				$("#room_reserve_ok_bt").css("display","inline-block");
				$("#room_reserve_delete_bt").css("display","inline-block");
			}
			$("#button_box").prepend("<span id='title_change_box'><a href='javascript:title_change()'><span class='y_star'>[●] &nbsp;</span></a></span><span id='co_director_box'><a href='javascript:add_codirector()'><span class='s_star'>[C]</span></a></span>");
			//$("#director_more").css("display","none");
			//$("#cinema_get_bt").css("display","none");
			$("#input_table").css("display","none");
			locate_pop(n);
		}
	})
}

$.fn.enterKey = function (fnc) {
	return this.each(function () {
		$(this).keypress(function (ev) {
			var keycode = (ev.keyCode ? ev.keyCode : ev.which);
			if (keycode == '13') {
				fnc.call(this, ev);
			}
		})
	})
}

$("#keyword").enterKey(function () {
	chech_keyword();
})

function delete_keyword() {
	$("#keyword").val("");
	$("#keyword").focus();
}

var is_first_search=0;
function chech_keyword() {
	is_first_search=0;
	var keyword=$.trim($("#keyword").val());
	var keyword_len=$.trim($("#keyword").val()).length;
	if(keyword_len>0 && keyword_len<2) {
		alert("검색어는 2글자 이상이어야 합니다.");
		$("#keyword").focus();
	} else get_more_movies(1);
}

var is_more_get=1;
var is_more_end=1;
var is_search=0;
var limit_movie_start=0;
var limit_movie_num=150;
var is_category=0;
var category_no=0;
function get_more_movies(n) {
	$("#input_table").css("display","none");
	var cond="and d.is_main=1 and (et.is_main=1 or et.is_main is null)";
	var orderby="t.year desc";
	if(n==1) is_search=1;
	if(n>1) is_search=0;
	if(is_search==1) {
		//검색 실행이므로 초기화
		is_category=0; //카테고리 검색을 무효화, 초기화 시킴
		if(is_first_search==0) {
			limit_movie_start=0; /////처음 검색일 경우에 초기화 시킴
			window_height_set(); /////////스크롤로 넓어진 윈도 높이를 줄임
			is_first_search=1;
		}
		is_more_end=1;
		
		var keyword=$.trim($("#keyword").val());
		if(keyword!="") {
			$("#region").val("0").prop("selected", true);
			$("#select_country > #country").val("NST").prop("selected", true);
		}
		if($("#region").val()>0) cond=" and t.region_no="+$("#region").val();
		if($("#select_country > #country").val()!="NST") cond+=" and t.country='"+$("#select_country > #country").val()+"'";
		var year_range=$("#amount").val().split(' - ');
		var point_range=$("#amount2").val().split(' - ');
		if(year_range[0]!="1910") cond+=" and t.year>="+year_range[0];
		if(year_range[1]!=now_year) cond+=" and t.year<="+year_range[1];
		if(point_range[0]!="60") cond+=" and t.m_score>="+point_range[0];
		if(point_range[1]!="100") cond+=" and t.m_score<="+point_range[1];

		var addcond="";
		var gs1=0;
		if($("#select_genre1").is(":checked")) { gs1=1; addcond+=" or t.genre_no=1"; }
		var gs2=0;
		if($("#select_genre2").is(":checked")) { gs2=1; addcond+=" or t.genre_no=2"; }
		var gs3=0;
		if($("#select_genre3").is(":checked")) { gs3=1; addcond+=" or t.genre_no=3"; }
		addcond=" ("+addcond.substring(4)+") ";
		if(gs1+gs2+gs3 == 0 ){
			addcond="";
			$("#select_genre1").prop("checked", true);
			$("#select_genre2").prop("checked", true);
			$("#select_genre3").prop("checked", true);
		}
		if(gs1+gs2+gs3 == 3) addcond="";
		if(addcond!="") cond+=" and "+addcond;

		var addcond2="";
		if($("#is_short1").is(":checked") && $("#is_short2").is(":checked")) { addcond2=""; }
		else if($("#is_short1").is(":checked")) { addcond2=" t.is_short=1 "; }
		else if($("#is_short2").is(":checked")) { addcond2=" t.is_short=2 "; }
		else { $("#is_short1").prop("checked", true); $("#is_short2").prop("checked", true); }
		if(addcond2!="") cond+=" and "+addcond2;

		var addcond3="";
		if($("#select_ani").is(":checked")) { addcond3=" t.is_ani=2 "; }
		if(addcond3!="") cond+=" and "+addcond3;

		if($("#select_order").val()==1) orderby="t.year";
		else if($("#select_order").val()==2) orderby="t.title";
		else if($("#select_order").val()==3) orderby="t.title desc";
		else if($("#select_order").val()==4) orderby="d.director, t.year desc";
		else if($("#select_order").val()==5) orderby="d.director desc, t.year desc";
		else if($("#select_order").val()==6) orderby="t.m_score desc, t.year desc";
		else if($("#select_order").val()==7) orderby="t.m_score, t.year desc";
	}
	if(n>1) { //n>1인 경우 그것은 카테고리 번호로 category iframe에서 호출하는 것임.
		limit_movie_start=0; /////처음 검색일 경우에 초기화 시킴
		window_height_set(); /////////스크롤로 넓어진 윈도 높이를 줄임
		is_more_end=1;
		is_category=1;
		category_no=n;
	}

	if(is_more_get==1&&is_more_end==1) {
		console.log(cond);
		//console.log(orderby);
		
		is_more_get=0;
		$.ajax({
			url:"./movies_get_more.php",
			type: "POST",   
			data: {limit_movie_start:limit_movie_start, limit_movie_num:limit_movie_num, orderby:orderby, cond:cond, keyword:keyword, category_no:category_no },
			dataType:'json',
			success:function(data){
				//console.log(data[0]);
				if(limit_movie_start==0) $("#movies_frame").empty();
				if(data[0]==0) {
					var null_text="<div class='movie_box' style='font-size:20px;color:#999;'>검색 결과가 없습니다.</div>";
					$("#movies_frame").append(null_text);
				}
				if(data[data.length-1]<limit_movie_num) is_more_end=0;
				var text_add="";
				for(var i=0; i<data.length-1; i++) {
					var ct=data[i].split('@@@@');
					var genre="";
					if(ct[2]=='2') genre="<span class='year green'>D </span>";
					if(ct[2]=='3') genre="<span class='year green'>E </span>";
					//if(ct[2]=='4') genre="<span class='year green'>M </span>";
					//if(ct[2]=='5') genre="<span class='year green'>E </span>";
					var country="";
					if(ct[4]!="NST"||ct[4]!="") {
						var country_name=eval("c_"+ct[4]);
						country="/<div class='tooltip2'>"+ct[4]+"<span class='tooltiptext'>"+country_name+"</span></div>";
					}
					var this_item_member_no=ct[5];
					var link=ct[7]; ///////한글 링크
					link=ct[12]; //////////영문 링크
					var title_text=ct[1]; ///////한글 제목
					//title_text=ct[10]; ///////영문 제목
					var box_id="box_"+ct[0];
					var m_box_color="";
					var m_box_title="";
					if(Number(ct[14])>0) {
						if(Number(ct[20])==2) {
							m_box_color="yellow";
							m_box_title="Metacritic Score";
						} else if(Number(ct[20])==1) {
							m_box_color="black";
							m_box_title="IMDB User Score";
						} else {
							m_box_color="gray";
							m_box_title="";
						}
					} else {
						m_box_color="null";
					}
					var r_box_color="";
					var r_box_title="";
					if(Number(ct[21])>0) {
						if(Number(ct[22])==2) {
							r_box_color="red";
							r_box_title="Rotten Tomatoes Consensus Score";
						} else if(Number(ct[22])==1) {
							r_box_color="black";
							r_box_title="Rotten Tomatoes Temporary Score";
						} else {
							r_box_color="gray";
							r_box_title="";
						}
					} else {
						r_box_color="null";
					}
					var is_short_text="";
					if(Number(ct[15])==2) is_short_text="<span class='year orange'>S </span>";
					var is_ani_text="";
					if(Number(ct[26])==2) is_ani_text="<span class='year orange'>A </span>";
					var is_file="";
					//if(member_no==4) {
					//if(member_no!=0) {
						var v_info=ct[18].substring(0,1);
						if(Number(ct[19]) == 1) { //2160p
							is_file="<span class='year red'>◆◆◆</span>";
						} else if(Number(ct[19]) == 2) { //1080p
							is_file="<span class='year red'>◆◆</span>";
						} else if(Number(ct[19]) == 3) { //720p
							is_file="<span class='year red'>◆</span>";
						} else if(Number(ct[19]) == 4) { //720p이하
							is_file="<span class='year lightorange'>◆</span>";
						} else if(Number(ct[19]) == 5) { //다운중
							is_file="<span class='year lightgray'>▼</span>";
						} else if(Number(ct[19]) == 6) { //다운중
							is_file="<span class='year gray'>▼▼▼</span>";
						} else if(Number(ct[19]) == 7) { //다운중
							is_file="<span class='year gray'>▼▼</span>";
						} else if(Number(ct[19]) == 8) { //다운중
							is_file="<span class='year gray'>▼</span>";
						} else if(which_disk.indexOf(v_info)==-1) {
							if(v_info!="") is_file="<span class='year black'>■</span>";
							v_info="";
						}
					//}
					/// if(which_disk.indexOf(v_info)!=-1)
					var sub_info="<div class='sub_info'>";
					if(v_info!="") {
						var subtitle=Number(ct[23]);
						if(subtitle==1) sub_info+="한글";
						else if(subtitle==2) sub_info+="영문";
						else if(subtitle==3) sub_info+="기타";
						else if(subtitle==4) sub_info+="무자막";
						else if(subtitle==5) sub_info+="더빙";
						else if(subtitle==6) sub_info+="필요";
						var subtitle_type=Number(ct[24]);
						var subtitle_ok=Number(ct[25]);
						var subtitle_plus_ok=Number(ct[27]);
						if(subtitle>0&&subtitle<4) {
							if(subtitle_type==1) sub_info+="(분리)";
							else if(subtitle_type==2) sub_info+="(묶임)";
							else if(subtitle_type==3) sub_info+="(삽입)";
							if(subtitle_ok==2) sub_info+=" | K자동";
							if(subtitle_plus_ok==2) sub_info+=" | K자동+";
						}
					}
					sub_info+="</div>";
					r1_opacity=Number(ct[14])/100;
					r2_opacity=Number(ct[21])/100;
					text_add="<div class='movie_box' id='"+box_id+"'>\
						<div class='tooltip' onclick='get_movie_ajax_data("+ct[0]+","+ct[11]+")'><div class='title'>"+genre+is_ani_text+is_short_text+title_text+"</div><span class='tooltiptext'>"+ct[10]+"</span></div>\
						<div class='director_box'><span class='year' onclick='get_movie_ajax_data("+ct[0]+","+ct[11]+")'>("+ct[3]+"</span><span class='country_box'>"+country+"</span>)</span> <span class='director' onclick='get_movie_ajax_data("+ct[0]+","+ct[11]+")'>"+ct[6]+"</span></div>\
						<div class='eng_title' onclick='get_movie_ajax_data("+ct[0]+","+ct[11]+")'>"+ct[10]+"</div>\
						<div class='rating-box box1 rating-"+m_box_color+"' style='opacity:"+r1_opacity+"' title='"+m_box_title+"'>"+ct[14]+"</div>\
						<div class='rating-box box2 rating-"+r_box_color+"' style='opacity:"+r2_opacity+"' title='"+r_box_title+"'>"+ct[21]+"</div><div class='file_info'>"+is_file+" <div class='hard_where red'>"+v_info+"</div>"+sub_info+"</div>\
						<div class='poster_frame'><a href='"+link+"' target='_blank'><img src='"+ct[17]+"' onerror='this.src=\"error_image.jpg\"' class='poster'></a></div>";
						//한국 포스터는 ct[8], 영문 포스터는 ct[17]
					$("#movies_frame").append(text_add);
				}
				limit_movie_start+=limit_movie_num;
				scroll_expand();
			}
		});
	}
}
get_more_movies();

$("#select_country > #country").change(function(){
	var sc=$("#select_country > #country").val();
	if(sc!="NST") {
		$("#region").val("0").prop("selected", true);
		$("#keyword").val("");
	}
});

$("#region").change(function(){
	var sc=$("#region").val();
	if(sc!="0") {
		$("#select_country > #country").val("NST").prop("selected", true);
		$("#keyword").val("");
	}
});

var filter = "win16|win32|win64|macintel|mac|"; // PC일 경우 가능한 값
if(navigator.platform) {
     if( filter.indexOf(navigator.platform.toLowerCase())<0 ) is_mobile=1;
}
</script>

</body></html>



<?
	@mysql_close($connect);
?>