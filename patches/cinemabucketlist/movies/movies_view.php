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

if(!$category) $category=0;
if(!$srl) $srl=0;
if(!$keyword) $keyword="None";
$what_language="ko"; // 기본 언어설정: ko 고정 (en 모드 비활성화)
?>
<html lang="ko">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Movies</title>
<link rel=StyleSheet HREF=style_view.css?<?=$real_reg_date?> type=text/css title=style>
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

var now_year=new Date().getFullYear();


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


<table id='input_table' border=0 cellpadding=0 cellspacing=0 width=100% style='display:none;'><tr>
<form method=post action="movies_ok.php" name="reserve_form" id="reserve_form" enctype="multipart/form-data">
<td>
<table class='input_table2' id='box_table' border=0 cellpadding=0 cellspacing=0>
<tr>
	<td  class='input_tag en-hide'>연도</td>
	<td  class='input_tag ko-hide admin-hide'>Year</td>
	<td  class='input_td' id='country_frame'><input type='text' name='year' id='year' class='input_box' style='width:50px;'></td>
</tr>
</table>

</form>
</td></tr></table>

<p id="tooltip_box"></p>
<div id="movies_frame">
</div>

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

$("#country_frame").append("<span class='input_tag ko-hide admin-hide' style='margin:0 0 0 18px ;'>Country</span> "+country_html);

for(var i=0; i<$("#country_frame > #country").children().length; i++) {
	var key=$('#country_frame select[name=country] option:eq('+i+')').val();
	var c_val=$('#country_frame select[name=country] option:eq('+i+')').html();
	eval("var c_" + key + "=\""+c_val+"\"");
}

var is_first_alert=0;

var is_first_search=0;

var is_more_get=1;
var is_more_end=1;
var is_search=0;
var limit_movie_start=0;
var limit_movie_num=150;
var is_category=0;
var category_no=0;
function get_more_movies(n,srl) {
	//$("#input_table").css("display","none");
	var cond="and d.is_main=1 and (et.is_main=1 or et.is_main is null)";
	var orderby="t.year desc";
	var is_list=1;
	if(n==1) is_search=1;
	if(n>1) is_search=0;
	if(srl>0) {
		var keyword=srl;
		 is_list=0;
	} else {
		var keyword="<?=$keyword?>";
		is_list=0;
	}
	if(is_search==1) {
		//검색 실행이므로 초기화
		is_category=0; //카테고리 검색을 무효화, 초기화 시킴
		if(is_first_search==0) {
			limit_movie_start=0; /////처음 검색일 경우에 초기화 시킴
			is_first_search=1;
		}
		is_more_end=1;
		//cond+=" and t.srl="+srl;
		//var keyword=$.trim($("#keyword").val());
		/*
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
		*/
	}
	if(n>1) { //n>1인 경우 그것은 카테고리 번호로 category iframe에서 호출하는 것임.
		limit_movie_start=0; /////처음 검색일 경우에 초기화 시킴
		is_more_end=1;
		is_category=1;
		category_no=n;
	}

	if(is_more_get==1&&is_more_end==1) {
		console.log(cond);
		console.log(keyword);
		console.log(orderby);
		
		is_more_get=0;
		$.ajax({
			url:"./movies_get_more_view.php",
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
					if(ct[2]=='2') genre="<span class='year green'>[다큐멘터리] </span>";
					if(ct[2]=='3') genre="<span class='year green'>[실험영화] </span>";
					//if(ct[2]=='4') genre="<span class='year green'>M </span>";
					//if(ct[2]=='5') genre="<span class='year green'>E </span>";
					var country="";
					if(ct[4]!="NST"||ct[4]!="") {
						var country_name=eval("c_"+ct[4]);
						//country="/<div class='tooltip2'>"+ct[4]+"<span class='tooltiptext'>"+country_name+"</span></div>";
						country="<div class='year'>"+country_name+"</div>";
					}
					var this_item_member_no=ct[5];
					var link=ct[7]; ///////한글 링크
					link=ct[12]; //////////영문 링크
					var box_id="box_"+ct[0];
					var m_box_color="";
					var m_box_title="";
					if(Number(ct[14])>0) {
						if(Number(ct[20])==2) {
							m_box_color="yellow";
							m_box_title="메타크리틱스 점수";
						} else if(Number(ct[20])==1) {
							m_box_color="black";
							m_box_title="IMDB 관객 점수";
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
							r_box_title="로튼토마토 점수";
						} else if(Number(ct[22])==1) {
							r_box_color="black";
							r_box_title="로튼토마토 임시 점수";
						} else {
							r_box_color="gray";
							r_box_title="";
						}
					} else {
						r_box_color="null";
					}
					var is_short_text="";
					if(Number(ct[15])==2) is_short_text="<span class='year orange'>[단편] </span>";
					var is_ani_text="";
					if(Number(ct[26])==2) is_ani_text="<span class='year orange'>[애니] </span>";
					var is_file="";
					//if(member_no==4) {
					//if(member_no!=0) {
						var v_info=ct[18].substring(0,1);
						if(Number(ct[19]) == 1) { //2160p
							is_file="<span class='year'>4K(2160p)</span>";
						} else if(Number(ct[19]) == 2) { //1080p
							is_file="<span class='year'>Bluray(1080p)</span>";
						} else if(Number(ct[19]) == 3) { //720p
							is_file="<span class='year'>HD(720p)</span>";
						} else if(Number(ct[19]) == 4) { //720p이하
							is_file="<span class='year'>720p 이하</span>";
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
					text_add="<div class='frame_box'><div class='movie_box' id='"+box_id+"'>\
						<div class='title' style='overflow:hidden; height:30px;'>"+genre+is_ani_text+is_short_text+ct[1]+"</div>\
						<div class='eng_title' style='color:#000'>"+ct[10]+"</div>\
						<div class='director_box'><span class='director'>"+ct[6]+" 감독</span></div>\
						<div class='director_box'><span class='year'>"+ct[3]+"</span></div>\
						<div class='director_box'>"+country+"</div>\
						<div class='score_box'><div class='score_name'>"+m_box_title+"</div><div class='rating-box box1 rating-"+m_box_color+"' style='opacity:"+r1_opacity+"'>"+ct[14]+"</div>\
						<div class='score_name'>"+r_box_title+"</div><div class='rating-box box2 rating-"+r_box_color+"' style='opacity:"+r2_opacity+"'>"+ct[21]+"</div></div>\
						<div class='file_size'>화질 : </div><div class='file_info'>"+is_file+"<span class='sub_info_t'>자막 : "+sub_info+"</span></div>\
						<div class='poster_frame'>";
					if(ct[28]!=0&&is_list==1) text_add+="<div class='serial_no'>"+ct[28]+"</div>";
					text_add+="<img src='"+ct[8]+"' onerror='this.src=\"error_image.jpg\"' class='poster'></div>\
						<div>"+ct[29]+"</div></div>";
						//한국 포스터는 ct[8], 영문 포스터는 ct[17]
					$("#movies_frame").append(text_add);
				}
				limit_movie_start+=limit_movie_num;
			}
		});
	}
}

////여기에 불러들일 분류값을 설정
var c_no=<?=$category?>;
var m_no=<?=$srl?>;
//if(c_no==0&&m_no==0) c_no=330;
if(m_no>0) c_no=1;
get_more_movies(c_no,m_no);

</script>

</body></html>



<?
	@mysql_close($connect);
?>