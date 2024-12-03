<?php declare(strict_types=1);

namespace Imhotep\Console\Formatter;

class Formatter
{
    protected array $styles = [];

    protected bool $decorated = true;

    protected int $position = 0;

    protected array $tokens = [];

    public function __construct(){
        $this->createStyle('b', new Style(options: [Option::bold]));
        $this->createStyle('i', new Style(options: [Option::italic]));
        $this->createStyle('dim', new Style(options: [Option::dim]));
        $this->createStyle('u', new Style(options: [Option::underline]));
        $this->createStyle('blink', new Style(options: [Option::blink]));
        $this->createStyle('reverse', new Style(options: [Option::reverse]));
        $this->createStyle('hidden', new Style(options: [Option::hidden]));

        $this->createStyle('success', new Style(Color::green));
        $this->createStyle('info', new Style(Color::blue));
        $this->createStyle('warn', new Style(Color::yellow));
        $this->createStyle('error', new Style(Color::red));
    }

    public function setDecorated($decorated): void
    {
        $this->decorated = $decorated;
    }

    public function isDecorated(): bool
    {
        return $this->decorated;
    }

    public function getStyles(): array
    {
        return $this->styles;
    }

    public function getStyle($name): ?Style
    {
        if($this->hasStyle($name)){
            return $this->styles[$name];
        }

        return null;
    }

    public function hasStyle($name): bool
    {
        return isset($this->styles[$name]);
    }

    public function createStyle($tagName, $style): void
    {
        $this->styles[$tagName] = $style;
    }

    public function getStringLength($string, $isDecoration = false): int
    {
        if ($isDecoration) {
            return mb_strlen($string, 'UTF-8');
        }

        $tokens = $this->getTokens($string);

        $text = '';

        foreach ($tokens as $token) {
            if (! $token['tag']) $text .= $token['text'];
        }

        return mb_strlen($text, 'UTF-8');
    }

    public function format($string): string
    {
        $tokens = $this->getTokens($string);

        if (! $this->decorated) {
            $text = '';
            foreach ($tokens as $token) {
                if (! $token['tag']) $text .= $token['text'];
            }
            return $text;
        }

        return $this->formatting($this->makeTokens($tokens));
    }

    private function getTokens($string): array
    {
        $tokens = []; $offset = 0;

        preg_match_all("/<\/?[a-z;,-= ]+>/i", $string, $matches, \PREG_OFFSET_CAPTURE);

        $matches = $matches[0];

        for ($i=0; $i<count($matches); $i++) {
            $match = $matches[$i];

            if ($offset < $match[1]) {
                $length = $match[1]-$offset;
                $tokens[] = ['tag' => false, 'text' => substr($string, $offset, $length)];
                $offset += $length;
            }

            $tokens[] = ['tag' => true, 'text' => $match[0]];
            $offset += strlen($match[0]);
        }

        if ($offset < strlen($string)) {
            $tokens[] = ['tag' => false, 'text' => substr($string, $offset, strlen($string) - $offset)];
        }

        return $tokens;
    }

    private function formatting(array $tokens): string
    {
        $text = '';

        foreach ($tokens as $token) {
            if (isset($token['children'])) {
                $value = $this->formatting($token['children']);

                if (! $this->decorated) {
                    $text .= $value;
                }
                elseif ($style = $this->resolveStyleByTag($token['tag'])) {
                    $text .= $style->apply($value);
                }
                else {
                    $text .= $value;
                }
            }
            elseif ($token['type'] == 'text') {
                $text.= $token['value'];
            }
        }

        return $text;
    }

    private function makeTokens(&$rawTokens): array
    {
        $tokens = [];

        while ($token = array_shift($rawTokens)) {
            if ($token['tag']) {
                if (str_starts_with($token['text'], "</")) {
                    break;
                }

                $tokens[] = $this->createTokenTag($token['text'], $this->makeTokens($rawTokens));
            }
            else {
                $tokens[] = $this->createTokenText($token['text']);
            }
        }

        return $tokens;
    }

    private function isCloseTag($currentTag, $nextTag): bool
    {
        if (is_null($nextTag)) {
            return true;
        }

        if ($nextTag['text'] == '</>') {
            return true;
        }

        if (str_starts_with("</", $nextTag['text'])) {
            return false;
        }

        $currentTag = trim(str_replace(['<','>'], '', $currentTag['text']));
        $nextTag = trim(str_replace(['<','>','/'], '', $nextTag['text']));

        if ($currentTag === $nextTag) {
            return true;
        }

        return false;
    }

    private function createTokenText($value): array
    {
        return [
            'type' => 'text',
            'value' => $value
        ];
    }

    private function createTokenTag($tag, $children): array
    {
        return [
            'type' => 'tag',
            'tag' => $tag,
            'children' => $children
        ];
    }

    private function resolveStyleByTag($tag): ?Style
    {
        if (preg_match('/^<([a-z]+)>$/i', $tag, $match)) {
            return $this->getStyle($match[1]);
        }

        return $this->resolveCustomTag($tag);
    }

    private function resolveCustomTag($tag): ?Style
    {
        if (!preg_match_all('/(bg|fg|options)=([A-z-]+)/', $tag, $matches, PREG_SET_ORDER)) {
            return null;
        }

        $style = new Style();
        foreach ($matches as $match) {
            if($match[1] == 'fg') $style->setForeground(Color::getByName($match[2]));
            if($match[1] == 'bg') $style->setBackground(Color::getByName($match[2]));
            if($match[1] == 'options') {
                $options = explode(",", $match[2]);
                foreach($options as $option){
                    $option = preg_replace("/([^a-z])/", "", $option);
                    if($option = Option::getByName($option)){
                        $style->setOption($option);
                    }
                }
            }
        }

        return $style;
    }
}