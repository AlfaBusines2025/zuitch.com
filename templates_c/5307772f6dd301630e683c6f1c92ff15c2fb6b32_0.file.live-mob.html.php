<?php
/* Smarty version 3.1.34-dev-7, created on 2025-03-19 16:18:20
  from '/var/www/vhosts/zuitch.com/httpdocs/vy-livestream/layout/live-mob.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.34-dev-7',
  'unifunc' => 'content_67dae03c59e3c0_66603203',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '5307772f6dd301630e683c6f1c92ff15c2fb6b32' => 
    array (
      0 => '/var/www/vhosts/zuitch.com/httpdocs/vy-livestream/layout/live-mob.html',
      1 => 1693731894,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_67dae03c59e3c0_66603203 (Smarty_Internal_Template $_smarty_tpl) {
?><section id="vy-livest" class="js__vylivest_<?php echo $_smarty_tpl->tpl_vars['live_id']->value;?>
"><div class="vy-live-entry-cnt"><div style="position: absolute;top:200px;left:0" id="243ushfdiush"></div><div id="vy_lv_productauthor_preview"></div><div class="vy_lv_host_away"><img src="<?php echo $_smarty_tpl->tpl_vars['cover_path']->value;?>
" border="0"/><div class="vy_lv_host_away_text"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['host_is_away'];?>
</div></div><div class="vy_lv_host_ended"><div class="_img" style="background-image:url(<?php echo $_smarty_tpl->tpl_vars['cover_path']->value;?>
);"></div><div class="vy_lv_host_ended_text"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['stream_ended'];?>
</div></div><div class="vy_lv_host_reconnecting"><div class="vy_lv_host_reconnecting_text"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['host_network_problems'];?>
</div></div><div class="vy-live-e1v"><video playsinline autoplay id="vy_lv_livestream" poster="<?php echo $_smarty_tpl->tpl_vars['cover_path']->value;?>
"></video><div class="vy_lv_author"><div class="vy_lv_author_flex"><div class="vy_lv_author_avatar"><img src="<?php echo $_smarty_tpl->tpl_vars['author']->value['avatar'];?>
" border="0" /></div><div class="vy_lv_author_nvws"><div class="vy_lv_author1"><?php echo $_smarty_tpl->tpl_vars['author']->value['fullname'];?>
</div><?php if ($_smarty_tpl->tpl_vars['rows']->value['islivenow'] == 'yes') {?><div class="vy_lv_viewers_count"><span class="js__vylv4_count_viewers">0</span> <?php echo $_smarty_tpl->tpl_vars['this']->value->lang['viewers'];?>
</div><?php } else { ?><div class="vy_lv_duration"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['duration'];?>
: <?php echo $_smarty_tpl->tpl_vars['this']->value->getNiceDuration($_smarty_tpl->tpl_vars['rows']->value['time']);?>
</div><?php }?></div><div class="vy_lv_ftopbtn"><?php echo Wo_GetFollowButton($_smarty_tpl->tpl_vars['author']->value['id']);?>
</div></div></div><?php if ($_smarty_tpl->tpl_vars['rows']->value['islivenow'] == 'yes') {?><div class="vy_lv_dashboard_llv_vvw_tm vy_lv_viewer_dashboard js__au4x2"><div class="vy_lv_dsh_llv_vvw"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['live'];?>
&nbsp;<div class="vy_lv_eyes"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['eye'];?>
&nbsp;<span class="js__vylv_count2_viewers">0</span></div></div><div class="vy_lv_dsh_tm js__vy_lv_dsh_tm">00:00</div></div><?php }?><div class="vy-lv-close" onclick="vy_lvst.exitFromStream(<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
);"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['close'];?>
</div><div class="vy_lv_reactions_floating" id="vy_lv_reactions_floating"></div></div><?php if ($_smarty_tpl->tpl_vars['rows']->value['islivenow'] == 'yes') {?><div class="vy_lv_entryli" id="vy_lv_comments_sectionstick"><!-- comments --><div class="vy_lv_comments_section_inp_andcmm"><div class="vy_lv_comments-section" id="vy_lv_comments_section"><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['comments']->value, 'comment');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['comment']->value) {
$_smarty_tpl->_assignInScope('user', $_smarty_tpl->tpl_vars['this']->value->lv_getUserDetails($_smarty_tpl->tpl_vars['comment']->value['user_id']));?><div class="vy_lv_comment" onclick="vy_lvst.replyToComment(this,event);"><div class="vy_lv_comment2a"><img src="<?php echo $_smarty_tpl->tpl_vars['user']->value['avatar'];?>
" border="0"/></div><div class="vy_lv_comment_str3"><input type="hidden" class="__none js__comment_author_name" value="<?php echo $_smarty_tpl->tpl_vars['user']->value['fullname'];?>
"><span class="vy_lv_comment3a js__comment_author"><?php echo $_smarty_tpl->tpl_vars['user']->value['fullname'];?>
</span><div class="vy_lv_comment4_str"><?php echo $_smarty_tpl->tpl_vars['comment']->value['text'];?>
</div></div></div><?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?></div><div class="vy-lv-comments-section js__vy-lv-comments-section"><input type="text" class="vy-lv-inputfantom js__inputfantom" onclick="vy_lvst.addCommentTxtarea(this,event,'<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
');" onfocus="vy_lvst.addCommentTxtarea(this,event,'<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
');"  placeholder="<?php echo $_smarty_tpl->tpl_vars['this']->value->lang['add_comments'];?>
" /><!-- reactions --><div class="vy_lv_reactions_bt js__vy_lv_reactions_bt"></div></div></div></div><?php }?></div></section><?php }
}
