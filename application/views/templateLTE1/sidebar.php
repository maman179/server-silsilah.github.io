 <!-- Main Sidebar Container -->
 <aside class="main-sidebar sidebar-dark-primary elevation-4">
 	<!-- Brand Logo -->
 	<a href="#" class="brand-link">
 		<img src="<?php echo base_url('assets/dist/img/AdminLTELogo.png') ?>" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
 		<span class="brand-text font-weight-light">Silsilah Keluarga</span>
 	</a>

 	<!-- Sidebar -->
 	<div class="sidebar">
 		<?php
			foreach ($tampil_ortu as $u) {
			?>
 			<!-- Sidebar user panel (optional) -->
 			<div class="user-panel mt-3 pb-3 mb-3 d-flex">
 				<div class="image">
 					<img src="<?php echo base_url() . '/assets/foto_ortu/' . $u->foto_ortu; ?>" class="img-circle elevation-2" alt="User Image">
 				</div>
 				<div class="info">
 					<a href="#" class="d-block"><?php echo $u->nama_ortu; ?></a>
 				</div>
 			<?php } ?>
 			</div>


 			<!-- SidebarSearch Form -->
 			<div class="form-inline mb-2">
 				<div class="input-group w-100">

 					<input
 						id="sidebarSearch"
 						class="form-control form-control-sidebar"
 						type="search"
 						placeholder="Cari menu..."
 						autocomplete="off">

 					<div class="input-group-append">
 						<button
 							class="btn btn-sidebar"
 							type="button"
 							id="btnSidebarSearch">
 							<i class="fas fa-search fa-fw"></i>
 						</button>
 					</div>
 				</div>
 			</div>

 			<!-- Sidebar Menu -->
 			<nav class="mt-2">
 				<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
 					<!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
 					<li class="nav-item menu-open">
 						<a href="<?= site_url('dashboard1/tampil_data') ?>" class="nav-link active">
 							<i class="nav-icon fas fa-tachometer-alt"></i>
 							<p>
 								Dashboard
 								<i class="right fas fa-angle-left"></i>
 							</p>
 						</a>

 						<ul class="nav nav-treeview">

 							<li class="nav-item">
 								<a href="<?= base_url('dashboard1/nested_form') ?>" class="nav-link active">
 									<i class="fas fa-user-plus nav-icon"></i>
 									<p>Quick Input</p>
 								</a>
 							</li>

 							<li class="nav-item">
 								<a href="<?= site_url('dashboard1/tampil_data_orangtua') ?>" class="nav-link active">
 									<i class="fas fa-user-plus nav-icon"></i>
 									<p>Input Data Anak</p>
 								</a>
 							</li>

 							<li class="nav-item">
 								<a href="<?= site_url('dashboard1/tampil_data_anak') ?>" class="nav-link">
 									<i class="fas fa-user-plus nav-icon"></i>
 									<p>Input Data cucu</p>
 								</a>
 							</li>
 							<li class="nav-item">
 								<a href="<?= site_url('dashboard1/tampil_data_cucu') ?>" class="nav-link">
 									<i class="fas fa-user-plus nav-icon"></i>
 									<p>Input Data Cicit</p>
 								</a>
 							</li>
 							<li class="nav-item">
 								<a href="<?= site_url('dashboard1/tampil_data_cicit') ?>" class="nav-link">
 									<i class="fas fa-user-plus nav-icon"></i>
 									<p>Input Data Baok</p>
 								</a>
 							</li>

 							<li class="nav-item">
 								<a href="<?= site_url('dashboard1/galery') ?>" class="nav-link">
 									<i class="fas fa-upload nav-icon"></i>
 									<p>Upload Foto</p>
 								</a>
 							</li>


 						</ul>
 					</li>
 					<li class="nav-item">
 						<a href="#" class="nav-link">
 							<i class="nav-icon fas fa-print"></i>
 							<p>
 								Print Data Keluarga
 								<i class="fas fa-angle-left right"></i>
 							</p>
 						</a>
 						<ul class="nav nav-treeview">
 							<li class="nav-item">
 								<a href="<?php echo base_url('dashboard1/print_anak'); ?>" class="nav-link">
 									<i class="fas fa-users nav-icon"></i>
 									<p>Print Data Anak</p>
 								</a>
 							</li>

 							<li class="nav-item">
 								<a href="<?php echo base_url('dashboard1/print_cucu'); ?>" class="nav-link">
 									<i class="fas fa-users nav-icon"></i>
 									<p>Print Data Cucu</p>
 								</a>
 							</li>

 							<li class="nav-item">
 								<a href="<?php echo base_url('dashboard1/print_cicit'); ?>" class="nav-link">
 									<i class="fas fa-users nav-icon"></i>
 									<p>Print Data Cicit</p>
 								</a>
 							</li>

 							<li class="nav-item">
 								<a href="<?php echo base_url('dashboard1/print_baok'); ?>" class="nav-link">
 									<i class="fas fa-users nav-icon"></i>
 									<p>Print Data Baok</p>
 								</a>
 							</li>
 						</ul>

 					<li class="nav-item">
 						<a href="<?= site_url('dashboard1/tampil_galery') ?>" class="nav-link">
 							<i class="nav-icon far fa-image"></i>
 							<p>
 								Gallery
 							</p>
 						</a>
 					</li>

 					<li class="nav-item">
 						<a href="<?= site_url('dashboard1/profile'); ?>" class="nav-link">
 							<i class="fas fa-portrait nav-icon"></i>
 							<p>Profile</p>
 						</a>
 					</li>

 					<li class="nav-item">
 						<a href="<?= site_url('dashboard1/tampil_WA'); ?>" class="nav-link">
 							<i class="fas fa-portrait nav-icon"></i>
 							<p>Whatsapp Center</p>
 						</a>
 					</li>

 					<li class="nav-item">
 						<a href="<?= site_url('dashboard1/kontak'); ?>" class="nav-link">
 							<i class="fas fa-portrait nav-icon"></i>
 							<p>Kontak Whatsapp</p>
 						</a>
 					</li>

 					<li class="nav-item">
 						<a href="<?= site_url('dashboard1/treeview'); ?>" class="nav-link">
 							<i class="fas fa-portrait nav-icon"></i>
 							<p>Diagram</p>
 						</a>
 					</li>

 					<li class="nav-item">
 						<a href="<?= site_url('dashboard1/form_input_backup'); ?>" class="nav-link">
 							<i class="fas fa-portrait nav-icon"></i>
 							<p>Backup Database</p>
 						</a>
 					</li>

 					<li class="nav-item">
 						<a href="<?php echo base_url() . 'login/logout'; ?>" class="tombol-logout">
 							<p><i class="fas fa-file-import nav-icon"></i>
 								Logout </p>
 						</a>
 					</li>
 				</ul>
 				</li>
 			</nav>
 			<!-- /.sidebar-menu -->
 	</div>
 	<!-- /.sidebar -->
 </aside>


 <script>
 	$(document).ready(function() {

 		const searchInput = $('#sidebarSearch');

 		// Simpan kondisi awal sidebar
 		const originalState = [];

 		$('.nav-sidebar .nav-item').each(function() {

 			originalState.push({
 				element: this,
 				display: $(this).css('display'),
 				hasMenuOpen: $(this).hasClass('menu-open'),
 				treeviewVisible: $(this)
 					.children('.nav-treeview')
 					.is(':visible')
 			});

 		});


 		// Fungsi mengembalikan sidebar seperti semula
 		function resetSidebar() {

 			$('.nav-sidebar .nav-item').each(function() {

 				$(this).show();

 			});

 			// Tutup kembali submenu yang memang tertutup dari awal
 			$('.nav-sidebar .nav-treeview').each(function() {

 				const parent = $(this).parent('.nav-item');

 				const state = originalState.find(function(item) {

 					return item.element === parent[0];

 				});

 				if (state) {

 					if (state.treeviewVisible) {

 						$(this).show();

 					} else {

 						$(this).hide();

 					}

 				}

 			});

 			// Kembalikan class menu-open
 			$('.nav-sidebar .nav-item').each(function() {

 				const state = originalState.find(function(item) {

 					return item.element === this;

 				}, this);

 				if (state) {

 					if (state.hasMenuOpen) {

 						$(this).addClass('menu-open');

 					} else {

 						$(this).removeClass('menu-open');

 					}

 				}

 			});

 		}


 		// SEARCH
 		searchInput.on('keyup', function() {

 			const keyword = $(this)
 				.val()
 				.toLowerCase()
 				.trim();


 			// Jika search kosong
 			if (keyword === '') {

 				resetSidebar();

 				return;

 			}


 			// Sembunyikan semua item
 			$('.nav-sidebar .nav-item').hide();


 			// Cari menu
 			$('.nav-sidebar .nav-link').each(function() {

 				const text = $(this)
 					.text()
 					.toLowerCase()
 					.trim();

 				if (text.includes(keyword)) {

 					const menuItem = $(this).closest('.nav-item');


 					// Tampilkan item yang cocok
 					menuItem.show();


 					// Tampilkan parent
 					menuItem
 						.parents('.nav-item')
 						.show();


 					// Buka submenu parent
 					menuItem
 						.parents('.nav-treeview')
 						.show();


 					menuItem
 						.parents('.nav-item')
 						.addClass('menu-open');

 				}

 			});

 		});


 		// Tombol search
 		$('#btnSidebarSearch').on('click', function() {

 			searchInput.focus();

 		});


 		// ESC = reset
 		searchInput.on('keydown', function(e) {

 			if (e.key === 'Escape') {

 				$(this).val('');

 				resetSidebar();

 			}

 		});


 	});
 </script>