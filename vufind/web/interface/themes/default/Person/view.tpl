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
        {* Toolbar content *}
      </div>
      {if $error}<p class="error">{$error}</p>{/if} 
      <div id = "fullcontent">
        <div id = "fullinfo">
	        {* Left Sidebar *}
	        {* Display Book Cover *}
	        <div id = "clearcover"> 
		        <div class="alignleft"> 
		          <a href="{$url}/Person/{$summShortId}">
		          {if $record.picture}
					    <a target='_blank' href='{$path}/files/original/{$record.picture|escape}'><img src="{$path}/files/medium/{$record.picture|escape}" class="alignleft listResultImage" alt="{translate text='Picture'}"/></a><br />
					    {else}
					    <img src="{$path}/interface/themes/default/images/person.png" class="alignleft listResultImage" alt="{translate text='No Cover Image'}"/><br />
					    {/if}
					    </a>
		        </div>  
	        </div>
	      </div> {* End of Sidebar*}
	      <div id = "fulldetails">
	        <h1>{$record.firstName|escape}{if $record.nickName} ({$record.nickName|escape}){/if} {$record.middleName|escape} {$record.lastName|escape}
          {if $userIsAdmin}
            <a href='{$path}/Admin/People?objectAction=edit&amp;id={$id}' ><img alt='Edit this person' src='{$path}/images/silk/user_edit.png' /></a>
            <a href='{$path}/Admin/People?objectAction=delete&amp;id={$id}' onclick='return confirm("Removing this person will permanently remove them from the system.  Are you sure?")'><img alt='Delete this person' src='{$path}/images/silk/user_delete.png' /></a>
          {/if}
          </h1>
          {if $record.maidenName}
          <div class='personDetail'><span class='personDetailLabel'>Maiden Name:</span><span class='personDetailValue'>{$record.maidenName|escape}</span></div>
          {/if}
          {if $record.otherName}
          <div class='personDetail'><span class='personDetailLabel'>Other Names:</span><span class='personDetailValue'>{$record.otherName|escape}</span></div>
          {/if}
	        {if $birthDate}
          <div class='personDetail'><span class='personDetailLabel'>Birth Date:</span><span class='personDetailValue'>{$birthDate}</span></div>
          {/if}
          {if $deathDate}
          <div class='personDetail'><span class='personDetailLabel'>Death Date:</span><span class='personDetailValue'>{$deathDate}</span></div>
          {/if}
          {if $ageAtDeath}
          <div class='personDetail'><span class='personDetailLabel'>Age at Death:</span><span class='personDetailValue'>{$record.ageAtDeath|escape}</span></div>
          {/if}
          {if $record.veteranOf}
          {implode subject=$record.veteranOf glue=", " assign='veteranOf'} 
          <div class='personDetail'><span class='personDetailLabel'>Veteran Of:</span><span class='personDetailValue'>{$veteranOf}</span></div>
          {/if}
          
          {if count($marriages) > 0 || $userIsAdmin}
            <div class="blockhead">Marriages
            {if $userIsAdmin}
              <a href='{$path}/Admin/Marriages?objectAction=add&amp;personId={$id}' title='Add a Marriage'><img src='{$path}/images/silk/group_add.png' alt='Add a Marriage'></a>
            {/if}
            </div>
            {foreach from=$marriages item=marriage}
	            <div class="marriageTitle">
	               {$marriage.spouseName}{if $marriage.formattedMarriageDate} on {$marriage.formattedMarriageDate}{/if}
	               {if $userIsAdmin}
			              <a href='{$path}/Admin/Marriages?objectAction=edit&amp;id={$marriage.marriageId}' title='Edit this Marriage'><img src='{$path}/images/silk/group_edit.png' alt='Edit this Marriage'></a>
                    <a href='{$path}/Admin/Marriages?objectAction=delete&amp;id={$marriage.marriageId}' title='Delete this Marriage' onclick='return confirm("Removing this marriage will permanently remove it from the system.  Are you sure?")'><img src='{$path}/images/silk/group_delete.png' alt='Delete this Marriage'></a>
			           {/if}
	            </div>
	            {if $marriage.comments}
                <div class="marriageComments">{$marriage.comments|escape}</div>
              {/if}
            {/foreach}
            
          {/if}
	        {if $record.cemeteryName || $record.cemeteryLocation || $record.mortuaryName}
		        <div class="blockhead">Burial Details</div>
		        {if $record.cemeteryName}
	          <div class='personDetail'><span class='personDetailLabel'>Cemetery Name:</span><span class='personDetailValue'>{$record.cemeteryName}</span></div>
	          {/if}
		        {if $record.cemeteryLocation}
	          <div class='personDetail'><span class='personDetailLabel'>Cemetery Location:</span><span class='personDetailValue'>{$record.cemeteryLocation}</span></div>
	          {/if}
	          {if $person->addition || $person->lot || $person->block || $person->grave}
	          <div class='personDetail'><span class='personDetailLabel'>Burial Location:</span>
	          <span class='personDetailValue'>
	          	Addition {$person->addition}, Block {$person->block}, Lot {$person->lot}, Grave {$person->grave}
	          </span></div>
	          {if $person->tombstoneInscription}
	          <div class='personDetail'><span class='personDetailLabel'>Tombstone Inscription:</span><div class='personDetailValue'>{$person->tombstoneInscription}</div></div>
	          {/if}
	          {/if}
	          {if $record.mortuaryName}
	          <div class='personDetail'><span class='personDetailLabel'>Mortuary Name:</span><span class='personDetailValue'>{$record.mortuaryName}</span></div>
	          {/if}
          {/if}
          {if count($obituaries) > 0 || $userIsAdmin}
            <div class="blockhead">Obituaries
            {if $userIsAdmin}
              <a href='{$path}/Admin/Obituaries?objectAction=add&amp;personId={$id}' title='Add an Obituary'><img src='{$path}/images/silk/report_add.png' alt='Add a Marriage'></a>
            {/if}
            </div>
            {foreach from=$obituaries item=obituary}
	            <div class="obituaryTitle">
	            {$obituary.source}{if $obituary.sourcePage} page {$obituary.sourcePage}{/if}{if $obituary.formattedObitDate} - {$obituary.formattedObitDate}{/if}
	            {if $userIsAdmin}
                 <a href='{$path}/Admin/Obituaries?objectAction=edit&amp;id={$obituary.obituaryId}' title='Edit this Obituary'><img src='{$path}/images/silk/report_edit.png' alt='Edit this Obituary'></a>
                 <a href='{$path}/Admin/Obituaries?objectAction=delete&amp;id={$obituary.obituaryId}' title='Delete this Obituary' onclick='return confirm("Removing this obituary will permanently remove it from the system.  Are you sure?")'><img src='{$path}/images/silk/report_delete.png' alt='Delete this Obituary'></a>
              {/if}
	            </div>
	            {if $obituary.contents && $obituary.picture}
                <div class="obituaryText">{if $obituary.picture|escape}<a href='{$path}/files/original/{$obituary.picture|escape}'><img class='obitPicture' src='{$path}/files/medium/{$obituary.picture|escape}'/></a>{/if}{$obituary.contents|escape}</div>
                <div class="clearer"></div>
              {elseif $obituary.contents}
                <div class="obituaryText">{$obituary.contents|escape}</div>
                <div class="clearer"></div>
              {elseif $obituary.picture}
	              <div class="obituaryPicture">{if $obituary.picture|escape}<a href='{$path}/files/original/{$obituary.picture|escape}'><img class='obitPicture' src='{$path}/files/medium/{$obituary.picture|escape}'/></a>{/if}</div>
	              <div class="clearer"></div>
	            {/if}
	            
            {/foreach}
            
          {/if}
          <div class="blockhead">Comments</div>
          {if $record.comments}
          <div class='personComments'>{$record.comments|escape}</div>
          {else}
          <div class='personComments'>No comments found.</div>
          {/if}
          
	      </div>
      </div>
      <div id="fullViewLink"></div>
    </div>
  </div>
  <div class="yui-b">
    <div class="sidegroup">
     {* Right sidebar *}
    </div>
  </div>
</div>
  