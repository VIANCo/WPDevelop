<?php

/**
* Image METADATA PHP class for the WordPress plugin FlAGallery
*/
class flagMeta {

	/**** Image Data ****/
    var $image			=	'';		// The image object
    var $size			=	false;	// The image size
	var $exif_data 		= 	false;	// EXIF data array
	var $iptc_data 		= 	false;	// IPTC data array
	var $xmp_data  		= 	false;	// XMP data array
	/**** Filtered Data ****/
	var $exif_array 	= 	false;	// EXIF data array
	var $iptc_array 	= 	false;	// IPTC data array
	var $xmp_array  	= 	false;	// XMP data array

	/**
	 * flagMeta::flagMeta()
	 *
	 * @param $pic_id
	 * @param bool $onlyEXIF parse only exif if needed
	 * @return bool
	 */
	function flagMeta($pic_id, $onlyEXIF = false) {

		//get the path and other data about the image
		$this->image = flagdb::find_image( $pic_id );
 
 		$this->image = apply_filters( 'flag_find_image_meta', $this->image  );		
 
 		if ( !file_exists( $this->image->imagePath ) )
			return false;

 		$this->size = @getimagesize ( $this->image->imagePath , $metadata );

		if ($this->size && is_array($metadata)) {

			// get exif - data
			if ( is_callable('exif_read_data'))
			$this->exif_data = @exif_read_data($this->image->imagePath , 0, true );
 			
 			// stop here if we didn't need other meta data
 			if ($onlyEXIF)
 				return true;
 			
 			// get the iptc data - should be in APP13
 			if ( is_callable('iptcparse'))
			$this->iptc_data = @iptcparse($metadata["APP13"]);

			// get the xmp data in a XML format
			if ( is_callable('xml_parser_create'))
			$this->xmp_data = $this->extract_XMP($this->image->imagePath );
						
			return true;
		}
 		
 		return false;
	}

	/**
	 * return the saved meta data from the database
	 *
	 * @param bool|string $object (optional)
	 * @return array|mixed return either the complete array or the single object
	 */
	function get_saved_meta($object = false) {
		
		$meta = $this->image->meta_data;
		
		//check if we already import the meat data to the database
		if (!is_array($meta) || ($meta['saved'] != true))
			return false;
		
		// return one element if requested	
		if ($object)
			return $meta[$object];
		
		//removed saved parameter we don't need that to show
		unset($meta['saved']);
		
		// and remove empty tags
		foreach ($meta as $key => $value) {
			if ( empty($value) )
				unset($meta[$key]);	
		}
		
		return $meta;
	}

	/**
	 * flagMeta::get_EXIF()
	 *
	 * @param bool $object
	 * @return string structured EXIF data
	 */
	function get_EXIF($object = false) {

		if (!$this->exif_data)
			return false;
		
		if (!is_array($this->exif_array)){
			
			$meta= array();
			
			// taken from WP core
			if(isset($this->exif_data['EXIF'])){
				$exif = $this->exif_data['EXIF'];
				if (!empty($exif['FNumber']))
					$meta['aperture'] = 'F ' . round( $this->exif_frac2dec( $exif['FNumber'] ), 2 );
				if (!empty($exif['Model']))
					$meta['camera'] = trim( $exif['Model'] );
				if (!empty($exif['DateTimeDigitized']))
					$meta['created_timestamp'] = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $this->exif_date2ts($exif['DateTimeDigitized']));
				if (!empty($exif['FocalLength']))
					$meta['focal_length'] = $this->exif_frac2dec( $exif['FocalLength'] ) . __(' mm','flag');
				if (!empty($exif['ISOSpeedRatings']))
					$meta['iso'] = $exif['ISOSpeedRatings'];
				if (!empty($exif['ExposureTime'])) {
					 $meta['shutter_speed']  = $this->exif_frac2dec ($exif['ExposureTime']);
					 $meta['shutter_speed']  =($meta['shutter_speed'] > 0.0 and $meta['shutter_speed'] < 1.0) ? ( '1/' . round( 1 / $meta['shutter_speed'], -1) ) : ($meta['shutter_speed']);
					 $meta['shutter_speed'] .=  __(' sec','flag');
					}
				//Bit 0 indicates the flash firing status
				if (!empty($exif['Flash']))
					$meta['flash'] =  ( $exif['Flash'] & 1 ) ? __('Fired', 'flag') : __('Not fired',' flag');
			}

			// additional information
			if(isset($this->exif_data['IFD0'])){
				$exif = $this->exif_data['IFD0'];
				if (!empty($exif['Model']))
					$meta['camera'] = $exif['Model'];
				if (!empty($exif['Make']))
					$meta['make'] = $exif['Make'];
				if (!empty($exif['ImageDescription']))
					$meta['title'] = utf8_encode($exif['ImageDescription']);
				if (!empty($exif['Orientation']))
					$meta['Orientation'] = $exif['Orientation'];
			}

			// this is done by Windows
			if(isset($this->exif_data['WINXP'])){
				$exif = $this->exif_data['WINXP'];
				if (!empty($exif['Title']) && empty($meta['title']))
					$meta['title'] = utf8_encode($exif['Title']);
				if (!empty($exif['Author']))
					$meta['author'] = utf8_encode($exif['Author']);
				if (!empty($exif['Keywords']))
					$meta['tags'] = utf8_encode($exif['Keywords']);
				if (!empty($exif['Subject']))
					$meta['subject'] = utf8_encode($exif['Subject']);
				if (!empty($exif['Comments']))
					$meta['caption'] = utf8_encode($exif['Comments']);
			}
							
			$this->exif_array = $meta;
		}
		
		// return one element if requested	
		if ($object){
			if(!isset($this->exif_array[$object]))
				$this->exif_array[$object] = '';
			return $this->exif_array[$object];
		}
				
		return $this->exif_array;
	
	}

	// convert a fraction string to a decimal
	function exif_frac2dec($str) {
		@ list($n, $d) = explode('/', $str);
		if (!empty ($d))
			return $n / $d;
		return $str;
	}

	// convert the exif date format to a unix timestamp
	function exif_date2ts($str) {
		// seriously, who formats a date like 'YYYY:MM:DD hh:mm:ss'?
		@ list($date, $time) = explode(' ', trim($str));
		@ list($y, $m, $d) = explode(':', $date);
		return strtotime("{$y}-{$m}-{$d} {$time}");
	}

	/**
	 * flagMeta::readIPTC() - IPTC Data Information for EXIF Display
	 *
	 * @param bool $object
	 * @return string IPTC-tags
	 */
	function get_IPTC($object = false) {
		if (!$this->iptc_data)
			return false;
		if (!is_array($this->iptc_array)) {
			// --------- Set up Array Functions --------- //
			$iptcTags = array(
				"2#005" => 'title', 
				"2#007" => 'status', 
				"2#012" => 'subject', 
				"2#015" => 'category', 
				"2#025" => 'keywords', 
				"2#055" => 'created_date', 
				"2#060" => 'created_time', 
				"2#080" => 'author', 
				"2#085" => 'position', 
				"2#090" => 'city', 
				"2#092" => 'location', 
				"2#095" => 'state', 
				"2#100" => 'country_code', 
				"2#101" => 'country', 
				"2#105" => 'headline', 
				"2#110" => 'credit', 
				"2#115" => 'source', 
				"2#116" => 'copyright', 
				"2#118" => 'contact', 
				"2#120" => 'caption'
			);
			
			// var_dump($this->iptc_data);
			$meta = array();
			foreach ($iptcTags as $key => $value) {
				if (isset($this->iptc_data[$key]))
					$meta[$value] = trim(utf8_encode(implode(", ", $this->iptc_data[$key])));
			}
			$this->iptc_array = $meta;
		}
		// return one element if requested
		if ($object){
			if(!isset($this->iptc_array[$object]))
				$this->iptc_array[$object] = '';
			return $this->iptc_array[$object];
		}
		return $this->iptc_array;
	}

	/**
	* flagMeta::extract_XMP()
	* get XMP DATA  
	* code by Pekka Saarinen http://photography-on-the.net	
	*
	* @param mixed $filename
	* @return string XML data
	*/
	function extract_XMP($filename) {
		//TODO:Require a lot of memory, could be better
		ob_start();
		@ readfile($filename);
		$source = ob_get_contents();
		ob_end_clean();
		$start = strpos($source, "<x:xmpmeta");
		$end = strpos($source, "</x:xmpmeta>");
		if ((!$start === false) && (!$end === false)) {
			$lenght = $end - $start;
			$xmp_data = substr($source, $start, $lenght + 12);
			unset ($source);
			return $xmp_data;
		}
		unset ($source);
		return false;
	}

	/**
	 * flagMeta::get_XMP()
	 *
	 * @package Taken from http://php.net/manual/en/function.xml-parse-into-struct.php
	 * @param bool $object
	 * @return Array|object XML
	 */
	function get_XMP($object = false) {
		if (!$this->xmp_data)
			return false;
		if (!is_array($this->xmp_array)) {
			$parser = xml_parser_create();
			xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);		// Dont mess with my cAsE sEtTings
			xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);		// Dont bother with empty info
			xml_parse_into_struct($parser, $this->xmp_data, $values);
			xml_parser_free($parser);
			
			$xmlarray			= array();	// The XML array
			$this->xmp_array  	= array();	// The returned array
			$stack        		= array();	// tmp array used for stacking
		 	$list_array   		= array();	// tmp array for list elements
		 	$list_element 		= false;	// rdf:li indicator
			
			foreach ($values as $val) {
				if ($val['type'] == "open") {
					array_push($stack, $val['tag']);
				}
				elseif ($val['type'] == "close") {
					// reset the compared stack
					if ($list_element == false)
						array_pop($stack);
					// reset the rdf:li indicator & array
					$list_element = false;
					$list_array = array();
				}
				elseif ($val['type'] == "complete") {
					if ($val['tag'] == "rdf:li") {
						// first go one element back
						if ($list_element == false)
							array_pop($stack);
						$list_element = true;
						// do not parse empty tags
						if ( empty($val['value']) ) continue;
						// save it in our temp array
						$list_array[] = $val['value'];
						// in the case it's a list element we seralize it
						$value = implode(",", $list_array);
						$this->setArrayValue($xmlarray, $stack, $value);
					}
					else {
						array_push($stack, $val['tag']);
						// do not parse empty tags
						if ( !empty($val['value']) )
							$this->setArrayValue($xmlarray, $stack, $val['value']);
						array_pop($stack);
					}
				}
			} // foreach
			
			// cut off the useless tags
			if(isset($xmlarray['x:xmpmeta']))
				$xmlarray = $xmlarray['x:xmpmeta']['rdf:RDF']['rdf:Description'];
			
			// --------- Some values from the XMP format--------- //
			$xmpTags = array (
				'xap:CreateDate' 			=> 'created_timestamp',
				'xap:ModifyDate'  			=> 'last_modfied',
				'xap:CreatorTool' 			=> 'tool',
				'dc:format' 				=> 'format',
				'dc:title'					=> 'title',
				'dc:creator' 				=> 'author',
				'dc:subject' 				=> 'keywords',
				'dc:description' 			=> 'caption',
				'photoshop:AuthorsPosition' => 'position',
				'photoshop:City'			=> 'city',
				'photoshop:Country' 		=> 'country'
			);

			foreach ($xmpTags as $key => $value) {
				// if the kex exist
				if (isset($xmlarray[$key]) && $xmlarray[$key]) {
					switch ($key) {
						case 'xap:CreateDate' :
						case 'xap:ModifyDate' :
							$this->xmp_array[$value] = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($xmlarray[$key]));
							break;
						default :
							$this->xmp_array[$value] = $xmlarray[$key];
					}
				}
			}
		}
		// return one element if requested
		if ($object){
			if(!isset($this->xmp_array[$object]))
				$this->xmp_array[$object] = '';
			return $this->xmp_array[$object];
		}
		return $this->xmp_array;
	}

	function setArrayValue(& $array, $stack, $value) {
		if ($stack) {
			$key = array_shift($stack);
			$this->setArrayValue($array[$key], $stack, $value);
		} else {
			$array = $value;
		}
		return $array;
	}

	/**
	 * flagMeta::get_META() - return a meta value form the available list
	 *
	 * @param bool|string $object
	 * @return mixed $value
	 */
	function get_META($object = false) {
		// defined order first look into database, then XMP, IPTC and EXIF.
		if ($value = $this->get_saved_meta($object))
			return $value;
		if ($value = $this->get_XMP($object))
			return $value;
		if ($value = $this->get_IPTC($object))
			return $value;
		if ($value = $this->get_EXIF($object))
			return $value;
		// nothing found ?
		return false;
	}

	/**
	* flagMeta::i8n_name() -  localize the tag name
	*
	* @param mixed $key
	* @return string translated $key
	*/
	function i8n_name($key) {
		$tagnames = array(
		'aperture' 			=> __('Aperture','flag'),
		'credit' 			=> __('Credit','flag'),
		'camera' 			=> __('Camera','flag'),
		'caption' 			=> __('Caption','flag'),
		'created_timestamp' => __('Date/Time','flag'),
		'copyright' 		=> __('Copyright','flag'),
		'focal_length' 		=> __('Focal length','flag'),
		'iso' 				=> __('ISO','flag'),
		'shutter_speed' 	=> __('Shutter speed','flag'),
		'title' 			=> __('Title','flag'),
		'author' 			=> __('Author','flag'),
		'tags' 				=> __('Tags','flag'),
		'subject' 			=> __('Subject','flag'),
		'make' 				=> __('Make','flag'),
		'status' 			=> __('Edit Status','flag'),
		'category'			=> __('Category','flag'),
		'keywords' 			=> __('Keywords','flag'),
		'created_date' 		=> __('Date Created','flag'),
		'created_time'		=> __('Time Created','flag'),
		'position'			=> __('Author Position','flag'),
		'city'				=> __('City','flag'),
		'location'			=> __('Location','flag'),
		'state' 			=> __('Province/State','flag'),
		'country_code'		=> __('Country code','flag'),
		'country'			=> __('Country','flag'),
		'headline' 			=> __('Headline','flag'),
		'source'			=> __('Source','flag'),
		'contact'			=> __('Contact','flag'),
		'last_modfied'		=> __('Last modified','flag'),
		'tool'				=> __('Program tool','flag'),
		'format'			=> __('Format','flag'),
		'width'				=> __('Image Width','flag'),
		'height'			=> __('Image Height','flag'),
		'flash'				=> __('Flash','flag')
		);
		
		if (isset($tagnames[$key])) $key = $tagnames[$key];
		
		return($key);
	}

	function get_date_time() {
		// get exif - data
		if ($this->exif_data) {
			$date_time = isset($this->exif_data['EXIF']['DateTimeDigitized'])? $this->exif_data['EXIF']['DateTimeDigitized'] : null;
			// if we didn't get the correct exif value we take filetime
			if ($date_time == null)
				$date_time = $this->exif_data['FILE']['FileDateTime'];
			else
				$date_time = $this->exif_date2ts($date_time);
		}
		else {
			// if no other date available, get the filetime
			$date_time = @ filectime($this->image->imagePath);
		}
		// Return the MySQL format
		$date_time = date('Y-m-d H:i:s', $date_time);
		return $date_time;
	}

	/**
	 * This function return the most common metadata, via a filter we can add more
	 * Reason : GD manipulation removes that options
	 * 
	 * @return mixed
	 */
	function get_common_meta() {

		$meta = array(
			'aperture' => 0,
			'credit' => '',
			'camera' => '',
			'caption' => '',
			'created_timestamp' => 0,
			'copyright' => '',
			'focal_length' => 0,
			'iso' => 0,
			'shutter_speed' => 0,
			'flash' => 0,
			'title' => '',
			'keywords' => ''
		);
				
		$meta = apply_filters( 'flag_read_image_metadata', $meta  );
		
		// meta should be still an array
		if ( !is_array($meta) )
			return false;
		
		foreach ($meta as $key => $value) {
			$meta[$key] = $this->get_META($key);			
		}
		
		//let's add now the size of the image 
		$meta['width']  = $this->size[0];
		$meta['height'] = $this->size[1];
		
		return $meta;		
	}

}
?>