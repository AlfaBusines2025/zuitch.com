<?php
/* Smarty version 3.1.34-dev-7, created on 2025-03-19 16:18:39
  from '/var/www/vhosts/zuitch.com/httpdocs/vy-livestream/layout/popups/live-settings.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.34-dev-7',
  'unifunc' => 'content_67dae04f8c9935_05375848',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'a20de8a528ebf3bf6e76557ac29e160d12554e63' => 
    array (
      0 => '/var/www/vhosts/zuitch.com/httpdocs/vy-livestream/layout/popups/live-settings.html',
      1 => 1693731894,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_67dae04f8c9935_05375848 (Smarty_Internal_Template $_smarty_tpl) {
ob_start();
echo dirname('__DIR__');
$_prefixVariable1=ob_get_clean();
$_smarty_tpl->_subTemplateRender($_prefixVariable1."/header.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?><div class="vy_lvst_settings_btns"><a href="javascript:void(0);" id="vy_lv_block_visitors" class="vy_lvst_settings_btn"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['block_mute_viewers'];?>
</a><a href="javascript:void(0);" id="vy_lv_add_moderators" class="vy_lvst_settings_btn"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['add_moderators'];?>
</a><a href="javascript:void(0);" id="vy_lv_remove_moderators" class="vy_lvst_settings_btn"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['remove_moderators'];?>
</a><a href="javascript:void(0);" id="vy_lv_end_live" class="vy_lvst_settings_btn __endlive"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['end_live'];?>
</a></div><div class="vy_lvst_setings_dv_cnt"></div><?php }
}
