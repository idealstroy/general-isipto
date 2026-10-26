<?php
/** Server-only Bitrix24 REST client. Never log the webhook URL or API response body. */
class IsiptoBitrix24Client
{
    private $baseUrl;
    private $transport;
    private $lastApiError;

    public function __construct($baseUrl, $transport = null)
    {
        $baseUrl = trim($baseUrl);
        $baseUrl = preg_replace('~/profile\.json$~', '/', $baseUrl);
        $parts = parse_url($baseUrl);
        if (!$parts || !isset($parts['scheme'], $parts['host'], $parts['path'])
            || $parts['scheme'] !== 'https' || $parts['host'] !== 'isipto.bitrix24.ru'
            || !preg_match('~^/rest/[0-9]+/[A-Za-z0-9_-]+/$~D', $parts['path'])
            || isset($parts['query']) || isset($parts['fragment']) || isset($parts['user']) || isset($parts['port'])) {
            throw new RuntimeException('BITRIX_CONFIG_INVALID');
        }
        $this->baseUrl = $baseUrl;
        $this->transport = $transport;
    }

    public static function fromEnvironment()
    {
        $file = getenv('BITRIX24_ENV_FILE');
        if (!$file) {
            $file = '/run/secrets/isipto-bitrix24.env';
            if (!is_readable($file) && defined('MODX_BASE_PATH')) {
                $file = dirname(rtrim(MODX_BASE_PATH, '/\\')) . '/.isipto-secrets/bitrix24.env';
            }
        }
        // The hosting secret must remain outside the public document root.
        if (defined('MODX_BASE_PATH') && realpath($file) !== false) {
            $public = rtrim(realpath(MODX_BASE_PATH), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if (strpos(realpath($file), $public) === 0) throw new RuntimeException('BITRIX_CONFIG_INVALID');
        }
        if (!is_readable($file)) {
            throw new RuntimeException('BITRIX_CONFIG_MISSING');
        }
        $values = array();
        foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
            $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
            if (!preg_match('/^\s*(?:export\s+)?BITRIX24_WEBHOOK_URL\s*=\s*(.*?)\s*$/', $line, $m)) continue;
            $value = $m[1];
            if (strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
                $value = substr($value, 1, -1);
            }
            $values[] = $value;
        }
        if (count($values) !== 1 || $values[0] === '') throw new RuntimeException('BITRIX_CONFIG_INVALID');
        return new self($values[0]);
    }

    public function call($method, array $parameters)
    {
        if (!in_array($method, array('crm.deal.fields', 'crm.deal.add', 'crm.deal.get', 'crm.contact.add', 'crm.duplicate.findbycomm', 'crm.category.get', 'crm.status.list'), true)) {
            throw new RuntimeException('BITRIX_METHOD_NOT_ALLOWED');
        }
        $this->lastApiError = null;
        $url = $this->baseUrl . $method . '.json';
        if ($this->transport !== null) {
            $response = call_user_func($this->transport, $url, $parameters);
        } else {
            if (!function_exists('curl_init')) throw new RuntimeException('BITRIX_CURL_MISSING');
            $handle = curl_init($url);
            curl_setopt_array($handle, array(
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($parameters),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_HTTPHEADER => array('Accept: application/json'),
            ));
            $body = curl_exec($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
            $failed = curl_errno($handle) !== 0;
            curl_close($handle);
            if ($failed || $body === false) throw new RuntimeException('BITRIX_TRANSPORT_UNCERTAIN');
            $response = array('status' => $status, 'body' => $body);
        }
        $result = json_decode($response['body'], true);
        if (is_array($result) && array_key_exists('error', $result)) {
            $this->lastApiError = preg_match('/^[A-Za-z0-9_]{1,100}$/D', (string) $result['error']) ? $result['error'] : 'UNSPECIFIED';
            throw new RuntimeException('BITRIX_API_REJECTED');
        }
        if ($response['status'] < 200 || $response['status'] >= 300 || !is_array($result) || !array_key_exists('result', $result)) {
            throw new RuntimeException('BITRIX_TRANSPORT_UNCERTAIN');
        }
        return $result['result'];
    }

    public function getLastApiError()
    {
        return $this->lastApiError;
    }

}

/** Durable, PII-free delivery receipt and lock, including MODX database-backed sessions. */
class IsiptoBitrix24Receipt
{
    private $handle;
    public function __construct($directory, $key)
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $key)) throw new RuntimeException('BITRIX_RECEIPT_UNAVAILABLE');
        if (!is_dir($directory) && !@mkdir($directory, 0770, true) && !is_dir($directory)) throw new RuntimeException('BITRIX_RECEIPT_UNAVAILABLE');
        $this->handle = @fopen($directory . '/' . $key . '.json', 'c+');
        if (!$this->handle || !flock($this->handle, LOCK_EX | LOCK_NB)) {
            if ($this->handle) fclose($this->handle);
            $this->handle = null;
            throw new RuntimeException('BITRIX_RECEIPT_UNAVAILABLE');
        }
    }
    public function read()
    {
        rewind($this->handle);$raw = stream_get_contents($this->handle);
        if ($raw === '') return array();
        $data = json_decode($raw, true);
        if (!is_array($data)) throw new RuntimeException('BITRIX_RECEIPT_UNAVAILABLE');
        return $data;
    }
    public function write(array $data)
    {
        $json = json_encode($data);
        rewind($this->handle);
        if (!ftruncate($this->handle, 0) || fwrite($this->handle, $json) !== strlen($json) || !fflush($this->handle)) throw new RuntimeException('BITRIX_RECEIPT_UNAVAILABLE');
    }
    public function close()
    {
        if ($this->handle) {flock($this->handle, LOCK_UN);fclose($this->handle);$this->handle = null;}
    }
    public function __destruct() {$this->close();}
}
