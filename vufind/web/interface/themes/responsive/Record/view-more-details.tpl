{strip}
	{* Display more information about the title*}
	{if $recordDriver->getAuthor()}
		<div class="row">
			<div class="result-label col-md-3">Author: </div>
			<div class="col-md-9 result-value">
				<a href="{$path}/Author/Home?author={$recordDriver->getAuthor()|escape:"url"}">{$recordDriver->getAuthor()|highlight:$lookfor}</a>
			</div>
		</div>
	{/if}

	{if $recordDriver->getContributors()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Contributors'}:</div>
			<div class="col-md-9 result-value">
				{foreach from=$recordDriver->getContributors() item=contributor name=loop}
					<a href="{$path}/Author/Home?author={$contributor|trim|escape:"url"}">{$contributor|escape}</a>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $published}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Published'}:</div>
			<div class="col-md-9 result-value">
				{foreach from=$published item=publish name=loop}
					{$publish|escape}
				{/foreach}
			</div>
		</div>
	{/if}

	{if $streetDate}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Street Date'}:</div>
			<div class="col-md-9 result-value">
				{$streetDate|escape}
			</div>
		</div>
	{/if}

	<div class="row">
		<div class="result-label col-md-3">{translate text='Format'}:</div>
		<div class="col-md-9 result-value">
			{implode subject=$recordFormat glue=", "}
		</div>
	</div>

	{if $mpaaRating}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Rating'}:</div>
			<div class="col-md-9 result-value">{$mpaaRating|escape}</div>
		</div>
	{/if}

	{if $physicalDescriptions}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Physical Desc'}:</div>
			<div class="col-md-9 result-value">
				{implode subject=$physicalDescriptions glue="<br/>"}
			</div>
		</div>
	{/if}

	<div class="row">
		<div class="result-label col-md-3">{translate text='Language'}:</div>
		<div class="col-md-9 result-value">
			{implode subject=$recordLanguage glue=", "}
		</div>
	</div>

	<div class="row">
		{if $editionsThis}
			<div class="result-label col-md-3">{translate text='Edition'}:</div>
			<div class="col-md-9 result-value">
				{implode subject=$editionsThis glue=", "}
			</div>
		{/if}
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

	{if $series}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Series'}:</div>
			<div class="col-md-9 result-value">
				{foreach from=$series item=seriesItem name=loop}
					<a href="{$path}/Search/Results?lookfor=%22{$seriesItem|escape:"url"}%22&amp;type=Series">{$seriesItem|escape}</a><br />
				{/foreach}
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

	{if $showTagging == 1}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Tags'}:</div>
			<div class="col-md-9 result-value">
				{if $tagList}
					{foreach from=$tagList item=tag name=tagLoop}
						<a href="{$path}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape:"html"}</a> ({$tag->cnt})
						{if $tag->userAddedThis}
							<a href='{$path}/MyResearch/RemoveTag?tagId={$tag->id}&amp;resourceId={$id}' onclick='return confirm("Are you sure you want to remove the tag \"{$tag->tag|escape:"javascript"}\" from this title?");'>
								<span class="silk tag_blue_delete">&nbsp;</span>
							</a>
						{/if}
						<br/>
					{/foreach}
				{else}
					{translate text='No Tags'}, {translate text='Be the first to tag this record'}!
				{/if}

				<div>
					<a href="{$path}/Resource/AddTag?id={$id|escape:"url"}&amp;source=VuFind" onclick="VuFind.Record.getAddTagForm(this, '{$id|escape}', 'VuFind'); return false;" class="btn btn-sm btn-default">
						{translate text="Add Tag"}
					</a>
				</div>
			</div>

		</div>
	{/if}
{/strip}