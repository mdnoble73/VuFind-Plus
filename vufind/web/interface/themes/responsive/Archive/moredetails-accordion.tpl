{strip}
	{* This uses the same CSS as the Full Record View accordions (all id=more-details-accordion)  *}
	<div id="more-details-accordion" class="panel-group">

		{if $description}
			<div class="panel active{*toggle on for open*}" id="descriptionPanel">
				<a href="#descriptionPanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Description
						</div>
					</div>
				</a>
				<div id="descriptionPanelBody" class="panel-collapse collapse in{*toggle on for open*}">
					<div class="panel-body">
						{$description}
					</div>
				</div>
			</div>
		{/if}

		{if $transcription}
			<div class="panel {*active*}{*toggle on for open*}" id="transcriptionPanel">
				<a href="#transcriptionPanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Transcription
						</div>
					</div>
				</a>
				<div id="transcriptionPanelBody" class="panel-collapse collapse {*in*}{*toggle on for open*}">
					<div class="panel-body">
						{$transcription.text}
					</div>
				</div>
			</div>
		{/if}

		{if $mods->subject}
				<div class="panel active{*toggle on for open*}" id="subjectPanel">
					<a href="#subjectPanelBody" data-toggle="collapse">
						<div class="panel-heading">
							<div class="panel-title">
								Subject
							</div>
						</div>
					</a>
					<div id="subjectPanelBody" class="panel-collapse collapse in{*toggle on for open*}">
						<div class="panel-body">
							{foreach from=$subjects item=subject}
								<a href='{$subject.link}'>
									{$subject.label}
								</a><br>
							{/foreach}
						</div>
					</div>
				</div>
		{/if}


		{if $rightsStatements}
			<div class="panel {*active*}{*toggle on for open*}" id="rightsStatementsPanel">
				<a href="#rightsStatementsPanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Rights Statements
						</div>
					</div>
				</a>
				<div id="rightsStatementsPanelBody" class="panel-collapse collapse {*in*}{*toggle on for open*}">
					<div class="panel-body">
						{foreach from=$rightsStatements item=rightsStatement}
							<div class="rightsStatement">{$rightsStatement}</div>
						{/foreach}
					</div>
				</div>
			</div>
		{/if}

	</div>
{/strip}