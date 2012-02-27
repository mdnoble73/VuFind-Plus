{foreach from=$recordSet item=record name="recordLoop"}
  {if ($smarty.foreach.recordLoop.iteration % 2) == 0}
  <div class="result alt record{$smarty.foreach.recordLoop.iteration}">
  {else}
  <div class="result record{$smarty.foreach.recordLoop.iteration}">
  {/if}
  
    <div class="yui-ge">
      <div class="yui-u first">
        <div class="resultitem">
          <div class="resultItemLine1">
            <a href="{$url}/Authority/Record?id={$record.id|escape:"url"}" class="title">{if $record.heading}{$record.heading|escape}{else}{translate text='Heading unavailable.'}{/if}</a>
          </div>

          <div class="resultItemLine2">
          {if $record.see_also}
            {translate text="See also"}:<br/>
            {foreach from=$record.see_also item=current}
              <a href="Search?lookfor=%22{$current|escape:"url"}%22&amp;type=MainHeading">{$current|escape}</a><br/>
            {/foreach}
          {/if}
          </div>

          <div class="resultItemLine3">
          {if $record.use_for}
            {translate text="Use for"}:<br/>
            {foreach from=$record.use_for item=current}
              {$current|escape}<br/>
            {/foreach}
          {/if}
          </div>
        </div>
      </div>
    </div>

  </div>
{/foreach}
