<?php


/**
* Menu parameters plugin
* @Copyright (C) 2009-2011 Gavick.com
* @ All rights reserved
* @ Joomla! is Free Software
* @ Released under GNU/GPL License : http://www.gnu.org/copyleft/gpl.html
* @version $Revision: GK4 1.0 $
**/


defined( '_JEXEC' ) or die();

jimport( 'joomla.plugin.plugin' );

jimport('joomla.application.module.helper');

jimport( 'joomla.event.plugin' );

jimport( 'joomla.html.parameter' );





class plgSystemGKExtMenu extends JPlugin {

	var $_params;

	var $_pluginPath;

	

	function __construct( &$subject ) {
		
		parent::__construct( $subject );
		$this->_plugin = JPluginHelper::getPlugin( 'system', 'gkextmenu' );

		$this->_params = new JRegistry( $this->_plugin->params );

		$this->_pluginPath = JPATH_PLUGINS."/system/gkextmenu/";

	}

	//Add Gavick menu parameter

	function onContentPrepareForm($form, $data) {
		
		
		if ($form->getName()=='com_menus.item') {

			JForm::addFormPath($this->_pluginPath);

			$form->loadFile('parameters', false);

		}

	}

}

?>