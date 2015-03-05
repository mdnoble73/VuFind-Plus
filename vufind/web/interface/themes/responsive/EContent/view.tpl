<div id="page-content" class="content">
  <div id="sidebar">
    {include file="MyResearch/menu.tpl"}
    
    {include file="Admin/menu.tpl"}
  </div>
  
  <div id="main-content">
    <h1>EPUB {$epubFile->id}</h1>
    {if $user && $user->hasRole('epubAdmin')}
    <div id='actions'>
      <div class='button'><a href='{$path}/EContent/{$epubFile->id}/Edit'>Edit</a></div>
      <div class='button'><a href='{$path}/EContent/{$epubFile->id}/Delete' onclick="return confirm('Are you sure you want to delete this EPub?');">Delete</a></div>
    </div>
    {/if}
    
    <div id='property'><span class='propertyLabel'>Title: </span><span class='propertyValue'>{$epubFile->title}</span></div>
    <div id='property'><span class='propertyLabel'>Author: </span><span class='propertyValue'>{$epubFile->author}</span></div>
    <div id='property'><span class='propertyLabel'>Type: </span><span class='propertyValue'>{$epubFile->type}</span></div>
    <div id='property'><span class='propertyLabel'>Source: </span><span class='propertyValue'>{$epubFile->source}</span></div>
    {if $epubFile->type == 'mp3'}
      <div id='property'><span class='propertyLabel'>Folder: </span><span class='propertyValue'>{$epubFile->folder}</span></div>
    {else}
      <div id='property'><span class='propertyLabel'>Filename: </span><span class='propertyValue'>{$epubFile->filename}</span></div>
    {/if}
    <div id='property'><span class='propertyLabel'>Cover: </span><span class='propertyValue'>{if strlen($epubFile->cover) > 0}{$epubFile->cover}{else}No cover image{/if}</span></div>
    <div id='property'><span class='propertyLabel'>Usage Restrictions: </span><span class='propertyValue'>{if $epubFile->hasDRM == 0}No Usage Restrictions{elseif $epubFile->hasDRM == 1}Adobe Content Server ({$epubFile->acsId}){else}Single use per copy{/if}</span></div>
    <div id='property'><span class='propertyLabel'>Related Record: </span><span class='propertyValue'><a href="{$path}/Record/{$epubFile->relatedRecords}/Home">{$epubFile->relatedRecords}</a></span></div>
    <div id='property'><span class='propertyLabel'>Number of copies: </span><span class='propertyValue'>{$epubFile->availableCopies}</span></div>
    <div id='property'><span class='propertyLabel'>Description: </span><br/><span class='propertyValue'>{$epubFile->description}</span></div>
    <div id='property'><span class='propertyLabel'>Notes: </span><span class='propertyValue'>{$epubFile->notes}</span></div>
  </div>
</div>