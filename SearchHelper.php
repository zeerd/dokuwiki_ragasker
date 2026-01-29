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
            $normalized = $this->normalizeResult($result, $key);
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
    private function normalizeResult($result, $key = null) {
        // 结果可能是字符串（页面ID）
        if (is_string($result) && $result !== '') {
            return ['id' => $result, 'score' => 0];
        }

        // 结果可能是数组，但键名不一致
        if (is_array($result)) {
            if (!empty($result['id'])) {
                return $result;
            }
            if (!empty($result['page'])) {
                $result['id'] = $result['page'];
                return $result;
            }
            if (!empty($result[0]) && is_string($result[0])) {
                return ['id' => $result[0], 'score' => $result['score'] ?? 0];
            }
        }

        // 结果可能是标量分数，ID 在 key 上
        if (is_string($key) && $key !== '') {
            return ['id' => $key, 'score' => is_numeric($result) ? $result : 0];
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
    public function exampleUsage($searchQuery) {
        // 1. 执行搜索
        $rawResults = ft_pageSearch($searchQuery);

        // 2. 提取两个列表
        $lists = $this->extractLists($rawResults);

        // 3. 返回结果
        return $lists;
    }
}
