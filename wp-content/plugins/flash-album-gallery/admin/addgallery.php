<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }
	
	// sometimes a error feedback is better than a white screen
	@ini_set('error_reporting', E_ALL ^ E_NOTICE);

	function flag_admin_add_gallery()  {

	global $wpdb, $flagdb, $flag;
	
	// same as $_SERVER['REQUEST_URI'], but should work under IIS 6.0
	$filepath    = admin_url() . 'admin.php?page=' . urlencode($_GET['page']);
	
	// check for the max image size
	$maxsize    = flagGallery::check_memory_limit();
	
	$defaultpath = $flag->options['galleryPath'];

	if ($_POST['addgallery']){
		check_admin_referer('flag_addgallery');
		$newgallery = trim($_POST['galleryname']);
		if ( !empty($newgallery) )
			flagAdmin::create_gallery($newgallery, $defaultpath);
	}
	if ($_POST['uploadimage']){
		check_admin_referer('flag_upload');

		$flag->options['thumbWidth']  = intval($_POST['thumbWidth'])? intval($_POST['thumbWidth']) : 100;
		$flag->options['thumbHeight'] = intval($_POST['thumbHeight'])? intval($_POST['thumbHeight']) : 100;
		$flag->options['thumbFix']    = isset($_POST['thumbFix'])? 1 : 0;
		update_option('flag_options', $flag->options);

		if ($_FILES['MF__F_0_0']['error'] == 0) {
			flagAdmin::upload_images();
		}
		else
			flagGallery::show_error( __('Upload failed!','flag') );
	}
	if ($_POST['importfolder']){
		check_admin_referer('flag_addgallery');
		$galleryfolder = $_POST['galleryfolder'];
		if ( ( !empty($galleryfolder) ) AND ($defaultpath != $galleryfolder) AND false === strpos($galleryfolder, '..') )
			flagAdmin::import_gallery($galleryfolder);
	}


	if ( isset($_POST['disable_flash']) ){
		check_admin_referer('flag_upload');
		$flag->options['swfUpload'] = false;	
		update_option('flag_options', $flag->options);
	}

	if ( isset($_POST['enable_flash']) ){
		check_admin_referer('flag_upload');
		$flag->options['swfUpload'] = true;	
		update_option('flag_options', $flag->options);
	}

	//get all galleries (after we added new ones)
	$gallerylist = $flagdb->find_all_galleries($flag->options['albSort'], $flag->options['albSortDir'], false, 0, 0, 0, true);

?>
	
<?php if( !IS_WPMU || current_user_can('FlAG Import folder') ) { ?>
	<link rel="stylesheet" type="text/css" href="<?php echo FLAG_URLPATH; ?>admin/js/jqueryFileTree/jqueryFileTree.css" />
	<script type="text/javascript" src="<?php echo FLAG_URLPATH; ?>admin/js/jqueryFileTree/jqueryFileTree.js"></script>
	<script type="text/javascript">
	/* <![CDATA[ */
		  jQuery(function() {
		    jQuery("span.browsefiles").show().click(function(){
			    jQuery("#file_browser").fileTree({
			      script: "admin-ajax.php?action=flag_file_browser&nonce=<?php echo wp_create_nonce( 'flag-ajax' ) ;?>",
			      root: jQuery("#galleryfolder").val()
			    }, function(file) {
			        //var path = file.replace("<?php echo WINABSPATH; ?>", "");
			        jQuery("#galleryfolder").val(file);
			    });

		    	jQuery("#file_browser").show("slide");
		    });
		  });
	/* ]]> */
	</script>
<?php } ?>
	<div id="slider" class="wrap">
	
		<ul id="tabs" class="tabs">
			<li class="selected"><a href="#" rel="addgallery"><?php _e('Add new gallery', 'flag'); ?></a></li>
			<li><a href="#" rel="uploadimage"><?php _e('Upload Images', 'flag'); ?></a></li>
<?php if( !IS_WPMU || current_user_can('FlAG Import folder') ) { ?>
			<li><a href="#" rel="importfolder"><?php _e('Import image folder', 'flag'); ?></a></li>
<?php } ?>
		</ul>

		<!-- create gallery -->
		<div id="addgallery" class="cptab">
			<h2><?php _e('Create a new gallery', 'flag'); ?></h2>
			<form name="addgallery" id="addgallery_form" method="POST" action="<?php echo $filepath; ?>" accept-charset="utf-8" >
			<?php wp_nonce_field('flag_addgallery'); ?>
				<table class="form-table" style="width: auto;"> 
				<tr>
					<th scope="col" colspan="2" style="padding-bottom: 0;"><strong><?php _e('New Gallery', 'flag'); ?></strong></th> 
				</tr>
				<tr valign="top"> 
					<td><input type="text" size="65" name="galleryname" value="" /><br />
					<?php if(!IS_WPMU) { ?>
						<?php _e('Create a new , empty gallery below the folder', 'flag'); ?>  <strong><?php echo $defaultpath; ?></strong><br />
					<?php } ?>
						<i>( <?php _e('Allowed characters for file and folder names are', 'flag'); ?>: a-z, A-Z, 0-9, -, _ )</i></td>
					<?php do_action('flag_add_new_gallery_form'); ?>
					<td><div class="submit" style="margin: 0; padding: 0;"><input class="button-primary" type="submit" name= "addgallery" value="<?php _e('Add gallery', 'flag'); ?>"/></div></td>
				</tr>
				</table>
				<p>&nbsp;</p>
			</form>
		</div>
		<!-- upload images -->
		<div id="uploadimage" class="cptab">
			<h2><?php _e('Upload images', 'flag'); ?></h2>
			<form name="uploadimage" id="gmUpload" method="POST" enctype="multipart/form-data" action="<?php echo $filepath; ?>" accept-charset="utf-8" >
				<?php wp_nonce_field('flag_upload'); ?>
				<table class="form-table">
				<tr valign="top">
					<td style="width: 216px;"><label for="galleryselect"><?php _e('Upload images in', 'flag'); ?> *</label>
						<select name="galleryselect" id="galleryselect" style="width: 200px">
							<option value="0" ><?php _e('Choose gallery', 'flag'); ?></option>
							<?php $ingallery = isset($_GET['gid']) ? (int) $_GET['gid'] : '';
							foreach($gallerylist as $gallery) {
									if ( !flagAdmin::can_manage_this_gallery($gallery->author) )
										continue;
									$name = ( empty($gallery->title) ) ? $gallery->name : esc_html(stripslashes($gallery->title));
									if($flag->options['albSort'] == 'gid'){ $name = $gallery->gid.' - '.$name; }
									if($flag->options['albSort'] == 'title'){ $name = $name.' ('.$gallery->gid.')'; }
									$sel = ($ingallery == $gallery->gid) ? 'selected="selected" ' : '';
									echo '<option ' . $sel . 'value="' . $gallery->gid . '" >' . $name . '</option>' . "\n";
							} ?>
						</select>
						<?php echo $maxsize; ?>
						<br /><?php if ((IS_WPMU) && flagGallery::flag_wpmu_enable_function('wpmuQuotaCheck')) display_space_usage(); ?>
						<br />
						<p><?php _e('Thumbnail WIDTH x HEIGHT (in pixel)','flag'); ?> *
						<br /><input type="text" size="5" maxlength="5" name="thumbWidth" id="thumbWidth" value="<?php echo $flag->options['thumbWidth']; ?>" /> x <input type="text" size="5" maxlength="5" name="thumbHeight" id="thumbHeight" value="<?php echo $flag->options['thumbHeight']; ?>" />
							<br /><small><?php _e('These values are maximum values ','flag'); ?></small></p>
						<p><label><input type="checkbox" name="thumbFix" id="thumbFix" value="1" <?php checked('1', $flag->options['thumbFix']); ?> /> <?php _e('Ignore the aspect ratio, no portrait thumbnails','flag'); ?></label></p>

						<div class="submit">
					<span class="useflashupload">
					<?php if ($flag->options['swfUpload']) { ?>
						<input type="submit" name="disable_flash" id="disable_flash" title="<?php _e('The batch upload via Plupload, disable it if you have problems','flag'); ?>" value="<?php _e('Switch to Browser Upload', 'flag'); ?>" />
					<?php } else { ?>
						<input type="submit" name="enable_flash" id="enable_flash" title="<?php _e('Upload multiple files at once by ctrl/shift-selecting in dialog','flag'); ?>" value="<?php _e('Switch to Plupload based Upload', 'flag'); ?>" />
					<?php } ?>
					</span>
							<div class="clear"></div>
						</div>

					</td>

					<td><div id="pluploadUploader">
					<?php if (!$flag->options['swfUpload']) { ?>
						<strong><?php _e('Upload image(s):', 'flag'); ?></strong><br>
						<input type="file" name="imagefiles[]" id="imagefiles" size="35" class="imagefiles"/></div>
						<span id="choosegalfirst">
							<input class="button-primary" type="submit" name="uploadimage" id="uploadimage_btn" value="<?php _e('Upload images', 'flag'); ?>" />
							<span class="disabledbut" style="display: none;"></span>
						</span>
					<?php } ?>
					</td>
				</tr>
				</table>
				<div id="pl-message"></div>
			</form>
<?php if($flag->options['swfUpload']) { ?>
	<script type="text/javascript">
		// Convert divs to queue widgets when the DOM is ready
		jQuery(function () {
			var files_remaining = 0;
			jQuery("#pluploadUploader").pluploadQueue({
				// General settings
				runtimes        		: 'flash,html5,html4',
				url             		: '<?php echo str_replace( '&#038;', '&', wp_nonce_url( plugins_url( FLAGFOLDER. '/admin/upload.php' ), 'flag_upload' ) ); ?>',
				multipart       		: true,
				multipart_params		: { postData: ''},
				max_file_size					: '<?php echo min((floor( wp_max_upload_size() * 0.99 / 1024 / 1024 ) - 1), 8); ?>Mb',
				unique_names    		: false,
				rename          		: true,
				urlstream_upload	: true,

				// Resize images on clientside if we can
				//resize 						: {width : 150, height : 150, quality : 90},

				// Specify what files to browse for
				filters         		: [{title: "Images", extensions: "jpg,gif,png"}],

				// Flash settings
				flash_swf_url   		: '<?php echo plugins_url( FLAGFOLDER. '/admin/js/plupload/plupload.flash.swf'); ?>',

				// PreInit events, bound before any internal events
				preinit : {
					Init: function(up, info) {
						console.log('[Init]', 'Info:', info, 'Features:', up.features);
					},

					UploadFile: function(up, file) {
						console.log('[UploadFile]', file);
						up.settings.multipart_params = { galleryselect: jQuery('#galleryselect').val(), thumbw:  jQuery('#thumbWidth').val(), thumbh:  jQuery('#thumbHeight').val(), thumbf: jQuery('#thumbFix').prop("checked"), last: files_remaining };
						files_remaining--;
						// You can override settings before the file is uploaded
						// up.settings.url = 'upload.php?id=' + file.id;
						// up.settings.multipart_params = {param1 : 'value1', param2 : 'value2'};
					}
				},

				// Post init events, bound after the internal events
				init : {
					Refresh: function(up) {
						// Called when upload shim is moved
						console.log('[Refresh]');
						files_remaining = up.files.length;
						if(jQuery("#galleryselect").val() == 0) {
							jQuery(".plupload_start").addClass("plupload_disabled");
						}
					},

					StateChanged: function(up) {
						// Called when the state of the queue is changed
						console.log('[StateChanged]', up.state == plupload.STARTED ? "STARTED" : "STOPPED");
					},

					QueueChanged: function(up) {
						// Called when the files in queue are changed by adding/removing files
						console.log('[QueueChanged]');
					},

					UploadProgress: function(up, file) {
						// Called while a file is being uploaded
						console.log('[UploadProgress]', 'File:', file, "Total:", up.total);
					},

					FileUploaded: function(up, file, info) {
						// Called when a file has finished uploading
						console.log('[FileUploaded] File:', file, "Info:", info);
						if (info.response){
							file.status = plupload.FAILED;
							jQuery('<div/>').addClass('error').html('<span><u><em>'+file.name+':</em></u> '+info.response+'</span>').appendTo('#pl-message');
						}
					},

					Error: function(up, args) {
						// Called when a error has occured
						jQuery('<div/>').addClass('error').html('<span><u><em>'+args.file.name+':</em></u> '+args.message+' '+args.status+'</span>').appendTo('#pl-message');
						console.log('[error] ', args);
					},

					UploadComplete: function(up, file) {
						console.log('[UploadComplete]');
						jQuery(".plupload_buttons").css("display", "inline");
						jQuery(".plupload_upload_status").css("display", "inline");
						jQuery(".plupload_start").addClass("plupload_disabled");
						jQuery("#gmUpload").one("mousedown", ".plupload_add", function () {
							up.splice();
							up.trigger('Refresh');
							//up.refresh();
						});
						jQuery('<div/>').addClass('success').html('<?php _e('Done!', 'flag'); ?> <a href="<?php echo wp_nonce_url( $flag->manage_page->base_page . "&mode=edit", 'flag_editgallery'); ?>&gid=' + jQuery("#galleryselect").val() + '">Open Gallery</a>').appendTo('#pl-message');
					}
				}
			});
			jQuery("#gmUpload").on('click','.plupload_disabled',function(){
				if(files_remaining){
					alert("Choose gallery, please.")
				}
			});
			jQuery("#galleryselect").change(function () {
				if(jQuery(this).val() == 0) {
					jQuery(".plupload_start").addClass('plupload_disabled');
				} else {
					if(files_remaining){
						jQuery(".plupload_start").removeClass('plupload_disabled');
					}
				}
			});

		});
	</script>
<?php } else { ?>
	<!-- MultiFile script -->
	<script type="text/javascript">
		/* <![CDATA[ */
		jQuery(document).ready(function(){
			jQuery('#imagefiles').MultiFile({
				STRING: {
					remove:'<?php _e('remove', 'flag'); ?>'
				}
			});

			if(jQuery("#galleryselect").val() == 0) {
				jQuery("#choosegalfirst").animate({opacity: "0.5"}, 600);
				jQuery("#choosegalfirst .disabledbut").show();
			}
			jQuery("#choosegalfirst .disabledbut").click(function () {
				alert("Choose gallery, please.")
			});
			jQuery("#galleryselect").change(function () {
				if(jQuery(this).val() == 0) {
					jQuery("#choosegalfirst .disabledbut").show();
					jQuery("#choosegalfirst").animate({opacity: "0.5"}, 600);
				} else {
					jQuery("#choosegalfirst .disabledbut").hide();
					jQuery("#choosegalfirst").animate({opacity: "1"}, 600);
				}
			});
		});
		/* ]]> */
	</script>

<?php } ?>
		</div>
<?php if( !IS_WPMU || current_user_can('FlAG Import folder') ) { ?>
		<!-- import folder -->
		<div id="importfolder" class="cptab">
		<h2><?php _e('Import image folder', 'flag'); ?></h2>
			<form name="importfolder" id="importfolder_form" method="POST" action="<?php echo $filepath; ?>" accept-charset="utf-8" >
			<?php wp_nonce_field('flag_addgallery'); ?>
				<table class="form-table">
				<tr valign="top"> 
					<th scope="row"><?php _e('Import from Server path:', 'flag'); ?></th> 
					<td><input type="text" size="35" id="galleryfolder" name="galleryfolder" value="<?php echo $defaultpath; ?>" /><span class="browsefiles button" style="display:none"><?php _e('Browse...',"flag"); ?></span>
					<div id="file_browser"></div>
					<div><?php echo $maxsize; ?>
					<?php if (SAFE_MODE) {?><br /><?php _e(' Please note : For safe-mode = ON you need to add the subfolder thumbs manually', 'flag'); ?><?php }; ?></div></td> 
				</tr>
				</table>
				<div class="submit"><input class="button-primary" type="submit" name="importfolder" value="<?php _e('Import folder', 'flag'); ?>"/></div>
			</form>
		</div>
<?php } ?>

<script type="text/javascript">
	var cptabs=new ddtabcontent("tabs");
	cptabs.setpersist(true);
	cptabs.setselectedClassTarget("linkparent");
	cptabs.init();
</script>
</div>
<?php
	}
?>