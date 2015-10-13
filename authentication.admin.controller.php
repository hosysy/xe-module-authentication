<?php
/**
 * @class  authenticationAdminController
 * @author NURIGO(contact@nurigo.net)
 * @brief  authenticationAdminController
 */
class authenticationAdminController extends authentication 
{
	/**
	 * @brief constructor
	 */
	function init() 
	{
	}

	/**
	 * @breif kcb error_code를 string으로 변환해서 돌려준다.
	 */
	function procAuthenticationAdminConfig()
	{
		$args = Context::getRequestVars();
		if(!trim(strip_tags($args->agreement)))
		{
			$agreement_file = _XE_PATH_.'files/authentication/agreement_' . Context::get('lang_type') . '.txt';
			FileHandler::removeFile($agreement_file);
			$args->agreement = NULL;
		}

		// check agreement value exist
		if($args->agreement)
		{
			$agreement_file = _XE_PATH_.'files/authentication/agreement_' . Context::get('lang_type') . '.txt';
			$output = FileHandler::writeFile($agreement_file, $args->agreement);

			unset($args->agreement);
		}

		if(!$args->sender_no) $args->sender_no = NULL;
		if(!$args->message_content) $args->message_content = NULL;
		if(!$args->list) $args->list = NULL;
		if(!$args->cellphone_fieldname) $args->cellphone_fieldname = NULL;

		// save module configuration.
		$oModuleController = getController('module');
		$output = $oModuleController->updateModuleConfig('authentication', $args);

		$this->setMessage('success_saved');

		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAuthenticationAdminConfig');
		$this->setRedirectUrl($redirectUrl);
	}

	/**
	 * @brief 인증모듈 디자인 설정 저장
	 */
	function procAuthenticationAdminDesignConfig()
	{
		$args = Context::getRequestVars();
		$oModuleController = getController('module');

		if(!$args->width) $args->width = NULL;

		$output = $oModuleController->updateModuleConfig('authentication', $args);

		$this->setMessage('success_saved');

		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAuthenticationAdminDesign');
		$this->setRedirectUrl($redirectUrl);
	}

	/**
	 * @breif 인증받은 회원 정보 변경
	 */
	function setAuthenticationMemberUpdate($args)
	{
		if(!$args->member_srl) return new Object(-1, 'member_srl is empty');
		$output = executeQuery('authentication.updateAuthenticationMember', $args);
		return $output;
	}
}
/* End of file authentication.admin.controller.php */
/* Location: ./modules/authentication/authentication.admin.controller.php */
