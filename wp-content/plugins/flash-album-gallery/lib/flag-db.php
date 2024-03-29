<?php
if ( !class_exists('flagdb') ) :
/**
 * FlAGallery Database Class
 * 
 */
class flagdb {
	
	/**
	 * Holds the list of all galleries
	 *
     * @access public
     * @var object|array
	 */
	var $galleries = false;
	
    /**
     * Holds the list of all images
     *
     * @access public
     * @var object|array
     */
    var $images = false;

    /**
     * Holds the list of all comments
     *
     * @access public
     * @var object|array
     */
    var $comments = false;

	var $paged;

	/**
	 * PHP4 compatibility layer for calling the PHP5 constructor.
	 * 
	 */
	function flagdb() {
		return $this->__construct();
	}

	/**
	 * Init the Database Abstraction layer for FlAGallery
	 * 
	 */	
	function __construct() {
		global $wpdb;
		
		$this->galleries = array();
        $this->images    = array();
        $this->comments  = array();
		
		register_shutdown_function(array(&$this, "__destruct"));
		
	}
	
	/**
	 * PHP5 style destructor and will run when database object is destroyed.
	 *
	 * @return bool Always true
	 */
	function __destruct() {
		return true;
	}

	/**
	 * Get all the galleries
	 *
	 * @param string $order_by
	 * @param string $order_dir
	 * @param bool $counter (optional) Select true  when you need to count the images
	 * @param int $limit number of paged galleries, 0 shows all galleries
	 * @param int $start the start index for paged galleries
	 * @param bool|int $exclude
	 * @param bool|int $draft
	 * @return array $galleries
	 */
  function find_all_galleries($order_by = '', $order_dir = '', $counter = false, $limit = 0, $start = 0, $exclude = 0, $draft = false) {
		/** @var $wpdb wpdb */
		global $wpdb;

		$flag_options = get_option('flag_options');
		if(empty($order_by)){ $order_by = $flag_options['albSort']; }
		if(empty($order_dir)){ $order_dir = $flag_options['albSortDir']; }
		$exclude_clause = ($exclude) ? ' AND exclude<>1 ' : '';
    $draft_clause = ($draft || (get_option( 'flag_db_version' ) < 2.75)) ? '' : 'WHERE status=0';
		$order_dir = ( $order_dir == 'DESC') ? 'DESC' : 'ASC';
		if( !in_array($order_by, array('title','rand')) ) {
			$order_by = 'gid';
		}
		if( $order_by == 'rand') $order_by = 'RAND()';
		$limit_by  = ( $limit > 0 ) ? 'LIMIT ' . intval($start) . ',' . intval($limit) : '';
		$this->galleries = $wpdb->get_results( "SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->flaggallery {$draft_clause} ORDER BY {$order_by} {$order_dir} {$limit_by}", OBJECT_K );
		
		// Count the number of galleries and calculate the pagination
		if ($limit > 0) {
			$this->paged['total_objects'] = intval ( $wpdb->get_var( "SELECT FOUND_ROWS()" ) );
			$this->paged['objects_per_page'] = count( $this->galleries );
			$this->paged['max_objects_per_page'] = ( $limit > 0 ) ? ceil( $this->paged['total_objects'] / intval($limit)) : 1;
		}
		
		if ( !$this->galleries )
			return array();
		
        // get the galleries information    
        foreach ($this->galleries as $key => $value) {
            $galleriesID[] = intval($key);
            // init the counter values
            $this->galleries[$key]->counter = 0;
            wp_cache_add($key, $this->galleries[$key], 'flag_gallery');      
        }

		// if we didn't need to count the images then stop here
		if ( !$counter )
			return $this->galleries;
		
		// get the counter values 	
		$picturesCounter = $wpdb->get_results('SELECT galleryid, COUNT(*) as counter FROM '.$wpdb->flagpictures.' WHERE galleryid IN (\''.implode('\',\'', $galleriesID).'\') ' . $exclude_clause . ' GROUP BY galleryid', OBJECT_K);

		if ( !$picturesCounter )
			return $this->galleries;
		
		// add the counter to the gallery objekt	
 		foreach ($picturesCounter as $key => $value) {
			$this->galleries[$value->galleryid]->counter = $value->counter;
            wp_cache_add($value->galleryid, $this->galleries[$value->galleryid], 'flag_gallery');
		}
		
		return $this->galleries;
	}

	/**
	 * Get all the Albums
	 * 
	 * @param string $order_by
	 * @param string $order_dir
	 * @return object $albums
	 */
	function find_all_albums($order_by = 'id', $order_dir = 'DESC') {
		/** @var $wpdb wpdb */
		global $wpdb;

		if(!in_array($order_by, array('id','name'))){
			$order_by = 'id';
		}
		$order_dir = ( $order_dir == 'DESC') ? 'DESC' : 'ASC';
		$albums = $wpdb->get_results( "SELECT * FROM $wpdb->flagalbum ORDER BY {$order_by} {$order_dir}", OBJECT_K );
		return $albums;
	}

	/**
	 * Get all galleries from Album
	 *
	 * @param $id
	 * @return object $albums
	 */
	function get_album($id) {
		/** @var $wpdb wpdb */
		global $wpdb;
		$id = esc_sql($id);
		$albums = $wpdb->get_var( "SELECT categories FROM $wpdb->flagalbum WHERE id = '{$id}'" );
		return $albums;
	}
	
	/**
	 * Get a gallery given its ID
	 * 
	 * @param int|string $id or $name
	 * @return object flagGallery (null if not found)
	 */
	static function find_gallery( $id ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		
        if( is_numeric($id) ) {
            
            if ( $gallery = wp_cache_get($id, 'flag_gallery') )
                return $gallery;
            
			$gallery = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->flaggallery WHERE gid = %d", $id ) );

        } else
			$gallery = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->flaggallery WHERE name = %s", $id ) );
		
		// Build the object from the query result
        if ($gallery) {
            $gallery->abspath = WINABSPATH . $gallery->path;
            wp_cache_add($id, $gallery, 'flag_gallery');
            
            return $gallery;            
        } else 
			return false;
	}

	/**
	 * This function return all information about the gallery and the images inside
	 *
	 * @param int|string $id or $name
	 * @param string $order_by
	 * @param string $order_dir (ASC |DESC)
	 * @param bool|int $exclude
	 * @param int $limit number of paged galleries, 0 shows all galleries
	 * @param int $start the start index for paged galleries
	 * @return array containing the flagImage objects representing the images in the gallery.
	 */
    function get_gallery($id, $order_by = 'sortorder', $order_dir = 'ASC', $exclude = 0, $limit = 0, $start = 0) {

		/** @var $wpdb wpdb */
		global $wpdb;

		// init the gallery as empty array
		$gallery = array();
		
        // Check for the exclude setting
        $exclude_clause = ($exclude) ? ' AND tt.exclude<>1 ' : '';

		// Say no to any other value
		$order_dir = ( $order_dir == 'DESC') ? 'DESC' : 'ASC';
		if(in_array($order_by, array('sortorder','pid','filename','alttext','imagedate','hitcounter','total_votes','rand()'))){
			$order_by= 'tt.'.$order_by;
		} else {
			$order_by  = 'tt.sortorder';
		}
		if($order_by == 'tt.rand()'){
			$order_by = 'rand()';
			$order_dir = '';
		}
		
		// Should we limit this query ?
		$limit_by  = ( $limit > 0 ) ? 'LIMIT ' . intval($start) . ',' . intval($limit) : '';
		
		// Query database
		if( is_numeric($id) )
			$result = $wpdb->get_results( $wpdb->prepare( "SELECT SQL_CALC_FOUND_ROWS tt.*, t.* FROM $wpdb->flaggallery AS t INNER JOIN $wpdb->flagpictures AS tt ON t.gid = tt.galleryid WHERE t.gid = %d {$exclude_clause} ORDER BY {$order_by} {$order_dir} {$limit_by}", $id ), OBJECT_K );
		else
			$result = $wpdb->get_results( $wpdb->prepare( "SELECT SQL_CALC_FOUND_ROWS tt.*, t.* FROM $wpdb->flaggallery AS t INNER JOIN $wpdb->flagpictures AS tt ON t.gid = tt.galleryid WHERE t.name = %s {$exclude_clause} ORDER BY {$order_by} {$order_dir} {$limit_by}", $id ), OBJECT_K );

        // Count the number of images and calculate the pagination
        if ($limit > 0) {
            $this->paged['total_objects'] = intval ( $wpdb->get_var( "SELECT FOUND_ROWS()" ) );
            $this->paged['objects_per_page'] = max ( count( $result ), $limit );
            $this->paged['max_objects_per_page'] = ( $limit > 0 ) ? ceil( $this->paged['total_objects'] / intval($limit)) : 1;
        }
        
		// Build the object
		if ($result) {
				
			// Now added all image data
			foreach ($result as $key => $value)
				$gallery[$key] = new flagImage( $value );
		}
		
        // Could not add to cache, the structure is different to find_gallery() cache_add, need rework
        //wp_cache_add($id, $gallery, 'flag_gallery');

		return $gallery;		
	}

	/**
	 * This function return all information about the gallery and the images inside
	 *
	 * @param int|string $id or $name
	 * @param string $order_by
	 * @param string $order_dir
	 * @param bool|int $exclude
	 * @return array containing the flagImage objects representing the images in the gallery.
	 */
  static function get_ids_from_gallery($id, $order_by = 'sortorder', $order_dir = 'ASC', $exclude = 0) {

		/** @var $wpdb wpdb */
		global $wpdb;
		
        // Check for the exclude setting
        $exclude_clause = ($exclude) ? ' AND tt.exclude<>1 ' : '';
        
		// Say no to any other value
		$order_dir = ( $order_dir == 'DESC') ? 'DESC' : 'ASC';		
		if(in_array($order_by, array('sortorder','pid','filename','alttext','imagedate','hitcounter','total_votes','rand()'))){
			$order_by= 'tt.'.$order_by;
		} else {
			$order_by  = 'tt.sortorder';
		}
		if($order_by == 'tt.rand()'){
			$order_by = 'rand()';
			$order_dir = '';
		}

		// Query database
		if( is_numeric($id) )
			$result = $wpdb->get_col( $wpdb->prepare( "SELECT tt.pid FROM $wpdb->flaggallery AS t INNER JOIN $wpdb->flagpictures AS tt ON t.gid = tt.galleryid WHERE t.gid = %d $exclude_clause ORDER BY $order_by $order_dir", $id ) );
		else
			$result = $wpdb->get_col( $wpdb->prepare( "SELECT tt.pid FROM $wpdb->flaggallery AS t INNER JOIN $wpdb->flagpictures AS tt ON t.gid = tt.galleryid WHERE t.name = %s $exclude_clause ORDER BY $order_by $order_dir", $id ) );

		return $result;		
	}	
	/**
	 * Delete a gallery AND all the pictures associated to this gallery!
	 * 
	 * @gid The gallery ID
	 */
	function delete_gallery($gid) {
		/** @var $wpdb wpdb */
		global $wpdb;
				
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->flagpictures WHERE galleryid = %d", $gid) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->flaggallery WHERE gid = %d", $gid) );
        
        wp_cache_delete($gid, 'flag_gallery');

		return true;
	}

	/**
	 * Insert an image in the database
	 *
	 * @param $gid
	 * @param $filename
	 * @param $alttext
	 * @param $desc
	 * @param int $exclude
	 * @return int the ID of the inserted image
	 */
	static function insert_image($gid, $filename, $alttext, $desc, $exclude = 0) {
		/** @var $wpdb wpdb */
		global $wpdb;
		
		$result = $wpdb->query( $wpdb->prepare("INSERT INTO {$wpdb->flagpictures} (galleryid, filename, description, alttext, exclude) VALUES ('%d','%s','%s','%s','%d')", $gid, $filename, $desc, $alttext, $exclude));
		$pid = (int) $wpdb->insert_id;
        wp_cache_delete($gid, 'flag_gallery');
		
		return $pid;
	}

	/**
	 * flagdb::update_image() - Insert an image in the database
	 *
	 * @param int $pid   id of the image
	 * @param bool $galleryid
	 * @param bool $filename
	 * @param bool $description
	 * @param bool $alttext
	 * @param int $exclude
	 * @param bool $sortorder
	 * @return bool result of the ID of the inserted image
	 */
	static function update_image($pid, $galleryid = false, $filename = false, $description = false, $alttext = false, $exclude = 0, $sortorder = false) {

		/** @var $wpdb wpdb */
		global $wpdb;
		
		$sql = array();
		$pid = (int) $pid;
		
		$update = array(
		    'galleryid'   => $galleryid,
		    'filename' 	  => $filename,
		    'description' => $description,
		    'alttext' 	  => $alttext,
            'exclude'     => $exclude,
			'sortorder'   => $sortorder);
		
		// create the sql parameter "name = value"
		foreach ($update as $key => $value)
			if ($value)
				$sql[] = $key . " = '" . esc_sql($value) . "'";
		
		// create the final string
		$sql = implode(', ', $sql);
		
		$result = false;
		if ( !empty($sql) && $pid != 0)
			$result = $wpdb->query( "UPDATE $wpdb->flagpictures SET $sql WHERE pid = $pid" );

        wp_cache_delete($pid, 'flag_image'); 

		return $result;
	}
	
	/**
	 * Get an image given its ID
	 * 
	 * @param int $id The image ID
	 * @return object A flagImage object representing the image (false if not found)
	 */
	static function find_image( $id ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		
        if ( $image = wp_cache_get($id, 'flag_image') )
            return $image;
        
		// Query database
		$result = $wpdb->get_row( $wpdb->prepare( "SELECT tt.*, t.* FROM $wpdb->flaggallery AS t INNER JOIN $wpdb->flagpictures AS tt ON t.gid = tt.galleryid WHERE tt.pid = %d ", $id ) );
		
		// Build the object from the query result
		if ($result) {
			$image = new flagImage($result);
			return $image;
		} 
		
		return false;
	}

	/**
	 * Get images given a list of IDs
	 *
	 * @param $pids array of picture_ids
	 * @param int $exclude
	 * @param string $order
	 * @return array of flagImage objects representing the images
	 */
    static function find_images_in_list( $pids, $exclude = 0, $order = 'ASC' ) {
		/** @var $wpdb wpdb */
		global $wpdb;
	
		$result = array();
		
        // Check for the exclude setting
        $exclude_clause = ($exclude) ? ' AND t.exclude <> 1 ' : '';

		// Check for the order setting
		$order_clause = ($order == 'RAND') ? 'ORDER BY rand() ' : ' ORDER BY t.pid ASC' ;
		
		if ( is_array($pids) ) {
			$pids = array_filter($pids, 'intval');
			$id_list = "'" . implode("', '", $pids) . "'";
			
			// Save Query database
			$images = $wpdb->get_results("SELECT t.*, tt.* FROM $wpdb->flagpictures AS t INNER JOIN $wpdb->flaggallery AS tt ON t.galleryid = tt.gid WHERE t.pid IN ($id_list) $exclude_clause $order_clause", OBJECT_K);
	
			// Build the image objects from the query result
			if ($images) {	
				foreach ($images as $key => $image)
					$result[$key] = new flagImage( $image );
			} 
		}
		return $result;
	}

	/**
	 * Add an image to the database
	 *
	 * @param bool $id
	 * @param bool $filename
	 * @param string $description
	 * @param string $alttext
	 * @param bool $meta_data
	 * @param int $post_id
	 * @param string $imagedate
	 * @param int $exclude
	 * @param int $sortorder
	 * @return bool result of the ID of the inserted image
	 */
    function add_image( $id = false, $filename = false, $description = '', $alttext = '', $meta_data = false, $post_id = 0, $imagedate = '0000-00-00 00:00:00', $exclude = 0, $sortorder = 0  ) {
		/** @var $wpdb wpdb */
		global $wpdb;
                
		if ( is_array($meta_data) )
			$meta_data = serialize($meta_data);
			
		// Add the image
		if ( false === $wpdb->query( $wpdb->prepare("INSERT INTO $wpdb->flagpictures (galleryid, filename, description, alttext, meta_data, post_id, imagedate, exclude, sortorder) 
													 VALUES (%d, %s, %s, %s, %s, %d, %s, %d, %d)", $id, $filename, $description, $alttext, $meta_data, $post_id, $imagedate, $exclude, $sortorder ) ) ) {
			return false;
		}
		
		$imageID = (int) $wpdb->insert_id;
			
		// Remove from cache the galley, needs to be rebuild now
	    wp_cache_delete( $id, 'flag_gallery'); 
		//and give me the new id
		
		return $imageID;
    }

	/**
	* Delete an image entry from the database
	*/
	static function delete_image($pid) {
		/** @var $wpdb wpdb */
		global $wpdb;
		
		// Delete the image row
		$result = $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->flagpictures WHERE pid = %d", $pid) );
		
        // Remove from cache
        wp_cache_delete( $pid, 'flag_image');
        
        return $result;
	}

	/**
	 * Get the last images registered in the database with a maximum number of $limit results
	 *
	 * @param integer $page
	 * @param integer $limit
	 * @param int $exclude
	 * @param int $galleryId Only look for images with this gallery id, or in all galleries if id is 0
	 * @param string $orderby
	 * @param string $orderby is one of "id" (default, order by pid), "date" (order by exif date), sort (order by user sort order)
	 * @return array
	 */
    function find_last_images($page = 0, $limit = 30, $exclude = 0, $galleryId = 0, $orderby = "id") {
		/** @var $wpdb wpdb */
		global $wpdb;
		
        // Check for the exclude setting
        $exclude_clause = ($exclude) ? ' AND exclude<>1 ' : '';
        
		$offset = (int) $page * $limit;
		
        $galleryId = (int) $galleryId;
        $gallery_clause = ($galleryId === 0) ? '' : ' AND galleryid = ' . $galleryId . ' ';

        // default order by pid
        $order = 'pid DESC';
        switch ($orderby) {
            case 'date':
                $order = 'imagedate DESC';
                break;
            case 'sort':
                $order = 'sortorder ASC';
                break;
        }

		$result = array();
		$gallery_cache = array();
		
		// Query database
		$images = $wpdb->get_results("SELECT * FROM $wpdb->flagpictures WHERE 1=1 $exclude_clause $gallery_clause ORDER BY $order LIMIT $offset, $limit");
		
		// Build the object from the query result
		if ($images) {	
			foreach ($images as $key => $image) {
				
				// cache a gallery , so we didn't need to lookup twice
				if (!array_key_exists($image->galleryid, $gallery_cache))
					$gallery_cache[$image->galleryid] = flagdb::find_gallery($image->galleryid);
				
				// Join gallery information with picture information	
				foreach ($gallery_cache[$image->galleryid] as $index => $value)
					$image->$index = $value;
				
				// Now get the complete image data
				$result[$key] = new flagImage( $image );
			}
		}
		
		return $result;
	}

	/**
	 * flagdb::get_random_images() - Get an random image from one ore more gally
	 *
	 * @param integer $number of images
	 * @param integer $galleryID optional a Gallery
	 * @param int $exclude
	 * @return object flagImage representing the image (null if not found)
	 */
	function get_random_images($number = 1, $galleryID = 0, $exclude = 0) {
		/** @var $wpdb wpdb */
		global $wpdb;
		
        // Check for the exclude setting
        $exclude_clause = ($exclude) ? ' AND tt.exclude != 1 ' : '';
				$draft_clause = (get_option( 'flag_db_version' ) < 2.75) ? '' : 'AND t.status == 0';

		$number = (int) $number;
		$galleryID = (int) $galleryID;
		$images = array();
		
		// Query database
		if ($galleryID == 0)
			$result = $wpdb->get_results("SELECT t.*, tt.* FROM {$wpdb->flaggallery} AS t INNER JOIN {$wpdb->flagpictures} AS tt ON t.gid = tt.galleryid WHERE 1=1 {$draft_clause} {$exclude_clause} ORDER by rand() limit {$number}");
		else
			$result = $wpdb->get_results("SELECT t.*, tt.* FROM {$wpdb->flaggallery} AS t INNER JOIN {$wpdb->flagpictures} AS tt ON t.gid = tt.galleryid WHERE t.gid = {$galleryID} {$exclude_clause} ORDER by rand() limit {$number}");
		
		// Return the object from the query result
		if ($result) {
			foreach ($result as $image) {
				$images[] = new flagImage( $image );
			}
			return $images;
		} 
			
		return null;
	}
 
    /**
     * search for images and return the result
     * 
     * @since 0.40
     * @param string $request
     * @return Array Result of the request
     */
    function search_for_images( $request ) {
		/** @var $wpdb wpdb */
		global $wpdb;
        
        // If a search pattern is specified, load the posts that match
        if ( !empty($request) ) {
            // added slashes screw with quote grouping when done early, so done later
            $request = stripslashes($request);
            
            // split the words it a array if seperated by a space or comma
            preg_match_all('/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $request, $matches);
            $search_terms = array_map(create_function('$a', 'return trim($a, "\\"\'\\n\\r ");'), $matches[0]);
            
            $n = '%';
            $search = '';
            $searchand = '';

            foreach( (array) $search_terms as $term) {
                $term = addslashes_gpc($term);
                $search .= "{$searchand}((tt.description LIKE '{$n}{$term}{$n}') OR (tt.alttext LIKE '{$n}{$term}{$n}') OR (tt.filename LIKE '{$n}{$term}{$n}'))";
                $searchand = ' AND ';
            }
            
            $term = esc_sql($request);
            if (count($search_terms) > 1 && $search_terms[0] != $request )
                $search .= " OR (tt.description LIKE '{$n}{$term}{$n}') OR (tt.alttext LIKE '{$n}{$term}{$n}') OR (tt.filename LIKE '{$n}{$term}{$n}')";

            if ( !empty($search) )
                $search = " AND ({$search}) ";
        }
        
        // build the final query
        $query = "SELECT t.*, tt.* FROM $wpdb->flaggallery AS t INNER JOIN $wpdb->flagpictures AS tt ON t.gid = tt.galleryid WHERE 1=1 $search ORDER BY tt.pid ASC ";
        $result = $wpdb->get_results($query);

        // Return the object from the query result
        if ($result) {
			$images = array();
            foreach ($result as $image) {
                $images[] = new flagImage( $image );
            }
            return $images;
        } 

        return null;
    }

	/**
	 * search for a filename
	 *
	 * @since 0.40
	 * @param string $filename
	 * @param bool|int $galleryID
	 * @return Array Result of the request
	 */
    function search_for_file( $filename, $galleryID = false ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		$where_clause = '';
		// If a search pattern is specified, load the posts that match
        if ( !empty($filename) ) {
            // added slashes screw with quote grouping when done early, so done later
            $term = esc_sql($filename);
            
            if ( is_numeric($galleryID) ) {
            	$id = (int) $galleryID;
            	$where_clause = " AND tt.galleryid = {$id}";
            }
        }
        
        // build the final query
        $query = "SELECT t.*, tt.* FROM $wpdb->flaggallery AS t INNER JOIN $wpdb->flagpictures AS tt ON t.gid = tt.galleryid WHERE tt.filename = '{$term}' {$where_clause} ORDER BY tt.pid ASC ";
		$result = $wpdb->get_row($query);

        // Return the object from the query result
        if ($result) {
        	$image = new flagImage( $result );
            return $image;
        } 

        return null;
    }

	/**
	 * Update or add meta data for an image
	 *
	 * @param int $id The image ID
	 * @param $new_values
	 * @internal param array $values An array with existing or new values
	 * @return bool result of query
	 */
    static function update_image_meta( $id, $new_values ) {
		/** @var $wpdb wpdb */
		global $wpdb;
        
        // Query database for existing values
        // Use cache object
        $old_values = $wpdb->get_var( $wpdb->prepare( "SELECT meta_data FROM $wpdb->flagpictures WHERE pid = %d ", $id ) );
        $old_values = unserialize( $old_values );

        $meta = array_merge( (array)$old_values, (array)$new_values );
		
        $result = $wpdb->query( $wpdb->prepare("UPDATE $wpdb->flagpictures SET meta_data = %s WHERE pid = %d", serialize($meta), $id) );
        
        wp_cache_delete($id, 'flag_image');
        
        return $result;
    }

		function update_picture($args) {
			global $wpdb;

			$pid =intval($args['pid']);
			$alttext = esc_sql($args['alttext']);
			$desc = esc_sql($args['description']);
			$link = esc_sql($args['link']);
			$sortorder = intval($args['sortorder']);
			$exclude = intval($args['exclude']);

			$result = $wpdb->query( "UPDATE $wpdb->flagpictures SET alttext = '$alttext', description = '$desc', link = '$link', sortorder = $sortorder, exclude = $exclude WHERE pid = $pid");

			return $result;
		}

}
endif;

if ( ! isset($GLOBALS['flagdb']) ) {
    /**
     * Initate the FlAGallery Database Object, for later cache reasons
     * @global object $flagdb Creates a new flagdb object
     */
    unset($GLOBALS['flagdb']);
    $GLOBALS['flagdb'] = new flagdb();
}
?>