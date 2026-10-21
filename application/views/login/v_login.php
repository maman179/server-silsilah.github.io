<!DOCTYPE html>
<html lang="id">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<title>Login - Portal Silsilah</title>

	<!-- Bootstrap -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

	<!-- Font Awesome -->
	<link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

	<!-- Google Font -->
	<link href="https://fonts.googleapis.com/css?family=Lato:300,400,700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo base_url('assets/dist/css/login.css') ?>">

	<!-- SweetAlert -->
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>

	<div class="login-wrapper">
		<div class="login-card">

			<!-- HEADER -->
			<div class="login-header">
				<div class="tree-icon">
					<i class="fa fa-sitemap"></i>
				</div>

				<h1 class="login-title">
					ADMIN SILSILAH
				</h1>

			</div>


			<!-- FORM -->

			<div class="login-body">

				<!-- Flash Data -->

				<div class="flash-data-registrasi" data-flashdata="<?= $this->session->flashdata('flash'); ?>">
				</div>

				<div class="flash-data-gagalreg"
					data-flashdata="<?= $this->session->flashdata('flash_gagal1'); ?>">
				</div>

				<div class="flash-data-gagallog"
					data-flashdata="<?= $this->session->flashdata('flash_gagal'); ?>">
				</div>

				<form action="<?= base_url('login/aksi_login'); ?>" method="post">

					<!-- EMAIL -->
					<label class="form-label"> Email </label>

					<div class="input-group">

						<span class="input-group-text">
							<i class="fa fa-envelope"></i>
						</span>

						<input
							type="email"
							class="form-control"
							name="username"
							value="<?= set_value('username'); ?>"
							placeholder="Masukkan email"
							autocomplete="username"
							required>

					</div>


					<!-- PASSWORD -->

					<label class="form-label">
						Password
					</label>

					<div class="input-group">

						<span class="input-group-text">
							<i class="fa fa-lock"></i>
						</span>

						<input
							type="password"
							class="form-control password-input"
							id="password"
							name="password"
							placeholder="Masukkan password"
							autocomplete="current-password"
							required>

						<span
							class="show-password"
							onclick="togglePassword()"
							title="Tampilkan password">
							<i
								class="fa fa-eye"
								id="eyeIcon"></i>
						</span>

					</div>


					<!-- LOGIN -->

					<div class="d-grid">

						<button type="submit" class="btn btn-login">
							&nbsp; LOGIN
						</button>
					</div>
				</form>


				<!-- LINKS -->

				<div class="login-links">
					<a href="<?= base_url('login/registrasi'); ?>">
						<i class="fa fa-user-plus"></i>
						Buat Akun
					</a>

					<a href="<?= base_url('login/tampilEmail'); ?>">

						Lupa Password
						<i class="fa fa-angle-right"></i>
					</a>
				</div>
			</div>


			<!-- FOOTER -->

			<div class="login-footer">
				© <?= date('Y'); ?> — Semua hak dilindungi
			</div>
		</div>
	</div>


	<!-- JAVASCRIPT -->

	<script>
		function togglePassword() {
			const password = document.getElementById('password');
			const icon = document.getElementById('eyeIcon');

			if (password.type === 'password') {
				password.type = 'text';
				icon.classList.remove('fa-eye');
				icon.classList.add('fa-eye-slash');

			} else {

				password.type = 'password';
				icon.classList.remove('fa-eye-slash');
				icon.classList.add('fa-eye');
			}
		}
	</script>

	<!-- SweetAlert -->
	<script type="text/javascript" src="<?= base_url('assets/js/script.js'); ?>"></script>

</body>

</html>