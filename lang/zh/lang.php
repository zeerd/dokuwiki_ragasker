<?php

$lang['no_apikey'] = '请先在插件配置中设置 OpenAI API 密钥';
$lang['keyword_prompt'] = '请从以下问题中提取3-5个最重要的关键词，返回一个用空格分隔的关键词列表，以优先度排列，不要解释：';
$lang['keyword_system'] = '你是一个关键词提取助手。';
$lang['summary_prompt'] = '请根据以下页面列表，结合用户原始问题，简要总结并回答用户的问题。';
$lang['summary_system'] = '你是一个dokuwiki知识库问答助手。';

// 通用错误与提示
$lang['error_api'] = 'API返回异常';
$lang['error_parse'] = '解析响应失败';
$lang['error_permission'] = '权限不足';
$lang['error_format'] = 'API返回格式异常';
$lang['error_noresult'] = '未找到相关内容';
$lang['error_input_empty'] = '请输入内容';
$lang['error_network'] = '网络错误';
$lang['error_request'] = '请求失败';

// 步骤与动态内容
$lang['step_title'] = '步骤%d：%s'; // sprintf($lang['step_title'], 1, '提取关键词')
$lang['step_extracting'] = '正在提取关键词...';
$lang['step_searching'] = '正在搜索页面...';
$lang['step_summarizing'] = 'AI总结回答...';

// 结果与内容
$lang['user_question'] = '用户问题：%s';
$lang['keywords'] = '关键词：%s';
$lang['result'] = '结果：%s';
$lang['search_result'] = '搜索结果：%s';
$lang['no_page_found'] = '未找到相关页面';
$lang['page_summary'] = '【%s】摘要：%s'; // sprintf($lang['page_summary'], $title, $summary)
$lang['page_list'] = '页面列表：';
$lang['prompt_detail'] = '提示词（点击展开）';

// 按钮与界面
$lang['input_placeholder'] = '请输入你的问题...';
$lang['submit_btn'] = '提交';
$lang['stop_btn'] = '终止';
$lang['stopped'] = '已终止';
