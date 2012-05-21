<script type="text/javascript" src="{$path}/js/overdrive.js"></script>
<ul data-role="listview" data-theme="a">
	<li data-role="list-divider">eContent Titles</li>
	<li><a href="/MyResearch/EContentCheckedOut">My checked out items <span class="ui-li-count">{$profile.numEContentCheckedOut}</span></a></li>
	{if $hasProtectedEContent}
		<li><a href="index.html">My holds available <span class="ui-li-count">{$profile.numEContentAvailableHolds}</span></a></li>
		<li><a href="index.html">My holds unavailable <span class="ui-li-count">{$profile.numEContentUnavailableHolds}</span></a></li>
		<li><a href="index.html">Wish List <span class="ui-li-count">{$profile.numEContentWishList}</span></a></li>
	{/if}
	<li data-role="list-divider">OverDrive Titles</li>
	<li><a href="index.html">My checked out items <span class="ui-li-count" id='checkedOutItemsOverDrivePlaceholder'>?</span></a></li>
	<li><a href="index.html">My holds available <span class="ui-li-count" id='availableHoldsOverDrivePlaceholder'>?</span></a></li>
	<li><a href="index.html">My holds unavailable <span class="ui-li-count" id='unavailableHoldsOverDrivePlaceholder'>?</span></a></li>
	<li data-role="list-divider">Actions</li>
	<li data-icon="delete"><a href="/MyResearch/Logout" data-ajax=false>Log Out</a></li>
</ul>
{literal}
	<script type="text/javascript">
		getOverDriveSummary();
	</script>
{/literal}