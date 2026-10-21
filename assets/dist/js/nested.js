function ubahMode(mode) {
	const formBaru = document.getElementById("formDataBaru");
	const formSusulan = document.getElementById("formDataSusulan");

	if (mode === "baru") {
		formBaru.style.display = "block";

		formSusulan.style.display = "none";
	} else {
		formBaru.style.display = "none";

		formSusulan.style.display = "block";
	}
}

//    INIT DROPDOWN DATA SUSULAN
function initDropdownSusulan() {
	const selectAnak = document.getElementById("pilihAnakSusulan");
	const selectCucu = document.getElementById("pilihCucuSusulan");
	const selectCicit = document.getElementById("pilihCicitSusulan");

	if (selectAnak) {
		selectAnak.addEventListener("change", function () {
			const idAnak = this.value;

			console.log("Anak dipilih:", idAnak);

			if (!idAnak) {
				resetSelect("pilihCucuSusulan", "Pilih Cucu");
				resetSelect("pilihCicitSusulan", "Pilih Cicit");
				return;
			}

			loadCucuSusulan(idAnak);
		});
	}

	//    PILIH CUCU
	if (selectCucu) {
		selectCucu.addEventListener("change", function () {
			const idCucu = this.value;
			console.log("Cucu dipilih:", idCucu);
			if (!idCucu) {
				resetSelect("pilihCicitSusulan", "Pilih Cicit");
				return;
			}
			loadCicitSusulan(idCucu);
		});
	}

	//    PILIH CICIT
	if (selectCicit) {
		selectCicit.addEventListener("change", function () {
			console.log("Cicit dipilih:", this.value);
		});
	}
}

let anakIndex = 0;

document.addEventListener("DOMContentLoaded", function () {
	initMode();
	initFormSubmit();
	initDropdownSusulan();
});

//    PILIH MODE
function initMode() {
	const mode = document.getElementById("modeData");

	if (!mode) {
		return;
	}

	mode.addEventListener("change", function () {
		const value = this.value;
		const formBaru = document.getElementById("formDataBaru");
		const formSusulan = document.getElementById("formDataSusulan");

		if (formBaru) {
			formBaru.style.display = value === "baru" ? "block" : "none";
		}

		if (formSusulan) {
			formSusulan.style.display = value === "susulan" ? "block" : "none";
		}
	});
}

//    TAMBAH ANAK BARU
function tambahAnak() {
	const container = document.getElementById("anakContainer");

	if (!container) {
		return;
	}

	const empty = document.getElementById("emptyAnak");

	if (empty) {
		empty.style.display = "none";
	}

	const nomorAnak = getNextAnakNumber();
	const anak = document.createElement("div");

	anak.className = "anak";
	anak.dataset.index = anakIndex;
	anak.dataset.type = "new";
	anak.innerHTML = `
        <div class="judul">

            👨‍👩‍👧
            DATA ANAK KE-${nomorAnak}
        </div>


        <div class="form-group">
            <label>
                Nama Anak Ke-${nomorAnak}
            </label>

            <input
                type="text"
                name="anak[${anakIndex}][nama]"
                placeholder="Masukkan nama anak"
                required
            >

        </div>

        <div class="form-group">
            <label>Pasangan Anak Ke-${nomorAnak}</label>

            <input type="text"
                name="anak[${anakIndex}][menantu]"
                placeholder="Nama pasangan">
        </div>

        <div class="cucu-container"></div>
        <div class="actions">
            <button type="button" class="btn-blue" onclick="tambahCucu(this)">
                + Tambah Cucu
            </button>

			<button type="button" class="btn-red"
                onclick="hapusElement(this)">
                Hapus Anak Ke-${nomorAnak}
            </button>
        </div>
    `;

	container.appendChild(anak);
	anakIndex++;
	refreshNomorSemua();
}

//    TAMBAH CUCU BARU
function tambahCucu(button) {
	const anak = button.closest(".anak");

	if (!anak) {
		return;
	}

	const container = anak.querySelector(".cucu-container");

	if (!container) {
		return;
	}

	const anakIndexDB = anak.dataset.index;

	const cucuIndex = container.children.length;

	const nomorCucu = cucuIndex + 1;

	const nomorAnak = getNomorElement(anak);

	const cucu = document.createElement("div");

	cucu.className = "cucu";

	cucu.dataset.index = cucuIndex;

	cucu.dataset.type = "new";

	cucu.innerHTML = `

        <div class="judul">

            👶
            DATA CUCU KE-${nomorCucu}

            <small>
                (Anak Ke-${nomorAnak})
            </small>

        </div>


        <div class="form-group">

            <label>
                Nama Cucu Ke-${nomorCucu}
            </label>

            <input
                type="text"
                name="anak[${anakIndexDB}][cucu][${cucuIndex}][nama]"
                placeholder="Masukkan nama cucu"
                required
            >

        </div>


        <div class="form-group">

            <label>
                Pasangan Cucu Ke-${nomorCucu}
            </label>

            <input
                type="text"
                name="anak[${anakIndexDB}][cucu][${cucuIndex}][menantu]"
                placeholder="Nama pasangan"
            >

        </div>


        <div class="cicit-container"></div>


        <div class="actions">

            <button
                type="button"
                class="btn-blue"
                onclick="tambahCicit(this)"
            >
                + Tambah Cicit
            </button>


            <button
                type="button"
                class="btn-red"
                onclick="hapusElement(this)"
            >
                Hapus Cucu Ke-${nomorCucu}
            </button>

        </div>

    `;

	container.appendChild(cucu);

	refreshNomorSemua();
}

/* =========================================================
   TAMBAH CICIT BARU
   ========================================================= */

function tambahCicit(button) {
	const cucu = button.closest(".cucu");

	if (!cucu) {
		return;
	}

	const anak = button.closest(".anak");

	if (!anak) {
		return;
	}

	const cucuContainer = anak.querySelector(".cucu-container");

	const cucuIndex = Array.from(cucuContainer.children).indexOf(cucu);

	const container = cucu.querySelector(".cicit-container");

	if (!container) {
		return;
	}

	const cicitIndex = container.children.length;

	const nomorCicit = cicitIndex + 1;

	const nomorAnak = getNomorElement(anak);

	const nomorCucu = getNomorElement(cucu);

	const cicit = document.createElement("div");

	cicit.className = "cicit";

	cicit.dataset.index = cicitIndex;

	cicit.dataset.type = "new";

	cicit.innerHTML = `

        <div class="judul">

            👦
            DATA CICIT KE-${nomorCicit}

            <small>
                (Anak Ke-${nomorAnak},
                 Cucu Ke-${nomorCucu})
            </small>

        </div>


        <div class="form-group">

            <label>
                Nama Cicit Ke-${nomorCicit}
            </label>

            <input
                type="text"
                name="anak[${anak.dataset.index}][cucu][${cucuIndex}][cicit][${cicitIndex}][nama]"
                placeholder="Masukkan nama cicit"
                required
            >

        </div>


        <div class="baok-container"></div>


        <div class="actions">

            <button
                type="button"
                class="btn-blue"
                onclick="tambahBaok(this)"
            >
                + Tambah Baok
            </button>


            <button
                type="button"
                class="btn-red"
                onclick="hapusElement(this)"
            >
                Hapus Cicit Ke-${nomorCicit}
            </button>

        </div>

    `;

	container.appendChild(cicit);

	refreshNomorSemua();
}

/* =========================================================
   TAMBAH BAOK BARU
   ========================================================= */

function tambahBaok(button) {
	const cicit = button.closest(".cicit");

	if (!cicit) {
		return;
	}

	const cucu = button.closest(".cucu");

	const anak = button.closest(".anak");

	if (!cucu || !anak) {
		return;
	}

	const cicitContainer = cucu.querySelector(".cicit-container");

	const cicitIndex = Array.from(cicitContainer.children).indexOf(cicit);

	const cucuContainer = anak.querySelector(".cucu-container");

	const cucuIndex = Array.from(cucuContainer.children).indexOf(cucu);

	const container = cicit.querySelector(".baok-container");

	if (!container) {
		return;
	}

	const baokIndex = container.children.length;

	const nomorBaok = baokIndex + 1;

	const baok = document.createElement("div");

	baok.className = "baok";

	baok.dataset.index = baokIndex;

	baok.dataset.type = "new";

	baok.innerHTML = `

        <div class="judul">

            👶
            DATA BAOK KE-${nomorBaok}

        </div>


        <div class="form-group">

            <label>
                Nama Baok Ke-${nomorBaok}
            </label>

            <input
                type="text"
                name="anak[${anak.dataset.index}][cucu][${cucuIndex}][cicit][${cicitIndex}][baok][${baokIndex}][nama]"
                placeholder="Masukkan nama baok"
                required
            >

        </div>


        <button
            type="button"
            class="btn-red"
            onclick="hapusElement(this)"
        >
            Hapus Baok Ke-${nomorBaok}
        </button>

    `;

	container.appendChild(baok);

	refreshNomorSemua();
}

/* =========================================================
   DATA SUSULAN
   ========================================================= */

/*
   Pilih Anak existing
*/

function pilihAnakSusulan(idAnak) {
	if (!idAnak) {
		resetSelect("pilihCucuSusulan", "Pilih Cucu");

		resetSelect("pilihCicitSusulan", "Pilih Cicit");

		return;
	}

	loadCucuSusulan(idAnak);
}

/* =========================================================
   LOAD CUCU
   ========================================================= */

/* =========================================================
   LOAD CUCU SUSULAN
   ========================================================= */

function loadCucuSusulan(idAnak) {
	const select = document.getElementById("pilihCucuSusulan");
	const selectCicit = document.getElementById("pilihCicitSusulan");

	if (!select) {
		console.error("Element #pilihCucuSusulan tidak ditemukan");
		return;
	}

	// Reset Cicit
	if (selectCicit) {
		selectCicit.innerHTML = `
			<option value="">
				Pilih Cicit
			</option>
		`;
	}

	// Jika Anak belum dipilih
	if (!idAnak) {
		select.innerHTML = `
			<option value="">
				Pilih Cucu
			</option>
		`;

		return;
	}

	// Tampilkan loading
	select.innerHTML = `
		<option value="">
			Memuat Cucu...
		</option>
	`;

	const url = BASE_URL + "Dashboard1/get_cucu/" + idAnak;

	fetch(url)
		.then(function (response) {
			console.log("HTTP STATUS:", response.status);

			if (!response.ok) {
				throw new Error("HTTP Error " + response.status);
			}

			return response.text();
		})

		.then(function (text) {
			console.log("RAW RESPONSE CUCU:");
			console.log(text);

			let data;

			try {
				data = JSON.parse(text);
			} catch (error) {
				console.error("Response bukan JSON:", error);

				throw new Error("Response get_cucu bukan JSON");
			}

			console.log("JSON CUCU:");
			console.log(data);

			select.innerHTML = `
				<option value="">
					Pilih Cucu
				</option>
			`;

			if (!Array.isArray(data)) {
				console.error("Data Cucu bukan array:", data);

				select.innerHTML = `
					<option value="">
						Data Cucu tidak valid
					</option>
				`;

				return;
			}

			if (data.length === 0) {
				console.warn("Tidak ada Cucu untuk Anak ID:", idAnak);

				select.innerHTML = `
					<option value="">
						Belum ada Cucu
					</option>
				`;

				return;
			}

			data.forEach(function (item, index) {
				console.log("Cucu [" + index + "]:", item);

				const option = document.createElement("option");

				/*
				 * Dukung beberapa kemungkinan
				 * nama field dari API.
				 */

				const idCucu = item.id_cucu ?? item.id ?? item.id_cucu_susulan;

				const namaCucu = item.nama_cucu ?? item.nama ?? item.nama_cucu_susulan;

				if (!idCucu) {
					console.warn("ID Cucu tidak ditemukan:", item);

					return;
				}

				option.value = idCucu;

				option.textContent = namaCucu || "Cucu tanpa nama";

				select.appendChild(option);
			});

			console.log("Jumlah option Cucu:", select.options.length - 1);
		})

		.catch(function (error) {
			console.error("ERROR LOAD CUCU:", error);

			select.innerHTML = `
				<option value="">
					Gagal mengambil Cucu
				</option>
			`;
		});
}

/* =========================================================
   PILIH CUCU
   ========================================================= */

function pilihCucuSusulan(idCucu) {
	if (!idCucu) {
		resetSelect("pilihCicitSusulan", "Pilih Cicit");

		return;
	}

	loadCicitSusulan(idCucu);
}

/* =========================================================
   LOAD CICIT
   ========================================================= */

function loadCicitSusulan(idCucu) {
	const select = document.getElementById("pilihCicitSusulan");

	if (!select) {
		return;
	}

	// Kosongkan dulu
	select.innerHTML = `
        <option value="">
            Memuat Cicit...
        </option>
    `;

	if (!idCucu) {
		select.innerHTML = `
            <option value="">
                -- Pilih Cicit --
            </option>
        `;

		return;
	}

	const url = BASE_URL + "dashboard1/get_cicit/" + idCucu;

	console.log("Mengambil Cicit:", url);

	fetch(url)
		.then(function (response) {
			console.log("Status get_cicit:", response.status);

			if (!response.ok) {
				throw new Error("HTTP Error " + response.status);
			}

			return response.json();
		})

		.then(function (data) {
			console.log("Data Cicit:", data);

			select.innerHTML = `
                <option value="">
                    -- Pilih Cicit --
                </option>
            `;

			if (!Array.isArray(data) || data.length === 0) {
				select.innerHTML = `
                    <option value="">
                        -- Belum ada Cicit --
                    </option>
                `;

				return;
			}

			data.forEach(function (item) {
				const option = document.createElement("option");

				option.value = item.id_cicit;

				option.textContent = item.nama_cicit;

				select.appendChild(option);
			});
		})

		.catch(function (error) {
			console.error("ERROR get_cicit:", error);

			select.innerHTML = `
                <option value="">
                    -- Gagal mengambil Cicit --
                </option>
            `;
		});
}

/* =========================================================
   TAMBAH CUCU SUSULAN
   ========================================================= */

function tambahCucuSusulan() {
	const idAnak = getValue("pilihAnakSusulan");

	if (!idAnak) {
		alert("Silakan pilih Anak terlebih dahulu.");

		return;
	}

	const nama = getValue("namaCucuSusulan");

	if (!nama) {
		alert("Nama Cucu harus diisi.");

		return;
	}

	const menantu = getValue("menantuCucuSusulan");
	const form = document.getElementById("formSusulan");

	if (!form) {
		alert("Form susulan tidak ditemukan.");

		return;
	}

	addHidden(form, "jenis_susulan", "cucu");
	addHidden(form, "id_anak", idAnak);
	addHidden(form, "nama_cucu", nama);
	addHidden(form, "menantu_cucu", menantu);

	form.submit();
}
/* =========================================================
   TAMBAH CICIT SUSULAN
   ========================================================= */

function tambahCicitSusulan() {
	const idCucu = getValue("pilihCucuSusulan");

	if (!idCucu) {
		alert("Silakan pilih Cucu terlebih dahulu.");

		return;
	}

	const nama = getValue("namaCicitSusulan");

	if (!nama) {
		alert("Nama Cicit harus diisi.");

		return;
	}

	const form = document.getElementById("formSusulan");

	if (!form) {
		return;
	}

	addHidden(form, "jenis_susulan", "cicit");
	addHidden(form, "id_cucu", idCucu);
	addHidden(form, "nama_cicit", nama);
	form.submit();
}

/* =========================================================
   TAMBAH BAOK SUSULAN
   ========================================================= */

function tambahBaokSusulan() {
	const idCicit = getValue("pilihCicitSusulan");

	if (!idCicit) {
		alert("Silakan pilih Cicit terlebih dahulu.");

		return;
	}

	const nama = getValue("namaBaokSusulan");

	if (!nama) {
		alert("Nama Baok harus diisi.");

		return;
	}

	const form = document.getElementById("formSusulan");

	if (!form) {
		return;
	}

	addHidden(form, "jenis_susulan", "baok");

	addHidden(form, "id_cicit", idCicit);

	addHidden(form, "nama_baok", nama);

	form.submit();
}

/* =========================================================
   HAPUS ELEMENT DATA BARU
   ========================================================= */

function hapusElement(button) {
	if (!button) {
		return;
	}

	const element = button.closest(".baok, .cicit, .cucu, .anak");

	if (!element) {
		return;
	}

	element.remove();

	refreshNomorSemua();
}

/* =========================================================
   REFRESH SEMUA NOMOR
   ========================================================= */

function refreshNomorSemua() {
	refreshNomorAnak();

	document.querySelectorAll(".anak").forEach(function (anak) {
		refreshNomorCucu(anak);
	});

	document.querySelectorAll(".cucu").forEach(function (cucu) {
		refreshNomorCicit(cucu);
	});

	document.querySelectorAll(".cicit").forEach(function (cicit) {
		refreshNomorBaok(cicit);
	});
}

/* =========================================================
   NOMOR ANAK
   ========================================================= */

function refreshNomorAnak() {
	const list = document.querySelectorAll("#anakContainer > .anak");

	list.forEach(function (anak, index) {
		const nomor = index + 1;

		const judul = anak.querySelector(".judul");

		if (judul) {
			judul.innerHTML = `👨‍👩‍👧 DATA ANAK KE-${nomor}`;
		}

		const labels = anak.querySelectorAll(".form-group label");

		if (labels[0]) {
			labels[0].textContent = `Nama Anak Ke-${nomor}`;
		}

		if (labels[1]) {
			labels[1].textContent = `Pasangan Anak Ke-${nomor}`;
		}
	});

	const empty = document.getElementById("emptyAnak");

	if (empty) {
		empty.style.display = list.length === 0 ? "block" : "none";
	}
}

/* =========================================================
   NOMOR CUCU
   ========================================================= */

function refreshNomorCucu(anak) {
	if (!anak) {
		return;
	}

	const nomorAnak = getNomorElement(anak);

	const list = anak.querySelectorAll(":scope > .cucu-container > .cucu");

	list.forEach(function (cucu, index) {
		const nomor = index + 1;

		const judul = cucu.querySelector(".judul");

		if (judul) {
			judul.innerHTML = `
                👶 DATA CUCU KE-${nomor}

                <small>
                    (Anak Ke-${nomorAnak})
                </small>
                `;
		}

		const labels = cucu.querySelectorAll(".form-group label");

		if (labels[0]) {
			labels[0].textContent = `Nama Cucu Ke-${nomor}`;
		}

		if (labels[1]) {
			labels[1].textContent = `Pasangan Cucu Ke-${nomor}`;
		}
	});
}

/* =========================================================
   NOMOR CICIT
   ========================================================= */

function refreshNomorCicit(cucu) {
	if (!cucu) {
		return;
	}

	const anak = cucu.closest(".anak");

	if (!anak) {
		return;
	}

	const nomorAnak = getNomorElement(anak);

	const nomorCucu = getNomorElement(cucu);

	const list = cucu.querySelectorAll(":scope > .cicit-container > .cicit");

	list.forEach(function (cicit, index) {
		const nomor = index + 1;

		const judul = cicit.querySelector(".judul");

		if (judul) {
			judul.innerHTML = `
                👦 DATA CICIT KE-${nomor}

                <small>
                    (Anak Ke-${nomorAnak},
                     Cucu Ke-${nomorCucu})
                </small>
                `;
		}

		const label = cicit.querySelector(".form-group label");

		if (label) {
			label.textContent = `Nama Cicit Ke-${nomor}`;
		}
	});
}

/* =========================================================
   NOMOR BAOK
   ========================================================= */

function refreshNomorBaok(cicit) {
	if (!cicit) {
		return;
	}

	const cucu = cicit.closest(".cucu");

	const anak = cicit.closest(".anak");

	if (!cucu || !anak) {
		return;
	}

	const nomorAnak = getNomorElement(anak);

	const nomorCucu = getNomorElement(cucu);

	const nomorCicit = getNomorElement(cicit);

	const list = cicit.querySelectorAll(":scope > .baok-container > .baok");

	list.forEach(function (baok, index) {
		const nomor = index + 1;

		const judul = baok.querySelector(".judul");

		if (judul) {
			judul.innerHTML = `
                👶 DATA BAOK KE-${nomor}

                <small>
                    (Anak Ke-${nomorAnak},
                     Cucu Ke-${nomorCucu},
                     Cicit Ke-${nomorCicit})
                </small>
                `;
		}

		const label = baok.querySelector(".form-group label");

		if (label) {
			label.textContent = `Nama Baok Ke-${nomor}`;
		}
	});
}

/* =========================================================
   CARI NOMOR ELEMENT
   ========================================================= */

function getNomorElement(element) {
	if (!element) {
		return 1;
	}

	const parent = element.parentElement;

	if (!parent) {
		return 1;
	}

	return Array.from(parent.children).indexOf(element) + 1;
}

/* =========================================================
   CARI NOMOR ANAK BERIKUTNYA
   ========================================================= */

function getNextAnakNumber() {
	const list = document.querySelectorAll("#anakContainer > .anak");

	return list.length + 1;
}

/* =========================================================
   AMBIL VALUE ELEMENT
   ========================================================= */

function getValue(id) {
	const element = document.getElementById(id);

	if (!element) {
		return "";
	}

	return element.value.trim();
}

/* =========================================================
   RESET SELECT
   ========================================================= */

function resetSelect(id, text) {
	const select = document.getElementById(id);

	if (!select) {
		return;
	}

	select.innerHTML = `<option value="">${text}</option>`;
}

/* =========================================================
   HIDDEN INPUT
   ========================================================= */

function addHidden(form, name, value) {
	let input = form.querySelector(`input[name="${name}"]`);

	if (!input) {
		input = document.createElement("input");

		input.type = "hidden";

		input.name = name;

		form.appendChild(input);
	}

	input.value = value;
}

/* =========================================================
   VALIDASI FORM DATA BARU
   ========================================================= */

function initFormSubmit() {
	const form = document.getElementById("familyForm");

	if (!form) {
		return;
	}

	form.addEventListener("submit", function (event) {
		const anak = document.querySelectorAll("#anakContainer > .anak");

		if (anak.length === 0) {
			event.preventDefault();

			alert("Silakan tambahkan minimal 1 Anak.");
		}
	});
}
