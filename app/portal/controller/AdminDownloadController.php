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

use cmf\controller\AdminBaseController;
use app\portal\model\PortalDownloadModel;
use app\portal\service\DownloadService;
use think\Db;
use app\admin\model\ThemeModel;

class AdminDownloadController extends AdminBaseController
{

	public function index()
    {

        $param = $this->request->param();

        $postService = new DownloadService();
        $data        = $postService->adminDownloadList($param);

        $data->appends($param);

        $this->assign('start_time', isset($param['start_time']) ? $param['start_time'] : '');
        $this->assign('end_time', isset($param['end_time']) ? $param['end_time'] : '');
        $this->assign('keyword', isset($param['keyword']) ? $param['keyword'] : '');
        $this->assign('download', $data->items());
        $this->assign('page', $data->render());

        return $this->fetch();
    }





    public function add()
    {
        $themeModel        = new ThemeModel();
        $articleThemeFiles = $themeModel->getActionThemeFiles('portal/Download/index');
        $this->assign('article_theme_files', $articleThemeFiles);
        return $this->fetch();
    }





    public function addPost()
    {
        if ($this->request->isPost())
        {
            $data   = $this->request->param();

            //状态只能设置默认值。未发布、未置顶、未推荐
            $data['post']['post_status'] = 0;
            $data['post']['is_top'] = 0;
            $data['post']['recommended'] = 0;

            $post   = $data['post'];

            $result = $this->validate($post, 'AdminDownload');
            if ($result !== true) {
                $this->error($result);
            }

            $portalDownloadModel = new PortalDownloadModel();

            if (!empty($data['photo_names']) && !empty($data['photo_urls']))
            {
                $data['post']['more']['photos'] = [];
                foreach ($data['photo_urls'] as $key => $url){
                    $photoUrl = cmf_asset_relative_url($url);
                    array_push($data['post']['more']['photos'], ["url" => $photoUrl, "name" => $data['photo_names'][$key]]);
                }
            }

            if (!empty($data['file_names']) && !empty($data['file_urls'])){
                $data['post']['more']['files'] = [];
                foreach ($data['file_urls'] as $key => $url) {
                    $fileUrl = cmf_asset_relative_url($url);
                    array_push($data['post']['more']['files'], ["url" => $fileUrl, "name" => $data['file_names'][$key]]);
                }
            }


            $portalDownloadModel->adminAddDownload($data['post']);

            $data['post']['id'] = $portalDownloadModel->id;
            $hookParam          = [
                'is_add'  => true,
                'download' => $data['post']
            ];
            hook('portal_admin_after_save_download', $hookParam);


            $this->success('添加成功!', url('AdminDownload/edit', ['id' => $portalDownloadModel->id]));
        }

    }




    public function edit()
    {
        $id = $this->request->param('id', 0, 'intval');

        $portalDownloadModel = new PortalDownloadModel();
        $post            = $portalDownloadModel->where('id', $id)->find();

        $themeModel        = new ThemeModel();
        $articleThemeFiles = $themeModel->getActionThemeFiles('portal/Download/index');
        $this->assign('download_theme_files', $articleThemeFiles);
        $this->assign('post', $post);

        return $this->fetch();
    }





    public function editPost()
    {

        if ($this->request->isPost()) 
        {
            $data   = $this->request->param();

            //需要抹除发布、置顶、推荐的修改。
            unset($data['post']['post_status']);
            unset($data['post']['is_top']);
            unset($data['post']['recommended']);

            $post   = $data['post'];
            $result = $this->validate($post, 'AdminDownload');
            if ($result !== true) {
                $this->error($result);
            }

            $portalDownloadModel = new PortalDownloadModel();

            if (!empty($data['photo_names']) && !empty($data['photo_urls'])) {
                $data['post']['more']['photos'] = [];
                foreach ($data['photo_urls'] as $key => $url) {
                    $photoUrl = cmf_asset_relative_url($url);
                    array_push($data['post']['more']['photos'], ["url" => $photoUrl, "name" => $data['photo_names'][$key]]);
                }
            }

            if (!empty($data['file_names']) && !empty($data['file_urls'])) {
                $data['post']['more']['files'] = [];
                foreach ($data['file_urls'] as $key => $url) {
                    $fileUrl = cmf_asset_relative_url($url);
                    array_push($data['post']['more']['files'], ["url" => $fileUrl, "name" => $data['file_names'][$key]]);
                }
            }

            $portalDownloadModel->adminEditDownload($data['post']);

            $hookParam = [
                'is_add'  => false,
                'download' => $data['post']
            ];
            hook('portal_admin_after_save_download', $hookParam);

            $this->success('保存成功!');

        }
    }





    public function delete()
    {
        $param           = $this->request->param();
        $portalPostModel = new PortalDownloadModel();

        if (isset($param['id'])) {
            $id           = $this->request->param('id', 0, 'intval');
            $result       = $portalPostModel->where(['id' => $id])->find();
            $data         = [
                'object_id'   => $result['id'],
                'create_time' => time(),
                'table_name'  => 'portal_download',
                'name'        => $result['post_title'],
                'user_id'=>cmf_get_current_admin_id()
            ];
            $resultPortal = $portalPostModel
                ->where(['id' => $id])
                ->update(['delete_time' => time()]);
            if ($resultPortal) {
                Db::name('portal_tag_download')->where(['download_id'=>$id])->update(['status'=>0]);

                Db::name('recycleBin')->insert($data);
            }
            $this->success("删除成功！", '');

        }

        if (isset($param['ids']))
        {
            $ids     = $this->request->param('ids/a');
            $recycle = $portalPostModel->where(['id' => ['in', $ids]])->select();
            $result  = $portalPostModel->where(['id' => ['in', $ids]])->update(['delete_time' => time()]);
            if ($result) {
                Db::name('portal_tag_download')->where(['download_id' => ['in', $ids]])->update(['status'=>0]);
                foreach ($recycle as $value)
                {
                    $data = [
                        'object_id'   => $value['id'],
                        'create_time' => time(),
                        'table_name'  => 'portal_download',
                        'name'        => $value['post_title'],
                        'user_id'=>cmf_get_current_admin_id()
                    ];
                    Db::name('recycleBin')->insert($data);
                }
                $this->success("删除成功！", '');
            }
        }
    }





    public function publish()
    {
        $param           = $this->request->param();
        $portalPostModel = new PortalDownloadModel();

        if (isset($param['ids']) && isset($param["yes"])) {
            $ids = $this->request->param('ids/a');

            $portalPostModel->where(['id' => ['in', $ids]])->update(['post_status' => 1, 'published_time' => time()]);

            $this->success("发布成功！", '');
        }

        if (isset($param['ids']) && isset($param["no"])) {
            $ids = $this->request->param('ids/a');

            $portalPostModel->where(['id' => ['in', $ids]])->update(['post_status' => 0]);

            $this->success("取消发布成功！", '');
        }

    }


}