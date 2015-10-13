<?php
/**
 * @class  authenticationAdminModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  authenticationAdminModel
 */
class authenticationAdminModel extends authentication 
{
	/**
	 * @brief 회원의 인증번호 가져오기
	 */
	function getAuthenticationAdminNumber()
	{
		$args->member_srl = Context::get('target_srl');
		$output = executeQuery('authentication.getAuthenticationMember', $args);
		if(!$output->toBool()) return;
		$this->add('number', $output->data->clue);
	}
}
/* End of file authentication.admin.model.php */
/* Location: ./modules/authentication/authentication.admin.model.php */
