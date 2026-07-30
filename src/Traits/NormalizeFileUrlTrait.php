<?php

namespace Macareux\ContentImporter\Traits;

use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Http\Request;

trait NormalizeFileUrlTrait
{
    /**
     * @var string|null
     */
    private $documentRoot;

    public function getDocumentRoot(): ?string
    {
        return $this->documentRoot;
    }

    public function setDocumentRoot(string $documentRoot): void
    {
        $this->documentRoot = $documentRoot;
    }

    public function supportPreview(): bool
    {
        return true;
    }

    public function validateRequest(Request $request): ErrorList
    {
        $error = new ErrorList();
        $documentRoot = trim((string) $request->get('documentRoot'));
        if ($documentRoot === '') {
            $error->add(t('Please input document root.'));

            return $error;
        }

        $parts = parse_url($documentRoot);
        $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
        if ($parts === false || !isset($parts['host']) || !in_array($scheme, ['http', 'https'], true)) {
            $error->add(t('Document root must be an HTTP or HTTPS URL.'));
        }

        return $error;
    }

    /**
     * Convert a relative URL to an absolute URL resolved against the document
     * root, like a browser would: `../` goes up one level from the document
     * root path (clamped at the host root), and root-relative paths (`/foo`)
     * resolve against the host root. Absolute HTTP(S) URLs are left absolute
     * (with path dot-segments normalized). Non-HTTP schemes are unchanged.
     */
    protected function normalizeFileUrl(string $url): string
    {
        $url = trim(urldecode($url));
        if ($url === '') {
            return '';
        }

        // Fragment-only or non-path schemes that should not be rewritten
        if (strpos($url, '#') === 0) {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        if (isset($parts['scheme'])) {
            $scheme = strtolower($parts['scheme']);
            if ($scheme === 'http' || $scheme === 'https') {
                return $this->buildUrlFromParts($this->normalizePathInParts($parts));
            }

            return $url;
        }

        $documentRoot = rtrim((string) $this->getDocumentRoot(), '/');
        if ($documentRoot === '') {
            return $url;
        }

        $rootParts = parse_url($documentRoot);
        if ($rootParts === false || !isset($rootParts['scheme'], $rootParts['host'])) {
            return $url;
        }

        $relativePath = $parts['path'] ?? '';
        // parse_url may leave path empty for some relative inputs; fall back to the raw string before query/fragment
        if ($relativePath === '' && !isset($parts['host'])) {
            $relativePath = preg_replace('/[?#].*$/', '', $url) ?? $url;
        }

        if (strpos($relativePath, '/') === 0) {
            // Root-relative path: resolve against the host root
            $fullPath = $relativePath;
        } else {
            $rootPath = isset($rootParts['path']) ? (string) $rootParts['path'] : '';
            $fullPath = rtrim($rootPath, '/') . '/' . $relativePath;
        }

        $merged = [
            'scheme' => $rootParts['scheme'],
            'host' => $rootParts['host'],
            'path' => '/' . implode('/', $this->pathToSegments($fullPath)),
        ];
        if (isset($rootParts['port'])) {
            $merged['port'] = $rootParts['port'];
        }
        if (isset($rootParts['user'])) {
            $merged['user'] = $rootParts['user'];
        }
        if (isset($rootParts['pass'])) {
            $merged['pass'] = $rootParts['pass'];
        }
        if (isset($parts['query'])) {
            $merged['query'] = $parts['query'];
        }
        if (isset($parts['fragment'])) {
            $merged['fragment'] = $parts['fragment'];
        }

        return $this->buildUrlFromParts($merged);
    }

    /**
     * @param array $parts parse_url parts
     *
     * @return array
     */
    private function normalizePathInParts(array $parts): array
    {
        if (!isset($parts['path'])) {
            return $parts;
        }
        $segments = $this->pathToSegments($parts['path']);
        $parts['path'] = '/' . implode('/', $segments);

        return $parts;
    }

    /**
     * Split a path into segments, resolving `.` and `..`.
     * `..` pops the previous segment; excess `..` at the host root is dropped.
     *
     * @return string[]
     */
    private function pathToSegments(string $path): array
    {
        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                if (!empty($segments)) {
                    array_pop($segments);
                }
                continue;
            }
            $segments[] = $segment;
        }

        return $segments;
    }

    /**
     * @param array $parts parse_url-style parts
     */
    private function buildUrlFromParts(array $parts): string
    {
        $result = '';
        if (isset($parts['scheme'])) {
            $result .= $parts['scheme'] . '://';
        }
        if (isset($parts['host'])) {
            if (isset($parts['user'])) {
                $result .= $parts['user'];
                if (isset($parts['pass'])) {
                    $result .= ':' . $parts['pass'];
                }
                $result .= '@';
            }
            $result .= $parts['host'];
            if (isset($parts['port'])) {
                $result .= ':' . $parts['port'];
            }
        }
        $result .= $parts['path'] ?? '';
        if (isset($parts['query'])) {
            $result .= '?' . $parts['query'];
        }
        if (isset($parts['fragment'])) {
            $result .= '#' . $parts['fragment'];
        }

        return $result;
    }
}
