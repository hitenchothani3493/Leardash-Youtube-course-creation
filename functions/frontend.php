<?php

class ldYtCourses {

	private $api_url = 'https://www.googleapis.com/youtube/v3/';
	private $api_key = '';
	private $api_part = 'snippet';
	private $api_videos_fields = 'items(id,snippet(title,description,thumbnails))';
	private $api_playlist_fields = 'items(id,snippet(title,description,thumbnails,resourceId))';
	private $api_search_fields = 'items(id(kind,playlistId,videoId),snippet(description,thumbnails,title))';
	private $api_maxResults = '50';

	function __construct() 
    {
        add_action('wp_ajax_fetch_youtube_link', array(&$this, 'parse_youtube_link'));
		add_action('wp_ajax_nopriv_fetch_youtube_link', array(&$this, 'parse_youtube_link'));

		add_action('wp_ajax_create_youtube_courses', array(&$this, 'create_youtube_courses'));
		add_action('wp_ajax_nopriv_create_youtube_courses', array(&$this, 'create_youtube_courses'));

        add_shortcode('ld_yt_courses', array(&$this, 'shortcode'));

        $this->set_api_key();
	}

	function set_api_key() {
		global $wpdb;

		$options = get_option( 'ld_yt_courses_settings' );

		if( isset($options['api_key']) )
			$this->api_key = $options['api_key'];

	}
	
	function parse_youtube_link()
	{

/*error_reporting(E_ALL);
ini_set('display_errors', '1');*/

		$search_text = $_POST['link'];
		
		if (filter_var($search_text, FILTER_VALIDATE_URL)) { // Filter by URL

			$url = parse_url($search_text);

			parse_str($url['query'], $query_string);


			if(array_key_exists('v' ,$query_string) && array_key_exists('list' ,$query_string)) { // filter video + playlist
				$end_point = 'playlistItems';
				
				$base_end_point_url = $this->api_url.$end_point;
				
				$base_vide_id = $query_string['v'];

				$query_string_end_point_url = array();
				$query_string_end_point_url['playlistId'] = $query_string['list'];
				$query_string_end_point_url['key'] = $this->api_key;
				$query_string_end_point_url['fields'] = $this->api_playlist_fields;
				$query_string_end_point_url['part'] = $this->api_part;
				$query_string_end_point_url['maxResults'] = $this->api_maxResults;

				$end_point_url = add_query_arg($query_string_end_point_url, $base_end_point_url);

				$response = wp_remote_get($end_point_url);

				if(is_wp_error($response)) {
					$course_content = '
					<div class="col-md-12 text-center">
						<h3 class="text-primary">Sorry!</h3>
					</div>';
					$lesson_content = '
					<div class="col-md-12 text-center">
						<p class="text-primary">Something went wrong, please try after sometime!</p>
					</div>';
				} else {
					$video_detail = json_decode(wp_remote_retrieve_body($response),true);
					
					if(array_key_exists('error', $video_detail)) {
						$course_content = '
						<div class="col-md-12 text-center">
							<h3 class="text-primary">Sorry!</h3>
						</div>';
						$lesson_content = '
						<div class="col-md-12 text-center">
							<p class="text-primary">'.$video_detail['error']['message'].'</p>
						</div>';
					} else {

						$course_content = '
						<div class="form-group">
						<label for="course_title">Course Title:</label>
						<input type="text" class="form-control" name="course_title" id="course_title" value="'.$video_detail['items'][0]['snippet']['title'].'">
						</div>
						<div class="form-group">
						<label for="course_description">Course Description:</label>
						<textarea class="form-control" name="course_description" id="course_description" rows="10">'.$video_detail['items'][0]['snippet']['description'].'</textarea>
						</div>
						<div class="form-group text-right">
							<button type="button" class="btn btn-primary btn-lg active course_create_individual">Create Course with highlighted video</button>
							<button type="button" class="btn btn-primary btn-lg active course_create">Create Course with playlist</button>
						</div>
						';

						$lesson_content = '';

						foreach($video_detail['items'] as $item) {
							$video_id = $item['snippet']['resourceId']['videoId'];
							$title = $item['snippet']['title'];
							$description = $item['snippet']['description'];
							$description = '';
							
							if(isset($item['snippet']['thumbnails']['maxres']))
								$img_url = $item['snippet']['thumbnails']['maxres']['url'];
							else if(isset($item['snippet']['thumbnails']['standard']))
								$img_url = $item['snippet']['thumbnails']['standard']['url'];
							else if(isset($item['snippet']['thumbnails']['default']))
								$img_url = $item['snippet']['thumbnails']['default']['url'];
							
							if($video_id == $base_vide_id) {
								$container_class = ' video-container-individual';
								$lesson_class = ' thumbnail-highlight';
							}
							else {
								$container_class = '';
								$lesson_class = '';
							}

							$lesson_content .= '
							<div class=" video-container'.$container_class.' col-lg-4 col-md-6 col-sm-12">
								<input type="hidden" name="img_url" value="'.$img_url.'">
								<input type="hidden" name="title" value="'.$title.'">
								<input type="hidden" name="description" value="'.$description.'">
								<input type="hidden" name="video_id" value="'.$video_id.'">
								<div class="thumbnail'.$lesson_class.'">
									<img src="'.$img_url.'" style="width:100%">
									<div class="caption">
										<h2 class="text-primary">'.$title.'</h2>
										<p class="text-muted small">'.$description.'</p>
									</div>
								</div>
							</div>';

						}
					}
				}

			} else if(array_key_exists('list' ,$query_string)) { // filter playlist only
				/*fetch playlist details*/

				$end_point = 'playlists';
				
				$base_end_point_url = $this->api_url.$end_point;
				
				$query_string_end_point_url = array();
				$query_string_end_point_url['id'] = $query_string['list'];
				$query_string_end_point_url['key'] = $this->api_key;
				$query_string_end_point_url['fields'] = 'items(id,snippet(title,description,thumbnails))';
				$query_string_end_point_url['part'] = $this->api_part;
				$query_string_end_point_url['maxResults'] = '1';

				$end_point_url = add_query_arg($query_string_end_point_url, $base_end_point_url);

				$playlists_detail = wp_remote_get($end_point_url);

				$course_title = '';
				$course_description = '';

				if(!is_wp_error($playlists_detail)) {
					$playlist_detail = json_decode(wp_remote_retrieve_body($playlists_detail),true);

					$course_title = $playlist_detail['items'][0]['snippet']['title'];
					$course_description = $playlist_detail['items'][0]['snippet']['description'];
					$course_url = '';

					if(isset($playlist_detail['items'][0]['snippet']['thumbnails'])) {
						if(isset($playlist_detail['items'][0]['snippet']['thumbnails']['maxres']))
							$course_url = $playlist_detail['items'][0]['snippet']['thumbnails']['maxres']['url'];
						else if(isset($playlist_detail['items'][0]['snippet']['thumbnails']['standard']))
							$course_url = $playlist_detail['items'][0]['snippet']['thumbnails']['standard']['url'];
						else if(isset($playlist_detail['items'][0]['snippet']['thumbnails']['default']))
							$course_url = $playlist_detail['items'][0]['snippet']['thumbnails']['default']['url'];
					}
				}

				/*Fetch video details*/
				$end_point = 'playlistItems';
				
				$base_end_point_url = $this->api_url.$end_point;
				
				$query_string_end_point_url = array();
				$query_string_end_point_url['playlistId'] = $query_string['list'];
				$query_string_end_point_url['key'] = $this->api_key;
				$query_string_end_point_url['fields'] = $this->api_playlist_fields;
				$query_string_end_point_url['part'] = $this->api_part;
				$query_string_end_point_url['maxResults'] = $this->api_maxResults;

				$end_point_url = add_query_arg($query_string_end_point_url, $base_end_point_url);

				$video_details = wp_remote_get($end_point_url);

				if(is_wp_error($video_details)) {
					$course_content = '
					<div class="col-md-12 text-center">
						<h3 class="text-primary">Sorry!</h3>
					</div>';
					$lesson_content = '
					<div class="col-md-12 text-center">
						<p class="text-primary">Something went wrong, please try after sometime!</p>
					</div>';
				} else {
					$video_detail = json_decode(wp_remote_retrieve_body($video_details),true);
					
					if(array_key_exists('error', $video_detail)) {
						$course_content = '
						<div class="col-md-12 text-center">
							<h3 class="text-primary">Sorry!</h3>
						</div>';
						$lesson_content = '
						<div class="col-md-12 text-center">
							<p class="text-primary">'.$video_detail['error']['message'].'</p>
						</div>';
					} else {

						$course_content = '
						<div class="form-group">
						<label for="course_title">Course Title:</label>
						<input type="text" class="form-control" name="course_title" id="course_title" value="'.$course_title.'">
						</div>
						<div class="form-group">
						<label for="course_description">Course Description:</label>
						<textarea class="form-control" name="course_description" id="course_description" rows="10">'.$course_description.'</textarea>
						</div>
						<input type="hidden" name="course_url" id="course_url" value="'.$course_url.'">
						<div class="form-group text-right">
							<button type="button" class="btn btn-primary btn-lg active course_create">Create Course</button>
						</div>
						';

						$lesson_content = '';

						foreach($video_detail['items'] as $item) {
							$video_id = $item['snippet']['resourceId']['videoId'];
							$title = $item['snippet']['title'];
							$description = $item['snippet']['description'];
							$description = '';
							
							if(isset($item['snippet']['thumbnails']['maxres']))
								$img_url = $item['snippet']['thumbnails']['maxres']['url'];
							else if(isset($item['snippet']['thumbnails']['standard']))
								$img_url = $item['snippet']['thumbnails']['standard']['url'];
							else if(isset($item['snippet']['thumbnails']['default']))
								$img_url = $item['snippet']['thumbnails']['default']['url'];
							
							$lesson_content .= '
							<div class=" video-container col-lg-4 col-md-6 col-sm-12">
								<input type="hidden" name="img_url" value="'.$img_url.'">
								<input type="hidden" name="title" value="'.$title.'">
								<input type="hidden" name="description" value="'.$description.'">
								<input type="hidden" name="video_id" value="'.$video_id.'">
								<div class="thumbnail">
									<img src="'.$img_url.'" style="width:100%">
									<div class="caption">
										<h2 class="text-primary">'.$title.'</h2>
										<p class="text-muted small">'.$description.'</p>
									</div>
								</div>
							</div>';

						}
					}
				}

			} else if(array_key_exists('v' ,$query_string)) { //filter video only
				$end_point = 'videos';
				
				$base_end_point_url = $this->api_url.$end_point;
				
				$query_string_end_point_url = array();
				$query_string_end_point_url['id'] = $query_string['v'];
				$query_string_end_point_url['key'] = $this->api_key;
				$query_string_end_point_url['fields'] = $this->api_videos_fields;
				$query_string_end_point_url['part'] = $this->api_part;

				$end_point_url = add_query_arg($query_string_end_point_url, $base_end_point_url);

				$response = wp_remote_get($end_point_url);

				if(is_wp_error($response)) {
					$course_content = '
					<div class="col-md-12 text-center">
						<h3 class="text-primary">Sorry!</h3>
					</div>';
					$lesson_content = '
					<div class="col-md-12 text-center">
						<p class="text-primary">Something went wrong, please try after sometime!</p>
					</div>';
				} else {
					$video_details = json_decode(wp_remote_retrieve_body($response),true);

					if(empty($video_details['items'])) {
						$course_content = '
						<div class="col-md-12 text-center">
							<h3 class="text-primary">Sorry!</h3>
						</div>';
						$lesson_content = '
						<div class="col-md-12 text-center">
							<p class="text-primary">Please check your video URL!</p>
						</div>';
					} else {
					
						$video_detail = $video_details['items'][0];
						
						$video_id = $video_detail['id'];
						$title = $video_detail['snippet']['title'];
						$description = $video_detail['snippet']['description'];
						$description = '';
						
						if(isset($video_detail['snippet']['thumbnails']['maxres']))
							$img_url = $video_detail['snippet']['thumbnails']['maxres']['url'];
						else if(isset($video_detail['snippet']['thumbnails']['standard']))
							$img_url = $video_detail['snippet']['thumbnails']['standard']['url'];
						else if(isset($video_detail['snippet']['thumbnails']['default']))
							$img_url = $video_detail['snippet']['thumbnails']['default']['url'];

						$course_content = '
						<div class="form-group">
						<label for="course_title">Course Title:</label>
						<input type="text" class="form-control" name="course_title" id="course_title" value="'.$title.'">
						</div>
						<div class="form-group">
						<label for="course_description">Course Description:</label>
						<textarea class="form-control" name="course_description" id="course_description" rows="10">'.$description.'</textarea>
						</div>
						<div class="form-group text-right">
							<button type="button" class="btn btn-primary btn-lg active course_create">Create Course</button>
						</div>
						';

						$lesson_content = '
						<div class="video-container col-lg-4 col-md-6 col-sm-12">
							<input type="hidden" name="img_url" value="'.$img_url.'">
							<input type="hidden" name="title" value="'.$title.'">
							<input type="hidden" name="description" value="'.$description.'">
							<input type="hidden" name="video_id" value="'.$video_id.'">
							<div class="thumbnail">
								<img src="'.$img_url.'" style="width:100%">
								<div class="caption">
									<h2 class="text-primary">'.$title.'</h2>
									<p>'.$description.'</p>
								</div>
							</div>
						</div>';
					}
				}

			} else {
				$course_content = '
				<div class="col-md-12 text-center">
					<h3 class="text-primary">Sorry!</h3>
				</div>';
				$lesson_content = '
				<div class="col-md-12 text-center">
					<p class="text-primary">Please check your video URL!</p>
				</div>';
			}
		} else { //Search text in youtube
			$end_point = 'search';
				
			$base_end_point_url = $this->api_url.$end_point;

			$query_string_end_point_url = array();
			$query_string_end_point_url['q'] = $search_text;
			$query_string_end_point_url['part'] = $this->api_part;
			$query_string_end_point_url['maxResults'] = $this->api_maxResults;
			$query_string_end_point_url['fields'] = $this->api_search_fields;
			$query_string_end_point_url['type'] = 'video,playlist';
			$query_string_end_point_url['key'] = $this->api_key;

			$end_point_url = add_query_arg($query_string_end_point_url, $base_end_point_url);

			/*$current_url = $this->currentUrl();*/

			$response = wp_remote_get($end_point_url);

			if(is_wp_error($response)) {
				$course_content = '
				<div class="col-md-12 text-center">
					<h3 class="text-primary">Sorry!</h3>
				</div>';
				$lesson_content = '
				<div class="col-md-12 text-center">
					<p class="text-primary">Something went wrong, please try after sometime!</p>
				</div>';
			} else {
				$search_details = json_decode(wp_remote_retrieve_body($response),true);

				$course_content = '';

				$lesson_content = '';

				if(empty($search_details['items'])) {
					$course_content = '
					<div class="col-md-12 text-center">
						<h3 class="text-primary">Sorry!</h3>
					</div>';
					$lesson_content = '
					<div class="col-md-12 text-center">
						<p class="text-primary">No video found, please check your serach term!</p>
					</div>';
				} else {
					$currentURL = $_POST['currentURL'];
					foreach($search_details['items'] as $item) {

						if($item['id']['kind'] == 'youtube#playlist') {
							/*echo "\n";
							echo $item['snippet']['title'];*/

							$playlist_id = $item['id']['playlistId'];
							$title = $item['snippet']['title'];
							$description = $item['snippet']['description'];
							$description = '';

							if(isset($item['snippet']['thumbnails']['high']))
								$img_url = $item['snippet']['thumbnails']['high']['url'];
							else if(isset($item['snippet']['thumbnails']['medium']))
								$img_url = $item['snippet']['thumbnails']['medium']['url'];
							else if(isset($item['snippet']['thumbnails']['default']))
								$img_url = $item['snippet']['thumbnails']['default']['url'];

							$youtube_url = 'https://www.youtube.com/playlist?list='.$playlist_id;
							$gener_link = add_query_arg(array('query' => $youtube_url), $currentURL);

							$lesson_content .= '
							<div class="video-container col-lg-4 col-md-6 col-sm-12">
								<div class="thumbnail">
									<img src="'.$img_url.'" style="width:100%">
									<div class="caption">
										<a href="'.$gener_link.'"><h2 class="text-primary">'.$title.'</a></h2>
										<p>Playlist</p>
									</div>
								</div>
							</div>
							';
						} else if($item['id']['kind'] == 'youtube#video') {
							/*echo "\n";
							echo $item['snippet']['title'];*/

							$video_id = $item['id']['videoId'];
							$title = $item['snippet']['title'];
							$description = $item['snippet']['description'];
							$description = '';

							if(isset($item['snippet']['thumbnails']['high']))
								$img_url = $item['snippet']['thumbnails']['high']['url'];
							else if(isset($item['snippet']['thumbnails']['medium']))
								$img_url = $item['snippet']['thumbnails']['medium']['url'];
							else if(isset($item['snippet']['thumbnails']['default']))
								$img_url = $item['snippet']['thumbnails']['default']['url'];

							$youtube_url = 'https://www.youtube.com/playlist?v='.$video_id;
							$gener_link = add_query_arg(array('query' => $youtube_url), $currentURL);

							$lesson_content .= '
							<div class="video-container col-lg-4 col-md-6 col-sm-12">
								<div class="thumbnail">
									<img src="'.$img_url.'" style="width:100%">
									<div class="caption">
										<a href="'.$gener_link.'"><h2 class="text-primary">'.$title.'</a></h2>
										<p>Video</p>
									</div>
								</div>
							</div>
							';
						}

					}
				}


			}


		}





		$html_content = '
		<section class="">
			<div class="container-fluid">
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">';
		
		$html_content .= $course_content;

		$html_content .= '
						</div>
					</div>
				</div>
				<div class="row">';

		$html_content .= $lesson_content;

		$html_content .= '
				</div>
			</div>
		</section>
		';

		echo $html_content;
		exit;

	}

	function create_youtube_courses() {

		$lesson_counter = 0;

		$course_data = $_POST['CourseData'];

		$course = array(
			'post_title' => $course_data['title'],
			'post_status' => 'publish',
			'post_type' => 'sfwd-courses',
			'post_content' => $course_data['description'],
			'post_author' => get_current_user_id(),
			'post_name' => $course_data['title'],
			'menu_order'    => 0,
		);

		$course_id = wp_insert_post($course);

		if(isset($course_data['url']) && !empty($course_data['url'])) {
			$this->gst_asign_image_to_post($course_data['url'], $course_id);
		}

		$lessons = $_POST['LessonData'];

		foreach($lessons as $lesson) {
			$lesson_counter++;
			$title = $lesson['title'];
			$description = $lesson['description'];
			$img_url = $lesson['img_url'];
			$video_id = $lesson['video_id'];

			$lesson = array(
				'post_title' => $title,
				'post_status' => 'publish',
				'post_type' => 'sfwd-lessons',
				'post_content' => $description,
				'post_author' => get_current_user_id(),
				'post_name' => $title,
				'menu_order'    => 0,
			);

			$lesson_id = wp_insert_post($lesson);

			$this->gst_asign_image_to_post($img_url, $lesson_id);

			add_post_meta( $lesson_id, 'course_id', $course_id );

			$lesson_meta = array();
			$lesson_meta['sfwd-lessons_course'] = $course_id;
			$lesson_meta['sfwd-lessons_lesson_video_enabled'] = 'on';
			$lesson_meta['sfwd-lessons_lesson_video_url'] = 'https://www.youtube.com/watch?v='.$video_id;
			$lesson_meta['sfwd-lessons_lesson_video_auto_start'] = 'on';
			$lesson_meta['sfwd-lessons_lesson_video_show_controls'] = 'on';
			$lesson_meta['sfwd-lessons_lesson_video_shown'] = 'BEFORE';

			//$sfwd_lessons_meta = serialize($lesson_meta);	
			add_post_meta( $lesson_id, '_sfwd-lessons', $lesson_meta );
		}

		$course_link = get_permalink($course_id);

		$html_content = '
		<section>
			<div class="container">
				<div class="row">
					<div class="col-md-12 text-center">
						<h3 class="text-primary">1 Course and '.$lesson_counter.' Lessons published</h3>
						<p><a href="'.$course_link.'">'.$course_link.'</a></p>
					</div>
				</div>
			</div>
		</section>
		';

		echo $html_content;

		exit;

	}
	
	function shortcode( $atts )
	{
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'ld-yt-courses-css', plugins_url( '/assets/styles/ld-yt-courses.css', dirname(__FILE__) ) );
		wp_enqueue_style( 'bootstrap-css', plugins_url( '/assets/styles/bootstrap.css', dirname(__FILE__) ) );

		wp_enqueue_script( 'ld-yt-courses-js', plugins_url( '/assets/scripts/ld-yt-courses.js', dirname(__FILE__) ), array('jquery') );
		wp_localize_script( 'ld-yt-courses-js', 'ld_yt_courses', array('ajaxurl' => admin_url('admin-ajax.php')) );

		wp_enqueue_script( 'bootstrap-js', plugins_url( '/assets/scripts/bootstrap.js', dirname(__FILE__) ) );

		extract( shortcode_atts( array(
		), $atts ) );
		

		$q = '';
		if(isset($_GET['query']) && !empty($_GET['query'])) {
			$q = $_GET['query'];		
		}

		$result = 
		'<div class="video-search">	
			<div class="">
				<div class="youtube-search-container container">
				<form target="_top" id="youtube_search" data-parsley-validate="true" action="'.get_permalink().'" method="post" novalidate="">
					<input type="url" name="q" id="search_field" placeholder="Enter YouTube URL" value="'.$q.'">
					<button class="submit-query-search" type="submit">
						<i class="icon-search"></i>
					</button>
				</form>
					<div class="search-results alert alert-default">
						
					</div>
				</div>
			</div>
		</div>';

		return $result;
	}

	function gst_asign_image_to_post($image_url, $post_id) {
		// Add Featured Image to Post
		$image_info		  = pathinfo($image_url);
		$image_name       = $image_info['basename'];
		$upload_dir       = wp_upload_dir(); // Set upload folder
		$image_data       = file_get_contents($image_url); // Get image data
		$unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
		$filename         = basename( $unique_file_name ); // Create image file name

		// Check folder permission and define file location
		if( wp_mkdir_p( $upload_dir['path'] ) ) {
		    $file = $upload_dir['path'] . '/' . $filename;
		} else {
		    $file = $upload_dir['basedir'] . '/' . $filename;
		}

		// Create the image  file on the server
		file_put_contents( $file, $image_data );

		// Check image file type
		$wp_filetype = wp_check_filetype( $filename, null );

		// Set attachment data
		$attachment = array(
		    'post_mime_type' => $wp_filetype['type'],
		    'post_title'     => sanitize_file_name( $filename ),
		    'post_content'   => '',
		    'post_status'    => 'inherit'
		);

		// Create the attachment
		$attach_id = wp_insert_attachment( $attachment, $file, $post_id );

		// Include image.php
		require_once(ABSPATH . 'wp-admin/includes/image.php');

		// Define attachment metadata
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file );

		// Assign metadata to attachment
		wp_update_attachment_metadata( $attach_id, $attach_data );

		// And finally assign featured image to post
		set_post_thumbnail( $post_id, $attach_id );
	}
	
	function currentUrl( $trim_query_string = false ) {
	    $pageURL = (isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on') ? "https://" : "http://";
	    $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
	    if( ! $trim_query_string ) {
	        return $pageURL;
	    } else {
	        $url = explode( '?', $pageURL );
	        return $url[0];
	    }
	}
}



?>