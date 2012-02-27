<div id='renew_results'>
	<div class='hold_result_title header'>
		Renewal Results
		<a href="#" onclick='hideLightbox();return false;' class="closeIcon">Close <img src="{$path}/images/silk/cancel.png" alt="close" /></a>
	</div>
	<div class = "content">
		{if $renew_message_data.Unrenewed == 0}
			<div>All items were renewed successfully.</div>
		{else}
			<div>{$renew_message_data.Renewed} of {$renew_message_data.Total} items were renewed successfully.</div>
		{/if}
	</div>
</div>