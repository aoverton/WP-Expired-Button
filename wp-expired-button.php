<?php
defined( 'ABSPATH' ) OR exit;
/*
Plugin Name: WP Expired Button
Plugin URI: http://aoverton.com
Description: A customizable button that will allow users to report broken or expired coupons.
Version: 1.0
Author: Austin Overton
Author URI: http://aoverton.com
License: GPL2
*/

define('WPEB_PLUGIN_VERSION', '1.0');
define('WPEB_DB_VERSION', '1.0');
define('WPEB_SUBMISSION_COUNT_TO_EXPIRE', '10');

if(!class_exists('WP_Expired_Button')) {

  class WP_Expired_Button {

    /**
     * Construct the plugin object
     */
    public function __construct() {
      // shortcodes
      add_shortcode('expired_button', array($this, 'expired_button_func'));

      // actions
      add_action('wp_ajax_handle_button_click', array($this, 'handle_button_click'));
      add_action('wp_ajax_nopriv_handle_button_click', array($this, 'handle_button_click')); // need this to serve non logged in users
      add_action('admin_menu', array($this, 'register_tag_submenu_page'));

      // filters
      add_filter('manage_posts_columns', array($this, 'posts_column'));
      add_action('manage_posts_custom_column', array($this, 'show_posts_column'));

      // enqueue and localise scripts
      wp_enqueue_script('wpeb-ajax-handle', plugin_dir_url( __FILE__).'js/script.js', array('jquery'));
      wp_localize_script('wpeb-ajax-handle', 'wpeb_script', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function posts_column($columns) {
      $columns['expired_count'] = 'Expired Count';
      return $columns;
    }

    public function show_posts_column($name) {
      global $post, $wpdb;
      switch ($name) {
        case 'expired_count':
          $table_name = $wpdb->prefix.'wpeb_btn_click_log';
          $total_count = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE post_id = %d", $post->ID) );
          echo $total_count;
      }
    }

    public function register_tag_submenu_page() {
      $expired_tag_id = get_option('wpeb_expired_tag_id');
      $expired_tag_id = $expired_tag_id['term_id'];
      $tag = get_term($expired_tag_id, 'post_tag');

      add_posts_page('Expired Posts', 'Expired Posts', 'read', 'edit.php?tag='.$tag->slug);
    }

    /**
     * Function for [expired_button btn_text=""] shortcode
     */
    public function expired_button_func($atts, $content=null) {
      extract( shortcode_atts( array(
        'btn_text' => 'BUTTON!'
      ), $atts ) );

      ob_start();
      ?>
        <button class="expired_button" data-pid="<?php echo get_the_ID(); ?>"><?php echo $btn_text; ?></button>
      <?php
      $content = ob_get_contents();
      ob_end_clean();
      return $content;
    }

    /**
     * Process expired button click, ajax handler function
     */
    public function handle_button_click() {
      $post_id = $_POST['pid'];
      if( ! isset($post_id) || ! is_numeric($post_id))
        return;

      if( ! empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
      }
      elseif( ! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
      }
      else{
        $ip = $_SERVER['REMOTE_ADDR'];
      }
      $ip = ip2long($ip); // SELECT INET_NTOA(ip) :: to get from DB

      echo 'Button Clicked! | '.$ip.' | Post ID: '.$post_id.'';

      global $wpdb;
      $table_name = $wpdb->prefix.'wpeb_btn_click_log';

      // limit user submission to 1 per post
      $user_count = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND ip = %d", $post_id, $ip) );
     if($user_count >= 1) {
        echo 'You voted already! | '.$user_count;
        die();
      }

      // insert submission
      $data = array(
        'post_id' => $post_id,
        'ip' => $ip,
        'created_at' => date("Y-m-d H:i:s", time())
      );
      $format = array('%s', '%d', '%s');
      $wpdb->insert($table_name, $data, $format);

      // check total submissions for post, if greater tipping point add to Expired category
      $total_count = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE post_id = %d", $post_id) );
      if($total_count >= WPEB_SUBMISSION_COUNT_TO_EXPIRE) {
        $expired_tag_id = get_option('wpeb_expired_tag_id');
        $expired_tag_id = $expired_tag_id['term_id'];
        echo 'time to change tag | '.$total_count.' | '.$expired_tag_id;
        wp_set_object_terms($post_id, (int)$expired_tag_id, 'post_tag', true);
        die();
      }

      die();
    }

    /**
     * Activate the plugin
     */
    public static function activate() {
      if( ! current_user_can('activate_plugins'))
        return;

      global $wpdb;
      $table_name = $wpdb->prefix.'wpeb_btn_click_log';

      $sql = "CREATE TABLE $table_name (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id BIGINT UNSIGNED NOT NULL,
  ip INT UNSIGNED NOT NULL,
  created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  UNIQUE KEY id (id)
    );";

      require_once(ABSPATH.'wp-admin/includes/upgrade.php');
      dbDelta($sql);

      add_option('wpeb_db_version', WPEB_DB_VERSION);

      // create the tag to apply to expired posts
      $expired_tag_id = wp_create_term('Expired');
      add_option('wpeb_expired_tag_id', $expired_tag_id);
    }

    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
      if( ! current_user_can('activate_plugins'))
        return;
    }

    /**
     * Uninstall the plugin
     */
    public static function uninstall() {
      if( ! current_user_can('activate_plugins'))
        return;

      global $wpdb;
      $table_name = $wpdb->prefix.'wpeb_btn_click_log';
      $sql = "DROP TABLE $table_name";
      $wpdb->query($sql);

      delete_option('wpeb_expired_tag_id');
      delete_option('wpeb_db_version');
    }

  }

}

if(class_exists('WP_Expired_Button')) {
  // WordPress plugin hooks
  register_activation_hook(__FILE__, array('WP_Expired_Button', 'activate'));
  register_deactivation_hook(__FILE__, array('WP_Expired_Button', 'deactivate'));
  register_uninstall_hook(__FILE__, array('WP_Expired_Button', 'uninstall'));

  $wp_expired_button = new WP_Expired_Button();
}







