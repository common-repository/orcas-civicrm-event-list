<?php

/*
 * Plugin Name: CiviCRM Event List
 * Description: Display a list of CiviCRM events
 * Author: orc@s
 * Author URI: https://orcas.de
 * Text Domain: civicrm-event-list
 * Domain Path: /languages
 * Version: 0.2.3
 */


include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

class CiviCRMEventList {
	/**
	 * @var CiviCRMEventList Class instance
	 */
	private static $instance;

	private $events;

	public static function instance() {
		if (null == self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

    private function loadCiviCRM() {
	    $active = is_plugin_active('civicrm/civicrm.php');
        $uploadDir = wp_upload_dir();
        $civicrmSettingDir = $uploadDir['basedir'] . "/civicrm/";
        $exists = file_exists($civicrmSettingDir . "civicrm.settings.php");

        if($exists && $active) {
            require_once $civicrmSettingDir . "civicrm.settings.php";
            require_once plugin_dir_path(__FILE__) . '/../civicrm/civicrm/CRM/Core/Config.php';
            $config = CRM_Core_Config::singleton();
            require_once plugin_dir_path(__FILE__) . '/../civicrm/civicrm/api/api.php';
            return true;
        }
        else{
            return false;
        }
    }

    private function checkDate($event){
	    $currentDate = new DateTime();
	    if(! array_key_exists('registration_start_date',$event)){
            if(array_key_exists('registration_end_date',$event)){
                $end = new DateTime($event['registration_end_date']);
                if($currentDate > $end)
                    return false;
            }
	        return true;
        }
        else{
	        $start = new DateTime($event['registration_start_date']);
            $end = new DateTime($event['registration_end_date']);
	        if($currentDate >= $start && $currentDate <= $end) {
                return true;
            }
            else {
                return false;
            }
        }
    }

    private function getTime($timestring){
	    if($timestring != '00:00:00')
	        return substr($timestring,0,5);
	    else
	        return null;
    }

	private function __construct() {
	    if($this->loadCiviCRM()){
	        add_action('plugins_loaded', array($this, 'loadTextDomain'));
            add_shortcode('civicrm-event-list', array($this, 'renderShortcode'));
            add_action('wp_enqueue_scripts', [$this, 'loadScripts']);
        }
        else {
            add_action('admin_notices',[$this, 'adminNotice']);
        }
	}

	public function renderShortcode() {
        $this->loadEvents();
	    ob_start();
	    include plugin_dir_path(__FILE__) . '/view/event-list.php';
	    $content = ob_get_clean();
	    return $content;
    }

	public function loadEvents(){
        $date = date('Ymd') . '000000';

	    $this->events = civicrm_api3('Event','get', array(
	            'version' => 3,
                'event_start_date' => array(
                        '>=' => $date
                ),
                'options' => array(
                    'limit' => 1000,
                    'sort' => 'event_start_date ASC'
                )
        ));
    }

	public function adminNotice(){
	    ?>
            <div class="notice notice-error">
                <?php echo 'CiviCRM Event List: ' . __('CiviCRM not available or installed','civicrm-event-list') ?>
            </div>
        <?php
    }

	public function loadTextDomain() {
		load_plugin_textdomain('civicrm-event-list', false, basename(dirname(__FILE__)) . '/languages');
	}

	public function loadScripts() {
	    wp_enqueue_style('civicrm-event-list',plugin_dir_url(__FILE__) . '/assets/css/event-list.css',[],'0.1');
	    wp_enqueue_style('civicrm-event-font-awesome', plugin_dir_url(__FILE__). '/assets/css/fontawesome-all.min.css',[], '5.0.6');
    }
}

CiviCRMEventList::instance();