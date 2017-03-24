$(function(){
	var flag=false;	   
	$("#cButton").click( function(){
		if(flag){							  
		$("#cWrap").hide();
		$("#cButton").css("background-position","-30px -120px");
		flag=false;
		}
		else {
		$("#cWrap").show();
		$("#cButton").css("background-position","0 -120px");
		flag=true;
		}
	})
	var menuYloc = $("#customer").offset().top;
	$(window).scroll(function (){ 
		var offsetTop = menuYloc + $(window).scrollTop() +"px";
		$("#customer").animate({top : offsetTop },{ duration:100 , queue:false });
	});
});