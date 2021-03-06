<?php

namespace Ecjia\App\Wechat;

use Ecjia\App\Platform\Models\PlatformCommandModel;
use Ecjia\App\Platform\Plugin\PlatformAbstract;
use Ecjia\App\Platform\Plugin\PlatformPlugin;
use RC_Hook;

class WechatCommand
{
    protected $message;
    
    protected $wechat_id;
    
    protected $wechat_uuid;
    
    public function __construct($message, WechatUUID $wechat_uuid)
    {
        $this->wechat_uuid = $wechat_uuid;
        
        $this->message = $message;
        $this->wechat_id = $wechat_uuid->getWechatID();
    }
    
    /**
     * 执行一个命令
     * @param string $cmd
     */
    public function runCommand($cmd) {
        list($cmd, $keyword) = explode(' ', $cmd);

        //查询$cmd命令是哪个插件的
        $model = PlatformCommandModel::where('account_id', $this->wechat_id)->where('cmd_word', $cmd)->first();
        
        if (!empty($model) && $model->ext_code) {
            $extend_handle = (new PlatformPlugin)->channel($model->ext_code);

            return $this->commandHandler($extend_handle, $model->sub_code, $keyword);
        } else {

            $reply = RC_Hook::apply_filters('wechat_command_empty_reply', null, $cmd, $this);

            if (! is_null($reply)) {
                return $reply;
            }

            return null;
        }
    }

    /**
     * @param PlatformAbstract $handler
     */
    public function commandHandler(PlatformAbstract $extend_handle, $sub_code = null, $keyword = null)
    {
        $extend_handle->setAccount($this->wechat_uuid->getAccount());
        $extend_handle->setMessage($this->message);
        $extend_handle->setSubCodeCommand($sub_code);
        $extend_handle->setStoreId($this->wechat_uuid->getAccount()->getStoreId());
        $extend_handle->setKeyword($keyword);

        if ($this->wechat_uuid->getAccount()->getStoreId() > 0 && $extend_handle->hasSupportTypeMerchant()) {
            $extend_handle->setStoreType(\Ecjia\App\Platform\Plugin\PlatformAbstract::TypeMerchant);
        }
        else if ($this->wechat_uuid->getAccount()->getStoreId() === 0 && $extend_handle->hasSupportTypeAdmin()) {
            $extend_handle->setStoreType(\Ecjia\App\Platform\Plugin\PlatformAbstract::TypeAdmin);
        }

        return $extend_handle->eventReply();
    }
    
    /**
     * 查询$cmd命令是否存在
     * @param string $cmd
     * @return boolean
     */
    public function hasCommand($cmd) 
    {
        $model = PlatformCommandModel::where('account_id', $this->wechat_id)->where('cmd_word', $cmd)->first();
        
        if (!empty($model)) {
            return true;
        } else {
            return false;
        }
    }
    
}