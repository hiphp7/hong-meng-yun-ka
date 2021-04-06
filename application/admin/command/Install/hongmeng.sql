/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50644
Source Host           : localhost:3306
Source Database       : 3500

Target Server Type    : MYSQL
Target Server Version : 50644
File Encoding         : 65001

Date: 2021-04-06 22:15:33
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for hm_admin
-- ----------------------------
DROP TABLE IF EXISTS `hm_admin`;
CREATE TABLE `hm_admin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `username` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '用户名',
  `nickname` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '昵称',
  `password` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '密码',
  `salt` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '密码盐',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '头像',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '电子邮箱',
  `loginfailure` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
  `logintime` int(10) DEFAULT NULL COMMENT '登录时间',
  `loginip` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '登录IP',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `token` varchar(59) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'Session标识',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `username` (`username`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='管理员表';

-- ----------------------------
-- Records of hm_admin
-- ----------------------------
INSERT INTO `hm_admin` VALUES ('1', 'admin', 'Admin', '010e7c10aec338db776976dadaa8fb63', 'fc0226', '/assets/img/avatar.png', 'admin@admin.com', '0', '1617603243', '127.0.0.1', '1492186163', '1617603243', 'c5030536-5119-4fa9-8585-a4d7134e3181', 'normal');

-- ----------------------------
-- Table structure for hm_admin_log
-- ----------------------------
DROP TABLE IF EXISTS `hm_admin_log`;
CREATE TABLE `hm_admin_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `username` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '管理员名字',
  `url` varchar(1500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '操作页面',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '日志标题',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '内容',
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'IP',
  `useragent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'User-Agent',
  `createtime` int(10) DEFAULT NULL COMMENT '操作时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `name` (`username`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='管理员日志表';

-- ----------------------------
-- Records of hm_admin_log
-- ----------------------------

-- ----------------------------
-- Table structure for hm_attach
-- ----------------------------
DROP TABLE IF EXISTS `hm_attach`;
CREATE TABLE `hm_attach` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(255) NOT NULL COMMENT '名称',
  `value_json` varchar(1000) DEFAULT NULL COMMENT '内容',
  `createtime` int(10) DEFAULT NULL COMMENT '添加时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='附加选项';

-- ----------------------------
-- Records of hm_attach
-- ----------------------------

-- ----------------------------
-- Table structure for hm_attachment
-- ----------------------------
DROP TABLE IF EXISTS `hm_attachment`;
CREATE TABLE `hm_attachment` (
  `id` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '物理路径',
  `imagewidth` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '宽度',
  `imageheight` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '高度',
  `imagetype` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '图片类型',
  `imageframes` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '图片帧数',
  `filename` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '文件名称',
  `filesize` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
  `mimetype` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'mime类型',
  `extparam` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '透传数据',
  `createtime` int(10) DEFAULT NULL COMMENT '创建日期',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `uploadtime` int(10) DEFAULT NULL COMMENT '上传时间',
  `storage` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local' COMMENT '存储位置',
  `sha1` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '文件 sha1编码',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='附件表';

-- ----------------------------
-- Records of hm_attachment
-- ----------------------------

-- ----------------------------
-- Table structure for hm_auth_rule
-- ----------------------------
DROP TABLE IF EXISTS `hm_auth_rule`;
CREATE TABLE `hm_auth_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('menu','file') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'file' COMMENT 'menu为菜单,file为权限节点',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '规则名称',
  `title` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '规则名称',
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '图标',
  `condition` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '条件',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '备注',
  `ismenu` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否为菜单',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `name` (`name`) USING BTREE,
  KEY `pid` (`pid`) USING BTREE,
  KEY `weigh` (`weigh`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='节点表';

-- ----------------------------
-- Records of hm_auth_rule
-- ----------------------------
INSERT INTO `hm_auth_rule` VALUES ('1', 'file', '0', 'dashboard', '控制台', 'fa fa-dashboard', '', 'Dashboard tips', '1', '1497429920', '1610011926', '99', 'normal');
INSERT INTO `hm_auth_rule` VALUES ('2', 'file', '14', 'general/config', '基础配置', 'fa fa-cog', '', 'Config tips', '1', '1497429920', '1612337221', '98', 'normal');
INSERT INTO `hm_auth_rule` VALUES ('3', 'file', '5', 'category', '商品分类', 'fa fa-leaf', '', '', '1', '1497429920', '1612337270', '94', 'normal');
INSERT INTO `hm_auth_rule` VALUES ('4', 'file', '0', 'user/user', '用户管理', 'fa fa-user', '', '', '1', '1516374729', '1608623287', '96', 'normal');
INSERT INTO `hm_auth_rule` VALUES ('5', 'file', '0', 'goods', '商品管理', 'fa fa-shopping-cart', '', '', '1', '1608693327', '1608693327', '95', 'normal');
INSERT INTO `hm_auth_rule` VALUES ('6', 'file', '103', 'order/index', '商品订单', 'fa fa-reorder', '', '', '1', '1608971632', '1612337438', '92', 'normal');
INSERT INTO `hm_auth_rule` VALUES ('7', 'file', '14', 'pay', '支付配置', 'fa fa-server', '', '您可以拖动操作栏中的排序按钮，从而控制用户支付的优先级', '1', '1608617067', '1612337230', '93', 'normal');
INSERT INTO `hm_auth_rule` VALUES ('8', 'file', '103', 'cashout', '提现记录', 'fa fa-paypal', '', '', '1', '1609123931', '1612337452', '90', 'normal');
INSERT INTO `hm_auth_rule` VALUES ('9', 'file', '103', 'recharge', '充值记录', 'fa fa-yen', '', '', '1', '1609124214', '1612337461', '89', 'normal');
INSERT INTO `hm_auth_rule` VALUES ('10', 'file', '0', 'template', '模板管理', 'fa fa-list-alt', '', '', '1', '1610007850', '1610007850', '88', 'normal');
INSERT INTO `hm_auth_rule` VALUES ('11', 'file', '0', 'auth/rule', '菜单管理', 'fa fa-bars', '', 'Rule tips', '1', '1497429920', '1612337512', '87', 'hidden');
INSERT INTO `hm_auth_rule` VALUES ('12', 'file', '0', 'general/profile', '个人资料', 'fa fa-user', '', '', '1', '1497429920', '1609136189', '0', 'hidden');
INSERT INTO `hm_auth_rule` VALUES ('13', 'file', '0', 'general/attachment', '附件管理', 'fa fa-file-image-o', '', 'Attachment tips', '1', '1497429920', '1609136192', '0', 'hidden');
INSERT INTO `hm_auth_rule` VALUES ('14', 'file', '0', 'config', '系统配置', 'fa fa-cog', '', '', '1', '1612337210', '1612337210', '97', 'normal');
INSERT INTO `hm_auth_rule` VALUES ('15', 'file', '5', 'goods/index', '商品列表', 'fa fa-list-ul', '', '', '1', '1612337333', '1612337333', '0', 'normal');
INSERT INTO `hm_auth_rule` VALUES ('16', 'file', '5', 'attach', '附加选项', 'fa fa-bookmark', '', '', '1', '1612337369', '1612337377', '0', 'normal');
INSERT INTO `hm_auth_rule` VALUES ('17', 'file', '0', 'order', '订单管理', 'fa fa-reorder', '', '', '1', '1612337430', '1612337430', '91', 'normal');
INSERT INTO `hm_auth_rule` VALUES ('18', 'file', '0', 'docking', '对接管理', 'fa fa-sliders', '', '', '1', '1615643470', '1615643470', '0', 'normal');
INSERT INTO `hm_auth_rule` VALUES ('19', 'file', '18', 'docking/docking_site', '对接站点', 'fa fa-sitemap', '', '', '1', '1615644927', '1615644927', '0', 'normal');
INSERT INTO `hm_auth_rule` VALUES ('20', 'file', '18', 'docking/increase', '加价模板', 'fa fa-usd', '', '', '1', '1615648888', '1615648888', '0', 'normal');
INSERT INTO `hm_auth_rule` VALUES ('21', 'file', '0', 'plugin', '插件管理', 'fa fa-plug', '', '', '1', '1615648888', '1615648888', '0', 'normal');

-- ----------------------------
-- Table structure for hm_category
-- ----------------------------
DROP TABLE IF EXISTS `hm_category`;
CREATE TABLE `hm_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '栏目类型',
  `name` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `nickname` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `flag` set('hot','index','recommend') COLLATE utf8mb4_unicode_ci DEFAULT '',
  `image` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '图片',
  `keywords` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '关键字',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '描述',
  `goods_sort` tinyint(1) DEFAULT '0' COMMENT '商品排序',
  `diyname` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '自定义名称',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `weigh` (`weigh`,`id`) USING BTREE,
  KEY `pid` (`pid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='分类表';

-- ----------------------------
-- Records of hm_category
-- ----------------------------

-- ----------------------------
-- Table structure for hm_cdkey
-- ----------------------------
DROP TABLE IF EXISTS `hm_cdkey`;
CREATE TABLE `hm_cdkey` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `type` varchar(255) DEFAULT NULL,
  `goods_id` int(10) DEFAULT NULL COMMENT '商品id',
  `cdk` varchar(255) DEFAULT NULL COMMENT '内容',
  `createtime` int(10) unsigned DEFAULT NULL COMMENT '添加时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '编辑时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='识别码；激活码；注册码；卡密；';

-- ----------------------------
-- Records of hm_cdkey
-- ----------------------------

-- ----------------------------
-- Table structure for hm_config
-- ----------------------------
DROP TABLE IF EXISTS `hm_config`;
CREATE TABLE `hm_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '变量名',
  `group` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '分组',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '变量标题',
  `tip` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '变量描述',
  `type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '类型:string,text,int,bool,array,datetime,date,file',
  `value` text COLLATE utf8mb4_unicode_ci COMMENT '变量值',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '变量字典数据',
  `rule` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '验证规则',
  `extend` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '扩展属性',
  `setting` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '配置',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `name` (`name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='系统配置';

-- ----------------------------
-- Records of hm_config
-- ----------------------------
INSERT INTO `hm_config` VALUES ('1', 'shop_title', 'basic', '网站标题', '', 'string', '红盟云卡在线自动发卡系统 - 全国最大的虚拟货源销售平台', '', '', '', '{\"table\":\"\",\"conditions\":\"\",\"key\":\"\",\"value\":\"\"}');
INSERT INTO `hm_config` VALUES ('2', 'shop_pet_name', 'basic', '网站名称', '', 'string', '红盟云卡', '', '', '', '{\"table\":\"\",\"conditions\":\"\",\"key\":\"\",\"value\":\"\"}');
INSERT INTO `hm_config` VALUES ('3', 'beian', 'basic', 'Beian', '粤ICP备15000000号-1', 'string', '', '', '', '', null);
INSERT INTO `hm_config` VALUES ('4', 'version', 'other', '后台静态文件版本', '如果静态资源有变动请重新配置该值', 'string', '1616675516', '', 'required', '', null);
INSERT INTO `hm_config` VALUES ('5', 'fixedpage', 'other', 'Fixed page', '请尽量输入左侧菜单栏存在的链接', 'string', 'dashboard', '', 'required', '', null);
INSERT INTO `hm_config` VALUES ('6', 'configgroup', 'dictionary', 'Config group', '', 'array', '{\"basic\":\"Basic\",\"money\":\"资金配置\",\"other\":\"其他配置\"}', '', '', '', null);
INSERT INTO `hm_config` VALUES ('7', 'min_cashout', 'money', '最低提现金额', '0则不限制金额', 'number', '0', '', '', '', '{\"table\":\"\",\"conditions\":\"\",\"key\":\"\",\"value\":\"\"}');
INSERT INTO `hm_config` VALUES ('8', 'max_cashout_num', 'money', '每日最多提现次数', '0则不限制次数', 'number', '3', '', '', '', '{\"table\":\"\",\"conditions\":\"\",\"key\":\"\",\"value\":\"\"}');
INSERT INTO `hm_config` VALUES ('9', 'cashout_charged', 'money', '提现手续费%', '按照百分比填写', 'number', '1', '', '', '', '{\"table\":\"\",\"conditions\":\"\",\"key\":\"\",\"value\":\"\"}');
INSERT INTO `hm_config` VALUES ('10', 'tourist_buy', 'basic', '游客购买', '', 'radio', '1', '{\"1\":\"开启\",\"0\":\"关闭\"}', '', '', '{\"table\":\"\",\"conditions\":\"\",\"key\":\"\",\"value\":\"\"}');
INSERT INTO `hm_config` VALUES ('11', 'login', 'basic', '登录功能', '', 'radio', '1', '{\"1\":\"开启\",\"0\":\"关闭\"}', '', '', '{\"table\":\"\",\"conditions\":\"\",\"key\":\"\",\"value\":\"\"}');
INSERT INTO `hm_config` VALUES ('12', 'register', 'basic', '注册功能', '', 'radio', '1', '{\"1\":\"开启\",\"0\":\"关闭\"}', '', '', '{\"table\":\"\",\"conditions\":\"\",\"key\":\"\",\"value\":\"\"}');
INSERT INTO `hm_config` VALUES ('13', 'statistics', 'basic', '统计代码', '第三方流量统计代码', 'text', '', '', '', '', '{\"table\":\"\",\"conditions\":\"\",\"key\":\"\",\"value\":\"\"}');
INSERT INTO `hm_config` VALUES ('21', 'diy_name', 'other', '商品自定义支付名称', '此选项可以替换官方支付接口的商品名称，留空使用原商品名称。', 'string', '红盟云卡商品购买', null, '', '', null);

-- ----------------------------
-- Table structure for hm_docking_increase
-- ----------------------------
DROP TABLE IF EXISTS `hm_docking_increase`;
CREATE TABLE `hm_docking_increase` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL COMMENT '模板名称',
  `type` varchar(255) DEFAULT NULL COMMENT '加价方式',
  `value` varchar(255) DEFAULT NULL COMMENT '加价',
  `effect` varchar(255) DEFAULT NULL COMMENT '模板生效场景',
  `expire` int(10) DEFAULT '0' COMMENT '价格检测过期时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='对接 加价模板';

-- ----------------------------
-- Records of hm_docking_increase
-- ----------------------------

-- ----------------------------
-- Table structure for hm_docking_site
-- ----------------------------
DROP TABLE IF EXISTS `hm_docking_site`;
CREATE TABLE `hm_docking_site` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL COMMENT '类型',
  `domain` varchar(255) NOT NULL COMMENT '域名',
  `info` text COMMENT '对接网站所需信息',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='对接站点管理';

-- ----------------------------
-- Records of hm_docking_site
-- ----------------------------

-- ----------------------------
-- Table structure for hm_goods
-- ----------------------------
DROP TABLE IF EXISTS `hm_goods`;
CREATE TABLE `hm_goods` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `site_id` int(10) DEFAULT '0' COMMENT '对接站点id',
  `category_id` int(10) DEFAULT '0' COMMENT '商品分类id',
  `attach_id` int(10) DEFAULT '0' COMMENT '附加选项id',
  `remote_id` int(10) DEFAULT '0' COMMENT '对接商品id',
  `name` varchar(255) DEFAULT NULL,
  `diy_name` varchar(255) DEFAULT NULL COMMENT '自定义商品名称',
  `dock_data` text COMMENT '购买对接商品所需信息',
  `price` decimal(10,2) DEFAULT '0.00',
  `buy_price` decimal(10,2) DEFAULT '0.00' COMMENT '进货价格',
  `sales` int(11) DEFAULT '0' COMMENT '销量',
  `sales_money` decimal(10,2) DEFAULT '0.00' COMMENT '销售额',
  `images` varchar(255) DEFAULT NULL,
  `details` text,
  `shelf` tinyint(1) DEFAULT '0' COMMENT '1为下架',
  `goods_type` varchar(10) DEFAULT '0' COMMENT '0 卡密 1 激活码 2账号和密码 3图片 4其他',
  `type` varchar(10) DEFAULT 'own' COMMENT '产品类型',
  `deliver` tinyint(1) DEFAULT '0' COMMENT '发货方式0自动发货 1手动发货',
  `stock` int(10) DEFAULT '0' COMMENT '库存',
  `createtime` int(10) DEFAULT NULL,
  `updatetime` int(10) DEFAULT NULL,
  `deletetime` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Records of hm_goods
-- ----------------------------

-- ----------------------------
-- Table structure for hm_information
-- ----------------------------
DROP TABLE IF EXISTS `hm_information`;
CREATE TABLE `hm_information` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(255) DEFAULT NULL COMMENT '标题',
  `cover` varchar(255) DEFAULT NULL COMMENT '封面',
  `content` text COMMENT '内容',
  `views` int(11) DEFAULT '0',
  `createtime` int(10) DEFAULT NULL COMMENT '添加时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Records of hm_information
-- ----------------------------

-- ----------------------------
-- Table structure for hm_money_bill
-- ----------------------------
DROP TABLE IF EXISTS `hm_money_bill`;
CREATE TABLE `hm_money_bill` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  `createtime` int(10) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL COMMENT '类型',
  `money` decimal(10,2) DEFAULT '0.00',
  `actual` decimal(10,2) DEFAULT NULL,
  `charged` int(10) DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `handletime` int(10) DEFAULT NULL,
  `pay_type` varchar(255) DEFAULT NULL,
  `order_no` varchar(35) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='余额账单';

-- ----------------------------
-- Records of hm_money_bill
-- ----------------------------

-- ----------------------------
-- Table structure for hm_notice
-- ----------------------------
DROP TABLE IF EXISTS `hm_notice`;
CREATE TABLE `hm_notice` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uid` int(10) DEFAULT '0' COMMENT '用户ID',
  `type` int(10) DEFAULT '0' COMMENT '消息类型',
  `title` varchar(255) DEFAULT NULL COMMENT '标题',
  `content` text COMMENT '内容',
  `status` tinyint(1) DEFAULT '0',
  `createtime` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Records of hm_notice
-- ----------------------------

-- ----------------------------
-- Table structure for hm_options
-- ----------------------------
DROP TABLE IF EXISTS `hm_options`;
CREATE TABLE `hm_options` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `option_name` varchar(255) DEFAULT '0',
  `option_content` text,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Records of hm_options
-- ----------------------------
INSERT INTO `hm_options` VALUES ('1', 'tourist_num', '0');
INSERT INTO `hm_options` VALUES ('2', 'user_total', '0');
INSERT INTO `hm_options` VALUES ('3', 'order_total', '0');
INSERT INTO `hm_options` VALUES ('4', 'money_total', '0');
INSERT INTO `hm_options` VALUES ('5', 'goods_total', '0');
INSERT INTO `hm_options` VALUES ('6', 'active_plugin', 'a:1:{i:1;s:37:\"noticeOrderEmail/noticeOrderEmail.php\";}');

-- ----------------------------
-- Table structure for hm_order
-- ----------------------------
DROP TABLE IF EXISTS `hm_order`;
CREATE TABLE `hm_order` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `station_id` int(10) DEFAULT '0',
  `order_no` varchar(30) DEFAULT NULL,
  `remote_order_no` varchar(30) DEFAULT NULL,
  `uid` int(10) DEFAULT '0' COMMENT '用户id',
  `attach` varchar(800) DEFAULT NULL COMMENT '附件内容',
  `goods_id` int(10) DEFAULT '0' COMMENT '商品id',
  `goods_name` varchar(255) DEFAULT NULL COMMENT '商品名称',
  `goods_cover` varchar(255) DEFAULT NULL COMMENT '商品封面图',
  `goods_num` int(10) DEFAULT '0' COMMENT '购买数量',
  `goods_money` decimal(10,2) DEFAULT '0.00' COMMENT '商品单价',
  `money` decimal(10,2) DEFAULT '0.00' COMMENT '订单金额',
  `remote_money` decimal(10,2) DEFAULT '0.00' COMMENT '进货价格',
  `pay_type` varchar(255) DEFAULT NULL COMMENT '支付方式',
  `kami` text,
  `goods_type` tinyint(1) DEFAULT '0' COMMENT '0 卡密 1 激活码 2账号和密码',
  `status` varchar(20) DEFAULT 'weizhifu' COMMENT '订单状态 1代发货 2待收货 9已完成',
  `qr_code` varchar(255) DEFAULT NULL COMMENT '收款二维码',
  `createtime` int(10) DEFAULT '0' COMMENT '创建时间',
  `paytime` int(10) DEFAULT '0' COMMENT '支付时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Records of hm_order
-- ----------------------------

-- ----------------------------
-- Table structure for hm_pay
-- ----------------------------
DROP TABLE IF EXISTS `hm_pay`;
CREATE TABLE `hm_pay` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `value` text,
  `type` varchar(15) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0' COMMENT '是否启用',
  `weigh` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Records of hm_pay
-- ----------------------------
INSERT INTO `hm_pay` VALUES ('1', '支付宝支付', '', 'alipay', '0', '2');
INSERT INTO `hm_pay` VALUES ('2', '码支付', '', 'codepay', '0', '1');
INSERT INTO `hm_pay` VALUES ('3', '易支付', '', 'epay', '0', '3');
INSERT INTO `hm_pay` VALUES ('4', 'V免签', '', 'vpay', '0', '4');

-- ----------------------------
-- Table structure for hm_shop_station
-- ----------------------------
DROP TABLE IF EXISTS `hm_shop_station`;
CREATE TABLE `hm_shop_station` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uid` int(10) DEFAULT '0' COMMENT '用户ID',
  `pid` int(10) DEFAULT '0' COMMENT '上级ID',
  `son_num` int(10) DEFAULT '0' COMMENT '子站数量',
  `domain` varchar(255) DEFAULT NULL COMMENT '域名',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态',
  `createtime` int(10) DEFAULT NULL,
  `updatetime` int(10) DEFAULT NULL,
  `deletetime` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='商城子站';

-- ----------------------------
-- Records of hm_shop_station
-- ----------------------------

-- ----------------------------
-- Table structure for hm_template
-- ----------------------------
DROP TABLE IF EXISTS `hm_template`;
CREATE TABLE `hm_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `directory` varchar(30) DEFAULT NULL COMMENT '模板目录',
  `name` varchar(50) DEFAULT NULL COMMENT '模板名称',
  `author` varchar(20) DEFAULT NULL COMMENT '作者',
  `version` varchar(5) DEFAULT NULL COMMENT '版本',
  `status` tinyint(1) DEFAULT '0' COMMENT '0正常 1关闭',
  `cover` varchar(255) DEFAULT NULL COMMENT '封面图',
  `default` tinyint(1) DEFAULT '0' COMMENT '默认模板',
  `pc` tinyint(1) DEFAULT '0',
  `mobile` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='模板表';

-- ----------------------------
-- Records of hm_template
-- ----------------------------
INSERT INTO `hm_template` VALUES ('1', 'default', '默认模板', '红盟云商', '1.0.0', '0', '/content/template/default/cover.jpg', '0', '0', '1');

-- ----------------------------
-- Table structure for hm_test
-- ----------------------------
DROP TABLE IF EXISTS `hm_test`;
CREATE TABLE `hm_test` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `createtime` int(10) DEFAULT NULL,
  `content` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of hm_test
-- ----------------------------

-- ----------------------------
-- Table structure for hm_user
-- ----------------------------
DROP TABLE IF EXISTS `hm_user`;
CREATE TABLE `hm_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `consume` decimal(10,2) DEFAULT '0.00' COMMENT '消费金额',
  `tourist` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '游客标识',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '组别ID',
  `username` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '用户名',
  `nickname` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '昵称',
  `password` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '密码',
  `salt` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '密码盐',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '电子邮箱',
  `mobile` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '手机号',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '头像',
  `level` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '等级',
  `gender` tinyint(1) unsigned DEFAULT NULL COMMENT '性别',
  `birthday` date DEFAULT NULL COMMENT '生日',
  `bio` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '格言',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '余额',
  `score` int(10) NOT NULL DEFAULT '0' COMMENT '积分',
  `successions` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '连续登录天数',
  `maxsuccessions` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '最大连续登录天数',
  `prevtime` int(10) DEFAULT NULL COMMENT '上次登录时间',
  `logintime` int(10) DEFAULT NULL COMMENT '登录时间',
  `loginip` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '登录IP',
  `loginfailure` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
  `joinip` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '加入IP',
  `jointime` int(10) DEFAULT NULL COMMENT '加入时间',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `token` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'Token',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '状态',
  `verification` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '验证',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `username` (`username`) USING BTREE,
  KEY `email` (`email`) USING BTREE,
  KEY `mobile` (`mobile`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='会员表';

-- ----------------------------
-- Records of hm_user
-- ----------------------------
