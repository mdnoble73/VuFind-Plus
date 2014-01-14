{strip}
	<div>
		<dl class="dl-horizontal">
			{if $contributors}
				<dt>{translate text='Contributors'}:</dt>
				{foreach from=$contributors item=contributor name=loop}
					<dd><a href="{$path}/Author/Home?author={$contributor|trim|escape:"url"}">{$contributor|escape}</a></dd>
				{/foreach}
			{/if}

			{if $published}
				<dt>{translate text='Published'}:</dt>
				{foreach from=$published item=publish name=loop}
					<dd>{$publish|escape}</dd>
				{/foreach}
			{/if}

			{if $streetDate}
				<dt>{translate text='Street Date'}:</dt>
				<dd>{$streetDate|escape}</dd>
			{/if}

			<dt>{translate text='Format'}:</dt>
			{if is_array($recordFormat)}
			 {foreach from=$recordFormat item=displayFormat name=loop}
				 <dd><span class="icon {$displayFormat|lower|regex_replace:"/[^a-z0-9]/":""}">&nbsp;</span><span class="iconlabel">{translate text=$displayFormat}</span></dd>
			 {/foreach}
			{else}
				<dd><span class="icon {$recordFormat|lower|regex_replace:"/[^a-z0-9]/":""}">&nbsp;</span><span class="iconlabel">{translate text=$recordFormat}</span></dd>
			{/if}

			{if $mpaaRating}
				<dt>{translate text='Rating'}:</dt>
				<dd>{$mpaaRating|escape}</dd>
			{/if}

			{if $physicalDescriptions}
				<dt>{translate text='Physical Desc'}:</dt>
				{foreach from=$physicalDescriptions item=physicalDescription name=loop}
					<dd>{$physicalDescription|escape}</dd>
				{/foreach}
			{/if}

			<dt>{translate text='Language'}:</dt>
			{foreach from=$recordLanguage item=lang}
				<dd>{$lang|escape}</dd>
			{/foreach}

			{if $editionsThis}
				<dt>{translate text='Edition'}:</dt>
				{foreach from=$editionsThis item=edition name=loop}
					<dd>{$edition|escape}</dd>
				{/foreach}
			{/if}

			{if $isbns}
				<dt>{translate text='ISBN'}:</dt>
				{foreach from=$isbns item=tmpIsbn name=loop}
					<dd>{$tmpIsbn|escape}</dd>
				{/foreach}
			{/if}

			{if $issn}
				<dt>{translate text='ISSN'}:</dt>
				<dd>{$issn}</dd>
				{if $goldRushLink}
					<dd><a href='{$goldRushLink}' target='_blank'>Check for online articles</a></dd>
				{/if}
			{/if}

			{if $upc}
				<dt>{translate text='UPC'}:</dt>
				<dd>{$upc|escape}</dd>
			{/if}

			{if $series}
				<dt>{translate text='Series'}:</dt>
				{foreach from=$series item=seriesItem name=loop}
					<dd><a href="{$path}/Search/Results?lookfor=%22{$seriesItem|escape:"url"}%22&amp;type=Series">{$seriesItem|escape}</a></dd>
				{/foreach}
			{/if}

			{if $arData}
				<dt>{translate text='Accelerated Reader'}:</dt>
				<dd>{$arData.interestLevel|escape}</dd>
				<dd>Level {$arData.readingLevel|escape}, {$arData.pointValue|escape} Points</dd>
			{/if}

			{if $lexileScore}
				<dt>{translate text='Lexile Score'}:</dt>
				<dd>{$lexileScore|escape}</dd>
			{/if}

			{if $standardSubjects}
				<dt>{translate text='Subjects'}</dt>
				{foreach from=$standardSubjects item=subject name=loop}
					<dd>
						{foreach from=$subject item=subjectPart name=subloop}
							{if !$smarty.foreach.subloop.first} -- {/if}
							<a href="{$path}/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;basicType=Subject">{$subjectPart.title|escape}</a>
						{/foreach}
					</dd>
				{/foreach}
			{/if}

			{if $bisacSubjects}
				<dt>{translate text='Bisac Subjects'}</dt>
				{foreach from=$bisacSubjects item=subject name=loop}
					<dd>
						{foreach from=$subject item=subjectPart name=subloop}
							{if !$smarty.foreach.subloop.first} -- {/if}
							<a href="{$path}/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;basicType=Subject">{$subjectPart.title|escape}</a>
						{/foreach}
					</dd>
				{/foreach}
			{/if}

			{if $oclcFastSubjects}
				<dt>{translate text='OCLC Fast Subjects'}</dt>
				{foreach from=$oclcFastSubjects item=subject name=loop}
					<dd>
						{foreach from=$subject item=subjectPart name=subloop}
							{if !$smarty.foreach.subloop.first} -- {/if}
							<a href="{$path}/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;basicType=Subject">{$subjectPart.title|escape}</a>
						{/foreach}
					</dd>
				{/foreach}
			{/if}

			{if $showTagging == 1}
				<dt>{translate text='Tags'}:</dt>
				{if $tagList}
					{foreach from=$tagList item=tag name=tagLoop}
						<dd>
							<a href="{$path}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape:"html"}</a> ({$tag->cnt})
							{if $tag->userAddedThis}
								<a href='{$path}/MyResearch/RemoveTag?tagId={$tag->id}&amp;resourceId={$id}' onclick='return confirm("Are you sure you want to remove the tag \"{$tag->tag|escape:"javascript"}\" from this title?");'>
									<span class="silk tag_blue_delete">&nbsp;</span>
								</a>
							{/if}
						</dd>
					{/foreach}
				{else}
					<dd>{translate text='No Tags'}, {translate text='Be the first to tag this record'}!</dd>
				{/if}
				<dd>
					<a href="{$path}/Resource/AddTag?id={$id|escape:"url"}&amp;source=VuFind" onclick="VuFind.Record.getAddTagForm(this, '{$id|escape}', 'VuFind'); return false;" class="btn btn-sm">
						<span class="silk add">&nbsp;</span>{translate text="Add Tag"}
					</a>
				</dd>
			{/if}
		</dl>
	</div>
{/strip}