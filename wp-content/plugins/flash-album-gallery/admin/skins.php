<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }
// look up for the path
require_once( dirname(dirname(__FILE__)) . '/flag-config.php');

// check for correct capability
if ( !is_user_logged_in() )
	die('-1');

// check for correct FlAG capability
if ( !current_user_can('FlAG Change skin') )
	die('-1');

global $flag;
$flag_options = get_option('flag_options');

require_once (dirname (__FILE__) . '/get_skin.php');

if( isset($_POST['installskin']) ) {
	check_admin_referer('skin-upload');
	require_once (dirname (__FILE__) . '/skin_install.php');
}
if( isset($_POST['skinzipurl']) ) {
	check_admin_referer('skin_install');
	$url = 'http://photogallerycreator.com/depository/'.sanitize_flagname(basename($_POST['skinzipurl']));
	$skins_dir = $flag_options['skinsDirABS'];
	$mzip = download_url($url);
	if(is_wp_error($mzip)){
		$userAgent = 'Googlebot/2.1 (http://www.googlebot.com/bot.html)';
		$filename = basename($url);
		$mzip = rtrim($skins_dir,'/').'/'.$filename;
		$ch = curl_init();
		$fp = fopen("$mzip", "w");
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_HEADER,0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER,true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		$data = curl_exec($ch);
		curl_close($ch);
		fclose($fp);
	}

	$mzip = str_replace("\\", "/", $mzip);

	if( class_exists('ZipArchive') ){
		$zip = new ZipArchive;
		$zip->open($mzip);
		$zip->extractTo($skins_dir);
		$zip->close();
	}else{
		require_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');
		$archive = new PclZip($mzip);
		$list = $archive->extract($skins_dir);
		if ($list == 0) {
			die("ERROR : '".$archive->errorInfo(true)."'");
		}

	}
	if(@unlink($mzip)){
		flagGallery::show_message( __('The skin installed successfully.', 'flag') );
	}
}
add_action('install_skins_upload', 'upload_skin');
function upload_skin() {

	echo '<div id="uploadaction">';
	echo '<h3>'.__('Install info', 'flag').'</h3>';

	if ( ! ( ( $uploads = wp_upload_dir() ) && false === $uploads['error'] ) ) {
		echo "<p>".$uploads['error']."</p>\n";
	} else {
		$filename = false;
		if ( !empty($_FILES) ) {
			$filename = $_FILES['skinzip']['name'];
		}	else if ( isset($_GET['package']) ) {
			$filename = urlencode($_GET['package']);
		}
		if ( !$filename ) {
			echo "<p>".__('No skin Specified', 'flag')."</p>\n";
		} else {
			check_admin_referer('skin-upload');
			echo '<h4>', sprintf( __('Installing Skin from file: %s', 'flag'), basename($filename) ), '</h4>';

			//Handle a newly uploaded file, Else assume it was
			if ( !empty($_FILES) ) {
				$filename = wp_unique_filename( $uploads['basedir'], $filename );
				$local_file = $uploads['basedir'] . '/' . $filename;

				// Move the file to the uploads dir
				if ( false === @move_uploaded_file( $_FILES['skinzip']['tmp_name'], $local_file) )
					echo "<p>".sprintf( __('The uploaded file could not be moved to %s.', 'flag'), $uploads['path'])."</p>\n";
			} else {
				$local_file = $uploads['basedir'] . '/' . $filename;
			}
			$installed_skin = do_skin_install_local_package($local_file, $filename);
		}
	}
	echo '</div>';
}

/**
 * Get skin options
 *
 */
function flag_skin_options_tab() {
	//Get the active skin
	$flag_options = get_option('flag_options');
	$active_skin_settings = $flag_options['skinsDirABS'].$flag_options['flashSkin'].'/settings/settings.xml';
	if(!file_exists($active_skin_settings)) {
		$active_skin = $flag_options['skinsDirABS'].$flag_options['flashSkin'].'/'.$flag_options['flashSkin'].'.php';
		include_once($active_skin);
	} else {
		include_once(dirname(__FILE__).'/skin_options.php');
	}
	if(function_exists('flag_skin_options')) {
		flag_skin_options();
	} else {
		include_once(FLAG_ABSPATH.'admin/db_skin_color_scheme.php');
		flag_skin_options();
	}
}


if ( isset($_POST['updateskinoption']) ) {
	check_admin_referer('skin_settings');
	// get the hidden option fields, taken from WP core
	$options = false;
	if ( $_POST['skin_options'] )
		$options = explode(',', stripslashes($_POST['skin_options']));
	elseif ( $_POST['skinoptions'] )
		$options = explode(',', stripslashes($_POST['skinoptions']));
	if ($options) {
		$settings_content = '<?php '."\n";
		foreach ($options as $option) {
			$option = trim($option);
			$value = trim($_POST[$option]);
			$flag->options[$option] = $value;
			$settings_content .= '$'.$option.' = \''.str_replace('#','',$value)."';\n";
		}
		$settings_content .= '?>'."\n";
		// the path should always end with a slash
		$flag->options['galleryPath']    = trailingslashit($flag->options['galleryPath']);
	}
	// Save options
	update_option('flag_options', $flag->options);
	if( flagGallery::saveFile($flag_options['skinsDirABS'].$flag_options['flashSkin'].'_settings.php',$settings_content,'w') ){
		flagGallery::show_message(__('Update Successfully','flag'));
	}
}

if ( isset($_POST['license_key']) ) {
	check_admin_referer('skin-api');
	$license_key = mysql_real_escape_string($_POST['license_key']);
	$flag_options['license_key'] = trim($license_key);
	update_option('flag_options', $flag_options);
 	flagGallery::show_message(__('License Key Updated','flag'));
}

if(!empty($flag_options['license_key'])){
	if(function_exists('curl_init')){
		$ch = curl_init('http://mypgc.co/app/account_st.php');
		curl_setopt ($ch, CURLOPT_REFERER, home_url());
		curl_setopt ($ch, CURLOPT_POST, 1);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, array('check_status'=>$flag_options['license_key']));
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt ($ch, CURLOPT_TIMEOUT, 10);
		$status = curl_exec ($ch);
		curl_close ($ch);
		if($status === '0'){
			$flag_options['license_key'] = '';
			update_option('flag_options', $flag_options);
			flagGallery::show_message(__('Your license key was deactivated','flag'));
		} elseif($status === ''){
			$flag_options['license_key'] = '';
			update_option('flag_options', $flag_options);
			flagGallery::show_message(__('Bad Licence Key','flag'));
		}
	} else {
		flagGallery::show_message(__('cURL library is not installed on your server.','flag'));
	}
}

if ( isset($_POST['updateoption']) ) {
	check_admin_referer('flag_settings');
	// get the hidden option fields, taken from WP core
	$options = false;
	if ( $_POST['page_options'] )
		$options = explode(',', stripslashes($_POST['page_options']));
	if ($options) {
		foreach ($options as $option) {
			$option = trim($option);
			$value = trim($_POST[$option]);
			$flag->options[$option] = $value;
		}
		// the path should always end with a slash
		$flag->options['galleryPath']    = trailingslashit($flag->options['galleryPath']);
	}
	// Save options
	update_option('flag_options', $flag->options);
 	flagGallery::show_message(__('Update Successfully','flag'));
}


if ( isset($_GET['delete']) ) {
	check_admin_referer('delete_skin');
	$delskin = sanitize_flagname($_GET['delete']);
	if ( current_user_can('FlAG Delete skins') && false === strpos($delskin, '..') ) {
		if ( $flag_options['flashSkin'] != $delskin ) {
			$skins_dir = trailingslashit( $flag_options['skinsDirABS'] );
			$skin = $skins_dir.$delskin.'/';
			if(basename($skin) != 'flagallery-skins') {
				if ( is_dir($skin) ) {
					if( flagGallery::flagFolderDelete($skin) ) {
						flagGallery::show_message( __('Skin','flag').' \''.$delskin.'\' '.__('deleted successfully','flag') );
					} else {
						flagGallery::show_message( __('Can\'t find skin directory ','flag').' \''.$delskin.'\' '.__('. Try delete it manualy via ftp','flag') );
					}
				}
			} else {
				flagGallery::show_message( __('Can\'t find skin directory ','flag').' \''.$delskin.'\' '.__('. Try delete it manualy via ftp','flag') );
			}
		} else {
			flagGallery::show_message( __('You need activate another skin before delete it','flag') );
		}
	} else {
		wp_die(__('You do not have sufficient permissions to delete skins of Grand Flagallery.'));
	}
}

if( isset($_GET['skin']) ) {
	check_admin_referer('set_default_skin');
	$set_skin = sanitize_flagname($_GET['skin']);
	if($flag_options['flashSkin'] != $set_skin) {
		$aValid = array('-', '_');
		if(!ctype_alnum(str_replace($aValid, '', $set_skin))){
			die('try again');
		}
		$active_skin = $flag_options['skinsDirABS'].$set_skin.'/'.$set_skin.'.php';
		if(!file_exists($active_skin)){
			die('try again');
		}
		$flag_options['flashSkin'] = $set_skin;
		include_once($active_skin);
		update_option('flag_options', $flag_options);
		flagGallery::show_message( __('Skin','flag').' \''.$set_skin.'\' '.__('activated successfully. Optionally it can be overwritten with shortcode parameter.','flag') );
	}
}
$type = isset($_GET['type'])? sanitize_key($_GET['type']) : '';
switch($type){
	case '':
		$stype = 'gallery';
		$new_skins = __('New Photo Skins', 'flag');
		break;
	case 'm':
		$stype = 'music';
		$new_skins = __('New Music Skins', 'flag');
		break;
	case 'v':
		$stype = 'video';
		$new_skins = __('New Video Skins', 'flag');
		break;
	case 'b':
		$stype = 'banner';
		$new_skins = __('New Banner Skins', 'flag');
		break;
	case 'w':
		$stype = 'widget';
		$new_skins = __('New Widget Skins', 'flag');
		break;
	default:
		$stype = 'gallery';
		$new_skins = __('New Photo Skins', 'flag');
		break;
}

if( isset($_GET['skins_refresh']) ) {
	check_admin_referer('skins_refresh');
	// upgrade plugin
	require_once(FLAG_ABSPATH . 'admin/tuning.php');
	$ok = flag_tune();
	if($ok)
		flagGallery::show_message( __('Skins refreshed successfully','flag') );
}
?>
<div id="slider" class="wrap">
	<ul id="tabs" class="tabs">
<?php if( current_user_can('FlAG Add skins') ) { ?>
		<li class="selected"><a href="#" rel="addskin"><?php _e('Add new skin', 'flag'); ?></a></li>
<?php } ?>
		<li><a href="#" rel="skinoptions"><?php _e('Active Skin Options', 'flag'); ?></a></li>
	</ul>

<?php if( current_user_can('FlAG Add skins') ) { ?>
	<div id="addskin" class="cptab">
		<h2><?php _e('Add new skin', 'flag'); ?></h2>
		<h4><?php _e('Install a skin in .zip format', 'flag'); ?></h4>
		<p><?php _e('If you have a skin in a .zip format, You may install it by uploading it here.', 'flag'); ?></p>
		<form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin.php?page=flag-skins'); ?>">
			<?php wp_nonce_field( 'skin-upload'); ?>
			<p><input type="file" name="skinzip" />
			<input type="submit" class="button" name="installskin" value="<?php _e('Install Now', 'flag'); ?>" /></p>
		</form>
		<?php if( isset($_POST['installskin']) ) {
			do_action('install_skins_upload');
		} ?>
	</div>
<?php } ?>

	<div id="skinoptions" class="cptab">
		<h2><?php _e('Active Skin Options', 'flag'); ?></h2>
		<?php flag_skin_options_tab(); ?>
	</div>

	<script type="text/javascript">
		/* <![CDATA[ */
		//noinspection JSPotentiallyInvalidConstructorUsage
		var cptabs=new ddtabcontent("tabs");
		cptabs.setpersist(false);
		cptabs.setselectedClassTarget("linkparent");
		cptabs.init();
		/* ]]> */
	</script>
</div>

<?php if( current_user_can('FlAG Add skins') ) { ?>
<div id="skinapikey">
	<h2><?php _e('Skin License Key', 'flag'); ?></h2>
	<p><?php _e('If you have license key then paste it here.', 'flag'); ?></p>
	<form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin.php?page=flag-skins'); ?>">
		<?php wp_nonce_field( 'skin-api'); ?>
		<p><input type="text" name="license_key" value="<?php echo $flag_options['license_key'] ?>" size="55" />
		<input type="submit" class="button" value="<?php _e('Save', 'flag'); ?>" /></p>
	</form>
</div>
<?php } ?>

<div class="wrap" style="min-width: 878px;">
<h2><?php _e('Skins', 'flag'); ?>:</h2>
<p style="float: right; display:none;"><a class="button" href="<?php echo wp_nonce_url('admin.php?page=flag-skins&amp;skins_refresh=1', 'skins_refresh'); ?>"><?php _e('Refresh / Update Skins', 'flag'); ?></a></p>
<p><a class="button<?php if(!$type) echo '-primary'; ?>" href="<?php echo admin_url('admin.php?page=flag-skins'); ?>"><span style="font-size: 14px;"><?php _e('Photo skins', 'flag'); ?></span></a>&nbsp;&nbsp;&nbsp;
<a class="button<?php if($type == 'm') echo '-primary'; ?>" href="<?php echo admin_url('admin.php?page=flag-skins&amp;type=m'); ?>"><span style="font-size: 14px;"><?php _e('Music skins', 'flag'); ?></span></a>&nbsp;&nbsp;&nbsp;
<a class="button<?php if($type == 'v') echo '-primary'; ?>" href="<?php echo admin_url('admin.php?page=flag-skins&amp;type=v'); ?>"><span style="font-size: 14px;"><?php _e('Video skins', 'flag'); ?></span></a>&nbsp;&nbsp;&nbsp;
<a class="button<?php if($type == 'b') echo '-primary'; ?>" href="<?php echo admin_url('admin.php?page=flag-skins&amp;type=b'); ?>"><span style="font-size: 14px;"><?php _e('Banner skins', 'flag'); ?></span></a>&nbsp;&nbsp;&nbsp;
<a class="button<?php if($type == 'w') echo '-primary'; ?>" href="<?php echo admin_url('admin.php?page=flag-skins&amp;type=w'); ?>"><span style="font-size: 14px;"><?php _e('Widget skins', 'flag'); ?></span></a>
</p>

<?php
$all_skins = get_skins(false,$type);
$total_all_skins = count($all_skins);

	$skins_remote_xml = 'https://dl.dropbox.com/u/104873029/flagallery_skins/skins_v2.xml';
	// not installed skins
	$skins_xml = @simplexml_load_file($skins_remote_xml, 'SimpleXMLElement', LIBXML_NOCDATA);
	$all_skins_arr = $skins_by_type = array();
	$skins_xml_error = false;
	if(empty($skins_xml)){
		$ch = curl_init($skins_remote_xml);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		$skins_xml = @simplexml_load_string(curl_exec($ch));
		curl_close ($ch);
	}
	if(!empty($skins_xml)) {
		foreach($skins_xml as $skin){
			$suid = (string) $skin->uid;
			$skintype = (string) $skin->type;
			$all_skins_arr[$suid] = get_object_vars($skin);
			$skins_by_type[$skintype][$suid] = $all_skins_arr[$suid];
		}
	} else {
		//$skins_xml_error = __('URL file-access is disabled in the server configuration.', 'flag');
		$skins_xml_error = __('cURL library is not installed on your server.','flag').'<br>'.__('Download skins from http://photogallerycreator.com', 'flag');
	}


?>

<div style="width:70%; overflow: hidden; float: left;">
<table class="widefat" cellspacing="0" id="skins-table">
	<thead>
	<tr>
		<th scope="col" class="manage-column"><?php _e('Skin', 'flag'); ?></th>
		<th scope="col" class="manage-column"><?php _e('Description', 'flag'); ?></th>
		<th scope="col" class="action-links"><?php _e('Action', 'flag'); ?></th>
	</tr>
	</thead>

	<tfoot>
	<tr>
		<th scope="col" class="manage-column"><?php _e('Skin', 'flag'); ?></th>
		<th scope="col" class="manage-column"><?php _e('Description', 'flag'); ?></th>
		<th scope="col" class="action-links"><?php _e('Action', 'flag'); ?></th>
	</tr>
	</tfoot>

	<tbody class="skins">
<?php

	if ( empty($all_skins) ) {
		echo '<tr>
			<td colspan="3">' . __('No skins to show') . '</td>
		</tr>';
	}
	foreach ( (array)$all_skins as $skin_file => $skin_data) {
		$class = ( dirname($skin_file) == $flag_options['flashSkin'] ) ? 'active' : 'inactive';
		if(!empty($skin_data['uid'])){
			$suid = (string) $skin_data['uid'];
			if(isset($all_skins_arr[$suid]) && (string) $all_skins_arr[$suid]['uid'] == $suid) {
				if(version_compare( (float) $all_skins_arr[$suid]['version'], (float) $skin_data['Version'], '<=' )) {
					unset($skins_by_type[$stype][$suid]);
				}
			}
		} ?>
	<tr id="<?php echo basename($skin_file, '.php'); ?>" class="<?php echo $class; ?> first">
		<td class="skin-title"><strong><?php echo $skin_data['Name']; ?></strong></td>
		<td class="desc">
		<?php
			$skin_meta = array();
			if ( !empty($skin_data['Version']) )
				$skin_meta[] = sprintf(__('Version %s', 'flag'), $skin_data['Version']);
			if ( !empty($skin_data['Author']) ) {
				$author = $skin_data['Author'];
				if ( !empty($skin_data['AuthorURI']) )
					$author = '<a href="' . $skin_data['AuthorURI'] . '" title="' . __( 'Visit author homepage', 'flag' ) . '">' . $skin_data['Author'] . '</a>';
				$skin_meta[] = sprintf( __('By %s', 'flag'), $author );
			}
			if ( ! empty($skin_data['SkinURI']) )
				$skin_meta[] = '<a href="' . $skin_data['SkinURI'] . '" title="' . __( 'Visit skin site', 'flag' ) . '">' . __('Visit skin site', 'flag' ) . '</a>';
		?>
		<?php echo implode(' | ', $skin_meta); ?>
		</td>
		<td class="skin-activate action-links" style="white-space: nowrap;">
		<?php
		if(isset($_GET['type']) && !empty($_GET['type'])) {
		} else {
			if ( dirname($skin_file) != $flag_options['flashSkin'] ) { ?>
				<strong><a href="<?php echo wp_nonce_url('admin.php?page=flag-skins&skin='.dirname($skin_file), 'set_default_skin'); ?>" title="<?php _e( 'Activate this skin', 'flag' ); ?>"><?php _e('Activate', 'flag' ); ?></a></strong>
			<?php } else { ?>
	 			<strong><?php _e('Activated by default', 'flag' ); ?></strong>
			<?php
	 		}
		} ?>
		</td>

	</tr>
	<tr class="<?php echo $class; ?> second">
		<td class="skin-title"><img src="<?php echo WP_PLUGIN_URL."/flagallery-skins/".dirname($skin_file); ?>/screenshot.png" alt="<?php echo $skin_data['Name'];?>" title="<?php echo $skin_data['Name']; ?>" /></td>
		<td class="desc">
			<p><?php echo $skin_data['Description']; ?></p>
		</td>
		<td class="skin-delete action-links">
		<?php
		$settings = $flag_options['skinsDirABS'].dirname($skin_file).'/settings';
		if(is_dir($settings)) { ?>
			<a class="thickbox" href="<?php echo FLAG_URLPATH.'admin/skin_options.php?show_options=1&amp;skin='.dirname($skin_file).'&amp;TB_iframe=1&amp;width=600&amp;height=560'; ?>"><?php _e('Options', 'flag' ); ?></a>
 		<?php }
		if ( current_user_can('FlAG Delete skins') ) {
		if ( dirname($skin_file) != $flag_options['flashSkin'] ) { ?>
			<br /><br /><a class="delete" onclick="javascript:return flag_delskin('<?php echo $skin_data['Name']; ?>');" href="<?php echo wp_nonce_url('admin.php?page=flag-skins&delete='.dirname($skin_file), 'delete_skin'); ?>" title="<?php _e( 'Delete this skin', 'flag' ); ?>"><?php _e('Delete', 'flag' ); ?></a>
		<?php }
 		} ?>
		</td>
	</tr>
<?php } ?>
	</tbody>
</table>
<script type="text/javascript">
	function flag_delskin(skin_name){
		return confirm( '<?php echo __('Delete' , 'flag'); ?> "'+skin_name+'"');
	}
</script>
</div>

<div class="postbox metabox-holder" id="newskins" style="width: 29%; float: right; padding-top: 5px;">
	<h3 style="font-size: 16px; line-height: 100%; font-weight: bold; color: #2583AD;"><?php echo $new_skins; ?></h3>
	<div class="inside">
	<?php
	if(isset($skins_by_type[$stype]) && !empty($skins_by_type[$stype])) {
		foreach($skins_by_type[$stype] as $skin) { ?>
		<div class="skin <?php echo $skin['type'].' '.$skin['status']; ?>" id="uid-<?php echo $skin['uid']; ?>" style="padding: 10px; float:left;">
			<div style="text-align: center;">
				<p><strong style="font-size: 120%;"><?php echo $skin['title']; ?></strong> <span class="version"><?php echo 'v'.$skin['version']; ?></span></p>
				<div class="screenshot"><img src="<?php echo $skin['screenshot']; ?>" width="200" height="184"  alt=""/></div>
			</div>
			<div class="content">
				<div class="links" style="text-align: center;">
				<form action="<?php echo admin_url('admin.php?page=flag-skins').'&amp;type='.$type; ?>" method="post">
					<?php wp_nonce_field('skin_install'); ?>
					<input type="hidden" name="skinzipurl" value="<?php echo $skin['download']; ?>" />
 					<p><a class="install button-primary" onclick="jQuery(this).closest('form').submit(); return false" href="<?php echo $skin['download']; ?>"><?php _e('Install', 'gmLang') ?></a>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a class="button" href="<?php echo $skin['demo']; ?>" target="_blank"><?php _e('Preview', 'gmLang') ?></a></p>
 				</form>
				</div>
			</div>
		</div>
		<?php
		}
	} else { ?>
		<div class="skin noskins"><?php if(!$skins_xml_error){ echo sprintf(__('All available %s skins are already installed...', 'gmLang'), $stype); } else { echo $skins_xml_error; } ?></div>
	<?php }
	?>
	</div>
</div>



</div>
<?php ?>