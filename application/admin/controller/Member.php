<?php
// +----------------------------------------------------------------------
// | ehcms [ Efficient Handy Content Management System ]
// +----------------------------------------------------------------------
// | Copyright (c) http://ehcms.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: zky < zky@ehcms.com >
// +----------------------------------------------------------------------

namespace app\admin\controller;

class Member extends Init{
    use \eh\traits\Password;

	public function __construct(){
		parent::__construct();
	}
	
	public function index(){
		$member = db('member')->field('id,username,is_admin,create_time')->order('id', 'desc')->paginate(20);
		
		$this->assign('member', $member);
		return $this->fetch();
	}

	public function create(){
	    $adminGroup = $this->getAdminGroup();
	    $this->assign('adminGroup', $adminGroup);
        $this->assign('actionSign', 'editor');
	    return $this->fetch('editor');
    }

    public function save(){
        $validate = validate('Member');
        $post = input();

        if (!empty($post['id'])){
            if (!empty($post['password']) && $validate->check($post) == TRUE){
                $password = $this->createPassword($post['password']);
                $data = [
                    'username' => $post['username'],
                    'password' => $password,
                    'phone' => $post['phone'],
                    'is_admin' => !empty($post['is_admin']) ? 1 : 0,
                    'admin_group' => !empty($post['is_admin']) ? $post['admin_group'] : ''
                ];
                if (db('member')->where('id', $post['id'])->update($data) == 1){
                    $this->success('用户更新成功');
                }

            }elseif (empty($post['password']) && $validate->scene('edit')->check($post) == TRUE){
                $data = [
                    'username' => $post['username'],
                    'phone' => $post['phone'],
                    'is_admin' => !empty($post['is_admin']) ? 1 : 0,
                    'admin_group' => !empty($post['is_admin']) ? $post['admin_group'] : ''
                ];
                if (db('member')->where('id', $post['id'])->update($data) == 1){
                    $this->success('用户更新成功');
                }else{
                    $this->success('用户更新成功，数据未修改。');
                }
            }else{
                $this->error($validate->getError());
            }
        }else{
            if ($validate->check($post) == TRUE){
                $password = $this->createPassword($post['password']);
                $data = [
                    'username' => $post['username'],
                    'password' => $password,
                    'phone' => $post['phone'],
                    'is_admin' => !empty($post['is_admin']) ? 1 : 0,
                    'admin_group' => !empty($post['is_admin']) ? $post['admin_group'] : '',
                    'create_time' => THINK_START_TIME
                ];
                if (db('member')->insert($data) == 1){
                    $this->success('用户添加成功');
                }else{
                    $this->error('用户添加失败');
                }
            }else{
                $this->error($validate->getError());
            }
        }
    }

    public function register(){
        if (request()->isPost()){
            $this->save();
        }
    }

    public function edit($id){
        $member = db('member')->where('id', $id)->find();
        $adminGroup = $this->getAdminGroup();
        $this->assign('adminGroup', $adminGroup);
        $this->assign('actionSign', 'editor');
        $this->assign('member', $member);
        $this->assign('id', $id);
        return $this->fetch('editor');
    }
	
	/**
	 * 获取用户列表
	 * @param int $page
	 * @return json
	 */
	public function getMemberList($page = 1, $key = ''){
		$memberModel = new \app\member\model\Member();
		
		$request = $this->asyncPostCheck();
		if ($request !== TRUE){
			return $request;
		}
		
		$page = is_numeric($page) ? $page : 1;

		$where = [];
        if (!empty($key)){
            $where['username'] = ['like', '%' . $key . '%'];
        }

		$countPage = $memberModel->field('id,username,is_admin,create_time')->where($where)->order('id', 'desc')->count() / 10;
 		$member = $memberModel->field('id,username,is_admin,create_time')->where($where)->order('id', 'desc')->page($page, 10)->select();

		if ($member){
			return $this->successResult(['member'=>$member, 'count_page'=>ceil($countPage)]);
		}else {
			return $this->errorResult('E-020201');
		}
	}

	private function getAdminGroup(){
        return db('admin_group')->field(['id', 'name'])->select();
    }
}