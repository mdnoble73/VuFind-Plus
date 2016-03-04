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


		{* Context Notes *}
		{if !empty($marmotExtension->contextNotes)}
			<div class="panel {*active*}{*toggle on for open*}" id="contextNotesPanel">
				<a href="#contextNotesPanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Context Notes
						</div>
					</div>
				</a>
				<div id="contextNotesPanelBody" class="panel-collapse collapse {*in*}{*toggle on for open*}">
					<div class="panel-body">
						{$marmotExtension->contextNotes}
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

		{if $relatedPeople}
			<div class="panel active{*toggle on for open*}" id="relatedPeoplePanel">
				<a href="#relatedPeoplePanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Related People
						</div>
					</div>
				</a>
				<div id="relatedPeoplePanelBody" class="panel-collapse collapse in{*toggle on for open*}">
					<div class="panel-body">

						{foreach from=$relatedPeople item=entity}
							<a href='{$entity.link}'>
								{$entity.label}
							</a>
							{if $entity.role}
								&nbsp;({$entity.role})
							{/if}
							<br>
						{/foreach}

					</div>
				</div>
			</div>
		{/if}

		{if $relatedPlaces}
			<div class="panel active{*toggle on for open*}" id="relatedPlacesPanel">
				<a href="#relatedPlacesPanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Related Places
						</div>
					</div>
				</a>
				<div id="relatedPlacesPanelBody" class="panel-collapse collapse in{*toggle on for open*}">
					<div class="panel-body">

						{foreach from=$relatedPlaces item=entity}
							<a href='{$entity.link}'>
								{$entity.label}
							</a><br>
						{/foreach}

					</div>
				</div>
			</div>
		{/if}

		{if $relatedEvents}
			<div class="panel active{*toggle on for open*}" id="relatedEventsPanel">
				<a href="#relatedEventsPanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Related Events
						</div>
					</div>
				</a>
				<div id="relatedEventsPanelBody" class="panel-collapse collapse in{*toggle on for open*}">
					<div class="panel-body">

						{foreach from=$relatedEvents item=entity}
							<a href='{$entity.link}'>
								{$entity.label}
							</a><br>
						{/foreach}

					</div>
				</div>
			</div>
		{/if}

		{if $hasMilitaryService}
			<div class="panel active{*toggle on for open*}" id="militaryServicePanel">
				<a href="#militaryServicePanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Military Service
						</div>
					</div>
				</a>
				<div id="militaryServicePanelBody" class="panel-collapse collapse in{*toggle on for open*}">
					<div class="panel-body">

						<div class="row">
							<div class="result-label col-sm-4">Military Branch: </div>
							<div class="result-value col-sm-8">
								{$militaryRecord.branch}
							</div>
						</div>
						<div class="row">
							<div class="result-label col-sm-4">Conflict: </div>
							<div class="result-value col-sm-8">
								{$militaryRecord.conflict}
							</div>
						</div>

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

						{* Date Created *}
						{if $mods->originInfo && strlen($mods->originInfo->dateCreated)}
							<div class="row">
								<div class="result-label col-sm-4">Created: </div>
								<div class="result-value col-sm-8">
									{$mods->originInfo->dateCreated}
								</div>
							</div>
						{/if}

						{* Local Identifier *}
						{if $mods->identifier}
							<div class="row">
								<div class="result-label col-sm-4">Local Identifier: </div>
								<div class="result-value col-sm-8">
									{$mods->identifier}
								</div>
							</div>
						{/if}


						{if $mods->physicalDescription || $mods->physicalLocation || $mods->shelfLocator}

							{* Physical Description *}
							{if !empty($mods->physicalDescription)}
								<div class="row">
									<div class="result-label col-sm-4">Physical Description: </div>
									<div class="result-value col-sm-8">
										{foreach from=$mods->physicalDescription->extent item=extent}
											{if $extent}
												<div>{$extent}</div>
											{/if}
										{/foreach}
									</div>
								</div>
							{/if}

							{* Physical Location *}
							{if !empty($mods->physicalLocation)}
								<div class="row">
									<div class="result-label col-sm-4">Located at: </div>
									<div class="result-value col-sm-8">
										{foreach from=$mods->physicalLocation item=location}
											{if $location}
												<div>{$location}</div>
											{/if}
										{/foreach}
									</div>
								</div>
							{/if}

							{* Shelf Locator *}
							{if !empty($mods->shelfLocator)}
								<div class="row">
									<div class="result-label col-sm-4">Shelf Locator: </div>
									<div class="result-value col-sm-8">
										{foreach from=$mods->shelfLocator item=location}
											{if $location}
												<div>{$location}</div>
											{/if}
										{/foreach}
									</div>
								</div>
							{/if}
						{/if}

						{* Record Origin Info *}
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