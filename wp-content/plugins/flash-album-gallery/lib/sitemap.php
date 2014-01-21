<?php
/**
 * PHP Class for Wordpress SEO plugin
 *
 */
class flagallerySitemaps {

	var $images	= array();

	/**
	 * flagallerySitemaps::__construct()
	 */
	function __construct() {

		add_filter('wpseo_sitemap_urlimages', array( &$this, 'add_wpseo_xml_sitemap_images'), 10, 2);

	}

	/**
	 * Filter support for WordPress SEO by Yoast 0.4.0 or higher ( http://wordpress.org/extend/plugins/wordpress-seo/ )
	 *
	 * @param array $images
	 * @param int   $post_id
	 *
	 * @return array $image list of all founded images
	 */
	function add_wpseo_xml_sitemap_images( $images, $post_id )  {

		$this->images = $images;

		// first get the content of the post/page
		$p = get_post($post_id);

		if('flagallery' == get_post_type( $post_id )){
			$flag_custom = get_post_custom($post_id);
			$content = $flag_custom["mb_scode"][0];
		} else {
			$content = $p->post_content;
		}

		// Don't process the images in the normal way
		remove_all_shortcodes();

		add_shortcode( 'flagallery', array(&$this, 'show_flashalbum' ) );

		// Search now for shortcodes
		do_shortcode( $content );

		return $this->images;
	}

	/**
	 * Parse the flagallery shortcode and return all images into an array
	 *
	 * @param string $atts
	 * @return string
	 */
	function show_flashalbum( $atts ) {
		global $wpdb, $flagdb;

		extract(shortcode_atts(array(
			'gid' 		=> '',
			'album'		=> '',
			'exclude' 	=> ''
		), $atts ));

		$siteurl = site_url();
		/**
		 * @var $album
		 * @var $gid
		 * @var $exclude
		 **/
		$draft_clause = (get_option( 'flag_db_version' ) < 2.75) ? '' : 'AND status=0';
		if($album) {
			$gallerylist = $flagdb->get_album($album);
			$ids = explode( ',', $gallerylist );
			$galleryIDs = array();
			foreach ($ids as $id) {
				$galleryIDs[] = $wpdb->get_var($wpdb->prepare("SELECT gid FROM {$wpdb->flaggallery} WHERE gid = %d $draft_clause", $id));
			}
			$galleryIDs = array_filter($galleryIDs);

		} elseif($gid == "all") {
			$galleryIDs = $gallerylist = array();
			$excludelist = explode(',',$exclude);
			$galleries = $flagdb->find_all_galleries();
			foreach($galleries as $gallery) {
				if (in_array($gallery->gid, $excludelist))
					continue;
				$galleryIDs[] = $gallery->gid;
			}

		} else {
			$ids = explode( ',', $gid );

			$galleryIDs = array();
			foreach ($ids as $id) {
				$id = intval($id);
				$galleryIDs[] = $wpdb->get_var($wpdb->prepare("SELECT gid FROM {$wpdb->flaggallery} WHERE gid = %d $draft_clause", $id));
			}
			$galleryIDs = array_filter($galleryIDs);

		}

		if(empty($galleryIDs))
			return '';

		foreach ( $galleryIDs as $galID ) {
			$galID = (int) $galID;
			$status = $wpdb->get_var("SELECT status FROM $wpdb->flaggallery WHERE gid={$galID}");
			if(intval($status)){
				continue;
			}

			$path = $wpdb->get_var("SELECT path FROM $wpdb->flaggallery WHERE gid={$galID}");
			$thepictures = $wpdb->get_results("SELECT filename, description, alttext FROM $wpdb->flagpictures WHERE galleryid = '{$galID}' AND exclude<>1", ARRAY_A);


			if (is_array ($thepictures) && count($thepictures)){
				foreach ($thepictures as $picture) {
					$picture = array_map('stripslashes', $picture);
					$newimage = array();
					$newimage['src']   = $newimage['sc'] = $siteurl.'/'.$path.'/'.$picture['filename'];
					if ( !empty($picture['alttext']) )
						$newimage['title'] = strip_tags($picture['alttext']);
					if ( !empty($picture['description']) )
						$newimage['alt']   = strip_tags($picture['description']);
					$this->images[] = $newimage;
				}
			}
		}

		return '';
	}

}
$flagallerySitemaps = new flagallerySitemaps();
