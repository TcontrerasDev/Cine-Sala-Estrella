document.addEventListener('DOMContentLoaded', function () {
	var offcanvasEl = document.getElementById('offcanvasNav');
	if (!offcanvasEl) return;

	offcanvasEl.querySelectorAll('a[href]').forEach(function (link) {
		var href = link.getAttribute('href');
		if (!href || href.startsWith('http') || href.startsWith('//')) return;

		link.addEventListener('click', function () {
			var instance = bootstrap.Offcanvas.getInstance(offcanvasEl);
			if (instance) instance.hide();
		});
	});
});
