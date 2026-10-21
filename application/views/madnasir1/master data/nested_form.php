<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>

<div class="flash-data" data-flashdata="<?= $this->session->flashdata('flash'); ?>"></div>
<?php if ($this->session->flashdata('flash')) : ?>
<?php endif; ?>

<link rel="stylesheet" href="<?= base_url('assets/dist/css/nested.css') ?>">
<div class="nested-page">
	<div class="nested-header">
		<div class="nested-header-icon">
			<i class="fas fa-sitemap"></i>
		</div>

		<div>
			<h2>Tambah Data Keluarga</h2>
			<p> Kelola data Anak, Cucu, Cicit dan Baok </p>
		</div>
	</div>

	<div class="mode-card">
		<div class="mode-title">
			<i class="fas fa-layer-group"></i>
			Pilih Jenis Penambahan Data
		</div>

		<div class="mode-options">
			<label class="mode-option">
				<input type="radio" name="mode" value="baru" checked
					onchange="ubahMode('baru')">
				<div class="mode-content">
					<div class="mode-icon mode-icon-green">
						<i class="fas fa-users"></i>
					</div>
					<div>
						<strong> Tambah Keluarga Baru </strong>
						<span> Membuat data Anak sampai Baok sekaligus. </span>
					</div>
				</div>
			</label>


			<!-- MODE SUSULAN -->
			<label class="mode-option">
				<input type="radio" name="mode" value="susulan"
					onchange="ubahMode('susulan')">

				<div class="mode-content">
					<div class="mode-icon mode-icon-blue">
						<i class="fas fa-user-plus"></i>
					</div>

					<div>
						<strong> Tambah Data Susulan </strong>
						<span> Menambahkan keturunan pada data yang sudah ada. </span>
					</div>
				</div>
			</label>
		</div>
	</div>

	<!-- MODE DATA BARU -->
	<div id="formDataBaru" class="mode-panel">
		<div class="panel-header">
			<div>
				<h3> <i class="fas fa-users"></i> Keluarga Baru </h3>
				<p> Tambahkan Anak dan keturunannya. </p>
			</div>
		</div>


		<!-- FORM UTAMA -->
		<form id="familyForm" action="<?= base_url('dashboard1/nested_form/simpan') ?>" method="post">
			<!-- CONTAINER ANAK -->
			<div id="anakContainer">
				<div id="emptyAnak" class="empty-state">

					<div class="empty-icon">
						<i class="fas fa-user-plus"></i>
					</div>

					<h4> Belum ada data Anak </h4>
					<p> Klik tombol di bawah untuk menambahkan Anak. </p>
				</div>
			</div>

			<!-- TAMBAH ANAK -->
			<div class="main-action">
				<button type="button" class="btn btn-green"
					onclick="tambahAnak()">
					<i class="fas fa-plus"></i> Tambah Anak
				</button>

			</div>

			<!-- SIMPAN -->
			<div class="save-area">
				<button type="submit" class="btn btn-save">
					<i class="fas fa-save"></i> SIMPAN SEMUA DATA
				</button>
			</div>
		</form>
	</div>

	<!-- MODE DATA SUSULAN -->
	<div id="formDataSusulan" class="mode-panel" style="display:none;">
		<div class="panel-header">
			<div>
				<h3><i class="fas fa-user-plus"></i>
					Data Susulan
				</h3>
				<p> Tambahkan keturunan pada data keluarga yang sudah tersimpan.
				</p>
			</div>
		</div>

		<!-- FORM SUSULAN -->
		<form id="formSusulan" action="<?= base_url('dashboard1/nested_form_susulan') ?>" method="post">

			<div class="susulan-card">
				<div class="susulan-title">
					<span class="number-circle"> 1 </span>
					Pilih Anak
				</div>

				<div class="form-group">
					<label for="pilihAnakSusulan">
						Anak
						<span class="required">
							*
						</span>
					</label>

					<select id="pilihAnakSusulan" name="id_anak"
						onchange="pilihAnakSusulan(this.value)">

						<option value=""> -- Pilih Anak -- </option>

						<?php if (!empty($anak)) : ?>
							<?php foreach ($anak as $row) : ?>
								<option
									value="<?= $row->id_anak ?>">
									<?= html_escape($row->nama) ?>
								</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</div>
			</div>

			<!-- PILIH CUCU -->
			<div class="susulan-card">
				<div class="susulan-title">
					<span class="number-circle"> 2 </span>
					Pilih Cucu
				</div>

				<div class="form-group">
					<label for="pilihCucuSusulan"> Cucu </label>
					<select id="pilihCucuSusulan" name="id_cucu"
						onchange="pilihCucuSusulan(this.value)">

						<option value=""> -- Pilih Cucu -- </option>
					</select>
				</div>
			</div>

			<!-- PILIH CICIT -->
			<div class="susulan-card">
				<div class="susulan-title">
					<span class="number-circle"> 3 </span>
					Pilih Cicit
				</div>

				<div class="form-group">
					<label for="pilihCicitSusulan"> Cicit </label>
					<select id="pilihCicitSusulan" name="id_cicit">
						<option value=""> -- Pilih Cicit -- </option>
					</select>
				</div>
			</div>

			<!-- TAMBAH CUCU -->
			<div class="add-data-card">
				<div class="add-data-header">
					<div class="add-data-icon">
						<i class="fas fa-baby"></i>
					</div>
					<div>

						<h4> Tambah Cucu </h4>

						<p> Tambahkan Cucu baru di bawah Anak yang dipilih. </p>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group">
						<label for="namaCucuSusulan">
							Nama Cucu
							<span class="required">
							</span>
						</label>
						<input type="text" id="namaCucuSusulan" placeholder="Masukkan nama cucu">
					</div>

					<div class="form-group">
						<label for="menantuCucuSusulan">
							Pasangan Cucu
						</label>

						<input type="text" id="menantuCucuSusulan" placeholder="Masukkan nama pasangan">
					</div>
				</div>

				<button type="button" class="btn btn-blue"
					onclick="tambahCucuSusulan()">
					<i class="fas fa-plus"></i>
					Tambah Cucu
				</button>
			</div>

			<!-- TAMBAH CICIT -->
			<div class="add-data-card">
				<div class="add-data-header">
					<div class="add-data-icon">
						<i class="fas fa-child"></i>
					</div>

					<div>
						<h4> Tambah Cicit </h4>
						<p> Pilih Cucu terlebih dahulu. </p>
					</div>
				</div>

				<div class="form-group">
					<label for="namaCicitSusulan">
						Nama Cicit
						<span class="required"> * </span>
					</label>

					<input type="text" id="namaCicitSusulan" placeholder="Masukkan nama cicit">
				</div>

				<button type="button" class="btn btn-blue"
					onclick="tambahCicitSusulan()">
					<i class="fas fa-plus"></i>
					Tambah Cicit
				</button>
			</div>

			<!-- TAMBAH BAOK -->
			<div class="add-data-card">
				<div class="add-data-header">
					<div class="add-data-icon">
						<i class="fas fa-baby-carriage"></i>
					</div>

					<div>
						<h4> Tambah Baok </h4>
						<p> Pilih Cicit terlebih dahulu. </p>
					</div>
				</div>

				<div class="form-group">
					<label for="namaBaokSusulan">
						Nama Baok
						<span class="required"> * </span>
					</label>

					<input type="text" id="namaBaokSusulan" placeholder="Masukkan nama baok">
				</div>

				<button type="button" class="btn btn-blue"
					onclick="tambahBaokSusulan()">
					<i class="fas fa-plus"></i>
					Tambah Baok
				</button>
			</div>
		</form>
	</div>
</div>

<!-- JAVASCRIPT -->
<script>
	const BASE_URL = "<?= base_url() ?>";
</script>

<script src="<?= base_url('assets/dist/js/nested.js') ?>"></script>