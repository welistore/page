<?php
/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');

class K2StoreViewPayment extends K2StoreView
{

	function display($tpl = null) {

		$mainframe = JFactory::getApplication();
		$option = 'com_k2store';
		$ns = 'com_k2store.payment';
		$task = $mainframe->input->getCmd('task');
		$session = JFactory::getSession();

			$db		=JFactory::getDBO();
			$uri	=JFactory::getURI();
			$params = JComponentHelper::getParams('com_k2store');

			$filter_order		= $mainframe->getUserStateFromRequest( $ns.'filter_order',		'filter_order',		'tbl.id',	'cmd' );
			$filter_order_Dir	= $mainframe->getUserStateFromRequest( $ns.'filter_order_Dir',	'filter_order_Dir',	'',				'word' );
			$filter_orderstate	= $mainframe->getUserStateFromRequest( $ns.'filter_orderstate',	'filter_orderstate',	'', 'string' );
			$filter_name = $mainframe->getUserStateFromRequest( $ns.'filter_name',		'filter_name',		'tbl.name',	'cmd' );

			$search				= $mainframe->getUserStateFromRequest( $ns.'search',			'search',			'',				'string' );
			if (strpos($search, '"') !== false) {
				$search = str_replace(array('=', '<'), '', $search);
			}
			$search = JString::strtolower($search);

			$model = $this->getModel('payment');
			// Get data from the model
			$items		=  $model->getList();
			$total		=  $model->getTotal();
			$pagination =  $model->getPagination();

			// table ordering
			$lists['order_Dir'] = $filter_order_Dir;
			$lists['order'] = $filter_order;
			$lists['filter_name'] = $filter_name;

			$update = array();
			$warning = '';

			//only call once per session. Dont call this often
			if(!$session->has('plugin_update_data', 'k2store')) {
				try {
				require_once(JPATH_ADMINISTRATOR.'/components/com_k2store/liveupdate/classes/download.php');
				$xmlfile = 'http://cdn.k2store.org/extensions.xml';
				$result = LiveUpdateDownloadHelper::downloadAndReturn($xmlfile );
				if($result) {
					$extensions = simplexml_load_string($result, 'SimpleXMLElement');
					if(is_object($extensions)) {
						$type = (string) $extensions->extension->attributes()->type;
						if($type == 'payment') {
							$plugins = $extensions->extension->plugins->plugin;
							foreach ($plugins as $plugin) {
								$update[(string) $plugin->attributes()->element] = (array)$plugin;
							}
						}

						if(count($update)) {
							$session->set('plugin_update_data', $update, 'k2store' );
						}
					}
				}

				}catch(Exception $e) {
					$warning = JText::_('K2STORE_PAYMENT_XML_REMOTE_ERROR');
					$this->assignRef('warning',	$warning);
				}

			} else {
				$update = $session->get('plugin_update_data', array(), 'k2store' );
			}
			$this->assignRef('update',		$update);

			// search filter
			$lists['search']= $search;

			$this->assignRef('lists',		$lists);
			$this->assignRef('items',		$items);
			$this->assignRef('pagination',	$pagination);

			JToolBarHelper::title(JText::_('K2STORE_PAYMENT_PLUGINS'),'k2store-logo');
			$toolbar = new K2StoreToolBar();
			$toolbar->renderLinkbar();

			parent::display($tpl);
	}

}
