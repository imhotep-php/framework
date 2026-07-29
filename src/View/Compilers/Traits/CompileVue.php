<?php declare(strict_types=1);

namespace Imhotep\View\Compilers\Traits;

trait CompileVue
{
    public function compileVue(mixed $expression): string
    {
        return "<?php echo app(\Imhotep\View\VueRegistry::class)->boot{$expression}; ?>";
    }

    public function compileVuehead(): string
    {
        return "<?php echo app(\Imhotep\View\VueRegistry::class)->renderStyles(); ?>";
    }

    public function compileVuecomposables(mixed $expression): string
    {
        $expression = $expression ? $this->stripBrackets($expression) : '["*"]';

        return "<?php echo app(\Imhotep\View\VueRegistry::class)->scanComposables($expression); ?>";
    }

    protected function compileVueTags(string $content): string
    {
        $vueRegistrations = [];

        $pattern = '/<((?:[\w:-]+:)?v-[\w:-]+|(?<![\w:-])(?:[\w:-]+:)?[A-Z][\w:-]*)(.*?)(\/>|>(?:.*?)<\/\1>)/s';
        $content = preg_replace_callback($pattern, function ($matches) use (&$vueRegistrations) {
            $fullName = $matches[1];
            $attributesStr = $matches[2];
            $fullSuffix = $matches[3]; // /> or >...</...>

            $ns = '';
            $name = $fullName;
            if (str_contains($name, '::')) {
                list($ns, $name) = explode('::', $name);
            }

            if (str_starts_with($name, 'v-')) {
                $name = substr($name, 2);
            }

            $registryPath = empty($ns) ? $name : "$ns::$name";

            $phpId = 'v_' . preg_replace('/[^A-Za-z0-9_]/', '_', $ns ? "$ns $name" : $name);

            $vueRegistrations[] = "<?php \${$phpId} = app(\Imhotep\View\VueRegistry::class)->add('{$registryPath}') ?: '{$name}'; ?>";

            $pattern = '/(data\-initial|:[\w-]+)="(\$[a-zA-Z0-9_>\'\[\]-]+)"/';
            $attributesStr = preg_replace_callback($pattern, function ($attrMatches) {
                $name = $attrMatches[1];
                if (str_starts_with($name, ':')) {
                    $name = "data-initial-".substr($name, 1);
                }

                return $name . '="' . '<?php echo escape(json_encode(' . $attrMatches[2] . ')) ?>' . '"';
            }, $attributesStr);

            return sprintf('<div data-v-component="<?php echo $%s ?>" %s></div>', $phpId, $attributesStr);
        }, $content);

        $vueRegistrations = implode("\n", $vueRegistrations);
        if (! empty($vueRegistrations)) {
            $vueRegistrations.= "\n";
        }

        return $vueRegistrations.$content;
    }
}