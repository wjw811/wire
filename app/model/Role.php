<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Role.php
* @touch date Sun 05 Jul 2020 00:00:00 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\model;

class Role extends \Next\Core\Model {
    protected $table = 's_role';
    private $trp = 's_role_privilege';
    private $tm = 's_module';
    private $tmp = 's_module_privilege';
    private $tp = 's_privilege';
    private $tl = 's_log';

/*{{{ delFull */
    public function delFull($code) {
        return $this->db->action(function($db) use($code) {
            if (!$db->delete($this->table, ['code' => $code])->rowCount()) {
                return false;
            }
            $db->delete($this->trp, ['role_code' => $code]);

            return true;
        });
    }
/*}}}*/

/*{{{ privilege */
    public function privilege($role) {
        $out = [];

        $mp = [];
        // all module with privilege
        if ($tmp = $this->db->select(
            $this->tmp.'(MP)',
            ['[><]'.$this->tp.'(P)' => ['MP.privilege_code' => 'code']],
            ['MP.id[Int]', 'MP.module_code(module)', 'P.code', 'P.name']
        )) {
            foreach ($tmp as $row) {
                if (!isset($mp[$row['module']])) {
                    $mp[$row['module']] = [];
                }
                $mp[$row['module']][] = [
                    'id' => $row['id'],
                    'code' => $row['code'],
                    'name' => $row['name'],
                ];
            }
        }

        // owner privilege
        $owner = [];
        if ($tmp = $this->db->select(
            $this->table.'(R)',
            ['[><]'.$this->trp.'(RP)' => ['R.code' => 'role_code']],
            'RP.module_privilege_id(id)',
            ['R.code' => $role]
        )) {
            $owner = array_map(function($row) {
                return intval($row);
            }, $tmp);
        }

        // construct out data
        if ($tmp = $this->db->select($this->tm, ['code', 'name', 'url'])) {
            foreach ($tmp as $row) {
                $privilege = isset($mp[$row['code']])? $mp[$row['code']]: [];

                // all privilege id
                $all = [];
                if ($privilege) {
                    $all = array_map(function ($row) {
                        return $row['id'];
                    }, $privilege);
                }

                // owner privilege
                $own = [];
                if ($owner) {
                    $own = array_filter($owner, function($row) use ($all) {
                        return in_array($row, $all);
                    });
                }

                $out[] = [
                    'code'      => $row['code'],
                    'name'      => $row['name'],
                    'url'       => $row['url'],
                    'privilege' => $privilege,
                    'have'      => array_values($own),
                ];
            }
        }

        return $out;
    }
/*}}}*/
/*{{{ setPrivilege */
    public function setPrivilege($args) {
        return $this->db->action(function($db) use($args) {
            // exists privilege
            $pids = $db->select($this->trp, 'module_privilege_id(pid)', ['role_code' => $args['role']]);

            $insert = array_diff($args['data'], $pids);
            foreach ($insert as $pid) {
                $a = [
                    'role_code' => $args['role'],
                    'module_privilege_id' => $pid,
                ];

                if (!$db->insert($this->trp, $a)->rowCount()) {
                    $this->app->log->error(sprintf('authrole.setprivilege insert error, role code: %s pid: %s', $args['role'], $pid));
                    return false;
                }
            }
            // need delete privilege
            $delete = array_diff($pids, $args['data']);
            if ($delete) {
                if (!$db->delete($this->trp, ['role_code' => $args['role'], 'module_privilege_id' => $delete])->rowCount()) {
                    $this->app->log->error(sprintf('authrole.setprivilege delete error, role code: %s pids: %s', $args['role'], implode(',', $delete)));
                    return false;
                }
            }

            return true;
        });
    }
/*}}}*/

}
