{strip}
	<div class="col-xs-12">
		<h2>
			{$title|escape}
		</h2>

		<div class="main-project-image">
			{* TODO: restrict access to original image *}
			<a href="{$original_image}">
				<img src="{$large_image}" class="img-responsive">

			</a>
		</div>

{* //Moved to accordion
		{if $description}
			<div class="row">
				<div class="result-label col-sm-4">Description: </div>
				<div class="col-sm-8 result-value">
					{$description}
				</div>
			</div>
		{/if}
*}

		{include file="Archive/metadata.tpl"}
	</div>
{/strip}
