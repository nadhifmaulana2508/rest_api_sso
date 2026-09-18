<?php
declare(strict_types=1);

function get_signature_profile(PDO $pdo, string $idPeg): ?array {
    $st = $pdo->prepare('SELECT * FROM signature_profiles WHERE id_peg=:id LIMIT 1');
    $st->execute(['id'=>$idPeg]);
    return $st->fetch() ?: null;
}

function get_signature_security(PDO $pdo, string $idPeg): ?array {
    $st = $pdo->prepare('SELECT * FROM signature_security WHERE id_peg=:id LIMIT 1');
    $st->execute(['id'=>$idPeg]);
    return $st->fetch() ?: null;
}

function log_signature(PDO $pdo, string $idPeg, string $action, string $status, ?string $docId=null, string $application='TTD IN BKK'): void {
    $st = $pdo->prepare("INSERT INTO signature_logs(id_peg,application,document_id,action,status,ip_address,user_agent)
                        VALUES(:id,:app,:doc,:act,:status,:ip,:ua)");
    $st->execute([
        'id'=>$idPeg,'app'=>$application,'doc'=>$docId,'act'=>$action,'status'=>$status,
        'ip'=>client_ip(),'ua'=>user_agent()
    ]);
}

function store_signature_image(string $dataUrl, string $idPeg): string {
    if (!preg_match('#^data:image/(png|jpeg);base64,(.+)$#', $dataUrl, $m)) {
        throw new RuntimeException('Format TTD tidak valid.');
    }
    $raw = base64_decode($m[2], true);
    if ($raw === false || strlen($raw) < 100 || strlen($raw) > 3*1024*1024) {
        throw new RuntimeException('Ukuran TTD tidak valid.');
    }

    $info = @getimagesizefromstring($raw);
    if (!$info || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
        throw new RuntimeException('File harus PNG atau JPG.');
    }

    $dir = dirname(__DIR__) . '/storage/signatures';
    if (!is_dir($dir)) mkdir($dir, 0750, true);

    $name = hash('sha256', $idPeg . '|ttd') . '.png';
    $path = $dir . '/' . $name;

    if (function_exists('imagecreatefromstring')) {
        $src = imagecreatefromstring($raw);
        $w = imagesx($src); $h = imagesy($src);
        $dst = imagecreatetruecolor($w, $h);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255,255,255,127);
        imagefill($dst,0,0,$transparent);
        for ($y=0;$y<$h;$y++) {
            for ($x=0;$x<$w;$x++) {
                $rgb = imagecolorat($src,$x,$y);
                $r=($rgb>>16)&255; $g=($rgb>>8)&255; $b=$rgb&255;
                if ($r>242 && $g>242 && $b>242) {
                    imagesetpixel($dst,$x,$y,$transparent);
                } else {
                    $c = imagecolorallocatealpha($dst,$r,$g,$b,0);
                    imagesetpixel($dst,$x,$y,$c);
                }
            }
        }
        imagepng($dst,$path,6);
        imagedestroy($src); imagedestroy($dst);
    } else {
        file_put_contents($path, $raw);
    }

    return 'storage/signatures/' . $name;
}
