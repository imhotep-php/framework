<?php declare(strict_types=1);

namespace Imhotep\Debug\Dumper;

use Imhotep\Debug\Data;

class HtmlDumper extends AbstractDumper
{
    //protected string $output = 'php://output';

    protected array $styles = [
        'default' => 'color:#fff; background-color:#2b2b2b; padding:1px 4px; border-radius:4px; font-family: Menlo, Monaco, Consolas, monospace; font-size: 14px; line-height: 1.6;',
        'meta' => 'color:#FF8400;',
        'type' => 'color:#909090;',
        'string' => 'color:#479a47;',
        'number' => 'color:#00a8f9;',
        'boolean' => 'color:#FF8400;',
        'null' => 'color:#FF8400;',
        'const' => 'color:#FF8400;',
        'property' => 'color:#9cdcfe;',
        'visibility' => 'color:#909090;',
        'recursion' => 'color:#FF8400;',
        'uninitialized' => 'color:#909090;',
    ];

    protected static int $uid = 0;

    public function dump(Data $data)
    {
        $result = $this->dumpData($data);
        $result = $this->getHtmlHeader() . sprintf('<pre class="imhotep-dump" style="%s">%s</pre>', $this->styles['default'], $result);
        $this->write($result);
    }

    protected function dumpData(Data $data, int $indent = 0): string
    {
        $indentStr = str_repeat('  ', $indent);
        $result = '';

        $type = $data['type'];
        $value = $data['value'];

        if ($type === 'string') {
            $result = $this->style('string', sprintf('"%s"', htmlspecialchars($value)));
            $result .= ' ' . $this->style('type', 'string(' . $data['length'] . ')');
        } elseif ($type === 'integer' || $type === 'double') {
            $result = $this->style('number', (string)$value);
            $result .= ' ' . $this->style('type', $type);
        } elseif ($type === 'boolean') {
            $result = $this->style('boolean', $value ? 'true' : 'false');
        } elseif ($type === 'NULL') {
            $result = $this->style('null', 'null');
        } elseif ($type === 'array') {
            $uid = ++self::$uid;
            $result = '<span class="imht-toggle" data-uid="'.$uid.'" style="cursor:pointer;user-select:none;">&#9654;</span> ';
            $result .= $this->style('meta', 'array:' . $data['count']);
            $result .= " <span class=\"imht-collapsible\" id=\"imht-collapsible-$uid\" style=\"display:none;\">(\n";
            foreach ($value as $key => $item) {
                $result .= sprintf('%s  [%s] => %s', $indentStr, $key, $this->dumpData($item, $indent + 1)) . "\n";
            }
            $result .= $indentStr . ')</span>';
        } elseif ($type === 'object') {
            $uid = ++self::$uid;
            $result = '<span class="imht-toggle" data-uid="'.$uid.'" style="cursor:pointer;user-select:none;">&#9654;</span> ';
            $result .= $this->style('meta', 'object:' . $data['class_name']) . '#' . $data['object_id'];
            $result .= " <span class=\"imht-collapsible\" id=\"imht-collapsible-$uid\" style=\"display:none;\">(\n";
            foreach ($value as $item) {
                $result .= $indentStr . '  ' . $this->dumpData($item, $indent + 1) . "\n";
            }
            $result .= $indentStr . ')</span>';
        } elseif ($type === 'property') {
            $visibility = $this->style('visibility', $data['visibility']);
            $name = $this->style('property', '$' . $data['name']);
            $value = $this->dumpData($data['value'], $indent);
            $result = sprintf('%s %s: %s', $visibility, $name, $value);
        } elseif ($type === 'recursion' || $type === 'uninitialized' || $type === 'meta') {
            $result = $this->style($type, (string)$value);
        } else {
            $result = htmlspecialchars((string)$value);
        }

        return $result;
    }

    protected function style(string $style, string $value, array $attrs = []): string
    {
        return sprintf('<span style="%s">%s</span>', $this->styles[$style] ?? '', $value);
    }

    protected function getHtmlHeader(): string
    {
        static $header = null;
        if ($header !== null) return '';

        $header = '<script>
            (function(){
                if(window.__imht_dump_inited)return;window.__imht_dump_inited=1;
                document.addEventListener("DOMContentLoaded",function(){
                    document.querySelectorAll(".imht-toggle").forEach(function(btn){
                        btn.addEventListener("click",function(){
                            var uid=btn.getAttribute("data-uid");
                            var coll=document.getElementById("imht-collapsible-"+uid);
                            if(!coll)return;
                            var open=coll.style.display!=="none";
                            coll.style.display=open?"none":"inline";
                            btn.innerHTML=open?"&#9654;":"&#9660;";
                        });
                    });
                });
            })();</script>';

        $header .= '<style>.imht-toggle{font-weight:bold;margin-right:2px;} .imht-collapsible{}</style>';

        return $header;
    }
}