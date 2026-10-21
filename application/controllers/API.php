<?php
defined('BASEPATH') or exit('No direct script access allowed');

class API extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->output->set_content_type('application/json');
	}

	private function cek_api_key()
	{
		$api_key = $this->input->get_request_header('X-API-KEY');

		if (empty($api_key)) {
			$this->output
				->set_status_header(401)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status' => false,
					'message' => 'API Key tidak ditemukan'
				]));

			return false;
		}

		$config_key = $this->config->item('api_key');

		if (!hash_equals($config_key, $api_key)) {
			$this->output
				->set_status_header(403)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status' => false,
					'message' => 'API Key tidak valid'
				]));

			return false;
		}

		return true;
	}

	private function cors()
	{
		$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

		$allowed_origins = [
			'http://localhost:5173',
			'http://127.0.0.1:5173',
		];

		if (in_array($origin, $allowed_origins)) {
			$this->output
				->set_header("Access-Control-Allow-Origin: {$origin}")
				->set_header("Access-Control-Allow-Credentials: true");
		}

		$this->output
			->set_header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS')
			->set_header('Access-Control-Allow-Headers: Content-Type, X-API-KEY, Authorization')
			->set_header('Access-Control-Max-Age: 86400');
	}

	public function dashboard()
	{
		if (!$this->cek_api_key()) {
			return;
		}

		$username = $this->input->get('username', TRUE);

		if (empty($username)) {
			return $this->output
				->set_status_header(400)
				->set_output(json_encode([
					'status'  => false,
					'message' => 'Username wajib diisi'
				]));
		}

		// DATA ORTU
		$ortu = $this->db
			->where('username', $username)
			->get('ortu')
			->row();

		if (!$ortu) {
			return $this->output
				->set_status_header(404)
				->set_output(json_encode([
					'status'  => false,
					'message' => 'Data user tidak ditemukan'
				]));
		}

		$id_ortu = $ortu->id_ortu;

		// JUMLAH ANAK
		$jumlah_anak = $this->db
			->where('id_ortu', $id_ortu)
			->count_all_results('anak');

		// AMBIL ID ANAK
		$anak = $this->db
			->select('id_anak')
			->where('id_ortu', $id_ortu)
			->get('anak')
			->result();

		$id_anak = [];
		foreach ($anak as $row) {
			$id_anak[] = $row->id_anak;
		}

		// JUMLAH CUCU
		$jumlah_cucu = 0;
		$id_cucu = [];

		if (!empty($id_anak)) {
			$jumlah_cucu = $this->db
				->where_in('id_anak', $id_anak)
				->count_all_results('cucu');

			// Ambil ID cucu
			$cucu = $this->db
				->select('id_cucu')
				->where_in('id_anak', $id_anak)
				->get('cucu')
				->result();

			foreach ($cucu as $row) {
				$id_cucu[] = $row->id_cucu;
			}
		}

		// JUMLAH CICIT
		$jumlah_cicit = 0;
		$id_cicit = [];

		if (!empty($id_cucu)) {

			$jumlah_cicit = $this->db
				->where_in('id_cucu', $id_cucu)
				->count_all_results('cicit');

			// Ambil ID cicit
			$cicit = $this->db
				->select('id_cicit')
				->where_in('id_cucu', $id_cucu)
				->get('cicit')
				->result();

			foreach ($cicit as $row) {
				$id_cicit[] = $row->id_cicit;
			}
		}

		// JUMLAH BAOK
		$jumlah_baok = 0;
		$id_baok = [];

		if (!empty($id_cicit)) {

			$jumlah_baok = $this->db
				->where_in('id_cicit', $id_cicit)
				->count_all_results('baok');

			// Ambil ID baok
			$baok = $this->db
				->select('id_baok')
				->where_in('id_cicit', $id_cicit)
				->get('baok')
				->result();

			foreach ($baok as $row) {
				$id_baok[] = $row->id_baok;
			}
		}

		// LOKASI ANAK
		$jumlah_lokasi = 0;

		if (!empty($id_anak)) {

			$jumlah_lokasi += $this->db
				->where_in('id_anak', $id_anak)
				->where('latitude IS NOT NULL', NULL, FALSE)
				->where('longitude IS NOT NULL', NULL, FALSE)
				->where('latitude !=', '')
				->where('longitude !=', '')
				->count_all_results('anak');
		}

		// LOKASI CUCU
		if (!empty($id_cucu)) {

			$jumlah_lokasi += $this->db
				->where_in('id_cucu', $id_cucu)
				->where('latitude IS NOT NULL', NULL, FALSE)
				->where('longitude IS NOT NULL', NULL, FALSE)
				->where('latitude !=', '')
				->where('longitude !=', '')
				->count_all_results('cucu');
		}

		// LOKASI CICIT
		if (!empty($id_cicit)) {

			$jumlah_lokasi += $this->db
				->where_in('id_cicit', $id_cicit)
				->where('latitude IS NOT NULL', NULL, FALSE)
				->where('longitude IS NOT NULL', NULL, FALSE)
				->where('latitude !=', '')
				->where('longitude !=', '')
				->count_all_results('cicit');
		}

		// LOKASI BAOK
		if (!empty($id_baok)) {

			$jumlah_lokasi += $this->db
				->where_in('id_baok', $id_baok)
				->where('latitude IS NOT NULL', NULL, FALSE)
				->where('longitude IS NOT NULL', NULL, FALSE)
				->where('latitude !=', '')
				->where('longitude !=', '')
				->count_all_results('baok');
		}

		// RESPONSE
		return $this->output
			->set_status_header(200)
			->set_output(json_encode([
				'status' => true,
				'data' => [
					'id_ortu' => $id_ortu,
					'nama' => $ortu->nama_ortu,
					'pasangan' => $ortu->pasangan,
					'alamat' => $ortu->alamat_ortu,
					'NoHP' => $ortu->NoHP_ortu,
					'username' => $ortu->username,
					'jumlah_anak' => $jumlah_anak,
					'jumlah_cucu' => $jumlah_cucu,
					'jumlah_cicit' => $jumlah_cicit,
					'jumlah_baok' => $jumlah_baok,
					'jumlah_lokasi' => $jumlah_lokasi
				]
			]));
	}

	public function login()
	{
		// Cek API Key
		if (!$this->cek_api_key()) {
			return;
		}

		$username = $this->input->post('username', TRUE);
		$password = $this->input->post('password', TRUE);

		if (empty($username) || empty($password)) {

			return $this->output
				->set_status_header(400)
				->set_output(json_encode([
					'status'  => false,
					'message' => 'Username dan password wajib diisi'
				]));
		}

		$ortu = $this->db
			->where('username', $username)
			->where('aktif', 1)
			->get('ortu')
			->row();

		if (!$ortu) {

			return $this->output
				->set_status_header(401)
				->set_output(json_encode([
					'status'  => false,
					'message' => 'Username atau password salah'
				]));
		}

		// Cek password bcrypt
		if (!password_verify($password, $ortu->password)) {

			return $this->output
				->set_status_header(401)
				->set_output(json_encode([
					'status'  => false,
					'message' => 'Username atau password salah'
				]));
		}

		return $this->output->set_status_header(200)
			->set_output(json_encode([
				'status'  => true,
				'message' => 'Login berhasil',

				'data' => [
					'id_ortu'       => $ortu->id_ortu,
					'nama_ortu'     => $ortu->nama_ortu,
					'username'      => $ortu->username,
					'jenis_kelamin' => $ortu->jenis_kelamin_ortu,
					'foto'          => $ortu->foto_ortu,
					'aktif'         => $ortu->aktif
				]
			]));
	}

	public function peta()
	{
		if (!$this->cek_api_key()) {
			return;
		}

		// AMBIL USERNAME
		$username = $this->input->get('username', TRUE);
		if (empty($username)) {
			return $this->output
				->set_status_header(400)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status'  => false,
					'message' => 'Username wajib diisi'
				]));
		}


		// DATA ORTU
		$ortu = $this->db
			->where('username', $username)
			->get('ortu')
			->row();

		if (!$ortu) {

			return $this->output
				->set_status_header(404)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status'  => false,
					'message' => 'Data user tidak ditemukan'
				]));
		}


		$id_ortu = $ortu->id_ortu;


		// ==========================================
		// ARRAY LOKASI
		// ==========================================

		$lokasi = [];


		// ==========================================
		// AMBIL DATA ANAK
		// ==========================================

		$anak = $this->db
			->where('id_ortu', $id_ortu)
			->get('anak')
			->result();


		$id_anak = [];

		foreach ($anak as $row) {

			$id_anak[] = $row->id_anak;


			// ======================================
			// LOKASI ANAK
			// ======================================

			if (
				$row->latitude !== NULL &&
				$row->longitude !== NULL &&
				$row->latitude !== '' &&
				$row->longitude !== ''
			) {

				$lokasi[] = [
					'id'        => $row->id_anak,
					'nama'      => $row->nama,
					'generasi'  => 'Anak',
					'latitude'  => (float) $row->latitude,
					'longitude' => (float) $row->longitude
				];
			}
		}


		// ==========================================
		// AMBIL DATA CUCU
		// ==========================================

		$cucu = [];

		if (!empty($id_anak)) {

			$cucu = $this->db
				->where_in('id_anak', $id_anak)
				->get('cucu')
				->result();
		}


		$id_cucu = [];

		foreach ($cucu as $row) {

			$id_cucu[] = $row->id_cucu;


			// ======================================
			// LOKASI CUCU
			// ======================================

			if (
				$row->latitude !== NULL &&
				$row->longitude !== NULL &&
				$row->latitude !== '' &&
				$row->longitude !== ''
			) {

				$lokasi[] = [
					'id'        => $row->id_cucu,
					'nama'      => $row->nama_cucu,
					'generasi'  => 'Cucu',
					'latitude'  => (float) $row->latitude,
					'longitude' => (float) $row->longitude
				];
			}
		}


		// AMBIL DATA CICIT
		$cicit = [];
		if (!empty($id_cucu)) {
			$cicit = $this->db
				->where_in('id_cucu', $id_cucu)
				->get('cicit')
				->result();
		}

		$id_cicit = [];
		foreach ($cicit as $row) {
			$id_cicit[] = $row->id_cicit;
			// LOKASI CICIT

			if (
				$row->latitude !== NULL &&
				$row->longitude !== NULL &&
				$row->latitude !== '' &&
				$row->longitude !== ''
			) {

				$lokasi[] = [
					'id'        => $row->id_cicit,
					'nama'      => $row->nama_cicit,
					'generasi'  => 'Cicit',
					'latitude'  => (float) $row->latitude,
					'longitude' => (float) $row->longitude
				];
			}
		}


		// ==========================================
		// AMBIL DATA BAOK
		// ==========================================

		$baok = [];

		if (!empty($id_cicit)) {

			$baok = $this->db
				->where_in('id_cicit', $id_cicit)
				->get('baok')
				->result();
		}


		$id_baok = [];

		foreach ($baok as $row) {

			$id_baok[] = $row->id_baok;


			// ======================================
			// LOKASI BAOK
			// ======================================

			if (
				$row->latitude !== NULL &&
				$row->longitude !== NULL &&
				$row->latitude !== '' &&
				$row->longitude !== ''
			) {

				$lokasi[] = [
					'id'        => $row->id_baok,
					'nama'      => $row->nama_baok,
					'generasi'  => 'Baok',
					'latitude'  => (float) $row->latitude,
					'longitude' => (float) $row->longitude
				];
			}
		}


		// ==========================================
		// RESPONSE API
		// ==========================================

		return $this->output
			->set_status_header(200)
			->set_content_type('application/json')
			->set_output(json_encode([
				'status'  => true,

				'message' => 'Data lokasi berhasil diambil',

				'jumlah'  => count($lokasi),

				'data'    => $lokasi
			]));
	}

	public function anak()
	{
		if (!$this->cek_api_key()) {
			return;
		}

		$username = $this->input->get('username', TRUE);

		if (empty($username)) {
			return $this->output
				->set_status_header(400)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status' => false,
					'message' => 'Username wajib diisi'
				]));
		}

		// CARI ORTU BERDASARKAN USERNAME
		$ortu = $this->db
			->where('username', $username)
			->get('ortu')
			->row();

		if (!$ortu) {
			return $this->output
				->set_status_header(404)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status' => false,
					'message' => 'Data user tidak ditemukan'
				]));
		}

		// DATA ANAK
		$anak = $this->db
			->where('id_ortu', $ortu->id_ortu)
			->order_by('id_anak', 'ASC')
			->get('anak')
			->result();

		// RESPONSE
		return $this->output
			->set_status_header(200)
			->set_content_type('application/json')
			->set_output(json_encode([
				'status' => true,
				'message' => 'Data anak berhasil diambil',
				'jumlah' => count($anak),
				'data' => $anak
			]));
	}

	public function detail_anak()
	{
		if (!$this->cek_api_key()) {
			return;
		}

		$id_anak = $this->input->get('id_anak');

		if (!$id_anak) {
			return $this->output
				->set_content_type('application/json')
				->set_status_header(400)
				->set_output(json_encode([
					'status' => false,
					'message' => 'ID anak wajib diisi'
				]));
		}

		$anak = $this->db
			->where('id_anak', $id_anak)
			->get('anak')
			->row();

		if (!$anak) {
			return $this->output
				->set_content_type('application/json')
				->set_status_header(404)
				->set_output(json_encode([
					'status' => false,
					'message' => 'Data anak tidak ditemukan'
				]));
		}

		return $this->output
			->set_content_type('application/json')
			->set_output(json_encode([
				'status' => true,
				'data' => $anak
			]));
	}

	public function keluarga_anak()
	{
		if (!$this->cek_api_key()) {
			return;
		}

		$id_anak = $this->input->get('id_anak');

		if (!$id_anak) {
			return $this->output
				->set_status_header(400)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status' => false,
					'message' => 'id_anak wajib diisi'
				]));
		}

		// DATA ANAK
		$anak = $this->db
			->where('id_anak', $id_anak)
			->get('anak')
			->row_array();

		if (!$anak) {
			return $this->output
				->set_status_header(404)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status' => false,
					'message' => 'Data anak tidak ditemukan'
				]));
		}

		// DATA CUCU
		$cucu = $this->db
			->where('id_anak', $id_anak)
			->get('cucu')
			->result_array();

		// DATA CICIT
		$cicit = [];
		foreach ($cucu as $item) {
			if (!empty($item['id_cucu'])) {
				$data_cicit = $this->db
					->where('id_cucu', $item['id_cucu'])
					->get('cicit')
					->result_array();

				$cicit = array_merge($cicit, $data_cicit);
			}
		}

		// DATA BAOK
		$baok = [];
		foreach ($cicit as $item) {

			if (!empty($item['id_cicit'])) {
				$data_baok = $this->db
					->where('id_cicit', $item['id_cicit'])
					->get('baok')
					->result_array();
				$baok = array_merge($baok, $data_baok);
			}
		}

		return $this->output
			->set_content_type('application/json')
			->set_output(json_encode([
				'status' => true,
				'anak' => $anak,
				'cucu' => $cucu,
				'cicit' => $cicit,
				'baok' => $baok
			]));
	}

	public function cucu()
	{
		if (!$this->cek_api_key()) {
			return;
		}

		$username = $this->input->get('username', TRUE);
		// CEK USERNAME
		if (empty($username)) {

			return $this->output
				->set_status_header(400)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status'  => false,
					'message' => 'Username wajib diisi'
				]));
		}

		// CARI DATA ORTU
		$ortu = $this->db
			->where('username', $username)
			->get('ortu')
			->row();

		if (!$ortu) {

			return $this->output
				->set_status_header(404)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status'  => false,
					'message' => 'Data user tidak ditemukan'
				]));
		}

		// AMBIL DATA CUCU + NAMA ANAK
		$this->db->select('
        cucu.*,
        anak.nama AS nama_ortu_cucu,
        anak.menantu AS pasangan_ortu
    ');

		$this->db->from('cucu');
		$this->db->join(
			'anak',
			'anak.id_anak = cucu.id_anak',
			'left'
		);

		$this->db->where('anak.id_ortu', $ortu->id_ortu);
		$this->db->order_by('cucu.id_anak', 'ASC');
		$cucu = $this->db->get()->result();

		// RESPONSE
		return $this->output
			->set_status_header(200)
			->set_content_type('application/json')
			->set_output(json_encode([

				'status'  => true,
				'message' => 'Data cucu berhasil diambil',
				'jumlah'  => count($cucu),
				'data'    => $cucu
			]));
	}

	public function cicit()
	{
		if (!$this->cek_api_key()) {
			return;
		}

		$username = $this->input->get('username', TRUE);

		// CEK USERNAME
		if (empty($username)) {

			return $this->output
				->set_status_header(400)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status'  => false,
					'message' => 'Username wajib diisi'
				]));
		}

		// CARI ORTU
		$ortu = $this->db
			->where('username', $username)
			->get('ortu')
			->row();

		if (!$ortu) {

			return $this->output
				->set_status_header(404)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status'  => false,
					'message' => 'Data user tidak ditemukan'
				]));
		}

		// AMBIL CICIT + NAMA CUCU + NAMA ANAK
		$this->db->select('
        cicit.*,
        cucu.nama_cucu AS nama_ortu_cicit,
        cucu.menantu_cucu AS pasangan_ortu_cicit,
        anak.nama AS nama_kakek_nenek,
        anak.menantu AS pasangan_kakek_nenek
    ');

		$this->db->from('cicit');

		// CICIT → CUCU
		$this->db->join('cucu',	'cucu.id_cucu = cicit.id_cucu', 'left');

		// CUCU → ANAK
		$this->db->join(
			'anak',
			'anak.id_anak = cucu.id_anak',
			'left'
		);

		// FILTER USER LOGIN
		$this->db->where('anak.id_ortu', $ortu->id_ortu);

		// URUT NAMA
		$this->db->order_by('anak.id_anak',	'ASC');
		$cicit = $this->db->get()->result();

		// RESPONSE
		return $this->output
			->set_status_header(200)
			->set_content_type('application/json')
			->set_output(json_encode([
				'status'  => true,
				'message' => 'Data cicit berhasil diambil',
				'jumlah'  => count($cicit),
				'data'    => $cicit
			]));
	}

	public function baok()
	{
		if (!$this->cek_api_key()) {
			return;
		}

		$username = $this->input->get('username', TRUE);

		// CEK USERNAME
		if (empty($username)) {

			return $this->output
				->set_status_header(400)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status'  => false,
					'message' => 'Username wajib diisi'
				]));
		}

		// CARI ORTU
		$ortu = $this->db
			->where('username', $username)
			->get('ortu')
			->row();

		if (!$ortu) {
			return $this->output
				->set_status_header(404)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status'  => false,
					'message' => 'Data user tidak ditemukan'
				]));
		}

		// AMBIL DATA BAOK
		// + CICIT
		// + CUCU
		// + ANAK

		$this->db->select('
        baok.*,
        cicit.nama_cicit AS nama_ortu_baok,
        cicit.menantu_cicit AS menantu_cicit,
        cucu.nama_cucu AS nama_kakek_baok,
        anak.nama AS nama_buyut_baok
    ');

		$this->db->from('baok');

		// BAOK → CICIT
		$this->db->join(
			'cicit',
			'cicit.id_cicit = baok.id_cicit',
			'left'
		);

		// CICIT → CUCU
		$this->db->join(
			'cucu',
			'cucu.id_cucu = cicit.id_cucu',
			'left'
		);

		// CUCU → ANAK
		$this->db->join(
			'anak',
			'anak.id_anak = cucu.id_anak',
			'left'
		);

		// FILTER USER LOGIN

		$this->db->where(
			'anak.id_ortu',
			$ortu->id_ortu
		);

		// URUT NAMA
		$this->db->order_by('cicit.id_cicit', 'ASC');
		$this->db->order_by('baok.id_baok', 'ASC');
		$baok = $this->db->get()->result();

		// RESPONSE
		return $this->output
			->set_status_header(200)
			->set_content_type('application/json')
			->set_output(json_encode([

				'status'  => true,
				'message' => 'Data baok berhasil diambil',
				'jumlah'  => count($baok),
				'data'    => $baok

			]));
	}

	public function silsilah()
	{
		if (!$this->cek_api_key()) {
			return;
		}

		$username = $this->input->get('username', TRUE);
		// CEK USERNAME
		if (empty($username)) {

			return $this->output
				->set_status_header(400)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status' => false,
					'message' => 'Username wajib diisi'
				]));
		}

		// DATA ORTU
		$ortu = $this->db
			->where('username', $username)
			->get('ortu')
			->row();

		if (!$ortu) {

			return $this->output
				->set_status_header(404)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status' => false,
					'message' => 'Data user tidak ditemukan'
				]));
		}

		// AMBIL ANAK
		$anak = $this->db
			->where('id_ortu', $ortu->id_ortu)
			->order_by('id_anak', 'ASC')
			->get('anak')
			->result();

		// ==========================================
		// BANGUN POHON
		// ==========================================

		$pohon = [];


		foreach ($anak as $a) {

			// --------------------------------------
			// AMBIL CUCU DARI ANAK INI
			// --------------------------------------

			$cucu = $this->db
				->where('id_anak', $a->id_anak)
				->order_by('id_cucu', 'ASC')
				->get('cucu')
				->result();


			$data_cucu = [];

			foreach ($cucu as $c) {

				// ----------------------------------
				// AMBIL CICIT DARI CUCU INI
				// ----------------------------------

				$cicit = $this->db
					->where('id_cucu', $c->id_cucu)
					->order_by('id_cicit', 'ASC')
					->get('cicit')
					->result();


				$data_cicit = [];


				foreach ($cicit as $ci) {

					// ------------------------------
					// AMBIL BAOK DARI CICIT INI
					// ------------------------------

					$baok = $this->db
						->where('id_cicit', $ci->id_cicit)
						->order_by('id_baok', 'ASC')
						->get('baok')
						->result();

					$data_baok = [];

					foreach ($baok as $b) {

						$data_baok[] = $b;
					}


					// ------------------------------
					// CICIT + BAOK
					// ------------------------------

					$ci->baok = $data_baok;

					$data_cicit[] = $ci;
				}


				// ------------------------------
				// CUCU + CICIT
				// ------------------------------

				$c->cicit = $data_cicit;

				$data_cucu[] = $c;
			}


			// --------------------------------------
			// ANAK + CUCU
			// --------------------------------------

			$a->cucu = $data_cucu;

			$pohon[] = $a;
		}

		// ==========================================
		// RESPONSE
		// ==========================================

		return $this->output
			->set_status_header(200)
			->set_content_type('application/json')
			->set_output(json_encode([

				'status' => true,

				'message' => 'Data pohon silsilah berhasil diambil',

				'data' => [

					'ortu' => [
						'id_ortu' => $ortu->id_ortu,
						'nama_ortu' => $ortu->nama_ortu,
						'jenis_kelamin_ortu' => $ortu->jenis_kelamin_ortu,
						'foto_ortu' => $ortu->foto_ortu
					],

					'anak' => $pohon

				]

			]));
	}

	public function galery()
	{
		if (!$this->cek_api_key()) {
			return;
		}

		$username = $this->input->get('username');

		if (!$username) {
			return $this->output
				->set_content_type('application/json')
				->set_status_header(400)
				->set_output(json_encode([
					'status' => false,
					'message' => 'Username wajib diisi'
				]));
		}

		// Cari orang tua berdasarkan username
		$ortu = $this->db
			->where('username', $username)
			->where('aktif', 1)
			->get('ortu')
			->row();

		if (!$ortu) {
			return $this->output
				->set_content_type('application/json')
				->set_status_header(404)
				->set_output(json_encode([
					'status' => false,
					'message' => 'Data orang tua tidak ditemukan'
				]));
		}

		// Ambil galery berdasarkan id_ortu
		$galery = $this->db
			->where('id_ortu', $ortu->id_ortu)
			->order_by('id_galery', 'DESC')
			->get('galery')
			->result();

		return $this->output
			->set_content_type('application/json')
			->set_output(json_encode([
				'status' => true,
				'id_ortu' => $ortu->id_ortu,
				'nama_ortu' => $ortu->nama_ortu,
				'data' => $galery
			]));
	}

	public function silsilah_anak()
	{
		if (!$this->cek_api_key()) {
			return;
		}

		$username = $this->input->get('username', TRUE);
		$id_anak  = $this->input->get('id_anak', TRUE);

		// ==========================================
		// CEK USERNAME
		// ==========================================

		if (empty($username)) {
			return $this->output
				->set_status_header(400)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status' => false,
					'message' => 'Username wajib diisi'
				]));
		}

		// ==========================================
		// CEK ID ANAK
		// ==========================================

		if (empty($id_anak)) {
			return $this->output
				->set_status_header(400)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status' => false,
					'message' => 'ID Anak wajib diisi'
				]));
		}

		// ==========================================
		// DATA ORTU
		// ==========================================

		$ortu = $this->db
			->where('username', $username)
			->where('aktif', 1)
			->get('ortu')
			->row();

		if (!$ortu) {
			return $this->output
				->set_status_header(404)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status' => false,
					'message' => 'Data user tidak ditemukan'
				]));
		}

		// ==========================================
		// AMBIL ANAK
		// HANYA MILIK ORTU YANG LOGIN
		// ==========================================

		$anak = $this->db
			->where('id_anak', $id_anak)
			->where('id_ortu', $ortu->id_ortu)
			->get('anak')
			->row();

		if (!$anak) {
			return $this->output
				->set_status_header(404)
				->set_content_type('application/json')
				->set_output(json_encode([
					'status' => false,
					'message' => 'Data anak tidak ditemukan'
				]));
		}

		// ==========================================
		// AMBIL CUCU
		// ==========================================

		$cucu = $this->db
			->where('id_anak', $anak->id_anak)
			->order_by('id_cucu', 'ASC')
			->get('cucu')
			->result();

		$data_cucu = [];

		foreach ($cucu as $c) {

			// ======================================
			// AMBIL CICIT
			// ======================================

			$cicit = $this->db
				->where('id_cucu', $c->id_cucu)
				->order_by('id_cicit', 'ASC')
				->get('cicit')
				->result();

			$data_cicit = [];

			foreach ($cicit as $ci) {

				// ==================================
				// AMBIL BAOK
				// ==================================

				$baok = $this->db
					->where('id_cicit', $ci->id_cicit)
					->order_by('id_baok', 'ASC')
					->get('baok')
					->result();

				$data_baok = [];

				foreach ($baok as $b) {
					$data_baok[] = $b;
				}

				// ==================================
				// CICIT + BAOK
				// ==================================

				$ci->baok = $data_baok;

				$data_cicit[] = $ci;
			}

			// ======================================
			// CUCU + CICIT
			// ======================================

			$c->cicit = $data_cicit;

			$data_cucu[] = $c;
		}

		// ==========================================
		// ANAK + CUCU
		// ==========================================

		$anak->cucu = $data_cucu;

		// ==========================================
		// RESPONSE
		// ==========================================

		return $this->output
			->set_status_header(200)
			->set_content_type('application/json')
			->set_output(json_encode([

				'status' => true,

				'message' => 'Data silsilah anak berhasil diambil',

				'data' => [

					// ORANG TUA
					'ortu' => [
						'id_ortu' => $ortu->id_ortu,
						'nama_ortu' => $ortu->nama_ortu,
						'jenis_kelamin_ortu' => $ortu->jenis_kelamin_ortu,
						'foto_ortu' => $ortu->foto_ortu
					],

					// SATU ANAK
					'anak' => $anak

				]

			]));
	}
}
