{if $lightbox}
<div onmouseup="this.style.cursor='default';" id="popupboxHeader" class="header">
  <a onclick="hideLightbox(); return false;" href="">close</a>
  {translate text='Title Citation'}
</div>
<div id="popupboxContent" class="content">
{/if}
{if $citationCount < 1}
  {translate text="No citations are available for this record"}.
{else}
  <div style="text-align: left;">
    {if $apa}
      <b>{translate text="APA Citation"}</b>
      <p style="width: 95%; padding-left: 25px; text-indent: -25px;">
        {include file=$apa}
      </p>
    {/if}

    {if $mla}
      <b>{translate text="MLA Citation"}</b>
      <p style="width: 95%; padding-left: 25px; text-indent: -25px;">
        {include file=$mla}
      </p>
    {/if}
  </div>
  <div class="note">{translate text="Citation formats are based on standards as of July 2010."}</div>
{/if}
{if $lightbox}
</div>
{/if}