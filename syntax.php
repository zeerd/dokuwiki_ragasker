<?php
require_once(__DIR__ . '/OpenAIHttpClient.php');

class syntax_plugin_ragasker extends DokuWiki_Syntax_Plugin {

    public function getType() { return 'substition'; }
    public function getSort() { return 155; }

    public function connectTo($mode) {
        // 多种语法支持
        $this->Lexer->addSpecialPattern('~~RAGASKER~~', $mode, 'plugin_ragasker');
    }

    public function handle($match, $state, $pos, Doku_Handler $handler) {
        // 只传递唯一ID用于渲染
        $uniqid = uniqid('ragasker_', true);
        return [$uniqid, null];
    }

    public function render($mode, Doku_Renderer $renderer, $data) {
        if($mode !== 'xhtml') return false;
        list($uniqid, $_) = $data;
        $inputId = $uniqid . '_input';
        $btnId = $uniqid . '_btn';
        $resultId = $uniqid . '_result';
        $renderer->doc .= '<div class="openai-widget" style="border:1px solid #ccc;padding:10px;margin:10px 0;">';
        $renderer->doc .= '<input type="text" id="' . hsc($inputId) . '" style="width:60%;" placeholder="请输入你的问题..." /> ';
        $renderer->doc .= '<button id="' . hsc($btnId) . '">提交</button>';
        $renderer->doc .= '<div id="' . hsc($resultId) . '" style="margin-top:10px;"></div>';
        $renderer->doc .= '</div>';
        $renderer->doc .= '<script type="text/javascript">
        (function(){
            var btn = document.getElementById("' . hsc($btnId) . '");
            var input = document.getElementById("' . hsc($inputId) . '");
            var result = document.getElementById("' . hsc($resultId) . '");
            var xhr = null;
            var running = false;
            var lastKeywords = "";
            var lastLinkList = null;
            var lastContentList = null;
            function step1() {
                result.innerHTML = "<em>步骤1：正在提取关键词...</em>";
                xhr = new XMLHttpRequest();
                xhr.open("POST", DOKU_BASE + "lib/exe/ajax.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if(xhr.readyState === 4) {
                        if(!running) return;
                        if(xhr.status === 200) {
                            try {
                                var resp = JSON.parse(xhr.responseText);
                                if(resp && resp.ragasker_response) {
                                    result.innerHTML = resp.ragasker_response;
                                    lastKeywords = resp.keywords || "";
                                    if(resp.step === 1 && lastKeywords) step2();
                                } else {
                                    result.innerHTML = "<span style=\'color:red\'>API返回异常</span>";
                                    running = false;
                                    btn.innerText = "提交";
                                }
                            } catch(e) {
                                result.innerHTML = "<span style=\'color:red\'>解析响应失败</span>";
                                running = false;
                                btn.innerText = "提交";
                            }
                        } else {
                            result.innerHTML = "<span style=\'color:red\'>请求失败("+xhr.status+")</span>";
                            running = false;
                            btn.innerText = "提交";
                        }
                    }
                };
                xhr.onerror = function(e) {
                    result.innerHTML = "<span style=\"color:red\">网络错误</span>";
                    running = false;
                    btn.innerText = "提交";
                };
                xhr.send("call=ragasker_generate&ragasker_widget=1&prompt=" + encodeURIComponent(input.value) + "&step=1");
            }
            function step2() {
                result.innerHTML += "<br><em>步骤2：正在搜索页面...</em>";
                xhr = new XMLHttpRequest();
                xhr.open("POST", DOKU_BASE + "lib/exe/ajax.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if(xhr.readyState === 4) {
                        if(!running) return;
                        if(xhr.status === 200) {
                            try {
                                var resp = JSON.parse(xhr.responseText);
                                if(resp && resp.ragasker_response) {
                                    result.innerHTML += "<hr>" + resp.ragasker_response;
                                    // 这里 linkList/contentList 是 JSON 字符串
                                    lastLinkList = resp.linkList;
                                    lastContentList = resp.contentList;
                                    if(resp.step === 2 && lastLinkList && lastContentList) step3();
                                } else {
                                    result.innerHTML += "<span style=\'color:red\'>API返回异常</span>";
                                    running = false;
                                    btn.innerText = "提交";
                                }
                            } catch(e) {
                                result.innerHTML += "<span style=\'color:red\'>解析响应失败</span>";
                                running = false;
                                btn.innerText = "提交";
                            }
                        } else {
                            result.innerHTML += "<span style=\'color:red\'>请求失败("+xhr.status+")</span>";
                            running = false;
                            btn.innerText = "提交";
                        }
                    }
                };
                xhr.onerror = function(e) {
                    result.innerHTML += "<span style=\"color:red\">网络错误</span>";
                    running = false;
                    btn.innerText = "提交";
                };
                xhr.send(
                    "call=ragasker_generate&ragasker_widget=1&prompt=" + encodeURIComponent(input.value) +
                    "&step=2&keywords=" + encodeURIComponent(lastKeywords)
                );
            }
            function step3() {
                result.innerHTML += "<hr><em>步骤3：AI总结回答...</em>";
                xhr = new XMLHttpRequest();
                xhr.open("POST", DOKU_BASE + "lib/exe/ajax.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if(xhr.readyState === 4) {
                        if(!running) return;
                        if(xhr.status === 200) {
                            try {
                                var resp = JSON.parse(xhr.responseText);
                                if(resp && resp.ragasker_response) {
                                    result.innerHTML += "<hr>" + resp.ragasker_response;
                                } else {
                                    result.innerHTML += "<span style=\'color:red\'>API返回异常</span>";
                                }
                            } catch(e) {
                                result.innerHTML += "<span style=\'color:red\'>解析响应失败</span>";
                            }
                        } else {
                            result.innerHTML += "<span style=\'color:red\'>请求失败("+xhr.status+")</span>";
                        }
                        running = false;
                        btn.innerText = "提交";
                    }
                };
                xhr.onerror = function(e) {
                    result.innerHTML += "<span style=\"color:red\">网络错误</span>";
                    running = false;
                    btn.innerText = "提交";
                };
                // 这里 linkList/contentList 需保证为 JSON 字符串
                xhr.send(
                    "call=ragasker_generate&ragasker_widget=1&prompt=" + encodeURIComponent(input.value) +
                    "&step=3&keywords=" + encodeURIComponent(lastKeywords) +
                    "&linkList=" + encodeURIComponent(lastLinkList) +
                    "&contentList=" + encodeURIComponent(lastContentList)
                );
            }
            if(btn && input && result) {
                btn.addEventListener("click", function() {
                    if(running) {
                        // 终止
                        running = false;
                        if(xhr) xhr.abort();
                        btn.innerText = "提交";
                        result.innerHTML += "<br><span style=\'color:orange\'>已终止</span>";
                        return;
                    }
                    var prompt = input.value;
                    if(!prompt) { result.innerHTML = "<span style=\'color:red\'>请输入内容</span>"; return; }
                    running = true;
                    btn.innerText = "终止";
                    lastKeywords = "";
                    lastLinkList = null;
                    lastContentList = null;
                    result.innerHTML = "";
                    step1();
                });
            }
        })();
        </script>';
        return true;
    }

    // callOpenAI 逻辑将迁移到 action 处理
    // 保留接口以兼容
    private function callOpenAI($prompt, $params = []) {
        return '';
    }

    private function formatResponse($text) {
        // 转换 Markdown 到 HTML
        $text = hsc($text); // HTML 安全转义

        // 基础 Markdown 转换
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
        $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);

        // 转换换行
        $text = nl2br($text);

        return $text;
    }
}
