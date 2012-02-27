function initializeSeriesCarousel(){
stepcarousel.setup({
	galleryid: 'series', //id of carousel DIV
	beltclass: 'serieslist', //class of inner "belt" DIV containing all the panel DIVs
	panelclass: 'item', //class of panel DIVs each holding content
	autostep: {enable:false, moveby:1, pause:3000},
	panelbehavior: {speed:500, wraparound:false, persist:true},
	defaultbuttons: {enable: true, moveby: 1, leftnav: ['http://opac.marmot.org/interface/themes/marmot/images/seriesleft.png', -25, 50], rightnav: ['http://opac.marmot.org/interface/themes/marmot/images/seriesright.png', -20, 50]},
	statusvars: ['statusA', 'statusB', 'statusC'], //register 3 variables that contain current panel (start), current panel (last), and total panels
	contenttype: ['inline'] //content setting ['inline'] or ['ajax', 'path_to_external_file']
});	
}