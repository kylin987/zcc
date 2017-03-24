/**
 * 上传头像
 */
function uploadUserAvatar() {
	jQuery('#avatarModal').modal('show');
}

jQuery(window).load(function() {
	var pageUrl = window.location.href;
	jQuery('#btnNew').bind('click', function() {	
		//alert("张宁");																	 
		jQuery('input#file').click();
	});

	var options = {
		thumbBox : '.thumbBox',
		spinner : '.spinner',
		imgSrc : '',
	}
	var cropper = jQuery('.imageBox').cropbox(options);
	var submit = false;
	//上传新头像
	jQuery('#file').on('change', function() {						  
		var reader = new FileReader();
		reader.onload = function(e) {
			options.imgSrc = e.target.result;
			cropper = jQuery('.imageBox').cropbox(options);
		}
		reader.readAsDataURL(this.files[0]);
		this.files = [];
		if(cropper.image.src!==pageUrl)
		{
			jQuery('input[name=avatar_base64]').val(cropper.getDataURL());
		}
	});
	//裁切
	jQuery('#btnCrop').on('click', function() {
		var img = cropper.getDataURL();
		jQuery('.cropped').append('<img src="' + img + '">');
		if(cropper.image.src!==pageUrl)
		{
			jQuery('input[name=avatar_base64]').val(cropper.getDataURL());
		}
	});
	//缩放
	jQuery('#btnZoomIn').on('click', function() {
		cropper.zoomIn();
		if(cropper.image.src!==pageUrl)
		{
			jQuery('input[name=avatar_base64]').val(cropper.getDataURL());
		}
	});
	//缩放
	jQuery('#btnZoomOut').on('click', function() {
		cropper.zoomOut();
		if(cropper.image.src!==pageUrl)
		{
			jQuery('input[name=avatar_base64]').val(cropper.getDataURL());
		}
	});
	//提交
	jQuery('.avatar-form').submit(function(e) {
		e.preventDefault();
		e.stopPropagation();

		if(cropper.image.src!==pageUrl)
		{
			jQuery('input[name=avatar_base64]').val(cropper.getDataURL());
		}
		else
		{
			jQuery('.avatar-form .alert').addClass('alert-danger').removeClass('alert-success hide').text('请您上传新头像！').show();
			setTimeout
			(
				function(){				
					jQuery('.avatar-form .alert').addClass('hide').removeClass('alert-success alert-danger').text('').hide();
				}, 3000
			);
			return false;
		}
		jQuery.ajax({
            type: 'POST',
            url: 'index.php?m=default&c=user&a=update_headimg',
            dataType: 'json',
            data: jQuery('.avatar-form').serialize(),
            error:function(){},
            success:function(rData){
               if(rData['code']==1 || rData['code']=='1')
               {
            	   jQuery('.avatar-form .alert').addClass('alert-success').removeClass('alert-danger hide').text(rData['msg']).show();
            	   jQuery('body').find('.user-avatar').find('img').attr({'src': rData['img_m']});
               }
               else
               {
            	   jQuery('.avatar-form .alert').addClass('alert-danger').removeClass('alert-success hide').text(rData['msg']).show();
               }
               setTimeout
	   			(
	   				function(){				
	   					jQuery('.avatar-form .alert').addClass('hide').removeClass('alert-success alert-danger').text('').hide();
	   					jQuery('#avatarModal').modal('hide');
	   				}, 5000
	   			);
            }
        });
		
	});

});