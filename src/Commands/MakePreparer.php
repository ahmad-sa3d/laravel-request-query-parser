<?php

/**
 * @package  saad/request-query-parser
 *
 * @author Ahmed Saad <a7mad.sa3d.2014@gmail.com>
 * @license MIT MIT
 */

namespace Saad\QueryParser\Commands;

use Saad\Fractal\Commands\BaseMakeCommand;

class MakePreparer extends BaseMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:preparer {model} {--nest=} {--name=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make Model Query Parser Preparer';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! parent::handle()) {
            return;
        }

        try {
            $this->create("Preparer");
             $this->addToServiceProviders();
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
            $this->info('Rolling Back');

            $this->delete("Preparer");
             $this->removeFromServiceProviders();
        }
    }

    /**
     * Get Stubs Path
     * @return string stubs path
     */
    protected function getStubsPath() {
        return __DIR__ . "/../../resources/stubs";
    }

    /**
     * Get Output Directory Name
     * @return string Directory name
     */
    protected function getOutputDirectoryName() {
        return 'ModelPreparers';
    }

    /**
     * Add Binding to service providers
     */
    protected function addToServiceProviders()
    {
        $stub_content = $this->filesystem->get($this->getStubsPath() . '/ServiceProvider.stub');
        $stub_content = $this->processStubContent($stub_content);

        $this->updateProviderBinding('/(boot\(\).*?\{)/s', "$1 {$stub_content}");
    }

    /**
     * Remove Binding from service providers
     */
    protected function removeFromServiceProviders()
    {
        $this->updateProviderBinding($this->getBindPattern(), '', false);
    }

    /**
     * Get Service Provider Bind Pattern 
     * 
     * @return string pattern
     */
    protected function getBindPattern() {
        $skipped_full_model = str_replace('\\', '\\\\', $this->full_model);
        return "/#\sBind\s".$skipped_full_model."\sPreparer.*?#\s".$skipped_full_model."\sPreparer\sEnd/smu";
    }

    /**
     * Add or Remove binding to Service Provider
     *
     * @param $find_pattern
     * @param $replacement
     * @param  boolean $add [description]
     * @return void [type]                [description]
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function updateProviderBinding($find_pattern, $replacement, $add = true) {
        $service_provider = app_path('Providers/AppServiceProvider.php');
        $content = $this->filesystem->get($service_provider);

        $already_exists = preg_match($this->getBindPattern(), $content);

        if (($add && !$already_exists) || (!$add && $already_exists)) {
            $this->replaceFileContent($find_pattern, $replacement, $content, $service_provider);
        }
    }

    /**
     * Replace File content by prepared content
     *
     * @param $find_pattern
     * @param $replacement
     * @param $content
     * @param $service_provider
     * @return void [type]                   [description]
     */
    protected function replaceFileContent($find_pattern, $replacement, $content, $service_provider)
    {
        $final = preg_replace($find_pattern, $replacement, $content);
        $this->filesystem->put($service_provider, $final);
    }
}