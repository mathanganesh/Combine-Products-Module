<?php

include '../../config/config.inc.php';

include '../../init.php';





$main_id=$_GET['main_id'];



if(isset($_POST['update']))

{

	$category = new Category($_POST['id_category'], (int)Context::getContext()->language->id);

	$products = $category->getProducts((int)Context::getContext()->language->id, 1, 1000);

	

foreach($products as $productDetail)

		{

	   if(in_array($productDetail['id_product'],$_POST['id_products']))

	   {

	   	     $checkexist="select * from "._DB_PREFIX_."combineproducts where main_id=".$main_id." and id_category=".$_POST['id_category']." and id_product=".$productDetail['id_product'];

	   	     

	   	     

	   	     

	   	     if(!is_array(DB::getInstance()->getRow($checkexist)))

	   	     {

	   	     	$sql="insert into "._DB_PREFIX_."combineproducts (main_id,id_product,id_category) values(".$main_id.",".$productDetail['id_product'].",".$_POST['id_category'].")";

	   	     	

	   	     	DB::getInstance()->execute($sql);

	   	     	$sql='';

	   	     }

	   }

	   else

	   {

	   	$delete="delete from "._DB_PREFIX_."combineproducts where main_id=".$main_id." and id_category=".$_POST['id_category']." and id_product=".$productDetail['id_product'];

	   	DB::getInstance()->execute($delete);

	   	$delete='';

	   }

			

		}

	

}



function get_products_list($id_category,$main_id)

{

	$category = new Category($id_category, (int)Context::getContext()->language->id);

	$products = $category->getProducts((int)Context::getContext()->language->id, 1, 1000);

	

foreach($products as $productDetail)

		{

			$image='../../img/tmp/product_mini_'.$productDetail['id_product'].'.jpg?time='.time();

		

			

			$sql="select * from "._DB_PREFIX_."combineproducts where id_product=".$productDetail['id_product']." and id_category=".$id_category." and main_id=".$main_id;

			

			//echo $sql;

			$check=DB::getInstance()->getRow($sql);

			$checked='';

			if(is_array($check))

			{

				$checked="checked";

			}			

			$output .='<tr><td><input type="checkbox" '.$checked.' value='.$productDetail['id_product'].' name="id_products[]" /></td><td align="center"><img src="'.$image.'" /></td><td>'.$productDetail['name'].'</td><td>'.$productDetail['orderprice'].'</td><td>'.$productDetail['price'].'</td><td>

			</td>';

		}

	

	

	return $output;

}

if($_POST['id_category'])

{

	$listProduct=get_products_list($_POST['id_category'],$main_id);

	

}





$list_cat=CategoryCore::getSimpleCategories((int)Context::getContext()->language->id,1);



?>





<form action="" method="POST">

<select name="id_category" onchange="this.form.submit()">

<option value="">--Selet Category--</option>

<?php 

foreach ($list_cat as $categories)

{

	if($categories['id_category']=='1' || $categories['id_category']==Configuration::get('PS_GROUPED_CAT'))

	{

		continue;

	}

	

	$select='';

	if($categories['id_category']==$_POST['id_category'])

	{

		$select="selected";

	}

	?>	

	<option value="<?php echo $categories['id_category'];?>" <?php echo $select;?>><?php echo $categories['name'];?></option>

	<?php

}

?>

</select>

</form>



<form action="" method="POST">

<table class="table" width="100%" cellpadding="0" ellspacing="0">

		<tr><thead>

		<th>Check</th>

		<th>Product Image</th>

		<th>Product Name</th>

		<th>Base Price</th>

		<th>Final Price</th>

	</thead></tr>

		<tbody>

<?php 

echo $listProduct;

?>

</tbody>

</table>

<input type="hidden" value="<?php echo $_POST['id_category'];?>" name="id_category" >

<input type="submit" name="update" value="Add products">

</form>



