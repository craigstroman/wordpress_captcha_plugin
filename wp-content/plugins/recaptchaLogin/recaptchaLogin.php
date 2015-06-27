<?php 
/*
Plugin Name: WP Login Form with reCAPTCHA
Plugin URI: http://www.stromannet.com/
Description: Add Google's reCAPTCHA to WordPress Login 
Version: 1.0
Author: Craig Stroman
Author URI: http://www.stromannet.com/
License: GPL2
*/
class reCAPTCHA_Login_Form {

	/** @type string private key|public key */
	private $public_key;
	private $private_key;

	/** class constructor **/
	public function __construct () {
		$this->public_key = "replace-with-your-recaptcha-public-key";
		$this->private_key = "replace-with-your-recaptcha-private-key;

		add_action( 'wp_enqueue_scripts', 'mysite_enqueue' );
		 // adds the captcha to the login form
		add_action( 'login_form', array( $this, 'captcha_display' ) );

		// authenticate the captcha answer
		add_action( 'wp_authenticate_user', array( $this, 'validate_captcha_field' ), 10, 2 );		
	}
	public function mysite_enqueue() {
		wp_enqueue_script( 'mysite-scripts', "https://www.google.com/recaptcha/api.js" );
	}
	/** Output  the reCaptcha form field. **/
	public function captcha_display () {
		?>
			<div class="g-recaptcha" data-sitekey="replace-with-your-site-key"></div>
		<?php	
	}

	/**
	* Verifiy the captcha answer
	* 
	* @param $user string login username
	* @param $password  string login password
	* 
	* @return WP_Error|WP_user
	**/
	public function validate_captcha_field ($user, $password) {
		if (!isset($_POST['recaptcha_response_field']) || empty($_POST['recaptcha_response_field'])) {
			return new WP_Error('empty_captcha', 'Captcha should not be empty.');
		}

		if(isset($_POST['recaptcha_response_field']) && $this->recaptcha_response() == 'false') {
			return new WP_Error('invalid_captcha', 'Captcha response was incorrect.');
		}

		return $user;
	}

	/**
	* Get the reCaptcha API response.
	* 
	* @return string
	**/
	public function recaptcha_response () {
		//reCaptcha challange post data
		$challenge = isset($_POST['recaptcha_challenge_field'])? esc_attr($_POST['recaptcha_challenge_field']) : '';

		//reCaptcha response post data
		$response = isset($_POST['recaptcha_response_field'])? esc_attr($_POST['recaptcha_response_field']) : '';

		$remote_ip = $_SERVER['REMOTE_ADDR'];

		$post_body = array(
			'privatekey' => $this->private_key,
			'remoteip' => $remote_ip,
			'challenge' => $challenge,
			'response' => $response
		);

		return $this->recaptcha_post_request($post_body);
	}

	/**
	* Send HTTP POST request and return the response.
	* 
	* @param $post_body array HTTP POST body
	*
	* @return bool
	**/
	public function recaptcha_post_request ($post_body) {
		$args = array('body'=>$post_body);

		//make a POST request to the Google reCaptcha Server
		$request  = wp_remote_post('https://www.google.com/recaptcha/api/verify', $args);

		//get the request response body
		$response_body = wp_remote_retrieve_body($request);
		
		/**
		* explode the response body and use the request_status
		* @see https://developers.google.com/recaptcha/docs/verify
		**/
		$answers = explode("\n", $response_body);

		$request_status = trim($answers[0]);

		return $request_status;
	}
	
}// reCAPTCHA_Login_Form

new reCAPTCHA_Login_Form();