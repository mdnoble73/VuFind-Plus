//Add error reporting system wide
/* Provides additional error trapping information.  Should not be used on production. */
window.onerror = function (err, url, line) {
	alert('The following error occured: ' + err + '\n' +
	'In file: ' + url + '\n' +
	'At line: ' + line);
	return true;
}

/*
window.onerror = function (err, url, line) {
	alert('Oops, we seem to be having a problem.  We\'re working on it now.');
	$.ajax({
	  type: 'POST',
	  url: path + '/Admin/ErrorReport',
	  data: {url: url, error: err, line: line}
	});

	return true;
}*/