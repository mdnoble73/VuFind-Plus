{strip}
	{if $recordDriver->getLanguage()}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Language'}:</div>
			<div class="col-xs-9 result-value">
				{$recordDriver->getLanguage()}
			</div>
		</div>
	{/if}

	{if count($recordDriver->getISBNs()) > 0}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='ISBN'}:</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordDriver->getISBNs() glue=", "}
			</div>
		</div>
	{/if}

	{if count($recordDriver->getUPCs()) > 0}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='UPC'}:</div>
			<div class="col-xs-9 result-value">
				{implode subject=$recordDriver->getUPCs() glue=", "}
			</div>
		</div>
	{/if}

	{if $recordDriver->getAcceleratedReaderData() != null}
		{assign var="arData" value=$recordDriver->getAcceleratedReaderData()}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Accelerated Reader'}:</div>
			<div class="col-xs-9 result-value">
				{$arData.interestLevel|escape}<br/>
				Level {$arData.readingLevel|escape}, {$arData.pointValue|escape} Points
			</div>
		</div>
	{/if}

	{if $recordDriver->getLexileCode()}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Lexile Code'}:</div>
			<div class="col-xs-9 result-value">
				{$recordDriver->getLexileCode()|escape}
			</div>
		</div>
	{/if}

	{if $recordDriver->getLexileScore()}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Lexile Score'}:</div>
			<div class="col-xs-9 result-value">
				{$recordDriver->getLexileScore()|escape}
			</div>
		</div>
	{/if}

	{if $notes}
		<h4>{translate text='Notes'}</h4>
		{foreach from=$notes item=note name=loop}
			<div class="row">
				<div class="result-label col-sm-3">{$note.label}</div>
				<div class="col-sm-9 result-value">{$note.note}</div>
			</div>
		{/foreach}
	{/if}
{/strip}