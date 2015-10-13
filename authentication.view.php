<?php
/**
 * @class  authenticationView
 * @author NURIGO(contact@nurigo.net)
 * @brief  authenticationView
 */
class authenticationView extends authentication 
{
	/**
	 * @brief constructor
	 */
	function init() 
	{
		$oAuthenticationModel = &getModel('authentication');
		$config = $oAuthenticationModel->getModuleConfig();
		$config->agreement = $oAuthenticationModel->_getAgreement();
		if(!$config->skin) $config->skin = "default";
		$this->setTemplatePath($this->module_path."skins/{$config->skin}");
	}

	/**
	 * @breif view kcb result page 
	 */
	function dispAuthenticationKcbResult()
	{
		$oAuthenticationModel = &getModel('authentication');
		$config = $oAuthenticationModel->getModuleConfig();

		$rqstSiteNm	= $_POST["rqst_site_nm"]; // 접속도메인
		$hsCertRqstCausCd =	$_POST["hs_cert_rqst_caus_cd"]; // 인증요청사유코드 2byte  (00:회원가입, 01:성인인증, 02:회원정보수정, 03:비밀번호찾기, 04:상품구매, 99:기타)
		$encInfo = $_POST["encInfo"]; // 인증결과 암호화 데이터
		$WEBPUBKEY = trim($_POST["WEBPUBKEY"]); // KCB서버 공개키
		$WEBSIGNATURE = trim($_POST["WEBSIGNATURE"]); // KCB서버 서명값

		// 파라미터에 대한 유효성여부를 검증한다.
		if(preg_match('~[^0-9a-zA-Z+/=]~', $encInfo, $match)) return $this->stop('check_parameater');
		if(preg_match('~[^0-9a-zA-Z+/=]~', $WEBPUBKEY, $match)) return $this->stop('check_parameater');
		if(preg_match('~[^0-9a-zA-Z+/=]~', $WEBSIGNATURE, $match)) return $this->stop('check_parameater');

		$memId = "V20650000000"; // KCB로부터 부여받은 회원사코드 설정 (12자리)
		//$exe = $this->xiConfig->kcb->module_file;
		$keyPath = "./safecert_$memId.key";
		$logPath = "./files/authentication/logs";
		$endPointUrl = "http://safe.ok-name.co.kr/KcbWebService/OkNameService";// 운영 서버
		$options = "SUL";	// S:인증결과복호화
		
		//run CMD
		$cmd = array($keyPath, $memId, $endPointUrl, $WEBPUBKEY, $WEBSIGNATURE, $encInfo, $logPath, $options);
		$output = NULL;
		$ret = okname($cmd, $output);

		if($ret == 0) 
		{
			$result = explode("\n", $output);
			$retcode = $result[0];

			//인증결과 복호화 성공
			// 인증결과를 확인하여 페이지분기등의 처리를 수행해야한다.
			if ($retcode == "B000") 
			{
				echo ("<script>alert('본인인증성공'); fncOpenerSubmit();</script>");
			}
			else 
			{
				echo ("<script>alert('본인인증실패 : ".$retcode."'); fncOpenerSubmit();</script>");
			}
		}
		else 
		{
			if($ret <=200)
			{
				$retcode=sprintf("B%03d", $ret);
			}
			else
			{
				$retcode=sprintf("S%03d", $ret);
			}

			//인증결과 복호화 실패
			echo ("<script>alert('인증결과복호화 실패 : ".$ret."'); self.close(); </script>");
		}

		//세션에 저장
		$authinfo = array();
		$authinfo["resultCd"] = $result[0]; //처리결과코드
		$authinfo["resultMsg"] = $result[1]; //처리결과메시지
		$authinfo["hsCertSvcTxSeqno"] = $result[2]; //거래일련번호 (sequence처리)
		$authinfo["auth_date"] = $result[3]; //인증일시
		$authinfo["DI"] = $result[4]; //DI
		$authinfo["CI"] = $result[5]; //CI
		$authinfo["user_name"] = $result[7]; //성명
		$authinfo["birthday"] = $result[8]; //생년월일
		$authinfo["age"] = substr(date('Ymd')-$result[8],0,2); //만 나이
		$authinfo["agency"] = $result[11]; //통신사코드
		$authinfo["mobile"] = $result[12]; //휴대폰번호
		$authinfo["ori_sex"] = ($result[9] == 1) ? "M" : "F";
		$authinfo["ori_for"] = ($result[10] == 1) ? "N" : "Y";
		$authinfo["ori_auth"] = "M";

		$args->authentication_srl = getNextSequence();
		$args->country_code = '82';
		$args->clue = $authinfo["mobile"];
		$args->authcode = 'kcb';
		$args->ipaddress = $_SERVER['REMOTE_ADDR'];
		$args->passed = 'Y';
		$output = executeQuery('authentication.insertAuthentication', $args);
		if(!$output->toBool()) return $output;

		// 인증성공
		$_SESSION['authentication_pass'] = 'Y';
		$_SESSION['authentication_srl'] = $args->authentication_srl;

		Context::set('authinfo',$authinfo);
		Context::set('hsCertMsrCd',$hsCertMsrCd);

		$this->setLayoutFile('');
		$this->setTemplateFile('auth_result');
	}
}
/* End of file authentication.view.php */
/* Location: ./modules/authentication/authentication.view.php */
