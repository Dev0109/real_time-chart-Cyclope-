$(function(){
	$(".zebra > div").each(function(i){ 
		var $this = $(this);
		if(i %2 ==0){
			return;
		}else if($this.hasClass("buttons")){
			return;
		}		
 		$this.addClass("even");
		$this = null;	
	}).find(":input").each(function(){
		var $this = $(this);
			if($this.attr('tagName') == 'SELECT'){
				return;
			}else if($this.attr('tagName') == 'INPUT'){
				if($this.attr('type') == "text" || $this.attr('type') == "password"){
					$this.focus(function(){
						$this.parent().parent().addClass("active");
					}).blur(function(){
						$this.parent().parent().removeClass("active").removeClass("field_error");
					});
				}
			}else if($this.attr('tagName') == 'TEXTAREA'){
				if($this.parent().attr("tagName") == "DIV"){
					$this.focus(function(){
						$this.parent().addClass("active");
					}).blur(function(){
						$this.parent().removeClass("active").removeClass("field_error");
					});	
					return;
				}
				$this.focus(function(){
					$this.parent().parent().addClass("active");
				}).blur(function(){
					$this.parent().parent().removeClass("active").removeClass("field_error");
				});
			}
	});	
});