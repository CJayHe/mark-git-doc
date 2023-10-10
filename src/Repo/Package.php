<?php
namespace PHPZlc\MarkGitDoc\Repo;

use PHPZlc\MarkGitDoc\Config;
use PHPZlc\MarkGitDoc\Main;
use PHPZlc\MarkGitDoc\Unit;

class Package
{
    //弃用
    const DEPRECATED = 'deprecated';

    //归档
    const ARCHIVE = 'archive';

    //支持
    const SUPPORT = 'support';

    //长期支持
    const LONG_SUPPORT = 'long_support';

    //最新
    const NEWEST = 'newest';

    //开发
    const DEVELOP = 'develop';

    /**
     * 项目名
     *
     * @var string
     */
    public $name;

    /**
     * 宣传标题
     * 
     * @var string 
     */
    public $title;
    
    /**
     * 描述
     *
     * @var string
     */
    public $description;


    /**
     * 关键词
     * 
     * @var string
     */
    public $keys;

    /**
     * 相对路径
     *
     * @var string
     */
    public $path;

    /**
     * 类型 用于自定义模块得大类
     * 
     * @var string
     *
     */
    public $type;

    /**
     * 目录名
     *
     * @var string
     */
    public $dirName;

    /**
     * 分支名
     *
     * @var string
     */
    public $branch;

    /**
     * 当前语言
     *
     * @var string
     */
    public $language;

    /**
     * 相关链接
     *
     * @var string
     */
    public $links = [];

    /**
     * 两级菜单
     *
     * @var array
     */
    public $menus = [];

    /**
     * 当前状态
     *
     * @var string
     */
    public $status;

    /**
     * 当前的这个分支最新的版本号
     *
     * @var string
     */
    public $version;


    /**
     * 排序值 越大排名越考前
     *
     * @var integer
     */
    public $sort = 0;

    /**
     * 自定义参数
     *
     * @var array
     */
    public $parameters = [];

    /**
     * 全部版本
     *
     * @var array
     */
    public $versions = [];

    /**
     * 全部可用包
     *
     * @var Package[]
     */
    public $packages = [];

    /**
     * 全部可用的语言包 http://www.lingoes.cn/zh/translator/langcode.htm
     *
     * @var array
     */
    public $languages = [];

    /**
     * 数据整理
     *
     * @param $language
     */
    public function dataCuration(Main $markGitDoc)
    {
        $this->menuDataCuration($markGitDoc, $this->menus, $this->language);

        return $this;
    }

    private function menuDataCuration(Main $markGitDoc, &$menus, $language)
    {
        foreach ($menus as $key => &$menu){
            if(is_array($menu['title'])){
                $menus[$key]['title'] = isset($menu['title'][$language]) ?  $menu['title'][$language] : $menu['title'][Config::defLanguage()];
            }

            if(isset($menu['menus'])){
                $this->menuDataCuration($markGitDoc, $menu['menus'], $language);
                if(empty($menu['url'])){
                    $menu['url'] = $menu['menus'][0]['url'];
                }
            }else{
                $menu['menus'] = [];
            }

            if(empty($menu['url'])){
                $menu['url'] = 'index';
            }

            if(strpos( $menu['url'], '/') !== 0){
                $menu['url'] = $this->path . '/' . $menu['url'];
            }

            $file = new File($this->branch, $this->dirName);
            $file->parserToUrlPath($markGitDoc, $language, $menu['url']);
            $menu['file'] = $file;
        }

        return $menus;
    }

    public function packagesTypeGroup()
    {
        $packages = [];
        
        foreach ($this->packages as $package) {
            $packages[$package->type][] = $package;
        }
        
        return $packages;
    }

    public function simpleVersions()
    {
        return array_keys($this->versions);
    }
    
    public function versionsDesc()
    {
        $versions = $this->versions;
        
        krsort($versions);

        foreach ($versions as $version => $status){
            $versions[$version] = $this->getVersionString($status);
        }
        
        return $versions;
    }
    
    private function getVersionString($status)
    {
        if(isset(Config::$vsersionStrings[$this->language])){
            return Config::$vsersionStrings[$this->language][$status];
        }

        return $status;
    }
}