<?php
require_once( dirname(dirname(__FILE__)) . '/flag-config.php');
// check for correct capability
if ( !is_user_logged_in() )
	die('-1');

// check for correct FlAG capability
if ( !current_user_can('FlAG Manage video') ) 
	die('-1');	?>
<html>
<head>
  <title>Preview Video</title>
	<script type="text/javascript" src="<?php echo plugins_url('/'.FLAGFOLDER.'/'); ?>admin/js/jquery.js"></script>
	<script type="text/javascript" src="<?php echo plugins_url('/'.FLAGFOLDER.'/'); ?>admin/js/swfobject.js"></script>
</head>
<body style="margin: 0; padding: 0; background: #555555; overflow: hidden;">
<?php $vidID = intval($_GET['vid']);
echo flagShowVmPlayer($vidID, $w='520', $h='304', $autoplay=true); ?>
</body>
</html>