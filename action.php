<?php

use dokuwiki\plugin\oauth\Adapter;
use dokuwiki\plugin\oauth\Exception;
use dokuwiki\plugin\oauthdiscordserver\Discord;

/**
 * Service Implementation for oAuth Doorkeeper authentication
 */
class action_plugin_oauthdiscordserver extends Adapter
{
    /**
     * @inheritdoc
     */
    public function registerServiceClass()
    {
        return Discord::class;
    }

    /**
     * @inheritDoc
     */
    public function getUser()
    {
        $oauth = $this->getOAuthService();
        $data = array();

        $requestURL = 'https://discord.com/api/users/@me/guilds';
        $result = json_decode($oauth->request($requestURL), true);
        if ($result === NULL) throw new Exception("No response received for server list");
        
        $inServer = false;
        $serverID = $this->getConf('serverID');
        foreach ($result as $server) {
            if ($server['id'] == $serverID) $inServer = true;
        }
        
        if (!$inServer) throw new Exception('Not a member of the correct server');
        
        $requestURL .= '/' . $serverID . '/member';
        $result = json_decode($oauth->request($requestURL), true);
        
        if (!$result || !$result['user']) throw new Exception("No response received for server membership");
        
        $roleID = $this->getConf('roleID');
        if ($roleID) {
            $inRole = false;
            foreach ($result['roles'] as $role) {
                if ($role == $roleID) $inRole = true;
            }
            if (!$inRole) throw new Exception("User doesn't have the correct role");
        }
        
        // prefer server nickname, if none, use user global name, if none, use username
        $prefs = [$result['nick'], $result['user']['global_name'], $result['user']['username']];
        $i = 0;
        while (!($prefs[$i] || $prefs[$i] === "0")) $i++;
        $data['user'] = $prefs[$i];
        $data['name'] = $prefs[$i];
        $data['mail'] = $result['user']['id'] . "@discord.com";
        
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function getScopes()
    {
        return [Discord::SCOPE_MEMBER, Discord::SCOPE_SERVERS];
    }

    /**
     * @inheritDoc
     */
    public function getLabel()
    {
        return 'Discord';
    }

    /**
     * @inheritDoc
     */
    public function getColor()
    {
        return '#7289da';
    }
}
