<?php
if (!isset($GLOBALS['vbulletin']->db))
{
	exit;
}

/**
* SSGTI - Stock Trader - Yahoo Finance - Class
*
* @author	SolidSnake@GTI
* @package	SSGTI - Stock Trader
* @version	$Revision: 11111 $
* @date		$Date: 2009-09-09 09:09:09 +0200 $
*/
class SSGTI_StockTrader_YF
{
	/**
	* Registry object
	*
	* @var	vB_Registry
	*/
	var $registry = null;

	/**
	* Array to store any errors encountered while building data
	*
	* @var	array
	*/
	var $errors = array();

	/**
	* The error handler for this object
	*
	* @var	string
	*/
	var $error_handler = ERRTYPE_STANDARD;

	/**
	* Callback to execute just before an error is logged.
	*
	* @var	callback
	*/
	var $failure_callback = null;


	/**
	* Constructor, sets up the object.
	*
	* @param	vB_Registry
	*/
	function SSGTI_StockTrader_YF(&$registry, $errtype = ERRTYPE_SILENT)
	{
		if (is_object($registry))
		{
			$this->registry =& $registry;
		}
		else
		{
			trigger_error("vB_Database::Registry object is not an object", E_USER_ERROR);
		}

		$this->set_error_handler($errtype);

		// Initiate Required Data
		if (!function_exists('ssgti_stocktrader_stags'))
		{
			require_once(DIR . '/includes/functions_ssgti_stocktrader.php');
		}
	}


	function retrieve($stocks = NULL, $local = NULL, $cache = TRUE)
	{
		if (is_array($stocks) AND count($stocks) > 0)
		{
			$stags = ssgti_stocktrader_stags(TRUE);
			$local = ((isset($local) AND $local != '') ? $local : NULL);
			$locals = array('ar', 'au', 'br', 'ca', 'cn', 'chinese', 'fr', 'cf', 'de', 'hk', 'in', 'it', 'jp', 'kr', 'mx', 'sg', 'es', 'espanol', 'tw', 'uk');

			if (!is_array($stags) OR count($stags) <= 0)
			{
				$this->error('ssgti_stocktrader_invalid_stags');
			}


			switch ($local)
			{
				case 'test':
					// Localized Link;
					break;
				default:
					$link = 'http://download.finance.yahoo.com/d/quotes.csv?s=' . implode('+', $stocks) . '&f=' . implode('', $stags) . '&e=.csv';
					break;
			}


			$handle = explode("\n", trim($this->vurl($link)));


			if ($handle === FALSE)
			{
				$this->error('ssgti_stocktrader_invalid_resource');
			}
			else
			{
				$readydata = array();
				foreach ($handle AS $row)
				{
					$data = @array_combine($stags, explode(',', $row));

					if (is_array($data) AND count($data) > 0)
					{
						// This isn't a valid stock, it doesn't has any data in Yahoo Finance, so ignore!
						if ($data['a2'] == 0 AND $data['l1'] == 0 AND $data['c1'] == 'N/A' AND $data['p'] == 'N/A' AND $data['v'] == 'N/A')
						{
							continue;
						}
						else
						{
							foreach ($data AS $key => $value)
							{
								$readydata[trim(str_replace('"', '', $data['s']))][trim(str_replace('"', '', $key))] = trim(str_replace('"', '', $value));
							}
						}
					}

					unset($data);
				}


				if (count($readydata) > 0)
				{
					sort($readydata);
					if ($cache)
					{
						$this->cache($readydata);
					}

					return $readydata;
				}
				else
				{
					$this->error('ssgti_stocktrader_retrieve_invalid_stocks');
				}
			}
		}
		else
		{
			$this->error('ssgti_stocktrader_retrieve_invalid_stocks');
		}
	}


	/**
	* Cache data from returned from external URL
	*
	* @param	array	Data to be cached
	*
	* @return	array
	*/
	function cache($data)
	{
		if (is_array($data) AND count($data) > 0)
		{
			$sql_query = '';
			foreach ($data AS $row)
			{
				$sql_query .= "('" . $row['s'] . "', " . TIMENOW . ", " . TIMENOW . ", '" . serialize($row) . "'), ";
			}
			$sql_query = substr(trim($sql_query), 0, -1);


			$this->registry->db->query_write("
				INSERT INTO " . TABLE_PREFIX . "ssgti_stocktrader_cache
					(stockid, dateline, lastupdate, stags)
				VALUES
					$sql_query
				ON DUPLICATE KEY UPDATE
					lastupdate=VALUES(lastupdate), stags=VALUES(stags)
			");

			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}


	/**
	* Get Data: If it's cached, get it from cache, if not then cache it first then get the result.
	*
	* @param	array	Stocks to get it's data
	*
	* @return	array	Requested Data ...
	*/
	function getdata($stocks = NULL)
	{
		if (is_array($stocks) AND count($stocks) > 0)
		{
			$data = $retrievethose = array();
			$where_clause = "WHERE stockid IN('" . implode('\', \'', $stocks) . "')";
			$stockscount = count($stocks);
			$stags = ssgti_stocktrader_stags();

			if ($cache = $this->registry->db->query_read("
				SELECT * FROM " . TABLE_PREFIX . "ssgti_stocktrader_cache
				$where_clause
				ORDER BY stockid ASC
			"))
			{
				while ($stock = $this->registry->db->fetch_array($cache))
				{
					$data[$stock['stockid']] = $stock;
				}
				
				$toberetrieved = @array_diff($stocks, array_keys($data));

				if (count($toberetrieved) > 0)
				{
					$retrievethose = $toberetrieved;
				}
			}
			else
			{
				$retrievethose = $stocks;
			}


			if (count($retrievethose) > 0)
			{
				$rawdata = $this->retrieve($retrievethose);
				if (is_array($rawdata) AND count($rawdata) > 0)
				{
					foreach ($rawdata AS $rowid => $rowdata)
					{
						$data[$rowid]['stockid'] = $rowid;
						$data[$rowid]['dateline'] = TIMENOW;
						$data[$rowid]['lastupdate'] = TIMENOW;
						$data[$rowid]['stags'] = serialize($rowdata);
					}
				}
				else
				{
					$this->error('ssgti_stocktrader_fetch_invalid_stocks');
				}
			}
		}
		else
		{
			$this->error('ssgti_stocktrader_fetch_invalid_stocks');
		}


		// Return only required data ...
		$return = $tmparr = array();
		foreach ($data AS $key => $value)
		{
			foreach (unserialize($value['stags']) AS $stagid => $stag)
			{
				if (in_array($stagid, $stags))
				{
					$tmparr["$stagid"] = $stag;
				}
			}

			$return["$key"] = array('stockid' => $value['stockid'], 'dateline' => $value['dateline'], 'lastupdate' => $value['lastupdate'], 'stags' => serialize($tmparr), 'flag' => $value['flag']);
		}


		return $return;
	}


	/**
	* Fetch data from external URL
	*
	* @param	string	Message text
	*
	* @return	array
	*/
	function vurl($url, $post = '0')
	{
		require_once(DIR . '/includes/class_vurl.php');

		$vurl = new vB_vURL($this->registry);
		$vurl->set_option(VURL_URL, $url);
		$vurl->set_option(VURL_USERAGENT, 'vBulletin/' . FILE_VERSION);

		if($post != '0') 
		{
			$vurl->set_option(VURL_POST, 1);
			$vurl->set_option(VURL_POSTFIELDS, $post);
		}

		$vurl->set_option(VURL_RETURNTRANSFER, 1);
		$vurl->set_option(VURL_CLOSECONNECTION, 1);
		return $vurl->exec();
	}


	/**
	* Sets the error handler for the object
	*
	* @param	string	Error type
	*
	* @return	boolean
	*/
	function set_error_handler($errtype = ERRTYPE_SILENT)
	{
		switch ($errtype)
		{
			case ERRTYPE_ARRAY:
			case ERRTYPE_STANDARD:
			case ERRTYPE_CP:
			case ERRTYPE_SILENT:
				$this->error_handler = $errtype;
				break;
			default:
				$this->error_handler = ERRTYPE_STANDARD;
				break;
		}
	}


	/**
	* Shows an error message and halts execution - use this in the same way as print_stop_message();
	*
	* @param	string	Phrase name for error message
	*/
	function error($errorphrase)
	{
		$args = func_get_args();

		if (is_array($errorphrase))
		{
			$error = fetch_error($errorphrase);
		}
		else
		{
			$error = call_user_func_array('fetch_error', $args);
		}

		$this->errors[] = $error;

		if ($this->failure_callback AND is_callable($this->failure_callback))
		{
			call_user_func_array($this->failure_callback, array(&$this, $errorphrase));
		}

		switch ($this->error_handler)
		{
			case ERRTYPE_ARRAY:
			case ERRTYPE_SILENT:
			{
				// do nothing
			}
			break;

			case ERRTYPE_STANDARD:
			{
				eval(standard_error($error));
			}
			break;

			case ERRTYPE_CP:
			{
				print_cp_message($error);
			}
			break;
		}
	}


	/**
	* Check if the DM currently has errors. Will kill execution if it does and $die is true.
	*
	* @param	bool	Whether or not to end execution if errors are found; ignored if the error type is ERRTYPE_SILENT
	*
	* @return	bool	True if there *are* errors, false otherwise
	*/
	function has_errors($die = true)
	{
		if (!empty($this->errors))
		{
			if ($this->error_handler == ERRTYPE_SILENT OR $die == false)
			{
				return true;
			}
			else
			{
				trigger_error('<ul><li>' . implode($this->errors, '</li><li>') . '</ul>Unable to proceed with save while $errors array is not empty in class <strong>' . get_class($this) . '</strong>', E_USER_ERROR);
				return true;
			}
		}
		else
		{
			return false;
		}
	}


	/**
	* Sets the function to call on an error.
	*
	* @param	callback	A valid callback (either a function name, or specially formed array)
	*/
	function set_failure_callback($callback)
	{
		$this->failure_callback = $callback;
	}
}

?>