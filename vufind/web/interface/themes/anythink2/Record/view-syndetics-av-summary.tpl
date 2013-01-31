<div class='avSummaryTitle'>Summary</div>
<div class='summary'>{$avSummaryData.summary}</div>

<div class='avSummaryTitle'>Track Listing</div>
<div class='trackListing'>
{foreach from=$avSummaryData.trackListing item=track}
<div class='track'>
<span class='trackNumber'>{$track.number}</span>
<span class='trackName'>{$track.name}</span>
</div>
{/foreach}
</div>