<?php
/**
 * @description Use WordPress Shortcode API for more features
 * @Docs http://codex.wordpress.org/Shortcode_API
 */

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

class FlAG_shortcodes {
	var $flag_shortcode;
	var $flag_add_script;
	var $flag_fancybox;
	var $flag_add_mousewheel;
	// register the new shortcodes
	function FlAG_shortcodes() {
	
		// do_shortcode on the_excerpt could causes several unwanted output. Uncomment it on your own risk
		// add_filter('the_excerpt', array(&$this, 'convert_shortcode'));
		// add_filter('the_excerpt', 'do_shortcode', 11);

		add_shortcode( 'flagallery', array(&$this, 'show_flashalbum' ) );
		add_shortcode( 'grandmp3', array(&$this, 'grandmp3' ) );
		add_shortcode( 'grandmusic', array(&$this, 'grandmusic' ) );
		add_shortcode( 'grandflv', array(&$this, 'grandflv' ) );
		add_shortcode( 'grandvideo', array(&$this, 'grandvideo' ) );
		add_shortcode( 'grandbanner', array(&$this, 'grandbanner' ) );
		add_shortcode( 'grandbannerwidget', array(&$this, 'grandbannerwidget' ) );
		add_action('wp_footer', array(&$this, 'add_script'));

	}

	function show_flashalbum( $atts ) {
		global $wpdb, $flagdb;

		extract(shortcode_atts(array(
			'gid' 		=> '',
			'album'		=> '',
			'name'		=> '',
			'w'		 	=> '',
			'h'		 	=> '',
			'orderby' 	=> '',
			'order'	 	=> '',
			'exclude' 	=> '',
			'skin'	 	=> '',
			'playlist' 	=> '',
			'wmode' 	=> '',
			'fullwindow'=> false,
			'align' 	=> ''
		), $atts ));
		
		$out = '';
		// make an array out of the ids
		$draft_clause = (get_option( 'flag_db_version' ) < 2.75) ? '' : 'AND status=0';
		if($album) {
			$gallerylist = $flagdb->get_album($album);
			$ids = explode( ',', $gallerylist );
			$galleryIDs = array();
			foreach ($ids as $id) {
				$galleryIDs[] = $wpdb->get_var($wpdb->prepare("SELECT gid FROM {$wpdb->flaggallery} WHERE gid = %d $draft_clause", $id));
			}
			$galleryIDs = array_filter($galleryIDs);
			if(empty($galleryIDs))
				return $out =  sprintf(__('[Gallery %s not found]','flag'),$gallerylist);

			$gids = implode('_',$galleryIDs);
			$out = flagShowFlashAlbum($gids, $name, $w, $h, $skin, $playlist, $wmode, false, $fullwindow, $align);

    } elseif($gid == "all") {
			$flag_options = get_option('flag_options');
			if(empty($orderby)){ $orderby = $flag_options['albSort']; }
			if(empty($order)){ $order = $flag_options['albSortDir']; }

			if(!in_array($orderby, array('title','rand'))) $orderby='gid';
			if(!$order) $order='DESC';
			$gallerylist = $flagdb->find_all_galleries($orderby, $order);
			if(is_array($gallerylist)) {
				$excludelist = explode(',',$exclude);
				foreach($gallerylist as $gallery) {
					if (in_array($gallery->gid, $excludelist))
						continue;
					$gids.='_'.$gallery->gid;
				}
				$gids = ltrim($gids,'_');
				$out = flagShowFlashAlbum($gids, $name, $w, $h, $skin, $playlist, $wmode, false, $fullwindow, $align);
			} else {
        $out = __('[Galleries not found]','flag');
			}
		} else {
			$ids = explode( ',', $gid );

			$galleryIDs = array();
			foreach ($ids as $id) {
				$id = intval($id);
    		$galleryIDs[] = $wpdb->get_var($wpdb->prepare("SELECT gid FROM {$wpdb->flaggallery} WHERE gid = %d  $draft_clause", $id));
   		}
			$galleryIDs = array_filter($galleryIDs);
			if(empty($galleryIDs)){
				$out =  sprintf(__('[Galleries %s not found]','flag'),$gid);
				return $out;
			}

			$gids = implode('_',$galleryIDs);
 			$out = flagShowFlashAlbum($gids, $name, $w, $h, $skin, $playlist, $wmode, false, $fullwindow, $align);
    	}

		$flag_options = get_option('flag_options');
		if($skin == '') $skin = $flag_options['flashSkin'];
		$skinpath = trailingslashit( $flag_options['skinsDirABS'] ).$skin;
		if(!is_dir($skinpath)) {
			$skin = 'minima_jn';
			$skinpath = trailingslashit( $flag_options['skinsDirABS'] ).$skin;
		} 
		$this->flag_shortcode = true;
		$this->flag_add_script = true;
		if(in_array($skin, array('slider_gallery', 'slider_gallery_demo', 'slider', 'slider_demo')) || !$flag_options['jAlterGalScript'] ){
			$this->flag_fancybox = true;
		} else {
			$this->flag_fancybox = false;
		}
		$swfmousewheel = false;
		if(file_exists($skinpath . "/settings/settings.xml")) {
			$data = file_get_contents($skinpath . "/settings/settings.xml");
			$swfmousewheel = flagGetBetween($data,'<swfmousewheel>','</swfmousewheel>');
		}
		if($swfmousewheel == 'true') $this->flag_add_mousewheel = true;

    return $out;
	}

	function add_script() {
		if ( $this->flag_shortcode ) {
			wp_register_script('flagscroll', plugins_url('/admin/js/flagscroll.js', dirname(__FILE__)), array('jquery'), '1.0', true );
			wp_print_scripts('flagscroll');
		}
		if ( $this->flag_fancybox ) {
			wp_register_style('fancybox', plugins_url('/flash-album-gallery/admin/js/jquery.fancybox-1.3.4.css') );
			wp_register_script('fancybox', plugins_url('/flash-album-gallery/admin/js/jquery.fancybox-1.3.4.pack.js'), array(), '1.3.4', true );
			wp_print_styles('fancybox');
			wp_print_scripts('fancybox');
		}
		if ( $this->flag_add_script ) {
			wp_register_script('flagscript', plugins_url('/admin/js/script.js', dirname(__FILE__)), array('jquery'), '1.1', true );
			wp_print_scripts('flagscript');
			wp_register_style('flagallery', plugins_url('/admin/css/flagallery.css', dirname(__FILE__)) );
			wp_print_styles('flagallery');
		}
		if ( $this->flag_add_mousewheel ) {
			wp_register_script('swfmousewheel', plugins_url('/admin/js/swfmousewheel.js', dirname(__FILE__)), false, '2.0', true );
			wp_print_scripts('swfmousewheel');
		}
	}

	function grandmusic( $atts ) {

		extract(shortcode_atts(array(
			'playlist'	=> '',
			'w'		 	=> '',
			'h'		 	=> '',
			'skin'		=> '',
			'is_widget'	=> 0
		), $atts ));
		$out = sprintf(__('[Playlist %s not found]','flag'),$playlist);
		if($playlist) {
			$flag_options = get_option('flag_options');
			if(!file_exists(ABSPATH.$flag_options['galleryPath'].'playlists/'.$playlist.'.xml')) {
				return $out;
			}
			$this->flag_shortcode = true;
			$this->flag_add_mousewheel = true;
            $out = flagShowMPlayer($playlist, $w, $h, $wmode='', $skin, $is_widget);
		}
        return $out;
	}

	function grandmp3( $atts ) {
		global $wpdb;
		extract(shortcode_atts(array(
			'id'	=> '',
			'autoplay'	=> 'false'
		), $atts ));
		$out = '';
		$flag_options = get_option('flag_options');
		if($id) {
			$url = wp_get_attachment_url($id);
			$url = str_replace(array('.mp3'), array(''), $url);
			if(	! flagGallery::getUserNow($_SERVER['HTTP_USER_AGENT']) ){
				$out = '<script type="text/javascript">swfobject.embedSWF("'.plugins_url('/lib/mini.swf', dirname(__FILE__)).'", "c-'.$id.'", "250", "20", "10.1.52", "expressInstall.swf", {path:"'.$url.'",bgcolor:"'.$flag_options["mpBG"].'",color1:"'.$flag_options["mpColor1"].'",color2:"'.$flag_options["mpColor2"].'",autoplay:"'.$autoplay.'"}, {wmode:"transparent"}, {id:"f-'.$id.'",name:"f-'.$id.'"});</script>';
			}
			$out .= '<div id="c-'.$id.'" class="grandmp3"><audio src="'.$url.'.mp3" controls preload="none" autobuffer="false"></audio></div>';
		}
       	return $out;
	}

	function grandvideo( $atts ) {

		extract(shortcode_atts(array(
			'playlist'	=> '',
			'w'		 	=> '',
			'h'		 	=> ''
		), $atts ));
		$out = sprintf(__('[Playlist %s not found]','flag'),$playlist);
		if($playlist) {
			$flag_options = get_option('flag_options');
			if(!file_exists(ABSPATH.$flag_options['galleryPath'].'playlists/video/'.$playlist.'.xml')) {
				return $out;
			}
			$data = file_get_contents(ABSPATH.$flag_options['galleryPath'].'playlists/video/'.$playlist.'.xml');
			$swfmousewheel = false;
			$swfmousewheel = flagGetBetween($data,'<swfmousewheel>','</swfmousewheel>');
			if($swfmousewheel == 'true') $this->flag_add_mousewheel = true;
			$this->flag_shortcode = true;
            $out = flagShowVPlayer($playlist, $w, $h);
			
		}
        return $out;
	}

	function grandflv( $atts ) {
		global $wpdb;
		extract(shortcode_atts(array(
			'id'		=> '',
			'w'			=> '',
			'h'			=> '',
			'autoplay'	=> ''
		), $atts ));
		$out = '';
		if($id) {
			$this->flag_shortcode = true;
            $out = flagShowVmPlayer($id, $w, $h, $autoplay);
		}
       	return $out;
	}
	
	function grandbanner( $atts ) {

		extract(shortcode_atts(array(
			'xml'	=> '',
			'w'		=> '',
			'h'		=> ''
		), $atts ));
		$out = sprintf(__('[XML %s not found]','flag'),$xml);
		if($xml) {
			$flag_options = get_option('flag_options');
			if(!file_exists(ABSPATH.$flag_options['galleryPath'].'playlists/banner/'.$xml.'.xml')) {
				return $out;
			}
			$data = file_get_contents(ABSPATH.$flag_options['galleryPath'].'playlists/banner/'.$xml.'.xml');
			$swfmousewheel = false;
			$swfmousewheel = flagGetBetween($data,'<swfmousewheel>','</swfmousewheel>');
			if($swfmousewheel == 'true') $this->flag_add_mousewheel = true;
			$this->flag_shortcode = true;
            $out = flagShowBanner($xml, $w, $h);
		}
        return $out;
	}

	function grandbannerwidget( $atts ) {

		extract(shortcode_atts(array(
			'xml'	=> '',
			'w'		=> '',
			'h'		=> '',
			'skin'	=> ''
		), $atts ));
		$out = sprintf(__('[XML %s not found]','flag'),$xml);
		if($xml && $skin) {
			$flag_options = get_option('flag_options');
			$skinpath = trailingslashit( $flag_options['skinsDirABS'] ).$skin;
			if(!file_exists($skinpath)) {
				return $out;
			}
			$data = @file_get_contents($skinpath);
			$swfmousewheel = false;
			$swfmousewheel = flagGetBetween($data,'<swfmousewheel>','</swfmousewheel>');
			if($swfmousewheel == 'true') $this->flag_add_mousewheel = true;
			$this->flag_shortcode = true;
            $out = flagShowWidgetBanner($xml, $w, $h, $skin);
		}
        return $out;
	}

}

// let's use it
$flagShortcodes = new FlAG_Shortcodes;	

?>