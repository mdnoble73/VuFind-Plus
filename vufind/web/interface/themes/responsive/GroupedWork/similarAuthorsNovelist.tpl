{strip}
	<h4>Similar Authors</h4>
	<div id="similarAuthorsNoveList" class="striped div-striped">
		{foreach from=$similarAuthors item=author name="recordLoop"}
			<div class="">
				{* This is raw HTML -- do not escape it: *}
				<h3><a href="{$author.link}">{$author.name}</a></h3>
				<div class="reason">
					{$author.reason}
				</div>
			</div>
		{/foreach}
	</div>
{/strip}