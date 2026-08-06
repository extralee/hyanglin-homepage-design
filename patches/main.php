<?
require "lib.php";
$reg_date=time();
$today=date("Ymd", $reg_date);
if(!$connect) $connect=dbConn();

//º∫º≠¿–±‚ '∫∏∞¸øÎ ∞‘Ω√∆«'ø°º≠ ø¿¥√ ≥Ø¬•∫∏¥Ÿ ¿€¿∫ ≥Ø¬•∞° ¿÷¿∏∏È ±◊∞Õ¿ª '¿Ãπ¯¡÷ º∫º≠¿–±‚' ∞‘Ω√∆«¿∏∑Œ ¿ÃµøΩ√≈¥
$bible_date=date("Ymd",mktime(0,0,0,substr($today,4,2),substr($today,6,2)+6,substr($today,0,4)));
$data=mysql_fetch_array(mysql_query("SELECT a.document_srl FROM `xe_documents` a left join `xe_document_extra_vars` b on a.document_srl=b.document_srl where a.module_srl=1912 and a.is_notice='N' and b.eid='date' and b.value<=$bible_date"));
if($data[0]>0){
	mysql_query("update `xe_documents` set module_srl=551 where document_srl=$data[0]");
}

$result0=mysql_query("SELECT title, content, eid, value FROM `xe_documents` a left join `xe_document_extra_vars` b on a.document_srl=b.document_srl where a.module_srl=3225 and a.is_notice='N' and b.eid='to' and b.value<=$today order by b.value desc limit 1");
$result1=mysql_query("SELECT title, content, c.value FROM `xe_documents` a left join `xe_document_extra_vars` b on a.document_srl=b.document_srl left join `xe_document_extra_vars` c on a.document_srl=c.document_srl where a.module_srl=304 and a.is_notice='N' and b.eid='to' and c.value>=$today order by b.value limit 4");
$result2=mysql_query("SELECT title, content, eid, value FROM `xe_documents` a left join `xe_document_extra_vars` b on a.document_srl=b.document_srl where a.module_srl=200 and a.is_notice='N' and b.eid='to' and b.value>=$today order by a.document_srl desc");

$i=1;
while($data=@mysql_fetch_array($result1)) {
	${"link".$i}="/home/".$data[title];
	$imsi=explode('" /></p>', $data[content]);
	$imsi=explode('src="', $imsi[0]);
	${"image_url".$i}=$imsi[1];
	$i++;
}

$ii=1;
while($data=@mysql_fetch_array($result2)) {
	${"link_big".$ii}="/home/".$data[title];
	$imsi=explode('" /></p>', $data[content]);
	$imsi=explode('src="', $imsi[0]);
	${"image_url_big".$ii}=$imsi[1];
	$ii++;
}

$iii=1;
while($data=@mysql_fetch_array($result0)) {
	${"link_big_bible".$iii}="/home/".$data[title];
	$imsi=explode('" /></p>', $data[content]);
	$imsi=explode('src="', $imsi[0]);
	${"image_url_big_bible".$iii}=$imsi[1];
	$iii++;
}
?>
<!DOCTYPE html>
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="style.css?<?=$reg_date?>" />
	<script src="/home/common/js/jquery.min.js"></script>
</head>
<body>
<div class="main">
	<a href="<?=$link_big_bible1?>" target="_top"><img src="<?=$image_url_big_bible1?>" class="square_img s_img" /></a>
	<div id="main_pic">
<?
	$pic_num=$ii-1;
	//if($pic_num>7) $pic_num=7;
	for($j=1;$j<=$pic_num;$j++) {
		if($j==1) $active="active"; else  $active="";
		echo "
		<img id='main_pic".$j."' src='".${'image_url_big'.$j}."' onclick='top.location.href=\"".${'link_big'.$j}."\"' class='square_img s_img $active' />
		";
	}

$pos1="top:2%; left:2%;";
$pos2="top:2%; left:26%;";
$pos3="top:2%; left:50.5%;";
$pos4="top:2%; left:74.9%;";
$pos5="top:26%; left:2%;";
$pos6="top:26%; left:26%;";
$pos7="top:26%; left:50.5%;";
$pos8="top:26%; left:74.9%;";
$pos9="top:50.5%; left:2%;";
$pos10="top:50.5%; left:26%;";
$pos11="top:50.5%; left:50.5%;";
$pos12="top:50.5%; left:74.9%;";
$pos13="top:74.9%; left:2%;";
$pos14="top:74.9%; left:26%;";
$pos15="top:74.9%; left:50.5%;";
$pos16="top:74.9%; left:74.9%;";
?>
	</div>
	<!--a href="/home/" target="_top"><img src="/images/main2.jpg" class="square_img e_img" /></a-->
	<div class="square_img e_img">
		<div style="position:relative;width:100%; height:100%; background-color:#eee">
			<a href="/home/board_SbYk83" target="_top"><img src="/images/icon3.png?<?=$reg_date?>" style="width:23%; height:23%; position:absolute; <?=$pos1?>" /></a><!--¡÷∫∏-->
			<a href="/home/board_cdIM31" target="_top"><img src="/images/icon5.png?<?=$reg_date?>" style="width:23%; height:23%; position:absolute; <?=$pos2?>" /></a><!--«œ¥√∂Ê∆Ï±‚-->
			<a href="/home/board_HbQf45" target="_top"><img src="/images/icon17.png?<?=$reg_date?>" style="width:23%; height:23%; position:absolute; <?=$pos3?>" /></a><!--±‚µµ-->
			<a href="/home/board_EkxT26" target="_top"><img src="/images/icon21.png?<?=$reg_date?>" style="width:23%; height:23%; position:absolute; <?=$pos4?>" /></a><!--º∫º≠π¨ªÛ-->

			<a href="/home/board_OrAG11" target="_top"><img src="/images/icon22.png" style="width:23%; height:23%; position:absolute; <?=$pos5?>" /></a><!--∂Ê ≥™¥Æ-->
			<a href="/home/b_movie" target="_top"><img src="/images/icon7.png?<?=$reg_date?>" style="width:23%; height:23%; position:absolute; <?=$pos6?>" /></a><!--µøøµªÛ-->
			<a href="/home/board_Zujt14" target="_top"><img src="/images/icon9.png?<?=$reg_date?>" style="width:23%; height:23%; position:absolute; <?=$pos7?>" /></a><!--æŸπ¸-->
			<a href="https://www.youtube.com/channel/UC2rFw5WcFB5vfzXC1WPOsWQ" target="_blank"><img src="/images/icon24.png?<?=$reg_date?>" style="width:23%; height:23%; position:absolute; <?=$pos8?>" /></a><!--«‚∏∞ ¿Ø∆©∫Í-->

			<a href="/home/b_paper/758" target="_top"><img src="/images/icon2.png?<?=$reg_date?>" style="width:23%; height:23%; position:absolute; <?=$pos9?>" /></a><!--¡§∞¸-->
			<a href="/home/board_XHld94" target="_top"><img src="/images/icon6.png?<?=$reg_date?>" style="width:23%; height:23%; position:absolute; <?=$pos10?>" /></a><!--∏Ò»∏¿⁄-->
			<a href="/home/page_QATA26" target="_top"><img src="/images/icon10.png?<?=$reg_date?>" style="width:23%; height:23%; position:absolute; <?=$pos11?>" /></a><!--±≥»∏º“∞≥-->
			<a href="/home/index.php?mid=b_church&category=649" target="_top"><img src="/images/icon20.png?<?=$reg_date?>" style="width:23%; height:23%; position:absolute; <?=$pos12?>" /></a><!--±≥¿∞∫Œ-->

			<!--img src="/images/icon99.png?<?=$reg_date?>" style="width:23%; height:23%; position:absolute;<?=$pos11?>" /></a--><!--»≠ªÏ«•-->
			<!--a href="/home/b_notice/761" target="_top"><img src="/images/icon8.png?<?=$reg_date?>" style="width:23%; height:23%; position:absolute;<?=$pos12?>" /></a--><!--ªı»®∆‰¿Ã¡ˆ æ»≥ª-->
			<!--a href="/home/b_church" target="_top"><img src="/images/icon14.png?<?=$reg_date?>" style="width:23%; height:23%; position:absolute; <?=$pos14?>" /></a--><!--øÓøµ/¡∂¡˜-->

			<a href="/home/board_LJBs67" target="_top"><img src="/images/icon23.png?<?=$reg_date?>" style="width:23%; height:23%; position:absolute; <?=$pos13?>" /></a><!--æ∑–ø° ∫Òƒ£ «‚∏∞-->
			<a href="http://www.ahn-library.org/" target="_blank"><img src="/images/icon11.png?<?=$reg_date?>" style="width:23%; height:23%; position:absolute; <?=$pos14?>" /></a><!--æ»∫¥π´µµº≠∞¸-->
			<a href="http://www.gilmok.org/new/" target="_blank"><img src="/images/icon4.png?<?=$reg_date?>" style="width:23%; height:23%; position:absolute; <?=$pos15?>" /></a><!--±Ê∏Ò-->
			<a href="https://www.youtube.com/channel/UCvtcwAc7Fcla1EFykQfuuUA" target="_blank"><img src="/images/icon25.png?<?=$reg_date?>" style="width:23%; height:23%; position:absolute; <?=$pos16?>" /></a><!--ƒ∏ªÁ¿ÃΩ≈«–-->
		</div>
	</div>
	<img src="/images/board.jpg" class="s_img m_hide" />
	<div id="card_box1">
		<img src='<?=$image_url1?>' onclick='top.location.href="<?=$link1?>"' class='c_img' />
		<img src='<?=$image_url2?>' onclick='top.location.href="<?=$link2?>"' class='c_img' />
	</div>
	<div id="card_box2">
		<img src='<?=$image_url3?>' onclick='top.location.href="<?=$link3?>"' class='c_img c_right' />
		<img src='<?=$image_url4?>' onclick='top.location.href="<?=$link4?>"' class='c_img c_right' />
	</div>
</div>
<script>
var sourceSwap = function () {
    var $this = $(this);
    var newSource = $this.data('alt-src');
    $this.data('alt-src', $this.attr('src'));
    $this.attr('src', newSource);
}

$(function() {
    $('img[data-alt-src]').each(function() { 
        new Image().src = $(this).data('alt-src'); 
    }).hover(sourceSwap, sourceSwap); 
});

var windowWidth = $( window ).width();
if(windowWidth>800) {
	var h=$('#content_iframe',parent.document.body).contents().find('body')[0].clientHeight;
	$('#content_iframe',parent.document.body).parent().parent().height(h);
	$('#content_iframe',parent.document.body).height(h);
}

$('#content_iframe',parent.document.body).load(function() {
	var h=$(this).contents().find('body')[0].scrollHeight;
	console.log(h);
	var w=$(this).contents().find('body')[0].scrollWidth;
	console.log(w);
	if(w>479){
		//var h=$(this).contents().find('body')[0].clientHeight;
		//console.log(h+"---");
		//$(this).parent().parent().height(h);
		//$(this).height(h);
		$('.w_hide').css('display','none');
	} else {
		var hh=w*5+5*15;
		$(this).parent().parent().height(hh);
		$(this).height(hh);
		$('#main_pic').height(w); //∫“æÓø¬ ¿ÃπÃ¡ˆ¿« ºº∑Œ ªÁ¿Ã¡Ó∏¶ ∞°∑ŒøÕ µø¿œ«œ∞‘... ¡§ªÁ∞¢«¸¿Ãπ«∑Œ
		$('#end_pic').height(w); //∫“æÓø¬ ¿ÃπÃ¡ˆ¿« ºº∑Œ ªÁ¿Ã¡Ó∏¶ ∞°∑ŒøÕ µø¿œ«œ∞‘... ¡§ªÁ∞¢«¸¿Ãπ«∑Œ
		$('#card_box1').height(w);
		$('#card_box2').height(w);
		$('.square_img').height(w);
		$('.m_hide').css('display','none');
	}
});


var pic_no=1;
var pic_num=<?=$pic_num?>;
function swapImages(){
	if(pic_no<pic_num) {
		var next_pic_no=pic_no+1;
		var $active = $('#main_pic'+pic_no);
		var $next = $('#main_pic'+next_pic_no);
		pic_no++;
	} else {
		var next_pic_no=1;
		var $active = $('#main_pic'+pic_no);
		var $next = $('#main_pic'+next_pic_no);
		pic_no=1;
	}
	$next.fadeIn(function(){
		$active.removeClass('active');
		$next.addClass('active');
		$active.fadeOut();
	});
}
setInterval('swapImages()', 5000);

$(document).ready(function () {
	$.ajax({
		type: 'GET',
		data: { },
		url:"./is_onair.php",
		success: function (data) {
			if(data[0]=="Y" ) {
				$('#on-air-icon').attr('src', '/images/icon18.gif');//on-air
			} else {
				$('#on-air-icon').attr('src', '/images/icon18.png');//off
			}
		}
	})
	setInterval(function () {
		$.ajax({
			type: 'GET',
			data: { },
			url:"./is_onair.php",
			success: function (data) {
				if(data[0]=="Y" ) {
					$('#on-air-icon').attr('src', '/images/icon18.gif');//on-air
				} else {
					$('#on-air-icon').attr('src', '/images/icon18.png');//off
				}
			}
		})
	}, 30000);
});
</script>
<script>
/* iframe ÏûêÎèô ÎÜíÏù¥ ÎßûÏ∂§ (GitHub #41) */
window.addEventListener("load", function() {
    if (window.frameElement) {
        var els = document.querySelectorAll(".main > *");
        var maxBottom = 0;
        for (var i = 0; i < els.length; i++) {
            var rect = els[i].getBoundingClientRect();
            var bottom = rect.top + window.scrollY + rect.height;
            if (bottom > maxBottom) maxBottom = bottom;
        }
        if (maxBottom > 100) {
            window.frameElement.style.height = (maxBottom + 30) + "px";
        }
    }
});
</script>
</body>
</html>

<?
mysql_close($connect);
?>