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
            // 多语言文本获取函数
            $L = function($key) { return $this->getLang($key); };
            $serverUrl = $this->getConf('server_url');
            $apiKey = $this->getConf('apikey');
            if (empty($apiKey)) {
                $this->sendJson(['ragasker_response' => '<span style="color:red">' . hsc($L('no_apikey')) . '</span>', 'step' => $step]);
                exit;
            }
            $model = $this->getConf('model');
            $maxTokens = (int)$this->getConf('max_tokens');
            $temperature = (float)$this->getConf('temperature');
            $client = new OpenAIHttpClient($serverUrl, $apiKey);

            // 步骤1：关键词提取
            if ($step === 1) {
                $keywordPrompt = $L('keyword_prompt') . "\n" . $prompt;
                $requestData1 = [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $L('keyword_system')],
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
                    $this->sendJson(['ragasker_response' => '<span style="color:red">' . hsc($L('error_api')) . '</span>', 'step' => 1]);
                    exit;
                }
                $step1msg = "<b>" . hsc(sprintf($L('step_title'), 1, $L('step_extracting'))) . "</b><br>"
                    . hsc(sprintf($L('user_question'), $prompt)) . "<br>"
                    . hsc(sprintf($L('result'), $keywords)) . "<br>";
                $this->sendJson(['ragasker_response' => $step1msg, 'step' => 1, 'keywords' => $keywords]);
                exit;
            }

            // 步骤2：关键词搜索
            if ($step === 2 && !empty($_POST['keywords'])) {
                $keywords = trim($_POST['keywords']);

                $processor = new SearchHelper();
                $lists = $processor->search($keywords);
                $linkList = $lists['links'];
                $contentList = $lists['contents'];
                $searchList = '';
                if (is_array($linkList) && count($linkList) > 0) {
                    $searchList = '<ul>';
                    foreach ($linkList as $idx => $link) {
                        $url = wl($link['id']);
                        $searchList .= '<li><a href="' . hsc($url) . '" target="_blank">' . hsc($link['title']) . '</a>';
                        $searchList .= '</li>';
                    }
                    $searchList .= '</ul>';
                } else {
                    $keywords = trim($_POST['keywords']);
                    $searchList = '<span style="color:orange">' . hsc($L('error_noresult')) . '</span>';
                }
                $step2msg = "<b>" . hsc(sprintf($L('step_title'), 2, $L('step_searching'))) . "</b><br>"
                    . hsc(sprintf($L('keywords'), $keywords)) . "<br>"
                    . hsc(sprintf($L('search_result'), '')) . $searchList . "<br>";
                $this->sendJson([
                    'ragasker_response' => $step2msg,
                    'step' => 2,
                    'keywords' => $keywords,
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
                $pageListArr = [];
                $idList = [];
                if (!empty($_POST['idList'])) {
                    $idList = json_decode($_POST['idList'], true);
                }
                if (count($contentList) > 0) {
                    foreach ($contentList as $idx => $item) {
                        $id = isset($linkList[$idx]['id']) ? $linkList[$idx]['id'] : '';
                        if ($id === '' || in_array($id, $idList)) continue;
                        $title = $linkList[$idx]['title'];
                        $summary = $item['summary'];
                        $pageListArr[] = sprintf($L('page_summary'), $title, $summary);
                        $idList[] = $id;
                    }
                }
                $pageListStr = count($pageListArr) > 0 ? implode("\n", $pageListArr) : $L('error_noresult');
                file_put_contents('/tmp/ragasker_debug_request.json', json_encode($pageListStr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $summaryPrompt = $L('summary_prompt') . "\n\n" . sprintf($L('user_question'), $prompt) . "\n\n" . $L('page_list') . "\n" . $pageListStr;
                $messages = [];
                if (!empty($_POST['messages'])) {
                    $messages = json_decode($_POST['messages'], true);
                }
                if (empty($messages)) {
                    $messages[] = ['role' => 'system', 'content' => $L('summary_system')];
                }
                $messages[] = ['role' => 'user', 'content' => $summaryPrompt];
                $requestData2 = [
                    'model' => $model,
                    'messages' => $messages,
                    'max_tokens' => $maxTokens,
                    'temperature' => $temperature
                ];
                $step3msg = "<b>" . hsc(sprintf($L('step_title'), 3, $L('step_summarizing'))) . "</b><br>";
                if ($this->getConf('verbose')) {
                    $step3msg .= "<details><summary>" . hsc($L('prompt_detail')) . "</summary><pre style='white-space:pre-wrap'>" . hsc($summaryPrompt) . "</pre></details><br>";
                }
                $response2 = $client->chatCompletion($requestData2);
                $finalAnswer = '';
                if (isset($response2['choices'][0]['message']['content'])) {
                    $answer = $response2['choices'][0]['message']['content'];
                    $messages[] = ['role' => 'assistant', 'content' => $answer];
                    $finalAnswer = $this->formatResponse($answer);
                } else {
                    $finalAnswer = '<span style="color:red">' . hsc($L('error_format')) . '</span>';
                }
                $step3msg .= $finalAnswer;
                $this->sendJson([
                    'ragasker_response' => $step3msg,
                    'step' => 3,
                    'messages' => $messages,
                    'idList' => $idList
                ]);
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
