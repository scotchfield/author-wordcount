<?php
/**
* Plugin Name: Author Wordcount
* Plugin URI: http://scotchfield.com/author-wordcount-plugin
* Description: Allows authors to show word counts for works in progress.
* Version: 1.0
* Author: Scott Grant
* Author URI: http://scotchfield.com
* License: GPL2
*/

class WP_Author_Wordcount extends WP_Widget {

	public function __construct() {
		parent::__construct( false, 'Author Wordcount' );

		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		load_plugin_textdomain(
			'author_wordcount',
			FALSE,
			dirname( plugin_basename( __FILE__ ) ) . '/languages/'
		);

		add_action( 'admin_menu', array( $this, 'add_menu' ) );

		wp_register_style(
			'author_wordcount_stylesheet',
			plugins_url( 'style.css', __FILE__ )
		);
		wp_enqueue_style( 'author_wordcount_stylesheet' );

		$this->word_obj = get_option( 'author_wordcount' );
		if ( ! $this->word_obj ) {
			$this->word_obj = array();
		}
	}

	public function add_menu() {
		$page = add_options_page(
			'Author Wordcount Settings',
			'Author Wordcount',
			'manage_options',
			'wp_author_wordcount',
			array( $this, 'plugin_settings_page' )
		);
	}

	public function widget( $args, $instance ) {

		if ( count( $this->word_obj ) > 0 ) {
			// todo don't bake this text, place in options
			echo( '<aside class="widget"><h1 class="widget-title">' .
				  'Works in Progress</h1>' );
			foreach ( $this->word_obj as $k => $v ) {
				$bar_width = round( 100.0 * floatval( $v[ 'count' ] ) /
					floatval( $v[ 'max' ] ) );

				if ( $bar_width > 100 ) {
					$bar_width = 100;
				} elseif ( $bar_width < 0 ) {
					$bar_width = 0;
				}
?>
<span class="widget_title"><?php echo( $k ); ?></span>
<div class="author_wordcount_element">
<div class="author_wordcount_bar" style="width:<?php
  echo( $bar_width ); ?>%;">
<br>
</div>
</div>
<p><?php echo( $v[ 'count' ] ); ?> / <?php echo( $v[ 'max' ] ); ?></p>
<?php
			}
			echo '</aside>';
		}

	}

	public function plugin_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions ' .
						'to access this page.' ) );
		}

		if ( isset( $_POST[ 'wordcount_name' ] ) ) {

			if ( isset( $_POST[ 'wordcount_add' ] ) &&
				 wp_verify_nonce( $_POST[ 'wp_nonce' ],
								  'author-create' ) ) {

				$this->word_obj[ $_POST[ 'wordcount_name' ] ] = array(
					'count' => $_POST[ 'wordcount_count' ],
					'max' => $_POST[ 'wordcount_max' ]
				);

				update_option( 'author_wordcount', $this->word_obj );

			} elseif ( isset( $_POST[ 'wordcount_delete' ] ) &&
					   wp_verify_nonce( $_POST[ 'wp_nonce' ],
						   'update-' . $_POST[ 'wordcount_name' ] ) ) {

				unset( $this->word_obj[ $_POST[ 'wordcount_name' ] ] );

				update_option( 'author_wordcount', $this->word_obj );

			} elseif ( isset( $_POST[ 'wordcount_update' ] ) &&
					   wp_verify_nonce( $_POST[ 'wp_nonce' ],
						   'update-' . $_POST[ 'wordcount_name' ] ) ) {

				$this->word_obj[ $_POST[ 'wordcount_name' ] ] = array(
					'count' => $_POST[ 'wordcount_count' ],
					'max' => $_POST[ 'wordcount_max' ]
				);

				update_option( 'author_wordcount', $this->word_obj );

			}

		}
?>
<div class="wrap">
<h2>Author Wordcount</h2>
<hr>
<h3>New Entry</h3>
<form method="post" action="options-general.php?page=wp_author_wordcount">
<input type="hidden" name="wp_nonce" value="<?php
	echo( wp_create_nonce( 'author-create' ) ); ?>">
<table class="form-table">
<tr valign="top">
  <th scope="row">Name</th>
  <td><input name="wordcount_name" id="wordcount_name" value=""
			 class="regular-text" type="text" placeholder="The Hobbit">
  </td>
</tr>
<tr valign="top">
  <th scope="row">Current Wordcount</th>
  <td><input name="wordcount_count" id="wordcount_count" value=""
			 class="regular-text" type="text" placeholder="12345">
  </td>
</tr>
<tr valign="top">
  <th scope="row">Expected Wordcount</th>
  <td><input name="wordcount_max" id="wordcount_max" value=""
			 class="regular-text" type="text" placeholder="50000">
  </td>
</tr>
</table>
<p class="submit">
<input name="wordcount_add" id="wordcount_add"
	   class="button button-primary" value="Add Wordcount" type="submit">
</p>
</form>
<?php
		if ( count( $this->word_obj ) > 0 ) {
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
			foreach ( $this->word_obj as $k => $v ) {
?>
<form class="tr" method="post"
	  action="options-general.php?page=wp_author_wordcount">
  <input type="hidden" name="wp_nonce" value="<?php
	  echo( wp_create_nonce( 'update-' . $k ) ); ?>">
  <span class="td"><input name="wordcount_name" id="wordcount_name"
	  value="<?php echo( $k ); ?>" type="text"></span>
  <span class="td"><input name="wordcount_count" id="wordcount_count"
	  value="<?php echo( $v[ 'count' ] ); ?>" type="text"></span>
  <span class="td"><input name="wordcount_max" id="wordcount_max"
	  value="<?php echo( $v[ 'max' ] ); ?>" type="text"></span>
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
?>
<hr>
<div class="wrap">
<h4>Please note that wordcounts will not show unless the widget has
been <a href="<?php echo( admin_url() ); ?>widgets.php">added to
a sidebar</a> or other location.</h4>
</div>
<?php
	}

}

function register_author_wordcount() {
	register_widget( 'WP_Author_Wordcount' );
}

add_action( 'widgets_init', 'register_author_wordcount' );
