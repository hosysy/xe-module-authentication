<?php
/**
 * @class  authenticationMobile
 * @author NURIGO(contact@nurigo.net)
 * @brief  authenticationMobile
 */
require_once(_XE_PATH_.'modules/authentication/authentication.view.php');
class authenticationMobile extends authenticationView
{
	/**
	 * @brief constructor
	 */
	function init() 
	{
		$oAuthenticationModel = &getModel('authentication');
		$config = $oAuthenticationModel->getModuleConfig();
		if(!$config->mskin) $config->mskin = "default";
		$this->setTemplatePath($this->module_path."m.skins/{$config->mskin}");

		$oLayoutModel = &getModel('layout');
		$layout_info = $oLayoutModel->getLayout($config->mlayout_srl);
		if($layout_info)
		{
			$this->module_info->mlayout_srl = $config->mlayout_srl;
			$this->setLayoutPath($layout_info->path);
		}
	}
}
/* End of file authentication.view.php */
/* Location: ./modules/authentication/authentication.view.php */
