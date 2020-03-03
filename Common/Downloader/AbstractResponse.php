<?php

declare(strict_types=1);

/**
 * Copyright (c) 2018-2020 Daniel Bannert
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/narrowspark/automatic
 */

namespace Narrowspark\Automatic\Prefetcher\Common\Downloader;

/**
 * This file is automatically generated, dont change this file, otherwise the changes are lost after the next mirror update.
 *
 * @codeCoverageIgnore
 *
 * @internal
 */
abstract class AbstractResponse
{
    /** @var null|array */
    protected $body;

    /** @var array<int|string, string> */
    protected $origHeaders;

    /** @var array<int|string, array<int, string>> */
    protected $headers;

    /** @var int */
    protected $code;

    /**
     * @param array<int|string, string> $headers
     */
    public function __construct(?array $body, array $headers = [], int $code = 200)
    {
        $this->body = $body;
        $this->origHeaders = $headers;
        $this->headers = $this->parseHeaders($headers);
        $this->code = $code;
    }

    /**
     * Gets the body of the message.
     */
    abstract public function getBody();

    /**
     * Returns the header array on given header name.
     *
     * @return array<int, int|string>
     */
    final public function getHeaders(string $name): array
    {
        return $this->headers[\strtolower($name)] ?? [];
    }

    /**
     * Returns the first value from header array on given header name.
     */
    final public function getHeader(string $name): string
    {
        return $this->headers[\strtolower($name)][0] ?? '';
    }

    /**
     * Returns the header before the parsing was done.
     */
    final public function getOriginalHeaders(): array
    {
        return $this->origHeaders;
    }

    /**
     * Gets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int status code
     */
    final public function getStatusCode(): int
    {
        return $this->code;
    }

    /**
     * @param array<int|string, string> $headers
     *
     * @return array<int|string, array<int, string>>
     */
    private function parseHeaders(array $headers): array
    {
        $values = [];

        foreach (\array_reverse($headers) as $header) {
            if (\preg_match('{^([^\:]+):\s*(.+?)\s*$}i', $header, $match) === 1) {
                $values[\strtolower($match[1])][] = $match[2];
            } elseif (\preg_match('{^HTTP/}i', $header) === 1) {
                break;
            }
        }

        return $values;
    }
}
