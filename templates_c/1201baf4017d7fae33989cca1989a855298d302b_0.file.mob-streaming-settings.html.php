<?php
/* Smarty version 3.1.34-dev-7, created on 2025-03-25 20:26:38
  from '/var/www/vhosts/zuitch.com/httpdocs/vy-livestream/layout/popups/mob/contents/mob-streaming-settings.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.34-dev-7',
  'unifunc' => 'content_67e3036e419d04_88311199',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '1201baf4017d7fae33989cca1989a855298d302b' => 
    array (
      0 => '/var/www/vhosts/zuitch.com/httpdocs/vy-livestream/layout/popups/mob/contents/mob-streaming-settings.html',
      1 => 1693731894,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_67e3036e419d04_88311199 (Smarty_Internal_Template $_smarty_tpl) {
?><section class="vy_lv_mob_popup_allincard"><fieldset><legend><b> <?php echo $_smarty_tpl->tpl_vars['this']->value->lang['viewers'];?>
 </b> </legend><button data-id="<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
" data-kind="viewers" data-title="Block/Mute Viewers" class="vy_lv_ripple" onclick="vy_lvst.mobstf(event,this);"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['block_mute_viewers'];?>
</button></fieldset></section><section class="vy_lv_mob_popup_allincard"><fieldset><legend><b> <?php echo $_smarty_tpl->tpl_vars['this']->value->lang['add_moderators'];?>
 </b> </legend><button data-id="<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
" data-kind="new-moder" data-title="Add moderators" class="vy_lv_ripple" onclick="vy_lvst.mobstf(event,this);"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['add_moderators'];?>
</button></fieldset></section><section class="vy_lv_mob_popup_allincard"><fieldset><legend><b> <?php echo $_smarty_tpl->tpl_vars['this']->value->lang['remove_moderators'];?>
 </b> </legend><button data-id="<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
" data-kind="remove-moder" data-title="Remove moderators" class="vy_lv_ripple" onclick="vy_lvst.mobstf(event,this);"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['remove_moderators'];?>
</button></fieldset></section><?php }
}
