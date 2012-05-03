<script type="text/javascript" src="{$path}/services/EcontentRecord/ajax.js"></script>
<div class="ui-grid-a" id='searchForm'>
	<div class="ui-block-a">
		<img src='{$bookCoverUrl}' heigth='90'/>
	</div>
	<div class="ui-block-b">
		<h2>{$eContentRecord->title}</h2>
		<p>
			{if $eContentRecord->description eq ""}
				No description available
			{else}
				{$eContentRecord->description|escape}
			{/if}
		</p>
		<p>
			<div id='holdingsSummaryPlaceholder'>
			
			</div>
		</P
		<p>
			<div class='checkoutLink' id="checkout{$id|escape:"url"}" style="display:none">
				<a href="{$path}/EcontentRecord/{$id|escape:"url"}/Checkout" data-role="button" data-mini="true" data-theme='b'>
					Checkout
				</a>
			</div>
			<div class='requestThisLink' id="placeHold{$id|escape:"url"}" style="display:none">
				<a href="{$path}/EcontentRecord/{$id|escape:"url"}/Hold" data-role="button" data-mini="true" data-theme='b'>
					Place Hold
				</a>
			</div>
			 <div class='addToWishListLink' id="addToWishList{$id|escape:"url"}" style="display:none">
			 	<a href="{$path}/EcontentRecord/{$id|escape:"url"}/AddToWishList" data-role="button" data-mini="true" data-theme='b'>
			 		Add to WishList
			 	</a>
			 </div>
		</p>
	</div>
</div>
<div id='holdingsPlaceholder' style='margin-top:30px;'>
			
</div>

{literal}
	<script type="text/javascript">		
		function refreshLayoutIdclReaderEcontentRecord()
		{
			$("#headerListAccessOnline").listview();
			$("#SummaryEbook").listview();
		}
	
		$(document).ready(function()
		{
			GetEContentHoldingsInfo('{/literal}{$id|escape:"url"}{literal}','', refreshLayoutIdclReaderEcontentRecord);
		});
	</script>
{/literal}