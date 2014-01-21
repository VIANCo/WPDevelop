<?php
define( 'SHORTINIT', true );
require_once( dirname(dirname(__FILE__)) . '/flag-config.php');

if(!function_exists('sanitize_flagname')){
	function sanitize_flagname( $filename ) {

		//$filename = wp_strip_all_tags( $filename );
		//$filename = remove_accents( $filename );
		// Kill octets
		$filename = preg_replace( '|%([a-fA-F0-9][a-fA-F0-9])|', '', $filename );
		$filename = preg_replace( '/&.+?;/', '', $filename ); // Kill entities
		$filename = preg_replace( '|[^a-zA-Z0-9 _.\-]|i', '', $filename );
		$filename = preg_replace('/[\s-]+/', '-', $filename);
		$filename = trim($filename, '.-_ ');

		return $filename;
	}
}

if(!function_exists('get_currentuserinfo')){
	require( ABSPATH . WPINC . '/formatting.php' );
	require( ABSPATH . WPINC . '/capabilities.php' );
	require( ABSPATH . WPINC . '/user.php' );
	require( ABSPATH . WPINC . '/meta.php' );
	require( ABSPATH . WPINC . '/pluggable.php' );
	require( ABSPATH . WPINC . '/post.php' );
	wp_cookie_constants( );
}

// check for correct capability
if ( !is_user_logged_in() )
	die('-1');

// check for correct FlAG capability
if ( !current_user_can('FlAG Change skin') )
	die('-1');

$flashPost = file_get_contents("php://input");
// parse properties_skin
$arr = array();
parse_str($flashPost, $arr);

$flag_options = get_option('flag_options');
$act_skin = isset($_GET['skin'])? $_GET['skin'] : $flag_options['flashSkin'];
$act_skin = sanitize_flagname($act_skin);
$settings = $flag_options['skinsDirABS'].$act_skin.'/settings';
$settingsXML =  $settings.'/settings.xml';

if(isset($arr['skin_name']))
	$settingsXML =  str_replace("\\","/", dirname(dirname(dirname(__FILE__))).'/flagallery-skins/'.sanitize_flagname($arr['skin_name']).'/settings/settings.xml');
if(isset($arr['properties_skin']) && !empty($arr['properties_skin'])) {
	$fp = fopen($settingsXML, "r");
	if(!$fp) {
		exit( "2");//Failure - not read;
	}
	$mainXML = '';
	while(!feof($fp)) {
		$mainXML .= fgetc($fp);
	}
	$fp = fopen($settingsXML, "w");
	if(!$fp)
		exit("0");//Failure
	$arr['properties_skin'] = str_replace( array( '=','?','"','$' ), '', $arr['properties_skin'] );
	$newProperties = preg_replace("|<properties>.*?</properties>|si", $arr['properties_skin'], $mainXML);
	if(fwrite($fp, $newProperties))
		echo "1";//Save
	else
		echo "0";
	fclose($fp);
}

if(isset($_GET['show_options'])) {
	flag_skin_options();
}

function flag_skin_options() {
	$flag_options = get_option('flag_options');
	$act_skin = isset($_GET['skin'])? urlencode($_GET['skin']) : $flag_options['flashSkin'];
	$act_skin = sanitize_flagname($act_skin);
	$settings = $flag_options['skinsDirURL'].$act_skin.'/settings';
	$settingsXML =  $flag_options['skinsDirABS'].$act_skin.'/settings/settings.xml';
	$fp = fopen($settingsXML, "r");
	if(!$fp) {
		echo '<p style="color:#ff0000;"><b>Error! The configuration file not be found. You need to reinstall this skin.</b></p>';
	} else {
		$flag_urlpath = dirname($flag_options['skinsDirURL']).'/flash-album-gallery/';
		$cPanel = $flag_urlpath."lib/cpanel.swf";
		$constructor = $flag_urlpath."lib/";
		$swfObject = $flag_urlpath."admin/js/swfobject.js?ver=2.2";
		?>
		<div id="skinOptions">
			<script type="text/javascript" src="<?php echo $swfObject ?>"></script>
			<script type="text/javascript">
				var flashvars = {
					path : "<?php echo $settings; ?>",
					constructor : "<?php echo $constructor; ?>",
					skin : "<?php echo $act_skin; ?>"
				};
				var params = {
					wmode : "transparent",
					scale : "noScale",
					saling : "lt",
					allowfullscreen : "false",
					menu : "false"
				};
				var attributes = {};
				swfobject.embedSWF("<?php echo $cPanel; ?>", "myContent", "600", "550", "9.0.0", "<?php echo $flag_urlpath; ?>skins/expressInstall.swf", flashvars, params, attributes);
			</script>
			<div id="myContent"><a href="http://www.adobe.com/go/getflash"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a>
				<p>This page requires Flash Player version 10.1.52 or higher.</p>
			</div>	
		</div> 
		<?php
	}
	fclose($fp);
}

?>