<?php
require_once(__DIR__ . '/OpenAIHttpClient.php');
require_once(__DIR__ . '/SearchHelper.php');
class action_plugin_ragasker extends DokuWiki_Action_Plugin {

    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handle_ajax');
    }

    public function handle_ajax(Doku_Event $event) {
        // 新增：处理 ragasker_widget=1 的 POST 请求（前端小部件调用）
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ragasker_widget']) && !empty($_POST['prompt'])) {
            $prompt = trim($_POST['prompt']);
            $step = isset($_POST['step']) ? intval($_POST['step']) : 1;
            $serverUrl = $this->getConf('server_url');
            $apiKey = $this->getConf('apikey');
            if (empty($apiKey)) {
                $this->sendJson(['ragasker_response' => '<span style="color:red">请先在插件配置中设置 OpenAI API 密钥</span>', 'step' => $step]);
                exit;
            }
            $model = $this->getConf('model');
            $maxTokens = (int)$this->getConf('max_tokens');
            $temperature = (float)$this->getConf('temperature');
            $client = new OpenAIHttpClient($serverUrl, $apiKey);

            // 步骤1：关键词提取
            if ($step === 1) {
                $keywordPrompt = "请从以下问题中提取3-5个最重要的关键词，返回一个用空格分隔的关键词列表，以优先度排列，不要解释：\n" . $prompt;
                $requestData1 = [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => '你是一个关键词提取助手。'],
                        ['role' => 'user', 'content' => $keywordPrompt]
                    ],
                    'max_tokens' => $maxTokens,
                    'temperature' => 0.2
                ];
                $response1 = $client->chatCompletion($requestData1);
                $keywords = '';
                if (isset($response1['choices'][0]['message']['content'])) {
                    $keywords = trim($response1['choices'][0]['message']['content']);
                } else {
                    $this->sendJson(['ragasker_response' => '<b>步骤1失败：</b>未能提取关键词', 'step' => 1]);
                    exit;
                }
                $step1msg = "<b>步骤1：提取关键词</b><br>用户问题：<code>" . hsc($prompt) . "</code><br>提取结果：<code>" . hsc($keywords) . "</code><br>";
                $this->sendJson(['ragasker_response' => $step1msg, 'step' => 1, 'keywords' => $keywords]);
                exit;
            }

            // 步骤2：关键词搜索
            if ($step === 2 && !empty($_POST['keywords'])) {
                $keywords = trim($_POST['keywords']);
                $highlight = false;
                $searchResults = ft_pageSearch($keywords, $highlight);
                $processor = new SearchHelper();

                // 新增：如果搜索结果为空，循环去掉最后一个关键词重试，直到没有关键词
                while ((!is_array($searchResults) || count($searchResults) === 0) && strpos($keywords, ' ') !== false) {
                    $keywordArr = explode(' ', $keywords);
                    array_pop($keywordArr); // 去掉最后一个
                    $keywords = trim(implode(' ', $keywordArr));
                    if ($keywords === '') break;
                    $searchResults = ft_pageSearch($keywords, $highlight);
                }

                $lists = $processor->extractLists($searchResults, 0);
                $linkList = $lists['links'];
                $contentList = $lists['contents'];
                $searchList = '';
                if (is_array($searchResults) && count($searchResults) > 0) {
                    $searchList = '<ul>';
                    foreach ($linkList as $idx => $link) {
                        $url = wl($link['id']);
                        $searchList .= '<li><a href="' . hsc($url) . '" target="_blank">' . hsc($link['title']) . '</a>';
                        $searchList .= '</li>';
                    }
                    $searchList .= '</ul>';
                } else {
                    $searchList = '<span style="color:orange">未找到相关页面</span>';
                }
                $step2msg = "<b>步骤2：关键词搜索</b><br>关键词：<code>" . hsc($keywords) . "</code><br>搜索结果：" . $searchList . "<br>";
                // 传递内容列表用于下一步
                $this->sendJson([
                    'ragasker_response' => $step2msg,
                    'step' => 2,
                    'keywords' => $keywords,
                    // 以 JSON 字符串返回，便于前端直接传递
                    'linkList' => json_encode($linkList),
                    'contentList' => json_encode($contentList)
                ]);
                exit;
            }

            // 步骤3：AI总结回答
            if ($step === 3 && !empty($_POST['keywords']) && !empty($_POST['linkList']) && !empty($_POST['contentList'])) {
                $keywords = trim($_POST['keywords']);
                $linkList = json_decode($_POST['linkList'], true);
                $contentList = json_decode($_POST['contentList'], true);
                $pageListStr = '';
                if (count($contentList) > 0) {
                    $pageListArr = [];
                    foreach ($contentList as $idx => $item) {
                        $title = $linkList[$idx]['title'];
                        $summary = $item['summary'];
                        $pageListArr[] = "【" . $title . "】摘要：" . $summary;
                    }
                    $pageListStr = implode("\n", $pageListArr);
                } else {
                    $pageListStr = '无相关页面';
                }
                $summaryPrompt = "请根据以下页面列表，结合用户原始问题，简要总结并回答用户的问题。\n\n用户问题：" . $prompt . "\n\n页面列表：\n" . $pageListStr;
                $requestData2 = [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => '你是一个dokuwiki知识库问答助手。'],
                        ['role' => 'user', 'content' => $summaryPrompt]
                    ],
                    'max_tokens' => $maxTokens,
                    'temperature' => $temperature
                ];
                $response2 = $client->chatCompletion($requestData2);
                $finalAnswer = '';
                if (isset($response2['choices'][0]['message']['content'])) {
                    $finalAnswer = $this->formatResponse($response2['choices'][0]['message']['content']);
                } else {
                    $finalAnswer = '<span style="color:red">API返回格式异常</span>';
                }
                $step3msg = "<b>步骤3：AI总结回答</b><br>";
                if ($this->getConf('verbose')) {
                    $step3msg .= "<details><summary>提示词（点击展开）</summary><pre style='white-space:pre-wrap'>" . hsc($summaryPrompt) . "</pre></details><br>";
                }
                $step3msg .= $finalAnswer;
                $this->sendJson(['ragasker_response' => $step3msg, 'step' => 3]);
                exit;
            }
        }

        // 兼容原有 AJAX 机制
        if($event->data !== 'ragasker_generate') return;
        $event->stopPropagation();
        $event->preventDefault();

        global $INPUT;
        $prompt = $INPUT->post->str('prompt', '');
        $params = $INPUT->post->arr('params', []);

        // 验证请求
        if(!$this->validateRequest()) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            return;
        }

        $syntax = new syntax_plugin_ragasker();
        $response = $syntax->callOpenAI($prompt, $params);

        header('Content-Type: application/json');
        echo json_encode([
            'response' => $response,
            'timestamp' => time()
        ]);
    }
    // 用于 ragasker_widget 直接 JSON 响应
    private function sendJson($arr) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($arr);
    }

    // 用于格式化响应内容（与 syntax.php 保持一致）
    private function formatResponse($text) {
        $text = hsc($text);
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
        $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);
        $text = nl2br($text);
        return $text;
    }

    private function validateRequest() {
        global $INPUT;

        // CSRF 保护
        $sess = $INPUT->server->str('REMOTE_USER');
        if(empty($sess)) return false;

        // 检查权限
        return auth_isadmin();
    }
}
