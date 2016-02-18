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


		{if $mods->identifier || $mods->recordInfo}
			<div class="panel {*active*}{*toggle on for open*}" id="moreDetailsPanel">
				<a href="#moreDetailsPanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							More Details
						</div>
					</div>
				</a>
				<div id="moreDetailsPanelBody" class="panel-collapse collapse {*in*}{*toggle on for open*}">
					<div class="panel-body">
						{if $mods->identifier}
							<div class="row">
								<div class="result-label col-sm-4">Local Identifier: </div>
								<div class="result-value col-sm-8">
									{$mods->identifier}
								</div>
							</div>
						{/if}

{* TODO Need to change styling for this to work (blends in with background)
						{if $mods->identifier && $mods->recordInfo}
						<hr>
						{/if}
*}

						{if $mods->recordInfo}
							{if $mods->recordInfo->recordOrigin}
								<div class="row">
									<div class="result-label col-sm-4">Entered By: </div>
									<div class="result-value col-sm-8">
										{$mods->recordInfo->recordOrigin}
									</div>
								</div>
							{/if}
							{if $mods->recordInfo->recordCreationDate}
								<div class="row">
									<div class="result-label col-sm-4">Entered On: </div>
									<div class="result-value col-sm-8">
										{$mods->recordInfo->recordCreationDate}
									</div>
								</div>
							{/if}
							{if $mods->recordInfo->recordChangeDate}
								<div class="row">
									<div class="result-label col-sm-4">Last Changed: </div>
									<div class="result-value col-sm-8">
										{$mods->recordInfo->recordChangeDate}
									</div>
								</div>
							{/if}
						{/if}

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

			{if $repositoryLink}
				<div class="panel {*active*}{*toggle on for open*}" id="staffViewPanel">
					<a href="#staffViewPanelBody" data-toggle="collapse">
						<div class="panel-heading">
							<div class="panel-title">
								Staff View
							</div>
						</div>
					</a>
					<div id="staffViewPanelBody" class="panel-collapse collapse {*in*}{*toggle on for open*}">
						<div class="panel-body">
							<a class="btn btn-small btn-default" href="{$repositoryLink}" target="_blank">
								View in Islandora
							</a>
							<a class="btn btn-small btn-default" href="{$repositoryLink}/datastream/MODS/view" target="_blank">
								View MODS Record
							</a>
						</div>
					</div>
				</div>
			{/if}


	</div>
{/strip}