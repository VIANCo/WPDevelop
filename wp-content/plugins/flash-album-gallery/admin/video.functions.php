<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { 	die('You are not allowed to call this page directly.'); }

function get_v_playlist_data( $playlist_file ) {
	global $wpdb;	
	$playlist_content = file_get_contents($playlist_file);

	$playlist_data['title'] = flagGallery::flagGetBetween($playlist_content,'<title><![CDATA[',']]></title>');
	$playlist_data['skin'] = flagGallery::flagGetBetween($playlist_content,'<skin><![CDATA[',']]></skin>');
	$playlist_data['width'] = flagGallery::flagGetBetween($playlist_content,'<width><![CDATA[',']]></width>');
	$playlist_data['height'] = flagGallery::flagGetBetween($playlist_content,'<height><![CDATA[',']]></height>');
	$playlist_data['description'] = flagGallery::flagGetBetween($playlist_content,'<description><![CDATA[',']]></description>');
	preg_match_all( '|<item id="(.*)">|', $playlist_content, $items );
	$playlist_data['items'] = $items[1];
	return $playlist_data;
}

/**
 * Check the playlists directory and retrieve all playlist files with playlist data.
 *
 */
function get_v_playlists($playlist_folder = '') {

	$flag_options = get_option('flag_options');
	$flag_playlists = array ();
	$playlist_root = ABSPATH.$flag_options['galleryPath'].'playlists/video';
	if( !empty($playlist_folder) )
		$playlist_root = $playlist_folder;

	// Files in flagallery/playlists directory
	$playlists_dir = @ opendir( $playlist_root);
	$playlist_files = array();
	if ( $playlists_dir ) {
		while (($file = readdir( $playlists_dir ) ) !== false ) {
			if ( substr($file, 0, 1) == '.' )
				continue;
			if ( substr($file, -4) == '.xml' )
				$playlist_files[] = $file;
		}
	}
	@closedir( $playlists_dir );

	if ( !$playlists_dir || empty($playlist_files) )
		return $flag_playlists;

	foreach ( $playlist_files as $playlist_file ) {
		if ( !is_readable( "$playlist_root/$playlist_file" ) )
			continue;

		$playlist_data = get_v_playlist_data( "$playlist_root/$playlist_file" );

		if ( empty ( $playlist_data['title'] ) )
			continue;

		$flag_playlists[basename( $playlist_file, ".xml" )] = $playlist_data;
	}
	uasort( $flag_playlists, create_function( '$a, $b', 'return strnatcasecmp( $a["title"], $b["title"] );' ));

	return $flag_playlists;
}

function flagSave_vPlaylist($title,$descr,$data,$file='',$skinaction='') {
	global $wpdb;
	if(!trim($title)) {
		$title = 'default';
	}
	$title = htmlspecialchars_decode(stripslashes($title), ENT_QUOTES);
	$descr = htmlspecialchars_decode(stripslashes($descr), ENT_QUOTES);
	if (!$file) {
		$file = sanitize_flagname($title);
	}

	if(!is_array($data))
		$data = explode(',', $data);

	$flag_options = get_option('flag_options');
    $skin = isset($_POST['skinname'])? sanitize_flagname($_POST['skinname']) : 'video_default';
	if(empty($skinaction))
    	$skinaction = isset($_POST['skinaction'])? sanitize_key($_POST['skinaction']) : 'update';
	$skinpath = trailingslashit( $flag_options['skinsDirABS'] ).$skin;
	$playlistPath = ABSPATH.$flag_options['galleryPath'].'playlists/video/'.$file.'.xml';
	$settings = '';
	if( file_exists($playlistPath) && ($skin == $skinaction) ) {
		$settings = file_get_contents($playlistPath);
	} elseif( file_exists($skinpath . "/settings/settings.xml") ) {
		$settings = file_get_contents($skinpath . "/settings/settings.xml");
	} else {
		flagGallery::show_message(__("Can't find skin settings", 'flag'));
		return;
	}
	$properties = flagGallery::flagGetBetween($settings,'<properties>','</properties>');
	if(empty($properties)) {
		flagGallery::show_message(__("Can't find skin settings", 'flag'));
		return;
	}

	if(count($data)) {
		$content = '<gallery>
<properties>'.$properties.'</properties>
<category id="'.$file.'">
	<properties>
		<title><![CDATA['.$title.']]></title>
		<description><![CDATA['.$descr.']]></description>
		<skin><![CDATA['.$skin.']]></skin>
	</properties>
	<items>';

		foreach( (array) $data as $id) {
			$flv = get_post($id);
			if( in_array( $flv->post_mime_type, array('video/x-flv') ) ) {
			    $thumb = get_post_meta($id, 'thumbnail', true);
				$content .= '
		<item id="'.$flv->ID.'">
          <track>'.wp_get_attachment_url($flv->ID).'</track>
          <title><![CDATA['.$flv->post_title.']]></title>
          <description><![CDATA['.$flv->post_content.']]></description>
          <thumbnail>'.$thumb.'</thumbnail>
        </item>';
			}
		}
		$content .= '
	</items>
</category>
</gallery>';
		// Save options
		$flag_options = get_option('flag_options');
		if(wp_mkdir_p(ABSPATH.$flag_options['galleryPath'].'playlists/video/')) {
			if( flagGallery::saveFile($playlistPath,$content,'w') ){
				flagGallery::show_message(__('Playlist Saved Successfully','flag'));
			}
		} else {
			flagGallery::show_message(__('Create directory please:','flag').'"/'.$flag_options['galleryPath'].'playlists/video/"');
		}
	}
}

function flagSave_vPlaylistSkin($file) {
	$file = sanitize_flagname($file);
	$flag_options = get_option('flag_options');
	$playlistPath = ABSPATH.$flag_options['galleryPath'].'playlists/video/'.$file.'.xml';
	// Save options
	$title = esc_html($_POST['playlist_title']);
	$descr = esc_html($_POST['playlist_descr']);
	$items = get_v_playlist_data($playlistPath);
	$data = $items['items'];
	flagSave_vPlaylist($title,$descr,$data,$file,$skinaction='update');
}

function flag_v_playlist_delete($playlist) {
	$playlist = sanitize_flagname($playlist);
	$flag_options = get_option('flag_options');
	$playlistXML = ABSPATH.$flag_options['galleryPath'].'playlists/video/'.$playlist.'.xml';
	if(file_exists($playlistXML)){
		if(unlink($playlistXML)) {
			flagGallery::show_message("'".$playlist.".xml' ".__('deleted','flag'));
		}
	}
}

?>