<script type="text/javascript" src="{$path}/services/EcontentRecord/ajax.js"></script>
<div data-role="content">
    {if $recordCount}
	    <p>
	      <strong>{$recordStart}</strong> - <strong>{$recordEnd}</strong>
	      {translate text='of'} <strong>{$recordCount}</strong>
	      {if $searchType == 'basic'}{translate text='for'}: <strong>{$lookfor|escape:"html"}</strong>{/if}
	    </p>
	{/if}
	{if $subpage}
      {include file=$subpage}
    {/if}
</div>