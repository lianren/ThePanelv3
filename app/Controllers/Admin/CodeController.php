<?php

namespace App\Controllers\Admin;

use App\Models\Code;
use App\Controllers\AdminController;
use App\Utils\Tools;


class CodeController extends AdminController
{
    public function index($request, $response, $args){
		$pageNum = 1;
        if (isset($request->getQueryParams()["page"])) {
            $pageNum = $request->getQueryParams()["page"];
        }
        $codes = Code::orderBy('id', 'desc')->paginate(15, ['*'], 'page', $pageNum);
		$codes->setPath('/admin/code');
        return $this->view()->assign('codes',$codes)->display('admin/code/index.tpl');
    }

    public function create($request, $response, $args){
        return $this->view()->display('admin/code/add.tpl');
    }

    public function add($request, $response, $args){
		
		$n = $request->getParam('amount');
		$type = $request->getParam('type');
		$number = $request->getParam('number');

		
		for ($i = 0; $i < $n; $i++) {
            $char = Tools::genRandomChar(32);
            $code = new Code();
            $code->code = time() . $char;
            $code->type = $type;
			$code->number = $number;
			$code->userid=0;
			$code->usedatetime="1989:06:04 02:30:00";
            $code->save();
        }
		
		
		
        $rs['ret'] = 1;
        $rs['msg'] = "兑换码添加成功";
        return $response->getBody()->write(json_encode($rs));
    }
}