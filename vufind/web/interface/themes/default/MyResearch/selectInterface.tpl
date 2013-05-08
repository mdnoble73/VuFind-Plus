<div id="page-content" class="content">
	<div class="myAccountTitle">{translate text='Select your Home Library'}</div>
	<div id="selectLibraryMenu">
		<form id="selectLibrary" method="get" action="/MyResearch/SelectInterface">
			<div>
				{foreach from=$libraries item=libraryInfo}
					<div class="selectLibraryOption">
						<input type="radio" id="library{$libraryInfo.id}" name="library" value="{$libraryInfo.id}"/><label for="library{$libraryInfo.id}">{$libraryInfo.displayName}</label>
					</div>
				{/foreach}
				<input type="submit" value="Set Library" id="submitButton"/>
			</div>
		</form>
	</div>
</div>