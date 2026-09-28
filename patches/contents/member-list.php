<?
include_once dirname(__FILE__)."/auth-guard.php";
extract($_REQUEST);
require "lib.php";
$reg_date=time();
$today=date("Ymd", $reg_date);
if(!$connect) $connect=dbConn();
if(!$view_type) $view_type=1;
if(!$mode) $mode=3;

if(!$sunday) {
	if (date('w') == 0 || date('m-d') == '12-16') {  // 0 = 일요일
		$sunday = date('Y-m-d');
	} else {
		$sunday = date('Y-m-d', strtotime('last sunday'));
	}
}
$before_sunday = date('Y-m-d', strtotime($sunday . ' -7 days'));
$next_sunday = date('Y-m-d', strtotime($sunday . ' +7 days'));

$kinder_num=$child_num=$young_num=$school_total_num=$teacher_num=$parent_num=$program_num=$service_num=$etc_num=$extra_total_num=$all_total_num=0;
$extra_main_num=$extra_sub_num=$extra_visit_num=$extra_sum_num=0;
?>
<!DOCTYPE html>
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="member-list-style.css?<?=$reg_date?>" />
	<link rel="shortcut icon" href="http://www.hyanglin.org/home/files/attach/xeicon/favicon.ico" /><link rel="apple-touch-icon" href="http://www.hyanglin.org/home/files/attach/xeicon/mobicon.png" />
	<script src="/common/js/jquery.min.js"></script>
</head>
<body>
<div id="head">
	<div id="title"><span class="title">향린교회 주일 출석<span></div>
	<div id="sunday"><span id="before-sunday" class="arrow" title="이전 주일">◀</span> <span id="sunday-text"></span> <span id="next-sunday" class="arrow" title="다음 주일">▶</span></div>
	<div id="add-icon"><img src="images/add-icon.png" title="교인 추가" style="cursor:pointer;"></div>
	<div class="view_select">
		<select id="view_select" name="view_select">
			<option value="1">전체 이름순으로 보기</option>
			<option value="2">분류별로 보기</option>
			<option value="3">교회학교 출석 현황</option>
			<option value="4">소속별 출석 현황</option>
			<option value="5">교인별 출석(가나다순)</option>
			<option value="6">교인별 출석(출석률순)</option>
			<option value="9">출석률 하락 교인</option>
			<option value="7">주일별 출석 현황</option>
			<option value="8">비노출 교인 명단</option>
		</select>
	</div>
	<div id="online">온라인 예배 <input type='text' id='online_num' data-id='<?=$sunday?>' class='online_num' value='<?=$online_num?>'> 명</div>
	<script>
	$("#view_select").find('option[value="<?=$view_type?>"]').prop('selected', true);
	</script>
	<div id="mode-holder">
		<div class="mode-icon" id="touch-mode"><img src="images/icon-touch.png" title="터치스크린 모드"></div>
		<div class="mode-icon" id="edit-mode"><img src="images/icon-edit.png" title="편집 모드"></div>
		<div class="mode-icon" id="lock-mode"><img src="images/icon-key.png" title="화면잠금 모드"></div>
	</div>
	<div class="rotate-image"><img src="images/vertical-icon.png" title="화면 회전" onclick="rotateContainer();"></div>
	<div class="fixed-image"><img src="images/fullscreen.png" title="전체 화면" onclick="enterFullscreen();"></div>
</div>

<div id="head_sub">
<span class="head_top">예배실</span>
<span class="head_sub">정회원 <span id="main_num" class="m_num"><?=$main_num?></span><span id="extra_main_num" class="extra_m_num"> (<?=$extra_main_num?>)</span></span>
<span class="head_sub">| 준회원 <span id="sub_num" class="m_num"><?=$sub_num?></span><span id="extra_sub_num" class="extra_m_num"> (<?=$extra_sub_num?>)</span></span>
<span class="head_sub">| 방문출석 <span id="visit_num" class="m_num"><?=$visit_num?></span><span id="extra_visit_num" class="extra_m_num"> (<?=$extra_visit_num?>)</span></span>
<span class="head_sub" style="margin-right:0px;">| 미확인 <span id="unknown_num" class="m_num"><?=$unknown_num?></span></span>
<span id="unknown-plus" class="known-arrow">▲</span>
<span id="unknown-minus" class="known-arrow">▼</span>
<span class="head_sub head_sub_total"> [계 <span id="total_num" class="m_num"><?=$total_num?></span><span id="extra_sum_num" class="extra_m_num"> (<?=$extra_sum_num?>)</span>]</span>
<span class="head_top margin_left_10">예배실外</span>
<span class="head_sub pointer" title="교회학교 교사">교사 <span id="teacher_num" class="m_num"><?=$teacher_num?></span></span>
<span class="head_sub pointer" title="교회학교 학부모">| 학부모 <span id="parent_num" class="m_num"><?=$parent_num?></span></span>
<span class="head_sub pointer" title="교회 프로그램 참여">| 프로그램 <span id="program_num" class="m_num"><?=$program_num?></span></span>
<span class="head_sub pointer" title="봉사 & 업무">| 업무 <span id="service_num" class="m_num"><?=$service_num?></span></span>
<span class="head_sub pointer" title="기타">| 기타 <span id="etc_num" class="m_num"><?=$etc_num?></span></span>
<span class="head_sub head_sub_total"> [계 <span id="extra_total_num" class="m_num"><?=$extra_total_num?></span>]</span>
<span class="head_top margin_left_10">교회학교</span>
<span class="head_sub pointer" title="유아유치부">유 <span id="kinder_num" class="m_num"><?=$kinder_num?></span></span>
<span class="head_sub pointer" title="어린이부">| 어 <span id="child_num" class="m_num"><?=$child_num?></span></span>
<span class="head_sub pointer" title="청소년부">| 청 <span id="young_num" class="m_num"><?=$young_num?></span></span>
<span class="head_sub head_sub_total"> [계 <span id="school_total_num" class="m_num"><?=$school_total_num?></span>]</span>
<span class="m_num margin_left_10"> <총계 <span id="all_total_num" class="m_num"><?=$all_total_num?></span>></span>
</div>
<div id="head_sub2">
<span class="head_sub"> ※ 1회 이상 예배 참석자만 표시됩니다. ●=예배실, ○=예배실외(교사, 학부모, 교회프로그램참여, 봉사&업무)</span>
</div>
<div id='input_box' >
<form method="post" action="member-ok.php" id="member_form" enctype="multipart/form-data">
<table class='input_table' id='table_box' border=0 cellpadding=0 cellspacing=0>
<input type='hidden' name='member_no' id='member_no'>
<tr class="tr_class">
	<td  class='input_title'>이름<span class="necessary"> (필수)</span></td>
	<td  class='input_td'><input type='text' name='member_name' id='member_name' class='input_box' maxlength="20" style='width:120px;'></td>
</tr>
<tr class="tr_class">
	<td  class='input_title'>구분<span class="necessary"> (필수)</span></td>
	<td  class='input_td'>
		<input type='radio' name='category' id='category1' value='1'><label for="category1" class='input_tag2'> 정회원</label>
		<input type='radio' name='category' id='category2' value='2'><label for="category2" class='input_tag2'> 준회원</label>
		<input type='radio' name='category' id='category3' value='3'><label for="category3" class='input_tag2'> 방문교우</label>
		<input type='radio' name='category' id='category4' value='4'><label for="category4" class='input_tag2'> 교회학교</label>
		<button class="reset-btn" onclick="resetSpecificRadio('category', event)">미선택</button>
	</td>
</tr>
<tr class="tr_class">
	<td  class='input_title'>신도회</td>
	<td  class='input_td'>
		<input type='radio' name='age_category' id='age_category1' value='1'><label for="age_category1" class='input_tag2'> 새날청년회</label>
		<input type='radio' name='age_category' id='age_category2' value='2'><label for="age_category2" class='input_tag2'> 청년신도회</label>
		<input type='radio' name='age_category' id='age_category3' value='3'><label for="age_category3" class='input_tag2'> 희년청년회</label></br>
		<input type='radio' name='age_category' id='age_category4' value='4'><label for="age_category4" class='input_tag2'> 청년여신도회</label>
		<input type='radio' name='age_category' id='age_category5' value='5'><label for="age_category5" class='input_tag2'> 청년남신도회</label></br>
		<input type='radio' name='age_category' id='age_category6' value='6'><label for="age_category6" class='input_tag2'> 희년여신도회</label>
		<input type='radio' name='age_category' id='age_category7' value='7'><label for="age_category7" class='input_tag2'> 희년남신도회</label></br>
		<input type='radio' name='age_category' id='age_category8' value='8'><label for="age_category8" class='input_tag2'> 장년여신도회</label>
		<input type='radio' name='age_category' id='age_category9' value='9'><label for="age_category9" class='input_tag2'> 장년남신도회</label></br>
		<button class="reset-btn" onclick="resetSpecificRadio('age_category', event)">미선택</button>
	</td>
</tr>
<tr class="tr_class">
	<td  class='input_title'>소속</td>
	<td  class='input_td'>
	<div class='input_td' id="service_list" style="border:0">
<?
$result_input=mysql_query("SELECT no, title FROM `members_service_category` order by no");
while($data=@mysql_fetch_array($result_input)) {
	echo "<label for='service_category$data[no]' class='input_tag2'><input type='radio' name='service_category' class='service_category' id='service_category$data[no]' value='$data[no]'>$data[title]</label>";
}
?>
		</div>
		<button class="reset-btn" onclick="resetSpecificRadio('service_category', event)">미선택</button></br>
		<span class="exp">소속 항목 추가</span> <input type='text' name='service_category_add' id='service_category_add' class='input_box' maxlength="16" style='width:100px;'>
		<button type="button" id="service_category_add_button" class="button">추가</button>
	</td>
</tr>
<tr class="tr_class">
	<td  class='input_title'>교인등록일</td>
	<td  class='input_td'>
		<input type='text' name='register_date' id='register_date' class='input_box' maxlength="8" style='width:100px;'><span class="exp">(예시-20250825)</span></br>
		<span class="exp">연도만 입력시 1월 1일로 등록됨, 월까지 입력시 해당 월 1일로 등록됨</span>
	</td>
</tr>
<tr class="tr_class">
	<td  class='input_title'>방문일</td>
	<td  class='input_td'>
		<input type='text' name='visit_date' id='visit_date' class='input_box' maxlength="8" style='width:100px;'><span class="exp">(예시-20250825)</span></br>
		<span class="exp">연도만 입력시 1월 1일로 등록됨, 월까지 입력시 해당 월 1일로 등록됨</span>
	</td>
</tr>
<tr class="tr_class">
	<td  class='input_title'>메모</td>
	<td  class='input_td'><input type='text' name='memo' id='memo' class='input_box' maxlength="80" style='width:460px;'></td>
</tr>
<tr class="tr_class">
	<td  class='input_title'>비노출</td>
	<td  class='input_td'><label for='no_use' class='checkbox-container'><input type='checkbox' name='no_use' id='no_use' value='1'> <span class="exp" style="margin-left:10px">더 이상 일반 명부에 노출 안함</span></label></td>
</tr>
<tr class="tr_class">
	<td  class='input_title'><button type="button" id="member_delete_button" class="button">삭제</button></td>
	<td  class='input_td'>
		<button type="button" id="member_close_button" class="button">창닫기</button>
		<button type="button" id="member_ok_button" class="button">등록</button>
	</td>
</tr>
</table>
</form>
</div>

<!--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------->
<div id="container">
<div id="hidden-box"></div>
<div id="hidden-place-box">
	<input type='radio' name='place' id='place2' value='2'><label for="place2" class='input_tag2 place-text'> 교회학교 교사</label></br>
	<input type='radio' name='place' id='place3' value='3'><label for="place3" class='input_tag2 place-text'> 교회학교 학부모</label></br>
	<input type='radio' name='place' id='place4' value='4'><label for="place4" class='input_tag2 place-text'> 교회 프로그램 참여</label></br>
	<input type='radio' name='place' id='place5' value='5'><label for="place5" class='input_tag2 place-text'> 봉사 & 업무</label></br>
	<input type='radio' name='place' id='place6' value='6'><label for="place6" class='input_tag2 place-text'> 기타</label></br>
	<input type='radio' name='place' id='place1' value='1'><label for="place1" class='input_tag2 place-text'> 선택 해제 (예베실)</label></br>
	<button type="button" id="place_close_button" class="button">창닫기</button>
</div>
</div>
<!--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------->
<div id="container-cover"></div>
<script>
var mode=<?=$mode?>; //처음에 Lock 모드로 설정
var memberBoxHeight;
var total_box_num=0;
var this_sunday="<?=$sunday?>";
var before_sunday="<?=$before_sunday?>";
var next_sunday="<?=$next_sunday?>";
var view_select=1;
$("#touch-mode").click(function(){ modeSet(1); });
$("#edit-mode").click(function(){ modeSet(2); });
$("#lock-mode").click(function(){ modeSet(3); });

function modeSet(m) {
	mode=m;
	$("#container-cover").css("display", "none");
	$("#touch-mode").css("background-color", "#ddd");
	$("#edit-mode").css("background-color", "#ddd");
	$("#lock-mode").css("background-color", "#ddd");
	if(mode==1) $("#touch-mode").css("background-color", "#33ffff");
	else if(mode==2) $("#edit-mode").css("background-color", "#33ffff");
	else if(mode==3) {
		$("#lock-mode").css("background-color", "#33ffff");
		$("#container-cover").css("display", "block");
	}
}

$("#before-sunday").click(function(){
	members_get(view_select, before_sunday);
});
$("#next-sunday").click(function(){
	members_get(view_select, next_sunday);
});

/*
$("#view_select").change(function() {
	var selectedValue = $(this).val();
	view_select=selectedValue;
	console.log("selectedValue-",selectedValue);
	members_get(selectedValue, this_sunday);
});
*/


function members_get(n,s) {
	var view_type=n;
	var sunday=s;
	console.log("sunday--",sunday);
	$.ajax({
		url:"member-list-get-ajax.php",
		type: "POST",   
		data: { view_type:view_type, sunday:sunday },
		dataType:'json',
		success:function(data){
			//console.log(data);
			var cc=data.split('@@@');
			$("#sunday-text"). text(sunday);
			$("#online_num").val(cc[2]);
			$("#main_num").text(cc[3]);
			$("#sub_num").text(cc[4]);
			$("#visit_num").text(cc[5]);
			$("#unknown_num").text(cc[6]);
			$("#total_num").text(cc[7]);
			$("#kinder_num").text(cc[8]);
			$("#child_num").text(cc[9]);
			$("#young_num").text(cc[10]);
			$("#school_total_num").text(cc[11]);

			$("#teacher_num").text(cc[12]);
			$("#parent_num").text(cc[13]);
			$("#program_num").text(cc[14]);
			$("#service_num").text(cc[15]);
			$("#etc_num").text(cc[16]);
			$("#extra_total_num").text(cc[17]);
			$("#all_total_num").text(cc[18]);
			
			$("#extra_main_num").text("("+cc[19]+")");
			$("#extra_sub_num").text("("+cc[20]+")");
			$("#extra_visit_num").text("("+cc[21]+")");
			$("#extra_sum_num").text("("+cc[22]+")");
			
			$("#container").children().not("#hidden-box, #hidden-place-box").remove();
			this_sunday=sunday;
			before_sunday=getSundayDates(sunday, 0);
			next_sunday=getSundayDates(sunday, 1);
			$('.member-name').remove();
			$('.cate').remove();
			$('#hidden-box').before(cc[0]);
			total_box_num=cc[1];
			//modeSet(mode);
			boxHeightSet();
			memberNameSet();
			if(isFullScreen()) fullScreenBoxHeightSet();
			if(view_type==5||view_type==6||view_type==9) {
				console.log(cc[8]);
				var aa=cc[23].split('#');
				for(var k=0; k<aa.length; k++) {
					var imsi=aa[k].split('^');
					if(imsi[1]==1) $("#"+imsi[0]).text("●");
					else $("#"+imsi[0]).text("○");
				}
				$("#head_sub").css("visibility","hidden");
				$("#head_sub2").css("display","flex");
				if(view_type==9) {
					var c33 = cc[24] || "";
					var c66 = cc[25] || "";
					$("#head_sub2").html("※ 최근 25주(2026.04.12 - 2026.09.27) 출석률 30% 미만 교인 중 직전 25주 대비 가장 많이 낮아진 교인 25명 (비노출 교인 제외) <span style='color: #ffffff; font-weight: bold; margin-left: 12px; text-shadow: 0 1px 2px rgba(0,0,0,0.25);'>[최근 25주 출석률 33% 이상인 교인수 " + c33 + "명, 66% 이상인 교인수 " + c66 + "명]</span>");
				} else {
					$("#head_sub2").html("※ 1회 이상 예배 참석자만 표시됩니다. ●=예배실, ○=예배실외(교사, 학부모, 교회프로그램참여, 봉사&업무) &nbsp;(출석률은 화면에 보이는 25주의 출석률임)");
				}
				$("#container").css("overflow","auto");
				modeSet(2);
			} else if(view_type==7) {
				//console.log(cc[0]);
				$("#head_sub").css("visibility","hidden");
				$("#head_sub2").css("display","flex").html("&nbsp;");
				$("#container").css("overflow","auto");
				modeSet(2);
			} else if(view_type==8) {
				console.log(cc[0]);
				$("#head_sub").css("visibility","hidden");
				$("#head_sub2").css("display","flex").html("&nbsp;");
				$("#container").css("overflow","hidden");
				modeSet(2);
			} else {
				$("#container").css("overflow","hidden");
				$("#head_sub2").css("display","none");
				$("#head_sub").css("visibility","visible");
				modeSet(mode);
			}

		}
	})
}
members_get(1,"<?=$sunday?>");

function getSundayDates(dateString, type) {
    // 1. 입력 날짜 및 시간 정규화
    const inputDate = new Date(dateString);
    inputDate.setHours(0, 0, 0, 0);

    // 2. 해당 연도의 성탄절 날짜 설정
    const xmasDate = new Date(inputDate.getFullYear(), 11, 25); // 11월 아님, 11=12월
    xmasDate.setHours(0, 0, 0, 0);

    // 3. [기준] 달력상의 "진짜 일요일" 계산 (성탄절 고려 X)
    const dayOfWeek = inputDate.getDay(); // 0:일요일
    
    // 3-1. 진짜 지난 일요일 (오늘이 일요일이면 7일 전)
    const daysSince = dayOfWeek === 0 ? 7 : dayOfWeek;
    let finalLast = new Date(inputDate);
    finalLast.setDate(inputDate.getDate() - daysSince);

    // 3-2. 진짜 다음 일요일 (오늘이 일요일이면 7일 후)
    const daysUntil = dayOfWeek === 0 ? 7 : (7 - dayOfWeek);
    let finalNext = new Date(inputDate);
    finalNext.setDate(inputDate.getDate() + daysUntil);

    // 4. [보정] 성탄절이 "진짜 일요일"과 "오늘" 사이에 끼어있는지 확인
    // (오늘이 성탄절 당일이라면, 아래 조건문(부등호)에 걸리지 않아 "진짜 일요일"들이 그대로 유지됩니다)

    // Case A: 지난 기준점 구하기
    // "진짜 지난 일요일" < "성탄절" < "오늘" 이라면 -> 바로 직전 기준일은 성탄절이 됨
    if (finalLast < xmasDate && xmasDate < inputDate) {
        finalLast = new Date(xmasDate);
    }

    // Case B: 다음 기준점 구하기
    // "오늘" < "성탄절" < "진짜 다음 일요일" 이라면 -> 바로 다음 기준일은 성탄절이 됨
    if (inputDate < xmasDate && xmasDate < finalNext) {
        finalNext = new Date(xmasDate);
    }

    // 5. 날짜 포맷팅 (YYYY-MM-DD, 로컬 타임존 보정)
    const formatDate = (date) => {
        const offset = date.getTimezoneOffset() * 60000;
        return new Date(date.getTime() - offset).toISOString().split('T')[0];
    };

    // 6. 결과 반환
    if (type === 0) {
        return formatDate(finalLast);
    } else if (type === 1) {
        return formatDate(finalNext);
    } else {
        return {
            last: formatDate(finalLast),
            next: formatDate(finalNext),
            lastDate: finalLast,
            nextDate: finalNext
        };
    }
}

$("#member_close_button").click(function(){
	$("#input_box").css("display", "none");
	$("#member_form")[0].reset();
	$("#member_no").val("");
	$('#insert_tr1').remove();
	$('#insert_tr2').remove();
	$('#insert_tr3').remove();
	$('#visit_date').val('');
});

$("#place_close_button").click(function(){
	//console.log("place_close_button");
	$('input[name="place"]').prop('checked', false);
	$("#hidden-place-box").css("display", "none");
});

$("#member_delete_button").click(function(){
	var no=$("#member_no").val();
	var name=$("#member_name").val();
	if (confirm(name+" 님을 삭제하시겠습니까?")) {
		console.log(no);
		$.ajax({
			url:"member-delete.php",
			type: "POST",   
			data: { no:no },
			dataType:'json',
			success:function(data){
				if(data>0) {
					$('div[data-id="'+data+'"]').css('display', 'none');
					$("#input_box").css("display", "none");
					$("#member_form")[0].reset();
					$("#member_no").val("");
					$('#insert_tr1').remove();
					$('#insert_tr2').remove();
					$('#insert_tr3').remove();
					$('#visit_date').val('');
				} else {
					alert("삭제에 실패했습니다. 다시 시도해 주십시요.");
				}
			}
		})
	}
});

$("#category1").change(function() {
	if ($(this).is(':checked')) {
		$('#insert_tr1').remove();
		$('#insert_tr2').remove();
		$('#insert_tr3').remove();
		$('#visit_date').val('');
	}
});

$("#category2").change(function() {
	if ($(this).is(':checked')) {
		$('#insert_tr2').remove();
		$('#insert_tr3').remove();
		$('#table_box tr:eq(2)').before("<tr class='tr_class' id='insert_tr1'><td  class='input_td' colspan='2' style='padding-left:90px;'><label for='subcategory1' class='input_tag2'><input type='radio' name='subcategory' id='subcategory1' value='1'>미세례</label><label for='subcategory2' class='input_tag2'><input type='radio' name='subcategory' id='subcategory2' value='2'>유아세례</label><label for='subcategory3' class='input_tag2'><input type='radio' name='subcategory' id='subcategory3' value='3'>미가입식</label><label for='subcategory4' class='input_tag2'><input type='radio' name='subcategory' id='subcategory4' value='4'>병역</label><label for='subcategory5' class='input_tag2'><input type='radio' name='subcategory' id='subcategory5' value='5'>해외</label><label for='subcategory6' class='input_tag2'><input type='radio' name='subcategory' id='subcategory6' value='6'>지방</label><label for='subcategory7' class='input_tag2'><input type='radio' name='subcategory' id='subcategory7' value='7'>교우</label><button class='reset-btn' onclick=\"resetSpecificRadio('subcategory', event)\">미선택</button></td></tr>");
		$('#visit_date').val('');
	}
});
$("#category3").change(function() {
	if ($(this).is(':checked')) {
		$('#insert_tr1').remove();
		$('#insert_tr3').remove();
		$('#table_box tr:eq(2)').before("<tr class='tr_class' id='insert_tr2'><td  class='input_td' colspan='2' style='padding-left:90px;'><label for='subcategory10' class='input_tag2'><input type='radio' name='subcategory' id='subcategory10' value='10'>2023년</label><label for='subcategory11' class='input_tag2'><input type='radio' name='subcategory' id='subcategory11' value='11'>2024년</label><label for='subcategory12' class='input_tag2'><input type='radio' name='subcategory' id='subcategory12' value='12' checked>2025년</label><label for='subcategory13' class='input_tag2'><input type='radio' name='subcategory' id='subcategory13' value='13'>2026년</label><label for='subcategory14' class='input_tag2'><input type='radio' name='subcategory' id='subcategory14' value='14'>2027년</label><label for='subcategory15' class='input_tag2'><input type='radio' name='subcategory' id='subcategory15' value='15'>2028년</label><button class='reset-btn' onclick=\"resetSpecificRadio('subcategory', event)\">미선택</button></td></tr>");
		$('#visit_date').val(getLastSundayYmd());
	}
});
$("#category4").change(function() {
	if ($(this).is(':checked')) {
		$('#insert_tr1').remove();
		$('#insert_tr2').remove();
		$('#table_box tr:eq(2)').before("<tr class='tr_class' id='insert_tr3'><td  class='input_td' colspan='2' style='padding-left:90px;'><label for='subcategory31' class='input_tag2'><input type='radio' name='subcategory' id='subcategory31' value='31'>유아유치부</label><label for='subcategory32' class='input_tag2'><input type='radio' name='subcategory' id='subcategory32' value='32'>어린이부</label><label for='subcategory33' class='input_tag2'><input type='radio' name='subcategory' id='subcategory33' value='33'>청소년부</label><button class='reset-btn' onclick=\"resetSpecificRadio('subcategory', event)\">미선택</button></td></tr>");
		$('#visit_date').val('');
	}
});
function resetSpecificRadio(groupName, event) {
    // 이벤트 전파 중단
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    const radios = document.querySelectorAll(`input[type="radio"][name="${groupName}"]`);
    radios.forEach(radio => {
        radio.checked = false;
    });
	if(groupName!='subcategory') {
		$('#insert_tr1').remove();
		$('#insert_tr2').remove();
		$('#visit_date').val('');
	}
}

$("#online_num").on('input', function() {
	var $element = $(this);
	var num = $element.val();
	$.ajax({
		url:"member-online-ok.php",
		type: "POST",   
		data: { sunday:this_sunday, num:num },
		dataType:'json',
		success:function(data){
			console.log("ok");
		}
	})
});

$("#unknown-plus").click(function(){
	var changeNum = 1;
	$.ajax({
		url:"member-unknown-ok.php",
		type: "POST",   
		data: { sunday:this_sunday, num:changeNum },
		dataType:'json',
		success:function(data){
			var cc=data.split('@@@');
			$("#main_num").text(cc[0]);
			$("#sub_num").text(cc[1]);
			$("#visit_num").text(cc[2]);
			$("#unknown_num").text(cc[3]);
			$("#total_num").text(cc[4]);

			$("#teacher_num").text(cc[5]);
			$("#parent_num").text(cc[6]);
			$("#program_num").text(cc[7]);
			$("#service_num").text(cc[8]);
			$("#etc_num").text(cc[9]);
			$("#extra_total_num").text(cc[10]);
			$("#all_total_num").text(cc[11]);

			$("#extra_main_num").text("("+cc[12]+")");
			$("#extra_sub_num").text("("+cc[13]+")");
			$("#extra_visit_num").text("("+cc[14]+")");
			$("#extra_sum_num").text("("+cc[15]+")");

		}
	})
});

$("#unknown-minus").click(function(){
	var changeNum = -1;
	$.ajax({
		url:"member-unknown-ok.php",
		type: "POST",   
		data: { sunday:this_sunday, num:changeNum },
		dataType:'json',
		success:function(data){
			var cc=data.split('@@@');
			$("#main_num").text(cc[0]);
			$("#sub_num").text(cc[1]);
			$("#visit_num").text(cc[2]);
			$("#unknown_num").text(cc[3]);
			$("#total_num").text(cc[4]);

			$("#teacher_num").text(cc[5]);
			$("#parent_num").text(cc[6]);
			$("#program_num").text(cc[7]);
			$("#service_num").text(cc[8]);
			$("#etc_num").text(cc[9]);
			$("#extra_total_num").text(cc[10]);
			$("#all_total_num").text(cc[11]);

			$("#extra_main_num").text("("+cc[12]+")");
			$("#extra_sub_num").text("("+cc[13]+")");
			$("#extra_visit_num").text("("+cc[14]+")");
			$("#extra_sum_num").text("("+cc[15]+")");
		}
	})
});

function isFullScreen() {
    return !!(document.fullscreenElement || 
              document.webkitFullscreenElement || 
              document.mozFullScreenElement || 
              document.msFullscreenElement);
}

$("#view_select").change(function() {
	var selectedValue = $(this).val();
	view_select=selectedValue;
	console.log("selectedValue-",selectedValue);
	members_get(selectedValue, this_sunday);
});
function enterFullscreen() {
	if(document.fullscreenElement) {
		const $container = $('#container');
		if ($container.hasClass('rotated')) {
			rotateContainer();
		}
		exitFullscreen();
	} else {
		const element = document.documentElement;
		if (element.requestFullscreen) {
			element.requestFullscreen();
		} else if (element.webkitRequestFullscreen) { // Safari
			element.webkitRequestFullscreen();
		} else if (element.msRequestFullscreen) { // IE/Edge
			element.msRequestFullscreen();
		} else if (element.mozRequestFullScreen) { // Firefox
			element.mozRequestFullScreen();
		}
	}
	fullScreenBoxHeightSet();
}
function exitFullscreen() {
	if (document.exitFullscreen) {
		document.exitFullscreen();
	} else if (document.webkitExitFullscreen) { // Safari
		document.webkitExitFullscreen();
	} else if (document.msExitFullscreen) { // IE/Edge
		document.msExitFullscreen();
	} else if (document.mozCancelFullScreen) { // Firefox
		document.mozCancelFullScreen();
	}
	//location.reload();
}

document.addEventListener('keydown', function(event) {
	if (event.key === 'Escape') {
		console.log('ESC 키가 눌렸습니다. 전체화면에서 나갑니다.');
	}
});

function getRotationDegrees(obj) {
	var matrix = obj.css("transform");
	if (matrix === 'none') return 0;
	var values = matrix.split('(')[1].split(')')[0].split(',');
	var a = values[0];
	var b = values[1];
	var angle = Math.round(Math.atan2(b, a) * (180/Math.PI));
    return angle < 0 ? angle + 360 : angle;
}

function fullScreenBoxHeightSet() {
	const $container = $('#container');
	const screenHeight = screen.height;
	const screenWidth = screen.width;
	 if (!$container.hasClass('rotated')) {
		var totalRows = Math.ceil(total_box_num/25);
		const containerHeight = screenHeight -80;
		const containerWidth = screenWidth;
		var newBoxHeight = containerHeight / totalRows;
		memberBoxHeight=newBoxHeight;
		if(newBoxHeight>80) newBoxHeight=80;
		$(".member-name").css("height", newBoxHeight);
		$(".member-name").css("width", "4%");
		$(".cate").css("height", newBoxHeight);
		$(".cate").css("width",  "4%");
	} else {
		var totalRows = Math.ceil(total_box_num/14);
		const containerHeight = screenWidth;
		const containerWidth = screenHeight;
		var newBoxHeight = containerHeight / totalRows;
		memberBoxHeight=newBoxHeight;
		if(newBoxHeight>80) newBoxHeight=80;
		$(".member-name").css("height", newBoxHeight);
		$(".member-name").css("width", "7.1%");
		$(".cate").css("height", newBoxHeight);
		$(".cate").css("width", "7.1%");
	}	
}

function rotateContainer() {
	if(document.fullscreenElement) {
		const $container = $('#container');
		const screenWidth = $(window).width();
		const screenHeight = $(window).height()-80;
		
		if (!$container.hasClass('rotated')) {
			const translateX = (screenWidth - screenHeight) / 2;
			const translateY = (screenHeight - screenWidth) / 2;
			
			$container.css({
				'transform': `translate(${translateX}px, ${translateY}px) rotate(-90deg)`,
				'width': screenHeight + 'px',
				'height': screenWidth + 'px'
			}).addClass('rotated');
		} else {
			$container.css({
				'transform': 'none',
				'width': '100vw',
				'height': '100vh'
			}).removeClass('rotated');
		}
		fullScreenBoxHeightSet();
	} else {
		alert("전체 화면에서 회전할 수 있습니다.");
	}
}
var thisSelectedMemberNo;
function memberNameSet() {
    $(".member-name").click(function(e) {
		if(mode==1) {
			var $element = $(this);
			var textValue = $element.text().replace(/\s+/g, '');
			var memberNo = $element.data('id');
			var memberPlace = $element.data('place');
			var sunday = this_sunday;
			
			$("#hidden-box").css({"display": "flex", "white-space":"nowrap", "word-break":"keep-all", "overflow":"hidden"});
			var x = e.pageX - 200;
			var y = e.pageY - 170;
			var rotation = getRotationDegrees($("#container"));
			
			if(y > -100) $("#hidden-box").css({"display":"flex", "white-space":"nowrap", "word-break":"keep-all", "overflow":"hidden"}).text(textValue);
			
			if(rotation == 0) {
				if(x < 0) x = 0;
				if(y < 0) y = 0;
				//$("#hidden-box").css({"width": "380px", "height":"200px", "font-size":"6rem", "padding":"40px 0"});
				$("#hidden-box").css({"width": "380px", "height":"200px", "font-size":"6rem", "display":"flex" });
				if(x + 380 > $("#container").width()) x = $("#container").width() - 380;
				if(y + 200 > $("#container").height()) y = $("#container").height() - 200;
				y = y + $("#container").scrollTop();
				$("#hidden-box").css({"top": y + "px", "left": x + "px"});
			} else {
				var c_width=$("#container").width();
				//var c_height=c_width*200/380;
				var c_height=$("#container").height()+100;
				//$("#hidden-box").css({"width": c_width+"px", "height":c_height+"px", "font-size":"15rem", "padding":"160px 0"});
				$("#hidden-box").css({"width": c_width+"px", "height":c_height+"px", "font-size":"15rem", "display":"flex"});
				$("#hidden-box").css({"top": "0px", "left":"0px"});
			}
			$.ajax({
				url:"member-attendance-ok.php",
				type: "POST",   
				data: { memberNo:memberNo, sunday:sunday },
				dataType:'json',
				success:function(data){
					var cc=data.split('@@@');
					if(cc[0]>0) {
						$("#hidden-box").fadeOut(1000);
						$element.removeClass().addClass("member-name bg_color_black");
						$element.data('place', 'place_1');
					} else {
						$("#hidden-box").fadeOut(300);
						$element.removeClass().addClass("member-name bg_color0");
						$element.data('place', 'place_');
					}
					$("#main_num").text(cc[1]);
					$("#sub_num").text(cc[2]);
					$("#visit_num").text(cc[3]);
					$("#unknown_num").text(cc[4]);
					$("#total_num").text(cc[5]);
					$("#kinder_num").text(cc[6]);
					$("#child_num").text(cc[7]);
					$("#young_num").text(cc[8]);
					$("#school_total_num").text(cc[9]);

					$("#teacher_num").text(cc[10]);
					$("#parent_num").text(cc[11]);
					$("#program_num").text(cc[12]);
					$("#service_num").text(cc[13]);
					$("#etc_num").text(cc[14]);
					$("#extra_total_num").text(cc[15]);
					$("#all_total_num").text(cc[16]);

					$("#extra_main_num").text("("+cc[17]+")");
					$("#extra_sub_num").text("("+cc[18]+")");
					$("#extra_visit_num").text("("+cc[19]+")");
					$("#extra_sum_num").text("("+cc[20]+")");
				}
			})
		} else if(mode==2) { //편집 모드
				$("#input_box").css("display", "block");
				var memberNo = $(this).data('id');
				$.ajax({
					url:"member-get-data.php",
					type: "POST",   
					data: { memberNo:memberNo },
					dataType:'json',
					success:function(data){
						var cc=data.split('@@@');
						$("#member_no").val(cc[0]);
						$("#member_name").val(cc[1]);
						var categoryValue = cc[2];
						$('input[name="category"][value="' + categoryValue + '"]').prop('checked', true);
						var subcategoryValue = cc[3];
						if(subcategoryValue>0&&subcategoryValue<10) {
							$('#table_box tr:eq(2)').before("<tr class='tr_class' id='insert_tr1'><td  class='input_td' colspan='2' style='padding-left:90px;'><label for='subcategory1' class='input_tag2'><input type='radio' name='subcategory' id='subcategory1' value='1'>미세례</label><label for='subcategory2' class='input_tag2'><input type='radio' name='subcategory' id='subcategory2' value='2'>유아세례</label><label for='subcategory3' class='input_tag2'><input type='radio' name='subcategory' id='subcategory3' value='3'>미가입식</label><label for='subcategory4' class='input_tag2'><input type='radio' name='subcategory' id='subcategory4' value='4'>병역</label><label for='subcategory5' class='input_tag2'><input type='radio' name='subcategory' id='subcategory5' value='5'>해외</label><label for='subcategory6' class='input_tag2'><input type='radio' name='subcategory' id='subcategory6' value='6'>지방</label><label for='subcategory7' class='input_tag2'><input type='radio' name='subcategory' id='subcategory7' value='7'>교우</label><button class='reset-btn' onclick=\"resetSpecificRadio('subcategory', event)\">미선택</button></td></tr>");
						} else if(subcategoryValue>=10&&subcategoryValue<31) {
							$('#table_box tr:eq(2)').before("<tr class='tr_class' id='insert_tr2'><td  class='input_td' colspan='2' style='padding-left:90px;'><label for='subcategory10' class='input_tag2'><input type='radio' name='subcategory' id='subcategory10' value='10'>2023년</label><label for='subcategory11' class='input_tag2'><input type='radio' name='subcategory' id='subcategory11' value='11'>2024년</label><label for='subcategory12' class='input_tag2'><input type='radio' name='subcategory' id='subcategory12' value='12'>2025년</label><label for='subcategory13' class='input_tag2'><input type='radio' name='subcategory' id='subcategory13' value='13'>2026년</label><label for='subcategory14' class='input_tag2'><input type='radio' name='subcategory' id='subcategory14' value='14'>2027년</label><label for='subcategory15' class='input_tag2'><input type='radio' name='subcategory' id='subcategory15' value='15'>2028년</label><button class='reset-btn' onclick=\"resetSpecificRadio('subcategory', event)\">미선택</button></td></tr>");
						} else if(subcategoryValue>=31) {
							$('#table_box tr:eq(2)').before("<tr class='tr_class' id='insert_tr3'><td  class='input_td' colspan='2' style='padding-left:90px;'><label for='subcategory31' class='input_tag2'><input type='radio' name='subcategory' id='subcategory31' value='31'>유아유치부</label><label for='subcategory32' class='input_tag2'><input type='radio' name='subcategory' id='subcategory32' value='32'>어린이부</label><label for='subcategory33' class='input_tag2'><input type='radio' name='subcategory' id='subcategory33' value='33'>청소년부</label><button class='reset-btn' onclick=\"resetSpecificRadio('subcategory', event)\">미선택</button></td></tr>");
						}
						$('input[name="subcategory"][value="' + subcategoryValue + '"]').prop('checked', true);
						var age_categoryValue = cc[4];
						$('input[name="age_category"][value="' + age_categoryValue + '"]').prop('checked', true);
						var service_categoryValue = cc[5];
						$('input[name="service_category"][value="' + service_categoryValue + '"]').prop('checked', true);
						var register_date = cc[6].replace(/-/g, '');
						$("#register_date").val(register_date);
						var visit_date = cc[7].replace(/-/g, '');
						$("#visit_date").val(visit_date);
						$("#memo").val(cc[8]);
						if(cc[9]==1) $('#no_use').prop('checked', true);
						//console.log(data);
				}
			})
		}
	});

	$(".member-name").on('contextmenu', function(e) {
		if(mode==1) {
			e.preventDefault(); // 기본 컨텍스트 메뉴 방지 (선택사항)
			var $element = $(this);
			var textValue = $element.text();
			var memberNo = $element.data('id');
			thisSelectedMemberNo=memberNo;
			var memberPlace = $element.data('place');
			var cc=memberPlace.split('_');
			memberPlace=cc[1];
			console.log("memberPlace",memberPlace);
			var sunday = this_sunday;
			if(memberPlace>=1) {
				$("#hidden-place-box").css("display", "block");
				var x = e.pageX - 100;
				var y = e.pageY - 150;
				$('input[name="place"][value="' + memberPlace + '"]').prop('checked', true);
				if(x < 0) x = 0;
				if(y < 0) y = 0;
				if(x + 200 > $("#container").width()) x = $("#container").width() - 200;
				if(y + 240 > $("#container").height()) y = $("#container").height() - 240;
				y = y + $("#container").scrollTop();
				$("#hidden-place-box").css({"top": y + "px", "left": x + "px"});
			}
		}
	});
}

$('input[name="place"]').change(function() {
	if (this.checked) {
		var selectedPlace=$(this).val();
		var sunday = this_sunday;
		console.log('thisSelectedMemberNo:', thisSelectedMemberNo);
		console.log('selectedPlace:', selectedPlace);
		$.ajax({
			url:"member-place-ok.php",
			type: "POST",   
			data: { memberNo:thisSelectedMemberNo, sunday:sunday, place:selectedPlace },
			dataType:'json',
			success:function(data){
				var cc=data.split('@@@');
				var placeText="place_"+selectedPlace;
				console.log("placeText",placeText);
				if(cc[0]>1) {
					$('div[data-id="'+thisSelectedMemberNo+'"]').removeClass().addClass("member-name bg_color_gray");
					$('div[data-id="'+thisSelectedMemberNo+'"]').data('place', placeText);
				} else {
					$('div[data-id="'+thisSelectedMemberNo+'"]').removeClass().addClass("member-name bg_color_black");
					$('div[data-id="'+thisSelectedMemberNo+'"]').data('place', placeText);
				}
				$("#main_num").text(cc[1]);
				$("#sub_num").text(cc[2]);
				$("#visit_num").text(cc[3]);
				$("#unknown_num").text(cc[4]);
				$("#total_num").text(cc[5]);
				$("#kinder_num").text(cc[6]);
				$("#child_num").text(cc[7]);
				$("#young_num").text(cc[8]);
				$("#school_total_num").text(cc[9]);

				$("#teacher_num").text(cc[10]);
				$("#parent_num").text(cc[11]);
				$("#program_num").text(cc[12]);
				$("#service_num").text(cc[13]);
				$("#etc_num").text(cc[14]);
				$("#extra_total_num").text(cc[15]);
				$("#all_total_num").text(cc[16]);

				$("#extra_main_num").text("("+cc[17]+")");
				$("#extra_sub_num").text("("+cc[18]+")");
				$("#extra_visit_num").text("("+cc[19]+")");
				$("#extra_sum_num").text("("+cc[20]+")");
				
				$('input[name="place"]').prop('checked', false);
				$("#hidden-place-box").css("display", "none");
			}
		})
	}
});

$("#member_ok_button").click(function(){
	var no=$("#member_no").val();
	if(!$("#member_name").val()) alert("이름을 입력해 주십시요.");
	else if(!$('input[name="category"]:checked').val()) alert("구분을 선택해 주십시요.");
	var name=$("#member_name").val();
	var category = $('input[name="category"]:checked').val();
	var subcategory = $('input[name="subcategory"]:checked').val();
	var age_category = $('input[name="age_category"]:checked').val();
	var service_category = $('input[name="service_category"]:checked').val();
	var register_date=$("#register_date").val();
	var visit_date=$("#visit_date").val();
	var memo=$("#memo").val();
	memo = memo.replace(/['"]/g, '');
	var no_use = $('#no_use').is(':checked') ? 1 : 0;
	//console.log(no);
	$.ajax({
		url:"member-ok.php",
		type: "POST",   
		data: { no:no, name:name, category:category, subcategory:subcategory, age_category:age_category, service_category:service_category, register_date:register_date, visit_date:visit_date, memo:memo, no_use:no_use },
		dataType:'json',
		success:function(data){
			var cc=data.split('@@@');
			if(cc[0]==0) {
				alert("동일한 이름이 있습니다. '비노출 교인'에 명단에 동일한 이름이 있을 수도 있습니다. 이름 뒤에 B, C순으로 알파벳을 붙여 구분해 주세요.")
			} else {
				$("#member_form")[0].reset();
				$("#member_no").val("");
				$('#insert_tr1').remove();
				$('#insert_tr2').remove();
				$('#insert_tr3').remove();
				$('#visit_date').val('');
				$("#input_box").css("display", "none");
				if(visit_date==this_sunday.replace(/-/g, '')) {
					var thisUnknownNum=Number($("#unknown_num").text());
					console.log("thisUnknownNum", thisUnknownNum);
					thisUnknownNum--;
					$("#unknown_num").text(thisUnknownNum);
				}
				if(cc[1]) {
					$('#container').prepend('<div class="member-name bg_color_new" data-class="member-name bg_color_new" data-id="'+cc[0]+'" style="height:'+memberBoxHeight+'px;">'+cc[1]+'</div>');
					memberNameSet();
				}
				if(view_select==8&&no_use==0) $('[data-id="'+no+'"]').remove();
				if(no_use==1)  $('[data-id="'+no+'"]').remove();
			}
		}
	})
});

$("#service_category_add_button").click(function(){
	var addItem=$("#service_category_add").val();
	$.ajax({
		url:"member-service-add-ok.php",
		type: "POST",   
		data: { addItem:addItem },
		dataType:'json',
		success:function(data){
			if(data==0) {
				alert("동일한 항목이 있습니다.")
			} else {
				$('#service_list').append("<label for='service_category"+data+"' class='input_tag2'><input type='radio' name='service_category' class='service_category' id='service_category"+data+"' value='"+data+"'>"+addItem+"</label>");
				$("#service_category_add").val("");
			}
		}
	})
});

$("#add-icon").click(function(){
	if(mode==2) {
		$("#input_box").css("display", "block");
		$("#member_form")[0].reset();
		$("#member_no").val("");
		$('#insert_tr1').remove();
		$('#insert_tr2').remove();
		$('#insert_tr3').remove();
		$('#visit_date').val('');
	}
});

function getRotatedCoordinates(x, y, rotation) {
    var centerX = $(window).width() / 2;
    var centerY = $(window).height() / 2;
    if (rotation === 180) {
        // 180도 회전: 중심점 기준으로 대칭 이동
        var newX = centerX + (centerX - x);
        var newY = centerY + (centerY - y);
        return { x: newX, y: newY };
    }
    return { x: x, y: y };
}

function getFloatRowCount() {
	var $container = $('#container');
	var $items = $container.find('div').filter(':visible');
	if ($items.length === 0) return 0;
	if ($container.width() === 0) {
		console.log('컨테이너가 아직 렌더링되지 않았습니다');
		return 0;
	}
	try {
		var positions = [];
		$items.each(function() {
			var pos = $(this).position();
			positions.push(Math.round(pos.top * 10) / 10); // 소수점 1자리
		});
		// jQuery 방식으로 중복 제거
		var uniquePositions = [];
		$.each(positions, function(i, pos) {
			if ($.inArray(pos, uniquePositions) === -1) {
			uniquePositions.push(pos);
			}
		});
		return uniquePositions.length;
	} catch (error) {
		console.error('줄 수 계산 중 오류:', error);
		return 0;
	}
}

function getLastSundayYmd() {
    var today = new Date();
    var dayOfWeek = today.getDay(); // 0: 일요일, 1: 월요일, ..., 6: 토요일
    
    // 오늘이 일요일이면 0일, 아니면 해당 요일 숫자만큼 빼기
    var daysToSubtract = dayOfWeek === 0 ? 0 : dayOfWeek;
    
    var lastSunday = new Date(today);
    lastSunday.setDate(today.getDate() - daysToSubtract);
    
    // YYYY-MM-DD 형식으로 변환
    var year = lastSunday.getFullYear();
    var month = String(lastSunday.getMonth() + 1).padStart(2, '0');
    var day = String(lastSunday.getDate()).padStart(2, '0');
    
    return year + month + day;
}

function boxHeightSet() {
    var $container = $('#container');
    var $items = $container.find('div');
    var containerWidth = $container.width();
    var itemWidth = $items.first().outerWidth(true);
    var itemsPerRow = Math.floor(containerWidth / itemWidth);
	var totalRows = Math.ceil(total_box_num/25);
    const $lastDiv = $('#container div:nth-last-child(2)');
    const position = $lastDiv.position();
	const boxHeight = $lastDiv.outerHeight();
	const allBboxesHeight = position + boxHeight;
	const containerHeight = $('#container').height();
	var newBoxHeight = containerHeight / totalRows;
	if(newBoxHeight>80) newBoxHeight=80;
	memberBoxHeight=newBoxHeight;
	$(".member-name").css("height", newBoxHeight);
	$(".cate").css("height", newBoxHeight);
}

$(document).ready(function() {
    var isShiftPressed = false;
    
    // Shift 키를 눌렀을 때
    $(document).keydown(function(e) {
        if (e.shiftKey && !isShiftPressed) {
            isShiftPressed = true;
            if(mode==1) modeSet(2);
			else if(mode==2) modeSet(1);
        }
    });
    
    // Shift 키를 뗐을 때
    $(document).keyup(function(e) {
        if (e.key === 'Shift') {
            isShiftPressed = false;
            if(mode==1) modeSet(2);
			else if(mode==2) modeSet(1);
        }
    });
});

/*
if(window.opener) {
	var memberService = window.opener.memberService;
	if(memberService!=1) window.location.href = '/';
}
if (!window.opener || window.opener.closed) {
        window.location.href = '/';
}
*/

function isInFullscreen() {
    return !!(
        document.fullscreenElement ||
        document.webkitFullscreenElement ||
        document.mozFullScreenElement ||
        document.msFullscreenElement ||
        // 브라우저 전체화면도 감지
        (window.outerHeight === screen.height && window.outerWidth === screen.width)
    );
}

window.addEventListener('resize', function(e) {
    if (!isInFullscreen()) {
        window.resizeTo(1500, 992);
		boxHeightSet();
    }
});

</script>
</body>
</html>

<?
mysql_close($connect);
?>