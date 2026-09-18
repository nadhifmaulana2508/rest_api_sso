<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../middleware/ttd.php';

[$user,$profile] = require_ttd($pdo);
$input=json_decode(file_get_contents('php://input'),true) ?: [];
$pin=(string)($input['pin'] ?? '');
$documentId=substr(trim((string)($input['document_id'] ?? '')),0,100);
$title=substr(trim((string)($input['document_title'] ?? 'Dokumen')),0,200);
$app=substr(trim((string)($input['application'] ?? 'SIMPEG')),0,50);
$idPeg=(string)$user['employee_id'];

if(!preg_match('/^\d{6}$/',$pin) || $documentId===''){
    json_response(422,['success'=>false,'message'=>'PIN 6 digit dan document_id wajib diisi.']);
}

if(!empty($profile['locked_until']) && strtotime($profile['locked_until']) > time()){
    json_response(423,['success'=>false,'message'=>'PIN terkunci sementara karena terlalu banyak percobaan.']);
}

if(!password_verify($pin,(string)$profile['pin_hash'])){
    $failed=(int)$profile['failed_attempt']+1;
    $locked=$failed>=5 ? date('Y-m-d H:i:s',time()+900) : null;
    $pdo->prepare("UPDATE ttd_profiles SET failed_attempt=:f,locked_until=:l WHERE id_peg=:id")->execute(['f'=>$failed,'l'=>$locked,'id'=>$idPeg]);
    $pdo->prepare("INSERT INTO ttd_logs(id_peg,application,document_id,action,status,ip_address,user_agent) VALUES(:id,:app,:doc,'SIGN','FAILED',:ip,:ua)")
        ->execute(['id'=>$idPeg,'app'=>$app,'doc'=>$documentId,'ip'=>$_SERVER['REMOTE_ADDR']??null,'ua'=>substr($_SERVER['HTTP_USER_AGENT']??'',0,255)]);
    json_response(401,['success'=>false,'message'=>$locked?'PIN salah 5 kali. Akun TTD dikunci 15 menit.':'PIN TTD salah.']);
}

$pdo->prepare("UPDATE ttd_profiles SET failed_attempt=0,locked_until=NULL WHERE id_peg=:id")->execute(['id'=>$idPeg]);
$payload=[
    'document_id'=>$documentId,
    'title'=>$title,
    'employee_id'=>$idPeg,
    'employee_name'=>$user['full_name'] ?? '',
    'application'=>$app,
    'signed_at'=>date(DATE_ATOM)
];
$hash=hash('sha256',json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
$token=bin2hex(random_bytes(32));

$pdo->prepare("INSERT INTO ttd_documents(document_id,id_peg,application,document_title,document_hash,verification_token) VALUES(:doc,:id,:app,:title,:hash,:token)")
    ->execute(['doc'=>$documentId,'id'=>$idPeg,'app'=>$app,'title'=>$title,'hash'=>$hash,'token'=>$token]);
$pdo->prepare("INSERT INTO ttd_logs(id_peg,application,document_id,action,status,ip_address,user_agent) VALUES(:id,:app,:doc,'SIGN','SUCCESS',:ip,:ua)")
    ->execute(['id'=>$idPeg,'app'=>$app,'doc'=>$documentId,'ip'=>$_SERVER['REMOTE_ADDR']??null,'ua'=>substr($_SERVER['HTTP_USER_AGENT']??'',0,255)]);

json_response(200,['success'=>true,'message'=>'Dokumen berhasil ditandatangani.','data'=>[
    'signature_url'=>app_url($profile['signature_path']),
    'verification_url'=>(isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off'?'https':'http').'://'.($_SERVER['HTTP_HOST']??'localhost').app_url('verify.php?token='.$token),
    'document_hash'=>$hash,
    'signed_at'=>date('Y-m-d H:i:s')
]]);
