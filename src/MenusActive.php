<?php

namespace PHPZlc\MarkGitDoc;

use PHPZlc\MarkGitDoc\Repo\File;
use PHPZlc\MarkGitDoc\Repo\Package;

class MenusActive
{
    private $keyLinks = [];

    private $isActive = false;

    public function packageMenusActive(Package $package, File $file)
    {
        foreach ($package->menus as $key => &$menu){
            if(!$this->isActive) {
                $this->keyLinks[] = $key;
            }

            if ($menu['file']->urlPath == $file->urlPath) {
                $menu['active'] = 1;
            } else {
                $menu['active'] = 0;
            }

            if(empty($menu['menus'])){
                if($menu['active'] == 1){
                    $this->isActive = true;
                }
            }else{
                $this->menusActive($menu['menus'], $file->urlPath);
            }

            if(!$this->isActive) {
                $this->keyLinks = [];
            }
        }

        foreach ($this->keyLinks as $key => $value){
            $code = '$package->menus';
            for ($i = 0; $i <= $key; $i++){
                if($key == 0){
                    $code .= "[" . $this->keyLinks[$i] . "]['active'] = 1;";
                } else{
                    $code .= "[". $this->keyLinks[$i] ."]['menus']";
                    if($i == $key){
                        $code .= "['active'] = 1;";
                    }
                }
            }
            eval($code);
        }
    }

    private function menusActive(&$menus, $curUrl)
    {
        foreach ($menus as $key => &$menu) {
            if(!$this->isActive) {
                $this->keyLinks[] = $key;
            }

            if ($menu['url'] == $curUrl) {
                $menu['active'] = 1;
            } else {
                $menu['active'] = 0;
            }

            if(empty($menu['menus'])){
                if($menu['active'] == 1){
                    $this->isActive = true;
                }else{
                    if(!$this->isActive) {
                       array_pop($this->keyLinks);
                    }
                }
            }else{
                $this->menusActive($menu['menus'], $curUrl);
            }
        }
    }
}