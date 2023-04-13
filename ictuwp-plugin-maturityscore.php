<?php
/*
 * ictuwp-plugin-maturityscore
 *
 * Plugin Name:         ICTU / Gebruiker Centraal / Maturity Scan Plugin
 * Plugin URI:          https://github.com/ICTU/ictuwp-plugin-maturityscore/
 * Description:         Plugin voor gebruikercentraal.nl waarmee extra functionaliteit mogelijk wordt voor enquetes en rapportages rondom digitale 'volwassenheid' van organisaties.
 * Version:             2.0.2
 * Version description: Versienummer opgehoogd. CMB2 verwijderd.
 * Author:              Paul van Buuren
 * Author URI:          https://wbvb.nl
 * License:             GPL-2.0+
 *
 * Text Domain:         ictuwp-plugin-maturityscore
 * Domain Path:         /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // disable direct access
}


add_action( 'plugins_loaded', 'gcmsf_init_load_plugin_textdomain' );

if ( ! class_exists( 'ictuwp_plugin_maturityscore_Plugin' ) ) :

	/**
	 * Register the plugin.
	 *
	 * Display the administration panel, add JavaScript etc.
	 */
	class ictuwp_plugin_maturityscore_Plugin {

		/**
		 * @var string
		 */
		public $version = '2.0.2';


		/**
		 * @var ictuwp-plugin-maturityscore
		 */
		public $gcmaturity = null;

		/**
		 * @var ictuwp-plugin-maturityscore
		 */

		public $option_name = null;

		public $survey_answers = null;

		public $survey_data = null;


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
			$this->gcmsf_init_setup_actions();
			$this->gcmsf_init_setup_filters();
			$this->gcmsf_admin_do_system_check();
			$this->gcmsf_frontend_append_comboboxes();

		}

		//========================================================================================================

		/**
		 * Define ictuwp-plugin-maturityscore constants
		 */
		private function define_constants() {

			$protocol = strtolower( substr( $_SERVER["SERVER_PROTOCOL"], 0, strpos( $_SERVER["SERVER_PROTOCOL"], '/' ) ) ) . '://';

			define( 'GCMS_C_VERSION', $this->version );
			define( 'GCMS_C_FOLDER', 'ictuwp-plugin-maturityscore' );
			define( 'GCMS_C_BASE_URL', trailingslashit( plugins_url( GCMS_C_FOLDER ) ) );
			define( 'GCMS_C_ASSETS_URL', trailingslashit( GCMS_C_BASE_URL . 'assets' ) );
			define( 'GCMS_C_MEDIAELEMENT_URL', trailingslashit( GCMS_C_BASE_URL . 'mediaelement' ) );
			define( 'GCMS_C_PATH', plugin_dir_path( __FILE__ ) );
			define( 'GCMS_C_PATH_LANGUAGES', trailingslashit( GCMS_C_PATH . 'languages' ) );

			define( 'GCMS_C_SURVEY_CPT', "enquetes" );
			define( 'GCMS_C_QUESTION_CPT', "vraag" );
			define( 'GCMS_C_QUESTION_GROUPING_CT', "groepering" );
			define( 'GCMS_C_QUESTION_DEFAULT', "default" );

			define( 'GCMS_C_SURVEY_DEFAULT_USERID', 2600 ); // 't is wat, hardgecodeerde userids (todo: invoerbaar maken via admin)

			define( 'GCMS_C_SURVEY_CT_ORG_TYPE', "Organisation type" );
			define( 'GCMS_C_SURVEY_CT_ORG_SIZE', "organisatiegrootte" );
			define( 'GCMS_C_SURVEY_CT_ORG_ATTITUDE', "organisatieattitude" );
			define( 'GCMS_C_SURVEY_CT_REGION', "regio" );

			define( 'GCMS_C_QUESTION_PREFIX', GCMS_C_SURVEY_CPT . '_pf_' ); // prefix for cmb2 metadata fields
			define( 'GCMS_C_CMBS2_PREFIX', GCMS_C_QUESTION_PREFIX . '_form_' ); // prefix for cmb2 metadata fields
			define( 'GCMS_C_FORMKEYS', GCMS_C_CMBS2_PREFIX . 'keys' ); // prefix for cmb2 metadata fields

			define( 'GCMS_C_PLUGIN_DO_DEBUG', true );
//        define( 'GCMS_C_PLUGIN_DO_DEBUG',         false );
//        define( 'GCMS_C_PLUGIN_OUTPUT_TOSCREEN',  false );
			define( 'GCMS_C_PLUGIN_OUTPUT_TOSCREEN', true );
			define( 'GCMS_C_PLUGIN_USE_CMB2', true );
			define( 'GCMS_C_PLUGIN_GENESIS_ACTIVE', true ); // todo: inbouwen check op actief zijn van Genesis framework
			define( 'GCMS_C_PLUGIN_AMCHART_ACTIVE', true ); // todo: inbouwen check op actief zijn AM-chart of op AM-chart licentie

//        define( 'GCMS_C_FRONTEND_SHOW_AVERAGES',  true );
			define( 'GCMS_C_FRONTEND_SHOW_AVERAGES', false );

//        define( 'GCMS_C_FRONTEND_SHOW_CUMULATE',  true );
			define( 'GCMS_C_FRONTEND_SHOW_CUMULATE', false );

			define( 'GCMS_C_AVGS_NR_SURVEYS', 'gcmsf_total_number_surveys3' );
			define( 'GCMS_C_AVGS_OVERALL_AVG', 'gcmsf_overall_average3' );

			define( 'GCMS_C_METABOX_ID', 'front-end-post-form' );
			define( 'GCMS_CMB2_RANDOM_OBJECT_ID', 'fake-oject-id' );

			define( 'GCMS_C_ALGEMEEN_LABEL', 'lalala label' );
			define( 'GCMS_C_ALGEMEEN_KEY', 'lalala_key' );

			define( 'GCMS_C_PLUGIN_KEY', 'gcms' );

			define( 'GCMS_C_PLUGIN_SEPARATOR', '__' );

			define( 'GCMS_SCORESEPARATOR', GCMS_C_PLUGIN_SEPARATOR . 'score' . GCMS_C_PLUGIN_SEPARATOR );

			define( 'GCMS_C_SCORE_MAX', 5 ); // max 5 sterren, max 5 punten per vraag / onderdeel

			define( 'GCMS_C_TABLE_COLNR_TH', 0 );
			define( 'GCMS_C_TABLE_COLNR_USER_AVERAGE', 1 );
			define( 'GCMS_C_TABLE_COLNR_SITE_AVERAGE', 2 );
			define( 'GCMS_C_TABLE_COLNR_USER_CUMULAT', 3 );

			define( 'GCMS_C_SURVEY_EMAILID', 'submitted_your_email2' );
			define( 'GCMS_C_SURVEY_YOURNAME', 'submitted_your_name2' );
			define( 'GCMS_C_SURVEY_POSTTITLE', 'post_title_here2' );
			define( 'GCMS_C_SURVEY_GDPR_CHECK', 'gdpr_do_save_my_emailaddress2' );

			// older, obsolete keys, kept for backward compat.
			define( 'GCMS_C_SURVEY_EMAILID_x', 'submitted_your_email' );
			define( 'GCMS_C_SURVEY_YOURNAME_x', 'submitted_your_name' );
			define( 'GCMS_C_SURVEY_POSTTITLE_x', 'post_title_here' );
			define( 'GCMS_C_SURVEY_GDPR_CHECK_x', 'gdpr_do_save_my_emailaddress' );

			define( 'GCMS_C_KEYS_VALUE', '_value' );
			define( 'GCMS_C_KEYS_LABEL', '_label' );

			define( 'GCMS_C_URLPLACEHOLDER', '[[url]]' );
			define( 'GCMS_C_NAMEPLACEHOLDER', '[[name]]' );

			define( 'GCMS_C_TEXTEMAIL', 'textforemail' );

			// Fake 'honeypot' field ID
			define( 'GCMS_C_HONEYPOT', 'name_email_id' );
			define( 'GCMS_C_HONEYPOT_LABEL', _x( "Laat dit veld leeg!", "honeypot-label", "ictuwp-plugin-maturityscore" ) );

			$this->option_name = 'gcms-option';
			$this->survey_data = array();


		}

		//========================================================================================================

		/**
		 * All ictuwp-plugin-maturityscore classes
		 */
		private function plugin_classes() {

			return array(
				'ictuwp_plugin_maturityscore_Systemcheck' => GCMS_C_PATH . 'inc/gc-maturity.systemcheck.class.php',
			);

		}

		//========================================================================================================

		/**
		 * Load required classes
		 */
		private function includes() {

			if ( GCMS_C_PLUGIN_USE_CMB2 ) {
				// load CMB2 functionality
				if ( ! defined( 'CMB2_LOADED' ) ) {
					// cmb2 NOT loaded
					if ( file_exists( dirname( __FILE__ ) . '/cmb2/init.php' ) ) {
						require_once dirname( __FILE__ ) . '/cmb2/init.php';
					} elseif ( file_exists( dirname( __FILE__ ) . '/CMB2/init.php' ) ) {
						require_once dirname( __FILE__ ) . '/CMB2/init.php';
					}
				}
			}

			$autoload_is_disabled = defined( 'GCMS_C_AUTOLOAD_CLASSES' ) && GCMS_C_AUTOLOAD_CLASSES === false;

			if ( function_exists( "spl_autoload_register" ) && ! ( $autoload_is_disabled ) ) {

				// >= PHP 5.2 - Use auto loading
				if ( function_exists( "__autoload" ) ) {
					spl_autoload_register( "__autoload" );
				}
				spl_autoload_register( array( $this, 'autoload' ) );

			} else {
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
		 * filter for when the CPT for the survey is previewed
		 */
		public function gcmsf_frontend_filter_for_preview( $content = '' ) {

			global $post;

			if ( is_singular( GCMS_C_SURVEY_CPT ) && in_the_loop() ) {
				// lets go
				return $this->gcmsf_frontend_display_survey_results( $post->ID );
			} else {
				return $content;
			}

		}

		//========================================================================================================

		/**
		 * Autoload ictuwp-plugin-maturityscore classes to reduce memory consumption
		 */
		public function autoload( $class ) {

			$classes = $this->plugin_classes();

			$class_name = strtolower( $class );

			if ( isset( $classes[ $class_name ] ) && is_readable( $classes[ $class_name ] ) ) {
				echo 'require: ' . $classes[ $class_name ] . '<br>';
				die();
				require_once( $classes[ $class_name ] );
			}

		}

		//========================================================================================================

		/**
		 * Hook ictuwp-plugin-maturityscore into WordPress
		 */
		private function gcmsf_init_setup_actions() {


			// add a page temlate name
			$this->templates    = array();
			$this->templatefile = 'stelselcatalogus-template.php';

			add_action( 'init', array( $this, 'gcmsf_init_register_post_type' ) );

			// add the page template to the templates list
			add_filter( 'theme_page_templates', array( $this, 'gcmsf_init_add_page_templates' ) );

			// activate the page filters
			add_action( 'template_redirect', array( $this, 'gcmsf_frontend_use_page_template' ) );

			// admin settings
			add_action( 'admin_init', array( $this, 'gcmsf_admin_register_settings' ) );

			// Hook do_sync method *todo*
			add_action( 'wp_ajax_gcmsf_reset', 'gcms_data_reset_values' );

			add_action( 'wp_enqueue_scripts', array( $this, 'gcmsf_frontend_register_frontend_style_script' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'gcmsf_admin_register_styles' ) );

			add_filter( 'genesis_single_crumb', array( $this, 'filter_breadcrumb' ), 10, 2 );
			add_filter( 'genesis_page_crumb', array( $this, 'filter_breadcrumb' ), 10, 2 );
			add_filter( 'genesis_archive_crumb', array( $this, 'filter_breadcrumb' ), 10, 2 );


		}
		//========================================================================================================

		/**
		 * Hook ictuwp-plugin-maturityscore into WordPress
		 */
		private function gcmsf_init_setup_filters() {

			// content filter
			add_filter( 'the_content', array( $this, 'gcmsf_frontend_filter_for_preview' ) );

		}

		//========================================================================================================

		/**
		 * Register post type
		 */
		public function gcmsf_init_register_post_type() {

			$typeUC_single = _x( "Survey", "labels", "ictuwp-plugin-maturityscore" );
			$typeUC_plural = _x( "Surveys", "labels", "ictuwp-plugin-maturityscore" );

			$typeLC_single = _x( "survey", "labels", "ictuwp-plugin-maturityscore" );
			$typeLC_plural = _x( "surveys", "labels", "ictuwp-plugin-maturityscore" );

			$labels = array(
				"name"               => sprintf( '%s', $typeUC_single ),
				"singular_name"      => sprintf( '%s', $typeUC_single ),
				"menu_name"          => sprintf( '%s', $typeUC_single ),
				"all_items"          => sprintf( _x( 'All %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_plural ),
				"add_new"            => sprintf( _x( 'Add %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_plural ),
				"add_new_item"       => sprintf( _x( 'Add new %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"edit"               => _x( "Edit?", "labels", "ictuwp-plugin-maturityscore" ),
				"edit_item"          => sprintf( _x( 'Edit %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"new_item"           => sprintf( _x( 'Add %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"view"               => _x( "Show", "labels", "ictuwp-plugin-maturityscore" ),
				"view_item"          => sprintf( _x( 'Add %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"search_items"       => sprintf( _x( 'Search %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"not_found"          => sprintf( _x( 'No %s available', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"not_found_in_trash" => sprintf( _x( 'No %s in trash', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_plural ),
				"parent"             => _x( "Parent", "labels", "ictuwp-plugin-maturityscore" ),

			);

			$args = array(
				"label"               => $typeUC_plural,
				"labels"              => $labels,
				"description"         => "",
				"public"              => true,
				"publicly_queryable"  => true,
				"show_ui"             => true,
				"show_in_rest"        => false,
				"rest_base"           => "",
				"has_archive"         => false,
				"show_in_menu"        => true,
				"exclude_from_search" => false,
				"capability_type"     => "post",
				"map_meta_cap"        => true,
				"hierarchical"        => false,
				"rewrite"             => array( "slug" => 'scans', "with_front" => true ),
				"query_var"           => true,
				"supports"            => array( "title", "editor" ),
			);

			register_post_type( GCMS_C_SURVEY_CPT, $args );


			$typeUC_single = _x( "Organisation type", "labels", "ictuwp-plugin-maturityscore" );
			$typeUC_plural = _x( "Organisation types", "labels", "ictuwp-plugin-maturityscore" );

			$typeLC_single = _x( "organisation type", "labels", "ictuwp-plugin-maturityscore" );
			$typeLC_plural = _x( "organisation types", "labels", "ictuwp-plugin-maturityscore" );

			// organisation types
			$labels = array(

				"name"               => sprintf( '%s', $typeUC_single ),
				"singular_name"      => sprintf( '%s', $typeUC_single ),
				"menu_name"          => sprintf( '%s', $typeUC_single ),
				"all_items"          => sprintf( _x( 'All %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_plural ),
				"add_new"            => sprintf( _x( 'Add %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_plural ),
				"add_new_item"       => sprintf( _x( 'Add new %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"edit"               => _x( "Edit?", "labels", "ictuwp-plugin-maturityscore" ),
				"edit_item"          => sprintf( _x( 'Edit %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"new_item"           => sprintf( _x( 'Add %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"view"               => _x( "Show", "labels", "ictuwp-plugin-maturityscore" ),
				"view_item"          => sprintf( _x( 'Add %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"search_items"       => sprintf( _x( 'Search %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"not_found"          => sprintf( _x( 'No %s available', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"not_found_in_trash" => sprintf( _x( 'No %s in trash', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_plural ),
				"parent"             => _x( "Parent", "labels", "ictuwp-plugin-maturityscore" ),
				"archives"           => _x( "Edit?", "labels", "ictuwp-plugin-maturityscore" ),

			);

			$args = array(
				"label"              => $typeUC_plural,
				"labels"             => $labels,
				"public"             => false,
				"hierarchical"       => true,
				"label"              => $typeUC_plural,
				"show_ui"            => true,
				"show_in_menu"       => true,
				"show_in_nav_menus"  => true,
				"query_var"          => true,
				"rewrite"            => array( 'slug' => GCMS_C_SURVEY_CT_ORG_TYPE, 'with_front' => true, ),
				"show_admin_column"  => true,
				"show_in_rest"       => false,
				"rest_base"          => "",
				"show_in_quick_edit" => false,
			);
			register_taxonomy( GCMS_C_SURVEY_CT_ORG_TYPE, array( GCMS_C_SURVEY_CPT ), $args );


			$typeUC_single = _x( "Organisation size", "labels", "ictuwp-plugin-maturityscore" );
			$typeUC_plural = _x( "Organisation sizes", "labels", "ictuwp-plugin-maturityscore" );

			$typeLC_single = _x( "organisation size", "labels", "ictuwp-plugin-maturityscore" );
			$typeLC_plural = _x( "organisation sizes", "labels", "ictuwp-plugin-maturityscore" );

			// organisatiegrootteS
			$labels = array(
				"name"               => sprintf( '%s', $typeUC_single ),
				"singular_name"      => sprintf( '%s', $typeUC_single ),
				"menu_name"          => sprintf( '%s', $typeUC_single ),
				"all_items"          => sprintf( _x( 'All %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_plural ),
				"add_new"            => sprintf( _x( 'Add %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_plural ),
				"add_new_item"       => sprintf( _x( 'Add new %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"edit"               => _x( "Edit?", "labels", "ictuwp-plugin-maturityscore" ),
				"edit_item"          => sprintf( _x( 'Edit %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"new_item"           => sprintf( _x( 'Add %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"view"               => _x( "Show", "labels", "ictuwp-plugin-maturityscore" ),
				"view_item"          => sprintf( _x( 'Add %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"search_items"       => sprintf( _x( 'Search %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"not_found"          => sprintf( _x( 'No %s available', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"not_found_in_trash" => sprintf( _x( 'No %s in trash', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_plural ),
				"parent"             => _x( "Parent", "labels", "ictuwp-plugin-maturityscore" ),
				"archives"           => _x( "Edit?", "labels", "ictuwp-plugin-maturityscore" ),
			);

			$args = array(
				"label"              => $typeUC_plural,
				"labels"             => $labels,
				"public"             => false,
				"hierarchical"       => true,
				"label"              => $typeUC_plural,
				"show_ui"            => true,
				"show_in_menu"       => true,
				"show_in_nav_menus"  => true,
				"query_var"          => true,
				"rewrite"            => array( 'slug' => GCMS_C_SURVEY_CT_ORG_SIZE, 'with_front' => true, ),
				"show_admin_column"  => true,
				"show_in_rest"       => false,
				"rest_base"          => "",
				"show_in_quick_edit" => false,
			);
			register_taxonomy( GCMS_C_SURVEY_CT_ORG_SIZE, array( GCMS_C_SURVEY_CPT ), $args );


			$typeUC_single = _x( "Region", "labels", "ictuwp-plugin-maturityscore" );
			$typeUC_plural = _x( "Regions", "labels", "ictuwp-plugin-maturityscore" );

			$typeLC_single = _x( "region", "labels", "ictuwp-plugin-maturityscore" );
			$typeLC_plural = _x( "regions", "labels", "ictuwp-plugin-maturityscore" );

			// REGIO'S
			$labels = array(
				"name"               => sprintf( '%s', $typeUC_single ),
				"singular_name"      => sprintf( '%s', $typeUC_single ),
				"menu_name"          => sprintf( '%s', $typeUC_single ),
				"all_items"          => sprintf( _x( 'All %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_plural ),
				"add_new"            => sprintf( _x( 'Add %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_plural ),
				"add_new_item"       => sprintf( _x( 'Add new %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"edit"               => _x( "Edit?", "labels", "ictuwp-plugin-maturityscore" ),
				"edit_item"          => sprintf( _x( 'Edit %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"new_item"           => sprintf( _x( 'Add %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"view"               => _x( "Show", "labels", "ictuwp-plugin-maturityscore" ),
				"view_item"          => sprintf( _x( 'Add %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"search_items"       => sprintf( _x( 'Search %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"not_found"          => sprintf( _x( 'No %s available', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"not_found_in_trash" => sprintf( _x( 'No %s in trash', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_plural ),
				"parent"             => _x( "Parent", "labels", "ictuwp-plugin-maturityscore" ),
				"archives"           => _x( "Edit?", "labels", "ictuwp-plugin-maturityscore" ),
			);

			$args = array(
				"label"              => $typeUC_plural,
				"labels"             => $labels,
				"public"             => false,
				"hierarchical"       => true,
				"label"              => $typeUC_plural,
				"show_ui"            => true,
				"show_in_menu"       => true,
				"show_in_nav_menus"  => true,
				"query_var"          => true,
				"rewrite"            => array( 'slug' => GCMS_C_SURVEY_CT_REGION, 'with_front' => true, ),
				"show_admin_column"  => true,
				"show_in_rest"       => false,
				"rest_base"          => "",
				"show_in_quick_edit" => false,
			);
			register_taxonomy( GCMS_C_SURVEY_CT_REGION, array( GCMS_C_SURVEY_CPT ), $args );


			$typeUC_single = _x( "Organisation attitude", "labels", "ictuwp-plugin-maturityscore" );
			$typeUC_plural = _x( "Organisation attitudes", "labels", "ictuwp-plugin-maturityscore" );

			$typeLC_single = _x( "organisation attitude", "labels", "ictuwp-plugin-maturityscore" );
			$typeLC_plural = _x( "organisation attitudes", "labels", "ictuwp-plugin-maturityscore" );

			// organisatieattitudes
			$labels = array(
				"name"               => sprintf( '%s', $typeUC_single ),
				"singular_name"      => sprintf( '%s', $typeUC_single ),
				"menu_name"          => sprintf( '%s', $typeUC_single ),
				"all_items"          => sprintf( _x( 'All %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_plural ),
				"add_new"            => sprintf( _x( 'Add %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_plural ),
				"add_new_item"       => sprintf( _x( 'Add new %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"edit"               => _x( "Edit?", "labels", "ictuwp-plugin-maturityscore" ),
				"edit_item"          => sprintf( _x( 'Edit %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"new_item"           => sprintf( _x( 'Add %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"view"               => _x( "Show", "labels", "ictuwp-plugin-maturityscore" ),
				"view_item"          => sprintf( _x( 'Add %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"search_items"       => sprintf( _x( 'Search %s', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"not_found"          => sprintf( _x( 'No %s available', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_single ),
				"not_found_in_trash" => sprintf( _x( 'No %s in trash', 'labels', "ictuwp-plugin-maturityscore" ), $typeLC_plural ),
				"parent"             => _x( "Parent", "labels", "ictuwp-plugin-maturityscore" ),
				"archives"           => _x( "Edit?", "labels", "ictuwp-plugin-maturityscore" ),
			);

			$args = array(
				"label"              => $typeUC_plural,
				"labels"             => $labels,
				"public"             => false,
				"hierarchical"       => true,
				"label"              => $typeUC_plural,
				"show_ui"            => true,
				"show_in_menu"       => true,
				"show_in_nav_menus"  => true,
				"query_var"          => true,
				"rewrite"            => array( 'slug' => GCMS_C_SURVEY_CT_ORG_ATTITUDE, 'with_front' => true, ),
				"show_admin_column"  => true,
				"show_in_rest"       => false,
				"rest_base"          => "",
				"show_in_quick_edit" => false,
			);
			register_taxonomy( GCMS_C_SURVEY_CT_ORG_ATTITUDE, array( GCMS_C_SURVEY_CPT ), $args );


			flush_rewrite_rules();

		}

		//========================================================================================================

		/**
		 * Hides the custom post template for pages on WordPress 4.6 and older
		 *
		 * @param array $post_templates Array of page templates. Keys are filenames, values are translated names.
		 *
		 * @return array Expanded array of page templates.
		 */
		function gcmsf_init_add_page_templates( $post_templates ) {

			$post_templates[ $this->templatefile ] = _x( 'Maturity score template', "naam template", "ictuwp-plugin-maturityscore" );

			return $post_templates;

		}

		//========================================================================================================

		/**
		 * filter the breadcrumb
		 */
		public function filter_breadcrumb( $crumb, $args ) {

			global $post;

			$span_before_start  = '<span class="breadcrumb-link-wrap" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">';
			$span_between_start = '<span itemprop="name">';
			$span_before_end    = '</span>';

			if ( function_exists( 'get_field' ) ) {

				$brief_page_overview = get_field( 'volwassenheidsscan_survey_page', 'option' );        // code hier

				if ( is_singular( GCMS_C_SURVEY_CPT ) && ( $brief_page_overview ) ) {

					$actueelpagetitle = get_the_title( $brief_page_overview );

					if ( $brief_page_overview ) {
						$crumb = gc_wbvb_breadcrumbstring( $brief_page_overview, $args );
					}
				}

			}

			return $crumb;

		}

		//========================================================================================================

		/**
		 * Register the options page
		 *
		 * @since    1.0.0
		 */
		public function gcmsf_admin_register_settings() {

			// Add a General section
			add_settings_section(
				$this->option_name . '_general',
				__( 'General settings', "ictuwp-plugin-maturityscore" ),
				array( $this, $this->option_name . '_general_cb' ),
				GCMS_C_PLUGIN_KEY
			);

		}

		//========================================================================================================

		/**
		 * Register admin-side styles
		 */
		public function gcmsf_admin_register_styles() {

			if ( is_admin() ) {
				wp_enqueue_style( 'ictuwp-plugin-maturityscore-admin', GCMS_C_ASSETS_URL . 'css/ictuwp-plugin-maturityscore-admin.css', false, GCMS_C_VERSION );
			}

		}

		//========================================================================================================

		/**
		 * Add the help tab to the screen.
		 */
		public function gcmsf_admin_help_tab() {

			$screen = get_current_screen();

			// documentation tab
			$screen->add_gcmsf_admin_help_tab( array(
					'id'      => 'documentation',
					'title'   => __( 'Documentation', "ictuwp-plugin-maturityscore" ),
					'content' => "<p><a href='https://github.com/ICTU/ictuwp-plugin-maturityscore/documentation/' target='blank'>" . __( 'GC Maturity documentation', "ictuwp-plugin-maturityscore" ) . "</a></p>",
				)
			);
		}

		//====================================================================================================

		/**
		 * Check our WordPress installation is compatible with ictuwp-plugin-maturityscore
		 */
		public function gcmsf_admin_main_page_get() {

			echo '<div class="wrap">';
			echo '	<h2>' . esc_html( get_admin_page_title() ) . '</h2>';
			echo '	<p>' . _x( 'Here you can edit the surveys.', "admin", "ictuwp-plugin-maturityscore" ) . '</p>';
			echo $this->gcmsf_frontend_get_tableview( false, 0, true );
			// cmb2
			echo '</div>';


		}

		//========================================================================================================

		/**
		 * Register admin JavaScript
		 */
		public function gcmsf_admin_register_scripts() {

			// media library dependencies
			wp_enqueue_media();

			// plugin dependencies
			wp_enqueue_script( 'jquery-ui-core', array( 'jquery' ) );

			$this->gcmsf_admin_localize_scripts();

			do_action( 'gcmsf_gcmsf_admin_register_scripts' );

		}

		//========================================================================================================

		/**
		 * Localise admin script
		 */
		public function gcmsf_admin_localize_scripts() {

			wp_localize_script( 'gcms-admin-script', 'gcms', array(
					'url'          => _x( "URL", "js", "ictuwp-plugin-maturityscore" ),
					'caption'      => _x( "Caption", "js", "ictuwp-plugin-maturityscore" ),
					'new_window'   => _x( "New Window", "js", "ictuwp-plugin-maturityscore" ),
					'confirm'      => _x( "Are you sure?", "js", "ictuwp-plugin-maturityscore" ),
					'ajaxurl'      => admin_url( 'admin-ajax.php' ),
					'resize_nonce' => wp_create_nonce( 'gcmsf_resize' ),
					'iframeurl'    => admin_url( 'admin-post.php?action=gcmsf_preview' ),
				)
			);

		}

		//====================================================================================================

		/**
		 * Check our WordPress installation is compatible with ictuwp-plugin-maturityscore
		 */
		public function gcmsf_admin_options_page() {

			echo '<div class="wrap">';
			echo '	<h2>' . esc_html( get_admin_page_title() ) . '</h2>';
			echo '<div id="thetable">';
			echo $this->gcmsf_frontend_get_tableview();
			echo '</div>';

			?>

            <table class="form-table" id="progress">
                <tr>
                    <td>
                        <input id="startsync" type="button" class="button button-primary"
                               value="<?php _e( 'Reset statistics', "ictuwp-plugin-maturityscore" ); ?>"/>
                        <input id="clearlog" type="button" class="button button-secondary"
                               value="<?php _e( 'Empty log', "ictuwp-plugin-maturityscore" ); ?>"/>
                    </td>
                </tr>
            </table>
            <noscript
                    style="background: red; padding: .5em; font-size: 120%;display: block; margin-top: 1em !important; color: white;">
                <strong><?php _e( 'Ehm, please allow JavaScript.', "ictuwp-plugin-maturityscore" ); ?></strong>
            </noscript>
            <div style="width: 100%; padding-top: 16px;" id="items">&nbsp;</div>
            <div style="width: 100%; padding-top: 16px; font-style: italic;"
                 id="log"><?php _e( 'Press the button!', "ictuwp-plugin-maturityscore" ); ?></div>


            <script type="text/javascript">


                var _button = jQuery('input#startsync');
                var _clearbutton = jQuery('input#clearlog');
                var _lastrow = jQuery('#progress tr:last');
                var startrec = 1;

                var setProgress = function (_message) {
                    _lastrow.append(_message);
                }

                jQuery(document).ready(function () {

                    _button.click(function (e) {

                        e.preventDefault();
                        jQuery(this).val('<?php _e( 'Just a moment please', "ictuwp-plugin-maturityscore" );?>').prop('disabled', true);
                        jQuery('#log').empty();
                        jQuery('#thetable').empty();
                        _requestJob();

                    });

                    // clear log div
                    _clearbutton.click(function () {
                        jQuery('#log').empty();
                        jQuery('#thetable').empty();
                    })

                })

                var _requestJob = function () {
                    jQuery.post(ajaxurl, {'action': 'gcmsf_reset', 'dofeedback': '1'}, _jobResult);
                }

                var _jobResult = function (response) {

                    _button.val('<?php _e( 'Reset statistics', "ictuwp-plugin-maturityscore" ) ?>').prop('disabled', false);

                    if (response.ajaxrespons_item.length > 0) {
                        // new messages appear on top. .append() can be used to have new entries at the bottom
                        jQuery('#thetable').html(response.ajaxrespons_item);
                    }
                    if (response.ajaxrespons_messages.length > 0) {
                        for (var i = 0; i < response.ajaxrespons_messages.length; i++) {
                            // new messages appear on top. .append() can be used to have new entries at the bottom
                            jQuery('#log').prepend(response.ajaxrespons_messages[i] + '<br />');
                        }
                    }

                    jQuery(this).val('<?php _e( 'Just a moment please', "ictuwp-plugin-maturityscore" );?>').prop('disabled', true);
                }

            </script>

			<?php

			echo '</div>';

		}

		//====================================================================================================

		/**
		 * Check our WordPress installation is compatible with ictuwp-plugin-maturityscore
		 */
		public function gcmsf_admin_do_system_check() {


			//        $systemCheck = new ictuwp_plugin_maturityscore_Systemcheck();
			//        $systemCheck->check();

		}

		//========================================================================================================

		/**
		 * Register the form and fields for our admin form
		 */
		public function gcmsf_admin_form_register_cmb2_form_new() {

			/**
			 * Registers options page menu item and form.
			 */
			$cmb_options = new_cmb2_box( array(
				'id'           => 'gcmsf_admin_options_metabox',
				'title'        => esc_html__( 'Maturity score', "ictuwp-plugin-maturityscore" ),
				'object_types' => array( 'options-page' ),
				'option_key'   => GCMS_C_PLUGIN_KEY,
				// The option key and admin menu page slug.
				'icon_url'     => 'dashicons-admin-settings',
				// Menu icon. Only applicable if 'parent_slug' is left empty.
				'capability'   => 'manage_options',
				// Cap required to view options-page.
				'tab_group'    => GCMS_C_PLUGIN_KEY,
				'tab_title'    => _x( 'Email settings &amp; general scoring', 'scoring menu', "ictuwp-plugin-maturityscore" ),
				'display_cb'   => 'yourprefix_options_display_with_tabs',
				'save_button'  => esc_html__( 'Save options', "ictuwp-plugin-maturityscore" ),
			) );
			/*
			 * Options fields ids only need
			 * to be unique within this box.
			 * Prefix is not needed.
			 */


			$tabscounter = 0;

			$key = 'SITESCORE';

			// get the structure from the JSON file
			$formfields_data   = gcmsf_data_get_survey_json();
			$sectiontitle_prev = '';

			if ( $formfields_data ) {

				// there are questions and answers
				// first, let's set up the forms for the general test results

				$counter          = 0;
				$tabscounter      = 0;
				$question_counter = 0;
				$total_answer_cnt = 0;
				$counter_group    = 0;

				$questionsnumber_start = 1;
				$questionsnumber_end   = 0;
				$questionsnumber_prev  = 0;

				foreach ( $formfields_data as $group_key => $value ) {

					// $group_key = 'g1', 'g2', 'g3', etc
					$tabscounter ++;
					$counter_group ++;

					$key_grouplabel        = $group_key . '_group_label';
					$key_group_description = $group_key . '_group_description';
					$grouplabel            = gcms_aux_get_value_for_cmb2_key( $key_grouplabel, $value->group_label, GCMS_C_PLUGIN_KEY );
					$groupdescription      = gcms_aux_get_value_for_cmb2_key( $key_group_description, $value->group_description, GCMS_C_PLUGIN_KEY );
					$groupquestions        = (array) $value->group_questions[0];

					$questionsnumber_start = ( $questionsnumber_prev + $tabscounter );
					$questionsnumber_end   = ( ( $questionsnumber_start - 1 ) + count( $groupquestions ) );
					$questionsnumber_prev  = $questionsnumber_end;

					$questionrange    = sprintf( _x( '%s - %s', 'question range', "ictuwp-plugin-maturityscore" ), ( $question_counter + 1 ), ( $question_counter + count( $groupquestions ) ) );
					$tablabel         = sprintf( _x( '%s - questions %s', 'tablabel', "ictuwp-plugin-maturityscore" ), $tabscounter, $questionrange );
					$groupheader      = sprintf( _x( 'Group %s: title, introduction and scoring texts', 'tablabel', "ictuwp-plugin-maturityscore" ), $counter_group );
					$grouptitle_label = sprintf( _x( 'Title for group %s', 'tablabel', "ictuwp-plugin-maturityscore" ), $counter_group );
					$groupintro_label = sprintf( _x( 'Introduction %s', 'tablabel', "ictuwp-plugin-maturityscore" ), $counter_group );

					// maak een tab aan voor de vragen van een groep
					$args = array(
						'id'           => GCMS_C_PLUGIN_KEY . $group_key,
						'menu_title'   => sprintf( _x( 'Group %s - %s', 'tablabel', "ictuwp-plugin-maturityscore" ), $tabscounter, '(' . $questionrange . ')' ),
						'object_types' => array( 'options-page' ),
						'option_key'   => GCMS_C_PLUGIN_KEY . GCMS_C_PLUGIN_SEPARATOR . $group_key,
						'parent_slug'  => GCMS_C_PLUGIN_KEY,
						'tab_group'    => GCMS_C_PLUGIN_KEY,
						'tab_title'    => $tablabel,
						'display_cb'   => 'yourprefix_options_display_with_tabs',
					);

					$questions_tab = new_cmb2_box( $args );

					$questions_tab->add_field( array(
						'name'        => $groupheader,
						'type'        => 'title',
						'id'          => GCMS_C_CMBS2_PREFIX . 'start_section' . $counter_group,
						'description' => '',
						// 'Key: ' . GCMS_C_CMBS2_PREFIX . 'start_section' . $counter_group . ', optionkey: "' . GCMS_C_PLUGIN_KEY . GCMS_C_PLUGIN_SEPARATOR . $group_key . '"',

					) );

					$questions_tab->add_field( array(
						'name'        => $grouptitle_label,
						'type'        => 'text',
						'id'          => $key_grouplabel,
						'default'     => $grouplabel,
						'description' => '',
						// 'Key: ' . $key_grouplabel . ', optionkey: "' . GCMS_C_PLUGIN_KEY . GCMS_C_PLUGIN_SEPARATOR . $group_key . '"',

					) );

					$questions_tab->add_field( array(
						'name'        => $groupintro_label,
						'type'        => 'textarea',
						'id'          => $key_group_description,
						'default'     => $groupdescription,
						'description' => '',
						// 'Key: ' . $key_group_description . ', optionkey: "' . GCMS_C_PLUGIN_KEY . GCMS_C_PLUGIN_SEPARATOR . $group_key . '"',
					) );


					// scoring texts
					$questions_tab->add_field( array(
						'name' => __( 'Score for this section', "ictuwp-plugin-maturityscore" ),
						'type' => 'title',
						'id'   => GCMS_C_CMBS2_PREFIX . 'start_scoring_section' . $counter_group
					) );

					$counter = 0;

					// code voor onderhouden namen en inleiding
					while ( $counter < GCMS_C_SCORE_MAX ) {

						$counter ++;

						$default = sprintf( __( 'For section <em>%s</em> you scored over %s, but lesss than %s. Here more about that.', "ictuwp-plugin-maturityscore" ), gcms_aux_get_value_for_cmb2_key( $key, $key, GCMS_C_PLUGIN_KEY ), ( $counter - 1 ), $counter );

						$label = sprintf( __( 'between %s and %s', "ictuwp-plugin-maturityscore" ), ( $counter - 1 ), $counter );
						if ( GCMS_C_SCORE_MAX == $counter ) {
							$default = sprintf( __( 'Perfect score: %s!', "ictuwp-plugin-maturityscore" ), $counter );
						}

						$fieldkey = $group_key . GCMS_SCORESEPARATOR . $counter;

						$questions_tab->add_field( array(
							'name'        => sprintf( _x( 'Text %s<br><small>if score %s.</small>', 'score range', "ictuwp-plugin-maturityscore" ), $counter, $label ),
							'description' => sprintf( __( 'Score %s', "ictuwp-plugin-maturityscore" ), $label . ' (id=' . $fieldkey . ', option_key=' . GCMS_C_PLUGIN_KEY . GCMS_C_PLUGIN_SEPARATOR . $group_key . ')' ),
							'description' => '',
							// 'fieldkey: ' . $fieldkey . ', fieldkey: "' . GCMS_C_CMBS2_PREFIX . 'start_scoring_section' . $counter_group . '"',
//				'description'   => sprintf( __( 'Score %s', "ictuwp-plugin-maturityscore" ), $label ),
							'type'        => 'wysiwyg',
							'id'          => $fieldkey,
							'default'     => $default
						) );

					}


					// loop through the questions
					foreach ( $groupquestions as $question_key => $question_single ) {

						$question_counter ++;

						$options        = array();
						$selectrandom   = array();
						$default        = '';
						$answers        = (array) $question_single->question_answers[0];
						$question_label = $question_single->question_label;

						$questionkey = $group_key . GCMS_C_PLUGIN_SEPARATOR . $question_key;

						$questions_tab->add_field( array(
							'name' => sprintf( __( 'Question %s - %s', "ictuwp-plugin-maturityscore" ), $question_counter, gcms_aux_get_value_for_cmb2_key( $questionkey, $question_label, GCMS_C_PLUGIN_KEY . GCMS_C_PLUGIN_SEPARATOR . $group_key ) ),
							'type' => 'title',
							'id'   => GCMS_C_CMBS2_PREFIX . '_section_' . $key . '_' . $group_key . '_' . $question_counter
						) );

						// put it together
						$fieldkey = $group_key . GCMS_C_PLUGIN_SEPARATOR . $question_key;
						$questions_tab->add_field( array(
							'name'        => sprintf( __( 'Question %s', "ictuwp-plugin-maturityscore" ), $question_counter ),
							'id'          => $fieldkey,
							'type'        => 'text',
							'default'     => $question_label,
							'description' => '',
							// 'Key: ' . $fieldkey . ', optionkey: "' . GCMS_C_PLUGIN_KEY . GCMS_C_PLUGIN_SEPARATOR . $group_key . '"',
							'attributes'  => array(
								'required' => 'required',
							),
						) );

						$optioncounter = 0;

						// get all possible answers
						foreach ( $answers as $answer_key => $answer ) {

							$total_answer_cnt ++;
							$optioncounter ++;

							$this_answer_key = $group_key . GCMS_C_PLUGIN_SEPARATOR . $question_key . GCMS_C_PLUGIN_SEPARATOR . $answer_key;

							// put it together
							$fieldkey = $this_answer_key . GCMS_C_KEYS_LABEL;
							$questions_tab->add_field( array(
								'name'        => sprintf( __( 'Label answer %s', "ictuwp-plugin-maturityscore" ), $optioncounter ),
								'id'          => $fieldkey,
								'type'        => 'text',
								'default'     => $answer->answer_label,
								'description' => ' (id=' . $fieldkey . ', option_key=' . GCMS_C_PLUGIN_KEY . GCMS_C_PLUGIN_SEPARATOR . $group_key . ')',
								'attributes'  => array(
									'required' => 'required',
								),
							) );

							// put it together
							$fieldkey = $this_answer_key . GCMS_C_KEYS_VALUE;
							$questions_tab->add_field( array(
								'type'        => 'text',
								'name'        => sprintf( __( 'Value answer %s', "ictuwp-plugin-maturityscore" ), $optioncounter ),
								'id'          => $fieldkey,
								'default'     => $answer->answer_value,
								'description' => ' (id=' . $fieldkey . ', option_key=' . GCMS_C_PLUGIN_KEY . GCMS_C_PLUGIN_SEPARATOR . $group_key . ')',
								'attributes'  => array(
									'required' => 'required',
								),
							) );

						}

					}

				}

			} else {
				// fout bij het ophalen van de formulierwaarden
				gcmsf_aux_write_to_log( 'Fout bij ophalen van de formulierwaarden' );
			}


			$cmb_options->add_field( array(
				'name'        => _x( 'Email settings', "email settings", "ictuwp-plugin-maturityscore" ),
				'type'        => 'title',
				'id'          => GCMS_C_CMBS2_PREFIX . 'start_section_emailsection',
				'description' => '(id: ' . GCMS_C_CMBS2_PREFIX . 'start_section_emailsection, option_key: ' . GCMS_C_PLUGIN_KEY . ')',

			) );

			$cmb_options->add_field( array(
				'name'        => _x( 'Sender email address', "email settings", "ictuwp-plugin-maturityscore" ),
				'type'        => 'text',
				'id'          => 'mail-from-address',
				'default'     => _x( 'info@gebruikercentraal.nl', "email settings", "ictuwp-plugin-maturityscore" ),
				'description' => '(id: mail-from-address, option_key: ' . GCMS_C_PLUGIN_KEY . ')',
			) );

			$cmb_options->add_field( array(
				'name'        => _x( 'Sender email name', "email settings", "ictuwp-plugin-maturityscore" ),
				'type'        => 'text',
				'id'          => 'mail-from-name',
				'default'     => _x( 'Gebruiker Centraal', "email settings", "ictuwp-plugin-maturityscore" ),
				'description' => '(id: mail-from-name, option_key: ' . GCMS_C_PLUGIN_KEY . ')',
			) );

			$cmb_options->add_field( array(
				'name'        => _x( 'Email subject line', "email settings", "ictuwp-plugin-maturityscore" ),
				'type'        => 'text',
				'id'          => 'mail-subject',
				'description' => '(id: mail-subject, option_key: ' . GCMS_C_PLUGIN_KEY . ')',
				'default'     => _x( 'Link to your survey results', "email settings", "ictuwp-plugin-maturityscore" )
			) );

			$default = sprintf( _x( "Hi %s,\n\nThank you for using this tool.\nFor your future reference, here is a link to the results page of the survey you just filled out:\n%s\n\nKind regards,\nThe Gebruiker Centraal Team.", "email settings", "ictuwp-plugin-maturityscore" ), GCMS_C_NAMEPLACEHOLDER, GCMS_C_URLPLACEHOLDER );

			$cmb_options->add_field( array(
				'name'        => _x( 'Text in the email to the user', "email settings", "ictuwp-plugin-maturityscore" ),
				'description' => '<span style="font-weight: 700; background: white; color: black;">' . sprintf( _x( 'This text should contain these strings:<br>%s - this is the placeholder for the link we send.<br>%s - this is the placeholder for user\'s name.', "email settings", "ictuwp-plugin-maturityscore" ), GCMS_C_URLPLACEHOLDER, GCMS_C_NAMEPLACEHOLDER ) . '</span>',
				'type'        => 'wysiwyg',
				'id'          => GCMS_C_TEXTEMAIL,
				'description' => '(id: ' . GCMS_C_TEXTEMAIL . ', option_key: ' . GCMS_C_PLUGIN_KEY . ')',
				'default'     => $default,
			) );


			$cmb_options->add_field( array(
				'name' => __( 'General scoring texts', "ictuwp-plugin-maturityscore" ),
				'type' => 'title',
				'id'   => GCMS_C_CMBS2_PREFIX . 'start_section_scoring'
			) );

			// reset
			$counter = 0;

			while ( $counter < GCMS_C_SCORE_MAX ) {

				$counter ++;

				$default = sprintf( __( 'The text for scores between %s and %s. ', "ictuwp-plugin-maturityscore" ), ( $counter - 1 ), $counter );
				$label   = sprintf( __( 'between %s and %s', "ictuwp-plugin-maturityscore" ), ( $counter - 1 ), $counter );

				if ( GCMS_C_SCORE_MAX == $counter ) {
					$default = sprintf( __( 'Perfect score: %s!', "ictuwp-plugin-maturityscore" ), $counter );
				}

				$fieldkey = $key . GCMS_SCORESEPARATOR . $counter;

				$cmb_options->add_field( array(
					'name'        => sprintf( _x( 'Score text %s<br><small>if the total score is %s.</small>', 'score range', "ictuwp-plugin-maturityscore" ), $counter, $label ),
					'description' => sprintf( __( 'General average score %s', "ictuwp-plugin-maturityscore" ), $label . ' (id: ' . $fieldkey . ', option_key: ' . GCMS_C_PLUGIN_KEY . ')' ),
					'type'        => 'wysiwyg',
					'id'          => $fieldkey,
					'default'     => $default
				) );


			}


		}

		//====================================================================================================

		/**
		 * Returns data from a survey and the site averages
		 */
		public function gcmsf_data_get_user_answers_and_averages( $postid = 0, $context = '' ) {

			$yourdata       = array();
			$values         = array();
			$user_answers   = array();
			$systemaverages = array();
			$valuescounter  = 0;

			$formfields_data = gcmsf_data_get_survey_json();

			if ( $postid ) {

				$user_answers_raw = get_post_meta( $postid );

				if ( isset( $user_answers_raw[ GCMS_C_FORMKEYS ][0] ) ) {
					$user_answers = maybe_unserialize( $user_answers_raw[ GCMS_C_FORMKEYS ][0] );
				}

				if ( $user_answers ) {

					// first, process users' answers
					foreach ( $user_answers as $key => $value ) {

						// some values we do not need in our data structure
						if (
							( $key == GCMS_C_QUESTION_PREFIX . GCMS_C_SURVEY_CT_ORG_TYPE ) ||
							( $key == GCMS_C_QUESTION_PREFIX . GCMS_C_SURVEY_CT_REGION ) ||
							( $key == GCMS_C_QUESTION_PREFIX . GCMS_C_SURVEY_CT_ORG_SIZE ) ||
							( $key == GCMS_C_QUESTION_PREFIX . GCMS_C_SURVEY_CT_ORG_ATTITUDE ) ||
							( $key == GCMS_C_SURVEY_YOURNAME ) ||
							( $key == GCMS_C_SURVEY_YOURNAME_x ) ||
							( $key == GCMS_C_SURVEY_POSTTITLE ) ||
							( $key == GCMS_C_SURVEY_POSTTITLE_x ) ||
							( $key == GCMS_C_SURVEY_GDPR_CHECK ) ||
							( $key == GCMS_C_SURVEY_GDPR_CHECK_x ) ||
							( $key == GCMS_C_SURVEY_EMAILID_x ) ||
							( $key == GCMS_C_SURVEY_EMAILID )
						) {
							// do not store values in this array for any of the custom taxonomies or for the email / name fields
							continue;
						}

						$array         = array();
						$group         = '';
						$question      = '';
						$answer        = '';
						$collectionkey = GCMS_C_PLUGIN_KEY;

						$constituents = explode( GCMS_C_PLUGIN_SEPARATOR, $value ); // [0] = group, [1] = question, [2] = answer

						if ( isset( $constituents[0] ) ) {
							$group = $constituents[0];
						}
						if ( isset( $constituents[1] ) ) {
							$question = $constituents[1];
						}
						if ( isset( $constituents[2] ) ) {
							$answer = $constituents[2];
						}

						$current_group    = (array) $formfields_data->$group;
						$current_question = (array) $formfields_data->$group->group_questions[0]->$question;
						$current_answer   = (array) $formfields_data->$group->group_questions[0]->$question->question_answers[0]->$answer;


						if ( GCMS_C_FRONTEND_SHOW_AVERAGES ) {
							$current_answer['answer_site_average'] = get_option( $key, 1 );
						}

						if ( $group ) {
							// we need this key to get the translation, looks like gcms__g[x]
							$collectionkey = GCMS_C_PLUGIN_KEY . GCMS_C_PLUGIN_SEPARATOR . $group;
						}


						// translate user's answers
						$current_answer['answer_label'] = gcms_aux_get_value_for_cmb2_key( $value . '_label', $current_answer['answer_label'], $collectionkey );
						$current_answer['answer_value'] = gcms_aux_get_value_for_cmb2_key( $value . '_value', $current_answer['answer_value'], $collectionkey );

						$huidigevraag                     = gcms_aux_get_value_for_cmb2_key( $key, $key, $collectionkey );
						$current_answer['question_label'] = $huidigevraag;
						$array['question_label']          = $huidigevraag;

						$array['question_answer']                 = $current_answer;
						$values['averages']['groups'][ $group ][] = $current_answer['answer_value'];
						$values['all_values'][]                   = $current_answer['answer_value'];
						$values['user_answers'][ $group ][ $key ] = $current_answer;

					}

					if ( $values ) {

						$values['averages']['overall'] = gcms_aux_get_average_for_array( $values['all_values'], 1 );

						unset( $values['all_values'] );

						// get the average per group
						foreach ( $values['averages']['groups'] as $key => $value ) {

							$average                              = gcms_aux_get_average_for_array( $value, 1 );
							$values['averages']['groups'][ $key ] = round( (float)$average, 1 );

							$columns = array();

							$collectionkey = GCMS_C_PLUGIN_KEY . GCMS_C_PLUGIN_SEPARATOR . $key;

							// hiero
							$rowname_translated = gcms_aux_get_value_for_cmb2_key( $key, $key, $collectionkey );

							if ( $key && $rowname_translated ) {

								$key_grouplabel = $key . '_group_label';
								$collectionkey  = GCMS_C_PLUGIN_KEY . GCMS_C_PLUGIN_SEPARATOR . $key;
								$default        = $formfields_data->$key->group_label;

								$columns[ GCMS_C_TABLE_COLNR_TH ]           = gcms_aux_get_value_for_cmb2_key( $key_grouplabel, $default, $collectionkey );
								$columns[ GCMS_C_TABLE_COLNR_USER_AVERAGE ] = $average;
//							$columns[ GCMS_C_TABLE_COLNR_USER_CUMULAT ] = $average;

								if ( GCMS_C_FRONTEND_SHOW_AVERAGES ) {
									$columns[ GCMS_C_TABLE_COLNR_SITE_AVERAGE ] = get_option( $key, 1 );
								}

								$values['rows'][ $key ] = $columns;

							}
						}

						// labels for the column headers
						$values['cols'][ GCMS_C_TABLE_COLNR_TH ] = _x( "Chapter", "table header", "ictuwp-plugin-maturityscore" );

						if ( $postid ) {
							$values['cols'][ GCMS_C_TABLE_COLNR_USER_AVERAGE ] = _x( "Your score", "table header", "ictuwp-plugin-maturityscore" );
						}

						if ( GCMS_C_FRONTEND_SHOW_CUMULATE ) {
//						$values['cols'][ GCMS_C_TABLE_COLNR_USER_CUMULAT ] = _x( "Score cumulated", "table header", "ictuwp-plugin-maturityscore" );
						}

						if ( GCMS_C_FRONTEND_SHOW_AVERAGES ) {
							$values['cols'][ GCMS_C_TABLE_COLNR_SITE_AVERAGE ] = _x( "Average score", "table header", "ictuwp-plugin-maturityscore" );
						}

						// add the default section titles to the collection
						foreach ( $formfields_data as $group_key => $value ) {
							$key_grouplabel                              = $group_key . '_group_label';
							$values['default_titles'][ $key_grouplabel ] = $value->group_label;
						}
					}
				}
			}

			return $values;

		}

		//========================================================================================================

		/**
		 * Register frontend styles
		 */

		public function gcmsf_frontend_register_frontend_style_script() {

			global $formfields_data;

			if ( ! is_admin() ) {
				$postid = get_the_ID();

				if ( ! $this->survey_data ) {
					$this->survey_data = $this->gcmsf_data_get_user_answers_and_averages( $postid );
				}

				if ( ! $formfields_data ) {
//				echo '<h1>ACH EN WEE formfields_data IS LEDIG</h1>';
					$formfields_data = gcmsf_data_get_survey_json();
				}

				$infooter = false;


				wp_enqueue_style( 'ictuwp-plugin-maturityscore-frontend', GCMS_C_ASSETS_URL . 'css/ictuwp-plugin-maturityscore.css', array(), GCMS_C_VERSION, $infooter );
				// contains minified versions of amcharts js files
				wp_enqueue_script( 'gcms-action-js', GCMS_C_ASSETS_URL . 'js/min/functions-min.js', array( 'jquery' ), GCMS_C_VERSION, $infooter );
				// get the graph for this user
				$mykeyname          = 'maturity_score';
				$yourscore_color    = '#FF7700';  // see also @starcolor_gold
				$averagescore_color = '#FF0000';  // as red as it gets

				if ( $this->survey_data ) {

					$averages = '';

					if ( GCMS_C_FRONTEND_SHOW_AVERAGES ) {
						$averages = '{
						balloonColor: "' . $averagescore_color . '",
						balloonText: "Gemiddelde score: [[value]]",
						bullet: "custom",
						bulletBorderThickness: 1,
						bulletOffset: -1,
						bulletSize: 18,
						customBullet: "' . GCMS_C_ASSETS_URL . 'images/star.svg",
						customMarker: "",
						fillAlphas: 0.15,
						fillColors: "' . $averagescore_color . '",
						id: "AmGraph-2",
						lineColor: "' . $averagescore_color . '",
						title: "graph 2",
						valueField: "' . _x( "Average score", "labels", "ictuwp-plugin-maturityscore" ) . '"
					},';
					}


					if ( GCMS_C_FRONTEND_SHOW_AVERAGES ) {
						$averages = '{
						"fillAlphas": 0.31,
						"fillColors": "' . $averagescore_color . '",
						"id": "AmGraph-2",
						"lineColor": "' . $averagescore_color . '",
						"title": "graph 2",
						"valueField": "Lalal gemiddelde score",
						"customBullet": "' . GCMS_C_ASSETS_URL . 'images/star2.svg",
						"bulletSize": 18,
						"bullet": "custom",
						"customMarker": "",
						"balloonText": "Gemiddelde score: [[value]]"
					},';
					}

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
						' . $averages . '
						{
							"balloonColor": "' . $yourscore_color . '",
							"balloonText": "Jouw score: [[value]]",
							"bullet": "custom",
							"bulletBorderThickness": 1,
							"bulletOffset": -1,
							"bulletSize": 18,
							"customBullet": "' . GCMS_C_ASSETS_URL . '/images/star.svg",
							"customMarker": "",
							"fillAlphas": 0.15,
							"fillColors": "' . $yourscore_color . '",
							"id": "AmGraph-1",
							"lineColor": "' . $yourscore_color . '",
							"valueField": "' . _x( "your score", "labels", "ictuwp-plugin-maturityscore" ) . '"
						}
					],
					"guides": [],
					"valueAxes": [
					{
						"axisTitleOffset": 20,
						"id": "ValueAxis-1",
						"minimum": 0,
						"maximum": 5,
						"zeroGridAlpha": 2,
						"axisAlpha": 0.76,
						"axisColor": "#6B6B6B",
						"axisThickness": 2,
						"dashLength": 0,
						"fillAlpha": 0.49,
						"gridAlpha": 0.68,
						"gridColor": "#6B6B6B",
						"minorGridAlpha": 0.4,
						"minorGridEnabled": false
					},
					{
						"id": "ValueAxis-2",
						"dashLength": 0,
						"fillAlpha": 0.43,
						"gridAlpha": 0.44,
						"gridColor": "' . $yourscore_color . '",
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
					}' );

					$columncounter = 0;
					$rowcounter    = 0;

					$radardata->graphs[ ( GCMS_C_TABLE_COLNR_USER_AVERAGE - 1 ) ]->valueField  = $this->survey_data['cols'][ GCMS_C_TABLE_COLNR_USER_AVERAGE ];
					$radardata->graphs[ ( GCMS_C_TABLE_COLNR_USER_AVERAGE - 1 ) ]->balloonText = $this->survey_data['cols'][ GCMS_C_TABLE_COLNR_USER_AVERAGE ] . ': [[value]]';

					if ( GCMS_C_FRONTEND_SHOW_AVERAGES ) {
						$radardata->graphs[ ( GCMS_C_TABLE_COLNR_SITE_AVERAGE - 1 ) ]->valueField  = $this->survey_data['cols'][ GCMS_C_TABLE_COLNR_SITE_AVERAGE ];
						$radardata->graphs[ ( GCMS_C_TABLE_COLNR_SITE_AVERAGE - 1 ) ]->balloonText = $this->survey_data['cols'][ GCMS_C_TABLE_COLNR_SITE_AVERAGE ] . ': [[value]]';

					} else {
						unset( $radardata->graphs[1] );
					}
					$columncounter           = 0;
					$radardata->dataProvider = array();

					foreach ( $this->survey_data['rows'] as $rowname => $rowvalue ) {

						// go through all rows
						$jouwscore       = isset( $rowvalue[ GCMS_C_TABLE_COLNR_USER_AVERAGE ] ) ? $rowvalue[ GCMS_C_TABLE_COLNR_USER_AVERAGE ] : 0;
						$gemiddeldescore = isset( $rowvalue[ GCMS_C_TABLE_COLNR_SITE_AVERAGE ] ) ? $rowvalue[ GCMS_C_TABLE_COLNR_SITE_AVERAGE ] : 0;
						$columncounter   = 0;

						foreach ( $this->survey_data['cols'] as $columname => $columnsvalue ) {

							$collectionkey      = GCMS_C_PLUGIN_KEY . GCMS_C_PLUGIN_SEPARATOR . $rowcounter;
							$rowname_translated = gcms_aux_get_value_for_cmb2_key( $rowname, $rowname, $collectionkey );

							if ( $rowname && $rowname_translated ) {

								$key_grouplabel = $rowname . '_group_label';
								$collectionkey  = GCMS_C_PLUGIN_KEY . GCMS_C_PLUGIN_SEPARATOR . $rowname;
								$default        = $formfields_data->$rowname->group_label;

								$radardata->dataProvider[$rowcounter]				= new stdClass();
//$melebeltje = gcms_aux_get_value_for_cmb2_key( $key_grouplabel, $default, $collectionkey );
//echo '$melebeltje: "' . $melebeltje . '" (mykeyname=' . $mykeyname . ') <br>';

								$radardata->dataProvider[ $rowcounter ]->$mykeyname = gcms_aux_get_value_for_cmb2_key( $key_grouplabel, $default, $collectionkey );

								if ( $columncounter == 2 ) {
									$radardata->dataProvider[ $rowcounter ]->$columnsvalue = '';
									if ( GCMS_C_FRONTEND_SHOW_AVERAGES ) {
										$radardata->dataProvider[ $rowcounter ]->$columnsvalue = $gemiddeldescore;
									}
								} elseif ( $columncounter == 1 ) {
									$radardata->dataProvider[ $rowcounter ]->$columnsvalue = $jouwscore;
								}
								$columncounter ++;
							}
						}

						$rowcounter ++;

					}

					$thedata = wp_json_encode( $radardata );

					if ( WP_DEBUG ) {
						/*
						echo '<pre>';
						var_dump( $radardata );
						echo '</pre>';
						*/
					}

					wp_add_inline_script( 'gcms-action-js',
						'try {
						var amchart1 = AmCharts.makeChart( "amchart1",
						' . $thedata . ' );
					}
					catch( err ) { console.log( err ); } ' );


				}
			}
		}



		//========================================================================================================

		/**
		 * Output the HTML
		 */
		public function gcmsf_frontend_display_survey_results( $postid ) {

			$returnstring = '';

			if ( ! $this->survey_data ) {
				$this->survey_data = $this->gcmsf_data_get_user_answers_and_averages( $postid );
			}

			if ( $this->survey_data ) {

				$returnstring .= $this->gcmsf_frontend_get_graph( false );
				$returnstring .= $this->gcmsf_frontend_get_interpretation( false );
				$returnstring .= $this->gcmsf_frontend_get_tableview( false );

			} else {
				$returnstring .= '<p>' . __( "Oopsy daisy. There are no data to display. The survey you requested is empty. The server may have made an error in saving or retrieving the data, or more likely: our developer botched up. It's not your fault.", "ictuwp-plugin-maturityscore" ) . '</p>';
			}

			return $returnstring;

		}

		//========================================================================================================

		public function gcmsf_frontend_append_comboboxes() {

			if ( GCMS_C_PLUGIN_USE_CMB2 ) {

				if ( ! defined( 'CMB2_LOADED' ) ) {
					return false;
					die( ' CMB2_LOADED not loaded ' );
					// cmb2 NOT loaded
				}

				add_shortcode( 'gcms_survey', 'gcmsf_frontend_register_shortcode' );

				add_action( 'cmb2_init', array( $this, 'gcmsf_frontend_form_register_cmb2_form' ) );
				add_action( 'cmb2_after_init', 'gcmsf_frontend_form_handle_posting' );

				add_action( 'cmb2_admin_init', array( $this, 'gcmsf_admin_form_register_cmb2_form_new' ) );

			}  // GCMS_C_PLUGIN_USE_CMB2

		}

		//====================================================================================================

		/**
		 * Register the form and fields for our front-end submission form
		 */
		public function gcmsf_frontend_form_register_cmb2_form() {

			$cmb = new_cmb2_box( array(
				'id'           => GCMS_C_METABOX_ID,
				'title'        => __( "Questions and answers", "ictuwp-plugin-maturityscore" ),
				'object_types' => array( 'post' ),
				'hookup'       => false,
				'save_fields'  => true,
				'cmb_styles'   => false, // false to disable the CMB stylesheet
			) );

			$sectiontitle_prev = '';
			$formfields_data   = gcmsf_data_get_survey_json();

			if ( $formfields_data ) {

				// loop through the groups
				foreach ( $formfields_data as $group_key => $value ) {

					$collectionkey         = GCMS_C_PLUGIN_KEY . GCMS_C_PLUGIN_SEPARATOR . $group_key;
					$key_grouplabel        = $group_key . '_group_label';
					$key_group_description = $group_key . '_group_description';
					$grouplabel            = gcms_aux_get_value_for_cmb2_key( $key_grouplabel, $value->group_label, $collectionkey );
					$groupdescription      = gcms_aux_get_value_for_cmb2_key( $key_group_description, $value->group_description, $collectionkey );
					$groupquestions        = (array) $value->group_questions[0];

					$cmb->add_field( array(
						'name'        => $grouplabel,
						'description' => $groupdescription,
						'type'        => 'title',
						'id'          => GCMS_C_CMBS2_PREFIX . '_section_' . $group_key
					) );

					// loop through the questions
					foreach ( $groupquestions as $question_key => $question_single ) {

						$options        = array();
						$selectrandom   = array();
						$default        = '';
						$answers        = (array) $question_single->question_answers[0];
						$question_label = $question_single->question_label;


						// get all possible answers
						foreach ( $answers as $answer_key => $answer ) {

							$this_answer_key = $group_key . GCMS_C_PLUGIN_SEPARATOR . $question_key . GCMS_C_PLUGIN_SEPARATOR . $answer_key;

							$label = gcms_aux_get_value_for_cmb2_key(
								$this_answer_key . GCMS_C_KEYS_LABEL,
								$answer->answer_label,
								$collectionkey
							);


							$options[ $this_answer_key ] = $label;
							$selectrandom[]              = $this_answer_key;

						}

						// only set a random default if we are debugging
						if ( WP_DEBUG && GCMS_C_PLUGIN_DO_DEBUG ) {
							$default = $selectrandom[ array_rand( $selectrandom ) ];
						}

						$questionkey    = $group_key . GCMS_C_PLUGIN_SEPARATOR . $question_key;
						$question_label = $question_single->question_label;

						$label = gcms_aux_get_value_for_cmb2_key(
							$questionkey,
							$question_label,
							$collectionkey
						);

						// put it together
						$cmb->add_field( array(
							'name'       => $label,
							'id'         => $group_key . GCMS_C_PLUGIN_SEPARATOR . $question_key,
							'type'       => 'radio',
							'options'    => $options,
							'default'    => $default,
							'attributes' => array(
								'required' => 'required',
							),

						) );

					}
				}
			} else {
				// fout bij het ophalen van de formulierwaarden
				gcmsf_aux_write_to_log( 'Fout bij ophalen van de formulierwaarden' );
			}


			$yournamedefault = gcmsf_get_post_or_cookie( GCMS_C_SURVEY_YOURNAME );
			$yourmaildefault = gcmsf_get_post_or_cookie( GCMS_C_SURVEY_EMAILID );

			$cmb->add_field( array(
				'name' => _x( 'About you', 'About you section', "ictuwp-plugin-maturityscore" ),
				'type' => 'title',
				'id'   => GCMS_C_CMBS2_PREFIX . 'start_section_survey_user_data',
				'desc' => _x( 'We can send you a link to your survey results if you fill in your email address here.', 'About you section', "ictuwp-plugin-maturityscore" ),
			) );


			$cmb->add_field( array(
				'name'    => _x( 'Your name', 'About you section', "ictuwp-plugin-maturityscore" ),
				'id'      => GCMS_C_SURVEY_YOURNAME,
				'type'    => 'text',
				'desc'    => _x( "We store your name for a maximum of 3 years; it will be visible on the resultpage. Leave this empty if you don't want this.", 'About you section', "ictuwp-plugin-maturityscore" ) . '<br>' . '<span class="not-required">' . _x( "(not required)", "field validation", "ictuwp-plugin-maturityscore" ) . '</span>',
				'default' => $yournamedefault,
			) );


			$cmb->add_field( array(
				'name'    => _x( 'Your emailaddress', 'About you section', "ictuwp-plugin-maturityscore" ),
				'id'      => GCMS_C_SURVEY_EMAILID,
				'type'    => 'text_email',
				'desc'    => _x( 'We use this email address to email you a link to your result page.', 'About you section', "ictuwp-plugin-maturityscore" ) . '<br>' . '<span class="not-required">' . _x( "(not required)", "field validation", "ictuwp-plugin-maturityscore" ) . '</span>',
				'default' => $yourmaildefault,
			) );


			$cmb->add_field( array(
				'name' => _x( 'Permission to store emailaddress', 'About you section', "ictuwp-plugin-maturityscore" ),
				'desc' => _x( 'Yes, keep me up to date about any developments regarding the maturity model system. You may store my email address and name for at most 3 years. Only site-admins have access to these data.', 'About you section', "ictuwp-plugin-maturityscore" ) . '<br>' . '<span class="not-required">' . _x( "(not required)", "field validation", "ictuwp-plugin-maturityscore" ) . '</span>',
				'id'   => GCMS_C_SURVEY_GDPR_CHECK,
				'type' => 'checkbox',
			) );

			$default = '';

			// organisation types
			$terms = get_terms( array(
				'taxonomy'   => GCMS_C_SURVEY_CT_ORG_TYPE,
				'orderby'    => 'term_order',
				'hide_empty' => false,
			) );

			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$options = array();
				$taxinfo = get_taxonomy( GCMS_C_SURVEY_CT_ORG_TYPE );

				foreach ( $terms as $term ) {
					$options[ $term->term_id ] = $term->name;
					// $default = $term->term_id;
				}

				$cmb->add_field( array(
					'name'    => $taxinfo->labels->singular_name,
					'id'      => GCMS_C_QUESTION_PREFIX . GCMS_C_SURVEY_CT_ORG_TYPE,
					'type'    => 'radio',
					'options' => $options,
					'default' => $default,
					'desc'    => '<span class="not-required">' . _x( "(not required)", "field validation", "ictuwp-plugin-maturityscore" ) . '</span>',
				) );

			}

			// regio's
			$terms = get_terms( array(
				'taxonomy'   => GCMS_C_SURVEY_CT_REGION,
				'orderby'    => 'term_order',
				'hide_empty' => false,
			) );

			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$options = array();
				$taxinfo = get_taxonomy( GCMS_C_SURVEY_CT_REGION );

				foreach ( $terms as $term ) {
					$options[ $term->term_id ] = $term->name;
					// $default = $term->term_id;
				}

				$cmb->add_field( array(
					'name'    => $taxinfo->labels->singular_name,
					'id'      => GCMS_C_QUESTION_PREFIX . GCMS_C_SURVEY_CT_REGION,
					'type'    => 'radio',
					'options' => $options,
					'default' => $default,
					'desc'    => '<span class="not-required">' . _x( "(not required)", "field validation", "ictuwp-plugin-maturityscore" ) . '</span>',
				) );

			}


			// organisatiegrootte
			$terms = get_terms( array(
				'taxonomy'   => GCMS_C_SURVEY_CT_ORG_SIZE,
				'orderby'    => 'term_order',
				'hide_empty' => false,
			) );

			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$options = array();
				$taxinfo = get_taxonomy( GCMS_C_SURVEY_CT_ORG_SIZE );

				foreach ( $terms as $term ) {
					$options[ $term->term_id ] = $term->name;
					// $default = $term->term_id;
				}

				$cmb->add_field( array(
					'name'    => $taxinfo->labels->singular_name,
					'id'      => GCMS_C_QUESTION_PREFIX . GCMS_C_SURVEY_CT_ORG_SIZE,
					'type'    => 'radio',
					'options' => $options,
					'default' => $default,
					'desc'    => '<span class="not-required">' . _x( "(not required)", "field validation", "ictuwp-plugin-maturityscore" ) . '</span>',
				) );

			}


			// organisatieattitude
			$terms = get_terms( array(
				'taxonomy'   => GCMS_C_SURVEY_CT_ORG_ATTITUDE,
				'orderby'    => 'term_order',
				'hide_empty' => false,
			) );

			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$options = array();
				$taxinfo = get_taxonomy( GCMS_C_SURVEY_CT_ORG_ATTITUDE );

				foreach ( $terms as $term ) {
					$options[ $term->term_id ] = $term->name;
					// $default = $term->term_id;
				}

				$cmb->add_field( array(
					'name'    => $taxinfo->labels->singular_name,
					'id'      => GCMS_C_QUESTION_PREFIX . GCMS_C_SURVEY_CT_ORG_ATTITUDE,
					'type'    => 'radio',
					'options' => $options,
					'default' => $default,
					'desc'    => '<span class="not-required">' . _x( "(not required)", "field validation", "ictuwp-plugin-maturityscore" ) . '</span>',
				) );

			}

			// Honeypot
			$cmb->add_field( array(
				'name'    => GCMS_C_HONEYPOT_LABEL,
				'id'      => GCMS_C_HONEYPOT,
				'type'    => 'text',
				'default' => '',
				'attributes' => array(
					'autocomplete' => 'new-password',
					'tabindex'     => '-1',
					'size'         => '40',
					'style'        => 'display: none !important; visibility: hidden !important;'
				),
				'classes' => 'visuallyhidden honingpot'
			) );

		}

		//====================================================================================================

		private function gcmsf_frontend_get_graph( $doecho = false ) {

			if ( ! $this->survey_data ) {
				$this->survey_data = $this->gcmsf_data_get_user_answers_and_averages( $postid );
			}

			$return = '';

			if ( $this->survey_data ) {

				$titleid = 'grafiek_amchart';

				$return .= '<div class="radarchart" id="amchart1" style="min-height: 500px; width: 100%" aria-labelledby="' . $titleid . '"></div>';
				$return .= '<p id="' . $titleid . '">';
				$return .= '<span class="visuallyhidden">';
				$return .= _x( "Radar graph with your score in this survey.", "table description", "ictuwp-plugin-maturityscore" );
				$return .= '</span>';


				if ( GCMS_C_FRONTEND_SHOW_AVERAGES ) {
					$return .= _x( "Your score in red; average score in orange.", "table description", "ictuwp-plugin-maturityscore" );
				} else {
					$return .= _x( "Your score in orange.", "table description", "ictuwp-plugin-maturityscore" );
				}
				$return .= '</p>';

			} else {
				$return = '<p>' . _x( "No data available. Not your fault, blame the server.", "no data error", "ictuwp-plugin-maturityscore" ) . '<p>';
			}


			if ( $doecho ) {
				echo $return;
			} else {
				return $return;
			}

		}

		//====================================================================================================

		private function gcmsf_frontend_get_percentage( $score = 0, $max = 5, $doecho = false ) {

			$return = '';

			if ( $max ) {

				$counter      = 0;
				$scorerounded = round( (float)$score, 0 );

				$displayvalue = intval( 100 / $max ); // percentage
//			$return       = ( floor( $score ) * floor( $displayvalue ) ) . '%';
//			$return .= ' (score: ' . $score . ', rounded: ' . $scorerounded . ')';

				$return .= '<div class="star-rating">';

				while ( $counter < $max ) {

					if ( $scorerounded > $counter ) {
						$return .= '<svg style="display: none;" class="ja" height="494" viewBox="0 0 507 494" width="507" xmlns="http://www.w3.org/2000/svg"><path d="m253.5 408.75-156.6447697 84.207131 29.9164887-178.353566-126.72828059-126.310696 175.13417659-26.021434 78.322385-162.271435 78.322385 162.271435 175.134177 26.021434-126.728281 126.310696 29.916489 178.353566z" fill="#ffffff" fill-rule="evenodd" stroke="#000"/></svg>';
					} else {
						$return .= '<svg style="display: none;" class="nee" height="494" viewBox="0 0 507 494" width="507" xmlns="http://www.w3.org/2000/svg"><path d="m253.5 408.75-156.6447697 84.207131 29.9164887-178.353566-126.72828059-126.310696 175.13417659-26.021434 78.322385-162.271435 78.322385 162.271435 175.134177 26.021434-126.728281 126.310696 29.916489 178.353566z" fill="#ffffff" fill-rule="evenodd" stroke="#000"/></svg>';
					}
					$counter ++;

				}

				$return .= '</div>';

			}

			if ( $doecho ) {
				echo $return;
			} else {
				return $return;
			}

		}

		//====================================================================================================

		private function gcmsf_frontend_get_interpretation( $userdata = array(), $doecho = false ) {

			if ( ! $this->survey_data ) {
				$this->survey_data = $this->gcmsf_data_get_user_answers_and_averages( $postid );
			}

			$return = '';

			if ( $this->survey_data ) {

				if ( isset( $this->survey_data['averages'] ) ) {

					$overall_average = number_format_i18n( $this->survey_data['averages']['overall'], 1 );

					$key                     = 'SITESCORE';
					$total_number_of_surveys = get_option( GCMS_C_AVGS_NR_SURVEYS, 1 );
					$site_average            = get_option( GCMS_C_AVGS_OVERALL_AVG, 1 );
					$collectionkey           = GCMS_C_PLUGIN_KEY . GCMS_C_PLUGIN_SEPARATOR . $key;

					$punten   = sprintf( _n( '%s point', '%s points', $overall_average, "ictuwp-plugin-maturityscore" ), $overall_average );
					$fieldkey = $key . GCMS_SCORESEPARATOR . number_format_i18n( $this->survey_data['averages']['overall'], 0 );

					$return .= '<p>' . sprintf( __( 'Your average score was %s. ', "ictuwp-plugin-maturityscore" ), $punten );

					if ( GCMS_C_FRONTEND_SHOW_AVERAGES ) {
						$return .= sprintf( _n( 'Thus far, we received %s survey, ', 'Thus far, we received %s surveys, ', $total_number_of_surveys, "ictuwp-plugin-maturityscore" ), $total_number_of_surveys );
						$return .= sprintf( __( ' and the overall average score is %s. ', "ictuwp-plugin-maturityscore" ), $site_average );
					}

					$return .= '</p>';
					$return .= '<p>' . gcms_aux_get_value_for_cmb2_key( $fieldkey, '', GCMS_C_PLUGIN_KEY ) . '</p>';
					$return .= '<h2>' . __( 'Score per section', "ictuwp-plugin-maturityscore" ) . '</h2>';

					$counter = 0;

					foreach ( $this->survey_data['user_answers'] as $key => $value ) {

						$counter ++;

						$jouwgemiddelde = $this->survey_data['averages']['groups'][ $key ];
						$collectionkey  = GCMS_C_PLUGIN_KEY . GCMS_C_PLUGIN_SEPARATOR . $key;
						$jouwscore      = number_format_i18n( $jouwgemiddelde, 1 );

						$thesectionid   = sanitize_title( $key . "_" . $counter );
						$titleid        = sanitize_title( $thesectionid . '_title' );
						$key_grouplabel = $key . '_group_label';

						$fieldkey       = $key . GCMS_SCORESEPARATOR . round( (float)$jouwscore, 0 ); // g2_score_4
						$key_grouplabel = $key . '_group_label';

						$titel = gcms_aux_get_value_for_cmb2_key( $key_grouplabel, $this->survey_data['default_titles'][ $key_grouplabel ], $collectionkey );

						$return .= '<section aria-labelledby="' . $titleid . '" id="' . $thesectionid . '" class="survey-result">';
						$return .= '<h3 class="rating-section-title"><span id="' . $titleid . '">' . $titel;
						$return .= ' <span class="visuallyhidden">' . sprintf( _x( ' - your score: %s', 'Your score for visually impaired users', "ictuwp-plugin-maturityscore" ), $jouwgemiddelde ) . '</span> ' . $this->gcmsf_frontend_get_percentage( $jouwgemiddelde, GCMS_C_SCORE_MAX );

						$return .= '</h3>';
						$return .= '<p>' . gcms_aux_get_value_for_cmb2_key( $fieldkey, $fieldkey, $collectionkey ) . '</p>';
						$return .= '<details>';
						$return .= '  <summary>' . _x( "Review your answers", "interpretatie", "ictuwp-plugin-maturityscore" ) . '</summary>';

						if ( $value ) {
							$return .= '<dl>';

							foreach ( $value as $vragen => $antwoorden ) {
								$return .= '<dt>' . _x( "Question", "interpretatie", "ictuwp-plugin-maturityscore" ) . '</dt>';
								$return .= '<dd>' . $antwoorden['question_label'] . '</dd>';

								$return .= '<dt>' . _x( "Your answer", "interpretatie", "ictuwp-plugin-maturityscore" ) . '</dt>';
								$return .= '<dd>' . $antwoorden['answer_label'] . '</dd>';

								$score = sprintf( _n( '%s point', '%s points', $antwoorden['answer_value'], "ictuwp-plugin-maturityscore" ), $antwoorden['answer_value'] );

								if ( GCMS_C_FRONTEND_SHOW_AVERAGES ) {
									$return .= '<dt>' . _x( "Score", "interpretatie", "ictuwp-plugin-maturityscore" ) . '</dt>';
									$return .= '<dd>' . $score . '</dd>';

									$return .= '<dt class="space-me">' . _x( "Average score", "interpretatie", "ictuwp-plugin-maturityscore" ) . '</dt>';
									$return .= '<dd class="space-me">' . $antwoorden['answer_site_average'] . '</dd>';
								} else {
									$return .= '<dt>' . _x( "Score", "interpretatie", "ictuwp-plugin-maturityscore" ) . '</dt>';
									$return .= '<dd class="space-me">' . $score . '</dd>';
								}
							}
							$return .= '</dl>';

						}

						$return .= '</details>';
						$return .= '</section>';

					}
				}
			} else {
				$return .= '<p>' . _x( "No data available. Not your fault, blame the server.", "no data error", "ictuwp-plugin-maturityscore" ) . '<p>';
			}

			if ( $doecho ) {
				echo $return;
			} else {
				return $return;
			}

		}

		//====================================================================================================

		private function gcmsf_frontend_get_tableview( $doecho = false ) {

			$return = '';

			if ( ! $this->survey_data ) {
				$this->survey_data = $this->gcmsf_data_get_user_answers_and_averages( $postid );
			}

			if ( $this->survey_data ) {

				if ( isset( $this->survey_data['cols'] ) ) {

					$return .= '	<table class="gcms-score">' . "\n";

					if ( GCMS_C_FRONTEND_SHOW_AVERAGES ) {
						$return .= '<caption>' . _x( "Average score and your score per section", "table description", "ictuwp-plugin-maturityscore" ) . "</caption>\n";
					} else {
						$return .= '<caption>' . _x( "Your score per section", "table description", "ictuwp-plugin-maturityscore" ) . "</caption>\n";
					}
					$return .= '<tr>';

					foreach ( $this->survey_data['cols'] as $key => $value ) {
						$return .= '<th scope="col">' . $value . "</th>\n";
					}
					$return .= "</tr>\n";

					if ( isset( $this->survey_data['rows'] ) ) {

						$usercumulate    = 0;
						$overallcumulate = 0;
						$rowcounter      = 0;

						foreach ( $this->survey_data['rows'] as $key => $value ) {

							$rowcounter ++;

							$return       .= '<tr>';
							$return       .= '<th scope="row">' . $value[ GCMS_C_TABLE_COLNR_TH ] . '</th>';
							$return       .= '<td>' . number_format_i18n( $value[ GCMS_C_TABLE_COLNR_USER_AVERAGE ], 1 ) . "</td>";
							$usercumulate = $usercumulate + $value[ GCMS_C_TABLE_COLNR_USER_AVERAGE ];
							if ( GCMS_C_FRONTEND_SHOW_AVERAGES ) {
								$return          .= '<td>' . number_format_i18n( $value[ GCMS_C_TABLE_COLNR_SITE_AVERAGE ], 1 ) . "</td>";
								$overallcumulate = $overallcumulate + $value[ GCMS_C_TABLE_COLNR_SITE_AVERAGE ];
							}
							$return .= "</tr>\n";
						}

						$overalltext = sprintf( _x( '%s average', 'table user average score', "ictuwp-plugin-maturityscore" ), number_format_i18n( ( $usercumulate / $rowcounter ), 1 ) );

						$return .= '<tr>';
						$return .= '<th scope="row">' . _x( "Total", "table description", "ictuwp-plugin-maturityscore" ) . '</th>';
						$return .= '<td>' . number_format_i18n( $usercumulate, 1 ) . " (" . $overalltext . ")<br>" . $this->gcmsf_frontend_get_percentage( number_format_i18n( ( $usercumulate / $rowcounter ), 1 ), GCMS_C_SCORE_MAX, false ) . " </td>";

						if ( GCMS_C_FRONTEND_SHOW_AVERAGES ) {
							$overalltext = sprintf( _x( '%s average', 'table overall average score', "ictuwp-plugin-maturityscore" ), number_format_i18n( ( $overallcumulate / $rowcounter ), 1 ) );
							$return      .= '<td>' . number_format_i18n( $overallcumulate, 1 ) . " (" . $overalltext . ")<br>" . $this->gcmsf_frontend_get_percentage( $overallcumulate / $rowcounter, GCMS_C_SCORE_MAX, false ) . " </td>";
						}
						$return .= "</tr>\n";
//array_rand

					}
					$return .= "</table>\n";
				} else {
					$return .= '<p>' . _x( "No data available. Not your fault, blame the server.", "no data error", "ictuwp-plugin-maturityscore" ) . '<p>';
				}
			}

			if ( $doecho ) {
				echo $return;
			} else {
				return $return;
			}

		}

		//====================================================================================================

		/**
		 * Modify page content if using a specific page template.
		 */
		public function gcmsf_frontend_use_page_template() {

			global $post;

			$page_template = get_post_meta( get_the_ID(), '_wp_page_template', true );

			if ( $this->templatefile == $page_template ) {

				add_action( 'genesis_entry_content', 'gcmsf_do_frontend_form_submission_shortcode_echo', 15 );

				//* Force full-width-content layout
				add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );

			}
		}


	}

//========================================================================================================

endif;

//========================================================================================================

add_action( 'plugins_loaded', array( 'ictuwp_plugin_maturityscore_Plugin', 'init' ), 10 );

//========================================================================================================

/**
 * Handle the gcmsf_survey shortcode
 *
 * @param array $atts Array of shortcode attributes
 *
 * @return string       Form html
 */

function gcmsf_frontend_register_shortcode( $atts = array() ) {

	// Get CMB2 metabox object
	$cmb = gcmsf_frontend_cmb2_get();

	// Get $cmb object_types
	$post_types = $cmb->prop( 'object_types' );

	// Initiate our output variable
	$output = '';

	// Get any submission errors
	if ( ( $error = $cmb->prop( 'submission_error' ) ) && is_wp_error( $error ) ) {
		// If there was an error with the submission, add it to our ouput.
		$output .= '<h2>' . sprintf( __( 'Your survey is not saved; errors occurred: %s', "ictuwp-plugin-maturityscore" ), '<strong>' . $error->get_error_message() . '</strong>' ) . '</h2>';
	}


	// Get our form
	$output .= cmb2_get_metabox_form( $cmb, GCMS_CMB2_RANDOM_OBJECT_ID, array( 'save_button' => __( "Submit", "ictuwp-plugin-maturityscore" ) ) );

	return $output;

}

//========================================================================================================

/**
 * Gets the front-end-post-form cmb instance
 *
 * @return CMB2 object
 */
function gcmsf_frontend_cmb2_get() {

	// Use ID of metabox in gcmsf_frontend_form_register_cmb2_form
	$metabox_id = GCMS_C_METABOX_ID;

	// Post/object ID is not applicable since we're using this form for submission
	$object_id = GCMS_CMB2_RANDOM_OBJECT_ID;

	// Get CMB2 metabox object
	return cmb2_get_metabox( $metabox_id, $object_id );

}

//========================================================================================================

/**
 * Handles form submission on save. Redirects if save is successful, otherwise sets an error message as a cmb property
 *
 * @return void
 */
function gcmsf_frontend_form_handle_posting() {

	// If no form submission, bail
	if ( empty( $_POST ) || ! isset( $_POST['submit-cmb'], $_POST['object_id'] ) ) {
		return false;
	}

	// Get CMB2 metabox object
	$cmb = gcmsf_frontend_cmb2_get();

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
		return $cmb->prop( 'submission_error', new WP_Error( 'security_fail', __( "Security checks for your form submission failed. Your data will be discarded.", "ictuwp-plugin-maturityscore" ) ) );
	}


	/**
	 * Fetch sanitized values
	 */
	$sanitized_values = $cmb->get_sanitized_values( $_POST );


	/**
	 * Check HoneyPot
	 * 
	 * This field lures spambots to fill it in by 
	 * 1) being empty 
	 * 2) having some 'generic' name/label like "Email Name ID"
	 * 
	 * We HIDE this field and (strongly!) advise to NOT fill it in
	 * so that Humans will leave it blank.
	 * (Dumb) spam-bots, however, are very greedy and will fill it in.
	 * 
	 * Therefore: when it is NOT EMPTY we assume a spam-entry and ignore it...
	 */
	if ( ! empty( $sanitized_values[GCMS_C_HONEYPOT] ) ) {
		// return $cmb->prop( 'submission_error', new WP_Error( 'security_fail', __( "Security checks for your form submission failed. Your data will be discarded.", "ictuwp-plugin-maturityscore" ) ) );
		// Do not even show an error, just silently bail...
		return false;
	}

	$datum = date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) );

	// Check name submitted
	if ( empty( $_POST[ GCMS_C_SURVEY_YOURNAME ] ) ) {
		$sanitized_values[ GCMS_C_SURVEY_POSTTITLE ] = __( "Your organisation's score", "ictuwp-plugin-maturityscore" ) . ' (' . $datum . ')';
	} else {
		$sanitized_values[ GCMS_C_SURVEY_POSTTITLE ] = $sanitized_values[ GCMS_C_SURVEY_YOURNAME ] . ' (' . $datum . ')';
	}

//	$rand   = $aantalenquetes . '-' . substr( md5( microtime() ),rand( 0, 26 ), 20 );
	$rand = substr( md5( microtime() ), rand( 0, 26 ), 20 );

	// Current user
	$user_id = get_current_user_id();

	// Set our post data arguments
	$post_data['post_title']  = $sanitized_values[ GCMS_C_SURVEY_POSTTITLE ];
	$post_data['post_name']   = sanitize_title( $rand . '-' . $sanitized_values[ GCMS_C_SURVEY_YOURNAME ] );
	$post_data['post_author'] = $user_id ? $user_id : GCMS_C_SURVEY_DEFAULT_USERID;
	$post_data['post_status'] = 'publish';
	$post_data['post_type']   = GCMS_C_SURVEY_CPT;

	$post_content = '';

	if ( isset( $sanitized_values[ GCMS_C_SURVEY_YOURNAME ] ) ) {
		$post_content .= _x( 'Your name', 'naam', "ictuwp-plugin-maturityscore" ) . '=' . $sanitized_values[ GCMS_C_SURVEY_YOURNAME ] . '<br>';
		setcookie( GCMS_C_SURVEY_YOURNAME, $sanitized_values[ GCMS_C_SURVEY_YOURNAME ], time() + ( 3600 * 24 * 60 ), '/' );
	}

	if ( isset( $sanitized_values[ GCMS_C_SURVEY_EMAILID ] ) ) {
		$post_content .= _x( 'Your email address', 'email', "ictuwp-plugin-maturityscore" ) . '=' . $sanitized_values[ GCMS_C_SURVEY_EMAILID ] . '<br>';
		setcookie( GCMS_C_SURVEY_EMAILID, $sanitized_values[ GCMS_C_SURVEY_EMAILID ], time() + ( 3600 * 24 * 60 ), '/' );
	}

	// update the number of surveys taken
	update_option( GCMS_C_AVGS_NR_SURVEYS, get_option( GCMS_C_AVGS_NR_SURVEYS, 1 ) );


	// Create the new post
	$new_submission_id = wp_insert_post( $post_data, true );

	// If we hit a snag, update the user
	if ( is_wp_error( $new_submission_id ) ) {
		return $cmb->prop( 'submission_error', $new_submission_id );
	}


	$theurl = get_permalink( $new_submission_id );

	// compose the mail
	if ( isset( $sanitized_values[ GCMS_C_SURVEY_EMAILID ] ) ) {

		if ( isset( $sanitized_values[ GCMS_C_SURVEY_EMAILID ] ) && filter_var( $sanitized_values[ GCMS_C_SURVEY_EMAILID ], FILTER_VALIDATE_EMAIL ) ) {

			// the users mailaddress appears to be a valid mailaddress

//			'description'   => '(id: ' . GCMS_C_TEXTEMAIL . ', option_key: ' . GCMS_C_PLUGIN_KEY . ')',


			$mailtext = gcms_aux_get_value_for_cmb2_key( GCMS_C_TEXTEMAIL, _x( 'No mail text found', 'email', "ictuwp-plugin-maturityscore" ), GCMS_C_PLUGIN_KEY );
			$mailtext = str_replace( GCMS_C_URLPLACEHOLDER, '<a href="' . $theurl . '">' . $theurl . '</a>', $mailtext );
			$mailtext = str_replace( GCMS_C_NAMEPLACEHOLDER, $sanitized_values[ GCMS_C_SURVEY_YOURNAME ], $mailtext );

			$mailfrom_address = gcms_aux_get_value_for_cmb2_key( 'mail-from-address', 'info@gebruikercentraal.nl', GCMS_C_PLUGIN_KEY );
			$mailfrom_name    = gcms_aux_get_value_for_cmb2_key( 'mail-from-name', 'Gebruiker Centraal', GCMS_C_PLUGIN_KEY );

			$subject = gcms_aux_get_value_for_cmb2_key( 'mail-subject', _x( 'Link to your survey results', "email settings", "ictuwp-plugin-maturityscore" ), GCMS_C_PLUGIN_KEY );
			$headers = array(
				'From: ' . $mailfrom_name . ' <' . $mailfrom_address . '>'
			);

			add_filter( 'wp_mail_content_type', 'gcmsf_mail_set_html_mail_content_type' );

			wp_mail( $sanitized_values[ GCMS_C_SURVEY_EMAILID ], $subject, wpautop( $mailtext ), $headers );

			// Reset content-type to avoid conflicts -- https://core.trac.wordpress.org/ticket/23578
			remove_filter( 'wp_mail_content_type', 'gcmsf_mail_set_html_mail_content_type' );

		}
	}

	if ( isset( $sanitized_values[ GCMS_C_SURVEY_GDPR_CHECK ] ) ) {
		// we are given permission to store the name and emailaddress
	} else {

		// do not save the name nor email
		unset( $sanitized_values[ GCMS_C_SURVEY_YOURNAME ] );
		unset( $sanitized_values[ GCMS_C_SURVEY_EMAILID ] );
		unset( $sanitized_values[ GCMS_C_SURVEY_GDPR_CHECK ] );

	}

	// save the extra fields as metadata
	$cmb->save_fields( $new_submission_id, 'post', $sanitized_values );
	update_post_meta( $new_submission_id, GCMS_C_FORMKEYS, $sanitized_values );

	gcms_data_reset_values( false );


	// add all custom tax values
	if ( ! empty( $sanitized_values[ GCMS_C_QUESTION_PREFIX . GCMS_C_SURVEY_CT_ORG_ATTITUDE ] ) ) {
		wp_set_post_terms( $new_submission_id, $sanitized_values[ GCMS_C_QUESTION_PREFIX . GCMS_C_SURVEY_CT_ORG_ATTITUDE ], GCMS_C_SURVEY_CT_ORG_ATTITUDE );
	}

	if ( ! empty( $sanitized_values[ GCMS_C_QUESTION_PREFIX . GCMS_C_SURVEY_CT_REGION ] ) ) {
		wp_set_post_terms( $new_submission_id, $sanitized_values[ GCMS_C_QUESTION_PREFIX . GCMS_C_SURVEY_CT_REGION ], GCMS_C_SURVEY_CT_REGION );
	}

	if ( ! empty( $sanitized_values[ GCMS_C_QUESTION_PREFIX . GCMS_C_SURVEY_CT_ORG_SIZE ] ) ) {
		wp_set_post_terms( $new_submission_id, $sanitized_values[ GCMS_C_QUESTION_PREFIX . GCMS_C_SURVEY_CT_ORG_SIZE ], GCMS_C_SURVEY_CT_ORG_SIZE );
	}

	if ( ! empty( $sanitized_values[ GCMS_C_QUESTION_PREFIX . GCMS_C_SURVEY_CT_ORG_TYPE ] ) ) {
		wp_set_post_terms( $new_submission_id, $sanitized_values[ GCMS_C_QUESTION_PREFIX . GCMS_C_SURVEY_CT_ORG_TYPE ], GCMS_C_SURVEY_CT_ORG_TYPE );
	}


	/*
	* Redirect back to the form page with a query variable with the new post ID.
	* This will help double-submissions with browser refreshes
	*/
	wp_redirect( get_permalink( $new_submission_id ) );


	/*
	* Redirect back to the form page with a query variable with the new post ID.
	* This will help double-submissions with browser refreshes
	*/
	wp_redirect( $theurl );

	exit;

}

//========================================================================================================

/**
 * Reset the statistics
 */
function gcms_data_reset_values( $givefeedback = true ) {

	if ( isset( $_POST['dofeedback'] ) ) {
		$givefeedback = true;
	}

	$log             = '';
	$subjects        = array();
	$allemetingen    = array();
	$formfields_data = gcmsf_data_get_survey_json();
	$counter         = 0;

	update_option( GCMS_C_AVGS_NR_SURVEYS, 0 );
	update_option( GCMS_C_AVGS_OVERALL_AVG, 0 );

	$args = array(
		'post_type'      => GCMS_C_SURVEY_CPT,
		'posts_per_page' => '-1',
		'post_status'    => 'publish',
		'order'          => 'ASC'
	);


	if ( $formfields_data ) {

		foreach ( $formfields_data as $key => $value ) {

			$optionkey = sanitize_title( $value->group_label );

			$subjects[] = 'Reset value for ' . $optionkey . ' = 0';

			update_option( $optionkey, '0' );

		}
	}

	$the_query = new WP_Query( $args );

	if ( $the_query->have_posts() ) {

		while ( $the_query->have_posts() ) {

			$the_query->the_post();

			$counter ++;
			$postid     = get_the_id();
			$subjects[] = $counter . ' ' . GCMS_C_SURVEY_CPT . ' = ' . get_the_title() . '(' . $postid . ')';

			$user_answers_raw = get_post_meta( $postid );

			if ( isset( $user_answers_raw[ GCMS_C_FORMKEYS ][0] ) ) {

				$user_answers = maybe_unserialize( $user_answers_raw[ GCMS_C_FORMKEYS ][0] );

				foreach ( $user_answers as $key => $value ) {


					$subjects[]   = '(' . $postid . ') ' . $key . '=' . $value . '.';
					$constituents = explode( GCMS_C_PLUGIN_SEPARATOR, $value ); // [0] = group, [1] = question, [2] = answer

					$group    = '';
					$question = '';
					$answer   = '';

					if ( isset( $constituents[0] ) ) {
						$group = $constituents[0];
					}
					if ( isset( $constituents[1] ) ) {
						$question = $constituents[1];
					}
					if ( isset( $constituents[2] ) ) {
						$answer = $constituents[2];
					}

//					echo '$group : ' . $group . ', 	$question : ' . $question . ', $answer : ' . $answer . '<br>';
					if ( $question && $answer ) {

						$current_answer = (array) $formfields_data->$group->group_questions[0]->$question->question_answers[0]->$answer;

						if ( intval( $current_answer['answer_value'] ) > 0 ) {
							$values[ $group . GCMS_C_PLUGIN_SEPARATOR . $question ][] = $current_answer['answer_value'];
							$values[ $group ][]                                       = $current_answer['answer_value'];
						}
					}
				}
			}
		}
	}

	// loop door alle keys en bereken hun gemiddelde
	foreach ( $values as $key => $value ) {

		$systemaverage_score = gcms_aux_get_average_for_array( $value, 1 );
		$subjects[]          = 'nieuw gemiddelde voor ' . $key . ' = ' . $systemaverage_score . '.';

		$allemetingen[ $key ] = $systemaverage_score;

		// save het gemiddelde
		update_option( $key, $systemaverage_score );

	}

	// overall gemiddelde
	$average_overall = gcms_aux_get_average_for_array( $allemetingen, 1 );

	update_option( GCMS_C_AVGS_OVERALL_AVG, $average_overall );
	update_option( GCMS_C_AVGS_NR_SURVEYS, $counter );

	if ( $givefeedback ) {

		wp_send_json( array(
			'ajaxrespons_messages' => $subjects,
			'ajaxrespons_item'     => $log,
		) );

	}

}


//========================================================================================================

if ( ! function_exists( 'gcmsf_data_get_survey_json' ) ) {
	/**
	 * Read a *LOCAL* JSON file that contains the form definitions
	 */
	function gcmsf_data_get_survey_json() {

		global $wp_filesystem;

		$formfields_location = GCMS_C_PATH . 'assets/antwoorden-vragen.json';
		$formfields_json     = file_get_contents( $formfields_location );

		if ( is_wp_error( $formfields_json ) ) {

			$error_string = $formfields_json->get_error_message();
			die( ' inlezen van jsong bestand ging fout: ' . $error_string );

			return false; // Bail early

		}

		// Retrieve the data
		$formfields_data = json_decode( $formfields_json );

		return $formfields_data;

	}
}

//========================================================================================================

add_action( 'wp_enqueue_scripts', 'gcmsf_aux_remove_cruft', 100 ); // high prio, to ensure all junk is discarded

/**
 * Unhook ictuwp-plugin-maturityscore styles from WordPress
 */
function gcmsf_aux_remove_cruft() {

	wp_dequeue_style( 'cmb2-styles' );
	wp_dequeue_style( 'cmb2-styles-css' );

}

//========================================================================================================

if ( ! function_exists( 'gcmsf_aux_write_to_log' ) ) {

	function gcmsf_aux_write_to_log( $log ) {

		$subject = 'log';
		$subject .= ' (ID = ' . getmypid() . ')';

		$subjects   = array();
		$subjects[] = $log;

		if ( true === WP_DEBUG ) {
			if ( is_array( $log ) || is_object( $log ) ) {
				error_log( $subject . ' - ' . print_r( $log, true ) );
			} else {
				error_log( $subject . ' - ' . $log );
			}
		}
	}

}

//========================================================================================================

/**
 * Wrapper function around cmb2_get_option
 *
 * @param string $key Options array key
 * @param mixed $default Optional default value
 *
 * @return mixed           Option value
 * @since  0.1.0
 */
function gcms_aux_get_value_for_cmb2_key( $key = '', $default = '', $optionkey = '' ) {

	$return = '';

	if ( function_exists( 'cmb2_get_option' ) ) {
		return cmb2_get_option( $optionkey, $key, $default );
	}

	// Fallback to get_option if CMB2 is not loaded yet.
	$opts = get_option( 'gcmsf_admin_options_metabox', $default );

	$val = $default;

	if ( 'all' == $key ) {
		$val = $opts;
	} elseif ( is_array( $opts ) && array_key_exists( $key, $opts ) && false !== $opts[ $key ] ) {
		$val = $opts[ $key ];
	}

	return $val;

}

//========================================================================================================

/**
 * gcms_aux_get_average_for_array : get average values from an array
 *
 * @param array $inputarray Options array key
 * @param number $roundby Optional default value
 *
 * @return $return    0 or an average value
 */
function gcms_aux_get_average_for_array( $inputarray = '', $roundby = 0 ) {

	$return  = 0;
	$roundby = intval( $roundby );

	if ( is_array( $inputarray ) ) {
		$return = round( ( array_sum( $inputarray ) / count( $inputarray ) ), $roundby );
	}

	return $return;

}

//========================================================================================================

if ( ! function_exists( 'dovardump' ) ) {

	function dovardump( $data, $context = '', $echo = true ) {

		if ( WP_DEBUG && GCMS_C_PLUGIN_DO_DEBUG ) {
			$contextstring = '';
			$startstring   = '<div class="debug-context-info">';
			$endtring      = '</div>';

			if ( $context ) {
				$contextstring = '<p>Vardump ' . $context . '</p>';
			}

			gcmsf_aux_write_to_log( print_r( $data ), true );


			if ( $echo && GCMS_C_PLUGIN_OUTPUT_TOSCREEN ) {

				echo $startstring . '<hr>';
				echo $contextstring;
				echo '<pre>';
				print_r( $data );
				echo '</pre><hr>' . $endtring;
			} else {
				return '<hr>' . $contextstring . '<pre>' . print_r( $data, true ) . '</pre><hr>';
			}
		}
	}
}

//========================================================================================================

if ( ! function_exists( 'dodebug' ) ) {

	function dodebug( $string, $tag = 'span' ) {

		if ( WP_DEBUG && GCMS_C_PLUGIN_DO_DEBUG ) {

			gcmsf_aux_write_to_log( $string );
			if ( GCMS_C_PLUGIN_OUTPUT_TOSCREEN ) {
				echo '<' . $tag . ' class="debugstring" style="border: 1px solid red; background: yellow; display: block; "> ' . $string . '</' . $tag . '>';
			}
		}
	}

}

//========================================================================================================

/**
 * Initialise translations
 */
function gcmsf_init_load_plugin_textdomain() {

	//          load_plugin_textdomain( "ictuwp-plugin-maturityscore", false, GCMS_C_PATH_LANGUAGES );
	load_plugin_textdomain( "ictuwp-plugin-maturityscore", false, basename( dirname( __FILE__ ) ) . '/languages' );

}

//========================================================================================================


/**
 * Helper function for reading post values or cookie values
 */
function gcmsf_get_post_or_cookie( $key = '', $default = '' ) {

	$return = '';

	if ( $default ) {
		$return = $default;
	}

	if ( $key ) {

		if ( isset( $_POST[ $key ] ) && ( ! empty( $_POST[ $key ] ) ) ) {
			$return = $_POST[ $key ];
		} elseif ( isset( $_COOKIE[ $key ] ) && ( ! empty( $_COOKIE[ $key ] ) ) ) {
			$return = $_COOKIE[ $key ];
		}

	}

	return $return;

}

//========================================================================================================

/**
 * Filter the mail content type.
 */
function gcmsf_mail_set_html_mail_content_type() {
	return 'text/html';
}

//========================================================================================================

add_filter( 'the_post_navigation', 'gcmsf_remove_post_navigation_for_survey' );

function gcmsf_remove_post_navigation_for_survey( $args ) {

	if ( GCMS_C_SURVEY_CPT == get_type() ) {
		return '';
	} else {
		return '';
	}

	return $args;

}

//========================================================================================================

if ( ! function_exists( 'gc_wbvb_breadcrumbstring' ) ) {

	function gc_wbvb_breadcrumbstring( $currentpageID, $args ) {

		global $post;
		$crumb      = '';
		$countertje = 0;

		if ( $currentpageID ) {
			$crumb       = '<a href="' . get_permalink( $currentpageID ) . '">' . get_the_title( $currentpageID ) . '</a>' . $args['sep'] . ' ' . get_the_title( $post->ID );
			$postparents = get_post_ancestors( $currentpageID );

			foreach ( $postparents as $postparent ) {
				$countertje ++;
				$crumb = '<a href="' . get_permalink( $postparent ) . '">' . get_the_title( $postparent ) . '</a>' . $args['sep'] . $crumb;
			}
		}

		return $crumb;

	}

}

//========================================================================================================

