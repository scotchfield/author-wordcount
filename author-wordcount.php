<?php
/**
 * Plugin Name: Author Wordcount
 * Plugin URI: http://scotchfield.com/author-wordcount-plugin
 * Description: Allows authors to show word counts for works in progress.
 * Version: 0.1
 * Author: Scott Grant
 * Author URI: http://scotchfield.com
 * License: GPL2
 */

if ( ! class_exists( 'WP_Author_Wordcount' ) ) {
    class WP_Author_Wordcount extends WP_Widget {
        public function __construct() {
            parent::__construct( false, 'Author Wordcount' );

            add_action( 'init', array( $this, 'load_plugin_textdomain') );
            add_action( 'admin_init', array( $this, 'admin_init' ) );
            add_action( 'admin_menu', array( $this, 'add_menu' ) );
        }

        function load_plugin_textdomain() {
            load_plugin_textdomain(
                'author_wordcount',
                FALSE,
                dirname( plugin_basename( __FILE__ ) ) . '/languages/'
            );
        }

        public function admin_init() {
            wp_register_style(
                'author_wordcount_stylesheet',
                plugins_url( 'style.css', __FILE__ )
            );
        }

        public static function activate() {
            global $wpdb;

            $table_name = $wpdb->prefix . 'authorwordcount';

            $sql = "CREATE TABLE $table_name (
                id MEDIUMINT NOT NULL AUTO_INCREMENT,
                name TINYTEXT NOT NULL,
                word_count MEDIUMINT NOT NULL,
                word_max MEDIUMINT NOT NULL,
                UNIQUE KEY id (id)
            );";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }

        public function add_menu() {
            $page = add_options_page(
                'Author Wordcount Settings',
                'Author Wordcount',
                'manage_options',
                'wp_author_wordcount',
                array( $this, 'plugin_settings_page' )
            );
            add_action(
                'admin_print_styles-' . $page,
                array( $this, 'plugin_admin_styles' )
            );
        }

        public function widget( $args, $instance ) {
            $wordcounts = $this->get_wordcounts();
            if ( count( $wordcounts ) > 0 ) {
                // todo don't bake this text, place in options
                echo( '<aside class="widget"><h1 class="widget-title">' .
                      'Works in Progress</h1>' );
                foreach ( $wordcounts as $wc ) {
                    $bar_width = round( 100.0 * floatval( $wc[ 'count' ] ) /
                        floatval( $wc[ 'max' ] ) );

                    if ( $bar_width > 100 ) {
                        $bar_width = 100;
                    } elseif ( $bar_width < 0 ) {
                        $bar_width = 0;
                    }

                    // todo add colours to plugin options, don't bake them in.
                    // improve style across themes
                    echo( '<span class="widget_title">' . $wc[ 'name' ] .
                          '</span>' );
                    echo( '<div style="width:100%;height:15px;background:' .
                          '#FFFFFF;border:1px solid #000000;">' );
                    echo( '<div style="width:' . $bar_width .
                         '%;height:15px;background:#1982d1;font-size:8px;' .
                         'line-height:8px;">' );
                    echo( '<br></div></div>' );
                    echo( '<p>' . $wc[ 'count' ] . ' / ' . $wc[ 'max' ] .
                          '</p>' );
//                    echo '<p>' . wp_create_nonce() . ',',
//                         wp_create_nonce() . '</p>';
                }
                echo '</aside>';
            }
        }

        public function plugin_admin_styles() {
            wp_enqueue_style( 'author_wordcount_stylesheet' );
        }

        public function plugin_settings_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( __( 'You do not have sufficient permissions ' .
                            'to access this page.' ) );
            }

            // TODO nonce
            if ( ( isset( $_POST[ 'wordcount_add' ] ) ) &&
                 ( strlen( $_POST[ 'wordcount_name' ] ) > 0 ) ) {

                global $wpdb;

                $table_name = $wpdb->prefix . 'authorwordcount';

                $wpdb->insert( $table_name,
                    array(
                        'name' => $_POST[ 'wordcount_name' ],
                        'word_count' => $_POST[ 'wordcount_count' ],
                        'word_max' => $_POST[ 'wordcount_max' ],
                    ),
                    array( '%s', '%d', '%d' )
                );

            } elseif ( isset( $_POST[ 'wordcount_delete' ] ) ) {

                global $wpdb;

                $table_name = $wpdb->prefix . 'authorwordcount';

                $wpdb->delete( $table_name,
                    array( 'id' => intval( $_POST[ 'wordcount_id' ] ) ) );

            } elseif ( isset( $_POST[ 'wordcount_update' ] ) ) {

                global $wpdb;

                $table_name = $wpdb->prefix . 'authorwordcount';

                $wpdb->update( $table_name,
                    array(
                        'name' => $_POST[ 'wordcount_name' ],
                        'word_count' => $_POST[ 'wordcount_count' ],
                        'word_max' => $_POST[ 'wordcount_max' ]
                    ),
                    array( 'id' => $_POST[ 'wordcount_id' ] ),
                    array( '%s', '%d', '%d' ),
                    array( '%d' )
                );

            }
?>
<div class="wrap">
  <h2>Author Wordcount</h2>
  <hr>
  <h3>New Entry</h3>
  <form method="post" action="options-general.php?page=wp_author_wordcount">
  <table class="form-table">
    <?php echo( settings_fields( 'debugbarextender_settings' ) ); ?>
    <tr valign="top">
      <th scope="row">Name</th>
      <td><input name="wordcount_name" id="wordcount_name" value=""
                 class="regular-text" type="text">
      </td>
    </tr>
    <tr valign="top">
      <th scope="row">Current Wordcount</th>
      <td><input name="wordcount_count" id="wordcount_count" value=""
                 class="regular-text" type="text">
      </td>
    </tr>
    <tr valign="top">
      <th scope="row">Expected Wordcount</th>
      <td><input name="wordcount_max" id="wordcount_max" value=""
                 class="regular-text" type="text">
      </td>
    </tr>
  </table>
  <p class="submit">
    <input name="wordcount_add" id="wordcount_add"
           class="button button-primary" value="Add Wordcount" type="submit">
  </p>
  </form>
<?php
            $wordcounts = $this->get_wordcounts();
            if ( count( $wordcounts ) > 0 ) {
?>
  <hr>
  <div class="table">
    <div class="tr">
      <span class="td bold">Name</span>
      <span class="td bold">Current Wordcount</span>
      <span class="td bold">Expected Wordcount</span>
      <span class="td bold">Options</span>
    </div>
<?php
                foreach ( $wordcounts as $wc ) {
?>
    <form class="tr" method="post"
          action="options-general.php?page=wp_author_wordcount">
      <input type="hidden" name="wordcount_id" value="<?php
          echo( $wc[ 'id' ] ); ?>">
      <span class="td"><input name="wordcount_name" id="wordcount_name"
          value="<?php echo( $wc[ 'name' ] ); ?>" type="text"></span>
      <span class="td"><input name="wordcount_count" id="wordcount_count"
          value="<?php echo( $wc[ 'count' ] ); ?>" type="text"></span>
      <span class="td"><input name="wordcount_max" id="wordcount_max"
          value="<?php echo( $wc[ 'max' ] ); ?>" type="text"></span>
      <span class="td">
        <input name="wordcount_update" id="wordcount_update"
               class="button button-primary" value="Update" type="submit">
        <input name="wordcount_delete" id="wordcount_delete"
               class="button button-primary" value="Delete" type="submit">
      </span>
    </form>
<?php
                }

                echo( '</div>' );
            }
            echo( '</div>' );
        }

        public function get_wordcounts() {
            global $wpdb;

            $table_name = $wpdb->prefix . 'authorwordcount';

            $wordcounts = array();
            $rows = $wpdb->get_results(
                "SELECT id, name, word_count, word_max FROM $table_name" );
            foreach ( $rows as $row ) {
                $wordcounts[ $row->id ] = array(
                    'id' => $row->id,
                    'name' => $row->name,
                    'count' => $row->word_count,
                    'max' => $row->word_max
                );
            }

            return $wordcounts;
        }
    }
}

function register_author_wordcount() {
    register_widget( 'WP_Author_Wordcount' );
}
add_action( 'widgets_init', 'register_author_wordcount' );