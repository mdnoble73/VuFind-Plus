{strip}
	{if $streetDate}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Street Date'}:</div>
			<div class="col-md-9 result-value">
				{$streetDate|escape}
			</div>
		</div>
	{/if}

	<div class="row">
		<div class="result-label col-md-3">{translate text='Language'}:</div>
		<div class="col-md-9 result-value">
			{implode subject=$recordLanguage glue=", "}
		</div>
	</div>

	{if $isbns}
		<div class="row">
			<div class="result-label col-md-3">{translate text='ISBN'}:</div>
			<div class="col-md-9 result-value">
				{implode subject=$isbns glue=", "}
			</div>
		</div>
	{/if}

	{if $issn}
		<div class="row">
			<div class="result-label col-md-3">{translate text='ISSN'}:</div>
			<div class="col-md-9 result-value">
				{$issn}
				{if $goldRushLink}
					&nbsp;<a href='{$goldRushLink}' target='_blank'>Check for online articles</a>
				{/if}
			</div>
		</div>
	{/if}

	{if $upc}
		<div class="row">
			<div class="result-label col-md-3">{translate text='UPC'}:</div>
			<div class="col-md-9 result-value">
				{$upc|escape}
			</div>
		</div>
	{/if}

	{if $arData}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Accelerated Reader'}:</div>
			<div class="col-md-9 result-value">
				{$arData.interestLevel|escape}<br/>
				Level {$arData.readingLevel|escape}, {$arData.pointValue|escape} Points
			</div>
		</div>
	{/if}

	{if $lexileScore}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Lexile Score'}:</div>
			<div class="col-md-9 result-value">
				{$lexileScore|escape}
			</div>
		</div>
	{/if}

	{if $standardSubjects}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Subjects'}</div>
			<div class="col-md-9 result-value">
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
			<div class="result-label col-md-3">{translate text='Bisac Subjects'}</div>
			<div class="col-md-9 result-value">
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
			<div class="result-label col-md-3">{translate text='OCLC Fast Subjects'}</div>
			<div class="col-md-9 result-value">
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