{strip}
	{if count($summary) > 0}
		{assign var=numDefaultItems value="0"}
		{assign var=numRowsShown value="0"}
		{foreach from=$summary item="item"}
			{if $item.displayByDefault && $numRowsShown<6}
				<div class="itemSummary">{$item.availableCopies} of {$item.totalCopies} @ <strong><span class="noBreak notranslate">{$item.shelfLocation}</span>: <span class="noBreak notranslate">{$item.callNumber}</span></strong></div>
				{assign var=numDefaultItems value=$numDefaultItems+$item.totalCopies}
				{assign var=numRowsShown value=$numRowsShown+1}
			{/if}
		{/foreach}
		{assign var=numRemainingCopies value=$totalCopies-$numDefaultItems}
		{if $numRemainingCopies > 0}
			<div class="itemSummary">
				&nbsp;&nbsp;<a href="#" onclick="return VuFind.showElementInPopup('Copy Summary', '#itemSummaryPopup_{$id|escapeCSS}_{$relatedManifestation.format|escapeCSS}');">
					View {if $numRemainingCopies > 1}{$totalCopies-$numDefaultItems} {/if}{if $numDefaultItems > 0}additional {/if}{if $numRemainingCopies > 1}copies{else}copy information{/if}
				</a>
			</div>
			<div id="itemSummaryPopup_{$itemSummaryId|escapeCSS}_{$relatedManifestation.format|escapeCSS}" class="itemSummaryPopup" style="display: none">
				<table class="table table-striped table-condensed itemSummaryTable">
					<thead>
					<tr>
						<th>Avail. Copies</th>
						<th>Location</th>
						<th>Call #</th>
					</tr>
					</thead>
					<tbody>
					{assign var=numRowsShown value=0}
					{foreach from=$summary item="item"}
						{if $item.displayByDefault}
							{assign var=numRowsShown value=$numRowsShown+1}
						{/if}
						{if !$item.displayByDefault || $numRowsShown >= 6}
							<tr {if $item.availableCopies}class="available" {/if}>
								<td>{$item.availableCopies} of {$item.totalCopies}</td>
								<td class="notranslate">{$item.shelfLocation}</td>
								<td class="notranslate">{$item.callNumber}</td>
							</tr>
						{/if}
					{/foreach}
					</tbody>
				</table>
			</div>
		{/if}
	{/if}
{/strip}