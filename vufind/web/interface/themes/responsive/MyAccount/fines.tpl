{if $user->cat_username}
	<h2>{translate text='Fines'}</h2>
	{if $userNoticeFile}
		{include file=$userNoticeFile}
	{/if}

	{if count($fines) > 0}
		<table id="finesTable" class="datagrid">
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
	{else}
		<p>You do not have any fines within the system.</p>
	{/if}
{else}
	You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login.
{/if}
