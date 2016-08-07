<?php defined('_JEXEC') or die('Restricted access');
require_once (JPATH_ADMINISTRATOR.'/components/com_k2store/library/shipping.php');
require_once (JPATH_ADMINISTRATOR.'/components/com_k2store/library/prices.php');

?>
<?php $state = $vars->state; ?>
<?php $form = $vars->form; ?>
<?php $items = $vars->list;
?>
<div class="k2store">
<form action="<?php echo JRoute::_( @$form['action'] )?>" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">

	<table class="adminlist table table-striped" style="clear: both;">
		<thead>
            <tr>
				<th width="5"><?php echo JText::_( 'K2STORE_NUM' ); ?></th>
				<th width="20"><input type="checkbox" name="checkall-toggle"
					value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>"
					onclick="Joomla.checkAll(this)" />
				</th>
				<th class="title"><?php echo JText::_('K2STORE_SHIPM_ID'); ?></th>
				<th class="title"><?php echo JText::_('K2STORE_SHIPM_NAME'); ?></th>
                <th><?php echo JText::_('K2STORE_SFR_TAX_CLASS_NAME'); ?></th>
                <th><?php echo JText::_('K2STORE_SHIPM_STATE'); ?></th>
            </tr>
		</thead>
        <tfoot>
            <tr>
                <td colspan="20">
                    &nbsp;
                </td>
            </tr>
        </tfoot>
        <tbody>
        <?php
			$i = 0; $k=0;
			foreach($items as $item):
				$checked = JHTML::_('grid.id', $i, $item->shipping_method_id );
        	?>
            <tr class='row<?php echo $k; ?>'>
				<td align="center">
					<?php echo $i + 1; ?>
				</td>
				<td style="text-align: center;">
					<?php echo $checked; ?>
				</td>
				<td style="text-align: center;">
					<a href="<?php echo $item->link; ?>">
						<?php echo $item->shipping_method_id; ?>
					</a>
				</td>
				<td style="text-align: left;">
                    <a href="<?php echo $item->link; ?>">
                        <?php echo $item->shipping_method_name; ?>
                    </a>
                    <div class="shipping_rates">
                      	<?php
                        $id = JFactory::getApplication()->input->getInt('id', '0');
                        ?>
                        <span style="float: right;">
                        [<?php
                      	  echo K2StorePopup::popup( "index.php?option=com_k2store&view=shipping&task=view&id={$id}&shippingTask=setRates&tmpl=component&sid={$item->shipping_method_id}",JText::_('K2STORE_SHIPM_SET_RATES') ); ?>
                      	 ]</span>
                        <?php
                        if ($shipping_method_type = K2StoreShipping::getType($item->shipping_method_type))
                        {
                        	echo "<b>".JText::_('K2STORE_STANDARD_SHIPPING_TYPE')."</b>: ".$shipping_method_type->title;
                        }
                        if ($item->subtotal_minimum > '0')
                        {
                        	echo "<br/><b>".JText::_('K2STORE_SHIPPING_METHODS_MINIMUM_SUBTOTAL_REQUIRED')."</b>: ".K2StorePrices::number( $item->subtotal_minimum );
                        }
                        if( $item->subtotal_maximum > '-1' )
                        {
                        	echo "<br/><b>".JText::_('K2STORE_SHIPPING_METHODS_SUBTOTAL_MAX')."</b>: ".K2StorePrices::number( $item->subtotal_maximum );
                        }
                        ?>
                    </div>
				</td>
				<td style="text-align: center;">
				    <?php echo $item->taxprofile_name; ?>
				</td>
				<td style="text-align: center;">
					<?php if($item->published){
						$img_url = JUri::root(true).'/media/k2store/images/tick.png';
						$value = 0;
					} else {
						$img_url = JUri::root(true).'/media/k2store/images/publish_x.png';
						$value = 1;
					}
					?>
					<a href="#" onclick="k2storePublishMethod(<?php echo $item->shipping_method_id; ?>)">
						<img id="smid_<?php echo $item->shipping_method_id; ?>" src="<?php echo $img_url; ?>" alt="" />
					</a>
					<?php //echo $published 	= JHTML::_('grid.published', $item, $i ); ?>
				</td>
			</tr>
			<?php $i++; $k = (1 - $k); ?>
			<?php endforeach; ?>

			<?php if (!count($items)) : ?>
			<tr>
				<td colspan="10" align="center">
					<?php echo JText::_('K2STORE_NO_ITEMS_FOUND'); ?>
				</td>
			</tr>
			<?php endif; ?>
		</tbody>
	</table>

	<input type="hidden" name="order_change" value="0" />
	<input type="hidden" name="sid" value=" <?php echo $vars->sid; ?>" />
	<input type="hidden" name="shippingTask" value="_default" />
	<input type="hidden" name="task" value="view" />
	<input type="hidden" name="boxchecked" value="" />
	<input type="hidden" name="filter_order" value="<?php echo $state->order; ?>" />
	<input type="hidden" name="filter_direction" value="<?php echo $state->direction; ?>" />
	<?php echo JHTML::_( 'form.token' ); ?>

</form>
</div>
<?php
	$img_url = JUri::root(true).'/media/k2store/images/';
	?>
<script type="text/javascript">
<!--
function k2storePublishMethod(smid) {
	(function($) {
		var jqxhr = $.post(
				"index.php",
				{	option:'com_k2store',
					view:'shipping',
					task:'view',
					shippingTask:'publish',
					smid:smid,
					id:'<?php echo $vars->sid;?>'

				},
				"json"
			)
			.done(function(data) {
				if(data == 1) {
					$('#smid_'+smid).attr('src', '<?php echo $img_url?>/tick.png');
				} else {
					$('#smid_'+smid).attr('src', '<?php echo $img_url?>/publish_x.png');
				}

			})
			.fail(function() {})
			.always(function() {});

	})(k2store.jQuery);
}
//-->
</script>
