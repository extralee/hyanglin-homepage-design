<?
include_once dirname(__FILE__)."/auth-guard.php";
extract($_REQUEST);
header("Content-Type:application/json");
require "lib.php";
if(!$connect) $connect=dbConn();

function getFirstSunday($date) {
	$clone = clone $date;
	$dayOfWeek = (int)$clone->format('w');
	if ($dayOfWeek === 0) {
		return $clone;
	} else {
		$daysToAdd = 7 - $dayOfWeek;
		$clone->add(new DateInterval('P' . $daysToAdd . 'D'));
	}
	return $clone;
}

/*
function getSundayCount($startDateStr, $endDateStr) {
    $start = new DateTime($startDateStr);
    $end = new DateTime($endDateStr);
    $current = clone $start;
    $count = 0;
    while ($current <= $end) {
        if ((int)$current->format('w') === 0) {
            $count++;
        }
        $current->add(new DateInterval('P1D'));
    }
    return $count;
}
*/
// [도우미 함수] 현재 날짜 기준으로 바로 다음 스케줄(일요일 or 성탄절) 구하기
function getNextScheduleDate($currentDate) {
    // 1. 다음 일요일 계산
    $nextSunday = clone $currentDate;
    $nextSunday->modify('next sunday');

    // 2. 다가오는 성탄절 계산
    $year = $currentDate->format('Y');
    $nextXmas = new DateTime("$year-12-25");
    // 만약 계산된 성탄절이 기준일보다 과거거나 같다면(이미 지났다면), 내년 성탄절로 설정
    if ($nextXmas <= $currentDate) {
        $nextXmas = new DateTime(($year + 1) . "-12-25");
    }

    // 3. 둘 중 더 가까운 날짜 반환
    if ($nextXmas < $nextSunday) {
        return $nextXmas;
    } else {
        return $nextSunday;
    }
}

// [도우미 함수] 현재 날짜 기준으로 바로 이전 스케줄(일요일 or 성탄절) 구하기 (최신순 역순 배치용)
function getPrevScheduleDate($currentDate) {
    // 1. 이전 일요일 계산
    $prevSunday = clone $currentDate;
    $prevSunday->modify('last sunday');

    // 2. 과거 성탄절 계산
    $year = (int)$currentDate->format('Y');
    $prevXmas = new DateTime("$year-12-25");
    if ($prevXmas >= $currentDate) {
        $prevXmas = new DateTime(($year - 1) . "-12-25");
    }

    // 3. 둘 중 더 가까운 과거 날짜 반환
    if ($prevXmas > $prevSunday) {
        return $prevXmas;
    } else {
        return $prevSunday;
    }
}

// [수정된 카운트 함수] 일요일 + 성탄절 개수 세기
function getHolidayCount($startDateStr, $endDateStr) {
    $start = new DateTime($startDateStr);
    $end = new DateTime($endDateStr);
    // 종료일이 시작일보다 과거인 경우 0 반환 (예외처리)
    if ($start > $end) return 0;

    $current = clone $start;
    $count = 0;
    
    while ($current <= $end) {
        // 일요일(w=0) 이거나, 월-일이 12-25 이면 카운트
        if ((int)$current->format('w') === 0 || $current->format('m-d') === '12-25') {
            $count++;
        }
        $current->add(new DateInterval('P1D'));
    }
    return $count;
}

$return_data="";
if($view_type==1) {
	$result=mysql_query("SELECT a.no, a.name, a.category, a.subcategory, b.date, b.place FROM `members` a LEFT JOIN `member_attendance` b ON a.no=b.member_no AND b.date='".$sunday."' WHERE a.no_use=0 AND a.age_category<10 ORDER BY a.name");
	$i=0;
	while($data=@mysql_fetch_array($result)) { //-----------------------------이름순 보기
		$name=$data[name];
		if($name<"김가") $bg_color="bg_color1";
		elseif($name<"나") $bg_color="bg_color1-1";
		elseif($name<"바") $bg_color="bg_color2";
		elseif($name<"사") $bg_color="bg_color3";
		elseif($name<"아") $bg_color="bg_color1";
		elseif($name<"이가") $bg_color="bg_color2-1";
		elseif($name<"자") $bg_color="bg_color2";
		elseif($name<"차") $bg_color="bg_color3";
		elseif($name<"하") $bg_color="bg_color1";
		else $bg_color="bg_color1-1";
		if($data[date]==$sunday) $bg_color="bg_color_black";
		if($data[place]>1) $bg_color="bg_color_gray";
		$return_data.="<div class='member-name ".$bg_color."' data-class='member-name ".$bg_color."' data-place='place_".$data[place]."' data-id='".$data[no]."'><span class='last-name'>".mb_substr($name, 0, 1, 'UTF-8')."</span>".mb_substr($name, 1, null, 'UTF-8')."</div>";
		$i++;
	}
	$total_box_num=$i;
	$return_data.="@@@".$total_box_num;
} elseif($view_type==2) {
	$result=mysql_query("SELECT a.no, a.name, a.category, b.title, c.date, c.place  FROM `members` a LEFT JOIN `members_subcategory` b ON a.subcategory=b.no LEFT JOIN `member_attendance` c ON a.no=c.member_no AND c.date='".$sunday."' WHERE a.no_use=0 AND a.age_category<10 ORDER BY a.category, a.subcategory, a.name");
	$pre_category=0;
	$pre_title=null;
	$i=0;
	while($data=@mysql_fetch_array($result)) {
		$name=$data[name];
		if($data[category]!=$pre_category) {
			if(	$data[category]==1) $category_text="정회원";
			elseif($data[category]==2) $category_text="준회원";
			elseif($data[category]==3) $category_text="방문출석";
			$bg_color="bg_color_category";
			$return_data.="<div class='cate ".$bg_color."'>".$category_text."</div>";
			$i++;
		}
		$pre_category=$data[category];

		if($data[title]!=$pre_title) {
			$bg_color="bg_color_subcategory";
			$return_data.="<div class='cate ".$bg_color."'>".$data[title]."</div>";
			$i++;
		}
		$pre_title=$data[title];
		$bg_color="bg_color_category_".$data[category];
		if($data[date]==$sunday) $bg_color="bg_color_black";
		if($data[place]>1) $bg_color="bg_color_gray";
		$return_data.="<div class='member-name ".$bg_color."' data-class='member-name ".$bg_color."' data-place='place_".$data[place]."' data-id='".$data[no]."'><span class='last-name'>".mb_substr($name, 0, 1, 'UTF-8')."</span>".mb_substr($name, 1, null, 'UTF-8')."</div>";
		$i++;
	}
	$total_box_num=$i;
	$return_data.="@@@".$total_box_num;
} elseif($view_type==3) {
	$result=mysql_query("SELECT a.no, a.name, a.age_category, c.date, b.title, c.place FROM `members` a LEFT JOIN `members_age_category` b ON a.age_category=b.no LEFT JOIN `member_attendance` c ON a.no=c.member_no AND c.date='".$sunday."' WHERE a.no_use=0 AND a.age_category>9 ORDER BY a.age_category, a.name");
	$pre_age_category=0;
	$pre_title=null;
	while($data=@mysql_fetch_array($result)) {
		$name=$data[name];
		if($data[age_category]!=$pre_age_category) {
			if(	$data[age_category]==10) $category_text="유아유치부";
			elseif($data[age_category]==11) $category_text="어린이부";
			elseif($data[age_category]==12) $category_text="청소년부";
			//$bg_color="bg_color_category";
			$return_data.="<div class='cate school-cate'>".$category_text."</div>";
		}
		$pre_age_category=$data[age_category];

		$bg_color="bg_color_category_1";
		if($data[date]==$sunday) $bg_color="bg_color_black";
		if($data[place]>1) $bg_color="bg_color_gray";
		$return_data.="<div class='member-name ".$bg_color."' data-class='member-name ".$bg_color."' data-place='place_".$data[place]."' data-id='".$data[no]."'><span class='last-name'>".mb_substr($name, 0, 1, 'UTF-8')."</span>".mb_substr($name, 1, null, 'UTF-8')."</div>";
	}
	$total_box_num=400;
	$return_data.="@@@".$total_box_num;
} elseif($view_type==4) {
	$result=mysql_query("SELECT a.no, a.name, a.service_category, b.title, c.date, c.place FROM `members` a LEFT JOIN `members_service_category` b ON a.service_category=b.no LEFT JOIN `member_attendance` c ON a.no=c.member_no AND c.date='".$sunday."' WHERE a.no_use=0 AND a.service_category>0 AND a.age_category<10 ORDER BY a.service_category, a.name");
	$pre_service_category=0;
	$pre_title=null;
	while($data=@mysql_fetch_array($result)) {
		$name=$data[name];
		if($data[service_category]!=$pre_service_category) {
			$return_data.="<div class='cate school-cate'>".$data[title]."</div>";
		}
		$pre_service_category=$data[service_category];

		$bg_color="bg_color_category_1";
		if($data[date]==$sunday) $bg_color="bg_color_black";
		if($data[place]>1) $bg_color="bg_color_gray";
		$return_data.="<div class='member-name ".$bg_color."' data-class='member-name ".$bg_color."' data-place='place_".$data[place]."' data-id='".$data[no]."'><span class='last-name'>".mb_substr($name, 0, 1, 'UTF-8')."</span>".mb_substr($name, 1, null, 'UTF-8')."</div>";
	}
	$total_box_num=400;
	$return_data.="@@@".$total_box_num;
} elseif($view_type==5||$view_type==6) {
/*
	$startDateStr='2025-08-10';
	$startDate = new DateTime($startDateStr);
	$currentSunday = getFirstSunday($startDate);
	
	$return_data.="<div class='col col-name'> </div><div class='col col-rate'>출석률</div>";
	for ($week = 1; $week <= 25; $week++) {
		$bg_color = "bg_color1";
		$dateString = $currentSunday->format('m.d'); // 문자열로 변환
		${"schedule_date_".$week}=$currentSunday->format('Y-m-d');
		$return_data .= "<div class='col col-date " . $bg_color . "'>" . $dateString . "</div>";
		$currentSunday->add(new DateInterval('P7D'));
	}
	$sundayNum=getSundayCount($startDateStr, $sunday);
*/
	$currentDate = new DateTime($sunday);
	$return_data .= "<div class='col col-name'> </div><div class='col col-rate'>출석률</div>";

	for ($week = 1; $week <= 25; $week++) {
		$bg_color = "bg_color1";
		if ($currentDate->format('m-d') === '12-25' && (int)$currentDate->format('w') !== 0) {
			$bg_color = "bg_xmas"; 
		}
		if ($week == 1) {
			$bg_color .= " latest-first";
		}

		$dateString = $currentDate->format('m.d'); 
		${"schedule_date_".$week} = $currentDate->format('Y-m-d');
		$return_data .= "<div class='col col-date " . $bg_color . "'>" . $dateString . "</div>";
		$currentDate = getPrevScheduleDate($currentDate);
	}

	$startDateStr = ${"schedule_date_25"};
	$endDateStr   = ${"schedule_date_1"};
	$sundayNum    = 25;

	if($view_type==5) $orderBy="b.name";
	elseif($view_type==6) $orderBy="2 desc, b.name";
	$result=mysql_query("SELECT a.member_no, count(*), b.category, b.name FROM `member_attendance` a left join `members` b on a.member_no=b.no WHERE b.no_use=0 AND b.age_category<10 and a.date>='".$startDateStr."' and a.date<='".$endDateStr."' group by a.member_no ORDER BY ".$orderBy);
	$ii=0;
	while($data=@mysql_fetch_array($result)) {
		$ii++;
		if($data[category]==1) { $category_text="정"; $cate_color="darkred"; }
		elseif($data[category]==2) { $category_text="준"; $cate_color="darkgreen"; }
		elseif($data[category]==3) { $category_text="방"; $cate_color="darkgray"; }
		$att_rate=round($data[1]/$sundayNum*100);
		$return_data.="<div class='col col-name'><span class='name_no'>".$ii.". </span>".$data[name]." <span class='exp ".$cate_color."'> (".$category_text.")</span></div>";
		$return_data.="<div class='col col-rate'>".$att_rate."%</div>";
		for($j=1; $j<=25; $j++) {
			$col_id=$data[member_no]."-".${"schedule_date_".$j};
			$return_data.="<div class='col col-attand' id='".$col_id."'></div>";
		}
	}
	$total_box_num=$ii;
	$attand_data="";
	$result=mysql_query("SELECT member_no, date, place FROM `member_attendance` WHERE place<6 and date>='".$startDateStr."' and date<='".$endDateStr."' ORDER BY member_no");
	while($data=@mysql_fetch_array($result)) {
		$attand_data.=$data[member_no]."-".$data[date]."^".$data[place]."#";
	}
	$return_data.="@@@".$total_box_num;
} elseif($view_type==9) {
	// ── 출석률 하락 교인 (최근 25주 30% 미만 교인 중 직전 25주 대비 최대 하락 25명) ──
	$currentDate = new DateTime($sunday);
	for ($week = 1; $week <= 25; $week++) {
		${"schedule_date_".$week} = $currentDate->format('Y-m-d');
		$currentDate = getPrevScheduleDate($currentDate);
	}
	for ($week = 26; $week <= 50; $week++) {
		${"schedule_date_".$week} = $currentDate->format('Y-m-d');
		$currentDate = getPrevScheduleDate($currentDate);
	}

	$recentStart = ${"schedule_date_25"};
	$recentEnd   = ${"schedule_date_1"};
	$priorStart  = ${"schedule_date_50"};
	$priorEnd    = ${"schedule_date_26"};

	// 최근 25주 출석률 33% 이상, 66% 이상 통계
	$c33 = 0;
	$c66 = 0;
	$resStats = mysql_query("SELECT a.member_no, count(*) as cnt FROM `member_attendance` a LEFT JOIN `members` b ON a.member_no=b.no WHERE b.no_use=0 AND a.date>='".$recentStart."' AND a.date<='".$recentEnd."' GROUP BY a.member_no");
	while($st = @mysql_fetch_array($resStats)) {
		$cnt = (int)$st['cnt'];
		if (($cnt / 25.0) >= (1.0 / 3.0)) $c33++;
		if (($cnt / 25.0) >= (2.0 / 3.0)) $c66++;
	}

	$recentAtt = array();
	$resR = mysql_query("SELECT a.member_no, count(*) as cnt, b.name, b.category FROM `member_attendance` a LEFT JOIN `members` b ON a.member_no=b.no WHERE b.no_use=0 AND b.age_category<10 AND a.date>='".$recentStart."' AND a.date<='".$recentEnd."' GROUP BY a.member_no");
	while($r = @mysql_fetch_array($resR)) {
		$recentAtt[$r['member_no']] = array('cnt' => (int)$r['cnt'], 'name' => $r['name'], 'category' => $r['category']);
	}

	$priorAtt = array();
	$resP = mysql_query("SELECT a.member_no, count(*) as cnt FROM `member_attendance` a LEFT JOIN `members` b ON a.member_no=b.no WHERE b.no_use=0 AND b.age_category<10 AND a.date>='".$priorStart."' AND a.date<='".$priorEnd."' GROUP BY a.member_no");
	while($p = @mysql_fetch_array($resP)) {
		$priorAtt[$p['member_no']] = (int)$p['cnt'];
	}

	$allMemRes = mysql_query("SELECT no, name, category FROM `members` WHERE no_use=0 AND age_category<10 ORDER BY name");
	$diffList = array();
	while($m = @mysql_fetch_array($allMemRes)) {
		$mNo = $m['no'];
		$rCnt = isset($recentAtt[$mNo]) ? $recentAtt[$mNo]['cnt'] : 0;
		$pCnt = isset($priorAtt[$mNo]) ? $priorAtt[$mNo] : 0;
		$rRate = ($rCnt / 25.0) * 100.0;
		$pRate = ($pCnt / 25.0) * 100.0;
		if ($rRate < 30.0) {
			$diff = $pRate - $rRate;
			$diffList[] = array(
				'no' => $mNo,
				'name' => $m['name'],
				'category' => $m['category'],
				'diff' => $diff,
				'rRate' => $rRate
			);
		}
	}

	usort($diffList, function($a, $b) {
		if ($b['diff'] != $a['diff']) return ($b['diff'] > $a['diff']) ? 1 : -1;
		return strcmp($a['name'], $b['name']);
	});
	$diffList = array_slice($diffList, 0, 25);

	$return_data .= "<div class='col col-name'> </div><div class='col col-rate'>감소폭</div>";
	for ($week = 1; $week <= 25; $week++) {
		$dateObj = new DateTime(${"schedule_date_".$week});
		$dateString = $dateObj->format('m.d');
		$bg_color = ($week == 1) ? "bg_color1 latest-first" : "bg_color1";
		$return_data .= "<div class='col col-date " . $bg_color . "'>" . $dateString . "</div>";
	}

	$ii = 0;
	foreach($diffList as $data) {
		$ii++;
		$category_text = "정"; $cate_color = "darkred";
		if($data['category'] == 2) { $category_text = "준"; $cate_color = "darkgreen"; }
		elseif($data['category'] == 3) { $category_text = "방"; $cate_color = "darkgray"; }
		
		$diffText = "-" . round($data['diff']) . "%p";
		$return_data .= "<div class='col col-name'><span class='name_no'>".$ii.". </span>".$data['name']." <span class='exp ".$cate_color."'> (".$category_text.")</span></div>";
		$return_data .= "<div class='col col-rate' style='color:#dc2626; font-weight:bold;'>".$diffText."</div>";
		for($j=1; $j<=25; $j++) {
			$col_id = $data['no']."-".${"schedule_date_".$j};
			$return_data .= "<div class='col col-attand' id='".$col_id."'></div>";
		}
	}
	$total_box_num = $ii;
	$attand_data = "";
	$result = mysql_query("SELECT member_no, date, place FROM `member_attendance` WHERE place<6 and date>='".$recentStart."' and date<='".$recentEnd."' ORDER BY member_no");
	while($data = @mysql_fetch_array($result)) {
		$attand_data .= $data['member_no']."-".$data['date']."^".$data['place']."#";
	}
	$return_data .= "@@@".$total_box_num."@@@".$attand_data."@@@".$c33."@@@".$c66;
} elseif($view_type==7) {
	$startDateStr='2025-08-10';
	$sub_num=$visit_num=$kinder_num=$child_num=$young_num=$school_total_num=0;
	$teacher_num=$parent_num=$program_num=$service_num=$etc_num=$extra_total_num=$all_total_num=0;
	$extra_main_num=$extra_sub_num=$extra_visit_num=$extra_sum_num=0;
	$main_num=$sub_num=$visit_num=0;
	$return_data.="<div class='col col-head col-week-date'>날짜</div><div class='col col-head col-week-title-title'>총계</div><div class='col col-head col-week-title-title'>예배실</div><div class='col col-head col-week-title'>정회원</div><div class='col col-head col-week-title'>준회원</div><div class='col col-head col-week-title'>방문출석</div><div class='col col-head col-week-title-short'>미확인</div><div class='col col-head col-week-title-short'>예배실外</div><div class='col col-head col-week-title-short'>교사</div><div class='col col-head col-week-title-short'>학부모</div><div class='col col-head col-week-title-short'>프로그램</div><div class='col col-head col-week-title-short'>업무</div><div class='col col-head col-week-title-short'>기타</div><div class='col col-head col-week-title-short'>교회학교</div><div class='col col-head col-week-title-short'>유아유치</div><div class='col col-head col-week-title-short'>어린이부</div><div class='col col-head col-week-title-short'>청소년부</div><div class='col col-head col-week-title-short'>온라인</div>";
	$result=mysql_query("SELECT b.category, count(*), b.subcategory, a.place, a.date FROM `member_attendance` a left join `members` b on a.member_no=b.no where a.date>='2025-08-10' group by a.date, b.category,  b.subcategory, a.place order by a.date");
	$preDateStr='2025-08-03';
	while($data=@mysql_fetch_array($result)) {
		if($preDateStr!=$data[4]) {
			$data2=mysql_fetch_array(mysql_query("select member_num from members_online where date='$preDateStr'"));
			$online_num=$data2[0];
			$data2=mysql_fetch_array(mysql_query("select member_num from members_unknown where date='$preDateStr'"));
			if($data2[0]) $unknown_num=$data2[0];
			else $unknown_num=0;
			$total_num=$main_num+$sub_num+$visit_num+$unknown_num;
			$extra_sum_num=$extra_main_num+$extra_sub_num+$extra_visit_num;
			$all_total_num+=$unknown_num;
			if($preDateStr!='2025-08-03') $return_data.="<div class='col col-week-date'>$preDateStr</div><div class='col col-week-title-long col-all'>$all_total_num<br><div class='graph' style='width:".$all_total_num."px'></div></div><div class='col col-week-title-long col-sub'>$total_num<br><div class='graph' style='width:".$total_num."px'></div></div><div class='col col-week-title'>$main_num</div><div class='col col-week-title'>$sub_num</div><div class='col col-week-title'>$visit_num</div><div class='col col-week-title-short'>$unknown_num</div><div class='col col-week-title-short col-sub'>$extra_total_num</div><div class='col col-week-title-short'> $teacher_num</div><div class='col col-week-title-short'>$parent_num</div><div class='col col-week-title-short'>$program_num</div><div class='col col-week-title-short'>$service_num</div><div class='col col-week-title-short'>$etc_num</div><div class='col col-week-title-short col-online'>$school_total_num</div><div class='col col-week-title-short col-school'>$kinder_num</div><div class='col col-week-title-short col-school'>$child_num</div><div class='col col-week-title-short col-school'>$young_num</div><div class='col col-week-title-short col-online'>$online_num</div>";
			$sub_num=$visit_num=$kinder_num=$child_num=$young_num=$school_total_num=0;
			$teacher_num=$parent_num=$program_num=$service_num=$etc_num=$extra_total_num=$all_total_num=0;
			$extra_main_num=$extra_sub_num=$extra_visit_num=$extra_total_num=0;
			$main_num=$sub_num=$visit_num=0;
			$preDateStr=$data[4];
		}
		if($data[place]>1) {
			if(	$data[place]==2) $teacher_num=$data[1];
			elseif(	$data[place]==3) $parent_num=$data[1];
			elseif(	$data[place]==4) $program_num=$data[1];
			elseif(	$data[place]==5) $service_num=$data[1];
			elseif(	$data[place]==6) $etc_num=$data[1];
			if($data[category]==1) $extra_main_num+=$data[1];
			elseif($data[category]==2) $extra_sub_num+=$data[1];
			elseif($data[category]==3) $extra_visit_num+=$data[1];
			$extra_total_num+=$data[1];
		} else {
			if($data[category]==1) $main_num+=$data[1];
			elseif($data[category]==2) $sub_num+=$data[1];
			elseif($data[category]==3) $visit_num+=$data[1];
			elseif($data[category]==4) {
				if($data[subcategory]==31) $kinder_num=+$data[1];
				elseif($data[subcategory]==32) $child_num=+$data[1];
				elseif($data[subcategory]==33) $young_num=+$data[1];
				$school_total_num+=$data[1];
			}
		}
		$all_total_num+=$data[1];
	}
	$data2=mysql_fetch_array(mysql_query("select member_num from members_online where date='$preDateStr'"));
	$online_num=$data2[0];
	$data2=mysql_fetch_array(mysql_query("select member_num from members_unknown where date='$preDateStr'"));
	if($data2[0]) $unknown_num=$data2[0];
	else $unknown_num=0;
	$total_num=$main_num+$sub_num+$visit_num+$unknown_num;
	$extra_sum_num=$extra_main_num+$extra_sub_num+$extra_visit_num;
	$all_total_num+=$unknown_num;
	if($preDateStr!='2025-08-03') $return_data.="<div class='col col-week-date'>$preDateStr</div><div class='col col-week-title-long col-all'>$all_total_num<br><div class='graph' style='width:".$all_total_num."px'></div></div><div class='col col-week-title-long col-sub'>$total_num<br><div class='graph' style='width:".$total_num."px'></div></div><div class='col col-week-title'>$main_num</div><div class='col col-week-title'>$sub_num</div><div class='col col-week-title'>$visit_num</div><div class='col col-week-title-short'>$unknown_num</div><div class='col col-week-title-short col-sub'>$extra_total_num</div><div class='col col-week-title-short'> $teacher_num</div><div class='col col-week-title-short'>$parent_num</div><div class='col col-week-title-short'>$program_num</div><div class='col col-week-title-short'>$service_num</div><div class='col col-week-title-short'>$etc_num</div><div class='col col-week-title-short col-online'>$school_total_num</div><div class='col col-week-title-short col-school'>$kinder_num</div><div class='col col-week-title-short col-school'>$child_num</div><div class='col col-week-title-short col-school'>$young_num</div><div class='col col-week-title-short col-online'>$online_num</div>";
} elseif($view_type==8) {
	$result=mysql_query("SELECT no, name, category, subcategory FROM `members` WHERE no_use=1 AND age_category<10 ORDER BY name");
	$i=0;
	while($data=@mysql_fetch_array($result)) { //-----------------------------이름순 보기
		$name=$data[name];
		$bg_color="bg_color1";
		$return_data.="<div class='member-name ".$bg_color."' data-class='member-name ".$bg_color."' data-id='".$data[no]."'><span class='last-name'>".mb_substr($name, 0, 1, 'UTF-8')."</span>".mb_substr($name, 1, null, 'UTF-8')."</div>";
		$i++;
	}
	$total_box_num=$i;
	$return_data.="@@@".$total_box_num;
}


if($view_type!=7) {
	$data=mysql_fetch_array(mysql_query("select member_num from members_online where date='$sunday'"));
	$online_num=$data[0];

	$sub_num=$visit_num=$kinder_num=$child_num=$young_num=$school_total_num=0;
	$teacher_num=$parent_num=$program_num=$service_num=$etc_num=$extra_total_num=$all_total_num=0;
	$extra_main_num=$extra_sub_num=$extra_visit_num=$extra_sum_num=0;
	$result=mysql_query("SELECT b.category, count(*), b.subcategory, a.place FROM `member_attendance` a left join `members` b on a.member_no=b.no where a.date='$sunday' group by b.category,  b.subcategory, a.place");
	$main_num=$sub_num=$visit_num=0;
	while($data=@mysql_fetch_array($result)) {
		if($data[place]>1) {
			if(	$data[place]==2) $teacher_num=$data[1];
			elseif(	$data[place]==3) $parent_num=$data[1];
			elseif(	$data[place]==4) $program_num=$data[1];
			elseif(	$data[place]==5) $service_num=$data[1];
			elseif(	$data[place]==6) $etc_num=$data[1];
			if($data[category]==1) $extra_main_num+=$data[1];
			elseif($data[category]==2) $extra_sub_num+=$data[1];
			elseif($data[category]==3) $extra_visit_num+=$data[1];
			$extra_total_num+=$data[1];
		} else {
			if($data[category]==1) $main_num+=$data[1];
			elseif($data[category]==2) $sub_num+=$data[1];
			elseif($data[category]==3) $visit_num+=$data[1];
			elseif($data[category]==4) {
				if($data[subcategory]==31) $kinder_num=+$data[1];
				elseif($data[subcategory]==32) $child_num=+$data[1];
				elseif($data[subcategory]==33) $young_num=+$data[1];
				$school_total_num+=$data[1];
			}
		}
		$all_total_num+=$data[1];
	}


	$data=mysql_fetch_array(mysql_query("select member_num from members_unknown where date='$sunday'"));
	if($data[0]) $unknown_num=$data[0];
	else $unknown_num=0;
	$total_num=$main_num+$sub_num+$visit_num+$unknown_num;
	$extra_sum_num=$extra_main_num+$extra_sub_num+$extra_visit_num;
	$all_total_num+=$unknown_num;

	$return_data.="@@@".$online_num."@@@".$main_num."@@@".$sub_num."@@@".$visit_num."@@@".$unknown_num."@@@".$total_num."@@@".$kinder_num."@@@".$child_num."@@@".$young_num."@@@".$school_total_num;
	$return_data.="@@@".$teacher_num."@@@".$parent_num."@@@".$program_num."@@@".$service_num."@@@".$etc_num."@@@".$extra_total_num."@@@".$all_total_num;
	$return_data.="@@@".$extra_main_num."@@@".$extra_sub_num."@@@".$extra_visit_num."@@@".$extra_sum_num."@@@".$attand_data;
}
echo json_encode($return_data);

mysql_close($connect);
?>
