{strip}
{if $offline}
	<div>The circulation system is currently offline.  Please check back later for a detailed list of which copies are in the catalog.</div>
{else}
	<table border="0" width ="100%" class="holdingsTable">
		<thead>
			<tr>
			<th>Location</th>
			<th>Call#</th>
			<th>Status</th>
			</tr>
		</thead>
		<tbody>
		{foreach from=$holdings item=holding1}
			{foreach from=$holding1 item=holding}
			<tr >
				{* Location *}
				<td style = "padding-bottom:5px;"><span><strong>
					{$holding.location|escape}
					{if $holding.locationLink} (<a href='{$holding.locationLink}' target='_blank'>Map</a>){/if}
					</strong></span>
				</td>

				{* Call# *}
				<td style = "padding-bottom:5px;">
					{$holding.callnumber|escape}
					{if $holding.link}
						{foreach from=$holding.link item=link}
							<a href='{$link.link}' target='_blank'>{$link.linkText}</a><br />
						{/foreach}
					{/if}
				</td>

				{* Status *}
				<td style = "padding-bottom:5px;">
					{if $holding.reserve == "Y"}
						{$holding.statusfull}
					{else}
						{if $holding.availability}
								<span class="available">{$holding.statusfull}{if $holding.holdable == 0 && $showHoldButton} <label class='notHoldable' title='{$holding.nonHoldableReason}'>(Not Holdable)</label>{/if}</span>
						{else}
								<span class="checkedout">{$holding.statusfull}{if $holding.holdable == 0 && $showHoldButton} <label class='notHoldable' title='{$holding.nonHoldableReason}'>(Not Holdable)</label>{/if}</span>
						{/if}
					{/if}
				</td>
			</tr>
			{/foreach}
			{foreachelse}
				No Copies Found
			{/foreach}
		</tbody>
	</table>
{/if}
{/strip}