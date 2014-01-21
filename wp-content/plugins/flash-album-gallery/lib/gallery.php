<?php
// Create XML output
header("content-type:text/xml;charset=utf-8");

// look up for the path
require_once( str_replace("\\","/", dirname(dirname(__FILE__)) . "/flag-config.php") );

/** @var $wpdb wpdb */
global $wpdb;
$siteurl = get_option ('siteurl');
// get the gallery id
$gID = explode( '_', $_GET['gid'] );
$gID = array_filter($gID, 'intval');
$skin = sanitize_flagname($_GET['skinName']);
$flag_options = get_option ('flag_options');

$file =  str_replace("\\","/", dirname(dirname(dirname(__FILE__))).'/flagallery-skins/'.$skin.'/settings/settings.xml');
$url_plug = plugins_url() . '/' . FLAGFOLDER . '/';
$mainXML="";
$fp = fopen($file, "r");
if(!$fp)
{
	exit( "0");//Failure - not read;
}
while(!feof($fp))
{
	$mainXML .= fgetc($fp);
}
if(isset($flag_options['license_key'])){
	$lkey = $flag_options['license_key'];
} else {
	$lkey = '';
}
$propertiesXML = substr ($mainXML, strpos($mainXML,"<properties>"),(strpos($mainXML,"</properties>")-strpos($mainXML,"<properties>")));
$propertiesXML .= "  <plug>{$url_plug}</plug>
  <key>{$lkey}</key>
</properties>
";

if ( is_user_logged_in() )
	$exclude_clause = '';
else 
	$exclude_clause = ' AND exclude<>1 ';

	echo "<gallery>\n";
	if($propertiesXML)
	{
		echo $propertiesXML;
	}
// get the pictures
foreach ( $gID as $galleryID ) {
	$galleryID = (int) $galleryID;
	$status = $wpdb->get_var("SELECT status FROM $wpdb->flaggallery WHERE gid={$galleryID}");
	if(intval($status)){
		continue;
	}
	if ( $galleryID == 0) {
		$thepictures = $wpdb->get_results("SELECT t.*, tt.* FROM $wpdb->flaggallery AS t INNER JOIN $wpdb->flagpictures AS tt ON t.gid = tt.galleryid WHERE 1=1 {$exclude_clause} ORDER BY tt.{$flag_options['galSort']} {$flag_options['galSortDir']} ");
	} else {
		$thepictures = $wpdb->get_results("SELECT t.*, tt.* FROM $wpdb->flaggallery AS t INNER JOIN $wpdb->flagpictures AS tt ON t.gid = tt.galleryid WHERE t.gid = '$galleryID' {$exclude_clause} ORDER BY tt.{$flag_options['galSort']} {$flag_options['galSortDir']} ");
	}

  if (is_array ($thepictures) && count($thepictures)){
	echo "	<category id='".$galleryID."'>\n";
	echo "		<properties>\n";
	echo "			<title>".esc_html(flagGallery::i18n(stripslashes($thepictures[0]->title)))."</title>\n";
	echo "		</properties>\n";
	echo "		<items>\n";

	if (is_array ($thepictures)){
		foreach ($thepictures as $picture) {
	echo "			<item id='".$picture->pid."'>\n";
	echo "				<thumbnail>".$siteurl."/".$picture->path."/thumbs/thumbs_".$picture->filename."</thumbnail>\n";
	echo "				<title><![CDATA[".esc_html(flagGallery::i18n(stripslashes($picture->alttext)))."]]></title>\n";
	echo "				<description><![CDATA[".html_entity_decode(esc_html(flagGallery::i18n(stripslashes($picture->description))))."]]></description>\n";
	//echo "				<link>".$picture->link."</link>\n";
	echo "				<photo>".$siteurl."/".$picture->path."/".$picture->filename."</photo>\n";
	echo "				<date>".$picture->imagedate."</date>\n";
	echo "			</item>\n";
		}
	}

	echo "		</items>\n";
	echo "	</category>\n";
  }
}
	echo "</gallery>\n";

?>