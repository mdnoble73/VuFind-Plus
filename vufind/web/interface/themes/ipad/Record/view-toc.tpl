{if $tocTemplate}
  <h4>{translate text='Table of Contents'}:</h4>
  {include file=$tocTemplate}
{else}
  {translate text="Table of Contents unavailable"}.
{/if}