<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_User', 'm_user');
    }

    //index
    public function index(){
        if($this->session->userdata('login')){
            redirect('');
        }
        $this->load->view('_template_login/login');
    }
	
	function actlogin(){
		if (strtoupper($this->input->method(TRUE)) !== 'POST') {
			show_error('Metode permintaan tidak diizinkan.', 405);
			return;
		}
		$nis = preg_replace("/[^0-9]/", "", (string) $this->input->post('nis', TRUE));
		$password = (string) $this->input->post('password');
		if ($nis === '' || $password === '') {
			$this->session->set_flashdata('flash', 'NIS atau Password salah !');
			redirect('login');
			return;
		}

		$siswa = $this->m_user->login(array('nis' => $nis));
		$s = $siswa->row_array();
		if (!$s || !$this->password_matches($password, isset($s['password']) ? $s['password'] : '')) {
			$this->session->set_flashdata('flash', 'NIS atau Password salah !');
			redirect('login');
			return;
		}

		// Upgrade legacy plaintext credentials after a successful login.
		$info = password_get_info($s['password']);
		if (empty($info['algo'])) {
			$this->m_user->gantipass(
				array('password' => password_hash($password, PASSWORD_DEFAULT)),
				array('id_siswa' => $s['id_siswa'])
			);
		}

		if (method_exists($this->session, 'sess_regenerate')) {
			$this->session->sess_regenerate(TRUE);
		}
		$this->session->set_userdata(array(
			'id' => $s['id_siswa'],
			'nama' => $s['nama'],
			'nis' => $s['nis'],
			'kelas' => $s['kode_kelas'],
			'login' => true
		));
		redirect('');
	}

	private function password_matches($plainPassword, $storedPassword)
	{
		if ($storedPassword === '') {
			return FALSE;
		}
		$info = password_get_info($storedPassword);
		if (!empty($info['algo'])) {
			return password_verify($plainPassword, $storedPassword);
		}
		return hash_equals($storedPassword, $plainPassword);
	}

	function logout(){
		$data = [
			'id' => '',
			'nama' => '',
			'nis' => '',
			'kelas' => '',
			'login' => false
		];
		$this->session->set_userdata($data);

		// $this->session->sess_destroy();
		redirect('login');
	}


	//Lupa Password
	//cek NIS
	function ceknis(){
		//preg_replace("/[^0-9]/", "", $var)
		$n = $this->input->post('nis');
		$nis = preg_replace("/[^0-9]/", "", $n);
		$whr = [
			'nis' => $nis
		];
		$cek = $this->m_user->ceknis($whr)->row_array();
		if (empty($cek['nis'])){
			# code...
			echo 0;
		}
		elseif (empty($cek['pertanyaan'])){
			# code...
			//echo "Anda belum mengatur pertanyaan, silahkan hubungi administrator";
			$err = ['error' => 'Anda belum mengatur pertanyaan untuk mereset password, silahkan hubungi Administrator'];
			echo json_encode($err);
		}
		else{
			echo json_encode($cek);
		}
	}
	//cek jawaban
	function cekjawab(){
		$jawaban = $this->input->post('jawaban');
		$nis = $this->input->post('nis');
		$whr = ['nis' => $nis];
		$cek = $this->m_user->cekjawab($whr)->row_array();
		if ($jawaban != $cek['jawaban']) {
			# code...
			echo 0;
		}
		else{
			echo json_encode($cek);
		}
	}
	//Reset password
	function resetpasswd(){
		$nis = $this->input->post('nis');
		$password = $this->input->post('password');
		$where = ['nis' => $nis];
		$data = ['password' => password_hash((string) $password, PASSWORD_DEFAULT)];
		if($this->m_user->resetpasswd('siswa', $where, $data)){
			echo 1;
		}
		$this->session->set_flashdata('flash', 'Password berhasil diubah, silahkan login !');
		redirect('login');
	}
}