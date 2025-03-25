<?php
/* Smarty version 3.1.34-dev-7, created on 2025-03-18 18:20:44
  from '/var/www/vhosts/zuitch.com/httpdocs/vy-livestream/layout/mobile-stream-author.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.34-dev-7',
  'unifunc' => 'content_67d9ab6c849131_09167598',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '345e2f0c920348d7780c34f38f3336626ebb5d06' => 
    array (
      0 => '/var/www/vhosts/zuitch.com/httpdocs/vy-livestream/layout/mobile-stream-author.html',
      1 => 1693731894,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_67d9ab6c849131_09167598 (Smarty_Internal_Template $_smarty_tpl) {
?><div class="vy_lv_a2" onclick="vy_lvst.makeFullScreen(event);"><div class="vy_lv_a3vs"><div class="vy_lv_a5v" id="vy_lv_rtmpv"><div class="vy_lv_reactions_floating __dashboard" id="vy_lv_reactions_floating"></div><div id="vy_lv_productauthor_preview"></div><div class="vy_lv_dashboard_llv_vvw_tm __none js__au4x2"><div class="vy_lv_dsh_llv_vvw"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['live'];?>
&nbsp;<div class="vy_lv_eyes"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['eye'];?>
&nbsp;<span class="js__vylv_count2_viewers">0</span></div></div><div class="vy_lv_dsh_tm js__vy_lv_dsh_tm">00:00</div></div><canvas id="vy-lv-cover" style="display:none;"></canvas><video autoplay playsinline muted id="vy_lv_main_videoel"></video><div id="vy_lv_a17cam_wait_stream" class="__none"><div class="vy_lv_a18camsvg"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['camera'];?>
</div><div class="vy_lv_a19camt"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['connect_streaming_soft_to_go_live'];?>
</div></div><div class="vy_lv_a17cam"><div class="vy_lv_a18camsvg"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['camera'];?>
</div><div id="vy_lv_a17cam_wait"><div class="vy_lv_a19camt"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['waiting_for_camera'];?>
</div></div><div id="vy_lv_camera_err_msg_markup"></div></div></div><section id="vy_lv_mob_opinv_comm" class="vy_lv_mob_opinv_comm __none"><div class="vy_lv_comments-section" id="vy_lv_comments_section"></div></section><section class="vy_lv_mob_footer"><div class="vy_lv_mob_f_btns"><div class="vy_lv_mob_a1"><div class="vy_lv_o_btn" id="vy_lv_mob_st_btn"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['mob_settings'];?>
</div><div id="vy_lv_mob_recording_btn" class="vy_lv_button_start_rec"><div class="vy_lv_button_start_rec__inner1"><div class="vy_lv_button_start_rec__inner2"></div></div></div><div class="vy_lv_o_btn __disabled" disabled id="vy_lv_mob_flip_btn"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['flip_camera'];?>
</div></div></div></section><?php ob_start();
echo dirname('__FILE__');
$_prefixVariable1=ob_get_clean();
$_smarty_tpl->_subTemplateRender($_prefixVariable1."/countdown.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?></div></div><?php ob_start();
echo dirname('__FILE__');
$_prefixVariable2=ob_get_clean();
$_smarty_tpl->_subTemplateRender($_prefixVariable2."/ripple-mob-gotohome.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
}
}
