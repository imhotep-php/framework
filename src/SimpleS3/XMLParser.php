<?php

declare(strict_types=1);

namespace Imhotep\SimpleS3;

use SimpleXMLElement;

class XMLParser
{
    public bool $error = false;

    public string $name = '';

    public string $content = '';

    public array $data = [];

    public function __construct(string $content)
    {
        $this->content = $content;

        $xml = simplexml_load_string($this->content);

        $this->name = $xml->getName();

        if ($this->name === 'Error') {
            $this->error = true;
        }

        $this->data = $this->parse($xml, $xml->getName());
    }

    protected function parse(SimpleXMLElement $nodes, string $prefix = ''): mixed
    {
        $result = [];

        foreach ($nodes as $name => $value) {
            $type = $this->getType($name, $prefix);

            if ($type === 'array') {
                $result[] = $this->parse($value, "{$prefix}.{$name}");
            }
            elseif ($type === 'object') {
                $result[$name] = $this->parse($value, "{$prefix}.{$name}");
            }
            elseif ($type === 'bool') {
                $result[$name] = boolval($value);
            }
            elseif ($type === 'int') {
                $result[$name] = intval($value);
            }
            elseif ($type === 'float') {
                $result[$name] = floatval($value);
            }
            elseif ($type === 'string') {
                $result[$name] = strval($value);
            }
            elseif ($type === 'etag') {
                $result[$name] = trim(strval($value), '"');
            }
        }

        return $result;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isError(): bool
    {
        return $this->error;
    }

    public function getData(): array
    {
        return $this->data;
    }

    protected function getType(string $nodeName, string $prefix): string
    {
        $key = empty($prefix) ? $nodeName : "{$prefix}.{$nodeName}";

        if (isset($this->rules[$key])) {
            return $this->rules[$key];
        }

        return 'string';
        //throw new \Exception("Type [$key] not found.");
    }

    protected array $rules = [
        'Error' => 'array',
        'Error.Code' => 'string',
        'Error.Message' => 'string',
        'Error.Resource' => 'string',
        'Error.RequestId' => 'string',
        'Error.SignatureProvided' => 'string',
        'Error.StringToSign' => 'string',
        'Error.StringToSignBytes' => 'string',
        'Error.Method' => 'string',

        'CopyObjectResult' => 'array',
        'CopyObjectResult.LastModified' => 'string',
        'CopyObjectResult.ETag' => 'etag',

        'DeleteResult' => 'array',
        'DeleteResult.Deleted' => 'array',
        'DeleteResult.Deleted.Key' => 'string',
        'DeleteResult.Deleted.VersionId' => 'string',

        'ListAllMyBucketsResult' => 'object',
        'ListAllMyBucketsResult.Owner' => 'object',
        'ListAllMyBucketsResult.Owner.DisplayName' => 'string',
        'ListAllMyBucketsResult.Owner.ID' => 'string',
        'ListAllMyBucketsResult.Buckets' => 'object',
        'ListAllMyBucketsResult.Buckets.Bucket' => 'array',
        'ListAllMyBucketsResult.Buckets.Bucket.Name' => 'string',
        'ListAllMyBucketsResult.Buckets.Bucket.CreationDate' => 'string',

        'ListBucketResult' => 'array',
        'ListBucketResult.Name' => 'string',
        'ListBucketResult.Prefix' => 'string',
        'ListBucketResult.Delimiter' => 'string',
        'ListBucketResult.Marker' => 'string',
        'ListBucketResult.NextMarker' => 'string',
        'ListBucketResult.ContinuationToken' => 'string',
        'ListBucketResult.NextContinuationToken' => 'string',
        'ListBucketResult.StartAfter' => 'string',
        'ListBucketResult.EncodingType' => 'string',
        'ListBucketResult.KeyCount' => 'int',
        'ListBucketResult.MaxKeys' => 'int',
        'ListBucketResult.IsTruncated' => 'bool',
        'ListBucketResult.CommonPrefixes' => 'array',
        'ListBucketResult.CommonPrefixes.Prefix' => 'string',
        'ListBucketResult.Contents' => 'array',
        'ListBucketResult.Contents.Key' => 'string',
        'ListBucketResult.Contents.LastModified' => 'string',
        'ListBucketResult.Contents.Owner' => 'array',
        'ListBucketResult.Contents.Owner.ID' => 'string',
        'ListBucketResult.Contents.Owner.DisplayName' => 'string',
        'ListBucketResult.Contents.ETag' => 'etag',
        'ListBucketResult.Contents.Size' => 'int',
        'ListBucketResult.Contents.StorageClass' => 'string',
    ];
}