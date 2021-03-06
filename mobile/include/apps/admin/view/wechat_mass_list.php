{include file="pageheader"}
<div class="container-fluid" style="padding:0">
  <div class="row" style="margin:0">
    <div class="col-md-2 col-sm-2 col-lg-1" style="padding-right:0;">{include file="wechat_left_menu"}</div>
    <div class="col-md-10 col-sm-10 col-lg-11" style="padding-right:0;">
      <div class="panel panel-default">
        <div class="panel-heading"><a href="{url('mass_message')}" class="btn btn-default">群发信息</a><a href="{url('mass_list')}" class="btn btn-primary" style="margin-left: 5px;">发送记录</a></div>
        <div class="panel-body bg-danger">
            请注意，只有已经发送成功的消息才能删除删除消息只是将消息的图文详情页失效，已经收到的用户，还是能在其本地看到消息卡片。 另外，删除群发消息只能删除图文消息和视频消息，其他类型的消息一经发送，无法删除。 
        </div>
        <ul class="list-group">
        {loop $list $val}
            <li class="list-group-item" style="overflow:hidden;">
                <div class="col-md-1"><img src="{$val['artinfo']['file']}" width="80" height="80" /></div>
                <div class="col-md-3">
                    <h5>[{$val['artinfo']['type']}]{$val['artinfo']['title']}</h5>
                    <p class="text-muted">{msubstr($val['artinfo']['content'], 60)}</p>
                </div>
                <div class="col-md-3">
                    <p>{$val['status']}</p>
                    <p>发送人数：{$val['totalcount']}人</p>
                    <p class="help-block">排除发送时过滤、用户拒收、接收已达到4条的用户数</p>
                    <p>成功人数：{$val['sentcount']}人</p>
                    <p>失败人数：{$val['errorcount']}人</p>
                </div>
                <div class="col-md-3">{date('Y年m月d日', $val['send_time'])}</div>
                <div class="col-md-2"><a href="javascript:if(confirm('{$lang['confirm_delete']}')){window.location.href='{url('mass_del', array('id'=>$val['id']))}'};" class="btn btn-default">删除</a></div>
                
            </li>
        {/loop}
        </ul>
      </div>
      {include file="pageview"}
    </div>
  </div>
</div>
{include file="pagefooter"}