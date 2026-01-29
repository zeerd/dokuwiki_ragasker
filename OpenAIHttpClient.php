<?php
class OpenAIHttpClient {
    private $apiKey;
    private $baseUrl = '';

    public function __construct($serverUrl, $apiKey) {
        $this->baseUrl = rtrim($serverUrl, '/');
        $this->apiKey = $apiKey;
    }

    /**
     * 发送聊天请求
     */
    public function chatCompletion($params) {
        return $this->request('/chat/completions', $params);
    }

    /**
     * 发送补全请求
     */
    public function completion($params) {
        return $this->request('/completions', $params);
    }

    /**
     * 通用 HTTP 请求方法
     */
    private function request($endpoint, $data) {
        $url = $this->baseUrl . $endpoint;

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 300,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FAILONERROR => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }

        $decoded = json_decode($response, true);

        if ($httpCode !== 200) {
            $errorMsg = $decoded['error']['message'] ?? "HTTP {$httpCode}";
            throw new Exception("API Error: " . $errorMsg);
        }

        return $decoded;
    }

    /**
     * 流式响应（适合长文本）
     */
    public function streamChatCompletion($params, $callback) {
        $params['stream'] = true;
        $url = $this->baseUrl . '/chat/completions';

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: text/event-stream',
            'Cache-Control: no-cache'
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_WRITEFUNCTION => function($ch, $data) use ($callback) {
                $callback($data);
                return strlen($data);
            },
            CURLOPT_TIMEOUT => 120
        ]);

        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * 获取可用模型列表
     */
    public function listModels() {
        $url = $this->baseUrl . '/models';

        $headers = [
            'Authorization: Bearer ' . $this->apiKey
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}
