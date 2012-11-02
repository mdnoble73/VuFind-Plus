function getFilterParams() {
	return "";
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
	$.getJSON(path + "/Report/AJAX?method=getSearchByTypeData" + filterParms,
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
	$.getJSON(path + "/Report/AJAX?method=getSearchByScopeData" + filterParms,
		function(data) {
			$.each(data, function(i, val){
				searchesByScopeChart.series[0].addPoint(val, true, false);
			});
		}
	);
}