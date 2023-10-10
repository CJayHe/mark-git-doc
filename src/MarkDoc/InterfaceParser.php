<?php
namespace PHPZlc\MarkGitDoc\MarkDoc;

interface InterfaceParser
{
    /**
     * @return array
     */
    public function getParams();

    /**
     * @return array
     */
    public function getMenus();

    /**
     * @return string
     */
    public function getHtml();
}