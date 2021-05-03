<?php

/**
 * @package Forward
 *
 * @author RapidDev
 * @copyright Copyright (c) 2019-2021, RapidDev
 * @link https://www.rdev.cc/forward
 * @license https://opensource.org/licenses/MIT
 */

namespace Forward;

defined('ABSPATH') or die('No script kiddies please!');

/**
 *
 * Ajax
 *
 * @author   Leszek Pomianowski <https://rdev.cc>
 * @license	MIT License
 * @access   public
 */
class Ajax
{
	/** ERROR CODES */
	private const ERROR_UNKNOWN                  = 'e00';
	private const ERROR_MISSING_ACTION           = 'e01';
	private const ERROR_MISSING_NONCE            = 'e02';
	private const ERROR_INVALID_NONCE            = 'e03';
	private const ERROR_INVALID_ACTION           = 'e04';
	private const ERROR_INSUFFICIENT_PERMISSIONS = 'e05';
	private const ERROR_MISSING_ARGUMENTS        = 'e06';
	private const ERROR_EMPTY_ARGUMENTS          = 'e07';
	private const ERROR_ENTRY_EXISTS             = 'e08';
	private const ERROR_ENTRY_DONT_EXISTS        = 'e09';
	private const ERROR_INVALID_URL              = 'e10';
	private const ERROR_INVALID_PASSWORD         = 'e11';
	private const ERROR_PASSWORDS_DONT_MATCH     = 'e12';
	private const ERROR_PASSWORD_TOO_SHORT       = 'e13';
	private const ERROR_PASSWORD_TOO_SIMPLE      = 'e14';
	private const ERROR_INVALID_EMAIL            = 'e15';
	private const ERROR_SPECIAL_CHARACTERS       = 'e16';
	private const ERROR_USER_EMAIL_EXISTS        = 'e17';
	private const ERROR_USER_NAME_EXISTS         = 'e18';
	private const ERROR_MYSQL_UNKNOWN            = 'e19';

	private const CODE_SUCCESS                   = 's01';

	/**
	 * Forward class instance
	 *
	 * @var Forward
	 * @access private
	 */
	private $Forward;

	/**
	 * Current ajax action
	 *
	 * @var string
	 * @access private
	 */
	private $action = '';

	/**
	 * Current ajax nonce
	 *
	 * @var string
	 * @access private
	 */
	private $nonce = '';

	/**
	 * __construct
	 * Class constructor
	 *
	 * @access   public
	 */
	public function __construct(Forward &$parent)
	{
		$this->Forward = $parent;

		if ($this->is_null())
			exit('Bad gateway');

		if (!isset($_POST['action']))
			exit(self::ERROR_MISSING_ACTION);
		else
			$this->action = filter_var($_POST['action'], FILTER_SANITIZE_STRING);

		if (!isset($_POST['nonce']))
			exit(self::ERROR_MISSING_NONCE);
		else
			$this->nonce = filter_var($_POST['nonce'], FILTER_SANITIZE_STRING);

		if (!$this->is_valid_nonce())
			exit(self::ERROR_INVALID_NONCE);

		if (!$this->is_valid_action())
			exit(self::ERROR_INVALID_ACTION);
		else {
			$this->Forward->AddStatistic($this->action, 'query');
			$this->{$this->action}();
		}

		$this->print_response();
	}

	/**
	 * is_valid_nonce
	 * Nonce validation
	 *
	 * @access   private
	 * @return	bool
	 */
	private function is_valid_nonce(): bool
	{
		if (isset($_POST['nonce']))
			if (Crypter::Compare('ajax_' . $this->action . '_nonce', $this->nonce, 'nonce'))
				return true;
			else
				return false;
		else
			return false;
	}

	/**
	 * is_valid_action
	 * Action validation
	 *
	 * @access   private
	 * @return	bool
	 */
	private function is_valid_action(): bool
	{
		if (method_exists($this, $this->action))
			return true;
		else
			return false;
	}

	/**
	 * is_null
	 * If $_POST is not empty
	 *
	 * @access   private
	 * @return	bool
	 */
	private function is_null(): bool
	{
		if (!empty($_POST))
			return false;
		else
			return true;
	}

	/**
	 * print_response
	 * End ajax script
	 *
	 * @access   private
	 * @return	bool
	 */
	private function print_response($text = null, $json = false)
	{
		$this->Forward->Session->Close();

		if ($text == null)
			echo self::ERROR_UNKNOWN;
		else
				if ($json)
			echo json_encode($text, JSON_UNESCAPED_UNICODE);
		else
			echo $text;

		exit;
	}


	/**
	 * Ajax methods
	 */

	/**
	 * sign_in
	 * The action is triggered on login
	 *
	 * @access   private
	 * @return	void
	 */
	private function sign_in(): void
	{
		if (!isset($_POST['login'], $_POST['password']))
			$this->print_response(self::ERROR_MISSING_ARGUMENTS);

		if (empty($_POST['login']) || empty($_POST['password']))
			$this->print_response(self::ERROR_ENTRY_DONT_EXISTS);

		$login = filter_var($_POST['login'], FILTER_SANITIZE_STRING);
		$password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);

		$user = $this->Forward->User->GetByName($login);

		if (empty($user))
			$user = $this->Forward->User->GetByEmail($login);

		if (empty($user)) {
			$this->Forward->AddStatistic('login_wrong_username', 'action');
			$this->print_response(self::ERROR_ENTRY_DONT_EXISTS);
		}

		if (!Crypter::Compare($password, $user['user_password'], 'password')) {
			$this->Forward->AddStatistic('login_wrong_password', 'action');
			$this->print_response(self::ERROR_ENTRY_DONT_EXISTS);
		}

		$this->Forward->User->LogIn($user);
		$this->print_response(self::CODE_SUCCESS);
	}

	/**
	 * sign_in
	 * The action is triggered on adding record
	 *
	 * @access   private
	 * @return	void
	 */
	private function add_record(): void
	{
		if (!$this->Forward->User->IsManager())
			$this->print_response(self::ERROR_INSUFFICIENT_PERMISSIONS);

		if (!isset(
			$_POST['input-record-url'],
			$_POST['input-record-slug'],
			$_POST['input-rand-value']
		))
			$this->print_response(self::ERROR_MISSING_ARGUMENTS);

		if (trim($_POST['input-record-url']) == '' || trim($_POST['input-rand-value']) == '')
			$this->print_response(self::ERROR_EMPTY_ARGUMENTS);

		if (!(str_starts_with(trim($_POST['input-record-url']), 'https://') || str_starts_with(trim($_POST['input-record-url']), 'http://')))
			$this->print_response(self::ERROR_INVALID_URL);

		if (trim($_POST['input-record-slug']) != '' && $_POST['input-record-slug'] != $_POST['input-rand-value'])
			$slug = filter_var($_POST['input-record-slug'], FILTER_SANITIZE_STRING);
		else
			$slug = filter_var($_POST['input-rand-value'], FILTER_SANITIZE_STRING);

		$query = $this->Forward->Database->query("SELECT record_id FROM forward_records WHERE record_name = ?", strtolower($slug))->fetchAll();
		if (!empty($query))
			$this->print_response(self::ERROR_ENTRY_EXISTS);

		$query = $this->Forward->Database->query(
			"INSERT INTO forward_records (record_name, record_display_name, record_url) VALUES (?,?,?)",
			strtolower($slug),
			$slug,
			filter_var($_POST['input-record-url'], FILTER_SANITIZE_STRING)
		);

		$this->print_response(self::CODE_SUCCESS);
	}

	/**
	 * remove_record
	 * Remove selected record
	 *
	 * @access   private
	 * @return	void
	 */
	private function remove_record()
	{
		if (!$this->Forward->User->IsManager())
			$this->print_response(self::ERROR_INSUFFICIENT_PERMISSIONS);

		if (!isset($_POST['input_record_id']))
			$this->print_response(self::ERROR_MISSING_ARGUMENTS);

		if (trim($_POST['input_record_id']) == '')
			$this->print_response(self::ERROR_EMPTY_ARGUMENTS);

		$query = $this->Forward->Database->query(
			"SELECT record_name FROM forward_records WHERE record_id = ?",
			filter_var($_POST['input_record_id'], FILTER_VALIDATE_INT)
		)->fetchArray();

		if (empty($query))
			$this->print_response(self::ERROR_ENTRY_DONT_EXISTS);

		$query = $this->Forward->Database->query(
			"UPDATE forward_records SET record_active = false, record_name = ? WHERE record_id = ?",
			'archieved_' . time() . '_' . $query['record_name'],
			filter_var($_POST['input_record_id'], FILTER_VALIDATE_INT)
		);

		$this->print_response(self::CODE_SUCCESS);
	}

	/**
	 * get_record_data
	 * A list of record information
	 *
	 * @access   private
	 * @return	void
	 */
	private function get_record_data(): void
	{
		if (!isset($_POST['input_record_id']))
			$this->print_response(self::ERROR_MISSING_ARGUMENTS);

		if (trim($_POST['input_record_id']) == '')
			$this->print_response(self::ERROR_EMPTY_ARGUMENTS);

		$query = $this->Forward->Database->query("SELECT * FROM forward_records WHERE record_id = ?", filter_var($_POST['input_record_id'], FILTER_VALIDATE_INT))->fetchAll();
		if (empty($query))
			$this->print_response(self::ERROR_ENTRY_DONT_EXISTS);

		$data = $query[0];
		$data['status'] = 'success';

		$data['visitors'] = array(
			'languages' => array(),
			'agents' => array(),
			'origins' => array(),
			'platforms' => array(),
			'days' => array(),
			'ip' => array()
		);

		$recent_days = array();
		for ($i = 0; $i < 30; $i++) {
			$timestamp = time();
			$tm = 86400 * $i; // 60 * 60 * 24 = 86400 = 1 day in seconds
			$tm = $timestamp - $tm;
			$recent_days[date('d-m-Y', $tm)] = 0;
		}
		$recent_days = array_reverse($recent_days);

		$query = $this->Forward->Database->query("SELECT * FROM forward_statistics_visitors WHERE record_id = ?", filter_var($_POST['input_record_id'], FILTER_VALIDATE_INT))->fetchAll();
		if (!empty($query)) {
			foreach ($query as $visitor) {

				$record_date = date('d-m-Y', strtotime($visitor['visitor_date']));
				if (array_key_exists($record_date, $recent_days)) {
					$recent_days[$record_date]++;
				}

				if (isset($data['visitors']['ip'][$visitor['visitor_ip']]))
					$data['visitors']['ip'][$visitor['visitor_ip']]++;
				else
					$data['visitors']['ip'][$visitor['visitor_ip']] = 1;

				if (isset($data['visitors']['agents'][$visitor['visitor_agent_id']]))
					$data['visitors']['agents'][$visitor['visitor_agent_id']]++;
				else
					$data['visitors']['agents'][$visitor['visitor_agent_id']] = 1;

				if (isset($data['visitors']['platforms'][$visitor['visitor_platform_id']]))
					$data['visitors']['platforms'][$visitor['visitor_platform_id']]++;
				else
					$data['visitors']['platforms'][$visitor['visitor_platform_id']] = 1;

				if (isset($data['visitors']['languages'][$visitor['visitor_language_id']]))
					$data['visitors']['languages'][$visitor['visitor_language_id']]++;
				else
					$data['visitors']['languages'][$visitor['visitor_language_id']] = 1;

				if (isset($data['visitors']['origins'][$visitor['visitor_origin_id']]))
					$data['visitors']['origins'][$visitor['visitor_origin_id']]++;
				else
					$data['visitors']['origins'][$visitor['visitor_origin_id']] = 1;
			}
		}

		$data['visitors']['days'] = $recent_days;

		$this->print_response($data, true);
	}

	/**
	 * save_settings
	 * Update settings in database
	 *
	 * @access   private
	 * @return	void
	 */
	private function save_settings(): void
	{
		if (!$this->Forward->User->IsAdmin())
			$this->print_response(self::ERROR_INSUFFICIENT_PERMISSIONS);

		if (!isset(
			$_POST['input_base_url'],
			$_POST['input_dashboard_url'],
			$_POST['input_login_url'],
			$_POST['input_redirect_404'],
			$_POST['input_redirect_404_direction'],
			$_POST['input_redirect_home'],
			$_POST['input_redirect_home_direction'],
			$_POST['input_cache'],
			$_POST['input_dashboard_captcha_public'],
			$_POST['input_dashboard_captcha_secret'],
			$_POST['input_force_redirect_ssl'],
			$_POST['input_force_dashboard_ssl'],
			$_POST['input_js_redirect'],
			$_POST['input_js_redirect_after'],
			$_POST['input_google_analytics'],
			$_POST['input_language_type'],
			$_POST['input_language_select']
		))
			$this->print_response(self::ERROR_MISSING_ARGUMENTS);

		if (
			trim($_POST['input_base_url']) == '' ||
			trim($_POST['input_dashboard_url']) == '' ||
			trim($_POST['input_login_url']) == ''
		)
			$this->print_response(self::ERROR_EMPTY_ARGUMENTS);

		//Update all
		$this->Forward->Options->Update('base_url', filter_var($_POST['input_base_url'], FILTER_SANITIZE_STRING));
		$this->Forward->Options->Update('dashboard', filter_var($_POST['input_dashboard_url'], FILTER_SANITIZE_STRING));
		$this->Forward->Options->Update('login', filter_var($_POST['input_login_url'], FILTER_SANITIZE_STRING));

		$this->Forward->Options->Update('redirect_404', $_POST['input_redirect_404'] == "1");
		$this->Forward->Options->Update('redirect_404_direction', filter_var($_POST['input_redirect_404_direction'], FILTER_SANITIZE_STRING));
		$this->Forward->Options->Update('redirect_home', $_POST['input_redirect_home'] == "1");
		$this->Forward->Options->Update('redirect_home_direction', filter_var($_POST['input_redirect_home_direction'], FILTER_SANITIZE_STRING));

		$this->Forward->Options->Update('cache', $_POST['input_cache'] == "1");

		$this->Forward->Options->Update('dashboard_captcha_public', filter_var($_POST['input_dashboard_captcha_public'], FILTER_SANITIZE_STRING));
		$this->Forward->Options->Update('dashboard_captcha_secret', filter_var($_POST['input_dashboard_captcha_secret'], FILTER_SANITIZE_STRING));

		$this->Forward->Options->Update('force_redirect_ssl', $_POST['input_force_redirect_ssl'] == "1");
		$this->Forward->Options->Update('force_dashboard_ssl', $_POST['input_force_dashboard_ssl'] == "1");

		$this->Forward->Options->Update('js_redirect', $_POST['input_js_redirect'] === "1");

		$this->Forward->Options->Update('js_redirect_after', filter_var(intval($_POST['input_js_redirect_after']), FILTER_VALIDATE_INT));

		$this->Forward->Options->Update('google_analytics', filter_var($_POST['input_google_analytics'], FILTER_SANITIZE_STRING));

		$this->Forward->Options->Update('dashboard_language_mode', filter_var(intval($_POST['input_language_type']), FILTER_VALIDATE_INT));

		$this->Forward->Options->Update('dashboard_language', filter_var($_POST['input_language_select'], FILTER_SANITIZE_STRING));

		$this->print_response(self::CODE_SUCCESS);
	}

	/**
	 * add_user
	 * Adds new user to the database
	 *
	 * @access   private
	 * @return	void
	 */
	private function add_user(): void
	{
		if (!$this->Forward->User->IsAdmin())
			$this->print_response(self::ERROR_INSUFFICIENT_PERMISSIONS);

		if (!isset(
			$_POST['input_user_username'],
			$_POST['input_user_display_name'],
			$_POST['input_user_email'],
			$_POST['input_user_password'],
			$_POST['input_user_password_confirm']
		))
			$this->print_response(self::ERROR_MISSING_ARGUMENTS);

		if (
			trim($_POST['input_user_username']) == '' ||
			trim($_POST['input_user_password']) == '' ||
			trim($_POST['input_user_password_confirm']) == ''
		)
			$this->print_response(self::ERROR_EMPTY_ARGUMENTS);

		$username = filter_var($_POST['input_user_username'], FILTER_SANITIZE_STRING);
		$displayname = filter_var($_POST['input_user_display_name'], FILTER_SANITIZE_STRING);
		$email = filter_var($_POST['input_user_email'], FILTER_SANITIZE_STRING);
		$passwordBlank = filter_var($_POST['input_user_password'], FILTER_SANITIZE_STRING);

		$userRole = 'analyst';
		switch ($_POST['input_user_type']) {
			case '2':
				$userRole = 'manager';
				break;
			case '3':
				$userRole = 'admin';
				break;
		}

		if (!empty($email))
			if (!filter_var($email, FILTER_VALIDATE_EMAIL))
				$this->print_response(self::ERROR_INVALID_EMAIL);

		if (preg_match('/[^A-Za-z0-9_-]+/', $username))
			$this->print_response(self::ERROR_SPECIAL_CHARACTERS);

		if (preg_match('/[^A-Za-z0-9 _-]+/', $displayname))
			$this->print_response(self::ERROR_SPECIAL_CHARACTERS);

		if (strlen(trim($_POST['input_user_password'])) < 6)
			$this->print_response(self::ERROR_PASSWORD_TOO_SHORT);

		if (trim($_POST['input_user_password']) != trim($_POST['input_user_password_confirm']))
			$this->print_response(self::ERROR_PASSWORDS_DONT_MATCH);

		$user = $this->Forward->User->GetByName($username);
		if (!empty($user))
			$this->print_response(self::ERROR_USER_NAME_EXISTS);

		$user = $this->Forward->User->GetByEmail($username);
		if (!empty($user))
			$this->print_response(self::ERROR_USER_EMAIL_EXISTS);

		$query = $this->Forward->Database->query(
			"INSERT INTO forward_users (user_name, user_display_name, user_password, user_email, user_token, user_role, user_status) VALUES (?, ?, ?, ?, ?, ?, 1)",
			$username,
			$displayname,
			Crypter::Encrypt($passwordBlank, 'password'),
			$email,
			Crypter::Encrypt(Crypter::DeepSalter(32), 'token'),
			$userRole
		);

		if (empty($query) || $query->affectedRows() < 1)
			$this->print_response(self::ERROR_MYSQL_UNKNOWN);

		$this->print_response(self::CODE_SUCCESS);
	}

	/**
	 * add_user
	 * Adds new user to the database
	 *
	 * @access   private
	 * @return	void
	 */
	private function update_user(): void
	{
		if (!($this->Forward->User->IsAdmin() || $_POST['id'] == $this->Forward->User->Active()['user_id']))
			$this->print_response(self::ERROR_INSUFFICIENT_PERMISSIONS);

		if (!isset(
			$_POST['id'],
			$_POST['input_user_username'],
			$_POST['input_user_display_name'],
			$_POST['input_user_email']
		))
			$this->print_response(self::ERROR_MISSING_ARGUMENTS);

		if (
			trim($_POST['input_user_username']) == '' ||
			trim($_POST['input_user_display_name']) == ''
		)
			$this->print_response(self::ERROR_EMPTY_ARGUMENTS);

		$userid = filter_var($_POST['id'], FILTER_SANITIZE_STRING);
		$username = filter_var($_POST['input_user_username'], FILTER_SANITIZE_STRING);
		$displayname = filter_var($_POST['input_user_display_name'], FILTER_SANITIZE_STRING);
		$email = filter_var($_POST['input_user_email'], FILTER_SANITIZE_STRING);

		if (preg_match('/[^A-Za-z0-9_-]+/', $username))
			$this->print_response(self::ERROR_SPECIAL_CHARACTERS);

		if (preg_match('/[^A-Za-z0-9 _-]+/', $displayname))
			$this->print_response(self::ERROR_SPECIAL_CHARACTERS);

		$user = $this->Forward->User->GetById($userid);
		if (empty($user))
			$this->print_response(self::ERROR_ENTRY_DONT_EXISTS);

		$query = $this->Forward->Database->query(
			"UPDATE forward_users SET user_name = ?, user_display_name = ?, user_email = ? WHERE user_id = ?",
			$username,
			$displayname,
			$email,
			$userid
		);

		if (empty($query) || $query->affectedRows() < 1)
			$this->print_response(self::ERROR_MYSQL_UNKNOWN);

		$this->print_response(self::CODE_SUCCESS);
	}

	/**
	 * add_user
	 * Adds new user to the database
	 *
	 * @access   private
	 * @return	void
	 */
	private function change_password(): void
	{
		if (!($this->Forward->User->IsAdmin() || $_POST['id'] == $this->Forward->User->Active()['user_id']))
			$this->print_response(self::ERROR_INSUFFICIENT_PERMISSIONS);

		if (!isset(
			$_POST['id'],
			$_POST['username'],
			$_POST['input_user_new_password'],
			$_POST['input_user_new_password_confirm']
		))
			$this->print_response(self::ERROR_MISSING_ARGUMENTS);

		if (
			trim($_POST['id']) == '' ||
			trim($_POST['input_user_new_password']) == '' ||
			trim($_POST['input_user_new_password_confirm']) == ''
		)
			$this->print_response(self::ERROR_EMPTY_ARGUMENTS);

		if (strlen(trim($_POST['input_user_new_password'])) < 6)
			$this->print_response(self::ERROR_PASSWORD_TOO_SHORT);

		if (trim($_POST['input_user_new_password']) != trim($_POST['input_user_new_password_confirm']))
			$this->print_response(self::ERROR_PASSWORDS_DONT_MATCH);

		$userid = filter_var($_POST['id'], FILTER_SANITIZE_STRING);

		$user = $this->Forward->User->GetById($userid);
		if (empty($user))
			$this->print_response(self::ERROR_ENTRY_DONT_EXISTS);

		$query = $this->Forward->Database->query(
			"UPDATE forward_users SET user_password = ? WHERE user_id = ?",
			Crypter::Encrypt($_POST['input_user_new_password'], 'password'),
			$userid
		);

		if (empty($query) || $query->affectedRows() < 1)
			$this->print_response(self::ERROR_MYSQL_UNKNOWN);

		$this->print_response(self::CODE_SUCCESS);
	}
}
