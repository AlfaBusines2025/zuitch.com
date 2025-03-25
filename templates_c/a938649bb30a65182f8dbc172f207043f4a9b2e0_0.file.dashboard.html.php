<?php
/* Smarty version 3.1.34-dev-7, created on 2025-03-19 16:18:10
  from '/var/www/vhosts/zuitch.com/httpdocs/vy-livestream/layout/dashboard.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.34-dev-7',
  'unifunc' => 'content_67dae0325d9a08_84215981',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'a938649bb30a65182f8dbc172f207043f4a9b2e0' => 
    array (
      0 => '/var/www/vhosts/zuitch.com/httpdocs/vy-livestream/layout/dashboard.html',
      1 => 1693731894,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_67dae0325d9a08_84215981 (Smarty_Internal_Template $_smarty_tpl) {
?><div class="vy_lv_dashboard vy_lvst_js__dashboard"><div class="vy_lv_dashboard1"><div class="vy_lv_a0558xt"><div onclick="vy_lvst.beforeStopLive();" class="vy_lv_r"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['shutdown'];?>
</div><div class="vy_lv_542f vy_lv_a8t"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['dashboard'];?>
</div><div class="vy_lv_timer js__vy_lv_timer">00:00</div></div><div class="vy_lvst_a35if" onclick="vy_lvst.openSettings(this,event,<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
);"><span class="vy_l_45f"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['settings'];?>
</span><span class="vy_l543"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['settings'];?>
</span></div><div style="display: none;" class="vy_lv_a8t"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['live_events'];?>
</div><div class="vy_lv_a8t_container"><div class="vy_lv_a8t_header"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['live_events'];?>
</div><div class="vy_lv_a8t_line"></div></div><div class="vy_lv_comments-section" id="vy_lv_comments_section"></div><div class="vy-lv-comments-section js__vy-lv-comments-section"><input type="text" class="vy-lv-inputfantom js__inputfantom" onclick="vy_lvst.addCommentTxtarea(this,event,'<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
');" onfocus="vy_lvst.addCommentTxtarea(this,event,'<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
');" placeholder="<?php echo $_smarty_tpl->tpl_vars['this']->value->lang['add_comments'];?>
" /></div></div></div><?php }
}
