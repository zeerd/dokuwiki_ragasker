<?php

$lang['no_apikey'] = 'Please set the OpenAI API key in the plugin settings first';
$lang['keyword_prompt'] = 'Please extract the 3-5 most important keywords from the following question, return a space-separated list of keywords in order of priority, do not explain:';
$lang['keyword_system'] = 'You are a keyword extraction assistant.';
$lang['summary_prompt'] = 'Based on the following list of pages, combined with the user\'s original question, briefly summarize and answer the user\'s question.';
$lang['summary_system'] = 'You are a dokuwiki knowledge base Q&A assistant.';

// Common errors and tips
$lang['error_api'] = 'API returned an error';
$lang['error_parse'] = 'Failed to parse response';
$lang['error_permission'] = 'Permission denied';
$lang['error_format'] = 'API returned an invalid format';
$lang['error_noresult'] = 'No related content found';
$lang['error_input_empty'] = 'Please enter content';
$lang['error_network'] = 'Network error';
$lang['error_request'] = 'Request failed';

// Steps and dynamic content
$lang['step_title'] = 'Step %d: %s'; // sprintf($lang['step_title'], 1, 'Extract Keywords')
$lang['step_extracting'] = 'Extracting keywords...';
$lang['step_searching'] = 'Searching pages...';
$lang['step_summarizing'] = 'Summarizing answer...';

// Results and content
$lang['user_question'] = 'User question: %s';
$lang['keywords'] = 'Keywords: %s';
$lang['result'] = 'Result: %s';
$lang['search_result'] = 'Search result: %s';
$lang['no_page_found'] = 'No related pages found';
$lang['page_summary'] = '[%s] Summary: %s'; // sprintf($lang['page_summary'], $title, $summary)
$lang['page_list'] = 'Page list:';
$lang['prompt_detail'] = 'Prompt (click to expand)';

// UI
$lang['input_placeholder'] = 'Please enter your question...';
$lang['submit_btn'] = 'Submit';
$lang['stop_btn'] = 'Stop';
$lang['stopped'] = 'Stopped';
