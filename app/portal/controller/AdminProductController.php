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
use app\portal\model\PortalProductModel;
use app\portal\service\ProductService;
use think\Db;
use app\admin\model\ThemeModel;

class AdminProductController extends AdminBaseController
{

	public function index()
    {

        $param = $this->request->param();

        $categoryId = $this->request->param('category', 0, 'intval');

        $productService = new ProductService();
        $data        = $productService->adminProductList($param);

        $data->appends($param);

        $this->assign('start_time', isset($param['start_time']) ? $param['start_time'] : '');
        $this->assign('end_time', isset($param['end_time']) ? $param['end_time'] : '');
        $this->assign('keyword', isset($param['keyword']) ? $param['keyword'] : '');
        $this->assign('product', $data->items());
        $this->assign('page', $data->render());

        return $this->fetch();
    }





    public function add()
    {
        $themeModel        = new ThemeModel();
        $articleThemeFiles = $themeModel->getActionThemeFiles('portal/Product/index');
        $this->assign('product_theme_files', $articleThemeFiles);
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

            $result = $this->validate($post, 'AdminProduct');
            if ($result !== true) {
                $this->error($result);
            }

            $portalProductModel = new PortalProductModel();

            if (!empty($data['photo_names']) && !empty($data['photo_urls'])){
                $data['post']['more']['photos'] = [];
                foreach ($data['photo_urls'] as $key => $url){
                    $photoUrl = cmf_asset_relative_url($url);
                    array_push($data['post']['more']['photos'], ["url" => $photoUrl, "name" => $data['photo_names'][$key]]);
                }
            }

            if (!empty($data['file_names']) && !empty($data['file_urls'])){
                $data['post']['more']['files'] = [];
                foreach ($data['file_urls'] as $key => $url){
                    $fileUrl = cmf_asset_relative_url($url);
                    array_push($data['post']['more']['files'], ["url" => $fileUrl, "name" => $data['file_names'][$key]]);
                }
            }


            $portalProductModel->adminAddProduct($data['post'], $data['post']['categories']);

            $data['post']['id'] = $portalProductModel->id;
            $hookParam          = [
                'is_add'  => true,
                'product' => $data['post']
            ];
            hook('portal_admin_after_save_product', $hookParam);

            $this->success('添加成功!', url('AdminProduct/edit', ['id' => $portalProductModel->id]));
        }

    }




    public function edit()
    {
        $id = $this->request->param('id', 0, 'intval');

        $portalProductModel = new PortalProductModel();
        $post            = $portalProductModel->where('id', $id)->find();
        $postCategories  = $post->categories()->alias('a')->column('a.name', 'a.id');
        $postCategoryIds = implode(',', array_keys($postCategories));

        $themeModel        = new ThemeModel();
        $articleThemeFiles = $themeModel->getActionThemeFiles('portal/Product/index');
        $this->assign('product_theme_files', $articleThemeFiles);
        $this->assign('post', $post);
        $this->assign('post_categories', $postCategories);
        $this->assign('post_category_ids', $postCategoryIds);

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
            $result = $this->validate($post, 'AdminProduct');
            if ($result !== true) {
                $this->error($result);
            }

            $portalProductModel = new PortalProductModel();

            if (!empty($data['photo_names']) && !empty($data['photo_urls'])){
                $data['post']['more']['photos'] = [];
                foreach ($data['photo_urls'] as $key => $url) {
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

            $portalProductModel->adminEditProduct($data['post'], $data['post']['categories']);

            $hookParam = [
                'is_add'  => false,
                'product' => $data['post']
            ];
            hook('portal_admin_after_save_product', $hookParam);

            $this->success('保存成功!');

        }
    }





    public function delete()
    {
        $param           = $this->request->param();
        $portalProductModel = new PortalProductModel();

        if (isset($param['id'])) {
            $id           = $this->request->param('id', 0, 'intval');
            $result       = $portalProductModel->where(['id' => $id])->find();
            $data         = [
                'object_id'   => $result['id'],
                'create_time' => time(),
                'table_name'  => 'portal_post',
                'name'        => $result['post_title'],
                'user_id'=>cmf_get_current_admin_id()
            ];
            $resultPortal = $portalProductModel
                ->where(['id' => $id])
                ->update(['delete_time' => time()]);
            if ($resultPortal) {
                Db::name('portal_pro_category')->where(['product_id'=>$id])->update(['status'=>0]);
                Db::name('portal_tag_product')->where(['product_id'=>$id])->update(['status'=>0]);

                Db::name('recycleBin')->insert($data);
            }
            $this->success("删除成功！", '');

        }

        if (isset($param['ids'])) {
            $ids     = $this->request->param('ids/a');
            $recycle = $portalProductModel->where(['id' => ['in', $ids]])->select();
            $result  = $portalProductModel->where(['id' => ['in', $ids]])->update(['delete_time' => time()]);
            if ($result){
                foreach ($recycle as $value) {
                    $data = [
                        'object_id'   => $value['id'],
                        'create_time' => time(),
                        'table_name'  => 'portal_product_id',
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
        $portalProductModel = new PortalProductModel();

        if (isset($param['ids']) && isset($param["yes"]))
        {
            $ids = $this->request->param('ids/a');

            $portalProductModel->where(['id' => ['in', $ids]])->update(['post_status' => 1, 'published_time' => time()]);

            $this->success("发布成功！", '');
        }

        if (isset($param['ids']) && isset($param["no"]))
        {
            $ids = $this->request->param('ids/a');

            $portalProductModel->where(['id' => ['in', $ids]])->update(['post_status' => 0]);

            $this->success("取消发布成功！", '');
        }

    }


}