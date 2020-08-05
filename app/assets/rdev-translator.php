<?php
/**
 * @package Forward
 *
 * @author RapidDev
 * @copyright Copyright (c) 2019-2020, RapidDev
 * @link https://www.rdev.cc/forward
 * @license https://opensource.org/licenses/MIT
 */
	namespace Forward;
	defined('ABSPATH') or die('No script kiddies please!');

	/**
	*
	* Translator
	*
	* @author   Leszek Pomianowski <https://rdev.cc>
	* @license	MIT License
	* @access   public
	*/
	class Translator
	{
		public $locale;
		public $domain;

		private $strings_array = array();

		public function SetLocale($locale, $domain = 'forward') : void
		{
			$this->locale = $locale;
			$this->domain = $domain;
		}

		public function Init() : void
		{
			if( $this->locale == NULL )
				$this->ParseLanguage();

			if( file_exists( APPPATH . '/languages/' . $this->locale.'.json' ) )
				if( self::IsValid( APPPATH . '/languages/' . $this->locale.'.json' ) )
					$this->strings_array = json_decode( file_get_contents( APPPATH . '/languages/' . $this->locale.'.json'), true );
		}

		private static function IsValid($file) : bool
		{
			return true;
		}

		private function ParseLanguage() : void
		{
			$this->locale = "pl_PL";
		}

		public function __($text)
		{
			if( array_key_exists( $text, $this->strings_array ) )
				return $this->strings_array[$text];
			else
				return $text;
		}

		public function _e($text)
		{
			echo $this->__($text);
		}
	}
?>