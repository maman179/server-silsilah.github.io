<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;

class Dashboard1 extends CI_Controller
{
	private $api = 'https://www.emsifa.com/api-wilayah-indonesia/api/';

	public function __construct()
	{
		parent::__construct();

		// Load library, helper dan model terlebih dahulu
		$this->load->library('session');
		$this->load->helper(array('form', 'url'));
		$this->load->library('form_validation');

		$this->load->model('m_anak1');
		$this->load->model('m_login');

		// Ambil nama method yang sedang dijalankan
		$method = $this->router->fetch_method();

		// Method yang boleh diakses tanpa login
		$public_methods = ['scan_anak',	'qr_anak'];

		// CEK LOGIN UNTUK METHOD PRIVATE
		if (!in_array($method, $public_methods)) {

			if (
				!$this->session->userdata('nama') || $this->session->userdata('status') != "login"
			) {

				$this->session->set_flashdata(
					'flash_gagal',
					'Login'
				);

				redirect(base_url("login"));
				return;
			}
		}
	}

	// CARI KOORDINAT OPENSTREETMAP / NOMINATIM
	private function get_koordinat_osm($desa, $kecamatan, $kabupaten, $provinsi, $alamat = '')
	{
		$queries = array();
		// 1. ALAMAT LENGKAP
		if (!empty($alamat)) {
			$queries[] =
				$alamat . ', ' .
				$desa . ', ' .
				$kecamatan . ', ' .
				$kabupaten . ', ' .
				$provinsi . ', Indonesia';
		}

		// 2. DESA + WILAYAH
		if (!empty($desa)) {
			$queries[] =
				$desa . ', ' .
				$kecamatan . ', ' .
				$kabupaten . ', ' .
				$provinsi . ', Indonesia';
		}

		// 3. KECAMATAN
		if (!empty($kecamatan)) {
			$queries[] =
				$kecamatan . ', ' .
				$kabupaten . ', ' .
				$provinsi . ', Indonesia';
		}

		// 4. KABUPATEN
		if (!empty($kabupaten)) {
			$queries[] =
				$kabupaten . ', ' .
				$provinsi . ', Indonesia';
		}

		// Hapus query yang sama
		$queries = array_unique($queries);

		foreach ($queries as $query) {
			$query = trim($query);
			if ($query == '') {
				continue;
			}

			$url = 'https://nominatim.openstreetmap.org/search?' .
				http_build_query(array(
					'q' => $query,
					'format' => 'json',
					'limit' => 1,
					'countrycodes' => 'id',
					'addressdetails' => 1
				));

			log_message('error', 'NOMINATIM QUERY: ' . $query);

			$ch = curl_init();

			curl_setopt_array($ch, array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CONNECTTIMEOUT => 5,
				CURLOPT_TIMEOUT => 15,
				CURLOPT_FOLLOWLOCATION => true,

				// WAJIB
				CURLOPT_USERAGENT =>
				'SilsilahKeluarga/1.0 (contact: admin@localhost)',

				CURLOPT_HTTPHEADER => array(
					'Accept: application/json'
				)
			));

			$response = curl_exec($ch);

			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			$curl_error = curl_error($ch);

			curl_close($ch);

			log_message('error', 'NOMINATIM HTTP: ' . $http_code);

			// CURL ERROR
			if ($response === false || $curl_error != '') {

				log_message('error', 'NOMINATIM CURL ERROR: ' . $curl_error);

				continue;
			}

			// HTTP ERROR
			if ($http_code != 200) {

				log_message('error', 'NOMINATIM HTTP ERROR: ' . $http_code);

				continue;
			}

			// JSON
			$hasil = json_decode($response,	true);

			if (!is_array($hasil) || empty($hasil)) {

				log_message('error', 'NOMINATIM TIDAK MENEMUKAN: ' . $query);

				continue;
			}

			// BERHASIL
			if (isset($hasil[0]['lat']) && isset($hasil[0]['lon'])) {
				log_message(
					'error',
					'KOORDINAT BERHASIL: ' .
						$hasil[0]['lat'] . ', ' . $hasil[0]['lon']
				);

				return array(
					'latitude' => $hasil[0]['lat'],
					'longitude' => $hasil[0]['lon']
				);
			}
		}

		// Semua gagal
		log_message('error', 'SEMUA QUERY NOMINATIM GAGAL');

		return false;
	}

	function index()
	{
		$username = $this->session->userdata('nama');
		$data['tampil_ortu'] = $this->db->query("select * from ortu WHERE username='" . $username . "'")->result();

		$data['title'] = 'Dashboard';
		$this->load->view('templateLTE1/header', $data);
		$this->load->view('templateLTE1/sidebar');
		$this->load->view('templateLTE1/topbar', $data);
		$this->load->view('templateLTE1/menu');
		$this->load->view('templateLTE1/footer');
	}

	// API WILAYAH INDONESIA
	private function get_api_wilayah($url)
	{
		$ch = curl_init();

		curl_setopt_array($ch, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		));

		$result = curl_exec($ch);

		if ($result === false) {
			$error = curl_error($ch);
			curl_close($ch);

			return json_encode(array('error' => $error));
		}

		curl_close($ch);

		return $result;
	}


	// PROVINSI
	public function api_provinsi()
	{
		$url = 'https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json';

		$result = $this->get_api_wilayah($url);

		$this->output
			->set_content_type('application/json')
			->set_output($result);
	}


	// CARI KOORDINAT OPENSTREETMAP / NOMINATIM
	private function update_koordinat($alamat)
	{
		$query_list = array();

		// QUERY 1 - ALAMAT LENGKAP
		if (!empty($alamat)) {
			$query_list[] = $alamat;
		}

		// BERSIHKAN ALAMAT
		$alamat_bersih = preg_replace('/,\s*,+/', ',', $alamat);

		$alamat_bersih = trim($alamat_bersih, " ,");

		$parts = array_map(
			'trim',
			explode(',', $alamat_bersih)
		);

		if (count($parts) >= 5) {
			$query2 = implode(', ',	array_slice($parts, 1));
			$query_list[] = $query2;
		}

		// QUERY 3 - DESA + WILAYAH
		if (count($parts) >= 4) {

			$query3 = implode(', ',	array_slice($parts, -4));
			$query_list[] = $query3;
		}

		// QUERY 4 - WILAYAH YANG LEBIH UMUM
		if (count($parts) >= 3) {
			$query4 = implode(', ',	array_slice($parts, -3));
			$query_list[] = $query4;
		}

		// HAPUS QUERY DUPLIKAT
		$query_list = array_unique($query_list);

		// PROSES NOMINATIM
		foreach ($query_list as $query) {
			$query = trim($query);

			if ($query == '') {
				continue;
			}

			$url =	'https://nominatim.openstreetmap.org/search?' .
				http_build_query(
					array(
						'q' => $query,
						'format' => 'json',
						'limit' => 1,
						'countrycodes' => 'id',
						'addressdetails' => 1
					)
				);

			$ch = curl_init();

			curl_setopt_array($ch, array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CONNECTTIMEOUT => 5,
				CURLOPT_TIMEOUT => 12,
				CURLOPT_FOLLOWLOCATION => true,

				// User-Agent wajib
				CURLOPT_USERAGENT => 'SilsilahKeluarga/1.0',
				CURLOPT_HTTPHEADER => array('Accept: application/json')
			));

			$response =	curl_exec($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$curl_error = curl_error($ch);
			curl_close($ch);

			// LOG
			log_message('error', 'NOMINATIM QUERY: ' . $query);

			log_message('error', 'NOMINATIM HTTP: ' . $http_code);

			// CURL ERROR
			if ($response === false || $curl_error != '') {

				log_message('error', 'NOMINATIM CURL ERROR: ' .
					$curl_error);
				continue;
			}

			// HTTP ERROR
			if ($http_code != 200) {

				log_message('error', 'NOMINATIM HTTP ERROR: ' .
					$http_code);
				continue;
			}

			// JSON
			$hasil = json_decode($response, true);

			if (!is_array($hasil) || empty($hasil)) {

				log_message('error', 'NOMINATIM TIDAK MENEMUKAN: ' .
					$query);

				continue;
			}

			// KOORDINAT DITEMUKAN

			if (
				isset($hasil[0]['lat']) &&	isset($hasil[0]['lon'])
			) {

				log_message(
					'error',
					'KOORDINAT BERHASIL: ' .
						$hasil[0]['lat'] . ', ' .
						$hasil[0]['lon']
				);

				return array(
					'latitude' => $hasil[0]['lat'],
					'longitude' => $hasil[0]['lon']
				);
			}
		}

		// SEMUA QUERY GAGAL
		log_message('error', 'SEMUA QUERY NOMINATIM GAGAL: ' .
			$alamat);

		return false;
	}

	// KABUPATEN
	public function api_kabupaten($id_provinsi)
	{
		$url = 'https://www.emsifa.com/api-wilayah-indonesia/api/regencies/'
			. $id_provinsi . '.json';

		$result = $this->get_api_wilayah($url);

		$this->output->set_content_type('application/json')->set_output($result);
	}


	// KECAMATAN
	public function api_kecamatan($id_kabupaten)
	{
		$url = 'https://www.emsifa.com/api-wilayah-indonesia/api/districts/'
			. $id_kabupaten . '.json';

		$result = $this->get_api_wilayah($url);
		$this->output->set_content_type('application/json')->set_output($result);
	}


	// DESA
	public function api_desa($id_kecamatan)
	{
		$url = 'https://www.emsifa.com/api-wilayah-indonesia/api/villages/'
			. $id_kecamatan . '.json';

		$result = $this->get_api_wilayah($url);
		$this->output->set_content_type('application/json')->set_output($result);
	}

	// menampilkan data profile Orang Tua (PROFILE INDUK SISLSILAH)
	function profile()
	{
		$username = $this->session->userdata('nama');
		$data['profile'] = $this->db->query("select * from ortu WHERE username='" . $username . "'")->result();
		$data['title'] = 'Profile';
		$this->load->view('templateLTE1/header', $data);
		$this->load->view('madnasir1/master data/v_profile');
		$this->load->view('templateLTE1/footer');
	}

	function view_profile_ortu()
	{
		$username = $this->session->nama;
		$data['profile'] = $this->db->query("select * from ortu WHERE username='" . $username . "'")->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_profile_ortu', $data);
		$this->load->view('templateLTE1/footer');
	}

	// menampilkan informasi anak, cucu, cicit, baok di dashboard
	function tampil_data()
	{
		$username = $this->session->userdata('nama');

		$ortu = $this->db->query("select * from ortu WHERE username='" . $username . "'")->result();

		$data['tampil_ortu'] = $this->db->query("select * from ortu WHERE username='" . $username . "'")->result();

		$data['anak'] = $this->db->query("SELECT anak.*, ortu.nama_ortu, ortu.alamat_ortu, ortu.pasangan, ortu.total_anak_ortu, ortu.username, ortu.foto_ortu 
		from anak 
		INNER JOIN ortu ON ortu.id_ortu=anak.id_ortu 
		WHERE ortu.username='" . $username . "'")->result();

		$data['total_cucu'] = $this->db->query("SELECT cucu.*, cucu.id_anak, cucu.nama_cucu, ortu.id_ortu, ortu.username 
		from cucu 
		INNER JOIN anak ON anak.id_anak=cucu.id_anak
		INNER JOIN ortu ON ortu.id_ortu=anak.id_ortu 
		WHERE ortu.username='" . $username . "'")->num_rows();

		$data['total_cicit'] = $this->db->query("SELECT cicit.*, cucu.id_cucu, cucu.nama_cucu, ortu.username  
		FROM cicit
		INNER JOIN cucu ON cucu.id_cucu=cicit.id_cucu
		INNER JOIN anak ON anak.id_anak=cucu.id_anak
		INNER JOIN ortu ON ortu.id_ortu=anak.id_ortu
		WHERE ortu.username='" . $username . "'")->num_rows();

		$data['total_baok'] = $this->db->query("SELECT baok.*, cicit.id_cicit, cicit.nama_cicit, cucu.id_cucu, ortu.username  
		FROM baok
		INNER JOIN cicit ON cicit.id_cicit=baok.id_cicit
		INNER JOIN cucu ON cucu.id_cucu=cicit.id_cucu
		INNER JOIN anak ON anak.id_anak=cucu.id_anak
		INNER JOIN ortu ON ortu.id_ortu=anak.id_ortu
		WHERE ortu.username='" . $username . "'")->num_rows();


		// JUMLAH ANAK BERDASARKAN USERNAME LOGIN

		$data['jumlah_anak'] = $this->db
			->from('anak')
			->join('ortu', 'ortu.id_ortu = anak.id_ortu')
			->where('ortu.username', $username)
			->count_all_results();


		// JUMLAH CUCU BERDASARKAN USERNAME LOGIN

		$data['jumlah_cucu'] = $this->db
			->from('cucu')
			->join('anak', 'anak.id_anak = cucu.id_anak')
			->join('ortu', 'ortu.id_ortu = anak.id_ortu')
			->where('ortu.username', $username)
			->count_all_results();

		// ======================================================
		// LOKASI KELUARGA SAMPAI DENGAN BAOK
		// BERDASARKAN USERNAME YANG LOGIN
		// ======================================================

		$lokasi_anak = $this->db
			->select('
        anak.id_anak AS id,
        anak.nama AS nama,
        anak.alamat AS alamat,
        anak.latitude,
        anak.longitude,
        "Anak" AS jenis
    ')
			->from('anak')
			->join('ortu', 'ortu.id_ortu = anak.id_ortu')
			->where('ortu.username', $username)
			->where('anak.latitude IS NOT NULL', null, false)
			->where('anak.longitude IS NOT NULL', null, false)
			->where('anak.latitude !=', '')
			->where('anak.longitude !=', '')
			->get()
			->result();


		$lokasi_cucu = $this->db
			->select('
        cucu.id_cucu AS id,
        cucu.nama_cucu AS nama,
        cucu.alamat_cucu AS alamat,
        cucu.latitude,
        cucu.longitude,
        "Cucu" AS jenis
    ')
			->from('cucu')
			->join('anak', 'anak.id_anak = cucu.id_anak')
			->join('ortu', 'ortu.id_ortu = anak.id_ortu')
			->where('ortu.username', $username)
			->where('cucu.latitude IS NOT NULL', null, false)
			->where('cucu.longitude IS NOT NULL', null, false)
			->where('cucu.latitude !=', '')
			->where('cucu.longitude !=', '')
			->get()
			->result();


		$lokasi_cicit = $this->db
			->select('
        cicit.id_cicit AS id,
        cicit.nama_cicit AS nama,
        cicit.alamat_cicit AS alamat,
        cicit.latitude,
        cicit.longitude,
        "Cicit" AS jenis
    ')
			->from('cicit')
			->join('cucu', 'cucu.id_cucu = cicit.id_cucu')
			->join('anak', 'anak.id_anak = cucu.id_anak')
			->join('ortu', 'ortu.id_ortu = anak.id_ortu')
			->where('ortu.username', $username)
			->where('cicit.latitude IS NOT NULL', null, false)
			->where('cicit.longitude IS NOT NULL', null, false)
			->where('cicit.latitude !=', '')
			->where('cicit.longitude !=', '')
			->get()
			->result();


		$lokasi_baok = $this->db
			->select('
        baok.id_baok AS id,
        baok.nama_baok AS nama,
        baok.alamat_baok AS alamat,
        baok.latitude,
        baok.longitude,
        "Baok" AS jenis
    ')
			->from('baok')
			->join('cicit', 'cicit.id_cicit = baok.id_cicit')
			->join('cucu', 'cucu.id_cucu = cicit.id_cucu')
			->join('anak', 'anak.id_anak = cucu.id_anak')
			->join('ortu', 'ortu.id_ortu = anak.id_ortu')
			->where('ortu.username', $username)
			->where('baok.latitude IS NOT NULL', null, false)
			->where('baok.longitude IS NOT NULL', null, false)
			->where('baok.latitude !=', '')
			->where('baok.longitude !=', '')
			->get()
			->result();


		// ======================================================
		// GABUNGKAN SEMUA LOKASI
		// ======================================================

		$data['lokasi_keluarga'] = array_merge(
			$lokasi_anak,
			$lokasi_cucu,
			$lokasi_cicit,
			$lokasi_baok
		);


		// ======================================================
		// JUMLAH SEMUA LOKASI
		// ======================================================

		$data['jumlah_lokasi'] = count($data['lokasi_keluarga']);

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_tampil', $data);
		$this->load->view('templateLTE1/footer');
	}

	function tampil_data_orangtua()
	{
		$username = $this->session->userdata('nama');

		$data['ortu'] = $this->db->query("select * from ortu WHERE username='" . $username . "'")->result();

		$data['anak'] = $this->db->query("SELECT anak.*, ortu.nama_ortu, ortu.alamat_ortu, ortu.pasangan, ortu.total_anak_ortu, ortu.username, ortu.foto_ortu 
		from anak 
		INNER JOIN ortu ON ortu.id_ortu=anak.id_ortu 
		WHERE ortu.username='" . $username . "'")->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_tampil_ortu', $data);
		$this->load->view('templateLTE1/footer');
	}

	function edit_ortu($id_ortu)
	{
		$where = array('id_ortu' => $id_ortu);
		$data['ortu'] = $this->m_anak1->edit_data($where, 'ortu')->result();
		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_edit_ortu', $data);
		$this->load->view('templateLTE1/footer');
	}

	function update_ortu()
	{
		$id_ortu = $this->input->post('id_ortu');

		// AMBIL DATA LAMA
		$ortu_lama = $this->db
			->where('id_ortu', $id_ortu)
			->get('ortu')
			->row();

		if (!$ortu_lama) {
			show_error('Data anak tidak ditemukan');
			return;
		}

		// AMBIL DATA FORM
		$nama = trim($this->input->post('nama'));
		$jenis_kelamin = trim($this->input->post('jenis_kelamin_ortu'));
		$tgl_lahir_input = $this->input->post('tgl_lahir_ortu');
		$pasangan = trim($this->input->post('pasangan'));
		$alamat = trim($this->input->post('alamat'));
		$NoHP = trim($this->input->post('NoHP_ortu'));
		$id_provinsi = $this->input->post('id_provinsi');
		$id_kabupaten =	$this->input->post('id_kabupaten');
		$id_kecamatan =	$this->input->post('id_kecamatan');
		$id_desa = $this->input->post('id_desa');
		$newLatitude  = trim($this->input->post('newLatitude'));
		$newLongitude = trim($this->input->post('newLongitude'));

		// VALIDASI
		if (
			$nama == '' ||
			$jenis_kelamin == '' ||
			$tgl_lahir_input == '' ||
			$alamat == '' ||
			$id_provinsi == '' ||
			$id_kabupaten == '' ||
			$id_kecamatan == '' ||
			$id_desa == ''
		) {

			$this->session->set_flashdata('flash', 'Data wajib belum lengkap');

			redirect('dashboard1/edit_ortu/' . $id_ortu);

			return;
		}

		// FORMAT TANGGAL

		$tgl_lahir = date('d-M-Y', strtotime($tgl_lahir_input));

		// CEK APAKAH ALAMAT / WILAYAH BERUBAH
		$alamat_lama = trim($ortu_lama->alamat_ortu);
		$alamat_berubah = $alamat_lama != $alamat;

		$wilayah_berubah =
			$ortu_lama->id_provinsi != $id_provinsi ||
			$ortu_lama->id_kabupaten != $id_kabupaten ||
			$ortu_lama->id_kecamatan != $id_kecamatan ||
			$ortu_lama->id_desa != $id_desa;

		// DEFAULT KOORDINAT LAMA

		$latitude =	$ortu_lama->latitude;
		$longitude = $ortu_lama->longitude;

		// KOORDINAT DARI MARKER MAP
		$latitude  = $ortu_lama->latitude;
		$longitude = $ortu_lama->longitude;

		// Jika user menggeser marker
		if (
			$newLatitude !== '' && $newLongitude !== '' &&
			is_numeric($newLatitude) &&	is_numeric($newLongitude)
		) {

			$latitude  = (float) $newLatitude;
			$longitude = (float) $newLongitude;
		}

		// UPLOAD FOTO
		$config['upload_path'] = './assets/foto_ortu/';
		$config['allowed_types'] = 'jpg|jpeg|png|gif|JPG|JPEG|PNG|GIF';
		$config['max_size'] = 5000;
		$this->load->library('upload',	$config);
		$foto_ortu = $ortu_lama->foto_ortu;

		// FOTO BARU
		if (!empty($_FILES['foto_ortu']['name'])) {

			if ($this->upload->do_upload('foto_ortu')) {

				$upload_data = $this->upload->data();
				$foto_ortu = $upload_data['file_name'];

				// HAPUS FOTO LAMA
				if (
					$ortu_lama->ortu_lama != '' &&	$ortu_lama->foto_ortu != 'user.jpg'
				) {
					$file_lama = './assets/foto_ortu/' .	$ortu_lama->foto_ortu;

					if (file_exists($file_lama)) {

						unlink($file_lama);
					}
				}
			} else {

				$this->session->set_flashdata(
					'flash_gagal',
					$this->upload->display_errors('', '')
				);

				redirect('dashboard1/edit_ortu/' .	$id_ortu);

				return;
			}
		}

		// DATA YANG AKAN DI UPDATE
		$data = array(
			'nama_ortu' =>	$nama,
			'jenis_kelamin_ortu' =>	$jenis_kelamin,
			'tgl_lahir_ortu' =>	$tgl_lahir,
			'pasangan' => $pasangan,
			'alamat_ortu' =>	$alamat,
			'NoHP_ortu' => $NoHP,
			'foto_ortu' =>	$foto_ortu,
			'id_provinsi' => $id_provinsi,
			'id_kabupaten' => $id_kabupaten,
			'id_kecamatan' => $id_kecamatan,
			'id_desa' => $id_desa,
			'latitude' =>	$latitude,
			'longitude' =>	$longitude
		);

		// UPDATE DATABASE
		$where = array('id_ortu' => $id_ortu);
		$this->m_anak1->update_data_ortu($where, $data, 'ortu');

		// PESAN
		$this->session->set_flashdata('flash', 'Data Ortu Berhasil Di Update');
		redirect('dashboard1/profile');
	}

	// MENAMPILKAN DATA ANAK UNTUK INPUT CUCU
	function tampil_data_anak()
	{
		$username = $this->session->nama;

		$data['ortu'] = $this->db->query("select * from ortu WHERE username='" . $username . "'")->result();

		$data['anak'] = $this->db->query("SELECT anak.*, ortu.username, ortu.foto_ortu 
		from anak 
		INNER JOIN ortu ON ortu.id_ortu=anak.id_ortu 
		WHERE ortu.username='" . $username . "'")->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_tampil_anak', $data);
		$this->load->view('templateLTE1/footer');
	}

	//MENAMPILKAN FORM TAMBAH DATA ANAK
	function tambah_anak($id_ortu)
	{
		$where = array('id_ortu' => $id_ortu);
		$data['anak'] = $this->m_anak1->form_simpan_data_anak($where, 'ortu')->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_input_anak', $data);
		$this->load->view('templateLTE1/footer');
	}

	function aksi_tambah_anak()
	{
		$id_ortu = $this->input->post('id_ortu');
		$nama = trim($this->input->post('nama'));
		$jenis_kelamin = trim($this->input->post('jenis_kelamin'));
		$tgl_lahir_input = $this->input->post('tgl_lahir');
		$menantu = trim($this->input->post('menantu'));
		$alamat = trim($this->input->post('alamat'));
		$NoHP = trim($this->input->post('NoHP'));
		$id_provinsi = $this->input->post('id_provinsi');
		$id_kabupaten = $this->input->post('id_kabupaten');
		$id_kecamatan = $this->input->post('id_kecamatan');
		$id_desa = $this->input->post('id_desa');
		$provinsi = trim($this->input->post('nama_provinsi'));
		$kabupaten = trim($this->input->post('nama_kabupaten'));
		$kecamatan = trim($this->input->post('nama_kecamatan'));
		$desa = trim($this->input->post('nama_desa'));
		$latitude = $this->input->post('latitude');
		$longitude = $this->input->post('longitude');

		if (
			$nama == "" ||
			$jenis_kelamin == "" ||
			$tgl_lahir_input == "" ||
			$alamat == "" ||
			$id_provinsi == "" ||
			$id_kabupaten == "" ||
			$id_kecamatan == "" ||
			$id_desa == ""
		) {
			$this->session->set_flashdata('flash_gagal', 'Data wajib belum lengkap');

			redirect('dashboard1/tambah_anak/' . $id_ortu);
			return;
		}

		$tgl_lahir = date("d-M-Y", strtotime($tgl_lahir_input));

		$alamat_osm = '';
		if ($desa != '') {
			$alamat_osm .= $desa;
		}

		if ($kecamatan != '') {
			$alamat_osm .= ', ' . $kecamatan;
		}

		if ($kabupaten != '') {
			$alamat_osm .= ', ' . $kabupaten;
		}

		if ($provinsi != '') {
			$alamat_osm .= ', ' . $provinsi;
		}

		$alamat_osm .= ', Indonesia';

		// Bersihkan kata-kata yang tidak perlu
		$alamat_osm = str_replace(
			array(
				'KABUPATEN ',
				'KOTA ',
				'KECAMATAN ',
				'PROVINSI '
			),
			'',
			strtoupper($alamat_osm)
		);

		// Hilangkan koma ganda
		$alamat_osm = preg_replace('/,\s*,+/', ',', $alamat_osm);

		// Hilangkan spasi berlebihan
		$alamat_osm = preg_replace('/\s+/', ' ', $alamat_osm);

		$data = array(
			'id_ortu' => $id_ortu,
			'nama' => $nama,
			'jenis_kelamin' => $jenis_kelamin,
			'tgl_lahir' => $tgl_lahir,
			'menantu' => $menantu,
			'alamat' => $alamat,
			'NoHP' => $NoHP,
			'foto_anak' => 'user.jpg',
			'id_provinsi' => $id_provinsi,
			'id_kabupaten' => $id_kabupaten,
			'id_kecamatan' => $id_kecamatan,
			'id_desa' => $id_desa,
			'latitude' => $latitude,
			'longitude' => $longitude
		);

		$this->m_anak1->input_data($data, 'anak');
		$this->session->set_flashdata('flash', 'Data Anak Berhasil Di Tambah');
		redirect('dashboard1/tampil_data_orangtua');
	}

	//MENAMPILKAN FORM EDIT DATA ANAK
	function edit_anak($id_anak)
	{
		$where = array('id_anak' => $id_anak);
		$data['anak'] = $this->m_anak1->edit_data($where, 'anak')->result();
		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_edit_anak', $data);
		$this->load->view('templateLTE1/footer');
	}


	// PROSES UPDATE DATA ANAK
	function update_anak()
	{
		$id_anak = $this->input->post('id_anak');

		// AMBIL DATA LAMA
		$anak_lama = $this->db
			->where('id_anak', $id_anak)
			->get('anak')
			->row();

		if (!$anak_lama) {
			show_error('Data anak tidak ditemukan');
			return;
		}

		// AMBIL DATA FORM
		$nama = trim($this->input->post('nama'));
		$jenis_kelamin = trim($this->input->post('jenis_kelamin'));
		$tgl_lahir_input = $this->input->post('tgl_lahir');
		$menantu = trim($this->input->post('menantu'));
		$alamat = trim($this->input->post('alamat'));
		$NoHP = trim($this->input->post('NoHP'));
		$id_provinsi = $this->input->post('id_provinsi');
		$id_kabupaten =	$this->input->post('id_kabupaten');
		$id_kecamatan =	$this->input->post('id_kecamatan');
		$id_desa = $this->input->post('id_desa');
		$newLatitude  = trim($this->input->post('newLatitude'));
		$newLongitude = trim($this->input->post('newLongitude'));

		// VALIDASI
		if (
			$nama == '' ||
			$jenis_kelamin == '' ||
			$tgl_lahir_input == '' ||
			$alamat == '' ||
			$id_provinsi == '' ||
			$id_kabupaten == '' ||
			$id_kecamatan == '' ||
			$id_desa == ''
		) {

			$this->session->set_flashdata('flash', 'Data wajib belum lengkap');
			redirect('dashboard1/edit_anak/' . $id_anak);
			return;
		}

		// FORMAT TANGGAL

		$tgl_lahir = date('d-M-Y', strtotime($tgl_lahir_input));

		// CEK APAKAH ALAMAT / WILAYAH BERUBAH
		$alamat_lama = trim($anak_lama->alamat);
		$alamat_berubah = $alamat_lama != $alamat;

		$wilayah_berubah =
			$anak_lama->id_provinsi != $id_provinsi ||
			$anak_lama->id_kabupaten != $id_kabupaten ||
			$anak_lama->id_kecamatan != $id_kecamatan ||
			$anak_lama->id_desa != $id_desa;

		// KOORDINAT DARI MARKER MAP
		$latitude  = $anak_lama->latitude;
		$longitude = $anak_lama->longitude;

		// Jika user menggeser marker
		if (
			$newLatitude !== '' &&
			$newLongitude !== '' &&
			is_numeric($newLatitude) &&
			is_numeric($newLongitude)
		) {

			$latitude  = (float) $newLatitude;
			$longitude = (float) $newLongitude;
		}

		// UPLOAD FOTO
		$config['upload_path'] = './assets/foto_anak/';
		$config['allowed_types'] = 'jpg|jpeg|png|gif|JPG|JPEG|PNG|GIF';
		$config['max_size'] = 5000;
		$this->load->library('upload',	$config);
		$foto_anak = $anak_lama->foto_anak;

		// FOTO BARU
		if (!empty($_FILES['foto_anak']['name'])) {

			if ($this->upload->do_upload('foto_anak')) {

				$upload_data = $this->upload->data();
				$foto_anak = $upload_data['file_name'];

				// HAPUS FOTO LAMA
				if (
					$anak_lama->foto_anak != '' &&	$anak_lama->foto_anak != 'user.jpg'
				) {
					$file_lama = './assets/foto_anak/' .	$anak_lama->foto_anak;

					if (file_exists($file_lama)) {

						unlink($file_lama);
					}
				}
			} else {

				$this->session->set_flashdata(
					'flash_gagal',
					$this->upload->display_errors(
						'',
						''
					)
				);

				redirect('dashboard1/edit_anak/' .	$id_anak);

				return;
			}
		}

		// DATA YANG AKAN DI UPDATE
		$data = array(
			'nama' =>	$nama,
			'jenis_kelamin' =>	$jenis_kelamin,
			'tgl_lahir' =>	$tgl_lahir,
			'menantu' => $menantu,
			'alamat' =>	$alamat,
			'NoHP' => $NoHP,
			'foto_anak' =>	$foto_anak,
			'id_provinsi' => $id_provinsi,
			'id_kabupaten' => $id_kabupaten,
			'id_kecamatan' => $id_kecamatan,
			'id_desa' => $id_desa,
			'latitude' =>	$latitude,
			'longitude' =>	$longitude
		);

		// UPDATE DATABASE
		$where = array('id_anak' => $id_anak);
		$this->m_anak1->update_data_anak($where, $data, 'anak');

		// PESAN
		$this->session->set_flashdata('flash', 'Data Anak Berhasil Di Update');
		redirect('dashboard1/view_profile_anak/' . $id_anak);
	}

	//PROSES HAPUS DATA 	
	function hapus($id)
	{
		$where = array('id_anak' => $id);
		$this->m_anak1->hapus_data($where, 'anak');
		$this->session->set_flashdata('flash', 'Di Hapus');

		redirect('dashboard1/tampil_data_anak');
	}

	//MENAMPILKAN DATA PROFILE ANAK
	function view_profile_anak($id)
	{

		$data['profile'] = $this->db->query("select anak.*, ortu.nama_ortu, ortu.pasangan FROM anak
		INNER JOIN ortu ON ortu.id_ortu=anak.id_ortu
		WHERE anak.id_anak=$id")->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_profile_anak', $data);
		$this->load->view('templateLTE1/footer');
	}

	//MENAMPILKAN SEMUA DATA CUCU 
	function tampil_data_cucu()
	{
		$username = $this->session->nama;

		$data['ortu'] = $this->db->query("select * from ortu WHERE username='" . $username . "'")->result();

		$data['cucu'] = $this->db->query("SELECT cucu.*, anak.id_anak, anak.nama, ortu.username 
		from cucu 
		INNER JOIN anak ON anak.id_anak=cucu.id_anak 
		INNER JOIN ortu ON ortu.id_ortu=anak.id_ortu 
		WHERE ortu.username='" . $username . "'")->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_tampil_cucu', $data);
		$this->load->view('templateLTE1/footer');
	}

	// MENAMPILKAN DATA DETAIL DATA ANAK BERDASARKAN ID
	function tambah_cucu($id_anak)
	{
		$username = $this->session->nama;
		$where = array('id_anak' => $id_anak);
		$data['anak'] = $this->m_anak1->form_simpan_data_cucu($where, 'anak')->result();

		$data['cucu'] = $this->db->query("SELECT cucu.*, anak.id_anak, anak.nama, ortu.username 
		from cucu 
		INNER JOIN anak ON anak.id_anak=cucu.id_anak 
		INNER JOIN ortu ON ortu.id_ortu=anak.id_ortu 
		WHERE anak.id_anak='" . $id_anak . "'")->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_detil_anak', $data);
		$this->load->view('templateLTE1/footer');
	}

	// MENAMPILKAN FORM TAMBAH DATA CUCU
	function form_cucu($id_anak)
	{
		$username = $this->session->nama;
		$where = array('id_anak' => $id_anak);
		$data['anak'] = $this->m_anak1->form_simpan_data_cucu($where, 'anak')->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_input_cucu', $data);
		$this->load->view('templateLTE1/footer');
	}


	// PROSES SIMPAN DATA CUCU
	function aksi_tambah_cucu()
	{
		$id_anak = $this->input->post('id_anak');
		$nama_cucu = $this->input->post('nama_cucu');
		$jenis_kelamin_cucu = $this->input->post('jenis_kelamin_cucu');
		$tgl_lahir_cucu = $this->input->post('tgl_lahir_cucu');
		$menantu_cucu = $this->input->post('menantu_cucu');
		$alamat_cucu = $this->input->post('alamat_cucu');
		$NoHP_cucu = $this->input->post('NoHP_cucu');
		$id_provinsi = $this->input->post('id_provinsi');
		$id_kabupaten = $this->input->post('id_kabupaten');
		$id_kecamatan = $this->input->post('id_kecamatan');
		$id_desa = $this->input->post('id_desa');
		$provinsi = trim($this->input->post('nama_provinsi'));
		$kabupaten = trim($this->input->post('nama_kabupaten'));
		$kecamatan = trim($this->input->post('nama_kecamatan'));
		$desa = trim($this->input->post('nama_desa'));
		$latitude = $this->input->post('latitude');
		$longitude = $this->input->post('longitude');

		if (
			$id_anak == "" ||
			$nama_cucu == "" ||
			$jenis_kelamin_cucu == "" ||
			$tgl_lahir_cucu == "" ||
			$alamat_cucu == "" ||
			$id_provinsi == "" ||
			$id_kabupaten == "" ||
			$id_kecamatan == "" ||
			$id_desa == ""
		) {
			$this->session->set_flashdata('flash_gagal', 'Kolom Jangan Di Kosongkan');
			redirect('dashboard1/tambah_cucu/' . $id_anak);
			return;
		}
		$alamat_osm = '';
		if ($desa != '') {
			$alamat_osm .= $desa;
		}

		if ($kecamatan != '') {
			$alamat_osm .= ', ' . $kecamatan;
		}

		if ($kabupaten != '') {
			$alamat_osm .= ', ' . $kabupaten;
		}

		if ($provinsi != '') {
			$alamat_osm .= ', ' . $provinsi;
		}

		$alamat_osm .= ', Indonesia';

		// Bersihkan kata-kata yang tidak perlu
		$alamat_osm = str_replace(
			array(
				'KABUPATEN ',
				'KOTA ',
				'KECAMATAN ',
				'PROVINSI '
			),
			'',
			strtoupper($alamat_osm)
		);

		// Hilangkan koma ganda
		$alamat_osm = preg_replace('/,\s*,+/', ',', $alamat_osm);

		// Hilangkan spasi berlebihan
		$alamat_osm = preg_replace('/\s+/', ' ', $alamat_osm);

		$koordinat = $this->get_koordinat_osm(
			$desa,
			$kecamatan,
			$kabupaten,
			$provinsi
		);

		$data = array(
			'id_anak' => $id_anak,
			'nama_cucu' => $nama_cucu,
			'jenis_kelamin_cucu' => $jenis_kelamin_cucu,
			'tgl_lahir_cucu' => date('d-M-Y', strtotime($tgl_lahir_cucu)),
			'menantu_cucu' => $menantu_cucu,
			'alamat_cucu' => $alamat_cucu,
			'NoHP_cucu' => $NoHP_cucu,
			'foto_cucu' => 'user.jpg',
			'id_provinsi' => $id_provinsi,
			'id_kabupaten' => $id_kabupaten,
			'id_kecamatan' => $id_kecamatan,
			'id_desa' => $id_desa,
			'latitude' => $latitude,
			'longitude' => $longitude
		);

		$this->db->insert('cucu', $data);
		$this->session->set_flashdata('flash', 'Data Cucu Berhasil Di Tambah');
		redirect('dashboard1/tampil_data_anak');
	}

	//MENAMPILKAN FORM EDIT DATA CUCU
	function edit_cucu($id_cucu)
	{
		$where = array('id_cucu' => $id_cucu);
		$data['cucu'] = $this->m_anak1->edit_data($where, 'cucu')->result();
		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_edit_cucu', $data);
		$this->load->view('templateLTE1/footer');
	}

	//PROSES UPDATE DATA CUCU
	function update_cucu()
	{
		$id_cucu = $this->input->post('id_cucu');
		// AMBIL DATA LAMA
		$cucu_lama = $this->db->where('id_cucu', $id_cucu)->get('cucu')
			->row();

		if (!$cucu_lama) {
			show_error('Data anak tidak ditemukan');
			return;
		}

		// AMBIL DATA FORM
		$nama = trim($this->input->post('nama_cucu'));
		$jenis_kelamin = trim($this->input->post('jenis_kelamin_cucu'));
		$tgl_lahir_input = $this->input->post('tgl_lahir_cucu');
		$menantu = trim($this->input->post('menantu_cucu'));
		$alamat = trim($this->input->post('alamat_cucu'));
		$NoHP = trim($this->input->post('NoHP_cucu'));
		$id_provinsi = $this->input->post('id_provinsi');
		$id_kabupaten =	$this->input->post('id_kabupaten');
		$id_kecamatan =	$this->input->post('id_kecamatan');
		$id_desa = $this->input->post('id_desa');
		$newLatitude  = trim($this->input->post('newLatitude'));
		$newLongitude = trim($this->input->post('newLongitude'));

		// VALIDASI
		if (
			$nama == '' ||
			$jenis_kelamin == '' ||
			$tgl_lahir_input == '' ||
			$alamat == '' ||
			$id_provinsi == '' ||
			$id_kabupaten == '' ||
			$id_kecamatan == '' ||
			$id_desa == ''
		) {

			$this->session->set_flashdata('flash', 'Data wajib belum lengkap');

			redirect('dashboard1/edit_cucu/' . $id_cucu);

			return;
		}

		// FORMAT TANGGAL

		$tgl_lahir = date('d-M-Y', strtotime($tgl_lahir_input));

		// CEK APAKAH ALAMAT / WILAYAH BERUBAH
		$alamat_lama = trim($cucu_lama->alamat);
		$alamat_berubah = $alamat_lama != $alamat;

		$wilayah_berubah =
			$cucu_lama->id_provinsi != $id_provinsi ||
			$cucu_lama->id_kabupaten != $id_kabupaten ||
			$cucu_lama->id_kecamatan != $id_kecamatan ||
			$cucu_lama->id_desa != $id_desa;


		// KOORDINAT DARI MARKER MAP
		$latitude  = $cucu_lama->latitude;
		$longitude = $cucu_lama->longitude;

		// Jika user menggeser marker
		if (
			$newLatitude !== '' &&
			$newLongitude !== '' &&
			is_numeric($newLatitude) &&
			is_numeric($newLongitude)
		) {

			$latitude  = (float) $newLatitude;
			$longitude = (float) $newLongitude;
		}

		$id_cucu = $this->input->post('id_cucu');

		$config['upload_path']	= './assets/foto_cucu';
		$config['allowed_types'] = 'jpg|png|gif|JPEG';
		$config['overwrite']    = true;

		$this->load->library('upload', $config);

		if (!$this->upload->do_upload('foto_cucu')) {
			$nama = $this->input->post('nama_cucu');
			$jenis_kelamin = $this->input->post('jenis_kelamin_cucu');
			$tgl_lahir = date("d-M-Y", strtotime($this->input->post('tgl_lahir_cucu')));
			$menantu = $this->input->post('menantu_cucu');
			$alamat = $this->input->post('alamat_cucu');
			$NoHP = $this->input->post('NoHP_cucu');
			$id_provinsi = $this->input->post('id_provinsi');
			$id_kabupaten = $this->input->post('id_kabupaten');
			$id_kecamatan = $this->input->post('id_kecamatan');
			$id_desa = $this->input->post('id_desa');

			$data = array(
				'nama_cucu' => $nama,
				'jenis_kelamin_cucu' => $jenis_kelamin,
				'tgl_lahir_cucu' => $tgl_lahir,
				'menantu_cucu' => $menantu,
				'alamat_cucu' => $alamat,
				'NoHP_cucu' => $NoHP,
				'id_provinsi' => $id_provinsi,
				'id_kabupaten' => $id_kabupaten,
				'id_kecamatan' => $id_kecamatan,
				'id_desa' => $id_desa,
				'latitude' =>	$latitude,
				'longitude' =>	$longitude
			);

			$where = array('id_cucu' => $id_cucu);
			$this->m_anak1->update_data_anak($where, $data, 'cucu');

			$this->session->set_flashdata('flash', 'Di Update');
			redirect('dashboard1/view_profile_cucu/' . $id_cucu);
		} else {
			$nama = $this->input->post('nama_cucu');
			$jenis_kelamin = $this->input->post('jenis_kelamin_cucu');
			$tgl_lahir = date("d-M-Y", strtotime($this->input->post('tgl_lahir_cucu')));
			$menantu = $this->input->post('menantu_cucu');
			$alamat = $this->input->post('alamat_cucu');
			$NoHP = $this->input->post('NoHP_cucu');
			$foto = $this->upload->data('file_name');
			$id_provinsi = $this->input->post('id_provinsi');
			$id_kabupaten = $this->input->post('id_kabupaten');
			$id_kecamatan = $this->input->post('id_kecamatan');
			$id_desa = $this->input->post('id_desa');

			$data = array(
				'nama_cucu' => $nama,
				'jenis_kelamin_cucu' => $jenis_kelamin,
				'tgl_lahir_cucu' => $tgl_lahir,
				'menantu_cucu' => $menantu,
				'alamat_cucu' => $alamat,
				'NoHP_cucu' => $NoHP,
				'foto_cucu' => $foto,
				'id_provinsi' => $id_provinsi,
				'id_kabupaten' => $id_kabupaten,
				'id_kecamatan' => $id_kecamatan,
				'id_desa' => $id_desa,
				'latitude' =>	$latitude,
				'longitude' =>	$longitude
			);

			$where = array('id_cucu' => $id_cucu);

			$this->m_anak1->update_data_cucu($where, $data, 'cucu');

			$this->session->set_flashdata('flash', 'Di Edit');

			redirect('dashboard1/view_profile_cucu/' . $id_cucu);
		}
	}

	// HAPUS DATA CUCU
	function hapus_cucu($id_cucu)
	{
		$where = array('id_cucu' => $id_cucu);
		$this->m_anak1->hapus_data($where, 'cucu');

		$this->session->set_flashdata('flash', 'Di Hapus');

		redirect('dashboard1/tampil_data_cucu');
	}

	function view_profile_cucu($id)
	{
		$data['profile'] = $this->db->query("select cucu.*, anak.nama, anak.menantu 
		FROM cucu
		INNER JOIN anak ON anak.id_anak=cucu.id_anak
		WHERE cucu.id_cucu=$id")->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_profile_cucu', $data);
		$this->load->view('templateLTE1/footer');
	}

	// MENAMPILKAN DATA CICIT
	function tampil_data_cicit()
	{
		$username = $this->session->nama;

		$data['ortu'] = $this->db->query("select * from ortu WHERE username='" . $username . "'")->result();

		$data['cicit'] = $this->db->query("SELECT cicit.*, cucu.id_cucu, cucu.nama_cucu, ortu.id_ortu, ortu.username  
		FROM cicit
		INNER JOIN cucu ON cucu.id_cucu=cicit.id_cucu
		INNER JOIN anak ON anak.id_anak=cucu.id_anak
		INNER JOIN ortu ON ortu.id_ortu=anak.id_ortu
		WHERE ortu.username='" . $username . "'")->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_tampil_cicit', $data);
		$this->load->view('templateLTE1/footer');
	}

	// MENAMPILKAN FORM TAMBAH DATA CICIT
	function tambah_cicit($id_cucu)
	{
		$username = $this->session->nama;

		$where = array('id_cucu' => $id_cucu);

		$data['cucu'] = $this->m_anak1->form_simpan_data_cucu($where, 'cucu')->result();

		$data['cicit'] = $this->db->query("SELECT cicit.*, cucu.id_cucu, cucu.nama_cucu, ortu.id_ortu, ortu.username  
		FROM cicit
		INNER JOIN cucu ON cucu.id_cucu=cicit.id_cucu
		INNER JOIN anak ON anak.id_anak=cucu.id_anak
		INNER JOIN ortu ON ortu.id_ortu=anak.id_ortu
		WHERE cucu.id_cucu='" . $id_cucu . "'")->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_detil_cucu', $data);
		$this->load->view('templateLTE1/footer');
	}

	// MENAMPILKAN FORM TAMBAH DATA CICIT
	function form_cicit($id_cucu)
	{
		$username = $this->session->nama;
		$where = array('id_cucu' => $id_cucu);
		$data['cucu'] = $this->m_anak1->form_simpan_data_cicit($where, 'cucu')->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_input_cicit', $data);
		$this->load->view('templateLTE1/footer');
	}


	// =========================================================
	// PROSES SIMPAN DATA CICIT
	// =========================================================
	function aksi_tambah_cicit()
	{
		$id_cucu = $this->input->post('id_cucu');
		$id_cicit = $this->input->post('id_cicit');
		$nama = trim($this->input->post('nama_cicit'));
		$jenis_kelamin = trim($this->input->post('jenis_kelamin_cicit'));
		$tgl_lahir_input = $this->input->post('tgl_lahir_cicit');
		$menantu = trim($this->input->post('menantu_cicit'));
		$alamat = trim($this->input->post('alamat_cicit'));
		$NoHP = trim($this->input->post('NoHP_cicit'));
		$id_provinsi = $this->input->post('id_provinsi');
		$id_kabupaten = $this->input->post('id_kabupaten');
		$id_kecamatan = $this->input->post('id_kecamatan');
		$id_desa = $this->input->post('id_desa');
		$provinsi = trim($this->input->post('nama_provinsi'));
		$kabupaten = trim($this->input->post('nama_kabupaten'));
		$kecamatan = trim($this->input->post('nama_kecamatan'));
		$desa = trim($this->input->post('nama_desa'));
		$latitude = $this->input->post('latitude');
		$longitude = $this->input->post('longitude');

		if (
			$nama == '' ||
			$jenis_kelamin == '' ||
			$tgl_lahir_input == '' ||
			$alamat == '' ||
			$id_provinsi == '' ||
			$id_kabupaten == '' ||
			$id_kecamatan == '' ||
			$id_desa == ''
		) {

			$this->session->set_flashdata('flash_gagal',  'Form Tidak Boleh Kosong');
			redirect('dashboard1/tambah_cicit/' . $id_cucu);
			return;
		}

		$tgl_lahir = date("d-M-Y", strtotime($tgl_lahir_input));

		$alamat_osm = '';
		if ($desa != '') {
			$alamat_osm .= $desa;
		}

		if ($kecamatan != '') {
			$alamat_osm .= ', ' . $kecamatan;
		}

		if ($kabupaten != '') {
			$alamat_osm .= ', ' . $kabupaten;
		}

		if ($provinsi != '') {
			$alamat_osm .= ', ' . $provinsi;
		}

		$alamat_osm .= ', Indonesia';

		// Bersihkan kata-kata yang tidak perlu
		$alamat_osm = str_replace(
			array(
				'KABUPATEN ',
				'KOTA ',
				'KECAMATAN ',
				'PROVINSI '
			),
			'',
			strtoupper($alamat_osm)
		);

		// Hilangkan koma ganda
		$alamat_osm = preg_replace('/,\s*,+/', ',', $alamat_osm);

		// Hilangkan spasi berlebihan
		$alamat_osm = preg_replace('/\s+/', ' ', $alamat_osm);


		// $koordinat = $this->get_koordinat_osm(
		// 	$desa,
		// 	$kecamatan,
		// 	$kabupaten,
		// 	$provinsi
		// );

		// 			// CARI KOORDINAT
		// 		$koordinat = $this->get_koordinat_osm(
		// 			$desa,
		// 			$kecamatan,
		// 			$kabupaten,
		// 			$provinsi
		// 		);

		// 		$latitude = null;
		// 		$longitude = null;

		// 		if (
		// 			$koordinat !== false &&
		// 			is_array($koordinat)
		// 		) {

		// 			$latitude =
		// 				$koordinat['latitude'];

		// 			$longitude =
		// 				$koordinat['longitude'];
		// 		}

		// 	// Jika berhasil
		// 	if ($koordinat !== false && is_array($koordinat)) {

		// 		$latitude  = $koordinat['latitude'];
		// 		$longitude = $koordinat['longitude'];

		// 	} else {

		// 		echo '<pre>';
		// 		echo "KOORDINAT GAGAL:\n";
		// 		var_dump($koordinat);
		// 		echo '</pre>';
		// 		exit;
		// 	}

		// DATA CICIT
		$data = array(
			'id_cucu' => $id_cucu,
			'id_cicit' => $id_cicit,
			'nama_cicit' => $nama,
			'jenis_kelamin_cicit' => $jenis_kelamin,
			'tgl_lahir_cicit' => $tgl_lahir,
			'menantu_cicit' => $menantu,
			'alamat_cicit' => $alamat,
			'NoHP_cicit' => $NoHP,
			'foto_cicit' => 'user.jpg',
			'id_provinsi' => $id_provinsi,
			'id_kabupaten' => $id_kabupaten,
			'id_kecamatan' => $id_kecamatan,
			'id_desa' => $id_desa,
			'latitude' => $latitude,
			'longitude' => $longitude
		);

		// SIMPAN
		$this->m_anak1->input_data($data, 'cicit');
		$this->session->set_flashdata('flash', 'Di Tambah');

		redirect('dashboard1/tambah_cicit/' . $id_cucu);
	}

	//HAPUS DATA CICIT
	function hapus_cicit($id_cicit)
	{
		$where = array('id_cicit' => $id_cicit);
		$this->m_anak1->hapus_data($where, 'cicit');
		$this->session->set_flashdata('flash', 'Di Hapus');

		redirect('dashboard1/tampil_data_cicit');
	}

	// MENAMPILKAN FORM EDIT DATA CICIT
	function edit_cicit($id_cicit)
	{
		$where = array('id_cicit' => $id_cicit);
		$data['cicit'] = $this->m_anak1->edit_data($where, 'cicit')->result();
		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_edit_cicit', $data);
		$this->load->view('templateLTE1/footer');
	}

	// PROSES UPDATE DATA CICIT
	function update_cicit()
	{

		$id_cicit = $this->input->post('id_cicit');
		$id_cucu  = $this->input->post('id_cucu');

		$cicit_lama = $this->db->where('id_cicit', $id_cicit)
			->get('cicit')->row();


		if (!$cicit_lama) {
			show_error('Data cicit tidak ditemukan');
			return;
		}

		$nama = trim($this->input->post('nama_cicit'));
		$jenis_kelamin = trim($this->input->post('jenis_kelamin_cicit'));
		$tgl_lahir_input = $this->input->post('tgl_lahir_cicit');
		$menantu = trim($this->input->post('menantu_cicit'));
		$alamat = trim($this->input->post('alamat_cicit'));
		$NoHP =	trim($this->input->post('NoHP_cicit'));
		$id_provinsi = $this->input->post('id_provinsi');
		$id_kabupaten =	$this->input->post('id_kabupaten');
		$id_kecamatan =	$this->input->post('id_kecamatan');
		$id_desa = $this->input->post('id_desa');
		$newLatitude  = trim($this->input->post('newLatitude'));
		$newLongitude = trim($this->input->post('newLongitude'));

		if (
			$nama == '' ||
			$jenis_kelamin == '' ||
			$tgl_lahir_input == '' ||
			$alamat == '' ||
			$id_provinsi == '' ||
			$id_kabupaten == '' ||
			$id_kecamatan == '' ||
			$id_desa == ''
		) {

			$this->session->set_flashdata('flash', 'Data wajib belum lengkap');
			redirect('dashboard1/edit_cicit/' .	$id_cicit);
			return;
		}

		$tgl_lahir = date('d-M-Y', strtotime($tgl_lahir_input));

		$alamat_lama = trim($cicit_lama->alamat_cicit);
		$alamat_berubah = ($alamat_lama != $alamat);
		$wilayah_berubah =
			$cicit_lama->id_provinsi != $id_provinsi ||
			$cicit_lama->id_kabupaten != $id_kabupaten ||
			$cicit_lama->id_kecamatan != $id_kecamatan ||
			$cicit_lama->id_desa != $id_desa;

		$latitude =	$cicit_lama->latitude;
		$longitude = $cicit_lama->longitude;

		// if ($alamat_berubah || $wilayah_berubah) {


		// 	$provinsi = '';

		// 	$json_provinsi =
		// 		@file_get_contents(
		// 			site_url(
		// 				'dashboard1/api_provinsi'
		// 			)
		// 		);


		// 	if ($json_provinsi !== false) {

		// 		$data_provinsi =
		// 			json_decode(
		// 				$json_provinsi,
		// 				true
		// 			);


		// 		if (
		// 			is_array(
		// 				$data_provinsi
		// 			)
		// 		) {

		// 			foreach (
		// 				$data_provinsi as $p
		// 			) {

		// 				if (
		// 					isset($p['id']) &&
		// 					$p['id'] ==
		// 					$id_provinsi
		// 				) {

		// 					$provinsi =
		// 						$p['name'];

		// 					break;
		// 				}
		// 			}
		// 		}
		// 	}


		// 	// ====================================================
		// 	// KABUPATEN
		// 	// ====================================================

		// 	$kabupaten = '';

		// 	$json_kabupaten =
		// 		@file_get_contents(
		// 			site_url(
		// 				'dashboard1/api_kabupaten/' .
		// 				$id_provinsi
		// 			)
		// 		);


		// 	if (
		// 		$json_kabupaten !== false
		// 	) {

		// 		$data_kabupaten =
		// 			json_decode(
		// 				$json_kabupaten,
		// 				true
		// 			);


		// 		if (
		// 			is_array(
		// 				$data_kabupaten
		// 			)
		// 		) {

		// 			foreach (
		// 				$data_kabupaten as $k
		// 			) {

		// 				if (
		// 					isset($k['id']) &&
		// 					$k['id'] ==
		// 					$id_kabupaten
		// 				) {

		// 					$kabupaten =
		// 						$k['name'];

		// 					break;
		// 				}
		// 			}
		// 		}
		// 	}


		// 	// ====================================================
		// 	// KECAMATAN
		// 	// ====================================================

		// 	$kecamatan = '';

		// 	$json_kecamatan =
		// 		@file_get_contents(
		// 			site_url(
		// 				'dashboard1/api_kecamatan/' .
		// 				$id_kabupaten
		// 			)
		// 		);


		// 	if (
		// 		$json_kecamatan !== false
		// 	) {

		// 		$data_kecamatan =
		// 			json_decode(
		// 				$json_kecamatan,
		// 				true
		// 			);


		// 		if (
		// 			is_array(
		// 				$data_kecamatan
		// 			)
		// 		) {

		// 			foreach (
		// 				$data_kecamatan as $kec
		// 			) {

		// 				if (
		// 					isset($kec['id']) &&
		// 					$kec['id'] ==
		// 					$id_kecamatan
		// 				) {

		// 					$kecamatan =
		// 						$kec['name'];

		// 					break;
		// 				}
		// 			}
		// 		}
		// 	}


		// 	// ====================================================
		// 	// DESA
		// 	// ====================================================

		// 	$desa = '';

		// 	$json_desa =
		// 		@file_get_contents(
		// 			site_url(
		// 				'dashboard1/api_desa/' .
		// 				$id_kecamatan
		// 			)
		// 		);


		// 	if (
		// 		$json_desa !== false
		// 	) {

		// 		$data_desa =
		// 			json_decode(
		// 				$json_desa,
		// 				true
		// 			);


		// 		if (
		// 			is_array(
		// 				$data_desa
		// 			)
		// 		) {

		// 			foreach (
		// 				$data_desa as $d
		// 			) {

		// 				if (
		// 					isset($d['id']) &&
		// 					$d['id'] ==
		// 					$id_desa
		// 				) {

		// 					$desa =
		// 						$d['name'];

		// 					break;
		// 				}
		// 			}
		// 		}
		// 	}


		// 	// ====================================================
		// 	// BUAT ALAMAT UNTUK OSM
		// 	// ====================================================

		// 	$alamat_osm =
		// 		$alamat;


		// 	if ($desa != '') {

		// 		$alamat_osm .=
		// 			', ' . $desa;
		// 	}


		// 	if ($kecamatan != '') {

		// 		$alamat_osm .=
		// 			', ' . $kecamatan;
		// 	}


		// 	if ($kabupaten != '') {

		// 		$alamat_osm .=
		// 			', ' . $kabupaten;
		// 	}


		// 	if ($provinsi != '') {

		// 		$alamat_osm .=
		// 			', ' . $provinsi;
		// 	}


		// 	$alamat_osm .=
		// 		', Indonesia';


		// 	// ====================================================
		// 	// BERSIHKAN KOMA GANDA
		// 	// ====================================================

		// 	$alamat_osm =
		// 		preg_replace(
		// 			'/,\s*,+/',
		// 			',',
		// 			$alamat_osm
		// 		);


		// 	$alamat_osm =
		// 		preg_replace(
		// 			'/\s+/',
		// 			' ',
		// 			$alamat_osm
		// 		);


		// 	$alamat_osm =
		// 		trim(
		// 			$alamat_osm,
		// 			' ,'
		// 		);


		// 	// ====================================================
		// 	// DEBUG LOG
		// 	// ====================================================

		// 	log_message(
		// 		'error',
		// 		'ALAMAT OSM CICIT: ' .
		// 		$alamat_osm
		// 	);


		// 	// ====================================================
		// 	// CARI KOORDINAT
		// 	// ====================================================

		// 	$koordinat =
		// 		$this->update_koordinat(
		// 			$alamat_osm
		// 		);


		// 	// ====================================================
		// 	// JIKA GAGAL
		// 	// ====================================================

		// 	if (
		// 		$koordinat === false
		// 	) {

		// 		$this->session->set_flashdata(
		// 			'flash',
		// 			'Alamat tidak ditemukan di OpenStreetMap. Silakan periksa kembali alamat.'
		// 		);


		// 		redirect(
		// 			'dashboard1/edit_cicit/' .
		// 			$id_cicit
		// 		);


		// 		return;
		// 	}


		// 	// ====================================================
		// 	// KOORDINAT BARU
		// 	// ====================================================

		// 	$latitude =
		// 		$koordinat['latitude'];

		// 	$longitude =
		// 		$koordinat['longitude'];
		// }

		// KOORDINAT DARI MARKER MAP
		$latitude  = $cicit_lama->latitude;
		$longitude = $cicit_lama->longitude;

		// Jika user menggeser marker
		if (
			$newLatitude !== '' &&
			$newLongitude !== '' &&
			is_numeric($newLatitude) &&
			is_numeric($newLongitude)
		) {

			$latitude  = (float) $newLatitude;
			$longitude = (float) $newLongitude;
		}

		$config['upload_path'] = './assets/foto_cicit';
		$config['allowed_types'] = 'jpg|jpeg|png|gif';
		$config['overwrite'] =	true;

		$this->load->library('upload', $config);

		$data = array(
			'id_cucu' => $id_cucu,
			'nama_cicit' =>	$nama,
			'jenis_kelamin_cicit' => $jenis_kelamin,
			'tgl_lahir_cicit' => $tgl_lahir,
			'menantu_cicit' =>	$menantu,
			'alamat_cicit' => $alamat,
			'NoHP_cicit' =>	$NoHP,
			'id_provinsi' => $id_provinsi,
			'id_kabupaten' => $id_kabupaten,
			'id_kecamatan' => $id_kecamatan,
			'id_desa' => $id_desa,
			'latitude' => $latitude,
			'longitude' =>	$longitude
		);

		if (!empty($_FILES['foto_cicit']['name'])) {

			if ($this->upload->do_upload('foto_cicit')) {
				$foto =	$this->upload->data('file_name');
				$data['foto_cicit'] =	$foto;
			} else {
				log_message('error', 'UPLOAD FOTO CICIT: ' .
					$this->upload->display_errors('', ''));
			}
		}

		$where = array('id_cicit' => $id_cicit);
		$this->m_anak1->update_data_cicit($where, $data, 'cicit');
		$this->session->set_flashdata('flash', 'Data cicit berhasil diupdate');

		redirect('dashboard1/view_profile_cicit/' .	$id_cicit);
	}

	//MENAMPILKAN DATA BAOK
	function tampil_data_baok()
	{

		$username = $this->session->nama;
		$data['baok'] = $this->db->query("SELECT baok.*, cicit.id_cicit, cicit.nama_cicit, cucu.id_cucu, ortu.username  
		FROM baok
		INNER JOIN cicit ON cicit.id_cicit=baok.id_cicit
		INNER JOIN cucu ON cucu.id_cucu=cicit.id_cucu
		INNER JOIN anak ON anak.id_anak=cucu.id_anak
		INNER JOIN ortu ON ortu.id_ortu=anak.id_ortu
		WHERE ortu.username='" . $username . "'")->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_tampil_baok', $data);
		$this->load->view('templateLTE1/footer');
	}

	// MENAMPILKAN FORM TAMBAH DATA BAOK
	function tambah_baok($id_cicit)
	{
		$username = $this->session->nama;

		$where = array('id_cicit' => $id_cicit);

		$data['cicit'] = $this->m_anak1->form_simpan_data_cucu($where, 'cicit')->result();

		$data['baok'] = $this->db->query("SELECT baok.*, cicit.id_cicit, cicit.nama_cicit, cucu.id_cucu, ortu.username  
		FROM baok
		INNER JOIN cicit ON cicit.id_cicit=baok.id_cicit
		INNER JOIN cucu ON cucu.id_cucu=cicit.id_cucu
		INNER JOIN anak ON anak.id_anak=cucu.id_anak
		INNER JOIN ortu ON ortu.id_ortu=anak.id_ortu
		WHERE cicit.id_cicit='" . $id_cicit . "'")->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_detil_baok', $data);
		$this->load->view('templateLTE1/footer');
	}

	// MENAMPILKAN FORM TAMBAH DATA CICIT
	function form_baok($id_cicit)
	{
		$username = $this->session->nama;
		$where = array('id_cicit' => $id_cicit);
		$data['cicit'] = $this->m_anak1->form_simpan_data_baok($where, 'cicit')->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_input_baok', $data);
		$this->load->view('templateLTE1/footer');
	}


	// PROSES SIMPAN DATA CICIT
	function aksi_tambah_baok()
	{
		$id_cicit = $this->input->post('id_cicit');
		$id_baok = $this->input->post('id_baok');
		$nama = trim($this->input->post('nama_baok'));
		$jenis_kelamin = trim($this->input->post('jenis_kelamin_baok'));
		$tgl_lahir_input = $this->input->post('tgl_lahir_baok');
		$menantu = trim($this->input->post('menantu_baok'));
		$alamat = trim($this->input->post('alamat_baok'));
		$NoHP = trim($this->input->post('NoHP_baok'));
		$id_provinsi = $this->input->post('id_provinsi');
		$id_kabupaten = $this->input->post('id_kabupaten');
		$id_kecamatan = $this->input->post('id_kecamatan');
		$id_desa = $this->input->post('id_desa');
		$provinsi = trim($this->input->post('nama_provinsi'));
		$kabupaten = trim($this->input->post('nama_kabupaten'));
		$kecamatan = trim($this->input->post('nama_kecamatan'));
		$desa = trim($this->input->post('nama_desa'));
		$latitude = $this->input->post('latitude');
		$longitude = $this->input->post('longitude');

		if (
			$nama == '' ||
			$jenis_kelamin == '' ||
			$tgl_lahir_input == '' ||
			$alamat == '' ||
			$id_provinsi == '' ||
			$id_kabupaten == '' ||
			$id_kecamatan == '' ||
			$id_desa == ''
		) {

			$this->session->set_flashdata('flash_gagal',  'Form Tidak Boleh Kosong');
			redirect('dashboard1/tambah_baok/' . $id_cicit);
			return;
		}

		$tgl_lahir = date("d-M-Y", strtotime($tgl_lahir_input));

		$alamat_osm = '';
		if ($desa != '') {
			$alamat_osm .= $desa;
		}

		if ($kecamatan != '') {
			$alamat_osm .= ', ' . $kecamatan;
		}

		if ($kabupaten != '') {
			$alamat_osm .= ', ' . $kabupaten;
		}

		if ($provinsi != '') {
			$alamat_osm .= ', ' . $provinsi;
		}

		$alamat_osm .= ', Indonesia';

		// Bersihkan kata-kata yang tidak perlu
		$alamat_osm = str_replace(
			array(
				'KABUPATEN ',
				'KOTA ',
				'KECAMATAN ',
				'PROVINSI '
			),
			'',
			strtoupper($alamat_osm)
		);

		// Hilangkan koma ganda
		$alamat_osm = preg_replace('/,\s*,+/', ',', $alamat_osm);

		// Hilangkan spasi berlebihan
		$alamat_osm = preg_replace('/\s+/', ' ', $alamat_osm);

		// DATA CICIT
		$data = array(
			'id_cicit' => $id_cicit,
			'id_baok' => $id_baok,
			'nama_baok' => $nama,
			'jenis_kelamin_baok' => $jenis_kelamin,
			'tgl_lahir_baok' => $tgl_lahir,
			'menantu_baok' => $menantu,
			'alamat_baok' => $alamat,
			'NoHP_baok' => $NoHP,
			'foto_baok' => 'user.jpg',
			'id_provinsi' => $id_provinsi,
			'id_kabupaten' => $id_kabupaten,
			'id_kecamatan' => $id_kecamatan,
			'id_desa' => $id_desa,
			'latitude' => $latitude,
			'longitude' => $longitude
		);

		// SIMPAN
		$this->m_anak1->input_data($data, 'baok');
		$this->session->set_flashdata('flash', 'Di Tambah');

		redirect('dashboard1/tambah_baok/' . $id_cicit);
	}

	//HAPUS DATA BAOK
	function hapus_baok($id_baok)
	{
		$where = array('id_baok' => $id_baok);
		$this->m_anak1->hapus_data_baok($where, 'baok');
		$this->session->set_flashdata('flash', 'Di Hapus');

		redirect('dashboard1/tampil_data_cicit');
	}

	//Menampilkan form input galery tambah foto
	function galery()
	{
		$username = $this->session->nama;
		$data['galery'] = $this->db->query("select * from ortu WHERE username='" . $username . "'")->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_input_galery', $data);
		$this->load->view('templateLTE1/footer');
	}

	function aksi_tambah_foto()
	{
		$id_ortu = $this->input->post('id_ortu');
		$caption = $this->input->post('caption');
		$galery = $_FILES['galery'];

		// CEK FOTO DUPLIKAT
		if (!empty($galery['tmp_name']) && $galery['error'] == 0) {
			$hash_foto = md5_file($galery['tmp_name']);

			// Ambil semua foto milik ortu
			$data_galery = $this->db
				->where('id_ortu', $id_ortu)
				->get('galery')
				->result();

			foreach ($data_galery as $foto) {

				$file_lama = FCPATH . 'assets/galery/' . $foto->galery;

				if (file_exists($file_lama)) {
					$hash_foto_lama = md5_file($file_lama);

					// Jika isi foto sama
					if ($hash_foto == $hash_foto_lama) {
						$this->session->set_flashdata(
							'flash_gagal',
							'Foto sudah ada, tidak boleh upload foto yang sama.'
						);

						redirect('dashboard1/galery');
						return;
					}
				}
			}
		}
		// =========================================================
		// SELESAI CEK FOTO DUPLIKAT
		// =========================================================

		if ($galery == '') {
		} else {
			$config['upload_path']  = './assets/galery';
			$config['allowed_types'] = 'jpg|png|gif';
			$config['encrypt_name'] = true;

			$this->load->library('upload', $config);

			if (!$this->upload->do_upload('galery')) {
				$this->session->set_flashdata('flash_gagal', 'Di Upload');
				redirect('dashboard1/galery');
			} else {
				$galery = $this->upload->data();

				$config['image_library'] = 'gd2';
				$config['source_image'] = './assets/galery' . $galery;
				$config['create_thumb'] = FALSE;
				$config['maintain_ratio'] = FALSE;
				$config['width'] = 500;
				$config['height'] = 500;
				$config['new_image'] = './assets/galery' . $galery;

				$this->load->library('image_lib', $config);
				$this->image_lib->resize();

				$galery = $this->upload->data('file_name');
			}
		}

		$data = array(
			'id_ortu' => $id_ortu,
			'caption' => $caption,
			'galery' => $galery
		);

		$this->m_anak1->form_simpan_galery($data, 'galery');

		$this->session->set_flashdata('flash', 'Di Upload');
		redirect('dashboard1/tampil_galery');
	}

	function tampil_galery()
	{
		$username = $this->session->nama;

		$data['galery'] = $this->db->query("SELECT galery.*, ortu.username 
		from galery 
		INNER JOIN ortu ON ortu.id_ortu=galery.id_ortu 
		WHERE ortu.username='" . $username . "'")->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_galery', $data);
		$this->load->view('templateLTE1/footer');
	}

	// PROSES HAPUS DATA
	function hapus_galery($id_galery)
	{
		// Ambil data galeri terlebih dahulu
		$galery = $this->db
			->where('id_galery', $id_galery)
			->get('galery')
			->row();

		// Pastikan data ditemukan
		if (!$galery) {
			$this->session->set_flashdata('error', 'Data galeri tidak ditemukan');
			redirect('dashboard1/tampil_galery');
			return;
		}

		// Lokasi file foto
		$file = FCPATH . 'assets/galery/' . $galery->galery;

		// Hapus file foto
		if (!empty($galery->galery) && file_exists($file)) {
			unlink($file);
		}

		// Hapus data dari database
		$where = array(
			'id_galery' => $id_galery
		);

		$this->m_anak1->hapus_data($where, 'galery');
		$this->session->set_flashdata('flash', 'Di Hapus');
		redirect('dashboard1/tampil_galery');
	}

	public function download_galery($id_galery)
	{
		$galery = $this->db->where('id_galery', $id_galery)->get('galery')->row();

		if (!$galery) {
			show_404();
			return;
		}

		$file_path = FCPATH . 'assets/galery/' . $galery->galery;

		if (!file_exists($file_path)) {
			show_404();
			return;
		}

		$extension = pathinfo($galery->galery, PATHINFO_EXTENSION);
		$caption = !empty($galery->caption) ? url_title($galery->caption, '_', true)			: 'foto_kegiatan';
		$file_name = $caption . '.' . $extension;
		$this->load->helper('download');
		force_download($file_name, file_get_contents($file_path));
	}

	function view_profile_cicit($id)
	{
		$data['profile_cicit'] = $this->db->query("select cicit.*, cucu.nama_cucu, cucu.menantu_cucu
		FROM cicit
		INNER JOIN cucu ON cucu.id_cucu=cicit.id_cucu
		WHERE cicit.id_cicit=$id")->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_profile_cicit', $data);
		$this->load->view('templateLTE1/footer');
	}

	function view_profile_baok($id)
	{
		$data['profile_baok'] = $this->db->query("select baok.*, cicit.nama_cicit, cicit.menantu_cicit
		FROM baok
		INNER JOIN cicit ON cicit.id_cicit=baok.id_cicit
		WHERE baok.id_baok=$id")->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_profile_baok', $data);
		$this->load->view('templateLTE1/footer');
	}

	function edit_baok($id_baok)
	{
		$where = array('id_baok' => $id_baok);
		$data['baok'] = $this->m_anak1->edit_data_cicit($where, 'baok')->result();
		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_edit_baok', $data);
		$this->load->view('templateLTE1/footer');
	}


	// PROSES UPDATE DATA BAOK
	function update_baok()
	{

		$id_baok = $this->input->post('id_baok');
		$id_cicit  = $this->input->post('id_cicit');

		$baok_lama = $this->db
			->where('id_baok', $id_baok)
			->get('baok')
			->row();

		if (!$baok_lama) {

			show_error(
				'Data cicit tidak ditemukan'
			);

			return;
		}

		$nama = trim($this->input->post('nama_baok'));
		$jenis_kelamin = trim($this->input->post('jenis_kelamin_baok'));
		$tgl_lahir_input = $this->input->post('tgl_lahir_baok');
		$menantu = trim($this->input->post('menantu_baok'));
		$alamat = trim($this->input->post('alamat_baok'));
		$NoHP =	trim($this->input->post('NoHP_baok'));
		$id_provinsi = $this->input->post('id_provinsi');
		$id_kabupaten =	$this->input->post('id_kabupaten');
		$id_kecamatan =	$this->input->post('id_kecamatan');
		$id_desa = $this->input->post('id_desa');
		$newLatitude  = trim($this->input->post('newLatitude'));
		$newLongitude = trim($this->input->post('newLongitude'));

		if (
			$nama == '' ||
			$jenis_kelamin == '' ||
			$tgl_lahir_input == '' ||
			$alamat == '' ||
			$id_provinsi == '' ||
			$id_kabupaten == '' ||
			$id_kecamatan == '' ||
			$id_desa == ''
		) {

			$this->session->set_flashdata('flash', 'Data wajib belum lengkap');
			redirect('dashboard1/edit_baok/' . $id_baok);
			return;
		}

		$tgl_lahir = date('d-M-Y', strtotime($tgl_lahir_input));
		$alamat_lama = trim($baok_lama->alamat_baok);
		$alamat_berubah = ($alamat_lama != $alamat);

		$wilayah_berubah =
			$baok_lama->id_provinsi != $id_provinsi ||
			$baok_lama->id_kabupaten != $id_kabupaten ||
			$baok_lama->id_kecamatan != $id_kecamatan ||
			$baok_lama->id_desa != $id_desa;

		// KOORDINAT DARI MARKER MAP
		$latitude  = $baok_lama->latitude;
		$longitude = $baok_lama->longitude;

		// Jika user menggeser marker
		if (
			$newLatitude !== '' && $newLongitude !== '' &&
			is_numeric($newLatitude) &&	is_numeric($newLongitude)
		) {

			$latitude  = (float) $newLatitude;
			$longitude = (float) $newLongitude;
		}

		// UPLOAD FOTO

		$config['upload_path'] = './assets/foto_baok';
		$config['allowed_types'] = 'jpg|jpeg|png|gif';
		$config['overwrite'] = true;
		$this->load->library('upload', $config);

		// DATA UTAMA

		$data = array(
			'id_baok' => $id_baok,
			'nama_baok' =>	$nama,
			'jenis_kelamin_baok' =>	$jenis_kelamin,
			'tgl_lahir_baok' =>	$tgl_lahir,
			'menantu_baok' => $menantu,
			'alamat_baok' => $alamat,
			'NoHP_baok' => $NoHP,
			'id_provinsi' => $id_provinsi,
			'id_kabupaten' => $id_kabupaten,
			'id_kecamatan' => $id_kecamatan,
			'id_desa' => $id_desa,
			'latitude' => $latitude,
			'longitude' => $longitude
		);

		// CEK UPLOAD FOTO
		if (!empty($_FILES['foto_baok']['name'])) {
			if ($this->upload->do_upload('foto_baok')) {
				$foto =	$this->upload->data('file_name');

				$data['foto_baok'] = $foto;
			} else {

				log_message(
					'error',
					'UPLOAD FOTO BAOK: ' .
						$this->upload->display_errors('', '')
				);
			}
		}

		$where = array('id_baok' => $id_baok);
		$this->m_anak1->update_data_cicit($where, $data, 'baok');
		$this->session->set_flashdata('flash', 'Data Baok berhasil diupdate');

		redirect('dashboard1/view_profile_baok/' .	$id_baok);
	}

	public function detil_cucu($id)
	{
		$data['detil_anak'] = $this->db->query("select cucu.id_cucu, cucu.id_anak, cucu.nama_cucu, cucu.menantu_cucu, cucu.alamat_cucu, NoHP_cucu, total_anak_cucu 
		FROM cucu
		INNER JOIN anak ON anak.id_anak=cucu.id_anak
		WHERE anak.id_anak=$id")->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/detail data/v_tampil_detil_cucu', $data);
		$this->load->view('templateLTE1/footer');
	}

	public function detil_cicit($id)
	{
		$data['detil_cucu'] = $this->db->query("select cicit.id_cucu, cicit.id_cicit, cicit.nama_cicit, cicit.tgl_lahir_cicit, cicit.menantu_cicit, 
		cicit.alamat_cicit, cicit.NoHP_cicit, cicit.total_anak_cicit 
		FROM cicit 
		INNER JOIN cucu ON cucu.id_cucu=cicit.id_cucu
		WHERE cucu.id_cucu=$id 
		ORDER BY cicit.tgl_lahir_cicit")->result();


		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/detail data/v_tampil_detil_cicit', $data);
		$this->load->view('templateLTE1/footer');
	}
	public function detil_baok($id)
	{
		$data['detil_cicit'] = $this->db->query("select baok.id_baok, baok.id_cicit, baok.nama_baok, baok.menantu_baok, baok.alamat_baok, baok.NoHP_baok 
		FROM baok
		INNER JOIN cicit ON cicit.id_cicit=baok.id_cicit
		WHERE cicit.id_cicit=$id")->result();


		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/detail data/v_tampil_detil_baok', $data);
		$this->load->view('templateLTE1/footer');
	}

	public function print_anak()
	{
		$username = $this->session->nama;

		// Data ortu
		$data['ortu'] = $this->db
			->where('username', $username)
			->get('ortu')
			->result();

		// Data anak
		$data['anak'] = $this->db
			->select('anak.*, ortu.nama_ortu, ortu.username, ortu.foto_ortu')
			->from('anak')
			->join('ortu', 'ortu.id_ortu = anak.id_ortu', 'inner')
			->where('ortu.username', $username)
			->get()
			->result();

		// Composer
		require_once FCPATH . 'vendor/autoload.php';

		// Dompdf
		$options = new \Dompdf\Options();
		$options->set('isHtml5ParserEnabled', true);
		$options->set('isRemoteEnabled', true);

		$dompdf = new \Dompdf\Dompdf($options);

		// Load view menjadi HTML
		$html = $this->load->view('madnasir1/detail data/print/print_anak',	$data, true);

		// Masukkan HTML ke Dompdf
		$dompdf->loadHtml($html);

		// Ukuran kertas
		$dompdf->setPaper('A4', 'portrait');

		// Render
		$dompdf->render();

		// Tampilkan PDF di browser
		$dompdf->stream('data-anak.pdf', ['Attachment' => false]);
	}

	public function print_cucu()
	{
		$username = $this->session->nama;

		// DATA ORTU
		$data['ortu'] = $this->db->where('username', $username)->get('ortu')->result();

		// DATA CUCU
		$data['cucu'] = $this->db
			->select('
				cucu.*,
				anak.nama AS nama_anak,
				anak.menantu,
				anak.alamat,
				anak.id_anak,
				ortu.nama_ortu,
				ortu.username,
				ortu.foto_ortu
			')
			->from('cucu')
			->join(
				'anak',
				'anak.id_anak = cucu.id_anak',
				'inner'
			)
			->join(
				'ortu',
				'ortu.id_ortu = anak.id_ortu',
				'inner'
			)
			->where('ortu.username', $username)
			->get()
			->result();

		// COMPOSER
		require_once FCPATH . 'vendor/autoload.php';

		// DOMPDF
		$options = new \Dompdf\Options();

		$options->set('isHtml5ParserEnabled', true);
		$options->set('isRemoteEnabled', true);

		$dompdf = new \Dompdf\Dompdf($options);

		// LOAD VIEW
		$html = $this->load->view('madnasir1/detail data/print/print_cucu',	$data, true);

		// LOAD HTML
		$dompdf->loadHtml($html);

		// KERTAS
		$dompdf->setPaper('A4', 'portrait');

		// RENDER
		$dompdf->render();

		// TAMPILKAN PDF
		$dompdf->stream('data-cucu.pdf', ['Attachment' => false]);
	}

	function print_cicit()
	{
		$username = $this->session->nama;

		$data['ortu'] = $this->db->query("select * from ortu WHERE username='" . $username . "'")->result();

		$data['cicit'] = $this->db->query("SELECT cicit.*, cucu.id_cucu, cucu.nama_cucu, cucu.menantu_cucu, anak.id_anak, anak.nama, ortu.nama_ortu, ortu.username  
		FROM cicit
		INNER JOIN cucu ON cucu.id_cucu=cicit.id_cucu
		INNER JOIN anak ON anak.id_anak=cucu.id_anak 
		INNER JOIN ortu ON ortu.id_ortu=anak.id_ortu
		WHERE ortu.username='" . $username . "'")->result();


		// COMPOSER
		require_once FCPATH . 'vendor/autoload.php';

		// DOMPDF
		$options = new \Dompdf\Options();
		$options->set('isHtml5ParserEnabled', true);
		$options->set('isRemoteEnabled', true);
		$dompdf = new \Dompdf\Dompdf($options);

		// LOAD VIEW
		$html = $this->load->view('madnasir1/detail data/print/print_cicit', $data, true);

		// LOAD HTML
		$dompdf->loadHtml($html);

		// KERTAS
		$dompdf->setPaper('A4', 'portrait');

		// RENDER
		$dompdf->render();

		// TAMPILKAN PDF
		$dompdf->stream('data-cicit.pdf', ['Attachment' => false]);
		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/detail data/print/print_cicit', $data);
	}

	function print_baok()
	{
		$username = $this->session->nama;
		$data['ortu'] = $this->db->query("select * from ortu WHERE username='" . $username . "'")->result();

		$data['baok'] = $this->db->query("SELECT baok.*, cicit.id_cicit, cicit.nama_cicit, cicit.menantu_cicit, cucu.id_cucu, cucu.nama_cucu, anak.id_anak, anak.nama, ortu.nama_ortu, ortu.username  
		FROM baok
		INNER JOIN cicit ON cicit.id_cicit=baok.id_cicit		
		INNER JOIN cucu ON cucu.id_cucu=cicit.id_cucu
		INNER JOIN anak ON anak.id_anak=cucu.id_anak 
		INNER JOIN ortu ON ortu.id_ortu=anak.id_ortu
		WHERE ortu.username='" . $username . "'")->result();

		// COMPOSER
		require_once FCPATH . 'vendor/autoload.php';

		// DOMPDF
		$options = new \Dompdf\Options();
		$options->set('isHtml5ParserEnabled', true);
		$options->set('isRemoteEnabled', true);
		$dompdf = new \Dompdf\Dompdf($options);

		// LOAD VIEW
		$html = $this->load->view('madnasir1/detail data/print/print_baok', $data, true);

		// LOAD HTML
		$dompdf->loadHtml($html);

		// KERTAS
		$dompdf->setPaper('A4', 'portrait');

		// RENDER
		$dompdf->render();

		// TAMPILKAN PDF
		$dompdf->stream('data-baok.pdf', ['Attachment' => false]);
		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/detail data/print/print_baok', $data);
	}

	public function print_detil_anak($id)
	{
		$data['print_detil_anak'] = $this->db->query("select anak.*, ortu.id_ortu,ortu.nama_ortu,ortu.pasangan 
		FROM anak
		INNER JOIN ortu ON ortu.id_ortu=anak.id_ortu
		WHERE anak.id_anak=$id")->result();

		// Composer
		require_once FCPATH . 'vendor/autoload.php';

		// Dompdf
		$options = new \Dompdf\Options();
		$options->set('isHtml5ParserEnabled', true);
		$options->set('isRemoteEnabled', true);

		$dompdf = new \Dompdf\Dompdf($options);

		// Load view menjadi HTML
		$html = $this->load->view('madnasir1/detail data/print/print_detil_anak', $data, true);

		// Masukkan HTML ke Dompdf
		$dompdf->loadHtml($html);

		// Ukuran kertas
		$dompdf->setPaper('A4', 'portrait');

		// Render
		$dompdf->render();

		// Tampilkan PDF di browser
		$dompdf->stream('data-anak.pdf', ['Attachment' => false]);
	}

	public function print_detil_cucu($id)
	{
		// Ambil nama orang tua
		$data['ortu'] = $this->db->where('id_anak', $id)->get('anak')->row();

		$data['print_detil_cucu'] = $this->db->query(
			"select cucu.*, anak.id_anak, anak.nama
		FROM cucu
		INNER JOIN anak ON anak.id_anak=cucu.id_anak
		WHERE anak.id_anak=$id"
		)->result();

		// Composer
		require_once FCPATH . 'vendor/autoload.php';

		// Dompdf
		$options = new \Dompdf\Options();
		$options->set('isHtml5ParserEnabled', true);
		$options->set('isRemoteEnabled', true);

		$dompdf = new \Dompdf\Dompdf($options);

		// Load view menjadi HTML
		$html = $this->load->view('madnasir1/detail data/print/print_detil_cucu', $data, true);

		// Masukkan HTML ke Dompdf
		$dompdf->loadHtml($html);

		// Ukuran kertas
		$dompdf->setPaper('A4', 'portrait');

		// Render
		$dompdf->render();

		// Tampilkan PDF di browser
		$dompdf->stream('data-cucu.pdf', ['Attachment' => false]);
	}

	public function print_detil_cicit($id)
	{
		// Ambil nama orang tua
		$data['ortu'] = $this->db->where('id_anak', $id)->get('anak')->row();

		$data['print_detil_cicit'] = $this->db->query("select cicit.*, anak.id_anak, anak.nama, anak.menantu, cucu.id_cucu,cucu.nama_cucu, cucu.menantu_cucu
		FROM cicit
		INNER JOIN cucu ON cucu.id_cucu=cicit.id_cucu
		INNER JOIN anak ON anak.id_anak=cucu.id_anak
		WHERE anak.id_anak=$id")->result();

		// Composer
		require_once FCPATH . 'vendor/autoload.php';

		// Dompdf
		$options = new \Dompdf\Options();
		$options->set('isHtml5ParserEnabled', true);
		$options->set('isRemoteEnabled', true);

		$dompdf = new \Dompdf\Dompdf($options);

		// Load view menjadi HTML
		$html = $this->load->view('madnasir1/detail data/print/print_detil_cicit', $data, true);

		// Masukkan HTML ke Dompdf
		$dompdf->loadHtml($html);

		// Ukuran kertas
		$dompdf->setPaper('A4', 'portrait');

		// Render
		$dompdf->render();

		// Tampilkan PDF di browser
		$dompdf->stream('data-cicit.pdf', ['Attachment' => false]);
	}

	public function print_detil_baok($id)
	{
		// Ambil nama orang tua
		$data['ortu'] = $this->db->where('id_anak', $id)->get('anak')->row();

		$data['print_detil_baok'] = $this->db->query(
			"select baok.*, anak.id_anak,anak.nama,cucu.id_cucu,cucu.nama_cucu, cicit.id_cicit, cicit.nama_cicit, cicit.menantu_cicit
		FROM baok
		INNER JOIN cicit ON cicit.id_cicit=baok.id_cicit
		INNER JOIN cucu ON cucu.id_cucu=cicit.id_cucu
		INNER JOIN anak ON anak.id_anak=cucu.id_anak
		WHERE anak.id_anak=$id"
		)->result();

		// Composer
		require_once FCPATH . 'vendor/autoload.php';

		// Dompdf
		$options = new \Dompdf\Options();
		$options->set('isHtml5ParserEnabled', true);
		$options->set('isRemoteEnabled', true);

		$dompdf = new \Dompdf\Dompdf($options);

		// Load view menjadi HTML
		$html = $this->load->view('madnasir1/detail data/print/print_detil_baok', $data, true);

		// Masukkan HTML ke Dompdf
		$dompdf->loadHtml($html);

		// Ukuran kertas
		$dompdf->setPaper('A4', 'portrait');

		// Render
		$dompdf->render();

		// Tampilkan PDF di browser
		$dompdf->stream('data-baok.pdf', ['Attachment' => false]);
	}

	function ganti_password($id_ortu)
	{
		$where = array('id_ortu' => $id_ortu);
		$data['ortu'] = $this->m_anak1->edit_data_cicit($where, 'ortu')->result();
		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_ganti_password', $data);
		$this->load->view('templateLTE1/footer');
	}

	function aksi_ubah_password()
	{
		$password1 = $this->input->post('password1');
		$password2 = $this->input->post('password2');
		$username = $this->input->post('username');
		$password = $this->input->post('password');

		if ($password1 == "" || $password2 == "" || $password == "") {
			$this->session->set_flashdata('flash_gagal', 'Password tidak boleh kosong');
			redirect(base_url('Dashboard1/profile'));
		} else if ($password1 == $password2) {
			$where = array('username' => $username);
			$cek = $this->m_login->cek_login("ortu", $where)->row_array();

			if ($cek) {
				if (password_verify($password, $cek['password'])) {
					$ubah = password_hash($password1, PASSWORD_DEFAULT);
					$data = array('password' => $ubah);
					$this->m_anak1->ubah_password($data, 'ortu');
					$this->session->set_flashdata('flash', 'Password Di Ubah');
					redirect(base_url('Dashboard1/profile'));
				} else {
					$this->session->set_flashdata('flash_gagal', 'Password gagal di ubah');
					redirect(base_url('Dashboard1/profile'));
				}
			} else {
				$this->session->set_flashdata('flash_gagal', 'Password tidak terdaftar');
				redirect(base_url('Dashboard1/profile'));
			}
		}
	}

	public function treeview()
	{
		$username = $this->session->userdata('nama');

		if (empty($username)) {
			redirect('login');
			return;
		}

		// AMBIL DATA ORTU SESUAI AKUN LOGIN
		$data['ortu'] = $this->db
			->where('username', $username)
			->get('ortu')
			->row();

		if (!$data['ortu']) {
			show_error('Data keluarga untuk akun ini belum tersedia.');
			return;
		}

		// ID ORTU
		$id_ortu = $data['ortu']->id_ortu;

		// ambil Data Anak Sesuai ID Ortu
		$data['anak'] = $this->db
			->where('id_ortu', $id_ortu)
			->get('anak')
			->result();

		// Ambil ID Anak
		$id_anak = array_column($data['anak'], 'id_anak');

		// Ambil Data Cucu Sesuai ID Anak
		if (!empty($id_anak)) {
			$data['cucu'] = $this->db
				->where_in('id_anak', $id_anak)
				->get('cucu')
				->result();
		} else {
			$data['cucu'] = [];
		}

		// Ambil ID Cucu
		$id_cucu = array_column($data['cucu'], 'id_cucu');

		// ambil data cicit
		if (!empty($id_cucu)) {

			$data['cicit'] = $this->db
				->where_in('id_cucu', $id_cucu)
				->get('cicit')
				->result();
		} else {
			$data['cicit'] = [];
		}

		// ambil id Cicit
		$id_cicit = array_column($data['cicit'], 'id_cicit');

		// Ambil Data Baok Sesuai ID Cicit
		if (!empty($id_cicit)) {

			$data['baok'] = $this->db
				->where_in('id_cicit', $id_cicit)
				->get('baok')
				->result();
		} else {

			$data['baok'] = [];
		}

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/treeview', $data);
		$this->load->view('templateLTE1/footer');
	}

	function tampil_WA($id_chat)
	{
		$username = $this->session->nama;

		$data['bani'] = $this->db->query("select * from ortu WHERE username='" . $username . "'")->result();

		$data['kontak'] = $this->db->query("select * from chat WHERE id_chat=$id_chat")->result();

		$data['inbox'] = $this->db->query("select * from inbox")->result();

		$data['outbox'] = $this->db->query("select * from chat_detail")->result();

		$data['tampil_chat'] = $this->db->query("SELECT chat.*, report.id_outbox,report.id, report.id_chat, report.device, report.target,report.message,report.stateid,report.status,report.state,report.timestamp
        
    		FROM chat
    		INNER JOIN report ON report.id_chat=chat.id_chat
    		
    		WHERE chat.id_chat='" . $id_chat . "'")->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_WA', $data);
		$this->load->view('templateLTE1/footer');
	}

	function outbox()
	{

		$id_chat = $this->input->post('id_chat');
		$no_tujuan = $this->input->post('target');
		$pesan = $this->input->post('message');

		if ($no_tujuan == "" || $pesan == "") {
			$this->session->set_flashdata('flash_gagal', 'Form Tidak Boleh Kosong');
			redirect('dashboard1/tampil_WA/');
		} else {
			$curl = curl_init();

			$token = 'WAbz8uQKGQrTNTfUrhpX';
			$kode_negara = '62';

			curl_setopt_array($curl, array(
				CURLOPT_URL => 'https://api.fonnte.com/send',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS => array(
					'target' => $no_tujuan,
					'message' => $pesan,
					'countryCode' => $kode_negara, //optional
				),

				CURLOPT_HTTPHEADER => array(
					"Authorization:  $token" //change TOKEN to your actual token
				),
			));

			$response = curl_exec($curl);

			curl_close($curl);
			$res = json_decode($response, true);
			var_dump($res);
			foreach ($res["id"] as $k => $v) {
				$target = $res["target"][$k];
				$status = $res["status"];
				date_default_timezone_set('Asia/Jakarta');
				$timestamp = date('d-m-Y H:i:s');
				$this->session->set_flashdata('flash', 'Dikirim');

				$data = array(
					'id_chat' => $id_chat,
					'id' => $v,
					'target' => $target,
					'message_out' => $pesan,
					'status' => $status,
					'tgl_kirim' => $timestamp
				);

				$this->m_anak1->input_data($data, 'chat_detail');

				// $uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
				// $uri_segments = explode('/', $uri_path);

				// $chat = $uri_segments[3];

				redirect('dashboard1/tampil_WA/' . $id_chat);
			}
		}
	}

	public function cetak_treeview()
	{
		// CEK LOGIN

		if (!$this->session->userdata('nama')) {

			redirect('dashboard1');

			return;
		}

		// USER LOGIN
		$username = $this->session->userdata('nama');

		// ORTU

		$ortu = $this->db->where('username', $username)->get('ortu')->row();

		if (!$ortu) {
			show_error('Data orang tua untuk akun ini tidak ditemukan.');
			return;
		}

		// ANAK
		$anak = $this->db->where('id_ortu', $ortu->id_ortu)->order_by('id_anak', 'ASC')
			->get('anak')
			->result();

		// CUCU
		$cucu = $this->db->select('cucu.*')->from('cucu')
			->join('anak', 'anak.id_anak = cucu.id_anak', 'inner')
			->where('anak.id_ortu', $ortu->id_ortu)
			->order_by('cucu.id_cucu', 'ASC')->get()->result();

		// CICIT

		$cicit = $this->db->select('cicit.*')->from('cicit')
			->join('cucu', 'cucu.id_cucu = cicit.id_cucu', 'inner')
			->join('anak', 'anak.id_anak = cucu.id_anak', 'inner')
			->where('anak.id_ortu',	$ortu->id_ortu)
			->order_by('cicit.id_cicit', 'ASC')->get()->result();

		// BAOK

		$baok = $this->db->select('baok.*')->from('baok')
			->join('cicit', 'cicit.id_cicit = baok.id_cicit', 'inner')
			->join('cucu', 'cucu.id_cucu = cicit.id_cucu', 'inner')
			->join('anak', 'anak.id_anak = cucu.id_anak', 'inner')
			->where('anak.id_ortu',	$ortu->id_ortu)
			->order_by('baok.id_baok', 'ASC')
			->get()
			->result();

		// DATA

		$data = array(
			'ortu'  => $ortu,
			'anak'  => $anak,
			'cucu'  => $cucu,
			'cicit' => $cicit,
			'baok'  => $baok
		);

		// LOAD VIEW

		$html = $this->load->view('madnasir1/master data/pdf_treeview',	$data, true);

		// DOMPDF
		require_once FCPATH . 'vendor/autoload.php';
		$options = new \Dompdf\Options();
		$options->set('isHtml5ParserEnabled', true);
		$options->set('isRemoteEnabled', true);
		$options->set('defaultFont', 'DejaVu Sans');

		$dompdf = new \Dompdf\Dompdf($options);
		$dompdf->loadHtml($html, 'UTF-8');
		$dompdf->setPaper('A3', 'landscape');

		$dompdf->render();

		$nama_file = 'struktur_silsilah_' .
			preg_replace('/[^A-Za-z0-9_-]/', '_', $ortu->nama_ortu) . '.pdf';

		$dompdf->stream($nama_file,	array('Attachment' => false));
	}

	function hapus_WA($id)
	{
		$where = array('id' => $id);
		$this->m_anak1->hapus_data($where, 'report');
		$this->session->set_flashdata('flash', 'Di Hapus');

		redirect('dashboard1/tampil_WA/id_chat');
	}

	function hapus_inbox($id)
	{
		$where = array('id' => $id);
		$this->m_anak1->hapus_data($where, 'inbox');
		$this->session->set_flashdata('flash', 'Di Hapus');

		redirect('dashboard1/tampil_WA/id_chat');
	}

	function tampil_kontak()
	{
		$username = $this->session->userdata('nama');
		$data['tampil_ortu'] = $this->db->query("select * from ortu WHERE username='" . $username . "'")->result();

		$data['kontak'] = $this->db->query("select * from chat")->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/kontak', $data);
		$this->load->view('templateLTE1/footer');
	}

	function tampil_chat($id)
	{
		$where = array('id_chat' => $id);
		$data['tujuan'] = $this->m_anak1->chat($where, 'chat')->result();

		$NoHP = $this->input->post('target');
		$data['chat'] = $this->db->query("select * FROM inbox WHERE sender='" . $NoHP . "'")->result();

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/chat', $data);
		$this->load->view('templateLTE1/footer');
	}

	function aksi_chat()
	{
		$id_chat = $this->input->post('id_chat');
		$no_tujuan = $this->input->post('target');
		$pesan = $this->input->post('message');


		if ($pesan == "") {
			$this->session->set_flashdata('flash_gagal', 'Form Tidak Boleh Kosong');
			redirect('dashboard1/tampil_chat');
		} else {
			$curl = curl_init();

			$token = 'WAbz8uQKGQrTNTfUrhpX';
			$kode_negara = '62';

			curl_setopt_array($curl, array(
				CURLOPT_URL => 'https://api.fonnte.com/send',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS => array(
					'target' => $no_tujuan,
					'message' => $pesan,
					'countryCode' => $kode_negara, //optional
				),

				CURLOPT_HTTPHEADER => array(
					"Authorization:  $token" //change TOKEN to your actual token
				),
			));

			$response = curl_exec($curl);

			curl_close($curl);
			$res = json_decode($response, true);
			var_dump($res);
			foreach ($res["id"] as $k => $v) {
				$target = $res["target"][$k];
				$status = $res["process"];
				date_default_timezone_set('Asia/Jakarta');
				$timestamp = date('d-m-Y H:i:s');


				$data = array(
					'id_chat' => $id_chat,
					'id' => $v,
					'target' => $no_tujuan,
					'message_out' => $pesan,
					'status' => $status,
					'tgl_kirim' => $timestamp
				);

				$this->m_anak1->input_data($data, 'chat_detail');
				$this->session->set_flashdata('flash', 'Dikirim');
				redirect('dashboard1/tampil_WA/id_chat');
			}
		}
	}

	public function tampil_backup()
	{
		// Ambil username dari session
		$username = $this->session->nama;

		if (empty($username)) {
			redirect('login');
			return;
		}

		// Bersihkan username
		$username_folder = preg_replace(
			'/[^a-zA-Z0-9_-]/',
			'_',
			$username
		);

		// Folder backup user
		$backup_dir = FCPATH
			. 'backup'
			. DIRECTORY_SEPARATOR
			. $username_folder
			. DIRECTORY_SEPARATOR;

		$data['backup_files'] = [];

		// Cek folder
		if (is_dir($backup_dir)) {

			$files = glob($backup_dir . '*.sql');

			if ($files !== false) {

				foreach ($files as $file) {

					if (is_file($file)) {

						$data['backup_files'][] = [
							'nama'    => basename($file),
							'ukuran'  => $this->format_bytes(filesize($file)),
							'tanggal' => filemtime($file)
						];
					}
				}

				// Urutkan terbaru
				usort(
					$data['backup_files'],
					function ($a, $b) {
						return $b['tanggal'] <=> $a['tanggal'];
					}
				);
			}
		}

		$this->load->view('templateLTE1/header');

		$this->load->view(
			'madnasir1/master data/v_riwayat',
			$data
		);

		$this->load->view('templateLTE1/footer');
	}

	private function format_bytes($bytes, $precision = 2)
	{
		$units = ['B', 'KB', 'MB', 'GB', 'TB'];
		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		$bytes /= pow(1024, $pow);
		return round($bytes, $precision) . ' ' . $units[$pow];
	}

	//Menampilkan Form Input Backup Database
	function form_input_backup()
	{
		$username = $this->session->nama;
		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/v_input_backup');
		$this->load->view('templateLTE1/footer');
	}

	//Proses Simpan Backup Database
	public function backup_database()
	{
		$nama_database = trim($this->input->post('nama_database', true));
		if (empty($nama_database)) {
			$this->session->set_flashdata('error', 'Nama database wajib diisi.');
			redirect('dashboard1/form_input_backup');
			return;
		}

		$nama_database = $this->input->post('nama_database');
		$username = $this->session->nama;

		if (empty($username)) {
			redirect('login');
			return;
		}

		// Bersihkan username agar aman dijadikan nama folder
		$username_folder = preg_replace(
			'/[^a-zA-Z0-9_-]/',
			'_',
			$username
		);

		$mysqldump = 'C:\xampp\mysql\bin\mysqldump.exe';

		$host = $this->db->hostname;
		$user = $this->db->username;
		$pass = $this->db->password;
		$db   = $this->db->database;

		//folder utama untuk menyimpan file backup
		$backup_root = FCPATH . 'backup' . DIRECTORY_SEPARATOR;

		$backup_dir = FCPATH . $username_folder . DIRECTORY_SEPARATOR;

		// Folder berdasarkan username
		$backup_dir = $backup_root
			. $username_folder
			. DIRECTORY_SEPARATOR;

		if (!is_dir($backup_dir)) {
			mkdir($backup_dir, 0755, true);
		}

		$filename = $username . '_' . $nama_database . '_' . date('Y-m-d_H-i-s') . '.sql';
		$filepath = $backup_dir . $filename;

		$command = '"' . $mysqldump . '"'
			. ' --host=' . escapeshellarg($host)
			. ' --user=' . escapeshellarg($user);

		if ($pass !== '') {
			$command .= ' --password=' . escapeshellarg($pass);
		}

		$command .=
			' --add-drop-table'
			. ' --add-locks'
			. ' --create-options'
			. ' --disable-keys'
			. ' --extended-insert'
			. ' --lock-tables'
			. ' --routines'
			. ' --triggers'
			. ' --events'
			. ' --single-transaction'
			. ' --skip-comments'
			. ' ' . escapeshellarg($db)
			. ' > ' . escapeshellarg($filepath)
			. ' 2>&1';

		exec($command, $output, $return_code);

		//  * Cek hasil backup
		if (
			$return_code !== 0 || !file_exists($filepath) ||
			filesize($filepath) === 0
		) {

			if (file_exists($filepath)) {
				unlink($filepath);
			}

			if (!empty($output)) {
				print_r($output);
			} else {
				echo "Tidak ada pesan error.";
			}

			exit;
		}

		$this->load->helper('download');
		$this->session->set_flashdata('flash', 'Backup database berhasil. File: ' . $filename);
		redirect('dashboard1/tampil_backup');
	}

	public function hapus_backup()
	{
		$username = $this->session->nama;

		if (empty($username)) {
			redirect('login');
			return;
		}

		$username_folder = preg_replace(
			'/[^a-zA-Z0-9_-]/',
			'_',
			$username
		);

		$filename = $this->input->post('filename', true);
		if (empty($filename)) {

			$this->session->set_flashdata('error', 'Nama file tidak ditemukan.');

			redirect('dashboard1/tampil_backup');
			return;
		}

		// Cegah path traversal
		$filename = basename($filename);

		// Hanya boleh file SQL
		if (strtolower(
			pathinfo($filename, PATHINFO_EXTENSION)
		) !== 'sql') {

			$this->session->set_flashdata('error', 'File tidak valid.');

			redirect('dashboard1/tampil_backup');
			return;
		}

		// Folder milik user
		$backup_dir = FCPATH
			. 'backup'
			. DIRECTORY_SEPARATOR
			. $username_folder
			. DIRECTORY_SEPARATOR;

		$filepath = $backup_dir . $filename;

		if (!is_file($filepath)) {

			$this->session->set_flashdata('error', 'File backup tidak ditemukan.');
			redirect('dashboard1/tampil_backup');
			return;
		}

		if (unlink($filepath)) {

			$this->session->set_flashdata('flash', 'Backup berhasil dihapus.');
		} else {

			$this->session->set_flashdata('error', 'Backup gagal dihapus.');
		}

		redirect('dashboard1/tampil_backup');
	}

	public function download_backup()
	{
		$username = $this->session->nama;

		if (empty($username)) {
			redirect('login');
			return;
		}

		// Nama folder username
		$username_folder = preg_replace(
			'/[^a-zA-Z0-9_-]/',
			'_',
			$username
		);

		// Ambil nama file dari POST
		$filename = $this->input->post('filename', true);

		if (empty($filename)) {
			$this->session->set_flashdata('error', 'Nama file tidak ditemukan.');

			redirect('dashboard1/tampil_backup');
			return;
		}

		// Cegah path traversal
		$filename = basename($filename);

		// Hanya file SQL
		if (
			strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'sql'
		) {
			$this->session->set_flashdata('error', 'File tidak valid.');
			redirect('dashboard1/tampil_backup');
			return;
		}

		// Folder backup milik username yang sedang login
		$backup_dir = FCPATH
			. 'backup'
			. DIRECTORY_SEPARATOR
			. $username_folder
			. DIRECTORY_SEPARATOR;

		$filepath = $backup_dir . $filename;

		// Pastikan file memang ada
		if (!is_file($filepath)) {
			$this->session->set_flashdata('error', 'File backup tidak ditemukan.');

			redirect('dashboard1/tampil_backup');
			return;
		}

		// Download
		$this->load->helper('download');

		force_download($filename, file_get_contents($filepath));
	}

	public function scan_anak($token)
	{
		if (empty($token)) {
			show_404();
			return;
		}

		$this->load->database();

		$anak = $this->db
			->where('qr_token', $token)
			->get('anak')
			->row();

		if (!$anak) {
			show_404();
			return;
		}

		// Kalau belum login → tampilkan profil publik
		if (!$this->session->userdata('nama')) {

			$data['anak'] = $anak;

			// Simpan URL untuk kembali setelah login
			$this->session->set_userdata(
				'redirect_after_login',
				current_url()
			);

			$this->load->view(
				'madnasir1/master data/profile_anak_public',
				$data
			);

			return;
		}

		// Kalau sudah login → langsung profil lengkap
		redirect(
			'Dashboard1/view_profile_anak/' . $anak->id_anak
		);
	}

	public function qr_anak($id)
	{
		$this->load->database();

		$anak = $this->db
			->where('id_anak', $id)
			->get('anak')
			->row();

		if (!$anak) {
			show_404();
			return;
		}

		if (empty($anak->qr_token)) {

			$token = bin2hex(random_bytes(16));

			$this->db
				->where('id_anak', $id)
				->update('anak', [
					'qr_token' => $token
				]);

			$anak->qr_token = $token;
		}

		$url = base_url(
			'Dashboard1/scan_anak/' . $anak->qr_token
		);

		$builder = new Builder(
			writer: new SvgWriter(),
			data: $url,
			size: 500,
			margin: 10
		);

		$result = $builder->build();

		header('Content-Type: image/svg+xml');

		echo $result->getString();
	}

	// Quick Insert
	public function nested_form()
	{
		$username = $this->session->userdata('nama');

		if (!($username)) {
			redirect('login');
			return;
		}

		$ortu = $this->db
			->where('username', $username)
			->get('ortu')
			->row();

		if (!$ortu) {
			show_error('Data Ortu Tidak Ditemukan');
			return;
		}

		$data['anak'] = $this->db
			->where('id_ortu', $ortu->id_ortu)
			->order_by('nama', 'ASC')
			->get('anak')
			->result();

		$data['title'] = 'Tambah Data Keluarga';

		$this->load->view('templateLTE1/header');
		$this->load->view('madnasir1/master data/nested_form', $data);
		$this->load->view('templateLTE1/footer');
	}

	public function nested_form_simpan()
	{
		$anak = $this->input->post('anak');

		if (empty($anak)) {
			$this->session->set_flashdata('error',	'Data anak belum diisi.');

			redirect('dashboard1/nested_form');
			return;
		}

		// =========================================
		// CARI DATA ORTU YANG SEDANG LOGIN
		// =========================================

		$username = $this->session->userdata('nama');

		$ortu = $this->db
			->where('username', $username)
			->get('ortu')
			->row();

		if (!$ortu) {

			$this->session->set_flashdata(
				'error',
				'Data orang tua tidak ditemukan.'
			);

			redirect('dashboard1/nested_form');
			return;
		}

		$id_ortu = $ortu->id_ortu;

		// =========================================
		// MULAI TRANSAKSI
		// =========================================

		$this->db->trans_start();


		foreach ($anak as $data_anak) {

			// =====================================
			// SIMPAN ANAK
			// =====================================

			$this->db->insert('anak', [

				'id_ortu' => $id_ortu,

				'nama' => $data_anak['nama'] ?? '',

				'menantu' => $data_anak['menantu'] ?? ''

			]);

			$id_anak = $this->db->insert_id();


			// =====================================
			// SIMPAN CUCU
			// =====================================

			if (!empty($data_anak['cucu'])) {

				foreach ($data_anak['cucu'] as $data_cucu) {

					$this->db->insert('cucu', [

						'id_anak' => $id_anak,

						'nama_cucu' =>
						$data_cucu['nama'] ?? '',

						'menantu_cucu' =>
						$data_cucu['menantu'] ?? ''

					]);

					$id_cucu = $this->db->insert_id();

					// =================================
					// SIMPAN CICIT
					// =================================

					if (!empty($data_cucu['cicit'])) {

						foreach (
							$data_cucu['cicit']
							as $data_cicit
						) {

							$this->db->insert('cicit', [
								'id_cucu' => $id_cucu,
								'nama_cicit' =>
								$data_cicit['nama'] ?? ''

							]);

							$id_cicit =	$this->db->insert_id();

							// =============================
							// SIMPAN BAOK
							// =============================

							if (!empty($data_cicit['baok'])) {

								foreach (
									$data_cicit['baok']
									as $data_baok
								) {

									$this->db->insert('baok', [

										'id_cicit' => $id_cicit,

										'nama_baok' =>
										$data_baok['nama'] ?? ''

									]);
								}
							}
						}
					}
				}
			}
		}

		// =========================================
		// SELESAIKAN TRANSAKSI
		// =========================================

		$this->db->trans_complete();

		if ($this->db->trans_status() === false) {

			$this->session->set_flashdata(
				'error',
				'Data gagal disimpan.'
			);
		} else {

			$this->session->set_flashdata(
				'success',
				'Data keluarga berhasil disimpan.'
			);
		}

		redirect('dashboard1/nested_form');
	}

	public function get_cucu($id_anak)
	{
		if (!$this->session->userdata('nama')) {
			return $this->output
				->set_status_header(401)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status' => false,
					'message' => 'Belum login'
				]));
		}


		$username = $this->session->userdata('nama');


		// Pastikan Anak tersebut milik user yang sedang login
		$anak = $this->db
			->select('anak.id_anak')
			->from('anak')
			->join(
				'ortu',
				'ortu.id_ortu = anak.id_ortu'
			)
			->where('anak.id_anak', $id_anak)
			->where('ortu.username', $username)
			->get()
			->row();


		if (!$anak) {

			return $this->output
				->set_status_header(404)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status' => false,
					'message' => 'Data Anak tidak ditemukan'
				]));
		}


		/*
     * Ambil Cucu berdasarkan id_anak
     *
     * Sesuaikan nama kolom jika struktur
     * tabel Anda berbeda.
     */

		$cucu = $this->db
			->where('id_anak', $id_anak)
			->order_by('id_cucu', 'ASC')
			->get('cucu')
			->result();


		return $this->output
			->set_content_type('application/json')
			->set_output(
				json_encode($cucu)
			);
	}

	public function get_cicit($id_cucu)
	{
		if (!$this->session->userdata('nama')) {
			return $this->output
				->set_status_header(401)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status' => false,
					'message' => 'Belum login'
				]));
		}


		$username = $this->session->userdata('nama');


		/*
     * Pastikan Cucu tersebut berada
     * di keluarga user yang sedang login.
     */

		$cucu = $this->db
			->select('
            cucu.id_cucu,
            cucu.id_anak
        ')
			->from('cucu')
			->join(
				'anak',
				'anak.id_anak = cucu.id_anak'
			)
			->join(
				'ortu',
				'ortu.id_ortu = anak.id_ortu'
			)
			->where('cucu.id_cucu', $id_cucu)
			->where('ortu.username', $username)
			->get()
			->row();


		if (!$cucu) {

			return $this->output
				->set_status_header(404)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status' => false,
					'message' => 'Data Cucu tidak ditemukan'
				]));
		}


		$data = $this->db
			->where('id_cucu', $id_cucu)
			->order_by('id_cicit', 'ASC')
			->get('cicit')
			->result();


		return $this->output
			->set_content_type('application/json')
			->set_output(
				json_encode($data)
			);
	}

	public function nested_form_susulan()
	{
		if (!$this->session->userdata('nama')) {
			redirect('login');
		}


		$username = $this->session->userdata('nama');


		/*
     * Cari ortu berdasarkan user login
     */

		$ortu = $this->db
			->where('username', $username)
			->get('ortu')
			->row();


		if (!$ortu) {
			show_error(
				'Data orang tua tidak ditemukan.'
			);
		}


		$jenis =
			$this->input->post(
				'jenis_susulan',
				true
			);


		/*
     * =====================================================
     * TAMBAH CUCU
     * =====================================================
     */

		if ($jenis === 'cucu') {

			$id_anak =
				$this->input->post(
					'id_anak',
					true
				);


			$nama =
				$this->input->post(
					'nama_cucu',
					true
				);


			$menantu =
				$this->input->post(
					'menantu_cucu',
					true
				);


			if (!$id_anak || !$nama) {

				$this->session->set_flashdata(
					'error',
					'Data Anak dan nama Cucu harus diisi.'
				);

				redirect(
					'dashboard1/nested_form'
				);

				return;
			}


			/*
         * Pastikan Anak milik user yang login
         */

			$anak = $this->db
				->where('id_anak', $id_anak)
				->where('id_ortu', $ortu->id_ortu)
				->get('anak')
				->row();


			if (!$anak) {

				$this->session->set_flashdata(
					'error',
					'Anak tidak ditemukan.'
				);

				redirect('dashboard1/nested_form');

				return;
			}

			$insert = [
				'id_anak' => $id_anak,
				'nama_cucu' => $nama,
				'menantu_cucu' => $menantu
			];

			$this->db->insert('cucu', $insert);

			if ($this->db->affected_rows() > 0) {

				$this->session->set_flashdata(
					'success',
					'Data Cucu berhasil ditambahkan.'
				);
			} else {

				$this->session->set_flashdata(
					'error',
					'Data Cucu gagal ditambahkan.'
				);
			}

			redirect('dashboard1/nested_form');
			return;
		}

		//  TAMBAH CICIT
		if ($jenis === 'cicit') {
			$id_cucu = $this->input->post('id_cucu', true);
			$nama =	$this->input->post('nama_cicit', true);

			if (!$id_cucu || !$nama) {
				$this->session->set_flashdata(
					'error',
					'Data Cucu dan nama Cicit harus diisi.'
				);
				redirect('dashboard1/nested_form');
				return;
			}

			$valid = $this->db
				->select('cucu.id_cucu')
				->from('cucu')
				->join(
					'anak',
					'anak.id_anak = cucu.id_anak'
				)
				->where(
					'cucu.id_cucu',
					$id_cucu
				)
				->where(
					'anak.id_ortu',
					$ortu->id_ortu
				)
				->get()
				->row();


			if (!$valid) {

				$this->session->set_flashdata(
					'error',
					'Data Cucu tidak ditemukan.'
				);

				redirect(
					'dashboard1/nested_form'
				);

				return;
			}


			$insert = [
				'id_cucu' => $id_cucu,
				'nama_cicit' => $nama
			];


			$this->db->insert(
				'cicit',
				$insert
			);


			if ($this->db->affected_rows() > 0) {

				$this->session->set_flashdata(
					'success',
					'Data Cicit berhasil ditambahkan.'
				);
			} else {

				$this->session->set_flashdata(
					'error',
					'Data Cicit gagal ditambahkan.'
				);
			}


			redirect(
				'dashboard1/nested_form'
			);

			return;
		}



		/*
     * =====================================================
     * TAMBAH BAOK
     * =====================================================
     */

		if ($jenis === 'baok') {

			$id_cicit =
				$this->input->post(
					'id_cicit',
					true
				);


			$nama =
				$this->input->post(
					'nama_baok',
					true
				);


			if (!$id_cicit || !$nama) {

				$this->session->set_flashdata(
					'error',
					'Data Cicit dan nama Baok harus diisi.'
				);

				redirect(
					'dashboard1/nested_form'
				);

				return;
			}

			$valid = $this->db
				->select('cicit.id_cicit')
				->from('cicit')
				->join(
					'cucu',
					'cucu.id_cucu = cicit.id_cucu'
				)
				->join(
					'anak',
					'anak.id_anak = cucu.id_anak'
				)
				->where(
					'cicit.id_cicit',
					$id_cicit
				)
				->where(
					'anak.id_ortu',
					$ortu->id_ortu
				)
				->get()
				->row();

			if (!$valid) {

				$this->session->set_flashdata(
					'error',
					'Data Cicit tidak ditemukan.'
				);

				redirect('dashboard1/nested_form');
				return;
			}

			$insert = [
				'id_cicit' => $id_cicit,
				'nama_baok' => $nama
			];

			$this->db->insert('baok', $insert);
			if ($this->db->affected_rows() > 0) {

				$this->session->set_flashdata(
					'success',
					'Data Baok berhasil ditambahkan.'
				);
			} else {

				$this->session->set_flashdata(
					'error',
					'Data Baok gagal ditambahkan.'
				);
			}

			redirect('dashboard1/nested_form');
			return;
		}

		$this->session->set_flashdata('error', 'Jenis data susulan tidak valid.');
		redirect('dashboard1/nested_form');
	}
}
