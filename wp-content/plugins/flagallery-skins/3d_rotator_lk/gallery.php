<?php // Create XML output
header("content-type:text/xml;charset=utf-8");
preg_match('|^(.*?/)(wp-content)/|i', str_replace('\\', '/', __FILE__), $_m);
require_once( $_m[1] . 'wp-load.php');
$flag_options = get_option ('flag_options');
$file = $_GET['playlist'];
$playlistPath = $_m[1].$flag_options['galleryPath'].'playlists/banner/'.$file.'.xml';
if(file_exists($playlistPath)) {
	require_once( FLAG_ABSPATH.'admin/banner.functions.php');
	$playlist = get_b_playlist_data($playlistPath);
	if(count($playlist['items'])) {
		$content = '<items>';
		foreach( $playlist['items'] as $id ) {
			$ban = get_post($id);
			if($ban->ID) {
				$track = wp_get_attachment_url($ban->ID);
				$path = get_attached_file($ban->ID);
				$info = pathinfo($path);
				$dir = $info['dirname'];
				$ext = $info['extension'];
				$name = urldecode( basename( str_replace( '%2F', '/', urlencode( $path ) ), ".$ext" ) );
				$img_file = "{$dir}/{$name}-{$suffix}.{$ext}";
				/*if(!file_exists($img_file)){
					$track = $url;
				} else {
					$track = dirname($url)."/{$name}-{$suffix}.{$ext}";
				}*/
			    $thumbnail = get_post_meta($id, 'thumbnail', true);
			    $link = get_post_meta($id, 'link', true);
			    $preview = get_post_meta($id, 'preview', true);
				$content .= '
		<item id="'.$ban->ID.'">
	          <track>'.$track.'</track>
	          <title><![CDATA['.$ban->post_title.']]></title>
	          <link>'.$link.'</link>
	          <preview>'.$preview.'</preview>
	          <description><![CDATA['.$ban->post_content.']]></description>
	          <thumbnail>'.$thumbnail.'</thumbnail>
	        </item>';
			}
		}
		$content .= '
	</items>';
	}
	$xml = file_get_contents($playlistPath);
	$newXML = preg_replace("|<items>.*?</items>|si", $content, $xml);
	echo $newXML;
} else {
	echo 'no such file or directory';
}
?>