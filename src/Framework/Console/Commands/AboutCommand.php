<?php declare(strict_types=1);

namespace Imhotep\Framework\Console\Commands;

use Imhotep\Console\Command\Command;

class AboutCommand extends Command
{
    public static string $defaultDescription = 'Display the basic application information.';

    public string $signature = '{--only= : The section to display}
            {--json : Output the information as JSON}';

    public function handle(): int
    {
        $data = $this->getAppInformation();

        if ($this->hasOption('json')) {
            $this->displayJson($data);
        }
        else {
            $this->displayDetail($data);
        }

        $this->newLine();

        return 0;
    }

    protected function displayDetail(array $data): void
    {
        foreach ($data as $section => $rows) {
            $this->newLine();
            $this->line("  <fg=green;options=bold>$section</>");

            foreach ($rows as $label => $value) {
                $this->components()->twoColumnDetail('  '.$label, $this->formatValue($value));
            }
        }
    }

    protected function displayJson(array $data): void
    {
        foreach ($data as $section => $rows) {
            $data[$section] = array_map(function ($value) {
                return $value;
            }, $rows);
        }

        $this->line(strip_tags(json_encode($data)));
    }

    protected function formatValue(mixed $value): string
    {
        if (is_null($value)) {
            $value = '';
        }

        if (is_bool($value)) {
            return $value ? '<fg=yellow;options=bold>ENABLED</>' : '<fg=yellow;options=bold>DISABLED</>';
        }

        if (is_string($value) && empty($value)) {
            return '<fg=yellow;options=bold>-</>';
        }

        return $value;
    }

    protected function getAppInformation(): array
    {
        $only = null;

        if ($this->hasOption('only')) {
            $only = ucfirst($this->option('only'));

            if (! in_array($only, ['Application', 'Cache', 'Drivers'])) {
                $only = null;
            }
        }

        $result = [];

        if (is_null($only) || $only === 'Application') {
            $result['Application'] = [
                'Application Name' => config('app.name'),
                'Imhotep Version' => $this->app->version(),
                'PHP Version' => phpversion(),
                'Debug Mode' => config('app.debug'),
                'URL' => str_replace(['http://','https://'], '', config('app.url')),
                'Timezone' => config('app.timezone'),
                'Locale' => config('app.locale'),
                'Fallback Locale' => config('app.fallback_locale'),
            ];
        }

        if (is_null($only) || $only === 'Cache') {
            $result['Cache'] = [
                'Config' => $this->app->configIsCached(),
                'Views' => config('view.cache')
            ];
        }

        if (is_null($only) || $only === 'Drivers') {
            $result['Drivers'] = [
                'Cache' => config('cache.default'),
                'Database' => config('cache.default'),
                'Session' => config('session.driver'),
                'Logs' => config('logging.default'),
            ];
        }

        return $result;
    }
}