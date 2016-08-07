<?php
/*------------------------------------------------------------------------
 # com_k2store - K2Store
# ------------------------------------------------------------------------
# author    Sasi varna kumar - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://k2store.org
# Technical Support:  Forum - http://k2store.org/forum/index.html
-------------------------------------------------------------------------*/

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.modeladmin');

class K2StoreModelCurrency extends JModelAdmin
{
	protected $text_prefix = 'COM_K2STORE';

	protected $view_list = 'currencies';

	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app	= JFactory::getApplication();
		// Get the form.
		$form = $this->loadForm('com_k2store.currency', 'currency', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form)) {
			return false;
		}

		return $form;
	}



	public function getTable($type = 'currency', $prefix = 'Table', $config = array())
	{

		return JTable::getInstance($type, $prefix, $config);
	}


	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk)) {
			// Convert the params field to an array.
		}

		return $item;
	}


	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_k2store.edit.currency.data', array());

		if (empty($data)) {
			$data = $this->getItem();

			// Prime some default values.
			if ($this->getState('currency.id') == 0) {
				$app = JFactory::getApplication();
				// set the id here if it does not work.
				//$data->set('pro_id', JRequest::getInt('pro_id', $app->getUserState('com_jsecommerce.profile.filter.category_id')));
			}
		}

		return $data;
	}

	protected function prepareTable($table)
	{
		jimport('joomla.filter.output');
		$date = JFactory::getDate();
		$user = JFactory::getUser();

	}

}
