jQuery(function(){
	var remarkReg = /^.{6,25}$/;
	
	
	//提交相关设置
	jQuery('.setting-form').submit(function(e){
		var remark = jQuery('.setting-form textarea[name=tg_1]').val();
		var nickname = jQuery('.setting-form input[name=nickname]').val();
		var remark2 = jQuery('.setting-form textarea[name=tg_2]').val();
		var remark2 = jQuery('.setting-form textarea[name=tg_2]').val();
		
		jQuery.ajax({
            type: 'POST',
            url: '/index.php?m=default&c=erwei&a=shengcheng',
            dataType: 'json',
            data: {'nickname': nickname,'remark':remark,'remark2':remark2},
            error:function(){},
            success:function(rData){
            	jQuery('.setting-form button[type=submit]').removeAttr('disabled');
            	
                if(rData['code'] == 1 || rData['code'] == '1')
                {
                	jQuery('.create-form').find('.alert').addClass('hide').hide().text('');
                	
                	jQuery('.create-form').find('input[name=nickname]').val(nickname);
                	jQuery('.create-form').find('input[name=remark]').val(remark);
                	jQuery('.create-form').find('input[name=is_download]').val(0);
                	
                	jQuery('.setting-form').addClass('hide').hide();
                	jQuery('.create-form').removeClass('hide').show();
                	
                	jQuery('.create-step li').not(jQuery('.create-step li').eq(1)).removeClass('active');
                	jQuery('.create-step li').eq(1).addClass('active');
                	
                	createQrcode();
                }
                else
                {
                	jQuery('.create-form').find('.alert').removeClass('hide').show().text(rData['msg']);
                	
                	jQuery('.create-form').find('input[name=nickname]').val('');
                	jQuery('.create-form').find('input[name=remark]').val('');
                	jQuery('.create-form').find('input[name=is_download]').val(0);
                	
                	jQuery('.create-form .create-wrapper').addClass('hide').hide();
                	jQuery('.create-form .create-wrapper').find('img.img-qrcode').attr({'src':''});
                	
                	jQuery('.create-form').addClass('hide').hide();
                	jQuery('.setting-form').removeClass('hide').show();
                	
                	jQuery('.create-step li').not(jQuery('.create-step li').eq(0)).removeClass('active');
                	jQuery('.create-step li').eq(0).addClass('active');
                }
            }
        });
	});
	
	
	//返回
	jQuery('.create-form .btn-operate-back').bind('click tap taphold swipe', function(){
		jQuery('.create-form').find('input[name=nickname]').val('');
    	jQuery('.create-form').find('input[name=remark]').val('');
    	
		jQuery('.create-form .create-wrapper').addClass('hide').hide();
    	jQuery('.create-form .create-wrapper').find('img.img-qrcode').attr({'src':''});
    	
    	jQuery('.create-form').addClass('hide').hide();
    	jQuery('.setting-form').removeClass('hide').show();
    	
    	jQuery('.create-step li').not(jQuery('.create-step li').eq(0)).removeClass('active');
    	jQuery('.create-step li').eq(0).addClass('active');
	});
	
	//下载
	jQuery('.create-form').submit(function(){
    	jQuery('.create-form').find('input[name=is_download]').val(1);
    	return true;
	});
	
});

/**
 * 创建二维码，预览
 */
function createQrcode()
{
	jQuery('.create-form').find('input[name=is_download]').val(0);
	jQuery('.create-form .create-wrapper').find('img.img-qrcode').attr({'src':'/code/qrcode?'+jQuery('.create-form').serialize()});
	
	jQuery('.create-form').find('.alert').removeClass('alert-success hide').addClass('alert-info').text('二维码生成过程有点缓慢，请耐心等待！').show();
	
	jQuery('.create-form .create-wrapper').find('img.img-qrcode').load(function(){
		jQuery('.create-form').find('.alert').removeClass('alert-info hide').addClass('alert-success').text('您的推广二维码已经生成成功，请长按二维码进行保存和分享！').show();
	});
	jQuery('.create-form .create-wrapper').removeClass('hide').show();
}
