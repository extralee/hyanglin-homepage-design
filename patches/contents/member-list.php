<?php
include_once dirname(__FILE__)."/auth-guard.php";
extract($_REQUEST);
require "lib.php";
$reg_date = time();
$today = date("Ymd", $reg_date);
if(!$connect) $connect = dbConn();

// 1. 기준 주일 및 전체 주일 목록 계산 (최신순 25주 및 과거 기록 시작일 2025-08-10까지)
function getPrevScheduleDate($dt) {
    $w = (int)date('w', $dt);
    $days = ($w == 0) ? 7 : $w;
    $prev_sunday = strtotime("-{$days} days", $dt);
    $year = (int)date('Y', $dt);
    $xmas = strtotime("{$year}-12-25");
    if ($xmas >= $dt) {
        $xmas = strtotime(($year - 1)."-12-25");
    }
    if ($xmas > $prev_sunday) {
        return $xmas;
    }
    return $prev_sunday;
}

if(!$sunday) {
    if (date('w') == 0 || date('m-d') == '12-25') {
        $cur_ts = strtotime(date('Y-m-d'));
    } else {
        $cur_ts = strtotime('last sunday');
    }
} else {
    $cur_ts = strtotime($sunday);
}

$origin_ts = strtotime('2025-08-10');
$allDates = array();
$loop_ts = $cur_ts;
while ($loop_ts >= $origin_ts) {
    $allDates[] = array(
        'full' => date('Y-m-d', $loop_ts),
        'short' => date('m.d', $loop_ts),
        'is_xmas' => (date('m-d', $loop_ts) == '12-25')
    );
    $loop_ts = getPrevScheduleDate($loop_ts);
}

// 2. 전체 교인 목록 및 상세 데이터
$allMembers = array();
$memberDetailsMap = array();
$m_res = mysql_query("SELECT no, name, category, subcategory, age_category, service_category, register_date, visit_date, memo, no_use FROM members ORDER BY name ASC", $connect);
while ($m = mysql_fetch_assoc($m_res)) {
    $no_str = (string)$m['no'];
    $memberDetailsMap[$no_str] = $m;
    if ($m['no_use'] == '0' && (int)$m['age_category'] < 10) {
        $cate = '정'; $col = 'darkred';
        if ($m['category'] == '2') { $cate = '준'; $col = 'darkgreen'; }
        elseif ($m['category'] == '3') { $cate = '방'; $col = 'darkgray'; }
        $allMembers[] = array(
            'no' => $m['no'],
            'name' => $m['name'],
            'cate' => $cate,
            'color' => $col,
            'no_use' => (int)$m['no_use']
        );
    }
}

// 3. 전체 출석 데이터 매핑
$attMap = array();
$att_res = mysql_query("SELECT member_no, date, place FROM member_attendance", $connect);
while ($r = mysql_fetch_assoc($att_res)) {
    $p = (int)$r['place'];
    $attMap[$r['member_no'].'-'.$r['date']] = ($p > 0) ? $p : 1;
}

// 4. 소속 카테고리
$serviceCategories = array();
$sc_res = mysql_query("SELECT srl, title FROM member_service_category WHERE title != '' ORDER BY srl ASC", $connect);
while ($r = mysql_fetch_assoc($sc_res)) {
    $serviceCategories[] = array('no' => $r['srl'], 'title' => $r['title']);
}

// 5. 주일별 통계 데이터 집계
$weeklyStatsList = array();
$stat_query = "SELECT 
    a.date,
    count(*) as total,
    sum(case when a.place=1 then 1 else 0 end) as sanctuary,
    sum(case when a.place>1 then 1 else 0 end) as outside,
    sum(case when b.category=1 and a.place=1 then 1 else 0 end) as reg,
    sum(case when b.category=2 and a.place=1 then 1 else 0 end) as sub,
    sum(case when b.category=3 and a.place=1 then 1 else 0 end) as visit,
    sum(case when a.place=2 then 1 else 0 end) as teacher,
    sum(case when a.place=3 then 1 else 0 end) as parent,
    sum(case when a.place=4 then 1 else 0 end) as prog,
    sum(case when a.place=5 then 1 else 0 end) as serv,
    sum(case when a.place=6 then 1 else 0 end) as etc,
    sum(case when b.category=4 and b.subcategory=31 then 1 else 0 end) as kinder,
    sum(case when b.category=4 and b.subcategory=32 then 1 else 0 end) as child,
    sum(case when b.category=4 and b.subcategory=33 then 1 else 0 end) as young
FROM member_attendance a LEFT JOIN members b ON a.member_no=b.no 
WHERE a.date>='2025-08-10'
GROUP BY a.date
ORDER BY a.date DESC";
$stat_res = mysql_query($stat_query, $connect);

$online_map = array();
$on_res = mysql_query("SELECT date, member_num FROM members_online", $connect);
while ($on_row = mysql_fetch_assoc($on_res)) {
    $online_map[$on_row['date']] = (int)$on_row['member_num'];
}
$unknown_map = array();
$unk_res = mysql_query("SELECT date, member_num FROM members_unknown", $connect);
while ($unk_row = mysql_fetch_assoc($unk_res)) {
    $unknown_map[$unk_row['date']] = (int)$unk_row['member_num'];
}

while ($st = mysql_fetch_assoc($stat_res)) {
    $dt = $st['date'];
    $st['online'] = isset($online_map[$dt]) ? (string)$online_map[$dt] : '0';
    $st['unknown'] = isset($unknown_map[$dt]) ? (string)$unknown_map[$dt] : '0';
    $weeklyStatsList[] = $st;
}

// 6. viewResponses 초기화
$viewResponses = array();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>향린교회 주일 출석</title>
    <link rel="stylesheet" href="member-list-style.css?<?=$reg_date?>" />
    <link rel="shortcut icon" href="http://www.hyanglin.org/home/files/attach/xeicon/favicon.ico" />
    <link rel="apple-touch-icon" href="http://www.hyanglin.org/home/files/attach/xeicon/mobicon.png" />
    <script src="/common/js/jquery.min.js"></script>
</head>
<body>

<div id="head">
	<div id="title"><span class="title">향린교회 주일 출석</span></div>
	<div id="sunday"><span id="before-sunday" class="arrow" onclick="changeSunday(-1)" title="이전 주일">◀</span> <span id="sunday-text"><?=$allDates[0]['full']?></span> <span id="next-sunday" class="arrow" onclick="changeSunday(1)" title="다음 주일">▶</span></div>
	<div id="add-icon" onclick="openMemberAddModal()" title="교인 추가"><img src="images/add-icon.png" title="교인 추가" style="cursor:pointer;"></div>
	<div class="view_select">
		<select id="view_select" name="view_select" onchange="onViewSelectChange()">
			<option value="1" selected>전체 이름순으로 보기</option>
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
	<div id="online">온라인 예배 <input type="text" id="online_num" value="" oninput="onOnlineNumChange(this.value)"> 명</div>
	<div id="mode-holder">
		<div class="mode-icon" id="touch-mode" onclick="modeSet(1)" title="터치스크린 모드"><img src="images/icon-touch.png" title="터치스크린 모드"></div>
		<div class="mode-icon" id="edit-mode" onclick="modeSet(2)" title="편집 모드"><img src="images/icon-edit.png" title="편집 모드"></div>
		<div class="mode-icon" id="lock-mode" onclick="modeSet(3)" title="화면잠금 모드"><img src="images/icon-key.png" title="화면잠금 모드"></div>
	</div>
	<div class="rotate-image" onclick="rotateContainer()" title="화면 회전"><img src="images/vertical-icon.png" title="화면 회전"></div>
	<div class="fixed-image" onclick="enterFullscreen()" title="전체 화면"><img src="images/fullscreen.png" title="전체 화면"></div>
</div>

<div id="container-cover" style="display:none; position:fixed; top:80px; left:0; width:100%; height:100%; background:rgba(255,255,255,0.4); z-index:90;"></div>

<div id="head_sub">
    <span class="head_top">예배실</span>
    <span class="head_sub">정회원 <span id="main_num" class="m_num">103</span><span id="extra_main_num" class="extra_m_num"> (0)</span></span>
    <span class="head_sub">| 준회원 <span id="sub_num" class="m_num">22</span><span id="extra_sub_num" class="extra_m_num"> (0)</span></span>
    <span class="head_sub">| 방문출석 <span id="visit_num" class="m_num">2</span><span id="extra_visit_num" class="extra_m_num"> (0)</span></span>
    <span class="head_sub" style="margin-right:0px;">| 미확인 <span id="unknown_num" class="m_num">19</span></span>
    <span id="unknown-plus" class="known-arrow" onclick="changeUnknown(1)" title="미확인 1명 증가">▲</span>
    <span id="unknown-minus" class="known-arrow" onclick="changeUnknown(-1)" title="미확인 1명 감소">▼</span>
    <span class="head_sub head_sub_total"> [계 <span id="total_num" class="m_num">146</span><span id="extra_sum_num" class="extra_m_num"> (0)</span>]</span>
    <span class="head_top margin_left_10">예배실外</span>
    <span class="head_sub pointer" title="교회학교 교사">교사 <span id="teacher_num" class="m_num">0</span></span>
    <span class="head_sub pointer" title="교회학교 학부모">| 학부모 <span id="parent_num" class="m_num">0</span></span>
    <span class="head_sub pointer" title="교회 프로그램 참여">| 프로그램 <span id="program_num" class="m_num">0</span></span>
    <span class="head_sub pointer" title="봉사 & 업무">| 업무 <span id="service_num" class="m_num">0</span></span>
    <span class="head_sub pointer" title="기타">| 기타 <span id="etc_num" class="m_num">0</span></span>
    <span class="head_sub head_sub_total"> [계 <span id="extra_total_num" class="m_num">0</span>]</span>
    <span class="head_top margin_left_10">교회학교</span>
    <span class="head_sub pointer" title="유아유치부">유 <span id="kinder_num" class="m_num">0</span></span>
    <span class="head_sub pointer" title="어린이부">| 어 <span id="child_num" class="m_num">0</span></span>
    <span class="head_sub pointer" title="청소년부">| 청 <span id="young_num" class="m_num">0</span></span>
    <span class="head_sub head_sub_total"> [계 <span id="school_total_num" class="m_num">0</span>]</span>
    <span class="m_num margin_left_10"> 〈총계 <span id="all_total_num" class="m_num">146</span>〉</span>
</div>

<div id="head_sub2">
    <span id="head_sub2_text">※ 1회 이상 예배 참석자만 표시됩니다. ●=예배실, ○=예배실외(교사, 학부모, 교회프로그램참여, 봉사&업무)</span>
</div>

<!-- ── [교인 정보 입력/수정 모달창] ── -->
<div id="modal-backdrop" onclick="closeMemberModal()"></div>
<div id="input_box">
<form method="post" id="member_form" onsubmit="return false;">
<table class="input_table" id="table_box" border="0" cellpadding="0" cellspacing="0">
<input type="hidden" name="member_no" id="member_no">
<tr class="tr_class">
	<td class="input_title">이름<span class="necessary"> (필수)</span></td>
	<td class="input_td"><input type="text" name="member_name" id="member_name" class="input_box" maxlength="20" style="width:120px;"></td>
</tr>
<tr class="tr_class" id="tr_category">
	<td class="input_title">구분<span class="necessary"> (필수)</span></td>
	<td class="input_td">
		<label class="input_tag2"><input type="radio" name="category" id="category1" value="1" onchange="onCategoryChange('1')"> 정회원</label>
		<label class="input_tag2"><input type="radio" name="category" id="category2" value="2" onchange="onCategoryChange('2')"> 준회원</label>
		<label class="input_tag2"><input type="radio" name="category" id="category3" value="3" onchange="onCategoryChange('3')"> 방문교우</label>
		<label class="input_tag2"><input type="radio" name="category" id="category4" value="4" onchange="onCategoryChange('4')"> 교회학교</label>
		<button type="button" class="reset-btn" onclick="resetSpecificRadio('category', event)">미선택</button>
	</td>
</tr>
<tr class="tr_class">
	<td class="input_title">신도회</td>
	<td class="input_td">
		<label class="input_tag2"><input type="radio" name="age_category" id="age_category1" value="1"> 새날청년회</label>
		<label class="input_tag2"><input type="radio" name="age_category" id="age_category2" value="2"> 청년신도회</label>
		<label class="input_tag2"><input type="radio" name="age_category" id="age_category3" value="3"> 희년청년회</label><br>
		<label class="input_tag2"><input type="radio" name="age_category" id="age_category4" value="4"> 청년여신도회</label>
		<label class="input_tag2"><input type="radio" name="age_category" id="age_category5" value="5"> 청년남신도회</label><br>
		<label class="input_tag2"><input type="radio" name="age_category" id="age_category6" value="6"> 희년여신도회</label>
		<label class="input_tag2"><input type="radio" name="age_category" id="age_category7" value="7"> 희년남신도회</label><br>
		<label class="input_tag2"><input type="radio" name="age_category" id="age_category8" value="8"> 장년여신도회</label>
		<label class="input_tag2"><input type="radio" name="age_category" id="age_category9" value="9"> 장년남신도회</label><br>
		<button type="button" class="reset-btn" onclick="resetSpecificRadio('age_category', event)">미선택</button>
	</td>
</tr>
<tr class="tr_class">
	<td class="input_title">소속</td>
	<td class="input_td">
		<div class="input_td" id="service_list" style="border:0; padding:0;">
			<label class="input_tag2"><input type="radio" name="service_category" id="service_category1" value="1"> 교역자</label>
			<label class="input_tag2"><input type="radio" name="service_category" id="service_category2" value="2"> 직원</label>
			<label class="input_tag2"><input type="radio" name="service_category" id="service_category3" value="3"> 교사</label>
			<label class="input_tag2"><input type="radio" name="service_category" id="service_category4" value="4"> 성가대</label>
			<label class="input_tag2"><input type="radio" name="service_category" id="service_category5" value="5"> 예향</label>
		</div>
		<button type="button" class="reset-btn" onclick="resetSpecificRadio('service_category', event)">미선택</button><br>
		<span class="exp">소속 항목 추가</span> <input type="text" name="service_category_add" id="service_category_add" class="input_box" maxlength="16" style="width:100px;">
		<button type="button" id="service_category_add_button" class="btn-blue" onclick="addServiceCategory()">추가</button>
	</td>
</tr>
<tr class="tr_class">
	<td class="input_title">교인등록일</td>
	<td class="input_td">
		<input type="text" name="register_date" id="register_date" class="input_box" maxlength="8" style="width:100px;"><span class="exp">(예시-20250825)</span><br>
		<span class="exp">연도만 입력시 1월 1일로 등록됨, 월까지 입력시 해당 월 1일로 등록됨</span>
	</td>
</tr>
<tr class="tr_class">
	<td class="input_title">방문일</td>
	<td class="input_td">
		<input type="text" name="visit_date" id="visit_date" class="input_box" maxlength="8" style="width:100px;"><span class="exp">(예시-20250825)</span><br>
		<span class="exp">연도만 입력시 1월 1일로 등록됨, 월까지 입력시 해당 월 1일로 등록됨</span>
	</td>
</tr>
<tr class="tr_class">
	<td class="input_title">메모</td>
	<td class="input_td"><input type="text" name="memo" id="memo" class="input_box" maxlength="80" style="width:460px;"></td>
</tr>
<tr class="tr_class">
	<td class="input_title">비노출</td>
	<td class="input_td"><label for="no_use" class="checkbox-container"><input type="checkbox" name="no_use" id="no_use" value="1"> <span class="exp" style="margin-left:10px">더 이상 일반 명부에 노출 안함</span></label></td>
</tr>
<tr class="tr_class">
	<td class="input_title"><button type="button" id="member_delete_button" onclick="deleteMember()">삭제</button></td>
	<td class="input_td">
		<button type="button" id="member_ok_button" onclick="saveMember()">등록</button>
		<button type="button" id="member_close_button" onclick="closeMemberModal()">창닫기</button>
	</td>
</tr>
</table>
</form>
</div>

<!-- 메인 스크롤 래퍼 -->
<div id="table-wrapper">
    <div id="hidden-box"></div>
    <!-- view_type 5, 6, 7, 9용 테이블 -->
    <div id="grid-view-container">
        <div id="table-header" class="table-row"></div>
        <div id="table-body"></div>
    </div>
    <!-- view_type 1, 2, 3, 4, 8용 타일 뷰 -->
    <div id="tiles-container"></div>
</div>

<!-- ── 반투명 화살표 네비게이션 ── -->
<div id="btn-prev" class="nav-arrow nav-arrow-left hidden" onclick="changePage(-1)" title="최신 날짜(앞선 25주) 보기">
    <svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
    <span class="nav-arrow-text">최신쪽</span>
</div>

<div id="btn-next" class="nav-arrow nav-arrow-right" onclick="changePage(1)" title="과거 날짜(다음 25주) 계속 보기">
    <svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
    <span class="nav-arrow-text">과거쪽</span>
</div>

<script>
var originDate = "2025-08-10";
var allDates = <?=json_encode($allDates)?>;
var allMembers = <?=json_encode($allMembers)?>;
var attMap = <?=json_encode($attMap)?>;
var viewResponses = <?=json_encode($viewResponses)?>;
var memberDetailsMap = <?=json_encode($memberDetailsMap)?>;
var serviceCategories = <?=json_encode($serviceCategories)?>;
var weeklyStatsList = <?=json_encode($weeklyStatsList)?>;

var weeklyStatsMap = {};
for (var i = 0; i < weeklyStatsList.length; i++) {
    weeklyStatsMap[weeklyStatsList[i].date] = weeklyStatsList[i];
}

var curSundayIdx = 0;
var curSunday = allDates[0].full; // 2026-09-27

var pageIndex = 0;
var PAGE_SIZE = 25;
var totalPages = Math.ceil(allDates.length / PAGE_SIZE); // 3 pages: 25 + 25 + 10 = 60 weeks

// ── 상단 주일 이동 (◀, ▶ 화살표) ──
function changeSunday(delta) {
    var newIdx = curSundayIdx - delta; // ◀ 누르면 이전(과거, index 증가), ▶ 누르면 다음(최신, index 감소)
    if (newIdx >= 0 && newIdx < allDates.length) {
        curSundayIdx = newIdx;
        curSunday = allDates[curSundayIdx].full;
        document.getElementById("sunday-text").innerText = curSunday;
        updateSundayStats();
        
        var curView = document.getElementById("view_select").value;
        if (curView === "1" || curView === "2" || curView === "3" || curView === "4" || curView === "8") {
            updateTileAttendanceForCurrentSunday();
        }
    }
}

// 주일 통계 수치 동기화
function updateSundayStats() {
    var stats = weeklyStatsMap[curSunday];
    if (!stats) {
        // 기본 계산
        stats = {
            reg: 0, sub: 0, visit: 0, unknown: 0,
            teacher: 0, parent: 0, prog: 0, serv: 0, etc: 0,
            kinder: 0, child: 0, young: 0, online: 0
        };
    }

    document.getElementById("main_num").innerText = stats.reg || "0";
    document.getElementById("sub_num").innerText = stats.sub || "0";
    document.getElementById("visit_num").innerText = stats.visit || "0";
    document.getElementById("unknown_num").innerText = stats.unknown || "0";
    var totalNum = (parseInt(stats.reg) || 0) + (parseInt(stats.sub) || 0) + (parseInt(stats.visit) || 0) + (parseInt(stats.unknown) || 0);
    document.getElementById("total_num").innerText = totalNum;

    document.getElementById("teacher_num").innerText = stats.teacher || "0";
    document.getElementById("parent_num").innerText = stats.parent || "0";
    document.getElementById("program_num").innerText = stats.prog || "0";
    document.getElementById("service_num").innerText = stats.serv || "0";
    document.getElementById("etc_num").innerText = stats.etc || "0";
    var extraTotal = (parseInt(stats.teacher) || 0) + (parseInt(stats.parent) || 0) + (parseInt(stats.prog) || 0) + (parseInt(stats.serv) || 0) + (parseInt(stats.etc) || 0);
    document.getElementById("extra_total_num").innerText = extraTotal;

    document.getElementById("kinder_num").innerText = stats.kinder || "0";
    document.getElementById("child_num").innerText = stats.child || "0";
    document.getElementById("young_num").innerText = stats.young || "0";
    var schoolTotal = (parseInt(stats.kinder) || 0) + (parseInt(stats.child) || 0) + (parseInt(stats.young) || 0);
    document.getElementById("school_total_num").innerText = schoolTotal;

    document.getElementById("all_total_num").innerText = totalNum + extraTotal + schoolTotal;
    document.getElementById("online_num").value = stats.online || "";
}

// ── 미확인 인원 증감 (▲, ▼ 화살표) ──
function changeUnknown(delta) {
    var unkEl = document.getElementById("unknown_num");
    var cur = parseInt(unkEl.innerText) || 0;
    var nextVal = cur + delta;
    if (nextVal < 0) nextVal = 0;
    unkEl.innerText = nextVal;

    if (!weeklyStatsMap[curSunday]) weeklyStatsMap[curSunday] = {};
    weeklyStatsMap[curSunday].unknown = nextVal;

    var mainVal = parseInt(document.getElementById("main_num").innerText) || 0;
    var subVal = parseInt(document.getElementById("sub_num").innerText) || 0;
    var visitVal = parseInt(document.getElementById("visit_num").innerText) || 0;
    var totalVal = mainVal + subVal + visitVal + nextVal;
    document.getElementById("total_num").innerText = totalVal;

    var extraTotal = parseInt(document.getElementById("extra_total_num").innerText) || 0;
    var schoolTotal = parseInt(document.getElementById("school_total_num").innerText) || 0;
    document.getElementById("all_total_num").innerText = totalVal + extraTotal + schoolTotal;

    $.ajax({
        type: "POST",
        url: "member-unknown-ok.php",
        data: { sunday: curSunday, num: delta },
        dataType: "json"
    });
}

// ── 온라인 예배 인원 입력 연동 ──
function onOnlineNumChange(val) {
    if (!weeklyStatsMap[curSunday]) weeklyStatsMap[curSunday] = {};
    weeklyStatsMap[curSunday].online = val;

    clearTimeout(window._onlineTimer);
    window._onlineTimer = setTimeout(function() {
        $.ajax({
            type: "POST",
            url: "member-online-ok.php",
            data: { sunday: curSunday, num: val },
            dataType: "json"
        });
    }, 400);
}

// ── 타일 뷰의 현재 주일 출석 마크 갱신 ──
function updateTileAttendanceForCurrentSunday() {
    var tiles = document.querySelectorAll("#tiles-container .member-name");
    for (var i = 0; i < tiles.length; i++) {
        var tile = tiles[i];
        var mNo = tile.getAttribute("data-id");
        if (!mNo) continue;
        var key = mNo + "-" + curSunday;
        var attVal = attMap[key];

        tile.classList.remove("bg_color_black", "bg_color_gray");
        if (attVal === 1) {
            tile.classList.add("bg_color_black");
        } else if (attVal >= 2 && attVal <= 6) {
            tile.classList.add("bg_color_gray");
        }
    }
}

// ── 드롭다운 뷰 변경 ──
function onViewSelectChange() {
    var sel = document.getElementById("view_select").value;
    var gridView = document.getElementById("grid-view-container");
    var tilesView = document.getElementById("tiles-container");
    var headSub1 = document.getElementById("head_sub");
    var headSub2 = document.getElementById("head_sub2");
    var subText = document.getElementById("head_sub2_text");
    var tableWrapper = document.getElementById("table-wrapper");

    if (sel === "5" || sel === "6") {
        headSub1.style.display = "none";
        headSub2.style.display = "flex";
        subText.innerHTML = "※ 1회 이상 예배 참석자만 표시됩니다. ●=예배실, ○=예배실외(교사, 학부모, 교회프로그램참여, 봉사&업무) &nbsp;(출석률은 화면에 보이는 25주의 출석률임)";
        tableWrapper.style.top = "75px";
        gridView.style.display = "block";
        tilesView.style.display = "none";
    } else if (sel === "7" || sel === "8") {
        headSub1.style.display = "none";
        headSub2.style.display = "flex";
        subText.innerHTML = "&nbsp;";
        tableWrapper.style.top = "75px";
        if (sel === "7") {
            gridView.style.display = "block";
            tilesView.style.display = "none";
        } else {
            gridView.style.display = "none";
            tilesView.style.display = "block";
        }
    } else if (sel === "9") {
        var countOneThird = 0;
        var countTwoThirds = 0;
        var recent25 = allDates.slice(0, 25);
        for (var i = 0; i < allMembers.length; i++) {
            var mNo = allMembers[i].no;
            var attCount = 0;
            for (var j = 0; j < recent25.length; j++) {
                var d = recent25[j].full;
                var key = mNo + "-" + d;
                var val = attMap[key];
                if (val && (val >= 1 && val <= 6)) {
                    attCount++;
                }
            }
            var rate = attCount / 25.0;
            if (rate >= (1.0 / 3.0)) {
                countOneThird++;
            }
            if (rate >= (2.0 / 3.0)) {
                countTwoThirds++;
            }
        }

        headSub1.style.display = "none";
        headSub2.style.display = "flex";
        subText.innerHTML = "※ 최근 25주(2026.04.12 - 2026.09.27) 출석률 30% 미만 교인 중 직전 25주 대비 가장 많이 낮아진 교인 25명 (비노출 교인 제외) " +
                            "<span style='color: #ffffff; font-weight: bold; margin-left: 12px; text-shadow: 0 1px 2px rgba(0,0,0,0.25);'>[최근 25주 출석률 33% 이상인 교인수 " + countOneThird + "명, 66% 이상인 교인수 " + countTwoThirds + "명]</span>";
        tableWrapper.style.top = "75px";
        gridView.style.display = "block";
        tilesView.style.display = "none";
    } else {
        headSub1.style.display = "flex";
        headSub2.style.display = "none";
        tableWrapper.style.top = "80px";
        gridView.style.display = "none";
        tilesView.style.display = "block";
    }

    tableWrapper.scrollTop = 0;
    var cover = document.getElementById("container-cover");
    if (cover && mode === 3) {
        cover.style.top = tableWrapper.style.top;
        cover.style.display = "block";
    }
    renderTable();
}

// ── 헤더 우측 5개 버튼 원래 기능 구현 ──
var mode = 1; // 1: 터치스크린 모드, 2: 편집 모드, 3: 화면잠금 모드

function modeSet(m) {
    mode = m;
    var cover = document.getElementById("container-cover");
    var touch = document.getElementById("touch-mode");
    var edit = document.getElementById("edit-mode");
    var lock = document.getElementById("lock-mode");

    if (cover) cover.style.display = "none";
    if (touch) touch.style.backgroundColor = "#ddd";
    if (edit) edit.style.backgroundColor = "#ddd";
    if (lock) lock.style.backgroundColor = "#ddd";

    if (mode === 1) {
        if (touch) touch.style.backgroundColor = "#33ffff";
    } else if (mode === 2) {
        if (edit) edit.style.backgroundColor = "#33ffff";
    } else if (mode === 3) {
        if (lock) lock.style.backgroundColor = "#33ffff";
        if (cover) {
            var currentView = document.getElementById("view_select").value;
            if (currentView === "1" || currentView === "2" || currentView === "3" || currentView === "4") {
                cover.style.top = "80px";
            } else {
                cover.style.top = "75px";
            }
            cover.style.display = "block";
        }
    }
}

// Shift 키 누름/뗌 시 터치(1) <-> 편집(2) 모드 전환
var isShiftPressed = false;
document.addEventListener("keydown", function(e) {
    if (e.shiftKey && !isShiftPressed) {
        isShiftPressed = true;
        if (mode === 1) modeSet(2);
        else if (mode === 2) modeSet(1);
    }
});
document.addEventListener("keyup", function(e) {
    if (e.key === "Shift") {
        isShiftPressed = false;
    }
});

// ── [교인 정보 입력 및 수정 모달 기능 완벽 구현] ──
function openMemberAddModal() {
    document.getElementById("member_form").reset();
    document.getElementById("member_no").value = "";
    document.getElementById("member_delete_button").style.display = "none";
    document.getElementById("member_ok_button").innerText = "등록";
    removeInsertRows();
    document.getElementById("modal-backdrop").style.display = "block";
    document.getElementById("input_box").style.display = "block";
}

function openMemberEditModal(memberNo) {
    var member = memberDetailsMap[memberNo];
    if (!member) {
        for (var i = 0; i < allMembers.length; i++) {
            if (String(allMembers[i].no) === String(memberNo)) {
                member = allMembers[i];
                break;
            }
        }
    }
    if (!member) return;

    document.getElementById("member_form").reset();
    removeInsertRows();

    document.getElementById("member_no").value = member.no || memberNo;
    document.getElementById("member_name").value = member.name || "";

    // 구분 라디오 설정
    var catVal = String(member.category || "1");
    var catRadio = document.querySelector('input[name="category"][value="' + catVal + '"]');
    if (catRadio) catRadio.checked = true;
    onCategoryChange(catVal);

    // 서브카테고리 라디오 설정
    var subVal = String(member.subcategory || "");
    if (subVal) {
        var subRadio = document.querySelector('input[name="subcategory"][value="' + subVal + '"]');
        if (subRadio) subRadio.checked = true;
    }

    // 신도회 라디오 설정
    var ageVal = String(member.age_category || "");
    if (ageVal && ageVal !== "0") {
        var ageRadio = document.querySelector('input[name="age_category"][value="' + ageVal + '"]');
        if (ageRadio) ageRadio.checked = true;
    }

    // 소속 라디오 설정
    var srvVal = String(member.service_category || "");
    if (srvVal && srvVal !== "0") {
        var srvRadio = document.querySelector('input[name="service_category"][value="' + srvVal + '"]');
        if (srvRadio) srvRadio.checked = true;
    }

    // 날짜 및 메모
    var regDate = member.register_date ? String(member.register_date).replace(/-/g, "") : "";
    document.getElementById("register_date").value = regDate;

    var visDate = member.visit_date ? String(member.visit_date).replace(/-/g, "") : "";
    document.getElementById("visit_date").value = visDate;

    document.getElementById("memo").value = member.memo || "";
    document.getElementById("no_use").checked = (String(member.no_use) === "1");

    document.getElementById("member_delete_button").style.display = "inline-block";
    document.getElementById("member_ok_button").innerText = "수정";

    document.getElementById("modal-backdrop").style.display = "block";
    document.getElementById("input_box").style.display = "block";
}

function closeMemberModal() {
    document.getElementById("modal-backdrop").style.display = "none";
    document.getElementById("input_box").style.display = "none";
    document.getElementById("member_form").reset();
    removeInsertRows();
}

function removeInsertRows() {
    var r1 = document.getElementById("insert_tr1");
    var r2 = document.getElementById("insert_tr2");
    var r3 = document.getElementById("insert_tr3");
    if (r1) r1.remove();
    if (r2) r2.remove();
    if (r3) r3.remove();
}

function onCategoryChange(catVal) {
    removeInsertRows();
    var trCategory = document.getElementById("tr_category");

    if (catVal === "2") {
        // 준회원 서브카테고리 행
        var tr = document.createElement("tr");
        tr.className = "tr_class";
        tr.id = "insert_tr1";
        tr.innerHTML = '<td class="input_td" colspan="2" style="padding-left:95px;">' +
            '<label class="input_tag2"><input type="radio" name="subcategory" id="subcategory1" value="1">미세례</label>' +
            '<label class="input_tag2"><input type="radio" name="subcategory" id="subcategory2" value="2">유아세례</label>' +
            '<label class="input_tag2"><input type="radio" name="subcategory" id="subcategory3" value="3">미가입식</label>' +
            '<label class="input_tag2"><input type="radio" name="subcategory" id="subcategory4" value="4">병역</label>' +
            '<label class="input_tag2"><input type="radio" name="subcategory" id="subcategory5" value="5">해외</label>' +
            '<label class="input_tag2"><input type="radio" name="subcategory" id="subcategory6" value="6">지방</label>' +
            '<label class="input_tag2"><input type="radio" name="subcategory" id="subcategory7" value="7">교우</label>' +
            '<button type="button" class="reset-btn" onclick="resetSpecificRadio(\'subcategory\', event)">미선택</button>' +
            '</td>';
        trCategory.after(tr);
        document.getElementById("visit_date").value = "";
    } else if (catVal === "3") {
        // 방문교우 연도 선택 행
        var tr = document.createElement("tr");
        tr.className = "tr_class";
        tr.id = "insert_tr2";
        tr.innerHTML = '<td class="input_td" colspan="2" style="padding-left:95px;">' +
            '<label class="input_tag2"><input type="radio" name="subcategory" id="subcategory10" value="10">2023년</label>' +
            '<label class="input_tag2"><input type="radio" name="subcategory" id="subcategory11" value="11">2024년</label>' +
            '<label class="input_tag2"><input type="radio" name="subcategory" id="subcategory12" value="12">2025년</label>' +
            '<label class="input_tag2"><input type="radio" name="subcategory" id="subcategory13" value="13" checked>2026년</label>' +
            '<label class="input_tag2"><input type="radio" name="subcategory" id="subcategory14" value="14">2027년</label>' +
            '<label class="input_tag2"><input type="radio" name="subcategory" id="subcategory15" value="15">2028년</label>' +
            '<button type="button" class="reset-btn" onclick="resetSpecificRadio(\'subcategory\', event)">미선택</button>' +
            '</td>';
        trCategory.after(tr);
        document.getElementById("visit_date").value = curSunday.replace(/-/g, "");
    } else if (catVal === "4") {
        // 교회학교 부서 선택 행
        var tr = document.createElement("tr");
        tr.className = "tr_class";
        tr.id = "insert_tr3";
        tr.innerHTML = '<td class="input_td" colspan="2" style="padding-left:95px;">' +
            '<label class="input_tag2"><input type="radio" name="subcategory" id="subcategory31" value="31">유아유치부</label>' +
            '<label class="input_tag2"><input type="radio" name="subcategory" id="subcategory32" value="32">어린이부</label>' +
            '<label class="input_tag2"><input type="radio" name="subcategory" id="subcategory33" value="33">청소년부</label>' +
            '<button type="button" class="reset-btn" onclick="resetSpecificRadio(\'subcategory\', event)">미선택</button>' +
            '</td>';
        trCategory.after(tr);
        document.getElementById("visit_date").value = "";
    } else {
        document.getElementById("visit_date").value = "";
    }
}

function resetSpecificRadio(groupName, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    var radios = document.querySelectorAll('input[type="radio"][name="' + groupName + '"]');
    for (var i = 0; i < radios.length; i++) {
        radios[i].checked = false;
    }
    if (groupName === "category") {
        removeInsertRows();
        document.getElementById("visit_date").value = "";
    }
}

function addServiceCategory() {
    var txt = document.getElementById("service_category_add").value.trim();
    if (!txt) {
        alert("추가할 소속명을 입력해주세요.");
        return;
    }
    var newNo = String(Date.now());
    var sList = document.getElementById("service_list");
    var lbl = document.createElement("label");
    lbl.className = "input_tag2";
    lbl.innerHTML = '<input type="radio" name="service_category" id="service_category' + newNo + '" value="' + newNo + '" checked> ' + txt;
    sList.appendChild(lbl);
    document.getElementById("service_category_add").value = "";
    $.ajax({
        type: "POST",
        url: "member-service-add-ok.php",
        data: { title: txt },
        dataType: "json"
    });
    alert("'" + txt + "' 소속 항목이 추가되었습니다.");
}

function saveMember() {
    var name = document.getElementById("member_name").value.trim();
    if (!name) {
        alert("이름을 입력해 주십시요.");
        return;
    }
    var catRadio = document.querySelector('input[name="category"]:checked');
    if (!catRadio) {
        alert("구분을 선택해 주십시요.");
        return;
    }

    var memberNo = document.getElementById("member_no").value;
    var catVal = catRadio.value;
    var subRadio = document.querySelector('input[name="subcategory"]:checked');
    var subVal = subRadio ? subRadio.value : "0";
    var ageRadio = document.querySelector('input[name="age_category"]:checked');
    var ageVal = ageRadio ? ageRadio.value : "0";
    var srvRadio = document.querySelector('input[name="service_category"]:checked');
    var srvVal = srvRadio ? srvRadio.value : "0";
    var regDate = document.getElementById("register_date").value.trim();
    var visDate = document.getElementById("visit_date").value.trim();
    var memo = document.getElementById("memo").value.trim();
    var noUse = document.getElementById("no_use").checked ? 1 : 0;

    var cateBadge = "정";
    var cateColor = "darkred";
    if (catVal === "2") { cateBadge = "준"; cateColor = "darkgreen"; }
    else if (catVal === "3") { cateBadge = "방"; cateColor = "darkgray"; }
    else if (catVal === "4") { cateBadge = "교"; cateColor = "darkgreen"; }

    if (!memberNo) {
        // 신규 교인 등록
        var newNo = String(Date.now());
        var newMember = {
            no: newNo,
            name: name,
            category: catVal,
            subcategory: subVal,
            age_category: ageVal,
            service_category: srvVal,
            register_date: regDate,
            visit_date: visDate,
            memo: memo,
            no_use: noUse,
            cate: cateBadge,
            color: cateColor
        };
        memberDetailsMap[newNo] = newMember;
        allMembers.unshift(newMember);

        // 타일 컨테이너 최상단에 추가
        var tileCont = document.getElementById("tiles-container");
        var newTile = document.createElement("div");
        newTile.className = "member-name bg_color_new";
        newTile.setAttribute("data-id", newNo);
        newTile.style.height = memberBoxHeight + "px";
        newTile.innerHTML = '<span class="last-name">' + name.substring(0, 1) + '</span>' + name.substring(1);
        newTile.onclick = function() { handleMemberTileClick(this); };
        tileCont.prepend(newTile);

        var formData = $("#member_form").serialize();
        $.ajax({
            type: "POST",
            url: "member-ok.php",
            data: formData,
            dataType: "json"
        });
        alert("교인 '" + name + "' 님이 새로 등록되었습니다.");
    } else {
        // 기존 교인 수정
        var mObj = memberDetailsMap[memberNo] || {};
        mObj.name = name;
        mObj.category = catVal;
        mObj.subcategory = subVal;
        mObj.age_category = ageVal;
        mObj.service_category = srvVal;
        mObj.register_date = regDate;
        mObj.visit_date = visDate;
        mObj.memo = memo;
        mObj.no_use = noUse;
        mObj.cate = cateBadge;
        mObj.color = cateColor;
        memberDetailsMap[memberNo] = mObj;

        for (var i = 0; i < allMembers.length; i++) {
            if (String(allMembers[i].no) === String(memberNo)) {
                allMembers[i].name = name;
                allMembers[i].cate = cateBadge;
                allMembers[i].color = cateColor;
                allMembers[i].no_use = noUse;
                break;
            }
        }

        // 타일 DOM 갱신
        var targetTile = document.querySelector('.member-name[data-id="' + memberNo + '"]');
        if (targetTile) {
            if (noUse === 1 && document.getElementById("view_select").value !== "8") {
                targetTile.remove();
            } else {
                targetTile.innerHTML = '<span class="last-name">' + name.substring(0, 1) + '</span>' + name.substring(1);
            }
        }

        var formData = $("#member_form").serialize();
        $.ajax({
            type: "POST",
            url: "member-ok.php",
            data: formData,
            dataType: "json"
        });
        alert("'" + name + "' 교인 정보가 수정되었습니다.");
    }

    closeMemberModal();
}

function deleteMember() {
    var memberNo = document.getElementById("member_no").value;
    var name = document.getElementById("member_name").value;
    if (!memberNo) return;

    if (confirm(name + " 님을 삭제하시겠습니까?")) {
        delete memberDetailsMap[memberNo];
        for (var i = 0; i < allMembers.length; i++) {
            if (String(allMembers[i].no) === String(memberNo)) {
                allMembers.splice(i, 1);
                break;
            }
        }
        var targetTile = document.querySelector('.member-name[data-id="' + memberNo + '"]');
        if (targetTile) targetTile.remove();

        $.ajax({
            type: "POST",
            url: "member-delete.php",
            data: { no: memberNo },
            dataType: "json"
        });
        closeMemberModal();
        alert("삭제되었습니다.");
    }
}

// ── 타일 클릭 핸들러 (모드별 분기) ──
function handleMemberTileClick(el) {
    var memberNo = el.getAttribute("data-id");
    if (!memberNo) return;

    if (mode === 1) {
        // 터치스크린 모드: 출석 토글
        var key = memberNo + "-" + curSunday;
        var curVal = attMap[key];
        var mainEl = document.getElementById("main_num");
        var curMain = parseInt(mainEl.innerText) || 0;
        var isChecked = false;

        if (curVal === 1) {
            delete attMap[key];
            el.classList.remove("bg_color_black");
            el.classList.add("bg_color_category_1");
            if (curMain > 0) curMain--;
            isChecked = false;
        } else {
            attMap[key] = 1;
            el.classList.add("bg_color_black");
            el.classList.remove("bg_color_category_1");
            curMain++;
            isChecked = true;
        }
        mainEl.innerText = curMain;
        changeUnknown(0); // 합계 재계산

        // 원본 기능: 전체화면에 이름이 크게 표시되었다가 서서히 사라짐
        var member = memberDetailsMap[memberNo];
        var memberName = (member && member.name) ? member.name : el.textContent.replace(/\s+/g, "");
        showBigNameOverlay(memberName, isChecked);

        // 실시간 서버 DB 반영 AJAX
        $.ajax({
            type: "POST",
            url: "member-attendance-ok.php",
            data: { memberNo: memberNo, sunday: curSunday },
            dataType: "json",
            success: function(res) {
                if (res && res.main_num !== undefined) {
                    if (!weeklyStatsMap[curSunday]) weeklyStatsMap[curSunday] = {};
                    weeklyStatsMap[curSunday].reg = res.main_num;
                    weeklyStatsMap[curSunday].sub = res.sub_num;
                    weeklyStatsMap[curSunday].visit = res.visit_num;
                    updateSundayStats();
                }
            }
        });
    } else if (mode === 2) {
        // 편집 모드: 교인 정보 수정 모달 열림!
        openMemberEditModal(memberNo);
    }
}

// ── 출석 토글시 전체화면 대형 이름 오버레이 (원본 hidden-box 완벽 재현) ──
function showBigNameOverlay(name, isChecked) {
    var hiddenBox = document.getElementById("hidden-box");
    var wrapper = document.getElementById("table-wrapper");
    if (!hiddenBox || !wrapper) return;

    name = (name || "").replace(/\s+/g, "");
    hiddenBox.innerText = name;
    hiddenBox.style.display = "flex";
    hiddenBox.style.opacity = "1";
    hiddenBox.style.transition = "";

    var scrollTop = wrapper.scrollTop || 0;
    hiddenBox.style.top = scrollTop + "px";
    hiddenBox.style.left = "0px";
    hiddenBox.style.width = wrapper.clientWidth + "px";
    hiddenBox.style.height = wrapper.clientHeight + "px";

    var isRot = wrapper.classList.contains("rotated");
    var baseMaxRem = isRot ? 14 : (isFullScreen() ? 14 : 11);
    var nameLen = Math.max(name.length, 2);
    // 한 줄에 여유 있게 완전히 들어가도록 글자수와 컨테이너 너비에 맞게 계산
    var maxRemByWidth = (wrapper.clientWidth * 0.72) / (nameLen * 18);
    var finalRem = Math.min(baseMaxRem, Math.max(4.5, maxRemByWidth));
    hiddenBox.style.fontSize = finalRem.toFixed(1) + "rem";

    var duration = isChecked ? 1000 : 300;
    if (hiddenBox._fadeTimer) clearTimeout(hiddenBox._fadeTimer);
    if (hiddenBox._hideTimer) clearTimeout(hiddenBox._hideTimer);

    hiddenBox._fadeTimer = setTimeout(function() {
        hiddenBox.style.transition = "opacity " + (duration / 1000) + "s ease-out";
        hiddenBox.style.opacity = "0";
        hiddenBox._hideTimer = setTimeout(function() {
            hiddenBox.style.display = "none";
            hiddenBox.style.transition = "";
        }, duration);
    }, 150);
}

// ── 전체화면 및 리사이징 로직 ──
function isFullScreen() {
    return !!(
        document.fullscreenElement ||
        document.webkitFullscreenElement ||
        document.mozFullScreenElement ||
        document.msFullscreenElement
    );
}

var total_box_num = 633;
var memberBoxHeight = 40;

function fullScreenBoxHeightSet() {
    var currentView = document.getElementById("view_select").value;
    var container = document.getElementById("table-wrapper");
    if (!container) return;

    var screenHeight = window.innerHeight;
    var screenWidth = window.innerWidth;
    var isRot = container.classList.contains("rotated");

    if (currentView === "7") {
        var rows = document.querySelectorAll("#table-body .table-row");
        if (rows.length > 0) {
            var topOffset = container.offsetTop || 75;
            var availHeight = screenHeight - topOffset - 55;
            var rowH = Math.floor(availHeight / rows.length);
            if (rowH > 40) {
                for (var r = 0; r < rows.length; r++) {
                    rows[r].style.height = rowH + "px";
                    var cols = rows[r].children;
                    for (var c = 0; c < cols.length; c++) {
                        cols[c].style.height = rowH + "px";
                    }
                }
            }
        }
        return;
    }

    if (currentView !== "1" && currentView !== "2" && currentView !== "3" && currentView !== "4" && currentView !== "8") {
        return;
    }

    var memberElements = document.querySelectorAll("#tiles-container .member-name, #tiles-container .cate");
    var totalBoxes = total_box_num || memberElements.length || 633;

    if (!isRot) {
        var totalRows = Math.ceil(totalBoxes / 25);
        var containerHeight = screenHeight - (container.offsetTop || 80);
        var newBoxHeight = containerHeight / totalRows;
        memberBoxHeight = newBoxHeight;
        if (newBoxHeight > 80) newBoxHeight = 80;

        for (var i = 0; i < memberElements.length; i++) {
            memberElements[i].style.height = newBoxHeight + "px";
            memberElements[i].style.width = "4%";
        }
    } else {
        var totalRows = Math.ceil(totalBoxes / 14);
        var containerHeight = screenWidth;
        var newBoxHeight = containerHeight / totalRows;
        memberBoxHeight = newBoxHeight;
        if (newBoxHeight > 80) newBoxHeight = 80;

        for (var i = 0; i < memberElements.length; i++) {
            memberElements[i].style.height = newBoxHeight + "px";
            memberElements[i].style.width = "7.1428%";
        }
    }
}

function enterFullscreen() {
    if (isFullScreen()) {
        var container = document.getElementById("table-wrapper");
        if (container && container.classList.contains("rotated")) {
            rotateContainer();
        }
        exitFullscreen();
    } else {
        var elem = document.documentElement;
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) {
            elem.msRequestFullscreen();
        } else if (elem.mozRequestFullScreen) {
            elem.mozRequestFullScreen();
        }
    }
}

function exitFullscreen() {
    if (document.exitFullscreen) {
        document.exitFullscreen();
    } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen();
    } else if (document.msExitFullscreen) {
        document.msExitFullscreen();
    } else if (document.mozCancelFullScreen) {
        document.mozCancelFullScreen();
    }
}

function resetFullscreenState() {
    var container = document.getElementById("table-wrapper");
    if (!container) return;

    container.style.transform = "none";
    container.style.width = "100%";
    container.style.height = "auto";
    container.style.overflowY = "scroll";
    container.style.overflowX = "hidden";
    container.classList.remove("rotated");

    var currentView = document.getElementById("view_select").value;
    if (currentView === "1" || currentView === "2" || currentView === "3" || currentView === "4") {
        container.style.top = "80px";
    } else {
        container.style.top = "75px";
    }

    if (currentView === "7") {
        var rows = document.querySelectorAll("#table-body .table-row");
        for (var r = 0; r < rows.length; r++) {
            rows[r].style.height = "40px";
            var cols = rows[r].children;
            for (var c = 0; c < cols.length; c++) {
                cols[c].style.height = "40px";
            }
        }
        return;
    }

    var memberElements = document.querySelectorAll("#tiles-container .member-name, #tiles-container .cate");
    for (var i = 0; i < memberElements.length; i++) {
        memberElements[i].style.height = "40px";
        memberElements[i].style.width = "4%";
    }
}

function rotateContainer() {
    if (isFullScreen()) {
        var container = document.getElementById("table-wrapper");
        if (!container) return;
        var screenWidth = window.innerWidth;
        var topOffset = container.offsetTop || 75;
        var screenHeight = window.innerHeight - topOffset;

        if (!container.classList.contains("rotated")) {
            var translateX = (screenWidth - screenHeight) / 2;
            var translateY = (screenHeight - screenWidth) / 2;

            container.style.transformOrigin = "50% 50%";
            container.style.transform = "translate(" + translateX + "px, " + translateY + "px) rotate(-90deg)";
            container.style.width = screenHeight + "px";
            container.style.height = screenWidth + "px";
            container.style.overflow = "hidden";
            container.classList.add("rotated");
        } else {
            container.style.transform = "none";
            container.style.width = "100%";
            container.style.height = (window.innerHeight - topOffset) + "px";
            container.style.overflow = "hidden";
            container.classList.remove("rotated");
        }
        fullScreenBoxHeightSet();
    } else {
        alert("전체 화면에서 회전할 수 있습니다.");
    }
}

function onFullscreenChange() {
    var container = document.getElementById("table-wrapper");
    if (!container) return;

    if (isFullScreen()) {
        container.style.overflow = "hidden";
        fullScreenBoxHeightSet();
    } else {
        container.style.overflow = "scroll";
        resetFullscreenState();
    }
}

document.addEventListener("fullscreenchange", onFullscreenChange);
document.addEventListener("webkitfullscreenchange", onFullscreenChange);
document.addEventListener("mozfullscreenchange", onFullscreenChange);
document.addEventListener("MSFullscreenChange", onFullscreenChange);

window.addEventListener("resize", function() {
    if (isFullScreen()) {
        fullScreenBoxHeightSet();
    }
});

// ── 메인 테이블 / 타일 렌더러 ──
function renderTable() {
    var currentView = document.getElementById("view_select").value;
    var btnPrev = document.getElementById("btn-prev");
    var btnNext = document.getElementById("btn-next");

    if (currentView === "1" || currentView === "2" || currentView === "3" || currentView === "4" || currentView === "8") {
        document.getElementById("grid-view-container").style.display = "none";
        document.getElementById("tiles-container").style.display = "block";
        btnPrev.classList.add("hidden");
        btnNext.classList.add("hidden");
        
        var resp = viewResponses[currentView];
        if (resp) {
            var parts = resp.split("@@@");
            document.getElementById("tiles-container").innerHTML = parts[0];
            total_box_num = parseInt(parts[1]) || 0;
            if (!total_box_num) {
                total_box_num = document.querySelectorAll("#tiles-container .member-name, #tiles-container .cate").length;
            }
            
            var tileElems = document.querySelectorAll("#tiles-container .member-name");
            for (var t = 0; t < tileElems.length; t++) {
                tileElems[t].onclick = function() { handleMemberTileClick(this); };
            }
            updateTileAttendanceForCurrentSunday();
            if (isFullScreen()) {
                fullScreenBoxHeightSet();
            }
        } else {
            document.getElementById("tiles-container").innerHTML = '<div style="padding:40px; text-align:center; font-size:1.2rem; color:#666;">불러오는 중...</div>';
            $.ajax({
                url: "member-list-get-ajax.php",
                data: { view_type: currentView, sunday: curSunday },
                dataType: "json",
                success: function(htmlRes) {
                    viewResponses[currentView] = htmlRes;
                    var parts = htmlRes.split("@@@");
                    document.getElementById("tiles-container").innerHTML = parts[0];
                    total_box_num = parseInt(parts[1]) || 0;
                    if (!total_box_num) {
                        total_box_num = document.querySelectorAll("#tiles-container .member-name, #tiles-container .cate").length;
                    }
                    var tileElems = document.querySelectorAll("#tiles-container .member-name");
                    for (var t = 0; t < tileElems.length; t++) {
                        tileElems[t].onclick = function() { handleMemberTileClick(this); };
                    }
                    updateTileAttendanceForCurrentSunday();
                    if (isFullScreen()) {
                        fullScreenBoxHeightSet();
                    }
                }
            });
        }
        return;
    } else {
        document.getElementById("grid-view-container").style.display = "block";
        document.getElementById("tiles-container").style.display = "none";
    }

    // ── [1. 주일별 출석 현황] ──
    if (currentView === "7") {
        btnPrev.classList.add("hidden");
        btnNext.classList.add("hidden");

        var headHtml = '<div class="col col-head col-week-date">날짜</div>' +
                       '<div class="col col-head col-week-title-title">총계</div>' +
                       '<div class="col col-head col-week-title-title">예배실</div>' +
                       '<div class="col col-head col-week-title">정회원</div>' +
                       '<div class="col col-head col-week-title">준회원</div>' +
                       '<div class="col col-head col-week-title">방문출석</div>' +
                       '<div class="col col-head col-week-title-short">미확인</div>' +
                       '<div class="col col-head col-week-title-short">예배실外</div>' +
                       '<div class="col col-head col-week-title-short">교사</div>' +
                       '<div class="col col-head col-week-title-short">학부모</div>' +
                       '<div class="col col-head col-week-title-short">프로그램</div>' +
                       '<div class="col col-head col-week-title-short">업무</div>' +
                       '<div class="col-head col-week-title-short col">기타</div>' +
                       '<div class="col col-head col-week-title-short">교회학교</div>' +
                       '<div class="col col-head col-week-title-short">유아유치</div>' +
                       '<div class="col col-head col-week-title-short">어린이부</div>' +
                       '<div class="col col-head col-week-title-short">청소년부</div>' +
                       '<div class="col col-head col-week-title-short">온라인</div>';
        document.getElementById("table-header").innerHTML = headHtml;

        var weeklyDetailed = weeklyStatsList;
        var bodyHtml = "";
        for (var i = 0; i < weeklyDetailed.length; i++) {
            var ws = weeklyDetailed[i];
            var allTotal = parseInt(ws.total) + parseInt(ws.unknown);
            var totalNum = parseInt(ws.total) + parseInt(ws.unknown);
            var extraTotal = parseInt(ws.outside);

            var row = '<div class="table-row" style="height:40px;">';
            row += '<div class="col col-week-date">' + ws.date + '</div>';
            row += '<div class="col col-week-title-long col-all">' + allTotal + '<br><div class="graph" style="width:' + allTotal + 'px;"></div></div>';
            row += '<div class="col col-week-title-long col-sub">' + totalNum + '<br><div class="graph" style="width:' + totalNum + 'px;"></div></div>';
            row += '<div class="col col-week-title">' + ws.reg + '</div>';
            row += '<div class="col col-week-title">' + ws.sub + '</div>';
            row += '<div class="col col-week-title">' + ws.visit + '</div>';
            row += '<div class="col col-week-title-short">' + ws.unknown + '</div>';
            row += '<div class="col col-week-title-short col-sub">' + extraTotal + '</div>';
            row += '<div class="col col-week-title-short">' + ws.teacher + '</div>';
            row += '<div class="col col-week-title-short">' + ws.parent + '</div>';
            row += '<div class="col col-week-title-short">' + ws.prog + '</div>';
            row += '<div class="col col-week-title-short">' + ws.serv + '</div>';
            row += '<div class="col col-week-title-short">' + ws.etc + '</div>';
            var schoolTotal = parseInt(ws.kinder) + parseInt(ws.child) + parseInt(ws.young);
            row += '<div class="col col-week-title-short col-online">' + schoolTotal + '</div>';
            row += '<div class="col col-week-title-short col-school">' + ws.kinder + '</div>';
            row += '<div class="col col-week-title-short col-school">' + ws.child + '</div>';
            row += '<div class="col col-week-title-short col-school">' + ws.young + '</div>';
            row += '<div class="col col-week-title-short col-online">' + ws.online + '</div>';
            row += '</div>';
            bodyHtml += row;
        }
        document.getElementById("table-body").innerHTML = bodyHtml;
        return;
    }

    // ── [2. 교인별 출석 (5, 6, 9)] ──
    var startIdx = pageIndex * PAGE_SIZE;
    var endIdx = Math.min(startIdx + PAGE_SIZE, allDates.length);
    var pageDates = allDates.slice(startIdx, endIdx);

    var rateTitle = "출석률";
    var rateTooltip = "클릭하여 출석률순/가나다순 전환";
    var rateClick = 'onclick="toggleSortMode()"';
    if (currentView === "6") {
        rateTitle = "출석률 ▼";
    } else if (currentView === "9") {
        rateTitle = "감소폭";
        rateTooltip = "출석률 감소폭";
        rateClick = "";
    }

    var headerHtml = '<div class="col-head-name">교인명</div>' +
                     '<div class="col-head-rate" title="' + rateTooltip + '" ' + rateClick + '>' + rateTitle + '</div>';
    
    for (var i = 0; i < pageDates.length; i++) {
        var d = pageDates[i];
        var cls = "col-head-date";
        var extraAttr = "";
        if (pageIndex === 0 && i === 0) {
            cls += " latest-first";
            extraAttr = ' title="최신 주일 (가장 최근)"';
        } else if (d.is_xmas) {
            cls += " xmas";
            extraAttr = ' title="성탄절 예배"';
        } else if (d.full === originDate) {
            cls += " origin";
            extraAttr = ' title="출석 기록 시작일 (2025.08.10)"';
        }
        headerHtml += '<div class="' + cls + '"' + extraAttr + '>' + d.short + '</div>';
    }
    document.getElementById("table-header").innerHTML = headerHtml;

    // 멤버 데이터 가공
    var displayMembers = [];
    var recent25 = allDates.slice(0, 25);
    var prior25 = allDates.slice(25, 50);

    for (var i = 0; i < allMembers.length; i++) {
        var m = allMembers[i];
        if (m.no_use === 1) continue; // 비노출 교인 제외

        var attCount = 0;
        for (var j = 0; j < pageDates.length; j++) {
            var d = pageDates[j].full;
            var key = m.no + "-" + d;
            var val = attMap[key];
            if (val && (val >= 1 && val <= 6)) {
                attCount++;
            }
        }
        var rate = Math.round((attCount / pageDates.length) * 100);

        var pRecent = 0;
        for (var j = 0; j < recent25.length; j++) {
            var key = m.no + "-" + recent25[j].full;
            var val = attMap[key];
            if (val && (val >= 1 && val <= 6)) pRecent++;
        }
        var rateRecent = (pRecent / 25.0) * 100.0;

        var pPrior = 0;
        for (var j = 0; j < prior25.length; j++) {
            var key = m.no + "-" + prior25[j].full;
            var val = attMap[key];
            if (val && (val >= 1 && val <= 6)) pPrior++;
        }
        var ratePrior = (pPrior / 25.0) * 100.0;
        var diff = ratePrior - rateRecent;

        displayMembers.push({
            no: m.no,
            name: m.name,
            cate: m.cate,
            color: m.color,
            attCount: attCount,
            rate: rate,
            rateRecent: rateRecent,
            ratePrior: ratePrior,
            diff: diff
        });
    }

    // 정렬
    if (currentView === "6") {
        displayMembers.sort(function(a, b) {
            if (b.rate !== a.rate) return b.rate - a.rate;
            return a.name.localeCompare(b.name, "ko");
        });
    } else if (currentView === "9") {
        var filtered = displayMembers.filter(function(m) {
            return m.rateRecent < 30.0;
        });
        filtered.sort(function(a, b) {
            if (b.diff !== a.diff) return b.diff - a.diff;
            return a.name.localeCompare(b.name, "ko");
        });
        displayMembers = filtered.slice(0, 25);
    } else {
        displayMembers.sort(function(a, b) {
            return a.name.localeCompare(b.name, "ko");
        });
    }

    var bodyHtml = "";
    for (var i = 0; i < displayMembers.length; i++) {
        var m = displayMembers[i];
        var row = '<div class="table-row">';
        row += '<div class="col-cell-name" title="' + m.name + ' (' + m.cate + '회원)"><span style="color:#64748b; font-size:0.85rem; margin-right:4px;">' + (i + 1) + '.</span> ' + m.name + ' <span class="' + m.color + '" style="font-size:0.82rem; margin-left:3px;">(' + m.cate + ')</span></div>';
        
        if (currentView === "9") {
            row += '<div class="col-cell-rate" style="color:#dc2626; font-weight:bold;">-' + Math.round(m.diff) + '%p</div>';
        } else {
            row += '<div class="col-cell-rate">' + m.rate + '%</div>';
        }

        for (var j = 0; j < pageDates.length; j++) {
            var d = pageDates[j].full;
            var key = m.no + "-" + d;
            var val = attMap[key];
            var mark = "";
            var cls = "col-cell-att";
            if (pageIndex === 0 && j === 0) cls += " latest-col";
            if (val === 1) {
                mark = "●";
            } else if (val >= 2 && val <= 6) {
                mark = "○";
                cls += " outside";
            }
            row += '<div class="' + cls + '">' + mark + '</div>';
        }
        row += '</div>';
        bodyHtml += row;
    }
    document.getElementById("table-body").innerHTML = bodyHtml;

    // 네비게이션 화살표 상태 갱신
    if (pageIndex > 0) btnPrev.classList.remove("hidden");
    else btnPrev.classList.add("hidden");

    if (pageIndex < totalPages - 1) btnNext.classList.remove("hidden");
    else btnNext.classList.add("hidden");
}

function toggleSortMode() {
    var sel = document.getElementById("view_select");
    if (sel.value === "5") sel.value = "6";
    else if (sel.value === "6") sel.value = "5";
    onViewSelectChange();
}

function changePage(delta) {
    var newPage = pageIndex + delta;
    if (newPage >= 0 && newPage < totalPages) {
        pageIndex = newPage;
        document.getElementById("table-wrapper").scrollTop = 0;
        renderTable();
    }
}

window.addEventListener("keydown", function(e) {
    var modal = document.getElementById("input_box");
    if (modal && modal.style.display === "block") {
        if (e.key === "Escape") closeMemberModal();
        return;
    }
    if (e.key === "ArrowRight") changePage(1);
    if (e.key === "ArrowLeft") changePage(-1);
});

// 초기 실행
window.addEventListener("DOMContentLoaded", function() {
    updateSundayStats();
    onViewSelectChange();
});
</script>
</body>
</html>
