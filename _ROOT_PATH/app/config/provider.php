<?php

/**
 * 服务商绑定
 */

//格式如：'别名或抽象类'=>'具体类或者匿名函数'
return [
    \lfly\contract\TemplateHandlerInterface::class => \lfly\view\Template::class
];
