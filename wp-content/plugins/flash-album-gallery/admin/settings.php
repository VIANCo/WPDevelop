<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

function flag_admin_options()  {

	global $flag;
	
	// same as $_SERVER['REQUEST_URI'], but should work under IIS 6.0
	$filepath    = admin_url() . 'admin.php?page='.urlencode($_GET['page']);

	if ( isset($_POST['updateoption']) ) {	
		check_admin_referer('flag_settings');
		// get the hidden option fields, taken from WP core
		$options=array();
		if ( $_POST['page_options'] )
			$options = explode(',', stripslashes($_POST['page_options']));
		if (!empty($options)) {
			foreach ($options as $option) {
				$option = trim($option);
				if(isset($_POST[$option]))
					$value = trim($_POST[$option]);
				else
					$value = false;
				$flag->options[$option] = $value;
			}
			if(isset($_POST['galleryPath'])) {
			// the path should always end with a slash
				$flag->options['galleryPath']    = trailingslashit($flag->options['galleryPath']);
			}
			// the custom sortorder must be ascending
			//$flag->options['galSortDir'] = ($flag->options['galSort'] == 'sortorder') ? 'ASC' : $flag->options['galSortDir'];
		}
		// Save options
		update_option('flag_options', $flag->options);

		flagGallery::show_message(__('Update Successfully','flag'));
	}
	$regform = 0;
	if( isset($_POST['membership']) ){
		if(function_exists('curl_init')){
			check_admin_referer('flag_settings');
			$ch = curl_init('http://mypgc.co/app/account_st.php');
			curl_setopt ($ch, CURLOPT_REFERER, site_url());
			curl_setopt ($ch, CURLOPT_POST, 1);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, array('access_key'=>$_POST['access_key'], 'access_url'=>$_POST['access_url'], 'license_key'=>$_POST['license_key']));
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			$access_key_return = curl_exec ($ch);
			curl_close ($ch);
		} else {
			$access_key_return = __('cURL library is not installed on your server.','flag');
		}
		if(strpos($access_key_return, 'Error') !== FALSE){
			$_POST['license_key'] = '';
		}
		$options = explode(',', stripslashes($_POST['page_options']));
		foreach ($options as $option) {
				$option = trim($option);
				$value = trim($_POST[$option]);
				$flag->options[$option] = $value;
		}

		if(strpos($access_key_return, 'Error') === FALSE || strpos($access_key_return, 'not a member') !== FALSE){
			flagGallery::show_message($access_key_return);
			if(strpos($access_key_return, 'not a member') !== FALSE){
				$regform = 1;
				//$flag->options['access_key'] = '';
			}
		} else {
			flagGallery::show_error($access_key_return);
			//$flag->options['access_key'] = '';
		}

		// Save options
		update_option('flag_options', $flag->options);
	}
	
	if( isset($_POST['register_subscriber']) ){
		if(empty($_POST['customer_first_name']) || empty($_POST['customer_last_name']) || empty($_POST['customer_email'])){
			$regform = 1;
			flagGallery::show_error(__('Error: All fields required.'));
		} else {
			if(function_exists('curl_init')){
				check_admin_referer('flag_settings');
				$ch = curl_init('http://mypgc.co/app/account_st.php');
				curl_setopt ($ch, CURLOPT_REFERER, site_url());
				curl_setopt ($ch, CURLOPT_POST, 1);
				curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt ($ch, CURLOPT_POSTFIELDS, array('access_key'=>$_POST['access_key'], 'access_url'=>$_POST['access_url'], 'customer_first_name'=>$_POST['customer_first_name'], 'customer_last_name'=>$_POST['customer_last_name'], 'customer_email'=>$_POST['customer_email']));
				$reg_return = curl_exec ($ch);
				curl_close ($ch);
			} else {
				$reg_return = __('cURL library is not installed on your server.','flag');
			}

			if(strpos($reg_return, 'Error') === FALSE){
				flagGallery::show_message($reg_return);
			} else {
				flagGallery::show_error($reg_return);
				$regform = 1;
			}
		}
	}


	if ( isset($_POST['update_cap']) ) {	

		check_admin_referer('flag_addroles');

		// now set or remove the capability
		flag_set_capability($_POST['general'],"FlAG overview");
		flag_set_capability($_POST['tinymce'],"FlAG Use TinyMCE");
		flag_set_capability($_POST['add_gallery'],"FlAG Upload images");
		flag_set_capability($_POST['import_gallery'],"FlAG Import folder");
		flag_set_capability($_POST['manage_gallery'],"FlAG Manage gallery");
		flag_set_capability($_POST['manage_others'],"FlAG Manage others gallery");
		flag_set_capability($_POST['change_skin'],"FlAG Change skin");
		flag_set_capability($_POST['add_skins'],"FlAG Add skins");
		flag_set_capability($_POST['delete_skins'],"FlAG Delete skins");
		flag_set_capability($_POST['change_options'],"FlAG Change options");
		flag_set_capability($_POST['manage_music'],"FlAG Manage music");
		flag_set_capability($_POST['manage_video'],"FlAG Manage video");
		flag_set_capability($_POST['manage_banners'],"FlAG Manage banners");
		flag_set_capability($_POST['facebook_page'],"FlAG Facebook page");
		
		flagGallery::show_message(__('Updated capabilities',"flag"));
	}
	
	// message windows
	if(!empty($messagetext)) { echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$messagetext.'</p></div>'; }
	
	$flag_options = get_option('flag_options');
	?>
	
<div id="slider" class="wrap">

	<ul id="tabs" class="tabs">
		<li class="selected"><a href="#" rel="imageoptions"><?php _e('Gallery Options', 'flag'); ?></a></li>
		<?php if(current_user_can('administrator')){ ?>
		<li><a href="#" rel="rControl"><?php _e('License Key & Remote Control', 'flag'); ?></a></li>
		<?php } ?>
		<li><a href="#" rel="vPlayer"><?php _e('FLV Single Player Options', 'flag'); ?></a></li>
		<li><a href="#" rel="mPlayer"><?php _e('MP3 Single Player Options', 'flag'); ?></a></li>
<?php if (flagGallery::flag_wpmu_enable_function('wpmuRoles')) : ?>
		<li><a href="#" rel="roles"><?php _e('Roles', 'flag'); ?></a></li>
<?php endif; ?>
	</ul>

	<!-- Image Gallery Options -->
<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery('.flag_colors .colorPick').each( function(){
		var inpID = jQuery(this).attr('name');
		jQuery('#cp_'+inpID).farbtastic('#'+inpID);
		jQuery('#'+inpID).focus( function(){
		    jQuery('#cp_'+inpID).show();
		});
		jQuery('#'+inpID).blur( function(){
		    jQuery('#cp_'+inpID).hide();
		});
	});
});
</script>
	<div id="imageoptions" class="cptab">
		<form name="generaloptions" method="post">
		<?php wp_nonce_field('flag_settings'); ?>
			<input type="hidden" name="page_options" value="galleryPath,flashWidth,flashHeight,deleteImg,deepLinks,useMediaRSS,optimized_imgs,jAlterGal,jAlterGalScript,BarsBG,CatBGColor,CatBGColorOver,CatColor,CatColorOver,ThumbBG,ThumbLoaderColor,TitleColor,DescrColor,imgQuality,albSort,albSortDir,albPerPage,galSort,galSortDir,disableViews" />
			<h2><?php _e('Image Gallery Options','flag'); ?></h2>
			<h3><?php _e('General Options','flag'); ?></h3>
			<table class="form-table flag-options" style="width: auto; white-space: nowrap;">
				<tr valign="top">
					<th align="left" width="200"><?php _e('Gallery path','flag'); ?></th>
					<td><input readonly="readonly" type="text" size="35" name="galleryPath" value="<?php echo $flag_options['galleryPath']; ?>" />
					<span class="setting-description"><?php _e('This is the default path for all galleries','flag'); ?></span></td>
				</tr>
				<tr valign="top">
					<th><?php _e('Default flash size (W x H)','flag'); ?>:</th>
					<td><input type="text" size="4" maxlength="4" name="flashWidth" value="<?php echo $flag_options['flashWidth']; ?>" /> x
					<input type="text" size="4" maxlength="4" name="flashHeight" value="<?php echo $flag_options['flashHeight']; ?>" /></td>
				</tr>					
				<tr valign="top">
					<th align="left"><?php _e('Delete image files','flag'); ?></th>
					<td><input <?php if (IS_WPMU) echo 'readonly = "readonly"'; ?> type="checkbox" name="deleteImg" value="1" <?php checked('1', $flag_options['deleteImg']); ?> />
					<?php _e('Delete files, when removing a gallery in the database','flag'); ?></td>
				</tr>
				<tr>
					<th align="left"><?php _e('Activate Deep Linking (optional)','flag'); ?><br /><small><?php _e('Not all skins support this feature.','flag'); ?></small></th>
					<td><input type="checkbox" name="deepLinks" value="1" <?php checked('1', $flag_options['deepLinks']); ?> />
					<span class="setting-description"><?php _e('Deep links for images in flash.','flag'); ?></span></td>
				</tr>
				<tr>
					<th align="left"><?php _e('Activate Media RSS feed','flag'); ?></th>
					<td><input type="checkbox" name="useMediaRSS" value="1" <?php checked('1', $flag_options['useMediaRSS']); ?> />
					<span class="setting-description"><?php _e('A RSS feed will be added to you blog header.','flag'); ?></span></td>
				</tr>
			</table>

			<h3><?php _e('Image settings','flag'); ?></h3>
			<table class="form-table flag-options" style="width: auto; white-space: nowrap;">
				<tr valign="top">
					<th scope="row" width="200"><label><?php _e('Use optimized images for mobile browsers','flag'); ?></label><br /><small>(Manage Gallery -> 'Optimize images for web' action)</small></th>
					<td><input type="checkbox" name="optimized_imgs" value="1" <?php checked('1', $flag_options['optimized_imgs']); ?> />
						<span class="setting-description"><?php _e('Optimized images loads faster, save traffic and make page more lightweight for browser','flag'); ?></span></td>
				</tr>
				<tr valign="top">
					<th align="left"><?php _e('Image quality','flag'); ?></th>
					<td><input type="text" size="3" maxlength="3" name="imgQuality" value="<?php echo $flag_options['imgQuality']; ?>" /> % &nbsp;&nbsp;<span class="setting-description"><?php _e('Default: 85%','flag'); ?></span></td>
				</tr>
			</table>

			<h3><?php _e('Sort options','flag'); ?></h3>
			<table class="form-table flag-options" style="width: auto; white-space: nowrap;">
				<tr>
					<th valign="top" width="200"><?php _e('Sort galleries','flag'); ?>:</th>
					<td valign="top">
						<label><input name="albSort" type="radio" value="gid" <?php checked('gid', $flag_options['albSort']); ?> /> <?php _e('Gallery ID', 'flag'); ?></label><br />
						<label><input name="albSort" type="radio" value="title" <?php checked('title', $flag_options['albSort']); ?> /> <?php _e('Title', 'flag'); ?></label><br />
					</td>
					<td valign="top"><label><input name="albSortDir" type="radio" value="ASC" <?php checked('ASC', $flag_options['albSortDir']); ?> /> <?php _e('Ascending', 'flag'); ?></label><br />
						<label><input name="albSortDir" type="radio" value="DESC" <?php checked('DESC', $flag_options['albSortDir']); ?> /> <?php _e('Descending', 'flag'); ?></label>
					</td>
				</tr>
				<tr style="border-bottom: 1px solid #000000;">
					<th valign="top" width="200"><?php _e('Galleries per page: <br><small>on Manage Galleries page</small>','flag'); ?></th>
					<td valign="top">
						<input name="albPerPage" type="text" value="<?php echo $flag_options['albPerPage']; ?>" />
					</td>
					<td valign="top"></td>
				</tr>
				<tr>
					<th valign="top" width="200"><?php _e('Sort images','flag'); ?>:</th>
					<td valign="top">
						<label><input name="galSort" type="radio" value="sortorder" <?php checked('sortorder', $flag_options['galSort']); ?> /> <?php _e('Custom order', 'flag'); ?></label><br />
						<label><input name="galSort" type="radio" value="pid" <?php checked('pid', $flag_options['galSort']); ?> /> <?php _e('Image ID', 'flag'); ?></label><br />
						<label><input name="galSort" type="radio" value="filename" <?php checked('filename', $flag_options['galSort']); ?> /> <?php _e('File name', 'flag'); ?></label><br />
						<label><input name="galSort" type="radio" value="alttext" <?php checked('alttext', $flag_options['galSort']); ?> /> <?php _e('Alt / Title text', 'flag'); ?></label><br />
						<label><input name="galSort" type="radio" value="imagedate" <?php checked('imagedate', $flag_options['galSort']); ?> /> <?php _e('Date / Time', 'flag'); ?></label><br />
						<label><input name="galSort" type="radio" value="hitcounter" <?php checked('hitcounter', $flag_options['galSort']); ?> /> <?php _e('Image views', 'flag'); ?></label><br />
						<label><input name="galSort" type="radio" value="total_votes" <?php checked('total_votes', $flag_options['galSort']); ?> /> <?php _e('Image likes', 'flag'); ?></label><br />
						<label><input name="galSort" type="radio" value="rand()" <?php checked('rand()', $flag_options['galSort']); ?> /> <?php _e('Randomly', 'flag'); ?></label>
					</td>
					<td valign="top"><label><input name="galSortDir" type="radio" value="ASC" <?php checked('ASC', $flag_options['galSortDir']); ?> /> <?php _e('Ascending', 'flag'); ?></label><br />
						<label><input name="galSortDir" type="radio" value="DESC" <?php checked('DESC', $flag_options['galSortDir']); ?> /> <?php _e('Descending', 'flag'); ?></label>
					</td>
				</tr>
			</table>

			<h3><?php _e('Alternative Gallery Options','flag'); ?> <br><small style="color: darkgreen;"><?php _e('(Note: this is not flash skin option. Options below only for alternative gallery in mobile browsers)','flag'); ?></small></h3>
			<table class="flag_colors form-table flag-options" style="width: auto; white-space: nowrap;">
				<tr>
					<th align="left"><?php _e('Show jQuery gallery for browsers without flashplayer','flag'); ?></th>
					<td><input type="checkbox" name="jAlterGal" value="1" <?php checked('1', $flag_options['jAlterGal']); ?> /></td>
				</tr>
				<tr>
					<th align="left"><?php _e('jQuery gallery script','flag'); ?></th>
					<td><select name="jAlterGalScript">
							<option value="0" <?php selected('0', $flag_options['jAlterGalScript']); ?>>FancyBox</option>
							<option value="1" <?php selected('1', $flag_options['jAlterGalScript']); ?>>PhotoSwipe</option>
						</select>
					</td>
				</tr>
				<tr>
					<th align="left"><?php _e('Disable image views/likes counter on thumbnails','flag'); ?></th>
					<td><input type="checkbox" name="disableViews" value="1" <?php checked('1', $flag_options['disableViews']); ?> /></td>
				</tr>
				<tr>
					<th width="200"><?php _e('Top Bar BG','flag'); ?>:</th>
					<td><input class="colorPick" type="text" size="7" maxlength="6" id="BarsBG" name="BarsBG" value="<?php echo $flag_options['BarsBG']?>" /><div id="cp_BarsBG" style="background:#F9F9F9;position:absolute;display:none;"></div></td>
				</tr>
				<tr>					
					<th><?php _e('Category Buttons BG','flag'); ?>:</th>
					<td>
						<input class="colorPick" type="text" size="7" maxlength="6" id="CatBGColorOver" name="CatBGColorOver" value="<?php echo $flag_options['CatBGColorOver']; ?>" /> mouseOver<br />
						<div id="cp_CatBGColorOver" style="background:#F9F9F9;position:absolute;display:none;"></div>
						<input class="colorPick" type="text" size="7" maxlength="6" id="CatBGColor" name="CatBGColor" value="<?php echo $flag_options['CatBGColor']; ?>" /> mouseOut<br />
						<div id="cp_CatBGColor" style="background:#F9F9F9;position:absolute;display:none;"></div>
					</td>
				</tr>
				<tr>					
					<th><?php _e('Category Buttons Color','flag'); ?>:</th>
					<td>
						<input class="colorPick" type="text" size="7" maxlength="6" id="CatColorOver" name="CatColorOver" value="<?php echo $flag_options['CatColorOver']; ?>" /> mouseOver<br />
						<div id="cp_CatColorOver" style="background:#F9F9F9;position:absolute;display:none;"></div>
						<input class="colorPick" type="text" size="7" maxlength="6" id="CatColor" name="CatColor" value="<?php echo $flag_options['CatColor']; ?>" /> mouseOut<br />
						<div id="cp_CatColor" style="background:#F9F9F9;position:absolute;display:none;"></div>
					</td>
				</tr>
				<tr>					
					<th><?php _e('Thumbnail BG','flag'); ?>:</th>
					<td><input class="colorPick" type="text" size="7" maxlength="6" id="ThumbBG" name="ThumbBG" value="<?php echo $flag_options['ThumbBG']; ?>" /><div id="cp_ThumbBG" style="background:#F9F9F9;position:absolute;display:none;"></div></td>
				</tr>
				<tr>					
					<th><?php _e('Thumbnail MouseOver BG','flag'); ?>:</th>
					<td><input class="colorPick" type="text" size="7" maxlength="6" id="ThumbLoaderColor" name="ThumbLoaderColor" value="<?php echo $flag_options['ThumbLoaderColor']; ?>" /><div id="cp_ThumbLoaderColor" style="background:#F9F9F9;position:absolute;display:none;"></div></td>
				</tr>
				<tr>
					<th><?php _e('Fancybox Title','flag'); ?>:<br /><small><?php _e('Only if FancyBox script is selected','flag'); ?></small></th>
					<td><input class="colorPick" type="text" size="7" maxlength="6" id="TitleColor" name="TitleColor" value="<?php echo $flag_options['TitleColor']; ?>" /><div id="cp_TitleColor" style="background:#F9F9F9;position:absolute;display:none;"></div></td>
				</tr>
				<tr>
					<th><?php _e('Fancybox Description Text','flag'); ?>:<br /><small><?php _e('Only if FancyBox script is selected','flag'); ?></small></th>
					<td><input class="colorPick" type="text" size="7" maxlength="6" id="DescrColor" name="DescrColor" value="<?php echo $flag_options['DescrColor']; ?>" /><div id="cp_DescrColor" style="background:#F9F9F9;position:absolute;display:none;"></div></td>
				</tr>
			</table>
			<div class="submit"><input class="button-primary" type="submit" name="updateoption" value="<?php _e('Save Changes', 'flag'); ?>"/></div>
		</form>	
	</div>

<?php if(current_user_can('administrator')){ ?>
	<div id="rControl" class="cptab">
		<form name="rControl"  method="post" style="float: left;width: 50%;">
			<?php wp_nonce_field('flag_settings'); ?>
			<input type="hidden" name="page_options" value="access_key,license_key" />
			<h2><?php _e('License Key & Remote Control','flag'); ?></h2>
			<input type="hidden" name="access_url" value="<?php echo plugins_url() . '/' . FLAGFOLDER . '/lib/app.php'; ?>" />
			<table class="form-table flag-options" style="">
				<tr>
					<th valign="top" width="200"><a href="http://mypgc.co/membership/" target="_blank"><?php _e('License Key', 'flag') ?></a>:</th>
					<td valign="top"><input type="text" size="40" id="license_key" name="license_key" value="<?php echo $flag_options['license_key']?>" /></td>
				</tr>
				<tr>
					<td colspan="2"><br><?php _e('If you want to upload photos to FlAGallery right from your iPhone <a href="https://itunes.apple.com/us/app/mypgc/id663405181?ls=1&mt=8">download application</a> and enter access key below. You can enter your own access key. You can change these at any point in time and this will force all users to have to log in again in application.', 'flag'); ?> </td>
				</tr>
				<tr>
					<th valign="top" width="200"><?php _e('Remote App Access Key','flag'); ?>:</th>
					<td valign="top"><input type="text" size="40" id="access_key" name="access_key" value="<?php echo $flag_options['access_key']?>" /><br>
						<small><?php _e('Leave blank to disable access from application', 'flag'); ?></small></td>
				</tr>
			</table>
			<p><a href="https://itunes.apple.com/us/app/mypgc/id663405181?ls=1&mt=8"><img src="<?php echo plugins_url() . '/' . FLAGFOLDER; ?>/admin/images/appstore_button.png" alt="Download from AppStore" /></a></p>
			<div class="submit"><input class="button-primary" type="submit" name="membership" value="<?php _e('Update Settings for Remote Access', 'flag'); ?>"/></div>
		</form>
		<?php if($regform || empty($flag_options['access_key'])){ ?>
			<form name="reg_on_mypgc" method="post" style="float: left; border: 1px solid #666666; background-color: #ffffee; margin-top: 95px; width: 49%;">
				<?php wp_nonce_field('flag_settings'); ?>
				<h3 style="padding-left: 10px;"><?php _e('Register with form below or <a href="http://mypgc.co/membership/" target="_blank">purchase license key</a>','flag'); ?></h3>
				<div style="padding:0 10px;">
					<p><?php _e("this email and password that you'll receive will be used to login in iOS application",'flag'); ?></p>
					<p><?php _e("Note: if you already purchased license key you don't need register again.",'flag'); ?></p>
				</div>
				<input type="hidden" name="access_key" value="<?php echo $flag_options['access_key']?>" />
				<input type="hidden" name="access_url" value="<?php echo plugins_url() . '/' . FLAGFOLDER . '/lib/app.php'; ?>" />
				<table class="form-table" style="100%;">
					<tr>
						<td valign="top" style="width: 50%;"><?php _e('First Name', 'flag') ?>:<br><input type="text" id="customer_first_name" name="customer_first_name" value="" style="width: 95%;" /></td>
						<td valign="top"><?php _e('Last Name', 'flag') ?>:<br><input type="text" id="customer_last_name" name="customer_last_name" value="" style="width: 95%;" /></td>
					</tr>
					<tr>
						<td valign="top"><?php _e('Email', 'flag') ?>:<br><input type="text" size="54" id="customer_email" name="customer_email" value="" style="width: 95%;" /></td>
						<td valign="top"><div class="submit"><input class="button-primary" type="submit" name="register_subscriber" value="<?php _e('Register', 'flag'); ?>"/></div></td>
					</tr>
				</table>
			</form>
		<?php } ?>
		<div style="clear: both;"> </div>
	</div>
<?php } ?>

	<div id="vPlayer" class="cptab">
		<form name="vPlayer"  method="post">
			<?php wp_nonce_field('flag_settings'); ?>
			<input type="hidden" name="page_options" value="videoBG,vmColor1,vmColor2,vmAutoplay,vmWidth,vmHeight" />
			<h2><?php _e('Flash Video Player Colors','flag'); ?></h2>
			<table class="flag_colors form-table flag-options" style="width: auto; white-space: nowrap;">
				<tr>
					<th width="200"><?php _e('Video BG','flag'); ?>:</th>
					<td><input class="colorPick" type="text" size="7" maxlength="6" id="videoBG" name="videoBG" value="<?php echo $flag_options['videoBG']?>" /><div id="cp_videoBG" style="background:#F9F9F9;position:absolute;display:none;"></div></td>
				</tr>
				<tr>
					<th><?php _e('Color 1','flag'); ?>:</th>
					<td><input class="colorPick" type="text" size="7" maxlength="6" id="vmColor1" name="vmColor1" value="<?php echo $flag_options['vmColor1']?>" /><div id="cp_vmColor1" style="background:#F9F9F9;position:absolute;display:none;"></div></td>
				</tr>
				<tr>
					<th><?php _e('Color 2','flag'); ?>:</th>
					<td>
						<input class="colorPick" type="text" size="7" maxlength="6" id="vmColor2" name="vmColor2" value="<?php echo $flag_options['vmColor2']; ?>" /><div id="cp_vmColor2" style="background:#F9F9F9;position:absolute;display:none;"></div></td>
				</tr>
				<tr>
					<th><?php _e('Autoplay','flag'); ?>:</th>
					<td>
						<label><input name="vmAutoplay" type="radio" value="true" <?php checked('true', $flag_options['vmAutoplay']); ?> /> <?php _e('True', 'flag'); ?></label><br />
						<label><input name="vmAutoplay" type="radio" value="false" <?php checked('false', $flag_options['vmAutoplay']); ?> /> <?php _e('False', 'flag'); ?></label><br />
					</td>
				</tr>
				<tr>
					<th><?php _e('Default Size','flag'); ?>:<br /><small>(width x height)</small></th>
					<td>
						<input name="vmWidth" type="text" size="3" maxlength="3" value="<?php echo $flag_options['vmWidth']; ?>" /> x <input name="vmHeight" type="text" size="3" maxlength="3" value="<?php echo $flag_options['vmHeight']; ?>" />
					</td>
				</tr>
			</table>
			<div class="submit"><input class="button-primary" type="submit" name="updateoption" value="<?php _e('Save Changes', 'flag'); ?>"/></div>
		</form>
	</div>

	<div id="mPlayer" class="cptab">
		<form name="mPlayer"  method="post">
			<?php wp_nonce_field('flag_settings'); ?>
			<input type="hidden" name="page_options" value="mpBG,mpColor1,mpColor2,mpAutoplay" />
			<h2><?php _e('MP3 Player Colors','flag'); ?></h2>
			<table class="flag_colors form-table flag-options" style="width: auto; white-space: nowrap;">
				<tr>
					<th width="200"><?php _e('Player BG','flag'); ?>:</th>
					<td><input class="colorPick" type="text" size="7" maxlength="6" id="mpBG" name="mpBG" value="<?php echo $flag_options['mpBG']?>" /><div id="cp_mpBG" style="background:#F9F9F9;position:absolute;display:none;"></div></td>
				</tr>
				<tr>
					<th><?php _e('Color 1','flag'); ?>:</th>
					<td><input class="colorPick" type="text" size="7" maxlength="6" id="mpColor1" name="mpColor1" value="<?php echo $flag_options['mpColor1']?>" /><div id="cp_mpColor1" style="background:#F9F9F9;position:absolute;display:none;"></div></td>
				</tr>
				<tr>					
					<th><?php _e('Color 2','flag'); ?>:</th>
					<td>
						<input class="colorPick" type="text" size="7" maxlength="6" id="mpColor2" name="mpColor2" value="<?php echo $flag_options['mpColor2']; ?>" /><div id="cp_mpColor2" style="background:#F9F9F9;position:absolute;display:none;"></div></td>
				</tr>
				<tr>
					<th><?php _e('Autoplay','flag'); ?>:</th>
					<td>
						<label><input name="mpAutoplay" type="radio" value="true" <?php checked('true', $flag_options['mpAutoplay']); ?> /> <?php _e('True', 'flag'); ?></label><br />
						<label><input name="mpAutoplay" type="radio" value="false" <?php checked('false', $flag_options['mpAutoplay']); ?> /> <?php _e('False', 'flag'); ?></label><br />
					</td>
				</tr>
			</table>
			<div class="submit"><input class="button-primary" type="submit" name="updateoption" value="<?php _e('Save Changes', 'flag'); ?>"/></div>
		</form>
	</div>
	
<?php if (flagGallery::flag_wpmu_enable_function('wpmuRoles')) : ?>
	<div id="roles" class="cptab">
		<form method="POST" name="addroles" id="addroles" accept-charset="utf-8">
			<?php wp_nonce_field('flag_addroles'); ?>
			<h2><?php _e('Roles / capabilities','flag'); ?></h2>
			<p><?php _e('Select the lowest role which should be able to access the follow capabilities. Flash Album Gallery supports the standard roles from WordPress.', 'flag'); ?></p>
			<table class="form-table"> 
			<tr valign="top"> 
				<th scope="row" style="white-space: nowrap"><?php _e('Main Flash Album Gallery overview', 'flag'); ?>:</th> 
				<td><label for="general"><select style="width: 150px;" name="general" id="general"><?php wp_dropdown_roles( flag_get_role('FlAG overview') ); ?></select></label></td>
			</tr>
			<tr valign="top"> 
				<th scope="row" style="white-space: nowrap"><?php _e('View TinyMCE Button / GRAND Pages', 'flag'); ?>:</th>
				<td><label for="tinymce"><select style="width: 150px;" name="tinymce" id="tinymce"><?php wp_dropdown_roles( flag_get_role('FlAG Use TinyMCE') ); ?></select></label></td>
			</tr>
			<tr valign="top"> 
				<th scope="row" style="white-space: nowrap"><?php _e('Add gallery / Upload images', 'flag'); ?>:</th> 
				<td><label for="add_gallery"><select style="width: 150px;" name="add_gallery" id="add_gallery"><?php wp_dropdown_roles( flag_get_role('FlAG Upload images') ); ?></select></label></td>
			</tr>
			<tr valign="top"> 
				<th scope="row" style="white-space: nowrap"><?php _e('Import images folder', 'flag'); ?>:</th> 
				<td><label for="add_gallery"><select style="width: 150px;" name="import_gallery" id="import_gallery"><?php wp_dropdown_roles( flag_get_role('FlAG Import folder') ); ?></select></label></td>
			</tr>
			<tr valign="top"> 
				<th scope="row" style="white-space: nowrap"><?php _e('Manage gallery', 'flag'); ?>:</th> 
				<td><label for="manage_gallery"><select style="width: 150px;" name="manage_gallery" id="manage_gallery"><?php wp_dropdown_roles( flag_get_role('FlAG Manage gallery') ); ?></select></label></td>
			</tr>
			<tr valign="top"> 
				<th scope="row" style="white-space: nowrap"><?php _e('Manage others galleries and Albums', 'flag'); ?>:</th>
				<td><label for="manage_others"><select style="width: 150px;" name="manage_others" id="manage_others"><?php wp_dropdown_roles( flag_get_role('FlAG Manage others gallery') ); ?></select></label></td>
			</tr>
			<tr valign="top"> 
				<th scope="row" style="white-space: nowrap"><?php _e('Manage music', 'flag'); ?>:</th> 
				<td><label for="manage_music"><select style="width: 150px;" name="manage_music" id="manage_music"><?php wp_dropdown_roles( flag_get_role('FlAG Manage music') ); ?></select></label></td>
			</tr>
			<tr valign="top"> 
				<th scope="row" style="white-space: nowrap"><?php _e('Manage video', 'flag'); ?>:</th> 
				<td><label for="manage_video"><select style="width: 150px;" name="manage_video" id="manage_video"><?php wp_dropdown_roles( flag_get_role('FlAG Manage video') ); ?></select></label></td>
			</tr>
			<tr valign="top"> 
				<th scope="row" style="white-space: nowrap"><?php _e('Manage banners', 'flag'); ?>:</th> 
				<td><label for="manage_banners"><select style="width: 150px;" name="manage_banners" id="manage_banners"><?php wp_dropdown_roles( flag_get_role('FlAG Manage banners') ); ?></select></label></td>
			</tr>
			<tr valign="top"> 
				<th scope="row" style="white-space: nowrap"><?php _e('Change skin', 'flag'); ?>:</th> 
				<td><label for="change_skin"><select style="width: 150px;" name="change_skin" id="change_skin"><?php wp_dropdown_roles( flag_get_role('FlAG Change skin') ); ?></select></label></td>
			</tr>
			<tr valign="top"> 
				<th scope="row" style="white-space: nowrap"><?php _e('Add skins', 'flag'); ?>:</th> 
				<td><label for="add_skins"><select style="width: 150px;" name="add_skins" id="add_skins"><?php wp_dropdown_roles( flag_get_role('FlAG Add skins') ); ?></select></label></td>
			</tr>
			<tr valign="top"> 
				<th scope="row" style="white-space: nowrap"><?php _e('Delete skins', 'flag'); ?>:</th> 
				<td><label for="delete_skins"><select style="width: 150px;" name="delete_skins" id="delete_skins"><?php wp_dropdown_roles( flag_get_role('FlAG Delete skins') ); ?></select></label></td>
			</tr>
			<tr valign="top"> 
				<th scope="row" style="white-space: nowrap"><?php _e('Change options', 'flag'); ?>:</th> 
				<td><label for="change_options"><select style="width: 150px;" name="change_options" id="change_options"><?php wp_dropdown_roles( flag_get_role('FlAG Change options') ); ?></select></label></td>
			</tr>
			<tr valign="top"> 
				<th scope="row" style="white-space: nowrap"><?php _e('Facebook page', 'flag'); ?>:</th> 
				<td><label for="facebook_page"><select style="width: 150px;" name="facebook_page" id="facebook_page"><?php wp_dropdown_roles( flag_get_role('FlAG Facebook page') ); ?></select></label></td>
			</tr>
			</table>
			<div class="submit"><input type="submit" class="button-primary" name= "update_cap" value="<?php _e('Update capabilities', 'flag'); ?>"/></div>
		</form>
	</div>
<?php endif; ?>
</div>
<script type="text/javascript">
	var cptabs=new ddtabcontent("tabs");
	cptabs.setpersist(true);
	cptabs.setselectedClassTarget("linkparent");
	cptabs.init();
</script>

	<?php
}

function flag_get_sorted_roles() {
	// This function returns all roles, sorted by user level (lowest to highest)
	global $wp_roles;
	$roles = $wp_roles->role_objects;
	$sorted = array();
	
	if( class_exists('RoleManager') ) {
		foreach( $roles as $role_key => $role_name ) {
			$role = get_role($role_key);
			if( empty($role) ) continue;
			$role_user_level = array_reduce(array_keys($role->capabilities), array('WP_User', 'level_reduction'), 0);
			$sorted[$role_user_level] = $role;
		}
		$sorted = array_values($sorted);
	} else {
		$role_order = array("subscriber", "contributor", "author", "editor", "administrator");
		foreach($role_order as $role_key) {
			$sorted[$role_key] = get_role($role_key);
		}
	}
	return $sorted;
}

function flag_get_role($capability){
	// This function return the lowest roles which has the capabilities
	$check_order = flag_get_sorted_roles();

	$args = array_slice(func_get_args(), 1);
	$args = array_merge(array($capability), $args);

	foreach ($check_order as $check_role) {
		if ( empty($check_role) )
			return false;
			
		if (call_user_func_array(array(&$check_role, 'has_cap'), $args))
			return $check_role->name;
	}
	return false;
}

function flag_set_capability($lowest_role, $capability){
	// This function set or remove the $capability
	$check_order = flag_get_sorted_roles();

	$add_capability = false;
	
	foreach ($check_order as $the_role) {
		$role = $the_role->name;

		if ( $lowest_role == $role )
			$add_capability = true;
		
		// If you rename the roles, the please use the role manager plugin
		
		if ( empty($the_role) )
			continue;
			
		$add_capability ? $the_role->add_cap($capability) : $the_role->remove_cap($capability) ;
	}
	
}

?>
