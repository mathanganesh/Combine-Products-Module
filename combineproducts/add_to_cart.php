<?php
include '../../config/config.inc.php';
include '../../init.php';
foreach($_POST as $key=>$values)
{
	if(strpos($key, 'catid')!==false)
	{
		$id_products[]=$values;
	}
	
}
function getProductswithids($product_ids=array(),$id_lang,$p, $n, $order_by = null, $order_way = null, $get_total = false, $active = true, $random = false, $random_number_products = 1, $check_access = true, Context $context = null)
	{
		if (!$context)
			$context = Context::getContext();
		
		$id_products=implode(',',$product_ids);
		
		
	
		$front = true;
		if (!in_array($context->controller->controller_type, array('front', 'modulefront')))
			$front = false;
			
		if ($p < 1) $p = 1;
	
		if (empty($order_by))
			$order_by = 'position';
		else
			/* Fix for all modules which are now using lowercase values for 'orderBy' parameter */
			$order_by = strtolower($order_by);
	
		if (empty($order_way))
			$order_way = 'ASC';
		if ($order_by == 'id_product' || $order_by == 'date_add' || $order_by == 'date_upd')
			$order_by_prefix = 'p';
		elseif ($order_by == 'name')
		$order_by_prefix = 'pl';
		elseif ($order_by == 'manufacturer')
		{
			$order_by_prefix = 'm';
			$order_by = 'name';
		}
		elseif ($order_by == 'position')
		$order_by_prefix = 'cp';
	
		if ($order_by == 'price')
			$order_by = 'orderprice';
	
		if (!Validate::isBool($active) || !Validate::isOrderBy($order_by) || !Validate::isOrderWay($order_way))
			die (Tools::displayError());
	
		$id_supplier = (int)Tools::getValue('id_supplier');
	
		
		
		/* Return only the number of products */
		if ($get_total)
		{
			$sql = 'SELECT COUNT(cp.`id_product`) AS total
			FROM `'._DB_PREFIX_.'product` p
			'.Shop::addSqlAssociation('product', 'p').'
			LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON p.`id_product` = cp.`id_product`
			WHERE cp.`id_category` = '.(int)$this->id.
			($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '').
			($active ? ' AND product_shop.`active` = 1' : '').
			($id_supplier ? 'AND p.id_supplier = '.(int)$id_supplier : '');
			return (int)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
		}
	
		$sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, pl.`description`, pl.`description_short`, pl.`available_now`,
		pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, MAX(image_shop.`id_image`) id_image,
		il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
		DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
		INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).'
		DAY)) > 0 AS new, product_shop.price AS orderprice
		FROM `'._DB_PREFIX_.'category_product` cp
		LEFT JOIN `'._DB_PREFIX_.'product` p
		ON p.`id_product` = cp.`id_product`
		'.Shop::addSqlAssociation('product', 'p').'
		LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
		ON (p.`id_product` = pa.`id_product`)
		'.Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.`default_on` = 1').'
		'.Product::sqlStock('p', 'product_attribute_shop', false, $context->shop).'
		LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
		ON (product_shop.`id_category_default` = cl.`id_category`
		AND cl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('cl').')
		LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
		ON (p.`id_product` = pl.`id_product`
		AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').')
		LEFT JOIN `'._DB_PREFIX_.'image` i
		ON (i.`id_product` = p.`id_product`)'.
		Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1').'
		LEFT JOIN `'._DB_PREFIX_.'image_lang` il
		ON (image_shop.`id_image` = il.`id_image`
		AND il.`id_lang` = '.(int)$id_lang.')
		LEFT JOIN `'._DB_PREFIX_.'manufacturer` m
		ON m.`id_manufacturer` = p.`id_manufacturer`
		WHERE product_shop.`id_shop` = '.(int)$context->shop->id.'
		and p.id_product in ('.$id_products.')
		GROUP BY product_shop.id_product';
		
		if ($random === true)
		{
			$sql .= ' ORDER BY RAND()';
			$sql .= ' LIMIT 0, '.(int)$random_number_products;
		}
		else
			$sql .= ' ORDER BY '.(isset($order_by_prefix) ? $order_by_prefix.'.' : '').'`'.pSQL($order_by).'` '.pSQL($order_way).'
			LIMIT '.(((int)$p - 1) * (int)$n).','.(int)$n;
	
		$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
		if ($order_by == 'orderprice')
			Tools::orderbyPrice($result, $order_way);
	
		if (!$result)
			return array();
	
		/* Modify SQL result */
		return Product::getProductsProperties($id_lang, $result);
	}
$combination=implode(',',$id_products);
$main_id=$_POST['main_id'];
$checkPrev="select * from "._DB_PREFIX_."combinecart where id_guest=".$cookie->id_guest." and main_id=".$main_id." and id_order=''";
$thisPack=DB::getInstance()->executeS($checkPrev);
if(empty($thisPack))
{
      $sql="insert into "._DB_PREFIX_."combinecart(main_id,id_guest,combination) 
	  values($main_id,".$cookie->id_guest.",'$combination')";
}
else
{
	$sql="update "._DB_PREFIX_."combinecart set combination='$combination' where id_guest=".$cookie->id_guest." and main_id=".$main_id." and id_order=''";
}
DB::getInstance()->executeS($sql);
$getDet=getProductswithids($id_products, 1, 1, 10000);
foreach($getDet as $weights)
{
	$totalweight=$totalweight+$weights['weight'];
}
$wQuery="update "._DB_PREFIX_."product set weight='".$totalweight."' where id_product=".$main_id;
DB::getInstance()->executeS($wQuery);

?>
