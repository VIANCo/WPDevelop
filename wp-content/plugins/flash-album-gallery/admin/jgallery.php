<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

global $wpdb, $post;
$xml = array();
$flag_options = get_option ('flag_options');
$siteurl = site_url();
$c = array();
$isCrawler = isset($_SERVER['HTTP_USER_AGENT'])? flagGetUserNow($_SERVER['HTTP_USER_AGENT']) : false;
extract($altColors);
$bg = ($wmode == 'window')? '#'.$Background : 'transparent';
$xml['alt'] = '<style type="text/css" scoped="scoped">';
if(!$isCrawler) {
	$xml['alt'] .= '@import url("'.plugins_url('/admin/css/flagallery_nocrawler.css', dirname(__FILE__)).'");';
}
$xml['alt'] .= '@import url("'.plugins_url('/admin/css/flagallery_noflash.css', dirname(__FILE__)).'");';
if($isCrawler) {
	$xml['alt'] .= '.flag_alternate .flagCatMeta h4 { padding: 4px 10px; margin: 7px 0; border: none; font: 14px Tahoma; text-decoration: none; background:#292929 none; color: #ffffff; }';
	$xml['alt'] .= '.flag_alternate .flagCatMeta p { font-size: 12px; }';
}
if($BarsBG) {
	$bgBar = ($wmode == 'window')? '#'.$BarsBG : 'transparent';
	if(!$isCrawler){
		$xml['alt'] .= "#fancybox-title-over .title { color: #{$TitleColor}; }";
		$xml['alt'] .= "#fancybox-title-over .descr { color: #{$DescrColor}; }";
		$xml['alt'] .= ".flag_alternate .flagcatlinks { background-color: #{$BarsBG}; }";
		$xml['alt'] .= ".flag_alternate .flagcatlinks a.flagcat, span.flag_pic_counters { color: #{$CatColor}; background-color: #{$CatBGColor}; }";
		$xml['alt'] .= ".flag_alternate .flagcatlinks a.active, .flag_alternate .flagcatlinks a.flagcat:hover { color: #{$CatColorOver}; background-color: #{$CatBGColorOver}; }";
	}
	$xml['alt'] .= ".flag_alternate .flagcategory a.flag_pic_alt { background-color: #{$ThumbBG}; border: 2px solid #{$ThumbBG}; color: #{$ThumbBG}; }";
	$xml['alt'] .= ".flag_alternate .flagcategory a.flag_pic_alt:hover { background-color: #{$ThumbBG}; border: 2px solid #{$ThumbLoaderColor}; color: #{$ThumbLoaderColor}; }";
	$xml['alt'] .= ".flag_alternate .flagcategory a.flag_pic_alt.current, .flag_alternate .flagcategory a.flag_pic_alt.last { border-color: #{$ThumbLoaderColor}; }";
}
if($altColors['FullWindow'] && !$isCrawler){
	$xml['alt'] .= ".flagcatlinks a.backlink { color: #{$CatColor}; background-color: #{$CatBGColor}; }";
}
$xml['alt'] .= '</style>';
if(!$isCrawler){
	if(!intval($flag_options['jAlterGalScript'])) {
		$xml['alt'] .= '<style type="text/css" scoped="scoped">@import url("'.plugins_url('/flash-album-gallery/admin/js/jquery.fancybox-1.3.4.css').'");</style>';
		$xml['alt'] .= "<script type='text/javascript' src='".plugins_url('/flash-album-gallery/admin/js/jquery.fancybox-1.3.4.pack.js')."'></script>";
		$xml['alt'] .= "<script type='text/javascript'>var ExtendVar='fancybox', hitajax = '".plugins_url('/lib/hitcounter.php', dirname(__FILE__))."';</script>";
	} else if(intval($flag_options['jAlterGalScript']) == 1) {
		$xml['alt'] .= "<style type='text/css'>@import url('".plugins_url('/admin/js/photoswipe/photoswipe.css', dirname(__FILE__))."');</style>";
		$xml['alt'] .= "<script type='text/javascript' src='".plugins_url('/admin/js/photoswipe/klass.min.js', dirname(__FILE__))."'></script>";
		$xml['alt'] .= "<script type='text/javascript' src='".plugins_url('/admin/js/photoswipe/code.photoswipe.jquery-3.0.5.min.js', dirname(__FILE__))."'></script>";
		$xml['alt'] .= "<script type='text/javascript'>var ExtendVar='photoswipe', hitajax = '".plugins_url('/lib/hitcounter.php', dirname(__FILE__))."';</script>";
	}
 }

$xml['alt'] .= '<div id="'.$skinID.'_jq" class="flag_alternate noLightbox"><div class="flagcatlinks">';
			if($altColors['FullWindow'] && !$isCrawler){
				$flag_custom = get_post_custom($post->ID);
				$backlink = $flag_custom["mb_button_link"][0];
				if(!$backlink || $backlink == 'http://'){ $backlink = $_SERVER["HTTP_REFERER"]; }
				if($backlink){
					$xml['alt'] .= '<a id="backlink" class="backlink" href="'.$backlink.'">'.$flag_custom["mb_button"][0].'</a>';
				}
			}
		$xml['alt'] .= '</div>';

$gID = explode( '_', $galleryID ); // get the gallery id
if ( is_user_logged_in() ) $exclude_clause = '';
else $exclude_clause = ' AND exclude<>1 ';
$i = 0;
if(isset($flag_options['disableViews']) && !empty($flag_options['disableViews'])){
	$disableViews = 1;
} else { $disableViews = 0; }
if((get_option( 'flag_db_version' ) < 2.75)){
	$select_columns = 'pid, galleryid, filename, description, alttext, imagedate, sortorder, hitcounter, total_value, total_votes, meta_data';
	$exclude_clause = '';
} else {
	$select_columns = 'pid, galleryid, filename, description, alttext, link, imagedate, sortorder, hitcounter, total_value, total_votes, meta_data';
}

foreach ( $gID as $galID ) {
	$galID = (int) $galID;
	$status = $wpdb->get_var("SELECT status FROM $wpdb->flaggallery WHERE gid={$galID}");
	if(intval($status)){
		continue;
	}

	if ( $galID == 0) {
		$thegalleries = array();
		$thepictures = $wpdb->get_results("SELECT $select_columns FROM $wpdb->flagpictures WHERE 1=1 {$exclude_clause} ORDER BY {$flag_options['galSort']} {$flag_options['galSortDir']} ", ARRAY_A);
	} else {
		$thegalleries = $wpdb->get_row("SELECT gid, name, path, title, galdesc FROM $wpdb->flaggallery WHERE gid={$galID}", ARRAY_A);
		$thepictures = $wpdb->get_results("SELECT $select_columns FROM $wpdb->flagpictures WHERE galleryid = '{$galID}' {$exclude_clause} ORDER BY {$flag_options['galSort']} {$flag_options['galSortDir']} ", ARRAY_A);
	}
	$captions = '';


	if (is_array ($thepictures) && count($thegalleries) && count($thepictures)){
		$thegalleries = array_map('stripslashes', $thegalleries);
		$galdesc = $thegalleries['galdesc'];
		$thegalleries['galdesc'] = htmlspecialchars_decode($galdesc);
		$a = $thegalleries;

		$xml['alt'] .= '<div class="flagCatMeta">';
		$xml['alt'] .= '<h4>'.htmlspecialchars_decode($thegalleries['title'], ENT_QUOTES).'</h4>';
		$xml['alt'] .= '<p>'.str_replace('"','', strip_tags(htmlspecialchars_decode($galdesc, ENT_QUOTES))).'</p>';
		$xml['alt'] .= '</div>';
		$xml['alt'] .= '<div class="flagcategory" id="gid_'.$galID.'_'.$skinID.'">';
			$n = count($thepictures);
			$var = floor($n/5);
			if($var==0 || $var > 4) $var=4;
			$split = ceil($n/$var);
			$j=0;
			$b = array();
		foreach ($thepictures as $picture) {
			$meta_data = maybe_unserialize($picture['meta_data']);
			$picture['width'] = $meta_data['width'];
			$picture['height'] = $meta_data['height'];
			$picture['thumbnail'] = $meta_data['thumbnail']['width'].'x'.$meta_data['thumbnail']['height'];
			$webview = '';
			if(isset($meta_data['webview'])){
				$picture['webview'] = $meta_data['webview'][0].'x'.$meta_data['webview'][1];
				$webview = $flag_options['optimized_imgs']? '/webview' : '';
			}
			unset($picture['meta_data']);
			$picture = array_map('stripslashes', $picture);
			$b['data'][] = $picture;

			$pid = intval($picture['pid']);

			if ($isCrawler){
				$xml['alt'] .= '<a style="display:block; overflow: hidden; height: 100px; width: 115px; margin-bottom: 10px; background-color: #eeeeee; background-position: 22px 44px; text-align: left;" class="i'. $j++ .' flag_pic_alt" href="'.$siteurl.'/'.$thegalleries['path'].$webview.'/'.$picture['filename'].'" id="flag_pic_'.$pid.'"><img style="float:left; margin-right: 10px; width: auto; height: auto; min-height:100px; min-width:115px;" title="'.esc_attr(strip_tags(htmlspecialchars_decode($picture['alttext']))).'" alt="'.esc_attr(strip_tags(htmlspecialchars_decode($picture['alttext']))).'" src="'.$siteurl.'/'.$thegalleries['path'].'/thumbs/thumbs_'.$picture['filename'].'" /><span style="display: block; overflow: hidden; text-decoration: none; color: #000; font-weight: normal;" class="flag_pic_desc" id="flag_desc_'.$pid.'"><strong>'.strip_tags(htmlspecialchars_decode($picture['alttext'])).'</strong><br />'.strip_tags(htmlspecialchars_decode($picture['description'])).'</span></a>';
			} else {
				if(!$disableViews){
					$views = (intval($picture['hitcounter']) < 10000) ? $picture['hitcounter'] : round($picture['hitcounter']/1000, 1).'k';
					$likes = (intval($picture['total_votes']) < 10000) ? $picture['total_votes'] : round($picture['total_votes']/1000, 1).'k';
					$views_panel = '<span class="flag_pic_counters"><i>'.$views.'</i><b>'.$likes.'</b></span>';
				} else {
					$views_panel = '';
				}
				$xml['alt'] .= '<a class="i'. $j++ .' flag_pic_alt" href="'.$siteurl.'/'.$thegalleries['path'].$webview.'/'.$picture['filename'].'" id="flag_pic_'.$pid.'" title="'.esc_attr(strip_tags(htmlspecialchars_decode($picture['alttext']))).'">[img src='.$siteurl.'/'.$thegalleries['path'].'/thumbs/thumbs_'.$picture['filename'].']'.$views_panel.'<span class="flag_pic_desc" id="flag_desc_'.$pid.'"><strong>'.$picture['alttext'].'</strong><br /><span>'.$picture['description'].'</span></span></a>';
			}
		}
		$xml['alt'] .= '</div>';
		$c['galleries'][] = $a + $b;
	}
}
$xml['alt'] .= '</div>';
$d = array('properties'=>$data) + $c + $musicData;
$xml['json'] = json_encode($d);
?>