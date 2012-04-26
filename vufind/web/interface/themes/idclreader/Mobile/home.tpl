<label for="search-basic">Search Input:</label>
<input type="search" name="search" id="searc-basic" value="" data-theme='a'/>

		<h3 class='titleNE'>New Ebooks</h3>
		<div id='imageGallery_NE' {if $NE neq ""} class='itemsInside' {/if}>
			{if $NE neq ""}
					<ul id="carousel_NE" class="jcarousel-skin-tango">
					{foreach from=$NE item=record}
						<li>
							<div class='item_wrapper'>
								<img src='{$record.image}' />
								<div class='eContentTitle'>
									<div class='widthEContentTitle'>
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
	            <li><a href="/Mobile/HighestRated/" rel="external">Highest Rated</a></li>
		        <li><a href="/Mobile/MostPopular/" rel="external">Most Popular</a></li>
		        <li><a href="/Mobile/FreeEbooks/" rel="external">Free eBooks</a></li>
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
				    wipeLeft: function() { $('#carousel_' + id).jcarousel('next');},
				    wipeRight: function() {$('#carousel_' + id).jcarousel('prev'); },
				    min_move_x: 20,
				    min_move_y: 20,
				    preventDefaultEvents: true
				});
			}
			{/literal}
				{if $NE neq ""} setUpCarousels("NE"); {/if}
			{literal}
		});
	</script>
{/literal}