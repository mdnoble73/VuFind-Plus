<div id='checkout_results' data-role="page" data-theme="b">
	<div data-role="header" data-theme="b">
		<h1>Checkout Results</h1>
	</div>
	<div class = "content">
		{if $checkout_message_data.successful == 'all'}
			<div class='checkout_result successful'>
			{if count($checkout_message_data.titles) > 1}
				All checkouts were successful.
			{else}
				Your checkout was successful.
			{/if}
			</div>
		{else}
				<div class='checkout_result partial'>Some titles could not be checked out to you.</div>
		{/if}
		<ol class='hold_result_details'>
		{foreach from=$checkout_message_data.titles item=title_data}
			<li class='title_checkout_result'>
			<span class='checkout_item_title'>
			{$title_data.title}
			</span>
			<br /><span class='{if $title_data.result == true}hold_result_title_ok{else}hold_result_title_failed{/if}'>{$title_data.message}</span>
			</li>
		{/foreach}
		</ol>
		<a href='#' data-role="button" onclick='window.location.href=window.location.href'>Close</a>
	</div>
</div>