<?php
/**
 * Created by PhpStorm.
 * User: Christoph
 * Date: 15.02.15
 * Time: 19:08.
 */
class OMenu
{
    private $prefix;
    private $hasButtons = false;
    private $items;
    private $build = [
      ':sub',
      '<li data-route=":route" class=":opened :active"><a href=":link">:title</a><ul>:sub</ul>:btn</li>',
    ];
    private $btn = '<div class="sub-menu-btn"><div class="a"></div><div class="b"></div><div class="c"></div></div>';

    public function __construct($args)
    {
      $this->prefix = $args['prefix'];
      $this->hasButtons = $args['hasButtons'];
      $this->items = $args['items'];
    }

    public function show()
    {
        return $this->compose_menu(['', '', $this->items], $this->prefix, 0, $this->build[0]);
    }

    public function compose_menu($arr, $nav, $lvl, $build)
    {
        $isActive = $this->isActive($arr[1]);
        $hasSub = count($arr) > 2;

        // insert page title
        $str = str_replace(':title', $arr[0], $build);

        // update nav link
        $nav .= strlen($arr[1]) > 0 ? $arr[1] . '/' : '';

        // insert link
        $str = str_replace(':link', $nav, $str);

        // insert active status
        $str = str_replace(':active', $isActive ? 'active' : '', $str);

        // insert openend status
        $str = str_replace(':opened', $isActive && $hasSub ? 'opened' : '', $str);

        // insert route
        $str = str_replace(':route', $arr[1], $str);

        // insert menu btn
        $str = str_replace(':btn', $hasSub ? $this->btn : '', $str);

        if (count($arr) > 2) {
            foreach ($arr[2] as $item) {
                $str = str_replace(':sub', $this->compose_menu($item, $nav, $lvl + 1, $this->build[1]).':sub', $str);
            }
        }
        
        $str = str_replace(':sub', '', $str);

        return $str;
    }

    public function isActive($path)
    {
        $urlParts = explode('/', URL);
        $pathParts = explode('/', $path);

        for ($i = 0; $i < count($pathParts); ++$i) {
            if ($i == count($urlParts)) {
                return false;
            }
            if ($pathParts[$i] != $urlParts[$i]) {
                return false;
            }
        }

        return true;
    }
}
