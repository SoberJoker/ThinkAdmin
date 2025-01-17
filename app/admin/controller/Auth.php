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

use app\admin\model\SystemAuth;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\admin\service\AdminService;

/**
 * 系统权限管理
 * Class Auth
 * @package app\admin\controller
 */
class Auth extends Controller
{
    /**
     * 系统权限管理
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->_query(SystemAuth::class)->layTable(function () {
            $this->title = '系统权限管理';
        }, function (QueryHelper $query) {
            $query->dateBetween('create_at')->like('title,desc')->equal('status,utype');
        });
    }

    /**
     * 添加系统权限
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function add()
    {
        $this->_form(SystemAuth::class, 'form');
    }

    /**
     * 编辑系统权限
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit()
    {
        $this->_form(SystemAuth::class, 'form');
    }

    /**
     * 修改权限状态
     * @auth true
     * @throws \think\db\exception\DbException
     */
    public function state()
    {
        $this->_save(SystemAuth::class, $this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除系统权限
     * @auth true
     * @throws \think\db\exception\DbException
     */
    public function remove()
    {
        $this->_delete(SystemAuth::class);
    }

    /**
     * 权限配置节点
     * @auth true
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function apply()
    {
        $map = $this->_vali(['auth.require#id' => '权限ID不能为空！']);
        if (input('action') === 'get') {
            if ($this->app->isDebug()) AdminService::instance()->clearCache();
            $checkeds = $this->app->db->name('SystemAuthNode')->where($map)->column('node');
            $this->success('获取权限节点成功！', AdminService::instance()->getTree($checkeds));
        } elseif (input('action') === 'save') {
            [$post, $data] = [$this->request->post(), []];
            foreach ($post['nodes'] ?? [] as $node) {
                $data[] = ['auth' => $map['auth'], 'node' => $node];
            }
            $this->app->db->name('SystemAuthNode')->where($map)->delete();
            $this->app->db->name('SystemAuthNode')->insertAll($data);
            sysoplog('系统权限管理', "配置系统权限[{$map['auth']}]授权成功");
            $this->success('访问权限修改成功！', 'javascript:history.back()');
        } else {
            $this->_form(SystemAuth::class, 'apply');
        }
    }

    /**
     * 表单后置数据处理
     * @param array $data
     */
    protected function _apply_form_filter(array &$data)
    {
        if ($this->request->isGet()) {
            $this->title = "编辑【{$data['title']}】授权";
        }
    }
}
