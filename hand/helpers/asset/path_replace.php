<?php
namespace Hand\Helpers\Asset;

use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterInterface;
use Assetic\Filter\BaseCssFilter;

class PathReplace extends BaseCssFilter implements FilterInterface {

    public function __construct($paths) {
        $this->paths = $paths;
    }

    public function filterLoad(AssetInterface $asset) {
    }

    public function filterDump(AssetInterface $asset) {
        $this->source_root   = $asset->getSourceRoot() . '/';
        $this->source_file   = $asset->getSourcePath();

        $content = $this->filterReferences($asset->getContent(), array($this, 'pathReplacer'));
        $asset->setContent($content);
    }

    public function pathReplacer($matches) {
        foreach($this->paths as $path => $new_path) {
            $new_url    = str_replace($path, $new_path, $matches['url']);
            $matches[0] = str_replace($matches['url'], $new_url, $matches[0]);
        }

        return $matches[0];
    }

}
