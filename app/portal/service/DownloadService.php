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

use app\portal\model\PortalDownloadModel;

class DownloadService
{

    public function adminDownloadList($filter)
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

        $startTime = empty($filter['start_time']) ? 0 : strtotime($filter['start_time']);
        $endTime   = empty($filter['end_time']) ? 0 : strtotime($filter['end_time']);
        if (!empty($startTime) && !empty($endTime)){
            $where['a.published_time'] = [['>= time', $startTime], ['<= time', $endTime]];
        } else {
            if (!empty($startTime)){
                $where['a.published_time'] = ['>= time', $startTime];
            }
            if (!empty($endTime)){
                $where['a.published_time'] = ['<= time', $endTime];
            }
        }

        $keyword = empty($filter['keyword']) ? '' : $filter['keyword'];
        if (!empty($keyword)){
            $where['a.post_title'] = ['like', "%$keyword%"];
        }

        if ($isPage){
            $where['a.post_type'] = 2;
        } else {
            $where['a.post_type'] = 1;
        }

        $portalDownloadModel = new PortalDownloadModel();
        $download       = $portalDownloadModel->alias('a')->field($field)
            ->join($join)
            ->where($where)
            ->order('update_time', 'DESC')
            ->paginate(10);

        return $download;

    }



    public function publishedDownload($postId)
    {
        $portalDownloadModel = new PortalDownloadModel();

        $where = [
            'post.post_type'      => 1,
            'post.published_time' => [['< time', time()], ['> time', 0]],
            'post.post_status'    => 1,
            'post.delete_time'    => 0,
            'post.id'             => $postId
        ];

        $download = $portalDownloadModel->where($where)->alias('post')
            ->find();
        
        return $download;
    }


    //上一篇文章
    public function publishedPrevDownload($postId)
    {
        $portalDownloadModel = new PortalDownloadModel();

        $where = [
            'post.post_type'      => 1,
            'post.published_time' => [['< time', time()], ['> time', 0]],
            'post.post_status'    => 1,
            'post.delete_time'    => 0,
            'post.id '            => ['<', $postId]
        ];

        $download = $portalDownloadModel->where($where)->alias('post')
            ->order('id', 'DESC')
            ->find();

        return $download;
    }



    //下一篇文章
    public function publishedNextDownload($postId)
    {
        $portalDownloadModel = new PortalDownloadModel();

        $where = [
            'post.post_type'      => 1,
            'post.published_time' => [['< time', time()], ['> time', 0]],
            'post.post_status'    => 1,
            'post.delete_time'    => 0,
            'post.id'             => ['>', $postId]
        ];

        $download = $portalDownloadModel->where($where)->alias('post')
            ->order('id', 'ASC')
            ->find();


        return $download;
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

        $portalDownloadModel = new PortalDownloadModel();
        $page            = $portalDownloadModel
            ->where($where)
            ->find();

        return $page;
    }

}