<?php
//
// Admin functions for Ingeni Onload Popup
//
/*
define("SAVE_INGENI_POPUP_SETTINGS", "Save Settings...");
define("INGENI_POPUP_EXPIRY_DATE", "ingeni_popup_expiry_date");
define("INGENI_POPUP_LOGGED_IN_ONLY", "ingeni_popup_logged_in_only");
define("INGENI_POPUP_REPEAT_DAYS", "ingeni_popup_repeat_days");
define("INGENI_POPUP_MSG", "ingeni_popup_msg");
*/


const SAVE_INGENI_POPUP_SETTINGS = 'Save Settings...';
const INGENI_POPUP_EXPIRY_DATE = 'ingeni_popup_expiry_date';
const INGENI_POPUP_LOGGED_IN_ONLY = 'ingeni_popup_logged_in_only';
const INGENI_POPUP_REPEAT_DAYS = 'ingeni_popup_repeat_days';
const INGENI_POPUP_MSG = 'ingeni_popup_msg';

public function ingeni_popup_submenu_page() {
	$this->ingeni_popup_log('ingeni_popup_submenu_page');

	add_submenu_page( 'tools.php', 'Ingeni Popup', 'Ingeni Popup', 'manage_options', 'ingeni_popup_options', array( &$this, 'ingeni_popup_options_page' ) );

}

 function ingeni_popup_options_page() {
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
				echo('<td>Show for Logged in Users Only</td><td><input type="checkbox" name="'.self::INGENI_POPUP_LOGGED_IN_ONLY.'" value="'.INGENI_POPUP_LOGGED_IN_ONLY.'" '.$logged_in_only.'></td>'); 
			echo('</tr>');



			echo('</tbody></table><br/>');			
			
			echo('<p class="submit"><input type="submit" name="btn_ingeni_popup_submit" id="btn_ingeni_popup_submit" class="button button-primary" value="'.SAVE_INGENI_POPUP_SETTINGS.'"></p>');
		echo('</form>');	
	echo('</div>');
}

?>