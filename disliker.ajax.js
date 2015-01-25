jQuery(document).ready(function() {
	jQuery('body').on('click','.idev-post-unlike',function(event){
		event.preventDefault();
		disliker = jQuery(this);
		post_id = disliker.data("post_id");
		disliker.html("<i id='icon-like' class='icon-like'></i><i id='icon-gear' class='icon-gear'></i>");
		jQuery.ajax({
			type: "post",
			url: ajax_var.url,
			data: "action=idev-post-unlike&nonce="+ajax_var.nonce+"&idev_post_unlike=&post_id="+post_id,
			success: function(count){
				if( count.indexOf( "count" ) !== -1 )
				{
					var lecount = count.replace("count","");
					if (lecount === "0")
					{
						lecount = "dislikes";
					}
					disliker.prop('title', 'dislikes');
					disliker.removeClass("liked");
					disliker.html("&nbsp;"+lecount);
				}
				else
				{
					disliker.prop('title', 'Disliked');
					disliker.addClass("liked");
					disliker.html("&nbsp;"+count);
				}
			}
		});
	});
});
