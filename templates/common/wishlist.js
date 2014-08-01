jQuery(function ($) {
   $(document).ready(function() {
	if( $('.add-to-wishlist').length ) // we have add to wishlist button
	{
		var rwe_sid = getCookie('rwe_sid');
		var cartid = getCookie('cartid');
		$.getJSON(rweURL,{sid: rwe_sid, action:'list_wishlist_items', wishlist_id: cartid}, function(result){
			if (result.wishlist_id !== cartid) {
					setCookie("cartid",result.wishlist_id,1);
			}
			if (result.invoice_line_items.length){
				var this_item = $('.add-to-wishlist').attr("value");
				$.each(result.invoice_line_items, function(index, value) {
					if (value.inventory_item_id == this_item) {
						$(".add-to-wishlist").attr("disabled", "disabled");
						$(".add-to-wishlist").html("Already on wishist");
						$("#wishlist-result").html('Added to wishlist.');
					}
				});

			}
		});
	}
	$(".add-to-wishlist").click(function() {
		var rwe_sid = getCookie('rwe_sid');
		var cartid = getCookie('cartid');
		var item = $(this).val();
		$.getJSON(rweURL,{sid: rwe_sid, action:'add_item_to_wishlist', wishlist_id: cartid, inventory_item_id: item, quantity: '+1'}, function(result){
			if (result.response_status == 'Error') {
				$("#wishlist-result").html(result.response_message);
			}
			else if (result.inventory_item_id == item) {
				// returned same item, add confirmed
				$(".add-to-wishlist").attr("disabled", "disabled");
				$(".add-to-wishlist").html("Already on wishist");
				$("#wishlist-result").html('Added to wishlist.');
				// check wishlist ID, if different update cookie
				if (result.wishlist_id !== cartid) {
						setCookie("cartid",result.wishlist_id,1);
				}
			}
			else {
				$("#wishlist-result").html('Unknown error occured.');
			}
			$("#wishlist").slideUp();
		});
	});
	$(".view-wishlist").click(function() {
	   if ($("#wishlist").is(":visible")) {
		$("#wishlist").slideUp();
	   }
	   else {
		var rwe_sid = getCookie('rwe_sid');
		var cartid = getCookie('cartid');
		$.getJSON(rweURL,{sid: rwe_sid, action:'list_wishlist_items', wishlist_id: cartid}, function(result){
			var content = '';
			if (result.wishlist_id !== cartid) {
					setCookie("cartid",result.wishlist_id,1);
			}
			if(typeof result.invoice_line_items === 'undefined'){
				$("#wishlist-contents").html('Your wishlist is empty.');
				$("#wishlist").slideDown();
			}
			else {
				if (!result.invoice_line_items.length){
					$("#wishlist-contents").html('Your wishlist is empty.');
					$("#wishlist").slideDown();
				}
				else {
					$.each(result.invoice_line_items, function(index, value) {
					    content += '<input name="' + value.inventory_item_id +'" class="item qty" type="text" value="' + value.quantity + '"/> ' + value.name;
					    if (showprice) {
					        content += ' $' + value.unit_price;
					    }
					    content += '<br />';
					});
					content += '<input id="update-wishlist" type="submit" style="visibility: hidden;" value="Update" />';
					content += '<button type="button" class="submit-wishlist">Submit Wishlist</button>';
					$("#wishlist-contents").html(content);
					$("#wishlist").slideDown();
					$("#wishlist-result").empty();
				}
			}
		});	
	   }
	});
	$("#wishlist").on("click", ".clear-whishlist", function() {
		var rwe_sid = getCookie('rwe_sid');
		var cartid = getCookie('cartid');
		$.getJSON(rweURL,{sid: rwe_sid, action:'clear_wishlist', wishlist_id: cartid}, function(result){
			if (result.response_status == 'Error') {
				$("#wishlist-contents").html(result.response_message);
			}
			else {
				$("#wishlist-contents").html('Wishlist cleared.');
			}
		});
	});
	// disable submit when changing data.
	$("#wishlist").on("change keyup paste click", "input.qty", function() {
		$("#update-wishlist").css("visibility", "visible");
		$(".submit-wishlist").attr("disabled", "disabled").css({ opacity: 0.5 });
	});
	// submit whishlist
	$("#wishlist").on("click", ".submit-wishlist", function() {
		var loc = window.location.href;
		newloc = updateQueryStringParameter(loc, 'rwewishlist', 1);
		window.location.href = newloc;
	});
	// update wishlist
	$("#wishlist").submit(function(e) {
		e.preventDefault();
		var rwe_sid = getCookie('rwe_sid');
		var cartid = getCookie('cartid');
		var itemArray = [];
		x=$("#wishlist").serializeArray();
		$.each(x, function(i, field){
			if (isPositiveInteger(field.value)) {
				itemArray.push({
					inventory_item_id: field.name,
					quantity: field.value
				});
			}
		});
		var dataString = '&sid='+rwe_sid+'&action=add_item_to_wishlist&wishlist_id='+cartid+'&items='+encodeURIComponent(JSON.stringify(itemArray));
		$.getJSON(rweURL,dataString, function(result){

			// same as view wishlist

			$.getJSON(rweURL,{sid: rwe_sid, action:'list_wishlist_items', wishlist_id: cartid}, function(result){
				var content = '';
				if(typeof result.invoice_line_items === 'undefined'){
					$("#wishlist-contents").html('Your wishlist is empty.');
					if( $('.add-to-wishlist').length ) // we have add to wishlist button
					{
						$(".add-to-wishlist").removeAttr("disabled");
						$(".add-to-wishlist").html("Add to wishist");
						$("#wishlist-result").html('');
					}
				}
				else {
					if (!result.invoice_line_items.length){
						$("#wishlist-contents").html('Your wishlist is empty.');
						if( $('.add-to-wishlist').length ) // we have add to wishlist button
						{
							$(".add-to-wishlist").removeAttr("disabled");
							$(".add-to-wishlist").html("Add to wishist");
							$("#wishlist-result").html('');
						}
					}
					else {
						if( $('.add-to-wishlist').length ) // we have add to wishlist button
						{
							var this_item = $('.add-to-wishlist').attr("value");
							var this_item_exists = 1;
						}
						$.each(result.invoice_line_items, function(index, value) {
						    content += '<input name="' + value.inventory_item_id +'" class="item qty" type="text" value="' + value.quantity + '"/> ' + value.name;
						    if ( this_item_exists && value.inventory_item_id == this_item) {
								this_item_exists +=1;
						    }
						    if (showprice) {
						        content += ' $' + value.unit_price;
						    }
						    content += '<br />';
						});
						content += '<input id="update-wishlist" type="submit" style="visibility: hidden;" value="Update" />';
						content += '<button type="button" class="submit-wishlist">Submit Wishlist</button>';
						content += 'Wishlist updated.';
						$("#wishlist-contents").html(content);
						if ( this_item_exists == 1) {
							$(".add-to-wishlist").removeAttr("disabled");
							$(".add-to-wishlist").html("Add to wishist");
							$("#wishlist-result").html('');
						}
					}
				}
			});
	
			// end same as view

		});

	});
   });
});

function updateQueryStringParameter(uri, key, value) {
  var re = new RegExp("([?|&])" + key + "=.*?(&|$)", "i");
  separator = uri.indexOf('?') !== -1 ? "&" : "?";
  if (uri.match(re)) {
    return uri.replace(re, '$1' + key + "=" + value + '$2');
  }
  else {
    return uri + separator + key + "=" + value;
  }
}
function isPositiveInteger(n) {
    return 0 === n % (!isNaN(parseFloat(n)) && 0 <= ~~n);
}
function setCookie(cname,cvalue,exdays)
{
var d = new Date();
d.setTime(d.getTime()+(exdays*24*60*60*1000));
var expires = "expires="+d.toGMTString();
document.cookie = cname + "=" + cvalue + "; " + expires + "; path=/";
}
function getCookie(cname)
{
var name = cname + "=";
var ca = document.cookie.split(';');
for(var i=0; i<ca.length; i++) 
  {
  var c = ca[i].trim();
  if (c.indexOf(name)==0) return c.substring(name.length,c.length);
}
return "";
}
