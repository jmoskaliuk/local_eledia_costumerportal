<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Catalog service — consumes the shared public catalog contract.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal\local;

/**
 * Wraps calls to GET /v1/catalog/search and GET /v1/catalog/{slug}.
 * Results are cached via Moodle Cache API.
 */
class catalog_service {
    /** @var api_client */
    private api_client $client;

    /**
     * Constructor.
     *
     * @param api_client|null $client Optional API client for testing.
     */
    public function __construct(?api_client $client = null) {
        $this->client = $client ?? new api_client();
    }

    /**
     * Search the public catalog.
     *
     * @param array $filters Supported keys: q, entry_type, plugin_type, proven_badge,
     *                       supported_moodle, page, page_size, sort.
     * @return array{data: array, meta: array}
     */
    public function search(array $filters = []): array {
        $cachekey = 'search_' . md5(serialize($filters));
        $cache    = \cache::make('local_customerportal', 'catalogsearch');

        if ($cached = $cache->get($cachekey)) {
            return $cached;
        }

        $result = $this->client->catalog_get('/search', $filters);
        $cache->set($cachekey, $result);

        return $result;
    }

    /**
     * Fetch a single catalog entry by slug.
     *
     * @param string $slug
     * @return array The 'data' payload from the API.
     * @throws \moodle_exception If not found or API error.
     */
    public function get_detail(string $slug): array {
        $cachekey = 'detail_' . md5($slug);
        $cache    = \cache::make('local_customerportal', 'catalogdetail');

        if ($cached = $cache->get($cachekey)) {
            return $cached;
        }

        $result = $this->client->catalog_get('/' . urlencode($slug));
        $data   = $result['data'] ?? [];

        $runbotdemoid = trim((string) ($data['runbot_demo_id'] ?? ''));
        $data['runbot_demo_id'] = $runbotdemoid === '' ? null : $runbotdemoid;

        $cache->set($cachekey, $data);

        return $data;
    }
}
