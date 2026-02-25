<?php

namespace CR;

class Upgrade
{
    public $config = null;

    public $releasesApi = 'https://api.github.com/repos/duanestorey/crossroads/releases/latest';
    public $releaseZipUrl = 'https://github.com/duanestorey/crossroads/archive/refs/tags/%s.zip';

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function runUpgrader()
    {
        if (CROSSROADS_IS_COMPOSER) {
            $this->runComposerUpgrade();
        } else {
            $this->runGitHubUpgrade();
        }
    }

    private function runComposerUpgrade()
    {
        LOG(_i18n('core.class.upgrade.composer_detected'), 1, Log::INFO);
        LOG(_i18n('core.class.upgrade.running_composer_update'), 1, Log::INFO);

        $output = [];
        $returnCode = 0;
        exec('composer update duanestorey/crossroads-core 2>&1', $output, $returnCode);

        foreach ($output as $line) {
            LOG($line, 2, Log::INFO);
        }

        if ($returnCode === 0) {
            LOG(_i18n('core.class.upgrade.composer_success'), 1, Log::INFO);
        } else {
            LOG(_i18n('core.class.upgrade.composer_failed'), 1, Log::ERROR);
        }
    }

    private function runGitHubUpgrade()
    {
        $releaseJson = Utils::curlDownloadFile($this->releasesApi);
        if (!$releaseJson) {
            LOG(_i18n('core.class.upgrade.downloading'), 1, Log::ERROR);
            return;
        }

        $release = json_decode($releaseJson);
        if (!$release || !isset($release->tag_name)) {
            LOG(_i18n('core.class.upgrade.downloading'), 1, Log::ERROR);
            return;
        }

        $latestVersion = ltrim($release->tag_name, 'v');

        LOG(sprintf(_i18n('core.class.upgrade.cur_ver'), CROSSROADS_VERSION), 1, Log::INFO);
        LOG(sprintf(_i18n('core.class.upgrade.next_ver'), $latestVersion), 1, Log::INFO);

        $compare = version_compare($latestVersion, CROSSROADS_VERSION);
        if ($compare != 1) {
            LOG(sprintf(_i18n('core.class.upgrade.up_to_date'), $latestVersion), 1, Log::INFO);
            return;
        }

        $zipFile = $this->downloadRelease($release->tag_name);
        if (!$zipFile) {
            return;
        }

        LOG(_i18n('core.class.upgrade.unzip'), 2, Log::INFO);

        $tempDir = sys_get_temp_dir();
        $unzipDirectory = $tempDir . '/crossroads-' . ltrim($release->tag_name, 'v');

        if (file_exists($unzipDirectory)) {
            Utils::recursiveRmdir($unzipDirectory);
        }

        $command = sprintf('unzip -d %s %s', escapeshellarg($tempDir), escapeshellarg($zipFile));
        $output = shell_exec($command);
        echo $output;

        if (file_exists($unzipDirectory) && is_dir($unzipDirectory)) {
            $allFiles = Utils::findAllFilesWithExtension($unzipDirectory . '/core', ['php', 'yaml', 'latte', 'css', 'js', 'scss', 'sql', 'avif', 'webp', 'ico']);
            foreach ($allFiles as $oneFile) {
                $relFile = str_replace($unzipDirectory . '/', '', $oneFile);
                $destFile = CROSSROADS_BASE_DIR . '/' . $relFile;

                LOG(sprintf('Copying file [%s] to [%s]', $relFile, $destFile), 1, Log::INFO);

                Utils::copyFile($oneFile, $destFile);
            }

            Utils::copyFile($unzipDirectory . '/crossroads', CROSSROADS_BASE_DIR . '/crossroads');

            LOG(_i18n('core.class.upgrade.composer'), 2, Log::INFO);

            exec('composer update');
        }
    }

    private function downloadRelease($tagName)
    {
        LOG(_i18n('core.class.upgrade.downloading'), 2, Log::INFO);

        $zipUrl = sprintf($this->releaseZipUrl, $tagName);
        $zipContents = Utils::curlDownloadFile($zipUrl);
        if ($zipContents) {
            $tempDir = sys_get_temp_dir();
            $destinationFile = tempnam(Utils::fixPath($tempDir), 'crossroads-') . '.zip';

            file_put_contents($destinationFile, $zipContents);

            return $destinationFile;
        }

        return false;
    }
}
