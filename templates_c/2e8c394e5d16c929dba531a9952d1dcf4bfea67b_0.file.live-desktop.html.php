<?php
/* Smarty version 3.1.34-dev-7, created on 2025-03-19 17:14:48
  from '/var/www/vhosts/zuitch.com/httpdocs/vy-livestream/layout/live-desktop.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.34-dev-7',
  'unifunc' => 'content_67daed78620fb2_52464685',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '2e8c394e5d16c929dba531a9952d1dcf4bfea67b' => 
    array (
      0 => '/var/www/vhosts/zuitch.com/httpdocs/vy-livestream/layout/live-desktop.html',
      1 => 1693731894,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_67daed78620fb2_52464685 (Smarty_Internal_Template $_smarty_tpl) {
?><section id="vy-livest" class="vylvdesktop js__vylivest_<?php echo $_smarty_tpl->tpl_vars['live_id']->value;?>
 chatopen"><div class="vylv_f3522"><div class="vy-live-entry-cnt"><div style="position: absolute;top:200px;left:0" id="243ushfdiush"></div><div id="vy_lv_productauthor_preview"></div><div class="vy_lv_host_away"><img src="<?php echo $_smarty_tpl->tpl_vars['cover_path']->value;?>
" border="0"/><div class="vy_lv_host_away_text"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['host_is_away'];?>
</div></div><div class="vy_lv_host_ended"><div class="_img" style="background-image:url(<?php echo $_smarty_tpl->tpl_vars['cover_path']->value;?>
);"></div><div class="vy_lv_host_ended_text"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['stream_ended'];?>
</div></div><div class="vy_lv_host_reconnecting"><div class="vy_lv_host_reconnecting_text"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['host_network_problems'];?>
</div></div><div class="vy-live-e1v"><video tabindex="2" mediatype="video" webkit-playsinline="true" playsinline autoplay id="vy_lv_livestream" poster="<?php echo $_smarty_tpl->tpl_vars['cover_path']->value;?>
"></video><div class="vylv-uskur1-DivPlayerBackground ev4k9613"><div class="vylv-1kdnu10-DivPlayerCover ev4k9612"></div><div class="vylv-5gqkmn-DivBgContainer ezkd7x70"><div class="vylv-1apwby8-DivLivePlayerImage ezkd7x71"><div class="vylv-1fxkm4b-DivLivePlayerImageContainer ezkd7x72"><img src="<?php if (strpos($_smarty_tpl->tpl_vars['author']->value['avatar'],'default') !== false) {
echo $_smarty_tpl->tpl_vars['author']->value['avatar'];
} else {
echo $_smarty_tpl->tpl_vars['cover_path']->value;
}?>" class="vylv-urm72h-ImgLivePlayerImage ezkd7x74"></div></div><div class="vylv-y2fd0r-DivLivePlayerCurtain ezkd7x75"></div></div></div><div class="vylv-wx0vpv-DivFeedLivePlayerContainer ev4k9610"><div class="vylv-wepvij-DivLiveRoomBanner e10bhxlw0"><div class="vylv-1s7wqxh-DivUserHoverProfileContainer e19m376d0"><div class="vylv-1env8v6-DivUserProfile e1571njr0"><a href="/livestream/u/<?php echo $_smarty_tpl->tpl_vars['author']->value['id'];?>
" target="_blank" rel="noreferrer noopener" data-e2e="user-profile-avatar-link"><img src="<?php echo $_smarty_tpl->tpl_vars['author']->value['avatar'];?>
" class="vylv-k9sb3-StyledProfileAvatar e1571njr2" style="display: block;"></a><div class="vylv-3z3ses-DivProfileInfo e1571njr1"><div class="vylv-1msvpkx-DivBroadcastTitleWrapper e1571njr4"><a href="/livestream/u/<?php echo $_smarty_tpl->tpl_vars['author']->value['id'];?>
" target="_blank" rel="noreferrer noopener" style="display: block; text-decoration: none;"><div class="vylv-1rv5okv-DivBroadcastTitleContainer e1571njr5"><h2 data-e2e="user-profile-uid" class="vylv-6km8xc-H2UniqueId e1571njr6"><?php echo $_smarty_tpl->tpl_vars['author']->value['fullname'];?>
</h2><h1 data-e2e="user-profile-nickname" class="vylv-1vec0rh-H1Nickname e1571njr7"><?php echo $_smarty_tpl->tpl_vars['author']->value['fullname'];?>
</h1></div></a></div><div class="vylv-vfxonb-DivExtraContainer e1571njr9"><div data-e2e="user-profile-live-title" class="vylv-plu37s-DivBroadcastText e1571njr8"><?php echo substr($_smarty_tpl->tpl_vars['post']->value['postText'],0,50);?>
</div><div class="vylv-181hozs-DivPeopleContainer e1108d8t0"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['live_2people'];?>
<div data-e2e="live-people-count" class="vylv-1p1g5jf-DivPeopleCounter e1108d8t2"><span class="js__vylv_count2_viewers">0</span></div></div></div></div></div></div><div class="vylv-ppntst-DivActionContainer e10bhxlw1"><?php if ($_smarty_tpl->tpl_vars['rows']->value['islivenow'] == 'yes') {?><div style="position: relative; z-index: 2; word-break: keep-all;" class="vy_lv_dsh_llv_vvw"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['live'];?>
</div><div style="position: relative;z-index: 2;word-break: keep-all;margin: 0 4px 0 0px;top: 0; right: 0;display: flex;justify-content: center;align-items: center;" class="vy_lv_dashboard_llv_vvw_tm vy_lv_viewer_dashboard js__au4x2"><div class="vy_lv_dsh_tm js__vy_lv_dsh_tm" style="margin-left:0;padding: 6px 9px;">00:00</div></div><?php }?><i onclick="Wo_SharePostOn(<?php echo $_smarty_tpl->tpl_vars['post']->value['id'];?>
,<?php echo $_smarty_tpl->tpl_vars['author']->value['id'];?>
,'timeline');" data-e2e="share-icon" class="vylv-13l877f-IActionButton ex1fqd93"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['share_post'];?>
</i><i style="display:none;" data-e2e="report-icon" class="vylv-13l877f-IActionButton e10bhxlw2"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['report_post'];?>
</i><div style="position: relative; z-index: 2; word-break: keep-all;margin:0 4px 0 0px;"><?php echo Wo_GetFollowButton($_smarty_tpl->tpl_vars['author']->value['id']);?>
</div><i data-e2e="open-chat-button" class="vylv-1vwmrlo-IChatButton e10bhxlw4"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['open_chat'];?>
</i><div style="position: relative;margin-right: 0; z-index: 2; word-break: keep-all;top: -1px;right: 0;" class="vy-lv-close vylv-13l877f-IActionButton" onclick="vy_lvst.exitFromStream(<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
);"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['close'];?>
</div><i onclick="vy_lvst.toggleBroadcastChat(event);" data-e2e="open-chat-button" class="vylvelment-14z7f4-IChatButton ilivechatbuttonshow e10bhxlw4"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['chat_show'];?>
</i></div></div></div><div class="vy_lv_reactions_floating" id="vy_lv_reactions_floating"></div></div><!-- reactions --><div class="vy_lv_reactions_bt js__vy_lv_reactions_bt"></div></div><?php if ($_smarty_tpl->tpl_vars['rows']->value['islivenow'] == 'yes') {?><div id="divanim32v" class="vylvelment-qftsfk-DivChatRoomAnimationContainer e14c6d575 isopen"><div class="vylvelment-5w1toe-DivChatRoomContainer ex6o5342"><div class="vylvelment-hdaobh-DivChatRoomHeader ex6o5343"><div data-e2e="chat-close-button" onclick="vy_lvst.toggleBroadcastChat(event);" class="vylvelment-1dnj95g-DivChatRoomHeaderIconContainer ilivechatbuttonhide ex6o53410"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['chat_hide'];?>
</div><div class="vylvelment-yiy2y4-DivChatTitle ex6o5341">LIVE chat</div></div><div class="vylvelment-rykcaj-DivChatRoomBody ex6o5344"><div id="vylveelement_commentsv2river" class="vylvelment-1gwk1og-DivChatMessageList ex6o5345"><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['comments']->value, 'comment');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['comment']->value) {
$_smarty_tpl->_assignInScope('user', $_smarty_tpl->tpl_vars['this']->value->lv_getUserDetails($_smarty_tpl->tpl_vars['comment']->value['user_id']));?><div onclick="vy_lvst.commentClick(this,event,<?php echo $_smarty_tpl->tpl_vars['comment']->value['user_id'];?>
);" class="vy_lv_comm_uid_<?php echo $_smarty_tpl->tpl_vars['comment']->value['user_id'];?>
 vylvelment-1nmf5oj-DivChatRoomMessage-StyledChatMessageItem e11g2s300"><div class="vylvelment-1h75rji-DivUserCardClickWrapperProps e1s7ldwo0"><div class="vylvelment-6rwu5g-DivBadgeWrap ex6o5346"><img src="<?php echo $_smarty_tpl->tpl_vars['user']->value['avatar'];?>
" style="display: block;"></div></div><input type="hidden" class="__none js__comment_author_name" value="<?php echo $_smarty_tpl->tpl_vars['user']->value['fullname'];?>
" /><div class="vylvelment-1cik7b1-DivChatMessageContent e11g2s301"><div class="vylvelment-ontg9t-DivUserCardClickWrapperProps e1s7ldwo0"><span class="vylvelment-cklfyg-SpanNickName ex6o5348"><?php echo $_smarty_tpl->tpl_vars['user']->value['fullname'];?>
</span></div><span class="vylvelment-1o9hp7f-SpanChatRoomComment e11g2s307"><?php echo $_smarty_tpl->tpl_vars['comment']->value['text'];?>
</span></div><div class="vylvelment-8dqif4-DivChatMessageMoreIconWrapper e11g2s303"><span class="vylvelment-1mjciu7-SpanChatMessageMore e11g2s302"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['comment_more_opt'];?>
</span></div></div><?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?></div><div id="vylv_realtimeviewers"></div></div><!-- write --><div class="vylvelment-c4al9d-DivCommentContainer e1ciaho84"><div class="vylvelment-19dw2t5-DivLayoutContainer e1ciaho85"><div class="vylvelment-1ac6744-DivInputAreaContainer e1ciaho86"><div class="vylvelment-hdchcw-DivInputEditorContainer e1ciaho87"><div id="vy_lv_txtaddcomment_js2" data-meteor-emoji="true" contenteditable="true" maxlength="150" placeholder="<?php echo $_smarty_tpl->tpl_vars['this']->value->lang['add_comments'];?>
" class="vylvelment-158nhpu-DivEditor e1ciaho81 desktop_v2comments"></div></div></div></div><div onclick="vy_lvst.button_send_comm(event);" id="vylv_sendcom_btn" class="vylvelment-1muuivz-DivPostButton e1ciaho89"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['desktop_openstream_3422'];?>
</div></div></div></div><div style="display:none;" class="vy_lv_entryli" id="vy_lv_comments_sectionstick"><!-- comments --><div class="vy_lv_comments_section_inp_andcmm"><div class="vy_lv_comments-section" id="vy_lv_comments_section"><?php
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
');" placeholder="<?php echo $_smarty_tpl->tpl_vars['this']->value->lang['add_comments'];?>
" /></div></div></div></div><?php }?></section><?php }
}
