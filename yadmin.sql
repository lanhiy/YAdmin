/*
 Navicat MySQL Dump SQL

 Source Server         : 本地开发
 Source Server Type    : MySQL
 Source Server Version : 80405 (8.4.5)
 Source Host           : 127.0.0.1:3306
 Source Schema         : yadmin

 Target Server Type    : MySQL
 Target Server Version : 80405 (8.4.5)
 File Encoding         : 65001

 Date: 17/11/2025 10:37:27
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for casbin_rule
-- ----------------------------
DROP TABLE IF EXISTS `casbin_rule`;
CREATE TABLE `casbin_rule`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ptype` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `v0` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `v1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `v2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `v3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `v4` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `v5` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 30 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of casbin_rule
-- ----------------------------
INSERT INTO `casbin_rule` VALUES (1, 'p', 'superadmin', '[\"system:menu:list\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (2, 'p', 'superadmin', '[\"system:menu:routes\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (3, 'p', 'superadmin', '[\"system:menu:buttons\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (4, 'p', 'superadmin', '[\"system:menu:show\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (5, 'p', 'superadmin', '[\"system:menu:add\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (6, 'p', 'superadmin', '[\"system:menu:edit\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (7, 'p', 'superadmin', '[\"system:menu:delete\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (8, 'p', 'superadmin', '[\"system:menu:status\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (9, 'p', 'superadmin', '[\"system:role:list\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (10, 'p', 'superadmin', '[\"system:role:all\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (11, 'p', 'superadmin', '[\"system:role:show\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (12, 'p', 'superadmin', '[\"system:role:add\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (13, 'p', 'superadmin', '[\"system:role:edit\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (14, 'p', 'superadmin', '[\"system:role:delete\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (15, 'p', 'superadmin', '[\"system:role:status\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (16, 'p', 'superadmin', '[\"system:admin:list\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (17, 'p', 'superadmin', '[\"system:admin:show\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (18, 'p', 'superadmin', '[\"system:admin:add\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (19, 'p', 'superadmin', '[\"system:admin:edit\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (20, 'p', 'superadmin', '[\"system:admin:delete\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (21, 'p', 'superadmin', '[\"system:admin:status\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (22, 'p', 'superadmin', '[\"profile:show\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (23, 'p', 'superadmin', '[\"profile:update\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (24, 'p', 'superadmin', '[\"profile:changePassword\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (25, 'p', 'superadmin', '[\"profile:uploadAvatar\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (26, 'p', 'admin', '[\"profile:show\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (27, 'p', 'admin', '[\"profile:update\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (28, 'p', 'admin', '[\"profile:changePassword\"]', '*', NULL, NULL, NULL);
INSERT INTO `casbin_rule` VALUES (29, 'p', 'admin', '[\"profile:uploadAvatar\"]', '*', NULL, NULL, NULL);

-- ----------------------------
-- Table structure for system_admin
-- ----------------------------
DROP TABLE IF EXISTS `system_admin`;
CREATE TABLE `system_admin`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '账号',
  `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `gender` tinyint NOT NULL DEFAULT 0 COMMENT '性别：0-未知，1-男，2-女',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '头像',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `last_login_at` datetime NOT NULL DEFAULT '1000-01-01 00:00:00' COMMENT '最后登录时间',
  `last_login_ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_status`(`status` ASC) USING BTREE,
  INDEX `idx_username`(`username` ASC) USING BTREE,
  INDEX `idx_mobile`(`mobile` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of system_admin
-- ----------------------------
INSERT INTO `system_admin` VALUES (1, 'admin', '18811012138', 'lanhiy@163.com', '$2y$10$5S6jKTpejKNnSlv2p2fmkuZeuJO68cNIub5m2mu/Yj5cOcgK/pQn6', '蓝海', 0, 'https://static.rocketzh.com/images/head/5.jpg', 1, 0, '2025-11-11 21:36:58', '127.0.0.1', '', '2025-11-01 00:00:00', '2025-11-11 21:36:58');
INSERT INTO `system_admin` VALUES (2, 'yyyyy', '', '', '$2y$10$EQpWowOdR4lEjT9Iaj6lhuR3osnSRYIZqxJ7NJ3TAbEFDi1sVO4i.', 'yyyyy', 0, '', 1, 0, '2025-11-11 17:24:09', '192.168.0.141', '', '2025-11-11 17:07:46', '2025-11-11 17:24:09');

-- ----------------------------
-- Table structure for system_admin_role
-- ----------------------------
DROP TABLE IF EXISTS `system_admin_role`;
CREATE TABLE `system_admin_role`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `admin_id` bigint UNSIGNED NOT NULL COMMENT '管理员ID',
  `role_id` bigint UNSIGNED NOT NULL COMMENT '角色ID',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_admin_role`(`admin_id` ASC, `role_id` ASC) USING BTREE,
  INDEX `idx_admin_id`(`admin_id` ASC) USING BTREE,
  INDEX `idx_role_id`(`role_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 12 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户角色关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of system_admin_role
-- ----------------------------
INSERT INTO `system_admin_role` VALUES (10, 1, 1, '2025-11-07 14:19:55', '2025-11-07 14:19:55');
INSERT INTO `system_admin_role` VALUES (11, 2, 2, '2025-11-11 17:07:46', '2025-11-11 17:07:46');

-- ----------------------------
-- Table structure for system_config
-- ----------------------------
DROP TABLE IF EXISTS `system_config`;
CREATE TABLE `system_config`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `config_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '配置键名',
  `config_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '配置值(JSON格式)',
  `config_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '配置类型：app,logo,theme,copyright,layout,tabbar,sidebar,header,breadcrumb,footer',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '配置描述',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_config_key`(`config_key` ASC) USING BTREE,
  INDEX `idx_config_type`(`config_type` ASC) USING BTREE,
  INDEX `idx_status`(`status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 47 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统配置表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of system_config
-- ----------------------------
INSERT INTO `system_config` VALUES (1, 'app_name', '\"YAdmin\"', 'app', '应用名称', 1, 1, '2025-11-11 15:44:06', '2025-11-11 16:04:54');
INSERT INTO `system_config` VALUES (2, 'app_default_home_path', '\"\\/analytics\"', 'app', '默认首页路径', 2, 1, '2025-11-11 15:44:06', '2025-11-11 16:16:17');
INSERT INTO `system_config` VALUES (3, 'app_access_mode', '\"backend\"', 'app', '权限模式', 3, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (4, 'app_login_expired_mode', '\"modal\"', 'app', '登录过期模式', 4, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (5, 'app_locale', '\"zh-CN\"', 'app', '默认语言', 5, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (6, 'app_watermark', 'false', 'app', '是否启用水印', 6, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (7, 'app_watermark_content', '\"\"', 'app', '水印内容', 7, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (8, 'app_default_avatar', '\"https:\\/\\/unpkg.com\\/@vbenjs\\/static-source@0.1.7\\/source\\/avatar-v1.webp\"', 'app', '默认头像', 8, 1, '2025-11-11 15:44:06', '2025-11-11 16:16:17');
INSERT INTO `system_config` VALUES (9, 'app_enable_refresh_token', 'false', 'app', '是否启用刷新令牌', 9, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (10, 'app_dynamic_title', 'true', 'app', '动态标题', 10, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (11, 'logo_enable', 'true', 'logo', '是否显示Logo', 11, 1, '2025-11-11 15:44:06', '2025-11-11 16:16:25');
INSERT INTO `system_config` VALUES (12, 'logo_source', '\"https:\\/\\/unpkg.com\\/@vbenjs\\/static-source@0.1.7\\/source\\/logo-v1.webp\"', 'logo', 'Logo地址', 12, 1, '2025-11-11 15:44:06', '2025-11-11 16:16:17');
INSERT INTO `system_config` VALUES (13, 'logo_fit', '\"contain\"', 'logo', 'Logo适配方式', 13, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (14, 'theme_mode', '\"light\"', 'theme', '主题模式', 14, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (15, 'theme_color_primary', '\"hsl(212 100% 45%)\"', 'theme', '主题色', 15, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (16, 'theme_color_success', '\"hsl(144 57% 58%)\"', 'theme', '成功色', 16, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (17, 'theme_color_warning', '\"hsl(42 84% 61%)\"', 'theme', '警告色', 17, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (18, 'theme_color_destructive', '\"hsl(348 100% 61%)\"', 'theme', '危险色', 18, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (19, 'theme_builtin_type', '\"default\"', 'theme', '内置主题类型', 19, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (20, 'theme_radius', '\"0.5\"', 'theme', '圆角大小', 20, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (21, 'copyright_enable', 'true', 'copyright', '是否显示版权', 21, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (22, 'copyright_company_name', '\"YAdmin\"', 'copyright', '公司名称', 22, 1, '2025-11-11 15:44:06', '2025-11-11 16:23:03');
INSERT INTO `system_config` VALUES (23, 'copyright_company_site_link', '\"https:\\/\\/www.baidu.com\"', 'copyright', '公司网站', 23, 1, '2025-11-11 15:44:06', '2025-11-11 16:23:03');
INSERT INTO `system_config` VALUES (24, 'copyright_date', '\"2025\"', 'copyright', '版权年份', 24, 1, '2025-11-11 15:44:06', '2025-11-11 16:23:03');
INSERT INTO `system_config` VALUES (25, 'copyright_icp', '\"\"', 'copyright', 'ICP备案号', 25, 1, '2025-11-11 15:44:06', '2025-11-11 16:23:03');
INSERT INTO `system_config` VALUES (26, 'copyright_icp_link', '\"\"', 'copyright', 'ICP备案链接', 26, 1, '2025-11-11 15:44:06', '2025-11-11 16:23:03');
INSERT INTO `system_config` VALUES (27, 'layout_type', '\"sidebar-nav\"', 'layout', '布局类型', 27, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (28, 'content_compact', '\"wide\"', 'layout', '内容宽度模式', 28, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (29, 'content_compact_width', '1200', 'layout', '内容宽度', 29, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (30, 'tabbar_enable', 'true', 'tabbar', '是否启用标签页', 30, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (31, 'tabbar_keep_alive', 'true', 'tabbar', '标签页缓存', 31, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (32, 'tabbar_persist', 'true', 'tabbar', '标签页持久化', 32, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (33, 'tabbar_show_icon', 'true', 'tabbar', '显示图标', 33, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (34, 'tabbar_style_type', '\"card\"', 'tabbar', '标签页样式', 34, 1, '2025-11-11 15:44:06', '2025-11-11 16:23:45');
INSERT INTO `system_config` VALUES (35, 'sidebar_enable', 'true', 'sidebar', '是否启用侧边栏', 35, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (36, 'sidebar_width', '224', 'sidebar', '侧边栏宽度', 36, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (37, 'sidebar_collapsed_button', 'true', 'sidebar', '折叠按钮', 37, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (38, 'sidebar_expand_on_hover', 'true', 'sidebar', '鼠标悬停展开', 38, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (39, 'header_enable', 'true', 'header', '是否启用头部', 39, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (40, 'header_height', '50', 'header', '头部高度', 40, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (41, 'header_mode', '\"fixed\"', 'header', '头部模式', 41, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (42, 'breadcrumb_enable', 'true', 'breadcrumb', '是否显示面包屑', 42, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (43, 'breadcrumb_show_icon', 'true', 'breadcrumb', '显示图标', 43, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (44, 'breadcrumb_show_home', 'false', 'breadcrumb', '显示首页', 44, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (45, 'footer_enable', 'false', 'footer', '是否启用页脚', 45, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');
INSERT INTO `system_config` VALUES (46, 'footer_height', '32', 'footer', '页脚高度', 46, 1, '2025-11-11 15:44:06', '2025-11-11 15:44:06');

-- ----------------------------
-- Table structure for system_menu
-- ----------------------------
DROP TABLE IF EXISTS `system_menu`;
CREATE TABLE `system_menu`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '菜单ID',
  `parent_id` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '父菜单ID，0为顶级菜单',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '路由名称（英文）',
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '路由路径',
  `component` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '组件路径',
  `redirect` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '重定向路径',
  `type` tinyint NOT NULL DEFAULT 1 COMMENT '菜单类型：1-目录，2-菜单，3-按钮',
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '菜单标题（支持国际化key）',
  `icon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '菜单图标',
  `active_icon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '激活图标',
  `hide_in_menu` tinyint NOT NULL DEFAULT 0 COMMENT '是否在菜单中隐藏：0-否，1-是',
  `hide_in_tab` tinyint NOT NULL DEFAULT 0 COMMENT '是否在标签页中隐藏：0-否，1-是',
  `hide_in_breadcrumb` tinyint NOT NULL DEFAULT 0 COMMENT '是否在面包屑中隐藏：0-否，1-是',
  `hide_children_in_menu` tinyint NOT NULL DEFAULT 0 COMMENT '是否隐藏子菜单：0-否，1-是',
  `keep_alive` tinyint NOT NULL DEFAULT 0 COMMENT '是否缓存页面：0-否，1-是',
  `authority` json NULL COMMENT '权限标识数组，如：[\"sys:user:view\"]',
  `ignore_access` tinyint NOT NULL DEFAULT 0 COMMENT '是否忽略权限：0-否，1-是',
  `menu_visible_with_forbidden` tinyint NOT NULL DEFAULT 0 COMMENT '菜单可见但访问403：0-否，1-是',
  `badge` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '徽标文本',
  `badge_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'normal' COMMENT '徽标类型：dot-小红点，normal-文本',
  `badge_variants` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'success' COMMENT '徽标颜色：default/destructive/primary/success/warning',
  `affix_tab` tinyint NOT NULL DEFAULT 0 COMMENT '是否固定标签页：0-否，1-是',
  `affix_tab_order` int NOT NULL DEFAULT 0 COMMENT '固定标签页排序',
  `full_path_key` tinyint NOT NULL DEFAULT 1 COMMENT '完整路径作为key：0-否，1-是',
  `active_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '激活的菜单路径',
  `max_num_of_open_tab` int NOT NULL DEFAULT -1 COMMENT '最大打开标签数，-1为不限制',
  `link` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '外链地址',
  `iframe_src` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'iframe地址',
  `open_in_new_window` tinyint NOT NULL DEFAULT 0 COMMENT '是否新窗口打开：0-否，1-是',
  `no_basic_layout` tinyint NOT NULL DEFAULT 0 COMMENT '不使用基础布局：0-否，1-是',
  `query` json NULL COMMENT '路由参数，JSON格式',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序（升序）',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_parent_id`(`parent_id` ASC) USING BTREE,
  INDEX `idx_name`(`name` ASC) USING BTREE,
  INDEX `idx_path`(`path` ASC) USING BTREE,
  INDEX `idx_type`(`type` ASC) USING BTREE,
  INDEX `idx_status`(`status` ASC) USING BTREE,
  INDEX `idx_sort`(`sort` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 36 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统菜单表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of system_menu
-- ----------------------------
INSERT INTO `system_menu` VALUES (1, 0, 'Dashboard', '/dashboard', '', '/analytics', 1, '平台概览', 'f7:command', '', 0, 0, 0, 0, 0, NULL, 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, -1, 1, '', '2025-11-06 18:02:32', '2025-11-07 15:50:48');
INSERT INTO `system_menu` VALUES (2, 1, 'Analytics', '/analytics', '/dashboard/analytics/index', '', 2, '分析页', 'f7:cube-box', '', 0, 0, 0, 0, 0, NULL, 0, 0, '', 'normal', 'success', 1, 1, 1, '', -1, '', '', 0, 0, NULL, 1, 1, '', '2025-11-06 18:02:32', '2025-11-07 15:11:53');
INSERT INTO `system_menu` VALUES (3, 1, 'Workspace', '/workspace', '/dashboard/workspace/index', '', 2, '工作台', 'f7:chart-bar-square', '', 0, 0, 0, 0, 0, NULL, 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 2, 1, '', '2025-11-06 18:02:32', '2025-11-07 15:11:56');
INSERT INTO `system_menu` VALUES (4, 0, 'System', '/system', '', '', 1, '系统管理', 'dashicons:admin-generic', '', 0, 0, 0, 0, 0, NULL, 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, -1, 1, '', '2025-11-07 10:13:27', '2025-11-07 15:12:01');
INSERT INTO `system_menu` VALUES (5, 4, 'SystemConfig', '/system/config', '/system/config/index', '', 2, '系统配置', 'material-symbols:settings-slow-motion', '', 0, 0, 0, 0, 0, NULL, 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 1, 1, '', '2025-11-07 10:50:55', '2025-11-07 15:12:09');
INSERT INTO `system_menu` VALUES (6, 4, 'SystemMenu', '/system/menu', '/system/menu/index', '', 2, '菜单配置', 'material-symbols:dataset-rounded', '', 0, 0, 0, 0, 0, NULL, 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 2, 1, '', '2025-11-07 10:53:57', '2025-11-07 15:12:19');
INSERT INTO `system_menu` VALUES (7, 4, 'SystemRole', '/system/role', '/system/role/index', '', 2, '角色配置', 'material-symbols:deployed-code-account-outline', '', 0, 0, 0, 0, 0, NULL, 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 3, 1, '', '2025-11-07 10:55:34', '2025-11-07 15:12:24');
INSERT INTO `system_menu` VALUES (8, 4, 'SystemAdmin', '/system/admin', '/system/admin/index', '', 2, '用户配置', 'material-symbols:admin-panel-settings-rounded', '', 0, 0, 0, 0, 0, NULL, 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 4, 1, '', '2025-11-07 10:56:18', '2025-11-07 15:12:29');
INSERT INTO `system_menu` VALUES (9, 6, 'SystemMenuList', '', '', '', 3, '查看列表', 'basil:book-mark-outline', '', 0, 0, 0, 0, 0, '[\"system:menu:list\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 1, 1, '', '2025-11-07 14:52:56', '2025-11-11 17:41:34');
INSERT INTO `system_menu` VALUES (10, 6, 'SystemMenuRoutes', '', '', '', 3, '获取路由菜单', 'basil:apps-outline', '', 0, 0, 0, 0, 0, '[\"system:menu:routes\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 2, 1, '', '2025-11-07 14:52:56', '2025-11-11 17:42:15');
INSERT INTO `system_menu` VALUES (11, 6, 'SystemMenuButtons', '', '', '', 3, '获取按钮权限', 'basil:user-outline', '', 0, 0, 0, 0, 0, '[\"system:menu:buttons\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 3, 1, '', '2025-11-07 14:52:56', '2025-11-11 17:42:36');
INSERT INTO `system_menu` VALUES (12, 6, 'SystemMenuShow', '', '', '', 3, '查看详情', 'basil:book-open-outline', '', 0, 0, 0, 0, 0, '[\"system:menu:show\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 4, 1, '', '2025-11-07 14:52:56', '2025-11-11 17:41:49');
INSERT INTO `system_menu` VALUES (13, 6, 'SystemMenuAdd', '', '', '', 3, '新增', 'basil:add-outline', '', 0, 0, 0, 0, 0, '[\"system:menu:add\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 5, 1, '', '2025-11-07 14:52:56', '2025-11-11 17:39:30');
INSERT INTO `system_menu` VALUES (14, 6, 'SystemMenuEdit', '', '', '', 3, '编辑', 'basil:exchange-solid', '', 0, 0, 0, 0, 0, '[\"system:menu:edit\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 6, 1, '', '2025-11-07 14:52:56', '2025-11-11 17:40:05');
INSERT INTO `system_menu` VALUES (15, 6, 'SystemMenuDelete', '', '', '', 3, '删除', 'basil:cancel-outline', '', 0, 0, 0, 0, 0, '[\"system:menu:delete\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 7, 1, '', '2025-11-07 14:52:56', '2025-11-11 17:41:13');
INSERT INTO `system_menu` VALUES (16, 6, 'SystemMenuStatus', '', '', '', 3, '修改状态', 'basil:comment-block-outline', '', 0, 0, 0, 0, 0, '[\"system:menu:status\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 8, 1, '', '2025-11-07 14:52:56', '2025-11-11 17:42:06');
INSERT INTO `system_menu` VALUES (17, 7, 'SystemRoleList', '', '', '', 3, '查看列表', 'basil:book-mark-outline', '', 0, 0, 0, 0, 0, '[\"system:role:list\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 1, 1, '', '2025-11-07 14:53:26', '2025-11-11 17:41:38');
INSERT INTO `system_menu` VALUES (18, 7, 'SystemRoleAll', '', '', '', 3, '获取所有角色', 'basil:contacts-outline', '', 0, 0, 0, 0, 0, '[\"system:role:all\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 2, 1, '', '2025-11-07 14:53:26', '2025-11-11 17:42:59');
INSERT INTO `system_menu` VALUES (19, 7, 'SystemRoleShow', '', '', '', 3, '查看详情', 'basil:book-open-outline', '', 0, 0, 0, 0, 0, '[\"system:role:show\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 3, 1, '', '2025-11-07 14:53:26', '2025-11-11 17:41:53');
INSERT INTO `system_menu` VALUES (20, 7, 'SystemRoleAdd', '', '', '', 3, '新增', 'basil:add-outline', '', 0, 0, 0, 0, 0, '[\"system:role:add\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 4, 1, '', '2025-11-07 14:53:26', '2025-11-11 17:39:34');
INSERT INTO `system_menu` VALUES (21, 7, 'SystemRoleEdit', '', '', '', 3, '编辑', 'basil:exchange-solid', '', 0, 0, 0, 0, 0, '[\"system:role:edit\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 5, 1, '', '2025-11-07 14:53:26', '2025-11-11 17:40:04');
INSERT INTO `system_menu` VALUES (22, 7, 'SystemRoleDelete', '', '', '', 3, '删除', 'basil:cancel-outline', '', 0, 0, 0, 0, 0, '[\"system:role:delete\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 6, 1, '', '2025-11-07 14:53:26', '2025-11-11 17:41:15');
INSERT INTO `system_menu` VALUES (23, 7, 'SystemRoleStatus', '', '', '', 3, '修改状态', 'basil:comment-block-outline', '', 0, 0, 0, 0, 0, '[\"system:role:status\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 7, 1, '', '2025-11-07 14:53:26', '2025-11-11 17:42:07');
INSERT INTO `system_menu` VALUES (24, 8, 'SystemAdminList', '', '', '', 3, '查看列表', 'basil:book-mark-outline', '', 0, 0, 0, 0, 0, '[\"system:admin:list\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 1, 1, '', '2025-11-07 14:53:55', '2025-11-11 17:41:38');
INSERT INTO `system_menu` VALUES (25, 8, 'SystemAdminShow', '', '', '', 3, '查看详情', 'basil:book-open-outline', '', 0, 0, 0, 0, 0, '[\"system:admin:show\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 2, 1, '', '2025-11-07 14:53:55', '2025-11-11 17:41:55');
INSERT INTO `system_menu` VALUES (26, 8, 'SystemAdminAdd', '', '', '', 3, '新增', 'basil:add-outline', '', 0, 0, 0, 0, 0, '[\"system:admin:add\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 3, 1, '', '2025-11-07 14:53:55', '2025-11-11 17:39:35');
INSERT INTO `system_menu` VALUES (27, 8, 'SystemAdminEdit', '', '', '', 3, '编辑', 'basil:exchange-solid', '', 0, 0, 0, 0, 0, '[\"system:admin:edit\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 4, 1, '', '2025-11-07 14:53:55', '2025-11-11 17:40:06');
INSERT INTO `system_menu` VALUES (28, 8, 'SystemAdminDelete', '', '', '', 3, '删除', 'basil:cancel-outline', '', 0, 0, 0, 0, 0, '[\"system:admin:delete\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 5, 1, '', '2025-11-07 14:53:55', '2025-11-11 17:41:16');
INSERT INTO `system_menu` VALUES (29, 8, 'SystemAdminStatus', '', '', '', 3, '修改状态', 'basil:comment-block-outline', '', 0, 0, 0, 0, 0, '[\"system:admin:status\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 6, 1, '', '2025-11-07 14:53:55', '2025-11-11 17:42:09');
INSERT INTO `system_menu` VALUES (30, 0, 'Account', '/account', '', '/account/profile', 1, '个人设置', 'eos-icons:compass', '', 0, 0, 0, 0, 0, NULL, 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, -1, 1, '用户个人资料设置页面（隐藏在菜单中）', '2025-11-07 16:43:01', '2025-11-11 18:00:42');
INSERT INTO `system_menu` VALUES (31, 35, 'ProfileShow', '', '', '', 3, '查看资料', 'basil:qq-outline', '', 0, 0, 0, 0, 0, '[\"profile:show\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 1, 1, '', '2025-11-07 16:43:01', '2025-11-11 17:43:14');
INSERT INTO `system_menu` VALUES (32, 35, 'ProfileUpdate', '', '', '', 3, '更新资料', 'basil:qq-solid', '', 0, 0, 0, 0, 0, '[\"profile:update\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 2, 1, '', '2025-11-07 16:43:01', '2025-11-11 17:43:27');
INSERT INTO `system_menu` VALUES (33, 35, 'ProfileChangePassword', '', '', '', 3, '修改密码', 'basil:lock-outline', '', 0, 0, 0, 0, 0, '[\"profile:changePassword\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 3, 1, '', '2025-11-07 16:43:01', '2025-11-11 17:43:37');
INSERT INTO `system_menu` VALUES (34, 35, 'ProfileUploadAvatar', '', '', '', 3, '上传头像', 'basil:windows-outline', '', 0, 0, 0, 0, 0, '[\"profile:uploadAvatar\"]', 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 4, 1, '', '2025-11-07 16:43:01', '2025-11-11 17:43:38');
INSERT INTO `system_menu` VALUES (35, 30, 'AccountProfile', '/account/profile', '/system/admin/profile/index', '', 2, '个人设置', 'eos-icons:patterns', '', 0, 0, 0, 0, 0, NULL, 0, 0, '', 'normal', 'success', 0, 0, 1, '', -1, '', '', 0, 0, NULL, 999, 1, '', '2025-11-07 17:02:25', '2025-11-11 18:00:24');

-- ----------------------------
-- Table structure for system_role
-- ----------------------------
DROP TABLE IF EXISTS `system_role`;
CREATE TABLE `system_role`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '角色名称',
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '角色编码',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '角色描述',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_code`(`code` ASC) USING BTREE,
  INDEX `idx_status`(`status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '角色表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of system_role
-- ----------------------------
INSERT INTO `system_role` VALUES (1, '超级管理员', 'superadmin', '超级管理员', 0, 1, '2025-11-07 13:03:15', '2025-11-07 13:03:15');
INSERT INTO `system_role` VALUES (2, '管理员', 'admin', '管理员', 0, 1, '2025-11-11 17:06:36', '2025-11-11 17:06:36');

-- ----------------------------
-- Table structure for system_role_menu
-- ----------------------------
DROP TABLE IF EXISTS `system_role_menu`;
CREATE TABLE `system_role_menu`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `role_id` bigint UNSIGNED NOT NULL COMMENT '角色ID',
  `menu_id` bigint UNSIGNED NOT NULL COMMENT '菜单ID',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_role_menu`(`role_id` ASC, `menu_id` ASC) USING BTREE,
  INDEX `idx_role_id`(`role_id` ASC) USING BTREE,
  INDEX `idx_menu_id`(`menu_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 169 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '角色菜单关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of system_role_menu
-- ----------------------------
INSERT INTO `system_role_menu` VALUES (123, 1, 2, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (124, 1, 3, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (125, 1, 5, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (126, 1, 9, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (127, 1, 10, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (128, 1, 11, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (129, 1, 12, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (130, 1, 13, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (131, 1, 14, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (132, 1, 15, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (133, 1, 16, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (134, 1, 17, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (135, 1, 18, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (136, 1, 19, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (137, 1, 20, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (138, 1, 21, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (139, 1, 22, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (140, 1, 23, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (141, 1, 24, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (142, 1, 25, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (143, 1, 26, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (144, 1, 27, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (145, 1, 28, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (146, 1, 29, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (147, 1, 6, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (148, 1, 7, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (149, 1, 8, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (150, 1, 1, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (151, 1, 4, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (152, 1, 30, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (153, 1, 35, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (154, 1, 31, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (155, 1, 32, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (156, 1, 33, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (157, 1, 34, '2025-11-11 17:05:15', '2025-11-11 17:05:15');
INSERT INTO `system_role_menu` VALUES (158, 2, 1, '2025-11-11 17:06:36', '2025-11-11 17:06:36');
INSERT INTO `system_role_menu` VALUES (159, 2, 2, '2025-11-11 17:06:36', '2025-11-11 17:06:36');
INSERT INTO `system_role_menu` VALUES (160, 2, 3, '2025-11-11 17:06:36', '2025-11-11 17:06:36');
INSERT INTO `system_role_menu` VALUES (161, 2, 5, '2025-11-11 17:06:36', '2025-11-11 17:06:36');
INSERT INTO `system_role_menu` VALUES (162, 2, 30, '2025-11-11 17:06:36', '2025-11-11 17:06:36');
INSERT INTO `system_role_menu` VALUES (163, 2, 35, '2025-11-11 17:06:36', '2025-11-11 17:06:36');
INSERT INTO `system_role_menu` VALUES (164, 2, 31, '2025-11-11 17:06:36', '2025-11-11 17:06:36');
INSERT INTO `system_role_menu` VALUES (165, 2, 32, '2025-11-11 17:06:36', '2025-11-11 17:06:36');
INSERT INTO `system_role_menu` VALUES (166, 2, 33, '2025-11-11 17:06:36', '2025-11-11 17:06:36');
INSERT INTO `system_role_menu` VALUES (167, 2, 34, '2025-11-11 17:06:36', '2025-11-11 17:06:36');
INSERT INTO `system_role_menu` VALUES (168, 2, 4, '2025-11-11 17:06:36', '2025-11-11 17:06:36');

SET FOREIGN_KEY_CHECKS = 1;
