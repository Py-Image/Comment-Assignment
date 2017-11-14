<?php
/**
 * Plugin Name: PYIS Comment Assignment
 * Plugin URI: https://github.com/Py-Image/comment-assignment
 * Description: Allows assigning Comments and Spoofing Comment Replies as another User
 * Version: 0.1.0
 * Text Domain: pyis-comment-assignment
 * Author: Real Big Marketing
 * Author URI: https://realbigmarketing.com/
 * Contributors: d4mation
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'PYIS_Comment_Assignment' ) ) {

	/**
	 * Main PYIS_Comment_Assignment class
	 *
	 * @since	  1.0.0
	 */
	final class PYIS_Comment_Assignment {
		
		/**
		 * @var			PYIS_Comment_Assignment $plugin_data Holds Plugin Header Info
		 * @since		1.0.0
		 */
		public $plugin_data;
		
		/**
		 * @var			PYIS_Comment_Assignment $admin_errors Stores all our Admin Errors to fire at once
		 * @since		1.0.0
		 */
		private $admin_errors;

		/**
		 * Get active instance
		 *
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  object self::$instance The one true PYIS_Comment_Assignment
		 */
		public static function instance() {
			
			static $instance = null;
			
			if ( null === $instance ) {
				$instance = new static();
			}
			
			return $instance;

		}
		
		protected function __construct() {
			
			$this->setup_constants();
			$this->load_textdomain();
			
			if ( version_compare( get_bloginfo( 'version' ), '4.4' ) < 0 ) {
				
				$this->admin_errors[] = sprintf( _x( '%s requires v%s of %s or higher to be installed!', 'Outdated Dependency Error', 'pyis-comment-assignment' ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '4.4', '<a href="' . admin_url( 'update-core.php' ) . '"><strong>WordPress</strong></a>' );
				
				if ( ! has_action( 'admin_notices', array( $this, 'admin_errors' ) ) ) {
					add_action( 'admin_notices', array( $this, 'admin_errors' ) );
				}
				
				return false;
				
			}
			
			$this->require_necessities();
			
			// Register our CSS/JS for the whole plugin
			add_action( 'init', array( $this, 'register_scripts' ) );
			
		}

		/**
		 * Setup plugin constants
		 *
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function setup_constants() {
			
			// WP Loads things so weird. I really want this function.
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			
			// Only call this once, accessible always
			$this->plugin_data = get_plugin_data( __FILE__ );

			if ( ! defined( 'PYIS_Comment_Assignment_VER' ) ) {
				// Plugin version
				define( 'PYIS_Comment_Assignment_VER', $this->plugin_data['Version'] );
			}

			if ( ! defined( 'PYIS_Comment_Assignment_DIR' ) ) {
				// Plugin path
				define( 'PYIS_Comment_Assignment_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'PYIS_Comment_Assignment_URL' ) ) {
				// Plugin URL
				define( 'PYIS_Comment_Assignment_URL', plugin_dir_url( __FILE__ ) );
			}
			
			if ( ! defined( 'PYIS_Comment_Assignment_FILE' ) ) {
				// Plugin File
				define( 'PYIS_Comment_Assignment_FILE', __FILE__ );
			}

		}

		/**
		 * Internationalization
		 *
		 * @access	  private 
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function load_textdomain() {

			// Set filter for language directory
			$lang_dir = PYIS_Comment_Assignment_DIR . '/languages/';
			$lang_dir = apply_filters( 'PYIS_Comment_Assignment_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), 'pyis-comment-assignment' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'pyis-comment-assignment', $locale );

			// Setup paths to current locale file
			$mofile_local   = $lang_dir . $mofile;
			$mofile_global  = WP_LANG_DIR . '/pyis-comment-assignment/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/pyis-comment-assignment/ folder
				// This way translations can be overridden via the Theme/Child Theme
				load_textdomain( 'pyis-comment-assignment', $mofile_global );
			}
			else if ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/pyis-comment-assignment/languages/ folder
				load_textdomain( 'pyis-comment-assignment', $mofile_local );
			}
			else {
				// Load the default language files
				load_plugin_textdomain( 'pyis-comment-assignment', false, $lang_dir );
			}

		}
		
		/**
		 * Include different aspects of the Plugin
		 * 
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function require_necessities() {
			
			if ( is_admin() ) {
				
				require_once PYIS_Comment_Assignment_DIR . 'core/admin/class-pyis-comment-assignment-edit-comments.php';
				
			}
			
		}
		
		/**
		 * Show admin errors.
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  HTML
		 */
		public function admin_errors() {
			?>
			<div class="error">
				<?php foreach ( $this->admin_errors as $notice ) : ?>
					<p>
						<?php echo $notice; ?>
					</p>
				<?php endforeach; ?>
			</div>
			<?php
		}
		
		/**
		 * Register our CSS/JS to use later
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  void
		 */
		public function register_scripts() {
			
			wp_register_style(
				'pyis-comment-assignment',
				PYIS_Comment_Assignment_URL . 'assets/css/style.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : PYIS_Comment_Assignment_VER
			);
			
			wp_register_script(
				'pyis-comment-assignment',
				PYIS_Comment_Assignment_URL . 'assets/js/script.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : PYIS_Comment_Assignment_VER,
				true
			);
			
			wp_localize_script( 
				'pyis-comment-assignment',
				'pyiscommentassignment',
				apply_filters( 'PYIS_Comment_Assignment_localize_script', array() )
			);
			
			wp_register_style(
				'pyis-comment-assignment-admin',
				PYIS_Comment_Assignment_URL . 'assets/css/admin.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : PYIS_Comment_Assignment_VER
			);
			
			wp_register_script(
				'pyis-comment-assignment-admin-edit-comments',
				PYIS_Comment_Assignment_URL . 'assets/js/edit-comments.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : PYIS_Comment_Assignment_VER,
				true
			);
			
			wp_localize_script( 
				'pyis-comment-assignment-admin',
				'pyiscommentassignment',
				apply_filters( 'pyis_comment_assignment_localize_admin_script', array() )
			);
			
		}
		
	}
	
} // End Class Exists Check

/**
 * The main function responsible for returning the one true PYIS_Comment_Assignment
 * instance to functions everywhere
 *
 * @since	  1.0.0
 * @return	  \PYIS_Comment_Assignment The one true PYIS_Comment_Assignment
 */
add_action( 'plugins_loaded', 'pyis_comment_assignment_load' );
function pyis_comment_assignment_load() {

	require_once __DIR__ . '/core/pyis-comment-assignment-functions.php';
	PYISCOMMENTASSIGNMENT();

}