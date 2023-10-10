<?php
namespace PHPZlc\MarkGitDoc\Repo;

use function DeepCopy\deep_copy;

class Repo
{
    const SSH = 'ssh';

    const HTTPS = 'https';

    const GITEE = 'gitee';

    const GITHUB = 'github';

    /**
     * @var string
     */
    public $indexUrl;

    /**
     * @var string
     */
    public $cloneUrl;

    /**
     * @var string
     */
    public $platform;

    /**
     * @var string
     */
    public $cloneType;

    /**
     * @var string
     */
    public $userName;

    /**
     * @var string
     */
    public $repository;

    /**
     * @var array
     */
    public $branchs;

    /**
     * @var string
     */
    public $time;

    public function __construct($cloneUrl = null)
    {
        $this->time = date("Y-m-d H:i:s");

        if($cloneUrl !== null) {
            try {
                $this->cloneUrl = $cloneUrl;

                $this->platform = strpos($cloneUrl, self::GITEE) === false ? self::GITHUB : self::GITEE;
                $this->cloneType = strpos($cloneUrl, self::HTTPS) === false ? self::SSH : self::HTTPS;

                if ($this->cloneType == self::SSH) {
                    $arr = explode(':', $cloneUrl);
                    $arr = explode('/', $arr[1]);
                    $this->userName = $arr[0];
                    $this->repository = rtrim($arr[1], '.git');
                } else {
                    $arr = explode('/', $cloneUrl);
                    $this->userName = $arr[count($arr) - 2];
                    $this->repository = rtrim($arr[count($arr) - 1], '.git');
                }

                if ($this->platform == self::GITHUB) {
                    $this->indexUrl = 'https://github.com/' . $this->userName . '/' . $this->repository;
                } else {
                    $this->indexUrl = 'https://gitee.com/' . $this->userName . '/' . $this->repository;
                }
            } catch (\Exception $exception) {
                die('clone url 格式不正确' . $exception->getMessage());
            }
        }
    }

}