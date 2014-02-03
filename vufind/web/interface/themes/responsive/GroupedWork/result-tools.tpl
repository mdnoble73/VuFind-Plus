{strip}
	<div class="btn-group-vertical">
		{if $showFavorites == 1}
			<button onclick="return VuFind.GroupedWork.showSaveToListForm(this, '{$summId|escape}');" class="btn btn-sm ">{translate text='Add to favorites'}</button>
		{/if}
		{if $showTextThis == 1}
			<button href="{$path}/GroupedWork/{$id|escape:"url"}/SMS" onclick='return VuFind.ajaxLightbox("{$path}/Record/{$id|escape}/SMS?lightbox")' class="btn btn-sm ">{translate text="Text this"}</button>
		{/if}
		{if $showEmailThis == 1}
			<button href="{$path}/GroupedWork/{$id|escape:'url'}/Email?lightbox" onclick="return VuFind.ajaxLightbox('{$path}/Record/{$id|escape}/Email?lightbox', true)" class="btn btn-sm ">
				{translate text="Email this"}
			</button>
		{/if}
	</div>
{/strip}