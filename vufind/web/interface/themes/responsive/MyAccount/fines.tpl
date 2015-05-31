{if $user->cat_username}
	{if $profile.web_note}
		<div class="row">
			<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile.web_note}</div>
		</div>
	{/if}

	{if $profile.numHoldsAvailableTotal && $profile.numHoldsAvailableTotal > 0}
		<div class="text-info text-center alert alert-info"><a href="/MyAccount/Holds" class="alert-link">You have {$profile.numHoldsAvailableTotal} holds ready for pick up.</a></div>
	{/if}

	<h2>{translate text='Fines'}</h2>

	{if count($fines) > 0}
		{if $profile.fines}
			<div class="alert alert-info">
				Your account has <strong>{$profile.fines}</strong> in fines.
			</div>
		{/if}

		<table id="finesTable" class="table table-striped">
			<thead>
				<tr>
					{if $showDate}
						<th>Date</th>
					{/if}
					{if $showReason}
						<th>Message</th>
					{/if}
					<th>Title</th>
					<th>Fine/Fee Amount</th>
					{if $showOutstanding}
						<th>Amount Outstanding</th>
					{/if}
				</tr>
			</thead>
			<tbody>
				{foreach from=$fines item=fine}
					<tr>
						{if $showDate}
							<td>{$fine.date}</td>
						{/if}
						{if $showReason}
							<td>{$fine.reason}</td>
						{/if}
						<td>{$fine.message}</td>
						<td>{$fine.amount}</td>
						{if $showOutstanding}
							<td>{$fine.amount_outstanding}</td>
						{/if}
					</tr>
				{/foreach}
			</tbody>
		</table>

		{* Pay Fines Button *}
		{if $showEcommerceLink && $profile.finesval > $minimumFineAmount}
			<a href='{$ecommerceLink}' ><div class="btn btn-sm btn-primary">{if $payFinesLinkText}{$payFinesLinkText}{else}Click to Pay Fines Online{/if}</div></a>
		{/if}

	{else}
		<p class="alert alert-success">You do not have any fines within the system.</p>
	{/if}
{else}
	You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login.
{/if}
