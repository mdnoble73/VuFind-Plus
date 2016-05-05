{strip}
	<div class="col-xs-12">
		<h2>
			{$title|escape}
		</h2>

		<img src="{$medium_image}" class="img-responsive">
		<audio width="100%" controls id="player">
			<source src="{$audioLink}" type="audio/mpeg">
		</audio>

{*//Moved to accordion
		{if $description}
			<div class="row">
				<div class="result-label col-sm-4">Description: </div>
				<div class="col-sm-8 result-value">
					{$description}
				</div>
			</div>
		{/if}
*}

{*//Moved to accordion
		{if $transcription}
			<div class="row">
				<div class="result-label col-xs-12">Transcription: </div>
				<div class="col-xs-12 result-value">
					{$transcription.text}
				</div>
			</div>
		{/if}
*}

		{include file="Archive/metadata.tpl"}
	</div>
{/strip}