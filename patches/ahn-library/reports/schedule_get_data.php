<?
require_once "lib.php";
if(!$connect) $connect=dbConn();

$cr_no = isset($_REQUEST["cr_no"]) ? intval($_REQUEST["cr_no"]) : 0;
$m_no = isset($_REQUEST["m_no"]) ? mysql_real_escape_string($_REQUEST["m_no"], $connect) : "";
if(!$m_no && isset($_REQUEST["mid"])) $m_no = mysql_real_escape_string($_REQUEST["mid"], $connect);

header("Content-Type:application/json");

$odata=mysql_fetch_array(mysql_query("select * from schedule where no=$cr_no"));
$member_no=$odata[member_no];
$cr_name=$odata[member_name];

$temp=mysql_fetch_array(mysql_query("select mb_name, mb_level from `g4_member` where mb_id='$m_no'"));
$user_name=$temp[0];
if($cr_name) $user_name=$cr_name;
$mb_level=$temp[1];
if($mb_level>7) $is_admin=1; else $is_admin=0;

$result=mysql_query("select from_time,time_length,place from schedule where r_date='$odata[r_date]' and no!=$cr_no");
$other_box_data="";
while($cdata=@mysql_fetch_array($result)) {
	$pre=$pre2="";
	if($cdata[place]==1) { $pre="a"; $pre2=""; }
	elseif($cdata[place]==2) { $pre="b"; $pre2=""; }
	elseif($cdata[place]==3) { $pre="c"; $pre2=""; }
	elseif($cdata[place]==4) { $pre="d"; $pre2=""; }
	elseif($cdata[place]==5) { $pre="a"; $pre2="b"; }
	elseif($cdata[place]==6) { $pre="c"; $pre2="d"; }
	for($i=$cdata[from_time];$i<$cdata[from_time]+$cdata[time_length];$i++) {
		$other_box_data.="-".$pre.$i;
		if($pre2!="") $other_box_data.="-".$pre2.$i;
	}
}

$box_data="";
$pre=$pre2="";
if($odata[place]==1) { $pre="a"; $pre2=""; }
elseif($odata[place]==2) { $pre="b"; $pre2=""; }
elseif($odata[place]==3) { $pre="c"; $pre2=""; }
elseif($odata[place]==4) { $pre="d"; $pre2=""; }
elseif($odata[place]==5) { $pre="a"; $pre2="b"; }
elseif($odata[place]==6) { $pre="c"; $pre2="d"; }
for($i=$odata[from_time];$i<$odata[from_time]+$odata[time_length];$i++) {
	$box_data.="-".$pre.$i;
	if($pre2!="") $box_data.="-".$pre2.$i;
}

$year=substr($odata[r_date],0,4);
$month=substr($odata[r_date],4,2)*1;
$day=substr($odata[r_date],6,2)*1;
$jdata = [$user_name, $odata[url], $member_no, $other_box_data, $box_data, $odata[subject], $year, $month, $day, $odata[from_time], $odata[time_length], $odata[place], $odata[category], $is_admin];
echo json_encode($jdata);

mysql_close($connect);
?>
