<script type="text/javascript">
_translations['Your book bag is empty'] = '{translate text="Your book bag is empty"}';
</script>
{if !empty($records)}
  <ul class="results bookbag" data-role="listview" data-split-icon="minus" data-split-theme="d" data-inset="false">
    {foreach from=$records item=record}
    <li>
      <a rel="external" href="{$path}/Record/{$record.id|escape}">
      <div class="result">
        <h3>{$record.title|trim:'/:'|escape}</h3>
        {if !empty($record.author)}
          <p>{translate text='by'} {$record.author}</p>
        {/if}
        {if !empty($record.format)}
        <p>
          {foreach from=$record.format item=format}
            <span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
          {/foreach}
        </p>
        {/if}
      </div>
      </a>
      <a href="#" data-record-id="{$record.id|escape}" title="{translate text='Remove'}" class="remove_from_book_bag">{translate text="Remove"}</a>
    </li>
    {/foreach}
  </ul>
  <div data-role="controlgroup">
    {*
    <a href="{$path}/Cart/Email" data-role="button" rel="external">{translate text="Email"}</a>
    <a href="{$path}/Cart/Save" data-role="button" rel="external">{translate text="Save"}</a>
    *}
    <a href="#" class="request_book_bag" data-role="button">{translate text="Place Request"}</a>
    <a href="#" class="empty_book_bag" data-role="button">{translate text="Empty"}</a>
  </div>
{else}
  <p>{translate text='Your book bag is empty'}.</p>
{/if}
