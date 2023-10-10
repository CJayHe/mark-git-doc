<?php

namespace PHPZlc\MarkGitDoc;

use PHPZlc\MarkGitDoc\Repo\Package;

class Config
{
    const REPO_DIR_NAME = 'mark-doc-repo';

    private static $cloneUrl;

    private static $saveWebDir;

    private static $defLanguage;

    private static $languageStrings = [
        'zh-CN' => '简体中文',
        'en' => 'English'
    ];

    public static $vsersionStrings = [
        'zh-CN' => [
            Package::DEVELOP => '弃用',
            Package::ARCHIVE => '归档',
            Package::SUPPORT => '支持',
            Package::NEWEST => '最新稳定',
            Package::DEVELOP => '开发',
        ]
    ];

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
     * 克隆地址
     *
     * @return string
     */
    public static function cloneUrl($clone_url = null)
    {
        if($clone_url === null){
            if(empty(self::$clone_url)){
                self::$cloneUrl = $_ENV['MARK_GIT_CLONE_URL'];
            }
        }else{
            self::$cloneUrl = $clone_url;
        }

        if(empty(self::$cloneUrl)){
            throw new \Exception('克隆地址不能为空');
        }else{
            return self::$cloneUrl;
        }
    }

    /**
     * 保存目录-放置在网络根下
     *
     * @return string
     */
    public static function saveWebDir($saveWebDir = null)
    {
        if($saveWebDir === null){
            if(empty(self::$saveWebDir)){
                self::$saveWebDir = $_ENV['MARK_GIT_SAVE_WEB_DIR'];
            }
        }else{
            self::$saveWebDir = $saveWebDir;
        }

        if(empty(self::$saveWebDir)){
            throw new \Exception('保存地址不能为空');
        }else{
            return self::$saveWebDir;
        }
    }

    /**
     * 默认语言
     *
     * @return string
     */
    public static function defLanguage($defLanguage = null)
    {
        if($defLanguage === null){
            if(empty(self::$defLanguage)){
                self::$defLanguage = isset($_ENV['MARK_GIT_DEF_LANGUAGE']) ? $_ENV['MARK_GIT_DEF_LANGUAGE'] : 'zh-CN';
            }
        }else{
            self::$defLanguage = $defLanguage;
        }

        return self::$defLanguage;
    }
    
    public static function languageString($language)
    {
        return self::$languageStrings[$language];
    }

    public static function languages()
    {
        return array_keys(self::$languageStrings);
    }

    /**
     * repo目录
     *
     * @return string
     */
    public static function repoDir()
    {
        return self::saveWebDir() . DIRECTORY_SEPARATOR . self::REPO_DIR_NAME;
    }
}