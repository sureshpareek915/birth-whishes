<?php 
/**
 * Settings Helper Class
 */	

class Birth_Wishes_Settings
{
	public static function settings_init(){

		add_action( 'admin_menu', array( 'Birth_Wishes_Settings','birth_api_add_admin_menu' ) );

		add_action( 'admin_init', array( 'Birth_Wishes_Settings','birth_api_settings_init' ) );

		add_action( 'admin_enqueue_scripts', array( 'Birth_Wishes_Settings','birth_api_settings_post_chek' ) );

		add_action("wp_ajax_send_user_birthday", array( 'Birth_Wishes_Settings','get_employee_data' ));
		
		add_action("wp_ajax_nopriv_send_user_birthday", array( 'Birth_Wishes_Settings', 'get_employee_data'));
	
	}

	public static function birth_activation_hook(){
		if(empty(get_option( 'birth_api_settings' ))){
			$options = array(
				'birth_api_text_field_0' => '' ,
				'birth_api_text_field_1' => '' ,
				'birth_api_text_field_2' => '' ,
				'birth_api_text_field_3' => '' ,
				'birth_data_birthday_email_sent' => '',
			);
			update_option( 'birth_api_settings' , $options , 'yes');
		}
	}

	public static function birth_deactivation_hook(){
		$timestamp = wp_next_scheduled( 'minute_remainder' );
    	wp_unschedule_event( $timestamp, 'minute_remainder' );
	}

	public static function birth_api_add_admin_menu(  ) {
		// add_options_page( 'Birth Whishes Settings', 'Birth Whishes Settings', 'manage_options', 'settings-api-page', array( 'Birth_Wishes_Settings','birth_api_options_page' ) );
		add_menu_page( 'Birth Whishes Settings', 'Birth Whishes Settings', 'manage_options', 'settings-api-page', array( 'Birth_Wishes_Settings','birth_api_options_page'), 'dashicons-chart-pie');
	}

	public static function birth_api_settings_init(  ) {
	    register_setting( 'birthPlugin', 'birth_api_settings' );
	    add_settings_section(
	        'birth_api_birthPlugin_section',
	        __( 'Settings for Birth Whishes', 'birth' ),array( 'Birth_Wishes_Settings','birth_api_settings_section_callback'),
	        'birthPlugin'
	    );

		/**
		 * @see We can extend this function by Registring new Field.
		 */
		add_settings_field(
	        'birth_api_text_field_1',
	        __( 'Send Email To', 'birth' ),array( 'Birth_Wishes_Settings','birth_to_email_field_callback'),
	        'birthPlugin',
	        'birth_api_birthPlugin_section'
		);
		add_settings_field(
	        'birth_api_text_field_2',
	        __( 'Email Subject', 'birth' ),array( 'Birth_Wishes_Settings','birth_to_email_subject_callback'),
	        'birthPlugin',
	        'birth_api_birthPlugin_section'
	    );
		add_settings_field(
	        'birth_api_text_field_0',
	        __( 'Birthday Email Template', 'birth' ),array( 'Birth_Wishes_Settings','birth_birthday_email_template_callback'),
	        'birthPlugin',
	        'birth_api_birthPlugin_section'
		);

		add_settings_field(
	        'birth_api_text_field_3',
	        __( 'Test For this Date', 'birth' ),array( 'Birth_Wishes_Settings','birth_test_date_callback'),
	        'birthPlugin',
	        'birth_api_birthPlugin_section'
		);
	}

	/**
	 * This function Used For the Birthday Mail Template 
	 */
	public static function birth_birthday_email_template_callback(  ) {
	    $options = get_option( 'birth_api_settings' );
	    $settings_value = empty($options['birth_api_text_field_0']) ? '' : $options['birth_api_text_field_0'] ;
	    ?>
		<p>use <code>%{names}%</code> to show names </p>
		<br/>
		<textarea name="birth_api_settings[birth_api_text_field_0]" id="birth_api_text_field_0" cols="80" rows="20" value="<?php echo $settings_value?>" required="true"><?php echo $settings_value?></textarea>
	    <?php
	}

	public static function birth_to_email_field_callback(  ) {
	    $options = get_option( 'birth_api_settings' );
	    $settings_value = empty($options['birth_api_text_field_1']) ? '' : $options['birth_api_text_field_1'];
	    ?>
		<input name="birth_api_settings[birth_api_text_field_1]" id="birth_api_text_field_1" type="text" value="<?php echo $settings_value?>" required="true">
	    <?php
	}

	
	public static function birth_to_email_subject_callback(  ) {
	    $options = get_option( 'birth_api_settings' );
	    $settings_value = empty($options['birth_api_text_field_2']) ? '' : $options['birth_api_text_field_2'];
	    ?>
		<input name="birth_api_settings[birth_api_text_field_2]" id="birth_api_text_field_2" type="text" value="<?php echo $settings_value?>" required="true">
	    <?php
	}

	public static function birth_test_date_callback(  ) {
	    $options = get_option( 'birth_api_settings' );
	    $settings_value = empty($options['birth_api_text_field_3']) ? '' : $options['birth_api_text_field_3'];
	    ?>
		<input name="birth_api_settings[birth_api_text_field_3]" id="birth_api_text_field_3" type="date" value="<?php echo $settings_value?>">
		<button class="button primary reset_date">Reset date</button> <p>(This field is to modify and set a particular date as current date, for testing purpose. <br/>If you want the script to take actual current date automatically, click <strong>Reset Date</strong> button and then click <strong>Save Changes</strong> button.)<p>
	    <?php
	}
	
	public static function get_employee_data(){
		$options = get_option( 'birth_api_settings' );
		$employee_list = json_decode(
			wp_remote_retrieve_body( 
				wp_remote_get( 
					BIRTH_EMPLOYEES_API,  
					array( 'headers' => array('Content-Type' => 'application/json') )
				) 
			)
		);
		$do_not_send_birthday_list = json_decode(
			wp_remote_retrieve_body( 
				wp_remote_get( BIRTH_DO_NOT_SEND_BIRTHDAY_EMAIL_API ) 
			)					
		);

		$birthday_whish_keys = [];

		$current_date = date( 'm-d' );
		if( ! empty( $options['birth_api_text_field_3'] ) ){
			$option_date = explode('-', $options['birth_api_text_field_3'] );	
			$current_date = $option_date[1].'-'.$option_date[2];
		}

		/**
		 * Run This the Filter for user Which have birthday in Leap years.
		 */
		$check_leap_birth_user = true;
		
		/**
		 * Checking if today is not leap year and 29 Feb 2020 
		 */
		$is_leap_year = Birth_Wishes_Settings::check_year( date('Y') );

		if( ( ! $is_leap_year ) && $current_date === '02-28' ){
			$check_leap_birth_user = false;
		}

		foreach( $employee_list as $key => $employee ){

			$birthdate = date( 'm-d', strtotime( $employee->dateOfBirth ) );

			if ( in_array( $employee->id, $do_not_send_birthday_list ) ) {
				continue;
			} else if ( $employee->employmentEndDate ) {
				continue;
			} else if ( $employee->employmentStartDate == '') {
				continue;
			} else if ( $check_leap_birth_user && $birthdate == '02-29') {
				$birthday_whish_keys[] = $key;
			} else if ( $current_date == $birthdate ){
				$birthday_whish_keys[] = $key;
			} else {
				/**Nothing */
			}

		}

		if( !empty ($birthday_whish_keys) ){
			foreach( $birthday_whish_keys as $key ){
				$birthday_whish[] = $employee_list[$key];
			}
			Birth_Wishes_Settings::send_mail($birthday_whish);
		}
	}

	private static function check_year($year) {
		$year = (int)$year;
		if ($year % 400 == 0) 
			return true; 
	
		if ($year % 100 == 0) 
			return false; 
	
		if ($year % 4 == 0) 
			return true; 
		return false; 
	} 

	/**
	 * Send Email Functions For the Whishes.
	 */

	private static function send_mail( $employee_data, $mail_type = 'birthday'){
		$options = get_option( 'birth_api_settings' );
		$employee_names = [];

		$birth_data_birthday_email_sent = get_option('birth_data_birthday_email_sent');
		print_r($birth_data_birthday_email_sent);
		if( ! is_array($birth_data_birthday_email_sent) ){
			$birth_data_birthday_email_sent[] = $birth_data_birthday_email_sent;
		}
		foreach( $employee_data as $employee ){
			if ( ! in_array( $employee->id, $birth_data_birthday_email_sent ) ) {
				$employee_names[] = $employee->name.' '.$employee->lastname;
				$employee_ids[] = $employee->id;
			}
		}
		switch( $mail_type ){
			case 'birthday':
				$content = ( $options['birth_api_text_field_0'] ) ? $options['birth_api_text_field_0'] : '';
				$to_mail = ( $options['birth_api_text_field_1'] ) ? $options['birth_api_text_field_1'] : get_option('admin_email', true)  ;
				$subject = ( $options['birth_api_text_field_2'] ) ? $options['birth_api_text_field_2'] : '';
				/**
				 * Dynamic name "%{names}%"
				 */
				$employee_names = implode( ", ", $employee_names );
				$content = str_replace("%{names}%", $employee_names, $content );		
				$headers = [];
				$headers[] = 'Content-Type: text/html; charset=UTF-8'; 
				$headers[] = 'From: Admin <'.get_option('admin_email', true).'>';
				if( isset($employee_ids) && $status = wp_mail( $to_mail, $subject, $content, $headers,'' ) ) {
					echo '1';
					$employee_ids = array_merge($birth_data_birthday_email_sent, $employee_ids); 
					update_option( "birth_data_birthday_email_sent", $employee_ids );
					
				} else { 
					echo '0';
				}

			break;
			default:
			
			break;
		}
		

	}
	public static function birth_api_settings_section_callback(  ) {
	    // echo __( 'This Template will be used For the Email', 'birth' );
	}

	public static function birth_api_settings_post_chek(  ) {
		wp_enqueue_script( 'admin-send-mail', BIRTH_WHISHES_URL.'assets/birthday.js','', true );
		wp_localize_script( 'admin-send-mail', 'urlAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        

	}
	/**
	 * Main Email For rendering The Settings Of Page.
	 */
	public static function birth_api_options_page(  ) {
		?>
	    <form action='options.php' method='post'>
	        <h2>Birth Whishes All Settings Admin Page</h2>
	        <?php
	        settings_fields( 'birthPlugin' );
			do_settings_sections( 'birthPlugin' );
			echo "<table><tr><td>";
			submit_button(null, 'primary', 'submit', false );
			echo "</td><td>";
			submit_button('Send Email', 'secondary', 'send_email', false);
			echo "</td></tr></table>";
	        ?>
	    </form>
	    <?php
	}
}
 

/***
 * Cron Job Scheduling
 */
add_filter( 'cron_schedules', 'minute_remainder' );
function minute_remainder( $schedules )
{
    $schedules['minute_remainder'] = array(
            'interval'  => DAY_IN_SECONDS,
            'display'   => __( 'Every hour', 'textdomain' )
    );
    return $schedules;
}
// Schedule an action if it's not already scheduled
 if ( ! wp_next_scheduled( 'minute_remainder' ) ) {
    wp_schedule_event( time(), 'minute_remainder', 'minute_remainder' );
}
// Hook into that action that'll fire every three minutes
add_action( 'minute_remainder', 'case_remainder_hour_function' );
function case_remainder_hour_function()
{
	$employee_ids = array();
	/**
	 * Clearing First the Email sent Users After 1 day in Cron job.
	 */
	update_option( "birth_data_birthday_email_sent", $employee_ids );
	Birth_Wishes_Settings::get_employee_data();
    
}