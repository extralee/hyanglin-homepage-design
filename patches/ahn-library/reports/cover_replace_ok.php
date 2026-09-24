<?
require_once "lib.php";
if(!$connect) $connect=dbConn();

$new_cover = isset($_REQUEST["new_cover"]) ? mysql_real_escape_string($_REQUEST["new_cover"], $connect) : "";
$fid = isset($_REQUEST["fid"]) ? intval($_REQUEST["fid"]) : 0;

if($new_cover) {
	mysql_query("update  `biblio_field` set field_data='".$new_cover."'  where fieldid='$fid'") or error(mysql_error());
}

mysql_close($connect);

?>