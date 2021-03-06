<?php
/*
    PufferPanel - A Minecraft Server Management Panel
    Copyright (c) 2013 Dane Everitt

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see http://www.gnu.org/licenses/.
 */
namespace PufferPanel\Core;

require_once('../../src/core/core.php');

$filesIncluded = true;

if($core->auth->isLoggedIn($_SERVER['REMOTE_ADDR'], $core->auth->getCookie('pp_auth_token'), $core->auth->getCookie('pp_server_hash')) === false){
	Components\Page::redirect($core->settings->get('master_url').'index.php?login');
	exit();
}

if($core->user->hasPermission('console.view') !== true)
	Components\Page::redirect('index.php?error=no_permission');

if($core->gsd->online() === true){

    $url = "http://".$core->server->nodeData('ip').":8003/gameservers/".$core->server->getData('gsd_id')."/file/logs/latest.log";
    $context = stream_context_create(array(
    	"http" => array(
    		"method" => "GET",
    		"header" => 'X-Access-Token: '.$core->server->getData('gsd_secret'),
    		"timeout" => 3
    	)
    ));
    $content = json_decode(@file_get_contents($url, 0, $context), true);

}else
    $content = array('contents' => '');

/*
 * Display Page
 */
echo $twig->render(
		'node/console.html', array(
			'server' => array_merge($core->server->getData(), array('console_inner' => $content['contents'])),
			'node' => array(
				'ip' => $core->server->nodeData('ip')
			),
			'footer' => array(
				'queries' => Database_Initiator::getCount(),
				'seconds' => number_format((microtime(true) - $pageStartTime), 4)
			)
	));
?>
