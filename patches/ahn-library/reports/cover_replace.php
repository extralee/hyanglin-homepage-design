<?
require_once "lib.php";
if(!$connect) $connect=dbConn();
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
</head>
<body>
<iframe src="cover_replace_ok.php" width=0 height=0 name='hiddenframe' style='display:none;'></iframe>
<?
//$result=mysql_query("SELECT a.bibid as id, a.field_data as isbn, b.fieldid as fid, b.field_data as cover FROM `biblio_field`  a LEFT JOIN `biblio_field` b ON a.bibid=b.bibid WHERE a.tag=20 AND a.subfield_cd='a' AND a.ind1_cd!=1 AND b.tag=856 AND b.is_done=0 ORDER BY a.bibid LIMIT 20");






$result=mysql_query("SELECT a.title, c.barcode_nmbr, a.bibid, b.field_data, b.fieldid FROM `biblio` a LEFT JOIN `biblio_field` b ON a.bibid=b.bibid LEFT JOIN `biblio_copy` c ON a.bibid=c.bibid WHERE b.tag=20 AND LENGTH(b.field_data)>9 AND b.field_data NOT LIKE '%세트%' ORDER BY a.bibid LIMIT 200, 100");

$pre_title="";
while($data=@mysql_fetch_array($result)) {
	if($data[0]!=$pre_title) {
		$imsi=mysql_fetch_array(mysql_query("SELECT field_data, fieldid FROM `biblio_field` WHERE bibid=$data[2] AND tag=856"));
		if(!$imsi[0]) {
			echo $data[0]."--".$data[1]."--".$data[3]."<br>";
			$isbn=trim($data[3]);
			$url="http://www.aladin.co.kr/ttb/api/ItemLookUp.aspx?ttbkey=ttbextralee1948002&itemIdType=ISBN&ItemId=".$isbn."&Cover=Big&output=js&Version=20131101";
			$adata = json_decode(file_get_contents($url), true);
			$item=$adata["item"];
			$item_data=$item[0];
			$aladin_cover_img=$item_data["cover"];
			echo $isbn."<br>";
			echo "새커버<br><img src='".$aladin_cover_img."'><br>";
			//echo $data[4]."<br>";
			//if(substr($imsi[0], 0, 1)=="/") $old_cover="http://www.ahn-library.org".$data[cover];
			//else $old_cover=$data[cover];
			//echo $data[cover]."<br>";
			//echo"기존커버<br><img src='".$old_cover."'><br>";
			//echo "<a href='cover_replace_ok.php?fid=".$imsi[1]."&new_cover=".$aladin_cover_img."' target='hiddenframe'>[커버교체]</a>------------------";
			//echo "<a href='cover_replace_ok.php?fid=".$imsi[1]."' target='hiddenframe'>[Not 커버교체]</a>";
			echo "<br><br>---------------------------------------------------------------------------------<br><br>";	 
		}
	}
	$pre_title=$data[0];
}



/*
while($data=@mysql_fetch_array($result)) {
	echo $data[id]."<br>";
	$isbn_data=explode("(", $data[isbn]);
	$isbn=trim($isbn_data[0]);
	$url="http://www.aladin.co.kr/ttb/api/ItemLookUp.aspx?ttbkey=ttbextralee1948002&itemIdType=ISBN&ItemId=".$isbn."&Cover=Big&output=js&Version=20131101";
	$adata = json_decode(file_get_contents($url), true);
	$item=$adata["item"];
	$item_data=$item[0];
	$aladin_cover_img=$item_data["cover"];
	echo $isbn."<br>";
	echo "<img src='".$aladin_cover_img."'><br>";
	echo $data[fid]."<br>";
	if(substr($data[cover], 0, 1)=="/") $old_cover="http://www.ahn-library.org".$data[cover];
	else $old_cover=$data[cover];
	echo $data[cover]."<br>";
	echo"<img src='".$old_cover."'><br>";
	echo "<a href='cover_replace_ok.php?fid=".$data[fid]."&new_cover=".$aladin_cover_img."' target='hiddenframe'>[커버교체]</a>------------------";
	echo "<a href='cover_replace_ok.php?fid=".$data[fid]."' target='hiddenframe'>[Not 커버교체]</a>";
	echo "<br><br>---------------------------------------------------------------------------------<br><br>";
}
*/
mysql_close($connect);

?>
</body>
</html>