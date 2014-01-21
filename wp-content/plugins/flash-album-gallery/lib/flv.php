<?php // Create XML output
require_once( dirname(dirname(__FILE__)) . '/flag-config.php');
$flag_options = get_option ('flag_options');
if(isset($_GET['vID'])) {
	header("content-type:text/xml;charset=utf-8");
	$id = intval($_GET['vID']);
	$vid = get_post($id);
	if(in_array($vid->post_mime_type, array('video/x-flv'))) {
		$thumb = get_post_meta($id, 'thumbnail', true);
		$content = '<item id="'.$vid->ID.'">
	<properties>
		<property0>0x'.$flag_options["vmColor1"].'</property0>
		<property1>0x'.$flag_options["vmColor2"].'</property1>
		<property2>0x'.$flag_options["videoBG"].'</property2>
	</properties>
	<content>
	  	<preview>'.$vid->guid.'</preview>
  		<title><![CDATA['.$vid->post_title.']]></title>
  		<description><![CDATA['.$vid->post_content.']]></description>
  		<thumbnail>'.$thumb.'</thumbnail>
	</content>
</item>';
		echo $content;
	} else {
		echo 'wrong mime type';
	}
} else {
	echo 'no such file ID';
}
?>