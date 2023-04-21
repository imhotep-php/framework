<?php

namespace Imhotep\SimpleS3;

/**
 *
 */
class S3Request
{
    protected string $action;

    protected string $endpoint;

    protected string $path;

    protected array $headers;

    protected array|string $params;

    protected mixed $body;

    protected mixed $multi_curl = null;

    protected mixed $curl;

    protected S3Response $response;

    public function __construct(string $action, string $endpoint, string $path)
    {
        $this->action = $action;
        $this->endpoint = $endpoint;
        $this->path = $path;

        $this->headers = [
            'Content-MD5' => '',
            'Content-Type' => '',
            'Date' => gmdate('D, d M Y H:i:s T'),
            'Host' => $this->endpoint
        ];

        $this->curl = curl_init();

        $this->response = new S3Response();
    }

    public function saveToResource($resource): static
    {
        $this->response->saveToResource($resource);

        return $this;
    }

    public function setHeaders(array $headers): static
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    public function setParams(array|string $params): static
    {
        $this->params = $params;

        return $this;
    }

    public function setBody(mixed $body): static
    {
        if (is_resource($body)) {
            $hash_ctx = hash_init('md5');
            $length = hash_update_stream($hash_ctx, $body);
            $md5 = hash_final($hash_ctx, true);

            rewind($body);

            curl_setopt($this->curl, CURLOPT_PUT, true);
            curl_setopt($this->curl, CURLOPT_INFILE, $body);
            curl_setopt($this->curl, CURLOPT_INFILESIZE, $length);
        }
        else {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $body);
            $md5 = md5($body, true);
        }

        $this->headers['Content-MD5'] = base64_encode($md5);

        return $this;
    }

    public function sign($access_key, $secret_key): static
    {
        $canonicalAmzHeaders = $this->getCanonicalAmzHeaders();

        $string_to_sign  = "{$this->action}\n";
        $string_to_sign .= "{$this->headers['Content-MD5']}\n";
        $string_to_sign .= "{$this->headers['Content-Type']}\n";
        $string_to_sign .= "{$this->headers['Date']}\n";

        if (! empty($canonicalAmzHeaders)) {
            $string_to_sign .= implode("\n", $canonicalAmzHeaders) . "\n";
        }

        $string_to_sign .= "/{$this->path}";

        $signature = base64_encode(
            hash_hmac('sha1', $string_to_sign, $secret_key, true)
        );

        $this->headers['Authorization'] = "AWS $access_key:$signature";

        return $this;
    }

    public function useMultiCurl($mh): static
    {
        $this->multi_curl = $mh;

        return $this;
    }

    public function useCurlOpts($curl_opts): static
    {
        curl_setopt_array($this->curl, $curl_opts);

        return $this;
    }

    public function getResponse(): S3Response
    {
        $http_headers = array_map(
            function ($header, $value) {
                return "$header: $value";
            },
            array_keys($this->headers),
            array_values($this->headers)
        );

        $queryParams = "";
        if (! empty($this->params)) {
            if (is_string($this->params)) {
                $queryParams = '?'.$this->params;
            }
            elseif (is_array($this->params)) {
                $queryParams = '?'.http_build_query($this->params);
            }
        }

        curl_setopt_array($this->curl, array(
            CURLOPT_USERAGENT => 'imhotep/s3-client',
            CURLOPT_URL => "https://{$this->endpoint}/{$this->path}{$queryParams}",
            CURLOPT_HTTPHEADER => $http_headers,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_WRITEFUNCTION => array(
                $this->response, '__curlWriteFunction'
            ),
            CURLOPT_HEADERFUNCTION => array(
                $this->response, '__curlHeaderFunction'
            ),
        ));

        switch ($this->action) {
            case 'DELETE':
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'HEAD':
                curl_setopt($this->curl, CURLOPT_NOBODY, true);
                break;
            case 'POST':
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
                break;
            case 'PUT':
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                break;
        }

        if (isset($this->multi_curl)) {
            curl_multi_add_handle($this->multi_curl, $this->curl);

            $running = null;
            do {
                curl_multi_exec($this->multi_curl, $running);
                curl_multi_select($this->multi_curl);
            } while ($running > 0);

            curl_multi_remove_handle($this->multi_curl, $this->curl);
        } else {
            $success = curl_exec($this->curl);
        }

        $this->response->finalize($this->curl);

        curl_close($this->curl);

        return $this->response;
    }

    public function getResult(): S3Result
    {
        return $this->getResponse()->getResult();
    }

    protected function getCanonicalAmzHeaders(): array
    {
        $canonical_amz_headers = array();

        foreach ($this->headers as $header => $value) {
            $header = trim(strtolower($header));
            $value = trim($value);

            if (str_starts_with($header, 'x-amz-')) {
                $canonical_amz_headers[$header] = "$header:$value";
            }
        }

        ksort($canonical_amz_headers);

        return $canonical_amz_headers;
    }
}