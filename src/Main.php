<?php

namespace PHPZlc\MarkGitDoc;

use PHPZlc\MarkGitDoc\Repo\Dir;
use PHPZlc\MarkGitDoc\Repo\File;
use PHPZlc\MarkGitDoc\Repo\Package;
use PHPZlc\MarkGitDoc\Repo\Repo;

class Main
{
    public function __construct()
    {
        if (empty(Pull::$packages)) {
            self::load();
        }
    }

    public static function load()
    {
        Pull::$repo = new Repo();
        Unit::setClassParams(Pull::$repo, json_decode(file_get_contents(Config::repoDir() . DIRECTORY_SEPARATOR . 'repo.json'), true));
        Pull::$packages = json_decode(file_get_contents(Config::repoDir() . DIRECTORY_SEPARATOR . 'packages.json'), true);
        Pull::$rootVersionPackages = json_decode(file_get_contents(Config::repoDir() . DIRECTORY_SEPARATOR . 'rootVersionPackages.json'), true);
        Pull::$branchToVersions = json_decode(file_get_contents(Config::repoDir() . DIRECTORY_SEPARATOR . 'branchToVersions.json'), true);
        Pull::$packageMajorVersion = json_decode(file_get_contents(Config::repoDir() . DIRECTORY_SEPARATOR . 'packageMajorVersion.json'), true);
        Pull::$files = json_decode(file_get_contents(Config::repoDir() . DIRECTORY_SEPARATOR . 'files.json'), true);
    }

    public function repo()
    {
        return Pull::$repo;
    }

    public function packageNewestVersion($packageDirName)
    {
        if(isset(Pull::$packageMajorVersion[$packageDirName])){
            return Pull::$packageMajorVersion[$packageDirName];
        }else{
            $array = $this->simpleVersions($packageDirName);
            return end($array);
        }
    }

    public function package($packageDirName = null, $version = null, $language = null)
    {
        if(empty($packageDirName)){
            $packageDirName = 'root';
        }

        if (empty($version)) {
            $version = $this->packageNewestVersion($packageDirName);
        }

        if (empty($language)) {
            $language = Config::defLanguage();
        }

        $package = new Package();

        Unit::setClassParams($package, Pull::$packages[$packageDirName][$version]);

        $package->versions = $this->versions($packageDirName);

        if ($packageDirName == 'root') {
            $package->packages = $this->packages($package->version);
        }

        if (!in_array(Config::defLanguage(), $package->languages, true)){
            array_unshift($package->languages, Config::defLanguage());
        }

        $package->language = $language;

        return $package->dataCuration($this);
    }
    
    public function versions($packageDirName)
    {
        $versions = [];

        foreach (Pull::$packages[$packageDirName] as $version => $package) {
            $versions[$version] = $package['status'];
        }

        ksort($versions);

        return $versions;
    }

    public function simpleVersions($packageDirName)
    {
        return array_keys($this->versions($packageDirName));
    }

    public function packages($rootVersion, $language = null)
    {
        if (empty($language)) {
            $language = Config::defLanguage();
        }

        $packages = [];

        $package = Pull::$packages['root'][$rootVersion];

        foreach (Pull::$rootVersionPackages[$rootVersion] as $packageDirName) {
            $packages[] = $this->package($packageDirName, Pull::$branchToVersions[$packageDirName][$package['branch']], $language);
        }

        return $packages;
    }
}