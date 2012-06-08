<div id="sidebar-wrapper"><div id="sidebar">
  {include file="MyResearch/menu.tpl"}
  {include file="Admin/menu.tpl"}
</div></div>
<div id="main-content">
  <h1>EContent Record</h1>
  {if $user && $user->hasRole('epubAdmin')}
  <div id='actions'>
    <div class='button'><a href='{$path}/EcontentRecord/{$eContentRecord->id}/Edit'>Edit</a></div>
    <div class='button'><a href='{$path}/EcontentRecord/{$eContentRecord->id}/Delete' onclick="return confirm('Are you sure you want to delete this EContent Record?');">Delete</a></div>
  </div>
  {/if}
  
  
  
  <div id='property'><span class='propertyLabel'>Title: </span><span class='propertyValue'>{$eContentRecord->title}</span></div>
  <div id='property'><span class='propertyLabel'>Author: </span><span class='propertyValue'>{$eContentRecord->author}</span></div>
  <div id='property'><span class='propertyLabel'>Description: </span><span class='propertyValue'>{$eContentRecord->description}</span></div>
  
  <div id='property'><span class='propertyLabel'>Subject: </span><span class='propertyValue'>{$eContentRecord->subject}</span></div>
  <div id='property'><span class='propertyLabel'>Notes: </span><span class='propertyValue'>{$eContentRecord->notes}</span></div>
</div>
