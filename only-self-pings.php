<?php
/*
Plugin Name: Only Self Pings
Plugin URI: http://wordpress.org/extend/plugins/only-self-pings/
Description: Keep your privacy AND automatic pingbacks
Version: 1.0
Author: Ulf Benjaminsson
Author URI: http://www.ulfben.com
Author Email: ulfben@gmail.com
License:
  Copyright 2015 (ulf@ulfben.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
include_once plugin_dir_path(__FILE__).'init.php';
register_activation_hook(__FILE__, array('OnlySelfPingsInit', 'on_activate'));
register_deactivation_hook(__FILE__, array('OnlySelfPingsInit', 'on_deactivate'));
register_uninstall_hook(__FILE__, array('OnlySelfPingsInit', 'on_uninstall'));
class OnlySelfPings {
	private $_protected = array();
	private $_plugin = '';	
	function __construct() {					
		$this->_plugin = plugin_basename(__FILE__);
		load_plugin_textdomain( 'OnlySelfPings', false, dirname($this->_plugin) . '/lang' );		    								    		
		new OnlySelfPingsInit('activate'); //the activation hook is finicky - I don't get default settings on fresh install. Thus this hackery.
		add_action('admin_menu', array($this, 'register_admin_menu'));
		add_action('admin_init', array($this, 'register_settings'));			 
		add_filter('plugin_row_meta', array($this, 'add_settings_link'), 10, 2 );					
		add_action('pre_ping', array($this, 'filter_pings'));					
	}
	function filter_pings(&$links, &$pung){		
		$allowed = get_option('OnlySelfPingsOpts');
		if(!$allowed || !isset($allowed['whitelist']) || !is_array($allowed['whitelist'])){
			$allowed['whitelist'] = array(get_option('home'));
		}
		$allowed = $allowed['whitelist'];
		$clean = array();				
		foreach($links as $l => $link){		
			foreach($allowed as $allowed_url){
				if(0 === strpos($link, $allowed_url) || false === strpos($link, 'http://')){
					$clean[] = $link;					
					break;
				}		
			}
		}		
		$links = $clean;	
	}		
	/*admin panel stuffs, many thanks to
		http://wp.tutsplus.com/tutorials/the-complete-guide-to-the-wordpress-settings-api-part-4-on-theme-options/*/
	function register_admin_menu(){
		add_options_page('Only Self Pings Options', 'Only Self Pings', 'administrator', 'onlyselfpings-options', array($this, 'options_page'));
	}	
	function add_settings_link($links, $file){			
		if ($file != $this->_plugin){return $links;}
		return array_merge($links, array('<a href="options-general.php?page=onlyselfpings-options">'.__('Settings').'</a>'));		
	}				
	function register_settings(){
		if(false == get_option('OnlySelfPingsOpts')){
			new OnlySelfPingsInit('activate');
		}		
		add_settings_section(
			'general_section',		// ID used to identify this section and with which to register options
			'',	// Title to be displayed on the administration page
			array($this, 'general_section_cb'),	// Callback used to render the description of the section
			'onlyselfpings-settings-group'	// Page on which to add this section of options
		);		
		add_settings_field(
			'whitelist', // ID used to identify the field throughout the theme
			'<h3><label for="whitelist">'.__('White List:', 'OnlySelfPings').'</label></h3><p>'.
				__('Add URLs that are allowed to be pinged, separated by comma or newline.<br /><br />
					The URLs must start with "http", but is <strong>not</strong> case sensitive.<br /><br />
					Example; <code>'.get_option('home').'/</code>', 'OnlySelfPings').'</p>', // The label to the left of the option interface element
			array($this, 'whitelist_option_cb'),	// The name of the function responsible for rendering the option interface
			'onlyselfpings-settings-group',	// The page on which this option will be displayed
			'general_section',	// The name of the section to which this field belongs
			array() // The array of arguments to pass to the callback.
		);
		register_setting(
			'onlyselfpings-settings-group',
			'OnlySelfPingsOpts',
			array($this,'OnlySelfPingsOpts_sanitize')
		);		
	}
	function general_section_cb(){}
	function whitelist_option_cb($args) {				
		$allowed = get_option('OnlySelfPingsOpts');
		if(!$allowed || !isset($allowed['whitelist']) || !is_array($allowed['whitelist'])){
			$allowed = array();
			$allowed['whitelist'] = array(get_option('home'));
		}		
		$whitelist = $allowed['whitelist'];		
		echo '<textarea id="whitelist" name="OnlySelfPingsOpts[whitelist]" rows="8" cols="50" type="textarea">'.esc_textarea(implode("\r\n", $whitelist)).'</textarea>';							
	}
	function startsWithHTTP($value){
		return(stripos($value, 'http') === 0);
	}
	function OnlySelfPingsOpts_sanitize($input){		
		$input['whitelist'] = array_map('trim', preg_split("/[\r\n,]+/", $input['whitelist'], -1, PREG_SPLIT_NO_EMPTY));
		$input['whitelist'] = array_filter($input['whitelist'], array($this, 'startsWithHTTP'));		
		$input['whitelist'] = array_unique($input['whitelist']);		
		return $input;			
	}
	function options_page() {  
	?>  		
		<div class="wrap">  	  			
			<div id="icon-options-general" class="icon32"></div>  
			<h2>Only Self Pings</h2>  	  					
			<?php settings_errors(); ?>  	  			
			<form method="post" action="options.php">  
				<?php settings_fields('onlyselfpings-settings-group'); ?>  
				<?php do_settings_sections('onlyselfpings-settings-group'); ?>  
				<?php submit_button(); ?>  
			</form> 
			<?php include_once(plugin_dir_path(__FILE__).'about.php'); ?>			
		</div>
	<?php  
	}
}
new OnlySelfPings();
?>