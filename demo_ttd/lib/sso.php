<?php
declare(strict_types=1);

function sso_request(string $method, string $path, ?array $body = null, ?string $token = null): array
{
    $url = rtrim(TTD_SSO_BASE_URL, '/') . '/' . ltrim($path, '/');
    $ch = curl_init($url);

    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $error) {
        throw new RuntimeException('SSO tidak dapat dihubungi: ' . $error);
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Respons SSO tidak valid.');
    }

    return ['status' => $status, 'body' => $decoded];
}

function sso_login(string $idPeg, string $password): array
{
    return sso_request('POST', '/api/auth/login', [
        'id_peg' => $idPeg,
        'password' => $password,
        'app' => TTD_SSO_APP,
    ]);
}

function sso_whoami(string $token): array
{
    return sso_request('GET', '/api/auth/whoami', null, $token);
}
