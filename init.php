<?php
if(!class_exists('OnlySelfPingsInit')):
/**
 * This class triggers functions that run during activation/deactivation & uninstallation
 * http://wordpress.stackexchange.com/questions/25910/uninstall-activate-deactivate-a-plugin-typical-features-how-to/25979#25979
 */
class OnlySelfPingsInit{    
    const STATE_OF_ORIGIN = false;// Set this to true to reset plugin, instead of uninstalling.
    function __construct($case = false){
        if(!$case){
			wp_die( 'Busted! You should not call this class directly', 'Doing it wrong!' );
		}
        switch($case){
            case 'activate' :         
                add_action('init', array(&$this, 'activate_cb'));
                break;
            case 'deactivate' :                 
                add_action('init', array(&$this, 'deactivate_cb'));
                break;
            case 'uninstall' :                 
                add_action('init', array(&$this, 'uninstall_cb'));
                break;
        }
    }    
    //Set up tables, add options, etc. - All preparation that only needs to be done once    
    function on_activate(){
        new OnlySelfPingsInit('activate');
    }
    /**
     * Do nothing like removing settings, etc. 
     * The user could reactivate the plugin and wants everything in the state before activation.
     * Take a constant to remove everything, so you can develop & test easier.
     */
    function on_deactivate(){
        $case = 'deactivate';
        if(STATE_OF_ORIGIN){
            $case = 'uninstall';
		}
        new OnlySelfPingsInit($case);
    }
    // Remove/Delete everything 
    function on_uninstall(){        
       /* if(__FILE__ != WP_UNINSTALL_PLUGIN){// important: check if the file is the one that was registered with the uninstall hook (function)
            return;
		}*/
        new OnlySelfPingsInit('uninstall');
    }    
	function activate_cb(){
        $defaults = array(
			'whitelist' => array(				
				get_option('home'),
				get_option('siteurl')
			)			
		);				
		add_option('HideReferOpts', $defaults);		
    }
    function deactivate_cb(){       
        //$this->error( "Some message.<br />" ); // if you need to output messages in the 'admin_notices' field
        //$this->error( "Some message.<br />", TRUE ); //to also stop further processing
    }
    function uninstall_cb(){ //// Stuff like delete tables, etc.        
        delete_option('HideReferOpts');
    }
    /**
     * trigger_error()
     * 
     * @param (string) $error_msg
     * @param (boolean) $fatal_error | catched a fatal error - when we exit, then we can't go further than this point
     * @param unknown_type $error_type
     * @return void
     */
    function error( $error_msg, $fatal_error = false, $error_type = E_USER_ERROR ){
        if( isset( $_GET['action'] ) && 'error_scrape' == $_GET['action'] ){
            echo "{$error_msg}\n";
            if ( $fatal_error ){
                exit;
			}
        } else {
            trigger_error( $error_msg, $error_type );
        }
    }
}
endif;
?>