<?php
/*
Plugin Name: R.W. Elephant Inventory Gallery
Plugin URI: http://www.rwelephant.com/
Description: Gallery displays R.W. Elephant rental inventory on your website.
Version: 1.0
Author: R.W. Elephant
Author URI: http://www.rwelephant.com/
License: GPL2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

$rwe_gallery_data = array();

function rwe_get_data($key) {
	global $rwe_gallery_data;
	return (isset($rwe_gallery_data[$key])) ? $rwe_gallery_data[$key] : '';
}

function rwe_set_data($key,$data) {
	global $rwe_gallery_data;
	$rwe_gallery_data[$key] = $data;
}

class RWEgallery {

	function rwe_gallery_core() {

		// Program defaults
		$default_options = array(
			'rwelephant_id' => 'coolrentals',
			'api_key' => '',
			'template' => 'greybox',
			'gallery_name' => 'Gallery',
			'category_thumbnail_size' => 100,
			'item_thumbnail_size' => 100,
			'facebook' => false,
			'twitter' => false,
			'pinterest' => false,
			'google' => false,
			'page_id' => 2,
			'title_format' => "[title] [separator] [gallery_name] [separator] "
		);

		// Read stored options from options table
		$defined_options = get_option('rwe_gallery_options');

		// Merge set options with defaults
		$options = wp_parse_args( $defined_options, $default_options );

		global $wp_query;

		// Get current page ID
		$location = $wp_query->post->ID;

		rwe_set_data('location',$location);

		// If current page is the gallery location
		if ( is_page($location) && $location == $options['page_id']) {

			// Store options for use in other functions

			rwe_set_data('gallery_name',$options['gallery_name']);
			rwe_set_data('api_key',$options['api_key']);
			rwe_set_data('title_format',$options['title_format']);

			// Define API calls

			$api_base = 'http://' . $options['rwelephant_id'] . '.rwelephant.com/api/public_api?';

			$category_list_url = $api_base . 'action=list_inventory_types';
			$tag_list_url = $api_base . 'action=list_tags';
			$items_in_category_url = $api_base . 'action=list_items_for_type&inclusion_mask=main_hash&inventory_type_id=';
			$items_by_tag_url = $api_base . 'action=list_items_for_tag&inclusion_mask=main_hash&inventory_tag_type_id=';
			$api_search_url = $api_base . 'action=list_items_for_search&inclusion_mask=main_hash&search_term=';
			$item_detail_url = $api_base . 'action=item_info&inventory_item_id=';
			$item_tags_url = $api_base . 'action=list_tags_for_item&inventory_item_id=';

			// Register the style sheet for chosen template

			wp_register_style( 'rwe-stylesheet', plugins_url('templates/'.$options['template'].'/style.css', __FILE__) );

			// Templates

			//   Main gallery page listing categories, and sub template for the category list
			$categories_template_file = plugin_dir_url(__FILE__) . 'templates/' . $options['template'] . '/categories.php';

			//   Pages with lists of items: category, tag, or search
			$category_template_file = plugin_dir_url(__FILE__) . 'templates/'. $options['template'] .'/category.php';
			$tag_template_file = plugin_dir_url(__FILE__) . 'templates/'. $options['template'] .'/tag.php';
			$search_results_template_file =  plugin_dir_url(__FILE__) . 'templates/'. $options['template'] .'/search-results.php';

			//   Item detail page
			$item_detail_template_file = plugin_dir_url(__FILE__) . 'templates/'. $options['template'] .'/item-detail.php';

			//   Sub-templates used for lists of items and categories
			$category_list_template_file = plugin_dir_url(__FILE__) . 'templates/common/category-list.php';
			$item_list_template_file = plugin_dir_url(__FILE__) . 'templates/common/item-list.php';

			//    The search form
			$search_form_template_file = plugin_dir_url(__FILE__) . 'templates/common/search-form.php';

			// Parse search form for use in other templates

			$search_form_template = file_get_contents($search_form_template_file);
			$search_form_placeholders = array(
				'[gallery_name]' => $options['gallery_name'],
				'[gallery_url]' => get_permalink($options['page_id'])
			);
			$search_form = RWEgallery::parse_template( $search_form_template, $search_form_placeholders );

			// Images

			rwe_set_data('no-thumbnail-image', plugin_dir_url(__FILE__) . 'images/no-thumbnail-image.png');
			rwe_set_data('no-item-image', plugin_dir_url(__FILE__) . 'images/no-item-image.png');

			// Request var names mapped to permalink names

			$request_vars_list = array ('category'=>'rwecat', 'tag'=>'rwetag', 'item'=>'rweitem', 'search'=>'rwe-search');
			rwe_set_data('request_vars_list',$request_vars_list); // store for use in other functions

			// Process the URL 

			if (get_query_var('rwegallery')) {

				// permalink - format: type/##

				$rwegallery_request_url = get_query_var('rwegallery');

				$page_request = array();
				$url = explode('/',$rwegallery_request_url);
				$i = 0;
				while( $i < count($url) ) {
					$page_request[$url[$i]] = intval($url[$i+1]);
					$i+=2;
				}
			}

			// process get requests:

				$get_request = array();
				foreach ($request_vars_list as $key=>$var) {
					if ($var=='rwe-search')
						$get_request['search'] = get_query_var('rwe-search');
					else
						$get_request["$key"] = intval(get_query_var("$var"));
				}
				// remove null requests
				$get_request = array_filter($get_request);


			// merge URL (permalink) with GET request (non-permalink)

			if ( is_array( $page_request ) && is_array( $get_request ) )
				$page_request = array_merge($page_request, $get_request);
			elseif ( is_array( $get_request))
					$page_request = $get_request;
			else {
				// nothing 
			}

			// Process the URL. Can contain multiple requests -- order matters! 

			if ($page_request['search']) {
				$search_terms = $page_request['search'];
			}
			elseif ($page_request['item']) {
				$item_id = $page_request['item'];
			}
			elseif ($page_request['category']) {
				$category_id = $page_request['category'];
			}
			elseif ($page_request['tag']) {
				$tag_id = $page_request['tag'];
			}
			else {
				// nothing else, show top level
				$location = 'main';
			}

			if ($search_terms) {

				$search_words = htmlspecialchars($search_terms, ENT_QUOTES); // make search terms safe for display

				$search_result = RWEgallery::rwe_api($api_search_url, urlencode($search_terms) );

				if ($search_result) {

					// variables to extract
					$search_variables_to_extract = array ('name', 'inventory_item_id', 'description', 'inventory_type_name',
						'quantity', 'rental_price', 'frac_length', 'frac_width', 'frac_height');

					$search_items = array();

					foreach ($search_result as $key=>$search_item) {
						foreach ($search_variables_to_extract as $item_variable) {
							if ( is_numeric( $search_item["$item_variable"] ))
								$search_items["$key"]["$item_variable"] =  $search_item["$item_variable"];
							else
								$search_items["$key"]["$item_variable"] =  htmlspecialchars( $search_item["$item_variable"] , ENT_QUOTES);
						}
						$search_items["$key"]['dimensions'] = RWEgallery::format_item_dimensions( $search_item['frac_length'],
									 $search_item['frac_width'], $search_item['frac_height'] );
						$search_items["$key"]['images'] = RWEgallery::process_item_in_category_image_links( $search_item['image_links'], $search_item['name'], $options['category_thumbnail_size'], $options['rwelephant_id'] );
					}

					$item_list_template = file_get_contents( $item_list_template_file );

					foreach ($search_items as $item) {
				
						$item_list_placeholders = array(
							'[item_name]' => $item['name'],
							'[item_url]' => RWEgallery::rwe_link('item',$item['inventory_item_id']),
							'[item_quantity]' => $item['quantity'],
							'[item_price]' => $item['rental_price'],
							'[item_dimensions]' => $item['dimensions'],
							'[item_photo]' => $item['images']['photo'],
							'[item_photo_url]' => $item['images']['photo_url']
						);

						// Parse item list sub-template and store content for use in main template
						$search_items_content .= RWEgallery::parse_template( $item_list_template, $item_list_placeholders );
					}

				}

				else {
					// empty result or error

					$error = '<p class="error">Nothing found.</p>';
				}

				$search_results_template = file_get_contents( $search_results_template_file );

				$url_connector = (get_option('permalink_structure'))? '?':'&'; // if permalinks are enabled use '?' else '&'
				$search_url = get_permalink($options['page_id']) . $url_connector . 'rwe-search=' . urlencode($search_words);

				$search_placeholders = array(
					'[gallery_name]' => $options['gallery_name'],
					'[gallery_url]' => get_permalink($options['page_id']),
					'[search_form]' => $search_form,
					'[search_terms]' => $search_words,
					'[search_items]' => $search_items_content,
					'[category_thumbnail_size]' => $options['category_thumbnail_size'],
					'[error]' => $error,
					'[page_url]' => $search_url
				);

				rwe_set_data('content', RWEgallery::parse_template( $search_results_template, $search_placeholders ));
				rwe_set_data('page_title', 'Search: ' . $search_words);
				rwe_set_data('page_link', $search_url);
				rwe_set_data('page_heading', 'Search: ' . $search_words);

			// end search

			}

			if ($location == 'main') {

				$category_list_result = RWEgallery::rwe_api($category_list_url);

				if ($category_list_result) {

					// variables to extract
					$category_variables_to_extract = array ('inventory_type_name', 'inventory_type_id');

					$category_list = array();

					foreach ($category_list_result as $key=>$category) {
						foreach ($category_variables_to_extract as $item_variable) {
							if ( is_numeric( $category["$item_variable"] ))
								$category_list["$key"]["$item_variable"] =  $category["$item_variable"];
							else
								$category_list["$key"]["$item_variable"] =  htmlspecialchars( $category["$item_variable"] , ENT_QUOTES);
	
							$category_image_links[0] = array ( 'photo_hash' => $category["photo_hash"] );
							$category_list["$key"]['images'] = RWEgallery::process_item_in_category_image_links( $category_image_links, $category_list["$key"]['inventory_type_name'], $options['category_thumbnail_size'], $options['rwelephant_id'] );
						}
					}

					$category_list_template = file_get_contents( $category_list_template_file );
				
					foreach ($category_list as $category) {
				
						$category_list_placeholders = array(
							'[category_name]' => $category['inventory_type_name'],
							'[category_url]' => RWEgallery::rwe_link('category',$category['inventory_type_id']),
							'[category_thumbnail]' => $category['images']['photo'],
							'[category_thumbnail_url]' => $category['images']['photo_url']
						);

						// Parse category list sub-template and store content for use in main template
						$category_list_content .= RWEgallery::parse_template( $category_list_template, $category_list_placeholders );
					}
				}

				else {
					// empty result or error

					$error = '<p class="error">No categories found.</p>';
				}


				$categories_template = file_get_contents( $categories_template_file );

				$categories_placeholders = array(
					'[gallery_name]' => $options['gallery_name'],
					'[gallery_url]' => get_permalink($options['page_id']),
					'[search_form]' => $search_form,
					'[category_list]' => $category_list_content,
					'[category_thumbnail_size]' => $options['category_thumbnail_size'],
					'[error]' => $error
				);

				rwe_set_data('content', RWEgallery::parse_template( $categories_template, $categories_placeholders ));
				// rwe_set_data('page_title', null );
				rwe_set_data('page_heading', $options['gallery_name']);


			// end main

			}

			if ($tag_id) {

				// list items by tag

				$items_by_tag_result = RWEgallery::rwe_api($items_by_tag_url, $tag_id);

				if ($items_by_tag_result) {

					// variables to extract
					$tag_variables_to_extract = array ('name', 'inventory_item_id', 'description', 'inventory_type_name',
						'quantity', 'rental_price', 'frac_length', 'frac_width', 'frac_height', 'inventory_tag_name');
					$items_by_tag = array();

					foreach ($items_by_tag_result as $key=>$item_by_tag) {
						foreach ($tag_variables_to_extract as $item_variable) {
							if ( is_numeric( $item_by_tag["$item_variable"] ))
								$items_by_tag["$key"]["$item_variable"] =  $item_by_tag["$item_variable"];
							else
								$items_by_tag["$key"]["$item_variable"] =  htmlspecialchars( $item_by_tag["$item_variable"] , ENT_QUOTES);
						}
						$items_by_tag["$key"]['dimensions'] = RWEgallery::format_item_dimensions( $item_by_tag['frac_length'],
									 $item_by_tag['frac_width'], $item_by_tag['frac_height'] );
						$items_by_tag["$key"]['images'] = RWEgallery::process_item_in_category_image_links( $item_by_tag['image_links'], $item_by_tag['name'], $options['category_thumbnail_size'], $options['rwelephant_id'] );
					}

					// get tag name

					$tag_name = $items_by_tag[0]['inventory_tag_name']; // each result should have the tag name
					if(!$tag_name)
						$tag_name = 'Unknown Tag';
	
					$item_list_template = file_get_contents( $item_list_template_file );
				
					foreach ($items_by_tag as $item) {
				
						$item_list_placeholders = array(
							'[item_name]' => $item['name'],
							'[item_url]' => RWEgallery::rwe_link('item',$item['inventory_item_id']),
							'[item_quantity]' => $item['quantity'],
							'[item_price]' => $item['rental_price'],
							'[item_dimensions]' => $item['dimensions'],
							'[item_photo]' => $item['images']['photo'],
							'[item_photo_url]' => $item['images']['photo_url']
						);

						// Parse item list sub-template and store content for use in main template
						$items_by_tag_content .= RWEgallery::parse_template( $item_list_template, $item_list_placeholders );
					}

				}

				else {
					// empty response or error

					$error = 'Could not find items for tag.';

				}

				$tag_template = file_get_contents( $tag_template_file );

				$tag_url = RWEgallery::rwe_link('tag',$tag_id);

				$tag_placeholders = array(
					'[gallery_name]' => $options['gallery_name'],
					'[gallery_url]' => get_permalink($options['page_id']),
					'[search_form]' => $search_form,
					'[tag_name]' => $tag_name,
					'[tag_items]' => $items_by_tag_content,
					'[category_thumbnail_size]' => $options['category_thumbnail_size'],
					'[error]' => $error,
					'[page_url]' => $tag_url
				);

				rwe_set_data('content', RWEgallery::parse_template( $tag_template, $tag_placeholders ));
				rwe_set_data('page_title', $tag_name);
				rwe_set_data('page_heading',  $tag_name);
				rwe_set_data('page_link',  $tag_url);


			// end items by tag
			}


			if ($category_id) {

				$items_in_category_result = RWEgallery::rwe_api($items_in_category_url, $category_id);

				if ($items_in_category_result) {

					// variables to extract
					$category_variables_to_extract = array ('name', 'inventory_item_id', 'description', 'inventory_type_name',
						'quantity', 'rental_price', 'frac_length', 'frac_width', 'frac_height');

					$items_in_category = array();
					foreach ($items_in_category_result as $key=>$item_in_category) {
						foreach ($category_variables_to_extract as $item_variable) {
							if ( is_numeric( $item_in_category["$item_variable"] ))
								$items_in_category["$key"]["$item_variable"] =  $item_in_category["$item_variable"];
							else
								$items_in_category["$key"]["$item_variable"] =  htmlspecialchars( $item_in_category["$item_variable"] , ENT_QUOTES);
						}
						$items_in_category["$key"]['dimensions'] = RWEgallery::format_item_dimensions( $item_in_category['frac_length'],
									 $item_in_category['frac_width'], $item_in_category['frac_height'] );
						$items_in_category["$key"]['images'] = RWEgallery::process_item_in_category_image_links( $item_in_category['image_links'], $item_in_category['name'], $options['category_thumbnail_size'], $options['rwelephant_id'] );
					}

					if ($items_in_category[0]['inventory_type_name'])
						$category_name = $items_in_category[0]['inventory_type_name'];
					else
						$category_name = 'Empty category';

					$item_list_template = file_get_contents( $item_list_template_file );
				
					foreach ($items_in_category as $item) {
				
						$item_list_placeholders = array(
							'[item_name]' => $item['name'],
							'[item_url]' => RWEgallery::rwe_link('item',$item['inventory_item_id']),
							'[item_quantity]' => $item['quantity'],
							'[item_price]' => $item['rental_price'],
							'[item_dimensions]' => $item['dimensions'],
							'[item_photo]' => $item['images']['photo'],
							'[item_photo_url]' => $item['images']['photo_url']
						);

						// Parse item list sub-template and store content for use in main template
						$items_in_category_content .= RWEgallery::parse_template( $item_list_template, $item_list_placeholders );
					}

				}

				else {
					// empty result or error

					$error = 'Nothing found in category.';

				}

				$category_template = file_get_contents( $category_template_file );

				$category_url = RWEgallery::rwe_link('category',$category_id);

				$category_placeholders = array(
					'[gallery_name]' => $options['gallery_name'],
					'[gallery_url]' => get_permalink($options['page_id']),
					'[search_form]' => $search_form,
					'[category_name]' => $category_name,
					'[category_items]' => $items_in_category_content,
					'[category_thumbnail_size]' => $options['category_thumbnail_size'],
					'[error]' => $error,
					'[page_url]' => $category_url
				);

				rwe_set_data('content', RWEgallery::parse_template( $category_template, $category_placeholders ));
				rwe_set_data('page_title', $category_name);
				rwe_set_data('page_heading',  $category_name);
				rwe_set_data('page_link',  $category_url);


			// end items in category
			}

			if ($item_id) {

				$item_detail_result = RWEgallery::rwe_api($item_detail_url, $item_id);

				$item_url = RWEgallery::rwe_link('item',$item_id);

				if ($item_detail_result) {

					$item_detail_result = $item_detail_result[0]; // result is the first item

					// variables to extract
					$item_detail_variables_to_extract = array ('name', 'description', 'inventory_type_name',
						'inventory_type_id', 'quantity', 'rental_price', 'frac_length', 'frac_width', 'frac_height');

					// create $item array, encode characters as html entities for web safe display
					$item = array();
					foreach ($item_detail_variables_to_extract as $item_variable) {
						if ( is_numeric( $item_detail_result["$item_variable"] ))
							$item["$item_variable"] =  $item_detail_result["$item_variable"];
						else
							$item["$item_variable"] =  htmlspecialchars( $item_detail_result["$item_variable"] , ENT_QUOTES);
					}


					// get tags for item
					$item_tags_result = RWEgallery::rwe_api($item_tags_url, $item_id);
					$item['tags'] = RWEgallery::format_item_tags($item_tags_result);
	
					// format item dimensions
					$item['dimensions'] = RWEgallery::format_item_dimensions( $item['frac_length'],
								 $item['frac_width'], $item['frac_height'] );
	
					// process images -- main image and thumbnails
					$item_images = RWEgallery::process_image_links( $item_detail_result['image_links'],
								 $item['name'], $options['item_thumbnail_size'], $options['rwelephant_id'] );

					// add Open Graph meta tags

					add_action('wp_head', array('RWEgallery', 'add_meta_og_tags'), 5);

					// process selected social links

					if($options['facebook']) {
						$social_link_script .= <<<EOF
(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

EOF;
						$social_links .= '<div class="rwe-share rwe-button-facebook" style="width:77px;"><div id="fb-root"></div><div class="fb-like" data-href="'.$item_url.'" data-send="false" data-layout="button_count" data-width="100" data-show-faces="false"></div></div>';
					}
					if($options['twitter']) {
						$social_link_script .= <<<EOF
!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");

EOF;
						$social_links .= '<div class="rwe-share rwe-button-twitter" style="width:83px;"><a href="https://twitter.com/share" class="twitter-share-button" data-text="'.$item['name'].'" data-url="'.$item_url.'" rel="nofollow"></a></div>';
					}
					if($options['google']) {
						$social_link_script .= <<<EOF
window.___gcfg = {lang: '<?php echo $lang_g; ?>'};
(function() {
   var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
   po.src = 'https://apis.google.com/js/plusone.js';
   var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
})();

EOF;
						$social_links .= '<div class="rwe-share rwe-button-googleplus" style="width:56px;"><div class="g-plusone" data-size="medium" data-href="'.$item_url.'"></div></div>';
					}
					if($options['pinterest']) {
						$social_script_pinterest = '<script type="text/javascript" src="//assets.pinterest.com/js/pinit.js"></script>';
						$social_links .= '<div class="rwe-share rwe-button-pinterest" style="width: 75px;"><a href="http://pinterest.com/pin/create/button/?url='.urlencode($item_url).'&media='.urlencode($item_images['main_photo_url']).'&description='.urlencode($item['name']).': '.urlencode($item['description']).'" class="pin-it-button" count-layout="horizontal" always-show-count="1"><img border="0" src="//assets.pinterest.com/images/PinExt.png" title="Pin It" /></a></div>';
					}

					// format social links
					if ($social_links) {
						$social_links = '<div class="rwe-share-box">'.$social_links.'<div style="clear:both;"></div></div>';
					}

					// include javascript for social links
					if ($social_link_script) {
						$social_links = '<script type="text/javascript">' ."\n".'//<![CDATA['."\n" . $social_link_script . '// ]]>'."\n".'</script>'."\n"  . $social_links;
					}


				}
				else {
					//empty result or error

					$error = 'Item could not be found.';
				}

				$item_detail_template = file_get_contents( $item_detail_template_file );

				$item_detail_placeholders = array(
					'[gallery_name]' => $options['gallery_name'],
					'[gallery_url]' => get_permalink($options['page_id']),
					'[search_form]' => $search_form,
					'[item_name]' => $item['name'],
					'[item_description]' => $item['description'],
					'[item_quantity]' => $item['quantity'],
					'[item_category_name]' => $item['inventory_type_name'],
					'[item_category_url]' => RWEgallery::rwe_link('category',$item['inventory_type_id']),
					'[item_photo]' => $item_images['main_photo'],
					'[item_photo_url]' => $item_images['main_photo_url'],
					'[item_thumbnails]' => $item_images['thumbnails'],
					'[item_thumbnails_url]' => $item_images['thumbnails_url'],
					'[item_tags]' => $item['tags'],
					'[item_dimensions]' => $item['dimensions'],
					'[item_price]' => $item['rental_price'],
					'[error]' => $error,
					'[page_url]' => $item_url,
					'[social_links]' => $social_links
				);

				// Parse template and set the content
				rwe_set_data('content', RWEgallery::parse_template( $item_detail_template, $item_detail_placeholders ).$social_script_pinterest);
				rwe_set_data('page_title', $item['name']);
				rwe_set_data('page_heading', $item['name']);
				rwe_set_data('page_link',  $item_url);
				rwe_set_data('item_image', $item_images['main_photo_url']);
				rwe_set_data('item_description', $item['description']);

			// end of item request
			}

			// Add hooks
			add_filter('wp_title', array('RWEgallery', 'change_page_title'), 1, 3);
			add_filter('the_title', array('RWEgallery', 'change_page_heading'), 100, 2);
			add_filter('page_link', array('RWEgallery', 'change_page_link'), 100, 2);
			add_filter('the_content', array('RWEgallery', 'rwe_gallery_display'), 10, 2);
			add_action('wp_enqueue_scripts', array('RWEgallery', 'include_scripts'));
		}
	}

	function rwe_api($api_call, $extra=null ) {
		$api_key = rwe_get_data('api_key');
		$args = array('timeout'=>10);
		$result = wp_remote_get( $api_call . $extra . '&api_key=' . $api_key . '&callback=myJsonpCallback' , $args );
		if ( is_wp_error( $result ) ) {
			// WP_HTTP returned an error -- $error_string = $result->get_error_message();
			return;
		}
		else {
			$data = jsonp_decode($result['body']);
			if($data["response_status"]=="Error") {
				// API response contains an error.
				return;
			}
			else
				return $data;
		}
	}

	function add_meta_og_tags() {
		echo '<meta property="og:title" content="' . rwe_get_data('page_title') . '" />';
		echo '<meta property="og:type" content="website" />';
		echo '<meta property="og:url" content="' . get_permalink() . '" />';
		echo '<meta property="og:image" content="' . rwe_get_data('item_image') . '" />';
		echo '<meta property="og:description" content="' . rwe_get_data('item_description') . '" />';
	}

	function format_item_tags($tags) {
		if ( is_array( $tags )) {
			foreach($tags as $tag) {
				$tag_link = RWEgallery::rwe_link('tag',$tag['inventory_tag_type_id']);
				$tag_list .= '<li><a href="'. $tag_link . '">' . $tag['inventory_tag_name'] . '</a></li>';
			}
			return $tag_list;
		}
	}

	function include_scripts() {
		wp_enqueue_script(
			'rwe_gallery_script',
			plugins_url('/templates/common/script.js', __FILE__),
			array('jquery'),
			'1.0'
		);
		// load the stylesheet previously registered for the chosen template
		wp_enqueue_style( 'rwe-stylesheet' );
	}

	function rwe_link ($type, $id) {
		if (get_option('permalink_structure')) {
			// permalink
			return get_permalink($options['page_id'])."$type/$id/";
		}
		else {
			// non-permalink direct request link
			$rwe_get_vars = rwe_get_data('request_vars_list');
			$type = $rwe_get_vars["$type"];
			return get_permalink($options['page_id'])."&$type=$id";
		}
	}


	function process_item_in_category_image_links ($image_links, $item_name, $thumbnail_size, $rwe_id) {

		if ($thumbnail_size == 100)
			$thumb_base = '_public_thumbnail_';
		else
			$thumb_base = '_large_thumbnail_';

		if ($image_links[0]['photo_hash']) {
			// array item 0 contains the main image
			$item_images['photo_url'] = 'http://images.rwelephant.com/' . $rwe_id . $thumb_base . $image_links[0]['photo_hash'];
			$item_images['photo'] = '<img src="' . $item_images['photo_url'] . '" class="rwe-category-photo" alt="' . $item_name . '" />';
		}
		else {
			$item_images['photo_url'] = rwe_get_data('no-thumbnail-image');
			$item_images['photo'] = '<img src="' . $item_images['photo_url'] . '" class="rwe-category-photo" alt="' . $item_name . '" />';
		}

		return $item_images;
	}

	function process_image_links ($image_links, $item_name, $thumbnail_size, $rwe_id) {

		if ($image_links[0]['photo_hash']) {
			// array item 0 contains the main image
			$item_images['main_photo_url'] = $image_links[0]['photo_link'];
			$item_images['main_photo'] = '<a href="'. $item_images['main_photo_url'] .'"><img src="' . $item_images['main_photo_url'] . '" class="rwe-item-photo" alt="' . $item_name . '" /></a>';
		}
		else {
			$item_images['main_photo_url'] = rwe_get_data('no-item-image');
			$item_images['main_photo'] = '<img src="' . $item_images['main_photo_url'] . '" class="rwe-item-photo" alt="' . $item_name . '" />';
		}

		if ($thumbnail_size == 100)
			$thumb_base = '_public_thumbnail_';
		else
			$thumb_base = '_large_thumbnail_';

		$full_base = '_photo_';

		$thumbnails = array();
		$thumbnail_list = array();

		if ($image_links) {
			foreach ( $image_links as $image ) {
				if ( $image['photo_hash'] ) {
					$thumbnail_url = 'http://images.rwelephant.com/' . $rwe_id . $thumb_base . $image['photo_hash'];
					$full_image_url = 'http://images.rwelephant.com/' . $rwe_id . $full_base . $image['photo_hash'];

					$thumbnails[] = $thumbnail_url;
					$thumbnail_list[] = '<li><a href="' . $full_image_url . '"><img src="' . $thumbnail_url . '" /></a></li>';
				}
			}
		}

		// if we have more than the main photo, create thumbnail list
		if ( count($thumbnails) > 1 ) {

			// format thumbnails list

			$item_images['thumbnails'] = implode( '', $thumbnail_list );

			// create csv of thumbnails url

			$item_images['thumbnails_url'] = implode( ',', $thumbnails );

		}

		return $item_images;
	}

	function format_item_dimensions( $length, $width, $height ) {
		if ( $length | $width | $height ) {
			$dimensions = array();
			if ($length)
				$dimensions[] = $length;
			if ($width)
				$dimensions[] = $width;
			if ($height)
				$dimensions[] = $height;
			
			$dim_total = count($dimensions);

			foreach ($dimensions as $dim) {
				$dim_total-=1;
				$formatted_dimensions .= $dim;
				if ($dim_total > 0)
					$formatted_dimensions .= ' x ';
			}
			return $formatted_dimensions;
		}
		else return;
	}

	function change_page_title($title, $sep, $seplocation) { 

		$custom_title = rwe_get_data('page_title');
		$gallery_name = rwe_get_data('gallery_name');

		if ( $custom_title ) {

			$title_placeholders = array(
				'[title]' => $custom_title,
				'[separator]' => $sep,
				'[gallery_name]' => $gallery_name
			);
	
			$new_title = RWEgallery::parse_template( rwe_get_data('title_format') , $title_placeholders );
			return $new_title;
		}
		else {
			// no title is set (on the main gallery page) so use the page title from WordPress
			return $title;
		}
	}

	function change_page_heading($title, $id=null) { 
		$location = rwe_get_data('location');
		if ( $location == $id && in_the_loop() )
			return rwe_get_data('page_heading');
		else
			return $title;
	}

	function change_page_link($url, $id) { 
		$new_url = rwe_get_data('page_link');
		if ($new_url && in_the_loop())
			return $new_url;
		else
			return $url;
	}

	function parse_template ($template, $placeholders) {
		return str_replace(array_keys($placeholders), $placeholders, $template);
	}

	function rwe_gallery_display($content) { 
		$rwe_content = rwe_get_data('content');
		$new_content = $rwe_content . $content;
		return $new_content;
	}
	function add_admin_menu() {
		$hook_suffix = add_options_page(
			'R.W. Elephant Inventory Gallery', //page title
			'R.W. Elephant', //menu-title
			'manage_options', //access/capability
			'rw-elephant-inventory-gallery', //slug name
			'rwe_admin_options' //function
		);
		add_action( 'load-' . $hook_suffix , 'rwe_load_function' );
		add_action( 'admin_print_styles-' . $hook_suffix, 'rwe_admin_styles' );
	}
}
function rwe_admin_init() {
	wp_register_style( 'rwe-admin-stylesheet', plugins_url('admin.css', __FILE__) );
}
function rwe_admin_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	$options = array (
		array(
		    'name' => 'Settings',
		    'desc' => 'These settings are required to use the R.W. Elephant Inventory Gallery on your site.',
		    'id'   => 'main-settings',
		    'type' => 'section'),
		array(
		    'name' => 'R.W. Elephant ID',
		    'desc' => 'Example: coolrentals',
		    'id'   => 'rwelephant_id',
		    'type' => 'text',
		    'std'  => 'coolrentals'),
		array(
		    'name' => 'R.W. Elephant API Key',
		    'desc' => '(32 character code)',
		    'id'   => 'api_key',
		    'type' => 'text',
		    'std'  => '854572e22dd949a11a5b3719308196fd'),
		array(
		    'name' => 'Page for Gallery',
		    'desc' => 'Gallery is displayed at this location on your website.',
		    'id'   => 'page_id',
		    'type' => 'dropdown_pages'),
		array(
		    'name' => 'Options',
		    'desc' => 'You can use these options to customize the appearance of your gallery.',
		    'id'   => 'extra-options',
		    'type' => 'section'),
		array(
		    'name' => 'Gallery Name',
		    'desc' => 'Default: Gallery',
		    'id'   => 'gallery_name',
		    'type' => 'text',
		    'std'  => 'Gallery'),
		array(
		    'name' => 'Title Format',
		    'desc' => 'Placeholders available: [title] [separator] [gallery_name]',
		    'id'   => 'title_format',
		    'type' => 'text',
		    'std'  => '[title] [separator] [gallery_name] [separator] '),
		array(
		    'name' => 'Template',
		    'desc' => '',
		    'id'   => 'template',
		    'type' => 'select',
		    'options'  => array('greybox','simple','custom') ),
		array(
		    'name' => 'Thumbnail Size: Item List',
		    'desc' => 'pixels. Item thumbnails on category, tag and search pages',
		    'id'   => 'category_thumbnail_size',
		    'type' => 'select',
		    'options' => array(100,200) ),
		array(
		    'name' => 'Thumbnail Size: Alternate Images',
		    'desc' => 'pixels. Alternate thumbnails on item detail pages',
		    'id'   => 'item_thumbnail_size',
		    'type' => 'select',
		    'options' => array(100,200) ),
		array(
		    'name' => 'Social Sharing Links',
		    'desc' => 'Select which social sharing links to display on item detail pages.',
		    'id'   => 'social-sharing-settings',
		    'type' => 'section'),
		array(
		    'name' => 'Facebook',
		    'id'   => 'facebook',
		    'type' => 'checkbox',
		    'std' => null ),
		array(
		    'name' => 'Twitter',
		    'id'   => 'twitter',
		    'type' => 'checkbox',
		    'std' => null ),
		array(
		    'name' => 'Pinterest',
		    'id'   => 'pinterest',
		    'type' => 'checkbox',
		    'std' => null ),
		array(
		    'name' => 'Google',
		    'id'   => 'google',
		    'type' => 'checkbox',
		    'std' => null )
	);
	if ( 'save' == $_REQUEST['action'] ) {
		$options_to_update = array();
		foreach ($options as $value) {
			if( isset( $_REQUEST[ $value['id'] ] ) ) {
				$options_to_update[ $value['id'] ] = $_REQUEST[ $value['id'] ] ;
			}
		}
		update_option('rwe_gallery_options',$options_to_update);
		$saved = true;
	}
	$options_array = get_option('rwe_gallery_options');
	echo '<div class="rwe-admin-head"><h2><img src="'. plugin_dir_url(__FILE__) . 'images/rwe-logo.png" class="rwe-logo" alt="R.W. Elephant" />';
	echo 'R.W. Elephant Inventory Gallery</h2></div>';
	if ( $saved == true ) echo '<div id="message" class="updated fade"><p><strong>R.W. Elephant Inventory Gallery settings saved.</strong></p></div>';
	echo '<div class="rwe-settings">';
	echo '<form method="post">';
	foreach ($options as $value) {
		switch ( $value['type'] ) {
		case 'section':
			if ($value['name']) echo '<h3>'.$value['name'].'</h3>';
			if ($value['desc']) echo '<p>'.$value['desc'].'</p>';
		break;
		case 'text':
			echo '<div><label class="" for="'.$value['id'].'">'. $value['name'].'</label>'
				. '<input name="'. $value['id'] .'" id="'. $value['id'] .'" type="'. $value['type'] .'" value="';
				if ( $options_array[ $value['id'] ] != "") {
					echo stripslashes( $options_array[ $value['id'] ] );
				}
				else {
					echo $value['std'];
				}
			echo '" />';
			if ( $value['desc'] ) echo '<small class="rwe-note">'. $value['desc'] .'</small>';
			echo '</div>';
		break;
		case 'select':
			echo '<div><label class="" for="'.$value['id'].'">'. $value['name'] .'</label>'
				.'<select name="'. $value['id'] .'" id="'. $value['id'] .'">';
			foreach ( $value['options'] as $opt ) {
				echo '<option value="' . $opt .'"';
				if ( $options_array[ $value['id'] ] == $opt) echo ' selected="selected"';
				echo '>';
				echo $opt . '</option>';
			}
			echo '</select>';
			echo ' <small class="rwe-note">' . $value['desc'] . '</small></div>';
		break;
		case 'dropdown_pages':
			echo '<div><label class="" for="'.$value['id'].'">'. $value['name'];
			echo '</label>';
			$args = array(
			    'depth'    => 0,
			    'child_of' => 0,
			    'selected' => $options_array[ $value['id'] ],
			    'echo'     => 1,
			    'name'     => $value['id']
			);
			wp_dropdown_pages( $args );
			echo '<small class="rwe-note">' . $value['desc'] . '</small>';
			echo '</div>';
		break;
		case 'checkbox':
			echo '<input type="checkbox" name="'.$value['id'].'" id="'.$value['id'].'" value="true"';
			if ( $options_array[ $value['id'] ] == true ) echo ' checked="true"';
			echo ' />';
			echo '<label for="'.$value['id'].'" class="social-checkbox">'.$value['name'].'</label>';
		break;
		}
	}
	echo '<input name="save" class="rwe-submit" type="submit" value="Save changes" />';
	echo '<input type="hidden" name="action" value="save" />';
	echo '</form>';
	echo '</div>';
}

if (!get_option('rwe_gallery_options')) {
	add_action( 'admin_notices', 'rwe_admin_notices' );
}

function rwe_load_function() {
	remove_action( 'admin_notices', 'rwe_admin_notices' );
}
function rwe_admin_notices() {
	echo "<div id='notice' class='updated fade'><p>R.W. Elephant Inventory Gallery is not configured yet. Please edit the settings now.</p></div>\n";
}

function rwe_admin_styles() {
	wp_enqueue_style( 'rwe-admin-stylesheet' );
}

function jsonp_decode($jsonp, $assoc = true) { // PHP 5.3 adds depth as third parameter to json_decode
	if($jsonp[0] !== '[' && $jsonp[0] !== '{') { // we have JSONP
		$jsonp = substr($jsonp, strpos($jsonp, '('));
	}
	return json_decode(trim($jsonp,'();'), $assoc);
}

// get the permalink for the gallery
function get_rwe_gallery_permalink() {
	// get permalink for gallery page
	$rwe_gallery_options = get_option('rwe_gallery_options');

	if ( $rwe_gallery_options['page_id'] ) {
		$rwe_gallery_page = substr(get_permalink( $rwe_gallery_options['page_id'] ), strlen(get_settings('home'))+1, -1 );
		return $rwe_gallery_page;
	}
}

// flush rewrite rules if our rules are included
function rwe_gallery_flush_rewrite_rules() {

	$rwe_gallery_page = get_rwe_gallery_permalink();

	$rules = get_option( 'rewrite_rules' );
	if ( ! isset( $rules['(' . $rwe_gallery_page . ')/(.+)$'] ) ) {
		global $wp_rewrite;
	   	$wp_rewrite->flush_rules();
	}
}

// Add rewrite rule
function rwe_gallery_rewrite_rules( $rules ) {

	$rwe_gallery_page = get_rwe_gallery_permalink();

	$newrules = array();
	$newrules['(' . $rwe_gallery_page . ')/(.+)$'] = 'index.php?pagename=$matches[1]&rwegallery=$matches[2]';
	return $newrules + $rules;
}

// Add variables to use in query
function rwe_gallery_query_vars( $vars ) {
	array_push($vars, 'rwegallery', 'rwecat', 'rwetag', 'rweitem', 'rwe-search');
	return $vars;
}

if ( get_option('permalink_structure') ) {
	// permalinks are enabled
	// add rewrite rules
	add_action( 'wp_loaded','rwe_gallery_flush_rewrite_rules' );
	add_filter( 'rewrite_rules_array','rwe_gallery_rewrite_rules' );
}


function rwe_gallery_plugin_action_links( $links ) {
 	return array_merge(
		$links, 
		array(
			sprintf(
				'<a href="%s">%s</a>',
				add_query_arg(
					array(
						'page' => 'rw-elephant-inventory-gallery'
					),
					admin_url('options-general.php')
				),
				__('Settings')
			)
		)

	);
 
}

add_filter( 'query_vars','rwe_gallery_query_vars' );
add_action('wp', array('RWEgallery', 'rwe_gallery_core'));
add_action( 'admin_init', 'rwe_admin_init' );
add_action('admin_menu', array('RWEgallery', 'add_admin_menu'));
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'rwe_gallery_plugin_action_links' );

?>