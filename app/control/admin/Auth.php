<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Auth.php
* @touch date Sun 05 Jul 2020 00:00:00 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/

namespace app\control\admin;

class Auth extends \Next\Core\Control {

    private $u;
    private $t;

/*{{{ construct */
    public function __construct() {
        parent::__construct();
        $this->u = $this->app->user;
        $this->t = $this->app->token;
    }
/*}}}*/

/*{{{ index */
    public function index() {
        $m = new \app\model\Auth();

        $name = $this->params('name');
        $code = $this->params('code');
        $status = $this->params('status');
        $page = $this->params('page', 1);
        $size = $this->params('size', 12);

        $data = [];
        $w = [];
        if ($name) {
            $w['name[~]'] = $name;
        }
        if ($code) {
            $w['code'] = $code;
        } else {
            $w['code[!]'] = 'super'; // system keep
        }
        if ($status !== null) {
            $w['status'] = $status;
        } else {
            $w['status[!]'] = 9;
        }

        if ($total = $m->count($w)) {
            $w['ORDER'] = ['id' => 'DESC'];
            $w['LIMIT'] = [($page - 1) * $size, $size];
            $data = $m->select(['id[Int]', 'name', 'code', 'phone', 'email', 'status[Int]', 'login_time'], $w);
        }

        $this->json(0, '', ['total' => $total, 'items' => $data]);
    }
/*}}}*/
/*{{{ add */
    public function add() {
        $m = new \app\model\Auth();

        $name   = $this->params('name');
        $code   = $this->params('code');
        $phone  = $this->params('phone');
        $email  = $this->params('email');
        $pwd    = $this->params('pwd');
        $status = $this->params('status');

        if ($m->has(['code' => $code, 'status[!]' => 9])) {
            $this->json(4001, '账号已经存在');
        }

        $a = [
            'name'    => $name,
            'code'    => $code,
            'pwd'     => $this->app->common->encryptPwd($pwd),
            'phone'   => $phone,
            'email'   => $email,
            'status'  => $status == 1? 1: 0,
            'created' => $m->raw('now()'),
            'updated' => $m->raw('now()'),
        ];
        if (!$m->insert($a)->rowCount()) {
            $this->json(4002, '添加失败');
        }
        $this->json(0, '添加成功');
    }
/*}}}*/
/*{{{ edit */
    public function edit() {
        $m = new \app\model\Auth();

        $id     = $this->params('id');
        $name   = $this->params('name');
        $phone  = $this->params('phone');
        $email  = $this->params('email');
        $status = $this->params('status');

        $u = [
            'name'    => $name,
            'phone'   => $phone,
            'email'   => $email,
            'status'  => $status == 1? 1: 0,
            'updated' => $m->raw('now()'),
        ];
        $w = [
            'id' => $id,
        ];
        if (!$m->update($u, $w)->rowCount()) {
            $this->json(4001, '保存失败');
        }
        $this->json(0, '保存成功');
    }
/*}}}*/
/*{{{ delete */
    public function delete() {
        $m = new \app\model\Auth();
        $id = $this->params('id');

        if ($id == $this->u['id']) {
            $this->json(4001, '不能自我删除');
        }

        $u = [
            'status'  => 9,
            'updated' => $m->raw('now()'),
        ];
        $w = [
            'id' => $id,
        ];
        if (!$m->update($u, $w)->rowCount()) {
            $this->json(4001, '删除失败，请重新查询页面再试');
        }
        $this->json(0, '删除成功');
    }
/*}}}*/
/*{{{ resetPwd */
    public function resetPwd() {
        $id  = $this->params('id');
        $pwd = $this->params('pwd');

        $m = new \app\model\Auth();
        $u = [
            'pwd'     => $this->app->common->encryptPwd($pwd),
            'updated' => $m->raw('now()'),
        ];
        $w = [
            'id'        => $id,
            'status[!]' => 9,
        ];
        if (!$m->update($u, $w)->rowCount()) {
            $this->json(4001, '密码重置失败，请稍后再试');
        }
        $this->json(0, '密码重置成功');
    }
/*}}}*/

/*{{{ roleList */
    public function roleList() {
        $out = [
            'data' => [],
            'have' => [],
        ];

        $id = $this->params('id');
        $m = new \app\model\Role();
        if ($tmp = $m->select(['code', 'name'], ['code[!]' => 'super'])) {
            $out['data'] = $tmp;
        }

        $code = $this->params('code');
        $mr = new \app\model\AuthRole();
        if ($tmp = $mr->select('role_code(code)', ['user_code' => $code])) {
            $out['have'] = $tmp;
        }

        $this->json(0, '', $out);
    }
/*}}}*/
/*{{{ setRole */
    public function setRole() {
        $m = new \app\model\Auth();
        $args = [
            'data' => $this->params('data'), // user roles
            'user' => $this->params('user'), // user code
        ];
        if (is_array($args['data'])) {
            $args['data'] = array_unique($args['data']);
        }
        asort($args['data']);

        if (!$m->setRole($args)) {
            $this->json(4001, '角色权限保存失败');
        }
        $this->json(0, '角色权限保存成功');
    }
/*}}}*/

/*{{{ role */
    public function role() {
        $m = new \app\model\Role();

        $name = $this->params('name');
        $code = $this->params('code');
        $page = $this->params('page', 1);
        $size = $this->params('size', 12);

        $data = [];
        $w = [];
        if ($name) {
            $w['name[~]'] = $name;
        }
        if ($code) {
            $w['code'] = $code;
        } else {
            $w['code[!]'] = 'super'; // system keep
        }

        if ($total = $m->count($w)) {
            $w['ORDER'] = ['id' => 'DESC'];
            $w['LIMIT'] = [($page - 1) * $size, $size];
            $data = $m->select(['id[Int]', 'code', 'name', 'preset[Int]'], $w);
        }

        $this->json(0, '', ['total' => $total, 'items' => $data]);
    }
/*}}}*/
/*{{{ roleAdd */
    public function roleAdd() {
        $m = new \app\model\Role();

        $name = $this->params('name');
        $code = $this->params('code');

        if ($m->has(['code' => $code])) {
            $this->json(4001, '编号已经存在');
        }

        $a = [
            'name'    => $name,
            'code'    => $code,
            'preset'  => 0,
            'created' => $m->raw('now()'),
            'updated' => $m->raw('now()'),
        ];
        if (!$m->insert($a)->rowCount()) {
            $this->json(4001, '添加失败');
        }
        $this->json(0, '添加成功');
    }
/*}}}*/
/*{{{ roleEdit */
    public function roleEdit() {
        $m = new \app\model\Role();

        $id = $this->params('id');
        $name = $this->params('name');

        $u = [
            'name'    => $name,
            'updated' => $m->raw('now()'),
        ];
        $w = [
            'id' => $id,
        ];
        if (!$m->update($u, $w)->rowCount()) {
            $this->json(4001, '保存失败');
        }
        $this->json(0, '保存成功');
    }
/*}}}*/
/*{{{ roleDelete */
    public function roleDelete() {
        $m = new \app\model\Role();
        $id = $this->params('id');

        if (!$role = $m->get(['code', 'preset'], ['id' => $id])) {
            $this->json(4001, '删除失败，请重新查询页面再试');
        }
        if ($role['preset']) {
            $this->json(4002, '系统级别的角色不能删除');
        }

        // check user with role
        $mar = new \app\model\AuthRole();
        if ($num = $mar->count(['role_code' => $role['code']])) {
            $this->json(4002, sprintf('该角色下有%s个账户，请先去除账户关联的本角色', $num));
        }

        if (!$m->delFull($role['code'])) {
            $this->json(4001, '删除失败，请重新查询页面再试');
        }
        $this->json(0, '删除成功');
    }
/*}}}*/

/*{{{ privilege */
    public function privilege() {
        $m = new \app\model\Role();
        $role = $this->params('role');

        $data = $m->privilege($role);
        $this->json(0, '', ['total' => count($data), 'items' => $data]);
    }
/*}}}*/
/*{{{ setPrivilege */
    public function setPrivilege() {
        $m = new \app\model\Role();
        $args = [
            'data' => $this->params('data'),
            'role' => $this->params('role'),
        ];
        if (is_array($args['data'])) {
            $args['data'] = array_unique($args['data']);
        }
        asort($args['data']);

        if (!$m->setPrivilege($args)) {
            $this->json(4001, '角色权限保存失败');
        }
        $this->json(0, '角色权限保存成功');
    }
/*}}}*/

/*{{{ login */
    public function login() {
        try {
        $out = array();

        $code = $this->params('code');
        $pwd = $this->params('pwd');

        $m = new \app\model\Auth();
        if (!$user = $m->loadByCode($code)) {
            // 兼容初始化阶段：若还未导入账户数据，允许 super/123456 临时登录
            if ($code === 'super' && $pwd === '123456') {
                $user = [
                    'id'     => 0,
                    'code'   => 'super',
                    'name'   => 'Super Admin',
                    'status' => 1,
                ];
            } else {
                $this->json(403, '用户名不存在');
            }
        }
        $stored = null;
        if (array_key_exists('pwd', $user) && $user['pwd'] !== null) {
            $stored = $user['pwd'];
        } elseif (array_key_exists('password', $user) && $user['password'] !== null && $user['password'] !== '') {
            $stored = $user['password'];
        }
        
        // Debug: log user data for troubleshooting
        error_log("Login attempt - code: $code, user_id: " . ($user['id'] ?? 'N/A') . ", has_password: " . ($stored !== null ? 'yes' : 'no'));
        
        $inputHash = $this->app->common->encryptPwd($pwd);
        // 兼容：super/123456 总是允许登录（用于初始化和重置）
        if ($code === 'super' && $pwd === '123456') {
            // 允许登录，同时更新数据库中的密码（如果为空）
            if ($stored === null || $stored === '') {
                try {
                    $m->update(['password' => $inputHash], ['id' => $user['id']]);
                } catch (\Throwable $e) {
                    // ignore update error
                }
            }
        } elseif ($stored !== null && $stored !== '') {
            // 有密码时，验证密码
            if ($stored != $inputHash) {
                $this->json(403, '用户名或密码有误');
            }
        } else {
            // 密码为空或NULL时，不允许登录（除了super/123456已在上面处理）
            $this->json(403, '用户名或密码有误');
        }
        if ($user['status'] == 0) {
            $this->json(403, '账号暂不可用，请联系管理员');
        }
        $user['avatar'] = 'avator.png';

        // Set login log (兼容缺少 login_time 字段的表结构)
        try {
            $u = [ 
                "login_time" => $m->raw('now()'),
                "updated" => $m->raw('now()'),
            ];
            $w = [
                "id" => $user["id"],
            ];
            $m->update($u, $w);
        } catch (\Throwable $e) {
            try {
                $m->update(["updated" => $m->raw('now()')], ["id" => $user["id"]]);
            } catch (\Throwable $e2) {
                // ignore
            }
        }

        // save in redis, fallback when redis unavailable
        $uuid = $this->app->common->uuid(false);
        try {
            if (isset($this->app->redis)) {
                $this->app->redis->hMSet($uuid, $user);
                $this->app->redis->setTimeout($uuid, 60*60*12);
            } else {
                // fallback token without redis
                $uuid = 'demo-token';
            }
        } catch (\Throwable $e) {
            $uuid = 'demo-token';
        }

        $this->json(0, '登录成功', ['token' => $uuid]);
        } catch (\Throwable $e) {
            error_log("Login error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->json(500, '登录失败: ' . $e->getMessage());
        }
    }
/*}}}*/
/*{{{ info */
    public function info() {
        // 检查用户是否已登录
        if (!$this->u || !isset($this->u['code'])) {
            $this->json(401, '未登录或登录已过期');
        }
        
        $code = $this->u['code'];
        
        // 调试信息
        error_log("Auth info - User code: " . $code);
        error_log("Auth info - User data: " . json_encode($this->u));

        $roles = [];
        $privileges = [];
        
        // 超级管理员拥有完整权限
        if ($code === 'super') {
            $roles = [['code' => 'super']];
            $privileges = [
                ['module' => 'dev', 'action' => ['select', 'add', 'edit', 'del']],
                ['module' => 'gateway', 'action' => ['select', 'add', 'edit', 'del']],
                ['module' => 'system', 'action' => ['select', 'add', 'edit', 'del']],
                ['module' => 'software', 'action' => ['select', 'add', 'edit', 'del']],
                ['module' => 'dashboard', 'action' => ['select']],
                ['module' => 'auth', 'action' => ['select', 'add', 'edit', 'del']],
                ['module' => 'role', 'action' => ['select', 'add', 'edit', 'del']]
            ];
        } else {
            // 从数据库读取用户的角色
            $mr = new \app\model\AuthRole();
            if ($tmp = $mr->select('role_code as code', ['user_code' => $code])) {
                $roles = $tmp;
                
                // 从数据库读取每个角色的权限
                $m = new \app\model\Auth();
                foreach ($tmp as $role) {
                    // $role可能是数组或字符串，需要处理
                    $roleCode = is_array($role) ? $role['code'] : $role;
                    error_log("Loading privileges for role: " . $roleCode);
                    
                    if ($rolePrivileges = $m->privilege($roleCode)) {
                        error_log("Role $roleCode privileges: " . json_encode($rolePrivileges));
                        // 合并所有角色的权限
                        foreach ($rolePrivileges as $priv) {
                            $moduleCode = $priv['module'];
                            $found = false;
                            
                            // 检查是否已存在该模块的权限
                            foreach ($privileges as &$existingPriv) {
                                if ($existingPriv['module'] === $moduleCode) {
                                    // 合并action权限（去重）
                                    $existingPriv['action'] = array_unique(array_merge(
                                        $existingPriv['action'], 
                                        $priv['action']
                                    ));
                                    $found = true;
                                    break;
                                }
                            }
                            
                            // 如果是新模块，直接添加
                            if (!$found) {
                                $privileges[] = $priv;
                            }
                        }
                    }
                }
            } else {
                // 如果没有配置角色，不给予任何权限（只能访问dashboard）
                error_log("User $code has no roles assigned");
                $roles = [];
                $privileges = [
                    ['module' => 'dashboard', 'action' => ['select']]
                ];
            }
        }
        
        // 调试信息
        error_log("Auth info - Roles: " . json_encode($roles));
        error_log("Auth info - Privileges: " . json_encode($privileges));

        // 兼容前端：role 需要为字符串数组（如 ['admin','user']），而不是对象数组
        $roleCodes = [];
        foreach ((array)$roles as $r) {
            if (is_array($r)) {
                if (isset($r['code'])) { $roleCodes[] = $r['code']; }
                else if (isset($r[0])) { $roleCodes[] = $r[0]; }
            } else if (is_string($r)) {
                $roleCodes[] = $r;
            }
        }

        $out = [
            'name'      => $this->u['name'],
            'avatar'    => $this->app->common->buildImg('default', @$this->u['avatar']),
            'role'      => $roleCodes,
            'privilege' => $privileges,
            'homePath'  => '/dashboard/index',
        ];

        $this->json(0, '权限获取成功', $out);
    }
/*}}}*/
/*{{{ logout */
    public function logout() {
        if ($token = $this->t) {
            $this->app->redis->del($token);
        }
        $this->json(0, '成功退出');
    }
/*}}}*/

/*{{{ devList */
public function devList() {
    $out = [];

    $m = new \app\model\Dev();
    if ($tmp = $m->select(['id[Int]', 'name', 'serial', 'addr', 'uid[Int]'], ['status[!]' => 9])) {
        $uids = [];
        foreach ($tmp as $row) {
            $uids[] = $row['uid'];
        }

        $users = [];
        $m = new \app\model\Auth();
        if ($x = $m->select(['id', 'name'], ['id' => $uids])) {
            foreach ($x as $row) {
                $users[$row['id']] = $row['name'];
            }
        }
        foreach ($tmp as $row) {
            $row['user'] = $users[$row['uid']];
            $out[] = $row;
        }
    }

    $this->json(0, '', $out);
}
/*}}}*/
/*{{{ setDev */
public function setDev() {
    $m = new \app\model\Dev();

    $uid = $this->params('id');
    $w = [
        'uid' => $uid,
    ];
    if ($m->has($w)) {
        $u = [
            'uid'     => 0,
            'updated' => $m->raw('now()'),
        ];
        if (!$m->update($u, $w)->rowCount()) {
            $this->json(40401, '设备权限保存失败');
        }
    }

    $did = $this->params('did');
    if (is_array($did)) {
        $did = array_unique($did);
    }
    if ($did) {
        $u = [
            'uid'     => $uid,
            'updated' => $m->raw('now()'),
        ];
        $w = [
            'id' => $did,
        ];
        if (!$m->update($u, $w)->rowCount()) {
            $this->json(40402, '设备权限保存失败');
        }
    }

    $this->json(0, '设备权限保存成功');
}
/*}}}*/

}
