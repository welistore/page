<?php
/*------------------------------------------------------------------------
# mod_k2store_cart - K2Store Cart
# ------------------------------------------------------------------------
# author    Sasi varna kumar - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://k2store.org
# Technical Support:  Forum - http://k2store.org/forum/index.html
-------------------------------------------------------------------------*/



// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
require_once( dirname(__FILE__).'/helper.php' );
require_once(JPATH_ADMINISTRATOR.'/components/com_k2store/library/base.php');
JFactory::getLanguage()->load('com_k2store', JPATH_SITE);

$currencies = ModK2StoreCurrencyHelper::getCurrencies($params);

$currency = K2StoreFactory::getCurrencyObject();
$currency_code = $currency->getCode();

$moduleclass_sfx = $params->get('moduleclass_sfx','');
$background_color = $params->get('background_color', '#FFFFFF');
$text_color = $params->get('text_color', '#000000');
$link_color = $params->get('link_color', '#CCCCCC');
$link_hover_color = $params->get('link_hover_color', '#000000');
$active_link_color = $params->get('active_link_color', '#000000');

require( JModuleHelper::getLayoutPath('mod_k2store_currency') );
?>
