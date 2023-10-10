<?php
namespace PHPZlc\MarkGitDoc;

use PHPZlc\MarkGitDoc\Repo\File;
use PHPZlc\MarkGitDoc\Repo\Package;
use PHPZlc\MarkGitDoc\Repo\Repo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class Pull
{
    /**
     * @var Filesystem
     */
    public $fileSystem;

    /**
     * @var Repo
     */
    public static $repo;

    /**
     * @var array
     */
    public static $packages = [];

    /**s
     * @var array
     */
    public static $branchToVersions = [];

    /**
     * @var array
     */
    public static $rootVersionPackages = [];

    /**
     * @var array
     */
    public static $packageMajorVersion = [];

    /**
     * @var array
     */
    public static $files = [];

    /**
     * @var string
     */
    private $repoBuffDir;

    public function __construct()
    {
        $this->fileSystem = new Filesystem();
        $this->repoBuffDir= Config::saveWebDir() . DIRECTORY_SEPARATOR . 'buff-mark-doc-repo';
    }

    public function main()
    {
        self::$repo = new Repo(Config::cloneUrl());

        $this->repoBuffDir = Config::saveWebDir() . DIRECTORY_SEPARATOR . 'buff-mark-doc-repo';
        
        if($this->fileSystem->exists($this->repoBuffDir)){
            $this->deldir($this->repoBuffDir);
        }

        $this->fileSystem->mkdir($this->repoBuffDir);

        //克隆代码
        $rootRepDir = $this->repoBuffDir . DIRECTORY_SEPARATOR . 'root';
        $result_code = null;
        passthru( 'git clone ' . Config::cloneUrl() . ' ' . $rootRepDir, $result_code);

        if($result_code != 0){
            return false;
        }

        $command = 'cd ' . $rootRepDir . ' && git branch -a';

        if($this->isWindows()){
            $command = 'cd ' . $rootRepDir . ' && git branch -a';
        }else{
            $command = 'cd ' . $rootRepDir . '; git branch -a';
        }

        $output = null;
        $result_code = null;
        exec($command,$output, $result_code);

        if($result_code != 0){
            return false;
        }

        foreach ($output as $key => $value){
            if(strpos($value, 'remotes/origin') != false && strpos($value, '>') === false){
                $tmp = explode('/', $value);
                $branchName = end($tmp);
                if(strpos($branchName,'dev') === false) {
                    $result_code = null;
                    passthru('git clone -b ' . $branchName . ' ' . Config::cloneUrl() . ' ' . $this->repoBuffDir . DIRECTORY_SEPARATOR . $branchName, $result_code);
                    if ($result_code != 0) {
                        return false;
                    }
                    self::$repo->branchs[$branchName] = $this->repoBuffDir . DIRECTORY_SEPARATOR . $branchName;
                }
            }
        }

        ksort(self::$repo->branchs);

        //生成索引文件
        file_put_contents($this->repoBuffDir . DIRECTORY_SEPARATOR . 'repo.json', json_encode(Unit::objectToArray(self::$repo)));

        foreach (self::$repo->branchs as $branch => $branch_path){
            $this->readDirConfig($branch, $branch_path);
        }

        file_put_contents($this->repoBuffDir . DIRECTORY_SEPARATOR . 'packages.json', json_encode(self::$packages));

        file_put_contents($this->repoBuffDir . DIRECTORY_SEPARATOR . 'packageMajorVersion.json', json_encode(self::$packageMajorVersion));

        ksort(self::$rootVersionPackages);
        foreach (self::$rootVersionPackages as $version => $packages){
            arsort($packages);
            self::$rootVersionPackages[$version] = array_keys($packages);
        }
        file_put_contents($this->repoBuffDir . DIRECTORY_SEPARATOR . 'rootVersionPackages.json', json_encode(self::$rootVersionPackages));

        file_put_contents($this->repoBuffDir . DIRECTORY_SEPARATOR . 'branchToVersions.json', json_encode(self::$branchToVersions));
        file_put_contents($this->repoBuffDir . DIRECTORY_SEPARATOR . 'files.json', json_encode(self::$files));

        //目录归纳
        $this->deldir($rootRepDir);
        $delRepoDir = null;
        if($this->fileSystem->exists(Config::repoDir())){
            $delRepoDir = Config::saveWebDir() . DIRECTORY_SEPARATOR . 'tmp-mark-doc-repo';
            $this->fileSystem->rename(Config::repoDir(), $delRepoDir);
        }
        $this->fileSystem->rename($this->repoBuffDir, Config::repoDir());
        if(!empty($delRepoDir)){
            $this->deldir($delRepoDir);
        }

        return true;
    }

    private function readDirConfig($branch, $path, $dirName = 'root')
    {
        $config_file = $path . DIRECTORY_SEPARATOR . 'config.yaml';

        if($this->fileSystem->exists($config_file)){
            $configs = Yaml::parseFile($config_file);

            $package = new Package();
            $package->branch = $branch;
            $package->path = str_replace(Config::repoDir() . DIRECTORY_SEPARATOR . $branch , '', str_replace('buff-mark-doc-repo', 'mark-doc-repo', $path));
            $package->dirName = $dirName;

            Unit::setClassParams($package, $configs);

            if(empty($package->name)) {
                $package->name = $dirName;
            }

            if(empty($package->version)) {
                $package->version = $branch;
            }

            if($package->status != Package::DEVELOP) {
                if ($package->status == Package::NEWEST) {
                    self::$packageMajorVersion[$dirName] = $package->version;
                }

                self::$packages[$dirName][$package->version] = Unit::objectToArray($package);
                self::$branchToVersions[$dirName][$package->branch] = $package->version;

                if($dirName == 'root'){
                    if (!array_key_exists($package->version, self::$rootVersionPackages)) {
                        self::$rootVersionPackages[$package->version] = [];
                    }
                }else{
                    self::$rootVersionPackages[self::$branchToVersions['root'][$package->branch]][$dirName] = $package->sort;
                }
            }
        }

        $dh = opendir($path);
        while ($file = readdir($dh)) {
            $isRead = true;
            if($file == '.' || $file == '..'){
                $isRead = false;
            }
            if(strpos($file, '.') === 0){
                $isRead = false;
            }
            if($isRead){
                $fullpath = $path . DIRECTORY_SEPARATOR . $file;
                if (is_dir($fullpath)) {
                    $this->readDirConfig($branch, $fullpath, $file);
                }else{
                    if($file != 'config.yaml') {
                        $var_path = str_replace($this->repoBuffDir . DIRECTORY_SEPARATOR . $branch, '', $fullpath);
                        $file = new File($branch, $this->getPackageNameToPath($var_path, $branch));
                        if ($file->parserToFileContent($fullpath)) {
                            self::$files[$branch][$var_path] = Unit::objectToArray($file);
                        }
                    }
                }
            }
        }
    }

    private function getPackageNameToPath($path, $branch)
    {
        $max = 0;
        $name = 'root';
        foreach (Pull::$packages as $packageName => $versions){
            if(isset(Pull::$branchToVersions[$packageName][$branch])){
                $package = $versions[Pull::$branchToVersions[$packageName][$branch]];
                $level = count(explode(DIRECTORY_SEPARATOR, $package['path']));
                if($level > $max && (empty($package['path']) ||strpos($path, $package['path']) !== false)){
                    $name = $package['dirName'];
                }
            }
        }
        return $name;
    }

    private function isWindows()
    {
        return strtoupper(substr(PHP_OS,0,3))==='WIN'? true : false;
    }

    private function deldir($dir)
    {
        $this->fileSystem->chmod($dir, 0777, 0000, true);

        //先删除目录下的文件：
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . DIRECTORY_SEPARATOR . $file;
                if (!is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    $this->deldir($fullpath);
                }
            }
        }

        closedir($dh);
        //删除当前文件夹：
        if (rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }
}