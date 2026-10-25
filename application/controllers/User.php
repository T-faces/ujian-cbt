<?php
defined('BASEPATH') OR exit('No direct script access allowed');

date_default_timezone_set("Asia/Jakarta");

class User extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	function __construct(){
		parent::__construct();
          $this->db->query('SET time_zone="+7:00"');
		$this->load->model('M_User', 'm_user');
		if (!$this->session->userdata('login')) {
			redirect('login');
		}
	}

	//Header
	private function header($data){
		$this->load->view('_template/header', $data);
	}

	//404
	function notfound(){
		redirect('');
	}

     //Index
     public function index(){
     	$id = ['id_siswa' => $this->session->id];
     	$data['ar'] = $this->m_user->cekdefpass($id)->row_array();
     	$data['title'] = 'Ujian Berbasis Komputer';
          $data['judul'] = 'Dashboard';
          $whrkelas = ['kelas.kode_kelas' => $this->session->userdata('kelas')];
          $data['jdwlujian'] = $this->m_user->jadwal_ujian($this->session->id)->result();

     	$this->header($data);
          $this->load->view('utama');
          $this->load->view('_template/footer');
     }

     //Ujian
     public function ujian($id_ujian){
          $c = $this->m_user->cek_detil_tes(['id_ujian' => $id_ujian]);
          if ($c->num_rows() < 1) {
               # code...
               redirect('');
          }
          //def session siswa
          $data['sess_id'] = $this->session->userdata('id');
          $data['sess_nama'] = $this->session->userdata('nama');

          $whr_cek_sdh_selesai = ['id_siswa' => $data['sess_id'], 'id_ujian' => $id_ujian, 'status' => 'N'];
          $cek_sdh_selesai = $this->m_user->cek_selesai_ujian($whr_cek_sdh_selesai)->num_rows();

          if ($cek_sdh_selesai < 1) {
               # jika belum selesai ujian | ambil detil soal
               $whr_cek_detil_tes = ['id_ujian' => $id_ujian];
               $cek_detil_tes = $this->m_user->cek_detil_tes($whr_cek_detil_tes)->row();

               $whr_cek_sdh_ujian = ['id_ujian' => $id_ujian, 'id_siswa' => $data['sess_id']];
               $cek_sdh_ujian = $this->m_user->cek_sdh_ujian($whr_cek_sdh_ujian);

               if ($cek_sdh_ujian->num_rows() < 1) {
                    # code...
                    $soal_urut_ok = array();
                    //$whr_q_soal = ['mapel' => $cek_detil_tes->id_mapel];
                    $kelas = $this->db->query('SELECT kelas FROM siswa WHERE id_siswa='.$this->session->id)->row();
                    $q_soal = $this->m_user->q_soal($cek_detil_tes->id_mapel, $kelas->kelas)->result();
                    $i = 0;
                    foreach ($q_soal as $q) {
                         # code...
                         $soal_per = new stdClass();
                         $soal_per->id = $q->id_soal;
                         $soal_per->soal = $q->soal;
                         $soal_per->media = $q->media;
                         $soal_per->opsi_a = $q->opsi_a;
                         $soal_per->opsi_b = $q->opsi_b;
                         $soal_per->opsi_c = $q->opsi_c;
                         $soal_per->opsi_d = $q->opsi_d;
                         $soal_per->opsi_e = $q->opsi_e;
                         $soal_per->jawaban = $q->jawaban;

                         $soal_urut_ok[$i] = $soal_per;
                         $i++;
                    }
                    $soal_urut_ok = $soal_urut_ok;

                    $list_id_soal = '';
                    $list_jw_soal = '';
                    if (!empty($q_soal)) {
                         # code...
                         foreach ($q_soal as $w) {
                              $list_id_soal .= $w->id_soal.',';
                              $list_jw_soal .= $w->id_soal.':,';
                         }
                    }
                    $list_id_soal = substr($list_id_soal, 0, -1);
                    $list_jw_soal = substr($list_jw_soal, 0, -1);
                    $waktu_mulai = date('Y-m-d H:i:s');
                    $waktu_selesai = date('Y-m-d H:i:s', strtotime('+'.$cek_detil_tes->waktu.' minutes'));

                    $data_tmbh_ujian = [
                         'id_ujian' => $id_ujian,
                         'id_siswa' => $data['sess_id'],
                         'list_soal' => $list_id_soal,
                         'list_jawaban' => $list_jw_soal,
                         'jml_benar' => 0,
                         'nilai' => 0,
                         'tgl_mulai' => $waktu_mulai,
                         'tgl_selesai' => $waktu_selesai,
                         'status' => 'Y'
                    ];
                    $this->m_user->tambah_ujian($data_tmbh_ujian);

                    redirect($id_ujian);
               }
               else{
                    $q_ambil_soal = $this->db->query('SELECT * FROM ikut_ujian WHERE id_ujian = '.$id_ujian.' AND id_siswa = '.$data['sess_id'].'')->row();
                    $urut_soal = explode(",", $q_ambil_soal->list_jawaban);

                    $soal_urut_ok = array();

                    for ($i=0; $i < sizeof($urut_soal); $i++) { 
                         # code...
                         $pc_urut_soal = explode(":", $urut_soal[$i]);
                         $pc_urut_soal1 = empty($pc_urut_soal[1]) ? "''" : "'".$pc_urut_soal[1]."'";

                         $ambil_soal = $this->db->query(
                              'SELECT id_soal, mapel, kelas, guru, soal, media, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, ? AS jawaban FROM soal WHERE id_soal = ?',
                              array(trim($pc_urut_soal[1], "'"), (int) $pc_urut_soal[0])
                         )->row();
                         $soal_urut_ok[] = $ambil_soal;
                    }

                    $whr_detil_soal = ['id_ujian' => $id_ujian];
                    $data['detil_soal'] = $this->m_user->detil_soal($whr_detil_soal)->row();
                    $data['detil_tes'] = $q_ambil_soal;
                    $data['data'] = $soal_urut_ok;

                    //view
                    $data['title'] = $data['detil_soal']->mapel.' | '.$data['detil_soal']->nama_ujian;
                    $data['judul'] = $data['detil_soal']->mapel;
                    $this->load->view('_template_ujian/header', $data);
                    $this->load->view('_template_ujian/ujian');
                    $this->load->view('_template_ujian/footer');
               }
          }
          else{
               //redirect ujian selesai
               $xx = $this->m_user->cek_selesai_ujian(['id_siswa' => $data['sess_id'], 'id_ujian' => $id_ujian, 'status' => 'N'])->row();
               redirect('selesai/'.$xx->id_tes);
          }
     }

     // Save answers on the server; the database deadline and attempt ownership are authoritative.
     public function ujian_simpan($id_tes)
     {
          if (strtoupper($this->input->method(TRUE)) !== 'POST') {
               return $this->j(array('status' => 'error', 'message' => 'Metode tidak diizinkan.'));
          }

          $id_tes = (int) $id_tes;
          $id_siswa = (int) $this->session->userdata('id');
          $payload = json_decode(file_get_contents('php://input'), TRUE);
          if (!is_array($payload)) {
               return $this->j(array('status' => 'error', 'message' => 'Data jawaban tidak valid.'));
          }

          $attempt = $this->db->get_where('ikut_ujian', array(
               'id_tes' => $id_tes,
               'id_siswa' => $id_siswa
          ))->row();

          if (!$attempt || $attempt->status !== 'Y') {
               return $this->j(array('status' => 'error', 'message' => 'Percobaan ujian tidak aktif.'));
          }

          if (strtotime($attempt->tgl_selesai) < time()) {
               return $this->j(array('status' => 'expired', 'message' => 'Waktu ujian telah berakhir.'));
          }

          $allowedIds = array_filter(array_map('intval', explode(',', (string) $attempt->list_soal)));
          if (empty($allowedIds)) {
               return $this->j(array('status' => 'error', 'message' => 'Daftar soal kosong.'));
          }

          $submittedIds = array();
          foreach ($allowedIds as $index => $questionId) {
               $field = 'id_soal_' . ($index + 1);
               if (isset($payload[$field]) && (int) $payload[$field] !== $questionId) {
                    return $this->j(array('status' => 'error', 'message' => 'Urutan soal tidak sesuai percobaan ini.'));
               }
               $answerField = 'opsi_' . ($index + 1);
               $answer = isset($payload[$answerField]) ? strtoupper(trim((string) $payload[$answerField])) : '';
               if ($answer !== '' && !in_array($answer, array('A', 'B', 'C', 'D', 'E'), TRUE)) {
                    return $this->j(array('status' => 'error', 'message' => 'Pilihan jawaban tidak valid.'));
               }
               $submittedIds[] = $questionId . ':' . $answer;
          }

          $this->db->where(array('id_tes' => $id_tes, 'id_siswa' => $id_siswa, 'status' => 'Y'));
          $this->db->update('ikut_ujian', array('list_jawaban' => implode(',', $submittedIds)));
          return $this->j(array('status' => $this->db->affected_rows() >= 0 ? 'ok' : 'error'));
     }

     // Finalize exactly one active attempt; correct answers are read only on the server.
     public function ujian_akhir($id_tes)
     {
          if (strtoupper($this->input->method(TRUE)) !== 'POST') {
               return $this->j(array('status' => 'error', 'message' => 'Metode tidak diizinkan.'));
          }

          $id_tes = (int) $id_tes;
          $id_siswa = (int) $this->session->userdata('id');
          $payload = json_decode(file_get_contents('php://input'), TRUE);
          if (!is_array($payload)) {
               return $this->j(array('status' => 'error', 'message' => 'Data jawaban tidak valid.'));
          }

          $this->db->trans_begin();
          $attempt = $this->db->query(
               'SELECT * FROM ikut_ujian WHERE id_tes = ? AND id_siswa = ? FOR UPDATE',
               array($id_tes, $id_siswa)
          )->row();

          if (!$attempt) {
               $this->db->trans_rollback();
               return $this->j(array('status' => 'error', 'message' => 'Percobaan ujian tidak ditemukan.'));
          }

          if ($attempt->status !== 'Y') {
               $this->db->trans_rollback();
               return $this->j(array('status' => 'ok', 'message' => 'Ujian sudah diselesaikan.'));
          }

          $allowedIds = array_filter(array_map('intval', explode(',', (string) $attempt->list_soal)));
          if (empty($allowedIds)) {
               $this->db->trans_rollback();
               return $this->j(array('status' => 'error', 'message' => 'Daftar soal kosong.'));
          }

          $examMeta = $this->db->select('id_mapel')
               ->get_where('ujian', array('id_ujian' => $attempt->id_ujian))
               ->row();
          if (!$examMeta) {
               $this->db->trans_rollback();
               return $this->j(array('status' => 'error', 'message' => 'Data ujian tidak valid.'));
          }

          $answers = array();
          $correct = 0;
          foreach (array_values($allowedIds) as $index => $questionId) {
               $field = 'opsi_' . ($index + 1);
               $answer = isset($payload[$field]) ? strtoupper(trim((string) $payload[$field])) : '';
               if ($answer !== '' && !in_array($answer, array('A', 'B', 'C', 'D', 'E'), TRUE)) {
                    $this->db->trans_rollback();
                    return $this->j(array('status' => 'error', 'message' => 'Pilihan jawaban tidak valid.'));
               }

               $question = $this->db->select('jawaban')
                    ->get_where('soal', array('id_soal' => $questionId, 'mapel' => $examMeta->id_mapel))
                    ->row();

               if (!$question) {
                    $this->db->trans_rollback();
                    return $this->j(array('status' => 'error', 'message' => 'Soal percobaan tidak valid.'));
               }

               if ($answer !== '' && strtoupper((string) $question->jawaban) === $answer) {
                    $correct++;
               }
               $answers[] = $questionId . ':' . $answer;
          }

          $score = round(($correct / count($allowedIds)) * 100, 2);
          $this->db->where(array('id_tes' => $id_tes, 'id_siswa' => $id_siswa, 'status' => 'Y'));
          $this->db->update('ikut_ujian', array(
               'jml_benar' => $correct,
               'nilai' => $score,
               'list_jawaban' => implode(',', $answers),
               'status' => 'N'
          ));

          if ($this->db->trans_status() === FALSE) {
               $this->db->trans_rollback();
               return $this->j(array('status' => 'error', 'message' => 'Gagal menyimpan hasil ujian.'));
          }
          $this->db->trans_commit();
          return $this->j(array('status' => 'ok', 'nilai' => $score, 'jml_benar' => $correct));
     }

     public function ujian_selesai($id_tes){
          if ($data['detil_tes'] = $this->m_user->detil_tes(['id_tes' => $id_tes])->num_rows() < 1) {
               redirect('');
          }
          $data['detil_tes'] = $this->m_user->detil_tes(['id_tes' => $id_tes])->row();
          $whr_detil_soal = ['id_ujian' => $data['detil_tes']->id_ujian];
          $data['detil_soal'] = $this->m_user->detil_soal($whr_detil_soal)->row();
          $data['title'] = 'Ujian Selesai';
          $data['judul'] = $data['detil_soal']->mapel;

          $this->load->view('_template_ujian/header', $data);
          $this->load->view('waktu_habis');
          $this->load->view('_template_ujian/footer');
     }

     //Setting
     public function setting(){
     	$id = ['id_siswa' => $this->session->id];
     	$data['ar'] = $this->m_user->cekdefpass($id)->row_array();
     	$data['title'] = 'Setting | Ujian Berbasis Komputer';
          $data['judul'] = 'Pengaturan';

     	$this->header($data);
     	$this->load->view('setting');
     	$this->load->view('_template/footer');
     }
     function gantipass(){
     	$password = $this->input->post('passLama');
     	$passwordBaru = $this->input->post('konfirPass');

     	$whr = ['id_siswa' => $this->session->id];
     	$cek = $this->m_user->cekpass($whr)->row_array();
     	if ($password != $cek['password']) {
     		# code...
     		$this->session->set_flashdata('repass', 'Password lama tidak cocok !');
     		redirect('setting');
     	}
     	else{
     		$data = ['password' => $passwordBaru];
     		$this->m_user->gantipass($data, $whr);
               $this->session->set_flashdata('repass', 'Password berhasil diubah');
               redirect('setting');
     	}
     }
      function gantiprtnyan(){
          $pertanyaan = $this->input->post('pertanyaan');
          $jawaban = $this->input->post('jawaban');
          $kjawaban = $this->input->post('konfirJawaban');
          if (empty($pertanyaan))  {
               $this->session->set_flashdata('reper', 'Pertanyaan belum diisi !');
               redirect('setting');
          }
          if (empty($jawaban) || empty($kjawaban))  {
               $this->session->set_flashdata('reper', 'Jawaban belum diisi !');
               redirect('setting');
          }
          if ($kjawaban != $jawaban)  {
               $this->session->set_flashdata('reper', 'Jawaban tidak sama !');
               redirect('setting');
          }
          else{
               $whr = ['id_siswa' => $this->session->id];
               $data = [
                    'pertanyaan' => $pertanyaan,
                    'jawaban' => $jawaban
               ];
               $this->m_user->gantipass($data, $whr);
               $this->session->set_flashdata('reper', 'Pertanyaan dan jawaban berhasil diubah');
               redirect('setting');
          }
      }


      //Fungsi tambahan
      public function j($data){
          header('Content-Type: application/json');
          echo json_encode($data);
      }

}