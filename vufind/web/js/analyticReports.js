function changeActiveReport(){
	var newDestination = $("#reportSwitcherSelect option:selected").data("destination");
	window.location.href = newDestination;
}
function showFilterValues(control){
	//Show options for this 
	var activeFilter = $(control);
	var selectedOption = activeFilter.find(":selected").val();
	var curIndex = activeFilter.data("filter-index");
	activeFilter.parent().find(".filterValues").remove();
	var filterValueSelection = "<select class='filterValues' name='filterValue[" + curIndex + "]'>";
	for (selectedValue in filterValues[selectedOption]){
		filterValueSelection += "<option value='" + selectedValue + "'>" + selectedValue + "</option>";
	}
	filterValueSelection += "</select>";
	activeFilter.after(filterValueSelection);
	//
}

function getFilterParams() {
	return filterParams;
}

var activePageViewChart;
function setupRecentPageViewChart() {
	activePageViewChart = new Highcharts.Chart({
		chart : {
			renderTo : 'activePageViewsChart',
			type: 'column',
		},
		legend : {
			enabled: false,
		},
		title: {
			text: 'Page Views'
		},
		xAxis: {
			title: {
				text: 'Time'
			},
		},
		
		yAxis: {
			title: {
				text: 'Count'
			},
			allowDecimals: false,
			min: 0,
		},
		series: [{name:'Page Views', data:[0,0,0,0,0,0,0,0,0,0, 
		                                  0,0,0,0,0,0,0,0,0,0,
		                                  0,0,0,0,0,0,0,0,0,0,
		                                  0,0,0,0,0,0,0,0,0,0, 
		                                  0,0,0,0,0,0,0,0,0,0,
		                                  0,0,0,0,0,0,0,0,0,0
		                                  ]},
		         ]
		
	});
}

var recentUsersChart;
function setupRecentUsersChart() {
	recentUsersChart = new Highcharts.Chart({
		chart : {
			renderTo : 'activeUsersChart',
			type: 'column',
		},
		legend : {
			enabled: false,
		},
		title: {
			text: 'Users'
		},
		xAxis: {
			title: {
				text: 'Time'
			},
		},
		
		yAxis: {
			title: {
				text: 'Count'
			},
			allowDecimals: false,
			min: 0,
		},
		series: [{name:'Page Views', data:[0,0,0,0,0,0,0,0,0,0, 
		                                  0,0,0,0,0,0,0,0,0,0,
		                                  0,0,0,0,0,0,0,0,0,0,
		                                  0,0,0,0,0,0,0,0,0,0, 
		                                  0,0,0,0,0,0,0,0,0,0,
		                                  0,0,0,0,0,0,0,0,0,0
		                                  ]},
		         ]
		
	});
}

var recentSearchesChart;
function setupRecentSearchesChart() {
	recentSearchesChart = new Highcharts.Chart({
		chart : {
			renderTo : 'activeSearchesChart',
			type: 'column',
		},
		legend : {
			enabled: false,
		},
		title: {
			text: 'Searches'
		},
		xAxis: {
			title: {
				text: 'Time'
			},
		},
		
		yAxis: {
			title: {
				text: 'Count'
			},
			allowDecimals: false,
			min: 0,
		},
		series: [{name:'Page Views', data:[0,0,0,0,0,0,0,0,0,0, 
		                                  0,0,0,0,0,0,0,0,0,0,
		                                  0,0,0,0,0,0,0,0,0,0,
		                                  0,0,0,0,0,0,0,0,0,0, 
		                                  0,0,0,0,0,0,0,0,0,0,
		                                  0,0,0,0,0,0,0,0,0,0
		                                  ]},
		         ]
		
	});
}

var recentEventsChart;
function setupRecentEventsChart() {
	recentEventsChart = new Highcharts.Chart({
		chart : {
			renderTo : 'activeEventsChart',
			type: 'column',
		},
		legend : {
			enabled: false,
		},
		title: {
			text: 'Events'
		},
		xAxis: {
			title: {
				text: 'Time'
			},
		},
		
		yAxis: {
			title: {
				text: 'Count'
			},
			allowDecimals: false,
			min: 0,
		},
		series: [{name:'Events', data:[0,0,0,0,0,0,0,0,0,0, 
		                                  0,0,0,0,0,0,0,0,0,0,
		                                  0,0,0,0,0,0,0,0,0,0,
		                                  0,0,0,0,0,0,0,0,0,0, 
		                                  0,0,0,0,0,0,0,0,0,0,
		                                  0,0,0,0,0,0,0,0,0,0
		                                  ]},
		         ]
		
	});
}

function getRecentActivity(){
	var filterParms = getFilterParams();
	$.getJSON(path + "/Report/AJAX?method=getRecentActivity&interval=5" + filterParms,
		function(data) {
			activePageViewChart.series[0].addPoint(parseInt(data.pageViews), true, true);
			recentUsersChart.series[0].addPoint(parseInt(data.activeUsers), true, true);
			recentSearchesChart.series[0].addPoint(parseInt(data.searches), true, true);
			recentEventsChart.series[0].addPoint(parseInt(data.events), true, true);
			setTimeout("getRecentActivity()", 5000);
		}
	);
}

var searchesByTypeChart;
function setupSearchesByTypeChart() {
	searchesByTypeChart = new Highcharts.Chart({
		chart : {
			renderTo : 'searchesByTypeChart',
			type: 'pie',
			events: {
				load: getSearchByTypeData
			}
		},
		legend : {
			enabled: false,
		},
		title: {
			text: 'Searches By Type'
		},
		plotOptions: {
			pie: {
				allowPointSelect: true,
				cursor: 'pointer',
				dataLabels: {
					enabled: false
				},
				showInLegend: false
			}
		},
		xAxis: {
			title: {
				text: 'Type'
			},
		},
		
		yAxis: {
			title: {
				text: '% Usage'
			},
			allowDecimals: false,
			min: 0,
		},
		series: [{
			name: 'Searches by type',
			data: []
		}]
	});
}

function getSearchByTypeData(){
	var filterParms = getFilterParams();
	$.getJSON(path + "/Report/AJAX?method=getSearchByTypeData&forGraph=true" + filterParms,
		function(data) {
			$.each(data, function(i, val){
				searchesByTypeChart.series[0].addPoint(val, true, false);
			});
		}
	);
}

var searchesByScopeChart;
function setupSearchesByScopeChart() {
	searchesByScopeChart = new Highcharts.Chart({
		chart : {
			renderTo : 'searchesByScopeChart',
			type: 'pie',
			events: {
				load: getSearchByScopeData
			}
		},
		legend : {
			enabled: false,
		},
		title: {
			text: 'Searches By Scope'
		},
		plotOptions: {
			pie: {
				allowPointSelect: true,
				cursor: 'pointer',
				dataLabels: {
					enabled: false
				},
				showInLegend: false
			}
		},
		xAxis: {
			title: {
				text: 'Scope'
			},
		},
		
		yAxis: {
			title: {
				text: '% Usage'
			},
			allowDecimals: false,
			min: 0,
		},
		series: [{
			name: 'Searches by scope',
			data: []
		}]
	});
}
function getSearchByScopeData(){
	var filterParms = getFilterParams();
	$.getJSON(path + "/Report/AJAX?method=getSearchByScopeData&forGraph=true" + filterParms,
		function(data) {
			$.each(data, function(i, val){
				searchesByScopeChart.series[0].addPoint(val, true, false);
			});
		}
	);
}

var searchesWithFacetsChart;
function setupSearchesWithFacetsChart() {
	searchesWithFacetsChart = new Highcharts.Chart({
		chart : {
			renderTo : 'searchesWithFacetsChart',
			type: 'pie',
			events: {
				load: getSearchWithFacetsData
			}
		},
		legend : {
			enabled: false,
		},
		title: {
			text: 'Searches with Facets'
		},
		xAxis: {
			title: {
				text: 'Scope'
			},
		},
		
		yAxis: {
			title: {
				text: '% Usage'
			},
			allowDecimals: false,
			min: 0,
		},
		series: [{
			name: 'Searches with Facets',
			data: []
		}]
	});
}
function getSearchWithFacetsData(){
	var filterParms = getFilterParams();
	$.getJSON(path + "/Report/AJAX?method=getSearchWithFacetsData&forGraph=true" + filterParms,
		function(data) {
			$.each(data, function(i, val){
				searchesWithFacetsChart.series[0].addPoint(val, true, false);
			});
		}
	);
}

var facetUsageByTypeChart;
function setupFacetUsageByTypeChart() {
	facetUsageByTypeChart = new Highcharts.Chart({
		chart : {
			renderTo : 'facetUsageByTypeChart',
			type: 'pie',
			events: {
				load: getFacetUsageByTypeData
			}
		},
		legend : {
			enabled: false,
		},
		title: {
			text: 'Facets By Type'
		},
		plotOptions: {
			pie: {
				allowPointSelect: true,
				cursor: 'pointer',
				dataLabels: {
					enabled: false
				},
				showInLegend: false
			}
		},
		xAxis: {
			title: {
				text: 'Type'
			},
		},
		
		yAxis: {
			title: {
				text: '% Usage'
			},
			allowDecimals: false,
			min: 0,
		},
		series: [{
			name: 'Facet',
			data: []
		}]
	});
}
function getFacetUsageByTypeData(){
	var filterParms = getFilterParams();
	$.getJSON(path + "/Report/AJAX?method=getFacetUsageByTypeData&forGraph=true" + filterParms,
		function(data) {
			$.each(data, function(i, val){
				facetUsageByTypeChart.series[0].addPoint(val, true, false);
			});
		}
	);
}

var pageViewsByModuleChart;
function setupPageViewsByModuleChart() {
	pageViewsByModuleChart = new Highcharts.Chart({
		chart : {
			renderTo : 'pageViewsByModuleChart',
			type: 'bar',
			events: {
				load: getPageViewsByModuleData
			}
		},
		legend : {
			enabled: false,
		},
		title: {
			text: 'Page Views By Module'
		},
		xAxis: {
			title: {
				text: 'Module'
			},
		},
		
		yAxis: {
			title: {
				text: 'Page Views'
			},
			allowDecimals: false,
			min: 0,
		},
		series: [{
			name: 'Page Views',
			data: []
		}]
	});
}
function getPageViewsByModuleData(){
	var filterParms = getFilterParams();
	$.getJSON(path + "/Report/AJAX?method=getPageViewsByModuleData&forGraph=true" + filterParms,
		function(data) {
			var categories = new Array();
			$.each(data, function(i, val){
				pageViewsByModuleChart.series[0].addPoint(val, true, false);
				categories.push( val[0]);
			});
			pageViewsByModuleChart.xAxis[0].setCategories(categories);
		}
	);
}

var pageViewsByThemeChart;
function setupPageViewsByThemeChart() {
	pageViewsByThemeChart = new Highcharts.Chart({
		chart : {
			renderTo : 'pageViewsByThemeChart',
			type: 'bar',
			events: {
				load: getPageViewsByThemeData
			}
		},
		legend : {
			enabled: false,
		},
		title: {
			text: 'Page Views By Theme'
		},
		xAxis: {
			title: {
				text: 'Theme'
			},
		},
		
		yAxis: {
			title: {
				text: 'Page Views'
			},
			allowDecimals: false,
			min: 0,
		},
		series: [{
			name: 'Page Views',
			data: []
		}]
	});
}
function getPageViewsByThemeData(){
	var filterParms = getFilterParams();
	$.getJSON(path + "/Report/AJAX?method=getPageViewsByThemeData&forGraph=true" + filterParms,
		function(data) {
			var categories = new Array();
			$.each(data, function(i, val){
				pageViewsByThemeChart.series[0].addPoint(val, true, false);
				categories.push( val[0]);
			});
			pageViewsByThemeChart.xAxis[0].setCategories(categories);
		}
	);
}


var pageViewsByDeviceChart;
function setupPageViewsByDeviceChart() {
	pageViewsByDeviceChart = new Highcharts.Chart({
		chart : {
			renderTo : 'pageViewsByDeviceChart',
			type: 'bar',
			events: {
				load: getPageViewsByDeviceData
			}
		},
		legend : {
			enabled: false,
		},
		title: {
			text: 'Page Views By Device'
		},
		xAxis: {
			title: {
				text: 'Device'
			},
		},
		
		yAxis: {
			title: {
				text: 'Page Views'
			},
			allowDecimals: false,
			min: 0,
		},
		series: [{
			name: 'Page Views',
			data: []
		}]
	});
}
function getPageViewsByDeviceData(){
	var filterParms = getFilterParams();
	$.getJSON(path + "/Report/AJAX?method=getPageViewsByDeviceData&forGraph=true" + filterParms,
		function(data) {
			var categories = new Array();
			$.each(data, function(i, val){
				pageViewsByDeviceChart.series[0].addPoint(val, true, false);
				categories.push( val[0]);
			});
			pageViewsByDeviceChart.xAxis[0].setCategories(categories);
		}
	);
}

var pageViewsByHomeLocationChart;
function setupPageViewsByHomeLocationChart() {
	pageViewsByHomeLocationChart = new Highcharts.Chart({
		chart : {
			renderTo : 'pageViewsByHomeLocationChart',
			type: 'bar',
			events: {
				load: getPageViewsByHomeLocationData
			}
		},
		legend : {
			enabled: false,
		},
		title: {
			text: 'Page Views By Home Location'
		},
		xAxis: {
			title: {
				text: 'Home Location'
			},
		},
		
		yAxis: {
			title: {
				text: 'Page Views'
			},
			allowDecimals: false,
			min: 0,
		},
		series: [{
			name: 'Page Views',
			data: []
		}]
	});
}
function getPageViewsByHomeLocationData(){
	var filterParms = getFilterParams();
	$.getJSON(path + "/Report/AJAX?method=getPageViewsByHomeLocationData&forGraph=true" + filterParms,
		function(data) {
			var categories = new Array();
			$.each(data, function(i, val){
				pageViewsByHomeLocationChart.series[0].addPoint(val, true, false);
				categories.push( val[0]);
			});
			pageViewsByHomeLocationChart.xAxis[0].setCategories(categories);
		}
	);
}

var pageViewsByPhysicalLocationChart;
function setupPageViewsByPhysicalLocationChart() {
	pageViewsByPhysicalLocationChart = new Highcharts.Chart({
		chart : {
			renderTo : 'pageViewsByPhysicalLocationChart',
			type: 'bar',
			events: {
				load: getPageViewsByPhysicalLocationData
			}
		},
		legend : {
			enabled: false,
		},
		title: {
			text: 'Page Views By Physical Location'
		},
		xAxis: {
			title: {
				text: 'Physical Location'
			},
		},
		
		yAxis: {
			title: {
				text: 'Page Views'
			},
			allowDecimals: false,
			min: 0,
		},
		series: [{
			name: 'Page Views',
			data: []
		}]
	});
}
function getPageViewsByPhysicalLocationData(){
	var filterParms = getFilterParams();
	$.getJSON(path + "/Report/AJAX?method=getPageViewsByPhysicalLocationData&forGraph=true" + filterParms,
		function(data) {
			var categories = new Array();
			$.each(data, function(i, val){
				pageViewsByPhysicalLocationChart.series[0].addPoint(val, true, false);
				categories.push( val[0]);
			});
			pageViewsByPhysicalLocationChart.xAxis[0].setCategories(categories);
		}
	);
}

