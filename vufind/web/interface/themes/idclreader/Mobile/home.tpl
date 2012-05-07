{include file="Mobile/searchBar.tpl"}
<h3 class='titleNE'>New Ebooks</h3>
<div id='imageGallery_NE' {if $NE neq ""} class='itemsInside' {/if}>
	{if $NE neq ""}
			<ul id="carousel_NE" class="jcarousel-skin-tango">
			{foreach from=$NE item=record}
				<li>
					<div class='item_wrapper'>
							<img src='{$record.image}' class='linkDetail' titleId='{$record.id}'/>
						<div class='eContentTitle'>
							<div class='widthEContentTitle linkDetail' titleId='{$record.id}'>
								{$record.title}
							</div>
						</div>
					</div>
				</li>
			{/foreach}
			</ul>
		{else}
			<p> There are no items in this category </p>
		{/if}
</div>

<ul data-role="listview" data-theme="a" data-inset="true">
    <li><a href="/Mobile/HighestRated" data-ajax=false>Highest Rated</a></li>
    <li><a href="/Mobile/MostPopular" data-ajax=false>Most Popular </a></li>
    <li><a href="/Mobile/FreeEbooks" data-ajax=false>Free eBooks  </a></li>
    <li><a href="/MyResearch/Home" data-ajax=false>My Account   </a></li>
	{if $user}   
    	<li data-icon="delete" data-theme='b'><a href="/MyResearch/Logout" data-ajax=false>Log Out</a></li>
    {/if}
</ul>
{literal}
	<script type="text/javascript">
		$(document).ready(function()
		{
			
			function setUpCarousels(id)
			{
				$('#carousel_' + id).jcarousel(
					{
						visible:4,
						scroll:2,
						buttonNextHTML:null,
						buttonPrevHTML:null
					}
				);
				
				$("#imageGallery_" + id).touchwipe({
				    wipeLeft: function() {$('#carousel_' + id).jcarousel('next');},
				    wipeRight: function() {$('#carousel_' + id).jcarousel('prev'); },
				    min_move_x: 20,
				    min_move_y: 20,
				    preventDefaultEvents: true
				});
			}
			{/literal}
				{if $NE neq ""} 
					setUpCarousels("NE");
				{/if}
			{literal}
		});
	</script>
{/literal}