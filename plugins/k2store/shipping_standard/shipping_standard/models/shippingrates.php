<?php
/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE.'/components/com_k2store/models/_base.php');

class K2StoreModelShippingRates extends K2StoreModelBase
{
    public $cache_enabled = false;

    protected function _buildQueryWhere($query)
    {
        $filter_id	= $this->getState('filter_id');
        $filter_shippingmethod  = $this->getState('filter_shippingmethod');
        $filter_weight = $this->getState('filter_weight');
       	$filter_user_group	= $this->getState('filter_user_group');
        $filter_geozone = $this->getState('filter_geozone');
        $filter_geozones = $this->getState('filter_geozones');

		if (strlen($filter_id))
        {
            $query->where('tbl.shipping_rate_id = '.(int) $filter_id);
       	}
        if (strlen($filter_shippingmethod))
        {
            $query->where('tbl.shipping_method_id = '.(int) $filter_shippingmethod);
        }
    	if (strlen($filter_user_group))
        {
            $query->where('tbl.group_id = '.(int) $filter_user_group);
       	}
    	if (strlen($filter_weight))
        {
        	$query->where("(
        		tbl.shipping_rate_weight_start <= '".$filter_weight."'
        		AND (
                    tbl.shipping_rate_weight_end >= '".$filter_weight."'
                    OR
                    tbl.shipping_rate_weight_end = '0.000'
                    )
			)");
       	}
        if (strlen($filter_geozone))
        {
            $query->where('tbl.geozone_id = '.(int) $filter_geozone);
        }

        if (is_array($filter_geozones))
        {
            $query->where("tbl.geozone_id IN ('" . implode("', '", $filter_geozones ) . "')" );
        }
    }

    protected function _buildQueryJoins($query)
    {
        $query->join('LEFT', '#__k2store_geozones AS geozone ON tbl.geozone_id = geozone.geozone_id');
    }

    protected function _buildQueryFields($query)
    {
        $field = array();
        $field[] = " geozone.geozone_name ";

        $query->select( $this->getState( 'select', 'tbl.*' ) );
        $query->select( $field );
    }

	public function getList($refresh = false)
	{
		$list = parent::getList($refresh);

		// If no item in the list, return an array()
        if( empty( $list ) ){
        	return array();
        }

		foreach($list as $item)
		{
			$item->link_remove = 'index.php?option=com_k2store&view=shippingrates&task=delete&cid[]='.$item->shipping_rate_id;
		}
		return $list;
	}
}
