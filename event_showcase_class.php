<?php
/*
 * Event Showcase
 * Creates an 'event' content type to showcase upcoming functions and information
 * Uses hooks and filters inside your theme to output relevant information
 */
 
 class event_showcase{
 	
	//variables
	private $directory = '';	
	private $singular_name = 'event';
	private $plural_name = 'events';
	private $content_type_name = 'event_showcase';
	
	
	//magic function, called on creation
	public function __construct(){
		
		$this->set_directory_value(); //set the directory url on creation
		add_action('init', array($this,'add_content_type')); //add content type
		add_action('init', array($this,'check_flush_rewrite_rules')); //flush re-write rules for permalinks (because of content type)
		add_action('add_meta_boxes', array($this,'add_meta_boxes_for_content_type')); //add meta boxes 
		add_action('wp_enqueue_scripts', array($this,'enqueue_public_scripts_and_styles')); //enqueue public facing elements
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts_and_styles')); //enqueues admin elements
		add_action('save_post_' . $this->content_type_name, array($this,'save_custom_content_type')); //handles saving of content type meta info
		add_action('display_content_type_meta', array($this,'display_additional_meta_data')); //displays the saved content type meta info	
	}
	
	//sets the directory (path) so that we can use this for our enqueuing
	public function set_directory_value(){
		$this->directory = get_stylesheet_directory_uri() . '/includes/event_showcase';
	}

	//check if we need to flush rewrite rules
	public function check_flush_rewrite_rules(){
		$has_been_flushed = get_option($this->content_type_name . '_flush_rewrite_rules');
		//if we haven't flushed re-write rules, flush them (should be triggered only once)
		if($has_been_flushed != true){
			flush_rewrite_rules(true);
			update_option($this->content_type_name . '_flush_rewrite_rules', true);
		}
	}
	
	//enqueue public scripts and styles
	public function enqueue_public_scripts_and_styles(){	
		//font awesome styles
		wp_enqueue_style(
			'font-awesome',
			'//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css'
		);
		//public styles
		wp_enqueue_style(
			$this->content_type_name . '_public_styles', 
			$this->directory . '/css/' . $this->content_type_name . '_public_styles.css'
		);
		//public scripts
		wp_enqueue_script(
			$this->content_type_name . '_public_scripts', 
			$this->directory . '/js/' . $this->content_type_name . '_public_scripts.js', 
			array('jquery')
		); 
	}
	
	//enqueue admin scripts and styles
	public function enqueue_admin_scripts_and_styles(){
			
		global $pagenow, $post_type;
		
		//process only on post edit page for custom content type
		if(($post_type == $this->content_type_name) && ($pagenow == 'post-new.php' || $pagenow == 'post.php')){
			
			//admin styles
			wp_enqueue_style(
				$this->content_type_name . '_public_styles', 
				$this->directory . '/css/' . $this->content_type_name . '_admin_styles.css'
			);
			//jquery ui styles for datepicker
			wp_enqueue_style(
				$this->content_type_name . '_jquery_ui_style',
				'//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css'
			);
			//timepicker styles
			wp_enqueue_style(
				'jquery_ui_timepicker_styles',
				$this->directory . '/css/jquery.ui.timepicker.css'
			);
			//timepicker script
			wp_enqueue_script(
				'jquery_ui_timepicker_script',
				$this->directory . '/js/jquery.ui.timepicker.js'
			);		
			//admin scripts (depends on datepicker and timepicker)
			wp_enqueue_script(
				$this->content_type_name . '_public_scripts', 
				$this->directory . '/js/' . $this->content_type_name . '_admin_scripts.js', 
				array('jquery','jquery-ui-datepicker','jquery_ui_timepicker_script')
			); 	
		}
	}
	
	//adding our new content type
	public function add_content_type(){
		 $labels = array(
            'name'               => ucwords($this->singular_name),
            'singular_name'      => ucwords($this->singular_name),
            'menu_name'          => ucwords($this->plural_name),
            'name_admin_bar'     => ucwords($this->singular_name),
            'add_new'            => ucwords($this->singular_name),
            'add_new_item'       => 'Add New ' . ucwords($this->singular_name),
            'new_item'           => 'New ' . ucwords($this->singular_name),
            'edit_item'          => 'Edit ' . ucwords($this->singular_name),
            'view_item'          => 'View ' . ucwords($this->plural_name),
            'all_items'          => 'All ' . ucwords($this->plural_name),
            'search_items'       => 'Search ' . ucwords($this->plural_name),
            'parent_item_colon'  => 'Parent ' . ucwords($this->plural_name) . ':', 
            'not_found'          => 'No ' . ucwords($this->plural_name) . ' found.', 
            'not_found_in_trash' => 'No ' . ucwords($this->plural_name) . ' found in Trash.',
        );
        
        $args = array(
            'labels'            => $labels,
            'public'            => true,
            'publicly_queryable'=> true,
            'show_ui'           => true,
            'show_in_nav'       => true,
            'query_var'         => true,
            'hierarchical'      => false,
            'supports'          => array('title','editor','thumbnail'), 
            'has_archive'       => true,
            'menu_position'     => 20,
            'show_in_admin_bar' => true,
            'menu_icon'         => 'dashicons-format-status'
        );
		
		//register your content type
		register_post_type($this->content_type_name, $args);
		
	}

	//adding meta box to save additional meta data for the content type
	public function add_meta_boxes_for_content_type(){
		
		//add a meta box
		add_meta_box(
			$this->singular_name . '_meta_box', //id
			ucwords($this->singular_name) . ' Information', //box name
			array($this,'display_function_for_content_type_meta_box'), //display function
			$this->content_type_name, //content type 
			'normal', //context
			'default' //priority
		);
		
	}
	
	//displays the visual output of the meta box in admin (where we will save our meta data)
	public function display_function_for_content_type_meta_box($post){
		
		//collect meta information
		$event_subtitle = get_post_meta($post->ID,'event_subtitle', true);
		$event_start_date = get_post_meta($post->ID,'event_start_date', true);
		$event_end_date = get_post_meta($post->ID,'event_end_date', true);
		$event_start_time = get_post_meta($post->ID,'event_start_time', true);
		$event_end_time = get_post_meta($post->ID,'event_end_time', true);
		$event_location = get_post_meta($post->ID,'event_location', true);
		$event_price = get_post_meta($post->ID,'event_price', true);
	
		//set nonce
		wp_nonce_field($this->content_type_name . '_nonce', $this->content_type_name . '_nonce_field');
		
		?>
		<p>Enter additional information about your event below</p>
		<div class="field-container">
			<label for="event_subtitle">Subtitle</label>
			<input type="text" name="event_subtitle" id="event_subtitle" value="<?php echo $event_subtitle; ?>"/>
		</div>
		<div class="field-container">
			<label for="event_location">Event Location</label>
			<textarea name="event_location" id="event_location"><?php echo $event_location; ?></textarea>
		</div>
		<div class="field-container">
			<label for="event_start_date">Start Date</label>
			<input type="text" name="event_start_date" id="event_start_date" class="admin-datepicker" value="<?php echo $event_start_date; ?>" required/>
		</div>
		<div class="field-container">
			<label for="event_end_date">End Date</label>
			<input type="text" name="event_end_date" id="event_end_date" class="admin-datepicker" value="<?php echo $event_end_date;  ?>" required/>
		</div>
		<div class="field-container">
			<label for="event_start_time">Start Time</label>
			<input type="text" name="event_start_time" id="event_start_time" class="admin-timepicker" value="<?php echo $event_start_time; ?>" required/>
		</div>
		<div class="field-container">
			<label for="event_end_time">End Time</label>
			<input type="text" name="event_end_time" id="event_end_time" class="admin-timepicker" value="<?php echo $event_end_time; ?>" required/>
		</div>
		<div class="field-container">
			<label for="event_price">Price</label>
			<input type="text" name="event_price" id="event_price"  value="<?php echo $event_price; ?>"/>
		</div>
		<?php
	}
	
	
	//when saving the custom content type, save additional meta data
	public function save_custom_content_type($post_id){
		
		//check for nonce
		if(!isset($_POST[$this->content_type_name . '_nonce_field'])){
			return $post_id;
		}
		//verify nonce
		if(!wp_verify_nonce($_POST[$this->content_type_name . '_nonce_field'] , $this->content_type_name . '_nonce')){
			return $post_id;
		}
		//check for autosaves
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
			return $post_id;
		}
		//check if the user can edit 
		if(!current_user_can('edit_posts')){
			return $post_id;
		}
		
		//collect sanitized information
		$event_subtitle =sanitize_text_field($_POST['event_subtitle']);
		$event_start_date =sanitize_text_field($_POST['event_start_date']);
		$event_end_date = sanitize_text_field($_POST['event_end_date']);
		$event_start_time =sanitize_text_field($_POST['event_start_time']);
		$event_end_time = sanitize_text_field($_POST['event_end_time']);
		$event_location = sanitize_text_field(wpautop($_POST['event_location']));
		$event_price = sanitize_text_field($_POST['event_price']);
		
		//save post meta
		update_post_meta($post_id,'event_subtitle',$event_subtitle);
		update_post_meta($post_id,'event_start_date',$event_start_date);
		update_post_meta($post_id,'event_end_date',$event_end_date);
		update_post_meta($post_id,'event_start_time',$event_start_time);
		update_post_meta($post_id,'event_end_time',$event_end_time);
		update_post_meta($post_id,'event_location',$event_location);
		update_post_meta($post_id,'event_price', $event_price);
		
	}
	
	//display additional meta information for the content type
	//@hooked using 'display_additional_meta_data' in theme
	function display_additional_meta_data(){
		global $post, $post_type;
		
		//if we are on our custom post type
		if($post_type == $this->content_type_name){
			
			//collect information
			$event_subtitle = get_post_meta($post->ID,'event_subtitle', true);
			$event_start_date = get_post_meta($post->ID,'event_start_date', true);
			$event_end_date = get_post_meta($post->ID,'event_end_date', true);
			$event_start_time = get_post_meta($post->ID,'event_start_time', true);
			$event_end_time = get_post_meta($post->ID,'event_end_time', true);
			$event_location = get_post_meta($post->ID,'event_location', true);
			$event_price = get_post_meta($post->ID,'event_price', true);
			
			$html = '';
			$html .= '<h3><i>' . $event_subtitle . '</i></h3>';
			$html .= '<section class="additional-meta">';
			$html .= '<div class="meta"><strong>Start: </strong><span>' . $event_start_date . ' - ' . $event_start_time . '</span></div>';
			$html .= '<div class="meta"><strong>End: </strong><span>' . $event_end_date . ' - ' . $event_end_time . '</span></div>';
			$html .= '<div class="meta"><strong>Location: </strong><span>' . $event_location . '</span></div>';
			$html .= '<div class="meta"><strong>Price: </strong><span>' . $event_price . '</span></div>';
			
			$html .= '</section>';
			
			echo $html;
		}
		
	}
 }
 
 //create new object 
 $event_showcase = new event_showcase;


?>