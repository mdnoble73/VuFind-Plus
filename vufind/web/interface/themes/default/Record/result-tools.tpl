{* Place hold link *}
{if $showHoldButton}
	<div class='requestThisLink resultAction button' id="placeHold{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" style="display:none">
		<a href="{$path}/Record/{$summId|escape:"url"}/Hold">{translate text="Place Hold"}</a>
	</div>
{/if}
{if $showMoreInfo !== false}
<div class="resultAction"><a href="{$path}/Record/{$summId|escape:"url"}/Home?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}"><span class="silk information">&nbsp;</span>More Info</a></div>
{/if}
<div class="resultAction"><a href="#" class="cart" onclick="addToBag('{$id|escape}', '{$summTitle|replace:'"':''|escape:'javascript'}', this);"><span class="silk cart">&nbsp;</span>{translate text="Add to cart"}</a></div>
<div class="resultAction"><a href="{$path}/Record/{$summId|escape:"url"}/SimilarTitles"><img src="/images/silk/arrow_switch.png">&nbsp;More Like This</a></div>
{if $showComments == 1}
	{include file="Record/title-review.tpl"}
{/if}
{if $showFavorites == 1}
<div id="saveLink{$recordId|escape}" class="resultAction">
	<a href="{$path}/Resource/Save?id={$recordId|escape:"url"}&amp;source=VuFind" onclick="getSaveToListForm('{$recordId|escape}', 'VuFind'); return false;"><span class="silk star_gold">&nbsp;</span>{translate text='Add to favorites'}</a>
	{if $user}
	<script type="text/javascript">
		getSaveStatuses('{$recordId|escape:"javascript"}');
	</script>
	{/if}
</div>
{/if}