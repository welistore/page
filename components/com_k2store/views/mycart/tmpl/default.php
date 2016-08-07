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

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
require_once(JPATH_ADMINISTRATOR.'/components/com_k2store/library/k2item.php');
require_once(JPATH_SITE.'/components/com_k2store/helpers/cart.php');
require_once (JPATH_ADMINISTRATOR.'/components/com_k2store/library/inventory.php');
$items = $this->cartobj;
//$mysubtotal = K2StoreHelperCart::getSubtotal();
//$state = $this->state;
$quantities = array();
$action = JRoute::_('index.php');
$storeAddress = K2StoreHelperCart::getStoreAddress();
if(isset($storeAddress->config_default_category) && $storeAddress->config_default_category != 0) {
require_once(JPATH_SITE.'/components/com_k2/helpers/route.php');
$continue_shopping_url = JRoute::_(K2HelperRoute::getCategoryRoute($storeAddress->config_default_category));
}
if(isset($storeAddress->config_continue_shopping_url ) && JString::strlen($storeAddress->config_continue_shopping_url ) > 5) {
$continue_shopping_url = $storeAddress->config_continue_shopping_url;
}

$checkout_url = JRoute::_('index.php?option=com_k2store&view=checkout');
?>
<div class="k2store">
<div class="row-fluid">
<div class="span12">
<?php if(!isset($this->remove)):?>
	<div id="k2storeCartPopup">
<?php endif; ?>

<div class='componentheading'>
    <span><?php echo JText::_( "K2STORE_MY_SHOPPING_CART" ); ?></span>
</div>

<div class="k2store_cartitems">
    <?php if (!empty($items)) { ?>
    <form action="<?php echo $action; ?>" method="post" name="adminForm" enctype="multipart/form-data">

        <table id="cart" class="adminlist table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th style="text-align: left;"><?php echo JText::_( "K2STORE_CART_ITEM" ); ?></th>
                    <th><?php echo JText::_( "K2STORE_CART_ITEM_QUANTITY" ); ?></th>
                    <th><?php echo JText::_( "K2STORE_CART_ITEM_TOTAL" ); ?></th>
                    <th><?php echo JText::_( "K2STORE_CART_ITEM_REMOVE" ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php $i=0; $k=0; $subtotal = 0;?>
            <?php foreach ($items as $item) : ?>

            	<?php
            		$link = K2StoreItem::getK2Link($item->product_id);
            		$link = JRoute::_($link);
            		$image_path = K2StoreItem::getK2Image($item->product_id, $this->params);
            	?>

                <tr class="row<?php echo $k; ?>">
                	<td>
                   <?php if($this->params->get('show_thumb_cart')) : ?>
                    <span class="pull-left k2store-cart-item-image">
                        <?php if(!empty($image_path)) : ?>
                        <img src="<?php echo $image_path; ?>" class="itemImg<?php echo $this->params->get('cartimage_size','small') ?>" />
                        <?php endif;?>
                     </span>
                    <?php endif; ?>
                        <a href="<?php echo $link; ?>">
                            <?php echo $item->product_name; ?>
                        </a>
                        <br/>

                        <?php if (!empty($item->product_options)) : ?>
	                       <?php foreach ($item->product_options as $option) : ?>
             				   - <small><?php echo $option->name; ?>: <?php echo $option->value; ?></small><br />
            				   <?php endforeach; ?>
	                    <?php endif; ?>
	                    <?php if($this->params->get('show_sku_field') && JString::strlen($item->product_model) > 0) : ?>
	                    	<?php echo JText::_( "K2STORE_SKU" ); ?>: <?php echo $item->product_model; ?>
	                    <?php endif; ?>
	                   <?php echo JText::_( "K2STORE_ITEM_PRICE" ); ?>: <?php echo K2StorePrices::number($item->price); ?>
                    </td>
                    <td style="text-align: center;" class="product_quantity_input">
                        <?php $type = 'text';
                       ?>

                   		<input type="text" name="quantity[<?php echo $item->key; ?>]" value="<?php echo $item->quantity; ?>" size="1" />

                        <!-- Keep Original quantity to check any update to it when going to checkout -->
                        <input name="original_quantities[<?php echo $item->key; ?>]" type="hidden" value="<?php echo $item->quantity; ?>" />
                    </td>
                    <td style="text-align: right;">
                        <?php $subtotal = $subtotal + $item->total; ?>
                        <?php echo K2StorePrices::number($item->total); ?>
                    </td>
                    <td><a href="#" title="<?php echo JText::_( 'K2STORE_CART_REMOVE_ITEM' ); ?>" onclick="k2storeCartRemove('<?php echo $item->key; ?>', <?php echo $item->product_id; ?>, 2)">
                    <div class="k2storeCartRemove"> </div>
                    </a>  </td>
                </tr>
            <?php ++$i; $k = (1 - $k); ?>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="k2store-cart-footer" >
             <!-- subtotal -->
               	<tr class="cart_subtotal">
                    <td colspan="2" style="font-weight: bold; text-align: right;">
                        <?php echo JText::_( "K2STORE_CART_SUBTOTAL" ); ?>
                    </td>
                     <td style="text-align: right;">
                        <?php echo K2StorePrices::number($this->totals['subtotal']); ?>
                     </td>
                     <td></td>
                </tr>

               <!-- coupon -->

               <?php if(isset($this->totals['coupon'])): ?>
               	<tr>
               		<td colspan="2" style="font-weight: bold; text-align: right;">
 							<?php echo $this->totals['coupon']['title']; ?>
 					</td>
 					 <td style="text-align: right;">
 						 <?php echo K2StorePrices::number($this->totals['coupon']['value']); ?>
 					 </td>
 					<td><a href="#" onClick="k2storeCartRemoveCoupon()" ><div class="k2storeCartRemove"> </div></a></td>
 				</tr>
 				<?php endif;?>

				<!-- tax -->
 				<?php if($this->params->get('show_tax_total', 1) && $this->totals['taxes']):?>
 				<tr>
 					<td colspan="2" style="font-weight: bold; text-align: right;">
	                       	<?php
	                        	foreach($this->totals['taxes'] as $tax) {
	                        		echo $tax['title'].'<br />';
	                        	}
	                        ?>
					</td>
					<td style="text-align: right;">
					 <?php
					 	foreach($this->totals['taxes'] as $tax) {
	                    	echo K2StorePrices::number($tax['value']).'<br />';
						}
					?>
					</td>
					<td></td>
				</tr>
              	 <?php endif; ?>

                  <!-- total-->
                  	<tr>
                  		<td colspan="2" style="font-weight: bold; text-align: right;">
                         <?php echo JText::_( "K2STORE_CART_GRANDTOTAL" ); ?>
	                    </td>
	                   	<td style="text-align: right;">

	                     <?php if($this->params->get('show_tax_total', 1)):?>
							<?php echo K2StorePrices::number($this->totals['total']);?>
						<?php else: ?>
							<?php echo K2StorePrices::number($this->totals['total_without_tax']);?>
                        <?php endif; ?>
	                    </td>
	                  <td></td>
                </tr>
            </tfoot>
        </table>
        <table id="cart_actions" width="100%">

               <tr>
               		<?php if(isset($continue_shopping_url)):?>
               		<td colspan="2">
               		 <a class="btn btn-primary begin_checkout" href="<?php echo $continue_shopping_url; ?>">
                        <?php echo JText::_('K2STORE_CONTINUE_SHOPPING'); ?>
                        </a>
                    </td>
                    <?php endif;?>

                    <td colspan="<?php echo $colspan = isset($continue_shopping_url)?'3':'5';?>">
                        <input style="float: right;" type="submit" class="k2store_cart_button btn btn-warning" value="<?php echo JText::_('K2STORE_UPDATE_QUANTITIES'); ?>" name="update" />
                    </td>
                </tr>

                <tr>
                	<td colspan="5" style="white-space: nowrap;">
                        <b><?php echo JText::_( "K2STORE_CART_TAX_SHIPPING_TOTALS" ); ?></b>
                        <br/>
                        <?php
                            echo JText::_( "K2STORE_CALCULATED_DURING_CHECKOUT_PROCESS" );
                    	?>
              	 	</td>
                </tr>
                <tr>
                    <td colspan="5">
				        <div style="float: right;">
				        <a class="btn btn-primary begin_checkout" href="<?php echo $checkout_url; ?>">
				            <?php echo JText::_( "K2STORE_BEGIN_CHECKOUT" ); ?>
				        </a>
				        </div>
                    </td>
                </tr>

        </table>
        <input type="hidden" name="option" value="com_k2store" />
         <input type="hidden" name="view" value="mycart" />
        <input type="hidden" name="task" value="update" />
    </form>
    <?php if($this->params->get('enable_coupon', 0) && K2STORE_PRO == 1):?>
	    <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data">
	    <div class="coupon">
	    <?php
	    $coupon = '';
	    if(JFactory::getSession()->has('coupon', 'k2store')) {
			$coupon =JFactory::getSession()->get('coupon', '', 'k2store');
	    }
	    ?>
		<input type="text" name="coupon" value="<?php echo $coupon; ?>" />
		<input type="submit" value="<?php echo JText::_('K2STORE_APPLY_COUPON')?>" class="button btn btn-primary" />
		<input type="hidden" name="option" value="com_k2store" />
         <input type="hidden" name="view" value="mycart" />
	    </div>
	     </form>
    <?php endif; ?>

    <?php } else { ?>
    <p><?php echo JText::_( "K2STORE_NO_ITEMS" ); ?></p>
    <?php } ?>
</div>
</div>
</div></div>
</div>