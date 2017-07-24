{strip}
	<h2>In {$interLibraryLoanName}</h2>
	<p id='prospectorSearchResultsNote'>Request items from other {$interLibraryLoanName} libraries to be delivered to your local library for pickup.</p>
	<div id='prospectorSearchResultsFooter'>
		<div id='moreResultsFromProspector'>
			<button class="btn btn-info" onclick="window.open ('{$interLibraryLoanUrl}', 'child'); return false">See more results in {$interLibraryLoanName}</button>
		</div>
		<div class='clearfix'>&nbsp;</div>
	</div>

{/strip}