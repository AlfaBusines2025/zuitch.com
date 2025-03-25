<?php
/* Smarty version 3.1.34-dev-7, created on 2025-03-25 20:26:38
  from '/var/www/vhosts/zuitch.com/httpdocs/vy-livestream/layout/popups/mob/content.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.34-dev-7',
  'unifunc' => 'content_67e3036e4043b7_44287831',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '5f96ac1eb7be1620510299bca1bfd9a2f60431a0' => 
    array (
      0 => '/var/www/vhosts/zuitch.com/httpdocs/vy-livestream/layout/popups/mob/content.html',
      1 => 1693731894,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_67e3036e4043b7_44287831 (Smarty_Internal_Template $_smarty_tpl) {
?><section id="vy_lv__mob_popup"><?php ob_start();
echo dirname('__FILE__');
$_prefixVariable1=ob_get_clean();
$_smarty_tpl->_subTemplateRender($_prefixVariable1."/header.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?><p hide-on-bottom><?php ob_start();
echo dirname('__FILE__');
$_prefixVariable2=ob_get_clean();
$_smarty_tpl->_subTemplateRender($_prefixVariable2."/contents/".((string)$_smarty_tpl->tpl_vars['file_content']->value), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?></p><?php ob_start();
echo dirname('__FILE__');
$_prefixVariable3=ob_get_clean();
$_smarty_tpl->_subTemplateRender($_prefixVariable3."/footer.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?></section><?php }
}
