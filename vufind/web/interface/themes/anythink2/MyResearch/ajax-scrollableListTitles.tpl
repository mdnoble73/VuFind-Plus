<ListTitles>
<![CDATA[
<div id='listWrapper{$list->id}' class='listWrapper'>
<ul id='list{$list->id}' class='jcarousel-skin-tango'>
{foreach from=$list->titles item=suggestion name="recordLoop"}
  {counter name=starCounter assign=starIndex}
  <li class='listTitle'>
    {* Display the book cover *}
    <div class='suggestedTitleInfo'>
      {* Display the cover *}
      <a href='{$path}/Record/{$suggestion.id}'>
      <img src="{$coverUrl}/bookcover.php?id={$suggestion.id}&amp;isn={$suggestion.isbn.0|@formatISBN}&amp;size=small&amp;upc={$suggestion.upc.0}&amp;category={$suggestion.format_category.0|escape:"url"}" class="suggestionImage" alt="{translate text='Cover Image'}" />
      </a>
      {* Let the user rate this title *}
      {if $showRatings == 1}
      {include file="Record/title-rating.tpl" ratingClass="suggestionRating" recordId=$suggestion.id shortId=$suggestion.shortId starPostFixId=_list$starIndex}
      {/if}
    </div>
    {* Display the title with a link to the actual record. *}
    <div class='suggestionTitleTitle'>
    <a href='{$path}/Record/{$suggestion.id}'>{$suggestion.title_short|regex_replace:"/(\/|:)$/":""|escape}</a>
    </div>
  </li>
{/foreach}
</ul>
</div>
<script type="text/javascript">
$('#list{$list->id}').jcarousel();
doGetRatings();
</script>
]]></ListTitles>