<?php

namespace App\Classes\Worker;

use App\Classes\Library\Security;

class API
{
    public string $query;
    public array $data;

    public const TEMPLATE_INACTIVE_PLAYER = 51;
    public const TEMPLATE_SPONSORSHIP = 52;

    public function __construct(
        protected Security $security,
        protected string $serverId,
        protected string $apiKey,
        protected string $getOutRoot,
    ) {
    }

    private function query(string $api, array $args): bool
    {
        $targ = '';
        $ch = \curl_init();

        foreach ($args as $k => $v) {
            $targ .= $k.'-'.$v.'/';
        }

        $this->query = $this->getOutRoot.'api/s-'.$this->serverId.'/a-'.$this->security->crypt('a-'.$api.'/'.$targ, $this->apiKey);

        \curl_setopt($ch, CURLOPT_URL, $this->query);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $answer = \curl_exec($ch);
        \curl_close($ch);

        if (false !== $answer) {
            $this->data = \unserialize($answer);

            return true;
        }

        return false;
    }

    public function userExist($bindkey)
    {
        if ($this->query('userexist', ['bindkey' => $bindkey])) {
            return 'success' == $this->data['statement'];
        }

        return false;
    }

    public function confirmInscription($bindkey)
    {
        if ($this->query('confirminscription', ['bindkey' => $bindkey, 'serverid' => $this->serverId])) {
            return 'success' == $this->data['statement'];
        }

        return false;
    }

    public function confirmConnection($bindkey)
    {
        if ($this->query('confirmconnection', ['bindkey' => $bindkey, 'serverid' => $this->serverId])) {
            return 'success' == $this->data['statement'];
        }

        return false;
    }

    public function playerIsDead($bindkey, $serverId)
    {
        if ($this->query('playerisdead', ['bindkey' => $bindkey, 'serverid' => $serverId])) {
            return 'success' == $this->data['statement'];
        }

        return false;
    }

    public function sendMail($bindkey, $template)
    {
        if ($this->query('sendmail', ['bindkey' => $bindkey, 'serverid' => $this->serverId, 'template' => $template])) {
            return 'success' == $this->data['statement'];
        }

        return false;
    }

    public function sendMail2($email, $serverId, $template, $playerId)
    {
        if ($this->query('sendmail2', ['email' => $email, 'serverid' => $serverId, 'template' => $template, 'playerid' => $playerId])) {
            return 'success' == $this->data['statement'];
        }

        return false;
    }

    public function abandonServer($bindkey)
    {
        if ($this->query('abandonserver', ['bindkey' => $bindkey, 'serverid' => $this->serverId])) {
            return 'success' == $this->data['statement'];
        }

        return false;
    }
}
