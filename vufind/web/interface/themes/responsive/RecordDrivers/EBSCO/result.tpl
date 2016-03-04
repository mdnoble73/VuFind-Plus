{strip}
{* //QUESTION: is this Depricated *}
<div id="record{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" class="resultsList row">
	<div class="col-tn-12">
		<div class="row">
			<strong>
				<strong>{$resultIndex}) </strong>
				<a href="{$summUrl}" class="title">{if !$summTitle|removeTrailingPunctuation}{translate text='Title not available'}{else}{$summTitle|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}</a>
			</strong>
		</div>

		<div class="row">
			<div class="resultDetails col-tn-12">
				{if $summAuthor}
					<div class="row hidden-phone">
						<div class="result-label col-md-3">{translate text='Author'}:</div>
						<div class="col-md-9 result-value">{$summAuthor|escape}</div>
					</div>
				{/if}

				{if strlen($summSourceDatabase)}
					<div class="row hidden-phone">
						<div class="result-label col-md-3">{translate text='Found in'}:</div>
						<div class="col-md-9 result-value">{$summSourceDatabase|escape}</div>
					</div>
				{/if}

				{if $summPublicationDates || $summPublishers || $summPublicationPlaces}
					<div class="row">

						<div class="result-label col-md-3">Published: </div>
						<div class="col-md-9 result-value">
							{$summPublicationPlaces.0|escape}{$summPublishers.0|escape}{$summPublicationDates.0|escape}
						</div>
					</div>
				{/if}

				{if strlen($summFormats)}
					<div class="row">
						<div class="result-label col-md-3">Format: </div>
						<div class="col-md-9 result-value">
							<span class="iconlabel">{translate text=$summFormats}</span>
						</div>
					</div>
				{/if}

				{if $summPhysical}
					<div class="row hidden-phone">
						<div class="result-label col-md-3">{translate text='Physical Desc'}:</div>
						<div class="col-md-9 result-value">{$summPhysical.0|escape}</div>
					</div>
				{/if}

				<div class="row hidden-phone">
					<div class="result-label col-md-3">{translate text='Full Text Available'}:</div>
					<div class="col-md-9 result-value">{if $summHasFullText}Yes{else}No{/if}</div>
				</div>

			</div>
		</div>
	</div>
</div>
{/strip}