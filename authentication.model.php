<?php
/**
 * @class  authenticationModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  authenticationModel
 */
class authenticationModel extends authentication 
{
	/**
	 * @brief constructor
	 */
	function init() 
	{
	}

	/**
	 * @brief get module config
	 */
	function getModuleConfig() 
	{
		if (!$GLOBALS['__authentication_config__'])
		{
			$oModuleModel = &getModel('module');
			$config = $oModuleModel->getModuleConfig('authentication');
			if(!$config->skin) $config->skin = 'default';
			if(!$config->digit_number) $config->digit_number = 5;
			if(!$config->country_code) $config->country_code = '82';
			if(!$config->resend_interval) $config->resend_interval = 20;
			if(!$config->day_try_limit) $config->day_try_limit = 10;
			if(!$config->message_content) $config->message_content = '[핸드폰인증] %authcode% ☜  인증번호를 정확히 입력해 주세요';
			if(!$config->number_overlap) $config->number_overlap = 'Y';
			$GLOBALS['__authentication_config__'] = $config;
		}
		return $GLOBALS['__authentication_config__'];
	}

	/**
	 * @brief 인증내역을 authentication_srl로 가져온다
	 */
	function getAuthenticationInfo($authentication_srl)
	{
		$args->authentication_srl = $authentication_srl;
		$output = executeQuery('authentication.getAuthentication', $args);
		if(!$output->toBool()) return;
		return $output->data;
	}

	/**
	 * @brief 회원의 인증정보를 가져온다
	 */
	function getAuthenticationMember($member_srl)
	{
		$args->member_srl = $member_srl;
		$output = executeQuery('authentication.getAuthenticationMember', $args);
		if(!$output->toBool()) return;
		return $output->data;
	}

	/**
	 * @brief 전화번호에 따른 회원의 인증정보를 가져온다
	 */
	function getAuthenticationMemberListByClue($clue)
	{
		$args->clue = $clue;
		$output = executeQueryArray('authentication.getAuthenticationMemberListByClue', $args);
		if(!$output->toBool()) return;
		return $output->data;
	}

	/**
	 * @brief 약관을 가져온다.
	 */
	function _getAgreement()
	{
		$agreement_file = _XE_PATH_.'files/authentication/agreement_' . Context::get('lang_type') . '.txt';
		if(is_readable($agreement_file))
		{
			return FileHandler::readFile($agreement_file);
		}

		$db_info = Context::getDBInfo();
		$agreement_file = _XE_PATH_.'files/authentication/agreement_' . $db_info->lang_type . '.txt';
		if(is_readable($agreement_file))
		{
			return FileHandler::readFile($agreement_file);
		}

		$lang_selected = Context::loadLangSelected();
		foreach($lang_selected as $key => $val)
		{
			$agreement_file = _XE_PATH_.'files/authentication/agreement_' . $key . '.txt';
			if(is_readable($agreement_file))
			{
				return FileHandler::readFile($agreement_file);
			}
		}

		return null;
	}

	/**
	 * @brief 다른 모듈에서 사용하기 위해 index.html을 compile 해서 보내준다.
	 */
	function getContent()
	{
		$oAuthenticationModel = &getModel('authentication');
		$config = $oAuthenticationModel->getModuleConfig();
		Context::set('config', $config);

		$tpl_path = sprintf('%sskins/default/', $this->module_path);
		$tpl_file = 'index.html';

		$oTemplate = TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, $tpl_file);
	}

	/**
	 * @brief get config value
	 * @params $obj : member info object.
	 */
	function getConfigValue(&$obj, $fieldname, $type=null) 
	{
		$return_value = null;
		$config = $this->getModuleConfig();

		// 기본필드에서 확인
		if ($obj->{$fieldname}) {
			$return_value = $obj->{$fieldname};
		}

		// 확장필드에서 확인
		if ($obj->extra_vars) {
			$extra_vars = unserialize($obj->extra_vars);
			if ($extra_vars->{$fieldname}) {
				$return_value = $extra_vars->{$fieldname};
			}
		}
		if ($type=='tel' && is_array($return_value)) {
			$return_value = implode($return_value);
		}

		return $return_value;
	}

	/**
	 * @brief 현재 전송상황을 가져온다
	 */
	function getDelayStatus()
	{
		$oTextmessageModel = &getModel('textmessage');
		$sms = $oTextmessageModel->getCoolSMS();
		$options->count=1;
		$status = $sms->status($options);

		if($status->code || !$status) return NULL; 

		if(gettype($status)=="array")
		{
			if(count($status)>0) return $status[0];
			return NULL;
		}

		return $status;
	}

	/**
	 * @brief 전송상황을 String으로
	 */
	function getDelayStatusString($sms)
	{
		$string = NULL;
		if($sms <= 60) $string = "양호";
		else if($sms > 60 && $sms <= 120) $string = "보통";
		else $string = "정체";

		return $string;
	}

	/**
	 * @brief error_code를 String으로
	 */
	function getErrorMessage($error_code)
	{
		switch($error_code)
		{
			case "InvalidAPIKey":
				$error_message = "문자메시지 모듈의 관리자 설정을 확인해주세요.";
				break;
			case "SignatureDoesNotMatch":
				$error_message = "문자메시지 모듈의 관리자 설정을 확인해주세요.";
				break;
			case "NotEnoughBalance":
				$error_message = "잔액이 부족합니다.";
				break;
			case "InternalError":
				$error_message = "서버오류";
				break;
			default:
				$error_message = "메시지 전송 오류";
				break;
		}
		$error_message = sprintf("%s(%s)", $error_message, $error_code);

		return $error_message;
	}

	/**
	 * @breif kcb 본인인증 필요정보 가져오기
	 * @return resultcode
	 */
	function getKcbMobileData()
	{
		$oAuthenticationModel = &getModel('authentication');
		$config = $oAuthenticationModel->getModuleConfig();

		// kcb설정이 제대로 되어있지 않다면 return resultcode '999'
		if(!$config->kcb_id || !$config->domain) return "999";

		//set Default Variables
		$name = "x"; $birthday = "x"; $gender = "x"; $nation="x"; $telComCd="x"; $telNo="x";
		// 0 = not posted data, no use Filter
		$inTpBit = 0;
		$svcTxSeqno = getNextSequence();
		
		$memId = $config->kcb_id;
		$serverIp = $_SERVER["SERVER_ADDR"]; 			
		$rsv1 = "0"; $rsv2 = "0"; $rsv3 = "0";
		$hsCertMsrCd = "10"; // 요청수단코드 (10:핸드폰)
		$hsCertRqstCausCd = "00"; // 인증요청사유코드 (00:회원가입, 01:성인인증, 02:회원정보수정, 03:비밀번호찾기, 04:상품구매, 99:기타)
		$returnMsg = "x";

		$returnUrl = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?act=dispAuthenticationKcbResult";
		$endPointURL = "http://safe.ok-name.co.kr/KcbWebService/OkNameService"; // is real server
		
		//$exe = "./kcb/php_okname.so";
		$logPath = "./files/authentication/logs";
		if(!FileHandler::makeDir($logPath)) return new Object(-1, 'msg_error');
		
		$siteUrl = "www." . $config->domain; // 회원사 사이트 URL
		$siteDomain = $config->domain; // 회원사 사이트 도메인
		$options = "QUL";

		$cmd = array($svcTxSeqno, $name, $birthday, $gender, $nation, $telComCd,
			$telNo, $rsv1, $rsv2, $rsv3, $returnMsg, $returnUrl, $inTpBit,
			$hsCertMsrCd, $hsCertRqstCausCd, $memId, $serverIp, $siteDomain,
			$endPointURL, $logPath, $options);
		
		//run Module
		$output = NULL;
		$ret = okname($cmd, $output);

		//성공일 경우 변수를 결과에서 얻음
		if ($ret == 0) 
		{
			$result = explode("\n", $output);
			$retcode = $result[0];
			$retmsg  = $result[1];
			$e_rqstData = $result[2];
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
		}

		Context::set('rqst_data',$e_rqstData);
		return sprintf("%03u", $ret);
	}

	/**
	 * @breif kcb error_code를 string으로 변환해서 돌려준다.
	 */
	function getKcbMobileError($code)
	{
		$error_list = array(
		"000" => "정상처리",
		"001" => "해당 주민번호 미존재 오류",
		"002" => "해당 이름 미존재 오류 ",
		"003" => "주민번호 형식 체계 오류",
		"004" => "요청 서버 IP 오류",
		"005" => "요청 서버 도메인 오류",
		"006" => "잔여건수 사용 초과, 충전제사용시 잔액부족.",
		"007" => "제휴가맹점 유효기간 만료",
		"008" => "제휴가맹점 코드 오류",
		"009" => "제휴가맹점 키 오류",
		"010" => "계약되지 않은 서비스 타입",
		"011" => "데이터 복호화 오류 또는 서버OS의 종류/비트가 맞지 않는 경우",
		"012" => "데이터 암호화 오류",
		"013" => "미승인 가맹점",
		"014" => "클라이언트 체크타입 오류",
		"015" => "접근 가능 대역 오류",
		"016" => "명의차단상태",
		"017" => "입력값 오류(이름또는주민번호 Null)",
		"018" => "실명요청 승인 대기 상태",
		"019" => "실명요청 반려 상태",
		"020" => "통신오류(KAIT)",
		"021" => "입력값 오류(조회점포코드 Null)",
		"022" => "입력값 오류(조회점포명 Null)",
		"023" => "입력값 오류(조회점포ID Null)",
		"024" => "조회대상자 구분 코드 오류",
		"025" => "실명 요청사유 코드 오류",
		"026" => "요청 인터페이스 오류",
		"027" => "성인인증일 경우 미성년",
		"028" => "외국인번호 형식 체계 오류",
		"029" => "KAIT 지연",
		"030" => "네트워크 오류 (타임아웃, 웹서비스 연결지연 등)",
		"031" => "요청시도IP차단상태",
		"032" => "사용자 미존재 오류(사망자)",
		"033" => "사용자 미존재 오류(예외자)",
		"035" => "성별코드오류",
		"081" => "이통사DB오류",
		"082" => "이통사SCI통신오류",
		"083" => "이통사DB암호화서버연결실패",
		"084" => "이통사CI/DI연동오류",
		"085" => "이통사CP코드없음",
		"086" => "이통사기타오류",
		"091" => "인증시도횟수초과",
		"092" => "이통사 연동 오류",
		"097" => "서비스거래번호 없음",
		"098" => "서비스거래번호 오류(길이/형식)",
		"099" => "기타오류",
		"100" => "본인인증 처리 실패",
		"101" => "기요청 서비스 거래번호",
		"102" => "선행 요청정보 없음",
		"103" => "서비스 사용 가능일이 아닙니다.",
		"104" => "서비스 사용 중지 상태 입니다.",
		"105" => "서비스 기간이 만료 되었습니다.",
		"106" => "요청사이트도메인 없음",
		"107" => "본인인증수단 없음",
		"108" => "본인인증 요청사유 없음",
		"109" => "타겟ID 없음",
		"110" => "리턴URL 없음",
		"111" => "파라메터체크(본인인증수단)",
		"112" => "파라메터체크(주민번호형식)",
		"113" => "파라메터체크(휴대폰 통신사 정보)",
		"114" => "파라메터체크(휴대폰 번호 앞자리)",
		"115" => "파라메터체크(휴대폰 번호)",
		"116" => "파라메터체크(신용카드번호)",
		"117" => "파라메터체크(신용카드유효기간)",
		"118" => "파라메터체크(신용카드비밀번호)",
		"119" => "공인인증서 없음",
		"120" => "인증번호 재전송 건수 초과",
		"121" => "서비스오류",
		"122" => "DB 오류",
		"123" => "본인인증 취소",
		"124" => "회원사 허용대역 오류",
		"125" => "인증번호 문자길이 오류 ( 80byte 초과시 )",
		"126" => "실행모듈 파일의 권한이 없는 경우",
		"-1" => "실행모듈 파일의 권한이 없는 경우(Windows Server)",
		"127" => "실행모듈 파일이 없거나 패스가 잘못지정된 오류",
		"129" => "인증번호입력시간초과",
		"130" => "인증번호 오류입력건수 초과",
		"131" => "인증번호 불일치",
		"132" => "해당건 미존재",
		"133" => "잘못된 접근 (페이지 리로딩 포함)",
		"135" => "등록되지않은 서비스구분",
		"136" => "인증번호재전송 요청시간이 초과되었습니다.",
		"137" => "SMS발송에 실패했습니다.",
		"138" => "잘못된 DI 정보가 수신되었습니다.",
		"139" => "잘못된 CI 정보가 수신되었습니다.",
		"140" => "CI 검증 실패",
		"141" => "파라메터체크(성명)",
		"142" => "파라메터체크(생년월일)",
		"143" => "파라메터체크(성별)",
		"144" => "파라메터체크(내외국인구분)",
		"145" => "파라메터체크(개인정보동의여부)",
		"146" => "파라메터체크(식별정보동의여부)",
		"147" => "파라메터체크(통신약관동의여부)",
		"148" => "파라메터체크(SMS/LMS구분)",
		"149" => "파라메터체크(SMS 인증번호)",
		"150" => "파라메터체크(원거래 주민번호 상이)",
		"151" => "파라메터체크(원거래 휴대폰번호 상이)",
		"152" => "허용되지 않는 정책의 공인인증서",
		"153" => "공인인증서 유효성 검사 실패",
		"154" => "휴대폰인증보호서비스앱 미설치",
		"155" => "차단회원사",
		"156" => "SMS발송차단회원사",
		"201" => "웹서비스초기화실패",
		"-55" => "웹서비스초기화실패 (WindowsServer)",
		"202" => "서버로부터 키 수신 실패",
		"-54" => "서버로부터 키 수신 실패(WindowsServer)",
		"203" => "클라이언트 키 생성 실패",
		"204" => "암호화/복호화 실패",
		"205" => "실명 인증 서비스 호출 실패",
		"206" => "요청 인터페이스 오류, 실행모듈 파라미터 개수 또는 타입 오류",
		"207" => "EndPointURL 에러",
		"208" => "EndPointURL는 정상이나 입력 파라메터의 포맷오류(순서나 암호화 등)",
		"209" => "프로퍼티 파일 로드 실패",
		"210" => "프로퍼티 파일 저장 실패",
		"211" => "private_key 길이 오류(32바이트)",
		"212" => "public_key 길이 오류(28바이트)",
		"213" => "signature 길이 오류(24바이트)",
		"214" => "필수 파라미터 누락",
		"999" => "KCB 설정이 되있지 않습니다. 관리자페이지에서 설정을 해주세요."
		);
		
		return "KCB 인증모듈 호출 에러 : ".$code."<br />".$error_list[$code];
	}
}
/* End of file authentication.model.php */
/* Location: ./modules/authentication/authentication.model.php */
