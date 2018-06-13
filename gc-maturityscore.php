<?php
/*
 * GC_Maturity. 
 *
 * Plugin Name:         Gebruiker Centraal Volwassenheidsscore Plugin
 * Plugin URI:          https://github.com/ICTU/gc-maturityscore-plugin/
 * Description:         Plugin voor gebruikercentraal.nl waarmee extra functionaliteit mogelijk wordt voor enquetes en rapportages rondom digitale 'volwassenheid' van organisaties.
 * Version:             1.0.0
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
    public $version = '1.0.0';


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
      define( 'GCMS_QUESTION_CPT',      "vraag" );
      define( 'GCMS_QUESTION_GROUP_CT', "gcms_custom_taxonomy" );
      
      define( 'GCMS_QUESTION_PREFIX',   GCMS_QUESTION_CPT . '_pf_' ); // prefix for cmb2 metadata fields
      define( 'GCMS_RV_DO_DEBUG',       false );
      define( 'GCMS_RV_USE_CMB2',       true ); 

    }


    /**
     * All GC_Maturity classes
     */
    private function plugin_classes() {

        return array(
            'GC_MaturitySystemCheck'  => GCMS_PATH . 'inc/gc-maturity.systemcheck.class.php',
        );

    }


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







    /**
     * filter for when the CPT is previewed
     */
    public function content_filter_for_preview($content = '') {
      global $post;
    
      if ( ( GCMS_QUESTION_CPT == get_post_type() ) && ( is_single() ) ) {

        // lets go
        $this->register_frontend_style_script();
        return $content . $this->gcms_makevideo( $post->ID );
      }
      else {
        return $content;
      }
      
    }


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



    /**
     * Hook GC_Maturity into WordPress
     */
    private function setup_actions() {

        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
        add_action( 'admin_footer', array( $this, 'admin_footer' ), 11 );


    }


    /**
     * Hook GC_Maturity into WordPress
     */
    private function setup_filters() {

      	// content filter
        add_filter( 'the_content', array( $this, 'content_filter_for_preview' ) );


    }



    /**
     * Register post type
     */
    public function register_post_type() {

    	$labels = array(
    		"name"                  => _x( "Questionary", "labels", "gcmaturity-translate" ),
    		"singular_name"         => _x( "Questionaries", "labels", "gcmaturity-translate" ),
    		"menu_name"             => _x( "Questionaries", "labels", "gcmaturity-translate" ),
    		"all_items"             => _x( "All questionaries", "labels", "gcmaturity-translate" ),
    		"add_new"               => _x( "Add new", "labels", "gcmaturity-translate" ),
    		"add_new_item"          => _x( "Add new item", "labels", "gcmaturity-translate" ),
    		"edit"                  => _x( "Edit?", "labels", "gcmaturity-translate" ),
    		"edit_item"             => _x( "Edit questionaries", "labels", "gcmaturity-translate" ),
    		"new_item"              => _x( "New questionaries", "labels", "gcmaturity-translate" ),
    		"view"                  => _x( "Show", "labels", "gcmaturity-translate" ),
    		"view_item"             => _x( "View questionaries", "labels", "gcmaturity-translate" ),
    		"search_items"          => _x( "Search questionaries", "labels", "gcmaturity-translate" ),
    		"not_found"             => _x( "No questionaries available", "labels", "gcmaturity-translate" ),
    		"not_found_in_trash"    => _x( "No questionaries found in the trash", "labels", "gcmaturity-translate" ),
    		"parent"                => _x( "Parent", "labels", "gcmaturity-translate" ),
    		);
    
    	$args = array(
        "label"                 => _x( "Questionaries", "labels", "gcmaturity-translate" ),
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
    		"supports"              => array( "title", "editor", "thumbnail", "excerpt" ),					
    		);
    	register_post_type( GCMS_QUESTION_CPT, $args );
    	
    	flush_rewrite_rules();

    }



    /**
     * Initialise translations
     */
    public function load_plugin_textdomain() {

        load_plugin_textdomain( "gcmaturity-translate", false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );


    }


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
    


    /**
     * Register admin-side styles
     */
    public function register_admin_styles() {

        wp_enqueue_style( 'gc-maturityscore-admin', GCMS_ASSETS_URL . 'css/gc-maturityscore-admin.css', false, GCMS_VERSION );

        do_action( 'gc-maturityscore-admin' );

    }


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


    /**
     * Localise admin script
     */
    public function localize_admin_scripts() {

        wp_localize_script( 'gcms-admin-script', 'rijksvideo', array(
                'url'               => __( "URL", "gcmaturity-translate" ),
                'caption'           => __( "Caption", "gcmaturity-translate" ),
                'new_window'        => __( "New Window", "gcmaturity-translate" ),
                'confirm'           => __( "Weet je het zeker?", "gcmaturity-translate" ),
                'ajaxurl'           => admin_url( 'admin-ajax.php' ),
                'resize_nonce'      => wp_create_nonce( 'gcms_resize' ),
                'add_video_nonce'   => wp_create_nonce( 'gcms_add_video' ),
                'change_video_nonce'=> wp_create_nonce( 'gcms_change_video' ),
                'iframeurl'         => admin_url( 'admin-post.php?action=gcms_preview' ),
            )
        );

    }

    //========================================================================================================
    /**
     * Output the HTML
     */
    public function gcms_makevideo($postid) {
      $videoplayer_width              = '500';
      $videoplayer_height             = '412';
      $video_id                       = 'movie-' . $postid;
      
      $videoplayer_aria_id            = 'mep_7';
      $videoplayer_date               = get_the_date();
      
      $videoplayer_title              = _x( 'Video Player', 'GC Maturity', "gcmaturity-translate" );
      
      
      $uniqueid                       = $this->getuniqueid( $postid );    
      
      $gcms_video_duur               = $this->get_stored_values( $postid, GCMS_QUESTION_PREFIX . 'video_time', '-' );
      
      $returnstring = 'gcms_makevideo<br>';
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
      else {
      }
    
      add_action( 'cmb2_admin_init', 'gcms_register_metabox_questionary' );
    
      /**
       * Hook in and add a demo metabox. Can only happen on the 'cmb2_admin_init' or 'cmb2_init' hook.
       */
      function gcms_register_metabox_questionary() {
        
        global $vragenlijst;

      	/**
      	 * Metabox with fields for the video
      	 */
      	$cmb2_metafields = new_cmb2_box( array(
      		'id'            => GCMS_QUESTION_PREFIX . 'metabox',
      		'title'         => __( 'Vragen en antwoorden', "gcmaturity-translate" ),
      		'object_types'  => array( GCMS_QUESTION_CPT ), // Post type
      	) );
    
      	/**
      	 * The fields
      	 */

    
      	$cmb2_metafields->add_field( array(
      		'name' => __( 'URL van thumbnail', "gcmaturity-translate" ),
      		'desc' => __( 'Als caption van de video wordt eerst gekeken of je een uitgelichte afbeelding hebt toegevoegd aan deze video. Als die er niet is, kun je hier de URL van het bijbehorende plaatje invoeren.', "gcmaturity-translate" ),
      		'id'   => GCMS_QUESTION_PREFIX . 'url_video_thumb',
      		'type' => 'text_url',
      		'protocols' => array('http', 'https', '//'), // Array of allowed protocols
      	) );
      	 
      	$cmb2_metafields->add_field( array(
      		'name' => __( 'Lengte van de video (*)', "gcmaturity-translate" ),
      		'desc' => __( '(verplicht) formaat: uu:mm:ss', "gcmaturity-translate" ),
      		'id'   => GCMS_QUESTION_PREFIX . 'video_time',
      		'type' => 'text_small',
          'attributes' => array(
              'data-validation' => 'required',
              'required' => 'required'
          ),      		
      	) );

      	$cmb2_metafields->add_field( array(
      		'name' => __( 'URL van MP4-bestand', "gcmaturity-translate" ),
      		'desc' => __( 'Apple Quicktime-bestand. Eindigt vaak op .mp4', "gcmaturity-translate" ),
      		'id'   => GCMS_QUESTION_PREFIX . 'mp4_url',
      		'type' => 'text_url',
      		'protocols' => array('http', 'https', '//'), // Array of allowed protocols
      	) );
      
      	$cmb2_metafields->add_field( array(
      		'name' => __( 'Bestandsgrootte van MP4-bestand', "gcmaturity-translate" ),
      		'id'   => GCMS_QUESTION_PREFIX . 'mp4_filesize',
      		'type' => 'text_small',
      	) );
    
      	$cmb2_metafields->add_field( array(
      		'name' => __( 'URL van Hi-res MP4-bestand', "gcmaturity-translate" ),
      		'desc' => __( 'Versie van Quicktime-bestand in hoge resolutie.', "gcmaturity-translate" ),
      		'id'   => GCMS_QUESTION_PREFIX . 'mp4_url_hires',
      		'type' => 'text_url',
      		'protocols' => array('http', 'https', '//'), // Array of allowed protocols
      	) );
      
      	$cmb2_metafields->add_field( array(
      		'name' => __( 'Bestandsgrootte van hi-res MP4-bestand', "gcmaturity-translate" ),
      		'id'   => GCMS_QUESTION_PREFIX . 'mp4_filesize_hires',
      		'type' => 'text_small',
      	) );
    
      	$cmb2_metafields->add_field( array(
      		'name' => __( 'Url voor 3GP formaat', "gcmaturity-translate" ),
      		'id'   => GCMS_QUESTION_PREFIX . '3gp_url',
      		'type' => 'text_url',
      		'protocols' => array('http', 'https', '//'), // Array of allowed protocols
      	) );
      
      	$cmb2_metafields->add_field( array(
      		'name' => __( 'Bestandsgrootte van 3GP-bestand', "gcmaturity-translate" ),
      		'id'   => GCMS_QUESTION_PREFIX . '3gp_filesize',
      		'type' => 'text_small',
      	) );
      
      	$cmb2_metafields->add_field( array(
      		'name' => __( 'URL van ondertitel (*)', "gcmaturity-translate" ),
      		'desc' => __( '(verplicht veld) Dit is meestal een bestand dat eindigt op .srt', "gcmaturity-translate" ),
      		'id'   => GCMS_QUESTION_PREFIX . 'url_transcript_file',
      		'type' => 'text_url',
      		'protocols' => array('http', 'https', '//'), // Array of allowed protocols
          'attributes' => array(
              'data-validation' => 'required',
              'required' => 'required'
          ),      		
      	) );
      
      	$cmb2_metafields->add_field( array(
      		'name' => __( 'Volledige transcriptie', "gcmaturity-translate" ),
      		'id'   => GCMS_QUESTION_PREFIX . 'transcriptvlak',
      		'type' => 'textarea',
      	) );
    
      	$cmb2_metafields->add_field( array(
      		'name' => __( 'URL van audio-track', "gcmaturity-translate" ),
      		'desc' => __( 'Dit is meestal een bestand dat eindigt op .mp3', "gcmaturity-translate" ),
      		'id'   => GCMS_QUESTION_PREFIX . 'audio_url',
      		'type' => 'text_url',
      		'protocols' => array('http', 'https', '//'), // Array of allowed protocols
      	) );

      	$cmb2_metafields->add_field( array(
      		'name' => __( 'Bestandsgrootte van audio-track', "gcmaturity-translate" ),
      		'id'   => GCMS_QUESTION_PREFIX . 'mp3_filesize',
      		'type' => 'text_small',
      	) );
      
    
    
      	$cmb2_metafields->add_field( array(
      		'name' => __( 'URL van FLV-bestand', "gcmaturity-translate" ),
      		'desc' => __( 'Eindigt op .flv', "gcmaturity-translate" ),
      		'id'   => GCMS_QUESTION_PREFIX . 'flv_url',
      		'type' => 'text_url',
      		'protocols' => array('http', 'https', '//'), // Array of allowed protocols
      	) );
      
      	$cmb2_metafields->add_field( array(
      		'name' => __( 'Bestandsgrootte van FLV-bestand', "gcmaturity-translate" ),
      		'id'   => GCMS_QUESTION_PREFIX . 'flv_filesize',
      		'type' => 'text_small',
      	) );
    
      	$cmb2_metafields->add_field( array(
      		'name' => __( 'URL van WMV-bestand', "gcmaturity-translate" ),
      		'desc' => __( 'Windows Media File. Eindigt vaak op .wmv', "gcmaturity-translate" ),
      		'id'   => GCMS_QUESTION_PREFIX . 'wmv_url',
      		'type' => 'text_url',
      		'protocols' => array('http', 'https', '//'), // Array of allowed protocols
      	) );
      
      	$cmb2_metafields->add_field( array(
      		'name' => __( 'Bestandsgrootte van WMV-bestand', "gcmaturity-translate" ),
      		'id'   => GCMS_QUESTION_PREFIX . 'filesize_wmv',
      		'type' => 'text_small',
      	) );

        require_once dirname( __FILE__ ) . '/inc/cmb2-check-required-fields.php';



      }
    
    
    }  // GCMS_RV_USE_CMB2
    else {
      if( function_exists('acf_add_local_field_group') ):
      
      acf_add_local_field_group(array (
      	'key' => 'group_57ea177ac9849',
      	'title' => 'Metadata voor ' . GCMS_QUESTION_CPT,
      	'fields' => array (
      		array (
      			'key' => 'field_57ea1788c6162',
      			'label' => 'Lengte van de video',
      			'name' => GCMS_QUESTION_PREFIX . 'video_time',
      			'type' => 'text',
      			'instructions' => '',
      			'required' => 0,
      			'conditional_logic' => 0,
      			'wrapper' => array (
      				'width' => '',
      				'class' => '',
      				'id' => '',
      			),
      			'default_value' => '',
      			'placeholder' => '',
      			'prepend' => '',
      			'append' => '',
      			'maxlength' => '',
      		),
      		array (
      			'key' => 'field_57ea17bd1c807',
      			'label' => 'Thumbnail URL',
      			'name' => GCMS_QUESTION_PREFIX . 'url_video_thumb',
      			'type' => 'url',
      			'instructions' => '',
      			'required' => 0,
      			'conditional_logic' => 0,
      			'wrapper' => array (
      				'width' => '',
      				'class' => '',
      				'id' => '',
      			),
      			'default_value' => '',
      			'placeholder' => '',
      		),
      		array (
      			'key' => 'field_57ea17e81c808',
      			'label' => 'Ondertitel URL',
      			'name' => GCMS_QUESTION_PREFIX . 'url_transcript_file',
      			'type' => 'url',
      			'instructions' => '',
      			'required' => 0,
      			'conditional_logic' => 0,
      			'wrapper' => array (
      				'width' => '',
      				'class' => '',
      				'id' => '',
      			),
      			'default_value' => '',
      			'placeholder' => '',
      		),
      		array (
      			'key' => 'field_57ea18001c809',
      			'label' => 'Transcript',
      			'name' => GCMS_QUESTION_PREFIX . 'transcriptvlak',
      			'type' => 'textarea',
      			'instructions' => '',
      			'required' => 0,
      			'conditional_logic' => 0,
      			'wrapper' => array (
      				'width' => '',
      				'class' => '',
      				'id' => '',
      			),
      			'default_value' => '',
      			'placeholder' => '',
      			'maxlength' => '',
      			'rows' => '',
      			'new_lines' => 'wpautop',
      		),
      		array (
      			'key' => 'field_57ea18101c80a',
      			'label' => 'Audio URL',
      			'name' => GCMS_QUESTION_PREFIX . 'audio_url',
      			'type' => 'url',
      			'instructions' => '',
      			'required' => 0,
      			'conditional_logic' => 0,
      			'wrapper' => array (
      				'width' => '',
      				'class' => '',
      				'id' => '',
      			),
      			'default_value' => '',
      			'placeholder' => '',
      		),
      		array (
      			'key' => 'field_57ea18211c80b',
      			'label' => 'Video (FLV) URL',
      			'name' => GCMS_QUESTION_PREFIX . 'flv_url',
      			'type' => 'url',
      			'instructions' => '',
      			'required' => 0,
      			'conditional_logic' => 0,
      			'wrapper' => array (
      				'width' => '',
      				'class' => '',
      				'id' => '',
      			),
      			'default_value' => '',
      			'placeholder' => '',
      		),
      		array (
      			'key' => 'field_57ea18411c80c',
      			'label' => 'Bestandsgrootte (FLV)',
      			'name' => GCMS_QUESTION_PREFIX . 'flv_filesize',
      			'type' => 'text',
      			'instructions' => '',
      			'required' => 0,
      			'conditional_logic' => 0,
      			'wrapper' => array (
      				'width' => '',
      				'class' => '',
      				'id' => '',
      			),
      			'default_value' => '',
      			'placeholder' => '',
      			'prepend' => '',
      			'append' => '',
      			'maxlength' => '',
      		),
      		array (
      			'key' => 'field_57ea18521c80d',
      			'label' => 'Video (WMV) URL',
      			'name' => GCMS_QUESTION_PREFIX . 'wmv_url',
      			'type' => 'text',
      			'instructions' => '',
      			'required' => 0,
      			'conditional_logic' => 0,
      			'wrapper' => array (
      				'width' => '',
      				'class' => '',
      				'id' => '',
      			),
      			'default_value' => '',
      			'placeholder' => '',
      			'prepend' => '',
      			'append' => '',
      			'maxlength' => '',
      		),
      		array (
      			'key' => 'field_57ea189c1c80e',
      			'label' => 'Bestandsgrootte (WMV)',
      			'name' => GCMS_QUESTION_PREFIX . 'filesize_wmv',
      			'type' => 'text',
      			'instructions' => '',
      			'required' => 0,
      			'conditional_logic' => 0,
      			'wrapper' => array (
      				'width' => '',
      				'class' => '',
      				'id' => '',
      			),
      			'default_value' => '',
      			'placeholder' => '',
      			'prepend' => '',
      			'append' => '',
      			'maxlength' => '',
      		),
      		array (
      			'key' => 'field_57ea18b11c80f',
      			'label' => 'Video (MP4) URL',
      			'name' => GCMS_QUESTION_PREFIX . 'mp4_url',
      			'type' => 'url',
      			'instructions' => '',
      			'required' => 0,
      			'conditional_logic' => 0,
      			'wrapper' => array (
      				'width' => '',
      				'class' => '',
      				'id' => '',
      			),
      			'default_value' => '',
      			'placeholder' => '',
      		),
      		array (
      			'key' => 'field_57ea18d01c810',
      			'label' => 'Bestandsgrootte (MP4)',
      			'name' => GCMS_QUESTION_PREFIX . 'mp4_filesize',
      			'type' => 'text',
      			'instructions' => '',
      			'required' => 0,
      			'conditional_logic' => 0,
      			'wrapper' => array (
      				'width' => '',
      				'class' => '',
      				'id' => '',
      			),
      			'default_value' => '',
      			'placeholder' => '',
      			'prepend' => '',
      			'append' => '',
      			'maxlength' => '',
      		),
      		array (
      			'key' => 'field_57ea18f793b47',
      			'label' => 'Video (MP4 High Resolution) URL',
      			'name' => GCMS_QUESTION_PREFIX . 'mp4_url_hires',
      			'type' => 'url',
      			'instructions' => '',
      			'required' => 0,
      			'conditional_logic' => 0,
      			'wrapper' => array (
      				'width' => '',
      				'class' => '',
      				'id' => '',
      			),
      			'default_value' => '',
      			'placeholder' => '',
      		),
      		array (
      			'key' => 'field_57ea191793b48',
      			'label' => 'Bestandsgroote (MP4 High Resolution)',
      			'name' => GCMS_QUESTION_PREFIX . 'mp4_filesize_hires',
      			'type' => 'text',
      			'instructions' => '',
      			'required' => 0,
      			'conditional_logic' => 0,
      			'wrapper' => array (
      				'width' => '',
      				'class' => '',
      				'id' => '',
      			),
      			'default_value' => '',
      			'placeholder' => '',
      			'prepend' => '',
      			'append' => '',
      			'maxlength' => '',
      		),
      		array (
      			'key' => 'field_57ea193193b49',
      			'label' => 'Video (3GP) URL',
      			'name' => GCMS_QUESTION_PREFIX . '3gp_url',
      			'type' => 'url',
      			'instructions' => '',
      			'required' => 0,
      			'conditional_logic' => 0,
      			'wrapper' => array (
      				'width' => '',
      				'class' => '',
      				'id' => '',
      			),
      			'default_value' => '',
      			'placeholder' => '',
      		),
      		array (
      			'key' => 'field_57ea194493b4a',
      			'label' => 'Bestandsgrootte (3GP)',
      			'name' => GCMS_QUESTION_PREFIX . '3gp_filesize',
      			'type' => 'text',
      			'instructions' => '',
      			'required' => 0,
      			'conditional_logic' => 0,
      			'wrapper' => array (
      				'width' => '',
      				'class' => '',
      				'id' => '',
      			),
      			'default_value' => '',
      			'placeholder' => '',
      			'prepend' => '',
      			'append' => '',
      			'maxlength' => '',
      		),
      	),
      	'location' => array (
      		array (
      			array (
      				'param' => 'post_type',
      				'operator' => '==',
      				'value' => GCMS_QUESTION_CPT,
      			),
      		),
      	),
      	'menu_order' => 0,
      	'position' => 'normal',
      	'style' => 'default',
      	'label_placement' => 'top',
      	'instruction_placement' => 'label',
      	'hide_on_screen' => '',
      	'active' => 1,
      	'description' => '',
      ));
      
      endif;  
    
    }  // else GCMS_RV_USE_CMB2
}    

    //========================================================================================================
    



    /**
     * Check our WordPress installation is compatible with GC_Maturity
     */
    public function do_system_check() {

        $systemCheck = new GC_MaturitySystemCheck();
        $systemCheck->check();

    }



    /**
     *
     */
    public function update_video() {

        check_admin_referer( "gcms_update_video" );

        $capability = apply_filters( 'gcms_capability', 'edit_others_posts' );

        if ( ! current_user_can( $capability ) ) {
            return;
        }

        $gcmaturity_id = absint( $_POST['gcms_id'] );

        if ( ! $gcmaturity_id ) {
            return;
        }

        // update settings
        if ( isset( $_POST['settings'] ) ) {

            $new_settings = $_POST['settings'];

            $old_settings = get_post_meta( $gcmaturity_id, 'gcms_settings', true );

            // convert submitted checkbox values from 'on' or 'off' to boolean values
            $checkboxes = apply_filters( "gcms_checkbox_settings", array( 'noConflict', 'fullWidth', 'hoverPause', 'links', 'reverse', 'random', 'printCss', 'printJs', 'smoothHeight', 'center', 'carouselMode', 'autoPlay' ) );

            foreach ( $checkboxes as $checkbox ) {
                if ( isset( $new_settings[$checkbox] ) && $new_settings[$checkbox] == 'on' ) {
                    $new_settings[$checkbox] = "true";
                } else {
                    $new_settings[$checkbox] = "false";
                }
            }

            $settings = array_merge( (array)$old_settings, $new_settings );

            // update the video settings
            update_post_meta( $gcmaturity_id, 'gcms_settings', $settings );

        }

        // update video title
        if ( isset( $_POST['title'] ) ) {

            $video = array(
                'ID' => $gcmaturity_id,
                'post_title' => esc_html( $_POST['title'] )
            );

            wp_update_post( $video );

        }

        // update individual video
        if ( isset( $_POST['attachment'] ) ) {

            foreach ( $_POST['attachment'] as $video_id => $fields ) {
                do_action( "gcms_save_{$fields['type']}_video", $video_id, $gcmaturity_id, $fields );
            }

        }

    }




    /**
     * Get all videos. Returns an array of 
     * published videos.
     *
     * @param string $sort_key
     * @return an array of published videos
     */
    public function get_all_videos( $sort_key = 'date' ) {

        $gcmaturitys = array();

        // list the tabs
        $args = array(
            'post_type'         => GCMS_QUESTION_CPT,
            'post_status'       => 'publish',
            'orderby'           => $sort_key,
            'suppress_filters'  => 1, // wpml, ignore language filter
            'order'             => 'ASC',
            'posts_per_page'    => -1
        );

        $args = apply_filters( 'gcms_get_all_videos_args', $args );

        // WP_Query causes issues with other plugins using admin_footer to insert scripts
        // use get_posts instead
        $videos = get_posts( $args );

        foreach( $videos as $video ) {

            $gcmaturitys[] = array(
//                'active'  => $active,
                'title'   => $video->post_title,
                'id'      => $video->ID
            );

        }

        return $gcmaturitys;

    }




    




    /**
     * Append the 'Choose GC_Maturity' thickbox content to the bottom of selected admin pages
     */
    public function admin_footer() {

        global $pagenow;

        // Only run in post/page creation and edit screens
        if ( in_array( $pagenow, array( 'post.php', 'page.php', 'post-new.php', 'post-edit.php' ) ) ) {
            $gcmaturitys = $this->get_all_videos( 'title' );
            ?>

            <script type="text/javascript">
                jQuery(document).ready(function() {
                  jQuery('#insert_video').on('click', function() {
                    var id = jQuery('#gcms-select option:selected').val();
                    window.send_to_editor('[<?php echo GCMS_QUESTION_CPT ?> id=' + id + ']');
                    tb_remove();
                  })
                });
            </script>

            <div id="choose-video-selector-screen" style="display: none;">
                <div class="wrap">
                    <?php

                        if ( count( $gcmaturitys ) ) {
                            echo "<h3>" . __( "Choose a video to insert into your post / page.", "gcmaturity-translate" ) . "</h3>";
                            echo "<select id='gcms-select'>";
                            echo "<option disabled=disabled>" . __( "Choose video", "gcmaturity-translate" ) . "</option>";
                            foreach ( $gcmaturitys as $gcmaturity ) {
                                echo "<option value='{$gcmaturity['id']}'>{$gcmaturity['title']}</option>";
                            }
                            echo "</select>";
                            echo "<button class='button primary' id='insert_video'>" . __( "Select and insert video", "gcmaturity-translate" ) . "</button>";
                        } else {
                            _e( "No videos found", "gcmaturity-translate" );
                        }
                    ?>
                </div>
            </div>

            <?php
        }
    }



}

endif;

add_action( 'plugins_loaded', array( 'GC_MaturityPlugin', 'init' ), 10 );
