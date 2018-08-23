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
namespace app\portal\service;

use app\portal\model\PortalProductModel;

class ProductService
{

    public function adminProductList($filter)
    {
        return $this->adminPostList($filter);
    }

    public function adminPageList($filter)
    {
        return $this->adminPostList($filter, true);
    }


    public function adminPostList($filter, $isPage = false)
    {
        $where = [
            'a.create_time' => ['>=', 0],
            'a.delete_time' => 0
        ];

        $join = [
            ['__USER__ u', 'a.user_id = u.id']
        ];

        $field = 'a.*,u.user_login,u.user_nickname,u.user_email';

        $category = empty($filter['category']) ? 0 : intval($filter['category']);
        if (!empty($category)) {
            $where['b.category_id'] = ['eq', $category];
            array_push($join, [
                '__PORTAL_PRO_CATEGORY_PRODUCT__ b', 'a.id = b.product_id'
            ]);
            $field = 'a.*,b.id AS product_category_id,b.list_order,b.category_id,u.user_login,u.user_nickname,u.user_email';
        }

        $startTime = empty($filter['start_time']) ? 0 : strtotime($filter['start_time']);
        $endTime   = empty($filter['end_time']) ? 0 : strtotime($filter['end_time']);
        if (!empty($startTime) && !empty($endTime)) {
            $where['a.published_time'] = [['>= time', $startTime], ['<= time', $endTime]];
        } else {
            if (!empty($startTime)) {
                $where['a.published_time'] = ['>= time', $startTime];
            }
            if (!empty($endTime)) {
                $where['a.published_time'] = ['<= time', $endTime];
            }
        }

        $keyword = empty($filter['keyword']) ? '' : $filter['keyword'];
        if (!empty($keyword)) {
            $where['a.post_title'] = ['like', "%$keyword%"];
        }

        if ($isPage) {
            $where['a.post_type'] = 2;
        } else {
            $where['a.post_type'] = 1;
        }

        $portalProductModel = new PortalProductModel();
        $product        = $portalProductModel->alias('a')->field($field)
            ->join($join)
            ->where($where)
            ->order('update_time', 'DESC')
            ->paginate(10);

        return $product;

    }



    public function publishedProduct($postId, $categoryId = 0)
    {
        $portalProductModel = new PortalProductModel();

        if (empty($categoryId)) 
        {

            $where = [
                'post.post_type'      => 1,
                'post.published_time' => [['< time', time()], ['> time', 0]],
                'post.post_status'    => 1,
                'post.delete_time'    => 0,
                'post.id'             => $postId
            ];

            $join = [
                ['__PORTAL_PRO_CATEGORY__ relation', 'post.id = relation.product_id'],
                ['__PORTAL_PRO_CATEGORY_PRODUCT__ c', 'relation.category_id = c.id']
            ];

            $product = $portalProductModel->alias('post')->field('post.*,c.id cid,c.name')
                ->join($join)
                ->where($where)
                ->find();
        } else {

            $where = [
                'post.post_type'       => 1,
                'post.published_time'  => [['< time', time()], ['> time', 0]],
                'post.post_status'     => 1,
                'post.delete_time'     => 0,
                'relation.category_id' => $categoryId,
                'relation.post_id'     => $postId
            ];

            $join = [
                ['__PORTAL_PRO_CATEGORY__ relation', 'post.id = relation.product_id']
            ];
            $product = $portalProductModel->alias('post')->field('post.*')
                ->join($join)
                ->where($where)
                ->find();
        }
        
        return $product;
    }


    //上一篇文章
    public function publishedPrevProduct($postId, $categoryId = 0)
    {
        $portalProductModel = new PortalProductModel();

        if (empty($categoryId)) {

            $where = [
                'post.post_type'      => 1,
                'post.published_time' => [['< time', time()], ['> time', 0]],
                'post.post_status'    => 1,
                'post.delete_time'    => 0,
                'post.id '            => ['<', $postId]
            ];

            $product = $portalProductModel->alias('post')->field('post.*')
                ->where($where)
                ->order('id', 'DESC')
                ->find();

        } else {
            $where = [
                'post.post_type'       => 1,
                'post.published_time'  => [['< time', time()], ['> time', 0]],
                'post.post_status'     => 1,
                'post.delete_time'     => 0,
                'relation.category_id' => $categoryId,
                'relation.post_id'     => ['<', $postId]
            ];

            $join    = [
                ['__PORTAL_PRO_CATEGORY__ relation', 'post.id = relation.product_id']
            ];
            $product = $portalProductModel->alias('post')->field('post.*')
                ->join($join)
                ->where($where)
                ->order('id', 'DESC')
                ->find();
        }


        return $product;
    }

    //下一篇文章
    public function publishedNextProduct($postId, $categoryId = 0)
    {
        $portalProductModel = new PortalProductModel();

        if (empty($categoryId)) {

            $where = [
                'post.post_type'      => 1,
                'post.published_time' => [['< time', time()], ['> time', 0]],
                'post.post_status'    => 1,
                'post.delete_time'    => 0,
                'post.id'             => ['>', $postId]
            ];

            $product = $portalProductModel->alias('post')->field('post.*')
                ->where($where)
                ->order('id', 'ASC')
                ->find();
        } else {
            $where = [
                'post.post_type'       => 1,
                'post.published_time'  => [['< time', time()], ['> time', 0]],
                'post.post_status'     => 1,
                'post.delete_time'     => 0,
                'relation.category_id' => $categoryId,
                'relation.post_id'     => ['>', $postId]
            ];

            $join    = [
                ['__PORTAL_PRO_CATEGORY__ relation', 'post.id = relation.product_id']
            ];
            $product = $portalProductModel->alias('post')->field('post.*')
                ->join($join)
                ->where($where)
                ->order('id', 'ASC')
                ->find();
        }


        return $product;
    }

    public function publishedPage($pageId)
    {

        $where = [
            'post_type'      => 2,
            'published_time' => [['< time', time()], ['> time', 0]],
            'post_status'    => 1,
            'delete_time'    => 0,
            'id'             => $pageId
        ];

        $portalProductModel = new PortalProductModel();
        $page            = $portalProductModel
            ->where($where)
            ->find();

        return $page;
    }

}