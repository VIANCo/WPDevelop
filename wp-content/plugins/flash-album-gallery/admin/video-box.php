<?php

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { 	die('You are not allowed to call this page directly.'); }

// check for correct capability
if ( !is_user_logged_in() )
	die('-1');

// check for correct FlAG capability
if ( !current_user_can('FlAG Manage video') ) 
	die('-1');	


require_once (dirname (__FILE__) . '/functions.php');
require_once (dirname (__FILE__) . '/video.functions.php');

function flag_video_controler() {
	if (isset($_POST['importfolder']) && $_POST['importfolder']){
		check_admin_referer('flag_addvideo');
		$videofolder = $_POST['videofolder'];
		if ( !empty($videofolder) && false === strpos($videofolder, '..') )
			flagAdmin::import_video($videofolder);
	}
	$mode = isset($_REQUEST['mode'])? $_REQUEST['mode'] : 'main';
	$action = isset($_REQUEST['bulkaction'])? $_REQUEST['bulkaction'] : false;
	if($action == 'no_action') {
		$action = false;
	}
	switch($mode) {
		case 'sort':
			check_admin_referer('flag_sort');
			include_once (dirname (__FILE__) . '/video-sort.php');
			flag_v_playlist_order();
		break;
		case 'edit':
			$file = sanitize_flagname($_GET['playlist']);
			if(isset($_POST['updatePlaylist'])) {
				check_admin_referer('flag_update');
				$title = esc_html($_POST['playlist_title']);
				$descr = esc_html($_POST['playlist_descr']);
				$data = array();
				foreach($_POST['item_a'] as $item_id => $item) {
					if($action=='delete_items' && in_array($item_id, $_POST['doaction']))
						continue;
					$data[] = $item_id;
				}
				flagGallery::flagSaveWpMedia();
				flagSave_vPlaylist($title,$descr,$data,$file);
			}
			if(isset($_POST['updatePlaylistSkin'])) {
				check_admin_referer('flag_update');
				flagSave_vPlaylistSkin($file);
			}
			include_once (dirname (__FILE__) . '/manage-video.php');
			flag_v_playlist_edit();
		break;
		case 'save':
			if(isset($_POST['items_array'])){
				check_admin_referer('flag_update');
				$title = esc_html($_POST['playlist_title']);
				$descr = esc_html($_POST['playlist_descr']);
				$data = $_POST['items_array'];
				$file = isset($_REQUEST['playlist'])? sanitize_flagname($_REQUEST['playlist']) : false;
				flagGallery::flagSaveWpMedia();
				flagSave_vPlaylist($title,$descr,$data, $file);
			}
			if(isset($_GET['playlist'])) {
				include_once (dirname (__FILE__) . '/manage-video.php');
				flag_v_playlist_edit();
			} else {
				flag_created_v_playlists();
				flag_video_wp_media_lib();
			}
		break;
  	case 'add':
			check_admin_referer('flag_add');
			$added = $_POST['items'];
			flag_video_wp_media_lib($added);
		break;
		case 'delete':
			check_admin_referer('flag_delete');
			flag_v_playlist_delete(sanitize_flagname($_GET['playlist']));
	  	case 'main':
			if(isset($_POST['updateMedia'])) {
				check_admin_referer('flag_update');
				flagGallery::flagSaveWpMedia();
				flagGallery::show_message( __('Media updated','flag') );
			}
		default:
			flag_created_v_playlists();
			flag_video_wp_media_lib();
		break;
	}

}

function flag_created_v_playlists() {

	// same as $_SERVER['REQUEST_URI'], but should work under IIS 6.0
	$filepath = admin_url() . 'admin.php?page=' . urlencode($_GET['page']);

	$all_playlists = get_v_playlists();
	$total_all_playlists = count($all_playlists);
	$flag_options = get_option ('flag_options');

?>
	<div class="wrap">
		<h2><?php _e('Created playlists', 'flag'); ?></h2>
		<table class="widefat" cellspacing="0">
			<thead>
			<tr>
				<th scope="col" width="25%"><?php _e('Title', 'flag'); ?></th>
				<th scope="col" width="55%"><?php _e('Description', 'flag'); ?></th>
				<th scope="col" ><?php _e('Quantity', 'flag'); ?></th>
				<th scope="col" ><?php _e('Shortcode', 'flag'); ?></th>
				<th scope="col" ><?php _e('Action', 'flag'); ?></th>
			</tr>
			</thead>
			<tbody>
<?php
if($all_playlists) {
	foreach((array)$all_playlists as $playlist_file => $playlist_data) {
		$query_m = get_posts(array('post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => null, 'post__in' => $playlist_data['items']));
		$class = ( !isset($class) || $class == 'class="alternate"' ) ? '' : 'class="alternate"';
		$playlist_name = basename($playlist_file, '.xml');
		if(count($query_m) != count($playlist_data['items'])) {
			flagSave_vPlaylist($playlist_data['title'],$playlist_data['description'],$playlist_data['items'],$playlist_name);
		}
?>
		<tr id="<?php echo $playlist_name; ?>" <?php echo $class; ?> >
			<td>
				<a href="<?php echo esc_url($filepath.'&playlist='.$playlist_name.'&mode=edit'); ?>" class='edit' title="<?php _e('Edit'); ?>" >
					<?php echo esc_html($playlist_data['title']); ?>
				</a>
			</td>
			<td><?php echo esc_html($playlist_data['description']); echo '&nbsp;('.__("player", "flag").': <strong>'.esc_html($playlist_data['skin']).'</strong>)' ?></td>
			<td><?php echo count($query_m); ?></td>
			<td style="white-space: nowrap;"><input type="text" class="shortcode1" style="width: 200px; font-size: 9px;" readonly="readonly" onfocus="this.select()" value="[grandvideo playlist=<?php echo $playlist_name; ?>]" /></td>
			<td>
				<a href="<?php echo wp_nonce_url($filepath.'&playlist='.$playlist_name."&mode=delete", 'flag_delete'); ?>" class="delete" onclick="javascript:check=confirm( '<?php _e("Delete this playlist?",'flag')?>');if(check==false) return false;"><?php _e('Delete','flag'); ?></a>
			</td>
		</tr>
		<?php
	}
} else {
	echo '<tr><td colspan="5" align="center"><strong>'.__('No playlists found','flag').'</strong></td></tr>';
}
?>			
			</tbody>
		</table>
	</div>

<?php } ?>

<?php // *** show media list
function flag_video_wp_media_lib($added=false) {
	global $wpdb;
	// same as $_SERVER['REQUEST_URI'], but should work under IIS 6.0
	$filepath = admin_url() . 'admin.php?page=' . urlencode($_GET['page']);
	if($added!==false) {
		$added = preg_replace('/[^\d,]+/', '', $added);
		$filepath .= '&playlist='.sanitize_flagname($_GET['playlist']).'&mode=save';
		$flag_options = get_option('flag_options');
		$playlistPath = $flag_options['galleryPath'].'playlists/video/'.sanitize_flagname($_GET['playlist']).'.xml';
		$playlist = get_v_playlist_data(ABSPATH.$playlistPath);
		$exclude = explode(',', $added);
		$exclude = array_filter($exclude, 'intval');
	}
	$filepath = esc_url($filepath);
?>
<script type="text/javascript"> 
<!--
jQuery(document).ready(function(){
    jQuery('.cb :checkbox').click(function() {
		var cur, arr, del;
		if(jQuery(this).is(':checked')){
			cur = jQuery(this).val();
			arr = jQuery('#items_array').val();
			if(arr) { del = ','; } else { del = ''; }
			jQuery('#items_array').val(arr+del+cur);
			jQuery(this).closest('tr').css('background-color','#DDFFBB').next().css('background-color','#DDFFBB');
		} else {
			cur = jQuery(this).val();
			arr = jQuery('#items_array').val().split(',');
			arr = jQuery.grep(arr, function(a){ return a != cur; }).join(',');
			jQuery('#items_array').val(arr);
			jQuery(this).closest('tr').removeAttr('style').next().removeAttr('style');
		}
 	});
    jQuery('.del_thumb').click(function(){
      var id = jQuery(this).attr('data-id');
      jQuery('#flvthumb-'+id).attr('value', '');
      jQuery('#thumb-'+id).attr('src', '<?php echo site_url()."/wp-includes/images/crystal/video.png"; ?>');
      return false;
    })
});
function checkAll(form)	{
	for (i = 0, n = form.elements.length; i < n; i++) {
		if(form.elements[i].type == "checkbox") {
			if(form.elements[i].name == "doaction[]") {
				if(form.elements[i].checked == true)
					form.elements[i].checked = false;
				else
					form.elements[i].checked = true;
			}
		}
	}
	var arr = jQuery('.cb input:checked').map(function(){return jQuery(this).val();}).get().join(',');
	jQuery('#items_array').val(arr);
}
// this function check for a the number of selected images, sumbmit false when no one selected
function checkSelected() {
	if(!jQuery('.cb input:checked')) { 
		alert('<?php echo esc_js(__('No items selected', 'flag')); ?>');
		return false; 
	} 
	actionId = jQuery('#bulkaction').val();
	switch (actionId) {
		case "new_playlist":
			showDialog('new_playlist', 160);
			return false;
			break;
	}
}

function showDialog( windowId, height ) {
	jQuery("#" + windowId + "_bulkaction").val(jQuery("#bulkaction").val());
	jQuery("#" + windowId + "_flvid").val(jQuery('#items_array').val());
	tb_show("", "#TB_inline?width=640&height=" + height + "&inlineId=" + windowId + "&modal=true", false);
}
var current_image = '';
function send_to_editor(html) {
	var source = html.match(/src=\".*\" alt/);
	source = source[0].replace(/^src=\"/, "").replace(/" alt$/, "");
	jQuery('#flvthumb-'+actInp).attr('value', source);
	jQuery('#thumb-'+actInp).attr('src', source);
	tb_remove();
}
//-->
</script>
	<div class="wrap">
<?php if($added===false) { ?>
<?php if( current_user_can('FlAG Import folder') ) {
	$defaultpath = basename(WP_CONTENT_DIR).'/';
?>
<link rel="stylesheet" type="text/css" href="<?php echo FLAG_URLPATH; ?>admin/js/jqueryFileTree/jqueryFileTree.css" />
<script type="text/javascript" src="<?php echo FLAG_URLPATH; ?>admin/js/jqueryFileTree/jqueryFileTree.js"></script>
<script type="text/javascript">
/* <![CDATA[ */
	  jQuery(function() {
	    jQuery("span.browsefiles").show().click(function(){
		    jQuery("#file_browser").fileTree({
		      script: "admin-ajax.php?action=flag_file_browser&nonce=<?php echo wp_create_nonce( 'flag-ajax' ) ;?>",
		      root: jQuery("#videofolder").val()
		    }, function(file) {
		        //var path = file.replace("<?php echo WINABSPATH; ?>", "");
		        jQuery("#videofolder").val(file);
		    });

	    	jQuery("#file_browser").show("slide");
	    });
	  });
/* ]]> */
</script>

		<!-- import folder -->
		<div id="importfolder">
		<h2><?php _e('Import video from folder', 'flag'); ?></h2>
			<form name="importfolder" id="importfolder_form" method="POST" action="<?php echo $filepath; ?>" accept-charset="utf-8" >
			<?php wp_nonce_field('flag_addvideo'); ?>
				<table class="form-table"> 
				<tr valign="top"> 
					<th scope="row"><?php _e('Import from Server path:', 'flag'); ?></th> 
					<td><input type="text" size="35" id="videofolder" name="videofolder" value="<?php echo $defaultpath; ?>" /><span class="browsefiles button" style="display:none"><?php _e('Browse...',"flag"); ?></span>
						<div id="file_browser"></div><br />
						<p><label><input type="checkbox" name="delete_files" value="delete" /> &nbsp;
						<?php _e('delete files after import in WordPress Media Library','flag'); ?></label></p>
					</td> 
				</tr>
				</table>
				<div class="submit"><input class="button-primary" type="submit" name="importfolder" value="<?php _e('Import folder', 'flag'); ?>"/></div>
			</form>
		</div>
<?php } ?>
<?php } ?>

		<h2><?php _e('WordPress Video Library', 'flag'); ?></h2>
		<form id="videolib" class="flagform" method="POST" action="<?php echo $filepath; ?>" accept-charset="utf-8">
		<?php wp_nonce_field('flag_update'); ?>
		<input type="hidden" name="page" value="video-box" />
		
		<div class="tablenav">
			
			<div class="actions">
<?php if($added===false) { ?>
				<input name="updateMedia" class="button-primary" style="float: right;" type="submit" value="<?php _e('Update Media','flag'); ?>" />
				<?php if ( function_exists('json_encode') ) { ?>
				<select name="bulkaction" id="bulkaction">
					<option value="no_action" ><?php _e("No action",'flag'); ?></option>
					<option value="new_playlist" ><?php _e("Create new playlist",'flag'); ?></option>
				</select>
				<input name="showThickbox" class="button-secondary" type="submit" value="<?php _e('Apply','flag'); ?>" onclick="if ( !checkSelected() ) return false;" />
				<?php } ?>
                <a href="<?php echo admin_url( 'media-new.php'); ?>" class="button"><?php _e('Upload Video','flag'); ?></a>
				<input type="hidden" id="items_array" name="items_array" value="" />
<?php } else { ?>
				<input type="hidden" name="mode" value="save" />
				<input style="width: 80%;" type="text" id="items_array" name="items_array" value="<?php echo $added; ?>" />
				<input type="hidden" name="playlist_title" value="<?php echo esc_html($playlist['title']); ?>" />
				<input type="hidden" name="skinname" value="<?php echo sanitize_flagname($playlist['skin']); ?>" />
				<input type="hidden" name="skinaction" value="<?php echo sanitize_flagname($playlist['skin']); ?>" />
				<textarea style="display: none;" name="playlist_descr" cols="40" rows="1"><?php echo esc_html($playlist['description']); ?></textarea>
				<input name="addToPlaylist" class="button-secondary" type="submit" value="<?php _e('Update Playlist','flag'); ?>" />
<?php } ?>
			</div>
			
		</div>
		<table class="widefat" cellspacing="0">
			<thead>
			<tr>
        		<th class="cb" width="54" scope="col"><a href="#" onclick="checkAll(document.getElementById('videolib'));return false;"><?php _e('Check', 'flag'); ?></a></th>
        		<th class="id" width="134" scope="col"><div><?php _e('ID', 'flag'); ?></div></th>
        		<th class="size" width="75" scope="col"><div><?php _e('Size', 'flag'); ?></div></th>
        		<th class="thumb" width="110" scope="col"><div><?php _e('Thumbnail', 'flag'); ?></div></th>
        		<th class="title_filename" scope="col"><div><?php _e('Filename / Title', 'flag'); ?></div></th>
        		<th class="description" scope="col"><div><?php _e('Description', 'flag'); ?></div></th>
			</tr>
			</thead>
			<tfoot>
			<tr>
        		<th class="cb" scope="col"><a href="#" onclick="checkAll(document.getElementById('videolib'));return false;"><?php _e('Check', 'flag'); ?></a></th>
        		<th class="id" scope="col"><?php _e('ID', 'flag'); ?></th>
        		<th class="size" scope="col"><?php _e('Size', 'flag'); ?></th>
        		<th class="thumb" scope="col"><?php _e('Thumbnail', 'flag'); ?></th>
        		<th class="title_filename" scope="col"><?php _e('Filename / Title', 'flag'); ?></th>
        		<th class="description" scope="col"><?php _e('Description', 'flag'); ?></th>
			</tr>
			</tfoot>
			<tbody>
<?php $videolist = get_posts( $args = array(
    'numberposts'     => -1,
    'orderby'         => 'ID',
    'order'           => 'DESC',
    'post_type'       => 'attachment',
    'post_mime_type'  => array('video/x-flv') ) 
); 
$uploads = wp_upload_dir();
$flag_options = get_option('flag_options');	
if($videolist) {
    //echo '<pre>';print_r($videolist); echo '</pre>';
	foreach($videolist as $flv) {
		$list[] = $flv->ID;
	}
    $class = ' class="alternate"';
	foreach($videolist as $flv) {
		$class = ( empty($class) ) ? ' class="alternate"' : '';
		$class2 = ( empty($class) ) ? '' : ' alternate';
		$ex = $checked = '';
		if($added!==false && in_array($flv->ID, $exclude) ) { 
			$ex = ' style="background-color:#DDFFBB;" title="'.__("Already Added", "flag").'"';
			$checked = ' checked="checked"';
		}
		$bg = ( !isset($class) || $class == 'class="alternate"' ) ? 'f9f9f9' : 'ffffff';
        $thumb = $flvthumb = get_post_meta($flv->ID, 'thumbnail', true);
        if(empty($thumb)) {
          $thumb = site_url().'/wp-includes/images/crystal/video.png';
          $flvthumb = '';
        }
		$url = wp_get_attachment_url($flv->ID);
?>
		<tr id="flv-<?php echo $flv->ID; ?>"<?php echo $class.$ex; ?>>
			<th class="cb" scope="row" height="24" style="padding-bottom: 0; border-bottom: none;"><input name="doaction[]" type="checkbox"<?php echo $checked; ?> value="<?php echo $flv->ID; ?>" /></th>
			<td class="id" style="padding-bottom: 0; border-bottom: none;"><p style="margin-bottom: 3px; white-space: nowrap;">ID: <?php echo $flv->ID; ?></p></td>
			<td class="size" style="padding-bottom: 0; border-bottom: none;"><?php 
				$path = $uploads['basedir'].str_replace($uploads['baseurl'],'',$url);
				$size = filesize($path);
				if($size){
					echo round($size/1024/1024,2).' Mb';
				} else {
					_e("Can't read file size. Perhaps file is broken.",'flag');
				}
			?></td>
			<td class="thumb" rowspan="2">
				<a class="thickbox" title="<?php echo basename($url); ?>" href="<?php echo FLAG_URLPATH; ?>admin/flv_preview.php?vid=<?php echo $flv->ID; ?>&amp;TB_iframe=1&amp;width=490&amp;height=293"><img id="thumb-<?php echo $flv->ID; ?>" src="<?php echo esc_url($thumb); ?>" width="100" height="100" alt="" /></a>
			</td>
			<td class="title_filename" rowspan="2">
				<strong><a href="<?php echo $url; ?>"><?php echo basename($url); ?></a></strong><br />
				<textarea title="Title" name="item_a[<?php echo $flv->ID; ?>][post_title]" cols="20" rows="1" style="width:95%; height: 25px; overflow:hidden;"><?php echo esc_html(stripslashes($flv->post_title)); ?></textarea><br />
				<p><?php _e('Thumb URL:', 'flag'); ?> <input id="flvthumb-<?php echo $flv->ID; ?>" name="item_a[<?php echo $flv->ID; ?>][post_thumb]" type="text" value="<?php echo esc_url($flvthumb); ?>" /> <a class="thickbox" onclick="actInp=<?php echo $flv->ID; ?>" href="media-upload.php?type=image&amp;TB_iframe=1&amp;width=640&amp;height=400" title="<?php _e('Add an Image','flag'); ?>"><?php _e('assist', 'flag'); ?></a></p>
			</td>
			<td class="description" rowspan="2">
				<textarea name="item_a[<?php echo $flv->ID; ?>][post_content]" style="width:95%; height: 96px; margin-top: 2px; font-size:12px; line-height:115%;" rows="1" ><?php echo esc_html(stripslashes($flv->post_content)); ?></textarea>
			</td>
		</tr>
        <tr class="flv-<?php echo $flv->ID.$class2; ?>"<?php echo $ex; ?>>
            <td valign="top" class="player" colspan="3"><p style="padding: 7px 3px;">Shortcode:<br /><input type="text" style="width: 240px; font-size: 9px;" class="shortcode1" readonly="readonly" onfocus="this.select()" value="[grandflv id=<?php echo $flv->ID; ?> w=<?php echo $flag_options['vmWidth']; ?> h=<?php echo $flag_options['vmHeight']; ?> autoplay=<?php echo $flag_options['vmAutoplay']; ?>]" /></p></td>
        </tr>
		<?php
	}
} else {
	echo '<tr><td colspan="6" align="center"><strong>'.__('No video in WordPress Media Library.','flag').'</strong></td></tr>';
}
?>			
			</tbody>
		</table>
		</form>
	</div>

	<!-- #new_playlist -->
	<div id="new_playlist" style="display: none;" >
		<form id="form_new_playlist" method="POST" action="<?php echo $filepath; ?>" accept-charset="utf-8">
		<?php wp_nonce_field('flag_update'); ?>
		<input type="hidden" id="new_playlist_flvid" name="items_array" value="" />
		<input type="hidden" id="new_playlist_bulkaction" name="TB_bulkaction" value="" />
		<input type="hidden" name="mode" value="save" />
		<input type="hidden" name="page" value="video-box" />
		<table width="100%" border="0" cellspacing="3" cellpadding="3" >
			<tr valign="top">
				<th align="left" style="padding-top: 5px;"><?php _e('Playlist Title','flag'); ?></th>
				<td><input type="text" class="alignleft" name="playlist_title" value="" />
                    <div class="alignright"><strong><?php _e("Choose skin", 'flag'); ?>:</strong>
                        <select id="skinname" name="skinname" style="width: 200px; height: 24px; font-size: 11px;">
                          <?php require_once (dirname(__FILE__) . '/get_skin.php');
                            $all_skins = get_skins($skin_folder='', $type='v');
                            if(count($all_skins)) {
                            	foreach ( (array)$all_skins as $skin_file => $skin_data) {
                            		echo '<option value="'.dirname($skin_file).'">'.$skin_data['Name'].'</option>'."\n";
                            	}
                            } else {
                                echo '<option value="video_default">'.__("No Skins", "flag").'</option>';
                            }
                          ?>
                        </select>
                    </div>
                </td>
			</tr>
			<tr valign="top">
				<th align="left" style="padding-top: 5px;"><?php _e('Playlist Description','flag'); ?></th>
				<td><textarea style="width:100%;" rows="3" cols="60" name="playlist_descr"></textarea></td>
			</tr>
		  	<tr>
				<td>&nbsp;</td>
		    	<td align="right"><input class="button-secondary" type="reset" value="&nbsp;<?php _e('Cancel', 'flag'); ?>&nbsp;" onclick="tb_remove()"/>
		    		&nbsp; &nbsp; &nbsp;
                    <input class="button-primary " type="submit" name="TB_NewPlaylist" value="<?php _e('OK', 'flag'); ?>" />
		    	</td>
			</tr>
		</table>
		</form>
	</div>
	<!-- /#new_playlist -->	
<?php } ?>