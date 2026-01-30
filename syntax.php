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
        $renderer->doc .= '<input type="text" id="' . hsc($inputId) . '" style="width:60%;" placeholder="' . hsc($this->getLang('input_placeholder')) . '" /> ';
        $renderer->doc .= '<button id="' . hsc($btnId) . '">' . hsc($this->getLang('submit_btn')) . '</button>';
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
            // 多语言文本
            var i18n = {
                step1: "' . hsc(sprintf($this->getLang('step_title'), 1, $this->getLang('step_extracting'))) . '",
                step2: "' . hsc(sprintf($this->getLang('step_title'), 2, $this->getLang('step_searching'))) . '",
                step3: "' . hsc(sprintf($this->getLang('step_title'), 3, $this->getLang('step_summarizing'))) . '",
                api_error: "' . hsc($this->getLang('error_api')) . '",
                parse_error: "' . hsc($this->getLang('error_parse')) . '",
                request_error: "' . hsc($this->getLang('error_request')) . '",
                network_error: "' . hsc($this->getLang('error_network')) . '",
                stop: "' . hsc($this->getLang('stop_btn')) . '",
                submit: "' . hsc($this->getLang('submit_btn')) . '",
                stopped: "' . hsc($this->getLang('stopped')) . '",
                input_empty: "' . hsc($this->getLang('error_input_empty')) . '"
            };
            function step1() {
                result.innerHTML = "<em>" + i18n.step1 + "</em>";
                xhr = new XMLHttpRequest();
                xhr.open("POST", DOKU_BASE + "lib/exe/ajax.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if(xhr.readyState === 4) {
                        if(!running) return;
                        if(xhr.status === 200) {
                            console.log(xhr.responseText);
                            try {
                                var resp = JSON.parse(xhr.responseText);
                                if(resp && resp.ragasker_response) {
                                    result.innerHTML = resp.ragasker_response;
                                    lastKeywords = resp.keywords || "";
                                    if(resp.step === 1 && lastKeywords) step2();
                                } else {
                                    result.innerHTML = "<span style=\'color:red\'>" + i18n.api_error + "</span>";
                                    running = false;
                                    btn.innerText = i18n.submit;
                                }
                            } catch(e) {
                                result.innerHTML = "<span style=\'color:red\'>" + i18n.parse_error + "</span>";
                                running = false;
                                btn.innerText = i18n.submit;
                            }
                        } else {
                            result.innerHTML = "<span style=\'color:red\'>" + i18n.request_error + "("+xhr.status+")</span>";
                            running = false;
                            btn.innerText = i18n.submit;
                        }
                    }
                };
                xhr.onerror = function(e) {
                    result.innerHTML = "<span style=\"color:red\">" + i18n.network_error + "</span>";
                    running = false;
                    btn.innerText = i18n.submit;
                };
                xhr.send("call=ragasker_generate&ragasker_widget=1&prompt=" + encodeURIComponent(input.value) + "&step=1");
            }
            function step2() {
                result.innerHTML += "<br><em>" + i18n.step2 + "</em>";
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
                                    lastLinkList = resp.linkList;
                                    lastContentList = resp.contentList;
                                    if(resp.step === 2 && lastLinkList && lastContentList) step3();
                                } else {
                                    result.innerHTML += "<span style=\'color:red\'>" + i18n.api_error + "</span>";
                                    running = false;
                                    btn.innerText = i18n.submit;
                                }
                            } catch(e) {
                                result.innerHTML += "<span style=\'color:red\'>" + i18n.parse_error + "</span>";
                                running = false;
                                btn.innerText = i18n.submit;
                            }
                        } else {
                            result.innerHTML += "<span style=\'color:red\'>" + i18n.request_error + "("+xhr.status+")</span>";
                            running = false;
                            btn.innerText = i18n.submit;
                        }
                    }
                };
                xhr.onerror = function(e) {
                    result.innerHTML += "<span style=\"color:red\">" + i18n.network_error + "</span>";
                    running = false;
                    btn.innerText = i18n.submit;
                };
                xhr.send(
                    "call=ragasker_generate&ragasker_widget=1&prompt=" + encodeURIComponent(input.value) +
                    "&step=2&keywords=" + encodeURIComponent(lastKeywords)
                );
            }
            function step3() {
                result.innerHTML += "<hr><em>" + i18n.step3 + "</em>";
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
                                    result.innerHTML += "<span style=\'color:red\'>" + i18n.api_error + "</span>";
                                }
                            } catch(e) {
                                result.innerHTML += "<span style=\'color:red\'>" + i18n.parse_error + "</span>";
                            }
                        } else {
                            result.innerHTML += "<span style=\'color:red\'>" + i18n.request_error + "("+xhr.status+")</span>";
                        }
                        running = false;
                        btn.innerText = i18n.submit;
                    }
                };
                xhr.onerror = function(e) {
                    result.innerHTML += "<span style=\"color:red\">" + i18n.network_error + "</span>";
                    running = false;
                    btn.innerText = i18n.submit;
                };
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
                        running = false;
                        if(xhr) xhr.abort();
                        btn.innerText = i18n.submit;
                        result.innerHTML += "<br><span style=\'color:orange\'>" + i18n.stopped + "</span>";
                        return;
                    }
                    var prompt = input.value;
                    if(!prompt) { result.innerHTML = "<span style=\'color:red\'>" + i18n.input_empty + "</span>"; return; }
                    running = true;
                    btn.innerText = i18n.stop;
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
