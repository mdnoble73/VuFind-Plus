<script type="text/javascript" src="{$path}/services/Record/ajax.js"></script>
{if (isset($title)) }
<script type="text/javascript">
    alert("{$title}");
</script>
{/if}
<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first">
      <b class="btop"><b></b></b>
        <div class="toolbar">
        <ul>
            <li><a href="{$path}/Record/{$id|escape:"url"}/Cite" class="cite" onclick="getLightbox('Record', 'Cite', '{$id|escape}', null, '{translate text="Cite this"}'); return false;">{translate text="Cite this"}</a></li>
            <li><a href="{$path}/Record/{$id|escape:"url"}/SMS" class="sms" onclick="getLightbox('Record', 'SMS', '{$id|escape}', null, '{translate text="Text this"}'); return false;">{translate text="Text this"}</a></li>
            <li><a href="{$path}/Record/{$id|escape:"url"}/Email" class="mail" onclick="getLightbox('Record', 'Email', '{$id|escape}', null, '{translate text="Email this"}'); return false;">{translate text="Email this"}</a></li>
            <li><a href="{$path}/Record/{$id|escape:"url"}/Export?style=endnote" class="export" onclick="toggleMenu('exportMenu'); return false;">{translate text="Import Record"}</a><br />
              <ul class="menu" id="exportMenu">
                <li><a href="{$path}/Record/{$id|escape:"url"}/Export?style=refworks">{translate text="Import to"} RefWorks</a></li>
                <li><a href="{$path}/Record/{$id|escape:"url"}/Export?style=endnote">{translate text="Import to"} EndNote</a></li>
                {* not implemented yet: <li><a href="{$path}/Record/{$id|escape:"url"}/Export?style=zotero">{translate text="Import to"} Zotero</a></li> *}
              </ul>
            </li>
            <li id="saveLink"><a href="{$path}/Resource/Save?id={$id|escape:"url"}&amp;source=VuFind" class="fav" onclick="getSaveToListForm('{$id|escape}', 'VuFind'); return false;">{translate text="Add to favorites"}</a></li>
          	<li id="Holdings"><a href="#holdings" class ="holdings">{translate text="Holdings"}</a></li>
          </ul>
        </div>
        <script type="text/javascript">
          getSaveStatus('{$id|escape:"javascript"}', 'saveLink');
          {if $isbn || $upc}
          GetEnrichmentInfo('{$id|escape:"url"}', '{$isbn|escape:"url"}', '{$upc|escape:"url"}');
	        {/if}
	        {if $enablePospectorIntegration == 1}
	          GetProspectorInfo('{$id|escape:"url"}');
	        {/if}
        </script>
       {if $error}<p class="error">{$error}</p>{/if} 
   <div id = "fullcontent">
      	<div id = "fullinfo">
   	 

        {* Display Book Cover *}
        <div id = "clearcover">  
            {if $isbn}
            <div class="alignleft">
                <a href="{$coverUrl}/bookcover.php?id={$id}&amp;isn={$isbn|@formatISBN}&amp;size=large&amp;category={$record.format_category.0|escape:"url"}">              
                    <img alt="{translate text='Book Cover'}" class="recordcover" src="{$coverUrl}/bookcover.php?isn={$isbn|@formatISBN}&amp;size=medium&amp;category={$record.format_category.0|escape:"url"}">
                </a><br />
	   <a href="{$path}/Record/{$id|escape:"url"}/Hold" class="hold">{translate text="Request This Title"}</a><br />
            </div>
            {else}
            {* <img src="{$coverUrl}/bookcover.php?id={$id}&amp;category={$record.format_category.0|escape:"url"}" alt="{translate text='No Cover Image'}"> *}
     	     <div class="alignleft">
	     <a href="{$path}/Record/{$id|escape:"url"}/Hold" class="hold">{translate text="Request This Title"}</a><br />
	     </div>
	    {/if}
         
          {* End Book Cover *}
        </div>  
        {if $goldRushLink}
        <div id ="titledetails">
        <a href='{$goldRushLink}' target='_blank'>Check for online articles</a>
        </div>
        {/if}
          <div id ="titledetails">
          {assign var=marcField value=$marc->getField('100')}
            {if $marcField}
      
              {translate text='Main Author'}: <br />
              <a href="{$path}/Author/Home?author={$marcField|getvalue:'a'|escape:"url"}{if $marcField|getvalue:'b'} {$marcField|getvalue:'b'|escape:"url"}{/if}{if $marcField|getvalue:'c'} {$marcField|getvalue:'c'|escape:"url"}{/if}{if $marcField|getvalue:'d'} {$marcField|getvalue:'d'|escape:"url"}{/if}">{$marcField|getvalue:'a'|escape}{if $marcField|getvalue:'b'} {$marcField|getvalue:'b'|escape}{/if}{if $marcField|getvalue:'c'} {$marcField|getvalue:'c'|escape}{/if}{if $marcField|getvalue:'d'} {$marcField|getvalue:'d'|escape}{/if}</a>
            
            {/if}
        </div>  
        <div id ="titledetails">
         {assign var=marcField value=$marc->getField('110')}
            {if $marcField}
              {translate text='Corporate Author'}: <br />
              {$marcField|getvalue:'a'|escape}
            </tr>
            {/if}
            </div>
	<div id = "titledetails">
            {assign var=marcField value=$marc->getFields('700')}
            {if $marcField}
            <tr valign="top">
              {translate text='Contributors'}: 
              
                {foreach from=$marcField item=field name=loop}
                  <br /><a href="{$path}/Author/Home?author={$field|getvalue:'a'|escape:"url"}{if $field|getvalue:'b'} {$field|getvalue:'b'|escape:"url"}{/if}{if $field|getvalue:'c'} {$field|getvalue:'c'|escape:"url"}{/if}{if $field|getvalue:'d'} {$field|getvalue:'d'|escape:"url"}{/if}">{$field|getvalue:'a'|escape} {$field|getvalue:'b'|escape} {$field|getvalue:'c'|escape} {$field|getvalue:'d'|escape}</a>{if !$smarty.foreach.loop.last}, {/if}
                {/foreach}
             
            {/if}
        </div>
        <div id = "titledetails">
         {translate text='Format'}:
              {if is_array($recordFormat)}
                {foreach from=$recordFormat item=displayFormat name=loop}
                  <span class="iconlabel {$displayFormat|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$displayFormat}</span>
                {/foreach}
              {else}
                <span class="iconlabel {$recordFormat|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$recordFormat}</span>
              {/if}  
        </div>
        <div class ="titledetails">
            {assign var=marcField value=$marc->getFields('300')}
            {if $marcField}
                {foreach from=$marcField item=field name=loop}
                  {$field|getvalue:'a'|regex_replace:"/[\/|;:]$/":""|regex_replace:"/p\./":"pages"|escape}<br />
                {/foreach}
            {/if}
        </div>
        <div id = "titledetails">
              {translate text='Language'}:
              {foreach from=$recordLanguage item=lang}{$lang|escape}<br />{/foreach}
        </div>
        <div id = "titledetails">
        {assign var=marcField value=$marc->getFields('260')}
            {if $marcField}
         
              {translate text='Published'}:<br />
              
                {foreach from=$marcField item=field name=loop}
                  {$field|getvalue:'a'|escape} {$field|getvalue:'b'|escape} {$field|getvalue:'c'|escape}<br />
                {/foreach}
            {/if}
            </div>
            <div id ="titledetails">
            {assign var=marcField value=$marc->getFields('250')}
            {if $marcField}
              {translate text='Edition'}: <br />
              
                {foreach from=$marcField item=field name=loop}
                  {$field|getvalue:'a'|escape}<br />
                {/foreach}
              </td>
            </tr>
            {/if}
        </div>
        <div id = "titledetails">
        {* Load the three possible series fields -- 440 is deprecated but
               still exists in many catalogs. *}
            {assign var=marcField440 value=$marc->getFields('440')}
            {assign var=marcField490 value=$marc->getFields('490')}
            {assign var=marcField830 value=$marc->getFields('830')}
            
            {* Check for 490's with indicator 1 == 0; these should be displayed
               since they will have no corresponding 830 field.  Other 490s would
               most likely be redundant and can be ignored. *}
            {assign var=visible490 value=0}
            {if $marcField490}
              {foreach from=$marcField490 item=field}
                {if $field->getIndicator(1) == 0}
                  {assign var=visible490 value=1}
                {/if}
              {/foreach}
            {/if}
            
            {* Display series section if at least one series exists. *}
            {if $marcField440 || $visible490 || $marcField830}
            <tr valign="top">
              <th>{translate text='Series'}: </th>
              <td>
                {if $marcField440}
                  {foreach from=$marcField440 item=field name=loop}
                    <a href="{$path}/Search/Results?lookfor=%22{$field|getvalue:'a'|escape:"url"}%22&amp;type=Series">{$field|getvalue:'a'|escape:"html"}</a><br />
                  {/foreach}
                {/if}
                {if $visible490}
                  {foreach from=$marcField490 item=field name=loop}
                    {if $field->getIndicator(1) == 0}
                      <a href="{$path}/Search/Results?lookfor=%22{$field|getvalue:'a'|escape:"url"}%22&amp;type=Series">{$field|getvalue:'a'|escape:"html"}</a><br />
                    {/if}
                  {/foreach}
                {/if}
                {if $marcField830}
                  {foreach from=$marcField830 item=field name=loop}
                    <a href="{$path}/Search/Results?lookfor=%22{$field|getvalue:'a'|escape:"url"}%22&amp;type=Series">{$field|getvalue:'a'|escape:"html"}</a><br />
                  {/foreach}
                {/if}
              </td>
            </tr>
            {/if}
        </div>
        {assign var=marcField value=$marc->getFields('020')}
        {if $marcField}
          <div id = "titledetails">
            {translate text='ISBN'}:<br />
            
            {foreach from=$marcField item=field name=loop}
              {assign var=isbnValue value=$field|getvalue:'a'}
              {if strlen($isbnValue) > 0}
              {$isbnValue|escape}<br />
              {/if}
            {/foreach}
          </div>
        {/if}
        {assign var=marcField value=$marc->getFields('022')}
        {if $marcField}
          <div id = "titledetails">
            {translate text='ISSN'}:<br />
            
            {foreach from=$marcField item=field name=loop}
              {$field|getvalue:'a'|escape}<br />
            {/foreach}
          </div>
        {/if}
        {assign var=marcField value=$marc->getFields('024')}
        {if $marcField}
          <div id = "titledetails">
            {translate text='UPC'}:<br />
            
            {foreach from=$marcField item=field name=loop}
              {$field|getvalue:'a'|escape}<br />
            {/foreach}
          </div>
        {/if}
        {if $linkToAmazon == 1 && $isbn}
        <div id="titledetails">
            <a href="http://amazon.com/dp/{$isbn|@formatISBN}" rel="external" onclick="window.open (this.href, 'child'); return false" class='amazonLink'> {translate text = "View on Amazon"}</a>
        </div>
        {/if}
        <center>
        <hr width = "150" color = "black">
        </center>
   	</div> {* End of Sidebar*}
   	
   	<div id = "fulldetails">
{$details}
   </div>
   </div>
   <div id = "fullViewLink"><a href ="{$path}/Record/{$id|escape:"url"}">Full Record</a></div>
   </div>
   </div>
     <div class="yui-b">
  
  
    <div class="sidegroup">
     {* Display either similar tiles from novelist or from the catalog*}
     <div id="similarTitlePlaceholder"></div>
     {if is_array($similarRecords)}
     <div id="relatedTitles">
      <h4>{translate text="Other Titles"}</h4>
      <ul class="similar">
        {foreach from=$similarRecords item=similar}
        <li>
          {if is_array($similar.format)}
            <span class="{$similar.format[0]|lower|regex_replace:"/[^a-z0-9]/":""}">
          {else}
            <span class="{$similar.format|lower|regex_replace:"/[^a-z0-9]/":""}">
          {/if}
          <a href="{$path}/Record/{$similar.id|escape:"url"}">{$similar.title|regex_replace:"/(\/|:)$/":""|escape}</a>
          </span>
          <span style="font-size: 80%">
          {if $similar.author}<br />{translate text='By'}: {$similar.author|escape}{/if}
          </span>
        </li>
        {/foreach}
      </ul>
     </div>
     {/if}
    </div>

    {if is_array($editions)}
    <div class="sidegroup">
      <h4>{translate text="Other Editions"}</h4>
      <ul class="similar">
        {foreach from=$editions item=edition}
        <li>
          {if is_array($edition.format)}
            <span class="{$edition.format[0]|lower|regex_replace:"/[^a-z0-9]/":""}">
          {else}
            <span class="{$edition.format|lower|regex_replace:"/[^a-z0-9]/":""}">
          {/if}
          <a href="{$path}/Record/{$edition.id|escape:"url"}">{$edition.title|escape}</a>
          </span>
          {$edition.edition|escape}
          {if $edition.publishDate}({$edition.publishDate.0|escape}){/if}
        </li>
        {/foreach}
      </ul>
    </div>
    {/if}

  </div>
  
  