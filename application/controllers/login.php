<?php

class Login extends CI_Controller
{

	function __construct()
	{
		parent::__construct();
		$this->load->model('m_login');
		$this->load->library('form_validation');
	}

	function index()
	{

		$this->load->view('login/v_login');
		// $this->load->view('templateLTE1/footer');
	}

	function aksi_login()
	{
		$username = $this->input->post('username');
		$password = $this->input->post('password');
		if ($username == "" || $password == "") {
			$this->session->set_flashdata('flash_gagal', 'Tidak Boleh Kosong');
			redirect(base_url("Login"));
		} else {
			$cek = $this->db->get_where('ortu', ['username' => $username])->row_array();

			if ($cek) {
				if ($cek['aktif'] == 1) {
					if (password_verify($password, $cek['password'])) {
						$data_session = array('nama' => $username, 'status' => "login");
						$this->session->set_userdata($data_session);
						$this->session->set_flashdata('flash', 'Login');
						redirect(base_url("Dashboard1"));
					} else {
						$this->session->set_flashdata('flash_gagal', ' kosong');
						redirect(base_url("login"));
					}
				} else {
					$this->session->set_flashdata('flash_gagal1', 'Aktivasi dahulu');
					redirect(base_url("login"));
				}
			}
		}
	}

	function registrasi()
	{
		$this->load->view('templateLTE1/header');
		$this->load->view('login/registrasi');
		$this->load->view('templateLTE1/footer');
	}

	function aksi_registrasi()
	{
		$save = $this->m_login; //objek model
		$validation = $this->form_validation;
		$validation->set_rules($save->rules());

		if ($validation->run() == false) {
			$this->load->view('templateLTE1/header');
			$this->load->view('login/registrasi');
			$this->load->view('templateLTE1/footer');
		} else {
			$nama_ortu = $this->input->post('nama_ortu');
			$jenis_kelamin_ortu = $this->input->post('jenis_kelamin_ortu');
			$tgl_lahir_ortu = date("d-M-Y", strtotime($this->input->post('tgl_lahir_ortu')));
			$pasangan = $this->input->post('pasangan');
			$alamat_ortu = $this->input->post('alamat_ortu');
			$NoHP_ortu = $this->input->post('NoHP_ortu');
			$foto_ortu = $_FILES['foto_ortu'];
			$username = $this->input->post('username');
			$password = password_hash($this->input->post('password'), PASSWORD_DEFAULT);

			if ($foto_ortu == '') {
			} else {
				$config['upload_path'] = './assets/foto_ortu';
				$config['allowed_types'] = 'jpg|png|gif|JPEG';
				$this->load->library('upload', $config);
				if (!$this->upload->do_upload('foto_ortu')) {
					redirect('login/registrasi');
				} else {
					$foto_ortu = $this->upload->data('file_name');
				}
			}
			$data = array(
				'nama_ortu' =>			$nama_ortu,
				'jenis_kelamin_ortu' => $jenis_kelamin_ortu,
				'tgl_lahir_ortu' => 	$tgl_lahir_ortu,
				'pasangan' => $pasangan,
				'alamat_ortu' => $alamat_ortu,
				'NoHP_ortu' => $NoHP_ortu,
				'foto_ortu' => $foto_ortu,
				'username' => $username,
				'password' => $password,
				'aktif'		=> 0,
				'tgl_buat'	=> time()
			);

			$token = base64_encode(random_bytes(32));
			$user_token = [
				'email' => $username,
				'token' => $token,
				'tgl_buat' => time()
			];

			$this->m_login->input_registrasi($data, 'ortu');
			$this->db->insert('token', $user_token);
			$this->_sendEmail($token, 'verify');
			$this->session->set_flashdata('flash', ' Silakan buka email');
			redirect('login');
		}
	}
	private function _sendEmail($token, $type)
	{
		$config = [
			'protocol'     => 'smtp',
			'smtp_host'    => 'smtp.gmail.com',
			'smtp_port'    => 587,
			'smtp_user'    => 'keluargasilsilah9@gmail.com',
			'smtp_pass'    => 'eycutbzgljiaeryf',
			'smtp_crypto'  => 'tls',
			'mailtype'     => 'html',
			'charset'      => 'utf-8',
			'newline'      => "\r\n",
			'crlf'         => "\r\n",
			'wordwrap'     => TRUE
		];

		$this->load->library('email', $config);
		$this->email->initialize($config);
		$this->email->from('keluargasilsilah9@gmail.com', 'Web Silsilah');
		$this->email->to($this->input->post('username'));

		if ($type == 'verify') {

			$this->email->subject('Verifikasi Registrasi Silsilah');

			$this->email->message('Klik Link untuk verifikasi : <a href="' . base_url() . 'login/verify?username=' .
				$this->input->post('username') . '&token=' . urlencode($token) . '">Aktivasi</a>');
		} else {
			$this->email->subject('Lupa Password');

			$this->email->message('Klik Link untuk verifikasi : <a href="' . base_url() . 'login/resetPassword?username=' .
				$this->input->post('username') . '&token=' . urlencode($token) . '">Reset Password</a>');
		}

		if ($this->email->send()) {
			return true;
		} else {
			echo $this->email->print_debugger();
			die;
		}
	}

	function verify()
	{
		$username = $this->input->get('username');
		$token = $this->input->get('token');

		$user = $this->db->get_where('ortu', ['username' => $username])->row_array();

		if ($user) {
			$user_token = $this->db->get_where('token', ['token' => $token])->row_array();
			if ($user_token) {
				if (time() - $user_token['tgl_buat'] < 60 * 60 * 1) {
					$this->db->set('aktif', 1);
					$this->db->where('username', $username);
					$this->db->update('ortu');
					$this->session->set_flashdata('flash', 'Verifikasi Silakan Login');
					redirect('login');
				} else {
					$this->db->delete('ortu', ['username' => $username]);
					$this->db->delete('token', ['email' => $username]);

					$this->session->set_flashdata('flash_gagal1', 'Token Expired');
					redirect('login');
				}
			} else {
				$this->session->set_flashdata('flash_gagal1', 'Token Tidak Sah');
				redirect('login');
			}
		} else {
			$this->session->set_flashdata('flash_gagal', 'Token Epired');
			redirect('login');
		}
	}

	public function resetPassword()
	{
		$username = $this->input->get('username');
		$token = $this->input->get('token');

		$user_token = $this->db->get_where('ortu', ['username' => $username])->row_array();
		if ($user_token) {
			$user_token = $this->db->get_where('token', ['token' => $token])->row_array();
			if ($user_token) {
				$this->session->set_userdata('reset_email', $username);
				$this->ubahPassword();
			} else {
				$this->session->set_flashdata('flash_gagal', ' Token Tidak Terdaftar');

				redirect(base_url('login'));
			}
		} else {
			$this->session->set_flashdata('flash_gagal', ' Email Tidak Terdaftar');

			redirect(base_url('login'));
		}
	}

	function ubahPassword()
	{
		$data['title'] = 'Ubah Password';
		$this->load->view('templateLTE1/header', $data);
		$this->load->view('login/v_ubahpassword');
		$this->load->view('templateLTE1/footer');
	}

	function aksi_ubah_password()
	{
		$username = $this->session->userdata('reset_email');
		$password1 = $this->input->post('password1');
		$password2 = $this->input->post('password2');

		if ($password1 == "" || $password2 == "") {
			$this->session->set_flashdata('flash_gagal', 'Password tidak boleh kosong');
			redirect(base_url('login/ubahPassword'));
		} else if ($password1 == $password2) {
			$ubah = password_hash($password1, PASSWORD_DEFAULT);
			$data = array('password' => $ubah);

			$this->m_login->ubah_password($username, $data, 'ortu');
			$this->session->set_flashdata('flash', 'Password Di Ubah');
			redirect(base_url('login'));
		} else {
			$this->session->set_flashdata('flash_gagal', 'Password tidak sama');
			redirect(base_url('login'));
		}
	}

	function tampilEmail()
	{
		$data['title'] = 'Lupa Password';
		$this->load->view('templateLTE1/header', $data);
		$this->load->view('login/v_email');
		$this->load->view('templateLTE1/footer');
	}

	function konfirmEmail()
	{
		$username = $this->input->post('username');
		$user = $this->db->get_where('ortu', ['username' => $username, 'aktif' => 1])->row_array();
		if ($user) {
			$token = base64_encode(random_bytes(32));
			$user_token = [
				'email' => $username,
				'token' => $token,
				'tgl_buat' => time()
			];
			$this->db->insert('token', $user_token);
			$this->_sendEmail($token, 'forgot');
			$this->session->set_flashdata('flash', ' Silakan buka email');
			redirect(base_url('login'));
		} else {
			$this->session->set_flashdata('flash_gagal', ' Email Tidak Terdaftar');
			redirect(base_url('login/tampilEmail'));
		}
	}

	function logout()
	{
		$this->session->sess_destroy();
		redirect(base_url('login'));
	}
}
