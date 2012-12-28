function showReindexNotes(id){
	ajaxLightbox("/Admin/AJAX?method=getReindexNotes&amp;id=" + id);
	return false;
}
function showReindexProcessNotes(id){
	ajaxLightbox("/Admin/AJAX?method=getReindexProcessNotes&amp;id=" + id);
	return false;
}
function toggleReindexProcessInfo(id){
	$("#reindexEntry" + id).toggleClass("expanded collapsed");
	$("#processInfo" + id).toggle();
}
function showReindexProcessNotes(id){
	ajaxLightbox("/Admin/AJAX?method=getReindexProcessNotes&id=" + id);
	return false;
}
function showCronNotes(id){
	ajaxLightbox("/Admin/AJAX?method=getCronNotes&id=" + id);
	return false;
}
function showCronProcessNotes(id){
	ajaxLightbox("/Admin/AJAX?method=getCronProcessNotes&id=" + id);
	return false;
}
function toggleCronProcessInfo(id){
	$("#cronEntry" + id).toggleClass("expanded collapsed");
	$("#processInfo" + id).toggle();
}