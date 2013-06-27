<div class="">
	{* Place hold link *}
	{if $showHoldButton}
		<div class='requestThisLink resultAction' id="placeHold{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" style="display:none">
			<a href="{$path}/Record/{$summId|escape:"url"}/Hold">{translate text="Place Hold"}</a>
		</div>
	{/if}
	{if $showMoreInfo !== false}
	<div class="resultAction"><a href="{$recordUrl}"><span class="silk information">&nbsp;</span>More Info</a></div>
	{/if}
	{*
	<div class="resultAction"><a href="#" class="cart" onclick="return addToBag('{$summId|escape}', '{$summTitle|replace:'"':''|escape:'javascript'}', '{$summShortId}');"><span class="silk cart">&nbsp;</span>{translate text="Add to cart"}</a></div>
	*}
	<div class="resultAction"><a href="{$path}/Record/{$summId|escape:"url"}/SimilarTitles"><img src="/images/silk/arrow_switch.png">&nbsp;More Like This</a></div>
	{if $showComments == 1}
		{include file="Record/title-review.tpl"}
	{/if}
	{if $showFavorites == 1}
	<div id="saveLink{$recordId|escape}" class="resultAction">
		<a href="{$path}/Resource/Save?id={$summId|escape:"url"}&amp;source=VuFind" onclick="getSaveToListForm('{$summId|escape}', 'VuFind'); return false;"><span class="silk star_gold">&nbsp;</span>{translate text='Add to favorites'}</a>
		{if $user}
		<script type="text/javascript">
			//getSaveStatuses('{$recordId|escape:"javascript"}');
		</script>
		{/if}
	</div>
	{/if}
	{if $showTextThis == 1}
		<a class="" href="{$path}/Record/{$id|escape:"url"}/SMS" onclick='ajaxLightbox("{$path}/Record/{$id|escape}/SMS?lightbox", "#smsLink"); return false;'><span class="silk phone">&nbsp;</span>{translate text="Text this"}</a>
	{/if}
	{if $showEmailThis == 1}
		<a class="" href="{$path}/Record/{$id|escape:"url"}/Email" onclick='ajaxLightbox("{$path}/Record/{$id|escape}/Email?lightbox", "#mailLink"); return false;'><span class="silk email">&nbsp;</span>{translate text="Email this"}</a>
	{/if}
</div>