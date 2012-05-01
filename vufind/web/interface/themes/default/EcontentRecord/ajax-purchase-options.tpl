<div id="popupboxHeader" class="header">
	<a onclick="hideLightbox(); return false;" href="">close</a>
	{translate text='Purchase Options'}
</div>
<div id="popupboxContent" class="content">
	<div id='purchaseOptions'>
		{if $errors}
			<div class="errors">
				{foreach from=$errors item=error}
					<div class="error">{$error}</div>
				{/foreach}
			</div>
		{else}
			This title is available from the following stores:
			<div class="purchseOptionLinks">
				{foreach from=$purchaseLinks item=purchaseLink}
					<div class='purchaseTitle button'><a href='/EcontentRecord/{$id}/Purchase?store={$purchaseLink.storeName|escape:"url"}{if $purchaseLink.field856Index}&index={$purchaseLink.field856Index}{/if}' target='_blank'>{$purchaseLink.storeName}</a></div>
				{/foreach}
			</div>
		{/if}
	</div>
</div>