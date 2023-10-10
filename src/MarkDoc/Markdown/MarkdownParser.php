<?php

namespace PHPZlc\MarkGitDoc\MarkDoc\Markdown;

use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use PHPZlc\MarkGitDoc\MarkDoc\InterfaceParser;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalink;
use League\CommonMark\Inline\Element\Code;
use League\CommonMark\Inline\Element\Text;
use League\CommonMark\Inline\Element\Image;
use League\CommonMark\Inline\Element\Link;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use PHPZlc\MarkGitDoc\Repo\File;

class MarkdownParser implements InterfaceParser
{
    /**
     * @var File 
     */
    public static $file;

    public static $menus = [];

    public static $params = [];

    private $html;
    
    public function __construct(File $file)
    {
        self::$file = $file;
        self::$menus = [];
        self::$params = [];

        $environment = Environment::createCommonMarkEnvironment();
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new InlineRendererExtension());
        $environment->addInlineRenderer(Text::class, new TextRenderer());
        $environment->addInlineRenderer(Image::class, new ImageRenderer());
        $environment->addInlineRenderer(Link::class, new LinkRenderer());

        $environment->mergeConfig([
            'html_input' => 'strip',
        ]);

        
        $parser = new DocParser($environment);
        $htmlRenderer = new HtmlRenderer($environment);

        $document = $parser->parse(file_get_contents($file->filePath));
        $this->html = $htmlRenderer->renderBlock($document);

    }

    public function getParams()
    {
       return self::$params;
    }

    public function getMenus()
    {
        return self::$menus;
    }

    public function getHtml()
    {
        return $this->html;
    }
}