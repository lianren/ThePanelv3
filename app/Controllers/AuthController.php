<?php

namespace App\Controllers;

use App\Models\InviteCode;
use App\Services\Config;
use App\Utils\Check;
use App\Utils\Tools;
use App\Utils\Radius;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

use App\Utils\Hash,App\Utils\Da;
use App\Services\Auth;
use App\Models\User;

/**
 *  AuthController
 */

class AuthController extends BaseController
{

    public function login()
    {
        return $this->view()->display('auth/login.tpl');
    }

    public function loginHandle($request, $response, $args)
    {
        // $data = $request->post('sdf');
        $email =  $request->getParam('email');
        $email = strtolower($email);
        $passwd = $request->getParam('passwd');
        $rememberMe = $request->getParam('remember_me');

        // Handle Login
        $user = User::where('email','=',$email)->first();

        if ($user == null){
            $rs['ret'] = 0;
            $rs['msg'] = "401 邮箱或者密码错误";
            return $response->getBody()->write(json_encode($rs));
        }

        if (!Hash::checkPassword($user->pass,$passwd)){
            $rs['ret'] = 0;
            $rs['msg'] = "402 邮箱或者密码错误";
            return $response->getBody()->write(json_encode($rs));
        }
        // @todo
        $time =  3600*24;
        if($rememberMe){
            $time = 3600*24*7;
        }
        Auth::login($user->id,$time);
        $rs['ret'] = 1;
        $rs['msg'] = "欢迎回来";
        return $response->getBody()->write(json_encode($rs));
    }

    public function register($request, $response, $next)
    {
        $ary = $request->getQueryParams();
        $code = "";
        if(isset($ary['code'])){
            $code = $ary['code'];
        }
        return $this->view()->assign('code',$code)->display('auth/register.tpl');
    }

    public function registerHandle($request, $response, $next)
    {
        $name =  $request->getParam('name');
        $email =  $request->getParam('email');
        $email = strtolower($email);
        $passwd = $request->getParam('passwd');
        $repasswd = $request->getParam('repasswd');
        $code = $request->getParam('code');
		$wechat = $request->getParam('wechat');
        // check code
        $c = InviteCode::where('code',$code)->first();
        if ( $c == null) {
            $res['ret'] = 0;
            $res['msg'] = "邀请码无效";
            return $response->getBody()->write(json_encode($res));
        }

        // check email format
        if(!Check::isEmailLegal($email)){
            $res['ret'] = 0;
            $res['msg'] = "邮箱无效";
            return $response->getBody()->write(json_encode($res));
        }
        // check pwd length
        if(strlen($passwd)<8){
            $res['ret'] = 0;
            $res['msg'] = "密码太短";
            return $response->getBody()->write(json_encode($res));
        }

        // check pwd re
        if($passwd != $repasswd){
            $res['ret'] = 0;
            $res['msg'] = "两次密码输入不符";
            return $response->getBody()->write(json_encode($res));
        }

        // check email
        $user = User::where('email',$email)->first();
        if ( $user != null) {
            $res['ret'] = 0;
            $res['msg'] = "邮箱已经被注册了";
            return $response->getBody()->write(json_encode($res));
        }
		
		if($wechat=="")
		{
			$res['ret'] = 0;
            $res['msg'] = "要填上你的微信哦";
            return $response->getBody()->write(json_encode($res));
		}
		
		$user = User::where('wechat',$wechat)->first();
        if ( $user != null) {
            $res['ret'] = 0;
            $res['msg'] = "此微信号已经被注册了";
            return $response->getBody()->write(json_encode($res));
        }

        // do reg user
        $user = new User();
        $user->user_name = $name;
        $user->email = $email;
        $user->pass = Hash::passwordHash($passwd);
        $user->passwd = Tools::genRandomChar(6);
        $user->port = Tools::getLastPort()+1;
        $user->t = 0;
        $user->u = 0;
        $user->d = 0;
		$user->wechat =  filter_var($wechat, FILTER_SANITIZE_STRING);
        $user->transfer_enable = Tools::toGB(Config::get('defaultTraffic'));
        $user->invite_num = Config::get('inviteNum');
        $user->ref_by = $c->user_id;
		$user->expire_in=date("Y-m-d H:i:s",time()+Config::get('user_expire_in_default')*86400);
		$user->reg_date=date("Y-m-d H:i:s");
		$user->money=0;
		$user->node_class=0;
		$user->plan='A';
		$user->node_speedlimit=0;
		$user->theme=Config::get('theme');
		

        if($user->save()){
            $res['ret'] = 1;
            $res['msg'] = "注册成功";


		
			Da::add($email);
			
			Radius::Add($user,$user->passwd);
		




            $c->delete();
            return $response->getBody()->write(json_encode($res));
        }
        $res['ret'] = 0;
        $res['msg'] = "未知错误";
        return $response->getBody()->write(json_encode($res));
    }

    public function logout($request, $response, $next){
        Auth::logout();
        $newResponse = $response->withStatus(302)->withHeader('Location', '/auth/login');
        return $newResponse;
    }

}