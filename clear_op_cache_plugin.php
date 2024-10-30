<?php
/**
 * Plugin Name: Clear OPcache Plugin
 * Description: Flush PHP OPcache and WinCache with the click of a button and automatically before WordPress updates.
 * Version: 0.5
 * Author: Binary Cocoa
 * Author URI: https://binarycocoa.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') or die('No script kiddies please!');

function bc_cocp_clear_iis_wincache() {
	if(!function_exists('wincache_ucache_get')) {
		return;
	}
	if(!wincache_ucache_clear()) {
		return false;
	} else {
		return true;
	}
}

function bc_cocp_clear_php_opcache() {
	if (!extension_loaded('Zend OPcache')) {
		return;
	}
	$opcache_status = opcache_get_status();
	if (false === $opcache_status["opcache_enabled"]) {
		// extension loaded but OPcache not enabled
		return;
	}
	if (!opcache_reset()) {
		return false;
	} else {
		/**
		 * opcache_reset() is performed, now try to clear the
		 * file cache.
		 * Please note: http://stackoverflow.com/a/23587079/1297898
		 *   "Opcache does not evict invalid items from memory - they
		 *   stay there until the pool is full at which point the
		 *   memory is completely cleared"
		 */
		foreach( $opcache_status['scripts'] as $key => $data ) {
			$dirs[dirname($key)][basename($key)] = $data;
			opcache_invalidate($data['full_path'] , $force=true);
		}
		return true;
	}
}

function bc_cocp_is_iis() {
	$software = strtolower($_SERVER["SERVER_SOFTWARE"]);
	if(false !== strpos($software, "microsoft-iis"))
		return true;
	else
		return false;
}

function bc_cocp_clear_caches() {
	if (bc_cocp_is_iis()) {
		if (bc_cocp_clear_iis_wincache()) {
			error_log('WinCache user cache cleared.');
		}
		else {
			error_log('Clearing WinCache user cache opcode cache failed.');
		}
	}
	if (bc_cocp_clear_php_opcache()) {
		error_log('PHP OPcache opcode cache cleared.');
	} else {
		error_log('Clearing PHP OPcache opcode cache failed.');
	}
}

add_filter('plugin_row_meta', 'bc_cocp_plugin_row_meta', 10, 2);
function bc_cocp_plugin_row_meta($links, $file) {
	if (!preg_match('/clear_op_cache_plugin.php$/', $file)) {
	  return $links;
	}

	return $links;
}
add_filter('upgrader_pre_install', 'bc_cocp_clear_caches', 10, 2);
add_filter('upgrader_process_complete', 'bc_cocp_clear_caches', 10, 2);

function bc_cocp_add_flush_opcache_button($admin_bar){
	?>
		<form action="<?php echo admin_url('admin-post.php'); ?>" method="POST" id="bc_cocp_clearopcache_form" style="display: none;">
			<input type="hidden" name="action" value="cocp_clear_opcache">
		</form>
		<script>
			function bc_cocp_clearopcache() {
				console.log('Clearing OpCache...');
				document.getElementById('bc_cocp_clearopcache_form').submit();
			}

			if (window.location.href.includes("opcache_cleared=true")) {
				alert("You've cleared your OPcache!");
			}

			if (window.location.href.includes("opcache_cleared=false")) {
				alert("OPCache isn't enabled!");
			}
		</script>
	<?php
  global $pagenow;
  $admin_bar->add_menu( array( 'id'=>'cache-purge','title'=>'Flush OPcache','href'=>'#', 'meta'=>['onclick'=>'bc_cocp_clearopcache(); return false;'] ) );
}
add_action('admin_bar_menu', 'bc_cocp_add_flush_opcache_button', 100);

function bc_cocp_clearopcache() {
	$exists = function_exists("opcache_get_status");

	if (!$exists) {
		$location = add_query_arg('opcache_cleared', 'false', $_SERVER['HTTP_REFERER']);
    wp_safe_redirect($location);
		die();
	}

	$enabled = opcache_get_status()["opcache_enabled"];

	if ($enabled) {
		bc_cocp_clear_caches();
		// wp_redirect("/wp-admin?opcache_cleared=true");
		$location = add_query_arg('opcache_cleared', 'true', $_SERVER['HTTP_REFERER']);
    wp_safe_redirect($location);
	} else {
		// wp_redirect("/wp-admin?opcache_cleared=false");
		$location = add_query_arg('opcache_cleared', 'false', $_SERVER['HTTP_REFERER']);
    wp_safe_redirect($location);
	}
}
add_action('admin_post_cocp_clear_opcache', 'bc_cocp_clearopcache');

function bc_cocp_register_options_page() {
	add_options_page('OPcache Details', 'OPcache', 'manage_options', 'bc_cocp', 'bc_cocp_options_page');
}
add_action('admin_menu', 'bc_cocp_register_options_page');

function bc_cocp_options_page() {
	$status = opcache_get_status();

	if (!$status) {
		?>
		<b>OPcache is disabled. You should enabled it.</b><br><br>
		<a href="https://www.php.net/manual/en/book.opcache.php">https://www.php.net/manual/en/book.opcache.php</a>
		<?php
		return;
	}

	$enabled = $status['opcache_enabled'] ? "Yes" : "No";
	$cache_full = $status['cache_full'] ? "Yes" : "No";

	$restart_pending = $status["restart_pending"] ? "Yes" : "No";
	$restart_in_progress = $status["restart_in_progress"] ? "Yes" : "No";

	$used_memory = floor($status["memory_usage"]["used_memory"] / 1000000);
	$free_memory = floor($status["memory_usage"]["free_memory"] / 1000000);

	$cached_scripts_count = $status["opcache_statistics"]["num_cached_scripts"];
	$cached_keys_count = $status["opcache_statistics"]["num_cached_keys"];

	$hits = $status["opcache_statistics"]["hits"];

	$config = opcache_get_configuration();

	$version = $config["version"]["version"] . " " . $config["version"]["opcache_product_name"];

	$directives = $config["directives"];

	?>
		<br>
		<button id="cocp-clear-btn">Flush OPcache</button><br>

		<script>
			var cocp_clear_btn = document.getElementById('cocp-clear-btn');
			cocp_clear_btn.onclick = function() {
				bc_cocp_clearopcache();
			}
		</script>

		<b>Memory Used/Available</b>: <i><?php echo $used_memory . "MB / " . $free_memory . "MB"; ?></i><br><br>

		<h2>Stats</h2>

		<b>Enabled</b>: <i><?php echo $enabled; ?></i><br>
		<b>Cache Full</b>: <i><?php echo $cache_full; ?></i><br>

		<br>

		<b>Restart Pending</b>: <i><?php echo $restart_pending; ?></i><br>
		<b>Restart In-Progress</b>: <i><?php echo $restart_in_progress; ?></i><br>

		<br>

		<b>Number of Cached Scripts</b>: <i><?php echo $cached_scripts_count; ?></i><br>
		<b>Number of Cached Keys   </b>: <i><?php echo $cached_keys_count; ?></i><br>

		<br>

		<b>Total Hits</b>: <i><?php echo $hits; ?></i><br>

		<h2>Configuration</h2>

		<b>Version</b>: <i><?php echo $version; ?></i><br>

		<br>

		<b>Directives (<a href="https://www.php.net/manual/en/opcache.configuration.php">documentation</a>)</b><br>

		<?php foreach($directives as $key=>$d): ?>
			<b><?php echo $key; ?></b>: <i><?php echo $d; ?></i><br>
		<?php endforeach; ?>
	<?php
}

function bc_cocp_plugin_activation() {
  update_option('bc_cocp_plugin_activated', true);
  update_option('bc_cocp_plugin_notice_shown', false);
}
register_activation_hook(__FILE__, 'bc_cocp_plugin_activation');

function bc_cocp_plugin_deactivation() {
  update_option('bc_cocp_plugin_activated', true);
}
register_deactivation_hook(__FILE__, 'bc_cocp_plugin_deactivation');

function bc_cocp_plugin_notices() {
  $shown = get_option('bc_cocp_plugin_notice_shown');

	$exists = function_exists("opcache_get_status");

	if (!$shown) {
		if ($exists) {
			$enabled = opcache_get_status()["opcache_enabled"];

			if ($enabled) {
				echo "<div class='updated'><h2>Clear OPcache is installed, and ready to go.</h2><p>You can now flush your OPcache with the <b>Flush OPcache</b> button above and view settings in the <i>Settings</i>><b>OPcache</b> page to the left.</p></div>";
			} else {
				echo "<div class='error'><h2>Clear OPcache is installed but you do not have OPcache enabled.</h2><p>Please refer to the <a href='https://www.php.net/manual/en/book.opcache.php'>documentation</a> or contact your host for support.</p></div>";
			}
		} else {
			echo "<div class='error'><h2>Clear OPcache is installed, but you do not have OPcache enabled.</h2><p>Please refer to the <a href='https://www.php.net/manual/en/book.opcache.php'>documentation</a> or contact your host for support.</p></div>";
		}

		update_option('bc_cocp_plugin_notice_shown', true);
	}
}
add_action('admin_notices', 'bc_cocp_plugin_notices');
