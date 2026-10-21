<div class="flash-data" data-flashdata="<?= $this->session->flashdata('flash'); ?>"></div>
<?php if ($this->session->flashdata('flash')) : ?>
<?php endif; ?>

<!-- GALERI KELUARGA -->
<div class="gallery-page">
	<div class="container pt-2">
		<nav aria-label="breadcrumb">
			<ol class="breadcrumb">
				<li class="breadcrumb-item"><a href="<?php echo base_url('dashboard1/tampil_galery'); ?>">Home</a></li>
				<li class="breadcrumb-item active" aria-current="page"><a href="<?php echo base_url('dashboard1/galery'); ?>">Upload Gallery</a></li>
				<li class="breadcrumb-item active" aria-current="page">Foto Kegiatan</a></li>
			</ol>


			<!-- TOOLBAR -->
			<div class="gallery-toolbar">
				<!-- SEARCH -->
				<div class=" gallery-search">
					<input type="text"
						id="gallerySearch"
						placeholder="Cari foto...">
				</div>

				<!-- FILTER -->
				<div class="gallery-filter">
					<button type="button"
						class="gallery-filter-btn active"
						data-filter="all">
						Semua
					</button>

					<button type="button"
						class="gallery-filter-btn"
						data-filter="keluarga">
						Keluarga
					</button>

					<button type="button"
						class="gallery-filter-btn"
						data-filter="kegiatan">
						Kegiatan
					</button>
				</div>
			</div>

			<!-- GALLERY GRID -->
			<div class="gallery-grid" id="galleryGrid">
				<?php if (!empty($galery)) : ?>
					<?php foreach ($galery as $u) : ?>
						<input type="hidden" name="id_galery" value="<?= $u->id_galery; ?>">
						<?php
						$caption = !empty($u->caption)
							? $u->caption
							: 'Foto Kegiatan';

						$kategori = !empty($u->kategori)
							? strtolower($u->kategori)
							: 'kegiatan';

						?>

						<div class="gallery-item"
							data-category="<?= htmlspecialchars($kategori); ?>"
							data-title="<?= strtolower(htmlspecialchars($caption)); ?>">

							<div class="gallery-card">
								<!-- IMAGE -->
								<a href="<?= base_url('assets/galery/' . $u->galery); ?>"
									class="fresco"
									data-fresco-group="family-gallery"
									data-fresco-caption="<?= htmlspecialchars($caption); ?>">

									<div class="gallery-image">
										<img src="<?= base_url('assets/galery/' . $u->galery); ?>"
											alt="<?= htmlspecialchars($caption); ?>"
											loading="lazy">

										<div class="gallery-overlay">
											<div class="gallery-zoom">
												<i class="fas fa-search-plus"></i>
											</div>
										</div>
									</div>
								</a>

								<!-- CAPTION -->
								<div class="gallery-info">
									<div class="gallery-caption text-center"
										title="<?= htmlspecialchars($caption); ?>">
										<?= htmlspecialchars($caption); ?>
									</div>
									<hr>

									<a href="<?= site_url('dashboard1/hapus_galery/' . $u->id_galery) ?>"
										class="btn btn-outline-danger btn-sm tombol-hapus" title="Hapus Foto">
										<i class="fa fa-trash"></i></a>

									<a href="<?= site_url('dashboard1/download_galery/' . $u->id_galery) ?>"
										class="btn btn-outline-primary btn-sm download-galery" title="Download Foto">
										<i class="fas fa-download"></i></a>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>

					<!-- EMPTY -->
					<div class="gallery-empty">
						<i class="fas fa-images"></i>
						<h5>Belum ada foto</h5>
						<p> Dokumentasi foto belum tersedia. </p>
					</div>
				<?php endif; ?>
			</div>

			<!-- NO RESULT -->
			<div id="galleryNoResult"
				class="gallery-empty d-none">
				<i class="fas fa-search"></i>
				<h5>Foto tidak ditemukan</h5>
				<p> Coba gunakan kata pencarian lain. </p>
			</div>
	</div>
