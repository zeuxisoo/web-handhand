<?php
namespace Hand\Hooks;

use Assetic\AssetManager as AssetManager_;
use Assetic\AssetWriter;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\AssetCache;
use Assetic\Cache\FilesystemCache;
use Assetic\Filter\CssMinFilter;
use Assetic\Filter\GoogleClosure\CompilerApiFilter;
use Hand\Helpers\Asset\PathReplace;

class AssetManager {

    public function __construct() {
        $this->slim     = \Slim\Slim::getInstance();
        $this->site_url = $this->slim->config('app.site_url');
    }

    public function makeCSS() {
        $path_replace_bootstrap    = new PathReplace(['../fonts/' => './static/vendor/bootstrap/fonts/']);
        $path_replace_font_awesome = new PathReplace(['../fonts/' => './static/vendor/font-awesome/fonts/']);

        $collection = new AssetCollection([
            new FileAsset(STATIC_ROOT.'/vendor/bootstrap/css/bootstrap.min.css', [$path_replace_bootstrap]),
            new FileAsset(STATIC_ROOT.'/vendor/font-awesome/css/font-awesome.min.css', [$path_replace_font_awesome]),
            new FileAsset(STATIC_ROOT.'/vendor/animate.min.css'),
            new FileAsset(STATIC_ROOT.'/vendor/bootstrap-social.css'),
            new FileAsset(STATIC_ROOT.'/vendor/nprogress/nprogress.css', [new CssMinFilter()]),
            new FileAsset(STATIC_ROOT.'/client/css/default.css', [new CssMinFilter()]),
        ]);
        $collection->setTargetPath('default.css');

        $cache   = new AssetCache($collection, new FilesystemCache(CACHE_ROOT.'/asset'));
        $manager = new AssetManager_();
        $manager->set('styles', $cache);

        $writer = new AssetWriter(STATIC_ROOT.'/asset');
        $writer->writeManagerAssets($manager);
    }

    public function makeJS() {
        try {
            $collection = new AssetCollection([
                new FileAsset(STATIC_ROOT.'/vendor/jquery/jquery.min.js'),
                new FileAsset(STATIC_ROOT.'/vendor/jquery.parseparams.js', [new CompilerApiFilter()]),
                new FileAsset(STATIC_ROOT.'/vendor/jquery.turbolinks.js', [new CompilerApiFilter()]),
                new FileAsset(STATIC_ROOT.'/vendor/bootstrap/js/bootstrap.min.js'),
                new FileAsset(STATIC_ROOT.'/vendor/bootstrap.file-input.js', [new CompilerApiFilter()]),
                new FileAsset(STATIC_ROOT.'/vendor/turbolinks.js', [new CompilerApiFilter()]),
                new FileAsset(STATIC_ROOT.'/vendor/nprogress/nprogress.js', [new CompilerApiFilter()]),
                new FileAsset(STATIC_ROOT.'/client/js/default.js', [new CompilerApiFilter()]),
            ]);
            $collection->setTargetPath('default.js');

            $cache   = new AssetCache($collection, new FilesystemCache(CACHE_ROOT.'/asset'));
            $manager = new AssetManager_();
            $manager->set('scripts', $cache);

            $writer = new AssetWriter(STATIC_ROOT.'/asset');
            $writer->writeManagerAssets($manager);
        }catch(\Exception $e) {}
    }

}
