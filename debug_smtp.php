<?php
// Debug settings
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>SMTP Debugger</h1>";

// Config (Same as in your newsletter.php)
$config = [
    'host' => 'poczta2686594.home.pl',
    'port' => 587,
    'username' => 'newsletter@pasiekapodgruszka.pl',
    'password' => 'YOUR_SMTP_PASSWORD',
    'from' => 'newsletter@pasiekapodgruszka.pl'
];

echo "<pre>";
echo "Connecting to {$config['host']}:{$config['port']}...\n";

try {
    $socket = fsockopen($config['host'], $config['port'], $errno, $errstr, 10);
    if (!$socket) throw new Exception("Connect failed: $errstr ($errno)");

    // Helper to read/write
    function raw($socket, $cmd = null) {
        if ($cmd) {
            echo "client> $cmd\n";
            fputs($socket, $cmd . "\r\n");
        }
        $response = '';
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == ' ') break;
        }
        echo "server> $response";
        return $response;
    }

    raw($socket); // Greeting

    raw($socket, "EHLO " . $_SERVER['SERVER_NAME']);

    if ($config['port'] == 587) {
        raw($socket, "STARTTLS");
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        raw($socket, "EHLO " . $_SERVER['SERVER_NAME']);
    }

    // Test 1: Full Email as Username
    echo "<h3>Test 1: Full Email (newsletter@pasiekapodgruszka.pl)</h3>";
    raw($socket, "AUTH LOGIN");
    raw($socket, base64_encode($config['username']));
    $res = raw($socket, base64_encode($config['password']));
    
    if (strpos($res, '235') !== false) {
        echo "\n<strong style='color:green'>SUCCESS with Full Email!</strong>\n";
    } else {
        echo "\n<strong style='color:red'>FAILURE with Full Email.</strong>\n";
        
        // Test 2: Username only (newsletter)
        echo "<h3>Test 2: Username Only (newsletter)</h3>";
        // Re-connect for clean state
        fclose($socket);
        $socket = fsockopen($config['host'], $config['port'], $errno, $errstr, 10);
        raw($socket); // banner
        raw($socket, "EHLO " . $_SERVER['SERVER_NAME']);
        if ($config['port'] == 587) {
            raw($socket, "STARTTLS");
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            raw($socket, "EHLO " . $_SERVER['SERVER_NAME']);
        }
        
        $userOnly = explode('@', $config['username'])[0];
        raw($socket, "AUTH LOGIN");
        raw($socket, base64_encode($userOnly));
        $res = raw($socket, base64_encode($config['password']));
        
        if (strpos($res, '235') !== false) {
            echo "\n<strong style='color:green'>SUCCESS with Username Only ('$userOnly')!</strong>\n";
            echo "Please update the config to use just '$userOnly'.\n";
        } else {
             echo "\n<strong style='color:red'>FAILURE with Username Only.</strong>\n";
        }
    }

    raw($socket, "QUIT");
    fclose($socket);

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
echo "</pre>";
