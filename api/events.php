<?php
/**
 * Project Industry - Server-Sent Events (SSE) Endpoint
 * Yeni işlem kayıtları eklendiğinde bağlı istemcilere bildirim gönderir.
 */

// Session kilitleme sorununu önle
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_write_close();

require_once __DIR__ . '/../config/database.php';

// SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');
header('Access-Control-Allow-Origin: *');

// Client'ın son bildiği kayıt ID'si
$lastId = isset($_GET['lastId']) ? (int)$_GET['lastId'] : 0;

// İlk bağlantı mesajı (heartbeat)
echo ": connected\n\n";
if (ob_get_level()) ob_flush();
flush();

set_time_limit(0);
ignore_user_abort(true);

$maxTime = 300; // 5 dakika sonra bağlantıyı kapat (yeniden bağlansın)
$startTime = time();

while (time() - $startTime < $maxTime) {
    if (connection_aborted() || connection_status() !== 0) break;
    
    // Yeni kayıt var mı?
    $stmt = $pdo->prepare("SELECT id FROM records WHERE id > ? AND aktif = 1 ORDER BY id ASC LIMIT 1");
    $stmt->execute([$lastId]);
    $yeni_kayit = $stmt->fetch();
    
    if ($yeni_kayit) {
        $lastId = $yeni_kayit['id'];
        
        // SSE mesajı gönder
        echo "event: yeni_islem\n";
        echo "id: " . $lastId . "\n";
        echo "data: " . json_encode(['kayit_id' => $lastId]) . "\n\n";
        
        if (ob_get_level()) ob_flush();
        flush();
    }
    
    // Heartbeat (bağlantıyı canlı tut)
    echo ": heartbeat\n\n";
    if (ob_get_level()) ob_flush();
    flush();
    
    sleep(3);
}
