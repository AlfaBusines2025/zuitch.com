<?php
/* Smarty version 3.1.34-dev-7, created on 2025-03-31 17:06:32
  from '/var/www/vhosts/zuitch.com/httpdocs/vy-livestream/layout/product-template-full.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.34-dev-7',
  'unifunc' => 'content_67eaaf7831ccb5_82294154',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '60cd9f4c576bfa0b67d4f0a7686bfa35b231d790' => 
    array (
      0 => '/var/www/vhosts/zuitch.com/httpdocs/vy-livestream/layout/product-template-full.html',
      1 => 1695373030,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:./popups/fullsizemodal/player-lightbox.html' => 1,
    'file:./popups/fullsizemodal/comments.html' => 2,
  ),
),false)) {
function content_67eaaf7831ccb5_82294154 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_assignInScope('broadcast', $_smarty_tpl->tpl_vars['this']->value->getBroadcastData($_smarty_tpl->tpl_vars['r']->value['post_id']));
$_smarty_tpl->_assignInScope('df_price', $_smarty_tpl->tpl_vars['r']->value['price']);
if ($_smarty_tpl->tpl_vars['r']->value['discount_price']) {
$_smarty_tpl->_assignInScope('df_price', $_smarty_tpl->tpl_vars['r']->value['discount_price']);
}
if (!(strpos($_smarty_tpl->tpl_vars['r']->value['price'],".") !== false)) {
$_smarty_tpl->_assignInScope('df_price', ((string)$_smarty_tpl->tpl_vars['df_price']->value).".00");
}?><div id="vy_lv_product_loading_details"></div><div class="vylvproducfull231Ub__2" id="vy_lv_product_cntload" style="display:none;"><div class="vy_prod_full_details span_643245 col2121"><div class="vy_lv_proddesktop_header"><div class="_1inYX"><?php echo $_smarty_tpl->tpl_vars['categ_full_text']->value;?>
</div><div class="_3ZZ7s"><?php echo $_smarty_tpl->tpl_vars['r']->value['name'];?>
</div></div><ul class="vbreio32"><?php if ($_smarty_tpl->tpl_vars['r']->value['active']) {?><li class="vtyr2_instock"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['product_in_stock'];?>
<span class="iqos2-Gc-b33">&nbsp;&bull;&nbsp;<span id="vyprodfullszieunit"><?php echo $_smarty_tpl->tpl_vars['r']->value['units'];?>
</span>&nbsp;<?php echo $_smarty_tpl->tpl_vars['this']->value->lang['product_units'];?>
</span></li><?php } else { ?><li class="vtyr2_instock _out"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['product_out_of_stock'];?>
</li><?php }
if (!empty($_smarty_tpl->tpl_vars['r']->value['location'])) {
$_smarty_tpl->_assignInScope('prod_location_data', $_smarty_tpl->tpl_vars['this']->value->getProductLocation($_smarty_tpl->tpl_vars['r']->value['location']));
if (!$_smarty_tpl->tpl_vars['prod_location_data']->value['empty']) {?><li class="vyew214_loc"><span class="dsf2222_DgD"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['product_view_location'];?>
:&nbsp;<span class="vy_prodlocationcountryf14__DQQ"><?php echo $_smarty_tpl->tpl_vars['prod_location_data']->value['data']['continent_name'];?>
&nbsp;&rarr;&nbsp;<i class="flag-icon flag-icon-<?php echo strtolower($_smarty_tpl->tpl_vars['prod_location_data']->value['data']['country_code']);?>
 mr-1"></i><?php echo $_smarty_tpl->tpl_vars['prod_location_data']->value['data']['country_name'];?>
</span></span></li><?php }
}?></ul><div class="_2BbPd"><div><?php if ($_smarty_tpl->tpl_vars['r']->value['discount_price']) {?><div class="_8RGRb">$<?php echo $_smarty_tpl->tpl_vars['r']->value['discount_price'];
if (!(strpos($_smarty_tpl->tpl_vars['r']->value['discount_price'],".") !== false)) {?>.00<?php }?><div class="F-YKM">$<?php echo $_smarty_tpl->tpl_vars['r']->value['price'];
if (!(strpos($_smarty_tpl->tpl_vars['r']->value['price'],".") !== false)) {?>.00<?php }?></div></div><?php } else { ?><div class="_8RGRb2">$<?php echo $_smarty_tpl->tpl_vars['r']->value['price'];
if (!(strpos($_smarty_tpl->tpl_vars['r']->value['price'],".") !== false)) {?>.00<?php }?></div><?php }
if ($_smarty_tpl->tpl_vars['seller']->value['id'] != $_smarty_tpl->tpl_vars['this']->value->userid) {?><div class="vylvprodaddtocartglbtn"><div class="vy_prod_addtocart"><div href="javascript:void(0);" <?php if (!$_smarty_tpl->tpl_vars['this']->value->productIsInCart($_smarty_tpl->tpl_vars['r']->value['pid'])) {?>style="display: none;"<?php }?> id="vy_lv_fullsizemodalproduct_addedtocart_1343" class="cd-add-to-cart _remove"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart-check-fill" viewBox="0 0 16 16"><path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1H.5zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-1.646-7.646-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L8 8.293l2.646-2.647a.5.5 0 0 1 .708.708z"/></svg><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['product_exist_in_your_cart'];?>
</div><a href="javascript:void(0);" id="vy_lv_fullsizemodalproduct_addtocart_1343" <?php if ($_smarty_tpl->tpl_vars['this']->value->productIsInCart($_smarty_tpl->tpl_vars['r']->value['pid'])) {?>style="display: none;"<?php }?> class="cd-add-to-cart js-cd-add-to-cart" units="<?php echo $_smarty_tpl->tpl_vars['r']->value['curunits'];?>
" data-productmaxunits="<?php echo $_smarty_tpl->tpl_vars['r']->value['maxunits'];?>
" data-productimg="<?php echo $_smarty_tpl->tpl_vars['image']->value;?>
" data-productid="<?php echo $_smarty_tpl->tpl_vars['r']->value['pid'];?>
" data-productname="<?php echo $_smarty_tpl->tpl_vars['r']->value['name'];?>
" data-price="<?php echo $_smarty_tpl->tpl_vars['df_price']->value;?>
" onclick="vy_lvst.exitFromStream(<?php echo $_smarty_tpl->tpl_vars['r']->value['post_id'];?>
);$('#vy_lv_fullsizemodalclose').trigger('click');AddProductToCart(this,'<?php echo $_smarty_tpl->tpl_vars['r']->value['pid'];?>
','add');"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart-plus-fill" viewBox="0 0 16 16"><path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1H.5zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM9 5.5V7h1.5a.5.5 0 0 1 0 1H9v1.5a.5.5 0 0 1-1 0V8H6.5a.5.5 0 0 1 0-1H8V5.5a.5.5 0 0 1 1 0z"/></svg><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['product_add_to_cart'];?>
</a></div></div><?php }?></div><div id="vy_lv_prod_full_description" class="_2qA7f <?php if (strlen($_smarty_tpl->tpl_vars['r']->value['descr']) >= 180) {?>maxtxt<?php }?>"><div id="vy_lv_prod_full_descriptionWrapper"><span class=""><?php echo $_smarty_tpl->tpl_vars['r']->value['descr'];?>
</span></div></div><?php if (strlen($_smarty_tpl->tpl_vars['r']->value['descr']) >= 180) {?><div onclick="toggleProductDescription(event,1);" id="vy_lv_product_read_more" class="_1VKoz"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['product_read_more'];?>
 <i class="fa fa-chevron-down GSSIm" aria-hidden="true"></i></div><div onclick="toggleProductDescription(event);" style="display: none;" id="vy_lv_product_read_less" class="_1VKoz"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['product_read_less'];?>
 <i class="fa fa-chevron-up GSSIm" aria-hidden="true"></i></div><?php }?></div><?php if ($_smarty_tpl->tpl_vars['broadcast']->value['ended'] == 'yes') {?><div class="_1u9C3"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['product_as_seen_on'];?>
</div><div class="js__v3320ap_DdAl"><div class="_1RwgS"><div id="video-row" class="HLTIe"><a class="_39UgG" id="vy_lv-prod_playerlbghx" href="javascript:void(0);" style="width: calc(31.3333%); padding: calc(31.3333%) 0px 0px;"><video style="background-color: #000;" class="_2Af14" poster="<?php echo $_smarty_tpl->tpl_vars['broadcast']->value['full_cover_path'];?>
" autoplay muted playsinline loop><source src="<?php echo $_smarty_tpl->tpl_vars['broadcast']->value['full_file_path'];?>
" type="video/mp4">Your browser does not support the video tag.</video><div class="GQYit"><i><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-play-circle-fill" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM6.79 5.093A.5.5 0 0 0 6 5.5v5a.5.5 0 0 0 .79.407l3.5-2.5a.5.5 0 0 0 0-.814l-3.5-2.5z"/></svg></i></div></a></div></div></div><?php $_smarty_tpl->_subTemplateRender("file:./popups/fullsizemodal/player-lightbox.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}?><div class="vyprod_F331x2WGaBB"><div class="vyprod_F331x2WGa"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['product_shipping_continents'];?>
</div><div class="vy_lv_continentsshipping_sect"><?php echo $_smarty_tpl->tpl_vars['this']->value->productShippingContinents($_smarty_tpl->tpl_vars['r']->value['shipping_countries']);?>
</div></div><?php if ($_smarty_tpl->tpl_vars['seller']->value['id'] != $_smarty_tpl->tpl_vars['this']->value->userid) {?><div class="vyprod_F331x2WGaBB"><div class="vyprod_F331x2WGa"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['product_contact_number'];?>
</div><div class="vy_lv_continentcontact_sect"><div><div class="vy_lv_contact_email"><a href="mailto:<?php echo $_smarty_tpl->tpl_vars['seller']->value['email'];?>
?subject=<?php echo $_smarty_tpl->tpl_vars['r']->value['name'];?>
"><i class="fa fa-inbox" aria-hidden="true"></i><span><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['product_contact_email'];?>
</span></a></div><span>&nbsp;&bull;&nbsp;</span><div class="vy_lv_phone_number_contact_productcls" id="vy_lv_phone_number_contact_product"><A href="tel:<?php echo $_smarty_tpl->tpl_vars['r']->value['contact'];?>
"><span><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['product_contact_phone'];?>
</span></A><a class="vy_lv_prod_sellershownmb" onclick="Swal.fire('<?php echo $_smarty_tpl->tpl_vars['r']->value['contact'];?>
','','info');" href="javascript:void(0);"><span>(<?php echo $_smarty_tpl->tpl_vars['this']->value->lang['show_seller_phonenumber'];?>
)</span></a></div></div></div></div><?php }
if ($_smarty_tpl->tpl_vars['broadcast']->value['ended'] == 'yes') {?><div id="vylv_waooID002"><?php $_smarty_tpl->_subTemplateRender("file:./popups/fullsizemodal/comments.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?></div><?php }?><div class="vyprod_F331x2WGaBB"><div class="vy_prod_fullfooter_by"><div class="vyproduB21iskkk_111"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['product_onsale_by'];?>
&nbsp;<a target="_blank" href="livestream/u/<?php echo $_smarty_tpl->tpl_vars['seller']->value['id'];?>
"><img src="<?php echo $_smarty_tpl->tpl_vars['seller']->value['avatar'];?>
" /><span><?php echo $_smarty_tpl->tpl_vars['seller']->value['fullname'];?>
</span></a></div></div></div></div><div class="vylvproduct_full_slider span_235325 col2121"><div class="vy_lv_prod_mob_header"><div class="_1inYX"><?php echo $_smarty_tpl->tpl_vars['categ_full_text']->value;?>
</div><div class="_3ZZ7s"><?php echo $_smarty_tpl->tpl_vars['r']->value['name'];?>
</div></div><div id="rs_simplegallery" class="royalSlider rsDefault vy_rs_productslider"><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['files']->value, 'file');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['file']->value) {
if (substr($_smarty_tpl->tpl_vars['file']->value,strrpos($_smarty_tpl->tpl_vars['file']->value,'.')+1) == 'mp4') {?><a class="rsImg" data-rsvideo="<?php echo $_smarty_tpl->tpl_vars['this']->value->getProductFilesPath($_smarty_tpl->tpl_vars['r']->value['user_id']);
echo $_smarty_tpl->tpl_vars['file']->value;?>
" href="<?php echo $_smarty_tpl->tpl_vars['this']->value->getProductFilesPath($_smarty_tpl->tpl_vars['r']->value['user_id'],1);?>
/<?php echo basename($_smarty_tpl->tpl_vars['file']->value,'mp4');?>
jpg"><?php echo $_smarty_tpl->tpl_vars['r']->value['name'];?>
<img width="96" height="72" class="rsTmb" src="<?php echo $_smarty_tpl->tpl_vars['this']->value->getProductFilesPath($_smarty_tpl->tpl_vars['r']->value['user_id'],1);?>
/<?php echo basename($_smarty_tpl->tpl_vars['file']->value,'mp4');?>
jpg" /></a><?php } else { ?><a class="rsImg" data-rsbigimg="<?php echo $_smarty_tpl->tpl_vars['this']->value->getProductFilesPath($_smarty_tpl->tpl_vars['r']->value['user_id']);
echo $_smarty_tpl->tpl_vars['file']->value;?>
" href="<?php echo $_smarty_tpl->tpl_vars['this']->value->getProductFilesPath($_smarty_tpl->tpl_vars['r']->value['user_id']);
echo $_smarty_tpl->tpl_vars['file']->value;?>
"><?php echo $_smarty_tpl->tpl_vars['r']->value['name'];?>
<img width="96" height="72" class="rsTmb" src="<?php echo $_smarty_tpl->tpl_vars['this']->value->getProductFilesPath($_smarty_tpl->tpl_vars['r']->value['user_id']);?>
thumbnail/<?php echo $_smarty_tpl->tpl_vars['file']->value;?>
" /></a><?php }
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?></div><?php if ($_smarty_tpl->tpl_vars['seller']->value['id'] == $_smarty_tpl->tpl_vars['this']->value->userid) {?><button onclick="vy_lvst.changeProductDefaultImage(event,<?php echo $_smarty_tpl->tpl_vars['r']->value['id'];?>
);" id="v34ooal__dD2GfAF" class="viII110d_DD"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['set_product_cover'];?>
</button><?php }
if ($_smarty_tpl->tpl_vars['broadcast']->value['ended'] == 'yes') {?><div id="vylv_waooID001"><?php $_smarty_tpl->_subTemplateRender("file:./popups/fullsizemodal/comments.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?></div><?php }
echo '<script'; ?>
>
 __j("#vy_lv_phone_number_contact_product").intlTelInput({
    autoHideDialCode:false,
    nationalMode: false
    });
function showCountriesOfContinent(ev,el){
ev.preventDefault();
ev.stopImmediatePropagation();

let target = __j(el.rel), i = __j(el).find('i');      

__j('.vylvproducontin2fFhP2__D1').not(target).slideUp();
target.slideToggle();

if(i.hasClass('fa-chevron-down')){
__j('.GSSIm2:not(.fa-chevron-down)').addClass('fa-chevron-down');
i.switchClass("fa-chevron-down", "fa-chevron-up");
} else {
__j('.GSSIm2:is(.fa-chevron-up)').addClass('fa-chevron-up');
i.switchClass("fa-chevron-up", "fa-chevron-down");
}

 

}
 
function toggleProductDescription(e,expand){

const descr = __j('#vy_lv_prod_full_description');
const read_more = __j('#vy_lv_product_read_more');
const read_less = __j('#vy_lv_product_read_less');
if(expand){

read_more.hide();
read_less.show();
descr.addClass('_1EOaj').scrollTop(0);

} else {
read_more.show();
read_less.hide();
descr.removeClass('_1EOaj').scrollTop(0);
}

}
<?php echo '</script'; ?>
>
<style>
#rs_simplegallery {
  width: 100%;
  -webkit-user-select: none;
  -moz-user-select: none;  
  user-select: none;
}

.rsDefault.vy_rs_productslider, .rsDefault.vy_rs_productslider .rsOverflow, .rsDefault.vy_rs_productslider .rsSlide, .rsDefault.vy_rs_productslider .rsVideoFrameHolder, .rsDefault.vy_rs_productslider .rsThumbs{

	background: #f9f9f9;
}

.rsDefault.vy_rs_productslider .rsThumb img {
 
    object-fit: contain;
    background-color: #2f2f2f;
}
.rsDefault.vy_rs_productslider .rsThumb.rsNavSelected {
    background: transparent;
}
.rsDefault.vy_rs_productslider .rsThumb img {
    opacity: 0.4;
    filter: alpha(opacity=40);
}
.rsDefault.vy_rs_productslider .rsThumb.rsNavSelected img {
    opacity: 1;
    filter: alpha(opacity=100);
}

</style>

</div>

<?php }
}
