{strip}
	<div id="home-page-library-section" class="row">
		{if $showLibraryHoursAndLocationsLink}
			<a href="{$path}/AJAX/JSON?method=getHoursAndLocations" data-title="Library Hours and Locations" class="modalDialogTrigger">
				<div id="home-page-hours-locations" class="sidebar-button">
					LIBRARY HOURS & LOCATIONS
				</div>
			</a>
		{/if}

		{if $homeLink}
			<a href="{$homeLink}">
				<div id="home-page-home-button" class="sidebar-button">
					LIBRARY HOME PAGE
				</div>
			</a>
		{/if}

		{if $libraryLinks}
			<div id="home-library-links" class="sidebar-links">
				<div class="panel-group" id="link-accordion">
					{foreach from=$libraryLinks item=linkCategory key=categoryName name=linkLoop}
						<div class="panel {if $smarty.foreach.linkLoop.first && !$user}active{/if}">
							<a data-toggle="collapse" data-parent="#link-accordion" href="#{$categoryName|escapeCSS}Panel">
								<div class="panel-heading">
									<div class="panel-title">
										{$categoryName}
									</div>
								</div>
							</a>
							<div id="{$categoryName|escapeCSS}Panel" class="panel-collapse collapse {if $smarty.foreach.linkLoop.first && !$user}in{/if}">
								<div class="panel-body">
									{foreach from=$linkCategory item=linkUrl key=linkName}
										<div>
											<a href="{$linkUrl}">{$linkName}</a>
										</div>
									{/foreach}
								</div>
							</div>
						</div>
					{/foreach}

				</div>
			</div>
		{/if}
	</div>
{/strip}