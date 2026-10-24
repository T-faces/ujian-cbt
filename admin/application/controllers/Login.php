<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_Admin', 'm_admin');
    }

    public function index()
    {
        if ($this->session->userdata('status')) {
            redirect('');
            return;
        }

        $this->load->view('template/login');
    }

    public function actlogin()
    {
        if (strtoupper($this->input->method(TRUE)) !== 'POST') {
            show_error('Metode permintaan tidak diizinkan.', 405);
            return;
        }

        $username = trim((string) $this->input->post('username', TRUE));
        $password = (string) $this->input->post('password');
        $hak = (string) $this->input->post('akses', TRUE);

        if ($username === '' || $password === '' || !in_array($hak, array('admin', 'guru'), TRUE)) {
            $this->login_failed();
            return;
        }

        if ($hak === 'admin') {
            $query = $this->m_admin->login_admin(array('username' => $username));
            $table = 'admin';
            $idField = 'id_admin';
            $record = $query->row_array();
        } else {
            $query = $this->m_admin->login_guru(array('guru.username' => $username));
            $table = 'guru';
            $idField = 'id_guru';
            $record = $query->row_array();
        }

        if (!$record || !$this->password_matches($password, isset($record['password']) ? $record['password'] : '')) {
            $this->login_failed();
            return;
        }

        // Upgrade legacy plaintext passwords to password_hash() after a successful login.
        if (!password_get_info($record['password'])['algo']) {
            $this->m_admin->update_passwd(
                $table,
                array('password' => password_hash($password, PASSWORD_DEFAULT)),
                array($idField => $record[$idField])
            );
        }

        // Rotate the session identifier to reduce session-fixation risk.
        if (method_exists($this->session, 'sess_regenerate')) {
            $this->session->sess_regenerate(TRUE);
        }

        $data = array(
            'id' => $record[$idField],
            'nama' => $record['nama'],
            'username' => $record['username'],
            'status' => $hak
        );

        if ($hak === 'guru') {
            $data['mapel'] = isset($record['mapel']) ? $record['mapel'] : '';
        }

        $this->session->set_userdata($data);
        redirect('');
    }

    private function password_matches($plainPassword, $storedPassword)
    {
        if ($storedPassword === '') {
            return FALSE;
        }

        // Support current hashes and legacy plaintext records during migration.
        $info = password_get_info($storedPassword);
        if (!empty($info['algo'])) {
            return password_verify($plainPassword, $storedPassword);
        }

        return hash_equals($storedPassword, $plainPassword);
    }

    private function login_failed()
    {
        $this->session->set_flashdata('gagal', 'Username / Password salah');
        redirect('login');
    }
}
