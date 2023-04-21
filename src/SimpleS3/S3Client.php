<?php

namespace Imhotep\SimpleS3;

class S3Client
{
    protected string $access_key;

    protected string $secret_key;

    protected string $endpoint;

    protected mixed $multi_curl;

    protected array $curl_opts;

    public function __construct(string $access_key, string $secret_key, string $endpoint)
    {
        $this->access_key = $access_key;
        $this->secret_key = $secret_key;
        $this->endpoint = $endpoint;

        $this->multi_curl = curl_multi_init();

        $this->curl_opts = array(
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_LOW_SPEED_LIMIT => 1,
            CURLOPT_LOW_SPEED_TIME => 30
        );
    }

    public function __destruct()
    {
        curl_multi_close($this->multi_curl);
    }



    public function copyObject(string $bucket, string $from, string $to, array $args = []): S3Result
    {
        $headers = ['X-Amz-Copy-Source' => "{$bucket}/{$from}"];

        foreach ($args as $k => $v) {
            if (! isset($headers[$k]))
                $headers[$k] = $v;
        }

        $request = $this->request('PUT', "{$bucket}/{$to}", [], $headers);

        return $request->getResult();
    }

    public function deleteObject(string $bucket, string $key, array $args = []): S3Result
    {
        $query = $this->makeQuery(__FUNCTION__, $args);
        $headers = $this->makeHeaders(__FUNCTION__, $args);

        $request = $this->request('DELETE', "{$bucket}/{$key}", $query, $headers);

        return $request->getResult();
    }

    public function deleteObjects(string $bucket, array $keys, bool $quiet = true, array $args = []): S3Result
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?>';
        $body.= '<Delete>';
            $body.= '<Quiet>'.(($quiet)?'true':'false').'</Quiet>';
            foreach ($keys as $key) {
                $body.= '<Object><Key>'.$key.'</Key></Object>';
            }
        $body.= '</Delete>';

        $headers = $this->makeHeaders(__FUNCTION__, $args);

        $request = $this->request('POST', "{$bucket}?delete", [], $headers, $body);

        return $request->getResult();
    }

    public function getObject(string $bucket, string $key, $resource = null, array $args = []): S3Result
    {
        $query = $this->makeQuery(__FUNCTION__, $args);
        $headers = $this->makeHeaders(__FUNCTION__, $args);

        $request = $this->request('GET', "$bucket/$key", $query, $headers);

        if (! is_null($resource)) {
            $request->saveToResource($resource);
        }

        return $request->getResult();
    }

    public function headObject(string $bucket, string $key, array $args = []): S3Result
    {
        $headers = $this->makeHeaders(__FUNCTION__, $args);

        $request = $this->request('HEAD', "$bucket/$key", [], $headers);

        return $request->getResult();
    }

    /**
     * Returns some or all (up to 1,000) of the objects in a bucket.
     *
     * @param string $bucket bucket name.
     * @param array $params [optional] <p>
     * <p><b>marker</b> - Used to get part of the list if all the results do not fit in one answer.</p>
     * <p><b>delimiter</b> - A delimiter is a character you use to group keys.</p>
     * <p><b>encoding-type</b> - Encode object keys in the response. Valid Values: <i>url</i></p>
     * <p><b>max-keys</b> - Sets the maximum number of keys returned in the response. By default the action returns up to 1000 key names.</p>
     * <p><b>prefix</b> - Limits the response to keys that begin with the specified prefix.</p>
     * </p>
     * @return S3Result
     */
    public function listObjects(string $bucket, array $args = []): S3Result
    {
        $query = $this->makeQuery(__FUNCTION__, $args);

        $headers = $this->makeHeaders(__FUNCTION__, $args);

        $request = $this->request('GET', $bucket, $query, $headers);

        return $request->getResult();
    }

    /**
     * Returns some or all (up to 1,000) of the objects in a bucket.
     *
     * @param string $bucket bucket name.
     * @param array $params [optional] <p>
     * <p><b>continuation-token</b> - Used to get part of the list if all the results do not fit in one answer.</p>
     * <p><b>delimiter</b> - A delimiter is a character you use to group keys.</p>
     * <p><b>encoding-type</b> - Encode object keys in the response. Valid Values: <i>url</i></p>
     * <p><b>max-keys</b> - Sets the maximum number of keys returned in the response. By default the action returns up to 1000 key names.</p>
     * <p><b>prefix</b> - Limits the response to keys that begin with the specified prefix.</p>
     * <p><b>start-after</b> - Starts listing after this specified key.</p>
     * </p>
     * @return S3Result
     */
    public function listObjectsV2(string $bucket, array $args = []): S3Result
    {
        return $this->listObjects($bucket, array_merge($args, ['list-type' => 2]));
    }

    public function putObject(string $bucket, string $key, mixed $content, array $args = []): S3Result
    {
        $headers = $this->makeHeaders(__FUNCTION__, $args);

        $request = $this->request('PUT', "{$bucket}/{$key}", [], $headers, $content);

        return $request->getResult();
    }



    public function listBuckets(array $args = []): S3Result
    {
        $headers = $this->makeHeaders(__FUNCTION__, $args);

        $request = $this->request('GET', "", [], $headers);

        return $request->getResult();
    }

    public function headBucket(string $bucket, array $args = []): S3Result
    {
        $headers = $this->makeHeaders(__FUNCTION__, $args);

        $request = $this->request('HEAD', $bucket, [], $headers);

        return $request->getResult();
    }

    public function createBucket(array $args = [])
    {
        return null;
    }

    public function deleteBucket(array $args = [])
    {
        return null;
    }



    protected function request(
        string $action,
        string $path,
        array|string $params = null,
        array $headers = null,
        mixed $body = null
    ): S3Request
    {
        $request = (new S3Request($action, $this->endpoint, $path))
            ->useMultiCurl($this->multi_curl)
            ->useCurlOpts($this->curl_opts);

        if (! is_null($headers)) $request->setHeaders($headers);

        if (! is_null($params)) $request->setParams($params);

        if (! is_null($body)) $request->setBody($body);

        return $request->sign($this->access_key, $this->secret_key);
    }

    protected function makeHeaders(string $method, array $args): array
    {
        $headers = [];

        foreach ($this->headerRules as $header => $methods) {
            if ($methods !== '*') {
                if (! in_array($method, $methods)) continue;
            }

            if (str_ends_with($header, '*')) {
                $start_with = substr($header, 0, -1);

                foreach ($args as $name => $value) {
                    if (str_starts_with($name, $start_with)) {
                        $headers[$name] = $value;
                    }
                }
            }
            elseif (isset($args[$header])) {
                $headers[$header] = $args[$header];
            }
        }

        return $headers;
    }

    protected array $headerRules = [
        // Common
        'Cache-Control' => '*',
        'Content-Disposition' => '*',
        'Content-Encoding' => '*',
        'Content-Type' => '*',

        // Methods
        'Range' => ['headObject','getObject'],
        'If-Modified-Since' => ['headObject','getObject'],
        'If-Unmodified-Since' => ['headObject','getObject'],
        'If-Match' => ['headObject','getObject'],
        'If-None-Match' => ['headObject','getObject'],
        'X-Amz-Meta-*' => ['copyObject','putObject'],
        'X-Amz-Metadata-Directive' => ['copyObject'],
        'X-Amz-Copy-Source' => ['copyObject'],
        'X-Amz-Copy-Source-If-Match' => ['copyObject'],
        'X-Amz-Copy-Source-If-None-Match' => ['copyObject'],
        'X-Amz-Copy-Source-If-Unmodified-Since' => ['copyObject'],
        'X-Amz-Copy-Source-If-Modified-Since' => ['copyObject'],
        'X-Amz-Storage-Class' => ['copyObject','putObject'],
        'X-Amz-Server-Side-Encryption' => ['copyObject','putObject'],
        'X-Amz-Server-Side-Encryption-Aws-Kms-Key-Id' => ['copyObject','putObject'],
        'X-Amz-Bypass-Governance-Retention' => ['deleteObjects'],

        // Acl
        'X-Amz-Acl' => ['putObject'],
        'X-Amz-Grant-Read' => ['putObject'],
        'X-Amz-Grant-Read-Acp' => ['putObject'],
        'X-Amz-Grant-Write-Acp' => ['putObject'],
        'X-Amz-Grant-Full-Control' => ['putObject'],
    ];

    protected function makeQuery(string $method, array $args): array
    {
        $query = [];

        foreach ($args as $name => $value) {
            if (in_array($method, $this->queryRules[$name])) {
                $query[$name] = $value;
            }
        }

        return $query;
    }

    protected array $queryRules = [
        'list-type' => ['listObjects'],
        'continuation-token' => ['listObjects'],
        'delimiter' => ['listObjects'],
        'encoding-type' => ['listObjects'],
        'max-keys' => ['listObjects'],
        'prefix' => ['listObjects'],
        'start-after' => ['listObjects'],
        'marker' => ['listObjects'],
        'versionId' => ['deleteObject', 'getObject'],
        'response-content-type' => ['getObject'],
        'response-content-language' => ['getObject'],
        'response-expires' => ['getObject'],
        'response-cache-control' => ['getObject'],
        'response-content-disposition' => ['getObject'],
        'response-content-encoding' => ['getObject'],
    ];
}