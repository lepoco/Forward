<?php defined('ABSPATH') or die('No script kiddies please!');
/**
 * @package Forward
 *
 * @author RapidDev
 * @copyright Copyright (c) 2019, RapidDev
 * @link https://www.rdev.cc/forward
 * @license https://opensource.org/licenses/MIT
 */

	namespace Forward;

	class RED
	{
		private $page;
		private $uri;

		private $DB;

		public function __construct()
		{
			$this->DB = array(
				'options' => new \Filebase\Database(['dir' => DB_PATH.DB_OPTIONS]),
				'records' => new \Filebase\Database(['dir' => DB_PATH.DB_RECORDS])
			);

			switch (RED_PAGE) {
				case 'dashboard':
					$this->DB['users'] = new \Filebase\Database(['dir' => DB_PATH.DB_USERS]);
					self::admin();
					break;
				case 'home':
					$this->page(['title' => 'Home page', 'page' => 'home']);
					break;
				case '404':
					$this->page(['title' => 'Page not found']);
					break;
				default:
					self::forward();
					break;
			}
		}

		public function page($data)
		{
			return new RED_PAGES($data, $this->DB);
		}

		private function admin()
		{
			if (is_file(ADMPATH.'red-admin.php'))
				require_once(ADMPATH.'red-admin.php');
			else
				$this->page(['title' => 'Page not found']);
		}

		private function ajax()
		{
			if (isset($_POST['action']))
			{
				if($_POST['action'] == 'addUser')
				{

					if($_POST['userPassword'] != $_POST['userPasswordConfirm'])
						exit('error_3');

					if($_POST['userName'] == '' || $_POST['userPassword'] == '')
						exit('error_4');

					$user = $this->DB['users']->get($_POST['userName']);

					if($user->password == NULL)
					{
						$user->email = $_POST['userEmail'];
						$user->password = self::encrypt($_POST['userPassword']);
						$user->save();
						exit('success');
					}else{
						exit('error_4');
					}
				}else if($_POST['action'] == 'addRecord')
				{
					if($_POST['forward-url'] == '' || $_POST['forward-slug'] == '')
						exit('error_4');

					$record = $this->DB['records']->get($_POST['forward-slug']);

					if($record->url == NULL)
					{
						$record->url = $_POST['forward-url'];
						$record->clicks = 0;
						$record->save();
					}else{
						exit('error_5');
					}
					var_dump($_POST);
					exit('success');
				}
				exit;
			}else{
				exit(header("Location: " . $this->DB['options']->get('siteurl')->value));
			}
		}

		private function signout()
		{
			exit('signed out');
		}

		private function forward()
		{
			$record = $this->DB['records']->get(RED_PAGE);

			if($record->url == NULL)
				$this->page(['title' => 'Page not found']);

			$record->clicks = $record->clicks + 1;
			$record->save();

			//Redirect
			header("Location: " . $record->url);
			exit;	
		}

		private function parse_url()
		{
			$URI = explode("/", $_SERVER['REQUEST_URI']);
			$this->page = $URI[2];
		}

		public static function encrypt($string, $type = 'password')
		{
			if($type == 'password')
			{
				return password_hash(hash_hmac('sha256', $string, RED_SALT), PASSWORD_ARGON2ID);
			}else if($type == 'nonce')
			{
				return hash_hmac('sha1', $string, RED_NONCE);
			}
		}

		public static function rand($length)
		{
			$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$randomString = '';
			for ($i = 0; $i < $length; $i++) {$randomString .= $characters[rand(0, 35)];}
			return $randomString;
		}

		public static function compare_crypt($input_string, $db_string, $type = 'password', $plain = true)
		{

			if($type == 'password')
			{
				if (password_verify(($plain ? hash_hmac('sha256', $input_string, RED_SALT) : $input_string), $db_string))
				{
					return TRUE;
				}else{
					return FALSE;
				}
			}
		}

		private function include($path)
		{
			if (is_file($path))
				return require_once(ADMPATH.'red-admin.php');
			else
				exit(RED_DEBUG ? 'The '.$path.' file was not found!' : '');
		}

		private function error($id, $title)
		{

		}
	}
?>
