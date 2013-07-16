<div id='renew_results'>
	{if $renew_message_data.Unrenewed == 0}
		<div>All items were renewed successfully.</div>
	{else}
		<div>{$renew_message_data.Renewed} of {$renew_message_data.Total} items were renewed successfully.</div>
	{/if}
	<p>
		Please take note of the new due date/s and return any items that could not be renewed. Items on Hold for another patron cannot be renewed.
	</p>
</div>