{strip}
	<div class="text-center row">
		{if $showFavorites == 1}
			<button onclick="return VuFind.GroupedWork.showSaveToListForm(this, '{$recordDriver->getPermanentId()|escape}');" class="btn btn-sm addtolistlink">{translate text='Add to favorites'}</button>
		{/if}
	</div>
	<div class="text-center row">
		<div class="share-tools">
			<span class="share-tools-label">SHARE</span>
			{if $showTextThis == 1}
				<a href="#" title="Text Title" onclick="return VuFind.GroupedWork.showSmsForm(this, '{$recordDriver->getPermanentId()|escape:"url"}')"><img src="{img filename='sms-icon.png'}" alt="Text This"/></a>
			{/if}
			<a href="http://twitter.com/home?status={$url}/GroupedWork/{$recordDriver->getPermanentId()}/Home">
				<img src="{img filename='twitter-icon.png'}" alt="Share on Twitter"/>
			</a>
			<a href="http://www.facebook.com/sharer/sharer.php?u={$url}/GroupedWork/{$recordDriver->getPermanentId()}/Home">
				<img src="{img filename='facebook-icon.png'}" alt="Share on Facebook"/>
			</a>
			{if $showEmailThis == 1}
				<a href="{$path}/GroupedWork/{$recordDriver->getPermanentId()|escape:'url'}/Email?lightbox" onclick="return VuFind.ajaxLightbox('{$path}/Record/{$id|escape}/Email?lightbox', true)">
					<img src="{img filename='email-icon.png'}" alt="E-mail this"/>
				</a>
			{/if}
		</div>

	</div>
{/strip}