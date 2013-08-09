$('document').ready( function() {
if($("#can_add").val()=="yes")
	{
		$("#add_to_cart").hide();

	}
		

var addedArray=[];

$(".comparator").click(function(){

var tisname=$(this).attr('name');

var add=$.inArray(tisname, addedArray);

if(add==-1)
{
   addedArray.push($(this).attr('name'));

}

 var len=addedArray.length;
if(len==$("#maxcat").val())
{
$("#add_to_cart").show();
$('body').scrollTo('#primary_block'); 
  
  $.ajax({
  type:'post',
  url:baseDir+'modules/combineproducts/add_to_cart.php',
  data:$("#combineproducts").serialize()
  })
  
}

});


});
