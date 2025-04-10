<?php
/* Smarty version 3.1.34-dev-7, created on 2025-03-31 17:06:18
  from '/var/www/vhosts/zuitch.com/httpdocs/vy-livestream/layout/product_template.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.34-dev-7',
  'unifunc' => 'content_67eaaf6a7f0f40_07984704',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '5d9396f50e3bd694fa5c71fb4acae6ed84eb5c21' => 
    array (
      0 => '/var/www/vhosts/zuitch.com/httpdocs/vy-livestream/layout/product_template.html',
      1 => 1693731894,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_67eaaf6a7f0f40_07984704 (Smarty_Internal_Template $_smarty_tpl) {
?><div class="_3nc5v"><div class="_20acl js__2jOp21rr"></div><div class="_3wdnw vylv_streamViewHeader_cl js__vylv_streamViewHeader js__2jOp21rr"><div class="_1mXXh"><div class="_2qZlS _2tebH js__2jOp21rr"><a class="Uj8Ox _2tebH" data-pi="<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
" onclick="vy_lvst.displayProduct(event,this);" href="javascript:void(0);"><div class="_2usXX zSIAp js__2jOp21rr"><img class="_2aEMu _2a-mH js__2jOp21rr" src="<?php echo $_smarty_tpl->tpl_vars['image']->value;?>
" /></div></a><div class="_14UCw _2tebH"><div class="OK7VU _2tebH js__2jOp21rr"><div class="_1ONL4"><a href="javascript:void(0);" data-pi="<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
" onclick="vy_lvst.displayProduct(event,this);"><?php echo $_smarty_tpl->tpl_vars['name']->value;?>
</a></div><div class="_2Kh3w"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['by'];?>
&nbsp;<a href="javasript:void(0);"><?php echo $_smarty_tpl->tpl_vars['seller_name']->value;?>
</a></div></div><div class="_1giS_"><div class="sash--horizontal -position-left -color-green -triangle-right -has-pointer-events"><div><i class="vy_lv_onsaleeicontag"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-tag" viewBox="0 0 16 16"><path d="M6 4.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-1 0a.5.5 0 1 0-1 0 .5.5 0 0 0 1 0z"/><path d="M2 1h4.586a1 1 0 0 1 .707.293l7 7a1 1 0 0 1 0 1.414l-4.586 4.586a1 1 0 0 1-1.414 0l-7-7A1 1 0 0 1 1 6.586V2a1 1 0 0 1 1-1zm0 5.586 7 7L13.586 9l-7-7H2v4.586z"/></svg></i><span>On sale</span></div></div><div class="vOQa8"><a class="_1St_B _1AM4s"><span class="_2Quow">$<?php echo $_smarty_tpl->tpl_vars['price']->value;?>
 &nbsp;&bull;&nbsp;<span id="vy_lv_prod_activeid__<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
"><?php echo $_smarty_tpl->tpl_vars['views']->value;?>
 <?php echo $_smarty_tpl->tpl_vars['this']->value->lang['product_views'];?>
</span></span></a></div></div></div></div></div></div></div><?php }
}
