<?php

namespace QQLogin\HtmlForm;

/**
 * QQ按钮的样式
 */
class HTMLQQLoginButtonField extends \HTMLSubmitField
{
	public function getInputHTML($value)
	{
		$this->addQQButtonStyleModule();
		return parent::getInputHTML($value);
	}

	/**
	 *  将所需的样式模块添加到OutputPage对象中
	 *
	 * @param string $target Defines which style module should be added (vform, ooui)
	 */
	private function addQQButtonStyleModule($target = "vform")
	{
		// if ($this->mParent instanceof HTMLForm) {
		// 	$out = $this->mParent->getOutput();
		// } else {
		// 	$out = \RequestContext::getMain()->getOutput();
		// }
		// $out->addModuleStyles('ext.QQLogin.userlogincreate.style');
	}
}
