<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Active Resource library for the Kohana Framework
 *
 * Ported from code written by John Luxford
 * http://code.google.com/p/phpactiveresource/
 *
 * @author John Luxford <lux@dojolearning.com>
 * @author Justin Hernandez <justin@transphorm.com>
 * @version 0.1
 * @license http://opensource.org/licenses/lgpl-2.1.php
 */
class ActiveResource
{

	// The REST site address, e.g., http://user:pass@domain:port/
	public $site = FALSE;
	// The remote collection, e.g., person or things
	public $element_name = FALSE;
	// The data of the current object, accessed via the anonymous get/set methods.
	public $_data = array();
	//An error message if an error occurred.
	public $error = FALSE;
	//The error number if an error occurred.
	public $errno = FALSE;
	//The request that was sent to the server.
	public $request_body = '';
	//The complete URL that the request was sent to.
	public $request_uri = '';
	// The request method sent to the server.
	public $request_method = '';
	//The response code returned from the server.
	public $response_code = FALSE;
	//The raw response headers sent from the server.
	public $response_headers = '';
	//The response body sent from the server.
	public $response_body = '';

	/**
	 * Constructor method.
	 *
	 * @param  array $data
	 */
	public function __construct ($data = array ())
	{
		$this->_data = $data;
		$this->element_name = strtolower(get_class ($this)).'s';
	}

	/**
	 * Saves a new record or updates an existing one via:
	 *
	 * POST /collection.xml
	 * PUT  /collection/id.xml
	 */
	public function save()
	{
		if (isset($this->_data['id']))
			// update
			return $this->_send_and_receive($this->site.$this->element_name.'/'.$this->_data['id'].'.xml', 'PUT', $this->_data);
		// create
		return $this->_send_and_receive($this->site.$this->element_name.'.xml', 'POST', $this->_data);
	}

	/**
	 * Deletes a record via:
	 *
	 * DELETE /collection/id.xml
	 */
	public function destroy()
	{
		return $this->_send_and_receive($this->site.$this->element_name.'/'.$this->_data['id'].'.xml', 'DELETE');
	}

	/**
	 * Finds a record or records via:
	 *
	 * GET /collection/id.xml
	 * GET /collection.xml
	 */
	public function find($id = 'all')
	{
		if ( !$id) $id = $this->_data['id'];
		if ($id == 'all')
			return $this->_send_and_receive($this->site.$this->element_name.'.xml', 'GET');

		return $this->_send_and_receive($this->site.$this->element_name.'/'.$id.'.xml', 'GET');
	}

	/**
	 * Gets a specified custom method on the current object via:
	 *
	 * GET /collection/id/method.xml
	 * GET /collection/id/method.xml?attr=value
	 */
	public function get($method, $options = array ())
	{
		$req = $this->site.$this->element_name;
        if (@$this->_data['id']) $req .= '/'.$this->_data['id'];
        $req .= '/'.$method.'.xml';
		if (count($options) > 0) $req .= '?'.http_build_query($options);
		
		return $this->_send_and_receive($req, 'GET');
	}

	/**
	 * Posts to a specified custom method on the current object via:
	 *
	 * POST /collection/id/method.xml
	 */
	public function post($method, $options = array ())
	{
		$req = $this->site . $this->element_name;
        if ($this->_data['id']) $req .= '/'.$this->_data['id'];
        $req .= '/'.$method.'.xml';

		return $this->_send_and_receive($req, 'POST', $options);
	}

	/**
	 * Puts to a specified custom method on the current object via:
	 *
	 * PUT /collection/id/method.xml
	 */
	public function put($method, $options = array ())
	{
		$req = $this->site.$this->element_name;
        if ($this->_data['id']) $req .= '/'.$this->_data['id'];
        $req .= '/'.$method.'.xml';
		if (count($options) > 0) $req .= '?' . http_build_query ($options);

		return $this->_send_and_receive($req, 'PUT');
	}

	/**
	 * Build the request, call _fetch() and parse the results.
	 */
	private function _send_and_receive($url, $method, $data = array ())
	{
		$params = '';
		$el = substr($this->element_name, 0, -1);
		foreach ($data as $k => $v)
		{
			if (($k != 'id') && ($k != 'created-at') && ($k != 'updated-at'))
				$params .= '&'.$el.'['.str_replace('-', '_', $k).']='.rawurlencode($v);
		}
		$params = substr($params, 1);
		$this->request_body = $params;
		$this->request_uri = $url;
		$this->request_method = $method;

		$res = $this->_fetch($url, $method, $params);

		list($headers, $res) = explode("\r\n\r\n", $res, 2);
		$this->response_headers = $headers;
		$this->response_body = $res;
		$this->response_code = (preg_match('/HTTP\/[0-9]\.[0-9] ([0-9]+)/', $headers, $regs))
							 ? $regs[1]
							 : FALSE;

		if ( !$res)
		{
			return $this;
		} 
		elseif ($res == ' ')
		{
			$this->error = 'Empty reply';
			
			return $this;
		}

		// parse XML response
		$xml = new SimpleXMLElement($res);

		if ($xml->getName() == $this->element_name)
		{
			// multiple
			$res = array();
			$cls = get_class($this);
			foreach($xml->children() as $child)
			{
				$obj = new $cls;
				foreach((array) $child as $k => $v)
				{
					$k = str_replace ('-', '_', $k);
					if ((isset($v['nil'])) && ($v['nil'] == 'true'))
					{
						continue;
					} 
					else
					{
						$obj->_data[$k] = $v;
					}
				}
				$res[] = $obj;
			}

			return $res;
		} 
		elseif ($xml->getName()  == 'errors')
		{
			// parse error message
			$this->error = $xml->error;
			$this->errno = $this->response_code;

			return FALSE;
		}

		foreach ((array) $xml as $k => $v)
		{
			$k = str_replace ('-', '_', $k);
			if ((isset($v['nil'])) && ($v['nil'] == 'true'))
			{
				continue;
			} 
			else
			{
				$this->_data[$k] = $v;
			}
		}

		return $this;
	}

	/**
	 * Fetch the specified request via cURL.
	 */
	private function _fetch($url, $method, $params)
	{
		if ( !extension_loaded('curl'))
		{
			$this->error = 'cURL extension not loaded.';

			return FALSE;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

		switch ($method)
		{
			case 'POST':
				curl_setopt ($ch, CURLOPT_POST, 1);
				curl_setopt ($ch, CURLOPT_POSTFIELDS, $params);
				//curl_setopt ($ch, CURLOPT_HTTPHEADER, array ("Content-Type: application/x-www-form-urlencoded\n"));
			break;
			case 'DELETE':
				curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
			break;
			case 'PUT':
				curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt ($ch, CURLOPT_POSTFIELDS, $params);
				//curl_setopt ($ch, CURLOPT_HTTPHEADER, array ("Content-Type: application/x-www-form-urlencoded\n"));
			break;
			case 'GET':
			default:
			break;
		}

		$res = curl_exec ($ch);
		if ( !$res)
		{
			$this->errno = curl_errno ($ch);
			$this->error = curl_error ($ch);
			curl_close ($ch);

			return FALSE;
		}

		curl_close ($ch);

		return $res;
	}

	/**
	 * Getter for internal object data.
	 */
	public function __get($k)
	{
		if (isset($this->_data[$k]))
		{
			return $this->_data[$k];
		}

		return $this->{$k};
	}

	/**
	 * Setter for internal object data.
	 */
	public function __set($k, $v)
	{
		if (isset($this->_data[$k]))
		{
			$this->_data[$k] = $v;

			return;
		}

		$this->{$k} = $v;
	}

	/**
	 * Quick setter for chaining methods.
	 */
	function set($k, $v = FALSE)
	{
		if ( ! $v && is_array ($k))
		{
			foreach ($k as $key => $value)
			{
				$this->_data[$key] = $value;
			}
		} 
		else
		{
			$this->_data[$k] = $v;
		}
		
		return $this;
	}
}