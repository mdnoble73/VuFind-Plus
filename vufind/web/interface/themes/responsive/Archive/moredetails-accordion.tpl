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
						{foreach from=$transcription item=transcript}
							<div class="transcript">
								{if $transcript.location}
									<div class="transcriptLocation">From the {$transcript.location}</div>
								{/if}
								{$transcript.text}
							</div>
						{/foreach}
					</div>
				</div>
			</div>
		{/if}

		{if $hasCorrespondenceInfo}
			<div class="panel active" id="correspondencePanel">
				<a href="#correspondencePanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Correspondence information
						</div>
					</div>
				</a>
				<div id="correspondencePanelBody" class="panel-collapse collapse in">
					<div class="panel-body">
						{if $includesStamp}
							<div class="row">
								<div class="result-label col-sm-4">Includes Stamp: </div>
								<div class="result-value col-sm-8">
									Yes
								</div>
							</div>
						{/if}
						{if $datePostmarked}
							<div class="row">
								<div class="result-label col-sm-4">Date Postmarked: </div>
								<div class="result-value col-sm-8">
									{$datePostmarked}
								</div>
							</div>
						{/if}
						{if $postmarkLocation}
							<div class="relatedPlace row">
								<div class="result-label col-sm-4">
									Postmark Location:
								</div>
								<div class="result-value col-sm-8">
									{if $postMarkLocation.link}
										<a href='{$postMarkLocation.link}'>
											{$postMarkLocation.label}
										</a>
									{else}
										{$postMarkLocation.label}
									{/if}
									{if $postMarkLocation.role}
										&nbsp;({$postMarkLocation.role})
									{/if}
								</div>
							</div>
						{/if}
						{if $postmarkLocation}
							<div class="relatedPlace row">
								<div class="result-label col-sm-4">
									Postmark Location:
								</div>
								<div class="result-value col-sm-8">
									{if $postMarkLocation.link}
										<a href='{$postMarkLocation.link}'>
											{$postMarkLocation.label}
										</a>
									{else}
										{$postMarkLocation.label}
									{/if}
								</div>
							</div>
						{/if}
						{if $correspondenceRecipient}
							<div class="relatedPlace row">
								<div class="result-label col-sm-4">
									Correspondence Recipient:
								</div>
								<div class="result-value col-sm-8">
									{if $correspondenceRecipient.link}
										<a href='{$correspondenceRecipient.link}'>
											{$correspondenceRecipient.label}
										</a>
									{else}
										{$correspondenceRecipient.label}
									{/if}
								</div>
							</div>
						{/if}
					</div>
				</div>
			</div>
		{/if}

		{if $hasAcademicResearchData}
			<div class="panel active" id="academicResearchPanel">
				<a href="#academicResearchPanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Research Information
						</div>
					</div>
				</a>
				<div id="academicResearchPanelBody" class="panel-collapse collapse in">
					<div class="panel-body">

						{if $researchType}
							<div class="row">
								<div class="result-label col-sm-4">Research Type: </div>
								<div class="result-value col-sm-8">
									{$researchType}
								</div>
							</div>
						{/if}
						{if $degreeName}
							<div class="row">
								<div class="result-label col-sm-4">Degree Name: </div>
								<div class="result-value col-sm-8">
									{$degreeName}
								</div>
							</div>
						{/if}
						{if $degreeDiscipline}
							<div class="row">
								<div class="result-label col-sm-4">Degree Discipline: </div>
								<div class="result-value col-sm-8">
									{$degreeDiscipline}
								</div>
							</div>
						{/if}
						{if $researchLevel}
							<div class="row">
								<div class="result-label col-sm-4">Research Level: </div>
								<div class="result-value col-sm-8">
									{$researchLevel}
								</div>
							</div>
						{/if}
						{if $peerReview}
							<div class="row">
								<div class="result-label col-sm-4">Peer Reviewed? </div>
								<div class="result-value col-sm-8">
									{$peerReview}
								</div>
							</div>
						{/if}
						{if $defenceDate}
							<div class="row">
								<div class="result-label col-sm-4">Defence Date:  </div>
								<div class="result-value col-sm-8">
									{$defenceDate}
								</div>
							</div>
						{/if}
						{if $acceptedDate}
							<div class="row">
								<div class="result-label col-sm-4">Accepted Date: </div>
								<div class="result-value col-sm-8">
									{$acceptedDate}
								</div>
							</div>
						{/if}
						{foreach from=$academicPeople item="academicPerson"}
							<div class="row">
								<div class="result-label col-sm-4">
									{$academicPerson.role}:
								</div>
								<div class="result-value col-sm-8">
									{if $academicPerson.link}
										<a href='{$academicPerson.link}'>
											{$academicPerson.label}
										</a>
									{else}
										{$academicPerson.label}
									{/if}
								</div>
							</div>
						{/foreach}
					</div>
				</div>
			</div>
		{/if}

		{if $directlyRelatedObjects && $directlyRelatedObjects.numFound > 0}
			<div class="panel active" id="relatedObjectsPanel">
				<a href="#relatedObjectsPanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Related Objects
						</div>
					</div>
				</a>
				<div id="relatedObjectsPanelBody" class="panel-collapse collapse in">
					<div class="panel-body">
						{if $solrSearchDebug}
							<div id="solrSearchOptionsToggle" onclick="$('#solrSearchOptions').toggle()">Show Search Options</div>
							<div id="solrSearchOptions" style="display:none">
								<pre>Search options: {$solrSearchDebug}</pre>
							</div>
						{/if}

						{if $solrLinkDebug}
							<div id='solrLinkToggle' onclick='$("#solrLink").toggle()'>Show Solr Link</div>
							<div id='solrLink' style='display:none'>
								<pre>{$solrLinkDebug}</pre>
							</div>
						{/if}
						{include file="accordion-items.tpl" relatedItems=$directlyRelatedObjects.objects}
					</div>
				</div>
			</div>
		{/if}

		{if count($obituaries) > 0}
			<div class="panel active{*toggle on for open*}" id="obituariesPanel">
				<a href="#obituariesPanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Obituaries
						</div>
					</div>
				</a>
				<div id="obituariesPanelBody" class="panel-collapse collapse in{*toggle on for open*}">
					<div class="panel-body">
						{foreach from=$obituaries item=obituary}
							<div class="obituaryTitle">
								{$obituary.source}{if $obituary.sourcePage} page {$obituary.sourcePage}{/if}{if $obituary.formattedObitDate} - {$obituary.formattedObitDate}{/if}
								{if $userIsAdmin}
									<div class="btn-toolbar">
										<a href='{$path}/Admin/Obituaries?objectAction=edit&amp;id={$obituary.obituaryId}' title='Edit this Obituary' class='btn btn-xs btn-default'>
											Edit
										</a>
										<a href='{$path}/Admin/Obituaries?objectAction=delete&amp;id={$obituary.obituaryId}' title='Delete this Obituary' onclick='return confirm("Removing this obituary will permanently remove it from the system.	Are you sure?")' class='btn btn-xs btn-danger'>
											Delete
										</a>
									</div>
								{/if}
							</div>
							{if $obituary.contents && $obituary.picture}
								<div class="obituaryText">{if $obituary.picture|escape}<a href='{$path}/files/original/{$obituary.picture|escape}'><img class='obitPicture' src='{$path}/files/medium/{$obituary.picture|escape}'></a>{/if}{$obituary.contents|escape}</div>
								<div class="clearer"></div>
							{elseif $obituary.contents}
								<div class="obituaryText">{$obituary.contents|escape|replace:"\r":"<br>"}</div>
								<div class="clearer"></div>
							{elseif $obituary.picture}
								<div class="obituaryPicture">{if $obituary.picture|escape}<a href='{$path}/files/original/{$obituary.picture|escape}'><img class='obitPicture' src='{$path}/files/medium/{$obituary.picture|escape}'></a>{/if}</div>
								<div class="clearer"></div>
							{/if}

						{/foreach}
					</div>
				</div>
			</div>
		{/if}

		{if $genealogyData->cemeteryName || $genealogyData->cemeteryLocation || $genealogyData->mortuaryName || $genealogyData->cemeteryAvenue || $genealogyData->lot || $genealogyData->block || $genealogyData->grave || $genealogyData->addition}
			<div class="panel {*active*}{*toggle on for open*}" id="burialDetailsPanel">
				<a href="#burialDetailsPanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Burial Details
						</div>
					</div>
				</a>
				<div id="burialDetailsPanelBody" class="panel-collapse collapse {*in*}{*toggle on for open*}">
					<div class="panel-body">
						{if $genealogyData->cemeteryName}
							<div class='genealogyDataDetail'><span class='result-label'>Cemetery Name: </span><span class='genealogyDataDetailValue'>{$genealogyData->cemeteryName}</span></div>
						{/if}
						{if $genealogyData->cemeteryLocation}
							<div class='genealogyDataDetail'><span class='result-label'>Cemetery Location: </span><span class='genealogyDataDetailValue'>{$genealogyData->cemeteryLocation}</span></div>
						{/if}
						{if $genealogyData->cemeteryAvenue}
							<div class='genealogyDataDetail'><span class='result-label'>Cemetery Avenue: </span><span class='genealogyDataDetailValue'>{$genealogyData->cemeteryAvenue}</span></div>
						{/if}
						{if $genealogyData->addition || $genealogyData->lot || $genealogyData->block || $genealogyData->grave}
							<div class='genealogyDataDetail'><span class='result-label'>Burial Location:</span>
								<span class='genealogyDataDetailValue'>
									{if $genealogyData->addition}Addition {$genealogyData->addition}{if $genealogyData->block || $genealogyData->lot || $genealogyData->grave}, {/if}{/if}
									{if $genealogyData->block}Block {$genealogyData->block}{if $genealogyData->lot || $genealogyData->grave}, {/if}{/if}
									{if $genealogyData->lot}Lot {$genealogyData->lot}{if $genealogyData->grave}, {/if}{/if}
									{if $genealogyData->grave}Grave {$genealogyData->grave}{/if}
								</span>
							</div>
							{if $genealogyData->tombstoneInscription}
								<div class='genealogyDataDetail'><span class='result-label'>Tombstone Inscription: </span><div class='genealogyDataDetailValue'>{$genealogyData->tombstoneInscription}</div></div>
							{/if}
						{/if}
						{if $genealogyData->mortuaryName}
							<div class='genealogyDataDetail'><span class='result-label'>Mortuary Name: </span><span class='genealogyDataDetailValue'>{$genealogyData->mortuaryName}</span></div>
						{/if}
					</div>
				</div>
			</div>
		{/if}

		{* Context Notes *}
		{if !empty($contextNotes)}
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
						{$contextNotes}
					</div>
				</div>
			</div>
		{/if}

		{if $relatedPeople || count($marriages) > 0}
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
						{if count($marriages) > 0}
							{foreach from=$marriages item=marriage}
								<div class="marriageTitle">
									Married: {$marriage.spouseName}{if $marriage.formattedMarriageDate} - {$marriage.formattedMarriageDate}{/if}
								</div>
								{if $marriage.comments}
									<div class="marriageComments">{$marriage.comments|escape}</div>
								{/if}
							{/foreach}
						{/if}
						{include file="accordion-items.tpl" relatedItems=$relatedPeople}
					</div>
				</div>
			</div>
		{/if}

		{if $relatedOrganizations}
			<div class="panel active{*toggle on for open*}" id="relatedOrganizationsPanel">
				<a href="#relatedOrganizationsPanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Related Organizations
						</div>
					</div>
				</a>
				<div id="relatedOrganizationsPanelBody" class="panel-collapse collapse in{*toggle on for open*}">
					<div class="panel-body">
						{include file="accordion-items.tpl" relatedItems=$relatedOrganizations}
					</div>
				</div>
			</div>
		{/if}

		{if $relatedPlaces && $recordDriver->getType() != 'event'}
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
						{if $mapsKey && $relatedPlaces.centerX && $relatedPlaces.centerY}
							<iframe width="100%" height="" frameborder="0" style="border:0" src="https://www.google.com/maps/embed/v1/place?q={$relatedPlaces.centerX|escape}%2C%20{$relatedPlaces.centerX|escape}&key={$mapsKey}" allowfullscreen></iframe>
						{/if}
						{include file="accordion-items.tpl" relatedItems=$relatedPlaces}
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
						{include file="accordion-items.tpl" relatedItems=$relatedEvents}
					</div>
				</div>
			</div>
		{/if}

		{if $hasEducationInfo}
			<div class="panel active{*toggle on for open*}" id="educationPanel">
				<a href="#educationPanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Education
						</div>
					</div>
				</a>
				<div id="educationPanelBody" class="panel-collapse collapse in{*toggle on for open*}">
					<div class="panel-body">
						{if $degreeName}
							<div class="row">
								<div class="result-label col-sm-4">Degree Name: </div>
								<div class="result-value col-sm-8">
									{$degreeName}
								</div>
							</div>
						{/if}
						{if $graduationDate}
							<div class="row">
								<div class="result-label col-sm-4">Graduation Date: </div>
								<div class="result-value col-sm-8">
									{$graduationDate}
								</div>
							</div>
						{/if}
						{foreach from=$educationPeople item="educationPerson"}
							<div class="row">
								<div class="result-label col-sm-4">
									{$educationPerson.role}:
								</div>
								<div class="result-value col-sm-8">
									{if $educationPerson.link}
										<a href='{$educationPerson.link}'>
											{$educationPerson.label}
										</a>
									{else}
										{$educationPerson.label}
									{/if}
								</div>
							</div>
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
								<a href="{$militaryRecord.branchLink}">{$militaryRecord.branch}</a>
							</div>
						</div>
						<div class="row">
							<div class="result-label col-sm-4">Conflict: </div>
							<div class="result-value col-sm-8">
								<a href="{$militaryRecord.conflictLink}">{$militaryRecord.conflict}</a>
							</div>
						</div>

					</div>
				</div>
			</div>
		{/if}

		{if count($notes) > 0}
			<div class="panel active" id="notesPanel">
				<a href="#notesPanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Notes
						</div>
					</div>
				</a>
				<div id="notesPanelBody" class="panel-collapse collapse in">
					<div class="panel-body">
						{foreach from=$notes item=note}
							<div>
								{$note}
							</div>
						{/foreach}
					</div>
				</div>
			</div>
		{/if}

		{if count($subjects) > 0}
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

		{if $productionTeam}
			<div class="panel {*active toggle on for open*}" id="relatedPeoplePanel">
				<a href="#productionTeamPanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Acknowledgements
						</div>
					</div>
				</a>
				<div id="productionTeamPanelBody" class="panel-collapse collapse {*in toggle on for open*}">
					<div class="panel-body">
						{foreach from=$productionTeam item=entity}
							<div class="relatedPerson row">
								<div class="col-tn-12">
									<a href='{$entity.link}'>
										{$entity.label}
									</a>
									{if $entity.role}
										&nbsp;({$entity.role})
									{/if}
									{if $entity.note}
										&nbsp;- {$entity.note}
									{/if}
								</div>
							</div>
						{/foreach}

					</div>
				</div>
			</div>
		{/if}

		{if count($externalLinks) > 0}
			<div class="panel active" id="externalLinksPanel">
				<a href="#externalLinksPanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Links
						</div>
					</div>
				</a>
				<div id="externalLinksPanelBody" class="panel-collapse collapse in">
					<div class="panel-body">
						{foreach from=$externalLinks item=link}
							<div>
								<a href="{$link.link}" target="_blank">
									{$link.text}
								</a>
							</div>
						{/foreach}
					</div>
				</div>
			</div>
		{/if}

		{if $identifier || $hasRecordInfo}
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
						{if $dateCreated}
							<div class="row">
								<div class="result-label col-sm-4">Created: </div>
								<div class="result-value col-sm-8">
									{$dateCreated}
								</div>
							</div>
						{/if}

						{if $dateIssued}
							<div class="row">
								<div class="result-label col-sm-4">Issued: </div>
								<div class="result-value col-sm-8">
									{$dateIssued}
								</div>
							</div>
						{/if}

						{if $language}
							<div class="row">
								<div class="result-label col-sm-4">Language: </div>
								<div class="result-value col-sm-8">
									{$language}
								</div>
							</div>
						{/if}

						{* Local Identifier *}
						{if count($identifier) > 0}
							<div class="row">
								<div class="result-label col-sm-4">Local Identifier{if count($identifier) > 1}s{/if}: </div>
								<div class="result-value col-sm-8">
									{implode subject=$identifier glue=', '}
								</div>
							</div>
						{/if}

						{* Date Created *}
						{if $postcardPublisherNumber}
							<div class="row">
								<div class="result-label col-sm-4">Postcard Publisher Number: </div>
								<div class="result-value col-sm-8">
									{$postcardPublisherNumber}
								</div>
							</div>
						{/if}

						{if $physicalExtents || $physicalLocation || $shelfLocator}

							{* Physical Description *}
							{if !empty($physicalExtents)}
								<div class="row">
									<div class="result-label col-sm-4">Physical Description: </div>
									<div class="result-value col-sm-8">
										{foreach from=$physicalExtents item=extent}
											{if $extent}
												<div>{$extent}</div>
											{/if}
										{/foreach}
									</div>
								</div>
							{/if}

							{* Physical Location *}
							{if !empty($physicalLocation)}
								<div class="row">
									<div class="result-label col-sm-4">Located at: </div>
									<div class="result-value col-sm-8">
										{foreach from=$physicalLocation item=location}
											{if $location}
												<div>{$location}</div>
											{/if}
										{/foreach}
									</div>
								</div>
							{/if}

							{* Shelf Locator *}
							{if !empty($shelfLocator)}
								<div class="row">
									<div class="result-label col-sm-4">Shelf Locator: </div>
									<div class="result-value col-sm-8">
										{foreach from=$shelfLocator item=location}
											{if $location}
												<div>{$location}</div>
											{/if}
										{/foreach}
									</div>
								</div>
							{/if}
						{/if}

						{* Record Origin Info *}
						{if $hasRecordInfo}
							{if $recordOrigin}
								<div class="row">
									<div class="result-label col-sm-4">Entered By: </div>
									<div class="result-value col-sm-8">
										{$recordOrigin}
									</div>
								</div>
							{/if}
							{if $recordCreationDate}
								<div class="row">
									<div class="result-label col-sm-4">Entered On: </div>
									<div class="result-value col-sm-8">
										{$recordCreationDate}
									</div>
								</div>
							{/if}
							{if $recordChangeDate}
								<div class="row">
									<div class="result-label col-sm-4">Last Changed: </div>
									<div class="result-value col-sm-8">
										{$recordChangeDate}
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
						{if $rightsHolderTitle}
							<div><em>Rights held by <a href="{$rightsHolderLink}">{$rightsHolderTitle}</a></em></div>
						{/if}
						{if $rightsCreatorTitle}
							<div><em>Rights created by <a href="{$rightsCreatorLink}">{$rightsCreatorTitle}</a></em></div>
						{/if}
					</div>
				</div>
			</div>
		{/if}

		{if $repositoryLink && $user && ($user->hasRole('archives') || $user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin'))}
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
						<a class="btn btn-small btn-default" href="{$repositoryLink}/datastream/MODS/edit" target="_blank">
							Edit MODS Record
						</a>
					</div>
				</div>
			</div>
		{/if}

	</div>
{/strip}