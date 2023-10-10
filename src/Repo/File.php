<?php
namespace PHPZlc\MarkGitDoc\Repo;

use App\Controller\Home\DocController;
use PHPZlc\MarkGitDoc\Config;
use PHPZlc\MarkGitDoc\Main;
use PHPZlc\MarkGitDoc\MarkDoc\InterfaceParser;
use PHPZlc\MarkGitDoc\MarkDoc\Markdown\MarkdownParser;
use PHPZlc\MarkGitDoc\MenusActive;
use PHPZlc\MarkGitDoc\Pull;
use PHPZlc\MarkGitDoc\Unit;

class File
{
    /**
     * @var Package
     */
    private $package;

    /**
     * 当前文件语言
     *
     * @var string
     */
    public $language;

    /**
     * 当前环境语言
     *
     * @var string
     */
    public $curLanguage;

    /**
     * 分支名
     *
     * @var string
     */
    public $branch;

    /**
     * 包名
     *
     * @var string
     */
    public $packageName;

    /**
     * 文件所在目录
     *
     * @var string
     */
    public $dirPath;

    /**
     * 文件完整地址
     *
     * @var string
     */
    public $filePath;

    /**
     * 文件相对路径
     *
     * @var string
     */
    public $path;

    /**
     * 文件名
     *
     * @var string
     */
    public $fileName;

    /**
     * 文件后缀
     *
     * @var string
     */
    public $fileSuffix;

    /**
     * 文件锚点
     *
     * @var string
     */
    public $fileAnchor;

    /**
     * 网络路径
     *
     * @var string
     */
    public $urlPath;

    /**
     * 完整网络地址
     *
     * @var string
     */
    public $hostUrl;

    /**
     * 文件编辑URL
     *
     * @var string
     */
    public $editUrl;

    /**
     * @var InterfaceParser
     */
    public $parser;

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
     * 菜单
     * 
     * 
     * @var array
     */
    public $menus;

    public function __construct($branch, $packageName)
    {
        if (empty($packageName)) {
            $packageName = 'root';
        }
        $this->branch = $branch;
        $this->packageName = $packageName;
    }

    /**
     * 通过网络路径解析
     * 
     * @param Main $markGitDoc
     * @param $language
     * @param $url_path
     * @return bool
     */
    public function parserToUrlPath(Main $markGitDoc, $language, $url_path)
    {
        $url_path = str_replace('/', DIRECTORY_SEPARATOR, $url_path);
        $fileAnchor = $this->setFileAnchor($url_path);
        
        if(!empty($fileAnchor)){
            $url_path = str_replace('#' . $fileAnchor, '', $url_path);
        }

        $file_path = Config::repoDir() . DIRECTORY_SEPARATOR . $this->branch . $url_path;

        if (is_dir($file_path)) {
            if (is_dir($file_path . DIRECTORY_SEPARATOR . 'index')) {
                $url_path .= DIRECTORY_SEPARATOR . 'index';
                if (!$this->findFileToPath($url_path . DIRECTORY_SEPARATOR . 'index')) {
                    if (!$this->findFileToPath($url_path . DIRECTORY_SEPARATOR . $this->language)) {
                        if (!$this->findFileToPath($url_path . DIRECTORY_SEPARATOR . Config::defLanguage())) {
                            return false;
                        }
                    }
                }
            } else {
                if (!$this->findFileToPath($url_path . DIRECTORY_SEPARATOR . 'index')) {
                    if (!$this->findFileToPath($url_path . DIRECTORY_SEPARATOR . $this->language)) {
                        if (!$this->findFileToPath($url_path . DIRECTORY_SEPARATOR . Config::defLanguage())) {
                            return false;
                        }
                    }
                }
            }
        } else {
            if (!$this->findFileToPath($url_path)) {
                return false;
            }
        }

        $this->curLanguage = $language;
        $this->fileAnchor = $fileAnchor;
        
        $this->setHostUrl();
        

        return true;
    }

    /**
     * 通过文件缓存解析
     * 
     * @param $filePath
     * @return boolean
     */
    public function parserToFileCache($filePath, $language)
    {
        $fileAnchor = $this->setFileAnchor($filePath);
        if(!empty($fileAnchor)){
            $filePath = str_replace('#' . $fileAnchor, '', $filePath);
        }
        
        if(!$this->findFileToPath($filePath)){
            return false;
        }
        
        $this->fileAnchor = $fileAnchor;
        $this->curLanguage = $language;
        
        $this->setHostUrl();
        
        return true;
    }

    /**
     * 从文件本本身检索
     * 
     * @param string $filePath 文件路径
     * @return boolean
     */
    public function parserToFileContent($filePath)
    {
        $this->filePath = $filePath;

        $pathinfos = pathinfo($filePath);

        $this->dirPath = $pathinfos['dirname'];
        $this->fileName = $pathinfos['filename'];
        $this->fileSuffix = isset($pathinfos['extension']) ? $pathinfos['extension'] : null;
        
        if(empty($this->fileSuffix)){
            return false;
        }

        if (strpos('#', $this->fileSuffix) !== false) {
            $arr = explode('#', $this->fileSuffix);
            $this->fileSuffix = $arr[0];
            $this->fileAnchor = $arr[1];
            $this->filePath = str_replace('#' . $this->fileAnchor, '', $this->filePath);
        }

        if (!$this->parserHtml()) {
            return false;
        }

        foreach ($this->parser->getParams() as $k => $v) {
            if ($v === null) {
                if ($this->$k === null) {
                    $this->$k = $v;
                }
            } else {
                $this->$k = $v;
            }
        }

        $this->menus = $this->parser->getMenus();

        $this->filePath = str_replace('buff-mark-doc-repo', 'mark-doc-repo', $this->filePath);
        $this->path = str_replace(Config::repoDir() . DIRECTORY_SEPARATOR . $this->branch, '', $this->filePath);
        $this->dirPath = str_replace('buff-mark-doc-repo', 'mark-doc-repo', $this->dirPath);
        $this->editUrl = Pull::$repo->indexUrl . '/edit' . str_replace(DIRECTORY_SEPARATOR, '/', str_replace(Config::repoDir(), '', $this->filePath));
        $this->urlPath = str_replace(DIRECTORY_SEPARATOR, '/', str_replace('.' . $this->fileSuffix, '', $this->path));
        if ($this->fileName == 'index' || $this->isLanguageFileName()) {
            $this->urlPath = Unit::removeRemoveString('/' . $this->fileName, $this->urlPath);
            if ($this->isLanguageFileName()) {
                $this->urlPath = Unit::removeRemoveString('/index', $this->urlPath);
            }
        }

        $this->getLanguage();

        return true;
    }

    public function parserHtml()
    {
        switch ($this->fileSuffix) {
            case 'markdown':
            case 'md':
                $this->parser = new MarkdownParser($this);
                break;
            default:
                return false;
        }

        return true;
    }

    public function getMarkdown()
    {
        $markdown =  preg_replace('/^---(.|\n)*?---'.PHP_EOL.'/i', '', file_get_contents($this->filePath));

        $pattern = '/\[(.*?)\]\((.*?)\)/';
        preg_match_all($pattern, $markdown, $matches, PREG_SET_ORDER);

        // 提取匹配到的链接和文本
        foreach ($matches as $match) {
            $text = $match[1]; // 这里获取到的是链接文本
            $href = $match[2]; // 这里获取到的是链接地址
            if(strpos($href, 'http') !== 0){
                $file = new File($this->branch, $this->packageName);
                if($file->parserToFileCache(str_replace('/' , DIRECTORY_SEPARATOR, $href), $this->curLanguage)){
                    $markdown = str_replace($href, $file->hostUrl, $markdown);
                }
            }
        }

        $pattern = '/!\[(.*?)\]\((.*?)\)/';
        preg_match_all($pattern, $markdown, $matches, PREG_SET_ORDER);

        // 提取匹配到的链接和文本
        foreach ($matches as $match) {
            $text = $match[1]; // 这里获取到的是链接文本
            $href = $match[2]; // 这里获取到的是链接地址
            if(strpos($href, 'http') !== 0){
                $host = str_replace('index.php', '', $_SERVER['SCRIPT_NAME']) . Config::REPO_DIR_NAME . '/' . $this->branch;
                if(strpos($href, '/') === 0){
                    $src = $host . $href;
                }else{
                    $src =  $host . '/' . $href;
                }

                $markdown = str_replace($href, $src, $markdown);
            }
        }

        return $markdown;
    }

    public function getHtml()
    {
        if ($this->parserHtml()) {
            return $this->parser->getHtml();
        }

        return '解析失败';
    }

    /**
     * 通过路径匹配文件
     *
     * @param $url_path
     * @return bool
     */
    public function findFileToPath($url_path)
    {
        foreach (Pull::$files[$this->branch] as $path => $file) {
            if (strpos($path, $url_path) !== false) {
                Unit::setClassParams($this, $file);
                return true;
            }
        }

        return false;
    }

    private function setFileAnchor($path)
    {
        if (strpos($path, '#') !== false) {
            $arr = explode('#', $path);
            if (count($arr) == 2) {
                $this->fileAnchor = $arr[1];
            }
        }

        return $this->fileAnchor;
    }

    private function setHostUrl()
    {
        $this->hostUrl = $_SERVER['SCRIPT_NAME'] . '/doc/' . $this->curLanguage . '/' . $this->packageName . '/' . Pull::$branchToVersions[$this->packageName][$this->branch] . $this->urlPath;

        if($_ENV['APP_ENV'] == 'dev'){
            $this->hostUrl = $_SERVER['SCRIPT_NAME'] . '/doc/' . $this->curLanguage . '/' . $this->packageName . '/' . Pull::$branchToVersions[$this->packageName][$this->branch] . $this->urlPath;
        }else{
            $this->hostUrl = '/doc/' . $this->curLanguage . '/' . $this->packageName . '/' . Pull::$branchToVersions[$this->packageName][$this->branch] . $this->urlPath;
        }

        if (!empty($this->fileAnchor)) {
            $this->hostUrl .= '#' . $this->fileAnchor;
        }
    }

    private function getLanguage()
    {
        $this->language = Config::defLanguage();

        foreach (Config::languages() as $language) {
            if (strpos($language, $this->fileName) !== false) {
                $this->language = $language;
            }
        }
    }
    
    public function getLanguages()
    {
        $lanauages = [];
        
        if($this->isLanguageFileName()){
            foreach (Pull::$files as $file){
                if(strpos($file['filePath'], $this->dirPath . DIRECTORY_SEPARATOR) !== false){
                    $lanauages[$file['language']] = [
                        'string' => Config::languageString($file['language']),
                        'url' => str_replace('/'. $this->language .'/', '/'. $file['language'] .'/', $this->hostUrl),
                    ];
                }
            }
        }else{
            $lanauages[$this->language] = [
                'string' => Config::languageString($this->language),
                'url' => $this->hostUrl,
            ];
        }
        
        return $lanauages;
    }

    private function isLanguageFileName()
    {
        foreach (Config::languages() as $language) {
            if (strpos($language, $this->fileName) !== false) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function getPackage(Main $markGitDoc = null)
    {
        if (empty($this->package)) {
            $this->package = $markGitDoc->package($this->packageName, Pull::$branchToVersions[$this->packageName][$this->branch], $this->language);
        }

        return $this->package;
    }
}