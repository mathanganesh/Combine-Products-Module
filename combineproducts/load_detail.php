<?php
include '../../config/config.inc.php';
include '../../init.php';

function getProductsList($main_id)
{
$category_ids=array();

$sql="select * from "._DB_PREFIX_."combineproducts where main_id=".$main_id;


$thisPack=DB::getInstance()->executeS($sql);

foreach($thisPack as $tp)

{
$category_ids[]=$tp['id_category'];
}

$getcategories=CategoryCore::getSimpleCategoriesByids(1,$category_ids);
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



$product_details=CategoryCore::getProductswithids($product_ids, 1, 1, 10000);

$productInfo[$i]['cat_name']=$cats['name'];

$productInfo[$i]['id_category']=$cats['id_category'];

$productInfo[$i]['products_data']=$product_details;

}
return $productInfo;
}
$id_product = $_REQUEST['id_product'];

$getProductsList=getProductsList($id_product);

?>
