<?php
/*
Plugin Name: YOURLS: WordPress to Twitter
Plugin URI: http://planetozh.com/blog/my-projects/yourls-wordpress-to-twitter-a-short-url-plugin/
Description: Create short URLs for posts with <a href="http://yourls.org/" title="Your Own URL Shortener">YOURLS</a> (or other services such as tr.im) and tweet them.
Author: Ozh
Author URI: http://planetozh.com/
Version: 1.2.1
*/

/* Release History :
 * 1.0:       Initial release
 * 1.1:       Fixed: template tag makes post previews die (more generally, plugin wasn't properly initiated when triggered from the public part of the blog). Thanks moggy!
 * 1.2:       Added: ping.fm support, unused at the moment because those fucktards from ping.fm just don't approve the api key.
              Added: template tag wp_ozh_yourls_raw_url()
			  Added: uninstall procedure
			  Added: "get url" button as on wp.com
			  Improved: using internal WP_Http class instead of cURL for posting to Twitter
			  Fixed: short URLs generated on pages or posts even if option unchecked in settings (thanks Viper007Bond for noticing)
			  Fixed: PEAR class was included without checking existence first, conflicting with Twitter Tools for instance (thanks Doug Stewart for noticing)
 * 1.2.1:     Fixed: oops, forgot to remove a test hook
 */


/********************* DO NOT EDIT *********************/

global $wp_ozh_yourls;
if (is_admin()) {   // TODO: optimize? unneeded loadings depending on pages
	require_once(dirname(__FILE__).'/inc/core.php');
	require_once(dirname(__FILE__).'/inc/options.php');
	// Handle new stuff published
	add_action('new_to_publish', 'wp_ozh_yourls_newpost');
	add_action('draft_to_publish', 'wp_ozh_yourls_newpost');
	add_action('pending_to_publish', 'wp_ozh_yourls_newpost');
	add_action('future_to_publish', 'wp_ozh_yourls_newpost');
	// Add menu page, init options, add box on the Post/Edit interface
	add_action('admin_menu', 'wp_ozh_yourls_add_page');
	add_action('admin_init', 'wp_ozh_yourls_admin_init', 1 );
	add_action('admin_init', 'wp_ozh_yourls_addbox', 10);
	// Handle AJAX requests
	add_action('wp_ajax_yourls-promote', 'wp_ozh_yourls_promote' );
	add_action('wp_ajax_yourls-reset', 'wp_ozh_yourls_reset_url' );
	// Custom icon & plugin action link
	add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), 'wp_ozh_yourls_plugin_actions', -10);
	add_filter( 'ozh_adminmenu_icon_ozh_yourls', 'wp_ozh_yourls_customicon' );
}


// Get or create the short URL for a post. Input integer (post id), output string(url)
function wp_ozh_yourls_geturl( $id ) {
	$short = get_post_meta( $id, 'yourls_shorturl', true );
	if (!$short) {
		// short URL never was not created before, let's get it now
		require_once(dirname(__FILE__).'/inc/core.php');
		$short = wp_ozh_yourls_get_new_short_url( get_permalink($id), $id );
	}
	
	return $short;
}

// Template tag: echo short URL for current post
function wp_ozh_yourls_url() {
	global $id;
	$short = wp_ozh_yourls_geturl( $id );
	if ($short)
		echo "<a href=\"$short\" rel=\"nofollow alternate short shorter shorturl shortlink\" title=\"short URL\">$short</a>";
}

// Template tag: echo short URL alternate link in <head> for current post. See http://revcanonical.appspot.com/ && http://shorturl.appjet.net/
function wp_ozh_yourls_head_linkrel() {
	global $id;
	$short = wp_ozh_yourls_geturl( $id );
	if ($short)
		echo "<link rel=\"alternate short shorter shorturl shortlink\" href=\"$short\" />\n";
}

// Template tag: return/echo short URL with no formatting
function wp_ozh_yourls_raw_url( $echo = false ) {
	global $id;
	$short = wp_ozh_yourls_geturl( $id );
	if ($short) {
		if ($echo)
			echo $short;
		return $short;
	}
}





add_shortcode('rssLike', 'rsslike_button');

add_action('wp_meta', 'rsslike_rss2_like_feed');
add_action('admin_menu', 'rsslike_admin_menu');

function rsslike_rss2_like_feed() {
	echo '<li><a href="'.get_option( 'likes_rss' ).'">Likes RSS</a></li>';
}

function rsslike_admin_menu() {
	add_options_page(
		'rssLike', // page title
		'rssLike', // sub-menu title
		'manage_options', // access/capa
		'plugin.php', // file
		'rsslike_settings_page' // function
	);
	add_action( 'admin_init', 'register_mysettings' );
}


function register_mysettings() {
	register_setting( 'rsslike-settings-group', 'likes_rss' );
	register_setting( 'rsslike-settings-group', 'likes_url' );
	register_setting( 'rsslike-settings-group', 'likes_openappstore' );
}


function rsslike_button() {
	return '<iframe src="'.get_option( 'likes_url' ).'/?url='.urlencode(get_permalink()).'&apps='.urlencode(get_option( 'likes_openappstore' )).'" scrolling="no" allowTransparency="true" frameborder="0" style="overflow:hidden;border:none;"></iframe>';
}

function rsslike_settings_page() {
?>

<div class="wrap">

<h2>rssLike Settings</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'rsslike-settings-group' ); ?>
    <table class="form-table">
	      <tr valign="top">
	        <th scope="row">iFrame URL</th>
	        <td><input size="70" type="text" name="likes_url" value="<?php echo get_option('likes_url'); ?>" /></td>
	      </tr>
	      <tr valign="top">
	        <th scope="row">RSS URL</th>
	        <td><input size="70" type="text" name="likes_rss" value="<?php echo get_option('likes_rss'); ?>" /></td>
	      </tr>
	      <tr valign="top">
	        <th scope="row">Apps</th>
	        <td><input size="40" type="text" name="likes_openappstore" value="<?php echo get_option('likes_openappstore'); ?>" /></td>
	      </tr>
    </table>
    
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
<?php } ?>
