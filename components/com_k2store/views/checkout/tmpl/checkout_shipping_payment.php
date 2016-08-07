<?php
// no direct access
defined('_JEXEC') or die('Restricted access');
?>
<!-- SHIPPING METHOD -->
			<?php if($this->showShipping):?>
				<div class="k2store-shipping" id="shippingcost-pane">
					<div id="onCheckoutShipping_wrapper">
						<?php echo $this->shipping_method_form;?>
					</div>
				</div>
			<?php endif;?>
	<!-- SHIPPING METHOD END -->

<?php if($this->showPayment): ?>
<div id='onCheckoutPayment_wrapper'>
	<h3>
		<?php echo JText::_('K2STORE_SELECT_A_PAYMENT_METHOD'); ?>
	</h3>
	<?php
	if ($this->plugins)
	{
		foreach ($this->plugins as $plugin)
		{


			?>
	<input value="<?php echo $plugin->element; ?>"
		class="payment_plugin" name="payment_plugin" type="radio"
		onclick="k2storeGetPaymentForm('<?php echo $plugin->element; ?>', 'payment_form_div');"
		<?php echo (!empty($plugin->checked)) ? "checked" : ""; ?>
		title="<?php echo JText::_('K2STORE_SELECT_A_PAYMENT_METHOD'); ?>"
		/>

	<?php
	$params= new JRegistry;
	$params->loadString($plugin->params);
	$title = $params->get('display_name', '');
	if(!empty($title)) {
		echo JText::_($title);
	} else {
		echo JText::_($plugin->name );
	}
	?>
	<br />
	<?php
		}
	}
	?>

</div>
<div id='payment_form_div' style="padding-top: 10px;">
	<?php
	if (!empty($this->payment_form_div))
	{
		echo $this->payment_form_div;
	}
	?>

</div>
<div id='payment_error_div' style="padding-top: 10px;"></div>
<?php endif; ?>

<?php
//custom fields
$html = $this->storeProfile->store_payment_layout;

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


<h3>
	<?php echo JText::_('K2STORE_CUSTOMER_NOTE'); ?>
</h3>
<textarea name="customer_note" rows="3" cols="40"></textarea>
<?php if($this->params->get('show_terms', 1)):?>
<?php
$tos_link = JRoute::_('index.php?option=com_k2store&view=checkout&task=getTerms&k2item_id='.$this->params->get('termsid', ''));
?>
	<div id="checkbox_tos">
		<?php if($this->params->get('terms_display_type', 'link') =='checkbox' ):?>
			<label for="tos_check">
			<input type="checkbox" class="required" name="tos_check" title="<?php echo JText::_('K2STORE_AGREE_TO_TERMS_VALIDATION'); ?>" />
			 <div class="k2error"></div>
				<?php echo JText::_('K2STORE_TERMS_AND_CONDITIONS_LABEL'); ?>
				<?php if($this->params->get('termsid', '')): ?>
					<a href="<?php echo $tos_link; ?>" class="k2store-toggle-modal" target="_blank">
							<?php echo JText::_('K2STORE_TERMS_AND_CONDITIONS'); ?>
					</a>
				<?php else: ?>
					<?php echo JText::_('K2STORE_TERMS_AND_CONDITIONS'); ?>
				<?php endif; ?>
			</label>

		<?php else: ?>
			<?php echo JText::_('K2STORE_TERMS_AND_CONDITION_PRETEXT'); ?>
				<?php if($this->params->get('termsid', '')): ?>
					<a href="<?php echo $tos_link; ?>" class="k2store-toggle-modal" target="_blank">
							<?php echo JText::_('K2STORE_TERMS_AND_CONDITIONS'); ?>
					</a>
				<?php else: ?>
					<?php echo JText::_('K2STORE_TERMS_AND_CONDITIONS'); ?>
				<?php endif; ?>

	<?php endif;?>
	</div>
<?php endif; ?>

<script type="text/javascript">
(function($) {
	$(document).ready(function() {
		// Support for AJAX loaded modal window.
		$('a.k2store-toggle-modal').click(function(e) {
			e.preventDefault();
			var url = $(this).attr('href');
			if (url.indexOf('#') == 0) {
				$(url).modal('open');
			} else {
				$.get(url, function(data) {
					  $(data).modal().on('hidden', function(){
							 	$(this).data('modal', null);
					           $('.modal-backdrop.in').each(function(i) {
					               $(this).remove();
					           });
					           $('.k2store-modal').each(function(i) {
					               $(this).remove();
					           });
					}); //close hidden function
				});
			}
		});
	});
})(k2store.jQuery);

</script>
<input type="hidden" name="option" value="com_k2store" />
<input type="hidden" name="view" value="checkout" />
<input type="hidden" name="task" value="shipping_payment_method_validate" />
<div class="buttons">
  <div class="right">
    <input type="button" value="<?php echo JText::_('K2STORE_CHECKOUT_CONTINUE'); ?>" id="button-payment-method" class="button btn btn-primary" />
  </div>
</div>