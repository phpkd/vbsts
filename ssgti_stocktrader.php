<?php
// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE & ~8192);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'ssgti_stocktrader');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('user', 'ssgti_stocktrader', 'cppermission');

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array(
	'SSGTI_STOCKTRADER_SHELL'
);

// pre-cache templates used by specific actions
$actiontemplates = array(
	'main' => array(
		'ssgti_stocktrader_main',
	),
	'retrieve' => array(
		'ssgti_stocktrader_retrieve',
	),
	'buy' => array(
		'ssgti_stocktrader_buy',
	),
	'sell' => array(
		'ssgti_stocktrader_sell',
	),
	'history' => array(
		'ssgti_stocktrader_history',
	),
	'spyeye' => array(
		'ssgti_stocktrader_spyeye',
	),
	'toplist' => array(
		'ssgti_stocktrader_toplist',
	),
	'stoploss' => array(
		'ssgti_stocktrader_stoploss',
	),
	'instantinvest' => array(
		'ssgti_stocktrader_instantinvest',
	)
);

$actiontemplates['none'] =& $actiontemplates['main'];

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/class_ssgti_stocktrader.php');
require_once(DIR . '/includes/functions_ssgti_stocktrader.php');


// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if (empty($_REQUEST['do']))
{
	$_REQUEST['do'] = 'main';
}

if (!($permissions['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']))
{
	print_no_permission();
}

if (empty($vbulletin->userinfo['userid']))
{
	print_no_permission();
}

if (!($permissions['ssgti_stocktrader_perms'] & $vbulletin->bf_ugp_ssgti_stocktrader_perms['canview']))
{
	print_no_permission();
}


// set shell template name
$shelltemplatename = 'SSGTI_STOCKTRADER_SHELL';
$templatename = '';

// start the navbar
$navbits = array('ssgti_stocktrader.php' . $vbulletin->session->vars['sessionurl_q'] => $vbulletin->options['ssgti_stocktrader_title']);


// Pre-defined Variables
$show['navprofile'] = ($vbulletin->options['ssgti_stocktrader_navblocks'] & 1);
$show['navstats'] = ($vbulletin->options['ssgti_stocktrader_navblocks'] & 2);
$show['navrules'] = ($vbulletin->options['ssgti_stocktrader_navblocks'] & 4);
$show['stoplossorder'] = ($permissions['ssgti_stocktrader_perms'] & $vbulletin->bf_ugp_ssgti_stocktrader_perms['stoplossorder']);
$show['instantinvestorder'] = ($permissions['ssgti_stocktrader_perms'] & $vbulletin->bf_ugp_ssgti_stocktrader_perms['instantinvestorder']);
$show['canobtaincustomname'] = ($permissions['ssgti_stocktrader_perms'] & $vbulletin->bf_ugp_ssgti_stocktrader_perms['canobtaincustomname']);
$show['stockexception'] = ($permissions['ssgti_stocktrader_perms'] & $vbulletin->bf_ugp_ssgti_stocktrader_perms['stockexception']);
$show['socialaccess'] = ($permissions['ssgti_stocktrader_perms'] & $vbulletin->bf_ugp_ssgti_stocktrader_perms['socialaccess']);
$show['socialparticipation'] = ($permissions['ssgti_stocktrader_perms'] & $vbulletin->bf_ugp_ssgti_stocktrader_perms['socialparticipation']);
$show['canclearselfhistory'] = ($permissions['ssgti_stocktrader_perms'] & $vbulletin->bf_ugp_ssgti_stocktrader_perms['canclearselfhistory']);


// ############################################################################
// ############################### EDIT PROFILE ###############################
// ############################################################################

if (!in_array($_REQUEST['do'], array('main', 'invest')))
{
	ssgti_stocktrader_construct_nav('home');
	$templatename = 'ssgti_stocktrader_underconstruction';
}


if ($_REQUEST['do'] == 'main')
{
	$data = $ssgti_stocktrader = array();
	$stocks = $db->query_read_slave("
		SELECT ssgti_stocktrader.*, ssgti_stocktrader_cache.stags FROM " . TABLE_PREFIX . "ssgti_stocktrader AS ssgti_stocktrader
		LEFT JOIN " . TABLE_PREFIX . "ssgti_stocktrader_cache AS ssgti_stocktrader_cache USING (stockid)
		WHERE userid = " . $vbulletin->userinfo['userid'] . "
		ORDER BY ssgti_stocktrader.stockid ASC
	");

	if ($db->num_rows($stocks) > 0)
	{
		$ssgti_stocktrader['tpprice'] = $ssgti_stocktrader['tpprice'] = $ssgti_stocktrader['tgain'] = 0;
		while ($stock = $db->fetch_array($stocks))
		{
			$unsstags = unserialize($stock['stags']);
			$data["$stock[stockid]"] = array('stockid' => $stock['stockid'], 'stockname' => $unsstags['n'], 'shares' => vb_number_format($stock['shares']), 'pdate' => vbdate($vbulletin->options['dateformat'], $stock['dateline'], true), 'lpurchase' => vbdate($vbulletin->options['dateformat'], $stock['lastupdate'], true), 'pprice' => ssgti_stocktrader_currency_symbol($stock['price']), 'tpprice' => ssgti_stocktrader_currency_symbol($stock['shares'] * $stock['price']), 'cprice' => ssgti_stocktrader_currency_symbol($unsstags['l1']), 'tcprice' => ssgti_stocktrader_currency_symbol($stock['shares'] * $unsstags['l1']), 'gain' => ssgti_stocktrader_currency_symbol($unsstags['l1'] - $stock['price']), 'tgain' => ssgti_stocktrader_currency_symbol(($stock['shares'] * $unsstags['l1']) - ($stock['shares'] * $stock['price'])), 'gainp' => construct_phrase($vbphrase['ssgti_stocktrader_gainp'], vb_number_format((($unsstags['l1'] - $stock['price']) * 100) / $stock['price'], 2)));
	
			$ssgti_stocktrader['tpprice'] += ($stock['shares'] * $stock['price']);
			$ssgti_stocktrader['tcprice'] += ($stock['shares'] * $unsstags['l1']);
			$ssgti_stocktrader['tgain'] += (($stock['shares'] * $unsstags['l1']) - ($stock['shares'] * $stock['price']));
		}
		$db->free_result($stocks);
	
	
		$ssgti_stocktrader['tgainp'] = construct_phrase($vbphrase['ssgti_stocktrader_gainp'], vb_number_format(($ssgti_stocktrader['tgain'] * 100) / $ssgti_stocktrader['tpprice'], 2));
		$ssgti_stocktrader['tpprice'] = ssgti_stocktrader_currency_symbol($ssgti_stocktrader['tpprice']);
		$ssgti_stocktrader['tcprice'] = ssgti_stocktrader_currency_symbol($ssgti_stocktrader['tcprice']);
		$ssgti_stocktrader['tgain'] = ssgti_stocktrader_currency_symbol($ssgti_stocktrader['tgain']);
	}


	// Output Stuff
	$funcname = 'ssgti_stocktrader_ustocks_' . $vbulletin->options['ssgti_stocktrader_stocktable'];
	$HTML =  $funcname($data, 'sell');
	ssgti_stocktrader_construct_nav('home');
	$navbits = construct_navbits($navbits);
	eval('$navbar = "' . fetch_template('navbar') . '";');
	eval('print_output("' . fetch_template($shelltemplatename) . '");');
}


// ############################### investment page ###############################
if ($_REQUEST['do'] == 'invest')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'stocks' => TYPE_STR,
	));

	if ($vbulletin->GPC['stocks'] != '')
	{
		if (strpos($vbulletin->GPC['stocks'], ',') !== FALSE)
		{
			$stocks = explode(',', $vbulletin->GPC['stocks']);
		}
		else
		{
			$stocks = explode(' ', $vbulletin->GPC['stocks']);
		}

		$stock = new SSGTI_StockTrader_YF($vbulletin);

		// Output Stuff
		$funcname = 'ssgti_stocktrader_' . $vbulletin->options['ssgti_stocktrader_stocktable'];
		$HTML = $funcname($stock->getdata(array_map('strtoupper', array_map('trim', $stocks))), 'buy');
	}
	else
	{
		if ($permissions['ssgti_stocktrader_perms'] & $vbulletin->bf_ugp_ssgti_stocktrader_perms['stockexception'])
		{
			$show['stockexception'] = TRUE;
		}

		$stocks = implode(', ', ssgti_stocktrader_stocks());
		eval('$content = "' . fetch_template('ssgti_stocktrader_invest') . '";');
		$HTML = $content;
	}

	ssgti_stocktrader_construct_nav('invest');
	$navbits[''] = $vbphrase['ssgti_stocktrader_nav_invest'];
	$navbits = construct_navbits($navbits);
	eval('$navbar = "' . fetch_template('navbar') . '";');
	eval('print_output("' . fetch_template($shelltemplatename) . '");');
}


// ############################### preview sale ###############################
if ($_POST['do'] == 'previeworder')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'buy'  => TYPE_ARRAY_UINT,
		'sell' => TYPE_ARRAY_UINT,
	));

	$previeworderbits = '';
	$navbits[$vbulletin->options['ssgti_stocktrader_filename'] . '.php?' . $vbulletin->session->vars['sessionurl'] . 'do=invest'] = $vbphrase['ssgti_stocktrader_nav_invest'];


	if ($vbulletin->GPC['buy'])
	{
		$templatename = 'ssgti_stocktrader_underconstruction';
	}
	else if ($vbulletin->GPC['sell'])
	{
		$navbits[''] = $title = $vbphrase['ssgti_stocktrader_sell'];

		$processthose = array();
		foreach ($vbulletin->GPC['sell'] AS $bitid => $bit)
		{
			if ($bit > 0)
			{
				$processthose["$bitid"] = $bit;
			}
		}

		$whereclause = $astrikebits = '';


		if ($permissions['ssgtistocktrader_itr'] > 0)
		{
			$whereclause = "AND ssgti_stocktrader." . $vbulletin->options['ssgti_stocktrader_itrd'] . " < " . (TIMENOW - ($permissions['ssgtistocktrader_itr'] * 86400));
		}


		$stocks = $db->query_read_slave("
			SELECT ssgti_stocktrader.*, ssgti_stocktrader_cache.stags FROM " . TABLE_PREFIX . "ssgti_stocktrader AS ssgti_stocktrader
			LEFT JOIN " . TABLE_PREFIX . "ssgti_stocktrader_cache AS ssgti_stocktrader_cache USING (stockid)
			WHERE ssgti_stocktrader.userid = " . $vbulletin->userinfo['userid'] . " AND stockid IN('" . implode('\',\'', array_keys($processthose)) . "')
				$whereclause
			ORDER BY ssgti_stocktrader.stockid ASC
		");


		$astrikes = array();
		if ($db->num_rows($stocks))
		{
			if ($permissions['ssgtistocktrader_itr'] > 0)
			{
				if ($vbulletin->options['ssgti_stocktrader_itrd'] == 'dateline')
				{
					$astrike = construct_phrase($vbphrase['ssgti_stocktrader_previeworder_sell_astrike31'], $permissions['ssgtistocktrader_itr']);
				}
				else
				{
					$astrike = construct_phrase($vbphrase['ssgti_stocktrader_previeworder_sell_astrike312'], $permissions['ssgtistocktrader_itr']);
				}

				// Investment Time Requirement
				$astrikes['astrike3'] = 'astrike3';
				eval('$astrikebits .= "' . fetch_template('ssgti_stocktrader_previeworder_astrike') . '";');
			}


			$allstocks = array('stocks' => 0, 'shares' => 0, 'doshares' => 0, 'rawtotalprice' => 0, 'rawtotalpricebefore' => 0);
			while ($stock = $db->fetch_array($stocks))
			{
				if ($processthose["$stock[stockid]"] >= $stock['shares'])
				{
					if (!isset($astrikes['astrike1']))
					{
						// Reduce -to be sold- shares if the used doesn't own that quantity (reduce to the amount he own)
						$astrikes['astrike1'] = 'astrike1';
						$astrike = $vbphrase['ssgti_stocktrader_previeworder_sell_astrike1'];
						eval('$astrikebits .= "' . fetch_template('ssgti_stocktrader_previeworder_astrike') . '";');
					}

					$stockdetails['doshares'] = vb_number_format($stock['shares']);
				}
				else
				{
					$stockdetails['doshares'] = vb_number_format($processthose["$stock[stockid]"]);
				}


				$stock['shares'] = vb_number_format($stock['shares']);
				$stockdetails['unser'] = unserialize($stock['stags']);
				$stockdetails['stockname'] = $stockdetails['unser']['n'];
				$stockdetails['shareprice'] = ssgti_stocktrader_currency_symbol($stockdetails['unser']['l1']);


				if ($stockdetails['unser']['l1'] < $stock['price'])
				{
					$stockdetails['lossing'] = TRUE;
					$stockdetails['rawtotalprice'] = ssgti_stocktrader_commissions(($stockdetails['unser']['l1'] * $stockdetails['doshares']), 'sell', 'loss');
				}
				else
				{
					$stockdetails['rawtotalprice'] = ssgti_stocktrader_commissions(($stockdetails['unser']['l1'] * $stockdetails['doshares']), 'sell', 'profit');
				}

				$stockdetails['totalprice'] = (($stockdetails['rawtotalprice'] > 0) ? ssgti_stocktrader_currency_symbol($stockdetails['rawtotalprice']) : 'ignore');

				if ($stockdetails['totalprice'] == 'ignore')
				{
					if (!isset($astrikes['astrike5']))
					{
						// Shares can't be sold since it equals zero or negative!!
						$astrikes['astrike5'] = 'astrike5';
						$astrike = $vbphrase['ssgti_stocktrader_previeworder_sell_astrike5'];
						eval('$astrikebits .= "' . fetch_template('ssgti_stocktrader_previeworder_astrike') . '";');
					}
				}


				$allstocks['stocks']++;
				$allstocks['shares'] += $stock['shares'];
				$allstocks['doshares'] += $stockdetails['doshares'];
				$allstocks['rawtotalprice'] += $stockdetails['rawtotalprice'];
				$allstocks['rawtotalpricebefore'] += ($stockdetails['unser']['l1'] * $stockdetails['doshares']);


				eval('$previeworderbits .= "' . fetch_template('ssgti_stocktrader_previeworder_sell_bits') . '";');
				unset($stockdetails, $astrikes['astrike1'], $astrikes['astrike5']);
			}


			$allstocks['stocks'] = vb_number_format($allstocks['stocks']);
			$allstocks['shares'] = vb_number_format($allstocks['shares']);
			$allstocks['doshares'] = vb_number_format($allstocks['doshares']);
			$allstocks['totalprice'] = ssgti_stocktrader_currency_symbol($allstocks['rawtotalprice']);


			// Commission Rates
			$astrikes['astrike4'] = 'astrike4';
			$astrike = $vbphrase['ssgti_stocktrader_previeworder_sell_astrike4'];
			eval('$astrikebits .= "' . fetch_template('ssgti_stocktrader_previeworder_astrike') . '";');
		}
		else
		{
			// No Records Returned
			$astrikes['astrike2'] = 'astrike2';
			$astrike = $vbphrase['ssgti_stocktrader_previeworder_sell_astrike2'];
			eval('$astrikebits .= "' . fetch_template('ssgti_stocktrader_previeworder_astrike') . '";');
		}
		$db->free_result($stocks);

		$templatename = 'ssgti_stocktrader_previeworder_sell';
	}
	else
	{
		eval(standard_error(fetch_error('ssgti_stocktrader_invalid_action')));
	}


	// Output Stuff
	ssgti_stocktrader_construct_nav('invest');
}


// ############################### process order ###############################
if ($_POST['do'] == 'processorder')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'doshares' => TYPE_ARRAY_UINT,
		'dovalue'  => TYPE_ARRAY_UNUM,
		'approve'  => TYPE_ARRAY_UNUM,
		'totals'   => TYPE_ARRAY_UNUM,
		'type'     => TYPE_STR,
	));


	if ($vbulletin->GPC['type'] == 'dobuy')
	{
		// Do something!!
		$templatename = 'ssgti_stocktrader_previeworder_buy';
	}
	else if ($vbulletin->GPC['type'] == 'dosell')
	{
		$processthose = array();
		foreach ($vbulletin->GPC['approve'] AS $bitid => $bit)
		{
			if ($bit == 1)
			{
				$processthose["$bitid"] = array('shares' => $vbulletin->GPC['doshares']["$bitid"], 'value' => $vbulletin->GPC['dovalue']["$bitid"]);
			}
		}


		$stocks = $db->query_read_slave("
			SELECT ssgti_stocktrader.*, ssgti_stocktrader_cache.stags FROM " . TABLE_PREFIX . "ssgti_stocktrader AS ssgti_stocktrader
			LEFT JOIN " . TABLE_PREFIX . "ssgti_stocktrader_cache AS ssgti_stocktrader_cache USING (stockid)
			WHERE ssgti_stocktrader.userid = " . $vbulletin->userinfo['userid'] . " AND stockid IN('" . implode('\',\'', array_keys($processthose)) . "')
			ORDER BY ssgti_stocktrader.stockid ASC
		");


		if ($db->num_rows($stocks))
		{
			$acualprocess = $allprocess = array();
			while ($stock = $db->fetch_array($stocks))
			{
				if ($stock['shares'] > $processthose["$stock[stockid]"]['shares'])
				{
					$acualprocess['update']["$stock[stockid]"] = array('shares' => $processthose["$stock[stockid]"]['shares'], 'value' => $processthose["$stock[stockid]"]['value']);
				}
				else if ($stock['shares'] == $processthose["$stock[stockid]"]['shares'])
				{
					$acualprocess['delete']["$stock[stockid]"] = array('shares' => $processthose["$stock[stockid]"]['shares'], 'value' => $processthose["$stock[stockid]"]['value']);
				}
				else
				{
					continue;
				}
			}
		}
		else
		{
			eval(standard_error(fetch_error('ssgti_stocktrader_invalid_action')));
		}
		$db->free_result($stocks);
 
 
		if (count($acualprocess['update']) > 0)
		{
			$updatesql = $tobeupdated = '';
			foreach ($acualprocess['update'] AS $key => $value)
			{
				$allprocess["$key"] = array('shares' => $value['shares'], 'price' => $value['value'], 'type' => 'sell');
				$updatesql .= "WHEN ssgti_stocktrader.stockid = '$key' THEN (ssgti_stocktrader.shares - $value[shares])\n";
				$tobeupdated .= ",'$key'";
			}

			if ($updatesql != '')
			{
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "ssgti_stocktrader
					SET ssgti_stocktrader.shares =
						CASE
							$updatesql
							ELSE ssgti_stocktrader.shares
						END
					WHERE ssgti_stocktrader.stockid IN (-1$tobeupdated) AND ssgti_stocktrader.userid = " . $vbulletin->userinfo['userid'] . "
				");
			}
		}


		if (count($acualprocess['delete']) > 0)
		{
			foreach ($acualprocess['delete'] AS $key => $value)
			{
				$allprocess["$key"] = array('shares' => $value['shares'], 'price' => $value['value'], 'type' => 'sell');
			}

			$db->query_write("
				DELETE FROM " . TABLE_PREFIX . "ssgti_stocktrader
				WHERE ssgti_stocktrader.stockid IN ('" . implode('\',\'', array_keys($acualprocess['delete'])) . "') AND ssgti_stocktrader.userid = " . $vbulletin->userinfo['userid'] . "
			");
		}


		if ($vbulletin->GPC['totals']['rawprice'] > 0)
		{
			$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
			$userdata->set_existing($vbulletin->userinfo);
			$userdata->set($vbulletin->options['ssgti_stocktrader_money'], (($vbulletin->userinfo[$vbulletin->options['ssgti_stocktrader_money']] + round($vbulletin->GPC['totals']['rawprice'])) / $permissions['ssgtistocktrader_exrate']));
			$userdata->save();
		}


		if ($vbulletin->GPC['totals']['rawpricebefore'] > $vbulletin->GPC['totals']['rawprice'])
		{
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "ssgti_stocktrader_global
				SET bank = (bank + " . ($vbulletin->GPC['totals']['rawpricebefore'] - $vbulletin->GPC['totals']['rawprice']) . ")
			");
		}


		if (count($allprocess) > 0)
		{
			$historysql = '';
			foreach ($allprocess AS $key => $value)
			{
				$historysql .= "(" . $vbulletin->userinfo['userid'] . ", '$key', " . TIMENOW . ", $value[shares], $value[price], 'sell'),";
			}
			$historysql = substr($historysql, 0, -1);

			$db->query_write("
				INSERT INTO " . TABLE_PREFIX . "ssgti_stocktrader_history
					(userid, stockid, dateline, shares, price, type)
				VALUES
					$historysql
			");
		}

 		$vbulletin->url = 'ssgti_stocktrader.php' . $vbulletin->session->vars['sessionurl_q'];
		eval(print_standard_redirect('redirect_ssgti_stocktrader_ordercompleted'));
 	}
	else
	{
		$vbulletin->url = 'ssgti_stocktrader.php' . $vbulletin->session->vars['sessionurl_q'];
		eval(print_standard_redirect('redirect_ssgti_stocktrader_invalidaction'));
	}
}


// #############################################################################
// spit out final HTML if we have got this far

if ($templatename != '')
{
	// make navbar
	$navbits = construct_navbits($navbits);
	eval('$navbar = "' . fetch_template('navbar') . '";');

	($hook = vBulletinHook::fetch_hook('profile_complete')) ? eval($hook) : false;

	// shell template
	eval('$HTML = "' . fetch_template($templatename) . '";');
	eval('print_output("' . fetch_template($shelltemplatename) . '");');
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 10:57, Wed Sep 9th 2009
|| # CVS: $RCSfile$ - $Revision: 31381 $
|| ####################################################################
\*======================================================================*/
?>
