<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use think\Db;

class FrontendController extends HomeBaseController
{

	public function _initialize()
    {
        //不加这句是重写该构造方法，
        parent::_initialize();

    	//获得控制器 方法 名称 
    	$request = \think\Request::instance();
		$module_name = $request->module();
		$controller_name = $request->controller();
		$action = $request->action();
		$active_url = $module_name.'/'.$controller_name.'/'.$action;
		
		$this->assign('controller_name', $controller_name);
		$this->assign('module_name', $module_name);
		$this->assign('action', $action);
		$this->assign('url', $active_url);

        //分类
        $cate = Db::name('portal_category')->where('status>0')->select();

        //文章
        $f_article = Db::name('portal_post')->where('post_status=1')->limit(5)->select();

        //标签
        $tags = Db::name('portal_tag')->field('name')->where('status=1')->select();

        //友情连接
        $flinks = Db::name('link')->where('status>0')->select();


        $this->assign('cate', $cate);
        $this->assign('tags', $tags);
        $this->assign('f_article',$f_article);
        $this->assign('flinks', $flinks);

    }



}