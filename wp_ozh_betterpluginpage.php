<?php
/*
Plugin Name: Ozh' Better Plugin Page
Plugin URI: http://planetozh.com/blog/my-projects/wordpress-better-plugin-page/
Description: Adds a little sliding plugin list and buttons to toggle display of Active/Inactive/Out-of-date plugins to the "Manage Plugins" page. For WordPress 2.5+
Author: Ozh
Author URI: http://planetozh.com/
Version: 1.0.1
*/

/* Release history:
   1.0    Initial Release
   1.0.1  Fixed: the plugin count was inaccurate
          Improved: the plugin count was also stupid :)
*/

global $wp_ozh_bpp;

$wp_ozh_bpp['add_list'] = true;
	// Boolean: to add a sliding mini list of all your plugins
	
$wp_ozh_bpp['add_refresh'] = false;
	// Boolean: to add the "Force Refresh" buttons. Please note: (ab)using this button will probably won't
	// help you fix anything, except under really particular circumstances that I cannot reveal because
	// their too secret and the world is not ready for a shocker this order of magnitude. Seriously.

function wp_ozh_bpp_reset() {
	update_option('update_plugins','');
	wp_redirect('plugins.php?force-check=true');
	exit;
}

function wp_ozh_bpp_init() {
	if (isset($_GET['action']) && $_GET['action'] == 'force-check') {
		check_admin_referer('force-check');
		wp_ozh_bpp_reset();
		die();
	}
	if ( isset($_GET['force-check']) ) {
		add_action('admin_notices', 'wp_ozh_bpp_refreshed');
	}
	wp_enqueue_script('jquery');
	add_action('admin_footer','wp_ozh_bpp_add_stuff');	
}

function wp_ozh_bpp_refreshed() {
	echo '<div id="message" class="updated fade"><p>Plugin check <strong>forced</strong></p></div>';
}


function wp_ozh_bpp_add_stuff() {
	global $wp_ozh_bpp;

	$path = get_option('siteurl').'/wp-content/plugins/'.dirname(plugin_basename(__FILE__)).'/images';
	$inactive = '<a class="button-secondary bpp_button" href="http://planetozh.com/blog/" id="bpp_inactive">Hide inactive</a>';
	$active = '<a class="button-secondary bpp_button" href="http://planetozh.com/blog/" id="bpp_active">Hide active</a>';
	$outdated = '<a class="button-secondary bpp_button" href="http://planetozh.com/blog/" id="bpp_outdated">Hide outdated</a>';
	$uptodate = '<a class="button-secondary bpp_button" href="http://planetozh.com/blog/" id="bpp_uptodate">Hide uptodate</a>';
	$allrows = '<a class="button-secondary bpp_button" href="http://planetozh.com/blog/" id="bpp_allrows">Show all</a>';
	
	if ($wp_ozh_bpp['add_refresh'])
	$force = '<a class="button-secondary" href="'.wp_nonce_url('plugins.php?action=force-check', 'force-check').'">Force plugin checks</a>';
	
	echo <<<CSS
<style type="text/css">
#ozh_bpp_wrap {
	background:#9e9 url($path/grip.gif) center left no-repeat;
	position:fixed;
	top: 0px;
	right:0px;
	z-index:9999;
	height:50%;
	overflow:hidden;
	padding:0px;
	padding-left:7px;
	width:0px;
	border-top:1px solid green;
	border-bottom:1px solid green;
	opacity:0.95;
}
#ozh_bpp {
	padding:0;
	color:green;
}
#ozh_bpp ol {
	margin: 0;
	font-size:10px;
	padding:0 0 0 3em;
	line-height:15px;
	background:#beb;
}
.bpp_vers {
	height:18px;
	float:center;
	background:transparent url($path/plugin_error.gif) top right no-repeat;
	padding-right:18px;
	padding-bottom:3px;
}
.bpp_zip {
	background:url($path/plugin_link.gif) left top no-repeat;
	padding-left:18px;
}
.bpp_upg {
	background:url($path/plugin_go.gif) left top no-repeat;
	padding-left:18px;
}
td.status span.active, .bpp_plugin {
	background:transparent url($path/plugin.gif) left top no-repeat !important;
	padding-left:18px;
}
td.status span.inactive {
	background:url($path/plugin_disabled.gif) left top no-repeat;
	padding-left:18px;
}
th.action-links, td.action-links {
	width:5%;
	text-align:left;
}
td.status span, td.status a, td.action-links a {
	display:block;
	height:18px;
}
.bpp_activate {
	background:url($path/plugin_add.gif) left top no-repeat;
	padding-left:18px;
}
.bpp_deactivate {
	background:url($path/plugin_delete.gif) left top no-repeat;
	padding-left:18px;
}
.bpp_edit {
	background:url($path/plugin_edit.gif) left top no-repeat;
	padding-left:18px;
}
#bpp_counter {
	margin:3px 8px 0pt 0pt;
	border-color:#5396C5;
	padding:2px 4px;
	vertical-align:top;
	font-size:12px;
	background:url($path/plugin.gif) left center no-repeat;
	padding-left:18px;
}
</style>

CSS;

	echo '<script type="text/javascript">'."\n";
	
	if ($wp_ozh_bpp['add_list']) echo <<<JS

jQuery(document).ready(function() {
	// Add Sliding plugin list if not MSIE
	if (jQuery.browser.msie) {return false;}
	jQuery('.wrap:first').before('<div id="ozh_bpp_wrap"><div id="ozh_bpp"></div></div>');
	ozh_bpp_list();
	jQuery('#ozh_bpp_wrap').mouseover(function() {
		jQuery(this).css('width','inherit').css('overflow','auto');
	});
	jQuery('#ozh_bpp_wrap').mouseout(function(){
		jQuery(this).css('width','0px').css('overflow','hidden');
	});

	// create plugin list
	function ozh_bpp_list() {
		var list = '';
		var i = 0;
		jQuery('td.name').each(function(){
			i = i + 1;
			jQuery(this).attr('id','ozh_bpp_plugin_'+i);
			var name=jQuery(this).text();
			list = list + '<li><a href="#ozh_bpp_plugin_'+i+'">'+name + '</a></li>' ;
		});
		jQuery('#ozh_bpp').html('<ol>'+list +'</ol>');
	}

});

JS;

	echo <<<JS
jQuery(document).ready(function() {
	var show = {
		"inactive": true,
		"active": true, 
		"outdated": true,
		"uptodate": true
	};
	// Add buttons
	jQuery('div.tablenav:first div.alignleft:last')
		.after('<div class="alignright" id="bpp_counter"></div>')
		.after('<div class="alignleft">$allrows</div>')
		//.after('<div class="alignleft">$outdated</div>')
		.after('<div class="alignleft">$uptodate</div>')
		.after('<div class="alignleft">$active</div>')
		.after('<div class="alignleft">$inactive</div>')
		.after('<div class="alignleft">$force</div>');
	// Button behavior togglage
	jQuery('.bpp_button').click(function(){
		var id = jQuery(this).attr('id').replace('bpp_','');
		if (id != 'allrows') {
			show[id] = !show[id];
			var text = (show[id]) ? 'Hide ' : 'Show ';
			jQuery(this).html(text+id);
			jQuery('#plugins tr').each(function(){
				if (jQuery(this).is('.'+id)) {
					jQuery(this).toggle();
				}
			});
		} else {
			jQuery('#plugins tr').each(function(){
				jQuery(this).show();
			});
		}
		ozh_bpp_count();
		return false;
	});
	// Mark plugin row (make them distinct from the "update plugin" rows
	jQuery('td.name').each(function(){
		jQuery(this).parent().addClass('bpp_pluginrow');
	});
	// Mark outdated rows
	jQuery('.plugin-update').each(function(){
		var zip = jQuery(this).find('a:first').attr('href');
		var ziphtml = jQuery(this).find('a:first').html();
		var upg = jQuery(this).find('a:last').attr('href');
		var upghtml = jQuery(this).find('a:last').html();
		jQuery(this).parent().addClass('outdated bpp_updaterow')
			.html('')
			.prev()
			.addClass('outdated')
			.find('td.vers').wrapInner('<span class="bpp_vers"></span>').end()
			.find('td:nth-child(4)').append('<span><a class="bpp_zip" href="'+zip+'" title="'+ziphtml+'">Download</a></span> <span><a class="bpp_upg" href="'+upg+'" title="'+upghtml+'">Upgrade</a></span>');
	});
	// Mark inactive and uptodate rows
	jQuery('#plugins tr').each(function(){
		if (!jQuery(this).is('.outdated')) {jQuery(this).addClass('uptodate')}
		if (!jQuery(this).is('.active')) {jQuery(this).addClass('inactive')}
	});
	// Change action links appearance
	jQuery('td.action-links').each(function(){
		jQuery(this)
			.find('a.edit[href^=plugin-editor.php]').addClass('bpp_edit').end()
			.find('a.edit[href^=plugins.php]').addClass('bpp_activate').end()
			.find('a.delete').addClass('bpp_deactivate');
		var html = jQuery(this).html();
		jQuery(this).html(html.replace('|',' '));
	});
	// Add credits
	jQuery('#plugins').parent().after('<div class="tablenav" style="padding-bottom:0px;-moz-border-radius:5px;">Thanks for using <a class="bpp_plugin" href="http://planetozh.com/blog/my-projects/wordpress-better-plugin-page/">Better Plugin Page</a> by <a href="http://planetozh.com/">Ozh</a>. Check my <a href="http://planetozh.com/blog/my-projects/">other plugins</a>! </div>');

	function ozh_bpp_count() {
		var count = jQuery('#plugins tr.bpp_pluginrow:visible').length;
		jQuery('#bpp_counter').html(count+ ' plugins');
	}
	ozh_bpp_count();
});

JS;
	
	echo '</script>'."\n";


}

add_action('load-plugins.php', 'wp_ozh_bpp_init');

?>