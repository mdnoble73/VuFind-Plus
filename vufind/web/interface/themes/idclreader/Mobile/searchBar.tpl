<form action="/Union/Search" data-ajax="false">
	<div class="ui-grid-a" id='searchForm'>
		<div class="ui-block-a search">
			<label for="search-basic">Search</label>
			<input type="search" name="lookfor" id="searc-basic" value="" data-theme='a'/>
		</div>
		<div class="ui-block-b search">
			<label class="offscreen" for="searchForm_type">Search Type</label>
			<select id="searchForm_type" name="basicType" data-native-menu="false">
	   			<option value="Keyword">Keyword</option>
	            <option value="AllFields">All Fields</option>
	            <option value="Title">Title</option>
	            <option value="Author">Author</option>
	            <option value="Subject">Subject</option>
	            <option value="ISN">ISBN/ISSN/UPC</option>
	            <option value="tag">Tag</option>
	            <option value="econtentText">Full Text</option>
	            <option value="id">Record Number</option>
			</select>
			<input type="hidden" name="shard[]" value='eContent' id="shardeContent" />
			<input type='hidden' name="searchSource" id="searchSource" value='local' />
		</div>
	</div>
</form>