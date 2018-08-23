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

use app\portal\controller\FrontendController;
use think\Db;

class IndexController extends FrontendController
{

	public function index()
    {

    	$field = 'p.*,c.name';

    	$join = [
            ['__PORTAL_CATEGORY_POST__ b', 'b.post_id = p.id'],
            ['__PORTAL_CATEGORY__ c', 'b.category_id = c.id']
        ];

        $article = Db::name('portal_post')
        		->alias('p')->field($field)
                ->join($join)
                ->where("p.post_status=1")
                ->order("p.published_time desc")
                ->paginate(5);

                
        $this->assign('articles', $article);
        $this->assign('page', $article->render());
        return $this->fetch(':index');
    }


 
}
