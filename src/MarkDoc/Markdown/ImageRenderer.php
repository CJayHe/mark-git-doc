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

use League\CommonMark\Inline\Renderer\InlineRendererInterface;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;
use League\CommonMark\Inline\Element\AbstractInline;
use League\CommonMark\Inline\Element\Image;
use League\CommonMark\Util\ConfigurationAwareInterface;
use League\CommonMark\Util\ConfigurationInterface;
use League\CommonMark\Util\RegexHelper;
use PHPZlc\MarkGitDoc\Config;

final class ImageRenderer implements InlineRendererInterface, ConfigurationAwareInterface
{
    /**
     * @var ConfigurationInterface
     */
    protected $config;

    /**
     * @param Image                    $inline
     * @param ElementRendererInterface $htmlRenderer
     *
     * @return HtmlElement
     */
    public function render(AbstractInline $inline, ElementRendererInterface $htmlRenderer)
    {
        if (!($inline instanceof Image)) {
            throw new \InvalidArgumentException('Incompatible inline type: ' . \get_class($inline));
        }

        $attrs = $inline->getData('attributes', []);

        $forbidUnsafeLinks = !$this->config->get('allow_unsafe_links');
        if ($forbidUnsafeLinks && RegexHelper::isLinkPotentiallyUnsafe($inline->getUrl())) {
            $attrs['src'] = '';
        } else {
            if(strpos($inline->getUrl(), 'http') !== 0){
                $host = str_replace('index.php', '', $_SERVER['SCRIPT_NAME']) . Config::REPO_DIR_NAME . '/' . MarkdownParser::$file->branch;
                if(strpos($inline->getUrl(), '/') === 0){
                    $attrs['src'] = $host . $inline->getUrl();
                }else{
                    $attrs['src'] = $host . '/' . $inline->getUrl();
                }
            }else{
                $attrs['src'] = $inline->getUrl();
            }
        }

        $alt = $htmlRenderer->renderInlines($inline->children());
        $alt = \preg_replace('/\<[^>]*alt="([^"]*)"[^>]*\>/', '$1', $alt);
        $attrs['alt'] = \preg_replace('/\<[^>]*\>/', '', $alt);

        if (isset($inline->data['title'])) {
            $attrs['title'] = $inline->data['title'];
        }else{
            $attrs['title'] = '';
        }

        return <<<EOF
        <div class="lightbox_shortcode">
            <img src="{$attrs['src']}" alt="{$attrs['title']}">
            <a href="{$attrs['src']}" alt="{$attrs['title']}" class="img_popup"><i class="icon_plus"></i></a>
        </div>
EOF;
    }

    public function setConfiguration(ConfigurationInterface $configuration)
    {
        $this->config = $configuration;
    }
}
