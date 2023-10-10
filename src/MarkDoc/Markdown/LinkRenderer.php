<?php

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * Original code based on the CommonMark JS reference parser (https://bitly.com/commonmark-js)
 *  - (c) John MacFarlane
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPZlc\MarkGitDoc\MarkDoc\Markdown;

use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;
use League\CommonMark\Inline\Element\AbstractInline;
use League\CommonMark\Inline\Element\Link;
use League\CommonMark\Inline\Renderer\InlineRendererInterface;
use League\CommonMark\Util\ConfigurationAwareInterface;
use League\CommonMark\Util\ConfigurationInterface;
use League\CommonMark\Util\RegexHelper;
use PHPZlc\MarkGitDoc\Pull;
use PHPZlc\MarkGitDoc\Repo\File;
use PHPZlc\MarkGitDoc\Unit;

final class LinkRenderer implements InlineRendererInterface, ConfigurationAwareInterface
{
    /**
     * @var ConfigurationInterface
     */
    protected $config;

    /**
     * @param Link                     $inline
     * @param ElementRendererInterface $htmlRenderer
     *
     * @return HtmlElement
     */
    public function render(AbstractInline $inline, ElementRendererInterface $htmlRenderer)
    {
        if (!($inline instanceof Link)) {
            throw new \InvalidArgumentException('Incompatible inline type: ' . \get_class($inline));
        }

        $attrs = $inline->getData('attributes', []);

        $forbidUnsafeLinks = !$this->config->get('allow_unsafe_links');
        if (!($forbidUnsafeLinks && RegexHelper::isLinkPotentiallyUnsafe($inline->getUrl()))) {
            $attrs['href'] = $inline->getUrl();
            if(strpos($inline->getUrl(), 'http') !== 0){
                $file = new File(MarkdownParser::$file->branch, MarkdownParser::$file->packageName);
                if($file->parserToFileCache(str_replace('/' , DIRECTORY_SEPARATOR, $inline->getUrl()), MarkdownParser::$file->curLanguage)){
                    $attrs['href'] = $file->hostUrl;
                }
            }
        }

        if (isset($inline->data['title'])) {
            $attrs['title'] = $inline->data['title'];
        }

        if (isset($attrs['target']) && $attrs['target'] === '_blank' && !isset($attrs['rel'])) {
            $attrs['rel'] = 'noopener noreferrer';
        }

        return new HtmlElement('a', $attrs, $htmlRenderer->renderInlines($inline->children()));
    }

    public function setConfiguration(ConfigurationInterface $configuration)
    {
        $this->config = $configuration;
    }
}
