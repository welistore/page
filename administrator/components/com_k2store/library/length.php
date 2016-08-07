<?php
/*------------------------------------------------------------------------
# com_k2store - K2Store
# ------------------------------------------------------------------------
# author    Ramesh Elamathi - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://k2store.org
# Technical Support:  Forum - http://k2store.org/forum/index.html
-------------------------------------------------------------------------*/

/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');

class K2StoreLength {

	private $lengths = array();

	/*
	 * K2StoreWeight instance
	*
	* since 2.6
	*/

	protected static $instance;

	public function __construct() {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)->select('*')
					->from('#__k2store_lengths');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		foreach ($rows as $row) {
      		$this->lengths[$row->length_class_id] = array(
        		'length_class_id' => $row->length_class_id,
        		'title'           => $row->length_title,
				'unit'            => $row->length_unit,
				'value'           => $row->length_value
      		);
    	}
  	}

  	public static function getInstance()
  	{
  		if (!is_object(self::$instance))
  		{
  			self::$instance = new self();
  		}

  		return self::$instance;
  	}


  	public function convert($value, $from, $to) {
		if ($from == $to) {
      		return $value;
		}

		if (isset($this->lengths[$from])) {
			$from = $this->lengths[$from]['value'];
		} else {
			$from = 0;
		}

		if (isset($this->lengths[$to])) {
			$to = $this->lengths[$to]['value'];
		} else {
			$to = 0;
		}

		return $value * ($to / $from);
  	}

	public function format($value, $length_class_id, $decimal_point = '.', $thousand_point = ',') {
		if (isset($this->lengths[$length_class_id])) {
    		return number_format($value, 2, $decimal_point, $thousand_point) . $this->lengths[$length_class_id]['unit'];
		} else {
			return number_format($value, 2, $decimal_point, $thousand_point);
		}
	}

	public function getUnit($length_class_id) {
		if (isset($this->lengths[$length_class_id])) {
    		return $this->lengths[$length_class_id]['unit'];
		} else {
			return '';
		}
	}
}