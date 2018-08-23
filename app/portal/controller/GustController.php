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

use app\user\model\CommentModel;
use think\Validate;
use app\portal\controller\FrontendController;
use think\Db;

class GustController extends FrontendController
{

	
    public function index()
    {
        $content = Db::name('comment')->where('status>0')->order('create_time desc')->paginate(5);

        //友情链接
        $flinks = Db::name('link')->where('status>0')->select();

        $this->assign('flinks',$flinks);
        $this->assign('contents', $content);
        $this->assign('page', $content->render());
        return $this->fetch(':gust');
    }



    /**
     * 留言
     */
    public function addGust()
    {

        if ($this->request->isPost())
        {
            $validate = new Validate([
                'captcha'  => 'require',
                'full_name' => 'require',
                'email' => 'require',
                'content' => 'require',
            ]);
            $validate->message([
                'full_name.require' => '用户名不能为空',
                'email.require' => '用户名不能为空',
                'content.require' => '用户名不能为空',
                'captcha.require'  => '验证码不能为空',
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)){
                $this->error($validate->getError());
            }

            if (!cmf_captcha_check($data['captcha'])) {
                $this->error(lang('CAPTCHA_NOT_RIGHT'));
            }

            unset($data['captcha']);
            unset($data['_captcha_id']);
            $data['status'] = 0;
            $data['create_time'] = time();
            if(Db::name("comment")->insert($data)){
                $this->success('留言成功');
            };

        } else {
            $this->error("请求错误");
        }
    }


}