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

namespace app\admin\model;

use think\admin\Model;

/**
 * 系统用户数据模型
 * Class SystemUser
 * @package app\admin\model
 */
class SystemUser extends Model
{
    /**
     * 日志名称
     * @var string
     */
    protected $oplogName = '系统用户';

    /**
     * 日志类型
     * @var string
     */
    protected $oplogType = '系统用户管理';

    /**
     * 获取用户数据
     * @param array $map 数据查询规则
     * @param array $data 用户数据集合
     * @param string $field 原外连字段
     * @param string $target 关联目标字段
     * @param string $fields 关联数据字段
     * @return array
     */
    public function items(array $map, array &$data = [], string $field = 'uuid', string $target = 'user_info', string $fields = 'username,nickname,headimg,status,is_deleted'): array
    {
        $query = $this->where($map)->order('sort desc,id desc');
        if (count($data) > 0) {
            $users = $query->whereIn('id', array_unique(array_column($data, $field)))->column($fields, 'id');
            foreach ($data as &$vo) $vo[$target] = $users[$vo[$field]] ?? [];
            return $users;
        } else {
            return $query->column($fields, 'id');
        }
    }

    /**
     * 格式化登录时间
     * @param string $value
     * @return string
     */
    public function getLoginAtAttr(string $value): string
    {
        return format_datetime($value);
    }

    /**
     * 格式化创建时间
     * @param string $value
     * @return string
     */
    public function getCreateAtAttr(string $value): string
    {
        return format_datetime($value);
    }
}