<?php
/**
 * @package Forward
 *
 * @author RapidDev
 * @copyright Copyright (c) 2019, RapidDev
 * @link https://www.rdev.cc/forward
 * @license https://opensource.org/licenses/MIT
 */
	namespace Forward;
	defined('ABSPATH') or die('No script kiddies please!');
	
	/** Database path */
	define('DB_PATH', ADMPATH.'db/');

	/** The name of the database for users */
	define('DB_USERS', 'users_database');

	/** The name of the database for options */
	define('DB_OPTIONS', 'options_database');

	/** The name of the database for records */
	define('DB_RECORDS', 'records_database');

	/** Salt for passwords */
	define('RED_SALT', 'example_salt');

	/** Salt for passwords */
	define('RED_SESSION', 'example_session_salt');

	/** Salt for nonce */
	define('RED_NONCE', 'example_nonce_salt');

	/** WebName */
	define('RED_NAME', 'Forward');

	/** Debug */
	define('RED_VERSION', 'beta 1.0.0');


	/** Debug */
	define('RED_DEBUG', false);


	/** Parse URL */
	foreach (array(
		'home' => "/",
		'page' => "/(?'page'[\w\-]+)",
		'get' => "/(?'get'[\w\-]+)?(.*?)",
		'dashboard' => "/dashboard/(?'dashboard'[\w\-]+)"
	) as $action => $rule )
	{
		if (preg_match( '~^'.$rule.'$~i', urldecode('/'.trim(str_replace(rtrim(dirname($_SERVER["SCRIPT_NAME"]),'/'),'',$_SERVER['REQUEST_URI']),'/')), $params ))
		{

			if (isset($params[0]))
			{
				if($params[0] == '/'){
					defined('RED_PAGE') or define('RED_PAGE', 'home');
				}
				else if (isset($params['dashboard']))
				{
					defined('RED_DASHBOARD') or define('RED_DASHBOARD', $params['dashboard']);
				}
				else if(isset($params['get']))
				{
					if($params['get'] != 'dashboard')
						defined('RED_PAGE') or define('RED_PAGE', $params['get']);
				}
				else if(isset($params['page']))
				{
					defined('RED_PAGE') or define('RED_PAGE', $params['page']);
				}
			}
		}
	}

	if(!defined('RED_PAGE'))
		if(defined('RED_DASHBOARD'))
			define('RED_PAGE', 'dashboard');
		else
			define('RED_PAGE', '404');
?>