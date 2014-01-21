<?php

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

/**
 * flag_upgrade() - update routine for older version
 * 
 * @return mixed Success message
 */
function flag_upgrade() {
	
	global $wpdb, $user_ID;

	// get the current user ID
	get_currentuserinfo();

	// Be sure that the tables exist
	if($wpdb->get_var("show tables like '$wpdb->flagpictures'") == $wpdb->prefix . 'flag_pictures') {

		$wpdb->show_errors();

		$installed_ver = get_option( "flag_db_version" );
		
		// v0.31 -> v0.32
		if (version_compare($installed_ver, '0.32', '<')) {
			// add description and previewpic for the ablum itself
			flag_add_sql_column( $wpdb->flagpictures, 'copyright', "TEXT;");
			flag_add_sql_column( $wpdb->flagpictures, 'credit', "TEXT;");
			flag_add_sql_column( $wpdb->flagpictures, 'country', "TINYTEXT;");
			flag_add_sql_column( $wpdb->flagpictures, 'state', "TINYTEXT;");
			flag_add_sql_column( $wpdb->flagpictures, 'city', "TINYTEXT;");
			flag_add_sql_column( $wpdb->flagpictures, 'location', "TEXT;");
			flag_add_sql_column( $wpdb->flagpictures, 'used_ips', "LONGTEXT;");
			flag_add_sql_column( $wpdb->flagpictures, 'total_votes', "INT(11) UNSIGNED DEFAULT '0';");
			flag_add_sql_column( $wpdb->flagpictures, 'total_value', "INT(11) UNSIGNED DEFAULT '0';");
			flag_add_sql_column( $wpdb->flagpictures, 'hitcounter', "INT(11) UNSIGNED DEFAULT '0';");
			flag_add_sql_column( $wpdb->flagpictures, 'commentson', "INT(1) UNSIGNED NOT NULL DEFAULT '1';");
			flag_add_sql_column( $wpdb->flagpictures, 'exclude', "TINYINT NULL DEFAULT '0';");
		
			$flag_options = get_option('flag_options');	
			$flag_options['skinsDirABS'] = FLAG_ABSPATH . 'skins/'; 
			$flag_options['skinsDirURL'] = FLAG_URLPATH . 'skins/'; 
			update_option('flag_options', $flag_options);
		}		
		// v0.32 -> v0.40
		if (version_compare($installed_ver, '0.40', '<')) {
			flag_add_sql_column( $wpdb->flagpictures, 'meta_data', "LONGTEXT AFTER used_ips;");
		}		

		// On some reason the import / date sometimes failed, due to the memory limit
		if (version_compare($installed_ver, '0.32', '<')) {
			echo __('Import date and time information...', 'flag');
			flag_import_date_time();
			echo __('finished', 'flag') . "<br />\n";
		}		

		if (version_compare($installed_ver, '1.20', '<')) {
			echo __('Adding new options to database...', 'flag');
			$flag_options = get_option('flag_options');	
			// Alternative gallery colors
			$flag_options['jAlterGal']				= true;
			$flag_options['BarsBG']					= '292929';
			$flag_options['CatBGColor']				= '292929';
			$flag_options['CatBGColorOver']			= '737373';
			$flag_options['CatColor']				= 'ffffff';
			$flag_options['CatColorOver']			= 'ffffff';
			$flag_options['ThumbBG']				= 'ffffff';
			$flag_options['ThumbLoaderColor']		= '4a4a4a';
			$flag_options['TitleColor']				= 'ff9900';
			$flag_options['DescrColor']				= 'cfcfcf';
			update_option('flag_options', $flag_options);
			echo __('finished', 'flag') . "<br />\n";
		}		
		if (version_compare($installed_ver, '1.22', '<')) {
			echo __('Adding new options to database...', 'flag');
			$flag_options = get_option('flag_options');	
			$flag_options['videoBG']				= '000000';
			$flag_options['vmColor1']				= 'ffffff';
			$flag_options['vmColor2']				= '3283A7';
			$flag_options['vmAutoplay']				= 'true';
			$flag_options['vmWidth']				= '520';
			$flag_options['vmHeight']				= '304';
			update_option('flag_options', $flag_options);
			echo __('finished', 'flag') . "<br />\n";
		}		
		if (version_compare($installed_ver, '1.24', '<')) {
			echo __('Adding new options to database...', 'flag');
			$flag_options = get_option('flag_options');	
			$flag_options['mpBG']			= '000000';
			$flag_options['mpColor1']		= 'ffffff';
			$flag_options['mpColor2']		= '3283A7';
			update_option('flag_options', $flag_options);
			echo __('finished', 'flag') . "<br />\n";
		}

		// v2.56 -> v2.70
		if (version_compare($installed_ver, '2.70', '<')) {
			flag_add_sql_column( $wpdb->flagpictures, 'link', "TEXT NULL AFTER alttext;");
		}

		// v2.72 -> v2.75
		if (version_compare($installed_ver, '2.75 ', '<')) {
			flag_add_sql_column( $wpdb->flagpictures, 'modified', "TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP AFTER imagedate;");
			flag_add_sql_column( $wpdb->flaggallery, 'status', "TINYINT NULL DEFAULT '0' AFTER author;");
		}

		// update now the database
		update_option( "flag_db_version", FLAG_DBVERSION );
		$wpdb->hide_errors();

	}
	return false;
}


/**
 * flag_import_date_time() - Read the timestamp from exif and insert it into the database
 * 
 * @return void
 */
function flag_import_date_time() {
	global $wpdb;
	
	$imagelist = $wpdb->get_results("SELECT t.*, tt.* FROM $wpdb->flaggallery AS t INNER JOIN $wpdb->flagpictures AS tt ON t.gid = tt.galleryid ORDER BY tt.pid ASC");
	if ( is_array($imagelist) ) {
		foreach ($imagelist as $image) {
			$picture = new flagImage($image);
			$meta = new flagMeta($picture->imagePath, true);
			$date = $meta->get_date_time();
			$wpdb->query($wpdb->prepare("UPDATE $wpdb->flagpictures SET imagedate = '%s' WHERE pid = '%d'", $date, $picture->pid));
		}		
	}	
}

/**
 * Adding a new column if needed
 * Example : flag_add_sql_column( $wpdb->flagpictures, 'imagedate', "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER alttext");
 * 
 * @param string $table_name Database table name.
 * @param string $column_name Database column name to create.
 * @param string $create_ddl SQL statement to create column
 * @return bool True, when done with execution.
 */
function flag_add_sql_column($table_name, $column_name, $create_ddl) {
	global $wpdb;
	
	foreach ($wpdb->get_col("SHOW COLUMNS FROM $table_name") as $column ) {
		if ($column == $column_name)
			return true;
	}
	
	//didn't find it try to create it.
	$wpdb->query("ALTER TABLE $table_name ADD COLUMN $column_name " . $create_ddl);
	
	// we cannot directly tell that whether this succeeded!
	foreach ($wpdb->get_col("SHOW COLUMNS FROM $table_name") as $column ) {
		if ($column == $column_name)
			return true;
	}
	
	echo("Could not add column $column_name in table $table_name<br />\n");
	return false;
}


?>
