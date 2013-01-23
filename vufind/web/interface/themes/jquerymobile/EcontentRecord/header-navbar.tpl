<div data-role="navbar">
	<ul>
		<li>
			<a {if $tab == 'Holdings'} class="ui-btn-active"{/if} rel="external" href="{$path}/EcontentRecord/{$id|escape:"url"}?subsection=Holdings">{translate text='Holdings'}</a>
		</li>
		<li>
			<a {if $tab == 'Description'} class="ui-btn-active"{/if} rel="external" href="{$path}/EcontentRecord/{$id|escape:"url"}?subsection=Description">{translate text='Description'}</a>
		</li>
		{if $hasTOC}
			<li>
				<a {if $tab == 'TOC'} class="ui-btn-active"{/if} rel="external" href="{$path}/EcontentRecord/{$id|escape:"url"}?subsection=TOC">{translate text='Contents'}</a>
			</li>
		{/if}
		{if true || $hasReviews}
			<li>
				<a {if $tab == 'Reviews'} class="ui-btn-active"{/if} rel="external" href="{$path}/EcontentRecord/{$id|escape:"url"}?subsection=Reviews">{translate text='Reviews'}</a>
			</li>
		{/if}
		{if false && $hasExcerpt}
			<li>
				<a {if $tab == 'Excerpt'} class="ui-btn-active"{/if} rel="external" href="{$path}/EcontentRecord/{$id|escape:"url"}?subsection=Excerpt">{translate text='Excerpt'}</a>
			</li>
		{/if}
		<li>
			<a {if $tab == 'UserComments'} class="ui-btn-active"{/if} rel="external" href="{$path}/EcontentRecord/{$id|escape:"url"}/UserComments">{translate text='Comments'}</a>
		</li>
	</ul>
</div>
