<?php
/* ==================================================
   MVNBC notifier
   Sends alerts via Email (raw SMTP) and Telegram (Bot API).
   - Used by the Alerts page for "Send test message".
   - Used by notify_run.php (CLI) after a backup run.
   No external libraries required.
================================================== */

require_once __DIR__ . '/settings.php';

if (!function_exists('mvnbc_send_telegram')) {

    /* ---------- Telegram ---------- */
    function mvnbc_send_telegram($token, $chatId, $text) {
        $token  = trim($token);
        $chatId = trim($chatId);
        if ($token === '' || $chatId === '') {
            return [false, 'Bot token and chat ID are required.'];
        }
        $url = "https://api.telegram.org/bot" . $token . "/sendMessage";
        $payload = http_build_query([
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_TIMEOUT        => 15,
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);
            if ($resp === false) return [false, "Telegram request failed: $err"];
            $j = json_decode($resp, true);
            if (isset($j['ok']) && $j['ok']) return [true, 'Telegram message sent.'];
            $desc = isset($j['description']) ? $j['description'] : "HTTP $code";
            return [false, "Telegram error: $desc"];
        }

        // Fallback without cURL
        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $payload,
            'timeout' => 15,
        ]]);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) return [false, 'Telegram request failed (network blocked?).'];
        $j = json_decode($resp, true);
        if (isset($j['ok']) && $j['ok']) return [true, 'Telegram message sent.'];
        return [false, 'Telegram error: ' . ($j['description'] ?? 'unknown')];
    }

    /* ---------- Email via raw SMTP ---------- */
    function mvnbc_send_email($cfg, $subject, $bodyText) {
        $host = trim($cfg['smtp_host'] ?? '');
        $port = (int)($cfg['smtp_port'] ?? 587);
        $secure = $cfg['smtp_secure'] ?? 'tls';
        $user = $cfg['username'] ?? '';
        $pass = $cfg['password'] ?? '';
        $from = trim($cfg['from'] ?? ($user ?: ''));
        $toRaw = trim($cfg['to'] ?? '');

        if ($host === '' || $from === '' || $toRaw === '') {
            return [false, 'SMTP host, from, and to are required.'];
        }
        $recipients = array_filter(array_map('trim', explode(',', $toRaw)));
        if (empty($recipients)) return [false, 'No valid recipients.'];

        $transport = ($secure === 'ssl') ? "ssl://$host" : $host;
        $errno = 0; $errstr = '';
        $fp = @stream_socket_client("$transport:$port", $errno, $errstr, 20);
        if (!$fp) return [false, "SMTP connect failed: $errstr ($errno)"];
        stream_set_timeout($fp, 20);

        $read = function() use ($fp) {
            $data = '';
            while ($line = fgets($fp, 515)) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') break;
            }
            return $data;
        };
        $cmd = function($c) use ($fp, $read) {
            if ($c !== null) fwrite($fp, $c . "\r\n");
            return $read();
        };

        $expect = function($resp, $codes) {
            $code = (int)substr($resp, 0, 3);
            return in_array($code, (array)$codes, true);
        };

        $banner = $read();
        if (!$expect($banner, 220)) { fclose($fp); return [false, "SMTP greeting: " . trim($banner)]; }

        $ehlo = $cmd("EHLO mvnbc.local");
        if (!$expect($ehlo, 250)) { fclose($fp); return [false, "EHLO failed: " . trim($ehlo)]; }

        if ($secure === 'tls') {
            $st = $cmd("STARTTLS");
            if (!$expect($st, 220)) { fclose($fp); return [false, "STARTTLS failed: " . trim($st)]; }
            if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT |
                STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
                fclose($fp); return [false, 'TLS negotiation failed.'];
            }
            $ehlo = $cmd("EHLO mvnbc.local");
            if (!$expect($ehlo, 250)) { fclose($fp); return [false, "EHLO after TLS failed."]; }
        }

        // AUTH LOGIN (only if a username is provided)
        if ($user !== '') {
            $a = $cmd("AUTH LOGIN");
            if (!$expect($a, 334)) { fclose($fp); return [false, "AUTH not accepted: " . trim($a)]; }
            $u = $cmd(base64_encode($user));
            if (!$expect($u, 334)) { fclose($fp); return [false, "Username rejected."]; }
            $p = $cmd(base64_encode($pass));
            if (!$expect($p, 235)) { fclose($fp); return [false, "Authentication failed."]; }
        }

        $mf = $cmd("MAIL FROM:<$from>");
        if (!$expect($mf, 250)) { fclose($fp); return [false, "MAIL FROM rejected: " . trim($mf)]; }

        foreach ($recipients as $rcpt) {
            $rc = $cmd("RCPT TO:<$rcpt>");
            if (!$expect($rc, [250, 251])) { fclose($fp); return [false, "RCPT rejected for $rcpt: " . trim($rc)]; }
        }

        $d = $cmd("DATA");
        if (!$expect($d, 354)) { fclose($fp); return [false, "DATA rejected: " . trim($d)]; }

        $date = date('r');
        $toHeader = implode(', ', $recipients);
        $subjectEnc = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers  = "From: MVNBC Backup <$from>\r\n";
        $headers .= "To: $toHeader\r\n";
        $headers .= "Subject: $subjectEnc\r\n";
        $headers .= "Date: $date\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "X-Mailer: MVNBC\r\n";

        // dot-stuffing for lines beginning with '.'
        $body = preg_replace('/^\./m', '..', $bodyText);
        fwrite($fp, $headers . "\r\n" . $body . "\r\n.\r\n");
        $sent = $read();
        if (!$expect($sent, 250)) { fclose($fp); return [false, "Message not accepted: " . trim($sent)]; }

        $cmd("QUIT");
        fclose($fp);
        return [true, 'Email sent to ' . $toHeader . '.'];
    }

    /* ---------- High-level dispatch ---------- */
    function mvnbc_dispatch_alert($settings, $subject, $body) {
        $results = [];
        if (!empty($settings['email']['enabled'])) {
            [$ok, $msg] = mvnbc_send_email($settings['email'], $subject, $body);
            $results['email'] = [$ok, $msg];
        }
        if (!empty($settings['telegram']['enabled'])) {
            $tg = $settings['telegram'];
            // Telegram HTML: escape angle brackets in the body
            $tgText = "<b>" . htmlspecialchars($subject) . "</b>\n\n" .
                      htmlspecialchars($body);
            [$ok, $msg] = mvnbc_send_telegram($tg['bot_token'], $tg['chat_id'], $tgText);
            $results['telegram'] = [$ok, $msg];
        }
        return $results;
    }
}
