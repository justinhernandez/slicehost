<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Main Slicehost API class. API Version: 1.4.1.1
 * Uses static methods to emulate classes extending Active Resource avoiding
 * possible namespace coflicts.
 *
 * @author Justin Hernandez <justin@transphorm.com>
 * @version 0.1
 * @license http://opensource.org/licenses/lgpl-2.1.php
 */
abstract class Slicehost_Core extends ActiveResource
{
	/**
	 * Creates and returns backup object
	 *
	 * @param   array   $data
	 * @param   string  $api_key
	 * @return  object
	 */
	public static function backup($data = array(), $api_key = FALSE)
	{
		return self::factory($data, $api_key, 'backups');
	}

	/**
	 * Creates and returns flavor object
	 *
	 * @param   array   $data
	 * @param   string  $api_key
	 * @return  object
	 */
	public static function flavor($data = array(), $api_key = FALSE)
	{
		return self::factory($data, $api_key, 'flavors');
	}

	/**
	 * Creates and returns image object
	 *
	 * @param   array   $data
	 * @param   string  $api_key
	 * @return  object
	 */
	public static function image($data = array(), $api_key = FALSE)
	{
		return self::factory($data, $api_key, 'images');
	}

	/**
	 * Creates and returns record object
	 *
	 * @param   array   $data
	 * @param   string  $api_key
	 * @return  object
	 */
	public static function record($data = array(), $api_key = FALSE)
	{
		return self::factory($data, $api_key, 'records');
	}

	/**
	 * Creates and returns slice object
	 *
	 * @param   array   $data
	 * @param   string  $api_key
	 * @return  object
	 */
	public static function slice($data = array(), $api_key = FALSE)
	{
		return self::factory($data, $api_key, 'slices');
	}

	/**
	 * Creates and returns zone object
	 *
	 * @param   array   $data
	 * @param   string  $api_key
	 * @return  object
	 */
	public static function zone($data = array(), $api_key = FALSE)
	{
		return self::factory($data, $api_key, 'zones');
	}

	/**
	 * Retrieve slicehost api information
	 *
	 * @param   string  $api_key
	 * @return  object
	 */
	public static function api($api_key = FALSE)
	{
		$obj = self::factory(array(), $api_key, NULL);
		$obj->get('api');

		// filter returned data
		return $obj->_data;
	}

	/**
	 * Filters response data array and returns information contained within
	 * the index _data
	 *
	 * @param   mixed  $input
	 * @return  array
	 */
	public static function filter_data($input)
	{
		// if input is not an array then exit
		if (!is_array($input))
			return $input;

		$it = new ArrayIterator($input);
		$data = array();

		while ($it->valid())
		{
			$data[] = $input[$it->key()]->_data;

			$it->next();
		}

		return $data;
	}

	/**
	 * Private, constructs and returns new Slicehost object.
	 *
	 * @param   array   $data
	 * @param   string  $element_name
	 * @return  object
	 */
	private static function factory($data, $api_key, $element_name)
	{
		$obj = new Slicehost($data);
		$obj->site = self::site_url($api_key);
		$obj->element_name = $element_name;

		return $obj;
	}

	/**
	 * Checks if api_key has been passed by reference. If not, then checks if
	 * api key has been set in config. Raises error if api key can not be found.
	 * Returns slicehost api url.
	 *
	 * @param   string  $api_key
	 * @return  string
	 */
	private static function site_url($api_key)
	{
		// if api key is not defined then pull it from the config
		if ( ! $api_key) $api_key = Kohana::config('slicehost.api_key');

		// throw an exception if api key is false
		if ( ! $api_key)
			throw new Kohana_Exception('Slicehost api key is not valid!');

		return "https://$api_key@api.slicehost.com/";
	}
}