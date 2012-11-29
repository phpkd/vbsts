<?php

if (!isset($GLOBALS['vbulletin']->db))
{
	exit;
}


/**
* Class for Profile "Stock Trader" Block
*
* @package SSGTI - Greece Weather Module
*/
class vB_ProfileBlock_SSGTI_StockTrader extends vB_ProfileBlock
{
	/**
	* The name of the template to be used for the block
	*
	* @var string
	*/
	var $template_name = 'ssgti_stocktrader_member_block';


	/**
	* Whether or not the block is enabled
	*
	* @return bool
	*/
	function block_is_enabled()
	{
		$continue = false;

		if ($this->registry->options['ssgti_stocktrader_active'] AND !in_array($this->registry->userinfo['userid'], explode(',', $this->registry->options['ssgti_stocktrader_exclude_users'])) AND ($this->registry->userinfo['permissions']['ssgti_stocktrader_perms'] & $this->registry->bf_ugp_ssgti_stocktrader_perms['canview']) AND $this->registry->userinfo['ssgti_stocktrader_active'] AND !in_array($this->profile->userinfo['userid'], explode(',', $this->registry->options['ssgti_stocktrader_exclude_users'])) AND ($this->profile->userinfo['permissions']['ssgti_stocktrader_perms'] & $this->registry->bf_ugp_ssgti_stocktrader_perms['canview']) AND $this->profile->userinfo['ssgti_stocktrader_active'])
		{
			$continue = true;
		}

		if (!$continue)
		{
			return false;
		}
		else
		{
			return true;
		}
	}


	/**
	* Whether to return an empty wrapper if there is no content in the blocks
	*
	* @return bool
	*/
	function confirm_empty_wrap()
	{
		return false;
	}


	/**
	* Should we actually display anything?
	*
	* @return	bool
	*/
	function confirm_display()
	{
		if ($this->block_data['ok'])
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}


	/**
	* Prepare any data needed for the output
	*
	* @param	string	The id of the block
	* @param	array	Options specific to the block
	*/
	function prepare_output($id = '', $options = array())
	{
		global $vbphrase;

		require_once(DIR . '/includes/functions_ssgti_stocktrader.php');
		$this->block_data['ok'] = FALSE;
		
		/*
		 * Do Something!!
		 */

		$ssgti_cgwm_cities = $this->registry->db->query_read_slave("
			SELECT * FROM " . TABLE_PREFIX . "ssgti_cgwm_cache AS cache
			WHERE ((date = '" . vbdate('d/m/Y', TIMENOW, false, false) . "' AND time >= '" . vbdate('H:00', TIMENOW, false, false) . "') OR date > '" . vbdate('d/m/Y', TIMENOW, false, false) . "')
				$query_city
				ORDER BY date, time
				" . iif($this->registry->options['ssgti_cgwm_4_6_days'] == 0 OR $this->registry->options['ssgti_cgwm_block_userprofile_46d'] == 0 OR $this->profile->userinfo['ssgti_cgwm_46d'] == 0 OR $this->registry->userinfo['ssgti_cgwm_46d'] == 0, "LIMIT 11") . "
		");

		if ($this->registry->db->num_rows($ssgti_cgwm_cities) > 0)
		{
			$count = 0;
			$rowid72h = $rowid46d = 1;
			while ($city = $this->registry->db->fetch_array($ssgti_cgwm_cities))
			{
				if ($count < 11)
				{
					eval('$ssgti_cgwm_bits_72h .= "' . fetch_template('ssgti_cgwm_72hbits') . '";');
					$rowid72h++;
				}
				else
				{
					eval('$ssgti_cgwm_bits_46d .= "' . fetch_template('ssgti_cgwm_46dbits') . '";');
					$rowid46d++;
				}

				$count++;
			}

			$this->block_data['ssgti_cgwm_bits_72h'] = $ssgti_cgwm_bits_72h;
			$this->block_data['ssgti_cgwm_bits_46d'] = $ssgti_cgwm_bits_46d;

			if ($this->registry->db->num_rows($ssgti_cgwm_cities) < 12)
			{
				$this->registry->options['ssgti_cgwm_4_6_days'] = 0;
			}
		}

		/*
		 * Do Something!!
		 */

		$this->registry->db->free_result($ssgti_cgwm_cities);
		$this->block_data['ok'] = TRUE;
	}
}

?>