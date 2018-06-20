<?php
/*
 * GC_Maturity. 
 *
 * Plugin Name:         Gebruiker Centraal Volwassenheidsscore Plugin
 * Plugin URI:          https://github.com/ICTU/gc-maturityscore-plugin/
 * Description:         Plugin voor gebruikercentraal.nl waarmee extra functionaliteit mogelijk wordt voor enquetes en rapportages rondom digitale 'volwassenheid' van organisaties.
 * Version:             1.0.3
 * Version description: Tonen van een grafiek, berekenen van een gemiddelde.
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
      public $version = '1.0.3';
  
  
      /**
       * @var GC_Maturity
       */
      public $gcmaturity = null;

      /**
       * @var GC_Maturity
       */
      public $plugin_name = null;

      public $option_name = null;


  
  
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
  
        define( 'GCMS_VERSION',                 $this->version );
        define( 'GCMS_FOLDER',                  'gc-maturityscore' );
        define( 'GCMS_BASE_URL',                trailingslashit( plugins_url( GCMS_FOLDER ) ) );
        define( 'GCMS_ASSETS_URL',              trailingslashit( GCMS_BASE_URL . 'assets' ) );
        define( 'GCMS_MEDIAELEMENT_URL',        trailingslashit( GCMS_BASE_URL . 'mediaelement' ) );
        define( 'GCMS_PATH',                    plugin_dir_path( __FILE__ ) );
        define( 'GCMS_QUESTION_CPT',            "enquetes" );
        define( 'GCMS_QUESTION_GROUP_CT',       "gcms_custom_taxonomy" );
        define( 'GCMS_DEFAULT',                 "default" );
        define( 'GCMS_DEFAULT_USERID',          2600 );

        define( 'GCMS_CT_ORGANISATIETYPE',      "organisatietype" );
        define( 'GCMS_CT_ORGANISATIEGROOTTE',   "organisatiegrootte" );
        define( 'GCMS_CT_ORGANISATIEATTITUDE',  "organisatieattitude" );
        define( 'GCMS_CT_REGIO',                "regio" );
        

        define( 'GCMS_QUESTION_PREFIX',         GCMS_QUESTION_CPT . '_pf_' ); // prefix for cmb2 metadata fields
        define( 'GCMS_CMBS2_PREFIX',            GCMS_QUESTION_PREFIX . '_form_' ); // prefix for cmb2 metadata fields
        define( 'GCMS_FORMKEYS',                GCMS_CMBS2_PREFIX . 'keys' ); // prefix for cmb2 metadata fields
        
        define( 'GCMS_PLUGIN_DO_DEBUG',         true );
        define( 'GCMS_PLUGIN_USE_CMB2',         true ); 
        define( 'GCMS_PLUGIN_GENESIS_ACTIVE',   true ); 
        define( 'GCMS_PLUGIN_AMCHART_ACTIVE',   true ); 

        define( 'GCMS_OPTIONS_TOTALSURVEYS',    'gcms_total_number_surveys' ); 
        define( 'GCMS_OPTIONS_OVERALLAVERAGE',  'gcms_overall_average' ); 

        $this->plugin_name = 'gcms';
        $this->option_name = 'gcms-option';
        


  
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
      
        if ( GCMS_PLUGIN_USE_CMB2 ) {
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
        
        add_action( 'admin_menu', array( $this, 'gcms_admin_register_menu_pages' ) );
        add_action( 'admin_init', array( $this, 'gcms_admin_register_settings' ) );
        
        // Hook do_sync method
        add_action( 'wp_ajax_gcms_reset', array( $this, 'gcms_reset_values' ) );


        add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_style_script' ) );

        
        
      }
  
      //========================================================================================================
  
      /**
       * Reset the statistics
       */
      public function gcms_reset_values( $givefeedback = true ) {
        
        if ( isset( $_POST['dofeedback'] ) ) {
          $givefeedback = true;
        }

        $log              = '';
        $subjects         = array();
        $allemetingen     = array();
        $formfields_data  = gcms_read_formfields();
        $counter          = 0;

        update_option( GCMS_OPTIONS_TOTALSURVEYS, 0 );  
        update_option( GCMS_OPTIONS_OVERALLAVERAGE, 0 );  
        
        $args = array(
          'post_type'       => GCMS_QUESTION_CPT,
          'posts_per_page'  => '-1',
      		'post_status'     => 'publish',
          'order'           => 'ASC'
        );   
                   
  
        if ( $formfields_data ) {
          foreach ( $formfields_data as $key => $value) {
  
            $optionkey = sanitize_title( $value->group_label );

            $subjects[] = 'Reset value for ' . $optionkey . ' = 0';
            
            update_option( $optionkey, '0' );  
  
          }
        }

        $the_query = new WP_Query( $args );

        if($the_query->have_posts() ) {
          
          while ( $the_query->have_posts() ) {
            
            $the_query->the_post();
            
            $counter++;
            $postid           = get_the_id();
            $subjects[]       = $counter . ' ' . GCMS_QUESTION_CPT . ' = ' . get_the_title() . '(' . $postid . ')';
            
            $metadata_raw     = get_post_meta( $postid );    	
            $metadata         = maybe_unserialize( $metadata_raw[GCMS_FORMKEYS][0] );
            
            foreach ( $metadata as $key => $value) {
            
              $subjects[]   = '(' . $postid . ') ' . $key . '=' . $value . '.';
              $constituents = explode( '_', $key );
              
              if ( isset( $constituents[1] ) ) {
                $groep = $constituents[1];
              }
              
              if ( intval( $value ) > 0 ) {
                $waarden[ $groep ][]  = $value;
              }
            }
          }        
        }

        foreach( $waarden as $key => $value ){        

          $thesum             = array_sum( $value );
          $average_onderdeel  = round( ( $thesum / count( $value ) ), 2 );
          
          update_option( sanitize_title( $formfields_data->$key->group_label ), $average_onderdeel );
          
          $subjects[] = 'nieuw gemiddelde voor ' . $formfields_data->$key->group_label . ' = ' . $average_onderdeel . '.';

          $allemetingen[]       = $average_onderdeel;

        }

        // overall gemiddelde
        $thesum           = array_sum( $allemetingen );
        $average_overall  = round( ( $thesum / count( $allemetingen ) ), 2 );

        update_option( GCMS_OPTIONS_OVERALLAVERAGE, $average_overall );
        update_option( GCMS_OPTIONS_TOTALSURVEYS, $counter );

  
        $log = $this->gcms_frontend_stats_table_get();

        if ( $givefeedback ) {
/*          
          
          dovardump( $waarden );
          dovardump( $subjects );
          dovardump( $log );
*/        
        	wp_send_json( array(
        		'ajaxrespons_messages'  => $subjects,
        		'ajaxrespons_item'      => $log,
        	) );



        }

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
      		"supports"              => array( "title", "editor" ),					
      		);
      		
      	register_post_type( GCMS_QUESTION_CPT, $args );


        // ORGANISATIETYPES
      	$labels = array(
      		"name"                  => __( 'Organisatietypes', "gcmaturity-translate" ),
      		"singular_name"         => __( 'Organisatietype', "gcmaturity-translate" ),
      		"menu_name"             => __( 'Organisatietypes', "gcmaturity-translate" ),
      		"all_items"             => __( 'Alle organisatietypes', "gcmaturity-translate" ),
      		"add_new"               => __( 'Nieuw organisatietype toevoegen', "gcmaturity-translate" ),
      		"add_new_item"          => __( 'Voeg nieuw organisatietype toe', "gcmaturity-translate" ),
      		"edit_item"             => __( 'Bewerk organisatietype', "gcmaturity-translate" ),
      		"new_item"              => __( 'Nieuw organisatietype', "gcmaturity-translate" ),
      		"view_item"             => __( 'Bekijk organisatietype', "gcmaturity-translate" ),
      		"search_items"          => __( 'Zoek organisatietype', "gcmaturity-translate" ),
      		"not_found"             => __( 'Geen organisatietypes gevonden', "gcmaturity-translate" ),
      		"not_found_in_trash"    => __( 'Geen organisatietypes gevonden in de prullenbak', "gcmaturity-translate" ),
      		"archives"              => __( 'Overzichten', "gcmaturity-translate" ),
      		);

      	$args = array(
      		"label"               => __( 'Organisatietypes', "gcmaturity-translate" ),
      		"labels"              => $labels,
      		"public"              => false,
      		"hierarchical"        => true,
      		"label"               => __( 'Organisatietypes', "gcmaturity-translate" ),
      		"show_ui"             => true,
      		"show_in_menu"        => true,
      		"show_in_nav_menus"   => true,
      		"query_var"           => true,
      		"rewrite"             => array( 'slug' => GCMS_CT_ORGANISATIETYPE, 'with_front' => true, ),
      		"show_admin_column"   => true,
      		"show_in_rest"        => false,
      		"rest_base"           => "",
      		"show_in_quick_edit"  => false,
      	);
      	register_taxonomy( GCMS_CT_ORGANISATIETYPE, array( GCMS_QUESTION_CPT ), $args );


        // organisatiegrootteS
      	$labels = array(
      		"name"                  => __( 'Organisatiegroottes', "gcmaturity-translate" ),
      		"singular_name"         => __( 'Organisatiegrootte', "gcmaturity-translate" ),
      		"menu_name"             => __( 'Organisatiegroottes', "gcmaturity-translate" ),
      		"all_items"             => __( 'Alle organisatiegroottes', "gcmaturity-translate" ),
      		"add_new"               => __( 'Nieuw organisatiegrootte toevoegen', "gcmaturity-translate" ),
      		"add_new_item"          => __( 'Voeg nieuw organisatiegrootte toe', "gcmaturity-translate" ),
      		"edit_item"             => __( 'Bewerk organisatiegrootte', "gcmaturity-translate" ),
      		"new_item"              => __( 'Nieuw organisatiegrootte', "gcmaturity-translate" ),
      		"view_item"             => __( 'Bekijk organisatiegrootte', "gcmaturity-translate" ),
      		"search_items"          => __( 'Zoek organisatiegrootte', "gcmaturity-translate" ),
      		"not_found"             => __( 'Geen organisatiegroottes gevonden', "gcmaturity-translate" ),
      		"not_found_in_trash"    => __( 'Geen organisatiegroottes gevonden in de prullenbak', "gcmaturity-translate" ),
      		"archives"              => __( 'Overzichten', "gcmaturity-translate" ),
      		);

      	$args = array(
      		"label"               => __( 'Organisatiegroottes', "gcmaturity-translate" ),
      		"labels"              => $labels,
      		"public"              => false,
      		"hierarchical"        => true,
      		"label"               => __( 'Organisatiegroottes', "gcmaturity-translate" ),
      		"show_ui"             => true,
      		"show_in_menu"        => true,
      		"show_in_nav_menus"   => true,
      		"query_var"           => true,
      		"rewrite"             => array( 'slug' => GCMS_CT_ORGANISATIEGROOTTE, 'with_front' => true, ),
      		"show_admin_column"   => true,
      		"show_in_rest"        => false,
      		"rest_base"           => "",
      		"show_in_quick_edit"  => false,
      	);
      	register_taxonomy( GCMS_CT_ORGANISATIEGROOTTE, array( GCMS_QUESTION_CPT ), $args );
	      	

        // REGIO'S
      	$labels = array(
      		"name"                  => __( "Regio's", "gcmaturity-translate" ),
      		"singular_name"         => __( 'Regio', "gcmaturity-translate" ),
      		"menu_name"             => __( "Regio's", "gcmaturity-translate" ),
      		"all_items"             => __( "Alle regio's", "gcmaturity-translate" ),
      		"add_new"               => __( 'Nieuwe regio toevoegen', "gcmaturity-translate" ),
      		"add_new_item"          => __( 'Voeg nieuwe regio toe', "gcmaturity-translate" ),
      		"edit_item"             => __( 'Bewerk regio', "gcmaturity-translate" ),
      		"new_item"              => __( 'Nieuwe regio', "gcmaturity-translate" ),
      		"view_item"             => __( 'Bekijk regio', "gcmaturity-translate" ),
      		"search_items"          => __( 'Zoek regio', "gcmaturity-translate" ),
      		"not_found"             => __( "Geen regio's gevonden", "gcmaturity-translate" ),
      		"not_found_in_trash"    => __( "Geen regio's gevonden in de prullenbak", "gcmaturity-translate" ),
      		"archives"              => __( 'Overzichten', "gcmaturity-translate" ),
      		);
      
      	$args = array(
      		"label"               => __( "Regio's", "gcmaturity-translate" ),
      		"labels"              => $labels,
      		"public"              => false,
      		"hierarchical"        => true,
      		"label"               => __( "Regio's", "gcmaturity-translate" ),
      		"show_ui"             => true,
      		"show_in_menu"        => true,
      		"show_in_nav_menus"   => true,
      		"query_var"           => true,
      		"rewrite"             => array( 'slug' => GCMS_CT_REGIO, 'with_front' => true, ),
      		"show_admin_column"   => true,
      		"show_in_rest"        => false,
      		"rest_base"           => "",
      		"show_in_quick_edit"  => false,
      	);
      	register_taxonomy( GCMS_CT_REGIO, array( GCMS_QUESTION_CPT ), $args );
	      	


        // organisatieattitudes
      	$labels = array(
      		"name"                  => __( 'Organisatieattitudes', "gcmaturity-translate" ),
      		"singular_name"         => __( 'Organisatieattitude', "gcmaturity-translate" ),
      		"menu_name"             => __( 'Organisatieattitudes', "gcmaturity-translate" ),
      		"all_items"             => __( 'Alle organisatieattitudes', "gcmaturity-translate" ),
      		"add_new"               => __( 'Nieuw organisatieattitude toevoegen', "gcmaturity-translate" ),
      		"add_new_item"          => __( 'Voeg nieuw organisatieattitude toe', "gcmaturity-translate" ),
      		"edit_item"             => __( 'Bewerk organisatieattitude', "gcmaturity-translate" ),
      		"new_item"              => __( 'Nieuw organisatieattitude', "gcmaturity-translate" ),  
      		"view_item"             => __( 'Bekijk organisatieattitude', "gcmaturity-translate" ),
      		"search_items"          => __( 'Zoek organisatieattitude', "gcmaturity-translate" ),
      		"not_found"             => __( 'Geen organisatieattitudes gevonden', "gcmaturity-translate" ),
      		"not_found_in_trash"    => __( 'Geen organisatieattitudes gevonden in de prullenbak', "gcmaturity-translate" ),
      		"archives"              => __( 'Overzichten', "gcmaturity-translate" ),
      		);

      	$args = array(
      		"label"               => __( 'Organisatieattitudes', "gcmaturity-translate" ),
      		"labels"              => $labels,
      		"public"              => false,
      		"hierarchical"        => true,
      		"label"               => __( 'Organisatieattitudes', "gcmaturity-translate" ),
      		"show_ui"             => true,
      		"show_in_menu"        => true,
      		"show_in_nav_menus"   => true,
      		"query_var"           => true,
      		"rewrite"             => array( 'slug' => GCMS_CT_ORGANISATIEATTITUDE, 'with_front' => true, ),
      		"show_admin_column"   => true,
      		"show_in_rest"        => false,
      		"rest_base"           => "",
      		"show_in_quick_edit"  => false,
      	);
      	register_taxonomy( GCMS_CT_ORGANISATIEATTITUDE, array( GCMS_QUESTION_CPT ), $args );


	      	
      	
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
       * Returns data from a survey and the site averages
       */
      public function gcms_get_surveydata( $postid = 0,  $context = '' ) {

        $yourdata = array();
        $waarden  = array();
        $metadata = array();
        $arrpoep  = array();
        
        $formfields_data  = gcms_read_formfields();

        if ( $postid ) {

          $metadata_raw     = get_post_meta( $postid );    	
          $metadata         = maybe_unserialize( $metadata_raw[GCMS_FORMKEYS][0] );

          foreach( $metadata as $key => $value ){        

            $constituents = explode( '_', $key );
            if ( isset( $constituents[1] ) ) {
              $groep = $constituents[1];
            }

            $waarden[ $formfields_data->$groep->group_label ][] = $value;

          }

          if ( $waarden ) {
            
            foreach( $waarden as $key => $value ){        

              $thesum             = array_sum( $waarden[ $key ] );
              $average_onderdeel  = round( ( $thesum / count( $waarden[ $key ] ) ), 2 );
              
              $arrpoep[ $key ]    = $average_onderdeel;
          
            }
          }
        }

        if ( $formfields_data ) {
          foreach ( $formfields_data as $key => $value) {
            $waarden[ $value->group_label ][] = $value->group_label;
          }
        }
        
        if ( $waarden ) {

          $yourdata['cols'][] = _x( "Onderdeel", "table header", "gcmaturity-translate" );

          $yourdata['cols'][] = _x( "Gemiddelde score", "table header", "gcmaturity-translate" );
          if ( $postid ) {
            $yourdata['cols'][] = _x( "Jouw score", "table header", "gcmaturity-translate" );
          }
          
          foreach( $waarden as $key => $value ){    
            $thevalue = get_option( sanitize_title( $key ) );
            if ( $thevalue ) {
              $yourdata['rows'][ $key ][] = $thevalue;    
            }
            if ( $postid && $arrpoep[ $key ]) {
              $yourdata['rows'][ $key ][] = $arrpoep[ $key ] ;    
            }
          }
        }

        
        return $yourdata;
  
      }
  
      //========================================================================================================
  
      /**
       * Register frontend styles
       */
      public function register_frontend_style_script() {
  
        if ( !is_admin() ) {

          $infooter = false;
          
          // don't add to any admin pages

          
          wp_enqueue_style( 'gc-maturityscore-frontend', GCMS_ASSETS_URL . 'css/gc-maturityscore.css', array(), GCMS_VERSION, $infooter );


          // contains minified versions of amcharts js files
          wp_enqueue_script( 'gcms-action-js', GCMS_ASSETS_URL . 'js/min/functions-min.js', array( 'jquery' ), GCMS_VERSION, $infooter );


          $postid = get_the_ID();
          $mydata = $this->gcms_get_surveydata( $postid, 'register_frontend_style_script' );
          
//          dovardump( $postid );
//          dovardump( $mydata );

if ( $mydata ) {

$mykeyname = 'Paul';  

$kleurjouwscore = '#0000FF'; // blauw
$kleurgemiddeldescore = '#FF0000'; // rood
  
  $radardata = json_decode( '{
	"type": "radar",
	"categoryField": "' . $mykeyname . '",
	"sequencedAnimation": false,
	"fontFamily": "\'Montserrat light\',Helvetica,Arial,sans-serif",
	"backgroundColor": "#FFFFFF",
	"color": "#000000",
	"handDrawScatter": 0,
	"handDrawThickness": 3,
	"percentPrecision": 1,
	"processCount": 1004,
	"theme": "dark",
	"graphs": [
		{
			"fillAlphas": 0.31,
			"fillColors": "' . $kleurgemiddeldescore . '",
			"id": "AmGraph-2",
			"lineColor": "' . $kleurgemiddeldescore . '",
			"title": "graph 2",
			"valueField": "gemiddelde score",
			"balloonText": "Gemiddelde score: [[value]]"
		},
		{
			"balloonColor": "#FF0000",
			"balloonText": "Jouw score: [[value]]",
			"bullet": "round",
			"fillAlphas": 0.48,
			"fillColors": "' . $kleurjouwscore . '",
			"id": "AmGraph-1",
			"lineColor": "' . $kleurjouwscore . '",
			"valueField": "jouw score"
		}
	],
	"guides": [],
	"valueAxes": [
		{
			"axisTitleOffset": 20,
			"id": "ValueAxis-1",
			"minimum": 0,
			"zeroGridAlpha": 2,
			"axisAlpha": 0.76,
			"axisColor": "#6B6B6B",
			"axisThickness": 2,
			"dashLength": 0,
			"fillAlpha": 0.49,
			"gridAlpha": 0.68,
			"gridColor": "#6B6B6B",
			"minorGridAlpha": 0.4,
			"minorGridEnabled": true
		},
		{
			"id": "ValueAxis-2",
			"dashLength": 0,
			"fillAlpha": 0.43,
			"gridAlpha": 0.44,
			"gridColor": "#0000FF",
			"minorGridAlpha": 0.32
		}
	],
	"allLabels": [],
	"balloon": {
		"borderAlpha": 0.24,
		"color": "#9400D3"
	},
	"titles": [],
	"dataProvider": [
		{
			"jouw score": "4.2",
			"gemiddelde score": "5",
			"Score": "Eerste dinges"
		},
		{
			"jouw score": "2.4",
			"gemiddelde score": "4",
			"Score": "Tweede dinges"
		},
		{
			"jouw score": "3.5",
			"gemiddelde score": "2",
			"Score": "Derde dinges"
		},
		{
			"jouw score": "2.8",
			"gemiddelde score": "1",
			"Score": "Vierde dinges"
		},
		{
			"jouw score": "4",
			"gemiddelde score": 2,
			"Score": "Vijfde dinges"
		}
	]
}
' );

//  dovardump( $mydata, 'my data' );
  
  $columncounter  = 0;
  $rowcounter     = 0;

//  dovardump( $radardata->graphs );

  foreach( $mydata['cols'] as $columname => $columnsvalue ) {

    
    if ( $columncounter > 0 ) {

      $radardata->graphs[ ( $columncounter - 1 ) ]->valueField = $columnsvalue;

      $radardata->graphs[ ( $columncounter - 1 ) ]->eh = $columncounter . '_' . $columnsvalue;
      $radardata->graphs[ ( $columncounter - 1 ) ]->balloonText = $columnsvalue . ': [[value]]';

    }

    $columncounter++;

         
  }

//  dovardump( $radardata->graphs );

//die();

  $columncounter  = 0;
  
/*
  [cols] => Array
      (
          [0] => Onderdeel
          [1] => Jouw score
          [2] => Gemiddelde score
      )

  [rows] => Array
      (
          [1 Middelen en processen] => Array
              (
                  [0] => 2.9
                  [1] => 2.33
              )

          [2 Management en sturing] => Array
              (
                  [0] => 2.71
                  [1] => 2.67
              )

          [3 Methodieken en meten] => Array
              (
                  [0] => 3.24
                  [1] => 2
              )

          [4 Kennis en cultuur] => Array
              (
                  [0] => 3.28
                  [1] => 3
              )

          [5 Begrijpen van eindgebruiker/klant] => Array
              (
                  [0] => 3.24
                  [1] => 4.67
              )

      )
*/

//  dovardump( $radardata->dataProvider, 'before' );

  $radardata->dataProvider = array();

  foreach( $mydata['rows'] as $rowname => $rowvalue ) {

    // 0 = jouw score
    // 1 = algemeneen gemiddelde

    $jouwscore        = $rowvalue[0];
    $gemiddeldescore  = $rowvalue[1];

    $columncounter = 0;

    foreach( $mydata['cols'] as $columname => $columnsvalue ) {
      
//      rowcounter > colname

      $thekey = 'MIJN_x_' . $columnsvalue . '_r' . $rowcounter . '_c' . $columncounter;

      $radardata->dataProvider[$rowcounter]->$mykeyname = $rowname;
      
      if ( $columncounter == 2 ) {
        $radardata->dataProvider[$rowcounter]->$columnsvalue = $gemiddeldescore;
      }
      elseif ( $columncounter == 1 ) {
        $radardata->dataProvider[$rowcounter]->$columnsvalue = $jouwscore;
      }



      $columncounter++;
  
    }

    $rowcounter++;

  }

//  dovardump( $radardata->graphs );
//  dovardump( $radardata->dataProvider, 'after' );
//  dovardump( $radardata );

}

          
          
          wp_add_inline_script( 'gcms-action-js', 
  '      try {
var amchart1 = AmCharts.makeChart( "amchart1", 
' . wp_json_encode( $radardata ) . ' );
}
catch( err ) { console.log( err ); } ' );
  

              
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
        $formfields_data  = gcms_read_formfields();
        $metadata_raw     = get_post_meta( $postid );    	
        $metadata         = maybe_unserialize( $metadata_raw[GCMS_FORMKEYS][0] );
        $waarden          = array();


        if ( $formfields_data && $metadata ) {

          foreach ( $metadata as $key => $value) {

            $constituents = explode( '_', $key );
            if ( isset( $constituents[1] ) ) {
              $groep = $constituents[1];
            }

            if ( intval( $value ) > 0 ) {
              $waarden[ $groep ][] = $value;
            }
          }
        }
        else {
          $returnstring = '';
        }

        if ( $waarden ) {
          $onderdeelcount   = 0;
          $overallscore     = 0;
          $aantalenquetes   = get_option( GCMS_OPTIONS_TOTALSURVEYS );

          $returnstring = '<p>' . sprintf( _n( 'Er is nu %s enquete ingevoerd.',  'Er zijn inmiddels %s enquetes ingevoerd.', $aantalenquetes, "gcmaturity-translate" ), $aantalenquetes ) . '</p>';

          $returnstring .= $this->gcms_frontend_interpretation_get( false, $postid );      
          $returnstring .= $this->gcms_frontend_graph_get( false, $postid );      
          $returnstring .= $this->gcms_frontend_stats_table_get( false, $postid );      

        }



        return $returnstring;
      
      }
  
      //========================================================================================================
  
      private function get_stored_values( $postid, $postkey, $defaultvalue = '' ) {
  
        if ( GCMS_PLUGIN_DO_DEBUG ) {
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
      
        if ( GCMS_PLUGIN_USE_CMB2 ) {
  
          if ( ! defined( 'CMB2_LOADED' ) ) {
            return false;
            die( ' CMB2_LOADED not loaded ' );
            // cmb2 NOT loaded
          }

          add_shortcode( 'gcms_survey', 'gcms_register_shortcode' );

          add_action( 'cmb2_init', array( $this, 'gcms_register_frontend_form' ) );

          add_action( 'cmb2_after_init', 'gcms_handle_survey_posting' );

        }  // GCMS_PLUGIN_USE_CMB2
  
    }    

    //====================================================================================================


    /**
     * Register the form and fields for our front-end submission form
     */
    public function gcms_register_frontend_form() {

    	$cmb = new_cmb2_box( array(
    		'id'            => 'front-end-post-form',
    		'title'         => __( "Vragen en antwoorden", "gcmaturity-translate" ),
    		'object_types'  => array( 'post' ),
    		'hookup'        => false,
    		'save_fields'   => false,
        'cmb_styles'    => false, // false to disable the CMB stylesheet
    	) );

      $formfields_data = gcms_read_formfields();

      // reset statistics
      $this->gcms_reset_values( false );


      if ( $formfields_data ) {

        $counter_top  = 0;
        $counter_loop = 0;
      
        foreach ( $formfields_data as $key => $value) {
          $counter_top++;
          $counter_loop = 0;

        	$cmb->add_field( array(
        		'id'            => GCMS_CMBS2_PREFIX . $counter_top,
        		'title'         => $value->group_label,
        		'object_types'  => array( GCMS_QUESTION_CPT ), // Post type
        	) );

          foreach ( $value->group_questions as $key2 => $value2 ) {

            $counter_loop++;

            $options = array();
            $label = 'label_' . $counter_top . '_' . $counter_loop;

            $options[ GCMS_DEFAULT ] = _x( 'Geen antwoord', 'default answer', "gcmaturity-translate" );
            $default = GCMS_DEFAULT;

            foreach ( $value2->question_answers as $antwoord ) {

              $options[ $antwoord->answer_value ] = $antwoord->answer_label;

            }
            
            $default = rand( 1, ( count( $options ) - 1 ) );
//            $default = rand( 1, ( count( $options ) - 3 ) );
//            $default = 5;
//            $default = 3;
//            $default = 2;
            
          	$cmb->add_field( array(
          		'name'    => $value2->question_label,
          		'id'      => $value2->question_key,
          		'type'    => 'radio',
              'options' => $options,
              'default' => $default,
          	) );
            
          }            
        }
      }
      else {
        // fout bij het ophalen van de formulierwaarden
        rijksreleasekalender_writedebug('Fout bij ophalen van de formulierwaarden');
      }
      
    	$cmb->add_field( array(
    		'name'    => __( 'Je naam', 'naam', "gcmaturity-translate" ),
    		'id'      => 'submitted_your_name',
    		'type'    => 'text',
    		'desc'    => _x( 'Niet verplicht', 'naam', "gcmaturity-translate" ),
    		'default' => ! empty( $_POST['submitted_your_name'] )
    			? $_POST['submitted_your_name']
    			: '',
    	) );
      
    	$cmb->add_field( array(
    		'name'    => _x( 'Je e-mailadres', 'email', "gcmaturity-translate" ),
    		'id'      => 'submitted_your_email',
    		'type'    => 'text_email',
    		'desc'    => _x( 'Niet verplicht', 'email', "gcmaturity-translate" ),
    		'default' => ! empty( $_POST['submitted_your_email'] )
    			? $_POST['submitted_your_email']
    			: '',
    	) );
    	
    	$default = '';

      // organisatietypes
      $terms = get_terms( array(
        'taxonomy' => GCMS_CT_ORGANISATIETYPE,
        'hide_empty' => false,
      ) );
    
      if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
        $options = array();
        $taxinfo = get_taxonomy( GCMS_CT_ORGANISATIETYPE );
    
        foreach ( $terms as $term ) {
          $options[ $term->term_id ] = $term->name;
          // $default = $term->term_id;
        }
    
      	$cmb->add_field( array(
      		'name'    => $taxinfo->labels->singular_name,
      		'id'      => GCMS_QUESTION_PREFIX . GCMS_CT_ORGANISATIETYPE,
      		'type'    => 'radio',
          'options' => $options,
          'default' => $default,
      	) );
    
      }

      // regio's
      $terms = get_terms( array(
        'taxonomy' => GCMS_CT_REGIO,
        'hide_empty' => false,
      ) );
    
      if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
        $options = array();
        $taxinfo = get_taxonomy( GCMS_CT_REGIO );
    
        foreach ( $terms as $term ) {
          $options[ $term->term_id ] = $term->name;
          // $default = $term->term_id;
        }
    
      	$cmb->add_field( array(
      		'name'    => $taxinfo->labels->singular_name,
      		'id'      => GCMS_QUESTION_PREFIX . GCMS_CT_REGIO,
      		'type'    => 'radio',
          'options' => $options,
          'default' => $default,
      	) );
    
      }


      // organisatiegrootte
      $terms = get_terms( array(
        'taxonomy' => GCMS_CT_ORGANISATIEGROOTTE,
        'hide_empty' => false,
      ) );
    
      if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
        $options = array();
        $taxinfo = get_taxonomy( GCMS_CT_ORGANISATIEGROOTTE );
    
        foreach ( $terms as $term ) {
          $options[ $term->term_id ] = $term->name;
          // $default = $term->term_id;
        }
    
      	$cmb->add_field( array(
      		'name'    => $taxinfo->labels->singular_name,
      		'id'      => GCMS_QUESTION_PREFIX . GCMS_CT_ORGANISATIEGROOTTE,
      		'type'    => 'radio',
          'options' => $options,
          'default' => $default,
      	) );
    
      }


      // organisatieattitude
      $terms = get_terms( array(
        'taxonomy' => GCMS_CT_ORGANISATIEATTITUDE,
        'hide_empty' => false,
      ) );
    
      if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
        $options = array();
        $taxinfo = get_taxonomy( GCMS_CT_ORGANISATIEATTITUDE );
    
        foreach ( $terms as $term ) {
          $options[ $term->term_id ] = $term->name;
          // $default = $term->term_id;
        }
    
      	$cmb->add_field( array(
      		'name'    => $taxinfo->labels->singular_name,
      		'id'      => GCMS_QUESTION_PREFIX . GCMS_CT_ORGANISATIEATTITUDE,
      		'type'    => 'radio',
          'options' => $options,
          'default' => $default,
      	) );
    
      }

    }  
	
    //====================================================================================================

  	/**
  	 * Register the options page
  	 *
  	 * @since    1.0.0
  	 */
  	public function gcms_admin_register_settings() {

  		// Add a General section
  		add_settings_section(
  			$this->option_name . '_general',
  			__( 'Algemeen', "gcmaturity-translate" ),
  			array( $this, $this->option_name . '_general_cb' ),
  			$this->plugin_name
  		);
  
  
    }  
	
    //====================================================================================================

  	/**
  	 * Register the options page
  	 *
  	 * @since    1.0.0
  	 */
  	public function gcms_admin_register_menu_pages() {
  
  
  		add_menu_page(
  			__( 'Volwassenheids&shy;score', "gcmaturity-translate" ),
  			__( 'Volwassenheids&shy;score', "gcmaturity-translate" ),
  			'manage_options',
  			$this->plugin_name,
  			array( $this, 'gcms_main_page' ),
  			'dashicons-admin-settings'
  		);
  		add_submenu_page(
  			$this->plugin_name,
  			__( 'GC Volwassenheidsscore - instellingen', "gcmaturity-translate" ),
  			__( 'Instellingen', "gcmaturity-translate" ),
  			'manage_options',
  			$this->plugin_name . '-instellingen',
  			array( $this, 'gcms_options_page' )
  		);
  		
  	}

	
    //====================================================================================================

    /**
     * Check our WordPress installation is compatible with GC_Maturity
     */
    public function gcms_main_page() {

      echo '<div class="wrap">';
      echo '	<h2>' .  esc_html( get_admin_page_title() ) . '</h2>';
      echo $this->gcms_frontend_stats_table_get();
      echo '</div>';


    }

    //====================================================================================================

    private function gcms_frontend_graph_get( $doecho = false, $postid = 0 ) {
      
      $return = '<h2>' . _x( "Grafiek", "table description", "gcmaturity-translate" ) . "</h2>\n";
      $return .= '<div class="radarchart" id="amchart1" style="min-height: 500px; width: 100%"></div>';
      $return .= '<p>' . _x( "Jouw score in het blauw; gemiddelde score in het rood.", "table description", "gcmaturity-translate" ) . '</p>';

      return $return;

    }


    //====================================================================================================

    private function gcms_frontend_interpretation_get( $doecho = false, $postid = 0 ) {
      
      $return = '<h2>' . _x( "Onze interpretatie", "table description", "gcmaturity-translate" ) . "</h2>\n";
      $return .= '<p>' . _x( "Hier komt de score-interpretatie", "table description", "gcmaturity-translate" ) . '</p>';

      return $return;

    }

    //====================================================================================================

    private function gcms_frontend_stats_table_get( $doecho = false, $postid = 0 ) {

      $mydata = $this->gcms_get_surveydata( $postid, 'gcms_frontend_stats_table_get' );
      $return = '';

      $formfields_data  = gcms_read_formfields();
      
      if ( $formfields_data ) {
        foreach ( $formfields_data as $key => $value) {
          $waarden[ $value->group_label ][] = $value->group_label;
        }
      }

      
      if ( $mydata ) {

        $return = '<h3>' . _x( "Jouw score en het algemeen gemiddelde", "table description", "gcmaturity-translate" ) . "</h3>\n";
        $return .= '	<table class="gcms-score">' . "\n";
        $return .= '<caption>' . _x( "Gemiddelde score per onderdeel", "table description", "gcmaturity-translate" ) . "</caption>\n";

        if ( $mydata['cols'] ) {
          $return .= '<tr>';
          foreach( $mydata['cols'] as $key => $value ){        
            $return .= '<th scope="col">' . $value . "</th>\n";
          }
          $return .= "</tr>\n";
        }
        
        if ( $mydata['rows'] ) {

          foreach( $mydata['rows'] as $key => $value ){        

            $return .= '<tr>';
            $return .= '<th scope="row">' . $key . '</th>';

            foreach( $value as $key2 => $value2 ){        

              $return .= '<td>' . number_format_i18n( $value2, 1)  . "</td>";

            }

            $return .= "</tr>\n";

          }
        }

        $return .= "</table>\n";
      
      }


      if ( $doecho ) {
        echo $return;
      }
      else {
        return $return;
      }
    
    }

    //====================================================================================================

    /**
     * Check our WordPress installation is compatible with GC_Maturity
     */
    public function gcms_options_page() {

// Call the schedule cron job method, $this is the _admin object

// But only do this when we have updated the settings
if ( isset( $_REQUEST[ 'settings-updated' ] ) && ($_REQUEST[ 'settings-updated' ] ) ) {
	$this->rijksreleasekalender_schedule_cron_job();
}


      echo '<div class="wrap">';
      echo '	<h2>' .  esc_html( get_admin_page_title() ) . '</h2>';
      echo '<div id="thetable">';
      echo $this->gcms_frontend_stats_table_get();      
      echo '</div>';
?>

	<table class="form-table" id="progress">
		<tr>
			<td>
				<input id="startsync" type="button" class="button button-primary" value="<?php _e( 'Statistieken opnieuw instellen', "gcmaturity-translate" ); ?>" />
				<input id="clearlog" type="button" class="button button-secondary" value="<?php _e( 'Log leegmaken', "gcmaturity-translate" ); ?>" />
			</td>
		</tr>
	</table>
  <noscript style="background: red; padding: .5em; font-size: 120%;display: block; margin-top: 1em !important; color: white;">
    <strong><?php _e( 'Dit werkt alleen als je JavaScript hebt aangezet.', "gcmaturity-translate" );?></strong>
  </noscript>
  <div style="width: 100%; padding-top: 16px;" id="items">&nbsp;</div>
	<div style="width: 100%; padding-top: 16px; font-style: italic;" id="log"><?php _e( 'Druk op de knop!', "gcmaturity-translate" );?></div>


	<script type="text/javascript">


		var _button       = jQuery('input#startsync');
		var _clearbutton  = jQuery('input#clearlog');
		var _lastrow      = jQuery('#progress tr:last');
		var startrec = 1;

		var setProgress = function (_message) {
			_lastrow.append(_message);
		}

		jQuery(document).ready(function () {

			_button.click(function (e) {

				e.preventDefault();
				jQuery(this).val('<?php _e( 'Momentje', "gcmaturity-translate" );?>').prop('disabled', true);
				jQuery( '#log' ).empty();
				jQuery( '#thetable' ).empty();
				_requestJob( );

			});

			// clear log div
			_clearbutton.click(function() {
				jQuery( '#log' ).empty();
				jQuery( '#thetable' ).empty();
			})

		})

		var _requestJob = function ( ) {
			jQuery.post(ajaxurl, { 'action': 'gcms_reset',  'dofeedback': '1' }, _jobResult);
		}

		var _jobResult = function (response) {

      _button.val('<?php _e( 'Statistieken opnieuw instellen', "gcmaturity-translate" ) ?>').prop('disabled', false);

			if (response.ajaxrespons_item.length > 0) {
				// new messages appear on top. .append() can be used to have new entries at the bottom
				jQuery('#thetable').html( response.ajaxrespons_item );
			}
			if (response.ajaxrespons_messages.length > 0) {
				for (var i = 0; i < response.ajaxrespons_messages.length; i++) {
					// new messages appear on top. .append() can be used to have new entries at the bottom
					jQuery('#log').prepend(response.ajaxrespons_messages[i] + '<br />');
				}
			}

			jQuery(this).val('<?php _e( 'Momentje', "gcmaturity-translate" );?>').prop('disabled', true);
		}

	</script>

<?php
  
      echo '</div>';

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
      
      $page_template  = get_post_meta( get_the_ID(), '_wp_page_template', true );
      
      if ( $this->templatefile == $page_template ) {
  
        add_action( 'genesis_entry_content',  'gcms_do_frontend_form_submission_shortcode_echo', 15 );

        //* Force full-width-content layout
        add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );
      
      }
    }

  
  }

//========================================================================================================

endif;

//========================================================================================================

add_action( 'plugins_loaded', array( 'GC_MaturityPlugin', 'init' ), 10 );

//========================================================================================================

/**
 * Handle the gcms_survey shortcode
 *
 * @param  array  $atts Array of shortcode attributes
 * @return string       Form html
 */

function gcms_register_shortcode( $atts = array() ) {

	// Get CMB2 metabox object
	$cmb = gcms_frontend_cmb2_get();

	// Get $cmb object_types
	$post_types = $cmb->prop( 'object_types' );

	// Current user
	$user_id = get_current_user_id();

	// Parse attributes
	$atts = shortcode_atts( array(
		'post_author' => $user_id ? $user_id : GCMS_DEFAULT_USERID, // Current user, or default
		'post_status' => 'publish',
		'post_type'   => GCMS_QUESTION_CPT, // Only use first object_type in array
	), $atts, 'gcms_survey' );

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
		$output .= '<h3>' . sprintf( __( 'Je inzending is niet opgeslagen, omdat er fouten zijn opgetreden: %s', "gcmaturity-translate" ), '<strong>'. $error->get_error_message() .'</strong>' ) . '</h3>';
	}

	// If the post was submitted successfully, notify the user.
	if ( isset( $_GET['post_submitted'] ) && ( $post = get_post( absint( $_GET['post_submitted'] ) ) ) ) {

		// Get submitter's name
		$name = get_post_meta( $post->ID, 'submitted_author_name', 1 );
		$name = $name ? ' '. $name : '';

	}

	// Get our form
	$output .= cmb2_get_metabox_form( $cmb, 'fake-oject-id', array( 'save_button' => __( "Versturen", "gcmaturity-translate" ) ) );

	return $output;

}

//========================================================================================================
  
/**
 * Gets the front-end-post-form cmb instance
 *
 * @return CMB2 object
 */
function gcms_frontend_cmb2_get() {

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
function gcms_handle_survey_posting() {

	// If no form submission, bail
	if ( empty( $_POST ) || ! isset( $_POST['submit-cmb'], $_POST['object_id'] ) ) {
		return false;
	}

	// Get CMB2 metabox object
	$cmb = gcms_frontend_cmb2_get();

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
		return $cmb->prop( 'submission_error', new WP_Error( 'security_fail', __( "Er is gerommeld met de onderwaterwaarden van het formulier. De inzending wordt niet opgeslagen", "gcmaturity-translate" ) ) );
	}


	/**
	 * Fetch sanitized values
	 */
	$sanitized_values = $cmb->get_sanitized_values( $_POST );

	// Check name submitted
	if ( empty( $_POST['submitted_your_name'] ) ) {
    $sanitized_values['submitted_your_name'] = __( "Geen naam opgegeven", "gcmaturity-translate" );
	}

  $datum  = date_i18n( get_option( 'date_format' ), current_time('timestamp') );
  $rand   = $aantalenquetes . '-' . substr( md5( microtime() ),rand( 0, 26 ), 20 );	

	// Set our post data arguments
	$post_data['post_title']  = $sanitized_values['submitted_your_name'];
	$post_data['post_name']   = sanitize_title( $rand . '-' . $sanitized_values['submitted_your_name'] );

	
	unset( $sanitized_values['submitted_your_name'] );
//	$post_data['post_content'] = 'Hier mijn content: <div class="radarchart" id="amchart1" style="min-height: 500px; width: 100%"></div>. Tot zover.';
	$post_data['post_content'] = 'Hier mijn content!';

	// Create the new post
	$new_submission_id = wp_insert_post( $post_data, true );

	// If we hit a snag, update the user
	if ( is_wp_error( $new_submission_id ) ) {
		return $cmb->prop( 'submission_error', $new_submission_id );
	}

	$cmb->save_fields( $new_submission_id, 'post', $sanitized_values );

	update_post_meta( $new_submission_id, GCMS_FORMKEYS, $sanitized_values );
	

	if ( ! empty( $sanitized_values[ GCMS_QUESTION_PREFIX . GCMS_CT_ORGANISATIEATTITUDE ] ) ) {
    wp_set_post_terms( $new_submission_id, $sanitized_values[ GCMS_QUESTION_PREFIX . GCMS_CT_ORGANISATIEATTITUDE ], GCMS_CT_ORGANISATIEATTITUDE );
	}

	if ( ! empty( $sanitized_values[ GCMS_QUESTION_PREFIX . GCMS_CT_REGIO ] ) ) {
    wp_set_post_terms( $new_submission_id, $sanitized_values[ GCMS_QUESTION_PREFIX . GCMS_CT_REGIO ], GCMS_CT_REGIO );
	}

	if ( ! empty( $sanitized_values[ GCMS_QUESTION_PREFIX . GCMS_CT_ORGANISATIEGROOTTE ] ) ) {
    wp_set_post_terms( $new_submission_id, $sanitized_values[ GCMS_QUESTION_PREFIX . GCMS_CT_ORGANISATIEGROOTTE ], GCMS_CT_ORGANISATIEGROOTTE );
	}

	if ( ! empty( $sanitized_values[ GCMS_QUESTION_PREFIX . GCMS_CT_ORGANISATIETYPE ] ) ) {
    wp_set_post_terms( $new_submission_id, $sanitized_values[ GCMS_QUESTION_PREFIX . GCMS_CT_ORGANISATIETYPE ], GCMS_CT_ORGANISATIETYPE );
	}


	/*
	 * Redirect back to the form page with a query variable with the new post ID.
	 * This will help double-submissions with browser refreshes
	 */
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
if (! function_exists( 'dodebug' ) ) {
  
  function dodebug( $string, $tag = 'span' ) {
    if ( WP_DEBUG && GCMS_PLUGIN_DO_DEBUG ) {

      rijksreleasekalender_writedebug( $string );      
      
      echo '<' . $tag . ' class="debugstring" style="border: 1px solid red; background: yellow; display: block; "> ' . $string . '</' . $tag . '>';

      
    }
  }

}

//========================================================================================================

if (! function_exists( 'gcms_read_formfields' ) ) {
  /**
   * Read a JSON file that contains the form definitions
   */
  function gcms_read_formfields() {

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

    wp_enqueue_style( 'gc-maturityscore-frontend', GCMS_ASSETS_URL . 'css/gc-maturityscore.css', array(), GCMS_VERSION, $infooter );
        
  }

}

//========================================================================================================

if (! function_exists( 'rijksreleasekalender_writedebug' ) ) {

	function rijksreleasekalender_writedebug( $log ) {
		
    $subject = 'log';
    $subject .= ' (ID = ' . getmypid() . ')';

    $subjects = array();
    $subjects[] = $log;

		if ( true === WP_DEBUG ) {
			if ( is_array( $log ) || is_object( $log ) ) {
				error_log( $subject . ' - ' .  print_r( $log, true ) );
			}
			else {
				error_log( $subject . ' - ' .  $log );
			}
		}
	}

}

//========================================================================================================

  
