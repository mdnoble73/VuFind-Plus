<div id='renew_results'>
	<div class='hold_result_title header'>
		Renewal Results
		<a href="#" onclick='hideLightbox();return false;' class="closeIcon">Close <img src="{$path}/images/silk/cancel.png" alt="close" /></a>
	</div>
	<div class = "content">
		{if $renew_message_data.Unrenewed == 0}
			<div class="successful">All items were renewed successfully.</div>
		{else}
			<div class="error"><strong>{$renew_message_data.Renewed} of {$renew_message_data.Total}</strong> items were renewed successfully.</div>
		{/if}
		<p>
		Please take note of the new due date/s and return any items that could not be renewed. Items on Hold for another patron cannot be renewed.
		</p>
	</div>
</div>