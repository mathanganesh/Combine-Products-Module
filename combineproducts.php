<?php
/*
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
* DISCLAIME3
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2013 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
if (!defined('_PS_VERSION_'))
  exit;
class combineproducts extends Module
{
	private $_html = '';
	private $_postErrors = array();
	function __construct()
	{
		$this->name = 'combineproducts';
		$this->tab = 'front_office_features';
		$this->version = '01.0';
		$this->author = 'Faiz Khan';
		$this->need_instance = 0;
		parent::__construct();
		$this->displayName = $this->l('Combine products.');
		$this->description = $this->l('Displays featured products in the middle of your homepage.');
	}
	function install()
	{
		if (!parent::install() OR
			!$this->registerHook('productFooter') OR
			!$this->registerHook('header') OR
			!$this->registerHook('displayAdminOrder') OR
			!$this->registerHook('orderConfirmation'))
			return false;
 Db::getInstance()->execute('CREATE TABLE  IF NOT EXISTS `'._DB_PREFIX_.'combineproducts` (
 `id_category` int(11) NOT NULL,
 `main_id` int(11) NOT NULL,
 `id_product` int(11) NOT NULL)
  ENGINE=InnoDB DEFAULT CHARSET=latin1');
  
 Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'combinecart` 
 ( `id_order` int(11) NOT NULL, 
 `id_cart` int(11) NOT NULL, 
 `id_guest` int(11) NOT NULL, 
 `main_id` int(11) NOT NULL, 
 `combination` text NOT NULL )
  ENGINE=InnoDB DEFAULT CHARSET=latin1');


$max_id=Db::getInstance()->executeS("select max(id_category) as max from `"._DB_PREFIX_."category");

$id_category=$max_id[0]['max']+1;

$data = array('id_category' => $id_category,
		'active' => 1,
		'date_add'=>date('Y-m-d H:i:s'),
		'level_depth'=>1,
		'id_parent'=>2);

Db::getInstance()->insert('category', $data);

$dataLang = array (
		'id_category' => $id_category,
		'id_lang' => 1,
		'name' => 'GroupedItem');
		
Db::getInstance()->insert('category_lang', $dataLang);

$dataLangposition = array (
		'id_category' => $id_category,
		'id_shop' => 1,
		'position' => 2);

Db::getInstance()->insert('category_shop', $dataLangposition);

		$getAllgroups=Db::getInstance()->executes("select distinct(id_group) as ids from "._DB_PREFIX_."category_group");
		foreach($getAllgroups as $ids)
		{
			$ids=array(
			'id_category' => $id_category,
			'id_group'=>$ids['ids']
			);
			Db::getInstance()->insert('category_group',$ids);
		}
		
		Configuration::updateValue('PS_GROUPED_CAT',$id_category);

		return true;
	}
	
	
	public function uninstall()
	{
		if (!parent::uninstall())
			return false;
		Db::getInstance()->execute("delete from "._DB_PREFIX_."category_group where id_category=".Configuration::get('PS_GROUPED_CAT'));
		Db::getInstance()->execute("delete from "._DB_PREFIX_."category where id_category=".Configuration::get('PS_GROUPED_CAT'));
		Db::getInstance()->execute("delete from "._DB_PREFIX_."category_lang where id_category=".Configuration::get('PS_GROUPED_CAT'));
		Db::getInstance()->execute("delete from "._DB_PREFIX_."category_shop where id_category=".Configuration::get('PS_GROUPED_CAT'));	
		Db::getInstance()->execute('drop table `'._DB_PREFIX_.'combinecart`');
		Db::getInstance()->execute('drop table `'._DB_PREFIX_.'combineproducts`');
		
		Configuration::deleteByName('PS_GROUPED_CAT');
		return true;
	}
	
	
	public function getProductswithids($product_ids=array(),$id_lang,$p, $n, $order_by = null, $order_way = null, $get_total = false, $active = true, $random = false, $random_number_products = 1, $check_access = true, Context $context = null)
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
	
	
	

	
public function getSimpleCategoriesByids($id_lang,$id_categories)
	{
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
		SELECT c.`id_category`, cl.`name`
		FROM `'._DB_PREFIX_.'category` c
		LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (c.`id_category` = cl.`id_category`'.Shop::addSqlRestrictionOnLang('cl').')
		'.Shop::addSqlAssociation('category', 'c').'
		WHERE cl.`id_lang` = '.(int)$id_lang.'
		AND c.`id_category` != '.Configuration::get('PS_ROOT_CATEGORY').'
                AND c.`id_category` in ('.implode(',',$id_categories).')
		GROUP BY c.id_category
		ORDER BY c.`id_category`, category_shop.`position`');
	}
	
	
	public function getCombinedDetails($pro_name,$id_product,$id_order)
	{
		$output='';
		$getCom=DB::getInstance()->executeS("select * from "._DB_PREFIX_."combinecart where id_order=".$id_order." and main_id=".$id_product);
	if(!empty($getCom))
	{
		$product_ids=explode(',',$getCom[0]['combination']);
		$output='';
		$product_details=$this->getProductswithids($product_ids, 1, 1, 10000);
		$i=1;
		$output .='<h3>'.$pro_name.'</h3><br>';
		
		foreach($product_details as $products)
		{
			$output .=$i++.'.'.$products['name']. '('. $products['category_default'].')<br />';
		}
	}
		return $output;
	}

	public function hookdisplayAdminOrder($params)
	{
	
		$id_order = (int)Tools::getValue('id_order');
		
		$order = new Order($id_order);
		
		$order_details = $order->getOrderDetailList();
		
		foreach ($order_details as $order_detail)
		{
			
			$outputs.=$this->getCombinedDetails($order_detail['product_name'],$order_detail['product_id'],$id_order);
		}
		
		$this->smarty->assign('adminOrders',$outputs);
		
		return $this->display(__FILE__, 'orderdetails.tpl');
	}
	
	
	public function hookOrderConfirmation($params)
	{
		
		$id_order = (int)Tools::getValue('id_order');
		
		
		global $cookie;
		
		$check="select * from "._DB_PREFIX_."combinecart where id_guest=".$cookie->id_guest." and id_order=''";
		
		$result=DB::getInstance()->executeS($check);
		
		
		if(!empty($result))
		{
		
		$sql="update "._DB_PREFIX_."combinecart set id_order='".$id_order."' where id_guest=".$cookie->id_guest." and id_order=''";
			
		DB::getInstance()->executeS($sql);
		
		$sql="select * from "._DB_PREFIX_."order_detail a , "._DB_PREFIX_."combinecart b where a.id_order=b.id_order and a.id_order=".$id_order;
		
		$listMain=DB::getInstance()->executeS($sql);
			foreach($listMain as $listin)
			{
				$quantity_used=$listin['product_quantity'];
				$sql="update "._DB_PREFIX_."stock_available set quantity=quantity-".$quantity_used." where id_product in (".$listin['combination'].")";
			
			
				DB::getInstance()->executeS($sql);
			}
		}
		
	}
	
public function viewdetails($main_id)
{
	
$category_ids=array();
$sql="select * from "._DB_PREFIX_."combineproducts where main_id=".$main_id;
$thisPack=DB::getInstance()->executeS($sql);
foreach($thisPack as $tp)
{
$category_ids[]=$tp['id_category'];
}
$product_ids=array();
$getcategories=$this->getSimpleCategoriesByids(1,$category_ids);
$i=0;
foreach($getcategories as $cats)
{
$this_cat=$cats['id_category'];
foreach($thisPack as $tps)
{
if($this_cat==$tps['id_category'])
{
$product_ids[]=$tps['id_product'];
}
}
}
$product_details=$this->getProductswithids($product_ids, 1, 1, 10000);
return $product_details;
}



	public function getContent()
	{
		$this->_html='<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
		<script src="../modules/combineproducts/jquery.colorbox.js"></script>
	
		<link rel="stylesheet" href="../modules/combineproducts/colorbox.css" />
		<script type="text/javascript">
		$(document).ready(function(){
		$(".iframe").colorbox({iframe:true, width:"80%", height:"80%",onClosed:function(){location.reload();}});
	
		})
		</script>';
		
		
		
		$this->_html .= "<fieldset>";
		$this->_html .= '<legend>'.$this->displayName.'</legend>';
		
		
		
		
		$category = new Category(Configuration::get('PS_GROUPED_CAT'), (int)Context::getContext()->language->id);
		
		
		
		$products = $category->getProducts((int)Context::getContext()->language->id, 1, 1000);
		
		$this->_html .='<table class="table" width="100%" cellpadding="0" ellspacing="0">
		<tr><thead><th>Product Image</th><th>Product Name</th><th>Base Price</th><th>Final Price</th><th>View Sub Products</th><th>View Products</th></thead></tr>
		<tbody>';
		
		
		foreach($products as $productDetail)
		{
			$image='../img/tmp/product_mini_'.$productDetail['id_product'].'.jpg?time='.time();
			
			$this->_html .='<tr><td align="center"><img src="'.$image.'" /></td><td>'.$productDetail['name'].'</td><td>'.$productDetail['orderprice'].'</td><td>'.$productDetail['price'].'</td><td>
			<a href="../modules/combineproducts/load_products.php?main_id='.$productDetail['id_product'].'" class="iframe">Add More</a></td>
			<td><form action="" method="post">
			<input type="hidden" name="main_id" value="'.$productDetail['id_product'].'"/>
			<input type="submit" name="view" value="View" /></form></div></td>';
		}
		$this->_html.='</tbody></table>';
		
		$this->_html .='</fieldset>';
		
		
		$this->_html .='<fieldset>
		<legend>Product Combined details</legend>
		<div id="detailsList">';
		if(isset($_POST['view']))
		{
			$listVal=$this->viewdetails($_POST['main_id']);
		
		$this->_html .='<table class="table" id="detailedView" width="100%" cellpadding="0" cellspacing="0">
		<tr><thead><th>Product Image</th><th>Product Name</th><th>Category Name</th><th>Base Price</th><th>Final Price</th></thead></tr>
		<tbody>';
		
		
		foreach($listVal as $productDetail)
		{
			$image='../img/tmp/product_mini_'.$productDetail['id_product'].'.jpg?time='.time();
			
			$this->_html .='<tr><td align="center"><img src="'.$image.'" /></td>
			<td>'.$productDetail['name'].'</td>
			<td>'.$productDetail['category_default'].'</td>
			<td>'.$productDetail['orderprice'].'</td><td>'.$productDetail['price'].'</td>';
			
		}
		$this->_html.='</tbody></table>';
		}
		
		
		$this->_html .='</div>
		</fieldset>';
		
	
		
		return $this->_html;
		
	}
	
	
public function hookHeader()
{
$category = new Category(Configuration::get('PS_GROUPED_CAT'), (int)Context::getContext()->language->id);
$products = $category->getProducts((int)Context::getContext()->language->id, 1, 1000);

foreach($products as $productDetail)
		{
		$id_product=$productDetail['id_product'];
	$sql="select * from "._DB_PREFIX_."combineproducts where main_id=".$id_product;
  	$thisPack=DB::getInstance()->executeS($sql);
	$category_ids=array();
foreach($thisPack as $tp)
{
$category_ids[]=$tp['id_category'];
}	


$getcategories=$this->getSimpleCategoriesByids(1,$category_ids);
$i=0;
foreach($getcategories as $cats)
{

$quantities=array();
  	$product_ids=array();
$this_cat=$cats['id_category'];
foreach($thisPack as $tps)
  	{
  		if($this_cat==$tps['id_category'])
        {
  		$product_ids[]=$tps['id_product'];
		}
  	}
  $product_details=$this->getProductswithids($product_ids, 1, 1, 10000);
 
 $quantity=array();
  	foreach($product_details as $quantcheck)
	{
	 $quantity[]=$quantcheck['quantity'];
	}
  $quantities[]=min($quantity);
  $i++;	
} 
		
		
		
		
	$minQu=min($quantities);	
$sql="update "._DB_PREFIX_."stock_available set quantity=$minQu where id_product=".$id_product;
DB::getInstance()->executeS($sql);
		}

			
	}
	
  public function hookProductFooter($params)
 {
  	
 $this->smarty->assign('can_add','no');
$id_product = (int)Tools::getValue('id_product');
$category_ids=array();
 	$quantities=array();
	$sql="select * from "._DB_PREFIX_."combineproducts where main_id=".$id_product;
  	$thisPack=DB::getInstance()->executeS($sql);
foreach($thisPack as $tp)
{
  		$category_ids[]=$tp['id_category'];
  	}
$getcategories=$this->getSimpleCategoriesByids(1,$category_ids);
$i=0;
foreach($getcategories as $cats)
{
  	$product_ids=array();
$this_cat=$cats['id_category'];
foreach($thisPack as $tps)
  	{
  		if($this_cat==$tps['id_category'])
        {
  		$product_ids[]=$tps['id_product'];
		}
  	}
  	
  $product_details=$this->getProductswithids($product_ids, 1, 1, 10000);
       $productInfo[$i]['cat_name']=$cats['name'];
       $productInfo[$i]['id_category']=$cats['id_category'];
       $productInfo[$i]['products_data']=$product_details;
$productInfo[$i]['cat_class']=$cats['id_category'].'catid';

  $i++;	
  $this->smarty->assign('can_add','yes');
 }
$this->smarty->assign('maxCats',$i);
$this->smarty->assign('thisproductid',$id_product);
$this->smarty->assign('combinedproducts',$productInfo);
$this->context->controller->addJS(($this->_path).'combineproducts.js');
  	return $this->display(__FILE__, 'combineproducts.tpl');
  	
  }
	
}
