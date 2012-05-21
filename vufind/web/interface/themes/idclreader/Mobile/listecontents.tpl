<ul data-role="listview" data-theme="a">
{if $LIST neq ''}
	{foreach from=$LIST item=ebook}
		<li>
			<a href="/EcontentRecord/{$ebook.id|substr:14:10}">
				<div class="my_icon_wrapper">
					<div class='noFloated'>
						<img src="{$ebook.small_image}" />
					</div>
				</div>
				<h3>{$ebook.title}</h3>
				<p>{$ebook.description}</p>
			</a>
		</li>
	{/foreach}
	
	{if $NEXTPAGE neq ''}
		<li>
			<a href="/Mobile/{$ACTION}?page={$NEXTPAGE}" data-transition="slidefade">NEXT</a>
		</li>
	{/if}
	
{else}
	<li class='ui-list-item-center'>
		There are no items on this category.
	</li>
{/if}
</ul>