{strip}
	{if $standardSubjects}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='LC Subjects'}</div>
			<div class="col-xs-9 result-value">
				{foreach from=$standardSubjects item=subject name=loop}
					{foreach from=$subject item=subjectPart name=subloop}
						{if !$smarty.foreach.subloop.first} -- {/if}
						<a href="{$path}/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;basicType=Subject">{$subjectPart.title|escape}</a>
					{/foreach}
					<br/>
			{/foreach}
			</div>
		</div>
	{/if}

	{if $bisacSubjects}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Bisac Subjects'}</div>
			<div class="col-xs-9 result-value">
				{foreach from=$bisacSubjects item=subject name=loop}
					{foreach from=$subject item=subjectPart name=subloop}
						{if !$smarty.foreach.subloop.first} -- {/if}
						<a href="{$path}/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;basicType=Subject">{$subjectPart.title|escape}</a>
					{/foreach}
					<br/>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $oclcFastSubjects}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='OCLC Fast Subjects'}</div>
			<div class="col-xs-9 result-value">
				{foreach from=$oclcFastSubjects item=subject name=loop}
					{foreach from=$subject item=subjectPart name=subloop}
						{if !$smarty.foreach.subloop.first} -- {/if}
						<a href="{$path}/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;basicType=Subject">{$subjectPart.title|escape}</a>
					{/foreach}
					<br/>
				{/foreach}
			</div>
		</div>
	{/if}
{/strip}