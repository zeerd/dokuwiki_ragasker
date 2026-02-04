<?php
/**
 * 最小限度的搜索结果处理器
 */
class SearchHelper {

    /**
     * 从ft_pageSearch结果中提取两个列表
     *
     * @param array $searchResults ft_pageSearch()返回的原始结果
     * @return array 包含两个列表的数组
     */
    public function extractLists($searchResults, $maxLines = 3) {
        $links = [];
        $contents = [];

        foreach ($searchResults as $key => $result) {
            $normalized = $this->normalizeResult($key, $result);
            if (!$normalized) {
                continue;
            }

            // 1. 链接列表
            $links[] = $this->extractLinkInfo($normalized);

            // 2. 内容列表
            $contents[] = $this->extractContentInfo($normalized, $maxLines);
        }

        return [
            'links' => $links,
            'contents' => $contents
        ];
    }

    /**
     * 提取链接信息
     */
    private function extractLinkInfo($result) {
        return [
            'id' => $result['id'],
            'title' => $this->getPageTitle($result['id']),
            'url' => wl($result['id']),
            'score' => $result['score'] ?? 0,
            'namespace' => getNS($result['id'])
        ];
    }

    /**
     * 提取内容信息
     */
    private function extractContentInfo($result, $maxLines = 3) {
        return [
            'id' => $result['id'],
            'summary' => $this->getFirstLines($result['id'], $maxLines),
            'has_content' => page_exists($result['id'])
        ];
    }

    /**
     * 兼容不同格式的搜索结果
     */
    private function normalizeResult($key, $score = null) {
        // 结果可能是字符串（页面ID）
        if (is_string($key) && $key !== '') {
            return ['id' => $key, 'score' => $score];
        }

        return null;
    }

    /**
     * 获取页面标题
     */
    private function getPageTitle($pageId) {
        $title = p_get_first_heading($pageId);
        return $title ?: $pageId;
    }

    /**
     * 获取页面前几行作为摘要
     */
    private function getFirstLines($pageId, $maxLines = 3) {
        if (!page_exists($pageId)) {
            return '';
        }

        $content = rawWiki($pageId);
        if ($maxLines === 0) {
            return $content;
        }

        $lines = explode("\n", $content, $maxLines + 1);
        return implode("\n", array_slice($lines, 0, $maxLines));
    }

    /**
     * 使用示例
     */
    public function search($searchQuery) {
        $keywords = trim($searchQuery);
        $highlight = false;
        $searchResults = ft_pageSearch($keywords, $highlight);

        while ((!is_array($searchResults) || count($searchResults) === 0) && strpos($searchQuery, ' ') !== false) {
            $keywordArr = explode(' ', $keywords);
            array_pop($keywordArr);
            $keywords = trim(implode(' ', $keywordArr));
            if ($keywords === '') break;
            $searchResults = ft_pageSearch($keywords, $highlight);
        }

        if ((!is_array($searchResults) || count($searchResults) === 0) && strpos($searchQuery, ' ') !== false) {
            $keywordArr = explode(' ', $searchQuery);
            while (count($keywordArr) > 1) {
                array_shift($keywordArr); // 去掉第一个关键词
                $keywords = trim(implode(' ', $keywordArr));
                if ($keywords === '') break;
                $searchResults = ft_pageSearch($keywords, $highlight);
                if (is_array($searchResults) && count($searchResults) > 0) break;
            }
        }

        if ((!is_array($searchResults) || count($searchResults) === 0) && strpos($searchQuery, ' ') !== false) {
            $keywordArr = explode(' ', trim($searchQuery)); // 用原始关键词
            $mergedResults = [];
            foreach ($keywordArr as $singleKeyword) {
                $singleKeyword = trim($singleKeyword);
                if ($singleKeyword === '') continue;
                $result = ft_pageSearch($singleKeyword, $highlight);
                if (is_array($result) && count($result) > 0) {
                    foreach ($result as $key => $score) {
                        // 用页面ID去重
                        if (is_array($score) && isset($score['score'])) {
                            $scoreValue = $score['score'];
                        } elseif (is_numeric($score)) {
                            $scoreValue = $score;
                        } else {
                            $scoreValue = 0;
                        }
                        if (!isset($mergedResults[$key])) {
                            $mergedResults[$key] = $scoreValue;
                        }
                    }
                }
            }
            if (count($mergedResults) > 0) {
                $searchResults = $mergedResults;
                $keywords = implode(' ', $keywordArr); // 保持原始关键词
            }
        }

        $lists = $this->extractLists($searchResults, 0);
        return $lists;
    }
}
