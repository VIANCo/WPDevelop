<?php if(file_exists(dirname(__FILE__) . '/flag-config.php')){
	require_once( dirname(__FILE__) . '/flag-config.php');
} else if(file_exists(dirname(__FILE__) . '/wp-load.php')){
	require_once( dirname(__FILE__) . '/wp-load.php');
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
<title><?php bloginfo('name'); ?> <?php if ( is_single() ) { ?> &raquo; Blog Archive <?php } ?> <?php wp_title(); ?> - <?php bloginfo('description'); ?> </title>
</head>
<body style="margin: 0; padding: 0;">
<div id="page">
<script language="JavaScript" src="<?php echo plugins_url('/flash-album-gallery/admin/js/jquery.js'); ?>" type="text/javascript"></script>
<script language="JavaScript" src="<?php echo plugins_url('/flash-album-gallery/admin/js/swfobject.js'); ?>" type="text/javascript"></script>
<?php $flag_options = get_option('flag_options');
if(isset($_GET['l'])) {
	$linkto = intval($_GET['l']);
} else {
	$posts = get_posts(array("showposts" => 1));
	$linkto = $posts[0]->ID;
}
if(isset($_GET['i'])) {
	$skin = '';
	if(isset($_GET['f']) && false === strpos($_GET['f'], '..') ){
		$skin = sanitize_flagname($_GET['f']);
		$skinpath = trailingslashit( $flag_options['skinsDirABS'] ).$skin;
	}
	$h = isset($_GET['h'])? intval($_GET['h']) : (int) $flag_options['flashHeight'];

	$gids = $_GET['i'];
	if($gids=='all') {
		/** @var $flagdb flagdb */
		global $flagdb;
		$gids='';
		$orderby=$flag_options['albSort'];
		$order=$flag_options['albSortDir'];
	  $gallerylist = $flagdb->find_all_galleries($orderby, $order);
	  if(is_array($gallerylist)) {
			foreach($gallerylist as $gallery) {
				$gids.='_'.$gallery->gid;
			}
			$gids = ltrim($gids,'_');
		}
	} else {
		$gids = explode('_',$gids);
		$mapping = array_map('intval', $gids);
		$gids = implode('_',$mapping);
	}

	if($gids){

		echo flagShowFlashAlbum($gids, $name='Gallery', $width='100%', $height=$h, $skin, $playlist='', $wmode='opaque', $linkto); ?>

<link href="<?php echo plugins_url('/flash-album-gallery/admin/js/jquery.fancybox-1.3.4.css'); ?>" rel="stylesheet" type="text/css" />
<script language="JavaScript" src="<?php echo plugins_url('/flash-album-gallery/admin/js/jquery.fancybox-1.3.4.pack.js'); ?>" type="text/javascript"></script>
<script language="JavaScript" src="<?php echo plugins_url('/flash-album-gallery/admin/js/flagscroll.js'); ?>" type="text/javascript"></script>
<script language="JavaScript" src="<?php echo plugins_url('/flash-album-gallery/admin/js/script.js'); ?>" type="text/javascript"></script>

<?php }
} ?>

<?php
if(isset($_GET['m'])) {
	$file = sanitize_flagname($_GET['m']);
	$playlistpath = $flag_options['galleryPath'].'playlists/'.$file.'.xml';
	if(file_exists($playlistpath))
		echo flagShowMPlayer($file, $width='', $height='', $wmode='opaque');
	else
		_e("Can't find playlist");
}
?>
<?php
if(isset($_GET['v'])) {
	$height = isset($_GET['h'])? intval($_GET['h']) : '';
	$width = isset($_GET['w'])? '100%' : '';
	$file = sanitize_flagname($_GET['v']);
	$playlistpath = $flag_options['galleryPath'].'playlists/video/'.$file.'.xml';
	if(file_exists($playlistpath))
		echo flagShowVPlayer($file, $width, $height, $wmode='opaque');
	else
		_e("Can't find playlist");
}
?>
<?php
if(isset($_GET['mv'])) {
	$height = isset($_GET['h'])? intval($_GET['h']) : '';
	$width = '100%';
	$mv = intval($_GET['mv']);
	echo flagShowVmPlayer($mv, $width, $height, $autoplay='true');
}
?>
<?php
if(isset($_GET['b'])) {
	$file = sanitize_flagname($_GET['b']);
	$playlistpath = $flag_options['galleryPath'].'playlists/banner/'.$file.'.xml';
	if(file_exists($playlistpath))
		echo flagShowBanner($file, $width='', $height='', $wmode='opaque');
	else
		_e("Can't find playlist");
}
?>
</div>
</body>
</html>