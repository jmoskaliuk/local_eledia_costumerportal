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
 * HTTP client for Directus API calls.
 *
 * @package    local_customerportal
 * @copyright  2026 eLeDia GmbH <info@eledia.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customerportal\local;

/**
 * Central HTTP client. All outgoing requests to Directus go through here.
 * Never called directly from page controllers — use the service classes.
 */
class api_client {
    /** @var string Directus base URL (no trailing slash). */
    private string $baseurl;

    /** @var string Bearer token for Directus service account. */
    private string $token;

    /** @var string Public catalog base URL (no trailing slash). */
    private string $catalogurl;

    /**
     * Constructor — reads connection settings from plugin config.
     */
    public function __construct() {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $this->baseurl    = rtrim((string) get_config('local_customerportal', 'directus_url'), '/');
        $this->token      = (string) get_config('local_customerportal', 'directus_token');
        $this->catalogurl = rtrim((string) get_config('local_customerportal', 'public_catalog_url'), '/');
    }

    /**
     * GET request against the public catalog API (no auth required).
     *
     * @param string $path  Path relative to catalog base URL, e.g. '/search'.
     * @param array  $params Query parameters.
     * @return array Decoded JSON response body.
     * @throws \moodle_exception On HTTP error or JSON decode failure.
     */
    public function catalog_get(string $path, array $params = []): array {
        $url = $this->catalogurl . $path;
        if ($params) {
            $url .= '?' . http_build_query($params);
        }
        return $this->do_get($url, []);
    }

    /**
     * GET request against the Directus private API (Bearer auth).
     *
     * @param string $path   Path relative to Directus base URL.
     * @param array  $params Query parameters.
     * @return array Decoded JSON response body.
     * @throws \moodle_exception On HTTP error or JSON decode failure.
     */
    public function get(string $path, array $params = []): array {
        $url = $this->baseurl . $path;
        if ($params) {
            $url .= '?' . http_build_query($params);
        }
        return $this->do_get($url, $this->auth_headers());
    }

    /**
     * POST request against the Directus private API (Bearer auth).
     *
     * @param string $path Path relative to Directus base URL.
     * @param array  $body Request body (will be JSON-encoded).
     * @return array Decoded JSON response body.
     * @throws \moodle_exception On HTTP error or JSON decode failure.
     */
    public function post(string $path, array $body): array {
        $url = $this->baseurl . $path;

        $curl = new \curl();
        $curl->setHeader(array_merge($this->auth_headers(), ['Content-Type: application/json']));
        $response = $curl->post($url, json_encode($body));

        return $this->parse_response($curl, $response);
    }

    /**
     * Execute a GET request and return the decoded body.
     *
     * @param string   $url
     * @param string[] $headers
     * @return array
     * @throws \moodle_exception
     */
    private function do_get(string $url, array $headers): array {
        $curl = new \curl();
        if ($headers) {
            $curl->setHeader($headers);
        }
        $response = $curl->get($url);
        return $this->parse_response($curl, $response);
    }

    /**
     * Build Bearer auth header array.
     *
     * @return string[]
     */
    private function auth_headers(): array {
        return ['Authorization: Bearer ' . $this->token];
    }

    /**
     * Parse a curl response and throw on HTTP error or invalid JSON.
     *
     * @param \curl  $curl
     * @param string $response
     * @return array
     * @throws \moodle_exception
     */
    private function parse_response(\curl $curl, string $response): array {
        $info = $curl->get_info();
        $httpcode = $info['http_code'] ?? 0;

        if ($httpcode < 200 || $httpcode >= 300) {
            throw new \moodle_exception(
                'error_api_unavailable',
                'local_customerportal',
                '',
                null,
                "HTTP {$httpcode}: {$response}"
            );
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new \moodle_exception(
                'error_api_unavailable',
                'local_customerportal',
                '',
                null,
                'Invalid JSON response'
            );
        }

        return $data;
    }
}
