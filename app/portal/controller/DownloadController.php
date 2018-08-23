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
use app\portal\service\DownloadService;
use app\portal\model\PortalDownloadModel;
use think\Db;

class DownloadController extends FrontendController
{


    /**
     * 下载列表页
     */
    public function index()
    {
        $download = Db::name('portal_download')
            ->where(array('post_status'=>1))
            ->order("published_time desc")
            ->paginate(5);

        $this->assign('download', $download);
        $this->assign('page', $download->render());
        return $this->fetch(':download_index');

    }




    /**
     * 下载内容页
     */
    public function download()
    {

        $DownloadService         = new DownloadService();

        $downloadId  = $this->request->param('id', 0, 'intval');
        $download    = $DownloadService->publishedDownload($downloadId);


        if (empty($download))
        {
            abort(404, '不存在!');
        }

        $prevDownload = $DownloadService->publishedPrevDownload($downloadId);
        $nextDownload = $DownloadService->publishedNextDownload($downloadId);

        $tplName = 'download';

        Db::name('portal_download')->where(['id' => $downloadId])->setInc('post_hits');


        hook('portal_before_assign_download', $download);

        $this->assign('download', $download);
        $this->assign('prev_download', $prevDownload);
        $this->assign('next_download', $nextDownload);

        $tplName = empty($download['more']['template']) ? $tplName : $download['more']['template'];

        return $this->fetch("/$tplName");
    }



    /**
     * 下载文件
     */
    public function down()
    {
        $id = $this->request->param('id');

        $type = Db::name('portal_download')->where(array("id"=>$id))->find();
        $type['more'] = json_decode($type['more']);

        $file_url = $type['more']->files[0]->url;

        $file = $type['more']->files[0]->name;

        $sql_file = UPLOAD_PATH . $file_url;

        if (file_exists($sql_file))
        {
            Db::name('portal_download')->where(array('id'=>$id))->setInc('download_hits');
            ob_end_clean();
            header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
            header( "Content-Description: File Transfer" );
            header( "Content-Type: application/octet-stream" );
            header( "Content-Length: " . filesize( $sql_file ) );
            header("Content-Disposition: attachment; filename=\"" . iconv("UTF-8","gbk",basename($sql_file)))."\"";
            @readfile( $sql_file );
            exit();

        } else{

            $this->error('没有找到下载的文件');
        }

    }


}