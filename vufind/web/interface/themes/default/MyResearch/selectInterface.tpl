<div id="page-content" class="content">
	<div class="myAccountTitle">{translate text='Select the Library Catalog you wish to use'}</div>
	<div id="selectLibraryMenu">
		<form id="selectLibrary" method="get" action="/MyResearch/SelectInterface">
			<div>
				{foreach from=$libraries item=libraryInfo}
					<div class="selectLibraryOption">
						<input type="radio" id="library{$libraryInfo.id}" name="library" value="{$libraryInfo.id}"/><label for="library{$libraryInfo.id}">{$libraryInfo.displayName}</label>
					</div>
				{/foreach}
				<div class="selectLibraryOption">
					<input type="checkbox" name="rememberThis" checked="checked" id="rememberThis"><label for="rememberThis"><b>Remember This</b></label>
				</div>
				<input type="submit" name="submit" value="Select Library Catalog" id="submitButton"/>
			</div>
		</form>
	</div>
</div>