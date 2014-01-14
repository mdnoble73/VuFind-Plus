{strip}
<div class="row">
	{* Setup the left bar *}
	<div class="col-sm-4 col-md-4 col-lg-3" id="home-page-side-bar">

		<div id="home-page-search" class="container-12">
			<div class="row">
				<div class="col-md-12 text-center" id="home-page-search-label">
					SEARCH <span class="glyphicon glyphicon-search"></span>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-10 col-md-10 col-sm-push-1 col-md-push-1">
					<form method="get" action="{$path}/Union/Search" id="searchForm" class="form-inline" onsubmit="VuFind.Searches.processSearchForm();">
						<input type="hidden" name="basicType" id="basicType" value=""/>
						<input type="hidden" name="genealogyType" id="genealogyType" value=""/>
						<fieldset>
							<div class="input-group input-group-sm">
								<input class="form-control"{/strip}
								       id="lookfor"
								       placeholder=""
								       type="search"
								       name="lookfor"
								       size="30"
								       value="{$lookfor|escape:"html"}"
								       title="Enter one or more terms to search for.	Surrounding a term with quotes will limit result to only those that exactly match the term."
								       onkeyup="return VuFind.Searches.resetSearchType()"
								       onclick="$(this).select()"
								       autocomplete="off"
												{strip}/>
								<div class="input-group-btn">
									<button class="btn btn-default" type="submit"><i class="glyphicon glyphicon-search"></i></button>
									<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
										<span class="caret"></span>
									</button>
									<ul id="searchType" class="dropdown-menu text-left">
										{foreach from=$basicSearchTypes item=searchDesc key=searchVal}
											<li>
												<a class="catalogType" href="#" onclick="return VuFind.Searches.updateSearchTypes('catalog', '{$searchVal}', '#searchForm');">{translate text="by"} {translate text=$searchDesc}</a>
											</li>
										{/foreach}
										<li class="divider catalogType"></li>
										{foreach from=$genealogySearchTypes item=searchDesc key=searchVal}
											<li>
												<a class="genealogyType" href="#" onclick="return VuFind.Searches.updateSearchTypes('genealogy', '{$searchVal}', '#searchForm');">{translate text="by"} {translate text=$searchDesc}</a>
											</li>
										{/foreach}
										<li class="divider genealogyType"></li>
										{if $showAdvancedSearchbox == 1}
											<li class="catalogType">
												<a href="{$path}/Search/AdvancedPopup" id="advancedSearch" title="{translate text='Advanced Search'}" class="modalDialogTrigger">
													<i class="icon-plus-sign"></i> {translate text="Advanced"}
												</a>
											</li>
										{/if}

										{* Link to Search Tips Help *}
										<li>
											<a href="{$path}/Help/Home?topic=search" title="{translate text='Search Tips'}" id="searchTips" class="modalDialogTrigger">
												<i class="icon-question-sign"></i> {translate text='Search Tips'}
											</a>
										</li>
									</ul>
								</div>
							</div>
						</fieldset>
					</form>
				</div>
			</div>
			<div class="row container-12 text-center">
				<select name="searchSource" id="searchSource" title="Select what to search.	Items marked with a * will redirect you to one of our partner sites." onchange='VuFind.Searches.enableSearchTypes();'>
					{if $filterList}
						<option data-catalog_type="existing" data-original_type="{$searchSource}" value="existing" title="{translate text="Existing Search"}" selected="selected" id="existing_search_option">{translate text="in Existing Search Results"}</option>
					{/if}
					{/strip}
					{foreach from=$searchSources item=searchOption key=searchKey}
						<option data-catalog_type="{$searchOption.catalogType}"
						        value="{$searchKey}"{if $searchKey == $searchSource && !$filterList} selected="selected"{/if}
										{if $searchKey == $searchSource} id="default_search_type"{/if}
						        title="{$searchOption.description}">
							{translate text="in"} {$searchOption.name}{if $searchOption.external} *{/if}
						</option>
					{/foreach}
					{strip}
				</select>
			</div>
		</div>
		<div id="home-page-login" class="text-center">
			<div class="logoutOptions hidden-phone" {if !$user} style="display: none;"{/if}>
				<a id="myAccountNameLink" href="{$path}/MyResearch/Home">Logged In As {$user->firstname|capitalize} {$user->lastname|capitalize}</a>
			</div>
			<div class="logoutOptions" {if !$user} style="display: none;"{/if}>
				<a href="{$path}/MyResearch/Home">{translate text="Your Account"}</a>
			</div>
			<div class="logoutOptions" {if !$user} style="display: none;"{/if}>
				<a href="{$path}/MyResearch/Logout" id="logoutLink" >{translate text="Log Out"}</a>
			</div>
			<div class="loginOptions" {if $user} style="display: none;"{/if}>
				{if $showLoginButton == 1}
					<a href="{$path}/MyResearch/Home" class='loginLink' title='Login To My Account' onclick="return VuFind.Account.followLinkIfLoggedIn(this);">{translate text="LOGIN TO MY ACCOUNT"}</a>
				{/if}
			</div>
		</div>
		<div id="home-page-library-section">
			<div id="home-page-hours-locations">
				<a href="">LIBRARY HOURS & LOCATIONS</a>
			</div>
			<div id="home-library-links">
				<div class="panel-group" id="link-accordion">
					{foreach from=$libraryLinks item=linkCategory key=categoryName name=linkLoop}
						<div class="panel {if $smarty.foreach.linkLoop.first}active{/if}">
							<div class="panel-heading">
								<div class="panel-title">
									<a data-toggle="collapse" data-parent="#link-accordion" href="#{$categoryName|escapeCss}Panel">
										{$categoryName}
									</a>
								</div>
							</div>
							<div id="{$categoryName|escapeCss}Panel" class="panel-collapse collapse {if $smarty.foreach.linkLoop.first}in{/if}">
								<div class="panel-body">
									{foreach from=$linkCategory item=linkUrl key=linkName}
										<div class="col-sm-5 col-md-5 col-lg-5">
											<a href="{$linkUrl}">{$linkName}</a>
										</div>
									{/foreach}
								</div>
							</div>
						</div>
					{/foreach}

				</div>
			</div>
		</div>

	</div>
	{* Setup the browse area *}
	<div class="col-sm-8 col-md-8 col-lg-9" id="homePageBrowseContent">
		<div class="homePageBrowseHeader">
			<div class="row text-center" id="browse-label">
				Browse the Catalog
			</div>
			<div class="row text-center" id="browse-label">
				{* Left Arrow *}
				{* Browse Categories *}
				{* Right Arrow *}
			</div>
		</div>


	</div>
</div>
{/strip}