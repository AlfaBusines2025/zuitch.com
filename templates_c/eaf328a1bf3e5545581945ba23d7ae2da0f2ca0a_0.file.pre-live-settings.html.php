<?php
/* Smarty version 3.1.34-dev-7, created on 2025-03-18 16:53:05
  from '/var/www/vhosts/zuitch.com/httpdocs/vy-livestream/layout/pre-live-settings.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.34-dev-7',
  'unifunc' => 'content_67d996e199fa26_93979819',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'eaf328a1bf3e5545581945ba23d7ae2da0f2ca0a' => 
    array (
      0 => '/var/www/vhosts/zuitch.com/httpdocs/vy-livestream/layout/pre-live-settings.html',
      1 => 1693731894,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_67d996e199fa26_93979819 (Smarty_Internal_Template $_smarty_tpl) {
?><div id="vy_lv_prelivedashboard" class="vy_lv_a4cp"><div class="vy_lv_ajax_loading1 js__vy_lv_ajax_loading1 __none"><div class="vy_lv_ajax_loading"></div></div><div class="vy_lv_a7t"><div class="vy_lv_a8t"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['post'];?>
</div><div class="vy_lv_a9u"><div class="vy_lv_a10ua"><img src="<?php echo $_smarty_tpl->tpl_vars['this']->value->USER['profile_photo'];?>
" /></div><div class="vy_lv_a12np"><div class="vy_lv_a11uf"><?php echo $_smarty_tpl->tpl_vars['this']->value->USER['fullname'];?>
</div><div class="vy_lv_a13s"><div class="vy_lv_a14s" id="vy_lv_op_privacy"><i class="vy_lv_a15_ic __everyone"></i><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['everyone'];?>
</div><input type="hidden" value="1" name="vy_lv_audience" /></div></div></div><?php if ($_smarty_tpl->tpl_vars['this']->value->page_id > 0) {
ob_start();
echo dirname('__FILE__');
$_prefixVariable1=ob_get_clean();
$_smarty_tpl->_subTemplateRender($_prefixVariable1."/to_page.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
}
if ($_smarty_tpl->tpl_vars['this']->value->group_id > 0) {
ob_start();
echo dirname('__FILE__');
$_prefixVariable2=ob_get_clean();
$_smarty_tpl->_subTemplateRender($_prefixVariable2."/to_group.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
}?><div class="vy_lv_a9u"><input type="text" onkeyup="vy_lvst.updateGoLiveData(this,'title');" class="vy_lv_a16t" name="vy_lv_title" value="" placeholder="<?php echo $_smarty_tpl->tpl_vars['this']->value->lang['title_optional'];?>
"/></div><div class="vy_lv_a9u"><textarea name="vy_lv_descr" id="vy_lv_txtemojis" onkeyup="vy_lvst.updateGoLiveData(this,'description');" placeholder="<?php echo $_smarty_tpl->tpl_vars['this']->value->lang['stream_description'];?>
" class="vy_lv_a17t"></textarea></div><div class="vy_lv_a9u" id="vy_lvst_add_product_cnt"><a href="javascript:void(0);" class="vy_lv_a24t" id="vy_lv_addproduct_btn" onclick="vy_lvst.addProduct(this,event);"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['plus_ic'];?>
 <?php echo $_smarty_tpl->tpl_vars['this']->value->lang['add_product'];?>
</a></div><?php if ($_smarty_tpl->tpl_vars['this']->value->recording) {?><div class="vy_lv_a9u"><label class="nm_tx" for="vy_ch_recordingtotimeline"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['record_stream'];?>
</label><label class="el-switch"><input type="checkbox" onclick="vy_lvst.updateGoLiveData(this,'record');" id="vy_ch_recordingtotimeline" name="vy_record_to_timeline" checked><span class="el-switch-style"></span></label></div></div><?php }?><Div class="vy_lv_fbt_go"><div class="vy_lv_p16"><button disabled readonly class="vy_lv_fvbbt_govtn js__vy_lv_fvbbtgo __disabled"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['go_live'];?>
</button></div></div></div></div><?php }
}
