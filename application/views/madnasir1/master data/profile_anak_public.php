<!DOCTYPE html>
<html lang="id">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title> Profil <?= html_escape($anak->nama); ?> </title>

	<link rel="stylesheet" href="<?= base_url('assets/dist/css/profile-public.css'); ?>">
</head>

<body>
	<div class="profile-public">
		<div class="profile-card">
			<div class="profile-photo">
				<?php if (!empty($anak->foto_anak)) : ?>
					<img src="<?= base_url('assets/foto_anak/' . $anak->foto_anak); ?>"
						alt="<?= html_escape($anak->nama); ?>">

				<?php else : ?>

					<div class="no-photo">
						Tidak ada foto
					</div>

				<?php endif; ?>

			</div>

			<div class="profile-content">

				<h1>
					<?= html_escape($anak->nama); ?>
				</h1>

				<div class="profile-info">

					<div>
						<span>Jenis Kelamin</span>
						<strong>
							<?= html_escape($anak->jenis_kelamin ?? '-'); ?>
						</strong>
					</div>

					<div>
						<span>Tanggal Lahir</span>
						<strong>
							<?= html_escape($anak->tgl_lahir ?? '-'); ?>
						</strong>
					</div>
				</div>

				<div class="private-info">
					<div class="lock-icon">
						🔒
					</div>

					<h3>
						Informasi Lengkap
					</h3>

					<p>
						Silakan login untuk melihat informasi keluarga lengkap.
					</p>
					<a href="<?= site_url('login'); ?>" class="btn-login">
						Login
					</a>
				</div>
			</div>
		</div>
	</div>
</body>

</html>