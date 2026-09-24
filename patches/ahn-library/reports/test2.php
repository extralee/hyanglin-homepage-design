<?php
require_once "lib.php";
if(!$connect) $connect=dbConn();
$mid='00001';
$data=mysql_fetch_array(mysql_query("SELECT mb_name FROM `g4_member` where mb_id='".$mid."'"));
$mb_name=$data[0];

mysql_close($connect);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/1999/REC-html401-19991224/strict.dtd">
<html lang="ko">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta http-equiv="Content-Style-Type" content="text/css">
<title></title>
<script type="text/javascript" src="../js/jquery-1.11.0.min.js"></script>
<script type="text/javascript" src="barcode.js"></script>
<script type="text/javascript" src="html2canvas.js"></script>
<style type="text/css">
.space { background:#FFFFFF;float:left;margin:0;padding:0;cursor:default; }
.bar { background:#000000;float:left;margin:0;padding:0;cursor:default; }
.bartext { clear:both;font-family:Fixedsys,Arial;font-size:12px;cursor:default; }
#capture {
  background-color: #ccff99;
  width: 500px;
  height: 700px;
  text-align: center;
  margin-bottom: 1rem;
}

.text {
  text-align: center;
  padding-top:80px;
  color: #000000;
  font-size:1.3rem
}

</style>
</head>
<div id="capture">
	<p class="text">
		<img src="http://www.ahn-library.org/images/logo_300.png" style="margin-top:10px; margin-bottom:30px; width:320px;"><br><br>
		<span style="font-size:2rem; font-weight:bold"><?=$mb_name?></span><br><br>
		회원님의 ID는 <span style="font-size:2rem; font-weight:bold"><?=$mid?></span>입니다.
	</p>
	<div style="margin-left:112px;">
		<script>barcode("<?=$mid?>", 100,3,6,3,6);</script>
	</div>
	<div class="text" style="font-size:1.4rem; margin-top:130px; text-align: center; line-height:180%">
		본 회원카드를 핸드폰에 저장하여<br>도서대출시에 보여주시면<br>신속한 처리가 이루어집니다.
	</div>
</div>
<button id="downloadButton">다운로드</button>
<body>
<script type="text/javascript">
let captureDiv = document.getElementById('capture');
let downloadButton = document.getElementById('downloadButton');

downloadButton.addEventListener('click', () => {
	html2canvas(captureDiv).then(canvas => {
		saveImg(canvas.toDataURL('image/jpg'), 'image.jpg');
	});
});

const saveImg = (uri, filename) => {
	const decodImg = atob(uri.split(',')[1]);
	 let array = [];
	 for (let i = 0; i < decodImg .length; i++) {
		array.push(decodImg .charCodeAt(i));
	 }
	const file = new Blob([new Uint8Array(array)], {type: 'image/jpeg'});
	const fileName = 'canvas_img_' + new Date().getMilliseconds() + '.jpg';
	let formData = new FormData();
	formData.append('file', file, fileName);
	$.ajax({
		type: 'post',
		url: '/reports/canvas_upload.php',
		data: {KData:uri},
		success: function (data) {
			alert(data)
		}
	})

};
</script>
<br>
<iframe width=400 height=200 id='hiddenframe' name='hiddenframe' style="display:none"></iframe>
</body>
</html>