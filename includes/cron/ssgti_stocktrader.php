<?php
// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE & ~8192);
if (!is_object($vbulletin->db))
{
	exit;
}

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

if ($vbulletin->options['ssgti_stocktrader_active'])
{
	require_once(DIR . '/includes/class_ssgti_stocktrader.php');

	$stock = new SSGTI_StockTrader_YF($vbulletin);
	$stock->retrieve(ssgti_stocktrader_stocks());

	log_cron_action('', $nextitem, 1);
}
?>