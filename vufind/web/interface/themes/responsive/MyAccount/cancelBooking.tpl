{strip}
	<div class="contents">
		{if $cancelResults.success}
			<div class="alert alert-success">{$cancelResults.message}</div>
		{else}
			{if is_array($cancelResults.message)}
				<div class="alert alert-warning"><strong>{$numCancelled} of {$totalCancelled}</strong> bookings were cancelled successfully.</div>
				{foreach from=$cancelResults.message item=message}
					<div class='alert alert-danger'>{$message}</div>
				{/foreach}
			{else}
				<div class='alert alert-danger'>{$cancelResults.message}</div>
			{/if}
		{/if}
	</div>
{/strip}