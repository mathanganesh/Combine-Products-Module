{*

* 2007-2013 PrestaShop

*

* NOTICE OF LICENSE

*

* This source file is subject to the Academic Free License (AFL 3.0)

* that is bundled with this package in the file LICENSE.txt.

* It is also available through the world-wide-web at this URL:

* http://opensource.org/licenses/afl-3.0.php

* If you did not receive a copy of the license and are unable to

* obtain it through the world-wide-web, please send an email

* to license@prestashop.com so we can send you a copy immediately.

*

* DISCLAIMER

*

* Do not edit or add to this file if you wish to upgrade PrestaShop to newer

* versions in the future. If you wish to customize PrestaShop for your

* needs please refer to http://www.prestashop.com for more information.

*

*  @author PrestaShop SA <contact@prestashop.com>

*  @copyright  2007-2013 PrestaShop SA

*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)

*  International Registered Trademark & Property of PrestaShop SA

*}

<!-- MODULE combined products -->
<form action="#" id="combineproducts" method="GET">
<input type="hidden" name="main_id" value="{$thisproductid}" />
<input type="hidden" value="{$can_add}" id="can_add" name="can_add"/>
<input type="hidden" value="{$maxCats}" id="maxcat" name="maxat"/>
{foreach from=$combinedproducts item=combinedproduct}

<!-- Products list -->
<ul id="product_list" class="bordercolor grid">
	<h1>{$combinedproduct.cat_name}</h1>
	{foreach from=$combinedproduct.products_data item=product name=products}

		<li class="ajax_block_product">
<a href="{$product.link|escape:'htmlall':'UTF-8'}" class="product_img_link" title="{$product.name|escape:'htmlall':'UTF-8'}">

					<img src="{$link->getImageLink($product.link_rewrite, $product.id_image, 'home_default')}" alt="{$product.legend|escape:'htmlall':'UTF-8'}" {if isset($homeSize)} width="{$homeSize.width}" height="{$homeSize.height}"{/if} />

					{if isset($product.new) && $product.new == 1}<span class="new">{l s='New'}</span>{/if}

				</a>
			

			<div class="center_block">

				<div class="product_flags">
					{if isset($product.available_for_order) && $product.available_for_order && !isset($restricted_country_mode)}<span class="availability bordercolor">{if ($product.allow_oosp || $product.quantity > 0)}{l s='Available'}{elseif (isset($product.quantity_all_versions) && $product.quantity_all_versions > 0)}{l s='Product available with different options'}{else}{l s='Out of stock'}{/if}</span>{/if}
				</div>
			

				<h3><a class="product_link" href="{$product.link|escape:'htmlall':'UTF-8'}" title="{$product.name|escape:'htmlall':'UTF-8'}">{$product.name|escape:'htmlall':'UTF-8'|truncate:35:'...'}</a></h3>

			</div>

			<div class="right_block bordercolor">

				{if isset($product.on_sale) && $product.on_sale && isset($product.show_price) && $product.show_price && !$PS_CATALOG_MODE}<span class="on_sale">{l s='On sale!'}</span>

				{elseif isset($product.reduction) && $product.reduction && isset($product.show_price) && $product.show_price && !$PS_CATALOG_MODE}<span class="discount">{l s='Reduced price!'}</span>{/if}

				{if (!$PS_CATALOG_MODE AND ((isset($product.show_price) && $product.show_price) || (isset($product.available_for_order) && $product.available_for_order)))}

				{if isset($product.show_price) && $product.show_price && !isset($restricted_country_mode)}<span class="price">{if !$priceDisplay}{convertPrice price=$product.price}{else}{convertPrice price=$product.price_tax_exc}{/if}</span>{/if}
				
				{if isset($product.online_only) && $product.online_only}<span class="online_only">{l s='Online only'}</span>{/if}

				{/if}

				{if ($product.id_product_attribute == 0 || (isset($add_prod_display) && ($add_prod_display == 1))) && $product.available_for_order && !isset($restricted_country_mode) && $product.minimal_quantity <= 1 && $product.customizable != 2 && !$PS_CATALOG_MODE}

					{if ($product.allow_oosp || $product.quantity > 0)}	
					<p class="compare checkbox">		

                <input type="radio" class="comparator" value="{$product.id_product}" name="{$combinedproduct.cat_class}"> 

				<label for="comparator_item_43">Select to choose</label></p><br />
					{*<input type="radio" class="selectcat" value="{$product.id_product}" name="{$combinedproduct.cat_class}"/>	*}			

					{else}
					<p class="compare checkbox" style="display:none;">		

                <input type="radio" class="comparator" value="{$product.id_product}" name="{$combinedproduct.cat_class}"> 

				<label for="comparator_item_43">Select to choose</label></p><br />
					{/if}

				{/if}

				<a class="button lnk_view" href="{$product.link|escape:'htmlall':'UTF-8'}" title="{l s='View'}">{l s='View'}</a>

			</div>

		</li>

	{/foreach}

	</ul>

	<!-- /Products list -->





{/foreach}

</form>