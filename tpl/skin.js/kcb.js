//약관세션체크
function checkAgree(next_func){
	if(jQuery("#accept_agreement").is(":checked") == false) {
		alert('개인정보수집에 동의해 주세요.');
	   	return false;
	}

	var popupWindow = window.open( "", "auth_popup", "left=200, top=100, status=0, width=450, height=550" );
	return eval(next_func+"(popupWindow)");
}

// kcb인증창 띄우기
function jsSubmit(popupWindow){
	var okname_frm = document.okname_frm;
	if(!popupWindow){
		window.open("", "auth_popup", "width=432,height=560,scrollbar=yes");
	}
	window.name = "";
	document.auth_frm.target = "auth_popup";
	document.auth_frm.action = "https://safe.ok-name.co.kr/CommonSvl";
	document.auth_frm.method = "post";
	document.auth_frm.submit();
}
