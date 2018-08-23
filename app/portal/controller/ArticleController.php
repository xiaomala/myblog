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
use app\portal\model\PortalCategoryModel;
use app\portal\service\PostService;
use app\portal\model\PortalPostModel;
use think\Db;

class ArticleController extends FrontendController
{


    /**
     * 文章列表页
     */
    public function index()
    {

        // 分类菜单显示
        $cid = $this->request->param('cid', 0, 'intval');
        
        $where['p.post_status'] = 1;
        if(!empty($cid))
        {
            $where['c.id'] = $cid;
            $cat = Db::name('portal_category')->where("id=".$cid)->column('name');
            $this->assign('cat_name', $cat);
        }    

        $catArr = Db::name('portal_category')->where("id=$cid")->find();// 当前分类

        $field = 'p.*,c.id cid, c.name';

        $join = [
            ['__PORTAL_CATEGORY_POST__ b', 'b.post_id = p.id'],
            ['__PORTAL_CATEGORY__ c', 'b.category_id = c.id']
        ];

        $pre = Config('database.prefix');
        $article = Db::name('portal_post')
                ->alias('p')->field($field)
                ->join($join)
                ->where($where)
                ->order("p.published_time desc")
                ->paginate(5);

        $this->assign('articles', $article);
        $this->assign('catArr', $catArr);
        $this->assign('page', $article->render());
        return $this->fetch(':list');

    }



    /**
     * 文章内容页
     */
    public function article()
    {
        $portalCategoryModel = new PortalCategoryModel();
        $postService         = new PostService();

        $articleId  = $this->request->param('id', 0, 'intval');
        $categoryId = $this->request->param('cid', 0, 'intval');
        $article    = $postService->publishedArticle($articleId, $categoryId);

        if (empty($article))
        {
            abort(404, '文章不存在!');
        }

        $prevArticle = $postService->publishedPrevArticle($articleId, $categoryId);
        $nextArticle = $postService->publishedNextArticle($articleId, $categoryId);

        $tplName = 'article';

        if (empty($categoryId)) {
            $categories = $article['categories'];

            if (count($categories) > 0) {
                $this->assign('category', $categories[0]);
            } else {
                abort(404, '文章未指定分类!');
            }

        } else {
            $category = $portalCategoryModel->where('id', $categoryId)->where('status', 1)->find();

            if (empty($category)) {
                abort(404, '文章不存在!');
            }

            $this->assign('category', $category);

            $tplName = empty($category["one_tpl"]) ? $tplName : $category["one_tpl"];
        }

        Db::name('portal_post')->where(['id' => $articleId])->setInc('post_hits');


        hook('portal_before_assign_article', $article);

        $this->assign('article', $article);
        $this->assign('prev_article', $prevArticle);
        $this->assign('next_article', $nextArticle);

        $tplName = empty($article['more']['template']) ? $tplName : $article['more']['template'];

        return $this->fetch("/$tplName");
    }



    // 文章点赞
    public function doLike()
    {
        $this->checkUserLogin();
        $articleId = $this->request->param('id', 0, 'intval');

        $canLike = cmf_check_user_action("posts$articleId", 1);

        if ($canLike) {
            Db::name('portal_post')->where(['id' => $articleId])->setInc('post_like');

            $this->success("赞好啦！");
        } else {
            $this->error("您已赞过啦！");
        }
    }

}
