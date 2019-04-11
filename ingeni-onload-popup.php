<?php
/*
Plugin Name: Ingeni Popup
Version: 2019.07
Plugin URI: http://ingeni.net
Author: Bruce McKinnon - ingeni.net
Author URI: http://ingeni.net
Description: Display a Foundation Reveal Popup when visit a site, or when you login
*/

/*
Copyright (c) 2019 Ingeni Web Solutions
Released under the GPL license
http://www.gnu.org/licenses/gpl.txt

Disclaimer: 
	Use at your own risk. No warranty expressed or implied is provided.
	This program is free software; you can redistribute it and/or modify 
	it under the terms of the GNU General Public License as published by 
	the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
 	See the GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA


Requires : Wordpress 3.x or newer ,PHP 5 +

v2019.01 - Initial version
v2019.02 - Add admin setting to change the number of days it repeats.
v2019.03 - Add support for anonymous non-logged in popups
v2019.04 - Re-organised code to improve anon popup reliability
v2019.05 - Last version was abit of a disaster. Re-factored code to make it much more
						reliable in both modes.
v2019.06 - Lots more refactoring and bug fixes.
v2019.07 - Supports updating via Github


*/

if ( !class_exists( 'IngeniOnloadPopup' ) ) {
	class IngeniOnloadPopup {
		public $name = 'Ingeni On-Load Popup';
		public $tag = 'ingeni_onload_popup';

		const SAVE_INGENI_POPUP_SETTINGS = "Save Settings...";
		const INGENI_POPUP_EXPIRY_DATE = 'ingeni_popup_expiry_date';
		const INGENI_POPUP_LOGGED_IN_ONLY = 'ingeni_popup_logged_in_only';
		const INGENI_POPUP_REPEAT_DAYS = 'ingeni_popup_repeat_days';
		const INGENI_POPUP_MSG = 'ingeni_popup_msg';

		public $show_popup = false;	// Global flag to display popup
		private $debug_on = false;

		public function __construct() {
			try {
				add_action( 'init', array( &$this,'ingeni_load_ingeni_popup') );

				add_action( 'wp_loaded', array( &$this,'ingeni_popup_set_anon_cookie') );
				if ( is_admin() ) {
					add_action( 'admin_menu', array( $this, 'ingeni_popup_submenu_page' ) );
				} else {

				}

			} catch (Exception $ex) {
				$this->ingeni_popup_log('__construct(): '.$ex->message);
			}		
		}


		public function init() {

			load_plugin_textdomain(
				$this->tag,
				false,
				basename( dirname( __FILE__ ) ) . '/languages/'
			);

			// Init auto-update from GitHub repo
			require 'plugin-update-checker/plugin-update-checker.php';
			$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
				'https://github.com/BruceMcKinnon/BLContactDetails',
				__FILE__,
				'bl-contact-details'
			);

		}
				
		public function ingeni_popup_submenu_page() {
			add_submenu_page( 'tools.php', 'Ingeni Popup', 'Ingeni Popup', 'manage_options', 'ingeni_popup_options_page', array( &$this, 'ingeni_popup_options_page' ) );

			$this->ingeni_popup_log('ingeni_popup_submenu_page');
		}
		
		public function ingeni_popup_options_page() {
			$this->ingeni_popup_log('ingeni_popup_options_page');

			if ( (isset($_POST['ingeni_popup_edit_hidden'])) && ($_POST['ingeni_popup_edit_hidden'] == 'Y') ) {
				$errMsg = '';
				
				switch ($_REQUEST['btn_ingeni_popup_submit']) {
		
					
					case self::SAVE_INGENI_POPUP_SETTINGS :
						try {
							//update_option(INGENI_POPUP_TOKEN, $_POST[INGENI_POPUP_TOKEN] );
							update_option(self::INGENI_POPUP_LOGGED_IN_ONLY, $_POST[self::INGENI_POPUP_LOGGED_IN_ONLY] );
							update_option(self::INGENI_POPUP_MSG, $_POST[self::INGENI_POPUP_MSG] );
							update_option(self::INGENI_POPUP_EXPIRY_DATE, $_POST[self::INGENI_POPUP_EXPIRY_DATE] );
							update_option(self::INGENI_POPUP_REPEAT_DAYS, $_POST[self::INGENI_POPUP_REPEAT_DAYS] );
		
							echo('<div class="updated"><p><strong>Settings saved...</strong></p></div>');
		
						} catch (Exception $e) {
							echo('<div class="updated"><p><strong>Error: '.$e->getMessage().'</strong></p></div>');		
						}
		
					break;
				}
			}
		
			echo('<div class="wrap">');
				echo('<h2>Ingeni Popup Settings</h2>');
		
				echo('<form action="'. str_replace( '%7E', '~', $_SERVER['REQUEST_URI']).'" method="post" name="ingeni_popup_options_page">'); 
					echo('<input type="hidden" name="ingeni_popup_edit_hidden" value="Y">');
					
					echo('<table class="form-table" style="width:90%;max-width:400px;">');
					
					echo('<tr valign="top" style="height:200px;">');
		
					// Preserve lines breaks in multiline text boxes and allow some HTML
					$allowed = array(
						'span' => array(),
						'br' => array(),
						'em' => array(),
						'strong' => array(),
						'p' => array(),
						'a' => array(),
						'div' => array(),
					);
					$cleaned_msg = wp_kses_post ( get_option(self::INGENI_POPUP_MSG), $allowed ) ;
		
		
					echo('<td>Popup Message</td><td><textarea rows="8" cols="100" name="'.self::INGENI_POPUP_MSG.'" >'.$cleaned_msg.'</textarea></td>'); 
					echo('</tr>');			
					echo('<tr valign="top">');
		
						$expiry_date = get_option(self::INGENI_POPUP_EXPIRY_DATE);
						if (DateTime::createFromFormat('Y-m-d', $expiry_date) === FALSE) {
							$expiry_date = date('Y-m-d');
						}
		
						echo('<td>Expiry Date</td><td><input type="date" min="2019-01-01" name="'.self::INGENI_POPUP_EXPIRY_DATE.'" value="'.$expiry_date.'"></td>'); 
					echo('</tr>');
					echo('<tr valign="top">');
		
						$repeat_days = get_option(self::INGENI_POPUP_REPEAT_DAYS);
				//fb_log('raw repeat: '.$repeat_days);
						if ( ($repeat_days == '') ) {
							$repeat_days = 7;
						}
						if ($repeat_days < 0) {
							$repeat_days = 0;
						}
						// Max  = 365 days
						if ($repeat_days > 365) {
							$repeat_days = 365;
						}				
						echo('<td>Repeat Every x Days</td><td><input type="number" min="0" max="365" name="'.self::INGENI_POPUP_REPEAT_DAYS.'" value="'.$repeat_days.'"></td>'); 
					echo('</tr>');
					echo('<tr valign="top">');
						$logged_in_only = trim( get_option(self::INGENI_POPUP_LOGGED_IN_ONLY) );
						if ( ($logged_in_only == '') || empty($logged_in_only) ) {
							$logged_in_only = 'unchecked';
						} else {
							$logged_in_only = 'checked';
						}
						echo('<td>Show for Logged in Users Only</td><td><input type="checkbox" name="'.self::INGENI_POPUP_LOGGED_IN_ONLY.'" value="'.self::INGENI_POPUP_LOGGED_IN_ONLY.'" '.$logged_in_only.'></td>'); 
					echo('</tr>');
		
		
		
					echo('</tbody></table><br/>');			
					
					echo('<p class="submit"><input type="submit" name="btn_ingeni_popup_submit" id="btn_ingeni_popup_submit" class="button button-primary" value="'.self::SAVE_INGENI_POPUP_SETTINGS.'"></p>');
				echo('</form>');	
			echo('</div>');
		}


		function ingeni_popup_set_anon_cookie() {

			$epoch_time = time();

			// If there is no valid cookie, set one for the current date/time
			// and then go down the route of check if the popup should be shown.
			// If the cook is set, then we are between the time the popup
			// was last shown and when it should next be shown.
			if ( !isset($_COOKIE["ingeni-onload-popup"]) ) {
				$this->ingeni_popup_log('cookie not set!');
				$repeat_days = get_option(self::INGENI_POPUP_REPEAT_DAYS);
				$value = "ingeni-anon-seen";

				// Set the longer lasting expiry cookie
				$expires = $epoch_time + ( $repeat_days * 86400 );

				setcookie( "ingeni-onload-popup", $value, $expires, '/', COOKIE_DOMAIN);
				$this->ingeniCheckCookies();

			} elseif ( get_option(self::INGENI_POPUP_LOGGED_IN_ONLY) == self::INGENI_POPUP_LOGGED_IN_ONLY ) {
				// If we are only showing this to logged in users, then the force checking as the WP db
				// holds details of when the user last saw the message.
				$this->ingeniCheckCookies();
			}

			if ($this->show_popup) {
				add_action( 'wp_footer', array( &$this,'ingeniShowPopup') );
			}
		}


		function ingeniCheckCookies() {

			$this->ingeni_popup_log('ingeniCheckCookies: '.$this->BoolToStr($this->show_popup));
			try {

				$this->ingeni_popup_log('show popup start');
				if ( !is_admin() ) {
					$logged_in_only = false;
					$this->ingeni_popup_log('not admin');

					if ( get_option(self::INGENI_POPUP_LOGGED_IN_ONLY) == self::INGENI_POPUP_LOGGED_IN_ONLY ) {
						$logged_in_only = true;
					}
					$this->ingeni_popup_log('logged in only: '.$this->BoolToStr($logged_in_only));
					$expiry_date = new DateTime(get_option(self::INGENI_POPUP_EXPIRY_DATE) . ' 23:59:59');

					$now = date("Y-m-d H:i:s");
					$last_visit = $now;

					if ($expiry_date > $now)  {
						// Has not expired
						$this->ingeni_popup_log('not expired');
						if ($logged_in_only) {
							$this->show_popup = is_user_logged_in();
							$this->ingeni_popup_log('is logged in: ['.get_current_user_id().'] '.$this->BoolToStr($this->show_popup));
							if ($this->show_popup) {
								$last_shown = get_user_meta(get_current_user_id(), 'last_popup_shown');
								if ( empty($last_shown) ) {
									$last_shown = '2000-01-01 00:00:00';
								}
								$this->ingeni_popup_log('was last shown: ['.get_current_user_id().'] '.print_r($last_shown,true));

								$repeat_days = get_option(self::INGENI_POPUP_REPEAT_DAYS);
								$last_visit = date( 'Y-m-d H:i:s', strtotime('+' . $repeat_days . ' days',strtotime($last_shown[0])));
								$this->ingeni_popup_log('checking last show: ['.get_current_user_id().'] ' . $last_visit . ' < ' . $now);

								// Show the popup??
								if ( $last_visit < $now ) {
									$this->show_popup = true;
								} else {
									$this->show_popup = false;
								}
							}

						} else {
							// Anon user - We pre-validate anonymous users in set_anon_cookie()
							$last_visit = date( 'Y-m-d H:i:s', strtotime('2000-01-01 00:00:00') );
							$this->show_popup = true;
							$this->ingeni_popup_log('anon will show: ');
						}
					}
				}
						
			} catch (Exception $ex) {
				if ( function_exists("fb_log") ) {
					$this->ingeni_popup_log('ShowIngeniPopup: '.$ex->message);
				}
			}
		}


		function ingeniShowPopup() {
			/*
				// Check that jQuery is loaded
				if ( ! wp_script_is('foundation.reveal.js') ) {
					//wp_enqueue_script( 'jquery', 'jquery' );
					$this->ingeni_popup_log('reveal js is not enqueued!');
				} else {
					$this->ingeni_popup_log('reveal js is supported!');
				}
			*/

			$this->ingeni_popup_log('ingeniShowPopup will show: '.$this->BoolToStr($this->show_popup));
			if ( $this->show_popup ) {
				$retHtml = '<div class="large reveal" style="display:none;" id="ingeniPopupModal" data-reveal>';
					$retHtml .=  wp_kses_post( get_option(self::INGENI_POPUP_MSG) );
					$retHtml .= '<button class="close-button" data-close aria-label="Close modal" type="button">';
						$retHtml .= '<span aria-hidden="true">&times;</span>';
					$retHtml .= '</button>';
				$retHtml .= '</div>';
				$retHtml .= '<script>
				jQuery(document).ready(function($) { $("#ingeniPopupModal").foundation("open");console.log("loaded...."); });</script>';

				echo $retHtml;

				if ( is_user_logged_in() && (!is_admin()) ) {
					$now = date("Y-m-d H:i:s");
					$this->ingeni_popup_log('ingeniShowPopup: ['.get_current_user_id().'] '.$this->BoolToStr($this->show_popup));
					update_user_meta(get_current_user_id(), 'last_popup_shown', $now);
				}
			}
		}


		private function BoolToStr($value) {
			if ($value) {
				return 'true';
			} else {
				return 'false';
			}
		}



		private function ingeni_popup_log($msg) {
			if ($this->debug_on) {
				$upload_dir = wp_upload_dir();
				$logFile = $upload_dir['basedir'] . '/' . 'ingeni_popup_log.txt';
				date_default_timezone_set('Australia/Sydney');
			
				// Now write out to the file
				$log_handle = fopen($logFile, "a");
				if ($log_handle !== false) {
					fwrite($log_handle, date("d M H:i:s").": ".$msg."\r\n");
					fclose($log_handle);
				}
			}
		}



		public function ingeni_load_ingeni_popup() {	
			// css
			$siteurl = get_option('siteurl');
			$url = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/';
			wp_enqueue_style( 'ingeni-popup-css', $url . 'ingeni-onload-popup.css' );
		}
		





	}
}

global $clsPopup;
$clsPopup = new IngeniOnloadPopup();

// Plugin activation/deactivation hooks
function ingeni_popup_activation() {
	flush_rewrite_rules( false );
}
register_activation_hook(__FILE__, 'ingeni_popup_activation');

function ingeni_popup_deactivation() {
  flush_rewrite_rules( false );
}
register_deactivation_hook( __FILE__, 'ingeni_popup_deactivation' );
?>