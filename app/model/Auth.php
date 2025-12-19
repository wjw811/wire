<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Auth.php
* @touch date Sun 05 Jul 2020 00:00:00 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\model;

class Auth extends \Next\Core\Model {
    protected $table = 's_user';
    private $tr = 's_role';
    private $trp = 's_role_privilege';
    private $tur = 's_user_role';
    private $tm = 's_module';
    private $tmp = 's_module_privilege';
    private $tp = 's_privilege';
    private $tl = 's_log';

/*{{{ loadByCode */
    public function loadByCode($code) {
        return $this->get('*', ['code' => $code, 'status[!]' => 9]);
    }
/*}}}*/

/*{{{ role */
    public function role($code) {
        $q = 'SELECT 
            [u].code user_code, [u].name user_name,
            [r].code role_code, [u].name role_name,
            [m].code moduel_code, [m].name moduel_name,
            [p].code privilege_code, [p].name privilege_name
            FROM [u], [ur], [r], [rp], [mp], [m], [p]
            WHERE [ur].user_code=[u].code AND [ur].role_code=[r].code
            AND [rp].role_code=[r].code AND [rp].module_privilege_id=[mp].id
            AND [mp].module_code=[m].code AND [mp].privilege_code=[p].code
            AND [u].code=:code
';
        $patten = [
            '[u]'  => $this->table,
            '[r]'  => $this->tr,
            '[rp]' => $this->trp,
            '[ur]' => $this->tur,
            '[m]'  => $this->tm,
            '[mp]' => $this->tmp,
            '[p]'  => $this->tp,
        ];
        $q = str_replace(array_keys($patten), array_values($patten), $q);
        $data = $this->db->query($q, [':code' => $code])->fetchAll(\PDO::FETCH_ASSOC);

        return $data;
    }
/*}}}*/
/*{{{ privilege */
    public function privilege($code) {
        $data = [];
        try {
            if ($tmp = $this->db->select(
                $this->tmp.'(MP)', [
                    '[><]'.$this->tm.'(M)' => ['MP.module_code' => 'code'], 
                    '[><]'.$this->tp.'(P)' => ['MP.privilege_code' => 'code'],
                    '[><]'.$this->trp.'(RP)' => ['MP.id' => 'module_privilege_id'],
                ],
                ['id' => $this->raw('DISTINCT(MP.id)'), 'M.code(mcode)', 'M.url', 'P.code(pcode)'],
                ['RP.role_code' => $code, 'ORDER' => ['mcode', 'pcode']]
            )) {
                foreach ($tmp as $row) {
                    $module = $row['mcode'];
                    if (!isset($data[$module])) {
                        $data[$module] = [
                            'module' => $module,
                            'action' => [],
                        ];
                    }
                    $data[$module]['action'][] = $row['pcode'];
                }
            }
        } catch (\Throwable $e) {
            // tables might be missing during initial setup; return empty to allow UI to加载
            return [];
        }

        return array_values($data);
    }
/*}}}*/

/*{{{ setRole */
    public function setRole($args) {
        return $this->db->action(function($db) use($args) {
            // exists role
            $roles = $db->select($this->tur, 'role_code', ['user_code' => $args['user']]);

            $insert = array_diff($args['data'], $roles);
            foreach ($insert as $role) {
                $a = [
                    'user_code' => $args['user'],
                    'role_code' => $role,
                ];

                if (!$db->insert($this->tur, $a)->rowCount()) {
                    $this->app->log->error(sprintf('auth.setrole insert error, user code: %s roles: %s', $args['user'], $role));
                    return false;
                }
            }
            // need delete role
            $delete = array_diff($roles, $args['data']);
            if ($delete) {
                if (!$db->delete($this->tur, ['user_code' => $args['user'], 'role_code' => $delete])->rowCount()) {
                    $this->app->log->error(sprintf('auth.setrole delete error, user code: %s roles: %s', $args['user'], implode(',', $delete)));
                    return false;
                }
            }

            return true;
        });
    }
/*}}}*/

}
