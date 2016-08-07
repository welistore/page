<?php
// no direct access
defined('_JEXEC') or die('Restricted access');
?>
<?php if ($this->addresses) { ?>
<input type="radio" name="billing_address" value="existing" id="billing-address-existing" checked="checked" />
<label for="billing-address-existing"><?php echo JText::_('K2STORE_ADDRESS_EXISTING'); ?></label>
<div id="billing-existing">
  <select name="address_id" style="width: 100%; margin-bottom: 15px;" size="5">
    <?php foreach ($this->addresses as $address) { ?>
    <?php if ($address['id'] == $this->address_id) { ?>
    <option value="<?php echo $address['id']; ?>" selected="selected"><?php echo $address['first_name']; ?> <?php echo $address['last_name']; ?>, <?php echo $address['address_1']; ?>, <?php echo $address['city']; ?>, <?php echo $address['zone_name']; ?>, <?php echo $address['country_name']; ?></option>
    <?php } else { ?>
    <option value="<?php echo $address['id']; ?>"><?php echo $address['first_name']; ?> <?php echo $address['last_name']; ?>, <?php echo $address['address_1']; ?>, <?php echo $address['city']; ?>, <?php echo $address['zone_name']; ?>, <?php echo $address['country_name']; ?></option>
    <?php } ?>
    <?php } ?>
  </select>
</div>
<p>
  <input type="radio" name="billing_address" value="new" id="billing-address-new" />
  <label for="billing-address-new"><?php echo JText::_('K2STORE_ADDRESS_NEW'); ?></label>
</p>
<?php } ?>
<div id="billing-new" style="display: <?php echo ($this->addresses ? 'none' : 'block'); ?>;">

<?php
$html = $this->storeProfile->store_billing_layout;

if(empty($html) || JString::strlen($html) < 5) {
	//we dont have a profile set in the store profile. So use the default one.
	$html = '<div class="row-fluid">
		<div class="span6">[first_name] [last_name] [phone_1] [phone_2] [company] [tax_number]</div>
		<div class="span6">[address_1] [address_2] [city] [zip] [country_id] [zone_id]</div>
		</div>';
}

//first find all the checkout fields
preg_match_all("^\[(.*?)\]^",$html,$checkoutFields, PREG_PATTERN_ORDER);

$allFields = $this->fields;
?>
  <?php foreach ($this->fields as $fieldName => $oneExtraField): ?>
						<?php
						$onWhat='onchange'; if($oneExtraField->field_type=='radio') $onWhat='onclick';
						//echo $this->fieldsClass->display($oneExtraField,@$this->address->$fieldName,$fieldName,false);
						if(property_exists($this->address, $fieldName)) {
						 	$html = str_replace('['.$fieldName.']',$this->fieldsClass->getFormatedDisplay($oneExtraField,$this->address->$fieldName, $fieldName,false, $options = '', $test = false, $allFields, $allValues = null),$html);
						}
						?>
  <?php endforeach; ?>


  <?php
  //check for unprocessed fields. If the user forgot to add the fields to the checkout layout in store profile, we probably have some.
  $unprocessedFields = array();
  foreach($this->fields as $fieldName => $oneExtraField) {
  	if(!in_array($fieldName, $checkoutFields[1])) {
  		$unprocessedFields[$fieldName] = $oneExtraField;
  	}
  }

    //now we have unprocessed fields. remove any other square brackets found.
  preg_match_all("^\[(.*?)\]^",$html,$removeFields, PREG_PATTERN_ORDER);
  foreach($removeFields[1] as $fieldName) {
  	$html = str_replace('['.$fieldName.']', '', $html);
  }

  ?>

  <?php echo $html; ?>
 <?php if(count($unprocessedFields)): ?>
 <div class="row-fluid">
  <div class="span12">
  <?php $uhtml = '';?>
 <?php foreach ($unprocessedFields as $fieldName => $oneExtraField): ?>
						<?php
						$onWhat='onchange'; if($oneExtraField->field_type=='radio') $onWhat='onclick';
						//echo $this->fieldsClass->display($oneExtraField,@$this->address->$fieldName,$fieldName,false);
						if(property_exists($this->address, $fieldName)) {
						 	$uhtml .= $this->fieldsClass->getFormatedDisplay($oneExtraField,$this->address->$fieldName, $fieldName,false, $options = '', $test = false, $allFields, $allValues = null);
						 	$uhtml .='<br />';
						}
						?>
  <?php endforeach; ?>
  <?php echo $uhtml; ?>
  </div>
</div>
<?php endif; ?>

</div>
<br />
<div class="buttons">
  <div class="right">
    <input type="button" value="<?php echo JText::_('K2STORE_CHECKOUT_CONTINUE'); ?>" id="button-billing-address" class="button btn btn-primary" />
  </div>
</div>
 <input type="hidden" name="task" value="billing_address_validate" />
  <input type="hidden" name="option" value="com_k2store" />
  <input type="hidden" name="view" value="checkout" />

<script type="text/javascript"><!--
(function($) {
$(document).on('change', '#billing-address input[name=\'billing_address\']', function() {
	if (this.value == 'new') {
		$('#billing-existing').hide();
		$('#billing-new').show();
	} else {
		$('#billing-existing').show();
		$('#billing-new').hide();
	}
});
})(k2store.jQuery);
//--></script>
<script type="text/javascript"><!--
(function($) {
$('#billing-address select[name=\'country_id\']').bind('change', function() {
	if (this.value == '') return;
	$.ajax({
		url: 'index.php?option=com_k2store&view=checkout&task=getCountry&country_id=' + this.value,
		dataType: 'json',
		beforeSend: function() {
			$('#billing-address select[name=\'country_id\']').after('<span class="wait">&nbsp;<img src="media/k2store/images/loader.gif" alt="" /></span>');
		},
		complete: function() {
			$('.wait').remove();
		},
		success: function(json) {
			if (json['postcode_required'] == '1') {
				$('#billing-postcode-required').show();
			} else {
				$('#billing-postcode-required').hide();
			}

			html = '<option value=""><?php echo JText::_('K2STORE_SELECT'); ?></option>';

			if (json['zone'] != '') {
				for (i = 0; i < json['zone'].length; i++) {
        			html += '<option value="' + json['zone'][i]['zone_id'] + '"';

					if (json['zone'][i]['zone_id'] == '<?php echo $this->zone_id; ?>') {
	      				html += ' selected="selected"';
	    			}

	    			html += '>' + json['zone'][i]['zone_name'] + '</option>';
				}
			} else {
				html += '<option value="0" selected="selected"><?php echo JText::_('K2STORE_CHECKOUT_NONE'); ?></option>';
			}

			$('#billing-address select[name=\'zone_id\']').html(html);
		},
		error: function(xhr, ajaxOptions, thrownError) {
			//alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});
});
})(k2store.jQuery);

(function($) {
	if($('#billing-address select[name=\'country_id\']').length > 0) {
		$('#billing-address select[name=\'country_id\']').trigger('change');
	}
})(k2store.jQuery);
//--></script>