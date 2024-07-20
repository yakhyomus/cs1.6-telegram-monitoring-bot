<?php
// In reunion.cfg, be sure to set "ServerInfoAnswerType = 0" and restart the server!

/*                Settings start                */

// Replace with your bot's token
$TOKEN  = "YOUR_TELEGRAM_BOT_TOKEN";

// Command to display information
$command    = '/info';

// Link to your domain without a trailing slash (! protocol https:// is mandatory !)
$url    = 'https://example.com';

// Folder with map screenshots (write the path to the folder here, not above)
$folder = '/maps/';

// Replace de_dust2_2x2 image with de_dust2 (removes "_2x2")
$replace2x2 = true;                                         

// Enter your server list
$servers = [
    '/public'   => '192.168.0.1:27015',
    '/server'   => '192.168.0.1:27016',
    '/csdm'     => '192.168.0.1:27017',
];

// Do not change the format. A file with this name will be created in the script folder.
$cacheFile = 'cache.json';

// Cache time, in seconds. 5 minutes * 60 = 300 seconds; (preferably not set to less than 1 minute)
$cacheTime = 300;

// Language settings
$language = [
    'welcome'       => "Welcome",
    'server'        => "Server",
    'map'           => "Map",
    'players'       => "Players",
    'bots'          => "bots",
    'error'         => "Error connecting to server",
    'nick'          => "Nick",
    'frags'         => "Frags",
    'time'          => "Time in game",
    'last_update'   => "Last update"
];


// You can output any "available" cvar on your server using the examples below. If you do not plan to use them, just comment out the respective lines.
$rulesArray = [
    'mp_timeleft'   => 'Time left until the end of the map',
    //'sv_gravity'    => 'Server gravity'
];
// PS: Changes will be displayed based on the cache file time.

/*                  Settings end                 */

require __DIR__ . '/SourceQuery/bootstrap.php';
use xPaw\SourceQuery\SourceQuery;

$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['message']['chat']['id'])) exit();

if (isset($data['message']['new_chat_members'])) {
    $user = $data['message']['from'];
    $name = $user['first_name'];
    if($user['last_name']) {
        $name .= ' ' . $user['last_name'];
    }
    file_get_contents("https://api.telegram.org/bot$TOKEN/sendMessage?" . 
        http_build_query([
            'chat_id'               => $data['message']['chat']['id'],
            'reply_to_message_id'   => $data['message']['message_id'],
            'parse_mode'            => "HTML",
            'text'                  => "{$language['welcome']} <a href='tg://user?id={$user['id']}'>$name</a>!\n",
        ])
    );
    exit();
}

if (!empty($data['message']['text'])) {
    $text = $data['message']['text'];
    $info = '';
    foreach ($servers as $key => $server) {
        if (mb_stripos($text, $command) !== false || $text === '/start') {
            $Query = new SourceQuery();
            try {
                $Query->Connect(explode(':', $server)[0], explode(':', $server)[1], 3, SourceQuery::GOLDSOURCE);
                $infos = $Query->GetInfo();
                $info .= "{$infos['HostName']}\nIP: <code>{$server}</code>\n";
                $info .= "{$language['map']}: {$infos['Map']}\n";
                $info .= "{$language['players']}: {$infos['Players']} / {$infos['MaxPlayers']} ({$infos['Bots']} {$language['bots']}) {$key}\n\n";
            } catch (Exception $e) {
                $info .= $server . ' — ' .$e->getMessage();
            } finally {
                $Query->Disconnect();
            }
        } elseif (mb_stripos($text, $key) !== false) {
            if (!file_exists($cacheFile) || (time() - filemtime($cacheFile)) > $cacheTime) {
                $dataFrom[$server] = getGameDataFromServer($server);
                $jsonData = json_encode($dataFrom);
                file_put_contents($cacheFile, $jsonData);
            } else {
                $jsonData = file_get_contents($cacheFile);
                $cacheData = json_decode($jsonData, true);
            
                if (!array_key_exists($server, $cacheData)) {
                    $cacheData[$server] = getGameDataFromServer($server);
                    $jsonData = json_encode($cacheData);
                    file_put_contents($cacheFile, $jsonData);
                }
            
                $dataFrom[$server] = $cacheData[$server];
            }

            file_get_contents("https://api.telegram.org/bot$TOKEN/sendPhoto?" . 
                http_build_query([
                    'chat_id'                   => $data['message']['chat']['id'],
                    'photo'                     => $dataFrom[$server]['image'] ?? $url . $folder . 'noimage.jpg',
                    'caption'                   => $dataFrom[$server]['caption'] ?? "{$language['error']}: <code>$server</code>\n",
                    'parse_mode'                => "HTML",
                    'disable_web_page_preview'  => true,
                    'reply_to_message_id'       => $data['message']['message_id']
                ])
            );
        
            exit();
        }
    }

    file_get_contents("https://api.telegram.org/bot$TOKEN/sendMessage?" . 
        http_build_query([
            'chat_id'                   => $data['message']['chat']['id'],
            'text'                      => $info,
            'parse_mode'                => "HTML",
            'disable_web_page_preview'  => true,
            'reply_to_message_id'       => $data['message']['message_id']
        ])
    );

    exit();
}

function getGameDataFromServer($server) {
    global $url, $folder, $replace2x2, $rulesArray, $language;
    $Query = new SourceQuery();
    try {
        $Query->Connect(explode(':', $server)[0], explode(':', $server)[1], 3, SourceQuery::GOLDSOURCE);
        $infos = $Query->GetInfo();
        $caption = "{$language['server']}: <code>{$infos['HostName']}</code>\n" .
           "IP: <code>{$server}</code>\n" .
           "{$language['map']}: <code>{$infos['Map']}</code>\n" .
           "{$language['players']}: {$infos['Players']} / {$infos['MaxPlayers']} ({$infos['Bots']} {$language['bots']})\n";
        
        if (!empty($rulesArray)) {
            $rules = $Query->GetRules();
            foreach ($rulesArray as $key => $rule) {
                if (isset($rules[$key])) {
                    $caption .= "$rule: <code>{$rules[$key]}</code>\n";
                }
            }
        }
        
        $caption .= "\n";
        $playersInfos = $Query->GetPlayers();
        if ($infos['Players']) {
            $caption .= "<strong>{$language['nick']} ({$language['frags']} | {$language['time']})</strong>\n";
            foreach ($playersInfos as $player) {
                $caption .= ++$player['Id'] . ". 👤 <code>{$player['Name']}</code> " .
                    "(<code>{$player['Frags']}</code> | <code>{$player['TimeF']}</code>)\n";
            }
        }

        $caption .= "\n{$language['last_update']}: <code>" . date('d.m.y H:i:s') . "</code>";

        $image = $url . $folder . 'noimage.jpg';
        $map = $infos['Map'];
        if ($replace2x2 && strpos($map, '_2x2')) {
            $map = strstr($map, '_2x2', true);
        }
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $folder . $map . '.jpg')) {
            $image = $url . $folder . $map . '.jpg';
        }
        return [
            'caption' => $caption,
            'image' => $image
        ];
    } catch (Exception $e) {
        return [
            'error' => $e->getMessage()
        ];
    } finally {
        $Query->Disconnect();
    }
}
// by yamus
