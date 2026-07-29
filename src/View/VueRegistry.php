<?php declare(strict_types=1);

namespace Imhotep\View;

use Imhotep\Facades\View;
use Imhotep\View\Engines\ScssEngine;

class VueRegistry
{
    protected bool $composableScanned = false;

    protected array $composables = [];

    protected array $components = [];

    protected array $scripts = []; // Stores path to file OR raw content for virtual files

    protected array $styles = [];

    protected array $externalStyles = [];

    protected array $externalScripts = [];

    protected array $loaded = [];

    // Track if a script key is "virtual" (content vs path)
    protected array $virtualScripts = [];

    public function add(string $name, array $props = []): ?string
    {
        $found = View::getFinder()->findVueComponent($name);

        if (! $found) {
            return null;
        }

        $requestKey = $found['view'];
        if (isset($this->loaded[$requestKey])) {
            return $found['name'];
        }
        $this->loaded[$requestKey] = true;

        $path = $found['path'];
        $ext = $found['extension'];
        $canonicalName = $found['name'];

        if ($ext === 'vue') {
            $this->processVueFile($path, $canonicalName, $requestKey);
        }
        else {
            // Standard JS handling
            $this->scripts[$requestKey] = $path;

            $scssPath = str_replace('.js', '.scss', $path);
            if (file_exists($scssPath)) {
                $this->styles[$requestKey] = $this->compileScss($scssPath);
            }

            $this->scanMetadata($path, false);
        }

        $this->components[] = [
            'name' => $canonicalName,
            'props' => $props
        ];

        return $canonicalName;
    }

    protected function processVueFile(string $path, string $componentName, string $requestKey): void
    {
        $content = file_get_contents($path);

        $getStartTemplate = function ($pattern, $text) {
            if (preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                return (object)[
                    'offset' => $matches[0][1],
                    'length' => strlen($matches[0][0])
                ];
            }
            return (object)['offset' => 0, 'length' => 0];
        };

        $getEndTemplate = function ($pattern, $text) {
            if (preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                $end = end($matches[0]);

                return (object)[
                    'offset' => $end[1],
                    'length' => strlen($end[0])
                ];
            }
            return (object)['offset' => 0, 'length' => 0];
        };

        $start = $getStartTemplate('/<template.*?>/', $content);
        $end = $getEndTemplate('/<\/template>/', $content);

        $template = '';
        if ($end->offset > $start->offset) {
            // Parse template with root template <template ...></template>
            if ($start->length > 10) {
                $offset = $start->offset;
                $length = $end->offset + $end->length - $offset;
            }
            else {
                $offset = $start->offset + $start->length;
                $length = $end->offset - $offset;
            }

            $template = trim(substr($content, $offset, $length));
        }

        // 2. Extract <script>
        $script = '';
        if (preg_match('/<script>(.*?)<\/script>/s', $content, $matches)) {
            $script = trim($matches[1]);
        }

        // 3. Extract <style>
        if (preg_match('/<style.*?(?:lang="scss")?>(.*?)<\/style>/s', $content, $matches)) {
            $scss = $matches[1];
            $this->styles[$requestKey] = $this->compileScssString($scss, dirname($path));
        }

        // 4. Transform Script
        $this->scanMetadataString($script);

        if (empty($script)) {
            $script = "export default {}";
        }

        $transformedScript = str_replace('export default', 'const componentDef =', $script);

        // Escape template backticks for JS string
        $escapedTemplate = str_replace(['`', '\\'], ['\`', '\\\\'], $template);

        $finalParams = "{ name: '{$componentName}', component: componentDef }";

        $finalScript = "
            {$transformedScript}
            componentDef.template = `{$escapedTemplate}`;
            (window.\$vueComponents = window.\$vueComponents || []).push({$finalParams});
        ";

        // Register as virtual script
        $this->scripts[$requestKey] = $finalScript;
        $this->virtualScripts[$requestKey] = true;
    }

    protected function scanMetadata(string $path, bool $isFile = true): void
    {
        $content = $isFile ? file_get_contents($path) : $path;
        $this->scanMetadataString($content);
    }

    protected function scanMetadataString(string $content): void
    {
        // 1. Dependencies
        if (preg_match('/depends:\s*\[(.*?)\]/s', $content, $matches)) {
            $depsString = $matches[1];
            if (preg_match_all('/[\'"]([\w:-]+)[\'"]/', $depsString, $deps)) {
                foreach ($deps[1] as $depName) {
                    $this->add($depName);
                }
            }
        }

        // 2. Head
        if (preg_match('/head:\s*\{(.*?)\}/s', $content, $headMatches)) {
            $headContent = $headMatches[1];

            // Styles
            if (preg_match('/styles:\s*\[(.*?)\]/s', $headContent, $styleMatches)) {
                if (preg_match_all('/[\'"](.*?)[\'"]/', $styleMatches[1], $styles)) {
                    foreach ($styles[1] as $url) {
                        if (!in_array($url, $this->externalStyles)) {
                            $this->externalStyles[] = $url;
                        }
                    }
                }
            }

            // Scripts
            if (preg_match('/scripts:\s*\[(.*?)\]/s', $headContent, $scriptMatches)) {
                if (preg_match_all('/[\'"](.*?)[\'"]/', $scriptMatches[1], $scripts)) {
                    foreach ($scripts[1] as $url) {
                        if (!in_array($url, $this->externalScripts)) {
                            $this->externalScripts[] = $url;
                        }
                    }
                }
            }
        }
    }

    protected function compileScss(string $path): string
    {
        $engine = new ScssEngine();
        ob_start();
        try {
            echo $engine->get($path);
        } catch (\Throwable $e) {
            ob_clean();
            return "/* Error: {$e->getMessage()} */";
        }
        return ob_get_clean();
    }

    protected function compileScssString(string $scss, string $importPath): string
    {
        try {
            $compiler = new \ScssPhp\ScssPhp\Compiler();
            $compiler->addImportPath($importPath);
            return $compiler->compileString($scss)->getCss();

        } catch (\Throwable $e) {
            return "/* SCSS Error: {$e->getMessage()} */";
        }
    }

    public function scanComposables(string|array $names = '*'): void
    {
        if ($this->composableScanned) {
            return;
        }

        $canComposableLoaded = function ($ns, $name) use ($names) {
            $names = (array)$names;

            if (in_array('*', $names) || in_array("$ns::*", $names)) {
                return true;
            }

            return $ns ? in_array("$ns::$name", $names) : in_array($name, $names);
        };

        $allPaths = [
            "" => View::getFinder()->getPaths(),
            ...View::getFinder()->getNamespaces()
        ];

        foreach ($allPaths as $ns => $paths) {
            foreach ($paths as $path) {
                $composablesPath = $path . '/vue/composables';

                if (is_dir($composablesPath)) {
                    $files = glob($composablesPath . '/*.js');

                    foreach ($files as $file) {
                        $name = basename($file, '.js');

                        if ($canComposableLoaded($ns, $name)) {
                            $this->composables[ $ns ? "$ns::$name" : $name ] = $file;
                        }
                    }
                }
            }
        }

        $this->composableScanned = true;
    }

    public function renderStyles(): string
    {
        $html = '';

        if (!empty($this->externalScripts)) {
            foreach ($this->externalScripts as $url) {
                $html .= "<script src=\"{$url}\"></script>\n";
            }
            $this->externalScripts = [];
        }

        if (!empty($this->externalStyles)) {
            foreach ($this->externalStyles as $url) {
                $html .= "<link rel=\"stylesheet\" href=\"{$url}\">\n";
            }
            $this->externalStyles = [];
        }

        if (!empty($this->styles)) {
            $html = "<style>".implode("\n", $this->styles)."</style>\n";

            $this->styles = [];
        }
        return $html;
    }

    public function renderScripts(array $data = [], ?string $namespace = null): string
    {
        $html = "<script>\n";
        $html .= "window.Imhotep = window.Imhotep || {};\n";
        $html .= "window.Imhotep.serverData = " . json_encode($data) . ";\n";
        $html .= "window.\$vueComponents = window.\$vueComponents || [];\n";
        $html .= "window.\$vueComposables = {};\n";
        $html .= "console.log('VueRegistry Scripts:', " . json_encode(array_keys($this->scripts)) . ");\n";
        $html .= "console.log('VueRegistry Composables:', " . json_encode(array_keys($this->composables)) . ");\n";
        $html .= "</script>\n";

        $components = $this->scripts;

        $getContent = function ($key, $val) {
            if (isset($this->virtualScripts[$key])) {
                return $val;
            }
            return file_get_contents($val);
        };

        foreach ($this->composables as $name => $path) {
            if (str_contains($name, "::")) {
                $name = substr(strstr($name, '::'), 2);
            }

            //foreach ($composables as $name => $path) {
                $content = file_get_contents($path);

                $content = str_replace(
                    "export function {$name}",
                    "window.{$name} = window.\$vueComposables.{$name} = function",
                    $content
                );

                $html .= "<script>\n// Composable: {$name}\n";
                $html .= "try {\n" . $content . "\n} catch(e) { console.error('Error loading composable [{$name}]:', e); }\n";
                $html .= "</script>\n";
            //}
        }

        foreach ($components as $name => $val) {
            $content = $getContent($name, $val);

            $html .= "<script>\n// Component: {$name}\n";
            $html .= "try {\n" . $content . "\n} catch(e) { console.error('Error loading component [{$name}]:', e); }\n";
            $html .= "</script>\n";
        }

        $viewUtils = ($namespace ? $namespace.'::' : '').'vue.utils';
        if ($utils = View::getFinder()->find($viewUtils) ) {
            if ($utils['extension'] === 'js') {
                $html .= "<!-- Vue Utils -->\n<script>\n";
                $html .= "try {\n" . file_get_contents($utils['path']) . "\n} catch(e) { console.error('Error loading Utils:', e); }\n";
                $html .= "</script>\n";
            }
        }

        $viewApp = ($namespace ? $namespace.'::' : '').'vue.app';
        if ($app = View::getFinder()->find($viewApp) ) {
            if ($app['extension'] === 'js') {
                $html .= "<!-- Vue Main App -->\n<script>\n";
                $html .= "try {\n" . file_get_contents($app['path']) . "\n} catch(e) { console.error('Error loading App:', e); }\n";
                $html .= "</script>\n";
            }
        }

        return $html;
    }

    public function boot(array $data = [], ?string $namespace = null): string
    {
        $this->scanComposables();

        $html = '';
        if (!empty($this->styles) || !empty($this->externalStyles) || !empty($this->externalScripts)) {
            $html .= $this->renderStyles();
        }

        $html .= $this->renderScripts($data, $namespace);

        return $html;
    }
}