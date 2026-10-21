<link rel="stylesheet" href="<?php echo base_url('assets/dist/css/qr.css') ?>">

<body>
	<div class="flash-data" data-flashdata="<?= $this->session->flashdata('flash'); ?>"></div>
	<?php if ($this->session->flashdata('flash')) : ?>
	<?php endif; ?>
	</div>


	<nav aria-label="breadcrumb"></nav>
	<div class="col pt-2">
		<nav aria-label="breadcrumb">
			<ol class="breadcrumb">
				<li class="breadcrumb-item"><a href="<?php echo base_url('dashboard1/tampil_data_anak'); ?>">Master Data</a></li>
				<li class="breadcrumb-item">Profile</li>
				<li class="breadcrumb-item active" aria-current="page">Profile Anak</a></li>
			</ol>

			<?php
			$no = 1;
			foreach ($profile as $u) { ?>
				<div class="row gutters-sm">

					<div class="col-md-4 mb-3">

						<!-- CARD PROFILE -->
						<div class="card">
							<div class="card-body">
								<div class="d-flex flex-column align-items-center text-center">
									<input type="hidden"
										name="id_anak"
										value="<?= $u->id_anak; ?>">

									<img width="150px"
										class="img-thumbnail"
										src="<?= base_url('assets/foto_anak/' . $u->foto_anak); ?>"
										onclick="myFunction(this);">

									<div class="mt-4">
										<h4>
											<b><?= $u->nama; ?></b>
										</h4>

										<h6>
											<?= $u->alamat; ?>
										</h6>

										<div class="dropdown">

											<button type="button"
												class="btn btn-outline-success dropdown-toggle"
												data-toggle="dropdown">
												Pilih Menu
											</button>

											<div class="dropdown-menu">

												<a class="dropdown-item"
													href="<?= site_url('dashboard1/edit_anak/' . $u->id_anak); ?>">
													Update Profile
												</a>

												<a class="dropdown-item"
													href="<?= site_url('dashboard1/print_detil_anak/' . $u->id_anak); ?>">
													Print Cover
												</a>

												<a class="dropdown-item"
													href="<?= site_url('dashboard1/print_detil_cucu/' . $u->id_anak); ?>">
													Print Anak
												</a>

												<a class="dropdown-item"
													href="<?= site_url('dashboard1/print_detil_cicit/' . $u->id_anak); ?>">
													Print Cucu
												</a>

												<a class="dropdown-item"
													href="<?= site_url('dashboard1/print_detil_baok/' . $u->id_anak); ?>">
													Print Cicit
												</a>

											</div>

										</div>

									</div>

								</div>

							</div>

						</div>
						<!-- AKHIR CARD PROFILE -->


						<!-- ========================= -->
						<!-- MAP DI LUAR CARD -->
						<!-- ========================= -->

						<?php if (!empty($u->latitude) && !empty($u->longitude)) : ?>

							<!-- MAP -->
							<div class="mt-3">

								<div id="mapAnak<?= $u->id_anak; ?>"
									style="width:100%; height:280px;
										border:1px solid #ddd;
										border-radius:8px;">
								</div>

								<!-- <div class="text-muted text-center mt-1">
									<small>
										<i class="fas fa-map-marker-alt"></i>
										<?= $u->latitude; ?>,
										<?= $u->longitude; ?>
									</small>
								</div> -->

								<!-- TOMBOL DI BAWAH MAP -->
								<div class="text-center mt-2">

									<button type="button"
										class="btn btn-sm btn-primary btnBagikanLokasi"
										data-lat="<?= $u->latitude; ?>"
										data-lng="<?= $u->longitude; ?>"
										data-nama="<?= htmlspecialchars($u->nama, ENT_QUOTES, 'UTF-8'); ?>">

										<i class="fas fa-share-alt"></i>
										Bagikan
									</button>
								</div>
							</div>
						<?php else : ?>

							<div class="text-muted text-center mt-3">
								<small>
									<i class="fas fa-map-marker-alt"></i>
									Lokasi belum tersedia
								</small>
							</div>

						<?php endif; ?>
					</div>

					<div class="col-md-8">
						<!-- general form elements -->
						<div class="card card-primary">
							<div class="card-header">
								<h3 class="card-title">VIEW PROFILE</h3>
							</div>
							<div class="card-body">
								<div class="row">
									<div class="col-sm-5">
										<h6 class="mb-0"><b>NAMA LENGKAP</b></h6>
									</div>
									<div class="col-sm-7 text-secondary">
										<?= $u->nama; ?>
									</div>
								</div>
								<hr>

								<div class="row">
									<div class="col-sm-5">
										<h6 class="mb-0"><b>NAMA ORANGTUA</b></h6>
									</div>
									<div class="col-sm-7 text-secondary">
										<?= $u->nama_ortu ?> / <?= $u->pasangan ?>
									</div>
								</div>
								<hr>

								<div class="row">
									<div class="col-sm-5">
										<h6 class="mb-0"><b>GENDER</b></h6>
									</div>
									<div class="col-sm-7 text-secondary">
										<?= $u->jenis_kelamin; ?>
									</div>
								</div>
								<hr>

								<div class="row">
									<div class="col-sm-5">
										<h6 class="mb-0"><b>TANGGAL LAHIR</b></h6>
									</div>
									<div class="col-sm-7 text-secondary">
										<?= $u->tgl_lahir; ?>
									</div>
								</div>
								<hr>

								<div class="row">
									<div class="col-sm-5">
										<h6 class="mb-0"><b>NAMA SUAMI/ISTRI</b></h6>
									</div>
									<div class="col-sm-7 text-secondary">
										<?= $u->menantu; ?>
									</div>
								</div>
								<hr>

								<div class="row">
									<div class="col-sm-5">
										<b>
											<h6 class="mb-0">SCAN BARCODE
										</b></h6>
									</div>
									<div class="col-sm-7 text-secondary">
										<!-- <a href="<?php echo base_url('dashboard1/qr_anak/' . $u->id_anak); ?>"
											target="_blank" class="btn btn-sm btn-success">
											<i class="fas fa fa-qrcode"></i>
											Barcode
										</a> -->

										<button type="button" class="btn-qr btn-primary"
											onclick="showQrAnak(<?= (int) $u->id_anak ?>)">
											<i class="fas fa fa-qrcode"></i> Tampilkan
										</button>
									</div>
								</div>

								<hr>
								<div class="row">
									<div class="col-sm-5">
										<b>
											<h6 class="mb-0">GOOGLE MAPS
										</b></h6>
									</div>
									<div class="col-sm-7 text-secondary">
										<?php if (!empty($u->latitude) && !empty($u->longitude)) : ?>
											<div class="d-flex flex-wrap" style="gap: 5px;">

												<!-- LIHAT MAPS -->
												<a href="https://www.google.com/maps?q=<?= $u->latitude; ?>,<?= $u->longitude; ?>"
													target="_blank"
													class="btn btn-sm btn-success">
													<i class="fas fa-map-marker-alt"></i>
													Lihat Maps
												</a>
											</div>

										<?php else : ?>

											<span class="text-danger">
												Koordinat belum tersedia
											</span>

										<?php endif; ?>
									</div>
								</div>
								<hr>

								<div class="row">
									<div class="col-sm-5">
										<b>
											<h6 class="mb-0">NO HANDPHONE
										</b></h6>
									</div>
									<div class="col-sm-7 text-secondary">
										<?= $u->NoHP; ?>
									</div>
								</div>
								<hr>

								<div class="row">
									<div class="col-sm-5">
										<b>
											<h6 class="mb-0">JUMLAH ANAK
										</b></h6>
									</div>
									<div class="col-sm-7 text-secondary">
										<a href="<?= site_url('dashboard1/detil_cucu/' . $u->id_anak) ?>"><?= $u->total_anak; ?> ORANG</a>
									</div>
								</div>
								<hr>

							</div>
						</div>
					</div>
				</div>
				<div class="col-sm-6 mb-2">
					<div class="card-hidden"><span onclick="this.parentElement.style.display='none'" class="closebtn">&times;</span><img id="expandedImg" style="width: 90%">
						<div id="imgtext">
						</div>
					</div>
				</div>
	</div>

<?php
			} ?>


<!-- MODAL QR ANAK -->
<div id="qrModal" class="qr-modal">
	<div class="qr-modal-box">

		<!-- CLOSE -->
		<button
			type="button"
			class="qr-modal-close"
			onclick="closeQrModal()">
			&times;
		</button>

		<!-- JUDUL -->
		<h3 class="qr-modal-title">
			QR CODE
		</h3>

		<!-- ID -->
		<div id="qrIdAnak" class="qr-modal-id"></div>

		<!-- QR -->
		<img
			id="qrImage"
			class="qr-modal-image"
			src=""
			alt="QR Code">

	</div>

</div>
</div>
</div>

<script>
	function showQrAnak(idAnak) {
		// Tampilkan ID

		document.getElementById('qrIdAnak').innerHTML =
			'ID Anak: ' + idAnak;

		// URL QR dari controller CI3
		document.getElementById('qrImage').src =
			'<?= site_url('Dashboard1/qr_anak/'); ?>' + idAnak;

		// Tampilkan modal
		document.getElementById('qrModal').style.display = 'flex';
	}


	function closeQrModal() {
		document.getElementById('qrModal').style.display = 'none';

		// Bersihkan QR
		document.getElementById('qrImage').src = '';
	}

	// Klik background untuk menutup
	document.getElementById('qrModal').addEventListener('click', function(e) {
		if (e.target === this) {
			closeQrModal();
		}
	});
</script>


<script>
	document.addEventListener("DOMContentLoaded", function() {
		const tombolBagikan = document.querySelectorAll(".btnBagikanLokasi");
		tombolBagikan.forEach(function(button) {
			button.addEventListener("click", async function() {

				const latitude = this.dataset.lat;
				const longitude = this.dataset.lng;
				const nama = this.dataset.nama;

				if (!latitude || !longitude) {

					alert("Koordinat lokasi belum tersedia.");
					return;

				}

				// LINK GOOGLE MAPS
				const link = "https://www.google.com/maps?q=" +
					latitude + "," + longitude;

				// SHARE BAWAAN HP / BROWSER
				if (navigator.share) {

					try {
						await navigator.share({
							title: "Lokasi " + nama,
							text: "Lokasi rumah " + nama,
							url: link

						});

					} catch (error) {

						console.log("Share dibatalkan:", error);
					}

					return;
				}

				// BROWSER TIDAK MENDUKUNG SHARE
				// COPY LINK
				try {

					await navigator.clipboard.writeText(
						link
					);

					alert("Link lokasi berhasil disalin.");

				} catch (error) {

					prompt("Salin link lokasi berikut:", link);

				}
			});
		});
	});

	document.addEventListener("DOMContentLoaded", function() {

		<?php foreach ($profile as $u) : ?>

			<?php if (!empty($u->latitude) && !empty($u->longitude)) : ?>

				var lat = <?= (float)$u->latitude; ?>;
				var lng = <?= (float)$u->longitude; ?>;

				var map = L.map(
					'mapAnak<?= $u->id_anak; ?>'
				).setView([lat, lng], 17);

				L.tileLayer(
					'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
						attribution: '&copy; OpenStreetMap contributors'
					}
				).addTo(map);

				L.marker([lat, lng])
					.addTo(map)
					.bindPopup(
						"Lokasi Rumah <br>" +
						"<b><?= htmlspecialchars($u->nama, ENT_QUOTES, 'UTF-8'); ?></b><br>"
					);

			<?php endif; ?>

		<?php endforeach; ?>

	});
</script>