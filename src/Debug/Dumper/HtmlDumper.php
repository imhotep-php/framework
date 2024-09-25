<?php

namespace Imhotep\Debug\Dumper;

class HtmlDumper extends AbstractDumper
{
    //protected string $output = 'php://output';

    protected array $styles = [
        'str' => 'color: #ffffff;',
        'num' => 'color: #00a8f9;',
        'const' => 'color: #ff8400;',
        'type' => 'color: #808080;',
        'def' => 'color: #808080;',
        'err' => 'color: red;'
    ];


    public function dumpArray(array $values, array $attrs = []): string
    {
        $result = '';

        foreach ($values as $key => $value) {
            $result.= '<li><span style="'.$this->styles['def'].'">'.$key.'</span> <span style="'.$this->styles['def'].'">=></span> '.$value->dump($this).'</li>';
        }

        $tpl = '<div class="imht-dump-array">';
        $tpl.= '<div style="'.$this->styles['const'].'">array (<span style="'.$this->styles['num'].'">'.$attrs['count'].'</span>) [</div>';
        $tpl.= '<ul>'.$result.'</ul>';
        $tpl.= '<div style="'.$this->styles['const'].'">]</div>';
        $tpl.= '</div>';

        return $tpl;
    }

    public function dumpObject(array $values, array $attrs = []): string
    {
        $result = '';

        foreach ($values as $value) {
            $result.= '<li>'.$value->dump($this).'</li>';
        }

        $tpl = '<div class="imht-dump-array">';
        $tpl.= '<div style="'.$this->styles['const'].'">'.$attrs['class_name'].' (#'.$attrs['object_id'].') {</div>';
        $tpl.= '<ul>'.$result.'</ul>';
        $tpl.= '<div style="'.$this->styles['const'].'">}</div>';
        $tpl.= '</div>';

        return $tpl;
    }

    public function dumpProperty(string $name, Data $data, bool $isPublic): string
    {
        $value = $data->dump($this);

        $tpl = '<div class="imht-dump-object">';
        $tpl.= '<span style="'.$this->styles['const'].'">'.($isPublic ? '+' : '-').'</span>';
        $tpl.= '<span style="'.$this->styles['def'].'">'.$name.' = </span>';
        $tpl.= $value;
        $tpl.= '</div>';

        return $tpl;
    }



    public function dump(Data $data)
    {
        $result = $data->dump($this);

        $result = sprintf('<div class="imht-dump">%s</div><style>%s</style>', $result, $this->getStyles());

        $this->write($result);


        return;

        $output = '';
        $depth = 0;
        //ob_start();

        $type = '';
        $value = '';
        $count = '';
        $name = '';

        $type = $this->getType($var);

        if ($this->isSimple($type)) {
            $output = $this->formatSimple($type, $this->getValue($var));

            //$tpl = '<div class="iht-dump-%s">';
            //$tpl.= '<span>%s</span>';
            //$tpl.= '<span> : %s%s</span>';
            //$tpl.= '</div>';

            //$length = $type === 'string' ? '('.mb_strlen($var, 'UTF-8').')' : '';

            //$output = sprintf($tpl, $type, $this->getValue($var), $type, $length);

            //$this->dumpLine($output, $depth++);
        }
        elseif (is_array($var)) {
            $output = '';

            foreach ($var as $key => $val) {
                $tpl = '<li class="iht-dump-row">';
                $tpl.= '<span>%s</span>';
                $tpl.= '<span> => </span>';
                    $tpl.= '<span class="iht-dump-%s">';
                        $tpl.= '<span>%s</span>';
                        $tpl.= '<span> : %s%s</span>';
                    $tpl.= '</span>';
                $tpl.= '</li>';

                $length = $this->getType($val) === 'string' ? '('.mb_strlen($val, 'UTF-8').')' : '';

                $output.= sprintf($tpl, $key, gettype($val), $this->getValue($val), $this->getType($val), $length);
            }

            $tpl = '<div class="iht-dump-array">';
            $tpl.= '<div style="imht-dump-const">array(<span class="imht-dump-num">%s</span>) [</div>';
            $tpl.= '<ul>%s</ul>';
            $tpl.= '<div class="imht-dump-const">]</div>';
            $tpl.= '</div>';

            $output = sprintf($tpl, count($var), $output);
        }

        echo sprintf('<div class="iht-dump">%s</div><style>%s</style>', $output, $this->getStyles());

        //echo $this->output;

        //$content = ob_get_clean();
    }

    protected function _dump(mixed $var, $depth = 0)
    {
        $type = gettype($var);

        if (in_array($type, ['string', 'integer', 'double', 'boolean'])) {
            return $this->formatSimple($type, $this->getValue($var));
        }

        if ($type === 'array') {
            $output = $this->_dump();
            foreach ($var as $key => $val) {
                $output.= sprintf('<div></div>');

                    $this->style('');
                    $this->_dump($val);
            }
        }
    }

    protected function formatSimple(string $type, string $value): string
    {
        if ($type === 'string') {
            $value = $this->style($type, $value, ['length' => mb_strlen($value, 'UTF-8')]);
        }
        else {
            $value = $this->style($type, $value);
        }

        return sprintf('<div class="imh-dump-row">%s</div>', $value);
    }

    protected function formatArray(string $value): string
    {
        return sprintf('<div class="imh-dump-array">%s</div>', $value);
    }

    protected function style(string $style, string $value, array $attrs = []): string
    {
        if ($style === 'def') {
            return sprintf("<span style='%s'>%s</span>", $this->styles[$style], $value);
        }

        if (in_array($style, ['str', 'num', 'const', 'err'])) {
            if ($style === 'str') {
                $value = sprintf('"%s"', $value);
            }
            return sprintf("<span style='%s'>%s</span>", $this->styles[$style], $value);
        }

        if ($style === 'row') {

        }

        return '';
    }

    public function dumpLine(string $output, int $depth)
    {

    }

    public function escape(mixed $value, bool $doubleEncode = true): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8', $doubleEncode);
    }


    public function getStyles()
    {
        $style = '
            .imht-dump {
                position: relative;
                z-index: 10000000;
                background: #2b2b2b;
                color: #fff;
                font-size: 14px;
                padding: 6px 10px;
                font-family: Menlo, Monaco, Consolas, monospace;
                margin-bottom: 6px;
            }
            .imht-dump-row {
                
            }
            
            .imht-dump-integer span:first-child { color: #00a8f9; }
            .imht-dump-integer span:first-child { color: #00a8f9; }
            .imht-dump-boolean span:first-child { color: #FF8400; }
            .imht-dump-double span:first-child { color: #00a8f9; }
            .imht-dump-string span:first-child::before, .iht-dump-string span:first-child::after { content: \'"\'; }
            .imht-dump-integer span:last-child, .iht-dump-boolean span:last-child,
            .imht-dump-double span:last-child, .iht-dump-string span:last-child { opacity: 0.4; }
            
            .imht-dump-array-start,
            .imht-dump-array-end {
                color: #FF8400;
            }
            .imht-dump-array-start span {
                color: #00a8f9;
            }
            .imht-dump-array ul{
                list-style: none;
                margin: 5px 0;
            }
        ';

        return $style;
    }
}