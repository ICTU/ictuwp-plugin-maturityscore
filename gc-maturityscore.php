<?php
/*
 * GC_Maturity. 
 *
 * Plugin Name:         Gebruiker Centraal Volwassenheidsscore Plugin
 * Plugin URI:          https://github.com/ICTU/gc-maturityscore-plugin/
 * Description:         Plugin voor gebruikercentraal.nl waarmee extra functionaliteit mogelijk wordt voor enquetes en rapportages rondom digitale 'volwassenheid' van organisaties.
 * Version:             1.0.1
 * Version description: First version
 * Author:              Paul van Buuren
 * Author URI:          https://wbvb.nl
 * License:             GPL-2.0+
 *
 * Text Domain:         gcmaturity-translate
 * Domain Path:         /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // disable direct access
}

if ( ! class_exists( 'GC_MaturityPlugin' ) ) :

/**
 * Register the plugin.
 *
 * Display the administration panel, insert JavaScript etc.
 */
  class GC_MaturityPlugin {
  
      /**
       * @var string
       */
      public $version = '1.0.1';
  
  
      /**
       * @var GC_Maturity
       */
      public $gcmaturity = null;
  
  
      /**
       * Init
       */
      public static function init() {
  
          $gcmaturity_this = new self();
  
      }
  
      //========================================================================================================
  
      /**
       * Constructor
       */
      public function __construct() {
  
          $this->define_constants();
          $this->includes();
          $this->setup_actions();
          $this->setup_filters();
          $this->append_comboboxes();

      }
  
      //========================================================================================================
  
      /**
       * Define GC_Maturity constants
       */
      private function define_constants() {
  
        $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,strpos( $_SERVER["SERVER_PROTOCOL"],'/'))).'://';
  
        define( 'GCMS_VERSION',           $this->version );
        define( 'GCMS_FOLDER',            'gc-maturityscore' );
        define( 'GCMS_BASE_URL',          trailingslashit( plugins_url( GCMS_FOLDER ) ) );
        define( 'GCMS_ASSETS_URL',        trailingslashit( GCMS_BASE_URL . 'assets' ) );
        define( 'GCMS_MEDIAELEMENT_URL',  trailingslashit( GCMS_BASE_URL . 'mediaelement' ) );
        define( 'GCMS_PATH',              plugin_dir_path( __FILE__ ) );
        define( 'GCMS_QUESTION_CPT',      "enquetes" );
        define( 'GCMS_QUESTION_GROUP_CT', "gcms_custom_taxonomy" );
        define( 'GCMS_DEFAULT',           "default" );
        
        
        define( 'GCMS_QUESTION_PREFIX',   GCMS_QUESTION_CPT . '_pf' ); // prefix for cmb2 metadata fields
        define( 'GCMS_CMBS2_PREFIX',      GCMS_QUESTION_PREFIX . '_form_' ); // prefix for cmb2 metadata fields
        define( 'GCMS_RV_DO_DEBUG',       false );
        define( 'GCMS_RV_USE_CMB2',       true ); 
  
      }
  
      //========================================================================================================
  
      /**
       * All GC_Maturity classes
       */
      private function plugin_classes() {
  
          return array(
              'GC_MaturitySystemCheck'  => GCMS_PATH . 'inc/gc-maturity.systemcheck.class.php',
          );
  
      }
  
      //========================================================================================================
  
      /**
       * Load required classes
       */
      private function includes() {
      
        if ( GCMS_RV_USE_CMB2 ) {
          // load CMB2 functionality
          if ( ! defined( 'CMB2_LOADED' ) ) {
            // cmb2 NOT loaded
            if ( file_exists( dirname( __FILE__ ) . '/cmb2/init.php' ) ) {
              require_once dirname( __FILE__ ) . '/cmb2/init.php';
            }
            elseif ( file_exists( dirname( __FILE__ ) . '/CMB2/init.php' ) ) {
              require_once dirname( __FILE__ ) . '/CMB2/init.php';
            }
          }
        }

        $autoload_is_disabled = defined( 'GCMS_AUTOLOAD_CLASSES' ) && GCMS_AUTOLOAD_CLASSES === false;
        
        if ( function_exists( "spl_autoload_register" ) && ! ( $autoload_is_disabled ) ) {
          
          // >= PHP 5.2 - Use auto loading
          if ( function_exists( "__autoload" ) ) {
            spl_autoload_register( "__autoload" );
          }
          spl_autoload_register( array( $this, 'autoload' ) );
          
        } 
        else {
          // < PHP5.2 - Require all classes
          foreach ( $this->plugin_classes() as $id => $path ) {
            if ( is_readable( $path ) && ! class_exists( $id ) ) {
              require_once( $path );
            }
          }
          
        }
      
      }
  
      //========================================================================================================
  
      /**
       * filter for when the CPT is previewed
       */
      public function content_filter_for_preview($content = '') {
        global $post;
      
        if ( ( GCMS_QUESTION_CPT == get_post_type() ) && ( is_single() ) ) {
          // lets go
          return $content . $this->gcms_display_questionary( $post->ID );
        }
        else {
          return $content;
        }
        
      }
  
      //========================================================================================================
  
      /**
       * Autoload GC_Maturity classes to reduce memory consumption
       */
      public function autoload( $class ) {
  
          $classes = $this->plugin_classes();
  
          $class_name = strtolower( $class );
  
          if ( isset( $classes[$class_name] ) && is_readable( $classes[$class_name] ) ) {
              require_once( $classes[$class_name] );
          }
  
      }
  
      //========================================================================================================
  
      /**
       * Hook GC_Maturity into WordPress
       */
      private function setup_actions() {
  
          add_action( 'init',           array( $this, 'register_post_type' ) );
          add_action( 'init',           array( $this, 'register_post_type' ) );
          add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
  
          // add a page temlate name
          $this->templates          = array();
          $this->templatefile   		= 'stelselcatalogus-template.php';
  
      		// add the page template to the templates list
      		add_filter( 'theme_page_templates', array( $this, 'gcms_add_page_templates' ) );
      		
      		// activate the page filters
      		add_action( 'template_redirect',    array( $this, 'gcms_use_page_template' )  );

      }
  
      //========================================================================================================
  
      /**
       * Hook GC_Maturity into WordPress
       */
      private function setup_filters() {

        	// content filter
          add_filter( 'the_content', array( $this, 'content_filter_for_preview' ) );

      }
  
      //========================================================================================================
  
      /**
       * Register post type
       */
      public function gcms_register_forms() {
        
      }
  
      //========================================================================================================
  
      /**
       * Register post type
       */
      public function register_post_type() {
  
      	$labels = array(
      		"name"                  => _x( "Enquête", "labels", "gcmaturity-translate" ),
      		"singular_name"         => _x( "Enquête", "labels", "gcmaturity-translate" ),
      		"menu_name"             => _x( "Enquêtes", "labels", "gcmaturity-translate" ),
      		"all_items"             => _x( "Alle enquêtes", "labels", "gcmaturity-translate" ),
      		"add_new"               => _x( "Enquête toevoegen", "labels", "gcmaturity-translate" ),
      		"add_new_item"          => _x( "Nieuwe enquête toevoegen", "labels", "gcmaturity-translate" ),
      		"edit"                  => _x( "Bewerken?", "labels", "gcmaturity-translate" ),
      		"edit_item"             => _x( "Enquête bewerken", "labels", "gcmaturity-translate" ),
      		"new_item"              => _x( "Enquête toevoegen", "labels", "gcmaturity-translate" ),
      		"view"                  => _x( "Toon", "labels", "gcmaturity-translate" ),
      		"view_item"             => _x( "Enquête bekijken", "labels", "gcmaturity-translate" ),
      		"search_items"          => _x( "Enquête zoeken", "labels", "gcmaturity-translate" ),
      		"not_found"             => _x( "Geen enquêtes beschikbaar", "labels", "gcmaturity-translate" ),
      		"not_found_in_trash"    => _x( "Geen enquêtes in prullenbak", "labels", "gcmaturity-translate" ),
      		"parent"                => _x( "Parent", "labels", "gcmaturity-translate" ),
      		);
      
      	$args = array(
          "label"                 => _x( "Enquêtes", "labels", "gcmaturity-translate" ),
          "labels"                => $labels,
          "description"           => "",
          "public"                => true,
          "publicly_queryable"    => true,
          "show_ui"               => true,
          "show_in_rest"          => false,
          "rest_base"             => "",
          "has_archive"           => false,
          "show_in_menu"          => true,
          "exclude_from_search"   => false,
          "capability_type"       => "post",
          "map_meta_cap"          => true,
          "hierarchical"          => false,
          "rewrite"               => array( "slug" => GCMS_QUESTION_CPT, "with_front" => true ),
          "query_var"             => true,
      		"supports"              => array( "title" ),					
      		);
      		
      	register_post_type( GCMS_QUESTION_CPT, $args );
      	
      	flush_rewrite_rules();
  
      }
  
      //========================================================================================================
  
      /**
       * Initialise translations
       */
      public function load_plugin_textdomain() {

          load_plugin_textdomain( "gcmaturity-translate", false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

      }
  
      //========================================================================================================
  
      /**
       * Add the help tab to the screen.
       */
      public function help_tab() {
  
        $screen = get_current_screen();
  
        // documentation tab
        $screen->add_help_tab( array(
          'id'      => 'documentation',
          'title'   => __( 'Documentation', "gcmaturity-translate" ),
          'content' => "<p><a href='https://github.com/ICTU/gc-maturityscore-plugin/documentation/' target='blank'>" . __( 'GC Maturity documentation', "gcmaturity-translate" ) . "</a></p>",
          )
        );
      }
  
      //========================================================================================================
  
      /**
       * Register frontend styles
       */
      public function register_frontend_style_script() {
  
        if ( !is_admin() ) {

          $infooter = false;
          
          // don't add to any admin pages
          wp_enqueue_script( 'gcms-action-js', GCMS_ASSETS_URL . 'assets/js/min/functions-min.js', array( 'jquery' ), GCMS_VERSION, $infooter );
          wp_enqueue_style( 'gc-maturityscore-frontend', GCMS_ASSETS_URL . 'css/gc-maturityscore.css', array(), GCMS_VERSION, $infooter );
              
        }
  
      }
  
      //========================================================================================================
  
      /**
       * Register admin-side styles
       */
      public function register_admin_styles() {
  
          wp_enqueue_style( 'gc-maturityscore-admin', GCMS_ASSETS_URL . 'css/gc-maturityscore-admin.css', false, GCMS_VERSION );
  
          do_action( 'rijksvideo_register_admin_styles' );
  
      }
  
      //========================================================================================================
  
      /**
       * Register admin JavaScript
       */
      public function register_admin_scripts() {
  
          // media library dependencies
          wp_enqueue_media();
  
          // plugin dependencies
          wp_enqueue_script( 'jquery-ui-core', array( 'jquery' ) );
  
          $this->localize_admin_scripts();
  
          do_action( 'gcms_register_admin_scripts' );
  
      }
  
      //========================================================================================================
  
      /**
       * Localise admin script
       */
      public function localize_admin_scripts() {
  
          wp_localize_script( 'gcms-admin-script', 'gcms', array(
                  'url'               => __( "URL", "gcmaturity-translate" ),
                  'caption'           => __( "Caption", "gcmaturity-translate" ),
                  'new_window'        => __( "New Window", "gcmaturity-translate" ),
                  'confirm'           => __( "Weet je het zeker?", "gcmaturity-translate" ),
                  'ajaxurl'           => admin_url( 'admin-ajax.php' ),
                  'resize_nonce'      => wp_create_nonce( 'gcms_resize' ),
                  'iframeurl'         => admin_url( 'admin-post.php?action=gcms_preview' ),
              )
          );
  
      }
  
      //========================================================================================================
  
      /**
       * Output the HTML
       */
      public function gcms_display_questionary( $postid ) {
        
        $returnstring     = '';
        $formfields_data  = $this->gcms_read_formfields();
        $metadata         = get_post_meta( $postid );    	
        $waarden          = array();

        if ( $formfields_data && $metadata ) {
          foreach ( $formfields_data as $key => $value) {
            foreach ( $value->vragen as $key2 => $value2 ) {

          		$keyvalue = GCMS_QUESTION_PREFIX . $value2->key;
          		$storedvalue = $metadata[$keyvalue][0];
              if ( intval( $storedvalue ) > 0 ) {
                $returnstring .= $keyvalue . ' => ' . $storedvalue . '<br>';

                $waarden[ $value->naam ][] = $storedvalue;
                
              }
            }
          }
        }
        
        if ( $waarden ) {
          foreach( $waarden as $key => $value ){        
            $thesum = array_sum( $value );
            $average = $thesum / count( $value );
            $returnstring .= 'gemiddelde voor ' . $key . ': ' . $average . '<br>';
          }
        }

//        $returnstring .= 'gcms_display_questionary<br>';
        return $returnstring;
      
      }
  
      //========================================================================================================
  
      private function get_stored_values( $postid, $postkey, $defaultvalue = '' ) {
  
        if ( GCMS_RV_DO_DEBUG ) {
          $returnstring = $defaultvalue;
        }
        else {
          $returnstring = '';
        }
  
        $temp = get_post_meta( $postid, $postkey, true );
        if ( $temp ) {
          $returnstring = $temp;
        }
        
        return $returnstring;
      }
  
      //========================================================================================================
      
      public function getuniqueid( $video_id ) {
        
        global $post;
        
        return '_video' . $video_id . '_post' . $post->ID;    
      
      }

      //========================================================================================================

      public function append_comboboxes() {
      
        if ( GCMS_RV_USE_CMB2 ) {
  
          if ( ! defined( 'CMB2_LOADED' ) ) {
            return false;
            die( ' CMB2_LOADED not loaded ' );
            // cmb2 NOT loaded
          }

          add_shortcode( 'cmb_frontend_form', 'gcms_register_shortcode' );

          add_action( 'cmb2_init', array( $this, 'gcms_register_frontend_form' ) );

          add_action( 'cmb2_after_init', 'yourprefix_handle_frontend_new_post_form_submission' );

        }  // GCMS_RV_USE_CMB2
  
    }    

    //====================================================================================================

    /**
     * Read a JSON file that contains the form definitions
     */
    public function gcms_read_formfields() {

      $formfields_location = GCMS_BASE_URL . 'assets/antwoorden-vragen.json';
      
      $formfields_json = wp_remote_get( $formfields_location );
  
      if( is_wp_error( $formfields_json ) ) {
          return false; // Bail early
      }
   
       // Retrieve the data
      $formfields_body = wp_remote_retrieve_body( $formfields_json );
      $formfields_data = json_decode( $formfields_body );
      
      return $formfields_data;
    
    }    

    //====================================================================================================

    /**
     * Register the form and fields for our front-end submission form
     */
    public function gcms_register_frontend_form() {
    
    	$cmb = new_cmb2_box( array(
    		'id'            => 'front-end-post-form',
    		'title'         => __( 'Vragen en antwoorden', "gcmaturity-translate" ),
    		'object_types'  => array( 'post' ),
    		'hookup'        => false,
    		'save_fields'   => false,
        'cmb_styles'    => false, // false to disable the CMB stylesheet
    	) );
    
    	$cmb->add_field( array(
    		'name'    => __( 'New Post Title', "gcmaturity-translate" ),
    		'id'      => 'submitted_post_title',
    		'type'    => 'text',
    		'default' => ! empty( $_POST['submitted_post_title'] )
    			? $_POST['submitted_post_title']
    			: __( 'New Post', "gcmaturity-translate" ),
    	) );
    
    	$cmb->add_field( array(
    		'name'    => __( 'Content', "gcmaturity-translate" ),
    		'id'      => 'submitted_post_content',
    		'type'    => 'text',
    		'default' => ! empty( $_POST['submitted_post_content'] )
    			? $_POST['submitted_post_content']
    			: __( 'Nieuwe content', "gcmaturity-translate" ),
    	) );

      $formfields_data = $this->gcms_read_formfields();

      if ( $formfields_data ) {

        $counter_top  = 0;
        $counter_loop = 0;
      
        foreach ( $formfields_data as $key => $value) {
          $counter_top++;
          $counter_loop = 0;

        	$cmb->add_field( array(
        		'id'            => GCMS_CMBS2_PREFIX . $counter_top,
        		'title'         => $value->naam,
        		'object_types'  => array( GCMS_QUESTION_CPT ), // Post type
        	) );

          foreach ( $value->vragen as $key2 => $value2 ) {
            $counter_loop++;


            $options = array();
            $label = 'label_' . $counter_top . '_' . $counter_loop;

            $options[ GCMS_DEFAULT ] = _x( 'Geen antwoord', 'default answer', "gcmaturity-translate" );

            foreach ( $value2->antwoorden as $antwoord ) {
              $options[ $antwoord->value ] = $antwoord->label;
            }

          	$cmb->add_field( array(
          		'name'    => $value2->vraag,
          		'id'      => GCMS_QUESTION_PREFIX . $value2->key,
          		'type'    => 'radio',
              'options' => $options,
              'default' => GCMS_DEFAULT,
          	) );
            
          }            
        }
      }
    
    }  
        

    //====================================================================================================

      /**
       * Check our WordPress installation is compatible with GC_Maturity
       */
      public function do_system_check() {
  
          $systemCheck = new GC_MaturitySystemCheck();
          $systemCheck->check();
  
      }

    //====================================================================================================

    /**
    * Hides the custom post template for pages on WordPress 4.6 and older
    *
    * @param array $post_templates Array of page templates. Keys are filenames, values are translated names.
    * @return array Expanded array of page templates.
    */
    function gcms_add_page_templates( $post_templates ) {
    
      $post_templates[$this->templatefile]  		= _x( 'Volwassenheidsscore-pagina', "naam template", "gcmaturity-translate" );    
      return $post_templates;
    
    }

    //====================================================================================================

    /**
    * Modify page content if using a specific page template.
    */
    public function gcms_use_page_template() {
      
      global $post;
      
      $page_template = get_post_meta( get_the_ID(), '_wp_page_template', true );
      $page_template  = get_post_meta( get_the_ID(), '_wp_page_template', true );
      
      if ( $this->templatefile == $page_template ) {
  
        add_action( 'genesis_entry_content',  'gcms_do_frontend_form_submission_shortcode_echo', 15 );

        $this->register_frontend_style_script();
        
        //* Force full-width-content layout
        add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );
      
      }
    
    }
    

  
  }



endif;

//========================================================================================================

add_action( 'plugins_loaded', array( 'GC_MaturityPlugin', 'init' ), 10 );

//========================================================================================================

/**
 * Handle the cmb_frontend_form shortcode
 *
 * @param  array  $atts Array of shortcode attributes
 * @return string       Form html
 */

function gcms_register_shortcode( $atts = array() ) {

	// Get CMB2 metabox object
	$cmb = yourprefix_frontend_cmb2_get();

	// Get $cmb object_types
	$post_types = $cmb->prop( 'object_types' );

	// Current user
	$user_id = get_current_user_id();

	// Parse attributes
	$atts = shortcode_atts( array(
		'post_author' => $user_id ? $user_id : 1, // Current user, or admin
		'post_status' => 'publish',
		'post_type'   => GCMS_QUESTION_CPT, // Only use first object_type in array
	), $atts, 'cmb_frontend_form' );

	/*
	 * Let's add these attributes as hidden fields to our cmb form
	 * so that they will be passed through to our form submission
	 */
	foreach ( $atts as $key => $value ) {
		$cmb->add_hidden_field( array(
			'field_args'  => array(
				'id'    => "atts[$key]",
				'type'  => 'hidden',
				'default' => $value,
			),
		) );
	}

	// Initiate our output variable
	$output = '';

	// Get any submission errors
	if ( ( $error = $cmb->prop( 'submission_error' ) ) && is_wp_error( $error ) ) {
		// If there was an error with the submission, add it to our ouput.
		$output .= '<h3>' . sprintf( __( 'There was an error in the submission: %s', "gcmaturity-translate" ), '<strong>'. $error->get_error_message() .'</strong>' ) . '</h3>';
	}

	// If the post was submitted successfully, notify the user.
	if ( isset( $_GET['post_submitted'] ) && ( $post = get_post( absint( $_GET['post_submitted'] ) ) ) ) {

		// Get submitter's name
		$name = get_post_meta( $post->ID, 'submitted_author_name', 1 );
		$name = $name ? ' '. $name : '';

		// Add notice of submission to our output
		$output .= '<h3>' . sprintf( __( 'Thank you%s, your new post has been submitted and is pending review by a site administrator.', "gcmaturity-translate" ), esc_html( $name ) ) . '</h3>';
	}

	// Get our form
	$output .= cmb2_get_metabox_form( $cmb, 'fake-oject-id', array( 'save_button' => __( 'Submit Post', "gcmaturity-translate" ) ) );

	return $output;

}

//========================================================================================================

/**
 * Sets the front-end-post-form field values if form has already been submitted.
 *
 * @return string
 */
function yourprefix_maybe_set_default_from_posted_values( $args, $field ) {

	if ( ! empty( $_POST[ $field->id() ] ) ) {
		return $_POST[ $field->id() ];
	}

	return '';
}

//========================================================================================================
  
/**
 * Gets the front-end-post-form cmb instance
 *
 * @return CMB2 object
 */
function yourprefix_frontend_cmb2_get() {

	// Use ID of metabox in gcms_register_frontend_form
	$metabox_id = 'front-end-post-form';

	// Post/object ID is not applicable since we're using this form for submission
	$object_id  = 'fake-oject-id';

	// Get CMB2 metabox object
	return cmb2_get_metabox( $metabox_id, $object_id );

}

//========================================================================================================
  
/**
 * Handles form submission on save. Redirects if save is successful, otherwise sets an error message as a cmb property
 *
 * @return void
 */
function yourprefix_handle_frontend_new_post_form_submission() {

	// If no form submission, bail
	if ( empty( $_POST ) || ! isset( $_POST['submit-cmb'], $_POST['object_id'] ) ) {
		return false;
	}

	// Get CMB2 metabox object
	$cmb = yourprefix_frontend_cmb2_get();

	$post_data = array();

	// Get our shortcode attributes and set them as our initial post_data args
	if ( isset( $_POST['atts'] ) ) {
		foreach ( (array) $_POST['atts'] as $key => $value ) {
			$post_data[ $key ] = sanitize_text_field( $value );
		}
		unset( $_POST['atts'] );
	}

	// Check security nonce
	if ( ! isset( $_POST[ $cmb->nonce() ] ) || ! wp_verify_nonce( $_POST[ $cmb->nonce() ], $cmb->nonce() ) ) {
		return $cmb->prop( 'submission_error', new WP_Error( 'security_fail', __( 'Security check failed.' ) ) );
	}

	// Check title submitted
	if ( empty( $_POST['submitted_post_title'] ) ) {
		return $cmb->prop( 'submission_error', new WP_Error( 'post_data_missing', __( 'New post requires a title.' ) ) );
	}

  	// And that the title is not the default title
  $compare1 = $cmb->get_field( 'submitted_post_title' )->default();
  $compare2 = $_POST['submitted_post_title'] . 'asf';
	
	if ( $compare1 == $compare2 ) {
		return $cmb->prop( 'submission_error', new WP_Error( 'post_data_missing', __( 'Please enter a new title. (' . $compare1 . ' / ' . $compare2. ')' ) ) );
	}

	/**
	 * Fetch sanitized values
	 */
	$sanitized_values = $cmb->get_sanitized_values( $_POST );

//dovardump( $sanitized_values );

	// Set our post data arguments
	$post_data['post_title']   = $sanitized_values['submitted_post_title'];
	unset( $sanitized_values['submitted_post_title'] );
	$post_data['post_content'] = $sanitized_values['submitted_post_content'];
	unset( $sanitized_values['submitted_post_content'] );

//dovardump( $post_data );

	// Create the new post
	$new_submission_id = wp_insert_post( $post_data, true );

	// If we hit a snag, update the user
	if ( is_wp_error( $new_submission_id ) ) {
		return $cmb->prop( 'submission_error', $new_submission_id );
	}

	$cmb->save_fields( $new_submission_id, 'post', $sanitized_values );

	foreach ( $sanitized_values as $key => $value ) {
		update_post_meta( $new_submission_id, $key, $value );
	}


	/**
	 * Other than post_type and post_status, we want
	 * our uploaded attachment post to have the same post-data
	 */
	unset( $post_data['post_type'] );
	unset( $post_data['post_status'] );

	/*
	 * Redirect back to the form page with a query variable with the new post ID.
	 * This will help double-submissions with browser refreshes
	 */
//	wp_redirect( esc_url_raw( add_query_arg( 'post_submitted', $new_submission_id ) ) );
	wp_redirect( get_permalink( $new_submission_id ) );
	
	exit;
}

//========================================================================================================

if (! function_exists( 'dovardump' ) ) {
  
  function dovardump($data, $context = '', $echo = true ) {
    if ( WP_DEBUG ) {
      $contextstring  = '';
      $startstring    = '<div class="debug-context-info">';
      $endtring       = '</div>';
      
      if ( $context ) {
        $contextstring = '<p>Vardump ' . $context . '</p>';        
      }
      
      if ( $echo ) {
        
        echo $startstring . '<hr>';
        echo $contextstring;        
        echo '<pre>';
        print_r($data);
        echo '</pre><hr>' . $endtring;
      }
      else {
        return '<hr>' . $contextstring . '<pre>' . print_r($data, true) . '</pre><hr>';
      }
    }        
  }
}


//========================================================================================================

add_action( 'wp_enqueue_scripts', 'gcms_deregister_styles', 100 );

/**
 * Hook GC_Maturity into WordPress
 */
function gcms_deregister_styles() {

    wp_dequeue_style('cmb2-styles');
    wp_dequeue_style('cmb2-styles-css');

}

//========================================================================================================

// Register style sheet.
add_action( 'wp_enqueue_scripts', 'register_plugin_styles' );

/**
 * Hook GC_Maturity into WordPress
 */
function register_plugin_styles() {

  if ( !is_admin() ) {

    $infooter = false;
    
    // don't add to any admin pages
    wp_enqueue_script( 'gcms-action-js', GCMS_ASSETS_URL . 'assets/js/min/functions-min.js', array( 'jquery' ), GCMS_VERSION, $infooter );
    wp_enqueue_style( 'gc-maturityscore-frontend', GCMS_ASSETS_URL . 'css/gc-maturityscore.css', array(), GCMS_VERSION, $infooter );
        
  }

}

//========================================================================================================
