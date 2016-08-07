<?php
require_once('fields.php');
require_once(JPATH_SITE.'/components/com_k2store/helpers/orders.php');
class K2StoreSelectableBase {

	var $tables = array('field');
	var $pkeys = array('field_id');
	var $namekeys = array();
	var $errors = array();
	var $prefix = '';
	var $suffix = '';
	var $excludeValue = array();
	var $toggle = array('field_required'=>'field_id','published'=>'field_id','field_backend'=>'field_id','field_backend_listing'=>'field_id','field_frontcomp'=>'field_id','field_core'=>'field_id');
	var $where = array();
	var $skipAddressName=false;
	var $report = true;
	var $externalValues = null;
	var $fielddata = null;
	var $database = null;

	function __construct() {
		$this->database = JFactory::getDbo();
	}


	function display($field, $value, $name, $translate=false, $options = '', $test = false, $allFields = null, $allValues = null) {
		$field_type = $field->field_type;
		if(substr($field->field_type,0,4) == 'plg.') {
			$field_type = substr($field->field_type,4);
			JPluginHelper::importPlugin('k2store', $field_type);
		}
		$classType = 'k2store'.ucfirst($field_type);
		$class = new $classType($this);
		if(is_string($value))
			$value = htmlspecialchars($value, ENT_COMPAT,'UTF-8');
		$html = '';
	//	if(!empty($field->field_required)){
	//		$html .=' <span class="k2store_field_required">*</span>';
	//	}
		$html .= $class->display($field,$value,$name,$translate, $options,$test,$allFields,$allValues);

		return $html;
	}

	function show($field,$value){
		$field_type = $field->field_type;
		if(substr($field->field_type,0,4) == 'plg.') {
			$field_type = substr($field->field_type,4);
			JPluginHelper::importPlugin('k2store', $field_type);
		}
		$classType = 'k2store'.ucfirst($field_type);
		$class = new $classType($this);
		if(is_string($value))
			$value = htmlspecialchars($value, ENT_COMPAT,'UTF-8');
		$html = '';
	//	if(!empty($field->field_required)){
	//		$html .=' <span class="k2store_field_required">*</span>';
	//	}
		$html .= $class->show($field,$value);

		return $html;
	}

	function getFormatedCustomFields($row, $layout='customfields', $type='billing') {

		$app = JFactory::getApplication();

		// get the template and default paths for the layout
		$templatePath = JPATH_SITE.'/templates/'.$app->getTemplate().'/html/com_k2store/orders/'.$layout.'.php';
		$defaultPath = JPATH_SITE.'/components/com_k2store/views/orders/tmpl/'.$layout.'.php';

		// if the site template has a layout override, use it
		jimport('joomla.filesystem.file');
		if (JFile::exists( $templatePath ))
		{
			$path = $templatePath;
		}
		else
		{
			$path = $defaultPath;
		}

		ob_start();
		include($path);
		$html = ob_get_contents();
		ob_end_clean();

		return $html;


	}

	function getCustomField($row, $type='billing', $fieldname, $valuetype) {
		if($type=='billing') {
			$field = 'all_billing';
		}elseif($type=='shipping') {
			$field = 'all_shipping';
		}elseif($type=='payment') {
			$field = 'all_payment';
		}
		$result = '';
		$label = '';
		$fields = array();
		if(!empty($row->$field) && JString::strlen($row->$field) > 0) {
			$custom_fields = json_decode(stripslashes($row->$field));
			if(isset($custom_fields) && count($custom_fields)) {
				foreach($custom_fields as $namekey=>$field) {
					if(!property_exists($row, $type.'_'.$namekey) && !property_exists($row, 'user_'.$namekey) && $namekey !='country_id' && $namekey != 'zone_id' && $namekey != 'option' && $namekey !='task' && $namekey != 'view' ) {
						$fields[$namekey] = $field;
					}
				}

			}
		}
		if(isset($fields) && count($fields)) {
			if(array_key_exists($fieldname, $fields)) {
				$field = $fields[$fieldname];
				if(is_object($field)) {
					$label = JText::_($field->label);
					if(is_array($field->value)) {
						foreach($field->value as $value) {
							$result .='- '.JText::_($value).'<br/>';
						}

					}elseif(K2StoreOrdersHelper::isJson(stripslashes($field->value))) {
						$json_values = json_decode(stripslashes($field->value));
						foreach($json_values as $value){
							$result .='- '.JText::_($value).'<br/>';
						}

					} else {
						$result .= JText::_($field->value);
					}

				}
			}
		}
		if($valuetype == 'label') {
			return $label;
		}
		return $result;
	}

	function getFormatedDisplay($field, $value, $name, $translate=false, $options = '', $test = false, $allFields = null, $allValues = null) {
		$label = $this->getFieldName($field);
		$input = $this->display($field, $value, $name, $translate, $options, $test, $allFields, $allValues);

		$html = $label.$input.'<br />';
		return $html;
	}


	function validate($formData, $area, $type='address') {

		$data = JArrayHelper::toObject($formData);
		$fields = $this->getFields($area,$data,$type);
		$json = array();
		foreach ($fields as $field) {
			$namekey = $field->field_namekey;
			$field_type = $field->field_type;
			if(substr($field->field_type,0,4) == 'plg.') {
				$field_type = substr($field->field_type,4);
				JPluginHelper::importPlugin('k2store', $field_type);
			}
			$classType = 'k2store'.ucfirst($field_type);
			$class = new $classType($this);

			if(isset($formData[$namekey])) {
				$val = $formData[$namekey];
			} else {
				$val = '';
			}
			$error = $class->check($field,$val, $oldValue='');
			if(!empty($error)) {
				$json['error'][$namekey] = $error;
			}
		}
		return $json;
	}

	function getField($fieldid,$type=''){
		if(is_numeric($fieldid)){
			$query = 'SELECT * FROM #__k2store_field WHERE field_id  = '.$this->database->Quote($fieldid).' LIMIT 1';
			$this->database->setQuery($query);
			$element = $this->database->loadObject();
		}else{
			$this->database->setQuery('SELECT * FROM #__k2store_field WHERE field_table='.$this->database->Quote($type).' AND field_namekey='.$this->database->Quote($fieldid));
			$element = $this->database->loadObject();
		}
		$fields = array($element);
		$data = null;
		$this->prepareFields($fields,$data,$fields[0]->field_type,'',true);
		return $fields[0];
	}


	function prepareFields(&$fields,&$data,$type='user',$url='checkout&task=state',$test=false){
		if(!empty($fields)){
			if($type == 'address') {
				$id = 'id';
			} else {
				$id = $type.'_id';
			}
			foreach($fields as $namekey => $field){
				if(!empty($fields[$namekey]->field_options) && is_string($fields[$namekey]->field_options)){
					$fields[$namekey]->field_options = unserialize($fields[$namekey]->field_options);
				}
				if(!empty($field->field_value) && is_string($fields[$namekey]->field_value)){
					$fields[$namekey]->field_value = $this->explodeValues($fields[$namekey]->field_value);
				}
				if(empty($data->$id) && empty($data->$namekey)){
					if($data == null || empty($data))
						$data = new stdClass();
					if(isset($field->field_default)) {
						$data->$namekey = $field->field_default;
					}
				}

				if(!empty($fields[$namekey]->field_options['zone_type']) && $fields[$namekey]->field_options['zone_type'] == 'country'){
					$baseUrl = JURI::base().'index.php?option=com_k2store&view='.$url.'&tmpl=component';
					$currentUrl = strtolower($this->getCurrentURL());
					if(substr($currentUrl, 0, 8) == 'https://') {
						$domain = substr($currentUrl, 0, strpos($currentUrl, '/', 9));
					} else {
						$domain = substr($currentUrl, 0, strpos($currentUrl, '/', 8));
					}
					if(substr($baseUrl, 0, 8) == 'https://') {
						$baseUrl = $domain . substr($baseUrl, strpos($baseUrl, '/', 9));
					} else {
						$baseUrl = $domain . substr($baseUrl, strpos($baseUrl, '/', 8));
					}
					$fields[$namekey]->field_url = $baseUrl . '&';
				}

			}
			$this->handleZone($fields,$test,$data);
		}
	}


	function handleZone(&$fields,$test=false,$data){

		$types = array();
		foreach($fields as $k => $field){
			if($field->field_type=='zone' && !empty($field->field_options['zone_type'])){
				if($field->field_options['zone_type']!='zone'){
					$types[$field->field_options['zone_type']]=$field->field_options['zone_type'];
				}elseif(empty($field->field_value)){
					$allFields = $this->getData('',$field->field_table,false);
					$country_id = '';
					foreach($allFields as $i => $oneField){

						if(!empty($oneField->field_options)&&is_string($oneField->field_options)){
							$oneField->field_options = unserialize($oneField->field_options);
						}
						if($oneField->field_type=='zone' && !empty($oneField->field_options['zone_type']) && $oneField->field_options['zone_type']=='country'){
							//$zoneClass = hikashop_get('class.zone');
							$namekey = $oneField->field_namekey;
							if(!empty($data->$namekey)){
								$oneField->field_default = $data->$namekey;
							}
							//$zone = $zoneClass->get($oneField->field_default);
							$country_id = $oneField->field_default;
							$ok = true;
						}
						if($country_id) {
							$zoneType = new k2storeCountryType();
							$zoneType->type = 'zone';
							$zoneType->published = true;
							//$zoneType->country_name = $oneField->field_default;
							$zoneType->country_id = $oneField->field_default;
							$zones = $zoneType->load();
							$this->setValues($zones,$fields,$k,$field);
							break;
						}

					}

				}
			}
		}
		if(!empty($types)){
			$zoneType = new k2storeCountryType();
			$zoneType->type = 'country';
			$zoneType->published = true;
			$zones = $zoneType->load();
			if(!empty($zones)){
				foreach($fields as $k => $field){
					$this->setValues($zones,$fields,$k,$field);
				}
			}
		}
	}


	function setValues(&$zones,&$fields,$k,&$field){

		if($field->field_type=='zone' && !empty($field->field_options['zone_type']) && $field->field_options['zone_type']=='country'){
			foreach($zones as $zone){
				$title = $zone->country_name;
				$obj = new stdClass();
				$obj->value = $zone->country_name;
				$obj->disabled = '0';
				$fields[$k]->field_value[$zone->country_id]=$obj;
			}
		} elseif($field->field_type=='zone' && !empty($field->field_options['zone_type']) && $field->field_options['zone_type']=='zone'){
			foreach($zones as $zone){
				if(isset($zone->zone_id)) {
					$title = $zone->zone_name;
					$obj = new stdClass();
					$obj->value = $title;
					$obj->disabled = '0';
					$fields[$k]->field_value[$zone->zone_id]=$obj;
				}
			}

		}
	}

	function getCurrentURL($checkInRequest='',$safe=true){
		$app = JFactory::getApplication();
		$config = JFactory::getConfig();
		if(!empty($checkInRequest)){
			$url = $app->input->getString($checkInRequest,'');
			if(!empty($url)){
				if(strpos($url,'http')!==0&&strpos($url,'/')!==0){
					if($checkInRequest=='return_url'){
						$url = base64_decode(urldecode($url));
					}elseif($checkInRequest=='url'){
						$url = urldecode($url);
					}
				}
				if($safe){
					$url = str_replace(array('"',"'",'<','>',';'),array('%22','%27','%3C','%3E','%3B'),$url);
				}
				return $url;
			}
		}
		if(!empty($_SERVER["REDIRECT_URL"]) && preg_match('#.*index\.php$#',$_SERVER["REDIRECT_URL"]) && empty($_SERVER['QUERY_STRING'])&&empty($_SERVER['REDIRECT_QUERY_STRING']) && !empty($_SERVER["REQUEST_URI"])){
			$requestUri = $_SERVER["REQUEST_URI"];
		}elseif(!empty($_SERVER["REDIRECT_URL"]) && (isset($_SERVER['QUERY_STRING'])||isset($_SERVER['REDIRECT_QUERY_STRING']))){
			$requestUri = $_SERVER["REDIRECT_URL"];
			if (!empty($_SERVER['REDIRECT_QUERY_STRING'])) $requestUri = rtrim($requestUri,'/').'?'.$_SERVER['REDIRECT_QUERY_STRING'];
			elseif (!empty($_SERVER['QUERY_STRING'])) $requestUri = rtrim($requestUri,'/').'?'.$_SERVER['QUERY_STRING'];
		}elseif(isset($_SERVER["REQUEST_URI"])){
			$requestUri = $_SERVER["REQUEST_URI"];
		}else{
			$requestUri = $_SERVER['PHP_SELF'];
			if (!empty($_SERVER['QUERY_STRING'])) $requestUri = rtrim($requestUri,'/').'?'.$_SERVER['QUERY_STRING'];
		}
		$result = ( $config->get('force_ssl')? 'https://' : 'http://').$_SERVER["HTTP_HOST"].$requestUri;
		if($safe){
			$result = str_replace(array('"',"'",'<','>',';'),array('%22','%27','%3C','%3E','%3B'),$result);
		}
		return $result;
	}

	function getFields($area,&$data,$type='user',$url='checkout&task=state', $notcoreonly=false){
		$fields = $this->getData($area,$type, $notcoreonly);
		$this->prepareFields($fields,$data,$type,$url);
		return $fields;
	}

	/*
	 * @area string display area - billing or shipping or payment
	 * @type string field table type example: address
	 * @notcoreonly boolen true for core fields
	 */

	function &getData($area,$type,$notcoreonly=false){
		static $data = array();
		$key = $area.'_'.$type.'_'.$notcoreonly;

		if(empty($data[$key])){
			$this->where = array();
			$this->where[] = 'a.`published` = 1';
			if($area == 'register'){
				$this->where[] = 'a.`field_display_register` = 1';
			}elseif($area == 'billing'){
				$this->where[] = 'a.`field_display_billing` = 1';
			}elseif($area == 'shipping'){
				$this->where[] = 'a.`field_display_shipping` = 1';
			}elseif($area == 'guest'){
					$this->where[] = 'a.`field_display_guest` = 1';
			}elseif($area == 'guest_shipping'){
				$this->where[] = 'a.`field_display_guest_shipping` = 1';
			}elseif($area=='payment'){
				$this->where[] = 'a.`field_display_payment` = 1';
			}else{
				$db = JFactory::getDBO();
				$clauses = explode(';', trim($area,';'));
				foreach($clauses as $clause) {
					if(empty($clause))
						continue;

					$v = '=1';
					if(strpos($clause, '=') !== false) {
						list($clause,$v) = explode('=', $clause, 2);
						$v = '=' . (int)$v;
					}
					if(substr($clause, 0, 8) == 'display:') {
						$cond = substr($clause, 8) . $v;
						if(HIKASHOP_J25)
							$cond = $db->escape($cond, true);
						else
							$cond = $db->getEscape($cond, true);
						$this->where[] = 'a.`field_display` LIKE \'%;'.$cond.';%\'';
					} else {
						if(HIKASHOP_J25)
							$this->where[] = 'a.' . $db->quoteName($clause) . $v;
						else
							$this->where[] = 'a.' . $db->nameQuote($clause) . $v;
					}
				}
			}
			if($notcoreonly){
				$this->where[] = 'a.`field_core` = 0';
			}

			$this->where[]='a.field_table='.$this->database->Quote($type);
			$filters='';

			//hikashop_addACLFilters($this->where,'field_access','a');
			$this->database->setQuery('SELECT * FROM #__k2store_field as a WHERE '.implode(' AND ',$this->where).' '.$filters.' ORDER BY a.`ordering` ASC');
			$data[$key] = $this->database->loadObjectList('field_namekey');

		}
		return $data[$key];
	}


	function getFieldName($field){
		$app = JFactory::getApplication();
		if($app->isAdmin()) return $this->translate($field->field_name);
		$html = '';
		if(!empty($field->field_required)) {
			$html .='<span class="k2store_field_required">*</span>';
		}

		return $html.='<label for="'.$this->prefix.$field->field_namekey.$this->suffix.'">'.$this->translate($field->field_name).'</label>';
	}

	function translate($name){
		$val = preg_replace('#[^a-z0-9]#i','_',strtoupper($name));
		$trans = JText::_($val);
		if($val==$trans){
			$trans = $name;
		}
		return $trans;
	}

	function get($field_id,$default=null){
		$query = 'SELECT a.* FROM #__k2store_field as a WHERE a.`field_id` = '.intval($field_id).' LIMIT 1';
		$this->database->setQuery($query);

		$field = $this->database->loadObject();
		if(!empty($field->field_options)){
			$field->field_options = unserialize($field->field_options);
		}

		if(!empty($field->field_value)){
			$field->field_value = $this->explodeValues($field->field_value);
		}

		return $field;
	}

	function explodeValues($values){
		$allValues = explode("\n",$values);
		$returnedValues = array();

		foreach($allValues as $id => $oneVal){
			$line = explode('::',trim($oneVal));
			$var = $line[0];
			$val = $line[1];
			if(count($line)==2){
				$disable = '0';
			}else{
				$disable = $line[2];
			}
			if(strlen($val)>0){
				$obj = new stdClass();
				$obj->value = $val;
				$obj->disabled = $disable;
				$returnedValues[$var] = $obj;
			}
		}
		return $returnedValues;
	}

	function _loadExternals() {
		if($this->externalValues == null) {
			$this->externalValues = array();
			JPluginHelper::importPlugin('k2store');
			$dispatcher = JDispatcher::getInstance();
			$dispatcher->trigger('onK2StoreTableFieldsLoad', array( &$this->externalValues ) );
			if(!empty($this->externalValues)) {
				foreach($this->externalValues as &$externalValue) {
					if(!empty($externalValue->table) && substr($externalValue->value, 0, 4) != 'plg.')
						$externalValue->value = 'plg.' . $externalValue->value;
					unset($externalValue);
				}
			}
		}
	}


	function _checkOneInput(&$fields,&$formData,&$data,$type,&$oldData){
		$ok = true;
		if(!empty($fields)){
			foreach($fields as $k => $field){
				$namekey = $field->field_namekey;
				if($field->field_type == "customtext"){
					if(isset($formData[$field->field_namekey])) unset($formData[$field->field_namekey]);
					continue;
				}


				$field_type = $field->field_type;
				if(substr($field->field_type,0,4) == 'plg.') {
					$field_type = substr($field->field_type,4);
					JPluginHelper::importPlugin('k2store', $field_type);
				}
				$classType = 'k2store'.ucfirst($field_type);
				$class = new $classType($this);
				$val = @$formData[$namekey];
				$error = $class->check($fields[$k],$val,@$oldData->$namekey);
				if(!empty($error)){
					$ok = false;
				}
				$formData[$namekey] = $val;
			}
		}
		$this->checkFields($formData,$data,$type,$fields);
		return $ok;
	}

	function checkFields(&$data,&$object,$type,&$fields){
		$app = JFactory::getApplication();
		static $safeHtmlFilter= null;
		if(is_null($object))$object=new stdClass();
		if($app->isAdmin()){
			if (is_null($safeHtmlFilter)) {
				jimport('joomla.filter.filterinput');
				$safeHtmlFilter = JFilterInput::getInstance(null, null, 1, 1);
			}
		}
		$noFilter = array();
		if(!empty($fields)) {
			foreach($fields as $field){
				if(isset($field->field_options['filtering']) && !$field->field_options['filtering']){
					$noFilter[]=$field->field_namekey;
				}
			}
		}
		if(!empty($data) && is_array($data)){
			foreach($data as $column => $value){
				$column = trim(strtolower($column));
				if($this->allowed($column,$type)){
					k2storeSelectableHelper::secureField($column);

					if(is_array($value)){
						$arrayColumn = false;
						if(substr($type, 0, 4) == 'plg.') {
							$this->_loadExternals();
							foreach($this->externalValues as $externalValue) {
								if($externalValue->value == $type && !empty($externalValue->arrayColumns)) {
									$arrayColumn = in_array($column, $externalValue->arrayColumns);
									break;
								}
							}
						}
						if( $arrayColumn || ($type=='user' && $column=='user_params') || ($type=='order' && $app->isAdmin() && in_array($column,array('history','mail','product'))) ) {
							$object->$column = new stdClass();
							foreach($value as $c => $v){
								$c = trim(strtolower($c));
								if($this->allowed($c,$type)){
									k2storeSelectableHelper::secureField($c);
									$object->$column->$c = in_array($c,$noFilter) ? $v : strip_tags($v);
								}
							}
						}else{
							$value = implode(',',$value);
							$object->$column = in_array($column,$noFilter) ? $value : strip_tags($value);
						}
					}elseif(is_null($safeHtmlFilter)){
						$object->$column = in_array($column,$noFilter) ? $value : strip_tags($value);
					}else{
						$object->$column = in_array($column,$noFilter) ? $value : $safeHtmlFilter->clean($value, 'string');
					}
				}
			}
		}
	}

	function allowed($column,$type='user'){
		$restricted = array(
				'user'=>array('user_partner_price'=>1,'user_partner_paid'=>1,'user_created_ip'=>1,'user_partner_id'=>1,'user_partner_lead_fee'=>1,'user_partner_click_fee'=>1,'user_partner_percent_fee'=>1,'user_partner_flat_fee'=>1),
				'order'=>array('order_id'=>1,'order_billing_address_id'=>1,'order_shipping_address_id'=>1,'order_user_id'=>1,'order_status'=>1,'order_discount_code'=>1,'order_created'=>1,'order_ip'=>1,'order_currency_id'=>1,'order_status'=>1,'order_shipping_price'=>1,'order_discount_price'=>1,'order_shipping_id'=>1,'order_shipping_method'=>1,'order_payment_id'=>1,'order_payment_method'=>1,'order_full_price'=>1,'order_modified'=>1,'order_partner_id'=>1,'order_partner_price'=>1,'order_partner_paid'=>1,'order_type'=>1,'order_partner_currency_id'=>1)
		);
		if(substr($type, 0, 4) == 'plg.') {
			$this->_loadExternals();
		}

		if(isset($restricted[$type][$column])){
			$app = JFactory::getApplication();
			if(!$app->isAdmin()){
				return false;
			}
		}
		return true;
	}

	function save() {

		$app = JFactory::getApplication();
		$field_id = $app->input->getInt('field_id');
		$formData = $app->input->get('data', array(), 'ARRAY');

		//initialise a object
		$field = new JObject();
		$field->field_id = $field_id;

		foreach($formData['field'] as $column => $value){
			k2storeSelectableHelper::secureField($column);
			if($column == 'field_default') {
				continue;
			} else {
				if(is_array($value)) $value = implode(',',$value);
				$field->$column = strip_tags($value);
			}
		}

		$fields = array( &$field );
		if(isset($field->field_namekey)) { $namekey = $field->field_namekey; }
		$field->field_namekey = 'field_default';
		if($this->_checkOneInput($fields,$formData['field'], $data, '', $oldData)) {
			if(isset($formData['field']['field_default']) && is_array($formData['field']['field_default'])){
				$defaultValue = '';
				foreach($formData['field']['field_default'] as $value){
					if(empty($defaultValue)){
						$defaultValue .= $value;
					}else{
						$defaultValue .= ",".$value;
					}
				}
				$field->field_default = strip_tags($defaultValue);
			}else{
				$field->field_default = @strip_tags($formData['field']['field_default']);
			}
		}
		unset($field->field_namekey);
		if(isset($namekey)) { $field->field_namekey = $namekey; }

		$fieldOptions = $app->input->get('field_options', array(), 'array');
		foreach($fieldOptions as $column => $value){
			if(is_array($value)){
				foreach($value as $id => $val){
					k2storeSelectableHelper::secureField($val);
					$fieldOptions[$column][$id] = strip_tags($val);
				}
			}else{
				$fieldOptions[$column] = strip_tags($value);
			}
		}

		if($field->field_type == "customtext"){
			$fieldOptions['customtext'] = $app->input->getHtml('fieldcustomtext','');
			if(empty($field->field_id)){
				$field->field_namekey = 'customtext_'.date('z_G_i_s');
			}else{
				$oldField = $this->get($field->field_id);
				if($oldField->field_core){
					$field->field_type=$oldField->field_type;
				}
			}
		}

		$field->field_options = serialize($fieldOptions);

		$fieldValues = $app->input->get('field_values', array(), 'array' );
		if(!empty($fieldValues)){
			$field->field_value = array();
			foreach($fieldValues['title'] as $i => $title){
				if(strlen($title)<1 AND strlen($fieldValues['value'][$i])<1) continue;
				$value = strlen($fieldValues['value'][$i])<1 ? $title : $fieldValues['value'][$i];
				$disabled = strlen($fieldValues['disabled'][$i])<1 ? '0' : $fieldValues['disabled'][$i];
				$field->field_value[] = strip_tags($title).'::'.strip_tags($value).'::'.strip_tags($disabled);
			}
			$field->field_value = implode("\n",$field->field_value);
		}

		if(empty($field->field_id) && $field->field_type != 'customtext'){
			if(empty($field->field_namekey)) $field->field_namekey = $field->field_name;
			$field->field_namekey = preg_replace('#[^a-z0-9_]#i', '',strtolower($field->field_namekey));
			if(empty($field->field_namekey)){
				$this->errors[] = 'Please specify a namekey';
				return false;
			}
			if($field->field_namekey > 50){
				$this->errors[] = 'Please specify a shorter column name';
				return false;
			}
			if(in_array(strtoupper($field->field_namekey),array(
					'ACCESSIBLE',
					'ADD',
					'ALL',
					'ALTER',
					'ANALYZE',
					'AND',
					'AS',
					'ASC',
					'ASENSITIVE',
					'BEFORE',
					'BETWEEN',
					'BIGINT',
					'BINARY',
					'BLOB',
					'BOTH',
					'BY',
					'CALL',
					'CASCADE',
					'CASE',
					'CHANGE',
					'CHAR',
					'CHARACTER',
					'CHECK',
					'COLLATE',
					'COLUMN',
					'CONDITION',
					'CONSTRAINT',
					'CONTINUE',
					'CONVERT',
					'CREATE',
					'CROSS',
					'CURRENT_DATE',
					'CURRENT_TIME',
					'CURRENT_TIMESTAMP',
					'CURRENT_USER',
					'CURSOR',
					'DATABASE',
					'DATABASES',
					'DAY_HOUR',
					'DAY_MICROSECOND',
					'DAY_MINUTE',
					'DAY_SECOND',
					'DEC',
					'DECIMAL',
					'DECLARE',
					'DEFAULT',
					'DELAYED',
					'DELETE',
					'DESC',
					'DESCRIBE',
					'DETERMINISTIC',
					'DISTINCT',
					'DISTINCTROW',
					'DIV',
					'DOUBLE',
					'DROP',
					'DUAL',
					'EACH',
					'ELSE',
					'ELSEIF',
					'ENCLOSED',
					'ESCAPED',
					'EXISTS',
					'EXIT',
					'EXPLAIN',
					'FALSE',
					'FETCH',
					'FLOAT',
					'FLOAT4',
					'FLOAT8',
					'FOR',
					'FORCE',
					'FOREIGN',
					'FROM',
					'FULLTEXT',
					'GRANT',
					'GROUP',
					'HAVING',
					'HIGH_PRIORITY',
					'HOUR_MICROSECOND',
					'HOUR_MINUTE',
					'HOUR_SECOND',
					'IF',
					'IGNORE',
					'IN',
					'INDEX',
					'INFILE',
					'INNER',
					'INOUT',
					'INSENSITIVE',
					'INSERT',
					'INT',
					'INT1',
					'INT2',
					'INT3',
					'INT4',
					'INT8',
					'INTEGER',
					'INTERVAL',
					'INTO',
					'IS',
					'ITERATE',
					'JOIN',
					'KEY',
					'KEYS',
					'KILL',
					'LEADING',
					'LEAVE',
					'LEFT',
					'LIKE',
					'LIMIT',
					'LINEAR',
					'LINES',
					'LOAD',
					'LOCALTIME',
					'LOCALTIMESTAMP',
					'LOCK',
					'LONG',
					'LONGBLOB',
					'LONGTEXT',
					'LOOP',
					'LOW_PRIORITY',
					'MASTER_SSL_VERIFY_SERVER_CERT',
					'MATCH',
					'MAXVALUE',
					'MEDIUMBLOB',
					'MEDIUMINT',
					'MEDIUMTEXT',
					'MIDDLEINT',
					'MINUTE_MICROSECOND',
					'MINUTE_SECOND',
					'MOD',
					'MODIFIES',
					'NATURAL',
					'NOT',
					'NO_WRITE_TO_BINLOG',
					'NULL',
					'NUMERIC',
					'ON',
					'OPTIMIZE',
					'OPTION',
					'OPTIONALLY',
					'OR',
					'ORDER',
					'OUT',
					'OUTER',
					'OUTFILE',
					'PRECISION',
					'PRIMARY',
					'PROCEDURE',
					'PURGE',
					'RANGE',
					'READ',
					'READS',
					'READ_WRITE',
					'REAL',
					'REFERENCES',
					'REGEXP',
					'RELEASE',
					'RENAME',
					'REPEAT',
					'REPLACE',
					'REQUIRE',
					'RESIGNAL',
					'RESTRICT',
					'RETURN',
					'REVOKE',
					'RIGHT',
					'RLIKE',
					'SCHEMA',
					'SCHEMAS',
					'SECOND_MICROSECOND',
					'SELECT',
					'SENSITIVE',
					'SEPARATOR',
					'SET',
					'SHOW',
					'SIGNAL',
					'SMALLINT',
					'SPATIAL',
					'SPECIFIC',
					'SQL',
					'SQLEXCEPTION',
					'SQLSTATE',
					'SQLWARNING',
					'SQL_BIG_RESULT',
					'SQL_CALC_FOUND_ROWS',
					'SQL_SMALL_RESULT',
					'SSL',
					'STARTING',
					'STRAIGHT_JOIN',
					'TABLE',
					'TERMINATED',
					'THEN',
					'TINYBLOB',
					'TINYINT',
					'TINYTEXT',
					'TO',
					'TRAILING',
					'TRIGGER',
					'TRUE',
					'UNDO',
					'UNION',
					'UNIQUE',
					'UNLOCK',
					'UNSIGNED',
					'UPDATE',
					'USAGE',
					'USE',
					'USING',
					'UTC_DATE',
					'UTC_TIME',
					'UTC_TIMESTAMP',
					'VALUES',
					'VARBINARY',
					'VARCHAR',
					'VARCHARACTER',
					'VARYING',
					'WHEN',
					'WHERE',
					'WHILE',
					'WITH',
					'WRITE',
					'XOR',
					'YEAR_MONTH',
					'ZEROFILL',
					'GENERAL',
					'IGNORE_SERVER_IDS',
					'MASTER_HEARTBEAT_PERIOD',
					'MAXVALUE',
					'RESIGNAL',
					'SIGNAL',
					'SLOW',
					'ALIAS',
					'OPTIONS',
					'RELATED',
					'IMAGES',
					'FILES',
					'CATEGORIES',
					'PRICES',
					'VARIANTS',
					'CHARACTERISTICS')))
			{
				$this->errors[] = 'The column name "'.$field->field_namekey.'" is reserved. Please use another one.';
				return false;
			}
			 $tables = array($field->field_table);
		 	foreach($tables as $table_name){
					$columns = $this->database->getTableColumns($this->fieldTable($table_name));

				if(isset($columns[$field->field_namekey])){
					$this->errors[] = 'The field "'.$field->field_namekey.'" already exists in the table "'.$table_name.'"';
					return false;
				}
			}
			foreach($tables as $table_name){
				$query = 'ALTER TABLE '.$this->fieldTable($table_name).' ADD `'.$field->field_namekey.'` TEXT NULL';
				$this->database->setQuery($query);
				$this->database->query();
			}

		}
		$this->fielddata = $field;
		return true;

	}

	function fieldTable($table_name) {
		if(substr($table_name, 0, 4) == 'plg.') {
			$this->_loadExternals();
			$table_name = substr($table_name, 4);
			foreach($this->externalValues as $name => $externalValue) {
				if($name == $table_name) {
					if(!empty($externalValue->table))
						return 	$externalValue->table;
					break;
				}
			}
		}
		return $this->k2storeTable($table_name);
	}

	function k2storeTable($name,$component = true){
		$prefix = '#__k2store_';
		return $prefix.$name;
	}

}

class k2storeFieldItem {

	var $prefix;
	var $suffix;
	var $excludeValue;
	var $report;
	var $parent;

	function __construct(&$obj){
		$this->prefix = $obj->prefix;
		$this->suffix = $obj->suffix;
		$this->excludeValue =& $obj->excludeValue;
		$this->report = @$obj->report;
		$this->parent =& $obj;
	}

	function translate($name){
		$val = preg_replace('#[^a-z0-9]#i','_',strtoupper($name));
		$trans = JText::_($val);
		if($val==$trans){
			$trans = $name;
		}
		return $trans;
	}


	function check(&$field,&$value, $oldvalue){
		$error = '';
		if(!$field->field_required || is_array($value) || strlen($value) || strlen($oldvalue)){
			return $error;
		}

		if($this->report){

			$app = JFactory::getApplication();
			if(!$app->isAdmin()) {
				if(!empty($field->field_options['errormessage'])){
					$error = addslashes($this->translate($field->field_options['errormessage']));
				} else {
					$error = JText::sprintf('K2STORE_FIELD_REQUIRED',$this->translate($field->field_name));
				}
			}
		}
		return $error;
	}

	function display($field, $value, $name, $translate, $options = '', $test = false, $allFields = null, $allValues = null) { return $value; }

	function show(&$field,$value){
		return $this->translate($value);
	}
}

class k2storeText extends k2storeFieldItem {

	var $type = 'text';
	var $class = 'inputbox';

	function display($field, $value, $name, $translate, $options = '', $test = false, $allFields = null, $allValues = null) {

		$size = empty($field->field_options['size']) ? '' : 'size="'.intval($field->field_options['size']).'"';
		$size .= empty($field->field_options['maxlength']) ? '' : ' maxlength="'.intval($field->field_options['maxlength']).'"';
		$size .= empty($field->field_options['readonly']) ? '' : ' readonly="readonly"';
		$js = '';
		if($translate) {
			$value = addslashes($this->translate($field->field_name));
		}
		return '<input class="'.$this->class.'" id="'.$this->prefix.$field->field_namekey.$this->suffix.'" '.$size.' '.$js.' '.$options.' type="'.$this->type.'" name="'.$name.'" value="'.$value.'" />';

	}

	function show(&$field,$value){

		if($field->field_table=='address') return $value;
		return $this->translate($value);
	}

}


class k2storeEmail extends k2storeText {
	function check(&$field,&$value,$oldvalue){
		$error = '';
		if(!$field->field_required || is_array($value)){
			return $error;
		}

		if (filter_var($value, FILTER_VALIDATE_EMAIL) == false) {
			$error = JText::_('K2STORE_VALIDATION_ENTER_VALID_EMAIL');
		} else {
			return $error;
		}

		if($this->report){
			$app = JFactory::getApplication();
			if(!$app->isAdmin()) {
				if(!empty($field->field_options['errormessage'])){
					$error = addslashes($this->translate($field->field_options['errormessage']));
				} else {
					$error = JText::sprintf('PLEASE_FILL_THE_FIELD',$this->translate($field->field_name));
				}
			}
		}
		$return = array();
		$return[$field->field_namekey] = $error;
		return $error;
	}

}

class k2storeLink extends k2storeText{
	function show(&$field,$value){
		return '<a href="'.$this->translate($value).'">'.$this->translate($value).'</a>';
	}
}


class k2storeTextarea extends k2storeFieldItem {
	function display($field, $value, $name, $translate, $options = '', $test = false, $allFields = null, $allValues = null){
		$js = '';
		$html = '';
		if($translate && strlen($value) < 1){
			$value = addslashes($this->translate($field->field_name));
			$this->excludeValue[$field->field_namekey] = $value;
			$js = 'onfocus="if(this.value == \''.$value.'\') this.value = \'\';" onblur="if(this.value==\'\') this.value=\''.$value.'\';"';
		}
		if(!empty($field->field_options['maxlength'])){
			static $done = false;
			if(!$done){
				$jsFunc='
				<script type="text/javascript">
				function k2storeTextCounter(textarea, counterID, maxLen) {
					cnt = document.getElementById(counterID);
					if (textarea.value.length > maxLen){
						textarea.value = textarea.value.substring(0,maxLen);
					}
					cnt.innerHTML = maxLen - textarea.value.length;
				}
				</script>
				';

				//$doc = JFactory::getDocument();

				//$doc->addScriptDeclaration( "<!--\n".$jsFunc."\n//-->\n" );
				$html .= $jsFunc;
				$html.= '<span class="k2store_remaining_characters">'.JText::sprintf('K2STORE_X_CHARACTERS_REMAINING',$this->prefix.@$field->field_namekey.$this->suffix.'_count',(int)$field->field_options['maxlength']).'</span>';
			}
			$js .= ' onKeyUp="k2storeTextCounter(this,\''.$this->prefix.@$field->field_namekey.$this->suffix.'_count'.'\','.(int)$field->field_options['maxlength'].');" onBlur="k2storeTextCounter(this,\''.$this->prefix.@$field->field_namekey.$this->suffix.'_count'.'\','.(int)$field->field_options['maxlength'].');" ';
		}

		$cols = empty($field->field_options['cols']) ? '' : 'cols="'.intval($field->field_options['cols']).'"';
		$rows = empty($field->field_options['rows']) ? '' : 'rows="'.intval($field->field_options['rows']).'"';
		$options .= empty($field->field_options['readonly']) ? '' : ' readonly="readonly"';
		return '<textarea class="inputbox" id="'.$this->prefix.@$field->field_namekey.$this->suffix.'" name="'.$name.'" '.$cols.' '.$rows.' '.$js.' '.$options.'>'.$value.'</textarea>'.$html;
	}

	function show(&$field,$value){
		return nl2br(parent::show($field,$value));
	}
}


class k2storeWysiwyg extends k2storeTextarea {
	function display($field, $value, $map, $inside, $options = '', $test = false, $allFields = null, $allValues = null){
		$editorHelper = k2storeSelectableHelper::getEditor();
		$editorHelper->name = $map;
		$editorHelper->content = $value;
		$editorHelper->id = $this->prefix.@$field->field_namekey.$this->suffix;
		$editorHelper->width = '100%';
		$editorHelper->cols = empty($field->field_options['cols']) ? 50 : intval($field->field_options['cols']);
		$editorHelper->rows = empty($field->field_options['rows']) ? 10 : intval($field->field_options['rows']);

		return $editorHelper->display();

		$js = '';
		$html = '';
		if($inside && strlen($value) < 1){
			$value = addslashes($this->translate($field->field_name));
			$this->excludeValue[$field->field_namekey] = $value;
			$js = 'onfocus="if(this.value == \''.$value.'\') this.value = \'\';" onblur="if(this.value==\'\') this.value=\''.$value.'\';"';
		}
		if(!empty($field->field_options['maxlength'])){
			static $done = false;
			if(!$done){
				$jsFunc='
				function k2storeTextCounter(textarea, counterID, maxLen) {
				cnt = document.getElementById(counterID);
				if (textarea.value.length > maxLen){
				textarea.value = textarea.value.substring(0,maxLen);
			}
			cnt.innerHTML = maxLen - textarea.value.length;
			}';
			//	$doc = JFactory::getDocument();
			//	$doc->addScriptDeclaration( "<!--\n".$jsFunc."\n//-->\n" );
				$html .= $jsFunc;
				$html.= '<span class="k2store_remaining_characters">'.JText::sprintf('K2STORE_X_CHARACTERS_REMAINING',$this->prefix.@$field->field_namekey.$this->suffix.'_count',(int)$field->field_options['maxlength']).'</span>';
			}
			$js .= ' onKeyUp="k2storeTextCounter(this,\''.$this->prefix.@$field->field_namekey.$this->suffix.'_count'.'\','.(int)$field->field_options['maxlength'].');" onBlur="k2storeTextCounter(this,\''.$this->prefix.@$field->field_namekey.$this->suffix.'_count'.'\','.(int)$field->field_options['maxlength'].');" ';
		}

		$cols = empty($field->field_options['cols']) ? '' : 'cols="'.intval($field->field_options['cols']).'"';
		$rows = empty($field->field_options['rows']) ? '' : 'rows="'.intval($field->field_options['rows']).'"';
		$options .= empty($field->field_options['readonly']) ? '' : ' readonly="readonly"';
		return '<textarea class="inputbox" id="'.$this->prefix.@$field->field_namekey.$this->suffix.'" name="'.$map.'" '.$cols.' '.$rows.' '.$js.' '.$options.'>'.$value.'</textarea>'.$html;
	}
	function show(&$field,$value){
		return $this->translate($value);
	}
}


class k2storeCustomtext extends k2storeFieldItem{
	function display($field, $value, $map, $inside, $options = '', $test = false, $allFields = null, $allValues = null){
		return $this->translate($field->field_options['customtext']);
	}
}


class k2storeDropdown extends k2storeFieldItem{
	var $type = '';
	function show(&$field,$value){
		if(!empty($field->field_value) && !is_array($field->field_value)){
			$field->field_value = $this->parent->explodeValues($field->field_value);
		}
		if(isset($field->field_value[$value])) $value = $field->field_value[$value]->value;
		return parent::show($field,$value);
	}

	function display($field, $value, $map, $inside, $options = '', $test = false, $allFields = null, $allValues = null){
		$string = '';
		if(!empty($field->field_value) && !is_array($field->field_value)){
			$field->field_value = $this->parent->explodeValues($field->field_value);
		}
		if(empty($field->field_value) || !count($field->field_value)){
			return '<input type="hidden" name="'.$map.'" value="" />';
		}
		if($this->type == "multiple"){
			$string.= '<input type="hidden" name="'.$map.'" value="" />';
			$map.='[]';
			$arg = 'multiple="multiple"';
			if(!empty($field->field_options['size'])) $arg .= ' size="'.intval($field->field_options['size']).'"';
		}else{
			$arg = 'size="1"';
			if(is_string($value)&& empty($value) && !empty($field->field_value)){
				$found = false;
				$first = false;
				foreach($field->field_value as $oneValue => $title){
					if($first===false){
						$first=$oneValue;
					}
					if($oneValue==$value){
						$found = true;
						break;
					}
				}
				if(!$found){
					$value = $first;
				}
			}
		}
		$string .= '<select id="'.$this->prefix.$field->field_namekey.$this->suffix.'" name="'.$map.'" '.$arg.$options.'>';
		if(empty($field->field_value))
			return $string.'</select>';

		$app = JFactory::getApplication();
		$admin = $app->isAdmin();

		foreach($field->field_value as $oneValue => $title){
			$selected = ((int)$title->disabled && !$admin) ? 'disabled="disabled" ' : '';
			$selected .= ((is_numeric($value) && is_numeric($oneValue) AND $oneValue == $value) || (is_string($value) && $oneValue === $value) || is_array($value) && in_array($oneValue,$value)) ? 'selected="selected" ' : '';
			$id = $this->prefix.$field->field_namekey.$this->suffix.'_'.$oneValue;
			$string .= '<option value="'.$oneValue.'" id="'.$id.'" '.$selected.'>'.$this->translate($title->value).'</option>';
		}
		$string .= '</select>';

		return $string;
	}
}

class k2storeSingledropdown extends k2storeDropdown{
	var $type = 'single';
	function display($field, $value, $map, $inside, $options = '', $test = false, $allFields = null, $allValues = null){
		return parent::display($field,$value,$map,$inside,$options,$test,$allFields,$allValues);
	}
}

class k2storeMultipledropdown extends k2storeDropdown{
	var $type = 'multiple';
	function display($field, $value, $map, $inside, $options = '', $test = false, $allFields = null, $allValues = null){
		$value = explode(',',$value);
		return parent::display($field,$value,$map,$inside,$options,$test,$allFields,$allValues);
	}
	function show(&$field,$value){
		if(!is_array($value)){
			$value = explode(',',$value);
		}
		if(!empty($field->field_value) && !is_array($field->field_value)){
			$field->field_value = $this->parent->explodeValues($field->field_value);
		}
		$results = array();
		foreach($value as $val){
			if(isset($field->field_value[$val])) $val = $field->field_value[$val]->value;
			$results[]= parent::show($field,$val);
		}
		return implode(', ',$results);
	}
}

class k2storeZone extends k2storeSingledropdown{

	function display($field, $value, $map, $inside, $options = '', $test = false, $allFields = null, $allValues = null){

		$app = JFactory::getApplication();
		require_once (JPATH_SITE.'/components/com_k2store/helpers/cart.php');
		$store = K2StoreHelperCart::getStoreAddress();

		$stateId = $currentZoneId = ($store->zone_id > 0)?$store->zone_id:'';
		$country_id = ($store->country_id > 0)?$store->country_id:'';

		//if no default value was set in the fields, then use the country id set in the store profile.
		if(empty($field->field_default)) {
			$defaultCountry = $country_id;
		}

		if(empty($value)) {
			$value = $field->field_default;
		}

		if($field->field_options['zone_type']=='country'){
			if(isset($defaultCountry)){
				$field->field_default = $defaultCountry;
			}


		} elseif($field->field_options['zone_type']=='zone') {
			$stateId = str_replace(array('[',']'),array('_',''),$map);
			$dropdown = '';
			if($allFields != null) {
				$country = null;
				foreach($allFields as $f) {
					if($f->field_type=='zone' && !empty($f->field_options['zone_type']) && $f->field_options['zone_type']=='country') {
						$key = $f->field_namekey;
						if(!empty($allValues->$key)) {
							$country = $allValues->$key;
						} else {
							$country = $f->field_default;
						}
						break;
					}
				}

				//no country id, then load it based on the zone default.

				if(empty($country) && isset($field->field_default)) {
					JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2store/tables');
					$table = JTable::getInstance('zone', 'Table');
					if($table->load($field->field_default)) {
						$country = $table->country_id;
					}
				}

				//still no. Set it to store default.

				if(empty($country)){
					$country = $store->country_id;
				}


				if(!empty($country)) {
					$countryType = new k2storeCountryType();
					$countryType->type = 'zone';
					$countryType->country_id = $country;
					$countryType->published = true;
					$dropdown = $countryType->displayZone($map, $value, true);
				}
			}
			$html= '<span id="'.$stateId.'_container">'.$dropdown.'</span>'.
					'<input type="hidden" id="'.$stateId.'_default_value" name="'.$stateId.'_default_value" value="'.$value.'"/>';
			return $html;
		}
		return parent::display($field,$value,$map,$inside,$options,$test,$allFields,$allValues);
	}

	function JSCheck(&$oneField,&$requiredFields,&$validMessages,&$values){
	}
}



class k2storeRadioCheck extends k2storeFieldItem {
	var $radioType = 'checkbox';
	function show(&$field,$value) {
		if(!empty($field->field_value) && !is_array($field->field_value)){
			$field->field_value = $this->parent->explodeValues($field->field_value);
		}
		if(isset($field->field_value[$value])) $value = $field->field_value[$value]->value;
		return parent::show($field,$value);
	}

	function display($field, $value, $map, $inside, $options = '', $test = false, $allFields = null, $allValues = null){
		$type = $this->radioType;
		$string = '<div id="'.$field->field_namekey.'">';
		if($inside) $string = $this->translate($field->field_name).' ';
		if($type == 'checkbox'){
			$string.= '<input type="hidden" name="'.$map.'" value=""/>';
			$map.='[]';
		}
		if(empty($field->field_value)) return $string;
		$app = JFactory::getApplication();
		$admin = $app->isAdmin();

		foreach($field->field_value as $oneValue => $title){
			$checked = ((int)$title->disabled && !$admin) ? 'disabled="disabled" ' : '';
			$checked .= ((is_string($value) && $oneValue == $value) || is_array($value) && in_array($oneValue,$value)) ? 'checked="checked" ' : '';
			$id = $this->prefix.$field->field_namekey.$this->suffix.'_'.$oneValue;
			$string .= '<input type="'.$type.'" name="'.$map.'" value="'.$oneValue.'" id="'.$id.'" '.$checked.' '.$options.' /><label for="'.$id.'">'.$this->translate($title->value).'</label>';
		}
		$string .='</div>';
		return $string;
	}
}

class k2storeRadio extends k2storeRadioCheck {
	var $radioType = 'radio';
	function display($field, $value, $map, $inside, $options = '', $test = false, $allFields = null, $allValues = null){
		return parent::display($field,$value,$map,$inside,$options,$test,$allFields,$allValues);
	}
}

class k2storeCheckbox extends k2storeRadioCheck {
	var $radioType = 'checkbox';
	function display($field, $value, $map, $inside, $options = '', $test = false, $allFields = null, $allValues = null){
		if(!is_array($value)){
			$value = explode(',',$value);
		}
		return parent::display($field,$value,$map,$inside,$options,$test,$allFields,$allValues);
	}
	function show(&$field,$value){
		if(!is_array($value)){
			$value = explode(',',$value);
		}
		if(!empty($field->field_value) && !is_array($field->field_value)){
			$field->field_value = $this->parent->explodeValues($field->field_value);
		}
		$results = array();
		foreach($value as $val){
			if(isset($field->field_value[$val])) $val = $field->field_value[$val]->value;
			$results[]= parent::show($field,$val);
		}
		return implode(', ',$results);
	}

	function check(&$field,&$value,$oldvalue){
		$error = '';
		if(!$field->field_required || is_array($value)){
			return $error;
		}

		$app = JFactory::getApplication();
			if(!$app->isAdmin()) {
				if(!empty($field->field_options['errormessage'])){
					$error = addslashes($this->translate($field->field_options['errormessage']));
				} else {
					$error = JText::sprintf('K2STORE_FIELD_REQUIRED',$this->translate($field->field_name));
				}
			}

		return $error;
	}

}

class k2storeDate extends k2storeText{
	function display($field, $value, $map, $inside, $options = '', $test = false, $allFields = null, $allValues = null){

		if(empty($field->field_options['format'])) $field->field_options['format'] = "yy-mm-dd";
		$format = $field->field_options['format'];
		$size = $options . empty($field->field_options['size']) ? '' : ' size="'.$field->field_options['size'].'"';

		$isAdmin = false;
		if(JFactory::getApplication()->isAdmin()) {
			$isAdmin = true;
		}

		require_once (JPATH_ADMINISTRATOR.'/components/com_k2store/helpers/strapper.php');

		$timepicker_script = K2StoreStrapper::getTimePickerScript($format, '', $map, $isAdmin);

		$script='<script type="text/javascript">'.$timepicker_script.'</script>';

		$this->class = $map.'_date';
		//$html ='<input class="'.$this->class.'" id="'.$this->prefix.$field->field_namekey.$this->suffix.'" '.$size.' '.$js.' '.$options.' type="'.$this->type.'" name="'.$name.'" value="'.$value.'" />';
		$html = parent::display($field, $value, $map, $inside, $options, $test, $allFields, $allValues);
		return $script.$html ;
	}
}

class k2storeDateTime extends k2storeText{
	function display($field, $value, $map, $inside, $options = '', $test = false, $allFields = null, $allValues = null){

		if(empty($field->field_options['format'])) $field->field_options['format'] = "yy-mm-dd | HH:mm";
		$format = $field->field_options['format'];
		$size = $options . empty($field->field_options['size']) ? '' : ' size="'.$field->field_options['size'].'"';

		$format_array = explode('|', $format);

		$isAdmin = false;
		if(JFactory::getApplication()->isAdmin()) {
			$isAdmin = true;
		}
		require_once (JPATH_ADMINISTRATOR.'/components/com_k2store/helpers/strapper.php');

		$timepicker_script = K2StoreStrapper::getTimePickerScript($format_array[0], $format_array[1], $map, $isAdmin);

		$script='<script type="text/javascript">'.$timepicker_script.'</script>';

		$this->class = $map.'_datetime';
		//$html ='<input class="'.$this->class.'" id="'.$this->prefix.$field->field_namekey.$this->suffix.'" '.$size.' '.$js.' '.$options.' type="'.$this->type.'" name="'.$name.'" value="'.$value.'" />';
		$html = parent::display($field, $value, $map, $inside, $options, $test, $allFields, $allValues);
		return $script.$html ;
	}
}

class k2storeTime extends k2storeText{
	function display($field, $value, $map, $inside, $options = '', $test = false, $allFields = null, $allValues = null){

		if(empty($field->field_options['format'])) $field->field_options['format'] = "HH:mm";
		$format = $field->field_options['format'];
		$size = $options . empty($field->field_options['size']) ? '' : ' size="'.$field->field_options['size'].'"';

		$isAdmin = false;
		if(JFactory::getApplication()->isAdmin()) {
			$isAdmin = true;
		}
		require_once (JPATH_ADMINISTRATOR.'/components/com_k2store/helpers/strapper.php');

		$timepicker_script = K2StoreStrapper::getTimePickerScript('', $format, $map, $isAdmin);

		$script='<script type="text/javascript">'.$timepicker_script.'</script>';

		$this->class = $map.'_time';
		//$html ='<input class="'.$this->class.'" id="'.$this->prefix.$field->field_namekey.$this->suffix.'" '.$size.' '.$js.' '.$options.' type="'.$this->type.'" name="'.$name.'" value="'.$value.'" />';
		$html = parent::display($field, $value, $map, $inside, $options, $test, $allFields, $allValues);
		return $script.$html ;
	}
}

class k2storeSelectableHelper {

	public static function secureField($fieldName){
		if (!is_string($fieldName) || preg_match('|[^a-z0-9#_.-]|i',$fieldName) !== 0 ){
			die('field "'.$fieldName .'" not secured');
		}
		return $fieldName;
	}

	public static function getEditor() {

		$editor = new k2storeEditorHelper();
		return $editor;

	}

}

class k2storeEditorHelper{
	var $width = '100%';
	var $height = '500';
	var $cols = 100;
	var $rows = 20;
	var $editor = null;
	var $name = '';
	var $content = '';
	var $id = 'jform_articletext';
	function k2storeEditorHelper(){
		$this->setEditor();
		$this->options = array('pagebreak');
	}

	function setDescription(){
		$this->width = 700;
		$this->height = 200;
		$this->cols = 80;
		$this->rows = 10;
	}

	function setContent($var){
		$name = $this->myEditor->get('_name');
		$function = "try{".$this->myEditor->setContent($this->name,$var)." }catch(err){alert('Error using the setContent function of the wysiwyg editor')}";
		if(!empty($name)){
			if($name == 'jce'){
				return " try{JContentEditor.setContent('".$this->name."', $var ); }catch(err){try{WFEditor.setContent('".$this->name."', $var )}catch(err){".$function."} }";
			}
			if($name == 'fckeditor'){
				return " try{FCKeditorAPI.GetInstance('".$this->name."').SetHTML( $var ); }catch(err){".$function."} ";
			}
			if($name == 'jckeditor'){
				return " try{oEditor.setData(".$var.");}catch(err){(!oEditor) ? CKEDITOR.instances.".$this->name.".setData($var) : oEditor.insertHtml = " .  $var.'}';
			}
			if($name == 'ckeditor'){
				return " try{CKEDITOR.instances.".$this->name.".setData( $var ); }catch(err){".$function."} ";
			}
			if($name == 'artofeditor'){
				return " try{CKEDITOR.instances.".$this->name.".setData( $var ); }catch(err){".$function."} ";
			}
		}

		return $function;
	}

	function getContent(){
		return $this->myEditor->getContent($this->name);
	}
	function display(){
		return $this->myEditor->display( $this->name,  $this->content ,$this->width, $this->height, $this->cols, $this->rows,$this->options, $this->id ) ;

	}
	function jsCode(){
		return $this->myEditor->save( $this->name );
	}

	function displayCode($name,$content){
		if($this->hasCodeMirror()){
			$this->setEditor('codemirror');
		}else{
			$this->setEditor('none');
		}
		$this->myEditor->setContent($name,$content);

		return $this->myEditor->display( $name,  $content ,$this->width, $this->height, $this->cols, $this->rows,false,$this->id) ;

	}

	function setEditor($editor=''){
		if(empty($editor)){
			$config = JFactory::getConfig();
			$this->editor = $config->get('editor',null);
			if(empty($this->editor)) $this->editor = null;
		}else{
			$this->editor = $editor;
		}

		$this->myEditor = JFactory::getEditor($this->editor);

		$this->myEditor->initialise();
	}

	function hasCodeMirror(){
		static $has = null;
		if(!isset($has)){
			$query = 'SELECT element FROM #__extensions WHERE element=\'codemirror\' AND folder=\'editors\' AND enabled=1 AND type=\'plugin\'';
			$db = JFactory::getDBO();
			$db->setQuery($query);
			$editor = $db->loadResult();
			$has = !empty($editor);
		}
		return $has;
	}
}
