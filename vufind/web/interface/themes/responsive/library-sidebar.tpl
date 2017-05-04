{strip}
	<div id="home-page-library-section" class="row"{if $displaySidebarMenu} style="display: none"{/if}>
		{if $showLibraryHoursAndLocationsLink}
			<a href="{$path}/AJAX/JSON?method=getHoursAndLocations" data-title="Library Hours and Locations" class="modalDialogTrigger">
				<div id="home-page-hours-locations" class="sidebar-button">
					{if !isset($numHours) || $numHours > 0}Library Hours &amp; {/if}Location{if $numLocations != 1}s{/if}
				</div>
			</a>
		{/if}

		{if $homeLink}
			<a href="{$homeLink}">
				<div id="home-page-home-button" class="sidebar-button">
					Library Home Page
				</div>
			</a>
		{/if}

		{if $libraryLinks}
			<div id="home-library-links" class="sidebar-links accordion">
				<div class="panel-group" id="link-accordion">
					{foreach from=$libraryLinks item=linkCategory key=categoryName name=linkLoop}
						{if $categoryName}
							{* Put the links within a collapsible section *}
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
										{foreach from=$linkCategory item=link key=linkName}
											{if $link->htmlContents}
												{$link->htmlContents}
											{else}
												<div>
													<a href="{$link->url}">{$linkName}</a>
												</div>
											{/if}
										{/foreach}
									</div>
								</div>
							</div>
						{else}
							{* No category name, display these links as buttons *}
							{foreach from=$linkCategory item=link key=linkName}
								{if $link->htmlContents}
									{$link->htmlContents}
								{else}
									<a href="{$link->url}">
										<div class="sidebar-button custom-sidebar-button" id="{$linkName|escapeCSS|lower}-button">
											{$linkName}
										</div>
									</a>
								{/if}
							{/foreach}
						{/if}
					{/foreach}

				</div>
			</div>
		{/if}
	</div>
{/strip}