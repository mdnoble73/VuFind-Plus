{strip}
	<div>
		<dl class="dl-horizontal">
			{if $recordDriver->getContributors()}
				<dt>{translate text='Contributors'}:</dt>
				{foreach from=$recordDriver->getContributors() item=contributor name=loop}
					<dd><a href="{$path}/Author/Home?author={$contributor|trim|escape:"url"}">{$contributor|escape}</a></dd>
				{/foreach}
			{/if}

			{if $recordDriver->getMpaaRating()}
				<dt>{translate text='Rating'}:</dt>
				<dd>{$recordDriver->getMpaaRating()|escape}</dd>
			{/if}

			{if $recordDriver->getISBNs()}
				<dt>{translate text='ISBN'}:</dt>
				{foreach from=$recordDriver->getISBNs() item=tmpIsbn name=loop}
					<dd>{$tmpIsbn|escape}</dd>
				{/foreach}
			{/if}

			{if $recordDriver->getISSNs()}
				<dt>{translate text='ISSN'}:</dt>
				<dd>{$recordDriver->getISSNs()}</dd>
			{/if}

			{if $recordDriver->getUPCs()}
				<dt>{translate text='UPC'}:</dt>
				{foreach from=$recordDriver->getUPCs() item=tmpUpc name=loop}
					<dd>{$tmpUpc|escape}</dd>
				{/foreach}
			{/if}

			{if $recordDriver->getIndexedSeries()}
				<dt>{translate text='Series'}:</dt>
				{foreach from=$recordDriver->getIndexedSeries() item=seriesItem name=loop}
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

		</dl>
	</div>
{/strip}