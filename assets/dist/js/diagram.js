function rapikanGarisTree() {
	document
		.querySelectorAll(".family-tree .children")
		.forEach(function (children) {
			const nodes = children.querySelectorAll(":scope > .node");

			if (nodes.length === 0) {
				return;
			}

			// Hapus garis lama
			const oldLine = children.querySelector(":scope > .family-line");

			if (oldLine) {
				oldLine.remove();
			}

			const first = nodes[0];
			const last = nodes[nodes.length - 1];

			const firstRect = first.getBoundingClientRect();
			const lastRect = last.getBoundingClientRect();

			const parentRect = children.getBoundingClientRect();

			// Tengah anak pertama
			const start = firstRect.left + firstRect.width / 2 - parentRect.left;

			// Tengah anak terakhir
			const end = lastRect.left + lastRect.width / 2 - parentRect.left;

			// Buat garis
			const line = document.createElement("div");
			line.className = "family-line";
			line.style.left = start + "px";
			line.style.width = end - start + "px";
			children.appendChild(line);
		});
}

/* Saat halaman selesai */
window.addEventListener("load", function () {
	setTimeout(rapikanGarisTree, 100);
});

/* Saat browser di-resize */
window.addEventListener("resize", function () {
	rapikanGarisTree();
});
