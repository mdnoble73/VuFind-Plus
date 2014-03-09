{strip}
	<div class="share-tools">
		<span class="share-tools-label">SHARE</span>
		{if $showTextThis == 1}
			<a href="#" title="Text Title" onclick="return VuFind.GroupedWork.showSmsForm(this, '{$recordDriver->getPermanentId()|escape:"url"}')"><img src="{img filename='sms-icon.png'}" alt="Text This"/></a>
		{/if}
		<a href="http://twitter.com/home?status={$recordDriver->getTitle()|urlencode}+{$url}/GroupedWork/{$recordDriver->getPermanentId()}/Home" target="_blank">
			<img src="{img filename='twitter-icon.png'}" alt="Share on Twitter"/>
		</a>
		<a href="http://www.facebook.com/sharer/sharer.php?u={$url}/GroupedWork/{$recordDriver->getPermanentId()}/Home" target="_blank">
			<img src="{img filename='facebook-icon.png'}" alt="Share on Facebook"/>
		</a>
		{if $showEmailThis == 1}
			<a href="{$path}/GroupedWork/{$recordDriver->getPermanentId()|escape:'url'}/Email?lightbox" onclick="return VuFind.ajaxLightbox('{$path}/GroupedWork/{$id|escape}/Email?lightbox', true)">
				<img src="{img filename='email-icon.png'}" alt="E-mail this"/>
			</a>
		{/if}
	</div>
{/strip}