<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2021 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免费声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace app\admin\controller;

use app\admin\model\SystemBase;
use app\admin\model\SystemUser;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\admin\service\AdminService;

/**
 * 系统用户管理
 * Class User
 * @package app\admin\controller
 */
class User extends Controller
{
    /**
     * 超级用户名称
     * @var string
     */
    protected $superName;

    /**
     * 控制器初始化
     */
    protected function initialize()
    {
        // 超级用户名称
        $this->superName = AdminService::instance()->getSuperName();
    }

    /**
     * 系统用户管理
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->type = input('type', 'index');
        $this->_query(SystemUser::class)->layTable(function () {
            $this->title = '系统用户管理';
            $this->bases = (new SystemBase)->items('身份权限');
        }, function (QueryHelper $query) {
            // 加载对应数据列表
            if ($this->type === 'index') {
                $query->where(['is_deleted' => 0, 'status' => 1]);
            } elseif ($this->type = 'recycle') {
                $query->where(['is_deleted' => 0, 'status' => 0]);
            }
            // 数据列表搜索过滤
            $query->equal('status,usertype')->dateBetween('login_at,create_at');
            $query->like('username,nickname,contact_phone#phone,contact_mail#mail');
        });
    }

    /**
     * 数据列表处理
     * @param array $data
     */
    protected function _page_filter(array &$data)
    {
        (new SystemBase)->items('身份权限', $data, 'usertype', 'userinfo');
    }

    /**
     * 添加系统用户
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function add()
    {
        $this->_form(SystemUser::class, 'form');
    }

    /**
     * 编辑系统用户
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit()
    {
        $this->_form(SystemUser::class, 'form');
    }

    /**
     * 修改用户密码
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function pass()
    {
        $this->_applyFormToken();
        if ($this->request->isGet()) {
            $this->verify = false;
            $this->_form(SystemUser::class, 'pass');
        } else {
            $data = $this->_vali([
                'id.require'                  => '用户ID不能为空！',
                'password.require'            => '登录密码不能为空！',
                'repassword.require'          => '重复密码不能为空！',
                'repassword.confirm:password' => '两次输入的密码不一致！',
            ]);
            $user = (new SystemUser)->find($data['id']);
            if (!empty($user) && $user->save(['password' => md5($data['password'])])) {
                sysoplog('系统用户管理', "修改用户[{$data['id']}]密码成功");
                $this->success('密码修改成功，请使用新密码登录！', '');
            } else {
                $this->error('密码修改失败，请稍候再试！');
            }
        }
    }

    /**
     * 表单数据处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function _form_filter(array &$data)
    {
        if ($this->request->isPost()) {
            // 账号权限绑定处理
            $data['authorize'] = arr2str($data['authorize'] ?? []);
            if (isset($data['id']) && $data['id'] > 0) {
                unset($data['username']);
            } else {
                // 检查账号是否重复
                if (empty($data['username'])) {
                    $this->error('登录账号不能为空！');
                }
                $map = ['username' => $data['username'], 'is_deleted' => 0];
                if ((new SystemUser)->where($map)->count() > 0) {
                    $this->error("账号已经存在，请使用其它账号！");
                }
                // 新添加的用户密码与账号相同
                $data['password'] = md5($data['username']);
            }
        } else {
            // 用户身份数据
            $this->bases = (new SystemBase)->items('身份权限');
            // 权限绑定处理
            $data['authorize'] = str2arr($data['authorize'] ?? '');
            // 用户权限管理
            $query = $this->app->db->name('SystemAuth')->where(['status' => 1]);
            $this->authorizes = $query->order('sort desc,id desc')->select()->toArray();
        }
    }

    /**
     * 修改用户状态
     * @auth true
     * @throws \think\db\exception\DbException
     */
    public function state()
    {
        $this->_checkInput();
        $this->_save(SystemUser::class, $this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除系统用户
     * @auth true
     * @throws \think\db\exception\DbException
     */
    public function remove()
    {
        $this->_checkInput();
        $this->_delete(SystemUser::class);
    }

    /**
     * 检查输入变量
     */
    private function _checkInput()
    {
        if (in_array('10000', str2arr(input('id', '')))) {
            $this->error('系统超级账号禁止删除！');
        }
    }
}
