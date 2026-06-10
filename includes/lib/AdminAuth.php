<?php
/**
 * 管理员认证与权限管理类
 * 支持多管理员账户、角色权限、bcrypt 密码哈希
**/

namespace lib;

class AdminAuth
{
    /**
     * 迁移旧系统管理员配置到 pre_admin 表
     */
    public static function migrateFromConfig(): bool
    {
        global $DB, $conf;

        // 检查是否已迁移
        $count = $DB->getColumn("SELECT COUNT(*) FROM pre_admin");
        if ($count > 0) {
            return true;
        }

        $username = $conf['admin_user'] ?? '';
        $password = $conf['admin_pwd'] ?? '';
        $paypwd = $conf['admin_paypwd'] ?? '';

        if (!$username || !$password) {
            return false;
        }

        // bcrypt 哈希
        $pwdHash = password_hash($password, PASSWORD_DEFAULT);
        $payPwdHash = password_hash($paypwd, PASSWORD_DEFAULT);
        $totpSecret = !empty($conf['totp_secret']) ? $conf['totp_secret'] : null;

        // 插入管理员记录（role_id=1 对应超级管理员）
        $DB->exec(
            "INSERT INTO pre_admin (username, password, pay_password, role_id, status, totp_secret, last_login_time, login_count)
             VALUES (?, ?, ?, 1, 1, ?, NOW(), 0)",
            [$username, $pwdHash, $payPwdHash, $totpSecret]
        );

        // 标记迁移
        $DB->exec(
            "INSERT INTO pre_config (k, v) VALUES ('admin_migrated', '1')
             ON DUPLICATE KEY UPDATE v = '1'"
        );

        return true;
    }

    /**
     * 管理员登录
     * @param string $username 用户名
     * @param string $password 明文密码
     * @return array ['success'=>bool, 'admin'=>array|null, 'msg'=>string, 'need_totp'=>bool]
     */
    public static function login(string $username, string $password): array
    {
        global $DB, $conf;

        // 检查表是否存在并获取管理员数（一次查询）
        $adminCount = $DB->getColumn("SELECT COUNT(*) FROM pre_admin");
        if ($adminCount === false) {
            // 表不存在，降级到旧系统配置验证
            if ($username === $conf['admin_user'] && $password === $conf['admin_pwd']) {
                $admin = self::buildAdminFromConfig($username);
                return ['success' => true, 'admin' => $admin, 'msg' => '', 'need_totp' => false];
            }
            return ['success' => false, 'admin' => null, 'msg' => '用户名或密码错误', 'need_totp' => false];
        }

        // 表存在但为空，自动迁移旧配置
        if ($adminCount == 0) {
            self::migrateFromConfig();
        }

        // 查询管理员
        $admin = $DB->find('admin', '*', ['username' => $username]);
        if (!$admin) {
            return ['success' => false, 'admin' => null, 'msg' => '用户名或密码错误', 'need_totp' => false];
        }

        // 检查状态
        if (empty($admin['status'])) {
            return ['success' => false, 'admin' => null, 'msg' => '账号已被禁用', 'need_totp' => false];
        }

        // 验证密码（兼容旧明文数据）
        $pwdVerified = self::verifyPassword($password, $admin['password']);
        if (!$pwdVerified) {
            return ['success' => false, 'admin' => null, 'msg' => '用户名或密码错误', 'need_totp' => false];
        }

        // TOTP 二次验证检查（仅对配置了 totp_secret 的管理员）
        $needTotp = !empty($admin['totp_secret']);
        if ($needTotp) {
            return ['success' => true, 'admin' => $admin, 'msg' => '', 'need_totp' => true];
        }

        // 更新登录信息
        self::updateLoginInfo($admin['id']);

        return ['success' => true, 'admin' => $admin, 'msg' => '', 'need_totp' => false];
    }

    /**
     * TOTP 验证后登录
     * @param array $admin 管理员数据（已通过密码验证）
     * @param string $totpCode TOTP 验证码
     * @return array ['success'=>bool, 'admin'=>array|null, 'msg'=>string]
     */
    public static function loginWithTotp(array $admin, string $totpCode): array
    {
        try {
            $totp = \lib\TOTP::create($admin['totp_secret'] ?? '');
            if (!$totp->verify($totpCode)) {
                return ['success' => false, 'admin' => null, 'msg' => '动态口令错误'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'admin' => null, 'msg' => $e->getMessage()];
        }

        self::updateLoginInfo($admin['id']);
        return ['success' => true, 'admin' => $admin, 'msg' => ''];
    }

    /**
     * 检查 Cookie 登录状态
     * @return array|null 验证成功返回管理员数据数组，失败返回 null
     */
    public static function check(): ?array
    {
        global $DB, $conf;

        if (!isset($_COOKIE['admin_token'])) {
            return null;
        }

        $token = authcode($_COOKIE['admin_token'], 'DECODE', defined('SYS_KEY') ? SYS_KEY : '');
        if (!$token) {
            return null;
        }

        $parts = explode("\t", $token);
        if (count($parts) !== 3) {
            return null;
        }

        $key = trim($parts[0]); // 可能是 id 或 username
        $sid = trim($parts[1]);
        $expiretime = intval($parts[2]);

        // 检查过期
        if ($expiretime <= time()) {
            return null;
        }

        $tableCount = $DB->getColumn("SELECT COUNT(*) FROM pre_admin");
        if ($tableCount === false) {
            // 表不存在，降级到旧系统
            return self::checkFromConfig($key, $sid);
        }

        // 优先按 ID 查找，再尝试按用户名
        if (is_numeric($key)) {
            $admin = $DB->find('admin', '*', ['id' => intval($key)]);
        } else {
            $admin = $DB->find('admin', '*', ['username' => $key]);
        }

        if (!$admin) {
            return null;
        }

        // 检查状态
        if (empty($admin['status'])) {
            return null;
        }

        // 重新计算 session_hash（与 login.php 生成 token 时一致）
        $password_hash = '!@#%!s!0';
        $expectedSession = md5($admin['username'] . $admin['password'] . $password_hash);

        if ($expectedSession !== $sid) {
            return null;
        }

        return $admin;
    }

    /**
     * 验证支付密码
     * @param int $adminId 管理员ID
     * @param string $payPassword 明文支付密码
     * @return bool
     */
    public static function verifyPayPassword(int $adminId, string $payPassword): bool
    {
        global $DB, $conf;

        $admin = $DB->find('admin', '*', ['id' => $adminId]);
        if (!$admin) {
            return false;
        }

        // 如果 pay_password 字段为空，降级到旧系统配置
        if (empty($admin['pay_password'])) {
            return ($payPassword === $conf['admin_paypwd']);
        }

        // bcrypt 验证
        if (password_verify($payPassword, $admin['pay_password'])) {
            return true;
        }

        return false;
    }

    /**
     * 检查管理员是否有指定权限
     * @param string $permission 权限代码
     * @return bool
     */
    public static function hasPermission(string $permission, array $admin = []): bool
    {
        global $DB;

        if (empty($admin)) {
            global $adminInfo;
            $admin = $adminInfo ?? null;
        }

        if (!$admin) {
            return false;
        }

        // 获取角色
        $roleId = $admin['role_id'] ?? 1;
        $role = $DB->find('admin_role', '*', ['id' => $roleId]);

        if (!$role) {
            return false;
        }

        $permissions = json_decode($role['permissions'] ?? '[]', true);
        if (!is_array($permissions)) {
            return false;
        }

        // 所有权限
        if (in_array('all', $permissions, true)) {
            return true;
        }

        return in_array($permission, $permissions, true);
    }

    /**
     * 登出
     */
    public static function logout(): void
    {
        setcookie('admin_token', '', time() - 2592000, '/', null, null, true);
    }

    /**
     * 确保 pre_admin 表存在
     */
    private static function ensureAdminTable(): bool
    {
        global $DB;
        try {
            $DB->exec("SELECT 1 FROM pre_admin LIMIT 0");
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 从旧配置构建管理员数据（兼容降级）
     */
    private static function buildAdminFromConfig(string $username): array
    {
        global $conf;
        $password_hash = '!@#%!s!0';
        $session = md5($username . $conf['admin_pwd'] . $password_hash);

        return [
            'id' => 0,
            'username' => $username,
            'password' => $conf['admin_pwd'],
            'pay_password' => $conf['admin_paypwd'] ?? '',
            'role_id' => 1,
            'status' => 1,
            'last_login_ip' => '',
            'last_login_time' => '',
            'login_count' => 0,
            'totp_secret' => $conf['totp_secret'] ?? '',
            '__session' => $session, // 临时用于 token 生成
        ];
    }

    /**
     * 从旧系统配置检查登录（兼容降级）
     */
    private static function checkFromConfig(string $key, string $expectedSid): ?array
    {
        global $conf;

        // 如果 key 是用户名，匹配
        if ($key === $conf['admin_user']) {
            $password_hash = '!@#%!s!0';
            $session = md5($conf['admin_user'] . $conf['admin_pwd'] . $password_hash);
            if ($session === $expectedSid) {
                return self::buildAdminFromConfig($conf['admin_user']);
            }
        }

        return null;
    }

    /**
     * 更新管理员登录信息
     */
    private static function updateLoginInfo(int $adminId): void
    {
        global $DB;
        global $clientip;
        global $date;

        $DB->exec(
            "UPDATE pre_admin SET
             last_login_ip = ?,
             last_login_time = ?,
             login_count = login_count + 1
             WHERE id = ?",
            [$clientip, $date, $adminId]
        );
    }

    /**
     * 验证密码（兼容 bcrypt 和旧明文）
     */
    private static function verifyPassword(string $password, string $storedHash): bool
    {
        if (empty($storedHash)) {
            return false;
        }

        // bcrypt 验证
        if (substr($storedHash, 0, 2) === '$2' && password_verify($password, $storedHash)) {
            return true;
        }

        // 明文兼容：如果存储的不是 bcrypt 格式，则直接比较
        if (substr($storedHash, 0, 2) !== '$2') {
            return ($password === $storedHash);
        }

        return false;
    }

    /**
     * 修改管理员密码
     */
    public static function changePassword(int $adminId, string $newPassword): bool
    {
        global $DB;
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        return (bool)$DB->exec("UPDATE pre_admin SET password = ? WHERE id = ?", [$hash, $adminId]);
    }

    /**
     * 修改管理员支付密码
     */
    public static function changePayPassword(int $adminId, string $newPayPassword): bool
    {
        global $DB;
        $hash = password_hash($newPayPassword, PASSWORD_DEFAULT);
        return (bool)$DB->exec("UPDATE pre_admin SET pay_password = ? WHERE id = ?", [$hash, $adminId]);
    }

    /**
     * 创建管理员
     */
    public static function createAdmin(string $username, string $password, string $payPassword, int $roleId = 1, int $status = 1): bool
    {
        global $DB;

        // 检查用户名是否存在
        $exists = $DB->getColumn("SELECT COUNT(*) FROM pre_admin WHERE username = ?", [$username]);
        if ($exists > 0) {
            return false;
        }

        $pwdHash = password_hash($password, PASSWORD_DEFAULT);
        $payPwdHash = password_hash($payPassword, PASSWORD_DEFAULT);

        return (bool)$DB->exec(
            "INSERT INTO pre_admin (username, password, pay_password, role_id, status)
             VALUES (?, ?, ?, ?, ?)",
            [$username, $pwdHash, $payPwdHash, $roleId, $status]
        );
    }

    /**
     * 更新管理员
     */
    public static function updateAdmin(int $adminId, array $data): bool
    {
        global $DB;
        $set = [];
        $params = [];

        if (isset($data['role_id'])) {
            $set[] = 'role_id = ?';
            $params[] = $data['role_id'];
        }
        if (isset($data['status'])) {
            $set[] = 'status = ?';
            $params[] = $data['status'];
        }
        if (!empty($data['password'])) {
            $set[] = 'password = ?';
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        if (!empty($data['pay_password'])) {
            $set[] = 'pay_password = ?';
            $params[] = password_hash($data['pay_password'], PASSWORD_DEFAULT);
        }
        if (isset($data['totp_secret'])) {
            $set[] = 'totp_secret = ?';
            $params[] = $data['totp_secret'];
        }

        if (empty($set)) {
            return true;
        }

        $params[] = $adminId;
        return (bool)$DB->exec("UPDATE pre_admin SET " . implode(', ', $set) . " WHERE id = ?", $params);
    }

    /**
     * 删除管理员
     */
    public static function deleteAdmin(int $adminId): bool
    {
        global $DB;

        // 系统内置角色不可删除
        $admin = $DB->find('admin', '*', ['id' => $adminId]);
        if (!$admin) {
            return false;
        }

        // 确保删除后还有至少 1 个管理员
        $total = $DB->getColumn("SELECT COUNT(*) FROM pre_admin WHERE id != ?", [$adminId]);
        if ($total <= 0) {
            return false;
        }

        // 不允许删除最后一个超级管理员
        $role = $DB->find('admin_role', '*', ['id' => $admin['role_id']]);
        if ($role && $role['code'] === 'superadmin') {
            $otherSuperadmins = $DB->getColumn(
                "SELECT COUNT(*) FROM pre_admin a JOIN pre_admin_role r ON a.role_id = r.id WHERE r.code = 'superadmin' AND a.id != ?",
                [$adminId]
            );
            if ($otherSuperadmins <= 0) {
                return false;
            }
        }

        return (bool)$DB->exec("DELETE FROM pre_admin WHERE id = ?", [$adminId]);
    }
}
