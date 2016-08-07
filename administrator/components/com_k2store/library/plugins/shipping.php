<?php

/** ensure this file is being included by a parent file */
defined( '_JEXEC' ) or die( 'Restricted access' );
require_once (JPATH_ADMINISTRATOR.'/components/com_k2store/library/plugins/_base.php');
require_once (JPATH_SITE.'/components/com_k2store/models/_base.php');

if ( !class_exists( 'K2StoreShippingPlugin' ) )
{

	class K2StoreShippingPlugin extends K2StorePluginBase
	{
		/**
		 * @var $_element  string  Should always correspond with the plugin's filename,
		 *                         forcing it to be unique
		 */
		var $_element = '';

		function __construct( &$subject, $config )
		{
			parent::__construct( $subject, $config );
			$this->loadLanguage( '', JPATH_ADMINISTRATOR );
			$this->loadLanguage( '', JPATH_SITE );
		//	$this->getShopAddress( );
		}

		/************************************
		 * Note to 3pd:
		 *
		 * The methods between here
		 * and the next comment block are
		 * yours to modify by overrriding them in your shipping plugin
		 *
		 ************************************/

		/**
		 * Returns the Shipping Rates.
		 * @param $element the shipping element name
		 * @param $product the product row
		 * @return array
		 */
		public function onK2StoreGetShippingRates( $element, $order )
		{
			if ( !$this->_isMe( $element ) )
			{
				return null;
			}

			$rate = array( );
			$rate['name'] = "";
			$rate['code'] = "";
			$rate['price'] = "";
			$rate['extra'] = "";
			$rate['total'] = "";
			$rate['tax'] = "";
			$rate['element'] = $this->_element;
			$rate['error'] = false;
			$rate['errorMsg'] = "";
			$rate['debug'] = "";

			$rates[] = $return;

			return $rates;
		}

		/**
		 * Here you will have to save the shipping rate information
		 *
		 * @param $element the shipping element name
		 * @param $order the order object
		 * @return html
		 */
		public function onK2StorePostSaveShipping( $element, $order )
		{
			if ( !$this->_isMe( $element ) )
			{
				return null;
			}
		}

		/**
		 * Get a particular shipping rate
		 * @param unknown_type $rate_id
		 */
		public function getShippingRate( $rate_id )
		{
		}

		/**
		 * Shows the shipping view
		 *
		 * @param $row	the shipping data
		 * @return unknown_type
		 */
		public function onK2StoreGetShippingView( $row )
		{
			if ( !$this->_isMe( $row ) )
			{
				return null;
			}
		}

		/**
		 * If you want to show something on the product admin page,
		 * override this function
		 *
		 * @param $product the product row
		 * @return html
		 */
		public function onK2StoreGetProductView( $product )
		{
			// show something on the product admin page
		}

		/**
		 * If you have to deal with the product data after the save
		 *
		 * @param $product the product row
		 * @return html
		 */
		public function onK2StoreAfterSaveProducts( $product )
		{
			// Do Something here with the product data
		}

		/************************************
		 * Note to 3pd:
		 *
		 * DO NOT MODIFY ANYTHING AFTER THIS
		 * TEXT BLOCK UNLESS YOU KNOW WHAT YOU
		 * ARE DOING!!!!!
		 *
		 ************************************/

		/**
		 * Tells extension that this is a shipping plugin
		 *
		 * @param $element  string      a valid shipping plugin element
		 * @return boolean	true if it is this particular shipping plugin
		 */
		public function onK2StoreGetShippingPlugins( $element )
		{
			$success = false;
			if ( $this->_isMe( $element ) )
			{
				$success = true;
			}
			return $success;
		}

	/**
     * Determines if this shipping option is valid for this order
     *
     * @param $element
     * @param $order
     * @return unknown_type
     */
    function onK2StoreGetShippingOptions($element, $order)
    {
        // Check if this is the right plugin
        if (!$this->_isMe($element))
        {
            return null;
        }

        $found = true;
        $geozones = $this->params->get('geozones');

        //return true if we have empty geozones
        if(!empty($geozones))
        {
        	$found = false;

          	$geozones = explode(',', $geozones);
          	$orderGeoZones = $order->getShippingGeoZones();

          	//loop to see if we have at least one geozone assigned
          	foreach( $orderGeoZones as $orderGeoZone )
          	{
          		if(in_array($orderGeoZone->geozone_id, $geozones))
          		{
          			$found = true;
          			break;
          		}
          	}
        }
        // if this shipping methods should be available for this order, return true
        // if not, return false.
        // by default, all enabled shipping methods are valid, so return true here,
        // but plugins may override this
        return $found;
    }


		/**
		 * Gets the reports namespace for state variables
		 * @return string
		 */
		protected function _getNamespace( )
		{
			$app = JFactory::getApplication( );
			$ns = $app->getName( ) . '::' . 'com.k2store.shipping.' . $this->get( '_element' );
		}

		/**
		 * Get the task for the shipping plugin controller
		 */
		public static function getShippingTask( )
		{
		 	return JFactory::getApplication()->input->getString( 'shippingTask', '' );

		}

		/**
		 * Get the id of the current shipping plugin
		 */
		public static function getShippingId( )
		{
			return JFactory::getApplication()->input->getInt( 'sid', '' );
		}

		/**
		 * Get a variable from the JRequest object
		 * @param unknown_type $name
		 */
		public function getShippingVar( $name )
		{
			$var = JFactory::getApplication()->input->getString( $name, '' );
			return $var;
		}

		function checkAddress( $address )
		{
			$this->includeK2StoreTables( );

			if ( empty( $address->zone_code ) )
			{
				if ( !empty( $address->zone_id ) )
				{
					$table = JTable::getInstance( 'Zones', 'Table' );
					$table->load( $address->zone_id );
					$address->zone_code = $table->zone_code;
				}
			}

			if ( empty( $address->country_code ) || empty( $address->country_name ) || empty( $address->country_isocode_2 )
					|| empty( $address->country_isocode_3 ) )
			{
				if ( !empty( $address->country_id ) )
				{
					$table = JTable::getInstance( 'Country', 'Table' );
					$table->load( $address->country_id );
					$address->country_name = $table->country_name;
					$address->country_isocode_3 = $table->country_isocode_3;
					$address->country_isocode_2 = $table->country_isocode_2;
					$address->country_code = $table->country_isocode_2;
				}
			}

			return $address;
		}

		function getZone($zone_id) {
			$this->includeK2StoreTables( );
			$table = JTable::getInstance( 'Zone', 'Table' );

			if ( !empty( $zone_id ) )
			{
				$table->load( $zone_id );
			}
			return $table;
		}

		function getCountry($country_id) {
			$this->includeK2StoreTables( );
			$table = JTable::getInstance( 'Country', 'Table' );

			if(!empty($country_id)) {
				$table->load($country_id);
			}
			return $table;
		}

		/**
		 * Prepares the 'view' tmpl layout
		 * when viewing a report
		 *
		 * @return unknown_type
		 */
		function _renderView( $view = 'view', $vars = null )
		{
			if ( $vars == null ) $vars = new JObject( );
			$html = $this->_getLayout( $view, $vars );

			return $html;
		}

		/**
		 * Prepares variables for the report form
		 *
		 * @return unknown_type
		 */
		function _renderForm($data )
		{
			$vars = new JObject( );
			$html = $this->_getLayout( 'form', $vars );

			return $html;
		}

		/**
		 * Gets the appropriate values from the request
		 *
		 * @return unknown_type
		 */
		function _getState( )
		{
			$state = new JObject( );

			foreach ( $state->getProperties( ) as $key => $value )
			{
				$new_value = JRequest::getVar( $key );
				$value_exists = array_key_exists( $key, JRequest::get( 'post' ) );
				if ( $value_exists && !empty( $key ) )
				{
					$state->$key = $new_value;
				}
			}
			return $state;
		}

	}

}
